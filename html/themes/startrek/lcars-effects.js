/* Star Trek LCARS Theme - LCARS Effects JavaScript */

/**
 * LCARS Effects - Advanced Star Trek LCARS Interface Effects
 * This file contains specialized effects and interactions for the LCARS theme
 */

class LCARSEffects {
    constructor() {
        this.isActive = false;
        this.effectsQueue = [];
        this.soundEnabled = true;
        this.init();
    }
    
    init() {
        this.setupKeyboardShortcuts();
        this.setupMouseEffects();
        this.setupAdvancedAnimations();
        this.isActive = true;
        console.log('LCARS Effects system initialized');
    }
    
    /**
     * Setup keyboard shortcuts for LCARS functions
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl + Alt + L = Activate LCARS mode
            if (e.ctrlKey && e.altKey && e.key === 'l') {
                e.preventDefault();
                this.activateLCARSMode();
            }
            
            // Ctrl + Alt + A = Activate alert
            if (e.ctrlKey && e.altKey && e.key === 'a') {
                e.preventDefault();
                this.redAlert();
            }
            
            // Ctrl + Alt + S = System status
            if (e.ctrlKey && e.altKey && e.key === 's') {
                e.preventDefault();
                this.systemStatus();
            }
            
            // Escape = Clear all effects
            if (e.key === 'Escape') {
                this.clearAllEffects();
            }
        });
    }
    
    /**
     * Setup advanced mouse effects
     */
    setupMouseEffects() {
        let mouseTrail = [];
        const maxTrailLength = 20;
        
        document.addEventListener('mousemove', (e) => {
            // Add mouse trail effect
            mouseTrail.push({
                x: e.clientX,
                y: e.clientY,
                time: Date.now()
            });
            
            // Limit trail length
            if (mouseTrail.length > maxTrailLength) {
                mouseTrail.shift();
            }
            
            // Create trail effect
            this.createMouseTrail(mouseTrail);
        });
        
        // Click effects
        document.addEventListener('click', (e) => {
            this.createClickEffect(e.clientX, e.clientY);
        });
    }
    
    /**
     * Create mouse trail effect
     */
    createMouseTrail(trail) {
        // Remove existing trail
        const existingTrail = document.querySelectorAll('.lcars-mouse-trail');
        existingTrail.forEach(el => el.remove());
        
        // Create new trail
        trail.forEach((point, index) => {
            const trailDot = document.createElement('div');
            trailDot.className = 'lcars-mouse-trail';
            trailDot.style.cssText = `
                position: fixed;
                left: ${point.x}px;
                top: ${point.y}px;
                width: 4px;
                height: 4px;
                background: var(--lcars-orange);
                border-radius: 50%;
                pointer-events: none;
                z-index: 9998;
                opacity: ${index / trail.length};
                box-shadow: 0 0 ${index * 2}px var(--lcars-orange);
                animation: lcarsfadeIn 0.3s ease-out reverse;
            `;
            
            document.body.appendChild(trailDot);
            
            // Auto-remove
            setTimeout(() => {
                if (trailDot.parentNode) {
                    trailDot.remove();
                }
            }, 300);
        });
    }
    
    /**
     * Create click effect
     */
    createClickEffect(x, y) {
        const clickEffect = document.createElement('div');
        clickEffect.className = 'lcars-click-effect';
        clickEffect.style.cssText = `
            position: fixed;
            left: ${x - 25}px;
            top: ${y - 25}px;
            width: 50px;
            height: 50px;
            border: 2px solid var(--lcars-orange);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            animation: lcarsClickRipple 0.6s ease-out;
        `;
        
        document.body.appendChild(clickEffect);
        
        // Play click sound
        if (this.soundEnabled) {
            this.playTone(800, 0.1, 0.1);
        }
        
        // Remove after animation
        setTimeout(() => {
            if (clickEffect.parentNode) {
                clickEffect.remove();
            }
        }, 600);
    }
    
