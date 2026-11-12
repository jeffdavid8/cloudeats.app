<?php

use PHPUnit\Framework\TestCase;

if (!defined('MB_RUNNING')) {
    define('MB_RUNNING', true);
}
require_once __DIR__ . '/../../html/apps/neighborhub/neighborhub.app.php';

final class NeighborhubCustomerStylesTest extends TestCase
{
    public function testLegacyStylesRequireTheMatchingStorefrontOverride(): void
    {
        $appDir = realpath(__DIR__ . '/../../html/apps/neighborhub');
        self::assertIsString($appDir);
        $style = 'apps/neighborhub/css/merchant_10/merchant-10-customer.css';
        self::assertSame([$style], neighborhub_customer_override_styles($appDir, 'merchant_products', 10, 'default'));
        self::assertSame([], neighborhub_customer_override_styles($appDir, 'merchant_products', 10, 'diner'));
        self::assertSame([], neighborhub_customer_override_styles($appDir, 'onboarding', 10, 'default'));
        self::assertSame([], neighborhub_customer_override_styles($appDir, 'merchant_products', 999999, 'default'));
        self::assertSame([], neighborhub_customer_override_styles($appDir, 'merchant_products', 0, 'default'));
    }
}
