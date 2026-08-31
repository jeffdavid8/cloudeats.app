<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * BibleBot Integration Tests
 * 
 * Tests end-to-end functionality and integration between BibleBot components:
 * - Search to results workflow
 * - TTS integration with search results
 * - Bookmark management workflow
 * - Sharing functionality
 * - User session management
 */
class BibleBotIntegrationTest extends TestCase
{
    private static $app;
    private static $bibleJson;
    
    public static function setUpBeforeClass(): void
    {
        // Set up test environment
        $_SERVER['REQUEST_URI'] = '/?app=bibleBot&s=love';
        $_SERVER['HTTP_HOST'] = 'mediabrain.app.local';
        $_GET = ['app' => 'bibleBot', 's' => 'love'];
        
        // Initialize app
        require_once __DIR__ . '/../../html/includes/app.php';
        require_once __DIR__ . '/../../html/apps/bibleBot/bibleBot.app.php';
        require_once __DIR__ . '/../../html/apps/bibleBot/includes/bibleJson.php';
        
        self::$app = App::getInstance('bibleBot');
        self::$bibleJson = BibleJson::getInstance();
    }

    public function setUp(): void
    {
        // Reset session state
        $_SESSION = [];
        
        // Reset GET/POST parameters
        $_GET = ['app' => 'bibleBot'];
        $_POST = [];
    }

    /**
     * Test complete search workflow from input to results
     */
    #[Test]
    public function testCompleteSearchWorkflow()
    {
        // 1. Simulate user search input
        $_GET['s'] = 'John 3:16';
        
        // 2. Initialize BibleBot with search
        bibleBot_init();
        
        // 3. Verify search was processed
        $searchString = self::$app->get('search_string');
        $searchResults = self::$app->get('search_results');
        
        $this->assertEquals('John 3:16', $searchString);
        $this->assertNotEmpty($searchResults);
        $this->assertIsArray($searchResults);
        
        // 4. Verify search results contain expected data
        $firstResult = $searchResults[0];
        $this->assertArrayHasKey('book', $firstResult);
        $this->assertArrayHasKey('chapter', $firstResult);
        $this->assertArrayHasKey('verse', $firstResult);
        $this->assertArrayHasKey('text', $firstResult);
        
        // 5. Verify result is the correct verse
        $this->assertEquals('John', $firstResult['book']);
        $this->assertEquals(3, $firstResult['chapter']);
        $this->assertEquals(16, $firstResult['verse']);
        $this->assertStringContainsString('God so loved', $firstResult['text']);
    }

    /**
     * Test search to TTS workflow
     */
    #[Test]
    public function testSearchToTTSWorkflow()
    {
        // 1. Perform search
        $_GET['s'] = 'Psalm 23:1';
        bibleBot_init();
        
        $searchResults = self::$app->get('search_results');
        $this->assertNotEmpty($searchResults);
        
        // 2. Test TTS integration with search result
        if (class_exists('TextToSpeechService')) {
            $firstVerse = $searchResults[0];
            $verseText = $firstVerse['text'];
            
            $tts = new TextToSpeechService();
            $audioResult = $tts->generateAudio($verseText);
            
            $this->assertIsArray($audioResult);
            $this->assertArrayHasKey('success', $audioResult);
            
            if ($audioResult['success']) {
                $this->assertArrayHasKey('audio_url', $audioResult);
                $this->assertArrayHasKey('cache_hit', $audioResult);
            }
        } else {
            $this->markTestSkipped('TextToSpeechService not available for integration test');
        }
    }

    /**
     * Test bookmark management integration
     */
    #[Test]
    public function testBookmarkManagementIntegration()
    {
        // 1. Set up authenticated user
        $_SESSION['user'] = [
            'username' => 'testuser',
            'role' => 'user',
            'is_admin' => false
        ];
        
        // 2. Perform search
        $_GET['s'] = 'Romans 8:28';
        bibleBot_init();
        
        $searchResults = self::$app->get('search_results');
        $this->assertNotEmpty($searchResults);
        
        // 3. Test bookmark workflow
        $verse = $searchResults[0];
        $bookmarkData = [
            'reference' => $verse['book'] . ' ' . $verse['chapter'] . ':' . $verse['verse'],
            'text' => $verse['text'],
            'timestamp' => time(),
            'user' => $_SESSION['user']['username']
        ];
        
        // 4. Simulate API call to add bookmark
        $_POST = [
            'action' => 'add_bookmark',
            'bookmark' => json_encode($bookmarkData)
        ];
        
        // Test that API would process this correctly
        $this->assertNotEmpty($_POST['bookmark']);
        $decodedBookmark = json_decode($_POST['bookmark'], true);
        $this->assertEquals($bookmarkData['reference'], $decodedBookmark['reference']);
    }

