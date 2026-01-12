<?php
require_once '../config/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email) || !isValidEmail($email)) {
        jsonResponse(false, "Valid email is required");
    }
    
    try {
        // Check if email exists
        $stmt = $db->prepare("SELECT id_number, CONCAT(first_name, ' ', last_name) as name FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        
        if ($student) {
            // In production, send email with reset link
            // For now, just log the request
            logAudit($student['id_number'], 'PASSWORD_RESET_REQUEST', 'Password reset requested for ' . $email, null);
            
            // Always show success message (security best practice - don't reveal if email exists)
            jsonResponse(true, "If this email exists in our system, password reset instructions have been sent.");
        } else {
            // Don't reveal that email doesn't exist
            jsonResponse(true, "If this email exists in our system, password reset instructions have been sent.");
        }
        
    } catch(Exception $e) {
        error_log("Password Reset Error: " . $e->getMessage());
        jsonResponse(false, "Password reset request failed. Please try again.");
    }
} else {
    jsonResponse(false, "Invalid request method");
}