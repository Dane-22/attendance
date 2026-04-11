# Standard Operating Procedure (SOP) - Approval Workflows & Super Admin Oversight

## Document Information
- **Focus:** Approval Processes & Super Admin Oversight Functions
- **For:** Super Admin, Admin (limited), Managers
- **Version:** 1.0
- **Effective Date:** April 2026

---

## Table of Contents
1. [Overview](#1-overview)
2. [Overtime Approval Workflow](#2-overtime-approval-workflow)
3. [Cash Advance Approval Workflow](#3-cash-advance-approval-workflow)
4. [Leave Approval Workflow](#4-leave-approval-workflow)
5. [Transfer Approval Workflow](#5-transfer-approval-workflow)
6. [Super Admin Oversight Functions](#6-super-admin-oversight-functions)
7. [Bulk Operations](#7-bulk-operations)
8. [Audit & Compliance Monitoring](#8-audit--compliance-monitoring)

---

## 1. Overview

### 1.1 Purpose
This SOP covers all approval workflows within the JAJR Attendance & Payroll System, with specific focus on Super Admin oversight functions, approval authority levels, and compliance monitoring.

### 1.2 Approval Authority Matrix

| Request Type | Requestor | Reviewer | Final Approver | System |
|--------------|-----------|----------|------------------|--------|
| **Overtime** | Employee | Supervisor | Super Admin | Required |
| **Cash Advance** | Employee | - | Super Admin | Required |
| **Leave** | Employee | Supervisor | Admin/Super Admin | Required |
| **Transfer** | Employee | Supervisor | Super Admin | Required |
| **Attendance Correction** | Admin | - | Super Admin (>3 days) | Logged |
| **Payroll Override** | Admin | - | Super Admin | Logged |

### 1.3 Notification Center

**Navigate to:** `employee/notification.php`

**Dashboard Shows:**
- Pending overtime requests count
- Pending cash advance requests count
- Pending leave requests count
- Pending transfer requests count
- Quick action buttons

---

## 2. Overtime Approval Workflow

### 2.1 Request Submission

**By Employee:**
1. Navigate to Overtime page
2. Click "Request Overtime"
3. Complete form:
   ```
   - Date: [Specific date]
   - Expected Hours: [Number]
   - Reason: [Business justification]
   - Type: Regular/Night/Rest Day/Holiday
   ```
4. Submit request
5. System assigns "Pending" status
6. Notifications sent to supervisors

### 2.2 First-Level Review (Supervisor)

**Responsibilities:**
- Verify business need
- Check operational requirements
- Confirm budget availability
- Preliminary approval/rejection

**Actions:**
- Review request details
- Add supervisor comments
- Forward to Super Admin with recommendation
- Or reject with reason

### 2.3 Final Approval (Super Admin)

**Navigate to:** Notification Center → Overtime Tab

**Review Checklist:**
- [ ] Request within policy limits
- [ ] Hours reasonable for work type
- [ ] Budget code available
- [ ] Employee eligible (no recent issues)
- [ ] Compliance with labor laws
- [ ] Proper documentation

**Approval Process:**
1. Open pending OT request
2. Review all details
3. Check employee history
4. Verify reason valid
5. **APPROVE:**
   - Click "Approve"
   - Add approval notes (optional)
   - Confirm
   - System updates status to "Approved"
   - Employee notified
   - Auto-included in payroll

6. **REJECT:**
   - Click "Reject"
   - Provide detailed reason
   - Suggest alternative if applicable
   - Confirm
   - System updates status to "Rejected"
   - Employee notified with reason

### 2.4 Pre-Approved Overtime

**For Urgent/Emergency OT:**
- Supervisor authorizes verbally
- Employee works overtime
- Admin marks OT in attendance record
- Select "Pre-approved" status
- Super Admin reviews post-facto
- Document reason for pre-approval

### 2.5 Overtime Rate Verification

**Verify Correct Rate Applied:**

| Type | Rate | Verification |
|------|------|--------------|
| Regular OT | 1.25x | Standard after 8 hours |
| Night Diff | +10% | 10 PM - 6 AM |
| Rest Day | 1.3x | Non-working day |
| Holiday | 2.0x | Official holiday |

**Check Calculation:**
```
Hourly Rate = Daily Rate ÷ 8
OT Pay = OT Hours × Hourly Rate × Multiplier
```

---

## 3. Cash Advance Approval Workflow

### 3.1 Request Submission

**By Employee:**
1. Navigate to Cash Advance page
2. Click "Request Cash Advance"
3. Complete form:
   ```
   - Amount Requested: [Amount]
   - Reason: [Detailed explanation]
   - Repayment Preference: [Payroll deduction/other]
   ```
4. Submit digital signature
5. Submit request
6. Status: "Pending"

### 3.2 Eligibility Check (Auto + Manual)

**System Auto-Checks:**
- Current CA balance < ₱5,000
- Amount ≤ 50% of monthly salary
- No recent rejections
- Account in good standing

**Super Admin Manual Check:**
- Employee tenure
- Repayment history
- Reason validity
- Current financial status

### 3.3 Approval Decision

**Navigate to:** Notification Center → Cash Advance Tab

**APPROVE Process:**
1. Review request details
2. Check CA history
3. Verify eligibility
4. Click "Approve"
5. Set deduction terms:
   - Single pay period
   - Multiple installments
   - Custom schedule
6. Confirm approval
7. Employee notified
8. Deduction scheduled in payroll

**REJECT Process:**
1. Review request
2. Identify reason for rejection:
   - Exceeds limit
   - Outstanding balance too high
   - Insufficient justification
   - Recent repayment issues
3. Click "Reject"
4. Provide detailed explanation
5. Offer alternative if possible
6. Confirm rejection
7. Employee notified

### 3.4 Special Approvals

**Emergency Cash Advance:**
- Medical emergency
- Family crisis
- Other urgent situations

**Process:**
- Employee contacts Super Admin directly
- Verbal approval possible
- Document after the fact
- Expedited processing
- May exceed normal limits (document reason)

**Partial Approval:**
- Request ₱5,000
- Approve ₱3,000 only
- Explain why partial
- Offer remainder later

### 3.5 Deduction Management

**Setting Up Deduction:**
1. Upon approval, access payroll preview
2. Add CA deduction line
3. Specify:
   - Amount per pay period
   - Number of periods
   - Start date
4. Employee sees deduction in payroll

**Early Repayment:**
- Employee pays cash/bank
- Record manual payment
- Update balance
- Stop payroll deduction if fully paid

---

## 4. Leave Approval Workflow

### 4.1 Request Submission

**By Employee:**
1. Navigate to Leave section
2. Select leave type:
   - Sick Leave
   - Vacation Leave
   - Emergency Leave
3. Select date(s)
4. Provide reason
5. Attach supporting docs (if required)
6. Submit

### 4.2 Balance Verification

**System Checks:**
- Available leave balance
- Overlapping with other leave
- Blackout dates (if any)
- Maximum consecutive days

### 4.3 Approval Levels

**Level 1 - Supervisor (Most leaves):**
- Reviews operational impact
- Checks coverage
- Approves routine requests

**Level 2 - Admin/Super Admin (Extended or special):**
- >5 consecutive days
- During critical periods
- Special circumstances

### 4.4 Approval Process

**APPROVE:**
1. Check leave balance sufficient
2. Verify dates acceptable
3. Confirm coverage arranged
4. Click "Approve"
5. Deduct from balance
6. Mark calendar
7. Notify employee

**REJECT:**
1. Explain operational need
2. Suggest alternative dates
3. Click "Reject"
4. Provide reason
5. Return balance if applicable

### 4.5 Sick Leave Special Handling

**>2 Days Requires:**
- Medical certificate
- Doctor's note
- Valid reason documentation

**Process:**
1. Employee submits request
2. Marks "Medical certificate to follow"
3. Provisional approval (1-2 days)
4. Employee submits certificate
5. Full approval granted
6. If no certificate, may be converted to unpaid leave

---

## 5. Transfer Approval Workflow

### 5.1 Request Types

**Temporary Transfer:**
- 1 day to 2 weeks
- Specific project need
- Returns to home site

**Permanent Transfer:**
- Long-term reassignment
- Changes home branch
- Full transfer

### 5.2 Request Submission

**By Employee/Admin:**
1. Navigate to Transfer Module
2. Click "Request Transfer"
3. Complete form:
   ```
   - From Branch: [Current]
   - To Branch: [Destination]
   - Transfer Date: [Date]
   - Duration: [Temporary/Permanent]
   - Reason: [Justification]
   ```
4. Submit

### 5.3 Review Process

**Step 1 - Current Supervisor:**
- Approves release
- Confirms handover plan
- Forwards request

**Step 2 - Receiving Supervisor:**
- Confirms acceptance
- Verifies need
- Approves incoming

**Step 3 - Super Admin Final:**
- Reviews both approvals
- Checks payroll implications
- Verifies dates
- Makes final decision

### 5.4 Approval Actions

**APPROVE:**
1. Both supervisors approved
2. Super Admin reviews
3. Click "Approve"
4. System:
   - Updates employee branch (if permanent)
   - Logs transfer record
   - Updates payroll location
   - Notifies both branches
5. Employee executes transfer on date

**REJECT:**
1. Identify reason:
   - No operational need
   - Staffing issues at current site
   - Receiving site can't accommodate
   - Timing inappropriate
2. Click "Reject"
3. Explain to requestor
4. Suggest alternative if possible

### 5.5 Day-of Transfer (Multi-Site Work)

**No Pre-Approval Needed:**
1. Employee works morning at Site A
2. Clocks out at Site A
3. Goes to Site B
4. Clocks in at Site B
5. System auto-detects and records
6. Payroll splits: 0.5 day each

**Note:** If frequent, should formalize as temporary transfer.

---

## 6. Super Admin Oversight Functions

### 6.1 Daily Oversight Checklist

**Morning (8:00 AM):**
- [ ] Review pending approvals count
- [ ] Check overnight system logs
- [ ] Review failed cron jobs
- [ ] Check for error reports

**Afternoon (2:00 PM):**
- [ ] Process approval queue
- [ ] Review admin actions from morning
- [ ] Check attendance corrections log
- [ ] Respond to escalated issues

**End of Day (5:00 PM):**
- [ ] Final approval queue check
- [ ] Review system status
- [ ] Check for urgent items

### 6.2 Approval Queue Management

**Prioritization:**
1. **URGENT (Process within 1 hour):**
   - Emergency cash advances
   - Critical overtime for deadline
   - Same-day transfer needs

2. **STANDARD (Process within 24 hours):**
   - Regular overtime
   - Normal cash advances
   - Planned transfers

3. **ROUTINE (Process within 48 hours):**
   - Leave requests
   - Non-urgent items

### 6.3 Override Authority

**When to Override:**
- System error prevents normal process
- Emergency situation
- Policy exception justified
- Technical issue blocking workflow

**Override Process:**
1. Document reason for override
2. Perform override action
3. Log detailed explanation
4. Notify relevant parties
5. Review for process improvement

**Examples:**
- Override geofence for field employee
- Approve CA beyond limit for emergency
- Manually mark attendance for system-down period
- Force payroll recalculation

### 6.4 Exception Handling

**Document All Exceptions:**
- Employee name
- Date of exception
- Normal policy/rule
- Exception granted
- Business justification
- Approving authority

**Review Exceptions Monthly:**
- Pattern analysis
- Policy adjustment needs
- Training opportunities

---

## 7. Bulk Operations

### 7.1 Bulk Attendance Correction

**When Needed:**
- System outage affected multiple employees
- Policy change retroactive application
- Holiday declaration retroactive

**Process:**
1. Identify affected employees
2. Prepare list with correct data
3. Use bulk correction tool
4. Preview changes
5. Execute corrections
6. Generate report
7. Notify affected employees
8. Log bulk operation

### 7.2 Bulk Payroll Adjustment

**When Needed:**
- Rate changes effective retroactively
- Policy change affecting calculations
- Correction of systemic error

**Process:**
1. Calculate adjustments needed
2. Prepare adjustment file
3. Run in test mode first
4. Review test results
5. Execute in production (off-hours)
6. Verify results
7. Generate correction reports
8. Notify Finance department

### 7.3 Bulk Employee Updates

**When Needed:**
- Branch closures (mass transfers)
- Position reclassifications
- Policy changes affecting all

**Process:**
1. Prepare update criteria
2. Create backup of affected records
3. Test update on sample
4. Execute bulk update
5. Verify changes
6. Generate audit trail
7. Communicate to employees

---

## 8. Audit & Compliance Monitoring

### 8.1 Approval Audit Trail

**System Logs:**
- Who approved/rejected
- Timestamp
- Request details
- Decision reason
- Any modifications

**Monthly Review:**
1. Generate approval summary report
2. Review rejection rates by type
3. Check for patterns
4. Verify compliance with policies
5. Identify training needs

### 8.2 Compliance Checks

**Verify:**
- All overtime properly approved
- No cash advance exceeds limits (without exception)
- Leave balances accurate
- Transfers properly documented
- All actions logged

**Red Flags:**
- High rejection rate (training needed)
- Frequent overrides (process issue)
- Same-day approvals (rush requests)
- Missing documentation

### 8.3 Reporting to Management

**Weekly Summary:**
- Total approvals by type
- Average approval time
- Rejection summary
- Exception count

**Monthly Report:**
- Trends analysis
- Policy compliance rate
- Training recommendations
- System improvement suggestions

---

## Quick Reference: Approval Actions

### Approval Shortcuts

| Action | Location | Shortcut |
|--------|----------|----------|
| View Pending OT | Notification Center | Ctrl+Shift+O |
| View Pending CA | Notification Center | Ctrl+Shift+C |
| View All Pending | Dashboard | Click bell icon |
| Quick Approve | Any request | Approve button |
| Bulk Actions | Notification Center | Select multiple |

### Approval Limits Reference

| Type | Standard Limit | Exception Possible |
|------|----------------|-------------------|
| OT Hours/Day | 4 hours | 8 hours (emergency) |
| CA Amount | 50% monthly | 100% (documented emergency) |
| Leave Days | Balance available | Negative (special approval) |
| Transfer Duration | 2 weeks | 3 months (project) |

---

## Contact & Escalation

### Approval Issues

| Issue | Contact | Timeframe |
|-------|---------|-----------|
| System won't allow approval | Developer | Immediate |
| Policy unclear | HR Manager | 24 hours |
| Budget questions | Finance | 24 hours |
| Urgent after-hours | On-call Super Admin | Immediate |

---

**Document Version:** 1.0  
**Last Updated:** April 2026  
**Next Review:** July 2026

---

**END OF APPROVAL WORKFLOWS SOP**
