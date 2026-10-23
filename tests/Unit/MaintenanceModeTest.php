<?php

namespace MediaBrain\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../html/includes/MaintenanceMode.php';

class MaintenanceModeTest extends TestCase
{
    private $originalMode;
    private $originalMessage;
    private $originalEta;
    private $originalGet;
    private $originalRequestUri;
    private $originalRequestMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMode = getenv('MAINTENANCE_MODE');
        $this->originalMessage = getenv('MAINTENANCE_MESSAGE');
        $this->originalEta = getenv('MAINTENANCE_ETA');
        $this->originalGet = $_GET;
        $this->originalRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        putenv('MAINTENANCE_MODE');
        putenv('MAINTENANCE_MESSAGE');
        putenv('MAINTENANCE_ETA');
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment('MAINTENANCE_MODE', $this->originalMode);
        $this->restoreEnvironment('MAINTENANCE_MESSAGE', $this->originalMessage);
        $this->restoreEnvironment('MAINTENANCE_ETA', $this->originalEta);
        $_GET = $this->originalGet;
        $this->restoreServerValue('REQUEST_URI', $this->originalRequestUri);
        $this->restoreServerValue('REQUEST_METHOD', $this->originalRequestMethod);
        parent::tearDown();
    }

    public function testEnvironmentOverrideIsUnsetByDefault()
    {
        $this->assertNull(\MaintenanceMode::getEnvironmentOverride());
    }

    public function testEnvironmentOverrideAcceptsTrueAndFalse()
    {
        putenv('MAINTENANCE_MODE=true');
        $this->assertTrue(\MaintenanceMode::getEnvironmentOverride());

        putenv('MAINTENANCE_MODE=false');
        $this->assertFalse(\MaintenanceMode::getEnvironmentOverride());
    }

    public function testInvalidEnvironmentOverrideIsRejected()
    {
        putenv('MAINTENANCE_MODE=sometimes');
        $this->expectException(\RuntimeException::class);

        \MaintenanceMode::getEnvironmentOverride();
    }

    public function testCallbackAndLoginRoutesRemainAvailable()
    {
        $_SERVER['REQUEST_URI'] = '/oauth/google.php';
        $this->assertTrue(\MaintenanceMode::isAllowedRequest());

        $_SERVER['REQUEST_URI'] = '/';
        $_GET = ['api' => 'neighborhub', 'action' => 'verify_registration'];
        $this->assertTrue(\MaintenanceMode::isAllowedRequest());

        $_GET = ['api' => 'neighborhub', 'action' => 'stripe_webhook'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue(\MaintenanceMode::isAllowedRequest());

        $_GET = ['p' => 'login'];
        $this->assertTrue(\MaintenanceMode::isAllowedRequest());

        $_GET = ['api' => 'auth', 'action' => 'login'];
        $this->assertTrue(\MaintenanceMode::isAllowedRequest());
    }

    public function testOrdinaryPagesAndApisAreBlockedByPolicy()
    {
        $_GET = ['app' => 'neighborhub'];
        $this->assertFalse(\MaintenanceMode::isAllowedRequest());

        $_GET = ['api' => 'neighborhub', 'action' => 'get_products'];
        $this->assertFalse(\MaintenanceMode::isAllowedRequest());
    }

    private function restoreEnvironment($name, $value)
    {
        if ($value === false) {
            putenv($name);
            return;
        }

        putenv($name . '=' . $value);
    }

    private function restoreServerValue($name, $value)
    {
        if ($value === null) {
            unset($_SERVER[$name]);
            return;
        }

        $_SERVER[$name] = $value;
    }
}
