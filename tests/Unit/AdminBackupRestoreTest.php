<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/apps/admin/includes/BackupFiles.php';

final class AdminBackupRestoreTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite driver is required for the isolated restore test.');
        }
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restore-test-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
        mkdir($this->directory . DIRECTORY_SEPARATOR . 'backups');
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'backup.json', json_encode([
            'tables' => ['selected_table' => [['id' => 2, 'name' => 'restored']], 'other_table' => []],
        ]));
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'notes.txt', 'not a backup');
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'outside.json', '{}');
    }

    protected function tearDown(): void
    {
        if (!isset($this->directory)) return;
        unlink($this->directory . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'backup.json');
        unlink($this->directory . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'notes.txt');
        unlink($this->directory . DIRECTORY_SEPARATOR . 'outside.json');
        rmdir($this->directory . DIRECTORY_SEPARATOR . 'backups');
        rmdir($this->directory);
    }

    public function testListsOnlyJsonAndRestoresOnlySelectedTables(): void
    {
        self::assertSame(['backup.json'], BackupFiles::list($this->directory . DIRECTORY_SEPARATOR . 'backups'));
        [$body, $state] = $this->request('backup.json');
        self::assertSame(200, $state['status']);
        self::assertTrue($body['success']);
        self::assertSame(['selected_table'], $body['data']['tables_restored']);
        self::assertSame([['id' => 2, 'name' => 'restored']], $state['selected']);
        self::assertSame([['id' => 99]], $state['other']);
    }

    public function testEmptyTableIsClearedAndAbsentTableIsSkipped(): void
    {
        $backup = $this->directory . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'backup.json';
        file_put_contents($backup, '{"tables":{"selected_table":[]}}');
        [$body, $state] = $this->request('backup.json');
        self::assertSame([], $state['selected']);
        self::assertSame(['selected_table'], $body['data']['tables_restored']);

        file_put_contents($backup, '{"tables":{"other_table":[]}}');
        [$body, $state] = $this->request('backup.json');
        self::assertSame([['id' => 1, 'name' => 'old']], $state['selected']);
        self::assertSame([], $body['data']['tables_restored']);
        self::assertStringContainsString('not present', $body['data']['results']['restore']['selected_table'][0]);
    }

    public function testRejectsTraversalMissingFilesUnauthorizedAndInvalidCsrf(): void
    {
        foreach ([
            ['../outside.json', 'admin', 'valid-token', 400],
            ['..\\outside.json', 'admin', 'valid-token', 400],
            ['notes.txt', 'admin', 'valid-token', 400],
            ['missing.json', 'admin', 'valid-token', 400],
            ['backup.json', 'user', 'valid-token', 403],
            ['backup.json', 'admin', 'wrong', 403],
        ] as [$file, $role, $token, $status]) {
            [$body, $state] = $this->request($file, $role, $token);
            self::assertSame($status, $state['status']);
            self::assertNotTrue($body['success'] ?? false);
            self::assertSame([['id' => 1, 'name' => 'old']], $state['selected']);
        }
    }

    public function testRejectsBadBackupWithoutChangingTables(): void
    {
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'backup.json', '{"tables":{"selected_table":[{"bad_column":1}]}}');
        [$body, $state] = $this->request('backup.json');
        self::assertSame(500, $state['status']);
        self::assertFalse($body['success']);
        self::assertSame([['id' => 1, 'name' => 'old']], $state['selected']);
    }

    private function request(string $file, string $role = 'admin', string $token = 'valid-token'): array
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/../fixtures/admin-restore-api-worker.php', $this->directory, $file, $role, $token],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $body = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr);
        $start = strrpos($stderr, '{"status"');
        self::assertNotFalse($start, $stderr);
        return [json_decode($body, true, 512, JSON_THROW_ON_ERROR), json_decode(substr($stderr, $start), true, 512, JSON_THROW_ON_ERROR)];
    }
}
