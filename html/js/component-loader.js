/**
 * MediaBrain Component Auto-Loader
 * Dynamically loads component JavaScript files based on DOM discovery
 */

window.mb = window.mb || {};

mb.ComponentLoader = {
    loadedComponents: [],
    componentBasePath: 'js/components/',

    // Scan DOM for components and load their JS files
    discoverAndLoadComponents: function() {
        mb.log('Discovering components in DOM...');
        
        const componentNames = new Set();
        
        // Find all data-component attributes
        $('[data-component]').each(function() {
            const componentName = $(this).data('component');
            if (componentName && !componentNames.has(componentName)) {
                componentNames.add(componentName);
            }
        });
        
        // Load component files
        componentNames.forEach(componentName => {
            this.loadComponent(componentName);
        });
        
        mb.log(`Discovered ${componentNames.size} unique components:`, Array.from(componentNames));
    },
    
    // Load a specific component file
    loadComponent: function(componentName) {
        if (this.loadedComponents.includes(componentName)) {
            mb.log(`Component ${componentName} already loaded`);
            return;
        }
        
        const scriptUrl = this.componentBasePath + componentName + '.js';
        
        mb.log(`Loading component: ${scriptUrl}`);
        
        const script = document.createElement('script');
        script.src = scriptUrl;
        script.onload = () => {
            this.loadedComponents.push(componentName);
            mb.log(`Component ${componentName} loaded successfully`);
            
            // Try to initialize this component type
            if (mb.ComponentRegistry) {
                setTimeout(() => {
                    mb.ComponentRegistry.initializeComponentType(componentName);
                }, 50);
            }
        };
        script.onerror = () => {
            console.warn(`Failed to load component: ${scriptUrl}`);
        };
        
        document.head.appendChild(script);
    },
    
    // Manually register component files (for components always needed)
    preloadComponents: function(componentNames) {
        componentNames.forEach(name => this.loadComponent(name));
    }
};

// Auto-discover and load components when DOM is ready
$(document).ready(function() {
    // Wait for component registry to be available
    const initLoader = function() {
        if (typeof mb.ComponentRegistry !== 'undefined') {
            // Preload critical components first (for logout functionality)
            mb.ComponentLoader.preloadComponents([
                'header-right',
                'admin-auth-sidenav', 
                'dashboard'
            ]);
            
            // Then discover and load other components
            mb.ComponentLoader.discoverAndLoadComponents();
        } else {
            setTimeout(initLoader, 50);
        }
    };
    
    setTimeout(initLoader, 150); // After component registry loads
});