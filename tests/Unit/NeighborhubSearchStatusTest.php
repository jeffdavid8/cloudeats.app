<?php

use PHPUnit\Framework\TestCase;

final class NeighborhubSearchStatusTest extends TestCase
{
    public function testSearchReturnsSharedServerOperationalStatuses(): void
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/../fixtures/neighborhub-search-api-worker.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $body = stream_get_contents($pipes[1]);
        $status = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $status);
        self::assertSame('200', $status);
        $result = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['success']);
        self::assertSame(5, $result['count']);
        $merchants = array_column($result['merchants'], null, 'id');
        self::assertSame('open', $merchants[1]['operational_status']['status']);
        self::assertSame('closed', $merchants[2]['operational_status']['status']);
        self::assertSame('closed', $merchants[3]['operational_status']['status']);
        self::assertSame('Hours not listed', $merchants[4]['operational_status']['message']);
        self::assertSame('Hours not listed', $merchants[5]['operational_status']['message']);
        self::assertSame(4.5, (float)$merchants[1]['delivery_fee']);
        self::assertSame('Main St, Marion, IN', $merchants[1]['full_address']);
    }
}
