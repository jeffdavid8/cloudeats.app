<?php

namespace MediaBrain\Services;

class AnalyticsService {
    private static $instance = null;
    private $dataFile;
    private $dailyStatsFile;
    
    private function __construct() {
        $this->dataFile = __DIR__ . '/../../json/analytics_visits.json';
        $this->dailyStatsFile = __DIR__ . '/../../json/analytics_daily.json';
        
        // Ensure directory exists
        $dir = dirname($this->dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Initialize files if they don't exist
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([]));
        }
        if (!file_exists($this->dailyStatsFile)) {
            file_put_contents($this->dailyStatsFile, json_encode([]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new AnalyticsService();
        }
        return self::$instance;
    }
    
    public function trackPageView($data = []) {
        try {
            $visit = [
                'timestamp' => time(),
                'datetime' => date('Y-m-d H:i:s'),
                'page' => $_GET['app'] ?? 'home',
                'subpage' => $_GET['p'] ?? null,
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
                'ip' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'browser' => $this->getBrowser(),
                'os' => $this->getOS(),
                'device_type' => $this->getDeviceType(),
                'session_id' => session_id(),
                'user' => isset($_SESSION['user']) ? (is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user']) : 'anonymous',
                'is_authenticated' => isset($_SESSION['user']),
            ];
            
            // Merge with any additional data
            $visit = array_merge($visit, $data);
            
            // Store visit
            $this->storeVisit($visit);
            
            // Update daily stats
            $this->updateDailyStats($visit);
            
        } catch (\Exception $e) {
            error_log("Analytics tracking error: " . $e->getMessage());
        }
    }
    
    private function storeVisit($visit) {
        $visits = $this->loadVisits();
        $visits[] = $visit;
        
        // Keep only last 10,000 visits to prevent file bloat
        if (count($visits) > 10000) {
            $visits = array_slice($visits, -10000);
        }
        
        file_put_contents($this->dataFile, json_encode($visits, JSON_PRETTY_PRINT));
    }
    
    private function updateDailyStats($visit) {
        $stats = $this->loadDailyStats();
        $date = date('Y-m-d', $visit['timestamp']);
        
        if (!isset($stats[$date])) {
            $stats[$date] = [
                'date' => $date,
                'total_visits' => 0,
                'unique_visitors' => [],
                'pages' => [],
                'browsers' => [],
                'devices' => [],
                'authenticated_visits' => 0,
                'anonymous_visits' => 0,
            ];
        }
        
        $stats[$date]['total_visits']++;
        
        // Track unique visitors by IP
        if (!in_array($visit['ip'], $stats[$date]['unique_visitors'])) {
            $stats[$date]['unique_visitors'][] = $visit['ip'];
        }
        
        // Track page views
        $page = $visit['page'];
        if (!isset($stats[$date]['pages'][$page])) {
            $stats[$date]['pages'][$page] = 0;
        }
        $stats[$date]['pages'][$page]++;
        
        // Track browsers
        $browser = $visit['browser'];
        if (!isset($stats[$date]['browsers'][$browser])) {
            $stats[$date]['browsers'][$browser] = 0;
        }
        $stats[$date]['browsers'][$browser]++;
        
        // Track devices
        $device = $visit['device_type'];
        if (!isset($stats[$date]['devices'][$device])) {
            $stats[$date]['devices'][$device] = 0;
        }
        $stats[$date]['devices'][$device]++;
        
        // Track auth status
        if ($visit['is_authenticated']) {
            $stats[$date]['authenticated_visits']++;
        } else {
            $stats[$date]['anonymous_visits']++;
        }
        
        // Keep only last 90 days
        if (count($stats) > 90) {
            ksort($stats);
            $stats = array_slice($stats, -90, null, true);
        }
        
        file_put_contents($this->dailyStatsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    private function loadVisits() {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        $content = file_get_contents($this->dataFile);
        return json_decode($content, true) ?? [];
    }
    
    private function loadDailyStats() {
        if (!file_exists($this->dailyStatsFile)) {
            return [];
        }
        $content = file_get_contents($this->dailyStatsFile);
        return json_decode($content, true) ?? [];
    }
    
    public function getOverviewStats($days = 30) {
        $stats = $this->loadDailyStats();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $totalVisits = 0;
        $uniqueVisitors = [];
        $pageViews = [];
        $browsers = [];
        $devices = [];
        $authVisits = 0;
        $anonVisits = 0;
        
        foreach ($stats as $date => $dayStat) {
            if ($date >= $startDate) {
                $totalVisits += $dayStat['total_visits'];
                $uniqueVisitors = array_merge($uniqueVisitors, $dayStat['unique_visitors']);
                $authVisits += $dayStat['authenticated_visits'];
                $anonVisits += $dayStat['anonymous_visits'];
                
                foreach ($dayStat['pages'] as $page => $count) {
                    if (!isset($pageViews[$page])) {
                        $pageViews[$page] = 0;
                    }
                    $pageViews[$page] += $count;
                }
                
                foreach ($dayStat['browsers'] as $browser => $count) {
                    if (!isset($browsers[$browser])) {
                        $browsers[$browser] = 0;
                    }
                    $browsers[$browser] += $count;
                }
                
                foreach ($dayStat['devices'] as $device => $count) {
                    if (!isset($devices[$device])) {
                        $devices[$device] = 0;
                    }
                    $devices[$device] += $count;
                }
            }
        }
        
        arsort($pageViews);
        arsort($browsers);
        arsort($devices);
        
        return [
            'total_visits' => $totalVisits,
            'unique_visitors' => count(array_unique($uniqueVisitors)),
            'authenticated_visits' => $authVisits,
            'anonymous_visits' => $anonVisits,
            'pages' => $pageViews,
            'browsers' => $browsers,
            'devices' => $devices,
            'period_days' => $days,
        ];
    }
    
    public function getChartData($days = 30) {
        $stats = $this->loadDailyStats();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $labels = [];
        $visits = [];
        $uniqueVisitors = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M j', strtotime($date));
            
            if (isset($stats[$date])) {
                $visits[] = $stats[$date]['total_visits'];
                $uniqueVisitors[] = count($stats[$date]['unique_visitors']);
            } else {
                $visits[] = 0;
                $uniqueVisitors[] = 0;
            }
        }
        
        return [
            'labels' => $labels,
            'visits' => $visits,
            'unique_visitors' => $uniqueVisitors,
        ];
    }
    
    public function getRecentVisits($limit = 50) {
        $visits = $this->loadVisits();
        return array_slice(array_reverse($visits), 0, $limit);
    }
    
    public function getTopPages($limit = 10, $days = 30) {
        $stats = $this->getOverviewStats($days);
        $pages = $stats['pages'];
        return array_slice($pages, 0, $limit, true);
    }
    
    private function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        
        // Anonymize IP for privacy (remove last octet)
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'xxx';
            $ip = implode('.', $parts);
        }
        
        return $ip;
    }
    
    private function getBrowser() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        if (strpos($userAgent, 'Opera') !== false) return 'Opera';
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'IE';
        
        return 'Other';
    }
    
