<?php

define('MB_RUNNING', true);

class HeroFixture
{
    public function get($name)
    {
        return (object)['business_name' => 'Example Merchant', 'image_url' => '/logo.png'];
    }

    public function render(string $layout, bool $hasHeroImage, string $logoStyle): void
    {
        $configuration = [
            'layout' => $layout,
            'logo_style' => $logoStyle,
            'assets' => $hasHeroImage ? ['hero_id' => 1] : [],
            'asset_urls' => $hasHeroImage ? [1 => '/hero.jpg'] : [],
        ];
        require __DIR__ . '/../../html/apps/neighborhub/views/storefront/components/merchant_hero.php';
    }
}

(new HeroFixture())->render($argv[1], ($argv[2] ?? '') === 'hero-image', $argv[3] ?? 'default');
