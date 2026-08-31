<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once __DIR__ . '/../Production/production_utils.php';

/**
 * Production Bug Fixes Tests
 * 
 * Validates fixes for production deployment issues including:
 * - Content Security Policy (CSP) configuration for YouTube embeds
 * - BibleBot JavaScript loading and initialization issues
 */
class ProductionBugFixesTest extends TestCase
{
    private $originalServerVars;
    
    public function setUp(): void
    {
        $this->originalServerVars = $_SERVER;
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'mediabrain.app.local';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
    
    public function tearDown(): void
    {
        $_SERVER = $this->originalServerVars;
    }

    /**
     * Test CSP frame-src directive allows YouTube embeds
     */
    #[Test]
    public function testCSPAllowsYouTubeEmbeds()
    {
        // Include SecurityHeaders to get CSP directives
        require_once __DIR__ . '/../../html/includes/SecurityHeaders.php';
        
        $cspDirectives = SecurityHeaders::getCSPDirectives();
        
        // Verify frame-src directive exists
        $this->assertArrayHasKey('frame-src', $cspDirectives, 'CSP should include frame-src directive');
        
        // Verify YouTube domains are allowed
        $frameSrc = $cspDirectives['frame-src'];
        $this->assertStringContainsString('youtube.com', $frameSrc, 'CSP should allow youtube.com');
        $this->assertStringContainsString('www.youtube.com', $frameSrc, 'CSP should allow www.youtube.com');
        $this->assertStringContainsString("'self'", $frameSrc, 'CSP should allow self for frames');
    }

    /**
     * Test CSP header generation includes frame-src
     */
    #[Test]
    public function testCSPHeaderGenerationIncludesFrameSrc()
    {
        require_once __DIR__ . '/../../html/includes/SecurityHeaders.php';
        
        // Test CSP string generation by checking the directives
        $cspDirectives = SecurityHeaders::getCSPDirectives();
        
        // Build expected CSP string
        $expectedCSP = '';
        foreach ($cspDirectives as $directive => $value) {
            $expectedCSP .= $directive . ' ' . $value . '; ';
        }
        $expectedCSP = trim($expectedCSP);
        
        $this->assertStringContainsString('frame-src', $expectedCSP, 'CSP should include frame-src directive');
        $this->assertStringContainsString('youtube.com', $expectedCSP, 'CSP should allow YouTube');
        $this->assertStringContainsString("'self'", $expectedCSP, 'CSP should include self');
    }

    /**
     * Test BibleBot component safety checks
     */
    #[Test]
    public function testBibleBotComponentSafetyChecks()
    {
        // Test bookmarks_menu.php component
        $bookmarksMenuContent = file_get_contents(__DIR__ . '/../../html/apps/bibleBot/views/components/bookmarks_menu.php');
        
        // With component registry pattern, dependencies are handled automatically
        $this->assertStringContainsString('mb.registerComponent', $bookmarksMenuContent,
            'Bookmarks menu should use component registry for dependency management');
        $this->assertStringContainsString("['jquery', 'bibleBot']", $bookmarksMenuContent,
            'Bookmarks menu should declare proper dependencies');
        
        // Test bookmarks_select.php component
        $bookmarksSelectContent = file_get_contents(__DIR__ . '/../../html/apps/bibleBot/views/components/bookmarks_select.php');
        
        // Verify it also uses component registry pattern
        $this->assertStringContainsString('mb.registerComponent', $bookmarksSelectContent,
            'Bookmarks select should use component registry for dependency management');
        $this->assertStringContainsString("['jquery', 'bibleBot']", $bookmarksSelectContent,
            'Bookmarks select should declare proper dependencies (no mb needed since it only displays)');
    }

    /**
     * Test bookmarks page initialization safety
     */
    #[Test]
    public function testBookmarksPageInitializationSafety()
    {
        $bookmarksPageContent = file_get_contents(__DIR__ . '/../../html/views/pages/bookmarks.php');
        
        // Verify uses component registry pattern
        $this->assertStringContainsString('mb.registerComponent', $bookmarksPageContent,
            'Bookmarks page should use component registry pattern');
        $this->assertStringContainsString('bookmarks-page', $bookmarksPageContent,
            'Bookmarks page should register with correct component name');
        
        // Verify it has the main functions for bookmark management
        $this->assertStringContainsString('addNewFacetsToSearchResultsList', $bookmarksPageContent,
            'Bookmarks page should have facet management function');
    }

    /**
     * Test CSP configuration prevents common security issues
     */
    #[Test]
    public function testCSPPreventsSecurityIssues()
    {
        require_once __DIR__ . '/../../html/includes/SecurityHeaders.php';
        
        $cspDirectives = SecurityHeaders::getCSPDirectives();
        
        // Verify restrictive default-src
        $this->assertEquals("'self'", $cspDirectives['default-src'], 
            'Default CSP should be restrictive to self only');
        
        // Verify object-src is blocked
        $this->assertEquals("'none'", $cspDirectives['object-src'], 
            'Object sources should be blocked for security');
        
        // Verify frame-ancestors is blocked
        $this->assertEquals("'none'", $cspDirectives['frame-ancestors'], 
            'Frame ancestors should be blocked to prevent clickjacking');
        
        // Verify base-uri is restricted
        $this->assertEquals("'self'", $cspDirectives['base-uri'], 
            'Base URI should be restricted to self');
        
        // Verify form-action is restricted
        $this->assertEquals("'self'", $cspDirectives['form-action'], 
            'Form actions should be restricted to self');
    }

    /**
     * Test script loading order and dependency management
     */
    #[Test]
    public function testScriptLoadingOrderAndDependencies()
    {
        // Test script loading order in head.php
        $headContent = file_get_contents(__DIR__ . '/../../html/views/components/head.php');
        
        // Verify component-registry.js is included in script loading sequence
        $this->assertStringContainsString('component-registry.js', $headContent,
            'Head should include component-registry.js in script loading sequence');
        
        // Verify it's loaded after mediabrain.js
        $mbPos = strpos($headContent, "js/mediabrain.js");
        $registryPos = strpos($headContent, "js/component-registry.js");
        $this->assertTrue($registryPos > $mbPos,
            'component-registry.js should be loaded after mediabrain.js');
        
        // Test that app.js initializes bibleBot object
        $appJsContent = file_get_contents(__DIR__ . '/../../html/apps/bibleBot/js/app.js');
        
        $this->assertStringContainsString('var bibleBot = {};', $appJsContent,
            'app.js should initialize bibleBot object');
        
        // Verify CSRF management exists
        $this->assertStringContainsString('bibleBot.csrf', $appJsContent,
            'app.js should include CSRF management');
        
        // Verify AJAX function exists
        $this->assertStringContainsString('bibleBot.ajax', $appJsContent,
            'app.js should include AJAX function');
    }

    /**
     * Test error handling in JavaScript components
     */
    #[Test]
    public function testJavaScriptComponentErrorHandling()
    {
        // Test bookmarks components use component registry for dependency management
        $bookmarksMenuContent = file_get_contents(__DIR__ . '/../../html/apps/bibleBot/views/components/bookmarks_menu.php');
        
        // With component registry pattern, dependencies are handled automatically
        // Verify the component uses the registry pattern
        $this->assertStringContainsString('mb.registerComponent', $bookmarksMenuContent,
            'Bookmarks menu should use component registry for dependency management');
        
        // Verify proper dependency declaration
        $this->assertStringContainsString("['jquery', 'bibleBot']", $bookmarksMenuContent,
            'Bookmarks menu should declare dependencies that will be checked by registry');
    }

    /**
     * Test CSP allows necessary external resources
     */
    #[Test]
    public function testCSPAllowsNecessaryExternalResources()
    {
        require_once __DIR__ . '/../../html/includes/SecurityHeaders.php';
        
        $cspDirectives = SecurityHeaders::getCSPDirectives();
        
        // Verify script sources include required external domains
        $scriptSrc = $cspDirectives['script-src'];
        $this->assertStringContainsString('apis.google.com', $scriptSrc, 'Should allow Google APIs for scripts');
        $this->assertStringContainsString('connect.facebook.net', $scriptSrc, 'Should allow Facebook scripts');
        
        // Verify style sources include font providers
        $styleSrc = $cspDirectives['style-src'];
        $this->assertStringContainsString('fonts.googleapis.com', $styleSrc, 'Should allow Google Fonts styles');
        
        // Verify font sources
        $fontSrc = $cspDirectives['font-src'];
        $this->assertStringContainsString('fonts.gstatic.com', $fontSrc, 'Should allow Google Fonts');
        $this->assertStringContainsString('data:', $fontSrc, 'Should allow data URLs for fonts');
        
        // Verify connect sources for APIs
        $connectSrc = $cspDirectives['connect-src'];
        $this->assertStringContainsString('apis.google.com', $connectSrc, 'Should allow Google API connections');
        $this->assertStringContainsString('facebook.com', $connectSrc, 'Should allow Facebook connections');
    }

    /**
     * Test component initialization timing
     */
    #[Test]
    public function testComponentInitializationTiming()
    {
        $bookmarksMenuContent = file_get_contents(__DIR__ . '/../../html/apps/bibleBot/views/components/bookmarks_menu.php');
        
        // With component registry, components are only initialized when dependencies are ready
        $this->assertStringContainsString('mb.registerComponent', $bookmarksMenuContent,
            'Component should use registry for proper timing');
        
        $this->assertStringNotContainsString('setTimeout', $bookmarksMenuContent,
            'Component should not need setTimeout with registry pattern');
        
        // Verify proper dependency declaration ensures correct timing
        $this->assertStringContainsString("['jquery', 'bibleBot']", $bookmarksMenuContent,
            'Component should declare dependencies for timing management');
    }

    /**
     * Test background selector component safety checks
     */
    #[Test]
    public function testBackgroundSelectorComponentSafetyChecks()
    {
        $backgroundSelectorContent = file_get_contents(__DIR__ . '/../../html/apps/bibleBot/views/components/background_selector_menu.php');
        
        // Verify component registration uses mb namespace
        $this->assertStringContainsString('mb.registerComponent', $backgroundSelectorContent,
            'Background selector should use mb.registerComponent');
        
        // Verify component name and dependencies
        $this->assertStringContainsString("'background-selector'", $backgroundSelectorContent,
            'Background selector should register with correct component name');
        $this->assertStringContainsString("['jquery', 'mb', 'bibleBot']", $backgroundSelectorContent,
            'Background selector should declare proper dependencies');
        
        // Verify no more manual dependency checking (since registry handles it)
        $this->assertStringNotContainsString('typeof mb === \'undefined\'', $backgroundSelectorContent,
            'Background selector should not need manual dependency checks with registry pattern');
        $this->assertStringNotContainsString('setTimeout', $backgroundSelectorContent,
            'Background selector should not need setTimeout with registry pattern');
        
        // Verify uses mb.getJson safely within component
        $this->assertStringContainsString('mb.getJson', $backgroundSelectorContent,
            'Background selector should use mb.getJson within component function');
    }

    /**
     * Test unified component registry system
     */
    #[Test]
    public function testUnifiedComponentRegistrySystem()
    {
        $componentRegistryContent = file_get_contents(__DIR__ . '/../../html/js/component-registry.js');
        
        // Verify data-driven architecture
        $this->assertStringContainsString('mb.registerComponent', $componentRegistryContent,
            'Registry should support component registration');
        $this->assertStringContainsString('data-component', $componentRegistryContent,
            'Registry should support DOM auto-discovery');
        
        // Verify dependency management
        $this->assertStringContainsString('dependenciesReady', $componentRegistryContent,
            'Registry should handle dependency checking');
        $this->assertStringContainsString('initializeComponentType', $componentRegistryContent,
            'Registry should handle component type initialization');
        
        // Verify component loader exists
        $componentLoaderExists = file_exists(__DIR__ . '/../../html/js/component-loader.js');
        $this->assertTrue($componentLoaderExists, 'Component loader should exist');
        
        if ($componentLoaderExists) {
            $componentLoaderContent = file_get_contents(__DIR__ . '/../../html/js/component-loader.js');
            $this->assertStringContainsString('discoverAndLoadComponents', $componentLoaderContent,
                'Component loader should auto-discover components');
            $this->assertStringContainsString('js/components/', $componentLoaderContent,
                'Component loader should load from components directory');
        }
        
        // Verify individual component files exist
        $componentsDir = __DIR__ . '/../../html/js/components/';
        $expectedComponents = [
            'background-selector.js',
            'bookmarks-menu.js', 
            'bookmarks-select.js',
            'under-construction-notice.js',
            'paypal-notification.js',
            'remove-all-bookmarks-dialog.js'
        ];
        
        foreach ($expectedComponents as $componentFile) {
            $this->assertTrue(
                file_exists($componentsDir . $componentFile),
                "Component file {$componentFile} should exist in modular structure"
            );
        }
        
        // Verify script loading includes component loader
        $headContent = file_get_contents(__DIR__ . '/../../html/views/components/head.php');
        $this->assertStringContainsString('component-loader.js', $headContent,
            'Head should include component-loader.js for auto-loading');
    }
}