    private function getOS() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'macOS';
        if (strpos($userAgent, 'Linux') !== false) return 'Linux';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) return 'iOS';
        
        return 'Other';
    }
    
    private function getDeviceType() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            return 'Mobile';
        }
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'Tablet';
        }
        
        return 'Desktop';
    }
    
    public function getActiveUsers($timeWindowMinutes = 5) {
        $visits = $this->loadVisits();
        $cutoffTime = time() - ($timeWindowMinutes * 60);
        
        $activeSessions = [];
        foreach ($visits as $visit) {
            if ($visit['timestamp'] >= $cutoffTime) {
                $activeSessions[$visit['session_id']] = [
                    'user' => $visit['user'],
                    'last_seen' => $visit['timestamp'],
                    'page' => $visit['page']
                ];
            }
        }
        
        return [
            'count' => count($activeSessions),
            'sessions' => array_values($activeSessions),
            'time_window_minutes' => $timeWindowMinutes
        ];
    }
    
    public function trackSearchQuery($query, $resultCount = 0, $app = 'bibleBot') {
        try {
            $searchData = [
                'timestamp' => time(),
                'datetime' => date('Y-m-d H:i:s'),
                'query' => $query,
                'app' => $app,
                'result_count' => $resultCount,
                'user' => isset($_SESSION['user']) ? (is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user']) : 'anonymous',
                'session_id' => session_id(),
            ];
            
            $searchFile = __DIR__ . '/../../json/analytics_searches.json';
            
            // Initialize file if it doesn't exist
            if (!file_exists($searchFile)) {
                file_put_contents($searchFile, json_encode([]));
            }
            
            $searches = json_decode(file_get_contents($searchFile), true) ?? [];
            $searches[] = $searchData;
            
            // Keep only last 5,000 searches
            if (count($searches) > 5000) {
                $searches = array_slice($searches, -5000);
            }
            
            file_put_contents($searchFile, json_encode($searches, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            error_log("Search tracking error: " . $e->getMessage());
        }
    }
    
    public function getTopSearches($limit = 20, $days = 30) {
        $searchFile = __DIR__ . '/../../json/analytics_searches.json';
        
        if (!file_exists($searchFile)) {
            return [];
        }
        
        $searches = json_decode(file_get_contents($searchFile), true) ?? [];
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        
        $queryCounts = [];
        foreach ($searches as $search) {
            if ($search['timestamp'] >= $cutoffTime) {
                $query = strtolower(trim($search['query']));
                if (!isset($queryCounts[$query])) {
                    $queryCounts[$query] = [
                        'query' => $search['query'], // Keep original case
                        'count' => 0,
                        'total_results' => 0
                    ];
                }
                $queryCounts[$query]['count']++;
                $queryCounts[$query]['total_results'] += $search['result_count'];
            }
        }
        
        // Sort by count descending
        usort($queryCounts, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return array_slice($queryCounts, 0, $limit);
    }
    
    public function trackError($errorData) {
        try {
            $errorRecord = [
                'timestamp' => time(),
                'datetime' => date('Y-m-d H:i:s'),
                'message' => $errorData['message'] ?? '',
                'file' => $errorData['file'] ?? '',
                'line' => $errorData['line'] ?? 0,
                'type' => $errorData['type'] ?? 'error',
                'user' => isset($_SESSION['user']) ? (is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user']) : 'anonymous',
                'url' => $_SERVER['REQUEST_URI'] ?? '',
            ];
            
            $errorFile = __DIR__ . '/../../json/analytics_errors.json';
            
            // Initialize file if it doesn't exist
            if (!file_exists($errorFile)) {
                file_put_contents($errorFile, json_encode([]));
            }
            
            $errors = json_decode(file_get_contents($errorFile), true) ?? [];
            $errors[] = $errorRecord;
            
            // Keep only last 1,000 errors
            if (count($errors) > 1000) {
                $errors = array_slice($errors, -1000);
            }
            
            file_put_contents($errorFile, json_encode($errors, JSON_PRETTY_PRINT));
            
            // Also update daily stats
            $this->updateDailyErrorStats($errorRecord);
        } catch (\Exception $e) {
            error_log("Error tracking error: " . $e->getMessage());
        }
    }
    
    private function updateDailyErrorStats($errorRecord) {
        $stats = $this->loadDailyStats();
        $date = date('Y-m-d', $errorRecord['timestamp']);
        
        if (!isset($stats[$date])) {
            return;
        }
        
        if (!isset($stats[$date]['errors'])) {
            $stats[$date]['errors'] = 0;
        }
        
        $stats[$date]['errors']++;
        
        file_put_contents($this->dailyStatsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    public function getErrorStats($days = 30) {
        $errorFile = __DIR__ . '/../../json/analytics_errors.json';
        
        if (!file_exists($errorFile)) {
            return [
                'total_errors' => 0,
                'recent_errors' => [],
                'error_rate' => 0
            ];
        }
        
        $errors = json_decode(file_get_contents($errorFile), true) ?? [];
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        
        $recentErrors = array_filter($errors, function($error) use ($cutoffTime) {
            return $error['timestamp'] >= $cutoffTime;
        });
        
        $overview = $this->getOverviewStats($days);
        $errorRate = $overview['total_visits'] > 0 ? (count($recentErrors) / $overview['total_visits']) * 100 : 0;
        
        return [
            'total_errors' => count($recentErrors),
            'recent_errors' => array_slice(array_reverse($recentErrors), 0, 50),
            'error_rate' => round($errorRate, 2)
        ];
    }
    
    public function getErrorLogTail($lines = 100) {
        $logFile = __DIR__ . '/../../logs/error.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);
        
        $logLines = [];
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (!empty($line)) {
                $logLines[] = [
                    'line' => $line,
                    'timestamp' => time() // Could parse from log format if needed
                ];
            }
        }
        
        return array_reverse($logLines);
    }
}
