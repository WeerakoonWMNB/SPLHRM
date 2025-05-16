<?php
session_start();
require "connection/connection.php";
require "functions.php"; // Ensure this includes getWeekdaysDiff()

$delays_by_department = [];

// Step 1: Fetch relevant step data
$sql = "SELECT 
        crs.cl_step_id,
        crs.created_date,
        crs.allocated_date AS assigned_date,
        crs.max_dates,
        crs.cl_step_id,
        crs.complete_date,
        bd.bd_name AS department_name,
        bd.bd_code
    FROM cl_requests cr 
    INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
    INNER JOIN branch_departments bd ON bd.bd_code = crs.bd_code AND bd.is_branch='1'
    WHERE cr.status = '1' 
      AND crs.step > 0
        AND crs.allocated_date IS NOT NULL
    GROUP BY crs.cl_step_id
";

$result = mysqli_query($conn, $sql);

// Step 2: Evaluate delays per department
while ($row = mysqli_fetch_assoc($result)) {
    $assigned_date = $row['assigned_date'] ?: $row['created_date'];
    $completed_date = $row['complete_date'] ?: date('Y-m-d');

    $date_diff = getWeekdaysDiff($assigned_date, $completed_date);
    $overdue_days = $date_diff - $row['max_dates'];

    if ($overdue_days > 0) {
        $dept_name = $row['department_name'];

        if (!isset($delays_by_department[$dept_name])) {
            $delays_by_department[$dept_name] = ['total_delay' => 0, 'count' => 0];
        }

        $delays_by_department[$dept_name]['total_delay'] += $overdue_days;
        $delays_by_department[$dept_name]['count'] += 1;
    }
}

// Step 3: Format result
$labels = [];
$data = [];

foreach ($delays_by_department as $dept => $info) {
    $avg_delay = round($info['total_delay'] / $info['count'], 2);
    $labels[] = $dept . " (" . $avg_delay . ")";
    $data[] = $avg_delay;
}

// Step 4: Output JSON
echo json_encode([
    "labels" => $labels,
    "data" => $data
]);
