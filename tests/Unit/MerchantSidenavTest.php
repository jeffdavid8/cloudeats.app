<?php

use PHPUnit\Framework\TestCase;

final class MerchantSidenavTest extends TestCase
{
    private function renderHeader(string $mode): string
    {
        $process = proc_open(
            [PHP_BINARY, '-d', 'short_open_tag=1', __DIR__ . '/../fixtures/merchant-sidenav-render-worker.php', $mode],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);
        return $output;
    }

    public function testStaffAndAdminsCanOpenMerchantMenu(): void
    {
        foreach (['staff', 'admin', 'merchant-view'] as $mode) {
            $html = $this->renderHeader($mode);
            self::assertStringContainsString('class="merchant-sidenav-trigger"', $html);
            self::assertStringContainsString('id="merchant-slide-out"', $html);
            self::assertStringContainsString('Store Settings &amp; Staff', $html);
            self::assertStringContainsString('Products', $html);
            if ($mode !== 'admin') self::assertStringNotContainsString('Neighborhub Admin', $html);
            else self::assertStringContainsString('Neighborhub Admin', $html);
        }
    }

    public function testOtherVisitorsDoNotReceiveManagementMenu(): void
    {
        foreach (['guest', 'other-staff', 'no-image', 'other-app'] as $mode) {
            $html = $this->renderHeader($mode);
            self::assertStringNotContainsString('merchant-sidenav-trigger', $html);
            self::assertStringNotContainsString('id="merchant-slide-out"', $html);
        }
        self::assertStringContainsString('scroll-to-top-link', $this->renderHeader('guest'));
    }

    public function testStatusDotReflectsOperationalHoursOnInitialRender(): void
    {
        foreach (['guest', 'merchant-view'] as $mode) {
            self::assertStringContainsString('data-merchant-id="8" aria-label="Open now"></span>', $this->renderHeader($mode));
        }
        foreach (['closed', 'merchant-closed'] as $mode) {
            self::assertStringContainsString('data-merchant-id="8" aria-label="Open now" hidden></span>', $this->renderHeader($mode));
        }
    }
}
