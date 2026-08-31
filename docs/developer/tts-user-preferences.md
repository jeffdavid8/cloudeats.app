# Text-to-Speech (TTS) Waterfall Configuration System

## Overview

The TTS system implements a comprehensive **waterfall configuration pattern** that allows multiple layers of customization:

1. **System Defaults** (hardcoded in TextToSpeechService)
2. **Admin Site-wide Settings** (configurable by administrators)  
3. **User Personal Preferences** (per-user customization)
4. **Runtime Overrides** (method-specific options)

Each layer can override settings from the layer below it, providing maximum flexibility while maintaining sensible defaults.

## Configuration Layers

### 1. System Defaults (Level 1 - Base Layer)
- **Purpose**: Hardcoded fallback values ensuring the system always works
- **Voice**: en-US-Neural2-A (Female Neural2 US English)
- **Language**: en-US
- **Gender**: NEUTRAL  
- **Audio Format**: MP3
- **Speech Rate**: 1.0x
- **SSML**: Enabled
- **Caching**: Enabled (24 hours)
- **Rate Limit**: 60 requests/minute
- **Max Text**: 5000 characters

### 2. Admin Site-wide Settings (Level 2 - Organization Layer)  
- **Purpose**: Administrators set organization-wide defaults for all users
- **Location**: Admin Settings → Text-to-Speech Site-wide Settings (`?app=admin&p=settings`)
- **Storage**: `system_data/tts_admin_config.json`
- **Scope**: Affects all users as their new baseline defaults
- **Permissions**: Admin-only access

### 3. User Personal Preferences (Level 3 - User Layer)
- **Purpose**: Individual users customize their personal TTS experience  
- **Location**: User Profile → Text-to-Speech Preferences (`?app=admin&p=profile`)
- **Storage**: `user_data/{username}/preferences/tts.json`
- **Scope**: Affects only the specific user's TTS synthesis
- **Permissions**: User can only modify their own preferences

### 4. Runtime Overrides (Level 4 - Method Layer)
- **Purpose**: Application-specific or context-specific TTS options
- **Usage**: Via API method parameters
- **Scope**: Affects only the specific TTS synthesis call
- **Priority**: Highest - overrides all other layers

## Waterfall Priority Example

For a TTS synthesis request, the final configuration is determined as:

```
Final Voice = Runtime Override → User Preference → Admin Setting → System Default
```

**Example Scenario:**
- **System Default**: en-US-Neural2-A (Female)
- **Admin Setting**: en-GB-Neural2-B (British Male) 
- **User Preference**: en-AU-Neural2-A (Australian Female)
- **Runtime Override**: Not specified

**Result**: en-AU-Neural2-A (User preference wins)

**Another Example:**
- **System Default**: en-US-Neural2-A
- **Admin Setting**: Not configured  
- **User Preference**: Not configured
- **Runtime Override**: en-GB-Neural2-C

**Result**: en-GB-Neural2-C (Runtime override wins)

## Admin Site-wide Configuration

### Interface Access
1. Navigate to Admin Dashboard (`?app=admin`)
2. Click "System Settings"
3. Scroll to "Text-to-Speech Site-wide Settings" section

### Available Admin Settings
- **Default Voice**: Organization's preferred voice for all users
- **Default Language**: Primary language for the organization
- **Default Gender**: Preferred voice gender
- **Default Audio Format**: Quality vs. bandwidth preference
- **Max Text Length**: Security limit for TTS requests (100-10000 chars)
- **Rate Limit**: API usage control per user (1-300 requests/minute)
- **Cache Duration**: Performance optimization (1-7 days)
- **Enable SSML**: Enhanced speech features for all users
- **Enable Caching**: Performance and cost optimization

### Admin API Endpoints

#### Get Admin TTS Configuration
```http
GET /api.php?app=admin&action=get_admin_tts_config
Authorization: Admin required
```

**Response:**
```json
{
  "success": true,
  "config": {
    "default_voice": "en-GB-Neural2-B",
    "default_language": "en-GB",
    "default_gender": "MALE",
    "audio_format": "MP3",
    "max_text_length": 3000,
    "rate_limit_per_minute": 30,
    "cache_duration": 86400,
    "enable_ssml": true,
    "enable_caching": true
  }
}
```

#### Save Admin TTS Configuration  
```http
POST /api.php?app=admin&action=save_admin_tts_config
Content-Type: application/json
Authorization: Admin required
```

**Request Body:**
```json
{
  "default_voice": "en-GB-Neural2-B", 
  "default_language": "en-GB",
  "default_gender": "MALE",
  "audio_format": "MP3",
  "max_text_length": 3000,
  "rate_limit_per_minute": 30,
  "cache_duration": 86400,
  "enable_ssml": true,
  "enable_caching": true
}
```

