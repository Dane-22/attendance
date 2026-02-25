# Employee Module Font Style Documentation

## Overview

This document tracks the font families used across all PHP files in the `employee/` directory to ensure consistent typography across the JAJR Attendance System.

---

## Font Style Summary by Page

| Page | Font Family | Source | Status |
|------|-------------|--------|--------|
| **dashboard.php** | `'Inter', system-ui, -apple-system, sans-serif` | Google Fonts | ✅ Active |
| **select_employee.php** | `'Inter', sans-serif` | Google Fonts | ✅ Active |
| **notification.php** | `'Inter', sans-serif` | Google Fonts | ✅ Active |
| **employees.php** | `'Inter', sans-serif` | Google Fonts | ✅ Active |
| **documents.php** | `'Inter', sans-serif` | Google Fonts | ✅ Active |
| **logs.php** | `'Segoe UI', Tahoma, Geneva, Verdana, sans-serif` | Inline CSS | ⚠️ Different |
| **weekly_report.php** | `'Inter', sans-serif` | Google Fonts | ✅ Active |
| **overtime.php** | `'Inter', sans-serif` | Google Fonts | ✅ Active |
| **billing.php** | Browser default (Times New Roman/Serif) | None | ❌ Inconsistent |
| **cash_advance.php** | `'Inter', sans-serif` | Google Fonts | ⚠️ Commented out |
| **settings.php** | `'Inter', sans-serif` | Google Fonts | ✅ Active |

---

## Detailed Breakdown

### ✅ Consistent Inter Font Pages

These pages use the **Inter** font family consistently:

1. **dashboard.php** (`@c:\wamp64\www\main\employee\dashboard.php:136`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
   ```
   CSS: `font-family: 'Inter', system-ui, -apple-system, sans-serif;`

2. **select_employee.php** (`@c:\wamp64\www\main\employee\select_employee.php:110`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
   ```
   CSS: `font-family: 'Inter', sans-serif;`

3. **notification.php** (`@c:\wamp64\www\main\employee\notification.php:570`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
   ```

4. **employees.php** (`@c:\wamp64\www\main\employee\employees.php:22`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
   ```

5. **documents.php** (`@c:\wamp64\www\main\employee\documents.php:256`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
   ```

6. **weekly_report.php** (`@c:\wamp64\www\main\employee\weekly_report.php:23`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
   ```

7. **overtime.php** (`@c:\wamp64\www\main\employee\overtime.php:140`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
   ```

8. **settings.php** (`@c:\wamp64\www\main\employee\settings.php:426`)
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
   ```
   CSS: `font-family: 'Inter', sans-serif;`

---

### ⚠️ Inconsistent Pages

#### **billing.php** - NO FONT DECLARATION
- **Location**: `c:\wamp64\www\main\employee\billing.css`
- **Issue**: The CSS file has no `font-family` declaration for `html, body`
- **Result**: Sidebar displays in browser default serif font (Times New Roman)
- **Fix Needed**: Add:
  ```css
  html, body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
  }
  ```

#### **cash_advance.php** - COMMENTED OUT
- **Location**: `c:\wamp64\www\main\employee\cash_advance.php:385`
- **Issue**: Google Fonts link is commented out
  ```html
  <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"> -->
  ```
- **Note**: Page still loads `select_employee.css` which has `font-family: 'Inter', sans-serif;`
- **Status**: Currently inherits from `select_employee.css`

#### **logs.php** - DIFFERENT FONT (Segoe UI)
- **Location**: `c:\wamp64\www\main\employee\logs.php:102`
- **Issue**: Uses different font stack:
  ```css
  body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  ```
- **Note**: No Google Fonts import; uses system fonts only
- **Visual Impact**: Sidebar has slightly different appearance

---

## Recommendations

### Immediate Actions

1. **billing.css** - Add Inter font declaration
   - File: `c:\wamp64\www\main\employee\css\billing.css`
   - Add after line 17:
   ```css
   html, body {
       font-family: 'Inter', system-ui, -apple-system, sans-serif;
   }
   ```

2. **logs.php** - Consider standardizing to Inter
   - Change from `Segoe UI` to `Inter`
   - Add Google Fonts import

3. **cash_advance.php** - Uncomment or remove
   - Either restore the Google Fonts link
   - Or rely on `select_employee.css` (current behavior)

### Long-term

- Consider creating a shared `font.css` file that all pages can import
- This would centralize font management and ensure consistency

---

## CSS Files with Font Declarations

### `dashboard.css`
```css
html, body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
```

### `select_employee.css`
```css
body {
    font-family: 'Inter', sans-serif;
}
```

### `style.css` (shared)
```css
html, body {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
}
```

---

## Sidebar Font Inheritance

The sidebar (`sidebar.php`) inherits its font from the parent page's CSS:

- On pages with **Inter**: Sidebar displays in Inter (modern, clean sans-serif)
- On **billing.php**: Sidebar displays in Times New Roman (system default serif)
- On **logs.php**: Sidebar displays in Segoe UI (Windows system font)

---

*Last Updated: February 25, 2026*
