<?php

require_once '../../config/init.php';

if (!checkRole('student')) {
    jsonResponse(false, "Unauthorized access");
}

try {
    $id_number = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        SELECT 
            attendance_id,
            event_name,
            ay_semester,
            attendance_date,
            am_in,
            am_out,
            pm_in,
            pm_out,
            recorded_by,
            date_recorded
        FROM attendance
        WHERE id_number = ?
        ORDER BY attendance_date DESC
    ");
    
    $stmt->execute([$id_number]);
    $attendance = $stmt->fetchAll();
    
    // Get officer names
    foreach ($attendance as &$record) {
        if ($record['recorded_by']) {
            $record['recorded_by_name'] = getOfficerNameById($record['recorded_by']);
        }
    }
    
    jsonResponse(true, "Attendance records retrieved", $attendance);
    
} catch(Exception $e) {
    error_log("Student Attendance Error: " . $e->getMessage());
    jsonResponse(false, "Failed to load attendance");
}