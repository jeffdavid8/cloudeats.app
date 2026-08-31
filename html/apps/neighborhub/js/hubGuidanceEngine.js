class HubGuidanceEngine {
    /**
     * @param {Object} config 
     * @param {boolean} config.isNativeApp - Set to true if running inside an Android WebView/Cordova APK
     */
    constructor(config = {}) {
        this.isNativeApp = config.isNativeApp || false;
        this.baseScheme = "https://www.google.com/maps/dir/?api=1";
    }

    /**
     * Compiles a dynamic map URL string entirely on the client side
     */
    buildUrl(destination, waypointsArray = [], travelMode = 'driving') {
        let url = `${this.baseScheme}&origin=&destination=${encodeURIComponent(destination)}`;
        
        if (waypointsArray && waypointsArray.length > 0) {
            const joinedWaypoints = waypointsArray.map(wp => encodeURIComponent(wp)).join('|');
            url += `&waypoints=${joinedWaypoints}`;
        }
        
        url += `&travelmode=${travelMode}`;
        return url;
    }

    /**
     * Safely escapes the browser or application frame to spawn the device's native GPS map layer
     * @param {string} destination 
     * @param {Array} waypointsArray 
     */
    launchNativeNavigation(destination, waypointsArray = []) {
        const targetUrl = this.buildUrl(destination, waypointsArray);
        
        console.log(`[Guidance Engine] Escaping runtime frame to open external path: ${targetUrl}`);

        if (this.isNativeApp) {
            // Force native mobile containers (Cordova/Capacitor) to break out to phone OS
            if (typeof window.open === 'function') {
                window.open(targetUrl, '_system');
            } else {
                window.location.href = targetUrl;
            }
        } else {
            // Standard clean desktop/mobile browser tab escape switch
            const mapWindow = window.open(targetUrl, '_blank', 'noopener,noreferrer');
            if (mapWindow) mapWindow.focus();
        }
    }
}