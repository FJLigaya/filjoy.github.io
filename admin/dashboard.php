<?php
/**
 * admin/dashboard.php
 * Admin/Adviser Dashboard Entry Point
 */

require_once '../config/init.php';

// Check if user is logged in as adviser
if (!isLoggedIn() || $_SESSION['role'] !== 'adviser') {
    redirect('../index.html');
}

// Get adviser information
$officer_id = $_SESSION['user_id'];

try {
    $stmt = $db->prepare("
        SELECT 
            officer_id,
            id_number,
            name,
            username,
            role,
            status
        FROM officers 
        WHERE officer_id = ?
    ");
    $stmt->execute([$officer_id]);
    $adviser = $stmt->fetch();
    
    if (!$adviser) {
        session_destroy();
        redirect('../index.html');
    }
    
    if ($adviser['status'] !== 'Active') {
        session_destroy();
        die('Your account has been deactivated.');
    }
    
    // Store adviser info in session
    $_SESSION['adviser_name'] = $adviser['name'];
    
} catch(Exception $e) {
    error_log("Adviser Dashboard Error: " . $e->getMessage());
    die("An error occurred. Please try again.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTrackHub - Adviser Dashboard (Admin)</title>
    <link rel="stylesheet" href="adviser.css">
    <style>
        /* Database Management Grid Styles */
        .db-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .db-card {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s;
            cursor: default;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 150px;
        }
        
        .db-card.action-card {
            cursor: pointer;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        }
        
        .db-card.action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #38761D 0%, #2d5e17 100%);
            color: white;
        }
        
        .db-card.action-card:hover i {
            color: white;
        }
        
        .db-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #8B0000;
        }
        
        .db-card .card-title {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
            display: block;
            margin-top: 10px;
        }
        
        .db-card .card-value {
            font-size: 1.3em;
            font-weight: 700;
            color: #38761D;
            display: block;
            margin-top: 5px;
        }
        
        .db-card.action-card:hover .card-title {
            color: white;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght=300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header class="top-bar">
        <div class="logo-container">
            <img src="../assets/images/tcgclogo1.png" alt="TCGC Logo" class="header-logo" style="height:40px; width:auto; margin-right:8px;">
            <img src="../assets/images/icslogo1.png" alt="ICS Logo" class="header-logo" style="height:40px; width:auto;">
        </div>
        <div class="header-right">
            <input type="text" placeholder="Search..." class="search-box">
            <i class="fa-solid fa-bell notification-icon"></i>
            <div class="system-name-top">eTrackHub</div>
        </div>
    </header>

    <div class="main-container">
        <aside class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar-placeholder"></div>
                <div class="profile-info">
                    <span class="profile-name">Admin</span>
                    <span class="profile-course">ICS-ISC Adviser</span>
                </div>
                <button class="edit-profile-btn" title="Edit Profile">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
            </div>

            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-view="dashboard">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
                <a href="#" class="nav-item" data-view="userManagement">
                    <i class="fa-solid fa-users"></i> User Management
                </a>
                <a href="#" class="nav-item" data-view="reportsAnalytics">
                    <i class="fa-solid fa-chart-bar"></i> Reports & Analytics
                </a>
                <a href="#" class="nav-item" data-view="auditLogs">
                    <i class="fa-solid fa-clipboard-list"></i> Audit Logs
                </a>
                <a href="#" class="nav-item" data-view="databaseManagement">
                    <i class="fa-solid fa-database"></i> Database Management
                </a>
            </nav>

            <button class="btn btn-logout" id="logoutBtn">Log out</button>
        </aside>

        <main class="content-area">
            
            <section id="dashboardView" class="view active">
                <div class="dashboard-welcome">Welcome, ICS-ISC Adviser!</div>
                <div class="dashboard-metrics">
                    <div class="metric-card"><div class="metric-title">Total Registered Students</div><div class="metric-value">0</div></div>
                    <div class="metric-card"><div class="metric-title">Total ICS-ISC Officers</div><div class="metric-value">0</div></div>
                    <div class="metric-card"><div class="metric-title">Total Payments Collected</div><div class="metric-value amount">₱0.00</div></div>
                    <div class="metric-card"><div class="metric-title">Pending Penalties</div><div class="metric-value penalty">0</div></div>
                    <div class="metric-card wide"><div class="metric-title">Upcoming Activities</div><div class="metric-value activity">LMC 2025</div></div>
                    <div class="metric-card wide"><div class="metric-title">Last Backup Date</div><div class="metric-value backup">Never</div></div>
                    <div class="metric-card wide"><div class="metric-title">Upcoming Activities</div><div class="metric-value activity">Code Buddy Program</div></div>
                </div>
            </section>

            <section id="userManagementView" class="view">
                <h2>User Management</h2>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Year Level</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody id="userList">
                            <tr><td colspan="6" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="reportsAnalyticsView" class="view">
                <h2>Reports & Analytics</h2>
                <p class="report-desc">Generate summaries of student transactions, attendance records, and penalties.</p>
                <div class="report-summaries">
                    <div class="report-item big"><span>Payments Report:</span> <span>₱0.00</span></div>
                    <div class="report-item big"><span>Penalties Report:</span> <span>₱0.00</span></div>
                    <div class="report-item big total"><span>Total Amount Collected:</span> <span>₱0.00</span></div>
                    <div class="report-item small"><span>Registered Students:</span> <span>0</span></div>
                    <div class="report-item small"><span>ICS-ISC Officers:</span> <span>0</span></div>
                </div>
                <button class="btn btn-print-report">Print Reports</button>
            </section>

            <section id="auditLogsView" class="view">
                <h2>Audit Logs</h2>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Date & Time</th>
                                <th>Changes Made</th>
                                <th>Officer on Duty</th>
                            </tr>
                        </thead>
                        <tbody id="auditLogList">
                            <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="databaseManagementView" class="view">
                <h2>Database Management</h2>
                <div class="db-grid">
                    <div class="db-card status-card">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span class="card-title">Last Backup Date</span>
                        <span class="card-value">Oct 19, 2025</span>
                    </div>
                    <div class="db-card status-card">
                        <i class="fa-solid fa-database"></i>
                        <span class="card-title">Backup Size</span>
                        <span class="card-value">250 MB</span>
                    </div>
                    <div class="db-card action-card manual-backup">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span class="card-title">Manual Backup</span>
                    </div>
                    <div class="db-card action-card restore-db">
                        <i class="fa-solid fa-database"></i>
                        <span class="card-title">Restore Database</span>
                    </div>
                    <div class="db-card status-card">
                        <i class="fa-solid fa-clock"></i>
                        <span class="card-title">Next Scheduled Backup</span>
                        <span class="card-value">Daily @ 1AM</span>
                    </div>
                    <div class="db-card action-card delete-logs">
                        <i class="fa-solid fa-trash-can"></i>
                        <span class="card-title">Delete Old Logs / Files</span>
                    </div>
                </div>
            </section>

            <!-- Logout Modal -->
            <div id="logoutModal" class="modal">
                <div class="modal-content modal-content-small">
                    <p>Are you sure you want to log out of eTrackHub: ICS Electronic Transaction and Records Management System?</p>
                    <div class="modal-actions">
                        <button class="btn btn-confirm-green" id="confirmLogoutBtn">Yes, Logout</button>
                        <button class="btn btn-cancel-red" id="cancelLogoutBtn">Cancel</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="adviser.js"></script>
</body>
</html>