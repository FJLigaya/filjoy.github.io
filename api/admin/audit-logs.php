<?php

require_once '../../config/init.php';

if (!checkRole('adviser')) {
    jsonResponse(false, "Unauthorized access");
}

try {
    $stmt = $db->query("
        SELECT 
            al.log_id,
            al.id_number,
            COALESCE(CONCAT(s.first_name, ' ', s.last_name), 'N/A') as student_name,
            al.action_type,
            al.description,
            o.name as officer_name,
            al.date_created
        FROM audit_logs al
        LEFT JOIN students s ON al.id_number = s.id_number
        LEFT JOIN officers o ON al.performed_by = o.officer_id
        ORDER BY al.date_created DESC
        LIMIT 100
    ");
    
    $logs = $stmt->fetchAll();
    jsonResponse(true, "Audit logs retrieved", $logs);
    
} catch(Exception $e) {
    error_log("Audit Logs Error: " . $e->getMessage());
    jsonResponse(false, "Failed to load audit logs");
}
