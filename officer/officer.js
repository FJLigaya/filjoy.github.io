// officer.js - Complete Auto-Refresh System (Never Need to Refresh Page)

document.addEventListener('DOMContentLoaded', () => {
    // Check authentication
    checkAuth();

    // --- DOM Elements ---
    const navItems = document.querySelectorAll('.nav-item');
    const views = document.querySelectorAll('.view');
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
    
    // Action buttons
    const addPaymentBtn = document.getElementById('addPaymentBtn');
    const addEventBtn = document.getElementById('addEventBtn');
    const addAttendanceBtn = document.getElementById('addAttendanceBtn');
    const addPenaltyBtn = document.getElementById('addPenaltyBtn');

    // Modals
    const paymentModal = document.getElementById('paymentModal');
    const eventModal = document.getElementById('eventModal');
    const attendanceModal = document.getElementById('attendanceModal');
    const penaltyModal = document.getElementById('penaltyModal');
    const closeBtns = document.querySelectorAll('.close-modal, .modal .close-btn');

    let currentEditId = null;
    let events = [];
    let autoRefreshInterval = null;

    // --- Authentication Check ---
    async function checkAuth() {
        try {
            const response = await fetch('../auth/check-session.php');
            const result = await response.json();
            
            if (!result.success || result.data.role !== 'officer') {
                window.location.href = '../index.html';
            }
        } catch (error) {
            console.error('Auth Check Error:', error);
            window.location.href = '../index.html';
        }
    }

    // --- MASTER REFRESH FUNCTION ---
    async function refreshAllData() {
        await loadEvents();
        await loadPayments();
        await loadAttendance();
        await loadPenalties();
        await loadDashboardStats();
    }

    // Load initial data
    refreshAllData();

    // --- START AUTO-REFRESH EVERY 5 SECONDS ---
    function startAutoRefresh() {
        // Clear any existing interval
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        // Refresh data every 5 seconds automatically
        autoRefreshInterval = setInterval(() => {
            const activeView = document.querySelector('.view.active');
            if (activeView && !document.querySelector('.modal.show')) {
                // Don't refresh if a modal is open (user is entering data)
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

    // Start auto-refresh
    startAutoRefresh();

    // --- Load Dashboard Statistics ---
    async function loadDashboardStats() {
        try {
            const response = await fetch('../api/officer/payments.php');
            const result = await response.json();
            
            if (result.success) {
                const totalCollected = result.data.reduce((sum, p) => sum + parseFloat(p.amount), 0);
                const receiptsIssued = result.data.length;
                
                const amountEl = document.querySelector('.metric-value.amount');
                const receiptsEl = document.querySelectorAll('.metric-value')[1];
                const activityEl = document.querySelector('.metric-value.activity');
                
                if (amountEl) amountEl.textContent = `₱${totalCollected.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                if (receiptsEl) receiptsEl.textContent = receiptsIssued;
                if (activityEl) activityEl.textContent = `Updated ${new Date().toLocaleTimeString()}`;
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    // --- General Modal Functions ---
    function openModal(modal) {
        modal.classList.add('show');
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        currentEditId = null;
    }

    closeBtns.forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            closeModal(btn.closest('.modal'));
        };
    });

    // --- Navigation Logic ---
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const viewId = item.dataset.view;

            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            views.forEach(v => v.classList.remove('active'));
            document.getElementById(viewId + 'View').classList.add('active');
            
            // Immediately refresh data for the new view
            refreshCurrentView();
        });
    });

    // --- STUDENT NAME AUTO-POPULATE ---
    function setupStudentAutoComplete(idInput, nameInput) {
        idInput.addEventListener('blur', async () => {
            const studentId = idInput.value.trim();
            
            if (!studentId) {
                nameInput.value = '';
                return;
            }

            try {
                const response = await fetch(`../api/officer/get-student-name.php?id_number=${studentId}`);
                const result = await response.json();

                if (result.success) {
                    nameInput.value = result.data.name;
                    nameInput.style.color = '#38761D';
                } else {
                    nameInput.value = 'Student not found';
                    nameInput.style.color = '#8B0000';
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error fetching student:', error);
                nameInput.value = 'Error loading student';
            }
        });
    }

    // --- EVENTS MANAGEMENT ---
    async function loadEvents() {
        try {
            const response = await fetch('../api/officer/events.php');
            const result = await response.json();
            
            if (result.success) {
                events = result.data;
                populateEventDropdowns();
            }
        } catch (error) {
            console.error('Error loading events:', error);
        }
    }

    function populateEventDropdowns() {
        const eventSelects = document.querySelectorAll('#aEvent, #penEvent');
        eventSelects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Event</option>';
            events.forEach(event => {
                const option = document.createElement('option');
                option.value = event.event_id;
                option.textContent = event.event_name;
                if (currentValue == event.event_id) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        });
    }

    addEventBtn?.addEventListener('click', () => {
        document.getElementById('eventForm').reset();
        openModal(eventModal);
    });

    document.getElementById('eventForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);

        try {
            const response = await fetch('../api/officer/events.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('✓ Event created successfully!', 'success');
                closeModal(eventModal);
                e.target.reset();
                
                // AUTO-REFRESH: Event list updates immediately
                await loadEvents();
                
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error creating event:', error);
            showToast('Failed to create event. Please try again.', 'error');
        }
    });

    // --- PAYMENTS CRUD ---
    async function loadPayments() {
        try {
            const response = await fetch('../api/officer/payments.php');
            const result = await response.json();
            
            if (result.success) {
                renderPayments(result.data);
            }
        } catch (error) {
            console.error('Error loading payments:', error);
        }
    }

    function renderPayments(payments) {
        const list = document.getElementById('paymentList');
        if (!list) return;

        if (payments.length === 0) {
            list.innerHTML = '<tr><td colspan="8" style="text-align:center;">No payment records found</td></tr>';
            return;
        }

        list.innerHTML = payments.map(p => `
            <tr>
                <td>${p.id_number}</td>
                <td>${p.student_name || 'N/A'}</td>
                <td>${p.payment_type}</td>
                <td>₱${parseFloat(p.amount).toFixed(2)}</td>
                <td>${new Date(p.date_paid).toLocaleDateString()}</td>
                <td>${p.or_number}</td>
                <td style="color: ${p.status === 'Verified' || p.status === 'Paid' ? '#38761D' : '#8B0000'}; font-weight: 700;">${p.status}</td>
                <td>
                    <i class="fa-solid fa-pen-to-square action-icon edit" title="Edit" onclick="editPayment(${p.payment_id})"></i>
                    <i class="fa-solid fa-trash action-icon delete" title="Delete" onclick="deletePayment(${p.payment_id})"></i>
                </td>
            </tr>
        `).join('');
    }

    addPaymentBtn?.addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('paymentForm').reset();
        openModal(paymentModal);
        
        const pId = document.getElementById('pId');
        const pName = document.getElementById('pName');
        setupStudentAutoComplete(pId, pName);
    });

    window.editPayment = async (paymentId) => {
        currentEditId = paymentId;
        
        try {
            const response = await fetch(`../api/officer/payments.php?id=${paymentId}`);
            const result = await response.json();
            
            if (result.success && result.data) {
                const p = result.data;
                document.getElementById('pId').value = p.id_number;
                document.getElementById('pName').value = p.student_name;
                document.getElementById('pType').value = p.payment_type;
                document.getElementById('pAmount').value = p.amount;
                document.getElementById('pDate').value = p.date_paid;
                document.getElementById('pOR').value = p.or_number;
                document.getElementById('pStatus').value = p.status;
                
                openModal(paymentModal);
            }
        } catch (error) {
            console.error('Error loading payment:', error);
            showToast('Failed to load payment details', 'error');
        }
    };

    window.deletePayment = async (paymentId) => {
        if (!confirm('Are you sure you want to delete this payment record?')) return;
        
        try {
            const response = await fetch('../api/officer/payments.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `payment_id=${paymentId}`
            });

            const result = await response.json();

            if (result.success) {
                showToast('✓ Payment deleted successfully!', 'success');
                
                // AUTO-REFRESH: Table updates immediately, no page reload
                await loadPayments();
                await loadDashboardStats();
                
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting payment:', error);
            showToast('Failed to delete payment', 'error');
        }
    };

    document.getElementById('paymentForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);

        try {
            let response;
            
            if (currentEditId) {
                const data = new URLSearchParams(formData);
                data.append('payment_id', currentEditId);
                
                response = await fetch('../api/officer/payments.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data.toString()
                });
            } else {
                response = await fetch('../api/officer/payments.php', {
                    method: 'POST',
                    body: formData
                });
            }

            const result = await response.json();

            if (result.success) {
                showToast(currentEditId ? '✓ Payment updated!' : '✓ Payment added!', 'success');
                closeModal(paymentModal);
                e.target.reset();
                
                // AUTO-REFRESH: Updates appear immediately in table
                await loadPayments();
                await loadDashboardStats();
                
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error saving payment:', error);
            showToast('Failed to save payment. Please try again.', 'error');
        }
    });

    // --- ATTENDANCE CRUD ---
    async function loadAttendance() {
        try {
            const response = await fetch('../api/officer/attendance.php');
            const result = await response.json();
            
            if (result.success) {
                renderAttendance(result.data);
            }
        } catch (error) {
            console.error('Error loading attendance:', error);
        }
    }

    function renderAttendance(attendance) {
        const list = document.getElementById('attendanceList');
        if (!list) return;

        if (attendance.length === 0) {
            list.innerHTML = '<tr><td colspan="10" style="text-align:center;">No attendance records found</td></tr>';
            return;
        }

        const icon = (isPresent) => isPresent ? 
            '<i class="fa-solid fa-check" style="color:#38761D;"></i>' : 
            '<i class="fa-solid fa-xmark" style="color:#8B0000;"></i>';

        list.innerHTML = attendance.map(a => `
            <tr>
                <td>${a.id_number}</td>
                <td>${a.student_name || 'N/A'}</td>
                <td>${a.event_name}</td>
                <td>${a.ay_semester}</td>
                <td>${new Date(a.attendance_date).toLocaleDateString()}</td>
                <td style="text-align:center;">${icon(a.am_in)}</td>
                <td style="text-align:center;">${icon(a.am_out)}</td>
                <td style="text-align:center;">${icon(a.pm_in)}</td>
                <td style="text-align:center;">${icon(a.pm_out)}</td>
                <td>${a.recorded_by_name || 'N/A'}</td>
            </tr>
        `).join('');
    }

    addAttendanceBtn?.addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('attendanceForm').reset();
        openModal(attendanceModal);
        
        const aId = document.getElementById('aId');
        const aName = document.getElementById('aName');
        setupStudentAutoComplete(aId, aName);
    });

    document.getElementById('attendanceForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('id_number', document.getElementById('aId').value);
        formData.append('event_id', document.getElementById('aEvent').value);
        formData.append('attendance_date', new Date().toISOString().split('T')[0]);
        formData.append('am_in', document.getElementById('amIn').checked ? 1 : 0);
        formData.append('am_out', document.getElementById('amOut').checked ? 1 : 0);
        formData.append('pm_in', document.getElementById('pmIn').checked ? 1 : 0);
        formData.append('pm_out', document.getElementById('pmOut').checked ? 1 : 0);

        try {
            const response = await fetch('../api/officer/attendance.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('✓ Attendance recorded successfully!', 'success');
                closeModal(attendanceModal);
                e.target.reset();
                
                // AUTO-REFRESH: Attendance table updates immediately
                await loadAttendance();
                
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error saving attendance:', error);
            showToast('Failed to save attendance. Please try again.', 'error');
        }
    });

    // --- PENALTIES CRUD ---
    async function loadPenalties() {
        try {
            const response = await fetch('../api/officer/penalties.php');
            const result = await response.json();
            
            if (result.success) {
                renderPenalties(result.data);
            }
        } catch (error) {
            console.error('Error loading penalties:', error);
        }
    }

    function renderPenalties(penalties) {
        const list = document.getElementById('penaltiesList');
        if (!list) return;

        if (penalties.length === 0) {
            list.innerHTML = '<tr><td colspan="7" style="text-align:center;">No penalty records found</td></tr>';
            return;
        }

        list.innerHTML = penalties.map(p => `
            <tr>
                <td>${p.id_number}</td>
                <td>${p.student_name || 'N/A'}</td>
                <td>${p.event_name || 'N/A'}</td>
                <td>${p.violation}</td>
                <td>${new Date(p.violation_date).toLocaleDateString()}</td>
                <td>${p.recorded_by_name || 'N/A'}</td>
                <td>
                    <i class="fa-solid fa-pen-to-square action-icon edit" title="Edit" onclick="editPenalty(${p.penalty_id})"></i>
                    <i class="fa-solid fa-trash action-icon delete" title="Delete" onclick="deletePenalty(${p.penalty_id})"></i>
                </td>
            </tr>
        `).join('');
    }

    addPenaltyBtn?.addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('penaltyForm').reset();
        openModal(penaltyModal);
        
        const penId = document.getElementById('penId');
        const penName = document.getElementById('penName');
        setupStudentAutoComplete(penId, penName);
    });

    window.editPenalty = async (penaltyId) => {
        showToast('Edit penalty feature coming soon', 'info');
    };

    window.deletePenalty = async (penaltyId) => {
        if (!confirm('Are you sure you want to delete this penalty record?')) return;
        
        try {
            const response = await fetch('../api/officer/penalties.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `penalty_id=${penaltyId}`
            });

            const result = await response.json();

            if (result.success) {
                showToast('✓ Penalty deleted successfully!', 'success');
                
                // AUTO-REFRESH: Penalties table updates immediately
                await loadPenalties();
                
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting penalty:', error);
            showToast('Failed to delete penalty', 'error');
        }
    };

    document.getElementById('penaltyForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);

        try {
            const response = await fetch('../api/officer/penalties.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('✓ Penalty added successfully!', 'success');
                closeModal(penaltyModal);
                e.target.reset();
                
                // AUTO-REFRESH: Penalty appears in table immediately
                await loadPenalties();
                
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error saving penalty:', error);
            showToast('Failed to save penalty. Please try again.', 'error');
        }
    });

    // --- TOAST NOTIFICATION SYSTEM ---
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

    // Add animation CSS
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

    // --- Logout Logic ---
    logoutBtn.addEventListener('click', () => {
        logoutModal.classList.add('show');
    });

    cancelLogoutBtn.addEventListener('click', () => {
        logoutModal.classList.remove('show');
    });

    confirmLogoutBtn.addEventListener('click', async () => {
        try {
            const response = await fetch('../auth/logout.php', { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                window.location.href = result.data.redirect;
            }
        } catch (error) {
            console.error('Logout Error:', error);
            window.location.href = '../index.html';
        }
    });

    // Show initial toast
    setTimeout(() => {
        showToast('Auto-refresh enabled - Data updates every 5 seconds', 'info');
    }, 1000);
});