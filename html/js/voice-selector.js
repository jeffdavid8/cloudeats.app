/**
 * Voice Selection UI Component
 * 
 * Provides a modern interface for TTS voice selection with:
 * - Voice preview functionality
 * - Voice type filtering (Neural2, WaveNet, Standard)
 * - Language filtering
 * - Real-time voice switching
 */

class VoiceSelector {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container element '${containerId}' not found`);
        }

        this.options = {
            showPreview: options.showPreview !== false,
            showLanguageFilter: options.showLanguageFilter !== false,
            showTypeFilter: options.showTypeFilter !== false,
            defaultLanguage: options.defaultLanguage || 'en-US',
            onVoiceChange: options.onVoiceChange || (() => {}),
            ...options
        };

        this.ttsClient = window.modernTTSClient;
        this.selectedVoice = null;
        this.voices = [];
        this.filteredVoices = [];

        this.init();
    }

    async init() {
        try {
            // Ensure TTS client is available
            if (!this.ttsClient) {
                this.ttsClient = initModernTTS();
                await new Promise(resolve => {
                    this.ttsClient.on('ready', resolve);
                });
            }

            this.voices = this.ttsClient.getVoices();
            this.createUI();
            this.setupEventListeners();
            this.filterVoices();

        } catch (error) {
            console.error('Voice selector initialization failed:', error);
            this.showError('Voice selection unavailable');
        }
    }

    createUI() {
        this.container.innerHTML = `
            <div class="voice-selector">
                <div class="voice-selector-header">
                    <h4><i class="fas fa-microphone"></i> Voice Selection</h4>
                </div>
                
                ${this.options.showLanguageFilter ? `
                <div class="voice-filter">
                    <label for="language-filter">Language:</label>
                    <select id="language-filter" class="voice-filter-select">
                        <option value="">All Languages</option>
                        ${this.getUniqueLanguages().map(lang => 
                            `<option value="${lang}" ${lang === this.options.defaultLanguage ? 'selected' : ''}>${lang}</option>`
                        ).join('')}
                    </select>
                </div>
                ` : ''}

                ${this.options.showTypeFilter ? `
                <div class="voice-filter">
                    <label for="type-filter">Voice Type:</label>
                    <select id="type-filter" class="voice-filter-select">
                        <option value="">All Types</option>
                        <option value="Neural2">Neural2 (Highest Quality)</option>
                        <option value="WaveNet">WaveNet (High Quality)</option>
                        <option value="Standard">Standard</option>
                    </select>
                </div>
                ` : ''}

                <div class="voice-list" id="voice-list">
                    <!-- Voice options will be populated here -->
                </div>

                ${this.options.showPreview ? `
                <div class="voice-preview">
                    <div class="preview-text-container">
                        <label for="preview-text">Preview Text:</label>
                        <input type="text" id="preview-text" value="Hello! This is a preview of my voice." 
                               placeholder="Enter text to preview...">
                    </div>
                    <button id="preview-btn" class="btn btn-primary" disabled>
                        <i class="fas fa-play"></i> Preview Voice
                    </button>
                </div>
                ` : ''}

