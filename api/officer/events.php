<?php
require_once '../../config/init.php';

if (!checkRole('officer')) {
    jsonResponse(false, "Unauthorized access");
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            // Get all events
            $stmt = $db->query("
                SELECT * FROM events 
                ORDER BY event_date DESC
            ");
            $events = $stmt->fetchAll();
            
            jsonResponse(true, "Events retrieved", $events);
            break;
            
        case 'POST':
            // Create event
            $event_name = sanitize($_POST['event_name'] ?? '');
            $venue = sanitize($_POST['venue'] ?? '');
            $event_type = sanitize($_POST['event_type'] ?? '');
            $ay_semester = sanitize($_POST['ay_semester'] ?? '');
            $event_date = sanitize($_POST['event_date'] ?? '');
            $start_time = sanitize($_POST['start_time'] ?? '');
            $end_time = sanitize($_POST['end_time'] ?? '');
            
            if (empty($event_name) || empty($event_date)) {
                jsonResponse(false, "Event name and date are required");
            }
            
            $stmt = $db->prepare("
                INSERT INTO events 
                (event_name, venue, event_type, ay_semester, event_date, start_time, end_time, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $event_name, $venue, $event_type, $ay_semester, 
                $event_date, $start_time, $end_time, $_SESSION['user_id']
            ]);
            
            $event_id = $db->lastInsertId();
            
            logAudit(null, 'EVENT_CREATED', "Event created: $event_name", $_SESSION['user_id']);
            
            jsonResponse(true, "Event created successfully", ['event_id' => $event_id]);
            break;
            
        default:
            jsonResponse(false, "Invalid request method");
    }
    
} catch(Exception $e) {
    error_log("Event Management Error: " . $e->getMessage());
    jsonResponse(false, "Operation failed. Please try again.");
}