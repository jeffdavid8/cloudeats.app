<?php

namespace MediaBrain\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MenuModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../html/apps/neighborhub/includes/models/menu.model.php';
    }

    public function testGetMenusWithProductsByMerchantIdReturnsNestedCategoriesAndProducts(): void
    {
        $rows = [
            [
                'menu_id' => 10,
                'merchant_id' => 42,
                'menu_name' => 'Lunch Menu',
                'menu_description' => 'Daily menu',
                'menu_is_active' => 1,
                'menu_sort_order' => 1,
                'category_id' => 20,
                'category_name' => 'Sandwiches',
                'category_sort_order' => 1,
                'category_status' => 'active',
                'product_id' => 30,
                'product_name' => 'Turkey Club',
                'product_price' => 8.5,
                'product_meta' => '{"sku":"TC-01"}',
                'description' => 'Grilled turkey sandwich',
                'image_url' => '/images/turkey-club.jpg',
                'tags' => 'lunch,sandwich',
                'is_available' => 1,
                'menu_item_id' => 100,
                'item_sort_order' => 1,
            ],
        ];

        $statement = new class($rows) {
            private array $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function execute(array $params = []): bool
            {
                return true;
            }

            public function fetchAll(int $mode = 0): array
            {
                return $this->rows;
            }
        };

        $db = new class($statement) {
            private $statement;
            public string $query = '';

            public function __construct($statement)
            {
                $this->statement = $statement;
            }

            public function prepare(string $query): object
            {
                $this->query = $query;
                return $this->statement;
            }
        };

        $app = \App::getInstance();
        $app->db = $db;

        $menus = \Menu::getMenusWithProductsByMerchantId(42, true);

        $this->assertCount(1, $menus);
        $this->assertSame('Lunch Menu', $menus[10]['name']);
        $this->assertCount(1, $menus[10]['categories']);
        $this->assertSame('Sandwiches', $menus[10]['categories'][20]['name']);
        $this->assertCount(1, $menus[10]['categories'][20]['products']);
        $this->assertSame('Turkey Club', $menus[10]['categories'][20]['products'][0]['name']);
        $this->assertSame('TC-01', $menus[10]['categories'][20]['products'][0]['sku']);
        $this->assertSame(8.5, $menus[10]['categories'][20]['products'][0]['price']);
        $this->assertStringContainsString('m.is_active = 1', $app->db->query);
        $this->assertStringContainsString("mc.status = 'active'", $app->db->query);
        $this->assertStringContainsString('mi.is_available = 1', $app->db->query);
    }

    public function testUpdateStatusPersistsTheVisibilityColumn(): void
    {
        $statement = new class {
            public array $params = [];

            public function execute(array $params = []): bool
            {
                $this->params = $params;
                return true;
            }
        };
        $db = new class($statement) {
            private $statement;
            public string $query = '';

            public function __construct($statement)
            {
                $this->statement = $statement;
            }

            public function prepare(string $query): object
            {
                $this->query = $query;
                return $this->statement;
            }
        };
        \App::getInstance()->db = $db;

        $this->assertTrue(\Menu::updateStatus(12, '0'));
        $this->assertStringContainsString('is_active = ?', $db->query);
        $this->assertSame([0, 12], $statement->params);
        $this->assertFalse(\Menu::updateStatus(12, 'inactive'));
    }

    public function testActiveAssignmentLookupRequiresActiveParentCategoryAndItem(): void
    {
        $statement = new class {
            public function execute(array $params = []): bool
            {
                return true;
            }

            public function fetchColumn()
            {
                return 1;
            }
        };
        $db = new class($statement) {
            private $statement;
            public string $query = '';

            public function __construct($statement)
            {
                $this->statement = $statement;
            }

            public function prepare(string $query): object
            {
                $this->query = $query;
                return $this->statement;
            }
        };
        \App::getInstance()->db = $db;

        $this->assertTrue(\Menu::productHasActiveMenuAssignment(30, 42));
        $this->assertStringContainsString('mi.is_available = 1', $db->query);
        $this->assertStringContainsString("mc.status = 'active'", $db->query);
        $this->assertStringContainsString('m.is_active = 1', $db->query);
    }

    public function testGroupedCustomerProductsFilterInactiveMenusAndCategories(): void
    {
        $statement = new class {
            public function execute(array $params = []): bool
            {
                return true;
            }

            public function fetchAll(int $mode = 0): array
            {
                return [];
            }
        };
        $db = new class($statement) {
            private $statement;
            public string $query = '';

            public function __construct($statement)
            {
                $this->statement = $statement;
            }

            public function prepare(string $query): object
            {
                $this->query = $query;
                return $this->statement;
            }
        };
        \App::getInstance()->db = $db;

        $this->assertSame([], \Menu::getProductsGroupedByCategory(10, true));
        $this->assertStringContainsString('m.is_active = 1', $db->query);
        $this->assertStringContainsString("mc.status = 'active'", $db->query);
        $this->assertStringContainsString('mi.is_available = 1', $db->query);
        $this->assertStringContainsString('p.is_available = 1', $db->query);
    }
}
