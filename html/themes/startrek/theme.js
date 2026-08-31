/* Star Trek LCARS Theme - JavaScript Enhancements */

$(document).ready(function() {
    
    // Initialize LCARS components
    initializeLCARSComponents();
    
    // Add Star Trek specific enhancements
    addStarTrekEnhancements();
    
    // Setup LCARS animations
    setupLCARSAnimations();
    
    // Play startup sound (optional)
    playStartupSequence();
    
    console.log('LCARS Interface initialized successfully');
});

/**
 * Initialize LCARS-specific components
 */
function initializeLCARSComponents() {
    // Convert regular cards to LCARS panels
    convertToLCARSPanels();
    
    // Convert buttons to LCARS style
    convertToLCARSButtons();
    
    // Add LCARS navigation enhancements
    enhanceLCARSNavigation();
    
    // Initialize data streams
    initializeDataStreams();
}

/**
 * Convert regular cards to LCARS panels
 */
function convertToLCARSPanels() {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(card => {
        if (!card.classList.contains('lcars-panel')) {
            card.classList.add('lcars-panel', 'lcars-startup');
            
            // Add scanning line effect
            const scanLine = document.createElement('div');
            scanLine.className = 'lcars-data-stream';
            card.appendChild(scanLine);
        }
    });
}

/**
 * Convert regular buttons to LCARS style
 */
function convertToLCARSButtons() {
    const buttons = document.querySelectorAll('.btn, button[type="submit"], input[type="submit"]');
    
    buttons.forEach(button => {
        if (!button.classList.contains('lcars-button')) {
            button.classList.add('lcars-button');
            
            // Add press effect
            button.addEventListener('click', function() {
                this.classList.add('lcars-button-press');
                setTimeout(() => {
                    this.classList.remove('lcars-button-press');
                }, 300);
                
                // Play button sound
                playLCARSSound('button');
            });
        }
    });
}

/**
 * Enhance navigation with LCARS styling
 */
function enhanceLCARSNavigation() {
    const nav = document.querySelector('nav, .nav-wrapper');
    if (nav && !nav.classList.contains('lcars-nav')) {
        nav.classList.add('lcars-nav');
        
        // Add hologram effect to brand logo
        const brandLogo = nav.querySelector('.brand-logo');
        if (brandLogo) {
            brandLogo.classList.add('lcars-hologram');
        }
    }
}

/**
 * Initialize data stream effects
 */
function initializeDataStreams() {
    const panels = document.querySelectorAll('.lcars-panel');
    
    panels.forEach(panel => {
        // Random data stream activation
        setInterval(() => {
            if (Math.random() < 0.1) { // 10% chance every interval
                activateDataStream(panel);
            }
        }, 3000 + Math.random() * 2000); // Random interval between 3-5 seconds
    });
}

/**
 * Activate data stream on a panel
 */
function activateDataStream(panel) {
    const existingStream = panel.querySelector('.data-stream');
    if (existingStream) {
        existingStream.remove();
    }
    
    const dataStream = document.createElement('div');
    dataStream.className = 'data-stream';
    dataStream.style.cssText = `
        position: absolute;
        top: ${Math.random() * 80 + 10}%;
        left: 0;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, 
            transparent 0%, 
            var(--lcars-cyan) 50%, 
            transparent 100%);
        animation: lcarsDataStream 2s ease-in-out;
        z-index: 10;
    `;
    
    panel.style.position = 'relative';
    panel.appendChild(dataStream);
    
    // Remove after animation
    setTimeout(() => {
        if (dataStream.parentNode) {
            dataStream.remove();
        }
    }, 2000);
}

/**
 * Add Star Trek specific enhancements
 */
function addStarTrekEnhancements() {
    // Add LCARS text effects
    addLCARSTextEffects();
    
    // Add status indicators
    addStatusIndicators();
    
    // Add alert system
    setupAlertSystem();
    
    // Add computer voice synthesis (optional)
    setupComputerVoice();
}

/**
 * Add LCARS text effects
 */
function addLCARSTextEffects() {
    const headers = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
    
    headers.forEach(header => {
        if (!header.classList.contains('lcars-text-glow')) {
            header.classList.add('lcars-text-glow');
            
            // Add random flicker effect
            setInterval(() => {
                if (Math.random() < 0.05) { // 5% chance
                    header.classList.add('lcars-flicker');
                    setTimeout(() => {
                        header.classList.remove('lcars-flicker');
                    }, 500);
                }
            }, 2000);
        }
    });
}

/**
 * Add status indicators to various elements
 */
