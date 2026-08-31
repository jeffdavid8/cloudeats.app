<?php
// admin profile view
$userManager = new UserManager();
$currentUsername = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
$currentUser = $userManager->getUser($currentUsername);

// Handle case where user is not found
if (!$currentUser) {
    $currentUser = [
        'username' => $currentUsername ?? 'admin',
        'email' => '',
        'role' => 'admin',
        'is_admin' => true,
        'active' => true,
        'created' => date('c'),
        'last_login' => null
    ];
}
?>
<div class="row">
    <div class="col s12">
        <h4>My Profile</h4>
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=profile" class="breadcrumb">My Profile</a>
                </div>
            </div>
        </nav>
    </div>
</div>
    
    <div id="profile-messages"></div>
    
    <div class="row">
        <div class="col s12 m8">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Profile Information</span>
                    
                    <form id="profile-form">
                        <div class="input-field">
                            <input type="text" id="username" value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>" readonly>
                            <label for="username" class="active">Username</label>
                        </div>
                        
                        <div class="input-field">
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                            <label for="email" class="active">Email</label>
                        </div>
                        
                        <div class="input-field">
                            <input type="password" id="current_password" name="current_password">
                            <label for="current_password">Current Password (required to change email)</label>
                        </div>
                        
                        <div class="center">
                            <button type="submit" class="btn">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Change Password</span>
                    
                    <form id="password-form">
                        <div class="input-field">
                            <input type="password" id="old_password" name="old_password" required>
                            <label for="old_password">Current Password</label>
                        </div>
                        
                        <div class="input-field">
                            <input type="password" id="new_password" name="new_password" required>
                            <label for="new_password">New Password</label>
                        </div>
                        
                        <div class="input-field">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <label for="confirm_password">Confirm New Password</label>
                        </div>
                        
                        <div class="center">
                            <button type="submit" class="btn orange">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TTS Preferences Section -->
            <div class="card">
                <div class="card-content">
                    <span class="card-title"><i class="material-icons left">record_voice_over</i>Text-to-Speech Preferences</span>
                    
                    <form id="tts-preferences-form">
                        <div class="row">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select id="tts_voice" name="tts_voice">
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
                                        <option value="en-AU-Neural2-C">Neural2 Female Young (AU)</option>
                                        <option value="en-AU-Neural2-D">Neural2 Male Young (AU)</option>
                                    </select>
                                    <label for="tts_voice">Voice</label>
                                </div>
                            </div>
                            
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select id="tts_language" name="tts_language">
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
                                    <label for="tts_language">Language</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select id="tts_gender" name="tts_gender">
                                        <option value="NEUTRAL">Neutral</option>
                                        <option value="FEMALE">Female</option>
                                        <option value="MALE">Male</option>
                                    </select>
                                    <label for="tts_gender">Gender Preference</label>
                                </div>
                            </div>
                            
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select id="audio_format" name="audio_format">
                                        <option value="MP3">MP3 (Recommended)</option>
                                        <option value="WAV">WAV (High Quality)</option>
                                        <option value="OGG_OPUS">OGG Opus (Compressed)</option>
                                    </select>
                                    <label for="audio_format">Audio Format</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12 m6">
                                <label>Speech Rate</label>
                                <p class="range-field">
                                    <input type="range" id="speech_rate" name="speech_rate" min="0.25" max="4.0" step="0.25" value="1.0" />
                                </p>
                                <span class="helper-text">Current rate: <span id="speech_rate_display">1.0x</span> (0.25x - 4.0x)</span>
                            </div>
                            
                            <div class="col s12 m6">
                                <label>Volume</label>
                                <p class="range-field">
                                    <input type="range" id="volume" name="volume" min="0" max="100" step="5" value="80" />
                                </p>
                                <span class="helper-text">Current volume: <span id="volume_display">80%</span></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12">
                                <p>
                                    <label>
                                        <input type="checkbox" id="enable_ssml" name="enable_ssml" checked />
                                        <span>Enable SSML (Enhanced Natural Speech)</span>
                                    </label>
                                </p>
                                <span class="helper-text">SSML provides more natural speech with emphasis, pauses, and pronunciation control</span>
                            </div>
                        </div>

                        <!-- TTS Preview Section -->
                        <div class="row">
                            <div class="col s12">
                                <h6>Voice Preview</h6>
                                <div class="input-field">
                                    <textarea id="preview_text" name="preview_text" class="materialize-textarea" data-length="200">Hello! This is a preview of your selected text-to-speech voice and settings. You can customize the voice, language, speech rate, and volume to your preference.</textarea>
                                    <label for="preview_text">Preview Text</label>
                                </div>
                                
                                <div class="center">
                                    <button type="button" class="btn blue" id="preview_voice_btn">
                                        <i class="material-icons left">play_arrow</i>Preview Voice
                                    </button>
                                    <button type="button" class="btn red" id="stop_preview_btn" style="display: none;">
                                        <i class="material-icons left">stop</i>Stop Preview
                                    </button>
                                </div>
                                
                                <!-- Audio Player for Preview -->
                                <div id="tts-audio-container" style="margin-top: 15px; display: none;">
                                    <audio id="tts-audio-player" controls style="width: 100%;">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>
                            </div>
                        </div>

                        <div class="center">
                            <button type="submit" class="btn green">
                                <i class="material-icons left">save</i>Save TTS Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col s12 m4">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Account Details</span>
                    <ul class="collection">
                        <li class="collection-item">
                            <strong>Role:</strong> <?php echo htmlspecialchars($currentUser['role'] ?? 'user'); ?>
                        </li>
                        <li class="collection-item">
                            <strong>Admin:</strong> <?php echo $currentUser['is_admin'] ? 'Yes' : 'No'; ?>
                        </li>
                        <li class="collection-item">
                            <strong>Status:</strong> <?php echo $currentUser['active'] ? 'Active' : 'Inactive'; ?>
                        </li>
                        <li class="collection-item">
                            <strong>Created:</strong><br>
                            <?php echo $currentUser['created'] ? date('M j, Y g:i A', strtotime($currentUser['created'])) : 'Unknown'; ?>
                        </li>
                        <?php if ($currentUser['last_login']): ?>
                        <li class="collection-item">
                            <strong>Last Login:</strong><br>
                            <?php echo date('M j, Y g:i A', strtotime($currentUser['last_login'])); ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<script>