                <div class="voice-selector-status" id="status-message"></div>
            </div>
        `;

        this.addStyles();
    }

    addStyles() {
        if (document.getElementById('voice-selector-styles')) return;

        const styles = `
            <style id="voice-selector-styles">
                .voice-selector {
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    padding: 1rem;
                    margin: 1rem 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .voice-selector-header {
                    margin-bottom: 1rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid #dee2e6;
                }

                .voice-selector-header h4 {
                    margin: 0;
                    color: #495057;
                    font-size: 1.1rem;
                }

                .voice-selector-header i {
                    color: #007bff;
                    margin-right: 0.5rem;
                }

                .voice-filter {
                    margin-bottom: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }

                .voice-filter label {
                    font-weight: 500;
                    color: #495057;
                    min-width: 80px;
                }

                .voice-filter-select {
                    padding: 0.375rem 0.75rem;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    background-color: white;
                    flex: 1;
                }

                .voice-list {
                    max-height: 300px;
                    overflow-y: auto;
                    margin-bottom: 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    background: white;
                }

                .voice-option {
                    padding: 0.75rem;
                    border-bottom: 1px solid #f8f9fa;
                    cursor: pointer;
                    transition: background-color 0.2s;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .voice-option:hover {
                    background-color: #f8f9fa;
                }

                .voice-option.selected {
                    background-color: #e3f2fd;
                    border-color: #007bff;
                }

                .voice-info {
                    flex: 1;
                }

                .voice-name {
                    font-weight: 500;
                    color: #212529;
                }

                .voice-details {
                    font-size: 0.875rem;
                    color: #6c757d;
                    margin-top: 0.25rem;
                }

                .voice-type-badge {
                    padding: 0.25rem 0.5rem;
                    border-radius: 12px;
                    font-size: 0.75rem;
                    font-weight: 500;
                    margin-left: 0.5rem;
                }

                .voice-type-Neural2 { background: #d4edda; color: #155724; }
                .voice-type-WaveNet { background: #cce5f4; color: #004085; }
                .voice-type-Standard { background: #e2e3e5; color: #383d41; }

                .voice-preview {
                    background: white;
                    padding: 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    margin-bottom: 1rem;
                }

                .preview-text-container {
                    margin-bottom: 0.75rem;
                }

                .preview-text-container label {
                    display: block;
                    margin-bottom: 0.25rem;
                    font-weight: 500;
                    color: #495057;
                }

                #preview-text {
                    width: 100%;
                    padding: 0.5rem;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    box-sizing: border-box;
                }

                #preview-btn {
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 0.5rem 1rem;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }

                #preview-btn:hover:not(:disabled) {
                    background: #0056b3;
                }

                #preview-btn:disabled {
                    background: #6c757d;
                    cursor: not-allowed;
                }

                #preview-btn i {
                    margin-right: 0.5rem;
                }

                .voice-selector-status {
                    font-size: 0.875rem;
                    padding: 0.5rem;
                    border-radius: 4px;
                    text-align: center;
                }