function addStatusIndicators() {
    const statCards = document.querySelectorAll('.dashboard-stat-card');
    
    statCards.forEach(card => {
        const indicator = document.createElement('div');
        indicator.className = 'lcars-status-indicator';
        indicator.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--lcars-green);
            box-shadow: var(--glow-intensity) var(--lcars-green);
            animation: lcarsPulse 2s ease-in-out infinite;
        `;
        
        card.style.position = 'relative';
        card.appendChild(indicator);
    });
}

/**
 * Setup LCARS alert system
 */
function setupAlertSystem() {
    // Override default alert system with LCARS style
    window.LCARSAlert = {
        show: function(message, type = 'info', duration = 4000) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `lcars-alert-panel ${type}`;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--panel-dark);
                border: 2px solid var(--lcars-orange);
                border-radius: 15px;
                padding: 20px;
                max-width: 400px;
                z-index: 10000;
                box-shadow: var(--glow-intensity) var(--lcars-orange);
                animation: lcarsSlideUp 0.5s ease-out;
                font-family: 'Exo 2', sans-serif;
                color: var(--text-primary);
            `;
            
            const colors = {
                'success': 'var(--lcars-green)',
                'error': 'var(--lcars-red)',
                'warning': 'var(--lcars-yellow)',
                'info': 'var(--lcars-blue)'
            };
            
            if (colors[type]) {
                alertDiv.style.borderColor = colors[type];
                alertDiv.style.boxShadow = `var(--glow-intensity) ${colors[type]}`;
            }
            
            const iconMap = {
                'success': 'check_circle',
                'error': 'error',
                'warning': 'warning',
                'info': 'info'
            };
            
            alertDiv.innerHTML = `
                <div style="display: flex; align-items: center;">
                    <i class="material-icons" style="margin-right: 10px; color: ${colors[type] || 'var(--lcars-orange)'}">${iconMap[type] || 'info'}</i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Play alert sound
            playLCARSSound(type);
            
            // Auto remove
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.style.animation = 'lcarsfadeIn 0.3s ease-in reverse';
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 300);
                }
            }, duration);
        }
    };
}

/**
 * Setup computer voice synthesis (optional)
 */
function setupComputerVoice() {
    if ('speechSynthesis' in window) {
        window.LCARSVoice = {
            speak: function(text, rate = 0.8, pitch = 0.8) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.rate = rate;
                utterance.pitch = pitch;
                utterance.volume = 0.5;
                
                // Use a robotic-sounding voice if available
                const voices = speechSynthesis.getVoices();
                const robotVoice = voices.find(voice => 
                    voice.name.toLowerCase().includes('microsoft') || 
                    voice.name.toLowerCase().includes('google')
                );
                
                if (robotVoice) {
                    utterance.voice = robotVoice;
                }
                
                speechSynthesis.speak(utterance);
            }
        };
    }
}

/**
 * Setup LCARS animations
 */
function setupLCARSAnimations() {
    // Stagger card animations on page load
    const cards = document.querySelectorAll('.lcars-panel');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Add intersection observer for scroll animations
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('lcars-startup');
                }
            });
        });
        
        const animatedElements = document.querySelectorAll('.card, .collection-item');
        animatedElements.forEach(el => observer.observe(el));
    }
}

/**
 * Play startup sequence
 */
function playStartupSequence() {
    // Show loading screen
    showLCARSLoading();
    
    // Hide after initialization
    setTimeout(() => {
        hideLCARSLoading();
        
        // Optional: Speak welcome message
        if (window.LCARSVoice) {
            //window.LCARSVoice.speak('Please select an audio directory to browse.');
        }
    }, 2000);
}

/**
 * Show LCARS loading screen
 */
function showLCARSLoading() {
    const loading = document.createElement('div');
    loading.id = 'lcars-loading';
    loading.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: var(--space-black); z-index: 9999; display: flex; 
                    flex-direction: column; align-items: center; justify-content: center;
                    font-family: 'Orbitron', monospace; color: var(--lcars-orange);">
            <div style="font-size: 3em; font-weight: 700; text-transform: uppercase; 
                        letter-spacing: 3px; text-shadow: var(--glow-intensity) var(--lcars-orange);
                        margin-bottom: 30px;">LCARS</div>
            <div style="font-size: 1.2em; margin-bottom: 40px; color: var(--text-secondary);">
                Library Computer Access and Retrieval System
            </div>
            <div class="lcars-loading-spinner"></div>
            <div style="margin-top: 20px; font-size: 0.9em; color: var(--text-dim);">
                Initializing Interface...
            </div>
        </div>
    `;
    document.body.appendChild(loading);
}

/**
 * Hide LCARS loading screen
 */
function hideLCARSLoading() {
    const loading = document.getElementById('lcars-loading');
    if (loading) {
        loading.style.animation = 'lcarsfadeIn 0.5s ease-in reverse';
        setTimeout(() => {
            loading.remove();
        }, 500);
    }
}

/**
 * Play LCARS sound effects (requires audio files)
 */
function playLCARSSound(type) {
    // This would require actual Star Trek sound files
    // For now, we'll use a simple beep or Web Audio API
    
    if ('AudioContext' in window || 'webkitAudioContext' in window) {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        const frequencies = {
            'button': 800,
            'success': 600,
            'error': 300,
            'warning': 500,
            'info': 700
        };
        
        const freq = frequencies[type] || 800;
        
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(freq, audioContext.currentTime);
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    }
}

/**
 * LCARS Theme utility functions
 */
window.StarTrekTheme = {
    // Show LCARS notification
    showNotification: function(message, type = 'info', duration = 4000) {
        LCARSAlert.show(message, type, duration);
    },
    
    // Speak message
    speak: function(message) {
        if (window.LCARSVoice) {
            LCARSVoice.speak(message);
        }
    },
    
    // Play sound
    playSound: function(type) {
        playLCARSSound(type);
    },
    
    // Add LCARS panel effect to element
    convertToPanel: function(element) {
        if (element && !element.classList.contains('lcars-panel')) {
            element.classList.add('lcars-panel', 'lcars-startup');
        }
    },
    
    // Activate alert mode
    activateAlert: function() {
        document.body.classList.add('lcars-alert-mode');
        setTimeout(() => {
            document.body.classList.remove('lcars-alert-mode');
        }, 5000);
    }
};

// Add alert mode styling
const alertModeStyle = document.createElement('style');
alertModeStyle.textContent = `
    .lcars-alert-mode .lcars-panel {
        animation: lcarsAlertFlash 1s ease-in-out infinite !important;
    }
    .lcars-alert-mode h1, .lcars-alert-mode h2, .lcars-alert-mode h3 {
        animation: lcarsFlicker 0.5s ease-in-out infinite !important;
    }
`;
document.head.appendChild(alertModeStyle);