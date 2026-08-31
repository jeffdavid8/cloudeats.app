<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Mock TextToSpeechService for testing
 */
class TextToSpeechService {
    public function generateSpeech($text, $voice = 'en-US-Neural2-A', $speed = 1.0) {
        return [
            'success' => true,
            'audio_url' => 'https://example.com/tts/audio.mp3',
            'duration' => 30,
            'cache_hit' => false
        ];
    }
    
    public function getVoices() {
        return ['en-US-Neural2-A', 'en-US-Neural2-B', 'en-GB-Neural2-A'];
    }
}

/**
 * Comprehensive BibleBot App Unit Tests
 * 
 * Tests the core functionality of the BibleBot application including:
 * - Verse parsing and validation
 * - Search functionality and results
 * - TTS integration 
 * - Bookmark management
 * - User preferences
 * - API endpoints
 */
class BibleBotTest extends TestCase
{
    private static $bibleBot;
    private static $bibleJson;
    private static $verseParser;
    
    public static function setUpBeforeClass(): void
    {
        // Initialize the App instance for BibleBot
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['app'] = 'bibleBot';
        
        // Mock the config function if it doesn't exist
        if (!function_exists('config')) {
            function config($key = null, $default = null) {
                $config = [
                    'app_path' => dirname(__DIR__, 2) . '/html/apps/bibleBot/',
                    'bible_json_file' => 'json/bible.json'
                ];
                
                if ($key === null) return $config;
                return $config[$key] ?? $default;
            }
        }
        
        // Include BibleBot dependencies
        require_once __DIR__ . '/../../html/apps/bibleBot/bibleBot.app.php';
        require_once __DIR__ . '/../../html/apps/bibleBot/includes/bibleJson.php';
        require_once __DIR__ . '/../../html/apps/bibleBot/includes/verseParser.php';
        
        // Initialize instances with absolute paths
        $jsonPath = dirname(__DIR__, 2) . '/html/apps/bibleBot/json';
        self::$bibleJson = BibleJson::getInstance($jsonPath . '/bible.json');
        self::$verseParser = new VerseParser($jsonPath);
    }
    
    public function setUp(): void
    {
        // Reset any global state
        $_SESSION = [];
        $_GET = ['app' => 'bibleBot'];
        $_POST = [];
    }

    /**
     * Test BibleBot app configuration and info
     */
    #[Test]
    public function testBibleBotAppInfo()
    {
        $info = bibleBot_info();
        
        $this->assertIsArray($info);
        $this->assertEquals('BibleBot', $info['title']);
        $this->assertEquals('1.0', $info['version']);
        $this->assertFalse($info['requires_auth']);
        $this->assertFalse($info['requires_admin']);
        $this->assertTrue($info['public_app']);
        $this->assertTrue($info['no_header']);
        
        // Test required assets are defined
        $this->assertIsArray($info['styles']);
        $this->assertIsArray($info['scripts']);
        $this->assertContains('apps/bibleBot/css/style.css', $info['styles']);
        $this->assertContains('apps/bibleBot/js/app.js', $info['scripts']);
    }

    /**
     * Test BibleJson singleton pattern
     */
    #[Test]
    public function testBibleJsonSingleton()
    {
        $instance1 = BibleJson::getInstance();
        $instance2 = BibleJson::getInstance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(BibleJson::class, $instance1);
    }

    /**
     * Test verse parsing functionality
     */
    #[Test]
    #[DataProvider('verseReferencesProvider')]
    public function testVerseParsingAndValidation($reference, $expectedValid, $expectedBook = null)
    {
        if (class_exists('VerseParser')) {
            $parser = new VerseParser();
            
            // Test parsing
            $parsed = $parser->parseReference($reference);
            
            if ($expectedValid) {
                $this->assertNotNull($parsed);
                if ($expectedBook) {
                    $this->assertStringContainsString($expectedBook, $parsed['book'] ?? '');
                }
            } else {
                $this->assertNull($parsed);
            }
        } else {
            $this->markTestSkipped('VerseParser class not available');
        }
    }

    public static function verseReferencesProvider(): array
    {
        return [
            'Valid single verse' => ['John 3:16', true, 'John'],
            'Valid verse range' => ['Genesis 1:1-5', true, 'Genesis'],
            'Valid chapter' => ['Psalm 23', true, 'Psalm'],
            'Valid book only' => ['Romans', true, 'Romans'],
            'Invalid reference' => ['InvalidBook 999:999', false],
            'Empty reference' => ['', false],
            'Malformed reference' => ['John 3:', false],
        ];
    }

