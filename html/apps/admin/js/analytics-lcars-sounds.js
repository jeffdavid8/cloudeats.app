/**
 * LCARS Analytics Sound Effects
 * Star Trek computer sounds for analytics dashboard interactions
 */

class AnalyticsLCARSSounds {
    constructor() {
        this.soundsEnabled = true;
        this.soundBase = 'https://www.trekcore.com/audio/';
        
        this.sounds = {
            // Real-time active user counter update - Sensor monitoring sound
            activeUser: this.soundBase + 'computer/sequences/sensor.mp3',
            
            // Error log tail toggle ON - Computer acknowledges activation
            acknowledge: this.soundBase + 'computer/voice/affirmative1_ep.mp3',
            
            // Error log live scanning - Tactical monitoring sequence
            scan: this.soundBase + 'computer/sequences/tactical_beep_sequence.mp3',
            
            // Data processing/refresh - Operations console activity
            processing: this.soundBase + 'computer/sequences/ops_beep_sequence.mp3',
            
            // Search query submitted - Screen search (perfect match!)
            search: this.soundBase + 'computer/scrsearch.mp3',
            
            // Accessing library data - Alternative for searches
            dataAccess: this.soundBase + 'computer/voice/accessinglibrarycomputerdata_clean.mp3',
            
            // Period chip/button click - Input accepted
            inputOk: this.soundBase + 'computer/input_ok_1_clean.mp3',
            
            // Keypress for minor interactions
            keypress: this.soundBase + 'computer/keyok1.mp3',
            
            // Error alerts (various severity levels)
            errorLow: this.soundBase + 'computer/alert03.mp3',
            errorMedium: this.soundBase + 'computer/consolewarning.mp3',
            errorHigh: this.soundBase + 'computer/damagealarm.mp3',
            errorCritical: this.soundBase + 'computer/critical.mp3',
            
            // Computer screen activation
            activate: this.soundBase + 'computer/sequences/computer_activate.mp3',
            
            // Data transfer complete
            complete: this.soundBase + 'computer/voice/transfercomplete_clean.mp3',
            
            // Ambient bridge sounds (optional for background)
            ambientBridge: this.soundBase + 'computer/sequences/ambient_bridge_1.mp3'
        };
        
        this.audio = new Audio();
        this.audio.volume = 0.3; // Set to 30% volume
    }
    
    play(soundName) {
        if (!this.soundsEnabled || !this.sounds[soundName]) {
            return;
        }
        
        try {
            this.audio.src = this.sounds[soundName];
            this.audio.play().catch(err => {
                console.log('LCARS sound playback failed:', err);
            });
        } catch (e) {
            console.log('LCARS sound error:', e);
        }
    }
    
    toggle() {
        this.soundsEnabled = !this.soundsEnabled;
        return this.soundsEnabled;
    }
    
    setVolume(volume) {
        this.audio.volume = Math.max(0, Math.min(1, volume));
    }
}

// Initialize sounds if LCARS theme is active
const lcarsAnalyticsSounds = new AnalyticsLCARSSounds();

// Export for global use
window.lcarsAnalyticsSounds = lcarsAnalyticsSounds;
