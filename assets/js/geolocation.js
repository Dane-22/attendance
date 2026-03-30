/**
 * GeoLocator Module for Attendance Management System
 * Uses MapLibre GL JS for mapping and Geolocation API for position tracking
 * 
 * @version 1.0.0
 * @author JAJR Construction
 * @license Private
 */

const GeoLocator = {
    map: null,
    markers: [],
    geofenceCircles: [],
    currentPosition: null,
    
    // Configuration based on user decisions
    config: {
        defaultRadius: 200, // meters
        maxAge: 60000, // 1 minute cache
        timeout: 10000, // 10 second timeout
        enableHighAccuracy: true,
        accuracyWarningThreshold: 100, // meters - show warning if accuracy > this
        mapStyle: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
        offlineCacheKey: 'geolocation_offline_cache',
        consentKey: 'geolocation_consent_given'
    },

    /**
     * Initialize MapLibre map
     * @param {string} containerId - DOM element ID for map container
     * @param {number} centerLat - Initial latitude
     * @param {number} centerLng - Initial longitude
     * @param {number} zoom - Initial zoom level (default: 15)
     * @returns {Object} MapLibre map instance
     */
    initMap: function(containerId, centerLat, centerLng, zoom = 15) {
        if (!document.getElementById(containerId)) {
            console.error('Map container not found:', containerId);
            return null;
        }

        this.map = new maplibregl.Map({
            container: containerId,
            style: this.config.mapStyle,
            center: [centerLng, centerLat],
            zoom: zoom,
            attributionControl: true
        });

        // Add navigation controls
        this.map.addControl(new maplibregl.NavigationControl(), 'top-right');
        
        // Add geolocate control (user location button)
        this.map.addControl(new maplibregl.GeolocateControl({
            positionOptions: {
                enableHighAccuracy: true
            },
            trackUserLocation: true,
            showAccuracyCircle: true
        }), 'top-right');

        // Add scale control
        this.map.addControl(new maplibregl.ScaleControl({
            maxWidth: 80,
            unit: 'metric'
        }), 'bottom-left');

        return this.map;
    },

    /**
     * Get current position with high accuracy
     * Uses GPS with WiFi fallback for indoor locations
     * @returns {Promise<Object>} Position object with lat, lng, accuracy, timestamp
     */
    getCurrentPosition: function() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported by your browser'));
                return;
            }

            // Try high accuracy first (GPS)
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const result = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        altitude: position.coords.altitude,
                        altitudeAccuracy: position.coords.altitudeAccuracy,
                        heading: position.coords.heading,
                        speed: position.coords.speed,
                        timestamp: position.timestamp,
                        source: position.coords.accuracy < 20 ? 'GPS' : 'Network/WiFi'
                    };
                    this.currentPosition = result;
                    resolve(result);
                },
                (error) => {
                    // If high accuracy fails, try without it (WiFi/cellular fallback)
                    if (this.config.enableHighAccuracy) {
                        this.tryLowAccuracyPosition(resolve, reject);
                    } else {
                        reject(this.parseGeolocationError(error));
                    }
                },
                {
                    enableHighAccuracy: this.config.enableHighAccuracy,
                    timeout: this.config.timeout,
                    maximumAge: this.config.maxAge
                }
            );
        });
    },

    /**
     * Fallback method for low accuracy position (WiFi/Cellular)
     */
    tryLowAccuracyPosition: function(resolve, reject) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const result = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp,
                    source: 'WiFi/Cellular'
                };
                this.currentPosition = result;
                resolve(result);
            },
            (error) => {
                reject(this.parseGeolocationError(error));
            },
            {
                enableHighAccuracy: false,
                timeout: 15000,
                maximumAge: 120000 // 2 minutes cache for fallback
            }
        );
    },

    /**
     * Calculate distance between two points using Haversine formula
     * @param {number} lat1 - Latitude of point 1
     * @param {number} lng1 - Longitude of point 1
     * @param {number} lat2 - Latitude of point 2
     * @param {number} lng2 - Longitude of point 2
     * @returns {number} Distance in meters
     */
    calculateDistance: function(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Earth radius in meters
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lng2 - lng1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return Math.round(R * c); // Distance in meters
    },

    /**
     * Validate if position is within branch geofence
     * @param {number} empLat - Employee latitude
     * @param {number} empLng - Employee longitude
     * @param {number} branchLat - Branch latitude
     * @param {number} branchLng - Branch longitude
     * @param {number} radiusMeters - Geofence radius in meters
     * @returns {Object} Validation result with isValid, distance, remaining
     */
    validateGeofence: function(empLat, empLng, branchLat, branchLng, radiusMeters) {
        const distance = this.calculateDistance(empLat, empLng, branchLat, branchLng);
        const remaining = Math.max(0, radiusMeters - distance);
        const isValid = distance <= radiusMeters;

        return {
            isValid: isValid,
            distance: distance,
            radius: radiusMeters,
            remaining: remaining,
            outsideBy: isValid ? 0 : distance - radiusMeters
        };
    },

    /**
     * Add geofence circle visualization to map
     * @param {number} lat - Center latitude
     * @param {number} lng - Center longitude
     * @param {number} radiusMeters - Radius in meters
     * @param {Object} options - Styling options
     * @returns {string} Circle layer ID
     */
    addGeofenceCircle: function(lat, lng, radiusMeters, options = {}) {
        if (!this.map) {
            console.error('Map not initialized');
            return null;
        }

        const id = 'geofence-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        const steps = 64;
        const coords = [];

        // Create circle polygon
        for (let i = 0; i <= steps; i++) {
            const angle = (i / steps) * 2 * Math.PI;
            // Approximate degree offset for meters
            const latOffset = (radiusMeters / 111320) * Math.cos(angle);
            const lngOffset = (radiusMeters / (111320 * Math.cos(lat * Math.PI / 180))) * Math.sin(angle);
            coords.push([lng + lngOffset, lat + latOffset]);
        }

        // Add source
        this.map.addSource(id, {
            type: 'geojson',
            data: {
                type: 'Feature',
                geometry: {
                    type: 'Polygon',
                    coordinates: [coords]
                },
                properties: {
                    radius: radiusMeters
                }
            }
        });

        // Add fill layer
        this.map.addLayer({
            id: id + '-fill',
            type: 'fill',
            source: id,
            paint: {
                'fill-color': options.color || '#3B82F6',
                'fill-opacity': options.fillOpacity || 0.15
            }
        });

        // Add outline layer
        this.map.addLayer({
            id: id + '-outline',
            type: 'line',
            source: id,
            paint: {
                'line-color': options.color || '#3B82F6',
                'line-width': options.lineWidth || 2,
                'line-dasharray': options.dashed ? [2, 2] : [1]
            }
        });

        this.geofenceCircles.push(id);
        return id;
    },

    /**
     * Add a marker to the map
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {Object} options - Marker options
     * @returns {Object} MapLibre marker instance
     */
    addMarker: function(lat, lng, options = {}) {
        if (!this.map) {
            console.error('Map not initialized');
            return null;
        }

        const el = document.createElement('div');
        el.className = 'map-marker';
        el.style.width = options.width || '30px';
        el.style.height = options.height || '30px';
        el.style.backgroundImage = options.icon ? `url(${options.icon})` : '';
        el.style.backgroundSize = 'cover';
        el.style.backgroundColor = options.color || '#EF4444';
        el.style.borderRadius = '50%';
        el.style.border = '3px solid white';
        el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
        el.style.cursor = options.draggable ? 'move' : 'pointer';

        const marker = new maplibregl.Marker({
            element: el,
            draggable: options.draggable || false
        })
            .setLngLat([lng, lat])
            .addTo(this.map);

        if (options.popup) {
            const popup = new maplibregl.Popup({ offset: 25 })
                .setHTML(options.popup);
            marker.setPopup(popup);
        }

        if (options.draggable && options.onDragEnd) {
            marker.on('dragend', () => {
                const lngLat = marker.getLngLat();
                options.onDragEnd(lngLat.lat, lngLat.lng);
            });
        }

        this.markers.push(marker);
        return marker;
    },

    /**
     * Clear all markers from map
     */
    clearMarkers: function() {
        this.markers.forEach(marker => marker.remove());
        this.markers = [];
    },

    /**
     * Clear all geofence circles from map
     */
    clearGeofences: function() {
        if (!this.map) return;

        this.geofenceCircles.forEach(id => {
            if (this.map.getLayer(id + '-fill')) {
                this.map.removeLayer(id + '-fill');
            }
            if (this.map.getLayer(id + '-outline')) {
                this.map.removeLayer(id + '-outline');
            }
            if (this.map.getSource(id)) {
                this.map.removeSource(id);
            }
        });
        this.geofenceCircles = [];
    },

    /**
     * Parse geolocation errors for user-friendly messages
     * @param {Object} error - GeolocationPositionError object
     * @returns {Error} Parsed error with user-friendly message
     */
    parseGeolocationError: function(error) {
        const errors = {
            1: {
                code: 'PERMISSION_DENIED',
                message: 'Location access was denied. Please enable location services in your browser settings.',
                userAction: 'enable-permission'
            },
            2: {
                code: 'POSITION_UNAVAILABLE',
                message: 'Unable to determine your location. Please check your GPS signal or WiFi connection.',
                userAction: 'check-signal'
            },
            3: {
                code: 'TIMEOUT',
                message: 'Location request timed out. Please try again in an area with better signal.',
                userAction: 'retry'
            }
        };

        const errorInfo = errors[error.code] || {
            code: 'UNKNOWN_ERROR',
            message: 'An unknown error occurred while getting your location.',
            userAction: 'retry'
        };

        const parsedError = new Error(errorInfo.message);
        parsedError.code = errorInfo.code;
        parsedError.userAction = errorInfo.userAction;
        parsedError.originalError = error;
        return parsedError;
    },

    /**
     * Check if employee has given consent for location tracking
     * @returns {boolean}
     */
    hasConsent: function() {
        return localStorage.getItem(this.config.consentKey) === 'true';
    },

    /**
     * Request consent from employee
     * @returns {Promise<boolean>} True if consent given
     */
    requestConsent: function() {
        return new Promise((resolve) => {
            const message = 
                'This application uses your location to verify you are at the work site.\n\n' +
                'Your location data will be:\n' +
                '• Stored with your attendance record\n' +
                '• Used to validate you are within the branch area\n' +
                '• Kept for 90 days for audit purposes\n' +
                '• Not shared with third parties\n\n' +
                'Do you consent to sharing your location for attendance verification?';

            const consent = confirm(message);
            if (consent) {
                localStorage.setItem(this.config.consentKey, 'true');
                localStorage.setItem(this.config.consentKey + '_date', new Date().toISOString());
            }
            resolve(consent);
        });
    },

    /**
     * Cache location data for offline mode
     * @param {Object} locationData - Location data to cache
     */
    cacheLocationForOffline: function(locationData) {
        let cache = JSON.parse(localStorage.getItem(this.config.offlineCacheKey) || '[]');
        cache.push({
            ...locationData,
            cachedAt: new Date().toISOString()
        });
        localStorage.setItem(this.config.offlineCacheKey, JSON.stringify(cache));
    },

    /**
     * Get cached offline locations
     * @returns {Array} Cached location data
     */
    getOfflineCache: function() {
        return JSON.parse(localStorage.getItem(this.config.offlineCacheKey) || '[]');
    },

    /**
     * Clear offline cache
     */
    clearOfflineCache: function() {
        localStorage.removeItem(this.config.offlineCacheKey);
    },

    /**
     * Sync offline cached locations with server
     * @param {Function} syncFunction - Function to call for each cached item
     * @returns {Promise<Array>} Results of sync operations
     */
    syncOfflineLocations: async function(syncFunction) {
        const cache = this.getOfflineCache();
        if (cache.length === 0) return [];

        const results = [];
        for (const item of cache) {
            try {
                const result = await syncFunction(item);
                results.push({ success: true, data: result, item: item });
            } catch (error) {
                results.push({ success: false, error: error, item: item });
            }
        }

        // Clear cache after sync attempt
        this.clearOfflineCache();
        return results;
    },

    /**
     * Show location accuracy warning
     * @param {number} accuracy - Accuracy in meters
     * @returns {boolean} True if warning shown
     */
    showAccuracyWarning: function(accuracy) {
        if (accuracy > this.config.accuracyWarningThreshold) {
            const message = `Warning: Your location accuracy is ${Math.round(accuracy)} meters. ` +
                          `For better accuracy, please ensure:\n\n` +
                          `• GPS is enabled\n` +
                          `• You are outdoors or near a window\n` +
                          `• WiFi is enabled (helps with indoor positioning)`;
            alert(message);
            return true;
        }
        return false;
    },

    /**
     * Get hybrid enforcement decision based on employee role
     * @param {string} position - Employee position/role
     * @returns {string} 'soft' or 'hard' enforcement
     */
    getEnforcementType: function(position) {
        // High-compliance roles require hard enforcement
        const highComplianceRoles = ['Admin', 'Super Admin', 'Manager', 'Supervisor'];
        
        if (highComplianceRoles.includes(position)) {
            return 'hard';
        }
        return 'soft';
    },

    /**
     * Validate location with hybrid enforcement
     * @param {Object} employee - Employee data
     * @param {Object} position - Current GPS position
     * @param {Object} branch - Branch data with lat/long/radius
     * @returns {Object} Validation result with enforcement action
     */
    validateWithEnforcement: function(employee, position, branch) {
        const validation = this.validateGeofence(
            position.lat, position.lng,
            parseFloat(branch.lat), parseFloat(branch.long),
            branch.geofence_radius_meters || this.config.defaultRadius
        );

        const enforcement = this.getEnforcementType(employee.position);
        
        return {
            ...validation,
            enforcement: enforcement,
            action: validation.isValid ? 'allow' : (enforcement === 'hard' ? 'block' : 'warn'),
            canOverride: enforcement === 'soft' && !validation.isValid
        };
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GeoLocator;
}
