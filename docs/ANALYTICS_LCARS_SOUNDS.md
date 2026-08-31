# Analytics LCARS Sound Effects Guide

## Overview
The Analytics dashboard uses authentic Star Trek: The Next Generation computer sounds from the TrekCore.com audio library. Sounds have been carefully selected to match their visual/functional context.

## Sound Mappings

### Core Analytics Events

#### 1. Active User Update (`activeUser`)
- **File**: `computer/sequences/sensor.mp3`
- **Description**: Sensor monitoring beep sequence
- **When Played**: Every 10 seconds when active user count changes
- **Context**: Mimics real-time sensor readings on the bridge

#### 2. Error Log Tail Toggle (`acknowledge`)
- **File**: `computer/voice/affirmative1_ep.mp3`
- **Description**: Computer voice says "Affirmative"
- **When Played**: When user enables live error log tail
- **Context**: Acknowledges command like the Enterprise computer

#### 3. Error Log Scanning (`scan`)
- **File**: `computer/sequences/tactical_beep_sequence.mp3`
- **Description**: Tactical console monitoring beeps
- **When Played**: Every 3 seconds during live error log tail
- **Context**: Continuous tactical monitoring of error stream

#### 4. Data Processing (`processing`)
- **File**: `computer/sequences/ops_beep_sequence.mp3`
- **Description**: Operations console activity sequence
- **When Played**: General data refresh/processing events
- **Context**: Operations station managing data flow

### Search & Query Events

#### 5. Search Query Submitted (`search`)
- **File**: `computer/scrsearch.mp3`
- **Description**: Screen search sound
- **When Played**: When BibleBot search is submitted
- **Context**: Perfect match for searching library computer data

#### 6. Data Access Alternative (`dataAccess`)
- **File**: `computer/voice/accessinglibrarycomputerdata_clean.mp3`
- **Description**: Computer voice says "Accessing library computer data"
- **When Played**: Alternative for search operations
- **Context**: Verbal confirmation of data access

### User Interaction Sounds

#### 7. Input Accepted (`inputOk`)
- **File**: `computer/input_ok_1_clean.mp3`
- **Description**: Positive input confirmation beep
- **When Played**: Period chip clicks, form submissions
- **Context**: Confirmation that input was accepted

#### 8. Keypress (`keypress`)
- **File**: `computer/keyok1.mp3`
- **Description**: Standard LCARS keypress sound
- **When Played**: Minor button clicks, UI interactions
- **Context**: General console interaction

### Error Severity Levels

#### 9. Low Priority Error (`errorLow`)
- **File**: `computer/alert03.mp3`
- **Description**: Gentle alert tone
- **When Played**: Minor errors, warnings
- **Context**: Low-level system notification

#### 10. Medium Priority Error (`errorMedium`)
- **File**: `computer/consolewarning.mp3`
- **Description**: Console warning beep
- **When Played**: Moderate errors requiring attention
- **Context**: Console-level warning

#### 11. High Priority Error (`errorHigh`)
- **File**: `computer/damagealarm.mp3`
- **Description**: Damage alarm klaxon
- **When Played**: Serious errors, system issues
- **Context**: Red alert level problems

#### 12. Critical Error (`errorCritical`)
- **File**: `computer/critical.mp3`
- **Description**: Critical system alert
- **When Played**: System failures, critical errors
- **Context**: Maximum priority alerts

### System Events

#### 13. Screen Activation (`activate`)
- **File**: `computer/sequences/computer_activate.mp3`
- **Description**: Computer console activation sequence
- **When Played**: Analytics dashboard load, major state changes
- **Context**: System initialization

#### 14. Operation Complete (`complete`)
- **File**: `computer/voice/transfercomplete_clean.mp3`
- **Description**: Computer voice says "Transfer complete"
- **When Played**: Data exports, long operations finishing
- **Context**: Task completion confirmation

#### 15. Ambient Bridge (`ambientBridge`)
- **File**: `computer/sequences/ambient_bridge_1.mp3`
- **Description**: Subtle background bridge sounds
- **When Played**: Optional ambient background (not currently used)
- **Context**: Creates authentic bridge atmosphere

## Sound Selection Criteria

Sounds were chosen based on:

1. **Contextual Accuracy**: Sound matches the visual/functional context
2. **User Recognition**: Familiar Star Trek sounds for immersion
3. **Non-Intrusive**: Sounds enhance without distracting
4. **Volume Level**: All sounds play at 30% volume by default
5. **Authentic Sources**: All from TNG-era Star Trek productions

## Usage in Code

### Playing a Sound
```javascript
if (isLCARS && window.lcarsAnalyticsSounds) {
    window.lcarsAnalyticsSounds.play('activeUser');
}
```

### Toggle Sound Effects
```javascript
window.lcarsAnalyticsSounds.toggle(); // Returns new state (true/false)
```

### Adjust Volume
```javascript
window.lcarsAnalyticsSounds.setVolume(0.5); // 50% volume (0.0 - 1.0)
```

## Sound Library Reference

Full sound library available at:
- **Base URL**: `https://www.trekcore.com/audio/`
- **Documentation**: See `html/views/pages/star-trek-sounds.php`
- **Categories**: Beeps, Alarms, Alerts, Voice, Sequences

## Alternative Sound Options

If current sounds don't fit, here are alternatives:

### For Active Users:
- `computer/alert26.mp3` - Sensors alert
- `computer/computerbeep_26.mp3` - Simple beep

### For Error Scanning:
- `computer/sequences/sensor.mp3` - Sensor sequence
- `computer/processing2.mp3` - Processing sound

### For Acknowledgment:
- `computer/voice/affirmative2_ep.mp3` - Alternative affirmative
- `computer/input_ok_2_clean.mp3` - Input OK variant

### For Search:
- `computer/voice/accessinglibrarycomputerdata_clean.mp3` - Voice confirmation
- `computer/scrshow.mp3` - Screen show sound

## Browser Compatibility

- Sounds require HTML5 Audio API support
- Modern browsers (Chrome, Firefox, Safari, Edge) all supported
- Sounds fail gracefully if API unavailable
- No sounds = no errors, silent fallback

## Performance Considerations

- Sounds loaded on-demand from TrekCore CDN
- Single Audio element reused for all sounds
- No preloading (minimal bandwidth impact)
- Sounds only loaded when LCARS theme active

## Customization

To customize sounds for your installation:

1. Download sounds from TrekCore
2. Host locally in `/audio/star trek sounds/`
3. Update `soundBase` in `analytics-lcars-sounds.js`:
   ```javascript
   this.soundBase = '/audio/star trek sounds/';
   ```
4. Update file paths to match local filenames

## Accessibility

- Sounds are optional enhancement only
- All functionality works without sound
- No critical information conveyed via sound alone
- Users can toggle sounds on/off
- Volume adjustable (default 30%)

## Future Enhancements

- Sound preferences per user
- Alternative sound packs (DS9, Voyager, Discovery)
- Randomized sound variations
- Context-aware sound selection
- Voice command integration
- Custom sound upload

## Credits

- Sound effects © CBS/Paramount
- Sourced from TrekCore.com audio library
- Original sounds from Star Trek: The Next Generation
- Sound design by Michael Westmore and team