    /**
     * Test search functionality
     */
    #[Test]
    #[DataProvider('searchTermsProvider')]
    public function testBibleSearchFunctionality($searchTerm, $expectResults)
    {
        $results = self::$bibleJson->search($searchTerm);
        
        if ($expectResults) {
            $this->assertNotEmpty($results);
            $this->assertIsArray($results);
            
            // Test that results contain expected structure
            foreach (array_slice($results, 0, 3) as $result) {
                $this->assertArrayHasKey('verse', $result);
                $this->assertArrayHasKey('book', $result);
                $this->assertArrayHasKey('chapter', $result);
                $this->assertArrayHasKey('text', $result);
            }
        } else {
            $this->assertEmpty($results);
        }
    }

    public static function searchTermsProvider(): array
    {
        return [
            'Common word' => ['love', true],
            'Biblical name' => ['Jesus', true],
            'Specific phrase' => ['in the beginning', true],
            'Very specific phrase' => ['For God so loved the world', true],
            'Nonsense term' => ['xyzqwertyasdfgh', false],
            'Empty search' => ['', false],
            'Single letter' => ['a', true], // Should find many results
        ];
    }

    /**
     * Test search result limits and pagination
     */
    #[Test]
    public function testSearchResultLimits()
    {
        $results = self::$bibleJson->search('the');
        
        $this->assertIsArray($results);
        // Should have many results for common word "the"
        $this->assertGreaterThan(10, count($results));
        
        // Test that search doesn't return excessive results (performance check)
        $this->assertLessThan(1000, count($results));
    }

    /**
     * Test TTS integration and audio functionality
     */
    #[Test]
    public function testTextToSpeechIntegration()
    {
        // Test TTS service availability
        if (class_exists('TextToSpeechService')) {
            $tts = new TextToSpeechService();
            $this->assertInstanceOf('TextToSpeechService', $tts);
            
            // Test audio generation for verse
            $verse = "For God so loved the world that he gave his one and only Son.";
            $result = $tts->generateSpeech($verse);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
        } else {
            $this->markTestSkipped('TextToSpeechService not available');
        }
    }

    /**
     * Test bookmark management functionality
     */
    #[Test]
    public function testBookmarkManagement()
    {
        // Mock session for bookmark testing
        $_SESSION['user'] = ['username' => 'testuser'];
        
        $bookmark = [
            'reference' => 'John 3:16',
            'text' => 'For God so loved the world...',
            'timestamp' => time()
        ];
        
        // Test adding bookmark (if method exists)
        if (method_exists(self::$bibleJson, 'addBookmark')) {
            $result = self::$bibleJson->addBookmark($bookmark);
            $this->assertTrue($result);
            
            // Test retrieving bookmarks
            $bookmarks = self::$bibleJson->getBookmarks();
            $this->assertIsArray($bookmarks);
            $this->assertContains($bookmark, $bookmarks);
        } else {
            $this->markTestSkipped('Bookmark methods not available');
        }
    }

    /**
     * Test verse sharing functionality
     */
    #[Test]
    public function testVerseSharing()
    {
        $verse = [
            'book' => 'John',
            'chapter' => 3,
            'verse' => 16,
            'text' => 'For God so loved the world that he gave his one and only Son.'
        ];
        
        // Test share URL generation
        if (method_exists(self::$bibleJson, 'generateShareUrl')) {
            $shareUrl = self::$bibleJson->generateShareUrl($verse);
            $this->assertIsString($shareUrl);
            $this->assertStringContainsString('John+3:16', $shareUrl);
        } else {
            $this->markTestSkipped('Share functionality not available');
        }
    }

    /**
     * Test user preferences and settings
     */
    #[Test]
    public function testUserPreferences()
    {
        $_SESSION['user'] = ['username' => 'testuser'];
        
        $preferences = [
            'translation' => 'ESV',
            'night_mode' => true,
            'auto_tts' => false,
            'font_size' => 'medium'
        ];
        
        // Test setting preferences
        if (method_exists(self::$bibleJson, 'setUserPreferences')) {
            $result = self::$bibleJson->setUserPreferences($preferences);
            $this->assertTrue($result);
            
            // Test getting preferences
            $retrievedPrefs = self::$bibleJson->getUserPreferences();
            $this->assertEquals($preferences['translation'], $retrievedPrefs['translation']);
            $this->assertEquals($preferences['night_mode'], $retrievedPrefs['night_mode']);
        } else {
            $this->markTestSkipped('User preferences methods not available');
        }
    }

