/**
 * BackgroundSelector ES6 Module
 * Modern approach using classes and modules
 */

export class BackgroundSelector {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            bucketDir: options.bucketDir || '',
            currentIndex: options.currentIndex || 0,
            imageNames: options.imageNames || [],
            ...options
        };
        
        this.bucketUrls = [];
        this.bucketThumbUrls = [];
        
        this.init();
    }
    
    static async create(selector, options = {}) {
        // Wait for dependencies
        await this.waitForDependencies(['mb', 'bibleBot', 'jquery']);
        
        const element = document.querySelector(selector);
        if (!element) {
            throw new Error(`Element not found: ${selector}`);
        }
        
        return new BackgroundSelector(element, options);
    }
    
    static async waitForDependencies(deps) {
        return new Promise((resolve) => {
            const checkDeps = () => {
                const ready = deps.every(dep => {
                    switch (dep) {
                        case 'mb': return typeof mb !== 'undefined' && typeof mb.getJson === 'function';
                        case 'bibleBot': return typeof bibleBot !== 'undefined';
                        case 'jquery': return typeof $ !== 'undefined';
                        default: return typeof window[dep] !== 'undefined';
                    }
                });
                
                if (ready) {
                    resolve();
                } else {
                    setTimeout(checkDeps, 50);
                }
            };
            
            checkDeps();
        });
    }
    
    async init() {
        try {
            // Load image data if not provided
            if (!this.options.imageNames.length) {
                this.options.imageNames = await this.loadImageNames();
            }
            
            this.buildImageUrls();
            this.bindEvents();
            this.updateUI();
            
            console.log('BackgroundSelector initialized');
        } catch (error) {
            console.error('Failed to initialize BackgroundSelector:', error);
        }
    }
    
    async loadImageNames() {
        try {
            return mb.getJson('apps/bibleBot/json/share_images.json');
        } catch (error) {
            console.error('Failed to load image names:', error);
            return [];
        }
    }
    
    buildImageUrls() {
        const { bucketDir, imageNames } = this.options;
        
        imageNames.forEach((imageName, i) => {
            this.bucketUrls[i] = `${bucketDir}/${imageName}.jpg`;
            this.bucketThumbUrls[i] = `${bucketDir}/thumbs/${imageName}_thumb.jpg`;
        });
    }
    
    bindEvents() {
        const $element = $(this.element);
        
        // Thumb clicks
        $element.find('.thumb-btn').on('click', (e) => {
            const selectedIndex = parseInt($(e.currentTarget).data('index'));
            this.selectImage(selectedIndex);
        });
        
        // Transparent button
        $element.find('.transparentBtn').on('click', (e) => {
            e.preventDefault();
            this.setTransparent();
        });
        
        // Random button
        $element.find('.randomBtn').on('click', (e) => {
            e.preventDefault();
            this.selectRandom();
        });
        
        // Current background button
        $element.find('.selectCurrentBackgroundBtn').on('click', (e) => {
            e.preventDefault();
            const index = parseInt($(e.currentTarget).data('index'));
            this.selectImage(index);
        });
    }
    
    selectImage(index) {
        if (index < 0 || index >= this.options.imageNames.length) {
            console.warn('Invalid image index:', index);
            return;
        }
        
        bibleBot.backgroundImageIndex = index;
        
        // Update UI
        $(this.element).find('.active').removeClass('active');
        $(this.element).find(`[data-index="${index}"]`).addClass('active');
        
        // Apply background
        $('body').addClass('image_bg');
        $('body').css('background-image', `url(${this.bucketUrls[index]})`);
        
        // Save preference
        this.saveBackgroundPreference(this.options.imageNames[index]);
        
        // Trigger custom event
        $(this.element).trigger('background-selected', {
            index: index,
            imageName: this.options.imageNames[index],
            url: this.bucketUrls[index]
        });
    }
    
    setTransparent() {
        bibleBot.backgroundImageIndex = -1;
        
        $(this.element).find('.active').removeClass('active');
        $(this.element).find('.transparentBtn').addClass('active');
        
        $('body').removeClass('image_bg');
        $('body').css('background-image', 'none');
        
        this.saveBackgroundPreference('transparent');
        
        $(this.element).trigger('background-transparent');
    }
    
    selectRandom() {
        if (!this.options.imageNames.length) {
            console.warn('No images available for random selection');
            return;
        }
        
        const randomIndex = Math.floor(Math.random() * this.options.imageNames.length);
        this.selectImage(randomIndex);
        
        $(this.element).find('.randomBtn').addClass('active');
        this.saveBackgroundPreference('random');
    }
    
    saveBackgroundPreference(value) {
        // Implement your preference saving logic
        if (typeof set_backgound_image === 'function') {
            set_backgound_image(value);
        }
    }
    
    updateUI() {
        const currentIndex = this.options.currentIndex;
        if (currentIndex >= 0) {
            $(this.element).find(`[data-index="${currentIndex}"]`).addClass('active');
        }
    }
    
    // Public API methods
    getCurrentIndex() {
        return bibleBot.backgroundImageIndex || this.options.currentIndex;
    }
    
    setImage(index) {
        this.selectImage(index);
    }
    
    getImageUrl(index) {
        return this.bucketUrls[index] || null;
    }
    
    destroy() {
        $(this.element).off();
        $(this.element).removeData('background-selector');
    }
}

// Global initialization helper for PHP views
window.initBackgroundSelector = async function(selector, options = {}) {
    try {
        const instance = await BackgroundSelector.create(selector, options);
        
        // Store reference for potential cleanup
        $(selector).data('background-selector', instance);
        
        return instance;
    } catch (error) {
        console.error('Failed to initialize background selector:', error);
        throw error;
    }
};