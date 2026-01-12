<?php
require_once '../config/init.php';

if (!checkRole('adviser')) {
    die('Unauthorized access - Adviser only');
}

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$action_type = $_GET['action_type'] ?? 'all';

try {
    $query = "
        SELECT 
            al.*,
            CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as student_name,
            o.name as officer_name
        FROM audit_logs al
        LEFT JOIN students s ON al.id_number = s.id_number
        LEFT JOIN officers o ON al.performed_by = o.officer_id
        WHERE DATE(al.date_created) BETWEEN ? AND ?
    ";
    
    $params = [$start_date, $end_date];
    
    if ($action_type !== 'all') {
        $query .= " AND al.action_type = ?";
        $params[] = $action_type;
    }
    
    $query .= " ORDER BY al.date_created DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
} catch(Exception $e) {
    error_log("Audit Report Error: " . $e->getMessage());
    die("Error generating report");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Report - eTrackHub</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #8B0000; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.85em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #8B0000; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .print-btn { background: #38761D; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print Report</button>
    <button class="print-btn no-print" onclick="window.close()">Close</button>
    
    <div class="header">
        <h1>AUDIT LOG REPORT</h1>
        <p>eTrackHub - Institute of Computer Studies</p>
        <p>Period: <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?></p>
        <?php if ($action_type !== 'all'): ?>
            <p>Action Type: <?php echo htmlspecialchars($action_type); ?></p>
        <?php endif; ?>
        <p>Generated: <?php echo date('F d, Y g:i A'); ?></p>
        <p><strong>Total Records: <?php echo count($logs); ?></strong></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Action Type</th>
                <th>Description</th>
                <th>Performed By</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="7" style="text-align:center;">No audit logs found</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('M d, Y g:i A', strtotime($log['date_created'])); ?></td>
                        <td><?php echo htmlspecialchars($log['id_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['student_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td><?php echo htmlspecialchars($log['officer_name'] ?? 'System'); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>