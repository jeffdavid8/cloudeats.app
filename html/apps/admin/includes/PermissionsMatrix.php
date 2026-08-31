<?php

/**
 * Permissions Matrix System for MediaBrain
 * 
 * This system allows fine-grained control over user access to:
 * - Apps (entire applications)
 * - Features (specific functionality within apps)
 * - Actions (specific operations like create, read, update, delete)
 */

class PermissionsMatrix
{
    private $permissionsFile;
    private $userPermissionsFile;
    private $storageManager;
    private $isCloudRun;
    private $_userPermissionsCache = [];
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new PermissionsMatrix();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        // Detect Cloud Run environment
        $this->isCloudRun = (getenv('K_SERVICE') !== false) || (getenv('GOOGLE_CLOUD_PROJECT') !== false);

        // Initialize storage manager for cloud-compatible data storage
        try {
            require_once __DIR__ . '/../../../includes/storage/FileStorageManager.php';
            $this->storageManager = FileStorageManager::getInstance();
        } catch (Exception $e) {
            log_error('PermissionsMatrix: Failed to initialize storage manager: ' . $e->getMessage());
            $this->storageManager = null;
        }

        // Set appropriate file paths based on environment
        if ($this->isCloudRun) {
            // Use writable tmp directory in Cloud Run
            $this->permissionsFile = '/bucket1/permissions.json';
            $this->userPermissionsFile = '/bucket1/user_permissions.json';
        } else {
            // Local development paths
            $this->permissionsFile = 'permissions.json';
            $this->userPermissionsFile = 'user_permissions.json';
        }

