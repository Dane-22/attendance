# Weekly_Report.php Backend Recreation Plan

A focused plan for recreating only the backend (PHP/API) of the weekly payroll report module. **The existing UI interface will NOT be changed.**

---

## Scope

**IMPORTANT:** The existing `weekly_report.php` interface (HTML/CSS/JS) will remain **UNCHANGED**. Only the backend API that supplies data will be rebuilt.

**IN SCOPE (Backend Only):**
- Payroll calculation engine
- API endpoints for data retrieval and updates
- Database models and queries
- Export functionality (Excel generation)
- Payslip generation logic

**OUT OF SCOPE:**
- ❌ **Frontend UI (HTML/CSS/JS)** - Existing interface stays exactly the same
- ❌ Changing any visual elements, buttons, tables, or layouts
- ❌ Audit.php recreation
- ❌ Calendar components
- ❌ Push notifications

**Compatibility Note:** The new backend API will return data in the same format as the current system, so the existing UI can consume it without any changes.

---

## Phase 1: Database Layer

### 1.1 New Tables Required

#### payroll_periods
```sql
CREATE TABLE payroll_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_type ENUM('weekly', 'monthly', 'custom') NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    week_number INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed TINYINT(1) DEFAULT 0,
    closed_by INT,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_period (year, month, week_number, period_type)
) ENGINE=InnoDB;
```

#### payroll_adjustments (for allowances, loans, CA)
```sql
CREATE TABLE payroll_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_id INT NOT NULL,
    adjustment_type ENUM('allowance', 'cash_advance', 'sss_loan', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_period (employee_id, period_id),
    INDEX idx_type (adjustment_type)
) ENGINE=InnoDB;
```

#### payment_status_tracking
```sql
CREATE TABLE payment_status_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_id INT NOT NULL,
    status ENUM('Not Paid', 'Paid', 'Pending') DEFAULT 'Not Paid',
    paid_at TIMESTAMP NULL,
    paid_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_emp_period (employee_id, period_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;
```

### 1.2 Existing Tables to Use
- `daily_payroll_reports` - Primary payroll data source
- `attendance` - Fallback source for calculations
- `employees` - Employee master data
- `branches` - Branch filtering
- `overtime_requests` - Authoritative OT hours
- `cash_advances` - CA deduction source

---

## Phase 2: Core Classes Architecture

### 2.1 Directory Structure
```
/classes/payroll/
├── PayrollEngine.php          # Main calculation engine
├── PayrollPeriod.php          # Period management
├── PayrollReport.php          # Report generation
├── PayrollCalculator.php      # Calculation logic
├── AttendanceAggregator.php   # Attendance data merging
├── GovernmentDeductions.php   # SSS/PhilHealth/PagIBIG
└── PayslipGenerator.php       # Payslip PDF/HTML

/models/
├── PayrollPeriodModel.php
├── PayrollAdjustmentModel.php
├── PaymentStatusModel.php
└── EmployeePayrollModel.php

/api/v1/payroll/
├── index.php                  # API router
├── getPayroll.php             # GET payroll data
├── updateAdjustment.php       # POST allowance/loan/CA
├── updatePaymentStatus.php    # POST payment status
├── exportExcel.php            # GET Excel export
└── generatePayslip.php        # GET payslip data
```

### 2.2 Class Definitions

#### PayrollEngine.php
```php
class PayrollEngine {
    private $db;
    private $deductions;
    private $aggregator;
    
    public function __construct($db);
    
    // Main calculation methods
    public function calculateForPeriod($startDate, $endDate, $branchId = null): array;
    public function calculateForEmployee($employeeId, $startDate, $endDate): array;
    public function recalculateWithAdjustments($payrollData, $adjustments): array;
    
    // Data retrieval
    private function getDailyPayrollReports($startDate, $endDate, $branchId): array;
    private function getAttendanceData($startDate, $endDate, $branchId): array;
    private function getApprovedOvertime($startDate, $endDate, $branchId): array;
    
    // Calculation helpers
    private function calculateDaysWorked($attendanceRecords): float;
    private function mergeAttendanceRecords($records, $thresholdMinutes = 15): array;
    private function handleTransferScenario($records): array;
    private function calculateGovernmentDeductions($viewType, $weekNumber): array;
}
```

