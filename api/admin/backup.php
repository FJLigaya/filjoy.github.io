<?php   
require_once '../../config/init.php';

if (!checkRole('adviser')) {
    jsonResponse(false, "Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'manual_backup') {
        // In production, trigger actual backup script
        // For now, just update the last backup date
        
        try {
            $current_date = date('Y-m-d');
            
            $stmt = $db->prepare("
                UPDATE system_settings 
                SET setting_value = ? 
                WHERE setting_key = 'last_backup_date'
            ");
            $stmt->execute([$current_date]);
            
            logAudit(null, 'MANUAL_BACKUP', 'Manual database backup initiated', $_SESSION['user_id']);
            
            jsonResponse(true, "Backup initiated successfully. New backup date: $current_date");
            
        } catch(Exception $e) {
            error_log("Backup Error: " . $e->getMessage());
            jsonResponse(false, "Backup failed");
        }
        
    } else if ($action === 'restore') {
        // This should be carefully implemented with confirmation
        jsonResponse(false, "Restore operation requires additional confirmation");
        
    } else if ($action === 'delete_old_logs') {
        try {
            // Delete audit logs older than 6 months
            $six_months_ago = date('Y-m-d', strtotime('-6 months'));
            
            $stmt = $db->prepare("DELETE FROM audit_logs WHERE date_created < ?");
            $stmt->execute([$six_months_ago]);
            
            $deleted_count = $stmt->rowCount();
            
            logAudit(null, 'LOGS_CLEANED', "Deleted $deleted_count old log entries", $_SESSION['user_id']);
            
            jsonResponse(true, "Deleted $deleted_count old log entries");
            
        } catch(Exception $e) {
            error_log("Delete Logs Error: " . $e->getMessage());
            jsonResponse(false, "Failed to delete old logs");
        }
    }
    
} else {
    jsonResponse(false, "Invalid request method");
}