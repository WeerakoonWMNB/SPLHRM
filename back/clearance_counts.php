<?php
header('Content-Type: application/json');

require "connection/connection.php";

$data = [
    'labels' => [],
    'created' => [],
    'completed' => []
];

for ($i = 5; $i >= 0; $i--) {
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));

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
