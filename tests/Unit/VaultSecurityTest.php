<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/includes/models/storage.model.php';
require_once __DIR__ . '/../../html/apps/vault/includes/models/vault.model.php';

final class VaultSecurityTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema($this->db);
        App::getInstance()->db = $this->db;
    }

    protected function tearDown(): void
    {
        App::getInstance()->db = null;
    }

    private function createSchema(PDO $db): void
    {
        $db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT NOT NULL)');
        $db->exec("INSERT INTO users VALUES (1, 'sender'), (2, 'recipient'), (3, 'other')");
        $db->exec('CREATE TABLE memory_anchors (uuid TEXT PRIMARY KEY, architect_id INTEGER, content TEXT, content_type TEXT, created_at TEXT, projected_to TEXT, status TEXT)');
    }

    private function credit(PDO $db, string $amount): void
    {
        $stmt = $db->prepare("INSERT INTO memory_anchors VALUES ('initial', 1, ?, 'token_credit', '', '', 'active')");
        $stmt->execute([json_encode(['amount' => $amount])]);
    }

    public function testTransferMovesOnlyAvailableFunds(): void
    {
        $this->credit($this->db, '50.00');
        self::assertTrue(Vault::transfer(1, 2, '12.34', 'test'));
        self::assertEqualsWithDelta(37.66, (float)Vault::get_balance(1), 0.00001);
        self::assertEqualsWithDelta(12.34, (float)Vault::get_balance(2), 0.00001);
        try {
            Vault::transfer(1, 2, '37.67', 'test');
            self::fail('Overspending must be rejected.');
        } catch (DomainException $e) {
            self::assertSame('Insufficient Vault balance.', $e->getMessage());
        }
        self::assertSame(3, (int)$this->db->query('SELECT COUNT(*) FROM memory_anchors')->fetchColumn());
        self::assertFalse($this->db->inTransaction());
    }

    public static function invalidAmounts(): array
    {
        return [[0], [-1], ['-1.00'], ['1.001'], ['1e2'], ['NaN'], [INF], [NAN], [[]], ['99999999999'], ['0.00']];
    }

    /** @dataProvider invalidAmounts */
    public function testInvalidAmountsAreRejected($amount): void
    {
        $this->expectException(InvalidArgumentException::class);
        Vault::transfer(1, 2, $amount, 'test');
    }

    public function testInvalidRecipientAndSelfTransferAreRejected(): void
    {
        $this->credit($this->db, '50.00');
        foreach ([1, 999, []] as $recipient) {
            try {
                Vault::transfer(1, $recipient, '1.00', 'test');
                self::fail('Invalid recipient must be rejected.');
            } catch (InvalidArgumentException $e) {
                self::assertFalse($this->db->inTransaction());
            }
        }
        self::assertSame(1, (int)$this->db->query('SELECT COUNT(*) FROM memory_anchors')->fetchColumn());
    }

    public function testMalformedLedgerFailsClosed(): void
    {
        $this->credit($this->db, '50.001');
        $this->expectException(RuntimeException::class);
        Vault::transfer(1, 2, '1.00', 'test');
    }

    public function testFailedCreditRollsBackDebit(): void
    {
        $this->credit($this->db, '50.00');
        $this->db->exec("CREATE TRIGGER reject_credit BEFORE INSERT ON memory_anchors WHEN NEW.architect_id = 2 BEGIN SELECT RAISE(ABORT, 'credit failed'); END");
        try {
            Vault::transfer(1, 2, '10.00', 'test');
            self::fail('A failed credit must abort the transfer.');
        } catch (PDOException $e) {
            self::assertSame(1, (int)$this->db->query('SELECT COUNT(*) FROM memory_anchors')->fetchColumn());
            self::assertFalse($this->db->inTransaction());
            self::assertEqualsWithDelta(50.0, (float)Vault::get_balance(1), 0.00001);
        }
    }

    public function testExactCentBoundaryIsEnforced(): void
    {
        $this->credit($this->db, '0.30');
        Vault::transfer(1, 2, '0.10', 'test');
        Vault::transfer(1, 2, '0.20', 'test');
        self::assertEqualsWithDelta(0.0, (float)Vault::get_balance(1), 0.00001);
        $this->expectException(DomainException::class);
        Vault::transfer(1, 2, '0.01', 'test');
    }

    public function testConcurrentTransfersCannotSpendSameBalance(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'vault-test-');
        self::assertNotFalse($path);
        try {
            $db = new PDO('sqlite:' . $path);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createSchema($db);
            $this->credit($db, '50.00');
            $worker = __DIR__ . '/../fixtures/vault-transfer-worker.php';
            $pipes1 = $pipes2 = [];
            $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $first = proc_open([PHP_BINARY, $worker, $path, '2'], $spec, $pipes1);
            $second = proc_open([PHP_BINARY, $worker, $path, '3'], $spec, $pipes2);
            self::assertIsResource($first);
            self::assertIsResource($second);
            $out1 = stream_get_contents($pipes1[1]);
            $err1 = stream_get_contents($pipes1[2]);
            $out2 = stream_get_contents($pipes2[1]);
            $err2 = stream_get_contents($pipes2[2]);
            foreach ([$pipes1[1], $pipes1[2], $pipes2[1], $pipes2[2]] as $pipe) {
                fclose($pipe);
            }
            self::assertSame(0, proc_close($first), $err1);
            self::assertSame(0, proc_close($second), $err2);
            $results = [$out1, $out2];
            sort($results);
            self::assertSame(['insufficient', 'success'], $results);
            self::assertSame(3, (int)$db->query('SELECT COUNT(*) FROM memory_anchors')->fetchColumn());
            $db = null;
        } finally {
            unlink($path);
        }
    }

    public function testPayoutAndAuthenticationAreEnforcedByApi(): void
    {
        $worker = __DIR__ . '/../fixtures/vault-api-worker.php';
        foreach ([
            [['request_vault_payout', 'POST'], 401],
            [['request_vault_payout', 'GET', '1'], 405],
            [['request_vault_payout', 'POST', '1'], 503],
            [['transfer', 'POST'], 401],
            [['transfer', 'POST', '1', '2'], 401],
        ] as [$args, $expected]) {
            $process = proc_open(array_merge([PHP_BINARY, $worker], $args), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $error);
            $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($expected, $response['status_code']);
            self::assertSame('error', $response['body']['status']);
        }
    }
}
