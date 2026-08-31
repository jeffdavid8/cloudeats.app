<?php

namespace MediaBrain\Tests\Api;

use PHPUnit\Framework\TestCase;

class ApiSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        clearTestSession();
        $_GET = [];
        $_POST = [];
        
        // Clean output buffers
        if (ob_get_length()) {
            ob_clean();
        }
    }

    protected function tearDown(): void
    {
        clearTestSession();
        $_GET = [];
        $_POST = [];
        
        if (ob_get_length()) {
            ob_clean();
        }
        parent::tearDown();
    }

    public function testMainApiFileExists()
    {
        $apiFile = __DIR__ . '/../../html/api.php';
        $this->assertTrue(file_exists($apiFile), 'Main API file should exist');
    }

    public function testAdminApiRequiresAuth()
    {
        $adminApiFile = __DIR__ . '/../../html/apps/admin/admin.api.php';
        $this->assertTrue(file_exists($adminApiFile), 'Admin API file should exist');
        
        // Admin API should require authentication
        // We can't easily test this without running the actual script,
        // but we can verify the file structure
        $content = file_get_contents($adminApiFile);
        $this->assertStringContainsString('AuthManager', $content, 'Admin API should use AuthManager');
        $this->assertStringContainsString('requireAdmin', $content, 'Admin API should require admin privileges');
    }

    public function testBibleBotApiExists()
    {
        $bibleApiFile = __DIR__ . '/../../html/apps/bibleBot/bibleBot.api.php';
        $this->assertTrue(file_exists($bibleApiFile), 'BibleBot API file should exist');
        
        // BibleBot API should have CSRF protection
        $content = file_get_contents($bibleApiFile);
        $this->assertStringContainsString('validateCsrf', $content, 'BibleBot API should have CSRF protection');
    }

    public function testAncestryApiExists()
    {
        $ancestryApiFile = __DIR__ . '/../../html/apps/ancestry/ancestry.api.php';
        $this->assertTrue(file_exists($ancestryApiFile), 'Ancestry API file should exist');
        
        // Ancestry API should require authentication  
        $content = file_get_contents($ancestryApiFile);
        $this->assertStringContainsString('requireLogin', $content, 'Ancestry API should require login');
    }

    public function testCsrfProtectionInApis()
    {
        $testFiles = [
            'admin' => __DIR__ . '/../../html/apps/admin/admin.api.php',
            'bibleBot' => __DIR__ . '/../../html/apps/bibleBot/bibleBot.api.php',
            'ancestry' => __DIR__ . '/../../html/apps/ancestry/ancestry.api.php'
        ];

        foreach ($testFiles as $app => $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Check for CSRF protection patterns
                $hasCsrfCheck = (
                    strpos($content, 'validateCsrf') !== false ||
                    strpos($content, 'csrf') !== false ||
                    strpos($content, 'CSRF') !== false
                );
                
                $this->assertTrue($hasCsrfCheck, 
                    "API file for $app should have CSRF protection");
            }
        }
    }

    public function testAuthenticationPatternsInApis()
    {
        $testFiles = [
            'admin' => __DIR__ . '/../../html/apps/admin/admin.api.php',
            'ancestry' => __DIR__ . '/../../html/apps/ancestry/ancestry.api.php'
        ];

        foreach ($testFiles as $app => $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Check for authentication patterns
                $hasAuthCheck = (
                    strpos($content, 'AuthManager') !== false ||
                    strpos($content, 'requireLogin') !== false ||
                    strpos($content, 'requireAdmin') !== false
                );
                
                $this->assertTrue($hasAuthCheck, 
                    "API file for $app should have authentication checks");
            }
        }
    }

    public function testLegacyApiFileExistence()
    {
        // Check that legacy API files exist and have been converted to shims
        $legacyFiles = [
            __DIR__ . '/../../html/apps/admin/api.php',
            __DIR__ . '/../../html/apps/bibleBot/api.php',
            __DIR__ . '/../../html/apps/ancestry/api.php'
        ];

        foreach ($legacyFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Should be a shim that includes the new centralized API
                $isShim = (
                    strpos($content, 'include') !== false ||
                    strpos($content, 'require') !== false ||
                    strpos($content, '.api.php') !== false
                );
                
                $this->assertTrue($isShim, 
                    "Legacy API file $file should be converted to shim");
            }
        }
    }

    public function testMainApiRouting()
    {
        $mainApiFile = __DIR__ . '/../../html/api.php';
        if (file_exists($mainApiFile)) {
            $content = file_get_contents($mainApiFile);
            
            // Should handle app routing
            $this->assertStringContainsString('$_GET[\'app\']', $content, 
                'Main API should handle app parameter routing');
                
            // Should have basic security
            $hasSecurityCheck = (
                strpos($content, 'AuthManager') !== false ||
                strpos($content, 'csrf') !== false ||
                strpos($content, 'session') !== false
            );
            
            $this->assertTrue($hasSecurityCheck, 
                'Main API should have security checks');
        } else {
            $this->markTestSkipped('Main API file not found');
        }
    }
}
?>