#### PayrollCalculator.php
```php
class PayrollCalculator {
    // Government deduction constants
    const MONTHLY_SSS = 450.00;
    const MONTHLY_PHILHEALTH = 250.00;
    const MONTHLY_PAGIBIG = 200.00;
    
    // Calculation methods
    public function calculateGrossPay($daysWorked, $dailyRate, $otHours, $otRate): float;
    public function calculateNetPay($grossPay, $deductions, $adjustments): float;
    public function prorateDeductions($deductionType, $weekNumber): float;
    public function calculateDaysFromHours($totalHours, $standardHours = 8): float;
    
    // Validation
    public function validateCalculation($payrollData): bool;
    public function detectAnomalies($payrollData): array;
}
```

#### AttendanceAggregator.php
```php
class AttendanceAggregator {
    private $mergeThresholdMinutes = 15;
    
    public function aggregateByEmployee($attendanceData): array;
    public function mergeRecords($records, $thresholdMinutes): array;
    public function identifyTransferScenarios($dailyRecords): bool;
    public function calculatePerBranchHours($records): array;
    public function determinePrimaryBranch($branchHours): string;
}
```

#### PayslipGenerator.php
```php
class PayslipGenerator {
    public function generate($employeeData, $periodData): array;
    public function generateForBundle($employeesData, $periodData): array;
    public function formatCurrency($amount): string;
    public function formatPeriodLabel($viewType, $periodData): string;
}
```

---

## Phase 3: API Endpoints

### 3.1 GET /api/v1/payroll/getPayroll.php
**Purpose:** Retrieve payroll data for view

**Parameters:**
```
view_type: weekly|monthly|range
start_date: YYYY-MM-DD (required for range)
end_date: YYYY-MM-DD (required for range)
month: YYYY-MM (for weekly/monthly)
week: 1-5 (for weekly)
branch_id: int|'all'
page: int (default: 1)
per_page: int (default: 50)
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "type": "weekly",
      "label": "Week 2: Apr 07 - Apr 11, 2026",
      "start_date": "2026-04-07",
      "end_date": "2026-04-11"
    },
    "summary": {
      "total_employees": 25,
      "total_days": 120,
      "total_hours": 960,
      "total_gross": 72000.00,
      "total_deductions": 12500.00,
      "total_net": 59500.00
    },
    "employees": [
      {
        "employee_id": 24,
        "employee_code": "E0014",
        "name": "KELVIN CALDERON",
        "position": "Worker",
        "branch": "BCDA - Admin",
        "daily_rate": 500.00,
        "days_worked": 5.0,
        "total_hours": 40.0,
        "basic_pay": 2500.00,
        "ot_hours": 3.0,
        "ot_rate": 62.50,
        "ot_amount": 187.50,
        "performance_allowance": 0.00,
        "gross_pay": 2687.50,
        "deductions": {
          "sss": 100.00,
          "philhealth": 100.00,
          "pagibig": 50.00,
          "cash_advance": 0.00,
          "sss_loan": 0.00,
          "total": 250.00
        },
        "take_home_pay": 2437.50,
        "payment_status": "Not Paid",
        "_breakdown": {
          "daily": [...],
          "branches": [...]
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_records": 25,
      "per_page": 50
    }
  }
}
```

### 3.2 POST /api/v1/payroll/updateAdjustment.php
**Purpose:** Update allowance, loan, or CA

**Parameters:**
```json
{
  "employee_id": 24,
  "period_id": 15,
  "adjustment_type": "allowance|cash_advance|sss_loan",
  "amount": 500.00,
  "description": "Performance bonus"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Performance allowance updated for KELVIN CALDERON",
  "data": {
    "employee_id": 24,
    "new_allowance": 500.00,
    "recalculated_net_pay": 2937.50
  }
}
```

