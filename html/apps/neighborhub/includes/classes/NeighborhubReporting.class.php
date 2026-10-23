<?php

class NeighborhubReporting
{
    private PDO $db;

    private const ORDER_STATES = [
        'PENDING_CONFIRMATION',
        'CONFIRMED',
        'READY_FOR_PICKUP',
        'IN_TRANSIT',
        'DELIVERED',
        'CANCELLED',
        'FAILED',
    ];

    private const REPORT_TYPES = ['orders_summary', 'line_items', 'payouts'];

    private const EVENT_PROPERTIES = [
        'merchant_search' => ['result_count'],
        'product_view' => ['merchant_type'],
        'cart_updated' => ['operation', 'item_count_bucket'],
        'checkout_started' => ['delivery_requested'],
        'checkout_completed' => ['delivery_requested'],
        'checkout_failed' => ['failure_category'],
        'order_created' => ['order_state', 'delivery_requested'],
        'order_cancelled' => ['actor_role'],
        'order_state_changed' => ['from_state', 'to_state'],
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public static function validateDateRange(array $filters, int $maxDays = 3660): array
    {
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $startDate = $filters['start_date'] ?? date('Y-m-d', strtotime('-29 days'));
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$startDate);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$endDate);

        if (
            !$start || !$end
            || $start->format('Y-m-d') !== $startDate
            || $end->format('Y-m-d') !== $endDate
            || $start > $end
            || $start->diff($end)->days > $maxDays
        ) {
            throw new InvalidArgumentException('Invalid date range.');
        }

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];
    }

    public function getOrderReport(
        string $view,
        int $userId,
        bool $isAdmin,
        array $filters,
        int $limit = 25,
        int $offset = 0
    ): array {
        $dateRange = self::validateDateRange($filters);
        $reportType = (string)($filters['report_type'] ?? 'orders_summary');
        if (!in_array($reportType, self::REPORT_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported report type.');
        }
        if ($reportType === 'payouts' && !in_array($view, ['admin', 'merchant'], true)) {
            throw new DomainException('Payout reports are not available for this role.');
        }

        $state = $filters['state'] ?? '';
        if ($state !== '' && !in_array($state, self::ORDER_STATES, true)) {
            throw new InvalidArgumentException('Unsupported order status.');
        }

        [$scopeSql, $scopeParams] = $this->buildOrderScope(
            $view,
            $userId,
            $isAdmin,
            isset($filters['merchant_id']) ? (int)$filters['merchant_id'] : 0
        );
        $whereSql = "o.created_at >= :start_datetime AND o.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)";
        if ($scopeSql !== '') {
            $whereSql = '(' . $scopeSql . ') AND ' . $whereSql;
        }
        $params = array_merge($scopeParams, [
            ':start_datetime' => $dateRange['start_date'] . ' 00:00:00',
            ':end_date' => $dateRange['end_date'],
        ]);
        if ($state !== '') {
            $whereSql .= ' AND o.state = :state';
            $params[':state'] = $state;
        }

        $fromSql = ' FROM neighborhub_orders o LEFT JOIN neighborhub_merchants m ON m.id = o.merchant_id ';
        $selectSql = $this->orderSelectSql($reportType);
        if ($reportType === 'line_items') {
            $fromSql .= ' LEFT JOIN neighborhub_order_items oi ON oi.order_id = o.id LEFT JOIN neighborhub_products p ON p.id = oi.product_id ';
        }

        $countStmt = $this->db->prepare('SELECT COUNT(*)' . $fromSql . ' WHERE ' . $whereSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $limit = max(1, min(10000, $limit));
        $offset = max(0, $offset);
        $query = $this->db->prepare(
            'SELECT ' . $selectSql . $fromSql . ' WHERE ' . $whereSql .
                ' ORDER BY o.created_at DESC, o.id DESC' .
                    ($reportType === 'line_items' ? ', oi.id DESC' : '') .
                    ' LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        return [
            'filters' => array_merge($dateRange, ['state' => $state, 'report_type' => $reportType]),
            'records' => $query->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function getOrderExport(
        string $view,
        int $userId,
        bool $isAdmin,
        array $filters,
        int $maxRows = 10000
    ): array {
        $result = $this->getOrderReport($view, $userId, $isAdmin, $filters, 1, 0);
        if ($result['total'] > $maxRows) {
            throw new LengthException('The export exceeds the 10,000 row limit. Narrow the date range or filters.');
        }

        return $this->getOrderReport($view, $userId, $isAdmin, $filters, $maxRows, 0);
    }

    public function getAnalyticsReport(
        array $filters,
        bool $isAdmin,
        int $userId = 0,
        int $merchantId = 0
    ): array
    {
        if (!$isAdmin) {
            $this->assertMerchantAnalyticsAccess($userId, $merchantId);
        } elseif ($merchantId < 0) {
            throw new InvalidArgumentException('Invalid merchant selection.');
        }
        $dateRange = self::validateDateRange($filters, 89);
        $retainedFrom = (new DateTimeImmutable('-89 days'))->format('Y-m-d');
        if ($dateRange['start_date'] < $retainedFrom) {
            throw new InvalidArgumentException('Analytics are available for the most recent 90 days.');
        }
        $this->purgeExpiredEvents();

        $scopeSql = $merchantId > 0 ? ' AND merchant_id = :analytics_merchant_id' : '';
        $scopeParams = $merchantId > 0 ? [':analytics_merchant_id' => $merchantId] : [];
        $queryParams = array_merge($scopeParams, [
            ':start_datetime' => $dateRange['start_date'] . ' 00:00:00',
            ':end_date' => $dateRange['end_date'],
        ]);

        $stmt = $this->db->prepare(
            'SELECT DATE(created_at) AS event_date, event_type, COUNT(*) AS event_count
             FROM neighborhub_usage_events
             WHERE created_at >= :start_datetime
               AND created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
               ' . $scopeSql . '
             GROUP BY DATE(created_at), event_type
             ORDER BY event_date ASC, event_type ASC'
        );
        $stmt->execute($queryParams);
        $trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totals = [];
        foreach ($trend as $row) {
            $totals[$row['event_type']] = ($totals[$row['event_type']] ?? 0) + (int)$row['event_count'];
        }

        $outcomesStmt = $this->db->prepare(
            "SELECT JSON_UNQUOTE(JSON_EXTRACT(properties, '$.to_state')) AS order_state, COUNT(*) AS event_count
             FROM neighborhub_usage_events
             WHERE event_type = 'order_state_changed'
               AND created_at >= :start_datetime
               AND created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)"
                . $scopeSql .
                " GROUP BY JSON_UNQUOTE(JSON_EXTRACT(properties, '$.to_state'))
             ORDER BY order_state ASC"
        );
        $outcomesStmt->execute($queryParams);
        $outcomes = $outcomesStmt->fetchAll(PDO::FETCH_ASSOC);

        $funnel = [];
        $funnelEvents = $merchantId > 0 ? [
            'product_view',
            'cart_updated',
            'checkout_started',
            'checkout_completed',
            'order_created',
        ] : [
            'merchant_search',
            'product_view',
            'cart_updated',
            'checkout_started',
            'checkout_completed',
            'order_created',
        ];
        foreach ($funnelEvents as $eventType) {
            $funnel[$eventType] = $totals[$eventType] ?? 0;
        }

        $eventsStmt = $this->db->prepare(
            'SELECT event_type, actor_role, properties, created_at
             FROM neighborhub_usage_events
             WHERE created_at >= :start_datetime
               AND created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
               ' . $scopeSql . '
             ORDER BY created_at DESC, id DESC
             LIMIT 100'
        );
        $eventsStmt->execute($queryParams);
        $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($events as &$event) {
            $event['properties'] = json_decode($event['properties'], true) ?: [];
        }
        unset($event);

        return [
            'filters' => array_merge($dateRange, $merchantId > 0 ? ['merchant_id' => $merchantId] : []),
            'totals' => $totals,
            'funnel' => $funnel,
            'outcomes' => $outcomes,
            'trend' => $trend,
            'events' => $events,
        ];
    }

    public function getAnalyticsExport(
        array $filters,
        bool $isAdmin,
        string $reportType,
        int $userId = 0,
        int $merchantId = 0
    ): array
    {
        $analytics = $this->getAnalyticsReport($filters, $isAdmin, $userId, $merchantId);
        if ($reportType === 'analytics_summary') {
            return [
                'filters' => $analytics['filters'],
                'records' => $analytics['trend'],
                'total' => count($analytics['trend']),
                'summary' => [
                    'totals' => $analytics['totals'],
                    'funnel' => $analytics['funnel'],
                    'outcomes' => $analytics['outcomes'],
                ],
            ];
        }
        if ($reportType !== 'analytics_events') {
            throw new InvalidArgumentException('Unsupported analytics report type.');
        }

        $dateRange = $analytics['filters'];
        $scopeSql = $merchantId > 0 ? ' AND merchant_id = :analytics_merchant_id' : '';
        $queryParams = array_merge(
            $merchantId > 0 ? [':analytics_merchant_id' => $merchantId] : [],
            [
                ':start_datetime' => $dateRange['start_date'] . ' 00:00:00',
                ':end_date' => $dateRange['end_date'],
            ]
        );
        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM neighborhub_usage_events
             WHERE created_at >= :start_datetime
               AND created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
               ' . $scopeSql
        );
        $countStmt->execute($queryParams);
        $total = (int)$countStmt->fetchColumn();
        if ($total > 10000) {
            throw new LengthException('The event export exceeds the 10,000 row limit. Narrow the date range.');
        }

        $stmt = $this->db->prepare(
            'SELECT event_type, actor_role, properties, created_at
             FROM neighborhub_usage_events
             WHERE created_at >= :start_datetime
               AND created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
               ' . $scopeSql . '
             ORDER BY created_at DESC, id DESC
             LIMIT 10000'
        );
        $stmt->execute($queryParams);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as &$record) {
            $record['properties'] = json_decode($record['properties'], true) ?: [];
        }
        unset($record);

        return [
            'filters' => $analytics['filters'],
            'records' => $records,
            'total' => $total,
        ];
    }

    public function assertMerchantAnalyticsAccess(int $userId, int $merchantId, bool $isAdmin = false): void
    {
        if ($isAdmin) {
            if ($merchantId <= 0) {
                throw new InvalidArgumentException('A valid merchant must be selected.');
            }
            return;
        }
        if ($userId <= 0) {
            throw new DomainException('Authentication is required.');
        }
        if ($merchantId <= 0) {
            throw new InvalidArgumentException('A valid merchant must be selected.');
        }

        $statement = $this->db->prepare(
            "SELECT 1
             FROM neighborhub_merchants m
             WHERE m.id = :merchant_id
               AND (
                 m.user_id = :owner_user_id
                 OR EXISTS (
                   SELECT 1
                   FROM neighborhub_merchant_users mu
                   WHERE mu.merchant_id = m.id
                     AND mu.user_id = :staff_user_id
                     AND mu.status = 'active'
                     AND mu.staff_role IN ('owner', 'staff')
                 )
               )
             LIMIT 1"
        );
        $statement->execute([
            ':merchant_id' => $merchantId,
            ':owner_user_id' => $userId,
            ':staff_user_id' => $userId,
        ]);
        if (!$statement->fetchColumn()) {
            throw new DomainException('You are not authorized to view analytics for this merchant.');
        }
    }

    public function recordEvent(
        string $eventType,
        string $actorRole,
        array $properties = [],
        ?string $dedupeKey = null,
        ?int $merchantId = null
    ): bool
    {
        if (!isset(self::EVENT_PROPERTIES[$eventType])) {
            throw new InvalidArgumentException('Unsupported NeighborHub event type.');
        }

        $safeProperties = [];
        foreach (self::EVENT_PROPERTIES[$eventType] as $key) {
            if (array_key_exists($key, $properties)) {
                $value = $properties[$key];
                if (is_scalar($value) || $value === null) {
                    $safeProperties[$key] = $value;
                }
            }
        }

        if ($dedupeKey !== null) {
            $dedupeKey = hash('sha256', $dedupeKey);
        }
        $statement = $this->db->prepare(
            'INSERT IGNORE INTO neighborhub_usage_events (event_type, actor_role, merchant_id, properties, dedupe_key, created_at)
             VALUES (:event_type, :actor_role, :merchant_id, :properties, :dedupe_key, NOW())'
        );
        $statement->execute([
            ':event_type' => $eventType,
            ':actor_role' => in_array($actorRole, ['admin', 'merchant', 'courier', 'customer', 'guest'], true) ? $actorRole : 'guest',
            ':merchant_id' => $merchantId !== null && $merchantId > 0 ? $merchantId : null,
            ':properties' => json_encode($safeProperties, JSON_THROW_ON_ERROR),
            ':dedupe_key' => $dedupeKey,
        ]);

        $this->purgeExpiredEvents();
        return $statement->rowCount() > 0;
    }

    private function purgeExpiredEvents(): void
    {
        $this->db->exec('DELETE FROM neighborhub_usage_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
    }

    public static function escapeCsvCell($value): string
    {
        $value = (string)$value;
        if (!is_numeric($value) && $value !== '' && preg_match('/^[\s]*[=+\-@]/u', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    private function buildOrderScope(string $view, int $userId, bool $isAdmin, int $merchantId): array
    {
        if ($view === 'admin') {
            if (!$isAdmin) {
                throw new DomainException('Administrative access is required.');
            }
            return ['', []];
        }

        if ($userId <= 0) {
            throw new DomainException('Authentication is required.');
        }

        if ($view === 'customer') {
            return ['o.customer_id = (SELECT c.id FROM neighborhub_customers c WHERE c.user_id = :scope_user_id LIMIT 1)', [':scope_user_id' => $userId]];
        }

        if ($view === 'courier') {
            return ['o.courier_id = (SELECT c.id FROM neighborhub_couriers c WHERE c.user_id = :scope_user_id LIMIT 1)', [':scope_user_id' => $userId]];
        }

        if ($view === 'merchant') {
            $scope = '(
                EXISTS (SELECT 1 FROM neighborhub_merchant_users mu
                    WHERE mu.user_id = :scope_user_id AND mu.merchant_id = o.merchant_id
                      AND mu.status = \'active\' AND mu.staff_role IN (\'owner\', \'staff\'))
                OR EXISTS (SELECT 1 FROM neighborhub_merchants own
                    WHERE own.user_id = :scope_owner_id AND own.id = o.merchant_id)
            )';
            $params = [':scope_user_id' => $userId, ':scope_owner_id' => $userId];
            if ($merchantId > 0) {
                $scope = 'o.merchant_id = :requested_merchant_id AND ' . $scope;
                $params[':requested_merchant_id'] = $merchantId;
            }
            return [$scope, $params];
        }

        throw new InvalidArgumentException('Unsupported report view.');
    }

    private function orderSelectSql(string $reportType): string
    {
        if ($reportType === 'line_items') {
            return 'o.order_number, o.created_at AS order_date, m.business_name AS merchant_name,
                    p.name AS product_name, oi.quantity, oi.price_at_order,
                    oi.subtotal AS item_subtotal, o.state AS order_status, o.sandbox_mode';
        }

        if ($reportType === 'payouts') {
            return 'o.order_number, o.created_at AS order_date, m.business_name AS merchant_name,
                    o.subtotal_amount, o.processing_fee, o.platform_fee, o.sales_tax,
                    o.delivery_fee, o.tips, o.total_amount,
                    CASE WHEN o.sandbox_mode = 0
                        THEN (o.subtotal_amount - o.platform_fee + o.delivery_fee + o.tips)
                        ELSE 0 END AS estimated_merchant_payout,
                    o.state AS order_status, o.sandbox_mode';
        }

        return 'o.order_number, o.created_at AS order_date, m.business_name AS merchant_name,
                o.state AS order_status, o.total_amount, o.delivered_at, o.sandbox_mode';
    }
}
