<?php

use PHPUnit\Framework\TestCase;

final class NeighborhubMerchantCheckoutTest extends TestCase
{
    private function runWorker(string $worker, string $mode = ''): array
    {
        $command = [PHP_BINARY, '-d', 'short_open_tag=1', __DIR__ . '/../fixtures/' . $worker];
        if ($mode !== '') {
            $command[] = $mode;
        }

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);

        return [$output, $error];
    }

    public function testMerchantPosRendersCatalogAndReturnsToPosAfterCheckout(): void
    {
        [$html] = $this->runWorker('neighborhub-merchant-pos-view-worker.php');

        self::assertStringContainsString('id="pos-shell"', $html);
        self::assertStringContainsString('id="pos-tile-1"', $html);
        self::assertStringContainsString('data-name="Test Sandwich"', $html);
        self::assertStringContainsString('$10.00', $html);
        self::assertStringContainsString('https://example.test/?app=neighborhub&view=merchant&p=pos&merchant_id=10', $html);
    }

    public function testSandboxCheckoutUsesCatalogPricingAndCreatesPendingCheckoutContext(): void
    {
        [$body, $state] = $this->runWorker('neighborhub-merchant-checkout-worker.php', 'success');
        $response = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $state = json_decode($state, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $state['status']);
        self::assertTrue($response['success']);
        self::assertStringContainsString('checkout_success', $response['checkout_url']);
        self::assertSame(42, $state['session']['customer_id']);
        $pendingOrders = array_filter($state['session'], 'is_array');
        self::assertCount(1, $pendingOrders);
        $pendingOrder = array_values($pendingOrders)[0];
        self::assertEquals(20, $pendingOrder['subtotal_amount']);
        self::assertSame(1.65, $pendingOrder['sales_tax']);
        self::assertEquals(10, $pendingOrder['merchant_package'][0]['price_at_order']);
        self::assertSame(2, $pendingOrder['merchant_package'][0]['quantity']);
    }

    public function testCheckoutRejectsClosedMerchantAndUnavailableProduct(): void
    {
        [$closedBody, $closedState] = $this->runWorker('neighborhub-merchant-checkout-worker.php', 'closed');
        self::assertSame(409, json_decode($closedState, true, 512, JSON_THROW_ON_ERROR)['status']);
        self::assertFalse(json_decode($closedBody, true, 512, JSON_THROW_ON_ERROR)['success']);

        [$unavailableBody, $unavailableState] = $this->runWorker('neighborhub-merchant-checkout-worker.php', 'unavailable');
        self::assertSame(409, json_decode($unavailableState, true, 512, JSON_THROW_ON_ERROR)['status']);
        self::assertFalse(json_decode($unavailableBody, true, 512, JSON_THROW_ON_ERROR)['success']);
        self::assertStringContainsString('no longer available', json_decode($unavailableBody, true, 512, JSON_THROW_ON_ERROR)['error']);
    }

    public function testSuccessfulCheckoutCompletionPersistsPendingOrder(): void
    {
        [$output] = $this->runWorker('neighborhub-merchant-checkout-completion-worker.php');
        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($result['context']['checkout_order_saved']);
        self::assertSame('success', $result['notifications'][0]['type']);
        self::assertSame(55, $result['order_id']);
        self::assertSame(10, $result['create_args'][1]);
        self::assertEquals(20, $result['create_args'][3]);
        self::assertSame('PENDING_CONFIRMATION', $result['create_args'][14]);
        self::assertTrue($result['create_args'][17]);
    }

    public function testMerchantDashboardRendersOrderQueuesAndStoreStatus(): void
    {
        [$html] = $this->runWorker('neighborhub-merchant-dashboard-view-worker.php');

        self::assertStringContainsString('data-orders-section="pending"', $html);
        self::assertStringContainsString('data-orders-section="confirmed"', $html);
        self::assertStringContainsString('data-orders-section="ready"', $html);
        self::assertStringContainsString('data-pending-count>1</span>', $html);
        self::assertStringContainsString('data-confirmed-count>1</span>', $html);
        self::assertStringContainsString('data-ready-count>1</span>', $html);
        self::assertStringContainsString('STORE ONLINE', $html);
        self::assertStringContainsString('Pending order card', $html);
        self::assertStringContainsString('Confirmed order card', $html);
        self::assertStringContainsString('Ready order card', $html);
    }
}
