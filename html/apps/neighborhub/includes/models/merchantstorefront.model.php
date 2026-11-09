<?php

if (!defined('MB_RUNNING')) {
    exit;
}

class MerchantStorefront
{
    private const THEMES = ['default', 'pizza-king', 'bakery', 'diner'];
    private const LAYOUTS = ['hero-centered', 'hero-split', 'hero-minimal', 'hero-full'];
    private const LOGO_STYLES = ['default', 'original', 'circle', 'rectangle'];
    private const BLOCKS = [
        'announcement',
        'featured_product',
        'image_text',
        'call_to_action',
        'store_information',
    ];

    public static function themeDefinitions(): array
    {
        return [
            'default' => [
                'label' => 'Default',
                'image_slots' => [
                    'logo' => ['label' => 'Storefront logo', 'required' => false],
                    'hero' => ['label' => 'Hero image', 'required' => false],
                ],
            ],
            'pizza-king' => [
                'label' => 'Pizza King',
                'image_slots' => [
                    'logo' => ['label' => 'Storefront logo', 'required' => false],
                    'hero' => ['label' => 'Hero image', 'required' => false],
                ],
            ],
            'bakery' => [
                'label' => 'Bakery',
                'image_slots' => [
                    'logo' => ['label' => 'Storefront logo', 'required' => false],
                    'hero' => ['label' => 'Hero image', 'required' => false],
                ],
            ],
            'diner' => [
                'label' => 'Diner',
                'image_slots' => [
                    'logo' => ['label' => 'Storefront logo', 'required' => false],
                    'hero' => ['label' => 'Hero image', 'required' => false],
                ],
            ],
        ];
    }

    public static function getThemeDefinition(string $theme): array
    {
        return self::themeDefinitions()[$theme] ?? [
            'label' => $theme,
            'image_slots' => [],
        ];
    }

    public static function defaultConfiguration(): array
    {
        return [
            'theme' => 'default',
            'layout' => 'hero-centered',
            'logo_style' => 'default',
            'colors' => [
                'primary' => '#4f46e5',
                'background' => '#ffffff',
                'text' => '#111827',
            ],
            'typography' => [
                'heading' => 'system',
                'body' => 'system',
            ],
            'visibility' => [
                'search' => true,
                'contact' => true,
                'categories' => true,
                'operational_status' => true,
            ],
            'blocks' => [],
            'assets' => [
                'logo_id' => null,
                'hero_id' => null,
            ],
        ];
    }

    public static function getByMerchantId(int $merchantId, string $configuration = 'draft'): ?array
    {
        if ($merchantId < 1 || !in_array($configuration, ['draft', 'published'], true)) {
            throw new InvalidArgumentException('A valid merchant ID and configuration are required.');
        }

        $db = App::getInstance('neighborhub')->db;
        $column = $configuration === 'published' ? 'published_config' : 'draft_config';
        $stmt = $db->prepare("SELECT {$column} FROM neighborhub_merchant_storefronts WHERE merchant_id = ? LIMIT 1");
        $stmt->execute([$merchantId]);
        $value = $stmt->fetchColumn();

        if ($value === false) {
            return self::defaultConfiguration();
        }

        $decoded = json_decode((string)$value, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Stored storefront configuration is invalid JSON.');
        }
        return self::normalizeConfiguration($decoded);
    }

    public static function saveDraft(int $merchantId, array $configuration, ?int $updatedBy = null, bool $isAdmin = false): array
    {
        $configuration = self::validateConfiguration($merchantId, $configuration, $isAdmin);
        $json = json_encode($configuration, JSON_THROW_ON_ERROR);
        $db = App::getInstance('neighborhub')->db;
        $existing = self::recordExists($db, $merchantId);

        if ($existing) {
            $stmt = $db->prepare(
                'UPDATE neighborhub_merchant_storefronts
                 SET draft_config = ?, draft_updated_at = NOW(), updated_by = ?
                 WHERE merchant_id = ?'
            );
            $stmt->execute([$json, $updatedBy, $merchantId]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO neighborhub_merchant_storefronts
                 (merchant_id, draft_config, published_config, draft_updated_at, updated_by)
                 VALUES (?, ?, ?, NOW(), ?)'
            );
            $stmt->execute([$merchantId, $json, json_encode(self::defaultConfiguration(), JSON_THROW_ON_ERROR), $updatedBy]);
        }

        return ['success' => true, 'message' => 'Storefront draft saved.', 'configuration' => $configuration];
    }

