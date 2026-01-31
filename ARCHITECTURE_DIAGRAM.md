# Role-Based Access Control - Visual Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    JAJR Attendance System                        │
│                   Role-Based Access Control                      │
└─────────────────────────────────────────────────────────────────┘

                         Login Page
                             │
                    Validate Credentials
                             │
                    Set $_SESSION['position']
                             │
              ┌──────────────┴──────────────┐
              │                             │
          Employee                      Admin/Super Admin
          (Position: 'Employee')       (Position: 'Admin' or
                                       'Super Admin')
              │                             │
              ▼                             ▼
        ┌──────────────┐            ┌──────────────────┐
        │   Sidebar    │            │    Sidebar       │
        │   (Filtered) │            │  (Full Menu)     │
        │              │            │                  │
        │ 📋 Site Att  │            │ 🏠 Dashboard     │
        │ 👥 Employees │            │ 📋 Site Att      │
        │ 🚪 Log Out   │            │ 👥 Employees     │
        │              │            │ 📅 Reports       │
        │              │            │ 💰 Billing       │
        │              │            │ 🏥 Documents     │
        │              │            │ 🗂️ Logs          │
        │              │            │ ⚙️ Settings      │
        │              │            │ 🚪 Log Out       │
        └──────┬───────┘            └────────┬─────────┘
               │                             │
               │ Try to access              │ Full access
               │ admin page?                │
               │                            ▼
               │                       Admin Page
               │                    (Guard passes)
               │                      execute code
               │                             │
               ▼                            ▼
          Security Guard              Render Page
          (Page top)                       │
               │                    Display to Admin
               │
          Check Session
               │
          Check Role
               │
          Role = 'Employee'?
               │
               YES ──────────────►  REDIRECT
               │              to select_employee.php
               │
               NO
               │
               ▼
          (Never reached)
```

---

## Data Flow

### Admin Page Access Flow

```
Admin/Super Admin User
          │
          ├─► Session set: $_SESSION['position'] = 'Admin'
          │
          ├─► Access /employee/dashboard.php
          │
          ├─► Page loads security guard
          │
          ├─► Check: Session exists? YES ✓
          │
          ├─► Get: $userRole = 'Admin'
          │
          ├─► Check: Is Employee? NO ✓
          │
          ├─► Continue execution
          │
          └─► Display admin content
```

### Employee Page Access Attempt

```
Employee User
          │
          ├─► Session set: $_SESSION['position'] = 'Employee'
          │
          ├─► Try to access /employee/dashboard.php
          │
          ├─► Page loads security guard
          │
          ├─► Check: Session exists? YES ✓
          │
          ├─► Get: $userRole = 'Employee'
          │
          ├─► Check: Is Employee? YES ✗
          │
          ├─► Execute: header("Location: select_employee.php")
          │
          ├─► Execute: exit;
          │
          └─► Redirect browser to select_employee.php
                        │
                        └─► Valid employee page loads
```

---

## Security Layers

```
┌─────────────────────────────────────────────────────┐
│               Security Architecture                  │
├─────────────────────────────────────────────────────┤
│                                                      │
│  Layer 1: Frontend (Sidebar)                        │
│  ┌────────────────────────────────────────────┐    │
│  │ Sidebar.php                                │    │
│  │ ├─ Check $_SESSION['position']             │    │
│  │ ├─ If Employee: Hide admin links           │    │
│  │ ├─ If Admin: Show all links                │    │
│  │ └─ User can't see forbidden menu items     │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  Layer 2: Backend (Page Guard)                      │
│  ┌────────────────────────────────────────────┐    │
│  │ Admin Page (dashboard.php, billing.php)    │    │
│  │ ├─ Check session valid                     │    │
│  │ ├─ Check role is not Employee              │    │
│  │ ├─ If Employee: Redirect                   │    │
│  │ ├─ If Admin: Continue                      │    │
│  │ └─ Direct URL access blocked               │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  Layer 3: Redirect                                  │
│  ┌────────────────────────────────────────────┐    │
│  │ header("Location: select_employee.php")    │    │
│  │ Browser redirected to valid page           │    │
│  │ Session maintained                         │    │
│  │ No error messages                          │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
└─────────────────────────────────────────────────────┘

Result: ✅ Multi-layer protection
        ✅ Can't bypass from frontend
        ✅ Can't access via direct URL
        ✅ Seamless redirect experience
```

---

## Role Permissions Matrix

```
┌────────────────────┬──────────┬───────────┬─────────────┐
│ Feature            │ Employee │   Admin   │ Super Admin │
├────────────────────┼──────────┼───────────┼─────────────┤
│ Dashboard          │    ❌    │     ✅    │      ✅     │
│ Site Attendance    │    ✅    │     ✅    │      ✅     │
│ Employee List      │    ✅    │     ✅    │      ✅     │
│ Reports            │    ❌    │     ✅    │      ✅     │
│ Billing            │    ❌    │     ✅    │      ✅     │
│ Documents          │    ❌    │     ✅    │      ✅     │
│ Activity Logs      │    ❌    │     ✅    │      ✅     │
│ Settings           │    ❌    │     ✅    │      ✅     │
│ Log Out            │    ✅    │     ✅    │      ✅     │
└────────────────────┴──────────┴───────────┴─────────────┘