                .status-error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }

                .status-success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }

                .status-info {
                    background: #cce5f4;
                    color: #004085;
                    border: 1px solid #b6d7ff;
                }

                /* Mobile responsive */
                @media (max-width: 768px) {
                    .voice-filter {
                        flex-direction: column;
                        align-items: stretch;
                    }

                    .voice-filter label {
                        min-width: auto;
                    }

                    .voice-option {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 0.5rem;
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    setupEventListeners() {
        // Language filter
        const languageFilter = document.getElementById('language-filter');
        if (languageFilter) {
            languageFilter.addEventListener('change', () => this.filterVoices());
        }

        // Type filter
        const typeFilter = document.getElementById('type-filter');
        if (typeFilter) {
            typeFilter.addEventListener('change', () => this.filterVoices());
        }

        // Preview button
        const previewBtn = document.getElementById('preview-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', () => this.previewSelectedVoice());
        }

        // Preview text changes
        const previewText = document.getElementById('preview-text');
        if (previewText) {
            previewText.addEventListener('input', () => {
                const hasText = previewText.value.trim().length > 0;
                previewBtn.disabled = !hasText || !this.selectedVoice;
            });
        }
    }

    filterVoices() {
        const languageFilter = document.getElementById('language-filter');
        const typeFilter = document.getElementById('type-filter');
        
        const selectedLanguage = languageFilter?.value || '';
        const selectedType = typeFilter?.value || '';

        this.filteredVoices = this.voices.filter(voice => {
            const matchesLanguage = !selectedLanguage || 
                voice.language_codes.some(code => code.includes(selectedLanguage));
            
            const matchesType = !selectedType || voice.type === selectedType;
            
            return matchesLanguage && matchesType;
        });

        this.renderVoiceList();
    }

    renderVoiceList() {
        const voiceList = document.getElementById('voice-list');
        
        if (this.filteredVoices.length === 0) {
            voiceList.innerHTML = `
                <div style="padding: 2rem; text-align: center; color: #6c757d;">
                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <div>No voices found matching the current filters.</div>
                </div>
            `;
            return;
        }

        voiceList.innerHTML = this.filteredVoices.map(voice => {
            const primaryLanguage = voice.language_codes[0] || 'Unknown';
            const genderText = this.formatGender(voice.ssml_gender);
            
            return `
                <div class="voice-option" data-voice="${voice.name}">
                    <div class="voice-info">
                        <div class="voice-name">${this.formatVoiceName(voice.name)}</div>
                        <div class="voice-details">
                            ${primaryLanguage} • ${genderText}
                            ${voice.natural_sample_rate ? ` • ${voice.natural_sample_rate}Hz` : ''}
                        </div>
                    </div>
                    <div class="voice-type-badge voice-type-${voice.type}">
                        ${voice.type}
                    </div>
                </div>
            `;
        }).join('');

        // Add click handlers
        voiceList.querySelectorAll('.voice-option').forEach(option => {
            option.addEventListener('click', () => {
                const voiceName = option.dataset.voice;
                this.selectVoice(voiceName);
            });
        });
    }

    selectVoice(voiceName) {
        // Update UI selection
        document.querySelectorAll('.voice-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        const selectedOption = document.querySelector(`[data-voice="${voiceName}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
        }

        // Store selection
        this.selectedVoice = this.voices.find(voice => voice.name === voiceName);
        
        // Enable preview button if text is available
        const previewBtn = document.getElementById('preview-btn');
        const previewText = document.getElementById('preview-text');
        if (previewBtn && previewText) {
            previewBtn.disabled = !this.selectedVoice || previewText.value.trim().length === 0;
        }

        // Update TTS client default voice
        if (this.ttsClient && this.selectedVoice) {
            this.ttsClient.setVoice(this.selectedVoice.name);
        }

        // Notify callback
        this.options.onVoiceChange(this.selectedVoice);
        
        this.showStatus(`Selected: ${this.formatVoiceName(voiceName)}`, 'success');
    }

    async previewSelectedVoice() {
        if (!this.selectedVoice) {
            this.showStatus('Please select a voice first', 'error');
            return;
        }

        const previewText = document.getElementById('preview-text');
        const sampleText = previewText?.value.trim() || 'Hello! This is a preview of my voice.';
        
        const previewBtn = document.getElementById('preview-btn');
        if (previewBtn) {
            previewBtn.disabled = true;
            previewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Playing...';
        }

        try {
            this.showStatus('Generating preview...', 'info');
            await this.ttsClient.previewVoice(this.selectedVoice.name, sampleText);
            this.showStatus('Preview played successfully', 'success');
            
        } catch (error) {
            console.error('Voice preview error:', error);
            this.showStatus('Preview failed: ' + error.message, 'error');
        } finally {
            if (previewBtn) {
                previewBtn.disabled = false;
                previewBtn.innerHTML = '<i class="fas fa-play"></i> Preview Voice';
            }
        }
    }

    getUniqueLanguages() {
        const languages = new Set();
        this.voices.forEach(voice => {
            voice.language_codes.forEach(code => {
                languages.add(code);
            });
        });
        return Array.from(languages).sort();
    }

    formatVoiceName(name) {
        // Convert "en-US-Neural2-A" to "US English Neural2 (A)"
        const parts = name.split('-');
        if (parts.length >= 4) {
            const language = parts[0];
            const region = parts[1];
            const type = parts[2];
            const variant = parts[3];
            return `${region} ${language.toUpperCase()} ${type} (${variant})`;
        }
        return name;
    }

    formatGender(gender) {
        switch (gender) {
            case 1: return 'Male';
            case 2: return 'Female';
            case 0:
            default: return 'Neutral';
        }
    }

    showStatus(message, type = 'info') {
        const statusElement = document.getElementById('status-message');
        if (!statusElement) return;

        statusElement.textContent = message;
        statusElement.className = `voice-selector-status status-${type}`;
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                statusElement.textContent = '';
                statusElement.className = 'voice-selector-status';
            }, 3000);
        }
    }

    showError(message) {
        this.container.innerHTML = `
            <div class="voice-selector">
                <div class="voice-selector-status status-error">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            </div>
        `;
        this.addStyles();
    }

    // Public API methods
    getSelectedVoice() {
        return this.selectedVoice;
    }

    setSelectedVoice(voiceName) {
        this.selectVoice(voiceName);
    }

    refresh() {
        if (this.ttsClient) {
            this.voices = this.ttsClient.getVoices();
            this.filterVoices();
        }
    }
}

// Usage example and integration helper
function createVoiceSelector(containerId, options = {}) {
    return new VoiceSelector(containerId, options);
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { VoiceSelector, createVoiceSelector };
}

console.log('Voice Selector component loaded successfully');