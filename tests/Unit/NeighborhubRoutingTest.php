<?php

use PHPUnit\Framework\TestCase;

final class NeighborhubRoutingTest extends TestCase
{
    public function testCustomerAndMerchantRouting(): void
    {
        foreach ([
            'guest-home' => [true, 200],
            'guest-login' => [true, 200],
            'guest-storefront' => [true, 200],
            'signed-in-storefront' => [true, 200],
            'signed-in-dashboard' => [true, 200],
            'guest-dashboard' => [false, 302],
            'signed-in-merchant' => [false, 302],
        ] as $mode => [$completed, $status]) {
            $process = proc_open(
                [PHP_BINARY, '-d', 'short_open_tag=1', __DIR__ . '/../fixtures/neighborhub-routing-worker.php', $mode],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $mode . ': ' . $error);
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($completed, $result['completed'], $mode);
            self::assertSame($status, $result['status'], $mode);
            self::assertCount($completed ? 0 : 1, $result['notifications'], $mode);
            if (!$completed) {
                self::assertSame('error', $result['notifications'][0]['type'], $mode);
            }
        }
    }
}
