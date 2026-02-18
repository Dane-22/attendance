# QR Code Scanner Documentation

## Overview

The QR code scanner in `login.php` allows employees to clock in/out without logging in. This is a **kiosk mode** feature that works independently of user sessions.

## Flow Diagram

```
┌─────────────────┐
│   User clicks   │
│  QR icon on     │
│   login.php     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Modal opens    │
│  Camera starts  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌──────────────────┐
│  QR code scanned │────▶│ Parse emp_id and │
│                  │     │ emp_code from QR │
└─────────────────┘     └────────┬─────────┘
                                 │
                                 ▼
                        ┌──────────────────┐
                        │  Extract from URL │
                        │  ?emp_id=123&     │
                        │   emp_code=ABC    │
                        └────────┬─────────┘
                                 │
                                 ▼
                        ┌──────────────────┐
                        │  POST to qr_clock │
                        │   /api/qr_clock.php│
                        │   action=in        │
                        │   employee_id=123  │
                        └────────┬─────────┘
                                 │
                                 ▼
                        ┌──────────────────┐
                        │  Server checks if │
                        │  already clocked  │
                        │       in          │
                        └────────┬─────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                          │
                    ▼                          ▼
           ┌────────────────┐         ┌─────────────────┐
           │   YES          │         │   NO            │
           │   Already in   │         │   Not clocked   │
           └───────┬────────┘         │   in yet         │
                   │                  └────────┬────────┘
                   │                           │
                   ▼                           ▼
           ┌────────────────┐          ┌─────────────────┐
           │ Auto-call      │          │ Insert new      │
           │ action=out     │          │ attendance row  │
           │ (clock-out)    │          │ with time_in    │
           └───────┬────────┘          └────────┬────────┘
                   │                            │
                   │              ┌─────────────┴─────────────┐
                   │              │                             │
                   ▼              ▼                             ▼
           ┌────────────────┐  ┌─────────────────┐    ┌─────────────────┐
           │ Update existing │  │ Return success   │    │ Return success   │
           │ row with time_out│  │ "Time-in at..." │    │ "Time-out at..."│
           └───────┬────────┘  └────────┬────────┘    └────────┬────────┘
                   │                    │                       │
                   └────────────────────┴───────────────────────┘
                                        │
                                        ▼
                              ┌───────────────────┐
                              │  Show result in    │
                              │  modal (green/red) │
                              └────────┬──────────┘
                                       │
                                       ▼
                              ┌───────────────────┐
                              │  "Scan Another"   │
                              │   button appears  │
                              └───────────────────┘
```

## Key Files

| File | Purpose |
|------|---------|
| `login.php` | Contains QR scanner modal and JavaScript logic |
| `employee/api/qr_clock.php` | Simple API for clock-in/out without session |
| `employee/select_employee.php` | Original QR redirect target (no longer used) |

## JavaScript Functions

### `parseEmployeeFromQR(text)`
- Extracts `emp_id` and `emp_code` from QR URL
- Supports format: `https://.../select_employee.php?auto_timein=1&emp_id=123&emp_code=ABC`
- Falls back to plain text as employee code

### `processClockIn(empId, empCode)`
- POST to `qr_clock.php` with `action=in`
- If response says "already clocked in", auto-calls `processClockOut()`
- Returns `{success, message}` for UI display

### `processClockOut(empId, empCode)`
- POST to `qr_clock.php` with `action=out`
- Updates existing attendance row with `time_out`
- Returns `{success, message}` for UI display

### `showResult(success, message)`
- Hides camera view
- Shows green (success) or red (error) message
- Displays "Scan Another" button

## API Endpoint: `qr_clock.php`

### Input
```php
$_POST['action']        // 'in' or 'out'
$_POST['employee_id']  // Employee ID (required)
$_POST['employee_code']// Employee code (optional)
```

### Output
```json
{
  "success": true,
  "message": "John Doe time-in recorded at 09:30 AM"
}
```

Or if already clocked in:
```json
{
  "success": false,
  "message": "Already clocked in",
  "already_in": true
}
```

## Database Operations

### Clock In
1. Check if employee exists and is active
2. Check if already clocked in today (open shift)
3. If yes: return `already_in: true`
4. If no: Insert new attendance row with `time_in = NOW()`, `status = 'Present'`

### Clock Out
1. Find latest open attendance row (today, has `time_in`, no `time_out`)
2. Update with `time_out = NOW()`
3. If no open shift found: return error

## Important Notes

- **No session required**: The QR scanner works without login
- **Branch auto-detection**: Uses employee's assigned branch from database
- **Auto clock-out**: If already clocked in, system automatically performs clock-out
- **Mobile optimized**: Uses `facingMode: 'environment'` for rear camera
- **Error handling**: Shows detailed error messages in modal

## Testing

1. Open `login.php` on mobile
2. Click QR icon
3. Allow camera permission
4. Scan employee QR code
5. Should see success message: "[Name] time-in recorded at [time]"
6. Scan same QR again
7. Should see: "[Name] time-out recorded at [time]"
