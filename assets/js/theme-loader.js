/**
 * Theme Loader - Prevents FOUC (Flash of Unstyled Content)
 * Run this immediately in <head> before any CSS loads
 */
(function() {
    'use strict';
    
    const THEME_KEY = 'jajr_theme_preference';
    
    // Only apply if user has explicitly chosen light mode
    const savedTheme = localStorage.getItem(THEME_KEY);
    
    // Default is dark - only add class for light mode
    if (savedTheme === 'light') {
        // Add class immediately to prevent any flash
        document.documentElement.classList.add('light-mode');
        
        // Also add to body as soon as it exists
        if (document.body) {
            document.body.classList.add('light-mode');
        } else {
            // Body doesn't exist yet, wait for it
            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.add('light-mode');
            });
        }
    }
    // If no preference or 'dark', do nothing - keep default dark theme
})();
