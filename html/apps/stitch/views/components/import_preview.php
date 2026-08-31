<?php
// components/import_preview.php
if (!empty($data)): ?>
    <table class="highlight">
        <thead>
            <tr>
                <th><label><input type="checkbox" id="select-all-import" checked /><span></span></label></th>
                <th>Ancestor & Migration Trail</th>
                <th>Primary Era</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $index => $person): ?>
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" class="import-person-check" value="<?= $index ?>" checked />
                            <span></span>
                        </label>
                    </td>
                    <td>
                        <span style="font-size: 1.1rem; font-weight: 500;">
                            <?= htmlspecialchars($person['name']) ?>
                        </span>
                        <div class="trail-container" style="margin-top: 8px;">
                            <?php foreach ($person['all_events'] as $tag => $ev): ?>
                                <?php if (!empty($ev['PLAC'])): ?>
                                    <span class="chip tiny purple white-text" title="<?= $tag ?>">
                                        <i class="tiny material-icons">location_on</i>
                                        <?= $tag ?>: <?= htmlspecialchars($ev['PLAC']) ?> 
                                        (<?= $ev['DATE'] ?? '???' ?>)
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <code class="grey-text"><?= date('Y-m-d', strtotime($person['date'])) ?></code>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
  <p class="center">No records found to import.</p>
<?php endif; ?>