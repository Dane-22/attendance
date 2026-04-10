# Production Deployment - Attendance/Payroll Discrepancy Fix

## Overview
The images show Menuel Benitez with **5 days worked** in `weekly_report.php` but only **4 attendance records** in `audit.php` calendar view. This indicates orphaned records in production's `daily_payroll_reports` table.

## Quick Start (Run on Production Server)

### Step 1: Backup Production Database
```bash
# SSH to production server, then:
mysqldump -u root -p your_database_name daily_payroll_reports > daily_payroll_reports_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Deploy Fix Scripts
Upload these files to `/employee/cron/` on production:
- `fix_attendance_discrepancy.php`
- `create_validation_trigger.php`
- `daily_reconciliation_check.php`

### Step 3: Run Fix Script
```bash
cd /path/to/employee/cron
php fix_attendance_discrepancy.php
```

### Step 4: Create Database Triggers
```bash
php create_validation_trigger.php
```

### Step 5: Update Code Files
Replace these files on production with the updated versions:
- `employee/cron/generate_daily_payroll.php`
- `employee/cron/daily_payroll_calculation.php`

---

## Detailed Deployment Steps

### 1. Pre-Deployment Checklist

- [ ] Verify SSH access to production server
- [ ] Confirm MySQL credentials
- [ ] Identify production database name
- [ ] Schedule maintenance window (if needed)
- [ ] Notify stakeholders of brief interruption

### 2. Database Backup (Critical)

**Option A: Using mysqldump**
```bash
mysqldump -h localhost -u root -p your_database_name daily_payroll_reports > backup_dpr_$(date +%Y%m%d).sql
```

**Option B: Using PHPMyAdmin**
1. Export `daily_payroll_reports` table
2. Save as SQL file with timestamp

### 3. Upload Files

Upload these files to production server's `/employee/cron/` directory:

```
employee/cron/
├── fix_attendance_discrepancy.php         [NEW]
├── create_validation_trigger.php            [NEW]
├── daily_reconciliation_check.php         [NEW]
├── generate_daily_payroll.php               [MODIFY]
└── daily_payroll_calculation.php            [MODIFY]
```

### 4. Execute Fix Script

```bash
# Navigate to cron directory
cd /var/www/html/main/employee/cron

# Run the fix
php fix_attendance_discrepancy.php 2>&1 | tee fix_output_$(date +%Y%m%d).log
```

**Expected Output:**
```
=== Starting Attendance/Payroll Discrepancy Fix ===
Step 1: Creating backup table: daily_payroll_reports_backup_2026_04_10_xxxxxx
✓ Backup created successfully
Step 2: Investigating orphaned records...
Found orphaned records:
  - Total orphaned records: [N]
  - Affected employees: [M]
  ...
Step 4: Deleting orphaned records...
✓ Deleted [N] orphaned records
Step 5: Regenerating clean payroll data...
✓ Regenerated [X] new records
=== Fix Complete ===
```

### 5. Create Database Triggers

```bash
php create_validation_trigger.php
```

This creates two triggers:
- `trg_validate_attendance_before_dpr_insert` - Prevents INSERT without valid attendance
- `trg_validate_attendance_before_dpr_update` - Prevents UPDATE to invalid references

### 6. Verify Fix

```bash
php verify_fix.php
```

Or manually check:
1. Open `audit.php` → Find Menuel Benitez → Check calendar for Week 2
2. Open `weekly_report.php` → Find Menuel Benitez → Check "Days" column
3. Numbers should now match

---

## Manual SQL Queries (If Script Fails)

If the PHP script fails, run these SQL queries directly on production:

### 1. Create Backup
```sql
CREATE TABLE daily_payroll_reports_backup_2026_04_10 AS 
SELECT * FROM daily_payroll_reports;
```

### 2. Find Orphaned Records
```sql
SELECT 
    dpr.employee_id,
    e.first_name,
    e.last_name,
    dpr.report_date,
    dpr.days_worked
FROM daily_payroll_reports dpr
JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
    AND dpr.report_date = a.attendance_date
    AND a.time_in IS NOT NULL 
    AND a.time_out IS NOT NULL
WHERE a.id IS NULL
ORDER BY dpr.report_date DESC;
```

### 3. Delete Orphaned Records
```sql
DELETE dpr FROM daily_payroll_reports dpr
LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
    AND dpr.report_date = a.attendance_date
    AND a.time_in IS NOT NULL 
    AND a.time_out IS NOT NULL
WHERE a.id IS NULL;
```

### 4. Regenerate Clean Data
```bash
# After deleting orphans, run:
php generate_daily_payroll.php?start_date=2026-04-01&end_date=2026-04-30
```

---

## Post-Deployment Verification

### Check 1: Menuel Benitez Data
```sql
-- Check attendance records
SELECT attendance_date, time_in, time_out 
FROM attendance 
WHERE employee_id = [MENUEL_ID] 
AND attendance_date BETWEEN '2026-04-06' AND '2026-04-10';

-- Check payroll records  
SELECT report_date, days_worked
FROM daily_payroll_reports
WHERE employee_id = [MENUEL_ID]
AND report_date BETWEEN '2026-04-06' AND '2026-04-10';
```

### Check 2: Overall Consistency
```sql
-- Count mismatches across all employees
SELECT COUNT(*) as mismatch_count
FROM daily_payroll_reports dpr
LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
    AND dpr.report_date = a.attendance_date
    AND a.time_in IS NOT NULL 
    AND a.time_out IS NOT NULL
WHERE a.id IS NULL;
```

---

## Rollback Plan

If issues occur after deployment:

### 1. Restore Data
```sql
-- Truncate current table
TRUNCATE TABLE daily_payroll_reports;

-- Restore from backup
INSERT INTO daily_payroll_reports 
SELECT * FROM daily_payroll_reports_backup_2026_04_10;
```

### 2. Remove Triggers
```sql
DROP TRIGGER IF EXISTS trg_validate_attendance_before_dpr_insert;
DROP TRIGGER IF EXISTS trg_validate_attendance_before_dpr_update;
```

### 3. Restore Code Files
Restore the original versions of:
- `generate_daily_payroll.php`
- `daily_payroll_calculation.php`

---

## Monitoring Schedule

After deployment, monitor for 1 week:

### Daily (Add to cron after daily_payroll_calculation.php)
```bash
# Run reconciliation check
php daily_reconciliation_check.php
```

### Weekly
Run manual verification:
```bash
php verify_fix.php
```

---

## Troubleshooting

### Issue: "Permission denied" when creating triggers
**Solution:** Use MySQL root user or grant TRIGGER privilege:
```sql
GRANT TRIGGER ON your_database_name.* TO 'your_user'@'localhost';
```

### Issue: "Backup table already exists"
**Solution:** Use different backup name or drop existing:
```sql
DROP TABLE IF EXISTS daily_payroll_reports_backup_old;
```

### Issue: Script times out on large datasets
**Solution:** Process in batches by date range:
```bash
php fix_attendance_discrepancy.php --start-date=2026-04-01 --end-date=2026-04-15
php fix_attendance_discrepancy.php --start-date=2026-04-16 --end-date=2026-04-30
```

---

## Support

If deployment fails:
1. Check `employee/cron/fix_attendance_discrepancy.log` for errors
2. Verify MySQL connection in `conn/db_connection.php`
3. Ensure PHP CLI is available: `php --version`

---

**Deployment Date:** _____________
**Deployed By:** _____________
**Verified By:** _____________
