<?php 

require_once '../../config/init.php';

if (!checkRole('officer')) {
    jsonResponse(false, "Unauthorized access");
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            // Get all penalties
            $stmt = $db->query("
                SELECT 
                    p.*,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    o.name as recorded_by_name
                FROM penalties p
                LEFT JOIN students s ON p.id_number = s.id_number
                LEFT JOIN officers o ON p.recorded_by = o.officer_id
                ORDER BY p.date_recorded DESC
            ");
            $penalties = $stmt->fetchAll();
            
            jsonResponse(true, "Penalties retrieved", $penalties);
            break;
            
        case 'POST':
            // Add penalty
            $id_number = sanitize($_POST['id_number'] ?? '');
            $event_id = intval($_POST['event_id'] ?? 0);
            $violation = sanitize($_POST['violation'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'Pending');
            $violation_date = sanitize($_POST['violation_date'] ?? date('Y-m-d'));
            
            if (empty($id_number) || empty($violation) || $amount <= 0) {
                jsonResponse(false, "All fields are required and amount must be greater than 0");
            }
            
            // Get event name if event_id provided
            $event_name = null;
            if ($event_id > 0) {
                $stmt = $db->prepare("SELECT event_name FROM events WHERE event_id = ?");
                $stmt->execute([$event_id]);
                $event = $stmt->fetch();
                $event_name = $event ? $event['event_name'] : null;
            }
            
            // Insert penalty
            $stmt = $db->prepare("
                INSERT INTO penalties 
                (id_number, event_id, event_name, violation, amount, status, violation_date, recorded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_number,
                $event_id > 0 ? $event_id : null,
                $event_name,
                $violation,
                $amount,
                $status,
                $violation_date,
                $_SESSION['user_id']
            ]);
            
            $penalty_id = $db->lastInsertId();
            
            logAudit(
                $id_number, 
                'PENALTY_ADDED', 
                "Penalty added: $violation - " . formatCurrency($amount), 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Penalty added successfully", ['penalty_id' => $penalty_id]);
            break;
            
        case 'PUT':
            // Update penalty
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $penalty_id = intval($_PUT['penalty_id'] ?? 0);
            $violation = sanitize($_PUT['violation'] ?? '');
            $amount = floatval($_PUT['amount'] ?? 0);
            $status = sanitize($_PUT['status'] ?? '');
            
            if ($penalty_id <= 0) {
                jsonResponse(false, "Invalid penalty ID");
            }
            
            // Get existing penalty
            $stmt = $db->prepare("SELECT * FROM penalties WHERE penalty_id = ?");
            $stmt->execute([$penalty_id]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                jsonResponse(false, "Penalty not found");
            }
            
            // Update penalty
            $stmt = $db->prepare("
                UPDATE penalties 
                SET violation = ?, amount = ?, status = ?
                WHERE penalty_id = ?
            ");
            $stmt->execute([$violation, $amount, $status, $penalty_id]);
            
            logAudit(
                $existing['id_number'], 
                'PENALTY_UPDATED', 
                "Penalty updated: $violation", 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Penalty updated successfully");
            break;
            
        case 'DELETE':
            parse_str(file_get_contents("php://input"), $_DELETE);
            $penalty_id = intval($_DELETE['penalty_id'] ?? 0);
            
            if ($penalty_id <= 0) {
                jsonResponse(false, "Invalid penalty ID");
            }
            
            // Get penalty details
            $stmt = $db->prepare("SELECT * FROM penalties WHERE penalty_id = ?");
            $stmt->execute([$penalty_id]);
            $penalty = $stmt->fetch();
            
            if (!$penalty) {
                jsonResponse(false, "Penalty not found");
            }
            
            // Delete penalty
            $stmt = $db->prepare("DELETE FROM penalties WHERE penalty_id = ?");
            $stmt->execute([$penalty_id]);
            
            logAudit(
                $penalty['id_number'], 
                'PENALTY_DELETED', 
                "Penalty deleted: " . $penalty['violation'], 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Penalty deleted successfully");
            break;
            
        default:
            jsonResponse(false, "Invalid request method");
    }
    
} catch(Exception $e) {
    error_log("Penalty CRUD Error: " . $e->getMessage());
    jsonResponse(false, "Operation failed. Please try again.");
}
