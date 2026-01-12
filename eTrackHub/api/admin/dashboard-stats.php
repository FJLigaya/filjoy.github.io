<?php
require_once '../../config/init.php';

if (!checkRole('adviser')) {
    jsonResponse(false, "Unauthorized access");
}

try {
    // Total registered students
    $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE status = 'Active'");
    $total_students = $stmt->fetch()['count'];
    
    // Total officers
    $stmt = $db->query("SELECT COUNT(*) as count FROM officers WHERE role = 'officer' AND status = 'Active'");
    $total_officers = $stmt->fetch()['count'];
    
    // Total payments collected
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status IN ('Paid', 'Verified')");
    $total_collected = $stmt->fetch()['total'];
    
    // Pending penalties
    $stmt = $db->query("SELECT COUNT(*) as count FROM penalties WHERE status = 'Pending'");
    $pending_penalties = $stmt->fetch()['count'];
    
    // Last backup date (from settings)
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'last_backup_date'");
    $stmt->execute();
    $last_backup = $stmt->fetch();
    
    $stats = [
        'total_students' => $total_students,
        'total_officers' => $total_officers,
        'total_collected' => $total_collected,
        'pending_penalties' => $pending_penalties,
        'last_backup_date' => $last_backup ? $last_backup['setting_value'] : 'Never'
    ];
    
    jsonResponse(true, "Dashboard stats retrieved", $stats);
    
} catch(Exception $e) {
    error_log("Admin Dashboard Stats Error: " . $e->getMessage());
    jsonResponse(false, "Failed to load statistics");
}