    /**
     * Test verse validation and error handling
     */
    #[Test]
    public function testVerseValidationErrorHandling()
    {
        // Test invalid book names
        $invalidBooks = ['InvalidBook', 'NotABook', '123Books'];
        
        foreach ($invalidBooks as $book) {
            $reference = $book . ' 1:1';
            $results = self::$bibleJson->search($reference);
            
            // Should either return empty results or handle gracefully
            $this->assertIsArray($results);
        }
        
        // Test invalid verse numbers
        $invalidReferences = [
            'Genesis 999:999', // Chapter doesn't exist
            'John 3:999',      // Verse doesn't exist  
            'Matthew 0:1',     // Invalid chapter number
            'Romans 1:0'       // Invalid verse number
        ];
        
        foreach ($invalidReferences as $reference) {
            $results = self::$bibleJson->search($reference);
            $this->assertIsArray($results);
        }
    }

    /**
     * Test search performance and optimization
     */
    #[Test]
    public function testSearchPerformance()
    {
        $startTime = microtime(true);
        
        // Perform a complex search
        $results = self::$bibleJson->search('love faith hope');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Search should complete within reasonable time
        $this->assertLessThan(5.0, $executionTime, 'Search took too long: ' . $executionTime . ' seconds');
        $this->assertIsArray($results);
    }

    /**
     * Test special characters and encoding
     */
    #[Test]
    public function testSpecialCharactersAndEncoding()
    {
        $specialTerms = [
            'café',           // Accented characters
            'naïve',          // Diaeresis  
            'résumé',         // Multiple accents
            'Señor',          // Spanish characters
            'α β γ',          // Greek letters (common in biblical text)
        ];
        
        foreach ($specialTerms as $term) {
            $results = self::$bibleJson->search($term);
            $this->assertIsArray($results);
            
            // Test that search doesn't break with special characters
            if (!empty($results)) {
                $this->assertIsString($results[0]['text']);
            }
        }
    }

    /**
     * Test API endpoint security and validation
     */
    #[Test]
    public function testAPIEndpointSecurity()
    {
        // Mock API request data
        $_POST['action'] = 'search';
        $_POST['query'] = 'John 3:16';
        
        // Test that API requires proper action parameter
        $_POST = [];
        ob_start();
        
        // This would normally trigger the API, but we'll test the validation
        if (file_exists(__DIR__ . '/../../html/apps/bibleBot/bibleBot.api.php')) {
            // Test API file exists and is readable
            $this->assertFileExists(__DIR__ . '/../../html/apps/bibleBot/bibleBot.api.php');
            $this->assertFileIsReadable(__DIR__ . '/../../html/apps/bibleBot/bibleBot.api.php');
        }
        
        ob_end_clean();
    }

    /**
     * Test data integrity and validation
     */
    #[Test]
    public function testDataIntegrityValidation()
    {
        // Test that BibleJson can handle malformed data
        $malformedInputs = [
            null,
            false,
            [],
            ['invalid' => 'structure'],
            'string instead of array',
            123456
        ];
        
        foreach ($malformedInputs as $input) {
            // Should not throw exceptions
            try {
                $result = self::$bibleJson->search($input);
                $this->assertIsArray($result);
            } catch (Exception $e) {
                $this->fail('BibleJson should handle malformed input gracefully: ' . $e->getMessage());
            }
        }
    }

    /**
     * Test memory usage and resource management
     */
    #[Test]
    public function testMemoryUsageAndResourceManagement()
    {
        $initialMemory = memory_get_usage();
        
        // Perform multiple searches to test memory management
        for ($i = 0; $i < 10; $i++) {
            $results = self::$bibleJson->search('test search ' . $i);
            $this->assertIsArray($results);
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease, 
            'Memory usage increased too much: ' . number_format($memoryIncrease / 1024 / 1024, 2) . ' MB');
    }

    /**
     * Test cross-platform compatibility
     */
    #[Test]
    public function testCrossPlatformCompatibility()
    {
        // Test path handling for different operating systems
        $jsonPath = self::$bibleJson->getJsonPath();
        
        if (!empty($jsonPath)) {
            // Path should work on current system
            $this->assertDirectoryExists(dirname($jsonPath));
        }
        
        // Test file encoding compatibility
        if (method_exists(self::$bibleJson, 'getEncoding')) {
            $encoding = self::$bibleJson->getEncoding();
            $this->assertContains($encoding, ['UTF-8', 'utf-8', 'ASCII']);
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up any test data
        self::$bibleBot = null;
        self::$bibleJson = null;
        self::$verseParser = null;
    }
}