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
function exportToExcel(buttonEl) {
    const table = document.getElementById('reportTable');
    if (!table) return;

    // If SheetJS is available (weekly_report.php includes it), export client-side
    if (typeof XLSX !== 'undefined' && XLSX.utils && XLSX.writeFile) {
        try {
            const wb = XLSX.utils.book_new();

            // Convert table -> worksheet
            const ws = XLSX.utils.table_to_sheet(table, {
                raw: true,
                display: true
            });

            // Basic styling: apply thin borders + header fill where possible
            const range = XLSX.utils.decode_range(ws['!ref'] || 'A1:A1');
            for (let R = range.s.r; R <= range.e.r; ++R) {
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    const cellAddress = XLSX.utils.encode_cell({ r: R, c: C });
                    const cell = ws[cellAddress];
                    if (!cell) continue;
                    cell.s = cell.s || {};
                    cell.s.border = {
                        top: { style: 'thin', color: { rgb: '999999' } },
                        bottom: { style: 'thin', color: { rgb: '999999' } },
                        left: { style: 'thin', color: { rgb: '999999' } },
                        right: { style: 'thin', color: { rgb: '999999' } }
                    };
                    if (R === 0 || R === 1) {
                        cell.s.fill = { patternType: 'solid', fgColor: { rgb: '1F2937' } };
                        cell.s.font = { bold: true, color: { rgb: 'FFFFFF' } };
                    }
                }
            }

            XLSX.utils.book_append_sheet(wb, ws, 'Payroll');

            const url = new URL(window.location.href);
            const view = url.searchParams.get('view') || 'weekly';
            const month = url.searchParams.get('month') || '';
            const week = url.searchParams.get('week') || '';
            const branch = url.searchParams.get('branch') || 'all';
            const filename = `payroll_${view}_${month}${week ? `_week${week}` : ''}_${branch}.xlsx`;

            XLSX.writeFile(wb, filename);
            return;
        } catch (e) {
            console.error('SheetJS export failed, falling back:', e);
        }
    }

    // Fallback: if there is an export form/flag-based export, submit it
    let form = null;
    if (buttonEl && buttonEl.closest) {
        form = buttonEl.closest('form');
    }
    if (!form) {
        form = document.getElementById('filterForm');
    }
    if (form) {
        const hasExportFlag = !!form.querySelector('input[name="export"], input[name="export_excel"], input[name="exportToExcel"], input[name="export_to_excel"]');
        if (hasExportFlag) {
            form.submit();
            return;
        }
    }

    // Last resort
    window.print();
}

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