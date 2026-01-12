<?php 
require_once '../config/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $id_number = sanitize($_POST['id_number'] ?? '');
    $first_name = sanitize($_POST['first_name'] ?? '');
    $middle_name = sanitize($_POST['middle_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $course = sanitize($_POST['course'] ?? 'BSCS');
    $year_level = sanitize($_POST['year_level'] ?? '');
    $religion = sanitize($_POST['religion'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($id_number)) $errors[] = "ID number is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email) || !isValidEmail($email)) $errors[] = "Valid email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($year_level)) $errors[] = "Year level is required";
    if (empty($religion)) $errors[] = "Religion is required";
    
    if (!empty($errors)) {
        jsonResponse(false, implode(", ", $errors));
    }
    
    try {
        // Check if ID number already exists
        $stmt = $db->prepare("SELECT id_number FROM students WHERE id_number = ?");
        $stmt->execute([$id_number]);
        if ($stmt->fetch()) {
            jsonResponse(false, "ID number already registered");
        }
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT email FROM students WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(false, "Email already registered");
        }
        
        // Hash password
        $hashed_password = hashPassword($password);
        
        // Insert student
        $stmt = $db->prepare("
            INSERT INTO students 
            (id_number, first_name, middle_name, last_name, email, password, course, year_level, religion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id_number, 
            $first_name, 
            $middle_name, 
            $last_name, 
            $email, 
            $hashed_password, 
            $course, 
            $year_level, 
            $religion
        ]);
        
        logAudit($id_number, 'REGISTRATION', 'New student registered', null);
        
        jsonResponse(true, "Registration successful! You can now log in.", [
            'id_number' => $id_number
        ]);
        
    } catch(Exception $e) {
        error_log("Registration Error: " . $e->getMessage());
        jsonResponse(false, "Registration failed. Please try again.");
    }
} else {
    jsonResponse(false, "Invalid request method");
}