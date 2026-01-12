<?php
/**
 * officer/dashboard.php
 * Officer Dashboard Entry Point
 */

require_once '../config/init.php';

// Check if user is logged in as officer
if (!isLoggedIn() || $_SESSION['role'] !== 'officer') {
    redirect('../index.html');
}

// Get officer information
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
    $officer = $stmt->fetch();
    
    if (!$officer) {
        session_destroy();
        redirect('../index.html');
    }
    
    if ($officer['status'] !== 'Active') {
        session_destroy();
        die('Your account has been deactivated. Please contact the ICS Adviser.');
    }
    
    // Store officer info in session
    $_SESSION['officer_name'] = $officer['name'];
    
} catch(Exception $e) {
    error_log("Officer Dashboard Error: " . $e->getMessage());
    die("An error occurred. Please try again.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTrackHub - Officer Dashboard</title>
    <link rel="stylesheet" href="officer.css">
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
            <span class="notification-count" id="notificationCount">10+</span>
            <div class="system-name-top">eTrackHub</div>
        </div>
    </header>

    <div class="main-container">
        <aside class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar-placeholder"></div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($officer['name']); ?></span>
                    <span class="profile-course">ICS-ISC Officer</span>
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
                    <i class="fa-solid fa-credit-card"></i> Payment
                </a>
                <a href="#" class="nav-item" data-view="attendance">
                    <i class="fa-solid fa-user-check"></i> Attendance
                </a>
                <a href="#" class="nav-item" data-view="penalties">
                    <i class="fa-solid fa-gavel"></i> Penalties
                </a>
                <a href="#" class="nav-item" data-view="reports">
                    <i class="fa-solid fa-chart-line"></i> Reports
                </a>
            </nav>

            <button class="btn btn-logout" id="logoutBtn">Log out</button>
        </aside>

        <main class="content-area">
            
            <section id="dashboardView" class="view active">
                <div class="dashboard-welcome">Welcome, ICS-ISC <?php echo htmlspecialchars(explode(' ', $officer['name'])[0]); ?>!</div>
                <div class="dashboard-metrics">
                    <div class="metric-card">
                        <div class="metric-title">Total Payments Collected</div>
                        <div class="metric-value amount">₱0.00</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-title">Receipts Issued</div>
                        <div class="metric-value">0</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-title">Recent Activity</div>
                        <div class="metric-value activity">Updated just now</div>
                    </div>
                </div>
                <div class="clearance-status">Clearance Status: <span class="status-validation">For Validation</span></div>
            </section>

            <section id="paymentsView" class="view">
                <h2>Payment</h2>
                <div class="filter-bar">
                    <button class="btn btn-action-green" id="addPaymentBtn"><i class="fa-solid fa-plus"></i> Add</button>
                    <input type="text" value="2025-2026" class="filter-year">
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Payment Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>OR No.</th>
                                <th>Status</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody id="paymentList">
                            <tr><td colspan="8" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="attendanceView" class="view">
                <h2>Attendance</h2>
                <div class="filter-bar">
                    <button class="btn btn-action-green" id="addAttendanceBtn"><i class="fa-solid fa-user-plus"></i> Add Attendance</button>
                    <button class="btn btn-action-green" id="addEventBtn"><i class="fa-solid fa-calendar-plus"></i> Add Event</button>
                    <input type="text" value="2025-2026" class="filter-year">
                </div>
                <div class="data-table attendance-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Event Name</th>
                                <th>A.Y Semester</th>
                                <th>Date</th>
                                <th colspan="4" class="status-header">Status</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceList">
                            <tr><td colspan="10" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="penaltiesView" class="view">
                <h2>Penalties</h2>
                <div class="filter-bar">
                    <button class="btn btn-action-green" id="addPenaltyBtn"><i class="fa-solid fa-plus"></i> Add Penalty</button>
                    <input type="text" value="2025-2026" class="filter-year">
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Event Name</th>
                                <th>Violation</th>
                                <th>Date</th>
                                <th>Recorded By</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody id="penaltiesList">
                            <tr><td colspan="7" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <section id="reportsView" class="view">
                <h2>Reports</h2>
                <p class="report-desc">Generate summaries of student transactions, attendance records, and penalties.</p>
                <div class="report-summaries">
                    <div class="report-card payment-report">
                        <h3>PAYMENT STUDENTS MADE</h3>
                        <div class="report-item"><span>Total Penalty Collected:</span> <span>₱0.00</span></div>
                        <div class="report-item"><span>Total LMC Contribution Collected:</span> <span>₱0.00</span></div>
                        <div class="report-item"><span>Total LMC T-Shirt Collected:</span> <span>₱0.00</span></div>
                        <div class="report-item total"><span>Total Amount Collected:</span> <span>₱0.00</span></div>
                    </div>
                </div>
                <button class="btn btn-print-report">Print Reports</button>
            </section>

            <!-- Payment Modal -->
            <div id="paymentModal" class="modal">
                <div class="modal-content modal-form">
                    <span class="close-btn">&times;</span>
                    <h3>Add/Edit Payment</h3>
                    <form id="paymentForm">
                        <label for="pId">Student ID</label><input type="text" id="pId" name="id_number" required>
                        <label for="pName">Full Name</label><input type="text" id="pName" name="student_name" readonly style="background:#f0f0f0;">
                        <label for="pType">Payment Type</label>
                        <select id="pType" name="payment_type">
                            <option>LMC Contribution</option>
                            <option>LMC T-Shirt</option>
                            <option>Membership Fee</option>
                            <option>Penalty</option>
                        </select>
                        <label for="pAmount">Amount</label><input type="number" id="pAmount" name="amount" required step="0.01">
                        <label for="pDate">Date</label><input type="date" id="pDate" name="date_paid" required>
                        <label for="pOR">OR No.</label><input type="text" id="pOR" name="or_number" required>
                        <label for="pStatus">Status</label>
                        <select id="pStatus" name="status"><option>Paid</option><option>Pending</option><option>Verified</option></select>
                        <div class="modal-actions">
                            <button type="submit" class="btn btn-confirm-green">Save</button>
                            <button type="button" class="btn btn-cancel-red close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Event Modal -->
            <div id="eventModal" class="modal">
                <div class="modal-content modal-form">
                    <span class="close-btn">&times;</span>
                    <h3>Add New Event</h3>
                    <form id="eventForm">
                        <label for="eName">Event Name</label><input type="text" id="eName" name="event_name" required>
                        <label for="eVenue">Venue</label><input type="text" id="eVenue" name="venue" required>
                        <label for="eType">Event Type</label>
                        <select id="eType" name="event_type"><option>Assembly</option><option>Meeting</option><option>Orientation</option></select>
                        <label for="eSemester">A.Y Semester</label><input type="text" id="eSemester" name="ay_semester" required>
                        <label for="eDate">Date</label><input type="date" id="eDate" name="event_date" required>
                        <label for="eStart">Start Time</label><input type="time" id="eStart" name="start_time" required>
                        <label for="eEnd">End Time</label><input type="time" id="eEnd" name="end_time" required>
                        <div class="modal-actions">
                            <button type="submit" class="btn btn-confirm-green">Save</button>
                            <button type="button" class="btn btn-cancel-red close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Modal -->
            <div id="attendanceModal" class="modal">
                <div class="modal-content modal-form">
                    <span class="close-btn">&times;</span>
                    <h3>Record Attendance</h3>
                    <form id="attendanceForm">
                        <label for="aId">Student ID</label><input type="text" id="aId" required>
                        <label for="aName">Full Name</label><input type="text" id="aName" readonly style="background:#f0f0f0;">
                        <label for="aEvent">Event Name</label>
                        <select id="aEvent"><option>Loading...</option></select>
                        <div class="attendance-time-slots">
                            <label>AM IN <input type="checkbox" id="amIn" class="toggle-checkbox"></label>
                            <label>AM OUT <input type="checkbox" id="amOut" class="toggle-checkbox"></label>
                            <label>PM IN <input type="checkbox" id="pmIn" class="toggle-checkbox"></label>
                            <label>PM OUT <input type="checkbox" id="pmOut" class="toggle-checkbox"></label>
                        </div>
                        <div class="modal-actions">
                            <button type="submit" class="btn btn-confirm-green">Save</button>
                            <button type="button" class="btn btn-cancel-red close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Penalty Modal -->
            <div id="penaltyModal" class="modal">
                <div class="modal-content modal-form">
                    <span class="close-btn">&times;</span>
                    <h3>Add Penalty</h3>
                    <form id="penaltyForm">
                        <label for="penId">Student ID</label><input type="text" id="penId" name="id_number" required>
                        <label for="penName">Full Name</label><input type="text" id="penName" readonly style="background:#f0f0f0;">
                        <label for="penEvent">Event Name</label>
                        <select id="penEvent" name="event_id"><option>Loading...</option></select>
                        <label for="penViolation">Violation</label><input type="text" id="penViolation" name="violation" required>
                        <label for="penAmount">Amount</label><input type="number" id="penAmount" name="amount" required step="0.01">
                        <label for="penDate">Date</label><input type="date" id="penDate" name="violation_date" required>
                        <div class="modal-actions">
                            <button type="submit" class="btn btn-confirm-green">Save</button>
                            <button type="button" class="btn btn-cancel-red close-modal">Cancel</button>
                        </div>
                    </form>
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

    <script src="officer.js"></script>
</body>
</html>