## Features

### 1. User Preference Settings
- **Voice Selection**: Choose from 17+ Neural2, WaveNet, and Standard voices across multiple languages and accents
- **Language Preferences**: Support for 14 languages including English (US, UK, AU, IN), Spanish, French, German, Italian, Portuguese, Japanese, Korean, and Chinese
- **Gender Preference**: Male, Female, or Neutral voice options
- **Speech Rate Control**: Adjustable from 0.25x to 4.0x normal speed with slider control
- **Volume Control**: 0-100% volume control with real-time adjustment during playback
- **Audio Format**: MP3 (recommended), WAV (high quality), or OGG Opus (compressed)
- **SSML Support**: Enhanced natural speech with emphasis, pauses, and pronunciation control

### 2. Real-Time Preview
- Live voice preview with custom text
- Immediate volume adjustment during playback
- Preview respects all current preference settings
- Temporary file cleanup for security

### 3. Persistent Storage
- User preferences saved per-user in cloud storage
- Automatic loading of preferences on page load
- Validation and fallback to sensible defaults
- Integration with existing authentication system

## User Interface

### Location
Text-to-Speech preferences are accessible through:
1. Navigate to Admin Dashboard (`?app=admin`)
2. Click "My Profile" 
3. Scroll to "Text-to-Speech Preferences" section

### Controls
- **Voice Dropdown**: Comprehensive list of available voices with regional variations
- **Language Dropdown**: 14 supported languages with proper locale codes
- **Gender Dropdown**: Voice gender preference selection
- **Audio Format Dropdown**: Quality vs. file size trade-offs
- **Speech Rate Slider**: Visual slider with real-time rate display (0.25x - 4.0x)
- **Volume Slider**: Percentage-based volume control (0% - 100%)
- **SSML Checkbox**: Enable/disable enhanced natural speech processing
- **Preview Section**: Text area for custom preview text with play/stop controls

## API Endpoints

### Get User TTS Preferences
```http
GET /api.php?app=admin&action=get_tts_preferences
Authorization: User session required
```

**Response:**
```json
{
  "success": true,
  "preferences": {
    "voice": "en-US-Neural2-A",
    "language": "en-US",
    "gender": "NEUTRAL",
    "audio_format": "MP3",
    "speech_rate": 1.0,
    "volume": 80,
    "enable_ssml": true
  }
}
```

### Save User TTS Preferences
```http
POST /api.php?app=admin&action=save_tts_preferences
Content-Type: application/json
Authorization: User session required
```

**Request Body:**
```json
{
  "voice": "en-US-Neural2-C",
  "language": "en-US",
  "gender": "MALE",
  "audio_format": "MP3",
  "speech_rate": 1.25,
  "volume": 85,
  "enable_ssml": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "TTS preferences saved successfully"
}
```

### Preview TTS with Settings
```http
POST /api.php?app=admin&action=preview_tts
Content-Type: application/json
Authorization: User session required
```

**Request Body:**
```json
{
  "text": "Hello! This is a preview of your selected voice.",
  "voice": "en-US-Neural2-A",
  "language": "en-US",
  "gender": "NEUTRAL",
  "audio_format": "MP3",
  "speech_rate": 1.0,
  "volume": 80,
  "enable_ssml": true
}
```

**Response:**
```json
{
  "success": true,
  "audio_url": "/api.php?app=admin&action=serve_temp_audio&file=tts_preview_username_abc123.mp3",
  "temp_file": "tts_preview_username_abc123.mp3",
  "metadata": {
    "text": "Hello! This is a preview...",
    "voice": "en-US-Neural2-A",
    "format": "MP3",
    "sample_rate": 24000,
    "synthesis_time": 1699123456,
    "size": 12345
  }
}
```

### Serve Temporary Audio
```http
GET /api.php?app=admin&action=serve_temp_audio&file={filename}
Authorization: User session required
```

**Response:** Audio file stream (MP3/WAV/OGG)
- Automatic cleanup after serving
- 1-hour expiration for security
- Filename validation for security

## Technical Implementation

### Architecture

#### UserPreferencesManager Class
- **Location**: `/html/includes/UserPreferencesManager.php`
- **Purpose**: Centralized preference storage and validation
- **Storage**: File-based storage using FileStorageManager
- **Path Structure**: `user_data/{username}/preferences/tts.json`

#### TextToSpeechService Integration
- **Location**: `/html/includes/Services/TextToSpeechService.php`
- **Enhanced Methods**:
  - `withUserPreferences($username)`: Creates service instance with user settings
  - `enhanceWithUserPreferences($username, $options)`: Applies user defaults to options