    /**
     * Setup advanced animations
     */
    setupAdvancedAnimations() {
        // Add CSS for advanced effects
        const advancedStyles = document.createElement('style');
        advancedStyles.textContent = `
            @keyframes lcarsClickRipple {
                0% {
                    transform: scale(0.1);
                    opacity: 1;
                    box-shadow: 0 0 10px var(--lcars-orange);
                }
                100% {
                    transform: scale(2);
                    opacity: 0;
                    box-shadow: 0 0 30px var(--lcars-orange);
                }
            }
            
            @keyframes lcarsSystemScan {
                0% {
                    transform: translateY(-100vh);
                }
                50% {
                    opacity: 1;
                }
                100% {
                    transform: translateY(100vh);
                }
            }
            
            .lcars-system-scan {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 4px;
                background: linear-gradient(90deg, 
                    transparent 0%,
                    var(--lcars-cyan) 50%,
                    transparent 100%);
                z-index: 10000;
                animation: lcarsSystemScan 3s ease-in-out;
                box-shadow: 0 0 20px var(--lcars-cyan);
            }
        `;
        document.head.appendChild(advancedStyles);
    }
    
    /**
     * Activate LCARS mode with full effects
     */
    activateLCARSMode() {
        // Show activation message
        this.showSystemMessage('LCARS MODE ACTIVATED', 'success');
        
        // Enhance all panels
        const panels = document.querySelectorAll('.card, .collection');
        panels.forEach((panel, index) => {
            setTimeout(() => {
                panel.classList.add('lcars-panel', 'lcars-startup');
                this.addScanningLine(panel);
            }, index * 100);
        });
        
        // Play activation sound sequence
        this.playActivationSequence();
        
        // Add computer voice
        if (window.LCARSVoice) {
            setTimeout(() => {
                LCARSVoice.speak('LCARS interface fully activated. All systems nominal.');
            }, 1000);
        }
    }
    
    /**
     * Red Alert function
     */
    redAlert() {
        const alertOverlay = document.createElement('div');
        alertOverlay.className = 'lcars-red-alert';
        alertOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 0, 0, 0.3);
            z-index: 9999;
            pointer-events: none;
            animation: lcarsAlertFlash 1s ease-in-out 5;
        `;
        
        document.body.appendChild(alertOverlay);
        
        // Show alert message
        this.showSystemMessage('RED ALERT - ALL HANDS TO STATIONS', 'error');
        
        // Play alert sound
        this.playAlertSequence();
        
        // Speak alert
        if (window.LCARSVoice) {
            LCARSVoice.speak('Red alert. All hands to battle stations.');
        }
        
        // Remove after 5 seconds
        setTimeout(() => {
            if (alertOverlay.parentNode) {
                alertOverlay.remove();
            }
        }, 5000);
    }
    
    /**
     * System status function
     */
    systemStatus() {
        const scanLine = document.createElement('div');
        scanLine.className = 'lcars-system-scan';
        document.body.appendChild(scanLine);
        
        // Show status message
        this.showSystemMessage('RUNNING SYSTEM DIAGNOSTICS', 'info');
        
        // Play scan sound
        this.playScanSequence();
        
        // Remove scan line
        setTimeout(() => {
            if (scanLine.parentNode) {
                scanLine.remove();
            }
            
            // Show results
            this.showSystemMessage('ALL SYSTEMS OPERATIONAL', 'success');
            
            if (window.LCARSVoice) {
                LCARSVoice.speak('System diagnostics complete. All systems operational.');
            }
        }, 3000);
    }
    
    /**
     * Add scanning line to element
     */
    addScanningLine(element) {
        const scanLine = document.createElement('div');
        scanLine.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg,
                transparent 0%,
                var(--lcars-cyan) 50%,
                transparent 100%);
            animation: lcarsDataStream 2s ease-in-out infinite;
            z-index: 10;
        `;
        
        if (element.style.position !== 'relative') {
            element.style.position = 'relative';
        }
        
