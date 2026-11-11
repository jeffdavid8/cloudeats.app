<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/apps/neighborhub/includes/models/merchantstorefront.model.php';

final class MerchantStorefrontTest extends TestCase
{
    public function testDefaultConfigurationContainsSafeStorefrontControls(): void
    {
        $configuration = MerchantStorefront::defaultConfiguration();

        self::assertSame('default', $configuration['theme']);
        self::assertSame('default', $configuration['logo_style']);
        self::assertTrue($configuration['visibility']['operational_status']);
        self::assertSame([], $configuration['blocks']);
    }

    public function testConfigurationIsNormalizedWithDefaults(): void
    {
        $configuration = MerchantStorefront::normalizeConfiguration([
            'theme' => 'bakery',
            'visibility' => ['search' => false],
        ]);

        self::assertSame('bakery', $configuration['theme']);
        self::assertFalse($configuration['visibility']['search']);
        self::assertTrue($configuration['visibility']['contact']);
        self::assertSame('#ffffff', $configuration['colors']['background']);
        self::assertSame('default', $configuration['logo_style']);
    }

    public function testUnsupportedThemeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MerchantStorefront::validateConfiguration(12, ['theme' => 'not-a-theme']);
    }

    public function testFullHeroLayoutIsAccepted(): void
    {
        $configuration = MerchantStorefront::validateConfiguration(12, ['layout' => 'hero-full']);

        self::assertSame('hero-full', $configuration['layout']);
    }

    public function testLogoStylesAreAccepted(): void
    {
        foreach (['default', 'original', 'circle', 'rectangle'] as $style) {
            $configuration = MerchantStorefront::validateConfiguration(12, ['logo_style' => $style]);
            self::assertSame($style, $configuration['logo_style']);
        }
    }

    public function testUnsupportedLogoStyleIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported storefront logo style.');
        MerchantStorefront::validateConfiguration(12, ['logo_style' => 'invalid']);
    }

    public function testCustomCssIsRestrictedToAdministrators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MerchantStorefront::validateConfiguration(12, ['custom_css' => '.x{color:red}']);
    }
}