    public static function publish(int $merchantId, ?int $updatedBy = null, bool $isAdmin = false): array
    {
        $db = App::getInstance('neighborhub')->db;
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT draft_config FROM neighborhub_merchant_storefronts WHERE merchant_id = ? FOR UPDATE');
            $stmt->execute([$merchantId]);
            $draft = $stmt->fetchColumn();
            if ($draft === false) {
                throw new RuntimeException('Save a storefront draft before publishing.');
            }
            $configuration = self::validateConfiguration($merchantId, json_decode((string)$draft, true), $isAdmin);
            $stmt = $db->prepare(
                'UPDATE neighborhub_merchant_storefronts
                 SET published_config = ?, published_at = NOW(), updated_by = ?
                 WHERE merchant_id = ?'
            );
            $stmt->execute([json_encode($configuration, JSON_THROW_ON_ERROR), $updatedBy, $merchantId]);
            $db->commit();
            return ['success' => true, 'message' => 'Storefront published.', 'configuration' => $configuration];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function revertDraft(int $merchantId, ?int $updatedBy = null): array
    {
        $db = App::getInstance('neighborhub')->db;
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'UPDATE neighborhub_merchant_storefronts
                 SET draft_config = published_config, draft_updated_at = NOW(), updated_by = ?
                 WHERE merchant_id = ?'
            );
            $stmt->execute([$updatedBy, $merchantId]);
            $db->commit();
            return ['success' => true, 'message' => 'Storefront draft reverted.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function validateConfiguration(int $merchantId, array $configuration, bool $isAdmin = false): array
    {
        if ($merchantId < 1) {
            throw new InvalidArgumentException('A valid merchant ID is required.');
        }
        $db = App::getInstance('neighborhub')->db;
        $configuration = self::normalizeConfiguration($configuration);
        if (!$isAdmin && !empty($configuration['custom_css'])) {
            throw new InvalidArgumentException('Custom CSS is restricted to platform administrators.');
        }
        if (!in_array($configuration['theme'], self::THEMES, true)) {
            throw new InvalidArgumentException('Unsupported storefront theme.');
        }
        if (!in_array($configuration['layout'], self::LAYOUTS, true)) {
            throw new InvalidArgumentException('Unsupported storefront layout.');
        }
        if (!in_array($configuration['logo_style'], self::LOGO_STYLES, true)) {
            throw new InvalidArgumentException('Unsupported storefront logo style.');
        }
        $themeDefinition = self::getThemeDefinition($configuration['theme']);
        foreach ($themeDefinition['image_slots'] as $slot => $definition) {
            $assetId = $configuration['assets'][$slot . '_id'] ?? null;
            if (!empty($definition['required']) && !$assetId) {
                throw new InvalidArgumentException($definition['label'] . ' is required for this theme.');
            }
            if ($assetId) {
                $stmt = $db->prepare(
                    "SELECT 1 FROM neighborhub_images
                     WHERE id = ? AND parent_type = 'merchant' AND parent_id = ? LIMIT 1"
                );
                $stmt->execute([(int)$assetId, $merchantId]);
                if ($stmt->fetchColumn() === false) {
                    throw new InvalidArgumentException($definition['label'] . ' does not belong to this merchant.');
                }
            }
        }
        foreach (['primary', 'background', 'text'] as $colorKey) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)($configuration['colors'][$colorKey] ?? ''))) {
                throw new InvalidArgumentException('Storefront colors must be six-digit hexadecimal values.');
            }
        }
        foreach ($configuration['blocks'] as $block) {
            if (!is_array($block) || !isset($block['type']) || !in_array($block['type'], self::BLOCKS, true)) {
                throw new InvalidArgumentException('Unsupported storefront block type.');
            }
            if (isset($block['content']) && !is_array($block['content'])) {
                throw new InvalidArgumentException('Storefront block content must be an object.');
            }
            $content = $block['content'] ?? [];
            if ($block['type'] === 'featured_product' && !empty($content['product_id'])) {
                $stmt = $db->prepare('SELECT 1 FROM neighborhub_products WHERE id = ? AND merchant_id = ? LIMIT 1');
                $stmt->execute([(int)$content['product_id'], $merchantId]);
                if ($stmt->fetchColumn() === false) {
                    throw new InvalidArgumentException('Featured product does not belong to this merchant.');
                }
            }
            if ($block['type'] === 'image_text' && !empty($content['image_id'])) {
                $stmt = $db->prepare(
                    "SELECT 1 FROM neighborhub_images
                     WHERE id = ? AND parent_type = 'merchant' AND parent_id = ? LIMIT 1"
                );
                $stmt->execute([(int)$content['image_id'], $merchantId]);
                if ($stmt->fetchColumn() === false) {
                    throw new InvalidArgumentException('Storefront image does not belong to this merchant.');
                }
            }
        }
        return $configuration;
    }

    public static function normalizeConfiguration(array $configuration): array
    {
        $defaults = self::defaultConfiguration();
        $configuration = array_replace_recursive($defaults, $configuration);
        $configuration['theme'] = (string)$configuration['theme'];
        $configuration['layout'] = (string)$configuration['layout'];
        $configuration['logo_style'] = (string)$configuration['logo_style'];
        $configuration['blocks'] = is_array($configuration['blocks']) ? array_values($configuration['blocks']) : [];
        unset($configuration['asset_urls']);
        return $configuration;
    }

    public static function assetIsReferenced(int $merchantId, int $assetId): bool
    {
        $db = App::getInstance('neighborhub')->db;
        $stmt = $db->prepare(
            "SELECT draft_config, published_config
             FROM neighborhub_merchant_storefronts
             WHERE merchant_id = ? LIMIT 1"
        );
        $stmt->execute([$merchantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        foreach (['draft_config', 'published_config'] as $column) {
            $config = json_decode((string)($row[$column] ?? ''), true);
            if (!is_array($config)) {
                continue;
            }
            if (in_array($assetId, [
                (int)($config['assets']['logo_id'] ?? 0),
                (int)($config['assets']['hero_id'] ?? 0),
            ], true)) {
                return true;
            }
            foreach ($config['blocks'] ?? [] as $block) {
                if ((int)($block['content']['image_id'] ?? 0) === $assetId) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function recordExists(PDO $db, int $merchantId): bool
    {
        $stmt = $db->prepare('SELECT 1 FROM neighborhub_merchant_storefronts WHERE merchant_id = ? LIMIT 1');
        $stmt->execute([$merchantId]);
        return $stmt->fetchColumn() !== false;
    }
}
