<?Php
require_once '../../config/init.php';

if (!checkRole('officer')) {
    jsonResponse(false, "Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_number = sanitize($_GET['id_number'] ?? '');
    
    if (empty($id_number)) {
        jsonResponse(false, "ID number is required");
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                id_number,
                CONCAT(first_name, ' ', last_name) as name,
                year_level,
                course,
                status
            FROM students 
            WHERE id_number = ?
        ");
        $stmt->execute([$id_number]);
        $student = $stmt->fetch();
        
        if ($student) {
            if ($student['status'] !== 'Active') {
                jsonResponse(false, "Student account is inactive");
            }
            
            jsonResponse(true, "Student found", $student);
        } else {
            jsonResponse(false, "Student not found");
        }
        
    } catch(Exception $e) {
        error_log("Get Student Error: " . $e->getMessage());
        jsonResponse(false, "Failed to fetch student information");
    }
} else {
    jsonResponse(false, "Invalid request method");
}