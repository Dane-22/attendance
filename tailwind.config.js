/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // Root level files
    '**/index.php',
    '**/index.html', 
    '**/login.php',
    '**/signup.php',
    // Employee folder
    '**/employee/**/*.php',
    // Include patterns that match all PHP files recursively
    './**/*.php',
    './*.php',
    '../**/*.php',
    // Exclude node_modules and backups
    '!**/node_modules/**',
    '!**/backups/**'
  ],
  theme: {
    extend: {
      colors: {
        'jajr-gold': '#FFD700',
        'jajr-dark': '#0b0b0b',
        'jajr-card': '#1a1a1a',
      },
      fontFamily: {
        'sans': ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
