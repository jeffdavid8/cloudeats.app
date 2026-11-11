<?php

declare(strict_types=1);

final class VaultLedgerAudit
{
    /**
     * Inspect supplied ledger rows without changing them. This is not a
     * reconciliation against Stripe or proof that any balance is backed.
     */
    public static function inspect(iterable $rows): array
    {
        $balances = [];
        $pairs = [];
        $issues = [];
        $unverifiedCredits = [];
        $unverifiedDebits = [];
        $count = 0;

        foreach ($rows as $row) {
            if (($row['status'] ?? null) !== 'active'
                || !in_array($row['content_type'] ?? null, ['daily_provision', 'token_credit', 'token_debit'], true)) {
                continue;
            }
            ++$count;
            $id = (string)($row['id'] ?? '');
            $user = (string)($row['architect_id'] ?? '');
            $uuid = (string)($row['uuid'] ?? '');
            $type = $row['content_type'];
            if ($id === '' || !preg_match('/^[1-9][0-9]*$/', $user) || $uuid === '') {
                $issues[] = ['kind' => 'invalid_identity', 'entry_id' => $id];
                continue;
            }
            try {
                $content = json_decode((string)($row['content'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $issues[] = ['kind' => 'invalid_content', 'entry_id' => $id];
                continue;
            }
            $amount = is_array($content) ? ($content['amount'] ?? null) : null;
            if ((!is_int($amount) && !is_float($amount) && !is_string($amount))
                || (is_float($amount) && !is_finite($amount))
                || !preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?$/', (string)$amount)) {
                $issues[] = ['kind' => 'invalid_amount', 'entry_id' => $id];
                continue;
            }
            $amount = bcadd((string)$amount, '0', 2);
            $balances[$user] = $balances[$user] ?? '0.00';
            $balances[$user] = $type === 'token_debit'
                ? bcsub($balances[$user], $amount, 2)
                : bcadd($balances[$user], $amount, 2);

            if (preg_match('/^([a-f0-9]{32})-(in|out)$/', $uuid, $match)) {
                $pairs[$match[1]][$match[2]][] = [
                    'entry_id' => $id, 'user_id' => $user, 'type' => $type, 'amount' => $amount,
                ];
            } elseif ($type === 'token_debit') {
                $unverifiedDebits[] = $id;
            } else {
                $unverifiedCredits[] = $id;
            }
        }

        foreach ($pairs as $pairId => $pair) {
            $in = $pair['in'] ?? [];
            $out = $pair['out'] ?? [];
            if (count($in) === 1 && count($out) === 0 && $in[0]['type'] === 'token_credit') {
                $unverifiedCredits[] = $in[0]['entry_id'];
                continue;
            }
            if (count($in) !== 1 || count($out) !== 1
                || $in[0]['type'] !== 'token_credit' || $out[0]['type'] !== 'token_debit'
                || $in[0]['user_id'] === $out[0]['user_id'] || $in[0]['amount'] !== $out[0]['amount']) {
                $issues[] = ['kind' => 'invalid_transfer_pair', 'pair_id' => $pairId];
            }
        }
        foreach ($balances as $user => $balance) {
            if (bccomp($balance, '0', 2) < 0) {
                $issues[] = ['kind' => 'negative_balance', 'user_id' => (string)$user, 'balance' => $balance];
            }
        }

        return [
            'inspected_entries' => $count,
            'balances' => $balances,
            'issues' => $issues,
            'unverified_credit_entry_ids' => $unverifiedCredits,
            'unverified_debit_entry_ids' => $unverifiedDebits,
            'backing_verified' => false,
        ];
    }
}
