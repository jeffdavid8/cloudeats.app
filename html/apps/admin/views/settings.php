<?php
            function renderDir($dir, $level = 0) {
              $items = @scandir($dir);
              if ($items) {
                echo "<ul class='browser-default' style='margin-left:" . (20 * $level) . "px'>";
                foreach ($items as $item) {
                  if ($item === '.' || $item === '..') continue;
                  $full = rtrim($dir, '/') . '/' . $item;
                  if (is_dir($full)) {
                    echo "<li><i class='material-icons tiny green-text'>folder</i> <strong>" . htmlspecialchars($item) . "/</strong>";
                    renderDir($full, $level + 1);
                    echo "</li>";
                  } else {
                    echo "<li><i class='material-icons tiny grey-text'>insert_drive_file</i> " . htmlspecialchars($item) . "</li>";
                  }
                }
                echo "</ul>";
              } else {
                echo "<span class='red-text'>Unable to read directory: " . htmlspecialchars($dir) . "</span>";
              }
            }

// admin settings view
?>
<div class="row">
  <div class="col s12">
    <h4>System Settings</h4>
    <nav class="admin-breadcrumb">
      <div class="nav-wrapper">
        <div class="col s12">
          <a href="?app=admin" class="breadcrumb">Admin</a>
          <a href="?app=admin&p=settings" class="breadcrumb">System Settings</a>
        </div>
      </div>
    </nav>
  </div>
</div>

<div id="settings-messages"></div>

<div class="row">
  <div class="col s12 m6">
    <div class="card">
      <div class="card-content">
        <span class="card-title">Authentication Settings</span>

        <form id="auth-settings-form">
          <div class="input-field">
            <input type="number" id="session_timeout" name="session_timeout" value="3600" min="300" max="86400">
            <label for="session_timeout" class="active">Session Timeout (seconds)</label>
            <span class="helper-text">Default: 3600 (1 hour)</span>
          </div>

          <p>
            <label>
              <input type="checkbox" id="require_strong_passwords" name="require_strong_passwords" checked>
              <span>Require Strong Passwords</span>
            </label>
          </p>

          <p>
            <label>
              <input type="checkbox" id="enable_csrf" name="enable_csrf" checked>
              <span>Enable CSRF Protection</span>
            </label>
          </p>

          <div class="center">
            <button type="submit" class="btn">Save Settings</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col s12 m6">
    <div class="card">
      <div class="card-content">
        <span class="card-title">System Information</span>
        <ul class="collection">
          <li class="collection-item">
            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
          </li>
          <li class="collection-item">
            <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
          </li>
          <li class="collection-item">
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
          </li>
          <li class="collection-item">
            <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
          </li>
          <li class="collection-item">
            <strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?>
          </li>
        </ul>
      </div>
    </div>
  </div>

</div>

