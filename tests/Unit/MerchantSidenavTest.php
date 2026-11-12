<?php

use PHPUnit\Framework\TestCase;

final class MerchantSidenavTest extends TestCase
{
    private function renderHeader(string $mode, string $headerRight = 'neighborhub'): string
    {
        $process = proc_open(
            [PHP_BINARY, '-d', 'short_open_tag=1', __DIR__ . '/../fixtures/merchant-sidenav-render-worker.php', $mode, $headerRight],
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

    public function testMerchantTriggerIsBeforeBasketInBothRightHeaders(): void
    {
        foreach (['neighborhub', 'global'] as $headerRight) {
            $html = $this->renderHeader('staff', $headerRight);
            $left = strpos($html, 'class="header-left"');
            $right = strpos($html, 'class="header-right"');
            $trigger = strpos($html, 'class="merchant-sidenav-trigger"');
            $basket = strpos($html, 'class="nh-header-cart');
            self::assertIsInt($left);
            self::assertIsInt($right);
            self::assertIsInt($trigger);
            self::assertIsInt($basket);
            self::assertLessThan($right, $left);
            self::assertLessThan($trigger, $right);
            self::assertLessThan($basket, $trigger);
            self::assertSame(1, substr_count($html, 'class="merchant-sidenav-trigger"'));
            self::assertStringContainsString('id="merchant-slide-out" class="sidenav right-aligned"', $html);
            self::assertStringContainsString('data-merchant-id="8" aria-label="Open now"></span>', $html);
            self::assertStringContainsString('data-merchant-id="8" aria-label="Open now" hidden></span>', $this->renderHeader('staff-closed', $headerRight));
            $withoutBasket = $this->renderHeader('merchant-view', $headerRight);
            self::assertLessThan(strpos($withoutBasket, 'class="merchant-sidenav-trigger"'), strpos($withoutBasket, 'class="header-right"'));
            self::assertStringNotContainsString('class="nh-header-cart', $withoutBasket);
        }
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
