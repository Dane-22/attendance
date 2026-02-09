
        // Toggle sidebar on mobile
        const menuToggle = document.querySelector('.menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && sidebar && menuToggle) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = menuToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768 && sidebar) {
                sidebar.classList.remove('active');
            }
        });

        // Print functionality
        function printReport() {
            window.print();
        }

        // Change view type (weekly/monthly)
        function changeView(viewType) {
            const url = new URL(window.location.href);
            url.searchParams.set('view', viewType);
            
            // Reset week to 1 when switching to monthly view
            if (viewType === 'monthly') {
                url.searchParams.delete('week');
            }
            
            window.location.href = url.toString();
        }

        // Export to Excel functionality with borders
        function exportToExcel() {
            const table = document.getElementById('reportTable');
            const tbody = table.querySelector('tbody');
            const dataRows = tbody.querySelectorAll('tr:not(:last-child)');
            const totalRow = tbody.querySelector('tr:last-child');
            
            // Build worksheet data
            let wsData = [];
            
            // Title rows
            wsData.push(['JAJR SECURITY SERVICES, INC.']);
            wsData.push(['PAYROLL PERIOD: <?php echo ($view_type === "weekly") ? "WEEK $selected_week - " : ""; ?><?php echo strtoupper(date('F Y', strtotime($year . "-" . $month . "-01"))); ?>']);
            wsData.push([]);
            
            // Headers - must match data structure exactly (21 columns)
            // Data structure: [EMPLOYEE, DAYS, HRS, RATE, BASIC, OT_HRS, OT_AMT, GROSS, PERF_ALLOW, '', GROSS_ALLOW, '', CA, SSS, PHIC, HDMF, SSS_LOAN, TOTAL, TAKE_HOME, '', SIGNATURE]
            wsData.push(['EMPLOYEE', 'DAYS WORKED', '', 'DAILY RATE', 'BASIC PAY', 'OVERTIME', '', 'GROSS PAY', 'PERFORMANCE ALLOWANCE', '', 'GROSS + ALLOWANCE', '', 'CA', 'SSS', 'PHIC', 'HDMF', 'SSS LOAN', 'TOTAL DEDUCTIONS', 'TAKE HOME PAY', '', 'SIGNATURE']);
            wsData.push(['', 'days', 'hrs', '', '', 'hrs', 'amt', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
            wsData.push([]);
            
            // Data rows
            dataRows.forEach((row, rowIdx) => {
                const cells = row.querySelectorAll('td');
                if (cells.length < 17) {
                    console.log('Row', rowIdx, 'skipped - only', cells.length, 'cells');
                    return;
                }
                
                // Debug: Log cell values
                console.log('Row', rowIdx, 'Cell 7 (Gross Pay):', cells[7]?.textContent?.trim());
                console.log('Row', rowIdx, 'Cell 9 (Gross+Allowance):', cells[9]?.textContent?.trim());
                console.log('Row', rowIdx, 'Cell 16 (Take Home):', cells[16]?.textContent?.trim());
                
                // Get allowance from input or text (Performance Allowance)
                const allowanceVal = cells[8].querySelector('input') ? cells[8].querySelector('input').value : cells[8].textContent.replace(/,/g, '').trim();
                
                // Get CA from input or text
                const caVal = cells[10].querySelector('input') ? cells[10].querySelector('input').value : cells[10].textContent.replace(/,/g, '').trim();
                
                // Map data to match header structure
                // Header: [EMPLOYEE, DAYS WORKED, '', DAILY RATE, BASIC PAY, OVERTIME, '', GROSS PAY, PERFORMANCE, '', GROSS +, '', CA, SSS, PHIC, HDMF, SSS LOAN, TOTAL, TAKE HOME, '', SIGNATURE]
                const rowData = [
                    cells[0].textContent.trim(),           // 0: EMPLOYEE
                    cells[1].textContent.trim(),           // 1: DAYS WORKED (days)
                    cells[2].textContent.trim(),           // 2: DAYS WORKED (hrs)
                    cells[3].textContent.replace(/,/g, '').trim(), // 3: DAILY RATE
                    cells[4].textContent.replace(/,/g, '').trim(), // 4: BASIC PAY
                    cells[5].textContent.trim(),           // 5: OVERTIME (hrs)
                    cells[6].textContent.replace(/,/g, '').trim(), // 6: OVERTIME (amt)
                    cells[7].textContent.replace(/,/g, '').trim(), // 7: GROSS PAY
                    allowanceVal,                          // 8: PERFORMANCE ALLOWANCE
                    '',                                    // 9: empty spacer
                    cells[9].textContent.replace(/,/g, '').trim(), // 10: GROSS + ALLOWANCE
                    '',                                    // 11: empty spacer
                    caVal,                                 // 12: CA
                    cells[11].textContent.replace(/,/g, '').replace('-', '0').trim(), // 13: SSS
                    cells[12].textContent.replace(/,/g, '').replace('-', '0').trim(), // 14: PHIC
                    cells[13].textContent.replace(/,/g, '').replace('-', '0').trim(), // 15: HDMF
                    cells[14].textContent.replace(/,/g, '').replace('-', '0').trim(), // 16: SSS LOAN
                    cells[15].textContent.replace(/,/g, '').trim(), // 17: TOTAL
                    cells[16].textContent.replace(/,/g, '').trim(), // 18: TAKE HOME PAY
                    '',                                    // 19: empty spacer
                    ''                                     // 20: SIGNATURE
                ];
                
                // Debug: Log the rowData being pushed
                console.log('Row', rowIdx, 'rowData[7] (Gross Pay):', rowData[7]);
                console.log('Row', rowIdx, 'rowData[10] (Gross+Allowance):', rowData[10]);
                console.log('Row', rowIdx, 'rowData[18] (Take Home):', rowData[18]);
                
                wsData.push(rowData);
            });
            
            // Total row
            if (totalRow) {
                const t = totalRow.querySelectorAll('td');
                console.log('Total row has', t.length, 'cells');
                console.log('t[15] (Total Deductions) raw text:', t[15]?.textContent);
                console.log('t[16] (Take Home) raw text:', t[16]?.textContent);
                if (t.length >= 18) {
                    // Calculate sum of Total Deductions column (t[15])
                    const totalDeductionsValue = t[15].textContent.replace(/,/g, '').trim();
                    const totalDeductionsNum = parseFloat(totalDeductionsValue) || 0;
                    console.log('Total Deductions parsed value:', totalDeductionsNum);
                    
                    wsData.push([
                        'TOTAL',                                // 0
                        t[1].textContent.trim(),                // 1: total days
                        t[2].textContent.trim(),                // 2: total hours
                        '',                                     // 3
                        t[4].textContent.replace(/,/g, '').trim(),    // 4: total gross
                        t[5].textContent.trim(),                // 5: total OT hrs
                        t[6].textContent.replace(/,/g, '').trim(),    // 6: total OT amt
                        t[7].textContent.replace(/,/g, '').trim(),    // 7: gross + OT
                        t[8].textContent.replace(/,/g, '').trim(),    // 8: total allowance
                        '',                                     // 9
                        t[9].textContent.replace(/,/g, '').trim(),    // 10: gross + allowance
                        '',                                     // 11
                        t[10].textContent.replace(/,/g, '').replace('-', '0').trim(), // 12: CA
                        t[11].textContent.replace(/,/g, '').replace('-', '0').trim(), // 13: SSS
                        '-', // 14: PHIC (show dash in total)
                        '-', // 15: HDMF (show dash in total)
                        '-', // 16: SSS Loan (show dash in total)
                        totalDeductionsNum.toString(), // 17: Total Deductions (sum) - always show number
                        t[16].textContent.replace(/,/g, '').trim(),                   // 18: Take Home
                        '',                                     // 19
                        ''                                      // 20
                    ]);
                }
            }
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            
            // Define border style
            const borderStyle = {
                top: { style: 'thin', color: { rgb: '000000' } },
                bottom: { style: 'thin', color: { rgb: '000000' } },
                left: { style: 'thin', color: { rgb: '000000' } },
                right: { style: 'thin', color: { rgb: '000000' } }
            };
            
            const boldBorderStyle = {
                top: { style: 'medium', color: { rgb: '000000' } },
                bottom: { style: 'medium', color: { rgb: '000000' } },
                left: { style: 'medium', color: { rgb: '000000' } },
                right: { style: 'medium', color: { rgb: '000000' } }
            };
            
            // Apply borders to all cells
            const range = XLSX.utils.decode_range(ws['!ref']);
            for (let R = 3; R <= range.e.r; R++) {
                for (let C = 0; C <= range.e.c; C++) {
                    const cellRef = XLSX.utils.encode_cell({ r: R, c: C });
                    if (!ws[cellRef]) ws[cellRef] = { v: '' };
                    if (!ws[cellRef].s) ws[cellRef].s = {};
                    
                    // Bold header rows
                    if (R === 3 || R === 4 || R === 5) {
                        ws[cellRef].s.font = { bold: true };
                        ws[cellRef].s.border = boldBorderStyle;
                    } else if (R === range.e.r) {
                        // Bold total row
                        ws[cellRef].s.font = { bold: true };
                        ws[cellRef].s.border = borderStyle;
                    } else {
                        ws[cellRef].s.border = borderStyle;
                    }
                }
            }
            
            // Set column widths
            ws['!cols'] = [
                { wch: 25 }, // Employee
                { wch: 8 }, { wch: 6 }, // Days worked
                { wch: 10 }, // Daily rate
                { wch: 10 }, // Basic pay
                { wch: 6 }, { wch: 8 }, // Overtime
                { wch: 10 }, // Gross pay
                { wch: 10 }, { wch: 2 }, // Performance allowance
                { wch: 10 }, { wch: 2 }, // Gross + allowance
                { wch: 8 }, { wch: 8 }, { wch: 8 }, { wch: 8 }, { wch: 10 }, { wch: 10 }, // Deductions
                { wch: 12 }, { wch: 2 }, // Take home pay
                { wch: 12 } // Signature
            ];
            
            // Create workbook and download
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Payroll Report');
            XLSX.writeFile(wb, 'payroll_report_<?php echo $selected_month; ?>_week<?php echo $selected_week; ?><?php echo ($selected_branch !== "all") ? "_" . $selected_branch : ""; ?>.xlsx');
        }

        // Auto-refresh on view change
        document.addEventListener('DOMContentLoaded', function() {
            const viewSelect = document.querySelector('select[name="view"]');
            if (viewSelect) {
                viewSelect.addEventListener('change', function() {
                    const form = this.closest('form');
                    form.submit();
                });
            }

            const employeeSearch = document.getElementById('employeeSearch');
            if (employeeSearch) {
                employeeSearch.addEventListener('input', function() {
                    const q = (this.value || '').trim().toLowerCase();
                    const tbody = document.querySelector('#reportTable tbody');
                    if (!tbody) return;

                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach((row, idx) => {
                        if (idx === rows.length - 1) return;
                        const text = (row.textContent || '').toLowerCase();
                        row.style.display = !q || text.includes(q) ? '' : 'none';
                    });
                });
            }
        });

        // Update calculations when CA input changes
        function updateCalculations(empId) {
            const caInput = document.getElementById('ca_' + empId);
            const caValue = parseFloat(caInput.value) || 0;
            
            // Get base values from the row (stored as data attributes)
            const row = caInput.closest('tr');
            
            // Find all cells in the row
            const cells = row.querySelectorAll('td');
            
            // Get values from cells (indices based on table structure)
            const grossPayText = cells[4].textContent.replace(/,/g, '');
            const grossPay = parseFloat(grossPayText) || 0;
            
            const otAmountText = cells[6].textContent.replace(/,/g, '');
            const otAmount = parseFloat(otAmountText) || 0;
            
            const allowanceInput = document.getElementById('allowance_' + empId);
            const allowance = parseFloat(allowanceInput.value) || 0;
            
            // Get deduction values
            const sssText = cells[11].textContent.replace(/,/g, '').replace('-', '0');
            const sss = parseFloat(sssText) || 0;
            
            const phicText = cells[12].textContent.replace(/,/g, '').replace('-', '0');
            const phic = parseFloat(phicText) || 0;
            
            const hdmfText = cells[13].textContent.replace(/,/g, '').replace('-', '0');
            const hdmf = parseFloat(hdmfText) || 0;
            
            const sssLoanText = cells[14].textContent.replace(/,/g, '').replace('-', '0');
            const sssLoan = parseFloat(sssLoanText) || 0;
            
            // Calculate totals
            const grossPlusAllowance = grossPay + allowance + otAmount;
            const totalDeductions = sss + phic + hdmf + caValue + sssLoan;
            const takeHome = grossPlusAllowance - totalDeductions;
            
            // Update Total Deductions cell (index 15)
            cells[15].textContent = numberFormat(totalDeductions);
            
            // Update Take Home cell (index 16)
            cells[16].textContent = numberFormat(takeHome);
            
            // Update the grand totals
            updateGrandTotals();
        }
        
        // Format number helper
        function numberFormat(num) {
            return Math.round(num).toLocaleString();
        }
        
        // Update grand total row
        function updateGrandTotals() {
            const allCAInputs = document.querySelectorAll('.ca-input');
            const allAllowanceInputs = document.querySelectorAll('.allowance-input');
            let totalCA = 0;
            let totalAllowance = 0;
            
            allCAInputs.forEach(input => {
                totalCA += parseFloat(input.value) || 0;
            });
            
            allAllowanceInputs.forEach(input => {
                totalAllowance += parseFloat(input.value) || 0;
            });
            
            // Get base totals from the total row
            const totalGross = parseFloat(document.getElementById('totalGross')?.textContent.replace(/,/g, '')) || 0;
            const totalOT = parseFloat(document.getElementById('totalOTAmount')?.textContent.replace(/,/g, '')) || 0;
            const baseDeductions = parseFloat(document.getElementById('grandTotalDeductions')?.textContent.replace(/,/g, '')) || 0;
            
            // Calculate grand totals
            const grandTotalDeductions = baseDeductions + totalCA;
            const grandTakeHome = totalGross + totalAllowance + totalOT - grandTotalDeductions;
            
            // Update total row cells
            const totalCAElement = document.getElementById('totalCA');
            if (totalCAElement) {
                totalCAElement.textContent = totalCA > 0 ? numberFormat(totalCA) : '-';
            }
            
            const totalAllowanceElement = document.getElementById('totalAllowance');
            if (totalAllowanceElement) {
                totalAllowanceElement.textContent = numberFormat(totalAllowance);
            }
            
            const totalGrossPlusAllowanceElement = document.getElementById('totalGrossPlusAllowance');
            if (totalGrossPlusAllowanceElement) {
                totalGrossPlusAllowanceElement.textContent = numberFormat(totalGross + totalAllowance + totalOT);
            }
            
            const grandTotalDeductionsElement = document.getElementById('grandTotalDeductions');
            if (grandTotalDeductionsElement) {
                grandTotalDeductionsElement.textContent = numberFormat(grandTotalDeductions);
            }
            
            const grandTakeHomeElement = document.getElementById('grandTakeHome');
            if (grandTakeHomeElement) {
                grandTakeHomeElement.textContent = numberFormat(grandTakeHome);
            }
        }