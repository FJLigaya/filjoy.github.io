<?php
 require_once '../../config/init.php';

if (!checkRole('adviser')) {
    jsonResponse(false, "Unauthorized access");
}

try {
    // Payment statistics
    $stmt = $db->query("
        SELECT 
            payment_type,
            COUNT(*) as count,
            SUM(amount) as total
        FROM payments
        WHERE status IN ('Paid', 'Verified')
        GROUP BY payment_type
    ");
    $payment_stats = $stmt->fetchAll();
    
    // Penalty statistics
    $stmt = $db->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(amount) as total
        FROM penalties
        GROUP BY status
    ");
    $penalty_stats = $stmt->fetchAll();
    
    // Student statistics
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_students,
            SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive_students
        FROM students
    ");
    $student_stats = $stmt->fetch();
    
    // Officer count
    $stmt = $db->query("
        SELECT COUNT(*) as total_officers
        FROM officers
        WHERE role = 'officer' AND status = 'Active'
    ");
    $officer_stats = $stmt->fetch();
    
    // Total collections
    $stmt = $db->query("
        SELECT 
            COALESCE(SUM(amount), 0) as total_payments
        FROM payments
        WHERE status IN ('Paid', 'Verified')
    ");
    $total_payments = $stmt->fetch();
    
    $stmt = $db->query("
        SELECT 
            COALESCE(SUM(amount), 0) as total_penalties
        FROM penalties
        WHERE status = 'Paid'
    ");
    $total_penalties = $stmt->fetch();
    
    $report = [
        'payment_breakdown' => $payment_stats,
        'penalty_breakdown' => $penalty_stats,
        'student_stats' => $student_stats,
        'total_officers' => $officer_stats['total_officers'],
        'total_collections' => [
            'payments' => $total_payments['total_payments'],
            'penalties' => $total_penalties['total_penalties'],
            'grand_total' => $total_payments['total_payments'] + $total_penalties['total_penalties']
        ]
    ];
    
    jsonResponse(true, "Report generated", $report);
    
} catch(Exception $e) {
    error_log("Reports Error: " . $e->getMessage());
    jsonResponse(false, "Failed to generate report");
}