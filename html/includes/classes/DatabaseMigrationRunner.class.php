<?php
if (!defined('MB_RUNNING')) exit;

class DatabaseMigrationRunner
{
    private PDO $db;
    private string $appName;
    private string $migrationDirectory;

    public function __construct(PDO $db, string $appName, string $migrationDirectory)
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $appName)) {
            throw new InvalidArgumentException('Migration app name must be a lowercase identifier.');
        }

        $this->db = $db;
        $this->appName = $appName;
        $this->migrationDirectory = $migrationDirectory;
    }

    public function getStatus(): array
    {
        $inspection = $this->inspectStatus();
        if ($inspection['untracked_migrations'] !== []) {
            throw new RuntimeException(
                "Applied migration file is missing for {$this->appName}: "
                . implode(', ', $inspection['untracked_migrations'])
            );
        }

        foreach ($inspection['migrations'] as $migration) {
            if ($migration['checksum_matches'] === false) {
                throw new RuntimeException("Applied migration {$this->appName}/{$migration['id']} has changed.");
            }
        }

        return $inspection['migrations'];
    }

    public function inspectStatus(): array
    {
        $migrations = $this->loadMigrations();
        $historyExists = $this->historyTableExists();
        $applied = $historyExists ? $this->getAppliedMigrations() : [];
        $status = [];

        foreach ($migrations as $id => $migration) {
            $appliedMigration = $applied[$id] ?? null;
            $status[] = [
                'id' => $id,
                'applied' => $appliedMigration !== null,
                'applied_at' => $appliedMigration['applied_at'] ?? null,
                'checksum_matches' => $appliedMigration === null
                    ? null
                    : hash_equals($appliedMigration['checksum'], $migration['checksum']),
            ];
        }

        $untrackedMigrations = [];
        foreach ($applied as $id => $appliedMigration) {
            if (!isset($migrations[$id])) {
                $untrackedMigrations[] = $id;
            }
        }

        $baselineTablesChecked = false;
        $baselineTablesExisting = [];
        if ($migrations !== []) {
            $firstMigration = reset($migrations);
            if (str_ends_with((string)array_key_first($migrations), '_baseline')) {
                $tables = $firstMigration['definition']['tables'] ?? null;
                if (is_array($tables) && $tables !== []) {
                    $baselineTablesExisting = $this->findExistingTables($tables);
                    $baselineTablesChecked = true;
                }
            }
        }

        return [
            'app' => $this->appName,
            'history_exists' => $historyExists,
            'migrations' => $status,
            'untracked_migrations' => $untrackedMigrations,
            'baseline_tables_checked' => $baselineTablesChecked,
            'baseline_tables_existing' => $baselineTablesExisting,
        ];
    }

    public function migrate(): array
    {
        $this->acquireLock();

        try {
            $migrations = $this->loadMigrations();
            $applied = $this->historyTableExists() ? $this->getAppliedMigrations() : [];
            $results = [];

            foreach ($migrations as $id => $migration) {
                if (isset($applied[$id])) {
                    if (!hash_equals($applied[$id]['checksum'], $migration['checksum'])) {
                        throw new RuntimeException("Applied migration {$this->appName}/{$id} has changed.");
                    }
                    continue;
                }

                if ($applied === [] && str_ends_with($id, '_baseline')) {
                    $this->assertBaselineTablesAreAbsent($migration['definition']);
                }

                foreach ($applied as $appliedId => $appliedMigration) {
                    if (!isset($migrations[$appliedId])) {
                        throw new RuntimeException("Applied migration file {$this->appName}/{$appliedId} is missing.");
                    }
                    if (!hash_equals($appliedMigration['checksum'], $migrations[$appliedId]['checksum'])) {
                        throw new RuntimeException("Applied migration {$this->appName}/{$appliedId} has changed.");
                    }
                }

                $this->ensureHistoryTable();
                $this->db->beginTransaction();
                try {
                    ($migration['definition']['up'])($this->db);

                    $statement = $this->db->prepare(
                        'INSERT INTO mb_schema_migrations (app, migration, checksum) VALUES (?, ?, ?)'
                    );
                    $statement->execute([$this->appName, $id, $migration['checksum']]);

                    if ($this->db->inTransaction()) {
                        $this->db->commit();
                    }
                } catch (Throwable $exception) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    throw new RuntimeException(
                        "Migration {$this->appName}/{$id} failed: {$exception->getMessage()}",
                        0,
                        $exception
                    );
                }

                $results[] = $id;
                $applied[$id] = [
                    'checksum' => $migration['checksum'],
                    'applied_at' => null,
                ];
            }

            return $results;
        } finally {
            $this->releaseLock();
        }
    }

    public function adoptBaseline(): string
    {
        $this->acquireLock();

        try {
            $migrations = $this->loadMigrations();
            if ($migrations === []) {
                throw new RuntimeException("No migration files found for {$this->appName}.");
            }

            $id = array_key_first($migrations);
            if (!str_ends_with($id, '_baseline')) {
                throw new RuntimeException("The first migration for {$this->appName} must be a baseline.");
            }

            if ($this->historyTableExists() && $this->getAppliedMigrations() !== []) {
                throw new RuntimeException("Cannot adopt {$this->appName} baseline after migrations have already been recorded.");
            }

            $verify = $migrations[$id]['definition']['verify'] ?? null;
            if (!is_callable($verify)) {
                throw new RuntimeException("Baseline {$this->appName}/{$id} must define a verify callback before it can be adopted.");
            }

            $verify($this->db);

            $this->ensureHistoryTable();
            $this->db->beginTransaction();
            try {
                $statement = $this->db->prepare(
                    'INSERT INTO mb_schema_migrations (app, migration, checksum) VALUES (?, ?, ?)'
                );
                $statement->execute([$this->appName, $id, $migrations[$id]['checksum']]);
                $this->db->commit();
            } catch (Throwable $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw new RuntimeException(
                    "Unable to record adopted baseline {$this->appName}/{$id}: {$exception->getMessage()}",
                    0,
                    $exception
                );
            }

            return $id;
        } finally {
            $this->releaseLock();
        }
    }

    public static function assertSchema(PDO $db, array $expectedTables, array $expectedForeignKeys = []): void
    {
        if ($expectedTables === []) {
            throw new InvalidArgumentException('Baseline schema verification requires at least one table.');
        }

        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $actualColumns = [];
        $actualForeignKeys = [];

        if ($driver === 'mysql') {
            $tableNames = array_keys($expectedTables);
            $placeholders = implode(',', array_fill(0, count($tableNames), '?'));
            $columnsStatement = $db->prepare(
                "SELECT TABLE_NAME, COLUMN_NAME
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME IN ({$placeholders})
                 ORDER BY TABLE_NAME, ORDINAL_POSITION"
            );
            $columnsStatement->execute($tableNames);
            foreach ($columnsStatement->fetchAll(PDO::FETCH_ASSOC) as $column) {
                $actualColumns[$column['TABLE_NAME']][] = $column['COLUMN_NAME'];
            }

            $foreignKeyStatement = $db->prepare(
                "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                   AND TABLE_NAME IN ({$placeholders})"
            );
            $foreignKeyStatement->execute($tableNames);
            foreach ($foreignKeyStatement->fetchAll(PDO::FETCH_ASSOC) as $foreignKey) {
                $actualForeignKeys[$foreignKey['TABLE_NAME']][] = [
                    $foreignKey['COLUMN_NAME'],
                    $foreignKey['REFERENCED_TABLE_NAME'],
                    $foreignKey['REFERENCED_COLUMN_NAME'],
                ];
            }
        } elseif ($driver === 'sqlite') {
            foreach (array_keys($expectedTables) as $table) {
                $quotedTable = '"' . str_replace('"', '""', $table) . '"';
                $exists = $db->query(
                    "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = " . $db->quote($table)
                )->fetchColumn();
                if ($exists === false) {
                    continue;
                }

                $actualColumns[$table] = array_column(
                    $db->query("PRAGMA table_info({$quotedTable})")->fetchAll(PDO::FETCH_ASSOC),
                    'name'
                );
                foreach ($db->query("PRAGMA foreign_key_list({$quotedTable})")->fetchAll(PDO::FETCH_ASSOC) as $foreignKey) {
                    $actualForeignKeys[$table][] = [
                        $foreignKey['from'],
                        $foreignKey['table'],
                        $foreignKey['to'],
                    ];
                }
            }
        } else {
            throw new RuntimeException("Schema adoption is not supported for PDO driver '{$driver}'.");
        }

        $mismatches = [];
        foreach ($expectedTables as $table => $columns) {
            $expectedColumns = array_values($columns);
            $foundColumns = $actualColumns[$table] ?? [];
            sort($expectedColumns, SORT_STRING);
            sort($foundColumns, SORT_STRING);
            if ($foundColumns !== $expectedColumns) {
                $missing = array_values(array_diff($expectedColumns, $foundColumns));
                $unexpected = array_values(array_diff($foundColumns, $expectedColumns));
                $details = [];
                if ($missing !== []) {
                    $details[] = 'missing columns: ' . implode(', ', $missing);
                }
                if ($unexpected !== []) {
                    $details[] = 'unexpected columns: ' . implode(', ', $unexpected);
                }
                if ($details === []) {
                    $details[] = 'column order differs';
                }
                $mismatches[] = "{$table} (" . implode('; ', $details) . ')';
            }
        }

        foreach (array_keys($expectedTables) as $table) {
            $expected = $expectedForeignKeys[$table] ?? [];
            $actual = $actualForeignKeys[$table] ?? [];
            sort($expected);
            sort($actual);
            if ($actual !== $expected) {
                $mismatches[] = "{$table} (foreign-key relationships differ)";
            }
        }

        if ($mismatches !== []) {
            throw new RuntimeException(
                'Existing schema does not match the baseline: ' . implode('; ', $mismatches)
            );
        }
    }

    public function rollbackLast(): ?string
    {
        $this->acquireLock();

        try {
            $this->ensureHistoryTable();
            $statement = $this->db->prepare(
                'SELECT migration, checksum
                 FROM mb_schema_migrations
                 WHERE app = ?
                 ORDER BY migration DESC
                 LIMIT 1'
            );
            $statement->execute([$this->appName]);
            $appliedMigration = $statement->fetch(PDO::FETCH_ASSOC);
            $statement->closeCursor();
            if ($appliedMigration === false) {
                return null;
            }

            $migrations = $this->loadMigrations();
            $id = $appliedMigration['migration'];
            if (!isset($migrations[$id])) {
                throw new RuntimeException("Applied migration file {$this->appName}/{$id} is missing.");
            }
            if (!hash_equals($appliedMigration['checksum'], $migrations[$id]['checksum'])) {
                throw new RuntimeException("Applied migration {$this->appName}/{$id} has changed.");
            }
            if (!isset($migrations[$id]['definition']['down'])) {
                throw new RuntimeException("Migration {$this->appName}/{$id} does not define a down operation.");
            }

            $this->db->beginTransaction();
            try {
                ($migrations[$id]['definition']['down'])($this->db);
                $delete = $this->db->prepare(
                    'DELETE FROM mb_schema_migrations WHERE app = ? AND migration = ?'
                );
                $delete->execute([$this->appName, $id]);

                if ($this->db->inTransaction()) {
                    $this->db->commit();
                }
            } catch (Throwable $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw new RuntimeException(
                    "Rollback of {$this->appName}/{$id} failed: {$exception->getMessage()}",
                    0,
                    $exception
                );
            }

            return $id;
        } finally {
            $this->releaseLock();
        }
    }

    private function ensureHistoryTable(): void
    {
        $engine = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            : '';
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS mb_schema_migrations (
                app VARCHAR(64) NOT NULL,
                migration VARCHAR(190) NOT NULL,
                checksum CHAR(64) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (app, migration)
            )' . $engine
        );
    }

    private function historyTableExists(): bool
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $statement = $this->db->prepare(
                'SELECT 1 FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
            );
            $statement->execute(['mb_schema_migrations']);
            return $statement->fetchColumn() !== false;
        }
        if ($driver === 'sqlite') {
            $statement = $this->db->prepare(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1"
            );
            $statement->execute(['mb_schema_migrations']);
            return $statement->fetchColumn() !== false;
        }

        throw new RuntimeException("Migration status is not supported for PDO driver '{$driver}'.");
    }

    private function assertBaselineTablesAreAbsent(array $definition): void
    {
        $tables = $definition['tables'] ?? null;
        if (!is_array($tables) || $tables === []) {
            throw new RuntimeException(
                "Baseline for {$this->appName} must declare its tables before it can be applied to a fresh database."
            );
        }

        $existingTables = $this->findExistingTables($tables);
        if ($existingTables !== []) {
            throw new RuntimeException(
                "Baseline for {$this->appName} was not applied because these tables already exist: "
                . implode(', ', $existingTables)
                . '. Verify the live schema and use baseline adoption instead.'
            );
        }
    }

    private function findExistingTables(array $tables): array
    {
        $existingTables = [];
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        foreach ($tables as $table) {
            if (!is_string($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                throw new RuntimeException("Baseline for {$this->appName} declares an invalid table name.");
            }

            if ($driver === 'mysql') {
                $statement = $this->db->prepare(
                    'SELECT 1 FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
                );
                $statement->execute([$table]);
                $exists = $statement->fetchColumn() !== false;
            } elseif ($driver === 'sqlite') {
                $statement = $this->db->prepare(
                    "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1"
                );
                $statement->execute([$table]);
                $exists = $statement->fetchColumn() !== false;
            } else {
                throw new RuntimeException("Baseline application is not supported for PDO driver '{$driver}'.");
            }

            if ($exists) {
                $existingTables[] = $table;
            }
        }

        return $existingTables;
    }

    private function loadMigrations(): array
    {
        if (!is_dir($this->migrationDirectory)) {
            return [];
        }

        $files = glob(rtrim($this->migrationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php');
        if ($files === false) {
            throw new RuntimeException("Unable to read migrations from {$this->migrationDirectory}.");
        }
        sort($files, SORT_STRING);

        $migrations = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (!preg_match('/^([0-9]{14}_[a-z0-9][a-z0-9_-]*)\.php$/', $filename, $matches)) {
                throw new RuntimeException("Invalid migration filename: {$filename}.");
            }

            $definition = require $file;
            if (!is_array($definition) || !isset($definition['up']) || !is_callable($definition['up'])) {
                throw new RuntimeException("Migration {$filename} must return an array with a callable up operation.");
            }
            if (isset($definition['down']) && !is_callable($definition['down'])) {
                throw new RuntimeException("Migration {$filename} has an invalid down operation.");
            }
            if (isset($definition['verify']) && !is_callable($definition['verify'])) {
                throw new RuntimeException("Migration {$filename} has an invalid verify operation.");
            }
            if (isset($definition['tables']) && !is_array($definition['tables'])) {
                throw new RuntimeException("Migration {$filename} has an invalid tables declaration.");
            }

            $id = $matches[1];
            $migrations[$id] = [
                'definition' => $definition,
                'checksum' => hash_file('sha256', $file),
            ];
        }

        return $migrations;
    }

    private function getAppliedMigrations(): array
    {
        $statement = $this->db->prepare(
            'SELECT migration, checksum, applied_at
             FROM mb_schema_migrations
             WHERE app = ?
             ORDER BY migration'
        );
        $statement->execute([$this->appName]);
        $applied = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $applied[$row['migration']] = [
                'checksum' => $row['checksum'],
                'applied_at' => $row['applied_at'],
            ];
        }

        return $applied;
    }

    private function acquireLock(): void
    {
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        $statement = $this->db->prepare('SELECT GET_LOCK(?, 30)');
        $statement->execute(['mb:migrations:' . $this->appName]);
        if ((int)$statement->fetchColumn() !== 1) {
            throw new RuntimeException("Unable to acquire migration lock for {$this->appName}.");
        }
    }

    private function releaseLock(): void
    {
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        $statement = $this->db->prepare('SELECT RELEASE_LOCK(?)');
        $statement->execute(['mb:migrations:' . $this->appName]);
    }
}
