<?php
if (!defined('MB_RUNNING')) exit;
?>

<div class="row">
    <div class="col s12">
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=migrations" class="breadcrumb">Database Migrations</a>
                </div>
            </div>
        </nav>
        <h4><i class="fas fa-database"></i> Database Migrations</h4>
        <p class="grey-text">Inspect migration history, adopt verified existing schemas, and apply reviewed migrations.</p>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div id="migration-database" class="card-panel grey lighten-4" aria-live="polite">
            Loading database and migration status...
        </div>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div class="card amber lighten-5">
            <div class="card-content">
                <span class="card-title"><i class="fas fa-exclamation-triangle"></i> Before changing the database</span>
                <ul class="browser-default">
                    <li>Create and verify a current database backup before adoption or applying migrations.</li>
                    <li>MySQL DDL can partially persist after an error; a failed migration may require manual repair.</li>
                    <li>Baseline adoption records migration history only. It does not run baseline SQL or change app data.</li>
                    <li>Current baseline verification checks table/column names and foreign keys only. It does not establish full schema equivalence (types, nullability, defaults, indexes, constraints, spatial definitions, or engines). Manually compare the complete schema before adoption.</li>
                    <li>For an existing populated database, adopt baselines in dependency order: Admin, Stitch, then NeighborHub. Do not apply a fresh baseline to an existing schema.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div class="right-align" style="margin-bottom: 1rem;">
            <button id="migration-refresh" type="button" class="btn-flat waves-effect">
                <i class="fas fa-sync-alt left"></i>Refresh status
            </button>
            <button id="migration-apply-all" type="button" class="btn red darken-1 waves-effect waves-light" disabled>
                <i class="fas fa-play left"></i>Apply pending in dependency order
            </button>
        </div>
        <div id="migration-apps" aria-live="polite"></div>
    </div>
</div>

<div id="migration-confirm-modal" class="modal mb-modal-fixed">
    <div class="modal-content">
        <h5 id="migration-confirm-title">Confirm migration operation</h5>
        <p id="migration-confirm-description"></p>
        <div id="migration-confirm-warnings" class="card-panel amber lighten-5"></div>
        <p>
            <label>
                <input id="migration-backup-confirmed" type="checkbox">
                <span>I have a current, usable database backup.</span>
            </label>
        </p>
        <p id="migration-schema-review-row" style="display: none;">
            <label>
                <input id="migration-schema-reviewed" type="checkbox">
                <span>I manually reviewed the complete live schema against this baseline.</span>
            </label>
        </p>
        <div class="input-field">
            <input id="migration-database-confirmation" type="text" autocomplete="off">
            <label for="migration-database-confirmation">Type the database name to confirm</label>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="modal-close btn-flat waves-effect">Cancel</button>
        <button id="migration-confirm-submit" type="button" class="btn red darken-1 waves-effect waves-light" disabled>
            Confirm and continue
        </button>
    </div>
</div>

