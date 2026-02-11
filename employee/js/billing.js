
        // Auto-refresh dashboard data every 60 seconds
        setInterval(function() {
            fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                // Parse the response and update only the summary numbers
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newNumbers = doc.querySelectorAll('.summary-number');
                const currentNumbers = document.querySelectorAll('.summary-number');
                
                newNumbers.forEach((newNum, index) => {
                    if (currentNumbers[index] && newNum.textContent !== currentNumbers[index].textContent) {
                        currentNumbers[index].textContent = newNum.textContent;
                        currentNumbers[index].style.animation = 'none';
                        currentNumbers[index].offsetHeight; // Trigger reflow
                        currentNumbers[index].style.animation = 'pulse 0.5s ease';
                    }
                });
            })
            .catch(error => console.log('Refresh error:', error));
        }, 60000);

        // Add pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        `;
        document.head.appendChild(style);

        // Mobile sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const mobileOpenBtn = document.getElementById('mobileOpenBtn');
        
        if (mobileOpenBtn && sidebar) {
            mobileOpenBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
            });
        }
