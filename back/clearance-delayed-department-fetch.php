<?php
session_start();
require "connection/connection.php"; // Ensure this uses mysqli

require "functions.php"; // Make sure this includes getWeekdaysDiff()

$cl_requests_id_array = [];

// Step 1: Fetch relevant step data
$sql = "SELECT 
        crs.cl_step_id,
        crs.created_date,
        crs.allocated_date AS assigned_date,
        crs.max_dates,
        crs.cl_step_id,
        crs.complete_date
    FROM cl_requests cr 
    INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
    INNER JOIN branch_departments bd ON bd.bd_code = crs.bd_code AND bd.is_branch='0'
    WHERE cr.status = '1' 
      AND crs.step > 0
      AND crs.allocated_date IS NOT NULL
    GROUP BY crs.cl_step_id
";

$result = mysqli_query($conn, $sql);

// Step 2: Evaluate delays
while ($row = mysqli_fetch_assoc($result)) {
    $assigned_date = $row['assigned_date'] ?: $row['created_date'];
    $completed_date = $row['complete_date'] ?: date('Y-m-d');

    $date_diff = getWeekdaysDiff($assigned_date, $completed_date);
    $overdue_days = $date_diff - $row['max_dates'];

    if ($overdue_days > 0) {
        $cl_requests_id_array[] = $row['cl_step_id'];
    }
}

// Step 3: Remove duplicate request IDs
$unique_ids = array_unique($cl_requests_id_array);

// If no delays found, return empty dataset
if (empty($unique_ids)) {
    echo json_encode([
        "labels" => [],
        "data" => []
    ]);
    exit;
}

// Step 4: Get branch-wise counts
$ids_string = implode(",", array_map('intval', $unique_ids));

$query = "SELECT 
        bd.bd_name AS branch_name,
        COUNT(DISTINCT crs.cl_step_id) AS delay_count
    FROM cl_requests_steps crs
    INNER JOIN branch_departments bd ON bd.bd_code = crs.bd_code AND bd.is_branch = '0'
    WHERE crs.cl_step_id IN ($ids_string)
    GROUP BY bd.bd_code, bd.bd_name
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['branch_name'] . " (" . $row['delay_count'] . ")";
    $data[] = (int)$row['delay_count'];
}

// Step 5: Output JSON
echo json_encode([
    "labels" => $labels,
    "data" => $data
]);
