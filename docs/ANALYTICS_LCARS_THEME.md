# Analytics LCARS Theme - Star Trek Integration

## Overview
The Analytics dashboard now features a complete Star Trek LCARS (Library Computer Access and Retrieval System) theme integration, providing a futuristic sci-fi interface with authentic Star Trek aesthetics.

## Features Implemented

### Visual Design
- **LCARS Color Scheme**: Authentic orange, blue, red, purple, yellow, and green LCARS colors
- **Glowing Borders**: Dynamic border glows with theme-appropriate colors
- **Scanning Animations**: Horizontal scan lines across cards mimicking LCARS data retrieval
- **Corner Brackets**: LCARS-style corner accent elements
- **Rounded Elbows**: Signature LCARS rounded rectangular shapes (20px 0 20px 0 border-radius)
- **Panel Headers**: LCARS-style headers with colored elbow sections

### Interactive Elements

#### Stat Cards
- **Gradient Backgrounds**: Deep space gradients with transparency
- **Pulsing Icons**: Icons pulse with opacity animation
- **Hover Effects**: Color-shift borders on hover (orange → blue)
- **Shadow Glows**: Multi-layer box-shadows for depth and glow effects

#### Active Users Card (Special Treatment)
- **Green Pulse Animation**: Continuous pulsing glow in LCARS green
- **Real-time Updates**: Visual pulse on data refresh
- **Live Counter**: Auto-updates every 10 seconds with animation

#### Error Card (Red Alert Style)
- **Red Alert Animation**: Pulsing red/orange glow animation
- **Live Tail Mode**: Special animation when error log tail is active
- **LCARS Red Theme**: Authentic red alert color scheme

### Sound Effects
Authentic Star Trek: The Next Generation computer sounds for user interactions:

- **Active User Update**: Computer beep (`tng_comp_beep_26.mp3`)
- **Error Log Tail Toggle**: Acknowledged sound (`tng_acknowledged.mp3`)
- **Error Log Scan**: Sensor scan sound (`tng_sensor_scan.mp3`)
- **Data Processing**: Panel beep (`tng_comp_panel_06.mp3`)

Sounds play at 30% volume and can be toggled on/off programmatically.

### Chart Styling
- **LCARS Container**: Deep space background with blue border
- **Glowing Effects**: Inset and outset glows for depth
- **Data Stream Colors**: Chart colors match LCARS palette

### UI Components

#### Period Selector Chips
- **Inactive State**: LCARS orange borders with gradient background
- **Hover State**: Orange-to-red gradient with increased glow
- **Active State**: Blue-to-purple gradient with bold text
- **Scale Transform**: Slight scale increase on hover (1.05)

#### Progress Bars
- **Dark Background**: Deep space theme with orange border
- **Animated Fills**: Orange-yellow-orange gradient with pulse animation
- **Glowing Effect**: Box-shadow on progress bar

#### Visitor Log Items
- **Blue Accent Border**: Left border in LCARS blue
- **Gradient Background**: Blue gradient fading to deep space
- **Hover Effect**: Shifts to orange theme with glow

#### Top Search Items
- **Purple Accent**: LCARS purple left border
- **Transparent Gradients**: Subtle purple gradient backgrounds

### Auto-Detection
The theme automatically detects if Star Trek LCARS theme is active via:
```php
$currentTheme = $_SESSION['theme'] ?? 'default';
$isLCARS = ($currentTheme === 'startrek');
```

### CSS Files Loaded (LCARS Mode)
1. `/themes/startrek/lcars-base.css` - Core LCARS variables and fonts
2. `/themes/startrek/analytics-lcars.css` - Analytics-specific LCARS styles
3. `/themes/startrek/animations.css` - LCARS animations library

### JavaScript Integration
- **Sound System**: `analytics-lcars-sounds.js` loaded conditionally
- **Dynamic Checks**: `isLCARS` JavaScript variable for runtime detection
- **Event Handlers**: Sound playback on data updates
- **Animation Triggers**: CSS animations triggered via JavaScript

## Technical Details

### LCARS Color Variables
```css
--lcars-orange: #ff9900
--lcars-blue: #9999ff
--lcars-red: #ff6600
--lcars-purple: #cc99cc
--lcars-yellow: #ffcc00
--lcars-green: #00ff00
```

### Key Animations
- `lcars-scan`: 3s infinite horizontal scan
- `lcars-pulse`: 2s alternate opacity pulse
- `lcars-active-pulse`: 1.5s infinite active user glow
- `lcars-alert`: 2s infinite red alert pulse
- `lcars-progress-glow`: 2s infinite progress bar glow
- `lcars-data-stream`: 2s infinite gradient movement

### Sound Effect Class
```javascript
class AnalyticsLCARSSounds {
    play(soundName)     // Play specific sound
    toggle()            // Enable/disable sounds
    setVolume(volume)   // Set volume (0-1)
}
```

## Usage

### For Users
1. Enable Star Trek LCARS theme in your profile/settings
2. Navigate to Analytics dashboard (`/?app=admin&p=analytics`)
3. Dashboard automatically applies LCARS styling
4. Interact with live updates to hear authentic computer sounds

### For Developers
The theme gracefully degrades - if LCARS theme is not active, standard MaterializeCSS styling is used:

```php
<div class="card analytics-card <?php echo $themeClass; ?>">
    <!-- Content -->
</div>
```

Where `$themeClass` is either `'lcars-theme'` or empty string.

## Browser Compatibility
- Modern browsers with CSS3 support
- CSS Grid and Flexbox required
- Audio API for sound effects
- Tested on Chrome, Firefox, Safari, Edge

## Performance Considerations
- CSS animations use GPU-accelerated properties (transform, opacity)
- Sound effects lazy-loaded only when LCARS theme active
- Minimal JavaScript overhead for theme detection
- No impact on non-LCARS users

## Accessibility
- High contrast maintained in LCARS color scheme
- Text remains readable with proper shadow effects
- Animations respect `prefers-reduced-motion` (could be enhanced)
- Sound effects are optional and don't convey critical information

## Future Enhancements
- Voice commands ("Computer, show analytics")
- More sound variations for different event types
- LCARS startup sequence animation
- Theme toggle button in analytics dashboard
- Customizable LCARS color schemes
- LCARS-style data tables for visitor logs

## Files Created/Modified

### New Files
1. `html/themes/startrek/analytics-lcars.css` - Main LCARS analytics stylesheet
2. `html/apps/admin/js/analytics-lcars-sounds.js` - Sound effects system
3. `docs/ANALYTICS_LCARS_THEME.md` - This documentation

### Modified Files
1. `html/apps/admin/views/analytics.php` - Theme detection and conditional styling

## Screenshots/Demo
Access the LCARS-themed analytics at:
- Standard: `https://mediabrain.app.local/?app=admin&p=analytics`
- Ensure Star Trek theme is enabled in user settings

## Credits
- Star Trek LCARS design © CBS/Paramount
- Sound effects sourced from TrekCore.com audio library
- Original LCARS interface designed by Michael Okuda for Star Trek: TNG
