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
