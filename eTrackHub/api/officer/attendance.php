<?php
require_once '../../config/init.php';

if (!checkRole('officer')) {
    jsonResponse(false, "Unauthorized access");
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            // Get all attendance records
            $stmt = $db->query("
                SELECT 
                    a.*,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    o.name as recorded_by_name
                FROM attendance a
                LEFT JOIN students s ON a.id_number = s.id_number
                LEFT JOIN officers o ON a.recorded_by = o.officer_id
                ORDER BY a.date_recorded DESC
            ");
            $attendance = $stmt->fetchAll();
            
            jsonResponse(true, "Attendance records retrieved", $attendance);
            break;
            
        case 'POST':
            // Record attendance
            $id_number = sanitize($_POST['id_number'] ?? '');
            $event_id = intval($_POST['event_id'] ?? 0);
            $attendance_date = sanitize($_POST['attendance_date'] ?? date('Y-m-d'));
            $am_in = isset($_POST['am_in']) ? 1 : 0;
            $am_out = isset($_POST['am_out']) ? 1 : 0;
            $pm_in = isset($_POST['pm_in']) ? 1 : 0;
            $pm_out = isset($_POST['pm_out']) ? 1 : 0;
            
            // Validation
            if (empty($id_number) || $event_id <= 0) {
                jsonResponse(false, "Student ID and Event are required");
            }
            
            // Get event details
            $stmt = $db->prepare("SELECT event_name, ay_semester FROM events WHERE event_id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch();
            
            if (!$event) {
                jsonResponse(false, "Event not found");
            }
            
            // Check if attendance already exists
            $stmt = $db->prepare("
                SELECT attendance_id 
                FROM attendance 
                WHERE id_number = ? AND event_id = ? AND attendance_date = ?
            ");
            $stmt->execute([$id_number, $event_id, $attendance_date]);
            
            if ($stmt->fetch()) {
                jsonResponse(false, "Attendance already recorded for this student on this date");
            }
            
            // Insert attendance
            $stmt = $db->prepare("
                INSERT INTO attendance 
                (id_number, event_id, event_name, ay_semester, attendance_date, am_in, am_out, pm_in, pm_out, recorded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_number,
                $event_id,
                $event['event_name'],
                $event['ay_semester'],
                $attendance_date,
                $am_in,
                $am_out,
                $pm_in,
                $pm_out,
                $_SESSION['user_id']
            ]);
            
            $attendance_id = $db->lastInsertId();
            
            logAudit(
                $id_number, 
                'ATTENDANCE_RECORDED', 
                "Attendance recorded for: " . $event['event_name'], 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Attendance recorded successfully", ['attendance_id' => $attendance_id]);
            break;
            
        case 'PUT':
            // Update attendance
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $attendance_id = intval($_PUT['attendance_id'] ?? 0);
            $am_in = isset($_PUT['am_in']) ? 1 : 0;
            $am_out = isset($_PUT['am_out']) ? 1 : 0;
            $pm_in = isset($_PUT['pm_in']) ? 1 : 0;
            $pm_out = isset($_PUT['pm_out']) ? 1 : 0;
            
            if ($attendance_id <= 0) {
                jsonResponse(false, "Invalid attendance ID");
            }
            
            // Get existing record
            $stmt = $db->prepare("SELECT * FROM attendance WHERE attendance_id = ?");
            $stmt->execute([$attendance_id]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                jsonResponse(false, "Attendance record not found");
            }
            
            // Update attendance
            $stmt = $db->prepare("
                UPDATE attendance 
                SET am_in = ?, am_out = ?, pm_in = ?, pm_out = ?
                WHERE attendance_id = ?
            ");
            $stmt->execute([$am_in, $am_out, $pm_in, $pm_out, $attendance_id]);
            
            logAudit(
                $existing['id_number'], 
                'ATTENDANCE_UPDATED', 
                "Attendance updated for: " . $existing['event_name'], 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Attendance updated successfully");
            break;
            
        case 'DELETE':
            parse_str(file_get_contents("php://input"), $_DELETE);
            $attendance_id = intval($_DELETE['attendance_id'] ?? 0);
            
            if ($attendance_id <= 0) {
                jsonResponse(false, "Invalid attendance ID");
            }
            
            // Get attendance details
            $stmt = $db->prepare("SELECT * FROM attendance WHERE attendance_id = ?");
            $stmt->execute([$attendance_id]);
            $attendance = $stmt->fetch();
            
            if (!$attendance) {
                jsonResponse(false, "Attendance record not found");
            }
            
            // Delete attendance
            $stmt = $db->prepare("DELETE FROM attendance WHERE attendance_id = ?");
            $stmt->execute([$attendance_id]);
            
            logAudit(
                $attendance['id_number'], 
                'ATTENDANCE_DELETED', 
                "Attendance deleted for: " . $attendance['event_name'], 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Attendance deleted successfully");
            break;
            
        default:
            jsonResponse(false, "Invalid request method");
    }
    
} catch(Exception $e) {
    error_log("Attendance CRUD Error: " . $e->getMessage());
    jsonResponse(false, "Operation failed. Please try again.");
}