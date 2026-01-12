<?php

require_once '../config/init.php';

if (!checkRole(['officer', 'adviser'])) {
    die('Unauthorized access');
}

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$event_id = $_GET['event_id'] ?? 'all';

try {
    $query = "
        SELECT 
            a.*,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.year_level,
            o.name as recorded_by_name
        FROM attendance a
        LEFT JOIN students s ON a.id_number = s.id_number
        LEFT JOIN officers o ON a.recorded_by = o.officer_id
        WHERE a.attendance_date BETWEEN ? AND ?
    ";
    
    $params = [$start_date, $end_date];
    
    if ($event_id !== 'all') {
        $query .= " AND a.event_id = ?";
        $params[] = $event_id;
    }
    
    $query .= " ORDER BY a.attendance_date DESC, a.event_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll();
    
} catch(Exception $e) {
    error_log("Attendance Report Error: " . $e->getMessage());
    die("Error generating report");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - eTrackHub</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #8B0000; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #8B0000; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .present { color: #38761D; font-weight: bold; }
        .absent { color: #8B0000; font-weight: bold; }
        .print-btn { background: #38761D; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print Report</button>
    <button class="print-btn no-print" onclick="window.close()">Close</button>
    
    <div class="header">
        <h1>ATTENDANCE REPORT</h1>
        <p>eTrackHub - Institute of Computer Studies</p>
        <p>Period: <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?></p>
        <p>Generated: <?php echo date('F d, Y g:i A'); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Year Level</th>
                <th>Event Name</th>
                <th>Date</th>
                <th>AM IN</th>
                <th>AM OUT</th>
                <th>PM IN</th>
                <th>PM OUT</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attendance)): ?>
                <tr><td colspan="10">No attendance records found</td></tr>
            <?php else: ?>
                <?php foreach ($attendance as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['id_number']); ?></td>
                        <td style="text-align:left;"><?php echo htmlspecialchars($a['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($a['year_level']); ?></td>
                        <td style="text-align:left;"><?php echo htmlspecialchars($a['event_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($a['attendance_date'])); ?></td>
                        <td class="<?php echo $a['am_in'] ? 'present' : 'absent'; ?>">
                            <?php echo $a['am_in'] ? '✓' : '✗'; ?>
                        </td>
                        <td class="<?php echo $a['am_out'] ? 'present' : 'absent'; ?>">
                            <?php echo $a['am_out'] ? '✓' : '✗'; ?>
                        </td>
                        <td class="<?php echo $a['pm_in'] ? 'present' : 'absent'; ?>">
                            <?php echo $a['pm_in'] ? '✓' : '✗'; ?>
                        </td>
                        <td class="<?php echo $a['pm_out'] ? 'present' : 'absent'; ?>">
                            <?php echo $a['pm_out'] ? '✓' : '✗'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($a['recorded_by_name'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
