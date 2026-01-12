<?php
require_once '../../config/init.php';

if (!checkRole('student')) {
    jsonResponse(false, "Unauthorized access");
}

try {
    $id_number = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        SELECT 
            payment_id,
            payment_type,
            amount,
            or_number,
            status,
            date_paid,
            date_recorded
        FROM payments
        WHERE id_number = ?
        ORDER BY date_recorded DESC
    ");
    
    $stmt->execute([$id_number]);
    $payments = $stmt->fetchAll();
    
    jsonResponse(true, "Payments retrieved", $payments);
    
} catch(Exception $e) {
    error_log("Student Payments Error: " . $e->getMessage());
    jsonResponse(false, "Failed to load payments");
}