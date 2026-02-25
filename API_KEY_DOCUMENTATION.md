# API Key System Documentation

## Overview

The Attendance System now uses API keys for authenticating API requests. This provides secure access control for all API endpoints.

## Quick Start

### 1. Generate API Keys

Run the setup script to auto-generate API keys:

```
https://your-domain.com/main/setup_api_keys.php
```

Or access the admin interface:

```
https://your-domain.com/main/employee/api_key_management.php
```

### 2. Using API Keys

Include your API key in every API request using one of these methods:

#### Method 1: Header (Recommended)
```
X-API-Key: jajr_live_abc123...
```

#### Method 2: Bearer Token
```
Authorization: Bearer jajr_live_abc123...
```

#### Method 3: POST/GET Parameter
```
POST /login_api.php
Content-Type: application/x-www-form-urlencoded

api_key=jajr_live_abc123...&identifier=EMP001&password=secret
```

## Protected API Endpoints

All API files now require API key authentication:

| API File | Endpoint Name | Purpose |
|----------|--------------|---------|
| login_api.php | login_api | Mobile app login |
| time_in_api.php | time_in_api | Clock in |
| time_out_api.php | time_out_api | Clock out |
| clock_out_api.php | clock_out_api | Alternative clock out |
| qr_clock_api.php | qr_clock_api | QR code clock in/out |
| submit_attendance_api.php | submit_attendance_api | Submit attendance |
| get_branches_api.php | get_branches_api | List branches |
| get_branch_api.php | get_branch_api | Get single branch |
| employees_today_status_api.php | employees_today_status_api | Today's status |
| get_available_employees_api.php | get_available_employees_api | Available employees |
| get_shift_logs_api.php | get_shift_logs_api | Shift logs |
| mark_attendance_absent_api.php | mark_attendance_absent_api | Mark absent |
| get_attendance_absent_notes_api.php | get_attendance_absent_notes_api | Absent notes |
| set_attendance_ot_hrs_api.php | set_attendance_ot_hrs_api | Set OT hours |
| transfer_branch_api.php | transfer_branch_api | Transfer employee |
| set_employee_branch_api.php | set_employee_branch_api | Set branch |
| update_profile_api.php | update_profile_api | Update profile |
| change-password-api.php | change-password-api | Change password |

## Admin Management

Access the API key management dashboard at:

```
/main/employee/api_key_management.php
```

Features:
- Generate new API keys
- Auto-generate system keys
- Revoke keys
- Delete keys
- View usage statistics

## Security Features

1. **Cryptographically Secure**: Keys use `random_bytes()` for generation
2. **Permission-Based**: Keys can be restricted to specific endpoints
3. **Expiration Support**: Optional expiration dates
4. **Usage Tracking**: Last used timestamp recorded
5. **Revocation**: Keys can be revoked without deleting

## Example cURL Requests

### Login with API Key (Header)
```bash
curl -X POST https://your-domain.com/main/login_api.php \
  -H "X-API-Key: jajr_live_abc123..." \
  -H "Content-Type: application/json" \
  -d '{"identifier": "EMP001", "password": "secret", "branch_name": "Main Branch"}'
```

### Clock In with API Key (POST data)
```bash
curl -X POST https://your-domain.com/main/time_in_api.php \
  -d "api_key=jajr_live_abc123..." \
  -d "employee_id=123" \
  -d "branch_name=Main Branch"
```

### Get Branches with API Key (Query string)
```bash
curl "https://your-domain.com/main/get_branches_api.php?api_key=jajr_live_abc123..."
```

## Error Responses

### Missing API Key
```json
{
  "success": false,
  "message": "API key required. Provide via X-API-Key header, api_key parameter, or Authorization: Bearer header."
}
```

### Invalid API Key
```json
{
  "success": false,
  "message": "Invalid or inactive API key"
}
```

### Expired API Key
```json
{
  "success": false,
  "message": "API key has expired"
}
```

### Unauthorized Endpoint
```json
{
  "success": false,
  "message": "API key not authorized for this endpoint"
}
```

## Database Schema

API keys are stored in the `api_keys` table:

```sql
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    api_name VARCHAR(100) NOT NULL,
    description TEXT,
    permissions JSON,
    is_active TINYINT(1) DEFAULT 1,
    rate_limit INT DEFAULT 1000,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    INDEX idx_api_key (api_key),
    INDEX idx_active (is_active)
);
```

## Integration Notes

### For Mobile App Developers

Include the API key in all requests:

```kotlin
// Android/Kotlin example
val client = OkHttpClient()
val request = Request.Builder()
    .url("https://your-domain.com/main/login_api.php")
    .header("X-API-Key", "jajr_live_abc123...")
    .post(formBody)
    .build()
```

```swift
// iOS/Swift example
var request = URLRequest(url: url)
request.setValue("jajr_live_abc123...", forHTTPHeaderField: "X-API-Key")
```

### For React Native

```javascript
fetch('https://your-domain.com/main/login_api.php', {
  method: 'POST',
  headers: {
    'X-API-Key': 'jajr_live_abc123...',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(data)
})
```

## Troubleshooting

### API Key Not Working
1. Check the key is active in the admin panel
2. Verify the key hasn't expired
3. Ensure correct endpoint permissions
4. Check the header/parameter name (case-sensitive)

### 403 Forbidden
- API key is invalid or revoked
- Key doesn't have permission for the endpoint

### 401 Unauthorized
- No API key provided in request

## Security Best Practices

1. **Store keys securely** - Never commit keys to version control
2. **Rotate keys periodically** - Generate new keys every 6-12 months
3. **Use environment variables** - Don't hardcode keys in source code
4. **Revoke unused keys** - Clean up keys that are no longer needed
5. **Monitor usage** - Check last_used_at for suspicious activity
