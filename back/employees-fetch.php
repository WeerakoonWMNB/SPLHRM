<?php
session_start();
require "connection/connection.php";

// Read DataTables request parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : "";

// Search condition
$searchQuery = "";
if ($searchValue != '') {
    $searchQuery = " WHERE employees.status = 1 AND (employees.code LIKE '%" . $searchValue . "%' OR employees.epf_no LIKE '%" . $searchValue . "%' OR employees.name_in_full LIKE '%" . $searchValue . "%' OR employees.name_with_initials LIKE '%" . $searchValue . "%' OR employees.nic LIKE '%" . $searchValue . "%')";
}

// Total records without filtering
$totalRecordsQuery = "SELECT COUNT(*) AS total FROM employees WHERE status = 1";
$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Total records with filtering
$filteredRecordsQuery = "SELECT COUNT(*) AS total FROM employees" . $searchQuery;
$filteredRecordsResult = $conn->query($filteredRecordsQuery);
$filteredRecords = $filteredRecordsResult->fetch_assoc()['total'];

// Fetch employee data with pagination and search
$dataQuery = "SELECT employees.*,branch_departments.bd_name FROM employees INNER JOIN branch_departments ON employees.bd_id = branch_departments.bd_id AND employees.status=1 " . $searchQuery . " LIMIT $start, $length";
$dataResult = $conn->query($dataQuery);

$data = [];
$i = 1;
while ($row = $dataResult->fetch_assoc()) {
    $emp_id = $row['emp_id']; // Assuming 'id' is the unique key for employees
    
    // Generate action buttons
    $actionButtons = '
        <form method="POST" action="../../back/employee-manage.php">
            <input type="hidden" name="emp_id" value="' . $emp_id . '">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Promotion/Demotion" onclick="emp_promote(' . $emp_id . ')">
                    <i class="mdi mdi-account-convert"></i>
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="emp_set(' . $emp_id . ')" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <i class="mdi mdi-playlist-check"></i>
                </button>
                <button 
                    type="submit" 
                    name="delete_employee" 
                    class="btn btn-danger btn-sm" 
                    onclick="return confirm(\'Are you sure you want to delete this Employee?\');"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <i class="mdi mdi-playlist-remove"></i>
                </button>
            </div>
        </form>
    ';

    $row['action'] = $actionButtons;
    $row['row_id'] = $i; // Set the unique key as the row ID for DataTables
    $row['f_name'] = $row['title'].' '.$row['name_in_full']; 
    $row['ini_name'] = $row['title'].' '.$row['name_with_initials'];

    $data[] = $row;
    $i++;
}

// Response JSON
$response = [
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $filteredRecords,
    "data" => $data
];

echo json_encode($response);

$conn->close();
?>