document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api.php?app=admin&action=update_profile', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('profile-messages').innerHTML = 
                '<div class="card-panel green lighten-4"><span class="green-text">' + 
                data.message + '</span></div>';
        } else {
            document.getElementById('profile-messages').innerHTML = 
                '<div class="card-panel red lighten-4"><span class="red-text">' + 
                (data.error || 'Update failed') + '</span></div>';
        }
    })
    .catch(error => {
        document.getElementById('profile-messages').innerHTML = 
            '<div class="card-panel red lighten-4"><span class="red-text">Update failed</span></div>';
    });
});

document.getElementById('password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        document.getElementById('profile-messages').innerHTML = 
            '<div class="card-panel red lighten-4"><span class="red-text">Passwords do not match</span></div>';
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('api.php?app=admin&action=change_password', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('profile-messages').innerHTML = 
                '<div class="card-panel green lighten-4"><span class="green-text">' + 
                data.message + '</span></div>';
            document.getElementById('password-form').reset();
            M.updateTextFields();
        } else {
            document.getElementById('profile-messages').innerHTML = 
                '<div class="card-panel red lighten-4"><span class="red-text">' + 
                (data.error || 'Password change failed') + '</span></div>';
        }
    })
    .catch(error => {
        document.getElementById('profile-messages').innerHTML = 
            '<div class="card-panel red lighten-4"><span class="red-text">Password change failed</span></div>';
    });
});

// TTS Preferences functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize select elements
    var elems = document.querySelectorAll('select');
    M.FormSelect.init(elems);
    
    // Initialize character counter
    M.CharacterCounter.init(document.querySelectorAll('#preview_text'));
    
    // Load existing TTS preferences
    loadTTSPreferences();
    
    // Update display values for range sliders
    updateSliderDisplay();
    
    // Add event listeners for range sliders
    document.getElementById('speech_rate').addEventListener('input', updateSliderDisplay);
    document.getElementById('volume').addEventListener('input', updateSliderDisplay);
});

function updateSliderDisplay() {
    const speechRate = document.getElementById('speech_rate').value;
    const volume = document.getElementById('volume').value;
    
    document.getElementById('speech_rate_display').textContent = speechRate + 'x';
    document.getElementById('volume_display').textContent = volume + '%';
}

