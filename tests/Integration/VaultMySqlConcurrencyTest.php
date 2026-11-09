<?php

use PHPUnit\Framework\TestCase;

final class VaultMySqlConcurrencyTest extends TestCase
{
    public function testConcurrentTransfersSerializeOnSender(): void
    {
        $dsn = getenv('VAULT_TEST_MYSQL_DSN');
        if (!$dsn) {
            self::markTestSkipped('Provide an empty, isolated vault_test_* MySQL database to run this test.');
        }
        $db = new PDO($dsn, getenv('VAULT_TEST_MYSQL_USER'), getenv('VAULT_TEST_MYSQL_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $name = $db->query('SELECT DATABASE()')->fetchColumn();
        if (!is_string($name) || !preg_match('/^vault_test_[a-z0-9_]+$/', $name)) {
            self::fail('Refusing to run outside an explicitly isolated vault_test_* database.');
        }
        $check = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?');
        $check->execute([$name]);
        if ((int)$check->fetchColumn() !== 0) {
            self::fail('Refusing to run in a database that is not empty.');
        }

        $db->exec('CREATE TABLE users (id INT PRIMARY KEY, username VARCHAR(255) NOT NULL) ENGINE=InnoDB');
        $db->exec("INSERT INTO users VALUES (1, 'sender'), (2, 'recipient'), (3, 'other')");
        $db->exec('CREATE TABLE memory_anchors (uuid VARCHAR(36) PRIMARY KEY, architect_id INT NOT NULL, content JSON NOT NULL, content_type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, projected_to DATETIME NOT NULL, status VARCHAR(20) NOT NULL, FOREIGN KEY (architect_id) REFERENCES users(id)) ENGINE=InnoDB');
        $stmt = $db->prepare('INSERT INTO memory_anchors VALUES (?, 1, ?, ?, NOW(), NOW(), ?)');
        $stmt->execute(['initial', '{"amount":50}', 'token_credit', 'active']);

        $db->beginTransaction();
        $db->query('SELECT id FROM users WHERE id = 1 FOR UPDATE')->fetchColumn();
        $worker = __DIR__ . '/../fixtures/vault-mysql-worker.php';
        $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $first = proc_open([PHP_BINARY, $worker, '2'], $spec, $pipes1);
        $second = proc_open([PHP_BINARY, $worker, '3'], $spec, $pipes2);
        self::assertIsResource($first);
        self::assertIsResource($second);
        try {
            self::assertSame("ready\n", fgets($pipes1[1]));
            self::assertSame("ready\n", fgets($pipes2[1]));
            usleep(300000);
            self::assertTrue(proc_get_status($first)['running']);
            self::assertTrue(proc_get_status($second)['running']);
            $db->commit();
            $result1 = stream_get_contents($pipes1[1]);
            $error1 = stream_get_contents($pipes1[2]);
            $result2 = stream_get_contents($pipes2[1]);
            $error2 = stream_get_contents($pipes2[2]);
        } finally {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            foreach ([$pipes1[1], $pipes1[2], $pipes2[1], $pipes2[2]] as $pipe) {
                fclose($pipe);
            }
            $exit1 = proc_close($first);
            $exit2 = proc_close($second);
        }
        self::assertSame(0, $exit1, $error1);
        self::assertSame(0, $exit2, $error2);
        $results = [$result1, $result2];
        sort($results);
        self::assertSame(['insufficient', 'success'], $results);
        self::assertSame(3, (int)$db->query('SELECT COUNT(*) FROM memory_anchors')->fetchColumn());
    }
}
