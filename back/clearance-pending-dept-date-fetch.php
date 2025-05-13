<?php
session_start();
require "connection/connection.php"; // Make sure this uses mysqli, not PDO

$stmt = $conn->prepare("
    SELECT 
    bd.bd_name AS branch_name,
    AVG(
    CASE 
        WHEN DATEDIFF(
            IFNULL(sp.pending_completed_datetime, CURRENT_DATE),
            sp.created_datetime
        ) <= 0 THEN 1
        ELSE CEIL(DATEDIFF(
            IFNULL(sp.pending_completed_datetime, CURRENT_DATE),
            sp.created_datetime
        ))
    END
) AS avg_days_taken

FROM cl_requests cr
INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id
INNER JOIN step_pending sp ON crs.cl_step_id = sp.cl_step_id
INNER JOIN branch_departments bd ON bd.bd_code = crs.bd_code AND bd.is_branch = '0'
WHERE cr.status = '1'
  AND sp.created_datetime IS NOT NULL
GROUP BY bd.bd_code, bd.bd_name
");

$stmt->execute();
$result = $stmt->get_result(); // 🔹 mysqli-specific method

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['branch_name']. " (".(double)$row['avg_days_taken'].")";
    $data[] = (double)$row['avg_days_taken'];
}

echo json_encode([
    "labels" => $labels,
    "data" => $data
]);