<script>
(function () {
    const appsElement = document.getElementById('migration-apps');
    const databaseElement = document.getElementById('migration-database');
    const refreshButton = document.getElementById('migration-refresh');
    const applyAllButton = document.getElementById('migration-apply-all');
    const modalElement = document.getElementById('migration-confirm-modal');
    const confirmModal = M.Modal.init(modalElement, { dismissible: false });
    const confirmButton = document.getElementById('migration-confirm-submit');
    const backupCheckbox = document.getElementById('migration-backup-confirmed');
    const schemaCheckbox = document.getElementById('migration-schema-reviewed');
    const confirmationInput = document.getElementById('migration-database-confirmation');
    const schemaReviewRow = document.getElementById('migration-schema-review-row');
    let statusData = null;
    let pendingOperation = null;

    function toast(message, classes) {
        M.toast({html: message, classes: classes || ''});
    }

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function apiError(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.error) return xhr.responseJSON.error;
        try {
            const response = JSON.parse(xhr.responseText || '{}');
            if (response.error) return response.error;
        } catch (error) {
            return xhr.statusText || 'The server returned an unreadable response.';
        }
        return xhr.statusText || 'The migration request failed.';
    }

    function requestStatus() {
        refreshButton.disabled = true;
        databaseElement.textContent = 'Loading database and migration status...';
        appsElement.replaceChildren();
        mb.ajax({
            url: '?api=admin&action=migration_status',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (!response.success) {
                    databaseElement.textContent = response.error || 'Unable to read migration status.';
                    return;
                }
                statusData = response;
                renderStatus(response);
            },
            error: function (xhr) {
                statusData = null;
                applyAllButton.disabled = true;
                databaseElement.textContent = 'Unable to read migration status: ' + apiError(xhr);
            },
            complete: function () {
                refreshButton.disabled = false;
            }
        });
    }

    function hasIntegrityProblem(appStatus) {
        return appStatus.untracked_migrations.length > 0
            || appStatus.migrations.some(function (migration) {
                return migration.checksum_matches === false;
            });
    }

    function renderStatus(response) {
        const database = response.database;
        databaseElement.replaceChildren();
        const target = element('strong', '', database.environment.toUpperCase() + ' / ' + database.driver + ' / ' + database.database);
        databaseElement.append('Target database: ', target);
        if (database.environment === 'production') {
            databaseElement.classList.add('red', 'lighten-4');
            databaseElement.classList.remove('grey', 'lighten-4');
        } else {
            databaseElement.classList.remove('red', 'lighten-4');
            databaseElement.classList.add('grey', 'lighten-4');
        }

        const allStatuses = Object.values(response.apps);
        const allHealthy = allStatuses.every(function (appStatus) {
            return !hasIntegrityProblem(appStatus);
        });
        const freshBaselineChecksPass = allStatuses.every(function (appStatus) {
            const firstMigration = appStatus.migrations[0];
            return !firstMigration || firstMigration.applied || !firstMigration.id.endsWith('_baseline')
                || (appStatus.baseline_tables_checked && appStatus.baseline_tables_existing.length === 0);
        });
        const anyPending = allStatuses.some(function (appStatus) {
            return appStatus.migrations.some(function (migration) {
                return !migration.applied;
            });
        });
        applyAllButton.disabled = !allHealthy || !freshBaselineChecksPass || !anyPending;
        applyAllButton.dataset.app = 'all';

        appsElement.replaceChildren();
        Object.entries(response.apps).forEach(function (entry) {
            const appName = entry[0];
            const appStatus = entry[1];
            appsElement.appendChild(renderApp(appName, appStatus, database.database));
        });
    }

    function renderApp(appName, appStatus, databaseName) {
        const card = element('div', 'card');
        const content = element('div', 'card-content');
        const heading = element('span', 'card-title');
        heading.appendChild(element('i', 'fas fa-cube'));
        heading.append(' ' + appName);
        content.appendChild(heading);

        const historyMessage = appStatus.history_exists
            ? 'Migration history table is present.'
            : 'Migration history table does not exist; status inspection did not create it.';
        content.appendChild(element('p', 'grey-text', historyMessage));

        const integrityProblem = hasIntegrityProblem(appStatus);
        if (integrityProblem) {
            const warning = element('div', 'card-panel red lighten-4');
            warning.textContent = 'Migration history is inconsistent. Resolve missing files or checksum drift before running operations.';
            content.appendChild(warning);
        }
        const firstMigration = appStatus.migrations[0];
        const baselineCollision = firstMigration && !firstMigration.applied
            && firstMigration.id.endsWith('_baseline')
            && appStatus.baseline_tables_existing.length > 0;
        if (baselineCollision) {
            const warning = element('div', 'card-panel amber lighten-4');
            warning.textContent = 'Existing baseline tables were detected (' + appStatus.baseline_tables_existing.join(', ')
                + '). Fresh baseline application is disabled; review the complete schema before adopting.';
            content.appendChild(warning);
        }

        const table = element('table', 'striped responsive-table');
        const head = document.createElement('thead');
        const headerRow = document.createElement('tr');
        ['Migration', 'State', 'Applied at', 'Checksum'].forEach(function (label) {
            headerRow.appendChild(element('th', '', label));
        });
        head.appendChild(headerRow);
        table.appendChild(head);
        const body = document.createElement('tbody');

        appStatus.migrations.forEach(function (migration) {
            const row = document.createElement('tr');
            row.appendChild(element('td', '', migration.id));
            row.appendChild(element('td', '', migration.applied ? 'Applied' : 'Pending'));
            row.appendChild(element('td', '', migration.applied_at || '—'));
            const checksum = migration.checksum_matches === null
                ? 'Not recorded'
                : (migration.checksum_matches ? 'Matches' : 'MISMATCH');
            row.appendChild(element('td', migration.checksum_matches === false ? 'red-text text-darken-2' : '', checksum));
            body.appendChild(row);
        });

        appStatus.untracked_migrations.forEach(function (migrationId) {
            const row = document.createElement('tr');
            row.appendChild(element('td', 'red-text', migrationId));
            row.appendChild(element('td', 'red-text', 'Applied file missing'));
            row.appendChild(element('td', '', '—'));
            row.appendChild(element('td', 'red-text', 'Unavailable'));
            body.appendChild(row);
        });

        if (appStatus.migrations.length === 0 && appStatus.untracked_migrations.length === 0) {
            const row = document.createElement('tr');
            row.appendChild(element('td', 'grey-text', 'No migration files found.'));
            body.appendChild(row);
        }
        table.appendChild(body);
        content.appendChild(table);

        const actions = element('div', 'card-action');
        const hasApplied = appStatus.migrations.some(function (migration) {
            return migration.applied;
        });
        const canAdopt = !integrityProblem && !hasApplied && firstMigration
            && firstMigration.id.endsWith('_baseline');
        if (canAdopt) {
            const adoptButton = element('button', 'btn orange darken-2 waves-effect waves-light', 'Adopt existing baseline');
            adoptButton.type = 'button';
            adoptButton.dataset.operation = 'adopt';
            adoptButton.dataset.app = appName;
            adoptButton.dataset.database = databaseName;
            actions.appendChild(adoptButton);
        }

        const hasPending = appStatus.migrations.some(function (migration) {
            return !migration.applied;
        });
        if (hasPending) {
            const applyButton = element('button', 'btn red darken-1 waves-effect waves-light', 'Apply pending');
            applyButton.type = 'button';
            applyButton.dataset.operation = 'apply';
            applyButton.dataset.app = appName;
            applyButton.dataset.database = databaseName;
            applyButton.disabled = integrityProblem || baselineCollision
                || (firstMigration && !firstMigration.applied && firstMigration.id.endsWith('_baseline')
                    && !appStatus.baseline_tables_checked);
            actions.appendChild(applyButton);
        }

        if (actions.childNodes.length > 0) content.appendChild(actions);
        card.appendChild(content);
        return card;
    }

    function openConfirmation(operation, appName) {
        if (!statusData) return;
        const databaseName = statusData.database.database;
        pendingOperation = {operation: operation, app: appName, database: databaseName};
        document.getElementById('migration-confirm-title').textContent =
            operation === 'adopt' ? 'Adopt existing schema baseline?' : 'Apply pending database migrations?';
        document.getElementById('migration-confirm-description').textContent =
            operation === 'adopt'
                ? 'Adoption only records the baseline in migration history; no baseline SQL or application data will be changed. This action is for ' + appName + '.'
                : 'Reviewed migration files will run in order for ' + (appName === 'all' ? 'Admin, Stitch, then NeighborHub' : appName) + '.';
        document.getElementById('migration-confirm-warnings').textContent =
            statusData.database.environment.toUpperCase() + ' database target: ' + databaseName
            + '. MySQL schema changes can partially persist if a migration fails.';
        schemaReviewRow.style.display = operation === 'adopt' ? '' : 'none';
        backupCheckbox.checked = false;
        schemaCheckbox.checked = false;
        confirmationInput.value = '';
        confirmButton.disabled = true;
        M.updateTextFields();
        confirmModal.open();
    }

    function updateConfirmButton() {
        const backupChecked = backupCheckbox.checked;
        const schemaReady = pendingOperation && pendingOperation.operation === 'adopt'
            ? schemaCheckbox.checked
            : true;
        confirmButton.disabled = !pendingOperation || !backupChecked || !schemaReady
            || confirmationInput.value !== pendingOperation.database;
    }

    function submitOperation() {
        if (!pendingOperation) return;
        const operation = pendingOperation;
        confirmButton.disabled = true;
        confirmButton.textContent = 'Working...';
        const action = operation.operation === 'adopt' ? 'migration_adopt_baseline' : 'migration_apply';
        const payload = {
            app: operation.app,
            confirm_database: operation.database,
            backup_confirmed: backupCheckbox.checked
        };
        if (operation.operation === 'adopt') payload.schema_reviewed = schemaCheckbox.checked;

        mb.ajax({
            url: '?api=admin&action=' + action,
            method: 'POST',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function (response) {
                if (!response.success) {
                    toast(response.error || 'Migration operation failed.', 'red');
                    return;
                }
                toast(operation.operation === 'adopt'
                    ? 'Baseline adopted. Application tables and data were not changed.'
                    : 'Migration request completed.', 'green');
                confirmModal.close();
                requestStatus();
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                toast(apiError(xhr) + (response.correlation_id ? ' (Request ' + response.correlation_id + ')' : ''), 'red');
                confirmModal.close();
                requestStatus();
            },
            complete: function () {
                confirmButton.innerHTML = 'Confirm and continue';
                pendingOperation = null;
                updateConfirmButton();
            }
        });
    }

    refreshButton.addEventListener('click', requestStatus);
    applyAllButton.addEventListener('click', function () {
        openConfirmation('apply', 'all');
    });
    appsElement.addEventListener('click', function (event) {
        const button = event.target.closest('button[data-operation]');
        if (button) openConfirmation(button.dataset.operation, button.dataset.app);
    });
    [backupCheckbox, schemaCheckbox, confirmationInput].forEach(function (control) {
        control.addEventListener('input', updateConfirmButton);
        control.addEventListener('change', updateConfirmButton);
    });
    confirmButton.addEventListener('click', submitOperation);
    requestStatus();
})();
</script>
