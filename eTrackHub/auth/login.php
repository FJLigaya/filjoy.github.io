<?php
require_once '../config/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($role)) {
        jsonResponse(false, "All fields are required");
    }
    
    try {
        if ($role === 'student') {
            // Student login
            $stmt = $db->prepare("
                SELECT id_number, CONCAT(first_name, ' ', last_name) as name, password, status 
                FROM students 
                WHERE id_number = ? AND status = 'Active'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_number'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = 'student';
                
                logAudit($user['id_number'], 'LOGIN', 'Student logged in', null);
                
                jsonResponse(true, "Login successful", [
                    'role' => 'student',
                    'redirect' => 'student/dashboard.php'
                ]);
            }
            
        } else if ($role === 'officer' || $role === 'adviser') {
            // Officer/Adviser login
            $stmt = $db->prepare("
                SELECT officer_id, id_number, name, password, role, status 
                FROM officers 
                WHERE (username = ? OR id_number = ?) AND role = ? AND status = 'Active'
            ");
            $stmt->execute([$username, $username, $role]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                $_SESSION['user_id'] = $user['officer_id'];
                $_SESSION['id_number'] = $user['id_number'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                logAudit(null, 'LOGIN', ucfirst($role) . ' logged in', $user['officer_id']);
                
                $redirect = $role === 'adviser' ? 'admin/dashboard.php' : 'officer/dashboard.php';
                
                jsonResponse(true, "Login successful", [
                    'role' => $role,
                    'redirect' => $redirect
                ]);
            }
        }
        
        // If we reach here, login failed
        jsonResponse(false, "Invalid credentials or inactive account");
        
    } catch(Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        jsonResponse(false, "Login failed. Please try again.");
    }
} else {
    jsonResponse(false, "Invalid request method");
}