### 3.3 POST /api/v1/payroll/updatePaymentStatus.php
**Purpose:** Update payment status

**Parameters:**
```json
{
  "employee_id": 24,
  "period_id": 15,
  "status": "Paid"
}
```

**Response:**
```json
{
  "success": true,
  "message": "KELVIN CALDERON marked as Paid",
  "data": {
    "employee_id": 24,
    "status": "Paid",
    "paid_at": "2026-04-13T13:58:00+08:00",
    "paid_by": 6
  }
}
```

### 3.4 GET /api/v1/payroll/exportExcel.php
**Purpose:** Export payroll to Excel

**Parameters:**
```
view_type: weekly|monthly|range
start_date: YYYY-MM-DD
end_date: YYYY-MM-DD
month: YYYY-MM
week: 1-5
branch_id: int|'all'
format: xlsx|csv
```

**Response:** File download (binary stream)

### 3.5 GET /api/v1/payroll/generatePayslip.php
**Purpose:** Get payslip data for printing

**Parameters:**
```
employee_id: int
period_id: int
view_type: weekly|monthly|range
start_date: YYYY-MM-DD
end_date: YYYY-MM-DD
```

**Response:**
```json
{
  "success": true,
  "data": {
    "employee": {
      "id": 24,
      "code": "E0014",
      "name": "KELVIN CALDERON",
      "position": "Worker"
    },
    "period": {
      "label": "Week 2: Apr 07 - Apr 11, 2026"
    },
    "earnings": {
      "days_worked": 5,
      "daily_rate": 500.00,
      "basic_pay": 2500.00,
      "ot_hours": 3.0,
      "ot_amount": 187.50,
      "allowance": 0.00,
      "gross_pay": 2687.50
    },
    "deductions": {
      "sss": 100.00,
      "philhealth": 100.00,
      "pagibig": 50.00,
      "cash_advance": 0.00,
      "sss_loan": 0.00,
      "total": 250.00
    },
    "net_pay": 2437.50
  }
}
```

---

## Phase 4: Calculation Logic Implementation

### 4.0 Payroll Computation Formulas

Based on the existing `weekly_report.php` and `function/report.php` analysis:

#### Basic Pay Calculation
```
Basic Pay = Days Worked × Daily Rate
```

**Days Worked Determination:**
- **Standard:** 8+ hours = 1 day
- **Partial day:** Hours ÷ 8 = decimal days
- **Transfer Scenario** (2 branches same day): 0.5 day per branch = 1 day total

#### Working Hours by Position

| Position | Schedule | Gross Hours | Lunch Break | Actual Work Hours |
|----------|----------|-------------|-------------|-------------------|
| **Worker** | 7:00 AM - 4:00 PM | 9 hours | 12:00 PM - 1:00 PM (excluded) | **8 hours** |
| **Engineer** | 7:00 AM - 4:00 PM | 9 hours | 12:00 PM - 1:00 PM (excluded) | **8 hours** |
| **Admin** | 8:00 AM - 5:00 PM | 9 hours | 12:00 PM - 1:00 PM (excluded) | **8 hours** |
| **Developer** | 8:00 AM - 5:00 PM | 9 hours | 12:00 PM - 1:00 PM (excluded) | **8 hours** |

**Important:** The 1-hour lunch break (12:00 PM - 1:00 PM) is **excluded** from worked hours calculation. If an employee clocks out for lunch and clocks back in, that 1-hour gap is not counted as work time.

**Example - Worker 7 AM to 4 PM with lunch:**
- Clock In: 7:00 AM
- Clock Out (lunch): 12:00 PM = 5 hours
- Clock In (return): 1:00 PM
- Clock Out: 4:00 PM = 3 hours
- **Total Worked Hours:** 5 + 3 = **8 hours** = **1 day**

**Example - Worker 7 AM to 2 PM (left early):**
- Clock In: 7:00 AM
- Clock Out: 2:00 PM
- Total time: 7 hours
- **No lunch break recorded** = 7 hours worked
- **Days:** 7 ÷ 8 = **0.875 days**

