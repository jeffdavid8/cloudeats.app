<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/apps/vault/includes/services/VaultLedgerAudit.php';

final class VaultLedgerAuditTest extends TestCase
{
    private function row(int $id, int $user, string $uuid, string $type, string $content, string $status = 'active'): array
    {
        return ['id' => $id, 'architect_id' => $user, 'uuid' => $uuid, 'content_type' => $type, 'content' => $content, 'status' => $status];
    }

    public function testSyntheticLedgerDoesNotAssumeCreditsAreBacked(): void
    {
        $pair = str_repeat('a', 32);
        $rows = [
            $this->row(1, 1, str_repeat('b', 32), 'daily_provision', '{"amount":50}'),
            $this->row(2, 1, $pair . '-out', 'token_debit', '{"amount":12.34}'),
            $this->row(3, 2, $pair . '-in', 'token_credit', '{"amount":12.34}'),
            $this->row(4, 2, str_repeat('c', 32) . '-in', 'token_credit', '{"amount":2}'),
            $this->row(5, 2, 'ignored', 'token_credit', '{"amount":100}', 'archived'),
        ];
        $report = VaultLedgerAudit::inspect($rows);
        self::assertSame(4, $report['inspected_entries']);
        self::assertSame('37.66', $report['balances'][1]);
        self::assertSame('14.34', $report['balances'][2]);
        self::assertSame(['1', '4'], $report['unverified_credit_entry_ids']);
        self::assertSame([], $report['issues']);
        self::assertFalse($report['backing_verified']);
    }

    public function testIdentifiesMalformedAmountsBrokenPairsAndNegativeBalances(): void
    {
        $pair = str_repeat('d', 32);
        $rows = [
            $this->row(1, 1, $pair . '-out', 'token_debit', '{"amount":10}'),
            $this->row(2, 2, $pair . '-in', 'token_credit', '{"amount":9}'),
            $this->row(3, 2, 'invalid', 'token_credit', '{"amount":1.001}'),
            $this->row(4, 2, 'broken', 'token_credit', '{'),
            $this->row(5, 2, str_repeat('e', 32) . '-out', 'token_debit', '{"amount":1}'),
        ];
        $report = VaultLedgerAudit::inspect($rows);
        self::assertSame(
            ['invalid_amount', 'invalid_content', 'invalid_transfer_pair', 'invalid_transfer_pair', 'negative_balance'],
            array_column($report['issues'], 'kind')
        );
        self::assertFalse($report['backing_verified']);
    }
}