#### Frontend JavaScript
- **Location**: Enhanced profile page (`/html/apps/admin/views/profile.php`)
- **Features**: Real-time preview, volume control, form validation, slider displays

### Data Flow

1. **Loading Preferences**:
   ```
   User loads profile page → loadTTSPreferences() → API call → UserPreferencesManager → JSON from storage
   ```

2. **Saving Preferences**:
   ```
   User clicks save → Form validation → API call → UserPreferencesManager → Validation → Storage
   ```

3. **Voice Preview**:
   ```
   User clicks preview → Gather settings → API call → TextToSpeechService → Temporary file → Audio playback
   ```

### Storage Schema

**File**: `user_data/{username}/preferences/tts.json`
```json
{
  "voice": "en-US-Neural2-A",
  "language": "en-US", 
  "gender": "NEUTRAL",
  "audio_format": "MP3",
  "speech_rate": 1.0,
  "volume": 80,
  "enable_ssml": true
}
```

### Validation Rules

- **Voice**: Must be from approved list of Google Cloud TTS voices
- **Language**: Must match supported locale codes
- **Gender**: NEUTRAL, FEMALE, or MALE only
- **Audio Format**: MP3, WAV, or OGG_OPUS only
- **Speech Rate**: Float between 0.25 and 4.0
- **Volume**: Integer between 0 and 100
- **Enable SSML**: Boolean value

## Integration with Applications

### For Application Developers

To use TTS with user preferences in your application:

```php
// Method 1: Create service with user preferences
$ttsService = TextToSpeechService::withUserPreferences($username);
$result = $ttsService->synthesize($text);

// Method 2: Enhance options with user preferences
$options = TextToSpeechService::enhanceWithUserPreferences($username, [
    'speech_rate' => 1.5  // Override user preference for this synthesis
]);
$result = $ttsService->synthesize($text, $options);
```

### JavaScript Integration

For frontend applications needing volume control:

```javascript
// Load user preferences
fetch('/api.php?app=admin&action=get_tts_preferences')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Apply user volume setting to audio players
      document.querySelectorAll('audio').forEach(audio => {
        audio.volume = data.preferences.volume / 100;
      });
    }
  });
```

## Security Considerations

### Access Control
- All TTS preference endpoints require user authentication
- Users can only access their own preferences
- Admin users have no special TTS preference access

### Temporary File Security
- Preview files use unique, unpredictable filenames
- Files automatically expire after 1 hour
- Served files are deleted immediately after streaming
- Filename validation prevents directory traversal

### Input Validation
- All preference values are validated against allowed ranges
- Voice names validated against known Google Cloud voices
- Preview text limited to reasonable length (5000 chars)
- Rate limiting applied to TTS generation

## Performance Optimization

### Caching Strategy
- TTS results cached for 24 hours by default
- Cache keys include user preferences for personalization
- Temporary preview files cleaned up automatically

### Resource Management
- Preview synthesis limited to avoid abuse
- Temporary file cleanup prevents storage bloat
- Background cleanup of expired files

## Troubleshooting

### Common Issues

#### Preferences Not Loading
- Check user authentication status
- Verify FileStorageManager is properly initialized
- Check browser console for JavaScript errors

#### Voice Preview Failing
- Verify Google Cloud TTS credentials are configured
- Check TextToSpeechService initialization
- Ensure temporary directory is writable

#### Volume Control Not Working
- Check if audio element supports volume property
- Verify JavaScript is not blocked
- Test with different browsers/devices

### Error Messages

- **"Authentication required"**: User not logged in
- **"Invalid filename"**: Security validation failed on temporary file
- **"File not found"**: Preview file expired or was cleaned up
- **"TTS generation failed"**: Google Cloud TTS service error
- **"Failed to save TTS preferences"**: Storage or validation error

### Debug Information

Enable debug logging by setting:
```php
$ttsService = new TextToSpeechService(['debug' => true]);
```

Logs will include:
- Preference loading attempts
- TTS synthesis details  
- Cache hits/misses
- Temporary file operations

## Future Enhancements

### Planned Features
- Voice cloning with user's voice sample
- Emotional tone selection (happy, sad, neutral)
- Background music integration
- Batch text processing
- Export preferences for backup
- Import/export voice collections

### API Versioning
- Current version: v1.0
- Backward compatibility maintained for preference format
- New features will extend existing structure
- Migration tools for schema updates

## Changelog

### Version 1.0.0 (November 2025)
- ✅ Initial release of TTS user preferences
- ✅ 17+ voice options with Neural2 support
- ✅ Real-time preview functionality  
- ✅ Volume control with slider interface
- ✅ Integration with user profile system
- ✅ Comprehensive API endpoint documentation
- ✅ Security hardening for temporary files
- ✅ Preference validation and sanitization