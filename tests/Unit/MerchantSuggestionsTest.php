<?php

use PHPUnit\Framework\TestCase;

final class MerchantSuggestionsTest extends TestCase
{
    private function request(string $query): array
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/../fixtures/merchant-suggestions-api-worker.php', $query],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $body = stream_get_contents($pipes[1]);
        $status = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $status);
        return [(int)$status, json_decode($body, true, 512, JSON_THROW_ON_ERROR)];
    }

    public function testNameAndAddressMatchOnlyPublicMerchants(): void
    {
        [$status, $result] = $this->request('pizza');
        self::assertSame(200, $status);
        self::assertSame([5, 1, 2], array_column($result['merchants'], 'id'));
        self::assertSame(['id', 'business_name', 'address', 'city', 'state', 'zip_code'], array_keys($result['merchants'][0]));
        [, $address] = $this->request('123 Main, Marion');
        self::assertSame([1], array_column($address['merchants'], 'id'));
        [, $limited] = $this->request('bakery');
        self::assertSame([6, 7, 8, 9, 10], array_column($limited['merchants'], 'id'));
    }

    public function testWildcardCharactersAreLiteralAndBadInputRejected(): void
    {
        [, $result] = $this->request('100%');
        self::assertSame([5], array_column($result['merchants'], 'id'));
        [$status, $result] = $this->request('pi');
        self::assertSame(400, $status);
        self::assertFalse($result['success']);
        [$status] = $this->request(str_repeat('x', 161));
        self::assertSame(400, $status);
    }
}
