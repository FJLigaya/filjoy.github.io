<?php
require_once '../config/init.php';

// Check authorization (officer or adviser)
if (!checkRole(['officer', 'adviser'])) {
    die('Unauthorized access');
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$payment_type = $_GET['payment_type'] ?? 'all';

try {
    // Build query
    $query = "
        SELECT 
            p.*,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.year_level,
            o.name as recorded_by_name
        FROM payments p
        LEFT JOIN students s ON p.id_number = s.id_number
        LEFT JOIN officers o ON p.recorded_by = o.officer_id
        WHERE p.date_paid BETWEEN ? AND ?
    ";
    
    $params = [$start_date, $end_date];
    
    if ($payment_type !== 'all') {
        $query .= " AND p.payment_type = ?";
        $params[] = $payment_type;
    }
    
    $query .= " ORDER BY p.date_paid DESC, p.payment_id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
    
    // Calculate totals
    $total_amount = 0;
    $total_verified = 0;
    $total_pending = 0;
    
    foreach ($payments as $payment) {
        $total_amount += $payment['amount'];
        if ($payment['status'] === 'Verified' || $payment['status'] === 'Paid') {
            $total_verified += $payment['amount'];
        } else {
            $total_pending += $payment['amount'];
        }
    }
    
} catch(Exception $e) {
    error_log("Payment Report Error: " . $e->getMessage());
    die("Error generating report");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Report - eTrackHub</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #8B0000; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        .summary { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .summary-item { display: inline-block; margin-right: 30px; }
        .summary-label { font-weight: bold; color: #333; }
        .summary-value { color: #38761D; font-size: 1.2em; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #8B0000; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .print-btn { background: #38761D; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print Report</button>
    <button class="print-btn no-print" onclick="window.close()">Close</button>
    
    <div class="header">
        <h1>PAYMENT REPORT</h1>
        <p>eTrackHub - Institute of Computer Studies</p>
        <p>Period: <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?></p>
        <?php if ($payment_type !== 'all'): ?>
            <p>Payment Type: <?php echo htmlspecialchars($payment_type); ?></p>
        <?php endif; ?>
        <p>Generated: <?php echo date('F d, Y g:i A'); ?></p>
    </div>
    
    <div class="summary">
        <div class="summary-item">
            <span class="summary-label">Total Payments:</span>
            <span class="summary-value"><?php echo count($payments); ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Amount:</span>
            <span class="summary-value">₱<?php echo number_format($total_amount, 2); ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Verified:</span>
            <span class="summary-value">₱<?php echo number_format($total_verified, 2); ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Pending:</span>
            <span class="summary-value" style="color: #8B0000;">₱<?php echo number_format($total_pending, 2); ?></span>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>OR No.</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Year Level</th>
                <th>Payment Type</th>
                <th>Amount</th>
                <th>Date Paid</th>
                <th>Status</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="9" style="text-align:center;">No payment records found</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['or_number']); ?></td>
                        <td><?php echo htmlspecialchars($p['id_number']); ?></td>
                        <td><?php echo htmlspecialchars($p['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($p['payment_type']); ?></td>
                        <td>₱<?php echo number_format($p['amount'], 2); ?></td>
                        <td><?php echo date('M d, Y', strtotime($p['date_paid'])); ?></td>
                        <td style="color: <?php echo $p['status'] === 'Verified' ? '#38761D' : '#8B0000'; ?>">
                            <?php echo htmlspecialchars($p['status']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['recorded_by_name'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
