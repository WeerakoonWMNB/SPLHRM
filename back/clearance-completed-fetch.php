<?php
session_start();
require "connection/connection.php";
require "functions.php";

// Read DataTables request parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : "";
$user_level = $_SESSION['ulvl'];
$department = $_SESSION['bd_id'];
$dept = $_POST['department'] ?? '';

// Prepare search condition
$searchQuery = "";
$params = [];

//$searchQuery .= " AND branch_departments.bd_code IN ('$dept') ";

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

$deptFilter = "";
if ($user_level != '1' && $user_level != '2') {
    $department = mysqli_real_escape_string($conn, $department);
    $department = str_replace(",", "','", $department);
    $deptFilter = " AND employees.bd_id IN ('$department') ";
    $dept = $department;
} elseif (!empty($dept)) {
    $dept = mysqli_real_escape_string($conn, $dept);
    $deptFilter = " AND employees.bd_id IN ('$dept') ";
}

// Total records count (without filtering)
$totalRecordsQuery = "SELECT COUNT(*) AS total FROM cl_requests WHERE status = 1 AND is_complete = 1 ";
$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Total records count (with filtering)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cl_requests 
                        INNER JOIN employees ON cl_requests.emp_id = employees.emp_id
                        LEFT JOIN branch_departments ON branch_departments.bd_id = employees.bd_id
                        WHERE cl_requests.status = 1 AND cl_requests.is_complete = 1 $deptFilter $searchQuery");

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
                     branch_departments.bd_name AS emp_dept
              FROM cl_requests 
              INNER JOIN employees ON cl_requests.emp_id = employees.emp_id 
              
              LEFT JOIN branch_departments ON branch_departments.bd_id = employees.bd_id
              WHERE cl_requests.status = 1 AND cl_requests.is_complete = 1 $deptFilter $searchQuery 
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
            <input type="hidden" name="row_id" value="' . $cl_req_id . '">
            <div class="d-flex gap-2">
                <a href="../clearance/clearance-item-summary.php?id='.base64_encode($cl_req_id).'&fl=fl" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="View">
                    <i class="mdi mdi-eye"></i>
                </a>';         

    $actionButtons .= '</div>';

    $row['action'] = $actionButtons;
    $row['row_id'] = $i++;
    $row['ini_name'] = htmlspecialchars($row['title'] . ' ' . $row['name_with_initials'], ENT_QUOTES, 'UTF-8');
    
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
