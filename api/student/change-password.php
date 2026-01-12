<?php

require_once '../../config/init.php';

if (!checkRole('student')) {
    jsonResponse(false, "Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        jsonResponse(false, "All fields are required");
    }
    
    if (strlen($new_password) < 6) {
        jsonResponse(false, "New password must be at least 6 characters");
    }
    
    if ($new_password !== $confirm_password) {
        jsonResponse(false, "New passwords do not match");
    }
    
    try {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM students WHERE id_number = ?");
        $stmt->execute([$id_number]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($current_password, $user['password'])) {
            jsonResponse(false, "Current password is incorrect");
        }
        
        // Update password
        $hashed_password = hashPassword($new_password);
        
        $stmt = $db->prepare("UPDATE students SET password = ? WHERE id_number = ?");
        $stmt->execute([$hashed_password, $id_number]);
        
        logAudit($id_number, 'PASSWORD_CHANGED', 'Student changed password', null);
        
        jsonResponse(true, "Password changed successfully");
        
    } catch(Exception $e) {
        error_log("Password Change Error: " . $e->getMessage());
        jsonResponse(false, "Failed to change password");
    }
} else {
    jsonResponse(false, "Invalid request method");
}