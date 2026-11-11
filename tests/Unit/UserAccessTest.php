<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/includes/User.php';

final class UserAccessTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('
            CREATE TABLE neighborhub_merchants (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL
            );
            CREATE TABLE neighborhub_merchant_users (
                id INTEGER PRIMARY KEY,
                merchant_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                staff_role TEXT NOT NULL,
                status TEXT NOT NULL
            );
            CREATE TABLE neighborhub_customers (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL
            )
        ');
        App::getInstance()->db = $this->db;
    }

    public function testAdminUsesFlagOrRole(): void
    {
        self::assertTrue((new User(['is_admin' => true]))->isAdmin());
        self::assertTrue((new User(['role' => 'ADMIN']))->isAdmin());
        self::assertFalse((new User(['role' => 'user']))->isAdmin());
    }

    public function testMerchantChecksSupportOwnershipAndActiveStaff(): void
    {
        $this->db->exec("
            INSERT INTO neighborhub_merchants (id, user_id) VALUES (10, 1), (20, 2);
            INSERT INTO neighborhub_merchant_users
                (id, merchant_id, user_id, staff_role, status)
            VALUES
                (1, 20, 3, 'staff', 'active'),
                (2, 20, 4, 'owner', 'active'),
                (3, 20, 5, 'staff', 'inactive')
        ");

        $owner = new User(['id' => 1]);
        $staff = new User(['id' => 3]);
        $merchantAdmin = new User(['id' => 4]);
        $inactiveStaff = new User(['id' => 5]);

        self::assertTrue($owner->isMerchantUser(10));
        self::assertTrue($owner->isMerchantAdmin(10));
        self::assertTrue($staff->isMerchantUser(20));
        self::assertFalse($staff->isMerchantAdmin(20));
        self::assertTrue($merchantAdmin->isMerchantAdmin(20));
        self::assertFalse($inactiveStaff->isMerchantUser(20));
    }

    public function testMerchantChecksCanSearchAcrossMerchants(): void
    {
        $this->db->exec("
            INSERT INTO neighborhub_merchants (id, user_id) VALUES (10, 1);
            INSERT INTO neighborhub_merchant_users
                (id, merchant_id, user_id, staff_role, status)
            VALUES (1, 10, 3, 'staff', 'active')
        ");

        $staff = new User(['id' => 3]);

        self::assertTrue($staff->isMerchantUser());
        self::assertFalse($staff->isMerchantAdmin());
    }

    public function testCustomerChecksCustomerLink(): void
    {
        $customer = new User(['id' => 7]);
        $nonCustomer = new User(['id' => 8]);
        $this->db->exec('INSERT INTO neighborhub_customers (id, user_id) VALUES (1, 7)');

        self::assertTrue($customer->isCustomer());
        self::assertFalse($nonCustomer->isCustomer());
    }
}
