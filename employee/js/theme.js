/**
 * Theme Switcher for JAJR Employee Dashboard
 * Handles light/dark mode toggling and persistence
 */

(function() {
    'use strict';

    // Theme configuration
    const THEME_KEY = 'jajr_theme_preference';
    const LIGHT_THEME_FILE = 'css/light-theme.css';
    
    /**
     * Initialize theme on page load
     */
    function initTheme() {
        const savedTheme = localStorage.getItem(THEME_KEY);
        
        if (savedTheme === 'light') {
            enableLightMode();
        } else {
            enableDarkMode();
        }
        
        // Add event listener to toggle button if it exists
        const toggleBtn = document.getElementById('themeToggleBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    }

    /**
     * Toggle between light and dark mode
     */
    function toggleTheme() {
        const body = document.body;
        const isLightMode = body.classList.contains('light-mode');
        
        if (isLightMode) {
            enableDarkMode();
            updateToggleButtonState(false);
        } else {
            enableLightMode();
            updateToggleButtonState(true);
        }
    }

    /**
     * Enable light mode
     */
    function enableLightMode() {
        const body = document.body;
        
        // Add light mode class
        body.classList.add('light-mode');
        
        // Set data attribute for CSS selectors
        body.setAttribute('data-theme', 'light');
        
        // Save preference
        localStorage.setItem(THEME_KEY, 'light');
        
        console.log('Light mode enabled');
    }

    /**
     * Enable dark mode (default)
     */
    function enableDarkMode() {
        const body = document.body;
        
        // Remove light mode class
        body.classList.remove('light-mode');
        
        // Remove data attribute
        body.removeAttribute('data-theme');
        
        // Save preference
        localStorage.setItem(THEME_KEY, 'dark');
        
        console.log('Dark mode enabled');
    }

    /**
     * Update toggle button appearance
     */
    function updateToggleButtonState(isLightMode) {
        const toggleBtn = document.getElementById('themeToggleBtn');
        if (!toggleBtn) return;
        
        const icon = toggleBtn.querySelector('i');
        const text = toggleBtn.querySelector('.theme-text');
        
        if (isLightMode) {
            if (icon) {
                icon.className = 'fas fa-moon';
            }
            if (text) {
                text.textContent = 'Switch to Dark Mode';
            }
            toggleBtn.classList.add('light-active');
        } else {
            if (icon) {
                icon.className = 'fas fa-sun';
            }
            if (text) {
                text.textContent = 'Switch to Light Mode';
            }
            toggleBtn.classList.remove('light-active');
        }
    }

    /**
     * Get current theme
     */
    function getCurrentTheme() {
        return localStorage.getItem(THEME_KEY) || 'dark';
    }

    /**
     * Check if light mode is active
     */
    function isLightMode() {
        return document.body.classList.contains('light-mode');
    }

    // Expose functions globally
    window.ThemeSwitcher = {
        toggle: toggleTheme,
        enableLight: enableLightMode,
        enableDark: enableDarkMode,
        getCurrentTheme: getCurrentTheme,
        isLightMode: isLightMode,
        init: initTheme
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        // DOM already loaded
        initTheme();
    }

})();