✅ = Visible in Sidebar + Full Access
❌ = Hidden in Sidebar + Redirected if accessed directly
```

---

## Code Flow Diagram

### sidebar.php

```
START sidebar.php
    │
    ├─► $_SESSION['position'] → $userRole
    │
    ├─► $isAdmin = in_array($userRole, ['Admin', 'Super Admin'])
    │
    ├─► Sidebar HTML Start
    │
    ├─► DASHBOARD MENU ITEM
    │   └─► if ($isAdmin) { show } else { hide }
    │
    ├─► SITE ATTENDANCE (Always show)
    │
    ├─► EMPLOYEE LIST (Always show)
    │
    ├─► REPORTS
    │   └─► if ($isAdmin) { show } else { hide }
    │
    ├─► BILLING
    │   └─► if ($isAdmin) { show } else { hide }
    │
    ├─► DOCUMENTS
    │   └─► if ($isAdmin) { show } else { hide }
    │
    ├─► ACTIVITY LOGS
    │   └─► if ($isAdmin) { show } else { hide }
    │
    ├─► SETTINGS
    │   └─► if ($isAdmin) { show } else { hide }
    │
    ├─► LOG OUT (Always show)
    │
    └─► END sidebar.php
```

### Admin Page (dashboard.php, billing.php, etc.)

```
START Page PHP
    │
    ├─► SESSION CHECK
    │   └─► if (!$_SESSION['user_id']) { redirect login }
    │
    ├─► ROLE CHECK
    │   └─► $userRole = $_SESSION['position']
    │
    ├─► PERMISSION CHECK
    │   ├─► if ($userRole === 'Employee')
    │   │   └─► header("Location: select_employee.php")
    │   │       exit;
    │   │
    │   └─► Admin/Super Admin → Continue
    │
    ├─► Page Logic (Admin users only reach here)
    │
    ├─► Render HTML
    │
    └─► END Page
```

---

## Login to Page Access Flow

```
User Visits: https://localhost/attendance_web/employee/dashboard.php

    │
    ▼
Session Check:
    Is user logged in?
    ├─ NO  → Redirect to login.php
    └─ YES → Continue

    │
    ▼
Role Check:
    Read $_SESSION['position']
    ├─ 'Employee'     → Continue below
    ├─ 'Admin'        → Continue below  
    └─ 'Super Admin'  → Continue below

    │
    ▼
Permission Check:
    Is user an Employee?
    ├─ YES → Redirect to select_employee.php
    │       (User sees no error, just valid page)
    │
    └─ NO  → User is Admin/Super Admin
            Continue to dashboard
            Load dashboard.php fully
            Display to admin user
```

---

## File Update Status

```
┌─────────────────────────────────────────────────────┐
│            Implementation Status                     │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ✅ DONE - Changes Made:                            │
│  ├─ employee/sidebar.php                           │
│  │  └─ Added role detection + filtering            │
│  │                                                 │
│  └─ Documentation created (5 files)                │
│     ├─ SECURITY_GUARD_SNIPPET.php                  │
│     ├─ SECURITY_GUARD_TEMPLATES.md                 │
│     ├─ ROLE_BASED_FILTERING_GUIDE.md               │
│     ├─ ROLE_FILTERING_SUMMARY.md                   │
│     └─ IMPLEMENTATION_COMPLETE.md                  │
│                                                      │
│  ⏳ YOU DO - Next Steps:                            │
│  ├─ employee/dashboard.php                         │
│  │  └─ Add security guard (copy/paste)            │
│  │                                                 │
│  ├─ employee/billing.php                           │
│  │  └─ Add security guard (copy/paste)            │
│  │                                                 │
│  ├─ (Optional) Other admin pages                   │
│  │  └─ Add security guard as needed               │
│  │                                                 │
│  └─ Test with both user roles                      │
│     ├─ Login as Employee                           │
│     ├─ Login as Admin                              │
│     └─ Verify behavior                             │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## Time Estimate

```
Activity                          Time
──────────────────────────────── ────────

Read this file                    5 min
Read SECURITY_GUARD_TEMPLATES.md  5 min
Add guard to dashboard.php        2 min
Add guard to billing.php          2 min
Test as Employee                  5 min
Test as Admin                     5 min
──────────────────────────────── ────────
Total                             24 min
```

---

## Success Criteria

✅ Sidebar shows correct items per role
✅ Employee can't see admin menu items
✅ Admin can see all menu items
✅ Employee redirected when accessing admin page
✅ Admin can access all admin pages
✅ No errors or broken functionality
✅ Dark theme maintained
✅ Responsive design working

---

## Key Points

```
┌─────────────────────────────────────────────┐
│ Remember:                                    │
├─────────────────────────────────────────────┤
│ 1. $_SESSION['position'] is the key         │
│    ├─ 'Employee'                            │
│    ├─ 'Admin'                               │
│    └─ 'Super Admin'                         │
│                                              │
│ 2. Sidebar filtering is frontend           │
│    └─ Hides items from view                 │
│                                              │
│ 3. Page guards are backend                 │
│    └─ Blocks direct access                  │
│                                              │
│ 4. Both layers needed                      │
│    └─ Defense in depth                      │
│                                              │
│ 5. Dark theme maintained                   │
│    └─ No styling changes                    │
│                                              │
│ 6. User experience seamless                │
│    └─ Redirects, not errors                 │
└─────────────────────────────────────────────┘
```

---

**Architecture Complete** ✅
**Documentation Complete** ✅
**Ready for Implementation** ✅
