<?php
 require_once '../config/init.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    if ($role === 'student') {
        logAudit($user_id, 'LOGOUT', 'Student logged out', null);
    } else {
        logAudit(null, 'LOGOUT', ucfirst($role) . ' logged out', $user_id);
    }
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Start new session for response
    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        jsonResponse(true, "Logged out successfully", ['redirect' => '../index.html']);
    } else {
        redirect('../index.html');
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        jsonResponse(false, "No active session");
    } else {
        redirect('../index.html');
    }
}