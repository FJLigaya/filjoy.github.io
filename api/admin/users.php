<?php
require_once '../../config/init.php';

if (!checkRole('adviser')) {
    jsonResponse(false, "Unauthorized access");
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            // Get all users (excluding advisers)
            $stmt = $db->query("
                SELECT 
                    s.id_number,
                    CONCAT(s.first_name, ' ', s.last_name) as name,
                    s.year_level,
                    s.status,
                    'Student' as role
                FROM students s
                UNION ALL
                SELECT 
                    o.id_number,
                    o.name,
                    'N/A' as year_level,
                    o.status,
                    CONCAT(UPPER(SUBSTRING(o.role, 1, 1)), SUBSTRING(o.role, 2)) as role
                FROM officers o
                WHERE o.role = 'officer'
                ORDER BY role DESC, name ASC
            ");
            
            $users = $stmt->fetchAll();
            jsonResponse(true, "Users retrieved", $users);
            break;
            
        case 'PUT':
            // Update user status or role
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $id_number = sanitize($_PUT['id_number'] ?? '');
            $action = sanitize($_PUT['action'] ?? ''); // 'toggle_status' or 'change_role'
            
            if (empty($id_number) || empty($action)) {
                jsonResponse(false, "Invalid parameters");
            }
            
            if ($action === 'toggle_status') {
                // Check if student or officer
                $stmt = $db->prepare("SELECT id_number, status FROM students WHERE id_number = ?");
                $stmt->execute([$id_number]);
                $student = $stmt->fetch();
                
                if ($student) {
                    $new_status = $student['status'] === 'Active' ? 'Inactive' : 'Active';
                    $stmt = $db->prepare("UPDATE students SET status = ? WHERE id_number = ?");
                    $stmt->execute([$new_status, $id_number]);
                    
                    logAudit($id_number, 'STATUS_CHANGED', "Status changed to $new_status", $_SESSION['user_id']);
                    jsonResponse(true, "Status updated to $new_status");
                } else {
                    // Try officer
                    $stmt = $db->prepare("SELECT officer_id, status FROM officers WHERE id_number = ?");
                    $stmt->execute([$id_number]);
                    $officer = $stmt->fetch();
                    
                    if ($officer) {
                        $new_status = $officer['status'] === 'Active' ? 'Inactive' : 'Active';
                        $stmt = $db->prepare("UPDATE officers SET status = ? WHERE id_number = ?");
                        $stmt->execute([$new_status, $id_number]);
                        
                        logAudit(null, 'OFFICER_STATUS_CHANGED', "Officer $id_number status changed to $new_status", $_SESSION['user_id']);
                        jsonResponse(true, "Status updated to $new_status");
                    } else {
                        jsonResponse(false, "User not found");
                    }
                }
            } else if ($action === 'change_role') {
                $new_role = sanitize($_PUT['new_role'] ?? '');
                
                if (empty($new_role)) {
                    jsonResponse(false, "New role is required");
                }
                
                // Only officers can have role changed
                $stmt = $db->prepare("UPDATE officers SET role = ? WHERE id_number = ?");
                $stmt->execute([$new_role, $id_number]);
                
                if ($stmt->rowCount() > 0) {
                    logAudit(null, 'ROLE_CHANGED', "Officer $id_number role changed to $new_role", $_SESSION['user_id']);
                    jsonResponse(true, "Role updated successfully");
                } else {
                    jsonResponse(false, "User not found or not an officer");
                }
            }
            break;
            
        case 'DELETE':
            // Delete user
            parse_str(file_get_contents("php://input"), $_DELETE);
            $id_number = sanitize($_DELETE['id_number'] ?? '');
            
            if (empty($id_number)) {
                jsonResponse(false, "ID number is required");
            }
            
            // Try to delete student first
            $stmt = $db->prepare("DELETE FROM students WHERE id_number = ?");
            $stmt->execute([$id_number]);
            
            if ($stmt->rowCount() > 0) {
                logAudit($id_number, 'ACCOUNT_DELETED', "Student account deleted", $_SESSION['user_id']);
                jsonResponse(true, "Student account deleted");
            } else {
                // Try officer
                $stmt = $db->prepare("DELETE FROM officers WHERE id_number = ? AND role != 'adviser'");
                $stmt->execute([$id_number]);
                
                if ($stmt->rowCount() > 0) {
                    logAudit(null, 'ACCOUNT_DELETED', "Officer $id_number account deleted", $_SESSION['user_id']);
                    jsonResponse(true, "Officer account deleted");
                } else {
                    jsonResponse(false, "User not found or cannot be deleted");
                }
            }
            break;
            
        default:
            jsonResponse(false, "Invalid request method");
    }
    
} catch(Exception $e) {
    error_log("User Management Error: " . $e->getMessage());
    jsonResponse(false, "Operation failed");
}