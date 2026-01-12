-- eTrackHub Database Schema
-- Drop existing database if exists
DROP DATABASE IF EXISTS etrackhub;
CREATE DATABASE etrackhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE etrackhub;

-- ============================================
-- 1. STUDENTS TABLE
-- ============================================
CREATE TABLE students (
    id_number VARCHAR(20) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    course VARCHAR(50) NOT NULL,
    year_level VARCHAR(10) NOT NULL,
    section VARCHAR(10),
    religion VARCHAR(50),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 2. OFFICERS TABLE
-- ============================================
CREATE TABLE officers (
    officer_id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('officer', 'adviser') DEFAULT 'officer',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ============================================
-- 3. EVENTS TABLE
-- ============================================
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    venue VARCHAR(100),
    event_type VARCHAR(50),
    ay_semester VARCHAR(50),
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    created_by INT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES officers(officer_id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB;

-- ============================================
-- 4. ATTENDANCE TABLE
-- ============================================
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL,
    event_id INT NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    ay_semester VARCHAR(50),
    attendance_date DATE NOT NULL,
    am_in BOOLEAN DEFAULT FALSE,
    am_out BOOLEAN DEFAULT FALSE,
    pm_in BOOLEAN DEFAULT FALSE,
    pm_out BOOLEAN DEFAULT FALSE,
    recorded_by INT,
    date_recorded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES students(id_number) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES officers(officer_id) ON DELETE SET NULL,
    INDEX idx_student_attendance (id_number),
    INDEX idx_event_attendance (event_id),
    INDEX idx_attendance_date (attendance_date)
) ENGINE=InnoDB;

-- ============================================
-- 5. PAYMENTS TABLE
-- ============================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL,
    payment_type ENUM('LMC Contribution', 'LMC T-Shirt', 'Membership Fee', 'Penalty', 'Other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    or_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('Paid', 'Pending', 'Verified') DEFAULT 'Pending',
    date_paid DATE NOT NULL,
    recorded_by INT,
    date_recorded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES students(id_number) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES officers(officer_id) ON DELETE SET NULL,
    INDEX idx_student_payments (id_number),
    INDEX idx_payment_status (status),
    INDEX idx_or_number (or_number)
) ENGINE=InnoDB;

-- ============================================
-- 6. PENALTIES TABLE
-- ============================================
CREATE TABLE penalties (
    penalty_id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL,
    event_id INT,
    event_name VARCHAR(100),
    violation VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Paid', 'Pending') DEFAULT 'Pending',
    violation_date DATE NOT NULL,
    recorded_by INT,
    date_recorded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES students(id_number) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES officers(officer_id) ON DELETE SET NULL,
    INDEX idx_student_penalties (id_number),
    INDEX idx_penalty_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 7. AUDIT LOGS TABLE
-- ============================================
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20),
    action_type VARCHAR(50) NOT NULL,
    description TEXT,
    performed_by INT,
    ip_address VARCHAR(45),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES students(id_number) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES officers(officer_id) ON DELETE SET NULL,
    INDEX idx_action_type (action_type),
    INDEX idx_date_created (date_created)
) ENGINE=InnoDB;

-- ============================================
-- 8. SYSTEM SETTINGS TABLE
-- ============================================
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Insert default admin/adviser
INSERT INTO officers (id_number, name, username, password, role, status) VALUES
('admin@ics.ph', 'Admin', 'admin@ics.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'adviser', 'Active');
-- Default password: 123

-- Insert sample officers
INSERT INTO officers (id_number, name, username, password, role, status) VALUES
('233375', 'Vincent Jay G. Pati-an', 'officer@ics.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'officer', 'Active'),
('232038', 'Piljoy A. Adala', '232038', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'officer', 'Active');

-- Insert sample students
INSERT INTO students (id_number, first_name, middle_name, last_name, email, password, course, year_level, section, religion, status) VALUES
('235375', 'Vincent', 'Jay', 'Pati-an', 'vjp@student.ics.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSCS', '3', 'A', 'Catholic', 'Active'),
('233300', 'Juan', '', 'Dela Cruz', 'juan@student.ics.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSCS', '2', 'B', 'Catholic', 'Active'),
('238825', 'Gena', 'Jenny', 'Ya', 'gena@student.ics.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSCS', '4', 'A', 'Christian', 'Active');

-- Insert sample events
INSERT INTO events (event_name, venue, event_type, ay_semester, event_date, start_time, end_time, created_by) VALUES
('ICS General Assembly', 'Gym', 'Assembly', '2025-2026, 1st Semester', '2025-10-05', '08:00:00', '12:00:00', 2),
('1st Officers Meeting', 'Office', 'Meeting', '2025-2026, 1st Semester', '2025-10-09', '13:00:00', '15:00:00', 2),
('ICS Orientation', 'Auditorium', 'Orientation', '2025-2026, 1st Semester', '2025-10-11', '09:00:00', '16:00:00', 2),
('SINAG 2025', 'Gym', 'Event', '2025-2026, 1st Semester', '2025-08-11', '08:00:00', '17:00:00', 2);

-- Insert sample attendance records
INSERT INTO attendance (id_number, event_id, event_name, ay_semester, attendance_date, am_in, am_out, pm_in, pm_out, recorded_by) VALUES
('235375', 1, 'ICS General Assembly', '2025-2026, 1st Semester', '2025-10-05', TRUE, TRUE, FALSE, FALSE, 3),
('235375', 2, '1st Officers Meeting', '2025-2026, 1st Semester', '2025-10-09', TRUE, TRUE, TRUE, TRUE, 2),
('235375', 3, 'ICS Orientation', '2025-2026, 1st Semester', '2025-10-11', FALSE, TRUE, TRUE, TRUE, 2);

-- Insert sample payments
INSERT INTO payments (id_number, payment_type, amount, or_number, status, date_paid, recorded_by) VALUES
('235375', 'LMC Contribution', 700.00, '2401', 'Verified', '2025-10-10', 2),
('235375', 'LMC T-Shirt', 300.00, '2402', 'Verified', '2025-10-11', 2),
('235375', 'Membership Fee', 50.00, '2403', 'Verified', '2025-10-11', 2),
('235375', 'Penalty', 100.00, '2398', 'Pending', '2025-10-09', 2),
('233375', 'LMC Contribution', 500.00, '2405', 'Paid', '2025-10-15', 2);

-- Insert sample penalties
INSERT INTO penalties (id_number, event_id, event_name, violation, amount, status, violation_date, recorded_by) VALUES
('235375', 1, 'ICS General Assembly', 'Absent', 25.00, 'Paid', '2025-10-05', 3),
('235375', 2, '1st Officers Meeting', 'Absent', 25.00, 'Pending', '2025-10-09', 2),
('235375', 3, 'ICS Orientation', 'Not Wearing ICS Shirt', 50.00, 'Paid', '2025-10-11', 2),
('232038', 4, 'SINAG 2025', 'Not Bringing Balloon', 100.00, 'Paid', '2025-08-11', 2);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value) VALUES
('last_backup_date', '2025-09-30'),
('backup_size', '250 MB'),
('next_backup', 'Daily @ 1AM');

-- ============================================
-- USEFUL VIEWS FOR REPORTING
-- ============================================

-- View: Student Payment Summary
CREATE VIEW view_student_payment_summary AS
SELECT 
    s.id_number,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.year_level,
    COUNT(p.payment_id) AS total_payments,
    SUM(p.amount) AS total_amount_paid,
    SUM(CASE WHEN p.status = 'Verified' THEN p.amount ELSE 0 END) AS verified_amount
FROM students s
LEFT JOIN payments p ON s.id_number = p.id_number
GROUP BY s.id_number;

-- View: Student Attendance Summary
CREATE VIEW view_student_attendance_summary AS
SELECT 
    s.id_number,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.year_level,
    COUNT(a.attendance_id) AS total_events_attended,
    SUM(CASE WHEN a.am_in = TRUE THEN 1 ELSE 0 END) AS am_in_count,
    SUM(CASE WHEN a.pm_in = TRUE THEN 1 ELSE 0 END) AS pm_in_count
FROM students s
LEFT JOIN attendance a ON s.id_number = a.id_number
GROUP BY s.id_number;

-- View: Pending Penalties Summary
CREATE VIEW view_pending_penalties AS
SELECT 
    s.id_number,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.year_level,
    p.event_name,
    p.violation,
    p.amount,
    p.violation_date
FROM students s
INNER JOIN penalties p ON s.id_number = p.id_number
WHERE p.status = 'Pending'
ORDER BY p.violation_date DESC;

-- ============================================
-- COMPLETION MESSAGE
-- ============================================
SELECT 'eTrackHub Database Created Successfully!' AS Status;