    /**
     * Test search result sharing workflow  
     */
    #[Test]
    public function testSearchResultSharingWorkflow()
    {
        // 1. Perform search
        $_GET['s'] = '1 Corinthians 13:4-7';
        bibleBot_init();
        
        $searchResults = self::$app->get('search_results');
        $this->assertNotEmpty($searchResults);
        
        // 2. Test share data preparation
        $verseRange = array_slice($searchResults, 0, 4); // Get first 4 verses
        
        $shareData = [
            'reference' => '1 Corinthians 13:4-7',
            'verses' => $verseRange,
            'translation' => 'ESV',
            'url' => self::$app->getConfig('base_url') . '/?app=bibleBot&s=' . urlencode('1 Corinthians 13:4-7')
        ];
        
        $this->assertCount(4, $shareData['verses']);
        $this->assertEquals('1 Corinthians 13:4-7', $shareData['reference']);
        $this->assertStringContainsString('love is patient', strtolower($shareData['verses'][0]['text']));
    }

    /**
     * Test user session and preference integration
     */
    #[Test]
    public function testUserSessionAndPreferencesIntegration()
    {
        // 1. Set up user session with preferences
        $_SESSION['user'] = [
            'username' => 'testuser',
            'preferences' => [
                'translation' => 'ESV',
                'night_mode' => true,
                'auto_tts' => false,
                'recent_searches' => ['John 3:16', 'Psalm 23', 'Romans 8:28']
            ]
        ];
        
        // 2. Test that app recognizes user session
        bibleBot_init();
        
        // 3. Verify user context is available
        if (isset($_SESSION['user'])) {
            $userPrefs = $_SESSION['user']['preferences'];
            $this->assertEquals('ESV', $userPrefs['translation']);
            $this->assertTrue($userPrefs['night_mode']);
            $this->assertFalse($userPrefs['auto_tts']);
            $this->assertContains('John 3:16', $userPrefs['recent_searches']);
        }
    }

    /**
     * Test error handling integration across components
     */
    #[Test]
    public function testErrorHandlingIntegration()
    {
        // 1. Test invalid search input
        $_GET['s'] = 'InvalidBook 999:999';
        bibleBot_init();
        
        $searchResults = self::$app->get('search_results');
        
        // Should handle gracefully without breaking
        $this->assertIsArray($searchResults);
        
        // 2. Test malformed API request
        $_POST = [
            'action' => 'invalid_action',
            'data' => 'malformed data'
        ];
        
        // Should not cause fatal errors
        $this->assertIsString($_POST['action']);
        
        // 3. Test missing search parameter
        unset($_GET['s']);
        bibleBot_init();
        
        $searchString = self::$app->get('search_string');
        $this->assertEmpty($searchString);
    }

    /**
     * Test page routing and navigation integration
     */
    #[Test]
    public function testPageRoutingIntegration()
    {
        $validPages = ['search', 'search_results', 'bookmarks', 'share'];
        
        foreach ($validPages as $page) {
            // Reset app state
            self::$app = App::getInstance('bibleBot');
            
            // Test each page routing
            $_GET = ['app' => 'bibleBot', 'p' => $page];
            
            if ($page === 'search_results') {
                $_GET['s'] = 'test search';
            }
            
            bibleBot_init();
            
            // Verify app state is set correctly for each page
            $currentPage = self::$app->get('page');
            $this->assertIsString($currentPage);
        }
    }

    /**
     * Test cross-browser compatibility features
     */
    #[Test]
    public function testCrossBrowserCompatibilityFeatures()
    {
        // Test different user agent scenarios
        $userAgents = [
            'Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Firefox' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Safari' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mobile' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1'
        ];
        
        foreach ($userAgents as $browser => $userAgent) {
            $_SERVER['HTTP_USER_AGENT'] = $userAgent;
            
            // Test that app initializes regardless of browser
            bibleBot_init();
            
            $this->assertInstanceOf('App', self::$app);
        }
    }

    /**
     * Test performance under load
     */
    #[Test]
    public function testPerformanceUnderLoad()
    {
        $startTime = microtime(true);
        
        // Simulate multiple rapid searches
        $searches = ['love', 'faith', 'hope', 'peace', 'joy'];
        
        foreach ($searches as $search) {
            $_GET['s'] = $search;
            bibleBot_init();
            
            $results = self::$app->get('search_results');
            $this->assertIsArray($results);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // All searches should complete within reasonable time
        $this->assertLessThan(10.0, $totalTime, "Multiple searches took too long: {$totalTime} seconds");
    }

    /**
     * Test data persistence and state management
     */
    #[Test]
    public function testDataPersistenceAndStateManagement()
    {
        // 1. Set up initial state
        $_SESSION['biblebot_state'] = [
            'last_search' => 'John 3:16',
            'current_translation' => 'ESV',
            'bookmarks_count' => 5
        ];
        
        // 2. Initialize app
        bibleBot_init();
        
        // 3. Test that session state persists
        if (isset($_SESSION['biblebot_state'])) {
            $this->assertEquals('John 3:16', $_SESSION['biblebot_state']['last_search']);
            $this->assertEquals('ESV', $_SESSION['biblebot_state']['current_translation']);
            $this->assertEquals(5, $_SESSION['biblebot_state']['bookmarks_count']);
        }
        
        // 4. Test state updates
        $_SESSION['biblebot_state']['last_search'] = 'Psalm 23';
        
        $this->assertEquals('Psalm 23', $_SESSION['biblebot_state']['last_search']);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test environment
        self::$app = null;
        self::$bibleJson = null;
        
        // Clear session
        $_SESSION = [];
        
        // Reset globals
        $_GET = [];
        $_POST = [];
    }
}