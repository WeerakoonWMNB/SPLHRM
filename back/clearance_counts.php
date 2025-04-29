<?php
header('Content-Type: application/json');
require "connection/connection.php";

$data = [
    'labels' => [],
    'created' => [],
    'completed' => []
];

$base = new DateTime('first day of this month');

for ($i = 5; $i >= 0; $i--) {
    $month = clone $base;
    $month->modify("-{$i} months");
    $start = $month->format('Y-m-01');
    $end = $month->format('Y-m-t');
    $label = $month->format('M Y');

    // Created Count
    $stmtCreated = $conn->prepare("SELECT COUNT(*) as total FROM cl_requests WHERE created_date BETWEEN ? AND ? AND status = '1'");
    $stmtCreated->bind_param('ss', $start, $end);
    $stmtCreated->execute();
    $createdResult = $stmtCreated->get_result()->fetch_assoc();
    $createdCount = (int)$createdResult['total'];
    $stmtCreated->close();

    // Completed Count
    $stmtCompleted = $conn->prepare("SELECT COUNT(*) as total FROM cl_requests WHERE completed_date BETWEEN ? AND ? AND status = '1' AND is_complete = '1'");
    $stmtCompleted->bind_param('ss', $start, $end);
    $stmtCompleted->execute();
    $completedResult = $stmtCompleted->get_result()->fetch_assoc();
    $completedCount = (int)$completedResult['total'];
    $stmtCompleted->close();

    $data['labels'][] = $label;
    $data['created'][] = $createdCount;
    $data['completed'][] = $completedCount;
}

$conn->close();
echo json_encode($data);
?>