#### Overtime Calculation
```
OT Rate = Daily Rate ÷ 8
OT Amount = OT Hours × OT Rate
```
**OT Hours Source:** `overtime_requests` table (approved/pre-approved status only)

#### Gross Pay
```
Gross Pay = Basic Pay + OT Amount
Gross + Allowance = Gross Pay + Performance Allowance
```

#### Government Deductions (Weekly Proration)

**Saturday is PAYDAY** - All deductions are applied on Saturday of each week.

| Week | SSS | PhilHealth | PagIBIG | Payday (Saturday) |
|------|-----|------------|---------|-------------------|
| Week 1 | ₱250 | ₱100 | ₱50 | 1st Saturday of month |
| Week 2 | ₱100 | ₱100 | ₱50 | 2nd Saturday of month |
| Week 3 | ₱100 | ₱50 | ₱100 | 3rd Saturday of month |
| Week 4-5 | ₱0 | ₱0 | ₱0 | 4th/5th Saturday - NO DEDUCTIONS |

**Monthly totals:** SSS ₱450, PhilHealth ₱250, PagIBIG ₱200

**Example:** If April 1 is Tuesday, the Saturdays would be:
- Week 1: April 5 (Saturday) - Deductions applied
- Week 2: April 12 (Saturday) - Deductions applied
- Week 3: April 19 (Saturday) - Deductions applied
- Week 4: April 26 (Saturday) - **NO DEDUCTIONS**

#### Total Deductions
```
Total Deductions = SSS + PhilHealth + PagIBIG + Cash Advance + SSS Loan
```

#### Net Pay (Take Home)
```
Net Pay = Gross Pay + Performance Allowance + OT Amount - Total Deductions
```

#### Attendance Merging Logic (REVISED)
All attendance records from the **same day are merged into one continuous record**:

```
For each employee per day:
1. Find earliest clock-in time across all records
2. Find latest clock-out time across all records
3. Raw Hours = Latest Clock Out - Earliest Clock In
4. If Raw Hours > 8:
     - Billable Hours = 8 (capped)
     - Excess Hours = Raw Hours - 8 (requires approved OT to count)
5. Sum all approved OT hours from overtime_requests table

Result: Single merged record per day with 8-hour cap
```

**Example - Multiple clock-ins merged:**
- Clock In: 6:00 AM, Clock Out: 8:00 AM (2 hrs)
- Clock In: 8:30 AM, Clock Out: 10:00 AM (1.5 hrs)
- Clock In: 10:02 AM, Clock Out: 4:00 PM (5.97 hrs)

**Merged Result WITHOUT approved OT:**
- Raw Hours: 10 hours (6 AM - 4 PM)
- **Billable Hours: 8 hours (capped)**
- Excess: 2 hours (void - no OT approval)
- **Days: 8 ÷ 8 = 1.0 day**

**Merged Result WITH approved OT (2 hrs):**
- Billable Hours: 8 hours
- Approved OT: 2 hours
- **Days: 1.0 day**
- **OT Pay: 2 hrs × (Daily Rate ÷ 8)**

#### Transfer Scenario Detection
```
If (2 unique branches same day) AND (2 separate records not merged):
    Days = 1.0 (0.5 per branch)
Else:
    Calculate based on hours: floor(Total Hours ÷ 8) + remainder/8
```

#### Example Calculation
**Employee:** KELVIN CALDERON | **Daily Rate:** ₱500 | **Days Worked:** 5 | **OT Hours:** 3 | **Week:** 2

| Component | Calculation | Amount |
|-----------|-------------|--------|
| Basic Pay | 5 × ₱500 | ₱2,500.00 |
| OT Rate | ₱500 ÷ 8 | ₱62.50 |
| OT Amount | 3 × ₱62.50 | ₱187.50 |
| **Gross Pay** | ₱2,500 + ₱187.50 | **₱2,687.50** |
| SSS | Week 2 rate | -₱100.00 |
| PhilHealth | Week 2 rate | -₱100.00 |
| PagIBIG | Week 2 rate | -₱50.00 |
| **Total Deductions** | | **-₱250.00** |
| **Net Pay** | ₱2,687.50 - ₱250 | **₱2,437.50** |

