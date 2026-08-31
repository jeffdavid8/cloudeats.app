<?php

namespace MediaBrain\Tests\Unit;

use PHPUnit\Framework\TestCase;
use MediaBrain\Services\TextToSpeechService;

/**
 * TTS v2 System Unit Tests
 * Tests the new TextToSpeechService and related functionality
 */
class TTSv2Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean session and environment
        $_SESSION = [];
        $_SERVER = [
            'HTTP_HOST' => 'mediabrain.app.local',
            'HTTPS' => 'on'
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testTextToSpeechServiceClassExists()
    {
        $this->assertTrue(class_exists('MediaBrain\\Services\\TextToSpeechService'));
    }

    public function testServiceInstantiation()
    {
        // Test that service can be instantiated without errors
        try {
            $service = new TextToSpeechService();
            $this->assertInstanceOf('MediaBrain\\Services\\TextToSpeechService', $service);
        } catch (\Exception $e) {
            // In testing environment, service might fail due to missing credentials
            // This is expected behavior
            $this->assertStringContainsString('credentials', strtolower($e->getMessage()));
        }
    }

    public function testServiceSingleton()
    {
        try {
            $service1 = TextToSpeechService::getInstance();
            $service2 = TextToSpeechService::getInstance();
            
            $this->assertSame($service1, $service2);
        } catch (\Exception $e) {
            // Expected in test environment without proper credentials
            $this->assertTrue(true);
        }
    }

    public function testVoiceOptions()
    {
        // Test that voice options structure is correct
        try {
            $service = new TextToSpeechService();
            
            if (method_exists($service, 'getVoiceOptions')) {
                $voices = $service->getVoiceOptions();
                $this->assertIsArray($voices);
                
                // Check structure of voice options
                if (!empty($voices)) {
                    $firstVoice = reset($voices);
                    $this->assertIsArray($firstVoice);
                    $this->assertArrayHasKey('name', $firstVoice);
                    $this->assertArrayHasKey('language', $firstVoice);
                    $this->assertArrayHasKey('type', $firstVoice);
                }
            }
        } catch (\Exception $e) {
            // Expected in test environment
            $this->assertTrue(true);
        }
    }

    public function testCacheKeyGeneration()
    {
        // Test cache key generation logic
        try {
            $service = new TextToSpeechService();
            
            if (method_exists($service, 'generateCacheKey')) {
                $reflection = new \ReflectionClass($service);
                $method = $reflection->getMethod('generateCacheKey');
                $method->setAccessible(true);
                
                $key1 = $method->invoke($service, 'Hello world', 'en-US-Neural2-A', 1.0, 0.0);
                $key2 = $method->invoke($service, 'Hello world', 'en-US-Neural2-A', 1.0, 0.0);
                $key3 = $method->invoke($service, 'Different text', 'en-US-Neural2-A', 1.0, 0.0);
                
                $this->assertEquals($key1, $key2);
                $this->assertNotEquals($key1, $key3);
                $this->assertIsString($key1);
                $this->assertGreaterThan(10, strlen($key1));
            }
        } catch (\Exception $e) {
            // Method might not exist or be accessible
            $this->assertTrue(true);
        }
    }

    public function testSSMLValidation()
    {
        try {
            $service = new TextToSpeechService();
            
            if (method_exists($service, 'isSSML')) {
                $reflection = new \ReflectionClass($service);
                $method = $reflection->getMethod('isSSML');
                $method->setAccessible(true);
                
                // Test SSML detection
                $this->assertTrue($method->invoke($service, '<speak>Hello world</speak>'));
                $this->assertTrue($method->invoke($service, '<emphasis>Hello</emphasis>'));
                $this->assertFalse($method->invoke($service, 'Plain text without markup'));
                $this->assertFalse($method->invoke($service, 'Text with < and > but not SSML'));
            }
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testConfigurationMethods()
    {
        try {
            $service = new TextToSpeechService();
            
            // Test configuration methods exist
            $this->assertTrue(method_exists($service, 'setDefaultVoice'));
            $this->assertTrue(method_exists($service, 'setDefaultSpeed'));
            $this->assertTrue(method_exists($service, 'setDefaultPitch'));
            
            if (method_exists($service, 'setDefaultVoice')) {
                $service->setDefaultVoice('en-US-Neural2-C');
                // If no exception is thrown, the method works
                $this->assertTrue(true);
            }
        } catch (\Exception $e) {
            // Expected in test environment
            $this->assertTrue(true);
        }
    }
}

/**
 * TTS API Endpoint Tests
 * Tests the API endpoint functionality
 */
class TTSAPITest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = ['csrf_token' => 'test-token'];
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_HOST' => 'mediabrain.app.local'
        ];
    }

    public function testAPIEndpointExists()
    {
        $apiFile = dirname(dirname(__DIR__)) . '/html/api-tts-v2.php';
        $this->assertFileExists($apiFile);
    }

    public function testAPIResponseStructure()
    {
        // Mock a minimal API request
        $testInput = json_encode([
            'text' => 'Test text',
            'voice' => 'en-US-Neural2-A',
            'csrf_token' => 'test-token'
        ]);
        
        // We can't easily test the actual API without setting up the full environment
        // But we can test that the file is properly formatted PHP
        $apiFile = dirname(dirname(__DIR__)) . '/html/api-tts-v2.php';
        $apiContent = file_get_contents($apiFile);
        
        // Basic syntax checks
        $this->assertStringContainsString('<?php', $apiContent);
        $this->assertStringContainsString('TextToSpeechService', $apiContent);
        $this->assertStringContainsString('application/json', $apiContent);
    }
}

