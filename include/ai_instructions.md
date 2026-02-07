# JAJR AI Assistant — Page Instructions

## select_employee.php

# Select Employee (Attendance) — Help

## What this page does
- Lets you select a deployment branch and manage attendance for employees assigned to that branch.

## How to use
1. Select a deployment branch from the cards at the top.
2. Use the **Filters** dropdown:
   - **Available**: employees with no attendance today (and/or already timed-out if time columns exist).
   - **Summary**: shows employees for the selected branch with their latest status today.
   - **Present**: employees currently timed-in (open shift) in the selected branch.
   - **Absent**: employees explicitly marked Absent today.
3. Actions per employee:
   - **Absent** button: marks employee as Absent (creates today’s attendance row if none exists).
   - **Time In / Time Out** button: logs attendance.
     - Disabled if employee is marked Absent.
4. Options menu (three dots):
   - **Time Logs Today**: view today’s time-in/out logs.
   - **Overtime**: adjust overtime hours if allowed.
   - **Transfer**: move employee to another branch (if enabled).
   - **Undo last action**: revert the latest attendance action.

## Common issues
- If you can’t Time In/Out, make sure:
  - A branch is selected.
  - The employee is not marked Absent.

## employees.php

# Employees (Management) — Help

## What this page does
- Lets Admin/Super Admin manage employee records (view, search, add, edit).

## How to use
1. Use the **Search** box to find employees by name/ID.
2. Use pagination controls to navigate pages and change page size.
3. Use **Add Employee** to create a new worker record (if your role allows it).
4. Use **Edit** to update employee details (name, contact, status, position, etc.).

## Tips
- If edits are not saving or buttons are missing, confirm your role/permission.

## settings.php

# Settings — Help

## What this page does
- Lets you manage your account profile, password, and (for admins) system tools.

## How to use
1. Use the left-side tabs to switch between settings sections.
2. **Update Profile**: change name/email/profile image.
3. **Change Password**: enter current + new password and confirm.
4. **Admin/System Tools** (if visible): perform system-level actions like backups or diagnostics.

## Tips
- If you don’t see admin tools, your current role may not have access.

## default

# General Help

Tell me what page you are on and what you are trying to do (e.g., mark attendance, add employee, change password). I can guide you step-by-step.
