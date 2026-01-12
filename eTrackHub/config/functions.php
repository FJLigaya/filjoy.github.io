<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check user role
function checkRole($allowedRoles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($allowedRoles)) {
        return in_array($_SESSION['role'], $allowedRoles);
    }
    
    return $_SESSION['role'] === $allowedRoles;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// JSON response helper
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Get student name by ID
function getStudentNameById($id_number) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM students WHERE id_number = ?");
    $stmt->execute([$id_number]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : null;
}

// Get officer name by ID
function getOfficerNameById($officer_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT name FROM officers WHERE officer_id = ?");
    $stmt->execute([$officer_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : null;
}

// Log audit trail
function logAudit($id_number, $action_type, $description, $performed_by = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (id_number, action_type, description, performed_by, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$id_number, $action_type, $description, $performed_by, $ip_address]);
    } catch(Exception $e) {
        error_log("Audit Log Error: " . $e->getMessage());
    }
}

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

// Get current academic year
function getCurrentAcademicYear() {
    $currentYear = date('Y');
    $currentMonth = date('n');
    
    if ($currentMonth >= 6) {
        return $currentYear . '-' . ($currentYear + 1);
    } else {
        return ($currentYear - 1) . '-' . $currentYear;
    }
}

// Error handler
function handleError($message, $code = 500) {
    http_response_code($code);
    error_log($message);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
        jsonResponse(false, "An error occurred. Please try again.", null);
    } else {
        echo "<h1>Error</h1><p>$message</p>";
        exit();
    }
}

