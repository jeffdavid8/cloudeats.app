<?php

/**
 * Mock App class for testing
 * Provides minimal functionality needed for AuthManager tests
 */
class App {
    private static $_instance = null;
    private $eventLogger = null;

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getEventLogger() {
        return $this->eventLogger;
    }

    public function setEventLogger($logger) {
        $this->eventLogger = $logger;
    }

    // Mock method to prevent errors
    public function log($type, $message, $context = []) {
        // Do nothing for tests
    }
}

/**
 * Mock EventLogger for testing
 */
class MockEventLogger {
    public function log($type, $message, $context = []) {
        // Silent logging for tests
    }
}