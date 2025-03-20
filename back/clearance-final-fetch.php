<?php
session_start();
require "connection/connection.php";

// Read DataTables request parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : "";
$user_level = $_SESSION['ulvl'];
$dept = $_SESSION['bd_id'];


// Prepare search condition
$searchQuery = "";
$params = [];


if (!empty($searchValue)) {
    $searchQuery .= " AND (cl_requests.cl_req_id LIKE ? 
                        OR employees.code LIKE ? 
                        OR employees.epf_no LIKE ? 
                        OR employees.name_in_full LIKE ? 
                        OR employees.name_with_initials LIKE ? 
                        OR employees.nic LIKE ?)";
    $searchValue = "%$searchValue%";
    $params = array_fill(0, 6, $searchValue);
}

// Total records count (without filtering)
$totalRecordsQuery = "SELECT COUNT(*) AS total FROM cl_requests WHERE cl_requests.status = 1  AND cl_requests.is_complete ='0' AND cl_requests.allocated_to_finance='1'";
$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Total records count (with filtering)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cl_requests 
                        INNER JOIN employees ON cl_requests.emp_id = employees.emp_id
                        LEFT JOIN branch_departments ON branch_departments.bd_id = employees.bd_id
                        WHERE cl_requests.status = 1 AND cl_requests.is_complete ='0' AND cl_requests.allocated_to_finance='1' $searchQuery");

if (!empty($params)) {
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
}
$stmt->execute();
$filteredRecordsResult = $stmt->get_result();
$filteredRecords = $filteredRecordsResult->fetch_assoc()['total'];
$stmt->close();

// Fetch employee data with pagination and search
$dataQuery = "SELECT cl_requests.*, 
                     employees.name_with_initials, 
                     employees.code, 
                     employees.system_emp_no, 
                     employees.title, 
                     cl_requests_steps.is_complete AS step_complete, 
                     cl_requests_steps.step,
                     cl_requests_steps.pending_note,
                     cl_requests_steps.complete_note,
                     cl_requests_steps.created_date as step_created_date,
                     cl_requests_steps.max_dates,
                     COALESCE(
                            (SELECT bd_name 
                            FROM branch_departments
                            INNER JOIN cl_requests_steps 
                                ON cl_requests_steps.bd_code = branch_departments.bd_code
                            WHERE (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2) 
                                AND cl_requests_steps.request_id = cl_requests.cl_req_id 
                            ORDER BY cl_requests_steps.step ASC 
                            LIMIT 1),
                            'Human Resource Department'
                        ) AS department,

                    (SELECT complete_date 
                        FROM cl_requests_steps
                        WHERE cl_requests_steps.is_complete = 1 
                              AND cl_requests_steps.request_id = cl_requests.cl_req_id 
                        ORDER BY cl_requests_steps.step DESC 
                        LIMIT 1) AS last_completed_date
              FROM cl_requests 
              INNER JOIN employees ON cl_requests.emp_id = employees.emp_id 
              LEFT JOIN branch_departments ON branch_departments.bd_id = employees.bd_id
              LEFT JOIN cl_requests_steps ON cl_requests_steps.request_id = cl_requests.cl_req_id
              AND (
                    cl_requests_steps.step = (
                        SELECT MIN(step) 
                        FROM cl_requests_steps 
                        WHERE cl_requests_steps.request_id = cl_requests.cl_req_id
                        AND (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2)
                    )  
                    OR (
                        NOT EXISTS (
                            SELECT MIN(step)
                            FROM cl_requests_steps 
                            WHERE cl_requests_steps.request_id = cl_requests.cl_req_id
                            AND (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2)
                        ) 
                        AND cl_requests_steps.step = 0
                    )
                )

              WHERE cl_requests.status = 1 AND cl_requests.is_complete ='0' AND cl_requests.allocated_to_finance='1' $searchQuery 
              ORDER BY cl_requests.cl_req_id DESC
              LIMIT ?, ?";
//echo $dataQuery;
$stmt = $conn->prepare($dataQuery);

// Properly merge params to avoid positional argument error
$bindTypes = str_repeat("s", count($params)) . "ii";
$bindValues = array_merge($params, [$start, $length]);

$stmt->bind_param($bindTypes, ...$bindValues);
$stmt->execute();
$dataResult = $stmt->get_result();

$data = [];
$i = 1;
while ($row = $dataResult->fetch_assoc()) {
    $cl_req_id = htmlspecialchars($row['cl_req_id'], ENT_QUOTES, 'UTF-8');

    // Generate action buttons
    $actionButtons = '
        <form method="POST" action="../../back/clearance-manage.php">
            <input type="hidden" name="row_id" value="' . $cl_req_id . '">
            <div class="d-flex gap-2">';

        $actionButtons .= '<a href="clearance-item-summary.php?id='.base64_encode($cl_req_id).'" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="summary">
                    <i class="mdi mdi-format-list-bulleted"></i>
                </a>'; 
                
    $actionButtons .= '</div></form>';

    $row['action'] = $actionButtons;
    $row['row_id'] = $i++;
    $row['ini_name'] = htmlspecialchars($row['title'] . ' ' . $row['name_with_initials'], ENT_QUOTES, 'UTF-8');
    $note = '';

    if (!empty($row['pending_note'])) {
        $note .= '* ' . $row['pending_note'];
    }

    if (!empty($row['complete_note'])) {
        if (!empty($note)) {
            $note .= '<br>'; // Add a break only if there's already content
        }
        $note .= '* ' . $row['complete_note'];
    }


    $note = trim($note); // Remove any extra spaces

    $row['notes'] = $note;


    // Delay Status Calculation
    $referenceDate = !empty($row['last_completed_date']) ? $row['last_completed_date'] : $row['created_date'];
    $daysGap = (new DateTime($referenceDate))->diff(new DateTime())->days;

    $delay_status = '<div class="d-flex gap-2">'.$cl_req_id.' <span class="status-dot green"></span> </div>';

    if ($row['step_complete'] == '2') {
        $delay_status = '<div class="d-flex gap-2">'.$cl_req_id.' <span class="status-dot yellow"></span> </div>';
    }

    if ($daysGap > $row['max_dates'] && $row['step_complete'] == '2') {
        $delay_status = '<div class="d-flex gap-2">'.$cl_req_id.' <span class="status-dot yellow"></span> <span class="status-dot red"></span> </div>';
    }

    if ($daysGap <= $row['max_dates'] && $row['step_complete'] == '2') {
        $delay_status = '<div class="d-flex gap-2">'.$cl_req_id.' <span class="status-dot yellow"></span> <span class="status-dot green"></span> </div>';
    }

    if ($daysGap > $row['max_dates'] && $row['step_complete'] != '2') {
        $delay_status = '<div class="d-flex gap-2">'.$cl_req_id.' <span class="status-dot red"></span> </div>';
    }


    $row['req_id'] = $delay_status;
    
    $data[] = $row;
}

$stmt->close();
$conn->close();

// Response JSON
$response = [
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $filteredRecords,
    "data" => $data
];

echo json_encode($response);
?>
