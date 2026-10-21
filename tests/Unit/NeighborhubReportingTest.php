<?php

namespace MediaBrain\Tests\Unit;

use InvalidArgumentException;
use DomainException;
use PHPUnit\Framework\TestCase;

class NeighborhubReportingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();        
        require_once __DIR__ . '/../../html/apps/neighborhub/includes/classes/NeighborhubReporting.class.php';
    }

    public function testValidateDateRangeReturnsNormalizedDates(): void
    {
        $range = \NeighborhubReporting::validateDateRange([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
        ]);

        $this->assertSame([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
        ], $range);
    }

    public function testValidateDateRangeRejectsInvalidAndOversizedRanges(): void
    {
        $this->expectException(InvalidArgumentException::class);
        \NeighborhubReporting::validateDateRange([
            'start_date' => '2026-02-30',
            'end_date' => '2026-03-01',
        ]);
    }

    public function testValidateDateRangeRejectsRangesPastMaximumWindow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        \NeighborhubReporting::validateDateRange([
            'start_date' => '2025-01-01',
            'end_date' => '2026-10-20',
        ], 90);
    }

    public function testEscapeCsvCellNeutralizesFormulaInputWithoutChangingNumbers(): void
    {
        $this->assertSame("'=1+1", \NeighborhubReporting::escapeCsvCell('=1+1'));
        $this->assertSame("'\t@SUM(A1:A2)", \NeighborhubReporting::escapeCsvCell("\t@SUM(A1:A2)"));
        $this->assertSame('-42.50', \NeighborhubReporting::escapeCsvCell('-42.50'));
        $this->assertSame('Turkey sandwich', \NeighborhubReporting::escapeCsvCell('Turkey sandwich'));
    }

    public function testCustomerReportScopeUsesSignedInCustomerInsteadOfSubmittedMerchantId(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);
        $reporting->getOrderReport('customer', 17, false, [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
            'merchant_id' => 999,
        ]);

        $this->assertStringContainsString(
            'o.customer_id = (SELECT c.id FROM neighborhub_customers c WHERE c.user_id = :scope_user_id',
            $db->queries[0]
        );
        $this->assertStringNotContainsString('requested_merchant_id', $db->queries[0]);
        $this->assertStringNotContainsString('999', $db->queries[0]);
    }

    public function testMerchantReportRequiresAnActiveStaffOrOwnerRelationship(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);
        $reporting->getOrderReport('merchant', 17, false, [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
            'merchant_id' => 42,
        ]);

        $this->assertStringContainsString('neighborhub_merchant_users mu', $db->queries[0]);
        $this->assertStringContainsString("mu.status = 'active'", $db->queries[0]);
        $this->assertStringContainsString("mu.staff_role IN ('owner', 'staff')", $db->queries[0]);
        $this->assertStringContainsString('o.merchant_id = :requested_merchant_id', $db->queries[0]);
    }

    public function testLineItemReportUsesStableOrderingAndPayoutsUseRecordedCheckoutFees(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);
        $filters = [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
        ];

        $reporting->getOrderReport('admin', 1, true, $filters + ['report_type' => 'line_items']);
        $this->assertStringContainsString('ORDER BY o.created_at DESC, o.id DESC, oi.id DESC', $db->queries[1]);

        $reporting->getOrderReport('admin', 1, true, $filters + ['report_type' => 'payouts']);
        $this->assertStringContainsString(
            '(o.subtotal_amount - o.platform_fee + o.delivery_fee + o.tips) AS estimated_merchant_payout',
            $db->queries[3]
        );
    }

    public function testAdminReportCannotBeRequestedWithoutAdminAuthorization(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);

        $this->expectException(DomainException::class);
        $reporting->getOrderReport('admin', 17, false, [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
        ]);
    }

    public function testUsageAnalyticsRejectNonAdminUsers(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);

        $this->expectException(DomainException::class);
        $reporting->getAnalyticsReport([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
        ], false);
    }

    public function testMerchantAnalyticsRequiresAccessAndScopesEveryEventQuery(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $db->columnValue = 1;
        $reporting = new \NeighborhubReporting($db);
        $today = new \DateTimeImmutable('today');
        $filters = [
            'start_date' => $today->modify('-29 days')->format('Y-m-d'),
            'end_date' => $today->format('Y-m-d'),
        ];

        $analytics = $reporting->getAnalyticsReport($filters, false, 17, 42);

        $this->assertStringContainsString('neighborhub_merchant_users mu', $db->queries[0]);
        $this->assertStringContainsString('merchant_id = :analytics_merchant_id', $db->queries[1]);
        $this->assertStringContainsString('merchant_id = :analytics_merchant_id', $db->queries[2]);
        $this->assertStringContainsString('merchant_id = :analytics_merchant_id', $db->queries[3]);
        $this->assertStringContainsString('GROUP BY JSON_UNQUOTE', $db->queries[2]);
        $this->assertStringNotContainsString('. $scopeSql .', $db->queries[2]);
        $this->assertSame(42, $db->executions[1][':analytics_merchant_id']);
        $this->assertArrayNotHasKey('merchant_search', $analytics['funnel']);
        $this->assertSame(42, $analytics['filters']['merchant_id']);

        $export = $reporting->getAnalyticsExport($filters, false, 'analytics_events', 17, 42);
        $this->assertSame(42, $export['filters']['merchant_id']);
        $this->assertStringContainsString('merchant_id = :analytics_merchant_id', $db->queries[8]);
        $this->assertSame(42, $db->executions[8][':analytics_merchant_id']);
    }

    public function testMerchantAnalyticsRejectsUnauthorizedMerchantSelection(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);

        $this->expectException(DomainException::class);
        $reporting->getAnalyticsReport([], false, 17, 42);
    }

    public function testAdminAnalyticsIncludesTrendFunnelAndOutcomeSummaries(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);
        $today = new \DateTimeImmutable('today');
        $filters = [
            'start_date' => $today->modify('-29 days')->format('Y-m-d'),
            'end_date' => $today->format('Y-m-d'),
        ];

        $analytics = $reporting->getAnalyticsReport($filters, true);

        $this->assertSame(0, $analytics['funnel']['checkout_started']);
        $this->assertSame([], $analytics['outcomes']);
        $this->assertSame([], $analytics['trend']);
        $this->assertStringContainsString('JSON_EXTRACT(properties', $db->queries[1]);

        $export = $reporting->getAnalyticsExport($filters, true, 'analytics_summary');
        $this->assertArrayHasKey('funnel', $export['summary']);
        $this->assertArrayHasKey('outcomes', $export['summary']);
    }

    public function testUsageEventsKeepOnlyAllowlistedProperties(): void
    {
        $db = new NeighborhubReportingTestPdo();
        $reporting = new \NeighborhubReporting($db);

        $this->assertTrue($reporting->recordEvent('merchant_search', 'guest', [
            'result_count' => 3,
            'search_text' => 'private query',
            'ip_address' => '192.0.2.10',
        ]));

        $properties = json_decode($db->executions[0][':properties'], true);
        $this->assertSame(['result_count' => 3], $properties);
        $this->assertSame('guest', $db->executions[0][':actor_role']);

        $reporting->recordEvent('product_view', 'customer', [], null, 42);
        $this->assertSame(42, $db->executions[1][':merchant_id']);
    }
}

class NeighborhubReportingTestPdo extends \PDO
{
    public array $queries = [];
    public array $executions = [];
    public mixed $columnValue = 0;

    public function __construct()
    {
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        $this->queries[] = $query;
        return new NeighborhubReportingTestStatement($this);
    }

    public function exec(string $statement): int|false
    {
        return 0;
    }
}

class NeighborhubReportingTestStatement extends \PDOStatement
{
    private NeighborhubReportingTestPdo $db;

    public function __construct(NeighborhubReportingTestPdo $db)
    {
        $this->db = $db;
    }

    public function execute(?array $params = null): bool
    {
        $this->db->executions[] = $params ?? [];
        return true;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->db->columnValue;
    }

    public function bindValue(string|int $param, mixed $value, int $type = \PDO::PARAM_STR): bool
    {
        return true;
    }

    public function fetchAll(int $mode = \PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return [];
    }

    public function rowCount(): int
    {
        return 1;
    }
}
