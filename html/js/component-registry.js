/**
 * MediaBrain Component Registry - Data-Driven Architecture
 * Components are registered globally and auto-discovered via data-component attributes
 */

// Extend existing mb object
window.mb = window.mb || {};

mb.ComponentRegistry = {
    components: {},
    initialized: false,
    pendingComponents: [],
    retryCount: 0,
    maxRetries: 50, // Prevent infinite loops
    
    // Register a component type (called from separate JS files)
    register: function(name, initFunction, dependencies = []) {
        this.components[name] = {
            init: initFunction,
            dependencies: dependencies,
            instances: []
        };
        
        mb.log(`Registered component type: ${name}`);
        
        // If system is already initialized, scan for new instances
        if (this.initialized) {
            this.initializeComponentType(name);
        }
        
        return this;
    },
    
    // Check if dependencies are ready
    dependenciesReady: function(dependencies) {
        for (let dep of dependencies) {
            switch(dep) {
                case 'jquery':
                    if (typeof $ === 'undefined') return false;
                    break;
                case 'mb':
                case 'mediabrain.js':
                    if (typeof mb === 'undefined' || typeof mb.getJson !== 'function') return false;
                    break;
                case 'bibleBot':
                    if (typeof bibleBot === 'undefined' || typeof bibleBot.storage === 'undefined') return false;
                    break;
                case 'Sortable':
                    if (typeof Sortable === 'undefined') return false;
                    break;
                case 'materialize.min.js':
                case 'materialize':
                    if (typeof M === 'undefined') return false;
                    break;
                default:
                    if (typeof window[dep] === 'undefined') return false;
            }
        }
        return true;
    },
    
    // Initialize all instances of a specific component type
    initializeComponentType: function(name) {
        const component = this.components[name];
        if (!component) {
            console.warn(`Component type not registered: ${name}`);
            return false;
        }
        
        if (!this.dependenciesReady(component.dependencies)) {
            this.pendingComponents.push(name);
            mb.log(`Dependencies not ready for ${name}:`, component.dependencies);
            return false;
        }
        
        let initializedCount = 0;
        
        // Find all DOM elements for this component type
        $(`[data-component="${name}"]`).each(function() {
            const $element = $(this);
            
            // Skip already initialized instances
            if ($element.hasClass('component-initialized')) {
                return;
            }
            
            try {
                // Get component configuration from data attributes
                const componentData = $element.data();
                
                // Initialize the component instance
                component.init($element, componentData);
                
                // Mark as initialized
                $element.addClass('component-initialized');
                
                // Track the instance
                component.instances.push({
                    element: $element,
                    data: componentData
                });
                
                initializedCount++;
                mb.log(`Initialized ${name} component:`, $element);
                
            } catch (error) {
                console.error(`Failed to initialize ${name} component:`, error, $element);
            }
        });
        
        // Remove from pending queue if successful
        if (initializedCount > 0) {
            const pendingIndex = this.pendingComponents.indexOf(name);
            if (pendingIndex > -1) {
                this.pendingComponents.splice(pendingIndex, 1);
            }
        }
        
        mb.log(`Initialized ${initializedCount} instances of ${name}`);
        return initializedCount > 0;
    },
    
    // Initialize all registered component types
    initializeAll: function() {
        mb.log('Scanning DOM for components...');
        let totalInitialized = 0;
        
        for (let name in this.components) {
            if (this.initializeComponentType(name)) {
                totalInitialized++;
            }
        }
        
        mb.log(`Initialized ${totalInitialized} component types`);
        
        // Retry pending components after delay (with retry limit)
        if (this.pendingComponents.length > 0 && this.retryCount < this.maxRetries) {
            this.retryCount++;
            mb.log(`Retrying ${this.pendingComponents.length} pending components... (attempt ${this.retryCount}/${this.maxRetries})`);
            
            // Clear pending list to avoid duplicates
            this.pendingComponents = [];
            
            setTimeout(() => {
                this.initializeAll();
            }, 100);
        } else {
            if (this.retryCount >= this.maxRetries) {
                console.warn('Maximum retry attempts reached. Some components may not be initialized.');
            }
            this.initialized = true;
            mb.log('Component initialization complete');
        }
    },
    
    // Get component instances by type
    getInstances: function(name) {
        return this.components[name] ? this.components[name].instances : [];
    }
};

// Provide convenient aliases
mb.registerComponent = function(name, initFunction, dependencies) {
    return mb.ComponentRegistry.register(name, initFunction, dependencies);
};

mb.dependenciesReady = function(dependencies) {
    return mb.ComponentRegistry.dependenciesReady(dependencies);
};

// Auto-initialize when DOM is ready
$(document).ready(function() {
    mb.log('DOM ready, checking dependencies...');
    
    // Check periodically if basic dependencies are ready
    const checkDependencies = function() {
        const basicDepsReady = (typeof mb !== 'undefined' && typeof mb.getJson === 'function');
        
        if (basicDepsReady) {
            mb.log('Basic dependencies ready, initializing components');
            mb.ComponentRegistry.initializeAll();
        } else {
            mb.log('Waiting for basic dependencies...');
            setTimeout(checkDependencies, 50);
        }
    };
    
    // Small delay to let other scripts load
    setTimeout(checkDependencies, 100);
});

// Re-scan for new components when DOM changes (for dynamic content)
mb.rescanComponents = function() {
    mb.log('Rescanning for new components...');
    mb.ComponentRegistry.initializeAll();
};