        // Initialize permissions structure if it doesn't exist
        $this->initializePermissions();
    }

    /**
     * Initialize the permissions structure with default permissions
     */
    private function initializePermissions()
    {
        // Ensure the data directory exists for local fallback
        $dataDir = dirname($this->permissionsFile);
        if (!is_dir($dataDir)) {
            if (!mkdir($dataDir, 0755, true)) {
                log_error("Failed to create permissions data directory: $dataDir");
                return;
            }
        }

        // Check if permissions exist in cloud storage or local files
        $existingPermissions = $this->loadPermissionsData('permissions.json');
        if (empty($existingPermissions)) {
            $defaultPermissions = [
                'apps' => [
                    'admin' => [
                        'name' => 'Admin Panel',
                        'description' => 'Administrative interface for user and system management',
                        'features' => [
                            'dashboard' => ['view'],
                            'users' => ['view', 'create', 'update', 'delete'],
                            'settings' => ['view', 'update'],
                            'logs' => ['view']
                        ]
                    ],
                    'recipes' => [
                        'name' => 'Recipe Manager',
                        'description' => 'Voice-guided cooking and recipe management',
                        'features' => [
                            'recipes' => ['view', 'create', 'update', 'delete'],
                            'voice_control' => ['use'],
                            'images' => ['upload', 'view'],
                            'sharing' => ['create', 'view']
                        ]
                    ],
                    'neighborhub' => [
                        'name' => 'neighborhub',
                        'description' => 'Local food, local products, local services, local businesses, local people.',
                        'features' => [
                            'current_weather' => ['view'],
                            'forecasts' => ['view'],
                            'locations' => ['add', 'remove']
                        ]
                    ],
                    'weather' => [
                        'name' => 'Weather App',
                        'description' => 'Weather information and forecasts',
                        'features' => [
                            'current_weather' => ['view'],
                            'forecasts' => ['view'],
                            'locations' => ['add', 'remove']
                        ]
                    ],
                    'ledger' => [
                        'name' => 'Ledger',
                        'description' => 'Track income, expenses, bills, and financial accounts with the Ledger app—simple, secure, and powerful financial management for individuals and small businesses.',
                    ],
                    'bibleBot' => [
                        'name' => 'Bible Bot',
                        'description' => 'Biblical text search and reference',
                        'features' => [
                            'search' => ['use'],
                            'bookmarks' => ['create', 'view', 'delete'],
                            'notes' => ['create', 'view', 'update', 'delete']
                        ]
                    ],
                    'ancestry' => [
                        'name' => 'Ancestry Research',
                        'description' => 'Family tree and genealogy research',
                        'features' => [
                            'family_tree' => ['view', 'edit'],
                            'research' => ['create', 'view'],
                            'documents' => ['upload', 'view', 'organize']
                        ]
                    ],
                    'audioLibrary' => [
                        'name' => 'Audio Library',
                        'description' => 'Audio management and playback features',
                        'features' => [
                            //'family_tree' => ['view', 'edit'],
                            //'research' => ['create', 'view'],
                            //'documents' => ['upload', 'view', 'organize']
                        ]
                    ],
                    'tryItEditor' => [
                        'name' => 'Try-it Editor',
                        'description' => 'Code editor with live preview and export/import features',
                        'features' => [
                            //'family_tree' => ['view', 'edit'],
                            //'research' => ['create', 'view'],
                            //'documents' => ['upload', 'view', 'organize']
                        ]
                    ],
                    'grapeJsEditor' => [
                        'name' => 'GrapeJS Editor',
                        'description' => 'Drag and drop web page builder',
                        'features' => [
                            //'family_tree' => ['view', 'edit'],
                            //'research' => ['create', 'view'],
                            //'documents' => ['upload', 'view', 'organize']
                        ]
                    ],
                    'researcher' => [
                        'name' => 'Researcher AI',
                        'description' => 'AI-powered research assistant for gathering information',
                        'features' => [
                            //'family_tree' => ['view', 'edit'],
                            //'research' => ['create', 'view'],
                            //'documents' => ['upload', 'view', 'organize']
                        ]
                    ],
                    'stitch' => [
                        'name' => 'Stitch',
                        'description' => '',
                        'features' => [
                            //'family_tree' => ['view', 'edit'],
                            //'research' => ['create', 'view'],
                            //'documents' => ['upload', 'view', 'organize']
                        ]
                    ],
                    'help' => [
                        'name' => 'Help & Documentation',
                        'description' => 'User guides, documentation, and support resources',
                        'features' => [
                            'documentation' => ['view'],
                            'tutorials' => ['view'],
                            'support' => ['access']
                        ]
                    ],
                    'messages' => [
                        'name' => 'Internal Comms',
                        'description' => 'Internal user-to-user messaging system.',
                        'features' => [
                            'messages' => ['read', 'write', 'delete'],
                            'attachments' => ['upload', 'download']
                        ]
                    ]
                ],
                'roles' => [
                    'guest' => [
                        'name' => 'Guest User',
                        'description' => 'Anonymous users with access to public apps',
                        'permissions' => [
                            // App-level access for public apps
                            'apps.weather' => ['access'],
                            'apps.recipes' => ['access'],
                            'apps.bibleBot' => ['access'],
                            'apps.help' => ['access'],
                            // Feature-level permissions
                            'apps.weather.features.current_weather' => ['view'],
                            'apps.weather.features.forecasts' => ['view'],
                            'apps.recipes.features.recipes' => ['view'],
                            'apps.bibleBot.features.search' => ['use']
                        ]
                    ],
                    'user' => [
                        'name' => 'Regular User',
                        'description' => 'Standard user with basic access',
                        'permissions' => [
                            'apps.weather' => ['access'],
                            'apps.weather.features.current_weather' => ['view'],
                            'apps.weather.features.forecasts' => ['view'],
                            'apps.weather.features.locations' => ['add', 'remove'],
                            'apps.recipes' => ['access'],
                            'apps.recipes.features.recipes' => ['view', 'create', 'update'],
                            'apps.recipes.features.voice_control' => ['use'],
                            'apps.recipes.features.images' => ['upload', 'view'],
                            'apps.bibleBot' => ['access'],
                            'apps.bibleBot.features.search' => ['use'],
                            'apps.bibleBot.features.bookmarks' => ['create', 'view', 'delete'],
                            'apps.help' => ['access'],
                            'apps.messages' => ['access']
                        ]
                    ],
                    'editor' => [
                        'name' => 'Content Editor',
                        'description' => 'User with content creation and editing privileges',
                        'permissions' => [
                            // Inherit all user permissions
                            'inherit' => ['user'],
                            // Additional permissions
                            'apps.recipes.features.recipes' => ['view', 'create', 'update', 'delete'],
                            'apps.recipes.features.sharing' => ['create', 'view'],
                            'apps.bibleBot.features.notes' => ['create', 'view', 'update', 'delete'],
                            'apps.ancestry' => ['access'],
                            'apps.ancestry.features.family_tree' => ['view', 'edit'],
                            'apps.ancestry.features.research' => ['create', 'view']
                        ]
                    ],
                    'admin' => [
                        'name' => 'Administrator',
                        'description' => 'Full system access and user management',
                        'permissions' => [
                            // Inherit all editor permissions
                            'inherit' => ['editor'],
                            // Admin-specific permissions
                            'apps.admin' => ['access'],
                            'apps.admin.features.dashboard' => ['view'],
                            'apps.admin.features.users' => ['view', 'create', 'update', 'delete'],
                            'apps.admin.features.settings' => ['view', 'update'],
                            'apps.admin.features.logs' => ['view'],
                            // Full access to all other apps
                            'apps.ancestry.features.documents' => ['upload', 'view', 'organize'],
                            'system.permissions' => ['manage']
                        ]
                    ]
                ]
            ];

            if (!$this->savePermissionsData('permissions.json', $defaultPermissions)) {
                error_log("Failed to write permissions file");
            }
        }

        // Check if user permissions exist in cloud storage or local files
        $existingUserPermissions = $this->loadPermissionsData('user_permissions.json');
        if (empty($existingUserPermissions)) {
            $defaultUserPermissions = [
                // Users can have role-based permissions plus individual overrides
                'admin' => [
                    'role' => 'admin',
                    'custom_permissions' => []
                ],
                'demo' => [
                    'role' => 'user',
                    'custom_permissions' => []
                ],
                'guest' => [
                    'role' => 'guest',
                    'custom_permissions' => []
                ]
            ];

            if (!$this->savePermissionsData('user_permissions.json', $defaultUserPermissions)) {
                error_log("Failed to write user permissions file");
            }
        }
    }

    /**
     * Check if a user has permission for a specific resource/action
     */
    public function hasPermission($username, $resource, $action = null)
    {
        $userPermissions = $this->getUserPermissions($username);
        $allPermissions = $this->getPermissionsStructure();

        // Build the permission key
        $permissionKey = $resource;
        if ($action) {
            $permissionKey .= '.' . $action;
        }

        // Check for explicit denials first (these override everything)
        if (isset($userPermissions['denied_permissions'][$permissionKey])) {
            $deniedActions = $userPermissions['denied_permissions'][$permissionKey];
            if ($action === null || in_array($action, $deniedActions) || in_array('*', $deniedActions)) {
                return false;
            }
        }

        // Also check the base resource for denials when action is specified
        if ($action && isset($userPermissions['denied_permissions'][$resource])) {
            $deniedActions = $userPermissions['denied_permissions'][$resource];
            if (in_array($action, $deniedActions) || in_array('*', $deniedActions)) {
                return false;
            }
        }

        // Check custom permissions
        if (isset($userPermissions['custom_permissions'][$permissionKey])) {
            return in_array($action, $userPermissions['custom_permissions'][$permissionKey]);
        }

        // Check role-based permissions
        $userRole = $userPermissions['role'] ?? 'guest';
        $rolePermissions = $this->getRolePermissions($userRole, $allPermissions);

        return $this->checkPermissionInList($permissionKey, $action, $rolePermissions);
    }

    /**
     * Alias for hasPermission to match naming used in ancestry app
     * @param string $username The username
     * @param string $permission Permission key (e.g., 'ancestry.family_tree.view')
     * @return bool
     */
    public function userHasPermission($username, $permission)
    {
        // First check if this is an app-specific permission
        $parts = explode('.', $permission);
        if (count($parts) >= 3) {
            $appName = $parts[0];
            $appPermission = implode('.', array_slice($parts, 1));

            // Check if user has app role that grants this permission
            if ($this->userHasAppPermissionFromRole($username, $appName, $permission)) {
                return true;
            }
        }

        // Fallback to regular permission check
        return $this->hasPermission($username, $permission);
    }

    /**
     * Check if user has app-specific permission through their app roles
     * Implements waterfall precedence: Stored Admin Data -> App Hook Defaults -> System Defaults
     * @param string $username The username
     * @param string $appName The app name
     * @param string $permission Full permission key
     * @return bool
     */
    private function userHasAppPermissionFromRole($username, $appName, $permission)
    {
        $userPermissions = $this->getUserPermissions($username);

        // Get user's app roles
        if (!isset($userPermissions['app_roles'][$appName])) {
            return false;
        }

        // Load permissions structure (includes both stored and app hook data)
        $allPermissions = $this->getPermissionsStructure();

        // WATERFALL PRECEDENCE: Check each of user's app roles for the permission
        foreach ($userPermissions['app_roles'][$appName] as $roleKey) {

            // 1. STORED ADMIN DATA (highest priority)
            // Check if role permissions have been customized in stored data
            if (isset($allPermissions['custom_app_roles'][$appName][$roleKey]['permissions'])) {
                if (in_array($permission, $allPermissions['custom_app_roles'][$appName][$roleKey]['permissions'])) {
                    return true;
                }
                // If custom permissions exist for this role, don't fall back to app defaults
                continue;
            }

            // 2. APP HOOK DEFAULTS (fallback)
            // Check app-defined role permissions (from hook_permissions)
            if (isset($allPermissions['app_roles'][$appName][$roleKey]['permissions'])) {
                if (in_array($permission, $allPermissions['app_roles'][$appName][$roleKey]['permissions'])) {
                    return true;
                }
            }

            // 3. SYSTEM DEFAULTS would go here if needed
        }

        return false;
    }

    /**
     * Check if user can access an app
     */
    public function canAccessApp($username, $appName)
    {
        return app_info($appName)['public_app'] || $this->hasPermission($username, "apps.{$appName}", 'access') ||
            $this->hasPermission($username, "apps.{$appName}");
    }

    /**
     * Check if user can perform action on app feature
     */
    public function canUseFeature($username, $appName, $featureName, $action)
    {
        return $this->hasPermission($username, "apps.{$appName}.features.{$featureName}", $action);
    }

    /**
     * Get all apps a user has access to
     */
    public function getUserApps($username)
    {
        static $_userPermissionsCache = [];
        if (isset($_userPermissionsCache[$username])) {
            return $_userPermissionsCache[$username];
        }
        $allPermissions = $this->getPermissionsStructure();
        $userApps = [];
        $structure = App::getInstance()->structure();
        //echo '<pre>' . print_r($userApps, true) . '</pre>';
        //echo '<pre>' . print_r($structure['apps'], true) . '</pre>'; die();
        //$mergedApps = array_merge($this->structure()['apps'], $userApps);
        //echo '<pre>' . print_r($mergedApps, true) . '</pre>';

        // 1. Create a lookup map for Array 2 based on the 'app' key for faster merging
        $lookup_map = [];
        foreach ($structure['apps'] as $details_entry) {
            $app_key = $details_entry['machine-name'] ?? null;
            if ($app_key) {
                // Store the details using the app_key as the primary key
                $lookup_map[$app_key] = $details_entry;
            }
        }
        //error_log('PermissionsMatrix Lookup Map: ' . print_r(array_keys($lookup_map), true));
        $app_list = array_merge($lookup_map, $allPermissions['apps']);
        //error_log('Merged List: ' . print_r(array_keys($app_list), true));

        foreach ($app_list as $appName => $appConfig) {
            $hasAppAccess = $this->canAccessApp($username, $appName);
            // Check for any feature/action permissions
            $features = array();
            foreach ($appConfig['features'] as $featureName => $actions) {
                foreach ($actions as $action) {
                    if ($this->canUseFeature($username, $appName, $featureName, $action)) {
                        $features[] = $featureName;
                    }
                }
            }

            if ($hasAppAccess) {
                $userApps[$appName] = $lookup_map[$appName] ?? [];
                $userApps[$appName] += [
                    'machine-name' => $appName,
                    'name' => $appConfig['name'],
                    'description' => $appConfig['description'],
                    'features' => $features,
                ];
            }
        }
        $_userPermissionsCache[$username] = $userApps;
        //error_log('User Apps for ' . $username . ': ' . print_r(array_keys($userApps), true));

        return $userApps;
    }

    /**
     * Get features a user can access within an app
     */
    public function getUserAppFeatures($username, $appName)
    {
        $allPermissions = $this->getPermissionsStructure();
        $appFeatures = $allPermissions['apps'][$appName]['features'] ?? [];
        $userFeatures = [];

        foreach ($appFeatures as $featureName => $actions) {
            $allowedActions = [];
            foreach ($actions as $action) {
                if ($this->canUseFeature($username, $appName, $featureName, $action)) {
                    $allowedActions[] = $action;
                }
            }

            if (!empty($allowedActions)) {
                $userFeatures[$featureName] = $allowedActions;
            }
        }

        return $userFeatures;
    }

    /**
     * Set custom permission for a user
     */
    public function setUserPermission($username, $resource, $actions)
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        if (!isset($userPermissions[$username])) {
            $userPermissions[$username] = [
                'role' => 'user',
                'custom_permissions' => []
            ];
        }

        $userPermissions[$username]['custom_permissions'][$resource] = is_array($actions) ? $actions : [$actions];

        $this->savePermissionsData('user_permissions.json', $userPermissions);
    }

    public function clearUserPermissionsCache($username = '')
    {
        if (!empty($username)) {
            if (isset($this->_userPermissionsCache[$username])) {
                unset($this->_userPermissionsCache[$username]);
            }
        } else {
            $this->_userPermissionsCache = [];
        }
    }

    /**
     * Set custom permission for a user
     */
    public function setUserPermissionCache($username, $resource, $actions)
    {

        $this->_userPermissionsCache[$username]['custom_permissions'][$resource] = is_array($actions) ? $actions : [$actions];
    }
    public function saveUserPermissionsCache()
    {
        $existing = $this->loadPermissionsData('user_permissions.json');
        // Merge cache into existing
        foreach ($this->_userPermissionsCache as $username => $data) {
            $existing[$username] = $data;
        }
        $this->savePermissionsData('user_permissions.json', $existing);
    }

    /**
     * Remove permission for a user
     */
    public function removeUserPermission($username, $resource)
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        if (isset($userPermissions[$username]['custom_permissions'][$resource])) {
            unset($userPermissions[$username]['custom_permissions'][$resource]);
            $this->savePermissionsData('user_permissions.json', $userPermissions);
        }
    }

    /**
     * Explicitly deny permission for a user (overrides role permissions)
     */
    public function denyUserPermission($username, $resource, $actions = ['*'])
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        if (!isset($userPermissions[$username])) {
            $userPermissions[$username] = [
                'role' => 'user',
                'custom_permissions' => [],
                'denied_permissions' => []
            ];
        }

        if (!isset($userPermissions[$username]['denied_permissions'])) {
            $userPermissions[$username]['denied_permissions'] = [];
        }

        $userPermissions[$username]['denied_permissions'][$resource] = is_array($actions) ? $actions : [$actions];

        $this->savePermissionsData('user_permissions.json', $userPermissions);
    }

    /**
     * Remove denial for a user permission
     */
    public function removeDeniedPermission($username, $resource)
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        if (isset($userPermissions[$username]['denied_permissions'][$resource])) {
            unset($userPermissions[$username]['denied_permissions'][$resource]);
            $this->savePermissionsData('user_permissions.json', $userPermissions);
        }
    }

    /**
     * Change user role
     */
    public function setUserRole($username, $role)
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        if (!isset($userPermissions[$username])) {
            $userPermissions[$username] = ['custom_permissions' => []];
        }

        $userPermissions[$username]['role'] = $role;

        $this->savePermissionsData('user_permissions.json', $userPermissions);
    }

    // Private helper methods

    public function getUserPermissions($username)
    {
        // Handle anonymous users explicitly
        if ($username === 'anonymous') {
            return [
                'role' => 'guest',
                'custom_permissions' => [],
                'denied_permissions' => []
            ];
        }

        $userPermissions = $this->loadPermissionsData('user_permissions.json');
        return $userPermissions[$username] ?? ['role' => 'guest', 'custom_permissions' => [], 'denied_permissions' => []];
    }

    /**
     * Get all user permissions data (for OAuth family checking)
     */
    public function getAllUserPermissions()
    {
        return $this->loadPermissionsData('user_permissions.json');
    }

    public function getPermissionsStructure()
    {
        $data = $this->loadPermissionsData('permissions.json');

        if (empty($data)) {
            log_error("Permissions data is empty or not found");
            return ['apps' => [], 'roles' => []];
        }

        return $data;
    }

    public function getRolePermissions($role, $allPermissions)
    {
        $roleConfig = $allPermissions['roles'][$role] ?? [];
        $permissions = $roleConfig['permissions'] ?? [];

        // Handle inheritance
        if (isset($permissions['inherit'])) {
            foreach ($permissions['inherit'] as $inheritRole) {
                $inheritedPermissions = $this->getRolePermissions($inheritRole, $allPermissions);
                $permissions = array_merge($inheritedPermissions, $permissions);
            }
        }

        return $permissions;
    }

    private function checkPermissionInList($permissionKey, $action, $permissionsList)
    {
        // Check exact match
        if (isset($permissionsList[$permissionKey])) {
            return $action ? in_array($action, $permissionsList[$permissionKey]) : true;
        }

        // Check wildcard matches (e.g., apps.recipes for apps.recipes.features.*)
        foreach ($permissionsList as $key => $actions) {
            if (strpos($permissionKey, $key . '.') === 0) {
                return $action ? in_array($action, $actions) : true;
            }
        }

        return false;
    }

    /**
     * Clear all custom permissions for a user
     */
    public function clearUserPermissions($username)
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        // Remove user from permissions
        if (isset($userPermissions[$username])) {
            unset($userPermissions[$username]);
            $this->savePermissionsData('user_permissions.json', $userPermissions);
        }
    }

    /**
     * Get permission summary for admin interface
     */
    public function getPermissionsSummary()
    {
        $allPermissions = $this->getPermissionsStructure();
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        return [
            'apps' => $allPermissions['apps'] ?? [],
            'roles' => $allPermissions['roles'] ?? [],
            'users' => $userPermissions
        ];
    }

    /**
     * Load permissions data from cloud storage or local files
     */
    private function loadPermissionsData($filename)
    {
        // Fallback to local file
        $filepath = ($filename === 'permissions.json') ? $this->permissionsFile : $this->userPermissionsFile;
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            if ($content !== false) {
                return json_decode($content, true) ?: [];
            }
        }

        return [];
    }

    /**
     * Create a new role
     */
    public function createRole($roleKey, $roleName, $roleDescription, $inheritRoles = [], $appAccess = [], $featurePermissions = [], $systemPermissions = [])
    {
        $permissionsData = $this->loadPermissionsData('permissions.json');

        // Check if role already exists
        if (isset($permissionsData['roles'][$roleKey])) {
            return ['success' => false, 'error' => 'Role already exists'];
        }

        // Build role permissions
        $permissions = [];

        // Add inheritance
        if (!empty($inheritRoles)) {
            $permissions['inherit'] = $inheritRoles;
        }

        // Add app access
        foreach ($appAccess as $appKey) {
            $permissions["apps.{$appKey}"] = ['access'];
        }

        // Add feature permissions
        foreach ($featurePermissions as $appKey => $features) {
            foreach ($features as $featureName => $actions) {
                if (!empty($actions)) {
                    $permissions["apps.{$appKey}.features.{$featureName}"] = $actions;
                }
            }
        }

        // Add system permissions
        foreach ($systemPermissions as $sysPerm) {
            if ($sysPerm === 'system.permissions.manage') {
                $permissions['system.permissions'] = ['manage'];
            }
        }

        // Create the role
        $permissionsData['roles'][$roleKey] = [
            'name' => $roleName,
            'description' => $roleDescription,
            'permissions' => $permissions
        ];

        // Save the updated permissions
        if ($this->savePermissionsData('permissions.json', $permissionsData)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to save role'];
        }
    }

    /**
     * Update an existing role
     */
    public function updateRole($originalRoleKey, $roleKey, $roleName, $roleDescription, $inheritRoles = [], $appAccess = [], $featurePermissions = [], $systemPermissions = [])
    {
        $permissionsData = $this->loadPermissionsData('permissions.json');

        // Check if original role exists
        if (!isset($permissionsData['roles'][$originalRoleKey])) {
            return ['success' => false, 'error' => 'Original role not found'];
        }

        // If role key changed, check if new key already exists
        if ($originalRoleKey !== $roleKey && isset($permissionsData['roles'][$roleKey])) {
            return ['success' => false, 'error' => 'New role key already exists'];
        }

        // Build role permissions
        $permissions = [];

        // Add inheritance
        if (!empty($inheritRoles)) {
            $permissions['inherit'] = $inheritRoles;
        }

        // Collect all apps that need access (either explicitly granted or have feature permissions)
        $appsNeedingAccess = array_merge($appAccess, array_keys($featurePermissions));
        $appsNeedingAccess = array_unique($appsNeedingAccess);

        // Add app access for all apps that have explicit access or feature permissions
        foreach ($appsNeedingAccess as $appKey) {
            $permissions["apps.{$appKey}"] = ['access'];
        }

        // Add feature permissions
        foreach ($featurePermissions as $appKey => $features) {
            foreach ($features as $featureName => $actions) {
                if (!empty($actions)) {
                    $permissions["apps.{$appKey}.features.{$featureName}"] = $actions;
                }
            }
        }

        // Add system permissions
        foreach ($systemPermissions as $sysPerm) {
            if ($sysPerm === 'system.permissions.manage') {
                $permissions['system.permissions'] = ['manage'];
            }
        }

        // Remove old role if key changed
        if ($originalRoleKey !== $roleKey) {
            unset($permissionsData['roles'][$originalRoleKey]);

            // Update all users with the old role to use the new role key
            $userPermissions = $this->loadPermissionsData('user_permissions.json');
            foreach ($userPermissions as $username => $userPerms) {
                if (isset($userPerms['role']) && $userPerms['role'] === $originalRoleKey) {
                    $userPermissions[$username]['role'] = $roleKey;
                }
            }
            $this->savePermissionsData('user_permissions.json', $userPermissions);
        }

        // Update the role
        $permissionsData['roles'][$roleKey] = [
            'name' => $roleName,
            'description' => $roleDescription,
            'permissions' => $permissions
        ];

        // Save the updated permissions
        $saveResult = $this->savePermissionsData('permissions.json', $permissionsData);

        // Add debug information
        $debugInfo = [
            'permissions_data_size' => count($permissionsData),
            'roles_count' => count($permissionsData['roles'] ?? []),
            'save_result' => $saveResult,
            'updated_role_exists' => isset($permissionsData['roles'][$roleKey]),
            'storage_manager_available' => ($this->storageManager !== null),
            'is_cloud_run' => $this->isCloudRun,
            'save_debug' => $this->lastSaveDebugInfo
        ];

        if ($saveResult) {
            return ['success' => true, 'debug' => $debugInfo];
        } else {
            return ['success' => false, 'error' => 'Failed to save role', 'debug' => $debugInfo];
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole($roleKey)
    {
        $permissionsData = $this->loadPermissionsData('permissions.json');

        // Check if role exists
        if (!isset($permissionsData['roles'][$roleKey])) {
            return ['success' => false, 'error' => 'Role not found'];
        }

        // Prevent deletion of critical system roles
        if (in_array($roleKey, ['admin', 'guest', 'user', 'editor'])) {
            return ['success' => false, 'error' => 'Cannot delete system roles'];
        }

        // Check if any users have this role
        $userPermissions = $this->loadPermissionsData('user_permissions.json');
        $usersWithRole = [];
        foreach ($userPermissions as $username => $userPerms) {
            if (isset($userPerms['role']) && $userPerms['role'] === $roleKey) {
                $usersWithRole[] = $username;
            }
        }

        if (!empty($usersWithRole)) {
            return ['success' => false, 'error' => 'Cannot delete role: ' . count($usersWithRole) . ' users have this role. Please change their roles first.'];
        }

        // Remove the role
        unset($permissionsData['roles'][$roleKey]);

        // Save the updated permissions
        if ($this->savePermissionsData('permissions.json', $permissionsData)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to delete role'];
        }
    }

    /**
     * Save permissions data to cloud storage or local files
     */
    private $lastSaveDebugInfo = [];

    private function savePermissionsData($filename, $data)
    {
        $this->lastSaveDebugInfo = [];

        // Fallback to local file
        $filepath = ($filename === 'permissions.json') ? $this->permissionsFile : $this->userPermissionsFile;
        $this->lastSaveDebugInfo['local_filepath'] = $filepath;

        // Ensure directory exists
        $dataDir = dirname($filepath);
        if (!is_dir($dataDir)) {
            $mkdirResult = mkdir($dataDir, 0755, true);
            $this->lastSaveDebugInfo['mkdir_result'] = $mkdirResult;
            $this->lastSaveDebugInfo['mkdir_error'] = $mkdirResult ? null : error_get_last();

            if (!$mkdirResult) {
                log_error("Failed to create directory $dataDir: " . json_encode(error_get_last()));
                return false;
            }
        } else {
            $this->lastSaveDebugInfo['mkdir_result'] = 'not_needed';
        }

        try {
            $writeResult = file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
            $this->lastSaveDebugInfo['local_write_result'] = $writeResult;
            log_error("Local file save for $filename to $filepath: " . ($writeResult ? 'SUCCESS' : 'FAILED'));

            if (!$writeResult) {
                $this->lastSaveDebugInfo['last_error'] = error_get_last();
            }

            return $writeResult;
        } catch (Exception $e) {
            $this->lastSaveDebugInfo['local_write_exception'] = $e->getMessage();
            log_error("Local file write exception for $filename: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Discover and register permissions from all loaded apps
     * Scans for app_hook_permissions() functions - Drupal-style hook discovery
     */
    public function discoverAppPermissions()
    {
        // Get list of loaded apps by scanning app directories
        $apps = $this->getLoadedApps();

        foreach ($apps as $appName) {
            $hookFunction = $appName . '_hook_permissions';

            // Check if the app defines a permissions hook
            if (function_exists($hookFunction)) {
                try {
                    $appPermissions = call_user_func($hookFunction);
                    $this->registerAppPermissions($appName, $appPermissions);
                    log_error("Registered permissions for app: $appName");
                } catch (Exception $e) {
                    log_error("Failed to register permissions for app $appName: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Register permissions defined by an app hook
     * @param string $appName The name of the app
     * @param array $permissions The permissions structure from hook_permissions()
     */
    public function registerAppPermissions($appName, $permissions)
    {
        $currentPermissions = $this->getPermissionsStructure();

        // Register app roles
        if (isset($permissions['roles'])) {
            if (!isset($currentPermissions['app_roles'])) {
                $currentPermissions['app_roles'] = array();
            }
            if (!isset($currentPermissions['app_roles'][$appName])) {
                $currentPermissions['app_roles'][$appName] = array();
            }

            $currentPermissions['app_roles'][$appName] = $permissions['roles'];
        }

        // Register app permissions 
        if (isset($permissions['permissions'])) {
            if (!isset($currentPermissions['app_permissions'])) {
                $currentPermissions['app_permissions'] = array();
            }
            if (!isset($currentPermissions['app_permissions'][$appName])) {
                $currentPermissions['app_permissions'][$appName] = array();
            }

            $currentPermissions['app_permissions'][$appName] = $permissions['permissions'];
        }

        $this->savePermissionsData('permissions.json', $currentPermissions);
    }

    /**
     * Get list of loaded apps by scanning app directories
     * @return array List of app names
     */
    private function getLoadedApps()
    {
        $apps = array();
        $appsDir = __DIR__ . '/../../';

        if (is_dir($appsDir)) {
            $dirs = scandir($appsDir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($appsDir . $dir)) {
                    $appFile = $appsDir . $dir . '/' . $dir . '.app.php';
                    if (file_exists($appFile)) {
                        $apps[] = $dir;
                    }
                }
            }
        }

        return $apps;
    }

    /**
     * Check if user has an app-specific role
     * @param string $username The username
     * @param string $appName The app name (e.g., 'ancestry')
     * @param string $roleKey The role key (e.g., 'ancestry_family_member')
     * @return bool
     */
    public function userHasAppRole($username, $appName, $roleKey)
    {
        $userPermissions = $this->getUserPermissions($username);

        // Check direct app role assignment
        if (
            isset($userPermissions['app_roles'][$appName]) &&
            in_array($roleKey, $userPermissions['app_roles'][$appName])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Assign app-specific role to user
     * @param string $username The username
     * @param string $appName The app name
     * @param string $roleKey The role key
     * @return bool Success status
     */
    public function assignUserAppRole($username, $appName, $roleKey)
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        // Initialize user permissions if they don't exist
        if (!isset($userPermissions[$username])) {
            $userPermissions[$username] = array(
                'role' => 'guest',
                'custom_permissions' => array(),
                'app_roles' => array()
            );
        }

        // Initialize app roles section
        if (!isset($userPermissions[$username]['app_roles'])) {
            $userPermissions[$username]['app_roles'] = array();
        }
        if (!isset($userPermissions[$username]['app_roles'][$appName])) {
            $userPermissions[$username]['app_roles'][$appName] = array();
        }

        // Add role if not already assigned
        if (!in_array($roleKey, $userPermissions[$username]['app_roles'][$appName])) {
            $userPermissions[$username]['app_roles'][$appName][] = $roleKey;
        }

        // Clear cache and save
        unset($this->_userPermissionsCache[$username]);
        return $this->savePermissionsData('user_permissions.json', $userPermissions);
    }

    /**
     * Remove app-specific role from user
     * @param string $username The username  
     * @param string $appName The app name
     * @param string $roleKey The role key
     * @return bool Success status
     */
    public function removeUserAppRole($username, $appName, $roleKey)
    {
        $userPermissions = $this->loadPermissionsData('user_permissions.json');

        if (isset($userPermissions[$username]['app_roles'][$appName])) {
            $key = array_search($roleKey, $userPermissions[$username]['app_roles'][$appName]);
            if ($key !== false) {
                unset($userPermissions[$username]['app_roles'][$appName][$key]);
                $userPermissions[$username]['app_roles'][$appName] = array_values($userPermissions[$username]['app_roles'][$appName]);
            }
        }

        // Clear cache and save
        unset($this->_userPermissionsCache[$username]);
        return $this->savePermissionsData('user_permissions.json', $userPermissions);
    }

    /**
     * Customize app role permissions (stored admin data overrides app hook defaults)
     * @param string $appName The app name
     * @param string $roleKey The role key
     * @param array $permissions Array of permission strings
     * @return bool Success status
     */
    public function customizeAppRolePermissions($appName, $roleKey, $permissions)
    {
        $permissionsData = $this->getPermissionsStructure();

        // Initialize custom app roles section
        if (!isset($permissionsData['custom_app_roles'])) {
            $permissionsData['custom_app_roles'] = array();
        }
        if (!isset($permissionsData['custom_app_roles'][$appName])) {
            $permissionsData['custom_app_roles'][$appName] = array();
        }

        // Store customized permissions (these override app hook defaults)
        $permissionsData['custom_app_roles'][$appName][$roleKey] = array(
            'permissions' => $permissions,
            'customized' => true,
            'timestamp' => time()
        );

        return $this->savePermissionsData('permissions.json', $permissionsData);
    }

    /**
     * Reset app role to use app hook defaults (remove custom override)
     * @param string $appName The app name
     * @param string $roleKey The role key
     * @return bool Success status
     */
    public function resetAppRoleToDefaults($appName, $roleKey)
    {
        $permissionsData = $this->getPermissionsStructure();

        // Remove custom override
        if (isset($permissionsData['custom_app_roles'][$appName][$roleKey])) {
            unset($permissionsData['custom_app_roles'][$appName][$roleKey]);

            // Clean up empty structures
            if (empty($permissionsData['custom_app_roles'][$appName])) {
                unset($permissionsData['custom_app_roles'][$appName]);
            }
            if (empty($permissionsData['custom_app_roles'])) {
                unset($permissionsData['custom_app_roles']);
            }
        }

        return $this->savePermissionsData('permissions.json', $permissionsData);
    }

    /**
     * Get effective permissions for an app role (waterfall precedence)
     * @param string $appName The app name
     * @param string $roleKey The role key
     * @return array Array of permissions with metadata
     */
    public function getEffectiveAppRolePermissions($appName, $roleKey)
    {
        $allPermissions = $this->getPermissionsStructure();

        // Check for custom admin override (highest priority)
        if (isset($allPermissions['custom_app_roles'][$appName][$roleKey])) {
            return array(
                'permissions' => $allPermissions['custom_app_roles'][$appName][$roleKey]['permissions'],
                'source' => 'custom_admin_override',
                'customized' => true
            );
        }

        // Fall back to app hook defaults
        if (isset($allPermissions['app_roles'][$appName][$roleKey]['permissions'])) {
            return array(
                'permissions' => $allPermissions['app_roles'][$appName][$roleKey]['permissions'],
                'source' => 'app_hook_default',
                'customized' => false
            );
        }

        // No permissions found
        return array(
            'permissions' => array(),
            'source' => 'none',
            'customized' => false
        );
    }
}
