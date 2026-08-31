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
                'product_id' => 30,
                'product_name' => 'Turkey Club',
                'product_price' => 8.5,
                'product_meta' => '{"sku":"TC-01"}',
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

            public function __construct($statement)
            {
                $this->statement = $statement;
            }

            public function prepare(string $query): object
            {
                return $this->statement;
            }
        };

        $app = \App::getInstance();
        $app->db = $db;

        $menus = \Menu::getMenusWithProductsByMerchantId(42);

        $this->assertCount(1, $menus);
        $this->assertSame('Lunch Menu', $menus[0]['name']);
        $this->assertCount(1, $menus[0]['categories']);
        $this->assertSame('Sandwiches', $menus[0]['categories'][0]['name']);
        $this->assertCount(1, $menus[0]['categories'][0]['products']);
        $this->assertSame('Turkey Club', $menus[0]['categories'][0]['products'][0]['name']);
        $this->assertSame('TC-01', $menus[0]['categories'][0]['products'][0]['sku']);
        $this->assertSame(8.5, $menus[0]['categories'][0]['products'][0]['price']);
    }
}
