// student.js - Full Auto-Refresh System (Sees Updates Instantly)

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();

    const navItems = document.querySelectorAll('.nav-item');
    const views = document.querySelectorAll('.view');
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
    const notificationIcon = document.querySelector('.notification-icon');
    const notificationModal = document.getElementById('notificationModal');
    const closeNotificationBtn = notificationModal?.querySelector('.close-btn');

    let autoRefreshInterval = null;

    // --- Authentication Check ---
    async function checkAuth() {
        try {
            const response = await fetch('../auth/check-session.php');
            const result = await response.json();
            
            if (!result.success || result.data.role !== 'student') {
                window.location.href = '../index.html';
                return;
            }
            
            loadAllData();
            startAutoRefresh();
        } catch (error) {
            console.error('Auth Check Error:', error);
            window.location.href = '../index.html';
        }
    }

    // --- MASTER REFRESH FUNCTION ---
    function loadAllData() {
        loadDashboardStats();
        loadPayments();
        loadAttendance();
        loadPenalties();
        loadNotifications();
    }

    // --- START AUTO-REFRESH EVERY 5 SECONDS ---
    function startAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        // Auto-refresh data every 5 seconds
        autoRefreshInterval = setInterval(() => {
            if (!document.querySelector('.modal.show')) {
                refreshCurrentView();
            }
        }, 5000); // 5 seconds
    }

    function refreshCurrentView() {
        const activeView = document.querySelector('.view.active');
        if (!activeView) return;

        const viewId = activeView.id;
        
        if (viewId === 'dashboardView') {
            loadDashboardStats();
        } else if (viewId === 'paymentsView') {
            loadPayments();
        } else if (viewId === 'attendanceView') {
            loadAttendance();
        } else if (viewId === 'penaltiesView') {
            loadPenalties();
        }
    }

    // --- Load Dashboard Statistics ---
    async function loadDashboardStats() {
        try {
            const response = await fetch('../api/student/dashboard-stats.php');
            const result = await response.json();
            
            if (result.success) {
                const stats = result.data;
                
                const amountEl = document.querySelector('.metric-value.amount');
                if (amountEl) {
                    amountEl.textContent = `₱${parseFloat(stats.total_paid).toFixed(2)}`;
                }
                
                const attendanceEl = document.querySelector('.metric-value:not(.amount):not(.penalty)');
                if (attendanceEl) {
                    attendanceEl.textContent = `${stats.events_attended}/${stats.total_events}`;
                }
                
                const penaltyEl = document.querySelector('.metric-value.penalty');
                if (penaltyEl) {
                    penaltyEl.textContent = stats.pending_penalties > 0 ? 
                        `${stats.pending_penalties} PENDING` : '0 PENDING';
                }

                // Update welcome message timestamp
                const welcomeDiv = document.querySelector('.dashboard-welcome');
                if (welcomeDiv && !welcomeDiv.dataset.originalText) {
                    welcomeDiv.dataset.originalText = welcomeDiv.textContent;
                }
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    // --- Load Payments ---
    async function loadPayments() {
        try {
            const response = await fetch('../api/student/my-payments.php');
            const result = await response.json();
            
            if (result.success) {
                renderPayments(result.data);
            }
        } catch (error) {
            console.error('Error loading payments:', error);
            showError('paymentList', 'Failed to load payment records');
        }
    }

    function renderPayments(payments) {
        const list = document.getElementById('paymentList');
        if (!list) return;

        if (!payments || payments.length === 0) {
            list.innerHTML = '<tr><td colspan="6" style="text-align:center;">No payment records found</td></tr>';
            return;
        }

        list.innerHTML = payments.map(p => `
            <tr>
                <td>${escapeHtml(p.payment_type)}</td>
                <td>₱${parseFloat(p.amount).toFixed(2)}</td>
                <td>${formatDate(p.date_paid)}</td>
                <td>${escapeHtml(p.or_number)}</td>
                <td style="color:${getStatusColor(p.status)}; font-weight:700;">${escapeHtml(p.status)}</td>
                <td><button class="view-receipt-btn" onclick="viewReceipt('${escapeHtml(p.or_number)}')">View</button></td>
            </tr>
        `).join('');
    }

    window.viewReceipt = function(orNumber) {
        showToast(`Receipt for OR #${orNumber} - Feature coming soon`, 'info');
    };

    // --- Load Attendance ---
    async function loadAttendance() {
        try {
            const response = await fetch('../api/student/my-attendance.php');
            const result = await response.json();
            
            if (result.success) {
                renderAttendance(result.data);
            }
        } catch (error) {
            console.error('Error loading attendance:', error);
            showError('attendanceList', 'Failed to load attendance records', 9);
        }
    }

    function renderAttendance(attendance) {
        const list = document.getElementById('attendanceList');
        if (!list) return;

        if (!attendance || attendance.length === 0) {
            list.innerHTML = '<tr><td colspan="9" style="text-align:center;">No attendance records found</td></tr>';
            return;
        }

        const icon = (isPresent) => isPresent ? 
            '<i class="fa-solid fa-check" style="color:#38761D;"></i>' : 
            '<i class="fa-solid fa-xmark" style="color:#8B0000;"></i>';

        list.innerHTML = attendance.map(a => `
            <tr>
                <td>${escapeHtml(a.event_name)}</td>
                <td>${escapeHtml(a.ay_semester || 'N/A')}</td>
                <td>${formatDate(a.attendance_date)}</td>
                <td style="text-align:center;">${icon(a.am_in)}</td>
                <td style="text-align:center;">${icon(a.am_out)}</td>
                <td style="text-align:center;">${icon(a.pm_in)}</td>
                <td style="text-align:center;">${icon(a.pm_out)}</td>
                <td>${escapeHtml(a.recorded_by_name || 'N/A')}</td>
                <td>${formatDate(a.date_recorded)}</td>
            </tr>
        `).join('');
    }

    // --- Load Penalties ---
    async function loadPenalties() {
        try {
            const response = await fetch('../api/student/my-penalties.php');
            const result = await response.json();
            
            if (result.success) {
                renderPenalties(result.data);
            }
        } catch (error) {
            console.error('Error loading penalties:', error);
            showError('penaltiesList', 'Failed to load penalty records');
        }
    }

    function renderPenalties(penalties) {
        const list = document.getElementById('penaltiesList');
        if (!list) return;

        if (!penalties || penalties.length === 0) {
            list.innerHTML = '<tr><td colspan="6" style="text-align:center;">No penalty records found</td></tr>';
            return;
        }

        list.innerHTML = penalties.map(p => `
            <tr>
                <td>${escapeHtml(p.event_name || 'N/A')}</td>
                <td>${escapeHtml(p.violation)}</td>
                <td>${formatDate(p.violation_date)}</td>
                <td>₱${parseFloat(p.amount).toFixed(2)}</td>
                <td style="color:${getStatusColor(p.status)}; font-weight:700;">${escapeHtml(p.status)}</td>
                <td>${escapeHtml(p.recorded_by_name || 'N/A')}</td>
            </tr>
        `).join('');
    }

    // --- Navigation Logic ---
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const viewId = item.dataset.view;

            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            views.forEach(v => v.classList.remove('active'));
            const targetView = document.getElementById(viewId + 'View');
            if (targetView) {
                targetView.classList.add('active');
            }
            
            // Immediately refresh data for the new view
            refreshCurrentView();
        });
    });

    // --- Notification Logic ---
    if (notificationIcon) {
        notificationIcon.addEventListener('click', () => {
            if (notificationModal) {
                notificationModal.classList.add('show');
                loadNotifications();
            }
        });
    }

    if (closeNotificationBtn) {
        closeNotificationBtn.onclick = () => {
            if (notificationModal) {
                notificationModal.classList.remove('show');
            }
        };
    }

    function loadNotifications() {
        const notifications = [
            { 
                type: 'Reminder', 
                message: 'Clearance validation starts next week.', 
                date: new Date().toLocaleString() 
            },
            { 
                type: 'Reminder', 
                message: 'Please check your payment records regularly.', 
                date: new Date(Date.now() - 86400000).toLocaleString() 
            },
            { 
                type: 'Info', 
                message: 'Data auto-refreshes every 5 seconds.', 
                date: new Date(Date.now() - 172800000).toLocaleString() 
            }
        ];

        const list = document.getElementById('notificationList');
        if (!list) return;

        list.innerHTML = notifications.map(n => `
            <div class="notification-item">
                <strong>${escapeHtml(n.type)}:</strong> ${escapeHtml(n.message)}
                <span style="float:right; font-size:0.8em; color:#777;">${escapeHtml(n.date)}</span>
            </div>
        `).join('');

        const countElement = document.getElementById('notificationCount');
        if (countElement) {
            countElement.textContent = notifications.length;
        }
    }

    // --- Logout Logic ---
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            logoutModal.classList.add('show');
        });
    }

    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', () => {
            logoutModal.classList.remove('show');
        });
    }

    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('../auth/logout.php', { method: 'POST' });
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.data.redirect;
                } else {
                    window.location.href = '../index.html';
                }
            } catch (error) {
                console.error('Logout Error:', error);
                window.location.href = '../index.html';
            }
        });
    }

    // --- Helper Functions ---
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid Date';
        
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    function getStatusColor(status) {
        const statusColors = {
            'Verified': '#38761D',
            'Paid': '#38761D',
            'Pending': '#8B0000',
            'Active': '#38761D',
            'Inactive': '#8B0000'
        };
        return statusColors[status] || '#333';
    }

    function showError(elementId, message, colspan = 6) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; color:#8B0000;">${escapeHtml(message)}</td></tr>`;
        }
    }

    function showToast(message, type = 'info') {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-circle-xmark' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        `;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: ${type === 'success' ? '#38761D' : type === 'error' ? '#8B0000' : '#2563eb'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // Show initial toast
    setTimeout(() => {
        showToast('Auto-refresh enabled - You will see updates instantly!', 'info');
    }, 1000);
});