<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/includes/classes/DatabaseMigrationRunner.class.php';

class DatabaseMigrationRunnerTest extends TestCase
{
    private string $migrationDirectory;
    private PDO $db;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite driver is not available.');
        }

        $this->migrationDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mb-migrations-' . bin2hex(random_bytes(8));
        mkdir($this->migrationDirectory);
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->migrationDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->migrationDirectory);
    }

    public function testMigrateAppliesPendingFilesOnceAndReportsStatus(): void
    {
        $this->writeMigration(
            '20261023160000_create_example.php',
            "return ['up' => static function (PDO \$db): void { \$db->exec('CREATE TABLE example (id INTEGER PRIMARY KEY)'); }];"
        );
        $runner = $this->createRunner();

        self::assertSame(['20261023160000_create_example'], $runner->migrate());
        self::assertSame([], $runner->migrate());
        $status = $runner->getStatus();
        self::assertCount(1, $status);
        self::assertSame('20261023160000_create_example', $status[0]['id']);
        self::assertTrue($status[0]['applied']);
        self::assertNotEmpty($status[0]['applied_at']);
        self::assertSame(
            1,
            (int)$this->db->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'example'")->fetchColumn()
        );
    }

    public function testFailedMigrationIsNotRecordedAndStopsTheBatch(): void
    {
        $this->writeMigration(
            '20261023160000_fail.php',
            "return ['up' => static function (PDO \$db): void { \$db->exec('CREATE TABLE broken'); throw new RuntimeException('expected failure'); }];"
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Migration test/20261023160000_fail failed');
        $runner = $this->createRunner();
        try {
            $runner->migrate();
        } finally {
            $recordCount = (int)$this->db
                ->query("SELECT COUNT(*) FROM mb_schema_migrations WHERE app = 'test'")
                ->fetchColumn();
            self::assertSame(0, $recordCount);
        }
    }

    public function testChangedAppliedMigrationIsRejected(): void
    {
        $filename = '20261023160000_create_example.php';
        $this->writeMigration(
            $filename,
            "return ['up' => static function (PDO \$db): void { \$db->exec('CREATE TABLE example (id INTEGER PRIMARY KEY)'); }];"
        );
        $runner = $this->createRunner();
        $runner->migrate();
        $this->writeMigration($filename, "return ['up' => static function (PDO \$db): void {}];");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has changed');
        $runner->getStatus();
    }

    public function testInspectStatusDoesNotCreateMigrationHistoryTable(): void
    {
        $this->writeMigration(
            '20261023160000_create_example.php',
            "return ['up' => static function (PDO \$db): void {}];"
        );

        $status = $this->createRunner()->inspectStatus();

        self::assertFalse($status['history_exists']);
        self::assertFalse($status['migrations'][0]['applied']);
        self::assertSame(
            0,
            (int)$this->db->query(
                "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'mb_schema_migrations'"
            )->fetchColumn()
        );
    }

    public function testInspectStatusReportsHistoryRowsWithoutMigrationFiles(): void
    {
        $this->db->exec(
            'CREATE TABLE mb_schema_migrations (
                app VARCHAR(64), migration VARCHAR(190), checksum CHAR(64), applied_at TEXT,
                PRIMARY KEY (app, migration)
            )'
        );
        $statement = $this->db->prepare(
            'INSERT INTO mb_schema_migrations (app, migration, checksum) VALUES (?, ?, ?)'
        );
        $statement->execute(['test', '20261023160000_missing', str_repeat('a', 64)]);

        $status = $this->createRunner()->inspectStatus();

        self::assertTrue($status['history_exists']);
        self::assertSame(['20261023160000_missing'], $status['untracked_migrations']);
    }

    public function testFreshBaselineRejectsExistingTablesBeforeRunningUp(): void
    {
        $this->db->exec('CREATE TABLE existing_records (id INTEGER PRIMARY KEY)');
        $this->writeMigration(
            '20261023160000_baseline.php',
            "return [
                'tables' => ['existing_records'],
                'up' => static function (PDO \$db): void { \$db->exec('CREATE TABLE should_not_exist (id INTEGER)'); },
            ];"
        );

        $runner = $this->createRunner();
        $status = $runner->inspectStatus();
        self::assertTrue($status['baseline_tables_checked']);
        self::assertSame(['existing_records'], $status['baseline_tables_existing']);

        try {
            $runner->migrate();
            self::fail('Expected existing baseline table to prevent fresh baseline application.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('use baseline adoption instead', $exception->getMessage());
        }

        self::assertSame(
            0,
            (int)$this->db->query(
                "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'should_not_exist'"
            )->fetchColumn()
        );
        self::assertSame(
            0,
            (int)$this->db->query(
                "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'mb_schema_migrations'"
            )->fetchColumn()
        );
    }

    public function testRollbackRequiresAndRunsDownOperation(): void
    {
        $this->writeMigration(
            '20261023160000_create_example.php',
            "return [
                'up' => static function (PDO \$db): void { \$db->exec('CREATE TABLE example (id INTEGER PRIMARY KEY)'); },
                'down' => static function (PDO \$db): void { \$db->exec('DROP TABLE example'); },
            ];"
        );
        $runner = $this->createRunner();
        $runner->migrate();

        self::assertSame('20261023160000_create_example', $runner->rollbackLast());
        $status = $runner->getStatus();
        self::assertCount(1, $status);
        self::assertFalse($status[0]['applied']);
        self::assertSame(
            0,
            (int)$this->db->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'example'")->fetchColumn()
        );
    }

    public function testAdoptBaselineVerifiesSchemaAndDoesNotRunUpOrChangeData(): void
    {
        $this->db->exec('CREATE TABLE existing_records (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $this->db->exec("INSERT INTO existing_records (id, name) VALUES (1, 'preserve me')");
        $this->writeMigration(
            '20261023160000_baseline.php',
            "return [
                'up' => static function (PDO \$db): void { throw new RuntimeException('up must not run during adoption'); },
                'verify' => static function (PDO \$db): void {
                    DatabaseMigrationRunner::assertSchema(\$db, ['existing_records' => ['id', 'name']]);
                },
            ];"
        );

        $runner = $this->createRunner();
        self::assertSame('20261023160000_baseline', $runner->adoptBaseline());
        $row = $this->db->query('SELECT name FROM existing_records WHERE id = 1')->fetchColumn();
        self::assertSame('preserve me', $row);
        self::assertTrue($runner->getStatus()[0]['applied']);
    }

    public function testAdoptionRejectsSchemaMismatchWithoutRecordingBaseline(): void
    {
        $this->db->exec('CREATE TABLE existing_records (id INTEGER PRIMARY KEY)');
        $this->writeMigration(
            '20261023160000_baseline.php',
            "return [
                'up' => static function (PDO \$db): void {},
                'verify' => static function (PDO \$db): void {
                    DatabaseMigrationRunner::assertSchema(\$db, ['existing_records' => ['id', 'missing_column']]);
                },
            ];"
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Existing schema does not match');
        try {
            $this->createRunner()->adoptBaseline();
        } finally {
            $historyExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'mb_schema_migrations'"
            )->fetchColumn() > 0;
            self::assertFalse($historyExists);
        }
    }

    private function createRunner(): DatabaseMigrationRunner
    {
        return new DatabaseMigrationRunner($this->db, 'test', $this->migrationDirectory);
    }

    private function writeMigration(string $filename, string $contents): void
    {
        file_put_contents($this->migrationDirectory . DIRECTORY_SEPARATOR . $filename, "<?php\n" . $contents);
    }
}