function loadTTSPreferences() {
    fetch('api.php?app=admin&action=get_tts_preferences')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.preferences) {
                const prefs = data.preferences;
                
                // Set form values
                if (prefs.voice) document.getElementById('tts_voice').value = prefs.voice;
                if (prefs.language) document.getElementById('tts_language').value = prefs.language;
                if (prefs.gender) document.getElementById('tts_gender').value = prefs.gender;
                if (prefs.audio_format) document.getElementById('audio_format').value = prefs.audio_format;
                if (prefs.speech_rate) document.getElementById('speech_rate').value = prefs.speech_rate;
                if (prefs.volume) document.getElementById('volume').value = prefs.volume;
                if (prefs.enable_ssml !== undefined) document.getElementById('enable_ssml').checked = prefs.enable_ssml;
                
                // Update displays
                updateSliderDisplay();
                
                // Reinitialize selects
                M.FormSelect.init(document.querySelectorAll('select'));
            }
        })
        .catch(error => {
            console.error('Failed to load TTS preferences:', error);
        });
}

// TTS Preferences form handler
document.getElementById('tts-preferences-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const preferences = {};
    
    // Convert FormData to object
    for (let [key, value] of formData.entries()) {
        if (key === 'enable_ssml') {
            preferences[key] = document.getElementById('enable_ssml').checked;
        } else {
            preferences[key] = value;
        }
    }
    
    // Save preferences
    fetch('api.php?app=admin&action=save_tts_preferences', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(preferences)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('profile-messages').innerHTML = 
                '<div class="card-panel green lighten-4"><span class="green-text">TTS preferences saved successfully!</span></div>';
        } else {
            document.getElementById('profile-messages').innerHTML = 
                '<div class="card-panel red lighten-4"><span class="red-text">' + 
                (data.error || 'Failed to save TTS preferences') + '</span></div>';
        }
    })
    .catch(error => {
        document.getElementById('profile-messages').innerHTML = 
            '<div class="card-panel red lighten-4"><span class="red-text">Failed to save TTS preferences</span></div>';
    });
});

// Voice preview functionality
document.getElementById('preview_voice_btn').addEventListener('click', function() {
    const previewText = document.getElementById('preview_text').value;
    
    if (!previewText.trim()) {
        M.toast({html: 'Please enter some text to preview', classes: 'red'});
        return;
    }
    
    const btn = this;
    const stopBtn = document.getElementById('stop_preview_btn');
    const audioContainer = document.getElementById('tts-audio-container');
    const audioPlayer = document.getElementById('tts-audio-player');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Generating...';
    
    // Get current form values
    const preferences = {
        voice: document.getElementById('tts_voice').value,
        language: document.getElementById('tts_language').value,
        gender: document.getElementById('tts_gender').value,
        audio_format: document.getElementById('audio_format').value,
        speech_rate: document.getElementById('speech_rate').value,
        volume: document.getElementById('volume').value,
        enable_ssml: document.getElementById('enable_ssml').checked,
        text: previewText
    };
    
    fetch('api.php?app=admin&action=preview_tts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(preferences)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.audio_url) {
            // Show audio player
            audioContainer.style.display = 'block';
            audioPlayer.src = data.audio_url;
            audioPlayer.volume = preferences.volume / 100;
            
            // Show stop button, hide preview button
            stopBtn.style.display = 'inline-block';
            btn.style.display = 'none';
            
            // Play audio
            audioPlayer.play().catch(error => {
                console.error('Audio playback failed:', error);
                M.toast({html: 'Audio playback failed', classes: 'red'});
                resetPreviewButtons();
            });
            
            // Handle audio end
            audioPlayer.addEventListener('ended', resetPreviewButtons, { once: true });
            
        } else {
            M.toast({html: 'Failed to generate voice preview: ' + (data.error || 'Unknown error'), classes: 'red'});
            resetPreviewButtons();
        }
    })
    .catch(error => {
        M.toast({html: 'Voice preview failed: ' + error.message, classes: 'red'});
        resetPreviewButtons();
    });
});

document.getElementById('stop_preview_btn').addEventListener('click', function() {
    const audioPlayer = document.getElementById('tts-audio-player');
    audioPlayer.pause();
    audioPlayer.currentTime = 0;
    resetPreviewButtons();
});

function resetPreviewButtons() {
    const previewBtn = document.getElementById('preview_voice_btn');
    const stopBtn = document.getElementById('stop_preview_btn');
    
    previewBtn.disabled = false;
    previewBtn.innerHTML = '<i class="material-icons left">play_arrow</i>Preview Voice';
    previewBtn.style.display = 'inline-block';
    stopBtn.style.display = 'none';
}

// Update volume when slider changes
document.getElementById('volume').addEventListener('input', function() {
    const audioPlayer = document.getElementById('tts-audio-player');
    if (audioPlayer.src) {
        audioPlayer.volume = this.value / 100;
    }
});

</script>