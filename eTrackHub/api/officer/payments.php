<?php
require_once '../../config/init.php';

if (!checkRole('officer')) {
    jsonResponse(false, "Unauthorized access");
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            // Get all payments or specific payment
            $payment_id = $_GET['id'] ?? null;
            
            if ($payment_id) {
                $stmt = $db->prepare("
                    SELECT p.*, CONCAT(s.first_name, ' ', s.last_name) as student_name
                    FROM payments p
                    LEFT JOIN students s ON p.id_number = s.id_number
                    WHERE p.payment_id = ?
                ");
                $stmt->execute([$payment_id]);
                $payment = $stmt->fetch();
                
                jsonResponse(true, "Payment retrieved", $payment);
            } else {
                $stmt = $db->query("
                    SELECT 
                        p.*,
                        CONCAT(s.first_name, ' ', s.last_name) as student_name,
                        o.name as recorded_by_name
                    FROM payments p
                    LEFT JOIN students s ON p.id_number = s.id_number
                    LEFT JOIN officers o ON p.recorded_by = o.officer_id
                    ORDER BY p.date_recorded DESC
                ");
                $payments = $stmt->fetchAll();
                
                jsonResponse(true, "Payments retrieved", $payments);
            }
            break;
            
        case 'POST':
            // Create new payment
            $id_number = sanitize($_POST['id_number'] ?? '');
            $payment_type = sanitize($_POST['payment_type'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $or_number = sanitize($_POST['or_number'] ?? '');
            $status = sanitize($_POST['status'] ?? 'Paid');
            $date_paid = sanitize($_POST['date_paid'] ?? date('Y-m-d'));
            
            // Validation
            if (empty($id_number) || empty($payment_type) || $amount <= 0 || empty($or_number)) {
                jsonResponse(false, "All fields are required and amount must be greater than 0");
            }
            
            // Verify student exists
            $stmt = $db->prepare("SELECT id_number FROM students WHERE id_number = ?");
            $stmt->execute([$id_number]);
            if (!$stmt->fetch()) {
                jsonResponse(false, "Student not found");
            }
            
            // Check if OR number already exists
            $stmt = $db->prepare("SELECT payment_id FROM payments WHERE or_number = ?");
            $stmt->execute([$or_number]);
            if ($stmt->fetch()) {
                jsonResponse(false, "OR number already exists");
            }
            
            // Insert payment
            $stmt = $db->prepare("
                INSERT INTO payments 
                (id_number, payment_type, amount, or_number, status, date_paid, recorded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_number, 
                $payment_type, 
                $amount, 
                $or_number, 
                $status, 
                $date_paid, 
                $_SESSION['user_id']
            ]);
            
            $payment_id = $db->lastInsertId();
            
            logAudit(
                $id_number, 
                'PAYMENT_ADDED', 
                "Payment recorded: $payment_type - " . formatCurrency($amount), 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Payment recorded successfully", ['payment_id' => $payment_id]);
            break;
            
        case 'PUT':
            // Update payment
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $payment_id = intval($_PUT['payment_id'] ?? 0);
            $payment_type = sanitize($_PUT['payment_type'] ?? '');
            $amount = floatval($_PUT['amount'] ?? 0);
            $or_number = sanitize($_PUT['or_number'] ?? '');
            $status = sanitize($_PUT['status'] ?? '');
            $date_paid = sanitize($_PUT['date_paid'] ?? '');
            
            if ($payment_id <= 0) {
                jsonResponse(false, "Invalid payment ID");
            }
            
            // Get existing payment
            $stmt = $db->prepare("SELECT * FROM payments WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                jsonResponse(false, "Payment not found");
            }
            
            // Check OR number uniqueness (excluding current record)
            $stmt = $db->prepare("SELECT payment_id FROM payments WHERE or_number = ? AND payment_id != ?");
            $stmt->execute([$or_number, $payment_id]);
            if ($stmt->fetch()) {
                jsonResponse(false, "OR number already exists");
            }
            
            // Update payment
            $stmt = $db->prepare("
                UPDATE payments 
                SET payment_type = ?, amount = ?, or_number = ?, status = ?, date_paid = ?
                WHERE payment_id = ?
            ");
            $stmt->execute([$payment_type, $amount, $or_number, $status, $date_paid, $payment_id]);
            
            logAudit(
                $existing['id_number'], 
                'PAYMENT_UPDATED', 
                "Payment updated: OR #$or_number", 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Payment updated successfully");
            break;
            
        case 'DELETE':
            parse_str(file_get_contents("php://input"), $_DELETE);
            $payment_id = intval($_DELETE['payment_id'] ?? 0);
            
            if ($payment_id <= 0) {
                jsonResponse(false, "Invalid payment ID");
            }
            
            // Get payment details before deletion
            $stmt = $db->prepare("SELECT * FROM payments WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                jsonResponse(false, "Payment not found");
            }
            
            // Delete payment
            $stmt = $db->prepare("DELETE FROM payments WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            
            logAudit(
                $payment['id_number'], 
                'PAYMENT_DELETED', 
                "Payment deleted: OR #" . $payment['or_number'], 
                $_SESSION['user_id']
            );
            
            jsonResponse(true, "Payment deleted successfully");
            break;
            
        default:
            jsonResponse(false, "Invalid request method");
    }
    
} catch(Exception $e) {
    error_log("Payment CRUD Error: " . $e->getMessage());
    jsonResponse(false, "Operation failed. Please try again.");
}