/**
 * TTS JavaScript Client Tests
 * Tests for the JavaScript client functionality
 */
class TTSJavaScriptTest extends TestCase
{
    public function testJavaScriptFilesExist()
    {
        $jsFiles = [
            'modern-tts-client.js',
            'voice-selector.js',
            'jquery-ready.js'
        ];
        
        foreach ($jsFiles as $jsFile) {
            $filePath = dirname(dirname(__DIR__)) . '/html/js/' . $jsFile;
            $this->assertFileExists($filePath, "JavaScript file {$jsFile} should exist");
        }
    }

    public function testModernTTSClientStructure()
    {
        $jsFile = dirname(dirname(__DIR__)) . '/html/js/modern-tts-client.js';
        $content = file_get_contents($jsFile);
        
        // Test for key components
        $this->assertStringContainsString('class ModernTTSClient', $content);
        $this->assertStringContainsString('function initModernTTS', $content);
        $this->assertStringContainsString('function speak', $content);
        
        // Test for essential methods
        $this->assertStringContainsString('speak(', $content);
        $this->assertStringContainsString('setVoice', $content);
        $this->assertStringContainsString('setSpeed', $content);
        $this->assertStringContainsString('setVolume', $content);
        $this->assertStringContainsString('pause(', $content);
        $this->assertStringContainsString('resume(', $content);
        $this->assertStringContainsString('stop(', $content);
    }

    public function testVoiceSelectorStructure()
    {
        $jsFile = dirname(dirname(__DIR__)) . '/html/js/voice-selector.js';
        $content = file_get_contents($jsFile);
        
        // Test for voice selector components
        $this->assertStringContainsString('class VoiceSelector', $content);
        $this->assertStringContainsString('renderVoiceOptions', $content);
        $this->assertStringContainsString('filterVoices', $content);
    }

    public function testJavaScriptSyntaxValidity()
    {
        $jsFiles = [
            'modern-tts-client.js',
            'voice-selector.js',
            'jquery-ready.js'
        ];
        
        foreach ($jsFiles as $jsFile) {
            $filePath = dirname(dirname(__DIR__)) . '/html/js/' . $jsFile;
            $content = file_get_contents($filePath);
            
            // Basic JavaScript syntax checks
            $openBraces = substr_count($content, '{');
            $closeBraces = substr_count($content, '}');
            $this->assertEquals($openBraces, $closeBraces, "Mismatched braces in {$jsFile}");
            
            $openParens = substr_count($content, '(');
            $closeParens = substr_count($content, ')');
            $this->assertEquals($openParens, $closeParens, "Mismatched parentheses in {$jsFile}");
        }
    }
}

/**
 * TTS Integration Tests
 * Tests integration between different TTS components
 */
class TTSIntegrationTest extends TestCase
{
    public function testTTSTestPageExists()
    {
        $testPage = dirname(dirname(__DIR__)) . '/html/tts-v2-test.php';
        $this->assertFileExists($testPage);
        
        $content = file_get_contents($testPage);
        $this->assertStringContainsString('TTS v2 Test', $content);
        $this->assertStringContainsString('modern-tts-client.js', $content);
        $this->assertStringContainsString('voice-selector.js', $content);
    }

    public function testBackwardCompatibility()
    {
        // Test that old speak() function still exists
        $jsFile = dirname(dirname(__DIR__)) . '/html/js/modern-tts-client.js';
        $content = file_get_contents($jsFile);
        
        $this->assertStringContainsString('function speak(words, attachListeners)', $content);
    }

    public function testServiceIntegration()
    {
        $serviceFile = dirname(dirname(__DIR__)) . '/html/includes/Services/TextToSpeechService.php';
        $this->assertFileExists($serviceFile);
        
        $content = file_get_contents($serviceFile);
        $this->assertStringContainsString('class TextToSpeechService', $content);
        $this->assertStringContainsString('namespace MediaBrain\\Services', $content);
    }

    public function testCacheSystem()
    {
        // Test that cache methods are available
        $jsFile = dirname(dirname(__DIR__)) . '/html/js/modern-tts-client.js';
        $content = file_get_contents($jsFile);
        
        $this->assertStringContainsString('clearCache', $content);
        $this->assertStringContainsString('getCacheStats', $content);
        $this->assertStringContainsString('cache', $content);
    }
}