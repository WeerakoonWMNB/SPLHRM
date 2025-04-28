<?php
header('Content-Type: application/json');

require "connection/connection.php";
// Prepare data arrays
$data = [
    'labels' => [],
    'values' => []
];

// Loop over past 6 months
for ($i = 5; $i >= 0; $i--) {
    $start = date('Y-m-01', strtotime("-$i months")); // 1st day of the month
    $end = date('Y-m-t', strtotime("-$i months"));     // last day of the month
    $label = date('M Y', strtotime("-$i months"));

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cl_requests WHERE completed_date BETWEEN ? AND ? AND status = '1' AND is_complete = '1'");
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // Store the label and the count
    $data['labels'][] = $label;
    $data['values'][] = (int)$result['total'];

    $stmt->close();
}

$conn->close();

// Output the JSON
echo json_encode($data);
?>
