/**
 * Geolocation Mock Injector for Phase 2 Testing
 * File: test/geo_mock.js
 * 
 * This script overrides navigator.geolocation.getCurrentPosition to allow
 * manual injection of GPS coordinates for testing geofence logic without
 * physical movement.
 * 
 * Usage:
 * 1. Include this script in your HTML before the main application code
 * 2. Use GeoMock.setLocation() to set desired coordinates
 * 3. Use GeoMock.setAccuracy() to set accuracy in meters
 * 4. Call GeoMock.enable() to activate mocking
 * 
 * @author QA Automation Engineer
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Store original geolocation API
    const originalGeolocation = navigator.geolocation;
    const originalGetCurrentPosition = navigator.geolocation?.getCurrentPosition?.bind(navigator.geolocation);
    const originalWatchPosition = navigator.geolocation?.watchPosition?.bind(navigator.geolocation);

    /**
     * GeoMock Controller Object
     */
    window.GeoMock = {
        // Configuration
        _enabled: false,
        _latitude: 14.5995,
        _longitude: 120.9842,
        _accuracy: 10,
        _altitude: null,
        _altitudeAccuracy: null,
        _heading: null,
        _speed: null,
        _timestamp: Date.now(),
        
        // Delay simulation (ms)
        _delay: 500,

        /**
         * Enable mock geolocation
         */
        enable: function() {
            this._enabled = true;
            this._injectMock();
            console.log('[GeoMock] ✅ Mock geolocation enabled');
            return this;
        },

        /**
         * Disable mock geolocation (restore original)
         */
        disable: function() {
            this._enabled = false;
            this._restoreOriginal();
            console.log('[GeoMock] ❌ Mock geolocation disabled - using real GPS');
            return this;
        },

        /**
         * Set mock location coordinates
         * @param {number} lat - Latitude
         * @param {number} lng - Longitude
         * @param {number} accuracy - Accuracy in meters (default: 10)
         */
        setLocation: function(lat, lng, accuracy = 10) {
            this._latitude = lat;
            this._longitude = lng;
            this._accuracy = accuracy;
            this._timestamp = Date.now();
            console.log(`[GeoMock] 📍 Location set: ${lat}, ${lng} (±${accuracy}m)`);
            return this;
        },

        /**
         * Set mock accuracy only
         * @param {number} accuracy - Accuracy in meters
         */
        setAccuracy: function(accuracy) {
            this._accuracy = accuracy;
            console.log(`[GeoMock] 🎯 Accuracy set: ±${accuracy}m`);
            return this;
        },

        /**
         * Simulate poor accuracy (>100m)
         */
        setPoorAccuracy: function() {
            return this.setAccuracy(150);
        },

        /**
         * Simulate very poor accuracy (>500m) - should trigger accuracy block
         */
        setVeryPoorAccuracy: function() {
            return this.setAccuracy(600);
        },

        /**
         * Set GPS timestamp (for spoofing tests)
         * @param {number} timestamp - Unix timestamp in milliseconds
         */
        setTimestamp: function(timestamp) {
            this._timestamp = timestamp;
            const timeDiff = Math.floor((Date.now() - timestamp) / 1000 / 60);
            console.log(`[GeoMock] ⏰ Timestamp set: ${timeDiff} minutes ago`);
            return this;
        },

        /**
         * Simulate spoofed timestamp (2 hours in the past)
         */
        setSpoofedTimestamp: function() {
            const twoHoursAgo = Date.now() - (2 * 60 * 60 * 1000);
            return this.setTimestamp(twoHoursAgo);
        },

        /**
         * Set simulation delay
         * @param {number} delayMs - Delay in milliseconds
         */
        setDelay: function(delayMs) {
            this._delay = delayMs;
            console.log(`[GeoMock] ⏱️ Delay set: ${delayMs}ms`);
            return this;
        },

        /**
         * Get current mock configuration
         */
        getConfig: function() {
            return {
                enabled: this._enabled,
                latitude: this._latitude,
                longitude: this._longitude,
                accuracy: this._accuracy,
                timestamp: this._timestamp,
                delay: this._delay
            };
        },

        /**
         * Quick preset for Inside Geofence test
         * @param {number} branchLat - Branch latitude
         * @param {number} branchLng - Branch longitude
         */
        presetInsideGeofence: function(branchLat, branchLng) {
            // Set location 50m from branch center
            const offsetLat = 0.00045; // ~50m in latitude
            return this.setLocation(branchLat + offsetLat, branchLng, 10);
        },

        /**
         * Quick preset for Outside Geofence test
         * @param {number} branchLat - Branch latitude
         * @param {number} branchLng - Branch longitude
         * @param {number} distanceMeters - Distance outside (default: 200)
         */
        presetOutsideGeofence: function(branchLat, branchLng, distanceMeters = 200) {
            // 1 degree latitude ≈ 111km, so 1 meter ≈ 0.000009 degrees
            const offsetDegrees = distanceMeters * 0.000009;
            return this.setLocation(branchLat + offsetDegrees, branchLng + offsetDegrees, 15);
        },

        /**
         * Reset to defaults
         */
        reset: function() {
            this._enabled = false;
            this._latitude = 14.5995;
            this._longitude = 120.9842;
            this._accuracy = 10;
            this._timestamp = Date.now();
            this._delay = 500;
            this._restoreOriginal();
            console.log('[GeoMock] 🔄 Reset to defaults');
            return this;
        },

        /**
         * Inject the mock geolocation API
         * @private
         */
        _injectMock: function() {
            const self = this;

            // Create mock geolocation object
            const mockGeolocation = {
                getCurrentPosition: function(successCallback, errorCallback, options) {
                    if (!self._enabled) {
                        // Fall back to original if somehow called while disabled
                        if (originalGetCurrentPosition) {
                            originalGetCurrentPosition(successCallback, errorCallback, options);
                        }
                        return;
                    }

                    console.log('[GeoMock] 📡 Mock getCurrentPosition called', options);

                    // Simulate async delay
                    setTimeout(() => {
                        const position = self._createPosition();
                        if (successCallback) {
                            successCallback(position);
                        }
                    }, self._delay);
                },

                watchPosition: function(successCallback, errorCallback, options) {
                    if (!self._enabled && originalWatchPosition) {
                        return originalWatchPosition(successCallback, errorCallback, options);
                    }

                    console.log('[GeoMock] 👁️ Mock watchPosition started');
                    
                    // Return an interval ID that simulates position updates
                    return setInterval(() => {
                        const position = self._createPosition();
                        if (successCallback) {
                            successCallback(position);
                        }
                    }, 5000); // Update every 5 seconds
                },

                clearWatch: function(watchId) {
                    if (!self._enabled && originalWatchPosition) {
                        return originalGeolocation.clearWatch(watchId);
                    }
                    clearInterval(watchId);
                    console.log('[GeoMock] 🛑 Mock watchPosition cleared');
                }
            };

            // Override navigator.geolocation
            Object.defineProperty(navigator, 'geolocation', {
                value: mockGeolocation,
                writable: true,
                configurable: true
            });

            console.log('[GeoMock] 🔧 Mock geolocation API injected');
        },

        /**
         * Restore original geolocation API
         * @private
         */
        _restoreOriginal: function() {
            if (originalGeolocation) {
                Object.defineProperty(navigator, 'geolocation', {
                    value: originalGeolocation,
                    writable: true,
                    configurable: true
                });
            }
        },

        /**
         * Create a Position object matching the W3C spec
         * @private
         */
        _createPosition: function() {
            return {
                coords: {
                    latitude: this._latitude,
                    longitude: this._longitude,
                    altitude: this._altitude,
                    accuracy: this._accuracy,
                    altitudeAccuracy: this._altitudeAccuracy,
                    heading: this._heading,
                    speed: this._speed
                },
                timestamp: this._timestamp
            };
        }
    };

    // Auto-enable if URL parameter present: ?mock_geo=true
    if (window.location.search.includes('mock_geo=true')) {
        window.GeoMock.enable();
    }

    // Console helper
    console.log('%c[GeoMock] Initialized', 'color: #10b981; font-weight: bold;');
    console.log('Usage: GeoMock.enable(); GeoMock.setLocation(14.5995, 120.9842, 10);');

})();


