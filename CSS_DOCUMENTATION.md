# CSS Files Documentation - JAJR Attendance System
## For Light Mode Theme Development

---

## Overview

This document catalogs all CSS files in the JAJR Attendance System to help developers create and maintain a consistent light mode theme across the entire application.

**Current Theme Architecture:**
- **Default Theme:** Dark (Gold/Black/Charcoal)
- **Light Mode Trigger:** `body.light-mode` class
- **Primary Brand Color:** Gold (#FFD700, #D4AF37)
- **Status Colors:** Green (#39ff14/#10b981), Red (#e06a6a/#ef4444), Blue (#3b82f6), Orange (#f59e0b)

---

## Core Theme Files

### 1. `assets/css/theme-variables.css` (308 lines)
**Purpose:** Central CSS custom properties (variables) for both dark and light themes

**Key Sections:**
- `:root` - Default dark theme variables
- `body.light-mode` - Light theme overrides
- Utility classes for both themes

**Variables Documented:**
```css
/* Background Colors */
--bg-page: #0b0b0b (dark) / #f8f9fa (light)
--bg-card: #161616 (dark) / #ffffff (light)
--bg-sidebar: gradient (dark) / #ffffff (light)
--bg-input: transparent (dark) / #ffffff (light)

/* Text Colors */
--text-primary: rgba(255,255,255,0.92) (dark) / #202124 (light)
--text-secondary: rgba(255,255,255,0.75) (dark) / #5f6368 (light)

/* Accent Colors */
--gold-1: #FFD66B
--gold-2: #D4AF37
--gold-3: #B8860B

/* Status Colors */
--status-success: #39ff14 (dark) / #059669 (light)
--status-danger: #e06a6a (dark) / #dc2626 (light)
--status-warning: #f59e0b (dark) / #d97706 (light)
--status-info: #3b82f6 (dark) / #2563eb (light)

/* Borders & Shadows */
--border-subtle: rgba(255,255,255,0.03) (dark) / #e8eaed (light)
--shadow-card: 0 6px 20px rgba(0,0,0,0.6) (dark) / 0 1px 3px rgba(0,0,0,0.08) (light)
```

**Action Items for Light Mode:**
- ✅ Already has light mode variables defined
- ⚠️ Check that all components use these variables instead of hardcoded colors

---

### 2. `employee/css/light-theme.css` (2,052 lines)
**Purpose:** Complete light mode override stylesheet (PURE BLACK & WHITE theme)

**Architecture:**
- Uses `body.light-mode` selector prefix for all rules
- Uses `!important` to override existing dark styles
- Black (#000000) and White (#ffffff) only - monochrome approach

**Key Pattern:**
```css
/* White backgrounds - Black text */
body.light-mode .card,
body.light-mode .summary-card,
body.light-mode .stat-card {
  background: #ffffff !important;
  color: #000000 !important;
  border-color: #000000 !important;
}

/* Black backgrounds - White text */
body.light-mode .sidebar,
body.light-mode .section-header,
body.light-mode .btn-primary {
  background: #000000 !important;
  color: #ffffff !important;
}
```

**Components Covered:**
- Base layout (body, app-shell, main-content)
- Sidebar (white bg, black text, black active state)
- Headers & Typography
- Cards (all variants)
- Buttons (primary black, secondary white outline)
- Form inputs
- Tables
- Status badges
- Modals
- Employee selection specific styles
- Pagination
- Loading states
- Undo snackbar

**Action Items:**
- ⚠️ This file is very large and may need refactoring
- ⚠️ Check consistency with `theme-variables.css`
- ✅ Already provides comprehensive light mode coverage

---

### 3. `employee/css/light-theme-notification.css` (55 lines)
**Purpose:** Light mode styles for notification components

**Components:**
- Notification badge
- Notification header
- Tab buttons
- Request cards

**Pattern:**
```css
body.light-mode .notification-header {
  background: #ffffff !important;
  border: 1px solid #000000 !important;
}
```

---

## Global/Shared Styles

### 4. `assets/css/style.css` (377 lines)
**Purpose:** Main dark theme styles for attendance management

**Key Components:**
- `.app-shell` - Flex layout container
- `.sidebar` - Off-canvas mobile, fixed desktop
- `.summary-card` - Dashboard stat cards with gold accents
- `.employee-card` - Employee list cards
- `.btn-present`, `.btn-transfer`, `.btn-absent` - Action buttons
- `.badge.present`, `.badge.absent` - Status badges
- Modal styles
- Table responsive styles
- Mobile hamburger menu

**Color Scheme:**
- Background: `#0b0b0b` (deep charcoal)
- Card BG: `#161616`
- Gold: `#FFD66B` to `#D4AF37` gradient
- Present Green: `#39ff14`
- Absent Red: `#e06a6a`

**Action Items for Light Mode:**
- ⚠️ Hardcoded dark colors need light mode overrides
- ⚠️ Check responsive breakpoints consistency

---

### 5. `assets/css/ai_chat.css` (168 lines)
**Purpose:** Glassmorphism AI chat widget

**Key Components:**
- `.ai-chat-widget` - Main container (glass effect)
- `#open-chat` - Floating action button
- `.chat-header` - Draggable header
- `.message` - Chat bubbles (user/ai variants)
- `.typing-indicator` - Animation

**Colors:**
- Background: `rgba(0, 0, 0, 0.8)` (glass)
- Border: `rgba(255, 215, 0, 0.3)` (gold)
- User message: `rgba(255, 215, 0, 0.2)`
- AI message: `rgba(255, 255, 255, 0.1)`

**Action Items for Light Mode:**
- ⚠️ Glass effect needs light mode variant
- Suggested: `rgba(255, 255, 255, 0.9)` background, dark text

---

### 6. `assets/styles.css` (42 lines)
**Purpose:** Utility styles and animations

**Classes:**
- `.hero-gradient` - Orange to black gradient
- `.blueprint-bg` - Grid background pattern
- `.glow-orange` - Hover glow effect
- `.reveal` - Scroll reveal animation
- `.draw` - SVG drawing animation
- `.gear` - Spinning animation

**Action Items for Light Mode:**
- ⚠️ Blueprint grid uses white lines on dark - needs inverse
- ⚠️ Orange gradient may need adjustment for light backgrounds

---

### 7. `assets/style_auth.css` (70 lines)
**Purpose:** Login and signup page styles

**Key Components:**
- `.auth-bg` - Gradient background (orange to black)
- `.auth-card` - Glassmorphism card
- `.input-field` - Form inputs
- Animations: `.gear-anim`, `.grid-pulse`, `.btn-glow`, `.fade-in`

**Action Items for Light Mode:**
- ⚠️ Orange-to-black gradient needs light variant
- ⚠️ Glass card needs light mode transparency adjustment

---

### 8. `assets/style_employee.css` (91 lines)
**Purpose:** Employee interface (dashboard) styles - LIGHT THEME ALREADY!

**Note:** This file appears to already use a light theme (white backgrounds)
- Background: `#f7fafc`
- Cards: `#ffffff`
- Sidebar: `#000000`

**Action Items:**
- ✅ Already light theme - may not need changes
- ⚠️ Verify consistency with main light mode theme

---

## Employee Module Styles

### 9. `employee/css/dashboard.css` (812 lines)
**Purpose:** Admin dashboard dark theme

**Key Components:**
- `.top-navbar` - Header with user info
- `.summary-grid`, `.summary-card` - Stat cards
- `.quick-actions-section` - Action buttons grid
- `.monitoring-section` - Data tables
- `.custom-table` - Styled data tables
- `.activity-list` - Activity feed

**Color Scheme:**
- Background: `#0b0b0b`
- Card: `#161616`, `#1e1e1e`
- Gold accents on borders and icons

**Mobile Styles:**
- Table to card conversion at `max-width: 767px`
- Responsive grid adjustments

**Action Items for Light Mode:**
- ⚠️ Extensive dark styling needs light overrides
- ⚠️ Mobile table conversion styles need light variants

---

### 10. `employee/css/employees.css` (909 lines)
**Purpose:** Employee management page (CRUD operations)

**Key Components:**
- `.edit-form-modal` - Employee edit form
- `.search-container`, `.search-input` - Search styling
- `.employees-list-view` - List layout
- `.employee-row` - Individual employee rows
- `.qr-modal` - QR code display
- `.pagination-container` - Pagination controls

**Colors:**
- Dark backgrounds: `#1a1a1a`, `#161616`
- Gold accents: `#FFD700`, `#FFA500`
- Form borders: `rgba(255, 215, 0, 0.3)`

**Action Items for Light Mode:**
- ⚠️ Edit form modal needs light variant
- ⚠️ QR modal needs light background
- ⚠️ Pagination needs light styling

---

### 11. `employee/css/notification.css` (611 lines)
**Purpose:** Overtime request approval dashboard

**Key Components:**
- `.notification-header` - Page header with badge
- `.notification-tabs` - Tab navigation
- `.requests-grid` - 5-column card grid
- `.request-card` - Individual request cards
- `.status-badge` - Pending/approved/rejected badges
- `.modal-backdrop`, `.modal-panel` - Rejection modal
- `.toast` - Success/error notifications

**Status Colors:**
- Pending: `#FFC107` (yellow)
- Approved: `#4CAF50` (green)
- Rejected: `#f44336` (red)

**Grid Layout:**
- Desktop: `grid-template-columns: repeat(5, 1fr)`
- Mobile: `grid-template-columns: 1fr`

**Action Items for Light Mode:**
- ⚠️ Partially covered by `light-theme-notification.css`
- ⚠️ Check complete coverage

---

### 12. `employee/css/my_notifications.css` (413 lines)
**Purpose:** Employee personal notification center

**Key Components:**
- `.notification-header` with `.btn-mark-all`
- `.notification-tabs`
- `.notifications-grid` - 5-column grid
- `.notification-card` with unread indicator
- `.notification-icon` - Status icons
- Toast notifications

**Card States:**
- `.approved` - Green left border
- `.rejected` - Red left border
- `.unread` - Gold background tint

**Action Items for Light Mode:**
- ⚠️ Needs light mode overrides
- ⚠️ Unread indicator gold dot may need adjustment

---

### 13. `employee/css/billing.css` (890 lines)
**Purpose:** Payroll and billing reports

**Key Components:**
- `.billing-container`, `.billing-header`
- `.filter-section` with `.filter-form`
- `.report-section` with `.billing-table`
- `.print-modal` for print preview
- `.payment-form` for payment requests

**Table Types:**
- Site Salary (7 columns)
- Office Salary (7 columns)
- Cash Advance (6 columns)
- Employer Share (5 columns)

**Mobile Styles:**
- Card conversion at `max-width: 767px`
- Label injection via `::before` pseudo-elements

**Print Styles:**
- `@media print` - Converts to black & white for printing

**Action Items for Light Mode:**
- ⚠️ Print styles already convert to light
- ⚠️ Regular view needs light mode
- ⚠️ Mobile card view needs light styling

---

### 14. `employee/css/select_employee.css` (1,917 lines)
**Purpose:** Employee attendance marking page

**Key Components:**
- `.welcome-banner` - Time and date display
- `.stat-card` - Branch statistics
- `.time-alert` - Cutoff time warnings
- `.branch-selection` - Branch picker
- `.filter-options-container` - Status filters
- `.employee-card-list` - List view
- `.employee-table` - Table view
- `.kebab-menu` - Action dropdown
- `.btn-present`, `.btn-absent`, `.btn-transfer` - Status buttons
- `.undo-snackbar` - Undo action
- `.modal-panel` - Time logs modal

**Status Badge Colors:**
- Available: `#FFD700` (gold)
- Present: `#16a34a` (green)
- Absent Auto: `#dc2626` (red, dashed)
- Absent Manual: `#b91c1c` (dark red)

**Action Items for Light Mode:**
- ⚠️ Large file with extensive dark styling
- ⚠️ Light mode partially covered in `light-theme.css`

---

### 15. `employee/css/report.css` (932 lines)
**Purpose:** Weekly payroll reports

**Key Components:**
- `.header-card` - Page header
- `.report-card` - Main container
- `.report-table` - Data table
- `.view-toggle` - View switcher
- `.branch-badge` - Filter badges
- `.btn-payslip` - Payslip button
- `.payslip-modal` - Payslip display
- `.remarks-select` - Payment status dropdown
- `.toast-notification` - Status alerts

**Print Styles:**
- Extensive `@media print` rules
- Converts colors to black & white
- Hides web-only elements

**Mobile Styles:**
- Card conversion at `max-width: 767px`
- 19 data columns with labeled cells

**Action Items for Light Mode:**
- ⚠️ Print styles already handle light backgrounds
- ⚠️ Screen view needs light mode

---

## Adjustment/Override Files

### 16. `CSS_ADJUSTMENTS.css` (201 lines)
**Purpose:** Spacing adjustments for employees.php to match select_employee.php

**Classes:**
- `.main-content` - `padding: 16px`
- `.employees-grid-view` - Grid layout
- `.employees-list-view` - Flex column
- `.employees-details-view` - Detailed cards
- `.employee-card-list`, `.employee-card-details` - Card styling
- `.view-options-container` - View switcher container

**Action Items for Light Mode:**
- ⚠️ Card backgrounds use `#1a1a1a` - needs light variant
- ⚠️ Borders use `#333` - needs light gray

---

## Light Mode Implementation Strategy

### Option 1: Variable-Based (Recommended)
Use `theme-variables.css` as the single source of truth:

```css
/* In component files, use variables instead of hardcoded colors */
.card {
  background: var(--bg-card);
  color: var(--text-primary);
  border-color: var(--border-default);
}
```

**Pros:**
- Single file to maintain
- Automatic theme switching
- Consistent across all components

**Cons:**
- Requires refactoring existing CSS
- Older browsers may need fallbacks

### Option 2: Override-Based (Current Approach)
Use `body.light-mode` prefixes with `!important`:

```css
body.light-mode .card {
  background: #ffffff !important;
  color: #000000 !important;
}
```

**Pros:**
- Works with existing CSS without refactoring
- Explicit control over each component
- Already implemented in `light-theme.css`

**Cons:**
- Difficult to maintain
- Multiple files to update
- `!important` can cause specificity issues

---

## Color Mapping Guide

### Dark to Light Conversion

| Dark Value | Light Value | Variable Name |
|------------|-------------|---------------|
| `#0b0b0b` | `#f8f9fa` | `--bg-page` |
| `#161616` | `#ffffff` | `--bg-card` |
| `#1a1a1a` | `#ffffff` | `--bg-card-hover` |
| `#1e1e1e` | `#f1f3f4` | `--bg-input` |
| `rgba(255,255,255,0.92)` | `#202124` | `--text-primary` |
| `rgba(255,255,255,0.75)` | `#5f6368` | `--text-secondary` |
| `rgba(255,255,255,0.5)` | `#80868b` | `--text-muted` |
| `rgba(255,255,255,0.03)` | `#e8eaed` | `--border-subtle` |
| `rgba(255,255,255,0.06)` | `#dadce0` | `--border-default` |
| `rgba(0,0,0,0.6)` | `0 1px 3px rgba(0,0,0,0.08)` | `--shadow-card` |

---

## File Checklist for Light Mode Completion

### Core Theme (Complete)
- [x] `assets/css/theme-variables.css` - Has both dark and light variables
- [x] `employee/css/light-theme.css` - Comprehensive light mode overrides
- [x] `employee/css/light-theme-notification.css` - Notification light styles

### Needs Light Mode Overrides
- [ ] `assets/css/style.css` - Main attendance styles
- [ ] `assets/css/ai_chat.css` - Chat widget
- [ ] `assets/styles.css` - Utility styles
- [ ] `assets/style_auth.css` - Login/signup pages
- [ ] `employee/css/dashboard.css` - Admin dashboard
- [ ] `employee/css/employees.css` - Employee management
- [ ] `employee/css/notification.css` - OT approval (partial)
- [ ] `employee/css/my_notifications.css` - Personal notifications
- [ ] `employee/css/billing.css` - Payroll reports
- [ ] `employee/css/select_employee.css` - Attendance marking (partial)
- [ ] `employee/css/report.css` - Weekly reports
- [ ] `CSS_ADJUSTMENTS.css` - Employee page adjustments

### Already Light (Verify)
- [x] `assets/style_employee.css` - Employee interface (appears light)

---

## Recommended Next Steps

1. **Audit `theme-variables.css`**
   - Ensure all necessary variables are defined
   - Check for missing color combinations

2. **Consolidate Light Mode Files**
   - Consider merging `light-theme.css` and `light-theme-notification.css`
   - Or split by component for better maintainability

3. **Refactor High-Priority Files**
   - `assets/css/style.css` - Used across multiple pages
   - `employee/css/dashboard.css` - Main admin view
   - `employee/css/select_employee.css` - Daily use attendance page

4. **Test Coverage**
   - All pages with light mode toggle
   - Mobile responsive views
   - Print styles
   - Modal dialogs
   - Form inputs

5. **Documentation**
   - Add light mode toggle instructions
   - Document variable usage for future developers
   - Create component style guide

---

## Usage Example

### Adding Light Mode to a New Component

```css
/* In your component CSS file */
.my-component {
  /* Use variables for themable properties */
  background: var(--bg-card);
  color: var(--text-primary);
  border: 1px solid var(--border-default);
  box-shadow: var(--shadow-card);
}

/* If you need component-specific light mode overrides */
body.light-mode .my-component {
  /* Only if the variable approach doesn't work */
}
```

### JavaScript Toggle

```javascript
// Toggle light mode
function toggleTheme() {
  document.body.classList.toggle('light-mode');
  
  // Save preference
  localStorage.setItem('theme', 
    document.body.classList.contains('light-mode') ? 'light' : 'dark'
  );
}

// Load saved preference
document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('theme') === 'light') {
    document.body.classList.add('light-mode');
  }
});
```

---

## Notes

- **Brand Color Consistency:** The gold (#FFD700, #D4AF37) brand color is maintained in both themes
- **Status Colors:** Status colors (green/red/blue) are adjusted for contrast in light mode
- **Glassmorphism:** Glass effects use `rgba(255,255,255,0.85)` in light mode instead of dark transparency
- **Shadows:** Shadows are softer in light mode for better aesthetics
- **Typography:** Dark text on light backgrounds uses Google Fonts Inter for consistency

---

*Last Updated: 2026-02-18*
*Total CSS Files: 16*
*Total Lines: ~9,800*
