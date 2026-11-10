<?php

use PHPUnit\Framework\TestCase;

final class MerchantProfileImageApiTest extends TestCase
{
    private function request(string $id, string $role = 'admin'): array
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/../fixtures/merchant-image-api-worker.php', $id, $role],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $body = stream_get_contents($pipes[1]);
        $details = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $details);
        $lines = explode("\n", trim($details));
        return [(int)array_pop($lines), json_decode($body, true, 512, JSON_THROW_ON_ERROR), implode("\n", $lines)];
    }

    public function testProfileImageMustBeInTheMerchantGallery(): void
    {
        [$status, $body, $updated] = $this->request('51');
        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('/own.png', json_decode($updated, true)['image_url']);

        [$status, $body, $updated] = $this->request('52');
        self::assertSame(400, $status);
        self::assertFalse($body['success']);
        self::assertSame('', $updated);

        [$status, $body, $updated] = $this->request('51', 'unrelated');
        self::assertSame(403, $status);
        self::assertFalse($body['success']);
        self::assertSame('', $updated);
    }

    public function testUploadReturnsTheUploadedAssetRatherThanAConcurrentUpload(): void
    {
        [$status, $body] = $this->request('upload');
        self::assertSame(200, $status);
        self::assertSame(53, (int)$body['image']['id']);
        self::assertSame('/uploaded.png', $body['image']['image_url']);

        [$status, $body, $updated] = $this->request('invalid');
        self::assertSame(400, $status);
        self::assertFalse($body['success']);
        self::assertSame('', $updated);
    }
}
