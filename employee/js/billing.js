
        let currentEmpId, currentEmpName, currentDailyRate, currentMonthlySalary, currentWeeklySalary, currentBillingData;
        let currentViewType = 'weekly';
        let originalPerformanceData = null;

        function openBillingModal(empId, empName, dailyRate, monthlySalary, weeklySalary) {
            try {
                const modalEl = document.getElementById('billingModal');
                if (!modalEl) {
                    console.error('billingModal element not found');
                    return;
                }

                currentEmpId = empId;
                currentEmpName = empName;
                currentDailyRate = dailyRate;
                currentMonthlySalary = monthlySalary;
                currentWeeklySalary = weeklySalary;

                const empNameEl = document.getElementById('empName');
                if (empNameEl) empNameEl.textContent = empName;

                const spinnerEl = document.getElementById('loadingSpinner');
                const contentEl = document.getElementById('billingContent');
                if (spinnerEl) spinnerEl.style.display = 'block';
                if (contentEl) contentEl.style.display = 'none';

                updateBilling();

                if (window.bootstrap && bootstrap.Modal) {
                    const modal = (bootstrap.Modal.getOrCreateInstance)
                        ? bootstrap.Modal.getOrCreateInstance(modalEl)
                        : new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    console.error('Bootstrap Modal is not available');
                }
            } catch (e) {
                console.error('openBillingModal failed:', e);
            }
        }

        function changeViewType(type) {
            currentViewType = type;
            
            document.querySelectorAll('.view-type-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.includes(type.charAt(0).toUpperCase() + type.slice(1))) {
                    btn.classList.add('active');
                }
            });
            
            updateBilling();
        }

        async function updateBilling() {
            if (!currentEmpId) return;
            
            try {
                const response = await fetch(`get_billing_data.php?emp_id=${currentEmpId}&view_type=${currentViewType}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const data = await response.json();
                currentBillingData = data;
                
                // Store original performance data for reset functionality
                originalPerformanceData = { ...data.performance };
                
                // Update form fields with current performance data
                document.getElementById('performanceScore').value = data.performance.performanceScore || 85;
                document.getElementById('performanceBonus').value = data.performance.performanceBonus || 0;
                document.getElementById('performanceRemarks').value = data.performance.remarks || '';
                
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('billingContent').style.display = 'block';
                
                renderDigitalReceipt(data);
                renderDetailedBreakdown(data);
                
            } catch (error) {
                console.error('Error:', error);
                simulateBillingData();
            }
        }

        function simulateBillingData() {
            // Generate dates excluding Sundays
            const attendance = [];
            const today = new Date('2026-01-26');
            
            // Generate last 7 working days (Monday-Saturday)
            let daysAdded = 0;
            let daysBack = 0;
            
            while (daysAdded < 7) {
                const date = new Date(today);
                date.setDate(date.getDate() - daysBack);
                daysBack++;
                
                // Skip Sundays (day 0 = Sunday)
                if (date.getDay() === 0) {
                    continue;
                }
                
                // Determine status
                let status;
                if (daysAdded === 3) {
                    status = 'Late';
                } else if (daysAdded === 5) {
                    status = 'Absent';
                } else {
                    status = 'Present';
                }
                
                attendance.push({
                    attendance_date: date.toISOString().split('T')[0],
                    status: status
                });
                
                daysAdded++;
            }
            
            const presentDays = attendance.filter(r => r.status !== 'Absent').length;
            const computation = {
                totalDays: presentDays,
                gross: currentDailyRate * presentDays,
                dailyRate: currentDailyRate,
                lateCount: attendance.filter(r => r.status === 'Late').length,
                earlyOutCount: 0,
                absentCount: attendance.filter(r => r.status === 'Absent').length
            };
            
            const deductions = {
                sss: 150.00,
                philhealth: 83.33,
                pagibig: 66.67,
                totalDeductions: 300.00
            };
            
            const performance = {
                performanceScore: 85,
                performanceBonus: currentDailyRate * presentDays * 0.02,
                performanceRating: 'Good',
                remarks: ''
            };
            
            const dateRange = currentViewType === 'weekly' 
                ? { start: attendance[attendance.length-1].attendance_date, end: attendance[0].attendance_date }
                : { start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] };
            
            currentBillingData = {
                attendance,
                computation,
                deductions,
                performance,
                dateRange,
                viewType: currentViewType,
                netPay: computation.gross - deductions.totalDeductions + performance.performanceBonus
            };
            
            // Store original performance data
            originalPerformanceData = { ...performance };
            
            // Update form fields
            document.getElementById('performanceScore').value = performance.performanceScore;
            document.getElementById('performanceBonus').value = performance.performanceBonus;
            document.getElementById('performanceRemarks').value = performance.remarks || '';
            
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('billingContent').style.display = 'block';
            
            renderDigitalReceipt(currentBillingData);
            renderDetailedBreakdown(currentBillingData);
        }

        function applyPerformance() {
            const score = parseInt(document.getElementById('performanceScore').value) || 85;
            const bonus = parseFloat(document.getElementById('performanceBonus').value) || 0;
            const remarks = document.getElementById('performanceRemarks').value;
            
            // Update current billing data
            if (currentBillingData && currentBillingData.performance) {
                // Update performance data
                currentBillingData.performance.performanceScore = score;
                currentBillingData.performance.performanceBonus = bonus;
                currentBillingData.performance.remarks = remarks;
                
                // Calculate performance rating based on score
                currentBillingData.performance.performanceRating = getPerformanceRating(score);
                
                // Update net pay with new bonus
                const newNetPay = currentBillingData.computation.gross - 
                                 currentBillingData.deductions.totalDeductions + 
                                 bonus;
                currentBillingData.netPay = newNetPay;
                
                // Save to database (optional)
                savePerformanceToDatabase(currentEmpId, {
                    score: score,
                    bonus: bonus,
                    remarks: remarks,
                    viewType: currentViewType
                });
                
                // Re-render receipt
                renderDigitalReceipt(currentBillingData);
                
                // Show success message
                showNotification('Performance updated successfully!', 'success');
            }
        }

        function resetPerformance() {
            if (originalPerformanceData) {
                // Restore original performance data
                currentBillingData.performance = { ...originalPerformanceData };
                currentBillingData.netPay = currentBillingData.computation.gross - 
                                           currentBillingData.deductions.totalDeductions + 
                                           originalPerformanceData.performanceBonus;
                
                // Update form fields
                document.getElementById('performanceScore').value = originalPerformanceData.performanceScore;
                document.getElementById('performanceBonus').value = originalPerformanceData.performanceBonus;
                document.getElementById('performanceRemarks').value = originalPerformanceData.remarks || '';
                
                // Re-render receipt
                renderDigitalReceipt(currentBillingData);
                
                showNotification('Performance reset to original values', 'info');
            }
        }

        function getPerformanceRating(score) {
            if (score >= 95) return 'Excellent';
            if (score >= 90) return 'Very Good';
            if (score >= 85) return 'Good';
            if (score >= 80) return 'Satisfactory';
            if (score >= 75) return 'Needs Improvement';
            return 'Poor';
        }

        async function savePerformanceToDatabase(empId, performanceData) {
            try {
                const response = await fetch('save_performance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        empId: empId,
                        ...performanceData,
                        date: new Date().toISOString().split('T')[0]
                    })
                });
                
                return await response.json();
            } catch (error) {
                console.error('Error saving performance:', error);
                // Still allow editing even if save fails
            }
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification alert alert-${type === 'success' ? 'success' : 'info'}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.remove()"></button>
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        function renderDigitalReceipt(data) {
            const { computation, deductions, performance, dateRange, payrollWeek } = data;
            const weekNum = payrollWeek || 1;
            const isWeek4 = weekNum === 4;
            const formattedGross = parseFloat(computation.gross).toFixed(2);
            const formattedNet = parseFloat(data.netPay || computation.gross).toFixed(2);
            const currentDate = new Date().toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            const currentTime = new Date().toLocaleTimeString('en-US', { 
                hour12: true, 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            // Check if performance has remarks
            const remarksText = performance.remarks ? `<br><small class="text-muted">Remarks: ${performance.remarks}</small>` : '';
            
            // Week badge style
            const weekBadgeStyle = isWeek4 
                ? 'background: linear-gradient(135deg, #10B981, #059669); color: white;' 
                : 'background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: var(--black);';
            const weekLabel = isWeek4 ? 'Week 4 - FULL PAYOUT (No Deductions)' : `Week ${weekNum} - Deduction Period`;
            
            let receiptHTML = `
                <div class="receipt-header">
                    <div class="receipt-badge" style="${weekBadgeStyle}">${weekLabel}</div>
                    <h4>WEEKLY PAYSLIP</h4>
                    <p class="mb-1"><i class="fas fa-calendar me-2"></i>${currentDate}</p>
                    <p class="mb-1"><i class="fas fa-clock me-2"></i>${currentTime}</p>
                    <p class="mb-0"><i class="fas fa-user me-2"></i><strong>${currentEmpName}</strong></p>
                    <p class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Period: ${dateRange.start} to ${dateRange.end}</p>
                    <p class="mb-0"><i class="fas fa-hashtag me-2"></i>Ref: INV-${Math.floor(Math.random() * 1000000).toString().padStart(6, '0')}</p>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-clock me-2"></i>Total Hours Worked</div>
                    <div class="receipt-right">${(computation.totalHours || 0).toFixed(2)} hrs</div>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-money-bill-wave me-2"></i>Daily Rate</div>
                    <div class="receipt-right">₱${parseFloat(computation.dailyRate || currentDailyRate).toFixed(2)}</div>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-calendar-check me-2"></i>Present Days</div>
                    <div class="receipt-right">${computation.totalDays} days</div>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-clock me-2"></i>Absent Days</div>
                    <div class="receipt-right">${computation.absentCount || 0} days</div>
                </div>
                
                <div class="receipt-item" style="border-bottom: 2px solid var(--gold);">
                    <div class="receipt-left"><i class="fas fa-coins me-2"></i>Gross Pay</div>
                    <div class="receipt-right" style="font-weight: bold;">₱${formattedGross}</div>
                </div>`;
            
            // Deductions section - Weekly shows ÷3 amounts, Monthly shows full amounts
            if (currentViewType === 'weekly') {
                if (isWeek4) {
                    receiptHTML += `
                    <div class="receipt-item">
                        <div class="receipt-left"><i class="fas fa-check-circle me-2"></i><strong>GOVERNMENT DEDUCTIONS</strong></div>
                        <div class="receipt-right text-success"><strong>₱0.00</strong></div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left text-muted">Week 4 - Full Payout (No deductions applied)</div>
                        <div class="receipt-right text-muted">-</div>
                    </div>`;
                } else {
                    receiptHTML += `
                    <div class="receipt-item">
                        <div class="receipt-left"><i class="fas fa-file-invoice me-2"></i><strong>WEEKLY GOVERNMENT DEDUCTIONS (Week ${weekNum}/4)</strong></div>
                        <div class="receipt-right"></div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left">SSS (Monthly ₱450 ÷ 3)</div>
                        <div class="receipt-right">-₱150.00</div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left">PhilHealth (Monthly ₱250 ÷ 3)</div>
                        <div class="receipt-right">-₱83.33</div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left">Pag-IBIG (Monthly ₱200 ÷ 3)</div>
                        <div class="receipt-right">-₱66.67</div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px; border-bottom: 1px dashed var(--light-gray);">
                        <div class="receipt-left"><strong>Total Weekly Deductions</strong></div>
                        <div class="receipt-right"><strong>-₱300.00</strong></div>
                    </div>`;
                }
            } else {
                // Monthly view - show full monthly amounts
                receiptHTML += `
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-file-invoice me-2"></i><strong>MONTHLY GOVERNMENT DEDUCTIONS</strong></div>
                    <div class="receipt-right"></div>
                </div>
                <div class="receipt-item" style="padding-left: 20px;">
                    <div class="receipt-left">SSS Contribution</div>
                    <div class="receipt-right">-₱450.00</div>
                </div>
                <div class="receipt-item" style="padding-left: 20px;">
                    <div class="receipt-left">PhilHealth</div>
                    <div class="receipt-right">-₱250.00</div>
                </div>
                <div class="receipt-item" style="padding-left: 20px;">
                    <div class="receipt-left">Pag-IBIG</div>
                    <div class="receipt-right">-₱200.00</div>
                </div>
                <div class="receipt-item" style="padding-left: 20px; border-bottom: 1px dashed var(--light-gray);">
                    <div class="receipt-left"><strong>Total Monthly Deductions</strong></div>
                    <div class="receipt-right"><strong>-₱900.00</strong></div>
                </div>`;
            }
            
            if (performance) {
                const bonusClass = performance.performanceBonus >= 0 ? 'text-success' : 'text-danger';
                const bonusSign = performance.performanceBonus >= 0 ? '+' : '';
                
                receiptHTML += `
                    <div class="receipt-item">
                        <div class="receipt-left">
                            <i class="fas fa-chart-line me-2"></i>
                            Performance Bonus (${performance.performanceScore}% - ${performance.performanceRating})
                            ${remarksText}
                        </div>
                        <div class="receipt-right ${bonusClass}">
                            ${bonusSign}₱${Math.abs(performance.performanceBonus || 0).toFixed(2)}
                        </div>
                    </div>`;
            }
            
            receiptHTML += `
                <div class="receipt-item total">
                    <div class="receipt-left"><i class="fas fa-hand-holding-usd me-2"></i>NET PAY</div>
                    <div class="receipt-right total-amount">₱${formattedNet}</div>
                </div>
                
                <div class="receipt-footer">
                    <p class="mb-1"><i class="fas fa-shield-alt me-2"></i>This is an official digital receipt</p>
                    <p class="mb-0">*** ${currentViewType === 'weekly' ? 'Weekly' : 'Monthly'} payment processed electronically ***</p>
                    <p class="mb-0"><i class="fas fa-print me-2"></i>Print for your records</p>
                    <p class="print-only">Printed on: ${new Date().toLocaleString()}</p>
                </div>`;
            
            document.getElementById('digitalReceipt').innerHTML = receiptHTML;
        }

        function renderDetailedBreakdown(data) {
            const { attendance } = data;
            let detailsHTML = `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-day me-2"></i>Date</th>
                            <th><i class="fas fa-user-check me-2"></i>Status</th>
                            <th><i class="fas fa-money-bill me-2"></i>Amount</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            attendance.forEach(record => {
                const statusClass = record.status === 'Present' ? 'success' : 
                                  record.status === 'Late' ? 'warning' : 
                                  record.status === 'Early Out' ? 'warning' : 'danger';
                const amount = (record.status === 'Present' || record.status === 'Late' || record.status === 'Early Out') 
                    ? currentDailyRate : 0;
                
                const date = record.attendance_date || record.date;
                
                detailsHTML += `
                    <tr>
                        <td>${new Date(date).toLocaleDateString()}</td>
                        <td>
                            <span class="badge bg-${statusClass}">
                                <i class="fas fa-${record.status === 'Present' ? 'check-circle' : 
                                                record.status === 'Late' ? 'clock' : 
                                                record.status === 'Early Out' ? 'sign-out-alt' : 'times-circle'} me-1"></i>
                                ${record.status}
                            </span>
                        </td>
                        <td class="${amount > 0 ? 'text-success fw-bold' : 'text-muted'}">
                            ${amount > 0 ? '₱' + parseFloat(amount).toFixed(2) : '₱0.00'}
                        </td>
                    </tr>`;
            });
            
            detailsHTML += '</tbody></table>';
            document.getElementById('attendanceDetails').innerHTML = detailsHTML;
        }

        function toggleDetails() {
            const detailsSection = document.getElementById('additionalDetails');
            const detailsButton = document.getElementById('detailsBtn');
            
            if (detailsSection.style.display === 'none') {
                detailsSection.style.display = 'block';
                detailsButton.innerHTML = '<i class="fas fa-eye-slash me-2"></i>Hide Details';
                detailsButton.classList.add('active');
            } else {
                detailsSection.style.display = 'none';
                detailsButton.innerHTML = '<i class="fas fa-eye me-2"></i>View Details';
                detailsButton.classList.remove('active');
            }
        }

        function printReceipt() {
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            const receiptContent = document.getElementById('digitalReceipt').innerHTML;
            const detailsContent = document.getElementById('attendanceDetails').innerHTML;
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${currentViewType === 'weekly' ? 'Weekly' : 'Monthly'} Payslip for ${currentEmpName}</title>
                    <style>
                        @page { margin: 10mm; }
                        body { font-family: Arial, sans-serif; font-size: 12px; padding: 5mm; background: white; color: black; }
                        .receipt-container { border: 2px solid #000; padding: 10mm; margin: 0 auto; page-break-inside: avoid; }
                        .receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
                        .receipt-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #ccc; }
                        .receipt-item.total { border-top: 2px solid #000; font-weight: bold; margin-top: 12px; padding-top: 12px; }
                        .total-amount { font-size: 18px; color: #000; font-weight: bold; }
                        .signature-block { margin-top: 15px; text-align: center; }
                        @media print { 
                            body { margin: 0; padding: 5mm; }
                            .no-print { display: none; }
                        }
                        .text-center { text-align: center; }
                    </style>
                </head>
                <body>
                    <div class="receipt-container">
                        ${receiptContent}
                        <div class="signature-block">
                            <hr>
                            <p><strong>Authorized Signature:</strong></p>
                            <p>_________________________</p>
                            <p>Date: ${new Date().toLocaleDateString()}</p>
                        </div>
                    </div>
                    
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 1000);
                        }
                    <\/script>
                </body>
                </html>
            `);
            
            printWindow.document.close();
        }

        function searchEmployees() {
            const input = document.getElementById('employeeSearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('employeeTableBody');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 0; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[1];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
            
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.3s ease';
                });
            });
        });
