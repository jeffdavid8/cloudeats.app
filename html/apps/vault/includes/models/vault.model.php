<?php

/**
 * 🏦 VAULT MODEL
 * * Manages token-based transactions and balance tracking.
 * Extends Storage for base CRUD operations on memory_anchors table.
 * Fully optimized for both SQLite and MySQL runtime configurations.
 */
class Vault extends Storage
{
    /**
     * 📋 CONFIGURATION: Database table name
     */
    protected static function getTableName()
    {
        return 'memory_anchors';
    }

    /**
     * 🎯 CONFIGURATION: Vault status values
     */
    protected static function getStatusValues()
    {
        return [
            'active'   => 'Active',
            'archived' => 'Archived',
            'pending'  => 'Pending',
        ];
    }
    
    public static function get_balance($user_id)
    {
        $db = self::getDb();
        // 🧮 Sum Credits - Sum Debits
        // Using + 0 forces cross-engine dynamic casting for JSON properties safely on both MySQL & SQLite
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE 
                    WHEN content_type IN ('daily_provision', 'token_credit') 
                    THEN (json_extract(content, '$.amount') + 0)
                    ELSE 0 
                END) - 
                SUM(CASE 
                    WHEN content_type = 'token_debit' 
                    THEN (json_extract(content, '$.amount') + 0)
                    ELSE 0 
                END) as balance
            FROM memory_anchors 
            WHERE architect_id = ?
            AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        return $row['balance'] ?? 0.00;
    }

    public static function transfer($from_id, $to_id, $amount, $note)
    {
        $db = self::getDb();
        $amount = self::moneyAmount($amount);
        if (!is_scalar($note) || (!is_string($from_id) && !is_int($from_id))
            || (!is_string($to_id) && !is_int($to_id)) || !preg_match('/^[1-9][0-9]*$/', (string)$from_id)
            || !preg_match('/^[1-9][0-9]*$/', (string)$to_id) || (string)$from_id === (string)$to_id) {
            throw new InvalidArgumentException('Invalid transfer details.');
        }

        $db->beginTransaction();
        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $lock = $db->prepare('UPDATE users SET id = id WHERE id = ?');
                $lock->execute([$from_id]);
            } elseif ($driver !== 'mysql') {
                throw new RuntimeException('Unsupported database for Vault transfers.');
            }

            $senderQuery = $db->prepare('SELECT username FROM users WHERE id = ?' . ($driver === 'mysql' ? ' FOR UPDATE' : ''));
            $senderQuery->execute([$from_id]);
            $sender = $senderQuery->fetchColumn();
            $recipientQuery = $db->prepare('SELECT username FROM users WHERE id = ?');
            $recipientQuery->execute([$to_id]);
            $recipient = $recipientQuery->fetchColumn();
            if ($sender === false || $recipient === false) {
                throw new InvalidArgumentException('Transfer user not found.');
            }

            $entries = $db->prepare("SELECT content_type, content FROM memory_anchors WHERE architect_id = ? AND status = 'active' AND content_type IN ('daily_provision', 'token_credit', 'token_debit')");
            $entries->execute([$from_id]);
            $balance = '0.00';
            while ($entry = $entries->fetch(PDO::FETCH_ASSOC)) {
                $content = json_decode($entry['content'], true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($content) || !array_key_exists('amount', $content)) {
                    throw new RuntimeException('Invalid Vault ledger entry.');
                }
                try {
                    $entryAmount = self::moneyAmount($content['amount'], true);
                } catch (InvalidArgumentException $e) {
                    throw new RuntimeException('Invalid Vault ledger amount.', 0, $e);
                }
                $balance = $entry['content_type'] === 'token_debit'
                    ? bcsub($balance, $entryAmount, 2)
                    : bcadd($balance, $entryAmount, 2);
            }
            if (bccomp($balance, $amount, 2) < 0) {
                throw new DomainException('Insufficient Vault balance.');
            }

            $uuid = bin2hex(random_bytes(16));
            $timestamp = date('Y-m-d H:i:s');

            $senderContent = json_encode([
                'description' => "Sent to User ($recipient): $note",
                'amount' => (float)$amount
            ], JSON_THROW_ON_ERROR);

            $receiverContent = json_encode([
                'description' => "Received from User ($sender): $note",
                'amount' => (float)$amount
            ], JSON_THROW_ON_ERROR);

            $stmt = $db->prepare("INSERT INTO memory_anchors (uuid, architect_id, content, content_type, created_at, projected_to, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$uuid . '-out', $from_id, $senderContent, 'token_debit', $timestamp, $timestamp, 'active']);
            $stmt->execute([$uuid . '-in', $to_id, $receiverContent, 'token_credit', $timestamp, $timestamp, 'active']);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private static function moneyAmount($amount, $allowZero = false)
    {
        if ((!is_string($amount) && !is_int($amount) && !is_float($amount))
            || (is_float($amount) && !is_finite($amount))
            || !preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?$/', (string)$amount)) {
            throw new InvalidArgumentException('Amount must be a valid value with at most two decimal places.');
        }
        $normalized = bcadd((string)$amount, '0', 2);
        if (!$allowZero && bccomp($normalized, '0', 2) <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
        return $normalized;
    }

    public static function addFundsToUser($user_id, $amount, $note)
    {
        $db = self::getDb();
        $user = User::getById($user_id);

        $db->beginTransaction();
        try {
            $uuid = bin2hex(random_bytes(16));
            $timestamp = date('Y-m-d H:i:s');

            // Wrap the data in a JSON string for the 'content' column
            $content = json_encode([
                'description' => "Added funds: $note",
                'amount' => (float)$amount
            ]);

            $stmt = $db->prepare("INSERT INTO memory_anchors (uuid, architect_id, content, content_type, created_at, projected_to, status) VALUES (?, ?, ?, ?, ?, ?, ?)");

            // Credit User
            $stmt->execute([$uuid . '-in', $user->id, $content, 'token_credit', $timestamp, $timestamp, 'active']);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            // 📢 Log the actual error to the server logs
            error_log("Vault Add Funds Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    

    public static function mint_provision($app = null)
    {
        if ($app === null) {
            $app = App::getInstance();
        }
        
        $db = $app->db ?? self::getDb();
        
        // 1. Get all active users
        $users = $db->query("SELECT id FROM users")->fetchAll();
        $mintCount = 0;
        $today = date('Y-m-d');
        $dateTime = new DateTime();
        $timestamp = $dateTime->format('Y-m-d H:i:s');

        foreach ($users as $u) {
            // 2. Check if they already got their "Daily Bread" today
            // date(created_at) works cleanly across both MySQL and SQLite string/datetime types
            $check = $db->prepare("SELECT id FROM memory_anchors 
                            WHERE architect_id = ? 
                            AND content_type = 'daily_provision' 
                            AND date(created_at) = ?
                            AND status = 'active'
                            ");
            $check->execute([$u['id'], $today]);

            if (!$check->fetch()) {
                // 3. MINT! 💎
                $content = json_encode([
                    'description' => "Daily Town Provision 🥰",
                    'amount' => 50.00
                ]);

                $stmt = $db->prepare("INSERT INTO memory_anchors 
                    (uuid, architect_id, content, content_type, created_at, projected_to, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    bin2hex(random_bytes(16)),
                    $u['id'],
                    $content,
                    "daily_provision",
                    $timestamp,
                    $timestamp,
                    "active"
                ]);
                $mintCount++;
            }
        }
        return array(
            'status' => 'success',
            'minted' => $mintCount,
        );
    }

    public static function get_impact($user_id)
    {
        $db = self::getDb();
        // Summing the 'amount' from the JSON safely using dynamic unquoting math evaluation
        $stmt = $db->prepare("
            SELECT SUM(json_extract(content, '$.amount') + 0) as impact 
            FROM memory_anchors 
            WHERE architect_id = ? 
            AND content_type = 'token_debit' 
            AND json_extract(content, '$.description') LIKE 'Sent to%'
            AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        return $row['impact'] ?? 0.00;
    }

    public static function get_history($user_id, $limit = 20)
    {
        $db = self::getDb();
        // 📜 Explicitly bind variables to guarantee compatibility with native MySQL string parameterization limits
        $stmt = $db->prepare("
            SELECT * FROM memory_anchors 
            WHERE architect_id = :user_id 
            AND content_type IN ('token_credit', 'token_debit', 'daily_provision') 
            AND status = 'active'
            ORDER BY created_at DESC LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            $decoded = json_decode($row['content'], true);
            $row['data'] = $decoded ?? ['description' => $row['content'], 'amount' => 0];
            return $row;
        }, $rows);
    }
}