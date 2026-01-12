<?php

require_once '../../config/init.php';

if (!checkRole('student')) {
    jsonResponse(false, "Unauthorized access");
}

try {
    $id_number = $_SESSION['user_id'];
    
    // Total payments made
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_paid
        FROM payments
        WHERE id_number = ? AND status IN ('Paid', 'Verified')
    ");
    $stmt->execute([$id_number]);
    $payment_stats = $stmt->fetch();
    
    // Attendance count
    $stmt = $db->prepare("
        SELECT COUNT(*) as events_attended
        FROM attendance
        WHERE id_number = ?
    ");
    $stmt->execute([$id_number]);
    $attendance_stats = $stmt->fetch();
    
    // Total events
    $stmt = $db->query("SELECT COUNT(*) as total_events FROM events");
    $event_stats = $stmt->fetch();
    
    // Pending penalties
    $stmt = $db->prepare("
        SELECT COUNT(*) as pending_penalties
        FROM penalties
        WHERE id_number = ? AND status = 'Pending'
    ");
    $stmt->execute([$id_number]);
    $penalty_stats = $stmt->fetch();
    
    $stats = [
        'total_paid' => $payment_stats['total_paid'],
        'events_attended' => $attendance_stats['events_attended'],
        'total_events' => $event_stats['total_events'],
        'pending_penalties' => $penalty_stats['pending_penalties']
    ];
    
    jsonResponse(true, "Dashboard stats retrieved", $stats);
    
} catch(Exception $e) {
    error_log("Student Dashboard Stats Error: " . $e->getMessage());
    jsonResponse(false, "Failed to load statistics");
}