        element.appendChild(scanLine);
    }
    
    /**
     * Show system message
     */
    showSystemMessage(message, type = 'info') {
        if (window.LCARSAlert) {
            LCARSAlert.show(message, type, 3000);
        }
    }
    
    /**
     * Play activation sound sequence
     */
    playActivationSequence() {
        if (!this.soundEnabled) return;
        
        const sequence = [
            { freq: 400, duration: 0.2, delay: 0 },
            { freq: 600, duration: 0.2, delay: 0.3 },
            { freq: 800, duration: 0.3, delay: 0.6 },
            { freq: 1000, duration: 0.4, delay: 1.0 }
        ];
        
        sequence.forEach(note => {
            setTimeout(() => {
                this.playTone(note.freq, note.duration, 0.1);
            }, note.delay * 1000);
        });
    }
    
    /**
     * Play alert sound sequence
     */
    playAlertSequence() {
        if (!this.soundEnabled) return;
        
        for (let i = 0; i < 5; i++) {
            setTimeout(() => {
                this.playTone(300, 0.3, 0.2);
                setTimeout(() => {
                    this.playTone(500, 0.3, 0.2);
                }, 400);
            }, i * 800);
        }
    }
    
    /**
     * Play scan sound sequence
     */
    playScanSequence() {
        if (!this.soundEnabled) return;
        
        let freq = 200;
        const scanInterval = setInterval(() => {
            this.playTone(freq, 0.1, 0.05);
            freq += 100;
            if (freq > 1200) {
                clearInterval(scanInterval);
            }
        }, 100);
    }
    
    /**
     * Play tone using Web Audio API
     */
    playTone(frequency, duration, volume = 0.1) {
        if (!('AudioContext' in window || 'webkitAudioContext' in window)) {
            return;
        }
        
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(volume, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + duration);
    }
    
    /**
     * Clear all effects
     */
    clearAllEffects() {
        // Remove all effect elements
        const effects = document.querySelectorAll('.lcars-mouse-trail, .lcars-click-effect, .lcars-red-alert, .lcars-system-scan');
        effects.forEach(el => el.remove());
        
        // Stop all animations
        const animated = document.querySelectorAll('.lcars-pulse, .lcars-flicker, .lcars-alert');
        animated.forEach(el => {
            el.classList.remove('lcars-pulse', 'lcars-flicker', 'lcars-alert');
        });
        
        // Show clear message
        this.showSystemMessage('ALL EFFECTS CLEARED', 'info');
    }
    
    /**
     * Toggle sound effects
     */
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        this.showSystemMessage(`SOUND ${this.soundEnabled ? 'ENABLED' : 'DISABLED'}`, 'info');
    }
    
    /**
     * Create holographic effect on element
     */
    createHologramEffect(element, duration = 3000) {
        element.classList.add('lcars-hologram');
        
        setTimeout(() => {
            element.classList.remove('lcars-hologram');
        }, duration);
    }
    
    /**
     * Create data transfer animation between elements
     */
    createDataTransfer(fromElement, toElement) {
        const fromRect = fromElement.getBoundingClientRect();
        const toRect = toElement.getBoundingClientRect();
        
        const dataPacket = document.createElement('div');
        dataPacket.style.cssText = `
            position: fixed;
            left: ${fromRect.left + fromRect.width / 2}px;
            top: ${fromRect.top + fromRect.height / 2}px;
            width: 6px;
            height: 6px;
            background: var(--lcars-cyan);
            border-radius: 50%;
            z-index: 10000;
            box-shadow: 0 0 10px var(--lcars-cyan);
            transition: all 1s ease-in-out;
        `;
        
        document.body.appendChild(dataPacket);
        
        // Animate to target
        setTimeout(() => {
            dataPacket.style.left = (toRect.left + toRect.width / 2) + 'px';
            dataPacket.style.top = (toRect.top + toRect.height / 2) + 'px';
            dataPacket.style.transform = 'scale(1.5)';
        }, 50);
        
        // Remove after animation
        setTimeout(() => {
            if (dataPacket.parentNode) {
                dataPacket.remove();
            }
        }, 1100);
        
        // Play transfer sound
        this.playTone(1000, 0.8, 0.05);
    }
}

// Initialize LCARS Effects when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.lcarsEffects = new LCARSEffects();
    
    // Add global keyboard shortcut info
    console.log(`
    🚀 LCARS Effects Active!
    
    Keyboard Shortcuts:
    - Ctrl+Alt+L: Activate LCARS Mode
    - Ctrl+Alt+A: Red Alert
    - Ctrl+Alt+S: System Status
    - Escape: Clear All Effects
    `);
});