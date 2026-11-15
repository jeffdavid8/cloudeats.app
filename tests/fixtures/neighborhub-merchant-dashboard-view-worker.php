<?php

define('MB_RUNNING', true);
$_SESSION['user'] = [
    'id' => 7,
    'username' => 'Merchant Staff',
    'active_merchant_id' => 10,
    'merchant_staff_role' => 'owner',
];

class Merchant
{
    public static function getAllMenuCategories($merchantId): array
    {
        return [];
    }
}

class DashboardViewContext
{
    private array $data;

    public function __construct()
    {
        $this->data = [
            'merchant' => (object)['id' => 10, 'business_name' => 'Test Merchant', 'status' => 'active'],
            'pending_orders' => [['html' => '<article>Pending order card</article>']],
            'confirmed_orders' => [['html' => '<article>Confirmed order card</article>']],
            'ready_orders' => [['html' => '<article>Ready order card</article>']],
            'staff_team' => [],
        ];
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function render(): void
    {
        include __DIR__ . '/../../html/apps/neighborhub/views/pages/merchant/merchant_dashboard.php';
    }
}

$context = new DashboardViewContext();
ob_start();
$context->render();
echo ob_get_clean();
