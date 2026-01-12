<?php
 require_once '../../config/init.php';

if (!checkRole('student')) {
    jsonResponse(false, "Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = $_SESSION['user_id'];
    $email = sanitize($_POST['email'] ?? '');
    $religion = sanitize($_POST['religion'] ?? '');
    
    // Validate
    if (empty($email) || !isValidEmail($email)) {
        jsonResponse(false, "Valid email is required");
    }
    
    try {
        // Check if email is already used by another student
        $stmt = $db->prepare("
            SELECT id_number FROM students 
            WHERE email = ? AND id_number != ?
        ");
        $stmt->execute([$email, $id_number]);
        
        if ($stmt->fetch()) {
            jsonResponse(false, "Email already in use");
        }
        
        // Update profile
        $stmt = $db->prepare("
            UPDATE students 
            SET email = ?, religion = ?
            WHERE id_number = ?
        ");
        $stmt->execute([$email, $religion, $id_number]);
        
        logAudit($id_number, 'PROFILE_UPDATED', 'Student updated profile', null);
        
        jsonResponse(true, "Profile updated successfully");
        
    } catch(Exception $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        jsonResponse(false, "Failed to update profile");
    }
} else {
    jsonResponse(false, "Invalid request method");
}