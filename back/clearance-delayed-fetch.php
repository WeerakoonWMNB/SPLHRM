<?php
session_start();
require "connection/connection.php";
require "functions.php"; // Ensure this includes getWeekdaysDiff()

// Read DataTables request parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : "";
$user_level = $_SESSION['ulvl'];
$department = $_SESSION['bd_id'];
$dept = $_POST['department'] ?? '';
$fromdate = $_POST['fromdate'] ?? '';
$todate = $_POST['todate'] ?? '';

// Prepare search condition
$searchQuery = "";
$params = [];

//$searchQuery .= " AND branch_departments.bd_code IN ('$dept') ";

if (!empty($searchValue)) {
    $searchQuery .= " AND (cr.cl_req_id LIKE ? 
                        OR e.code LIKE ? 
                        OR e.epf_no LIKE ? 
                        OR e.name_in_full LIKE ? 
                        OR e.name_with_initials LIKE ? 
                        OR e.nic LIKE ?)";
    $searchValue = "%$searchValue%";
    $params = array_fill(0, 6, $searchValue);
}

// Total records count (without filtering)
$totalRecordsQuery = "SELECT 
                count(*) AS total,
                crs.allocated_date AS last_complete_date,
                crs.max_dates,
                crs.complete_date,
                crs.created_date
            FROM cl_requests cr 
            INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
            INNER JOIN branch_departments bd ON bd.bd_code = crs.bd_code 
            WHERE cr.status = '1' 
            AND crs.step > 0 
            AND crs.allocated_date IS NOT NULL
            AND DATEDIFF(IFNULL(crs.complete_date, CURDATE()),IFNULL(crs.allocated_date,crs.created_date)) + 1 - crs.max_dates > 0";

if ($dept) {
    $totalRecordsQuery .= " AND crs.bd_code IN ('$dept')";
}

if ($fromdate) {
    $totalRecordsQuery .= " AND last_complete_date >= '$fromdate 00:00:00' ";
}

if ($todate) {
    $totalRecordsQuery .= " AND last_complete_date <= '$todate 23:59:59' ";
}

$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Total records count (with filtering)
$totalRecordsQuery = "SELECT 
                count(*) AS total,
                crs.allocated_date AS last_complete_date,
                crs.max_dates,
                crs.complete_date,
                crs.created_date
            FROM cl_requests cr 
            INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
            INNER JOIN branch_departments bd ON bd.bd_code = crs.bd_code 
            WHERE cr.status = '1' 
            AND crs.step > 0
            AND crs.allocated_date IS NOT NULL
            AND DATEDIFF(IFNULL(crs.complete_date, CURDATE()) ,IFNULL(crs.allocated_date,crs.created_date)) + 1 - crs.max_dates > 0 
                $searchQuery";
if ($dept) {
    $totalRecordsQuery .= " AND crs.bd_code IN ('$dept')";
}

if ($fromdate) {
    $totalRecordsQuery .= " AND last_complete_date >= '$fromdate 00:00:00' ";
}

if ($todate) {
    $totalRecordsQuery .= " AND last_complete_date <= '$todate 23:59:59' ";
}

$stmt = $conn->prepare($totalRecordsQuery);

if (!empty($params)) {
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
}
$stmt->execute();
$filteredRecordsResult = $stmt->get_result();
$filteredRecords = $filteredRecordsResult->fetch_assoc()['total'];
$stmt->close();

// Fetch employee data with pagination and search
$dataQuery = "SELECT 
                cr.*, 
                e.name_with_initials, 
                e.code, 
                e.epf_no, 
                e.title, 
                crs.is_complete AS step_complete, 
                crs.step,
                crs.pending_note,
                crs.complete_note,
                crs.created_date AS step_created_date,
                crs.complete_date AS step_completed_date,
                crs.max_dates,
                bds.bd_name AS selected_branch,
                crs.allocated_date AS last_complete_date


            FROM cl_requests cr
            INNER JOIN employees e ON cr.emp_id = e.emp_id
            LEFT JOIN branch_departments bd ON bd.bd_id = e.bd_id
            INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
            INNER JOIN branch_departments bds ON bds.bd_code = crs.bd_code 
            WHERE cr.status = '1' 
            AND crs.allocated_date IS NOT NULL
            AND crs.step > 0 AND DATEDIFF(IFNULL(crs.complete_date, CURDATE()),IFNULL(crs.allocated_date,crs.created_date)) +1 - crs.max_dates > 0 ";
if ($dept) {
    $dataQuery .= " AND crs.bd_code IN ('$dept')";
}

if ($fromdate) {
    $dataQuery .= " AND last_complete_date >= '$fromdate 00:00:00' ";
}

if ($todate) {
    $dataQuery .= " AND last_complete_date <= '$todate 23:59:59' ";
}

$dataQuery .= " $searchQuery

            ORDER BY cr.cl_req_id ASC
            LIMIT ?, ?
            ";
//echo $dataQuery;exit;
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

    $assigned_date = $row['last_complete_date'] ?: $row['step_created_date'];
    $completed_date = $row['step_completed_date'] ?: date('Y-m-d');

    $date_diff = getWeekdaysDiff($assigned_date, $completed_date);
    $overdue_days = $date_diff - $row['max_dates'];

    if ($overdue_days > 0) {
        $row['days_taken'] = $overdue_days;
        $cl_req_id = htmlspecialchars($row['cl_req_id'], ENT_QUOTES, 'UTF-8');

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

        $row['req_id'] = $cl_req_id;
        
        $row['assigned_date'] = $assigned_date;
        $data[] = $row;
    }

    
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