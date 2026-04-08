# Logs Expandable Details Plan

Add expandable details feature to activity logs table where clicking three dots shows full details.

## Changes Required

### 1. logs.php - Modify Details column
Add a three-dots button and expandable row content after each log row:
- Replace the simple truncated details div with a flex container
- Add three-dots button (⋮) that toggles expanded view
- Add expandable details section that shows full details when expanded

### 2. logs.php - Add CSS styles
Add CSS for:
- .expand-btn (three dots button styling)
- .log-details-expanded (expanded content styling)
- .log-details-expanded.show (visible state with animation)

### 3. logs.php - Add JavaScript
Add toggle function to handle:
- Click on three-dots button to toggle expanded view
- Click outside to close expanded view
- Smooth animation transition

### 4. logs.php - Update table structure
Modify the table row to include a second row that contains the expanded details, using colspan to span all columns.

## Implementation Details
- Use data-id attribute to link button with expanded content
- Use Tailwind classes for transitions (transition-all, duration-300, etc.)
- Keep mobile view working - ensure expanded content works on mobile card layout
- Only show three-dots button when details content exceeds the visible area
