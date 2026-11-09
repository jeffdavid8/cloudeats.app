<?php

use PHPUnit\Framework\TestCase;

final class NeighborhubOperationalApiTest extends TestCase
{
    private function request(string $action, string $merchantId): array
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/../fixtures/neighborhub-operational-api-worker.php', $action, $merchantId],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $body = stream_get_contents($pipes[1]);
        $status = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $status);
        [$httpStatus, $sessionStatus] = array_map('intval', explode(':', $status));
        return [$httpStatus, json_decode($body, true, 512, JSON_THROW_ON_ERROR), $sessionStatus];
    }

    public function testStatusEndpointReturnsCurrentStatusAndValidatesMerchant(): void
    {
        [$status, $body, $sessionStatus] = $this->request('get_merchant_operational_status', '10');
        self::assertSame(200, $status);
        self::assertSame(PHP_SESSION_NONE, $sessionStatus);
        self::assertSame(10, $body['merchant_id']);
        self::assertSame('closed', $body['operational_status']['status']);
        self::assertSame(400, $this->request('get_merchant_operational_status', 'invalid')[0]);
        self::assertSame(404, $this->request('get_merchant_operational_status', '11')[0]);
    }

    public function testCheckoutRejectsClosedSandboxMerchant(): void
    {
        [$status, $body, $sessionStatus] = $this->request('create_checkout_session', '10');
        self::assertSame(409, $status);
        self::assertSame(PHP_SESSION_ACTIVE, $sessionStatus);
        self::assertFalse($body['success']);
        self::assertStringContainsString('not currently accepting orders', $body['error']);
    }
}
