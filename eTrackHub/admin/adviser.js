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
    
    // DB Action Cards
    const manualBackupCard = document.querySelector('.db-card.manual-backup');
    const restoreDbCard = document.querySelector('.db-card.restore-db');
    const deleteLogsCard = document.querySelector('.db-card.delete-logs');

    // --- Authentication Check ---
    async function checkAuth() {
        try {
            const response = await fetch('../auth/check-session.php');
            const result = await response.json();
            
            if (!result.success || result.data.role !== 'adviser') {
                window.location.href = '../index.html';
            } else {
                // Load initial data
                loadDashboardStats();
                loadUsers();
                loadAuditLogs();
                loadReports();
            }
        } catch (error) {
            console.error('Auth Check Error:', error);
            window.location.href = '../index.html';
        }
    }

    // --- Load Dashboard Statistics ---
    async function loadDashboardStats() {
        try {
            const response = await fetch('../api/admin/dashboard-stats.php');
            const result = await response.json();
            
            if (result.success) {
                const stats = result.data;
                
                // Update metric cards
                const metricCards = document.querySelectorAll('.metric-card .metric-value');
                if (metricCards[0]) metricCards[0].textContent = stats.total_students;
                if (metricCards[1]) metricCards[1].textContent = stats.total_officers;
                if (metricCards[2]) metricCards[2].textContent = `₱${parseFloat(stats.total_collected).toLocaleString()}`;
                if (metricCards[3]) metricCards[3].textContent = stats.pending_penalties;
                
                // Update last backup date cards
                const backupCards = document.querySelectorAll('.metric-card.wide .metric-value.backup');
                backupCards.forEach(card => {
                    if (card) card.textContent = stats.last_backup_date;
                });
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    // --- User Management ---
    async function loadUsers() {
        try {
            const response = await fetch('../api/admin/users.php');
            const result = await response.json();
            
            if (result.success) {
                renderUsers(result.data);
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    function renderUsers(users) {
        const list = document.getElementById('userList');
        if (!list) return;

        if (users.length === 0) {
            list.innerHTML = '<tr><td colspan="6" style="text-align:center;">No users found</td></tr>';
            return;
        }

        list.innerHTML = users.map(u => `
            <tr>
                <td>${u.id_number}</td>
                <td>${u.name}</td>
                <td>${u.year_level}</td>
                <td style="color:${u.status === 'Active' ? '#38761D' : '#8B0000'}; font-weight:700;">${u.status}</td>
                <td>${u.role}</td>
                <td>
                    <i class="fa-solid fa-arrows-rotate action-icon" title="Change Role" onclick="changeUserRole('${u.id_number}', '${u.role}')"></i>
                    <i class="fa-solid ${u.status === 'Active' ? 'fa-circle-check' : 'fa-circle-xmark'} action-icon" 
                       title="${u.status === 'Active' ? 'Deactivate' : 'Activate'}" 
                       onclick="toggleUserStatus('${u.id_number}', '${u.status}')"></i>
                    <i class="fa-solid fa-trash action-icon delete" title="Delete" onclick="deleteUser('${u.id_number}', '${u.name}')"></i>
                </td>
            </tr>
        `).join('');
    }

    window.toggleUserStatus = async function(idNumber, currentStatus) {
        const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
        
        if (!confirm(`Are you sure you want to ${newStatus === 'Active' ? 'activate' : 'deactivate'} this user?`)) {
            return;
        }

        try {
            const response = await fetch('../api/admin/users.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_number=${idNumber}&action=toggle_status`
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                loadUsers();
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Error toggling status:', error);
            alert('Failed to update user status');
        }
    };

    window.changeUserRole = async function(idNumber, currentRole) {
        if (currentRole === 'Student') {
            alert('Cannot change role for students');
            return;
        }

        const newRole = prompt(`Enter new role for user ${idNumber}:\n(officer, adviser)`, currentRole.toLowerCase());
        
        if (!newRole || newRole === currentRole.toLowerCase()) {
            return;
        }

        try {
            const response = await fetch('../api/admin/users.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_number=${idNumber}&action=change_role&new_role=${newRole}`
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                loadUsers();
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Error changing role:', error);
            alert('Failed to change user role');
        }
    };

    window.deleteUser = async function(idNumber, name) {
        if (!confirm(`Are you sure you want to DELETE ${name}?\n\nThis action cannot be undone and will delete all associated records.`)) {
            return;
        }

        try {
            const response = await fetch('../api/admin/users.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_number=${idNumber}`
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                loadUsers();
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            alert('Failed to delete user');
        }
    };

    // --- Audit Logs ---
    async function loadAuditLogs() {
        try {
            const response = await fetch('../api/admin/audit-logs.php');
            const result = await response.json();
            
            if (result.success) {
                renderAuditLogs(result.data);
            }
        } catch (error) {
            console.error('Error loading audit logs:', error);
        }
    }

    function renderAuditLogs(logs) {
        const list = document.getElementById('auditLogList');
        if (!list) return;

        if (logs.length === 0) {
            list.innerHTML = '<tr><td colspan="5" style="text-align:center;">No audit logs found</td></tr>';
            return;
        }

        list.innerHTML = logs.map(log => `
            <tr>
                <td>${log.id_number || 'N/A'}</td>
                <td>${log.student_name}</td>
                <td>${new Date(log.date_created).toLocaleString()}</td>
                <td>${log.description || log.action_type}</td>
                <td>${log.officer_name || 'System'}</td>
            </tr>
        `).join('');
    }

    // --- Reports & Analytics ---
    async function loadReports() {
        try {
            const response = await fetch('../api/admin/reports.php');
            const result = await response.json();
            
            if (result.success) {
                renderReports(result.data);
            }
        } catch (error) {
            console.error('Error loading reports:', error);
        }
    }

    function renderReports(data) {
        const summariesDiv = document.querySelector('.report-summaries');
        if (!summariesDiv) return;

        const totals = data.total_collections;
        
        summariesDiv.innerHTML = `
            <div class="report-item big">
                <span>Payments Report:</span> 
                <span>₱${parseFloat(totals.payments).toLocaleString()}</span>
            </div>
            <div class="report-item big">
                <span>Penalties Report:</span> 
                <span>₱${parseFloat(totals.penalties).toLocaleString()}</span>
            </div>
            <div class="report-item big total">
                <span>Total Amount Collected:</span> 
                <span>₱${parseFloat(totals.grand_total).toLocaleString()}</span>
            </div>
            <div class="report-item small">
                <span>Registered Students:</span> 
                <span>${data.student_stats.total_students}</span>
            </div>
            <div class="report-item small">
                <span>ICS-ISC Officers:</span> 
                <span>${data.total_officers}</span>
            </div>
        `;
    }

    // Print reports button
    const printReportBtn = document.querySelector('.btn-print-report');
    if (printReportBtn) {
        printReportBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // --- Database Management ---
    if (manualBackupCard) {
        manualBackupCard.addEventListener('click', async () => {
            if (!confirm('Initiate Manual Backup now?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'manual_backup');

                const response = await fetch('../api/admin/backup.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    loadDashboardStats();
                }
            } catch (error) {
                console.error('Backup Error:', error);
                alert('Backup operation failed');
            }
        });
    }

    if (restoreDbCard) {
        restoreDbCard.addEventListener('click', () => {
            alert('WARNING: Database restore operation requires administrator access.\n\nPlease contact the system administrator to perform this action.');
        });
    }

    if (deleteLogsCard) {
        deleteLogsCard.addEventListener('click', async () => {
            if (!confirm('Delete all logs and files older than 6 months?\n\nThis action is permanent and cannot be undone.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete_old_logs');

                const response = await fetch('../api/admin/backup.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    loadAuditLogs();
                }
            } catch (error) {
                console.error('Delete Logs Error:', error);
                alert('Failed to delete old logs');
            }
        });
    }

    // --- Navigation Logic ---
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const viewId = item.dataset.view;

            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            views.forEach(v => v.classList.remove('active'));
            document.getElementById(viewId + 'View').classList.add('active');
            
            // Reload data when switching views
            switch(viewId) {
                case 'dashboard':
                    loadDashboardStats();
                    break;
                case 'userManagement':
                    loadUsers();
                    break;
                case 'reportsAnalytics':
                    loadReports();
                    break;
                case 'auditLogs':
                    loadAuditLogs();
                    break;
            }
        });
    });

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
});