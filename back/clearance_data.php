<?php
header('Content-Type: application/json');
require "connection/connection.php";

// Prepare data arrays
$data = [
    'labels' => [],
    'values' => []
];

// Start from the 1st of the current month
$base = new DateTime('first day of this month');

// Loop over past 6 months
for ($i = 5; $i >= 0; $i--) {
    $month = clone $base;
    $month->modify("-{$i} months");

    $start = $month->format('Y-m-01');
    $end = $month->format('Y-m-t');
    $label = $month->format('M Y');

    // Query completed clearances
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM cl_requests 
        WHERE completed_date BETWEEN ? AND ? 
        AND status = '1' 
        AND is_complete = '1'
    ");
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // Store results
    $data['labels'][] = $label;
    $data['values'][] = (int)$result['total'];

    $stmt->close();
}

$conn->close();

// Output the JSON
echo json_encode($data);
?>