---

### 4.1 Week Boundary Calculation (from function/report.php)

**Working Days: Monday - Saturday** (Sunday is the only excluded day)

```php
class WeekBoundaryCalculator {
    public function calculateWeekBoundaries($year, $month, $weekNumber): array {
        // Get all work days Monday-Saturday (exclude only Sundays)
        $workDays = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dayOfWeek = date('w', strtotime($dateStr));
            if ($dayOfWeek != 0) { // Exclude Sundays
                $workDays[] = $day;
            }
        }
        
        // Calculate week boundaries
        $weekBoundaries = [];
        $currentWeek = 1;
        $dayIndex = 0;
        
        while ($dayIndex < count($workDays)) {
            $weekStartDay = $workDays[$dayIndex];
            $daysInThisWeek = 0;
            $weekEndIndex = $dayIndex;
            
            while ($weekEndIndex < count($workDays) && $daysInThisWeek < 5) {
                if ($daysInThisWeek > 0) {
                    $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $workDays[$weekEndIndex]);
                    $prevDate = sprintf('%04d-%02d-%02d', $year, $month, $workDays[$weekEndIndex - 1]);
                    $currentDow = date('w', strtotime($currentDate));
                    $prevDow = date('w', strtotime($prevDate));
                    
                    // New week if Saturday to Monday transition
                    if ($prevDow == 6 && $currentDow == 1) {
                        break;
                    }
                }
                $daysInThisWeek++;
                $weekEndIndex++;
            }
            
            $weekEndDay = $workDays[$weekEndIndex - 1];
            $weekBoundaries[$currentWeek] = [
                'start' => $weekStartDay,
                'end' => $weekEndDay
            ];
            
            $dayIndex = $weekEndIndex;
            $currentWeek++;
        }
        
        return $weekBoundaries[$weekNumber] ?? $weekBoundaries[1];
    }
}
```

### 4.2 Attendance Merging Logic
```php
private function mergeAttendanceRecords($records, $thresholdMinutes = 15): array {
    if (empty($records)) return [];
    
    // Sort by time_in
    usort($records, function($a, $b) {
        return strtotime($a['time_in']) - strtotime($b['time_in']);
    });
    
    $mergedRecords = [];
    $currentMerge = null;
    
    foreach ($records as $record) {
        if ($currentMerge === null) {
            $currentMerge = $record;
        } else {
            $gap = strtotime($record['time_in']) - strtotime($currentMerge['time_out']);
            
            if ($gap < ($thresholdMinutes * 60)) {
                // Merge - extend time_out and accumulate
                $currentMerge['time_out'] = $record['time_out'];
                $currentMerge['hours'] += $record['hours'];
                $currentMerge['ot_hours'] += $record['ot_hours'];
                
                // Track cross-branch if different
                if ($currentMerge['branch'] !== $record['branch']) {
                    $currentMerge['branch'] = $currentMerge['branch'] . '/' . $record['branch'];
                }
            } else {
                $mergedRecords[] = $currentMerge;
                $currentMerge = $record;
            }
        }
    }
    
    if ($currentMerge !== null) {
        $mergedRecords[] = $currentMerge;
    }
    
    return $mergedRecords;
}
```