/**
 * Test Scenario Presets for Phase 2 Validation
 * 
 * Use these presets to quickly test specific scenarios:
 */

// Scenario 1: Inside Geofence (Worker)
// Expected: success
function testInsideGeofence() {
    GeoMock.enable()
        .presetInsideGeofence(14.6091, 121.0223) // Manila Branch example
        .setAccuracy(10);
    console.log('Test: Inside Geofence - Worker should be allowed');
}

// Scenario 2: Outside Geofence (Worker) - Should BLOCK
// Expected: block
function testOutsideGeofenceWorker() {
    GeoMock.enable()
        .presetOutsideGeofence(14.6091, 121.0223, 200)
        .setAccuracy(15);
    console.log('Test: Outside Geofence - Worker should be BLOCKED');
}

// Scenario 3: Outside Geofence (Manager) - Should allow OVERRIDE
// Expected: allow_override
function testOutsideGeofenceManager() {
    GeoMock.enable()
        .presetOutsideGeofence(14.6091, 121.0223, 200)
        .setAccuracy(15);
    console.log('Test: Outside Geofence - Manager should see override dialog');
}

// Scenario 4: Spoofed Timestamp - Should SECURITY BLOCK
// Expected: security_block
function testSpoofedTimestamp() {
    GeoMock.enable()
        .presetInsideGeofence(14.6091, 121.0223)
        .setSpoofedTimestamp()
        .setAccuracy(10);
    console.log('Test: Spoofed Timestamp - Should trigger security block');
}

// Scenario 5: Low Accuracy (>500m) - Should ACCURACY BLOCK
// Expected: accuracy_block
function testLowAccuracy() {
    GeoMock.enable()
        .presetInsideGeofence(14.6091, 121.0223)
        .setVeryPoorAccuracy();
    console.log('Test: Low Accuracy (>500m) - Should be accuracy blocked');
}

// Make test functions globally available
window.testInsideGeofence = testInsideGeofence;
window.testOutsideGeofenceWorker = testOutsideGeofenceWorker;
window.testOutsideGeofenceManager = testOutsideGeofenceManager;
window.testSpoofedTimestamp = testSpoofedTimestamp;
window.testLowAccuracy = testLowAccuracy;
