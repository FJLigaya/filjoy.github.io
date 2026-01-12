<?php
require_once '../config/init.php';

// Check if user is logged in as student
if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('../index.html');
}

// Get student information
$id_number = $_SESSION['user_id'];

try {
    $stmt = $db->prepare("
        SELECT 
            id_number,
            first_name,
            middle_name,
            last_name,
            email,
            course,
            year_level,
            section,
            status
        FROM students 
        WHERE id_number = ?
    ");
    $stmt->execute([$id_number]);
    $student = $stmt->fetch();
    
    if (!$student) {
        // Student not found, logout
        session_destroy();
        redirect('../index.html');
    }
    
    if ($student['status'] !== 'Active') {
        // Account inactive
        session_destroy();
        die('Your account has been deactivated. Please contact the ICS Office.');
    }
    
    // Store student info in session for easy access
    $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
    $_SESSION['year_level'] = $student['year_level'];
    $_SESSION['course'] = $student['course'];
    
} catch(Exception $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    die("An error occurred. Please try again.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTrackHub - Student Dashboard</title>
    <link rel="stylesheet" href="student.css">
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
            <span class="notification-count" id="notificationCount">3</span>
            <div class="system-name-top">eTrackHub</div>
        </div>
    </header>

    <div class="main-container">
        <aside class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar-placeholder"></div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
                    <span class="profile-course"><?php echo htmlspecialchars($student['course'] . '-' . $student['year_level']); ?></span>
                </div>
                <button class="edit-profile-btn" title="Edit Profile">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
            </div>

            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-view="dashboard">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
                <a href="#" class="nav-item" data-view="payments">
                    <i class="fa-solid fa-credit-card"></i> My Payment
                </a>
                <a href="#" class="nav-item" data-view="attendance">
                    <i class="fa-solid fa-user-check"></i> My Attendance
                </a>
                <a href="#" class="nav-item" data-view="penalties">
                    <i class="fa-solid fa-gavel"></i> My Penalties
                </a>
            </nav>

            <button class="btn btn-logout" id="logoutBtn">Log out</button>
        </aside>

        <main class="content-area">
            
            <section id="dashboardView" class="view active">
                <div class="dashboard-welcome">Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</div>
                <div class="dashboard-metrics">
                    <div class="metric-card">
                        <div class="metric-title">Total Payments Made</div>
                        <div class="metric-value amount">₱0.00</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-title">Number of Attendance</div>
                        <div class="metric-value">0/0</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-title">Active Penalties</div>
                        <div class="metric-value penalty">0 PENDING</div>
                    </div>
                </div>
                <div class="clearance-status">Clearance Status: <span class="status-validation">For Validation</span></div>
            </section>

            <section id="paymentsView" class="view">
                <h2>My Payment</h2>
                <div class="filter-bar">
                    <input type="text" value="2025-2026" class="filter-year">
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Payment Category</th>
                                <th>Amount</th>
                                <th>Date Paid</th>
                                <th>OR No.</th>
                                <th>Status</th>
                                <th>Receipt (QR)</th>
                            </tr>
                        </thead>
                        <tbody id="paymentList">
                            <tr><td colspan="6" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="attendanceView" class="view">
                <h2>My Attendance</h2>
                <div class="filter-bar">
                    <input type="text" value="2025-2026" class="filter-year">
                </div>
                <div class="data-table attendance-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>A.Y Semester</th>
                                <th>Date</th>
                                <th colspan="4" class="status-header">Status</th>
                                <th>Recorded By</th>
                                <th>Date Recorded</th>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                                <th>AM IN</th>
                                <th>AM OUT</th>
                                <th>PM IN</th>
                                <th>PM OUT</th>
                                <td colspan="2"></td>
                            </tr>
                        </thead>
                        <tbody id="attendanceList">
                            <tr><td colspan="9" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="penaltiesView" class="view">
                <h2>My Penalties</h2>
                <div class="filter-bar">
                    <input type="text" value="2025-2026" class="filter-year">
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Violation</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody id="penaltiesList">
                            <tr><td colspan="6" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Notification Modal -->
            <div id="notificationModal" class="modal">
                <div class="modal-content-notification">
                    <span class="close-btn">&times;</span>
                    <h2>Notification</h2>
                    <div id="notificationList"></div>
                </div>
            </div>

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

    <script src="student.js"></script>
</body>
</html>