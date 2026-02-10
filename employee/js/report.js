
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
            const form = this.closest('form');
            form.submit();
        }

        // Employee search functionality
        document.addEventListener('DOMContentLoaded', function() {
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