### 4.3 Government Deduction Proration
```php
private function getProratedDeductions($viewType, $weekNumber): array {
    if ($viewType === 'monthly') {
        return [
            'sss' => self::MONTHLY_SSS,
            'philhealth' => self::MONTHLY_PHILHEALTH,
            'pagibig' => self::MONTHLY_PAGIBIG
        ];
    }
    
    // Weekly proration
    switch ($weekNumber) {
        case 1:
            return [
                'sss' => 250.00,
                'philhealth' => 100.00,
                'pagibig' => 50.00
            ];
        case 2:
            return [
                'sss' => 100.00,
                'philhealth' => 100.00,
                'pagibig' => 50.00
            ];
        case 3:
            return [
                'sss' => 100.00,
                'philhealth' => 50.00,
                'pagibig' => 100.00
            ];
        case 4:
        case 5:
        default:
            return [
                'sss' => 0.00,
                'philhealth' => 0.00,
                'pagibig' => 0.00
            ];
    }
}
```

---

## Phase 5: Implementation Order

### Week 1: Foundation
| Day | Task | Output |
|-----|------|--------|
| 1-2 | Create new database tables | 3 tables created |
| 3-4 | Build PayrollCalculator class | Unit tests passing |
| 5 | Build GovernmentDeductions class | Proration tests passing |

### Week 2: Data Layer
| Day | Task | Output |
|-----|------|--------|
| 6-7 | Build AttendanceAggregator class | Merging logic working |
| 8-9 | Build PayrollEngine class | Integration tests passing |
| 10 | Build models (Adjustment, Status, Period) | CRUD operations working |

### Week 3: API Layer
| Day | Task | Output |
|-----|------|--------|
| 11-12 | Implement getPayroll.php endpoint | Returns correct data |
| 13 | Implement updateAdjustment.php | Saves to database |
| 14 | Implement updatePaymentStatus.php | Status updates working |
| 15 | Implement exportExcel.php | Excel files generating |

### Week 4: Payslip & Testing
| Day | Task | Output |
|-----|------|--------|
| 16-17 | Implement generatePayslip.php | Payslip data correct |
| 18-19 | Build PayslipGenerator class | Bundle generation working |
| 20 | Integration testing | All endpoints tested |
| 21 | Performance optimization | Queries optimized |

**Total: 21 days (3 weeks)**

---

## Phase 6: Testing Checklist

### Unit Tests
- [ ] PayrollCalculator.calculateGrossPay()
- [ ] PayrollCalculator.calculateNetPay()
- [ ] PayrollCalculator.prorateDeductions()
- [ ] AttendanceAggregator.mergeRecords()
- [ ] AttendanceAggregator.identifyTransferScenarios()
- [ ] WeekBoundaryCalculator.calculateWeekBoundaries()

### Integration Tests
- [ ] GET /api/v1/payroll/getPayroll.php - Weekly view
- [ ] GET /api/v1/payroll/getPayroll.php - Monthly view
- [ ] GET /api/v1/payroll/getPayroll.php - Range view
- [ ] POST /api/v1/payroll/updateAdjustment.php
- [ ] POST /api/v1/payroll/updatePaymentStatus.php
- [ ] GET /api/v1/payroll/exportExcel.php
- [ ] GET /api/v1/payroll/generatePayslip.php

### Edge Cases
- [ ] Employee with no attendance
- [ ] Employee with transfer (2 branches same day)
- [ ] Multiple clock-ins within 15 minutes
- [ ] Month with 5 weeks
- [ ] Month starting on Sunday
- [ ] Employee with has_deduction = 0

---

## Key Design Decisions

### 1. Data Source Priority
1. **Primary:** `daily_payroll_reports` - Pre-calculated daily data
2. **Secondary:** `attendance` + calculation - For dates not in DPR
3. **Overtime:** `overtime_requests` - Authoritative approved OT

### 2. Calculation Strategy
- Merge attendance records within 15-minute gap
- Transfer scenario = 0.5 day per branch
- Government deductions prorated weekly
- Minimum 0.5 hours to count as work

### 3. API Design
- RESTful endpoints with JSON
- Consistent response format
- Error handling with HTTP status codes
- Pagination for large datasets

### 4. Security
- Session-based authentication
- Role checking (Admin, Super Admin, Developer)
- Input validation on all endpoints
- Prepared statements for SQL

---

*Backend-only recreation plan for weekly_report.php*
*Generated: April 13, 2026*
*Status: Ready for implementation*
