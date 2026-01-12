<?php
require_once '../../config/init.php';

if (!checkRole('student')) {
    jsonResponse(false, "Unauthorized access");
}

try {
    $id_number = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        SELECT 
            penalty_id,
            event_name,
            violation,
            amount,
            status,
            violation_date,
            recorded_by,
            date_recorded
        FROM penalties
        WHERE id_number = ?
        ORDER BY violation_date DESC
    ");
    
    $stmt->execute([$id_number]);
    $penalties = $stmt->fetchAll();
    
    // Get officer names
    foreach ($penalties as &$record) {
        if ($record['recorded_by']) {
            $record['recorded_by_name'] = getOfficerNameById($record['recorded_by']);
        } else {
            $record['recorded_by_name'] = 'N/A';
        }
    }
    
    jsonResponse(true, "Penalties retrieved", $penalties);
    
} catch(Exception $e) {
    error_log("Student Penalties Error: " . $e->getMessage());
    jsonResponse(false, "Failed to load penalties");
}