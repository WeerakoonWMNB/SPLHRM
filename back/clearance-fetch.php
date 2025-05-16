<?php
session_start();
include "credential-check.php";
require "connection/connection.php";
require "functions.php";

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

if ($user_level != 1 && $user_level != 2) {
    $deptArray = explode(',', $dept); // or wherever $dept comes from
    // Optional: sanitize input to avoid SQL injection
    $deptArray = array_map('trim', $deptArray); // remove extra spaces
    $deptArray = array_map(function($d) { return "'" . addslashes($d) . "'"; }, $deptArray);
    $dept = implode(',', $deptArray);

    $searchQuery .= " AND branch_departments.bd_code IN ($dept) ";
}

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
$totalRecordsQuery = "SELECT COUNT(*) AS total FROM cl_requests WHERE status = 1 ";
$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Total records count (with filtering)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cl_requests 
                        INNER JOIN employees ON cl_requests.emp_id = employees.emp_id
                        LEFT JOIN branch_departments ON branch_departments.bd_id = employees.bd_id
                        WHERE cl_requests.status = 1 $searchQuery");

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
                     employees.epf_no, 
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

              WHERE cl_requests.status = 1 $searchQuery 
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
            <div class="d-flex gap-2">
                <a href="clearance-hr-approve.php?id='.base64_encode($cl_req_id).'" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="View">
                    <i class="mdi mdi-eye"></i>
                </a>';
    if ($user_level == '1' || $user_level == '2') {
        $actionButtons .= '<a href="clearance-item-summary.php?id='.base64_encode($cl_req_id).'&cl=cl" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="summary">
                    <i class="mdi mdi-format-list-bulleted"></i>
                </a>';
     } 
    // else {
    //     $actionButtons .= '<button type="button" class="btn btn-success btn-sm" title="summary" disabled>
    //                         <i class="mdi mdi-format-list-bulleted"></i>
    //                     </button>';
    // }           

    if ($row['is_complete'] == '0' && $row['step'] == "0" && $row['step_complete'] == "0") {
        $actionButtons .= '<button type="button" class="btn btn-warning btn-sm" onclick="data_set(' . $cl_req_id . ')" title="Edit">
                            <i class="mdi mdi-playlist-check"></i>
                        </button>';
    } else {
        $actionButtons .= '<button type="button" class="btn btn-warning btn-sm" title="Edit" disabled>
            <i class="mdi mdi-playlist-check"></i>
        </button>';
    }

    if ($row['is_complete'] == '0' && $row['step'] == "0" && $row['step_complete'] == "0") {
        $actionButtons .= '<button type="submit" name="delete_data" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\');" title="Delete">
                            <i class="mdi mdi-playlist-remove"></i>
                        </button>';
    } else {
        $actionButtons .= '<button type="button" class="btn btn-danger btn-sm" title="Delete" disabled>
                            <i class="mdi mdi-playlist-remove"></i>
                        </button>';
    }

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

    // Progress Bar Calculation
    $progressQuery = "SELECT 
                        ROUND((SUM(CASE WHEN is_complete = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*))) AS completion_percentage
                      FROM cl_requests_steps
                      WHERE request_id = ?";
    $stmtProgress = $conn->prepare($progressQuery);
    $stmtProgress->bind_param("i", $cl_req_id);
    $stmtProgress->execute();
    $stmtProgress->bind_result($completion_percentage);
    $stmtProgress->fetch();
    $stmtProgress->close();

    if ($row['is_complete'] == '0' && ($row['step_complete'] == '1' && $row['step'] == "0")) {
        $completion_percentage = 10;
    }

    $row['progress'] = '<div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ' . ($completion_percentage ?: 0) . '%" aria-valuenow="' . ($completion_percentage ?: 0) . '" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>';

    // Delay Status Calculation
    $referenceDate = !empty($row['last_completed_date']) ? $row['last_completed_date'] : $row['created_date'];
    //$daysGap = (new DateTime($referenceDate))->diff(new DateTime())->days;
    $daysGap = getWeekdaysDiff(date('Y-m-d', strtotime($referenceDate)), date('Y-m-d'));

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