<!-- Theme Management Section -->
<div class="row">
  <div class="col s12">
    <div class="card">
      <div class="card-content">
        <span class="card-title"><i class="material-icons left">palette</i>Theme Management</span>
        <p class="grey-text">Manage site-wide themes and user theme preferences. Themes control the visual appearance and style of the entire MediaBrain interface.</p>
        
        <?php
        // Initialize Theme Manager
        require_once __DIR__ . '/../../../includes/theme/ThemeManager.php';
        $themeManager = new ThemeManager();
        $availableThemes = $themeManager->getThemePreviewData();
        $currentTheme = $themeManager->getCurrentTheme();
        $systemDefaultTheme = $themeManager->getThemeConfig('default') ? 'default' : $currentTheme;
        ?>
        
        <div class="row">
          <div class="col s12 m6">
            <div class="input-field">
              <select id="system_default_theme" name="system_default_theme">
                <?php foreach ($availableThemes as $themeName => $themeData): ?>
                  <option value="<?php echo htmlspecialchars($themeName); ?>" 
                          <?php echo ($themeName === $systemDefaultTheme) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($themeData['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <label for="system_default_theme">System Default Theme</label>
              <span class="helper-text">Default theme for new users and guests</span>
            </div>
          </div>
          <div class="col s12 m6">
            <div class="input-field">
              <select id="current_user_theme" name="current_user_theme">
                <?php foreach ($availableThemes as $themeName => $themeData): ?>
                  <option value="<?php echo htmlspecialchars($themeName); ?>" 
                          <?php echo ($themeName === $currentTheme) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($themeData['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <label for="current_user_theme">Your Personal Theme</label>
              <span class="helper-text">Theme for your admin interface</span>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col s12">
            <h6>Available Themes</h6>
            <div class="theme-gallery" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
              <?php foreach ($availableThemes as $themeName => $themeData): ?>
                <div class="theme-card" data-theme="<?php echo htmlspecialchars($themeName); ?>" 
                     style="border: 2px solid <?php echo ($themeName === $currentTheme) ? '#2196F3' : '#ddd'; ?>; 
                            border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s ease;">
                  <div class="theme-preview" style="background: <?php echo ($themeName === 'startrek') ? '#000' : '#f5f5f5'; ?>; 
                                                     height: 120px; border-radius: 4px; margin-bottom: 10px; position: relative;
                                                     <?php echo ($themeName === 'startrek') ? 'background-image: radial-gradient(circle at 20% 80%, rgba(255, 153, 0, 0.3) 0%, transparent 50%);' : ''; ?>">
                    <?php if ($themeName === 'startrek'): ?>
                      <div style="position: absolute; top: 5px; left: 5px; right: 5px; height: 2px; 
                                  background: linear-gradient(90deg, transparent, #FF9900, #9999FF, #FF6600, transparent); 
                                  animation: lcarsScanning 3s ease-in-out infinite;"></div>
                      <div style="position: absolute; bottom: 10px; left: 10px; color: #FF9900; font-size: 12px; font-family: monospace;">
                        LCARS INTERFACE
                      </div>
                    <?php else: ?>
                      <div style="position: absolute; top: 10px; left: 10px; right: 10px; height: 4px; 
                                  background: linear-gradient(90deg, #2196F3, #FF9800); border-radius: 2px;"></div>
                      <div style="position: absolute; bottom: 10px; left: 10px; color: #666; font-size: 12px;">
                        Material Design
                      </div>
                    <?php endif; ?>
                  </div>
                  <h6 style="margin: 0 0 5px 0; color: <?php echo ($themeName === $currentTheme) ? '#2196F3' : '#333'; ?>;">
                    <?php echo htmlspecialchars($themeData['name']); ?>
                    <?php if ($themeName === $currentTheme): ?>
                      <i class="material-icons tiny" style="vertical-align: middle; margin-left: 5px;">check_circle</i>
                    <?php endif; ?>
                  </h6>
                  <p style="margin: 0 0 10px 0; font-size: 0.9em; color: #666;">
                    <?php echo htmlspecialchars($themeData['description']); ?>
                  </p>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8em; color: #888;">
                      by <?php echo htmlspecialchars($themeData['author']); ?>
                    </span>
                    <span style="font-size: 0.8em; color: #888;">
                      v<?php echo htmlspecialchars($themeData['version']); ?>
                    </span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col s12">
            <button type="button" class="btn blue" onclick="saveThemeSettings()">
              <i class="material-icons left">save</i>
              Save Theme Settings
            </button>
            <button type="button" class="btn grey lighten-1" onclick="resetUserTheme()">
              <i class="material-icons left">refresh</i>
              Reset to Default
            </button>
            <button type="button" class="btn orange lighten-1" onclick="showThemePreview()" style="margin-left: 10px;">
              <i class="material-icons left">visibility</i>
              Preview Theme
            </button>
            <div style="margin-top: 10px; color: #666; font-size: 0.9em;">
              <i class="material-icons tiny" style="vertical-align: middle;">info</i>
              Keyboard shortcut: <kbd>Ctrl+Alt+T</kbd> to open quick theme selector
            </div>
          </div>
        </div>
        
      </div>
    </div>
  </div>
</div>

<!-- Admin TTS Site-wide Configuration -->
<div class="row">
  <div class="col s12">
    <div class="card">
      <div class="card-content">
        <span class="card-title"><i class="material-icons left">record_voice_over</i>Text-to-Speech Site-wide Settings</span>
        <p class="grey-text">Configure default TTS settings for all users. Users can override these in their personal preferences.</p>
        
        <form id="admin-tts-settings-form">
          <div class="row">
            <div class="col s12 m6">
              <div class="input-field">
                <select id="admin_tts_default_voice" name="default_voice">
                  <option value="en-US-Neural2-A">Neural2 Female (US)</option>
                  <option value="en-US-Neural2-C">Neural2 Male (US)</option>
                  <option value="en-US-Neural2-D">Neural2 Male Deep (US)</option>
                  <option value="en-US-Neural2-E">Neural2 Female Gentle (US)</option>
                  <option value="en-US-Neural2-F">Neural2 Female Young (US)</option>
                  <option value="en-US-Neural2-G">Neural2 Female Strong (US)</option>
                  <option value="en-US-Neural2-H">Neural2 Female Clear (US)</option>
                  <option value="en-US-Neural2-I">Neural2 Male Warm (US)</option>
                  <option value="en-US-Neural2-J">Neural2 Male Clear (US)</option>
                  <option value="en-GB-Neural2-A">Neural2 Female (UK)</option>
                  <option value="en-GB-Neural2-B">Neural2 Male (UK)</option>
                  <option value="en-GB-Neural2-C">Neural2 Female Posh (UK)</option>
                  <option value="en-GB-Neural2-D">Neural2 Male Posh (UK)</option>
                  <option value="en-AU-Neural2-A">Neural2 Female (AU)</option>
                  <option value="en-AU-Neural2-B">Neural2 Male (AU)</option>
                </select>
                <label for="admin_tts_default_voice">Default Voice</label>
              </div>
            </div>
            
            <div class="col s12 m6">
              <div class="input-field">
                <select id="admin_tts_default_language" name="default_language">
                  <option value="en-US">English (US)</option>
                  <option value="en-GB">English (UK)</option>
                  <option value="en-AU">English (Australia)</option>
                  <option value="en-IN">English (India)</option>
                  <option value="es-ES">Spanish (Spain)</option>
                  <option value="es-MX">Spanish (Mexico)</option>
                  <option value="fr-FR">French (France)</option>
                  <option value="fr-CA">French (Canada)</option>
                  <option value="de-DE">German</option>
                  <option value="it-IT">Italian</option>
                  <option value="pt-BR">Portuguese (Brazil)</option>
                  <option value="ja-JP">Japanese</option>
                  <option value="ko-KR">Korean</option>
                  <option value="zh-CN">Chinese (Simplified)</option>
                </select>
                <label for="admin_tts_default_language">Default Language</label>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col s12 m6">
              <div class="input-field">
                <select id="admin_tts_default_gender" name="default_gender">
                  <option value="NEUTRAL">Neutral</option>
                  <option value="FEMALE">Female</option>
                  <option value="MALE">Male</option>
                </select>
                <label for="admin_tts_default_gender">Default Gender</label>
              </div>
            </div>
            
            <div class="col s12 m6">
              <div class="input-field">
                <select id="admin_tts_audio_format" name="audio_format">
                  <option value="MP3">MP3 (Recommended)</option>
                  <option value="WAV">WAV (High Quality)</option>
                  <option value="OGG_OPUS">OGG Opus (Compressed)</option>
                </select>
                <label for="admin_tts_audio_format">Default Audio Format</label>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col s12 m4">
              <div class="input-field">
                <input type="number" id="admin_tts_max_text_length" name="max_text_length" min="100" max="10000" value="5000">
                <label for="admin_tts_max_text_length" class="active">Max Text Length</label>
                <span class="helper-text">Maximum characters per TTS request (100-10000)</span>
              </div>
            </div>
            
            <div class="col s12 m4">
              <div class="input-field">
                <input type="number" id="admin_tts_rate_limit" name="rate_limit_per_minute" min="1" max="300" value="60">
                <label for="admin_tts_rate_limit" class="active">Rate Limit</label>
                <span class="helper-text">TTS requests per minute per user</span>
              </div>
            </div>
            
            <div class="col s12 m4">
              <div class="input-field">
                <input type="number" id="admin_tts_cache_duration" name="cache_duration" min="3600" max="604800" value="86400">
                <label for="admin_tts_cache_duration" class="active">Cache Duration</label>
                <span class="helper-text">Seconds to cache TTS results</span>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col s12">
              <p>
                <label>
                  <input type="checkbox" id="admin_tts_enable_ssml" name="enable_ssml" checked />
                  <span>Enable SSML by Default</span>
                </label>
              </p>
              <span class="helper-text">SSML provides enhanced natural speech with emphasis and pronunciation control</span>
            </div>
          </div>

          <div class="row">
            <div class="col s12">
              <p>
                <label>
                  <input type="checkbox" id="admin_tts_enable_caching" name="enable_caching" checked />
                  <span>Enable TTS Caching</span>
                </label>
              </p>
              <span class="helper-text">Cache TTS results to improve performance and reduce API usage</span>
            </div>
          </div>

          <div class="center">
            <button type="submit" class="btn green">
              <i class="material-icons left">save</i>Save Admin TTS Settings
            </button>
            <button type="button" class="btn blue" id="reset-admin-tts-btn">
              <i class="material-icons left">refresh</i>Reset to Defaults
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <form id="oauth-settings-form">
    <div class="card">
      <div class="card-content">
        <span class="card-title">OAuth Settings</span>

        <div class="col s12 m6">
          <h6>Google OAuth</h6>
          <p>
            <label>
              <input type="checkbox" id="google_oauth_enabled" name="google_oauth_enabled">
              <span>Enable Google Login</span>
            </label>
          </p>

          <div class="input-field">
            <input type="text" id="google_client_id" name="google_client_id">
            <label for="google_client_id">Google Client ID</label>
          </div>

          <div class="input-field">
            <input type="password" id="google_client_secret" name="google_client_secret">
            <label for="google_client_secret">Google Client Secret</label>
          </div>

          <div class="divider" style="margin: 20px 0;"></div>
        </div>
        <div class="col s12 m6">

          <h6>Apple OAuth</h6>
          <p>
            <label>
              <input type="checkbox" id="apple_oauth_enabled" name="apple_oauth_enabled">
              <span>Enable Apple Login</span>
            </label>
          </p>

          <div class="input-field">
            <input type="text" id="apple_client_id" name="apple_client_id">
            <label for="apple_client_id">Apple Client ID (Service ID)</label>
          </div>

          <div class="input-field">
            <input type="text" id="apple_team_id" name="apple_team_id">
            <label for="apple_team_id">Apple Team ID</label>
          </div>

          <div class="input-field">
            <input type="text" id="apple_key_id" name="apple_key_id">
            <label for="apple_key_id">Apple Key ID</label>
          </div>

          <div class="file-field input-field">
            <div class="btn">
              <span>Apple Private Key</span>
              <input type="file" id="apple_private_key" name="apple_private_key" accept=".p8">
            </div>
            <div class="file-path-wrapper">
              <input class="file-path validate" type="text" placeholder="Upload Apple private key (.p8)">
            </div>
          </div>

          <div class="divider" style="margin: 20px 0;"></div>
        </div>
      </div>
      <div class="row">
        <div class="col s12 m6">

          <h6>Facebook OAuth</h6>
          <p>
            <label>
              <input type="checkbox" id="facebook_oauth_enabled" name="facebook_oauth_enabled">
              <span>Enable Facebook Login</span>
            </label>
          </p>

          <div class="input-field">
            <input type="text" id="facebook_client_id" name="facebook_client_id">
            <label for="facebook_client_id">Facebook App ID</label>
          </div>

          <div class="input-field">
            <input type="password" id="facebook_client_secret" name="facebook_client_secret">
            <label for="facebook_client_secret">Facebook App Secret</label>
          </div>

          <div class="center">
            <button type="submit" class="btn blue">Save OAuth Settings</button>
            <button type="button" class="btn orange" onclick="testOAuthConfig()">Test Configuration</button>
          </div>
        </div>

        <div id="oauth-test-results" style="margin-top: 15px; display: none;">
          <!-- Test results will appear here -->
        </div>
      </div>
    </div>
  </form>
</div>

<div class="row">
  <div class="col s12">
    <div class="card">
      <div class="card-content">
        <span class="card-title">Maintenance Tools</span>

        <div class="row">
          <div class="col s12 m4">
            <button onclick="clearCache()" class="btn-large orange full-width">
              <i class="material-icons left">clear_all</i>Clear Cache
            </button>
          </div>

          <div class="col s12 m4">
            <button onclick="exportUsers()" class="btn-large blue full-width">
              <i class="material-icons left">download</i>Export Users
            </button>
          </div>

          <div class="col s12 m4">
            <button onclick="viewLogs()" class="btn-large green full-width">
              <i class="material-icons left">list</i>View Logs
            </button>
          </div>
        </div>



      </div>
    </div>
  </div>
</div>

<!-- File Storage Explorer & Diagnostics -->
<div class="row">
  <div class="col s12">
    <div class="card">
      <div class="card-content">
        <span class="card-title"><i class="material-icons left">folder_open</i>File Storage Explorer</span>
        <?php
          require_once __DIR__ . '/../../../includes/storage/FileStorageManager.php';
          $fsm = FileStorageManager::getInstance();
          $providerInfo = $fsm->getProviderInfo();
          $storageType = $providerInfo['type'];
          $isCloud = ($storageType === 'google_cloud');
          $providerInfo = $fsm->getProviderInfo();
          $bucketName = $providerInfo['config']['bucket_name'] ?? '';
          echo '<strong>Bucket Name:</strong> ' . htmlspecialchars($bucketName);
        ?>
        <p><strong>Storage Type:</strong>
          <span class="chip <?php echo $isCloud ? 'blue white-text' : 'green white-text'; ?>">
            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $storageType))); ?>
          </span>
          <?
          if (!empty($bucketName)) {
            ?><span class="chip <?php echo $isCloud ? 'blue white-text' : 'green white-text'; ?>">
              <?php echo $bucketName; ?>
            </span>
            <?
          }
          ?>
          <?php if ($isCloud): ?><i class="material-icons tiny blue-text">cloud</i><?php else: ?><i class="material-icons tiny green-text">storage</i><?php endif; ?>
        </p>

        <h6><i class="material-icons tiny">account_tree</i> Directory Tree</h6>
        <div class="card" style="max-height:350px;overflow:auto;border:1px solid #eee;padding:8px;background:#fafafa">
        <?php
          if ($isCloud) {
            $files = $fsm->listFiles('', 1000, 0);
            $bucketName = $files['bucket'];
            if (isset($files['files'])) {
              echo "<ul class='browser-default'>";
              foreach ($files['files'] as $file) {
                echo "<li><i class='material-icons tiny blue-text'>insert_drive_file</i> " . htmlspecialchars($file['name']) . "</li>";
              }
              echo "</ul>";
            } else {
              echo "<span class='red-text'>Unable to list files from cloud storage. " . htmlspecialchars($bucketName) . "</span>";
            }
          } else {
            $base = $providerInfo['config']['base_path'] ?? '/';
            renderDir($base);
          }
        ?>
        </div>

        <h6><i class="material-icons tiny">bug_report</i> Diagnostics & Troubleshooting</h6>
        <ul class="browser-default">
          <li><strong>Config:</strong> <pre style="background:#f5f5f5;border-radius:4px;padding:6px;"><?php echo htmlspecialchars(print_r($providerInfo['config'], true)); ?></pre></li>
          <?php if ($isCloud):
            $keyFile = $providerInfo['config']['key_file'] ?? '';
          ?>
            <li><strong>Key File:</strong> <span class="chip"><?php echo htmlspecialchars($keyFile); ?></span></li>
            <?php if ($keyFile):
              if (file_exists($keyFile)) { ?>
                <li class="green-text"><i class="material-icons tiny">check_circle</i> Key file found and readable.</li>
              <?php } else { ?>
                <li class="red-text"><i class="material-icons tiny">error</i> Key file not found: <?php echo htmlspecialchars($keyFile); ?></li>
              <?php }
            endif;
          endif; ?>
          <li><strong>Provider Status:</strong> <pre style="background:#f5f5f5;border-radius:4px;padding:6px;"><?php echo htmlspecialchars(print_r($providerInfo['status'], true)); ?></pre></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Storage Management Section -->
<div class="row">
  <div class="col s12">
    <div class="card">
      <div class="card-content">
        <span class="card-title">File Storage Management</span>

        <!-- Storage Status -->
        <div class="row">
          <div class="col s12">
            <h6>Current Storage Provider
              <a href="#" onclick="loadStorageStatus(); return false;" class="btn-flat waves-effect" style="padding: 0 8px;">
                <i class="material-icons">refresh</i>
              </a>
            </h6>
            <div id="storage-status" class="card-panel grey lighten-4">
              <div class="preloader-wrapper small active" id="storage-status-loader">
                <div class="spinner-layer spinner-blue-only">
                  <div class="circle-clipper left">
                    <div class="circle"></div>
                  </div>
                  <div class="gap-patch">
                    <div class="circle"></div>
                  </div>
                  <div class="circle-clipper right">
                    <div class="circle"></div>
                  </div>
                </div>
              </div>
              <span>Loading storage status...</span>
            </div>
          </div>
        </div>

        <!-- Provider Switching -->
        <div class="row">
          <div class="col s12 m6">
            <h6>Switch Storage Provider</h6>
            <div class="input-field">
              <select id="storage-provider-select">
                <option value="">Choose Provider</option>
                <option value="local">Local Storage</option>
                <option value="google_cloud">Google Cloud Storage</option>
              </select>
              <label>Storage Provider</label>
            </div>
            <button id="switch-provider-btn" class="btn blue" onclick="switchStorageProvider()" disabled>
              <i class="material-icons left">swap_horiz</i>Switch Provider
            </button>
          </div>

          <div class="col s12 m6">
            <h6>Migration Tools</h6>
            <div class="row">
              <div class="col s12">
                <button id="estimate-migration-btn" class="btn orange full-width" onclick="estimateMigration()">
                  <i class="material-icons left">assessment</i>Estimate Migration
                </button>
              </div>
            </div>
            <div class="row">
              <div class="col s12">
                <button id="start-migration-btn" class="btn red full-width" onclick="startMigration()" disabled>
                  <i class="material-icons left">cloud_sync</i>Start Migration
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Migration Progress -->
        <div id="migration-progress" class="row" style="display: none;">
          <div class="col s12">
            <h6>Migration Progress</h6>
            <div class="card-panel blue lighten-5">
              <div id="migration-details">
                <p><strong>Status:</strong> <span id="migration-status">Initializing...</span></p>
                <p><strong>Current File:</strong> <span id="current-file">-</span></p>
                <p><strong>Progress:</strong> <span id="migration-progress-text">0/0</span></p>
              </div>
              <div class="progress">
                <div id="migration-progress-bar" class="determinate" style="width: 0%"></div>
              </div>
              <div id="migration-errors" style="display: none;">
                <h6>Errors:</h6>
                <ul id="migration-error-list"></ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Migration History -->
        <div class="row">
          <div class="col s12">
            <h6>Migration History</h6>
            <button class="btn-small blue" onclick="loadMigrationHistory()">
              <i class="material-icons left">history</i>Refresh History
            </button>
            <div id="migration-history" class="collection">
              <!-- History items will be loaded here -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Initialize page
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize select elements
    var elems = document.querySelectorAll('select');
    M.FormSelect.init(elems);

    // Load initial data
    loadStorageStatus();
    loadMigrationHistory();
    loadOAuthConfig();
    loadAdminTTSConfig();

    // Setup provider select change handler
    document.getElementById('storage-provider-select').addEventListener('change', function() {
      const selectedProvider = this.value;
      const switchBtn = document.getElementById('switch-provider-btn');
      const migrateBtn = document.getElementById('start-migration-btn');

      if (selectedProvider && selectedProvider !== currentStorageProvider) {
        switchBtn.disabled = false;
        migrateBtn.disabled = false;
      } else {
        switchBtn.disabled = true;
        migrateBtn.disabled = true;
      }
    });
  });

  let currentStorageProvider = '';
  let migrationInterval = null;

  // Load current storage status
  function loadStorageStatus() {
    const statusDiv = document.getElementById('storage-status');
    const loader = document.getElementById('storage-status-loader');

    // Show loading state
    statusDiv.innerHTML = `
        <div class="preloader-wrapper small active" id="storage-status-loader">
            <div class="spinner-layer spinner-blue-only">
                <div class="circle-clipper left">
                    <div class="circle"></div>
                </div>
                <div class="gap-patch">
                    <div class="circle"></div>
                </div>
                <div class="circle-clipper right">
                    <div class="circle"></div>
                </div>
            </div>
        </div>
        <span>Loading storage status...</span>
    `;

    fetch('api.php?app=admin&action=storage_status')
      .then(response => {
        console.log('Storage status response:', response.status, response.statusText);
        return response.json();
      })
      .then(data => {
        console.log('Storage status data:', data);
        const statusDiv = document.getElementById('storage-status');

        if (data.success) {
          currentStorageProvider = data.storage.type || 'google_cloud'; // Default to google_cloud in Cloud Run
          const status = data.storage.status;
          const statusColor = status.healthy ? 'green' : 'red';

          statusDiv.innerHTML = `
                    <div class="row">
                        <div class="col s12 m4">
                            <strong>Provider:</strong> ${data.storage.type.charAt(0).toUpperCase() + data.storage.type.slice(1).replace('_', ' ')}
                        </div>
                        <div class="col s12 m4">
                            <strong>Status:</strong> <span class="${statusColor}-text">${status.healthy ? 'Healthy' : 'Unhealthy'}</span>
                        </div>
                        <div class="col s12 m4">
                            <strong>Available:</strong> <span class="${status.available ? 'green' : 'red'}-text">${status.available ? 'Yes' : 'No'}</span>
                        </div>
                    </div>
                `;

          // Update provider select to exclude current provider
          updateProviderSelect();
        } else {
          statusDiv.innerHTML = `<span class="red-text">Error loading storage status: ${data.error}</span>`;
          if (data.error.includes('Admin privileges required')) {
            statusDiv.innerHTML += `<br><small>Please ensure you are logged in as an admin user.</small>`;
          }
        }
      })
      .catch(error => {
        console.error('Storage status error:', error);
        document.getElementById('storage-status').innerHTML = `<span class="red-text">Network error: ${error.message}</span>`;
      });
  }

  // Update provider select options
  function updateProviderSelect() {
    const select = document.getElementById('storage-provider-select');
    const options = select.querySelectorAll('option');

    options.forEach(option => {
      if (option.value === currentStorageProvider) {
        option.disabled = true;
        option.textContent += ' (Current)';
      }
    });

    // Reinitialize materialize select
    M.FormSelect.init(select);
  }

  // Switch storage provider
  function switchStorageProvider() {
    const selectedProvider = document.getElementById('storage-provider-select').value;

    if (!selectedProvider) {
      M.toast({
        html: 'Please select a storage provider',
        classes: 'red'
      });
      return;
    }

    if (!confirm(`Are you sure you want to switch to ${selectedProvider}? This will change where new files are stored.`)) {
      return;
    }

    const switchBtn = document.getElementById('switch-provider-btn');
    switchBtn.disabled = true;
    switchBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Switching...';

    fetch('api.php?app=admin&action=storage_switch', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          provider: selectedProvider,
          config: {}
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          M.toast({
            html: 'Storage provider switched successfully!',
            classes: 'green'
          });
          loadStorageStatus(); // Reload status
        } else {
          M.toast({
            html: 'Error switching provider: ' + data.error,
            classes: 'red'
          });
        }
      })
      .catch(error => {
        M.toast({
          html: 'Error: ' + error.message,
          classes: 'red'
        });
      })
      .finally(() => {
        switchBtn.disabled = false;
        switchBtn.innerHTML = '<i class="material-icons left">swap_horiz</i>Switch Provider';
      });
  }

  // Estimate migration
  function estimateMigration() {
    // Ensure we have a current provider set
    if (!currentStorageProvider) {
      // Try to detect from environment or default to google_cloud for Cloud Run
      currentStorageProvider = window.location.hostname.includes('run.app') ? 'google_cloud' : 'local';
    }

    const estimateBtn = document.getElementById('estimate-migration-btn');
    estimateBtn.disabled = true;
    estimateBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Estimating...';

    fetch('api.php?app=admin&action=storage_migration_estimate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          source_provider: currentStorageProvider
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const estimate = data.estimate;
          const sizeInMB = (estimate.total_size / 1048576).toFixed(2);
          const timeInMin = Math.ceil(estimate.estimated_time / 60);

          const message = `
                Migration Estimate:<br>
                • Files: ${estimate.total_files}<br>
                • Size: ${sizeInMB} MB<br>
                • Estimated Time: ${timeInMin} minutes
            `;

          M.toast({
            html: message,
            classes: 'blue',
            displayLength: 8000
          });
        } else {
          M.toast({
            html: 'Error estimating migration: ' + data.error,
            classes: 'red'
          });
        }
      })
      .catch(error => {
        M.toast({
          html: 'Error: ' + error.message,
          classes: 'red'
        });
      })
      .finally(() => {
        estimateBtn.disabled = false;
        estimateBtn.innerHTML = '<i class="material-icons left">assessment</i>Estimate Migration';
      });
  }

  // Start migration
  function startMigration() {
    // Ensure we have a current provider set
    if (!currentStorageProvider) {
      // Try to detect from environment or default to google_cloud for Cloud Run
      currentStorageProvider = window.location.hostname.includes('run.app') ? 'google_cloud' : 'local';
    }

    const selectedProvider = document.getElementById('storage-provider-select').value;

    if (!selectedProvider) {
      M.toast({
        html: 'Please select a target storage provider',
        classes: 'red'
      });
      return;
    }

    if (!confirm(`Start migration to ${selectedProvider}? This may take several minutes and cannot be stopped once started.`)) {
      return;
    }

    // Show progress section
    document.getElementById('migration-progress').style.display = 'block';
    document.getElementById('migration-status').textContent = 'Starting...';
    document.getElementById('current-file').textContent = '-';
    document.getElementById('migration-progress-text').textContent = '0/0';
    document.getElementById('migration-progress-bar').style.width = '0%';

    const startBtn = document.getElementById('start-migration-btn');
    startBtn.disabled = true;
    startBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Migrating...';

    // Start migration
    fetch('api.php?app=admin&action=storage_migrate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          source_provider: currentStorageProvider,
          target_provider: selectedProvider,
          options: {
            overwrite: false
          }
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateMigrationProgress(data);
          M.toast({
            html: 'Migration completed successfully!',
            classes: 'green'
          });
          loadMigrationHistory(); // Refresh history
        } else {
          M.toast({
            html: 'Migration failed: ' + data.error,
            classes: 'red'
          });
        }
      })
      .catch(error => {
        M.toast({
          html: 'Migration error: ' + error.message,
          classes: 'red'
        });
      })
      .finally(() => {
        startBtn.disabled = false;
        startBtn.innerHTML = '<i class="material-icons left">cloud_sync</i>Start Migration';

        if (migrationInterval) {
          clearInterval(migrationInterval);
          migrationInterval = null;
        }
      });

    // Start progress polling
    migrationInterval = setInterval(checkMigrationProgress, 2000);
  }

  // Check migration progress
  function checkMigrationProgress() {
    fetch('api.php?app=admin&action=storage_migration_progress')
      .then(response => response.json())
      .then(data => {
        if (data.success && data.progress) {
          updateMigrationProgress(data.progress);
        }
      })
      .catch(error => {
        console.error('Progress check error:', error);
      });
  }

  // Update migration progress display
  function updateMigrationProgress(progress) {
    if (progress.overall_progress) {
      const overall = progress.overall_progress;
      document.getElementById('migration-status').textContent = 'In Progress';
      document.getElementById('migration-progress-text').textContent = `${overall.migrated}/${overall.total}`;

      const percent = overall.total > 0 ? (overall.migrated / overall.total) * 100 : 0;
      document.getElementById('migration-progress-bar').style.width = percent + '%';

      if (progress.current_file) {
        document.getElementById('current-file').textContent = progress.current_file;
      }

      if (overall.errors && overall.errors.length > 0) {
        const errorsDiv = document.getElementById('migration-errors');
        const errorsList = document.getElementById('migration-error-list');
        errorsDiv.style.display = 'block';

        errorsList.innerHTML = '';
        overall.errors.slice(-5).forEach(error => {
          const li = document.createElement('li');
          li.textContent = error;
          li.className = 'red-text';
          errorsList.appendChild(li);
        });
      }
    }
  }

  // Load migration history
  function loadMigrationHistory() {
    fetch('api.php?app=admin&action=storage_migration_history')
      .then(response => response.json())
      .then(data => {
        const historyDiv = document.getElementById('migration-history');

        if (data.success) {
          if (data.migrations.length === 0) {
            historyDiv.innerHTML = '<div class="collection-item">No migration history found</div>';
            return;
          }

          historyDiv.innerHTML = '';
          data.migrations.slice(0, 10).forEach(migration => {
            const date = new Date(migration.timestamp).toLocaleString();
            const results = migration.results;
            const statusColor = results.success ? 'green' : 'red';

            const item = document.createElement('div');
            item.className = 'collection-item';
            item.innerHTML = `
                        <div class="row">
                            <div class="col s12 m3">
                                <strong>${date}</strong>
                            </div>
                            <div class="col s12 m3">
                                ${migration.source_provider} → ${migration.target_provider}
                            </div>
                            <div class="col s12 m3">
                                <span class="${statusColor}-text">
                                    ${results.migrated}/${results.total} files
                                </span>
                            </div>
                            <div class="col s12 m3">
                                Duration: ${results.duration || 0}s
                            </div>
                        </div>
                    `;
            historyDiv.appendChild(item);
          });
        } else {
          historyDiv.innerHTML = `<div class="collection-item red-text">Error loading history: ${data.error}</div>`;
        }
      })
      .catch(error => {
        document.getElementById('migration-history').innerHTML = `<div class="collection-item red-text">Error: ${error.message}</div>`;
      });
  }

  // Original settings functions
  document.getElementById('auth-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();

    document.getElementById('settings-messages').innerHTML =
      '<div class="card-panel blue lighten-4"><span class="blue-text">Settings functionality coming soon...</span></div>';
  });

  // OAuth settings form handler
  document.getElementById('oauth-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const oauthConfig = {};

    // Process form data
    for (let [key, value] of formData.entries()) {
      if (key.endsWith('_enabled')) {
        oauthConfig[key] = document.getElementById(key).checked;
      } else if (key !== 'apple_private_key') {
        oauthConfig[key] = value;
      }
    }

    // Handle Apple private key file upload
    const appleKeyFile = document.getElementById('apple_private_key').files[0];
    if (appleKeyFile) {
      const reader = new FileReader();
      reader.onload = function(e) {
        oauthConfig.apple_private_key_content = e.target.result;
        saveOAuthConfig(oauthConfig);
      };
      reader.readAsText(appleKeyFile);
    } else {
      saveOAuthConfig(oauthConfig);
    }
  });

  function saveOAuthConfig(config) {
    const saveBtn = document.querySelector('#oauth-settings-form button[type="submit"]');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Saving...';

    fetch('api.php?app=admin&action=save_oauth_config', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(config)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          M.toast({
            html: 'OAuth settings saved successfully!',
            classes: 'green'
          });
        } else {
          M.toast({
            html: 'Error saving OAuth settings: ' + data.error,
            classes: 'red'
          });
        }
      })
      .catch(error => {
        M.toast({
          html: 'Network error: ' + error.message,
          classes: 'red'
        });
      })
      .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
      });
  }

  function testOAuthConfig() {
    const testBtn = document.querySelector('button[onclick="testOAuthConfig()"]');
    const originalText = testBtn.innerHTML;
    testBtn.disabled = true;
    testBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Testing...';

    const resultsDiv = document.getElementById('oauth-test-results');
    resultsDiv.style.display = 'block';
    resultsDiv.innerHTML = '<div class="preloader-wrapper small active"><div class="spinner-layer spinner-blue-only"><div class="circle-clipper left"><div class="circle"></div></div><div class="gap-patch"><div class="circle"></div></div><div class="circle-clipper right"><div class="circle"></div></div></div></div> Testing OAuth configuration...';

    fetch('api.php?app=admin&action=test_oauth_config', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        let html = '<div class="card-panel">';

        if (data.success) {
          html += '<h6>OAuth Configuration Test Results</h6>';

          // Google results
          if (data.results.google) {
            const google = data.results.google;
            const googleStatus = google.configured ? (google.valid ? 'green' : 'orange') : 'grey';
            html += `<p><strong>Google OAuth:</strong> <span class="${googleStatus}-text">${google.status}</span></p>`;
            if (google.details) {
              html += `<p><small>${google.details}</small></p>`;
            }
          }

          // Apple results
          if (data.results.apple) {
            const apple = data.results.apple;
            const appleStatus = apple.configured ? (apple.valid ? 'green' : 'orange') : 'grey';
            html += `<p><strong>Apple OAuth:</strong> <span class="${appleStatus}-text">${apple.status}</span></p>`;
            if (apple.details) {
              html += `<p><small>${apple.details}</small></p>`;
            }
          }

          // Facebook results
          if (data.results.facebook) {
            const facebook = data.results.facebook;
            const facebookStatus = facebook.configured ? (facebook.valid ? 'green' : 'orange') : 'grey';
            html += `<p><strong>Facebook OAuth:</strong> <span class="${facebookStatus}-text">${facebook.status}</span></p>`;
            if (facebook.details) {
              html += `<p><small>${facebook.details}</small></p>`;
            }
          }
        } else {
          html += `<p class="red-text">Test failed: ${data.error}</p>`;
        }

        html += '</div>';
        resultsDiv.innerHTML = html;
      })
      .catch(error => {
        resultsDiv.innerHTML = `<div class="card-panel red lighten-4"><p class="red-text">Test error: ${error.message}</p></div>`;
      })
      .finally(() => {
        testBtn.disabled = false;
        testBtn.innerHTML = originalText;
      });
  }

  function loadOAuthConfig() {
    fetch('api.php?app=admin&action=get_oauth_config')
      .then(response => response.json())
      .then(data => {
        if (data.success && data.config) {
          const config = data.config;

          // Load Google settings
          if (config.google) {
            document.getElementById('google_oauth_enabled').checked = config.google.enabled || false;
            document.getElementById('google_client_id').value = config.google.client_id || '';
            if (config.google.client_secret) {
              document.getElementById('google_client_secret').value = '••••••••'; // Masked for security
            }
          }

          // Load Apple settings
          if (config.apple) {
            document.getElementById('apple_oauth_enabled').checked = config.apple.enabled || false;
            document.getElementById('apple_client_id').value = config.apple.client_id || '';
            document.getElementById('apple_team_id').value = config.apple.team_id || '';
            document.getElementById('apple_key_id').value = config.apple.key_id || '';
          }

          // Load Facebook settings
          if (config.facebook) {
            document.getElementById('facebook_oauth_enabled').checked = config.facebook.enabled || false;
            document.getElementById('facebook_client_id').value = config.facebook.client_id || '';
            if (config.facebook.client_secret) {
              document.getElementById('facebook_client_secret').value = '••••••••'; // Masked for security
            }
          }

          // Update labels for pre-filled fields
          M.updateTextFields();
        }
      })
      .catch(error => {
        console.error('Failed to load OAuth configuration:', error);
      });
  }

  function clearCache() {
    if (confirm('Are you sure you want to clear the cache?')) {
      document.getElementById('settings-messages').innerHTML =
        '<div class="card-panel blue lighten-4"><span class="blue-text">Cache clearing functionality coming soon...</span></div>';
    }
  }

  function exportUsers() {
    document.getElementById('settings-messages').innerHTML =
      '<div class="card-panel blue lighten-4"><span class="blue-text">User export functionality coming soon...</span></div>';
  }

  function viewLogs() {
    document.getElementById('settings-messages').innerHTML =
      '<div class="card-panel blue lighten-4"><span class="blue-text">Log viewing functionality coming soon...</span></div>';
  }

  // Admin TTS Configuration Functions
  function loadAdminTTSConfig() {
    fetch('api.php?app=admin&action=get_admin_tts_config')
      .then(response => response.json())
      .then(data => {
        if (data.success && data.config) {
          const config = data.config;

          // Populate form with existing config
          if (config.default_voice) document.getElementById('admin_tts_default_voice').value = config.default_voice;
          if (config.default_language) document.getElementById('admin_tts_default_language').value = config.default_language;
          if (config.default_gender) document.getElementById('admin_tts_default_gender').value = config.default_gender;
          if (config.audio_format) document.getElementById('admin_tts_audio_format').value = config.audio_format;
          if (config.max_text_length) document.getElementById('admin_tts_max_text_length').value = config.max_text_length;
          if (config.rate_limit_per_minute) document.getElementById('admin_tts_rate_limit').value = config.rate_limit_per_minute;
          if (config.cache_duration) document.getElementById('admin_tts_cache_duration').value = config.cache_duration;
          if (config.enable_ssml !== undefined) document.getElementById('admin_tts_enable_ssml').checked = config.enable_ssml;
          if (config.enable_caching !== undefined) document.getElementById('admin_tts_enable_caching').checked = config.enable_caching;

          // Reinitialize selects and update text fields
          M.FormSelect.init(document.querySelectorAll('select'));
          M.updateTextFields();
        }
      })
      .catch(error => {
        console.error('Failed to load admin TTS configuration:', error);
      });
  }

  // Admin TTS Settings form handler
  document.getElementById('admin-tts-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const config = {};

    // Convert FormData to object
    for (let [key, value] of formData.entries()) {
      if (key === 'enable_ssml' || key === 'enable_caching') {
        config[key] = document.getElementById(`admin_tts_${key}`).checked;
      } else {
        config[key] = value;
      }
    }

    // Convert numeric fields
    if (config.max_text_length) config.max_text_length = parseInt(config.max_text_length);
    if (config.rate_limit_per_minute) config.rate_limit_per_minute = parseInt(config.rate_limit_per_minute);
    if (config.cache_duration) config.cache_duration = parseInt(config.cache_duration);

    const saveBtn = this.querySelector('button[type="submit"]');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Saving...';

    fetch('api.php?app=admin&action=save_admin_tts_config', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(config)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        M.toast({
          html: 'Admin TTS settings saved successfully!',
          classes: 'green'
        });
        
        document.getElementById('settings-messages').innerHTML = 
          '<div class="card-panel green lighten-4"><span class="green-text">Admin TTS settings saved successfully! Users will see these as their new defaults.</span></div>';
      } else {
        M.toast({
          html: 'Error saving admin TTS settings: ' + (data.error || 'Unknown error'),
          classes: 'red'
        });
      }
    })
    .catch(error => {
      M.toast({
        html: 'Failed to save admin TTS settings: ' + error.message,
        classes: 'red'
      });
    })
    .finally(() => {
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalText;
    });
  });

  // Reset admin TTS settings to defaults
  document.getElementById('reset-admin-tts-btn').addEventListener('click', function() {
    if (confirm('Reset all admin TTS settings to system defaults? This will affect the defaults for all users.')) {
      // Set form back to system defaults
      document.getElementById('admin_tts_default_voice').value = 'en-US-Neural2-A';
      document.getElementById('admin_tts_default_language').value = 'en-US';
      document.getElementById('admin_tts_default_gender').value = 'NEUTRAL';
      document.getElementById('admin_tts_audio_format').value = 'MP3';
      document.getElementById('admin_tts_max_text_length').value = 5000;
      document.getElementById('admin_tts_rate_limit').value = 60;
      document.getElementById('admin_tts_cache_duration').value = 86400;
      document.getElementById('admin_tts_enable_ssml').checked = true;
      document.getElementById('admin_tts_enable_caching').checked = true;

      // Reinitialize form elements
      M.FormSelect.init(document.querySelectorAll('select'));
      M.updateTextFields();

      M.toast({html: 'Admin TTS settings reset to defaults', classes: 'blue'});
    }
  });

  // Theme Management Functions
  
  // Save theme settings
  function saveThemeSettings() {
    const systemDefaultTheme = document.getElementById('system_default_theme').value;
    const currentUserTheme = document.getElementById('current_user_theme').value;
    
    // Save system default theme
    fetch('?app=admin&api=themes', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        action: 'set_system_default',
        theme: systemDefaultTheme
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        M.toast({html: 'System default theme updated!', classes: 'green'});
      }
    });
    
    // Save user theme if different from current
    if (currentUserTheme !== '<?php echo $currentTheme; ?>') {
      fetch('?app=admin&api=switch-theme', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          theme: currentUserTheme,
          persistent: true
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          M.toast({html: 'Your theme updated! Refreshing page...', classes: 'green'});
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          M.toast({html: 'Failed to update your theme: ' + (data.error || 'Unknown error'), classes: 'red'});
        }
      })
      .catch(error => {
        M.toast({html: 'Theme update error: ' + error.message, classes: 'red'});
      });
    } else {
      M.toast({html: 'Theme settings saved!', classes: 'green'});
    }
  }
  
  // Reset user theme to default
  function resetUserTheme() {
    if (confirm('Reset your theme to the system default?')) {
      fetch('?app=admin&api=themes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          action: 'reset_theme'
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          M.toast({html: 'Theme reset to default! Refreshing page...', classes: 'green'});
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          M.toast({html: 'Failed to reset theme: ' + (data.error || 'Unknown error'), classes: 'red'});
        }
      })
      .catch(error => {
        M.toast({html: 'Theme reset error: ' + error.message, classes: 'red'});
      });
    }
  }
  
  // Show theme preview
  function showThemePreview() {
    const selectedTheme = document.getElementById('current_user_theme').value;
    
    if (selectedTheme === '<?php echo $currentTheme; ?>') {
      M.toast({html: 'This is already your current theme!', classes: 'blue'});
      return;
    }
    
    // Temporarily apply theme for preview
    fetch('?app=admin&api=switch-theme', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        theme: selectedTheme,
        persistent: false // Don't save permanently
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        M.toast({html: 'Preview mode activated! Refreshing...', classes: 'orange'});
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        M.toast({html: 'Preview failed: ' + (data.error || 'Unknown error'), classes: 'red'});
      }
    })
    .catch(error => {
      M.toast({html: 'Preview error: ' + error.message, classes: 'red'});
    });
  }
  
  // Theme card click handlers
  document.addEventListener('DOMContentLoaded', function() {
    const themeCards = document.querySelectorAll('.theme-card');
    
    themeCards.forEach(card => {
      card.addEventListener('click', function() {
        const themeName = this.dataset.theme;
        
        // Update the select element
        document.getElementById('current_user_theme').value = themeName;
        M.FormSelect.init(document.getElementById('current_user_theme'));
        
        // Update visual selection
        themeCards.forEach(c => {
          c.style.borderColor = '#ddd';
          const title = c.querySelector('h6');
          if (title) {
            title.style.color = '#333';
          }
          const checkIcon = c.querySelector('i');
          if (checkIcon) {
            checkIcon.remove();
          }
        });
        
        this.style.borderColor = '#2196F3';
        const title = this.querySelector('h6');
        if (title) {
          title.style.color = '#2196F3';
          title.innerHTML += ' <i class="material-icons tiny" style="vertical-align: middle; margin-left: 5px;">check_circle</i>';
        }
        
        M.toast({html: 'Selected ' + this.querySelector('h6').textContent.trim() + ' theme', classes: 'blue'});
      });
      
      // Hover effects
      card.addEventListener('mouseenter', function() {
        if (this.style.borderColor !== 'rgb(33, 150, 243)') { // Not selected
          this.style.borderColor = '#999';
          this.style.transform = 'translateY(-2px)';
          this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        }
      });
      
      card.addEventListener('mouseleave', function() {
        if (this.style.borderColor !== 'rgb(33, 150, 243)') { // Not selected
          this.style.borderColor = '#ddd';
          this.style.transform = 'translateY(0)';
          this.style.boxShadow = 'none';
        }
      });
    });
    
    // Initialize LCARS animation for Star Trek theme preview
    const startrekCard = document.querySelector('[data-theme="startrek"]');
    if (startrekCard) {
      const style = document.createElement('style');
      style.textContent = `
        @keyframes lcarsScanning {
          0%, 100% { opacity: 0.3; }
          50% { opacity: 1; }
        }
      `;
      document.head.appendChild(style);
    }
  });

</script>