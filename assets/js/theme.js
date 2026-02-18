/**
 * Global Theme Manager for JAJR Attendance System
 * Handles light/dark mode switching and persistence across all pages
 */

(function() {
    'use strict';

    // Theme configuration
    const THEME_KEY = 'jajr_theme_preference';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

    /**
     * Initialize theme on page load
     */
    function initTheme() {
        const savedTheme = localStorage.getItem(THEME_KEY);
        
        if (savedTheme === THEME_LIGHT) {
            document.body.classList.add('light-mode');
        } else {
            // Default to dark mode
            document.body.classList.remove('light-mode');
        }

        // Update any theme toggle buttons on the page
        updateThemeToggles();
    }

    /**
     * Toggle between light and dark mode
     */
    function toggleTheme() {
        const isLightMode = document.body.classList.contains('light-mode');
        
        if (isLightMode) {
            setTheme(THEME_DARK);
        } else {
            setTheme(THEME_LIGHT);
        }
    }

    /**
     * Set specific theme
     * @param {string} theme - 'light' or 'dark'
     */
    function setTheme(theme) {
        if (theme === THEME_LIGHT) {
            document.body.classList.add('light-mode');
            localStorage.setItem(THEME_KEY, THEME_LIGHT);
        } else {
            document.body.classList.remove('light-mode');
            localStorage.setItem(THEME_KEY, THEME_DARK);
        }
        
        updateThemeToggles();
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('themechange', { 
            detail: { theme: theme } 
        }));
    }

    /**
     * Get current theme
     * @returns {string} 'light' or 'dark'
     */
    function getTheme() {
        return document.body.classList.contains('light-mode') ? THEME_LIGHT : THEME_DARK;
    }

    /**
     * Update all theme toggle buttons on the page
     */
    function updateThemeToggles() {
        const isLightMode = document.body.classList.contains('light-mode');
        const toggles = document.querySelectorAll('[data-theme-toggle]');
        
        toggles.forEach(toggle => {
            const icon = toggle.querySelector('i');
            const text = toggle.querySelector('.theme-text');
            
            if (isLightMode) {
                if (icon) {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
                if (text) text.textContent = 'Switch to Dark Mode';
            } else {
                if (icon) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
                if (text) text.textContent = 'Switch to Light Mode';
            }
        });
    }

    /**
     * Listen for theme toggle clicks
     */
    function setupEventListeners() {
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('[data-theme-toggle]');
            if (toggle) {
                e.preventDefault();
                toggleTheme();
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            setupEventListeners();
        });
    } else {
        initTheme();
        setupEventListeners();
    }

    // Expose API globally
    window.JAJRTheme = {
        toggle: toggleTheme,
        set: setTheme,
        get: getTheme,
        init: initTheme
    };

})();
