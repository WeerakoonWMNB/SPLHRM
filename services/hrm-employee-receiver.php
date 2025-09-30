<?php
include "../back/connection/connection.php";
date_default_timezone_set("Asia/Colombo");
// Read JSON input
$input = file_get_contents("php://input");
$employees = json_decode($input, true);

if (empty($employees)) {
    echo json_encode(["status" => "error", "message" => "No employee data received"]);
    exit;
}

$insertedIds = [];

foreach ($employees as $emp) {
    $emp_id = (int)$emp['emp_no'];
    $emp_code = mysqli_real_escape_string($conn, $emp['emp_code']);
    $epf_no = mysqli_real_escape_string($conn, $emp['epf_no']);
    $title = mysqli_real_escape_string($conn, $emp['emp_title']);
    // Trim spaces, lowercase everything, then ucfirst to capitalize first letter
    $title = ucfirst(strtolower(trim($title)));
    
    // Ensure it ends with a dot
    if (substr($title, -1) !== ".") {
        $title .= ".";
    }
    
    $birthday = mysqli_real_escape_string($conn, $emp['date_of_birth']);
    $appointment_date = mysqli_real_escape_string($conn, $emp['appointed_from']);
    
    $designation_code = mysqli_real_escape_string($conn, $emp['designation_code']);
    // Assuming designation_code is a code, we need to fetch the corresponding designation_id from the database
    $designation_id = null;
    if (!empty($designation_code)) {
        $desig_query = "SELECT desig_id FROM designations WHERE designation = '$designation_code' LIMIT 1";
        $desig_result = mysqli_query($conn, $desig_query);
        if ($desig_result && mysqli_num_rows($desig_result) > 0) {
            $desig_row = mysqli_fetch_assoc($desig_result);
            $designation_id = $desig_row['desig_id'];
        }
        // If not found, insert new designation
        else {
            $insert_desig_query = "INSERT INTO designations (desig_code,designation) VALUES ('$designation_code','$designation_code')";
            if (mysqli_query($conn, $insert_desig_query)) {
                $designation_id = mysqli_insert_id($conn);
            }
        }

    }
    
    $address_line_1 = mysqli_real_escape_string($conn, $emp['address1']);
    $address_line_2 = mysqli_real_escape_string($conn, $emp['address2']) . ' ' .mysqli_real_escape_string($conn, $emp['address3']);
    $mobile = mysqli_real_escape_string($conn, $emp['MobileNo']);
    $home = mysqli_real_escape_string($conn, $emp['telephone']);
    $emp_cat_id = mysqli_real_escape_string($conn, $emp['casual_category']); //casual_category 1 freelance,0 permanent
    if($emp_cat_id == 1){
        $emp_cat_id = 3;
    }
    else {
        $emp_cat_id = 2;
    }
    
    $reporting_officer_emp_id = mysqli_real_escape_string($conn, $emp['upliner']);
    // Assuming upliner is an emp_no, we need to fetch the corresponding emp_id from the database
    $reporting_officer_id = null;
    if (!empty($reporting_officer_emp_id)) {
        $ro_query = "SELECT emp_id FROM employees WHERE code = '$reporting_officer_emp_id' LIMIT 1";
        $ro_result = mysqli_query($conn, $ro_query);
        if ($ro_result && mysqli_num_rows($ro_result) > 0) {
            $ro_row = mysqli_fetch_assoc($ro_result);
            $reporting_officer_id = $ro_row['emp_id'];
        }
    }

    $bd_id = mysqli_real_escape_string($conn, $emp['cost_centre']);
    // Assuming cost_centre is a code, we need to fetch the corresponding bd_id from the database
    $branch_id = null;
    if (!empty($bd_id)) {
        $bd_query = "SELECT bd_id FROM branch_departments WHERE bd_code = '$bd_id' LIMIT 1";
        $bd_result = mysqli_query($conn, $bd_query);
        if ($bd_result && mysqli_num_rows($bd_result) > 0) {
            $bd_row = mysqli_fetch_assoc($bd_result);
            $branch_id = $bd_row['bd_id'];
        }
    }
    
    $bank_code = mysqli_real_escape_string($conn, $emp['bank_code']);
    $account_number = mysqli_real_escape_string($conn, $emp['bank_account_no']);
    // fetch bank_branch_name from bank table
    $bank_branch_name = '';
    if (!empty($bank_code)) {
        $bank_query = "SELECT bank_name,branch_name FROM bank_branch WHERE code = '$bank_code' LIMIT 1";
        $bank_result = mysqli_query($conn, $bank_query);
        if ($bank_result && mysqli_num_rows($bank_result) > 0) {
            $bank_row = mysqli_fetch_assoc($bank_result);
            $bank_branch_name = $bank_row['bank_name'] . ' - ' .$bank_row['branch_name'];
        }
    }
    $employee_name = mysqli_real_escape_string($conn, $emp['employee_name']);
    $full_name = mysqli_real_escape_string($conn, $emp['full_name']);
    $marital_status = mysqli_real_escape_string($conn, $emp['status']);
    if($marital_status == 'Married'){
        $marital_status = 1;
    }
    else {
        $marital_status = 0;
    }
    $gender = mysqli_real_escape_string($conn, $emp['sex']);
    if($gender == 'male'){
        $gender = 0;
    }
    else {
        $gender = 1;
    }
    $nic = mysqli_real_escape_string($conn, $emp['nid']);
    $company_code = '001'; // Assuming a default company code, modify as needed
    if ($emp_cat_id == '1' || $emp_cat_id == '2') { // If employee type is permanent
            $last_id = "SELECT permanent_sequence FROM companies WHERE company_code = ?";
            $stmt = $conn->prepare($last_id);
            $stmt->bind_param("i", $company_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $system_generated_id = $row['permanent_sequence'].'P';
            $stmt->close();
        }
        else {
            $last_id = "SELECT fl_sequence FROM companies WHERE company_code = ?";
            $stmt = $conn->prepare($last_id);
            $stmt->bind_param("i", $company_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $system_generated_id = $row['fl_sequence'].'F';
            $stmt->close();
        }
    
    $date_created = date('Y-m-d H:i:s');
    $created_by = 1; // Assuming a default user ID, modify as needed
    
    if(!empty($branch_id)){
    $sql = "INSERT INTO employees (
    code, 
    epf_no, 
    title, 
    name_with_initials, 
    name_in_full, 
    nic, 
    gender, 
    birthday, 
    appointment_date, 
    designation_id, 
    address_line_1, 
    address_line_2, 
    mobile, 
    home, 
    emp_cat_id, 
    marital_status, 
    reporting_officer_emp_id, 
    bd_id,
    created_date,
    created_by,
    system_emp_no,
    bank_code,
    branch_code,
    account_number,
    bank_branch_name
    ) VALUES (
    '$emp_code',
    '$epf_no',
    '$title',
    '$employee_name',
    '$full_name',
    '$nic',
    $gender,
    '$birthday',
    '$appointment_date',
    " . ($designation_id ? $designation_id : "NULL") . ",
    '$address_line_1',
    '$address_line_2',
    '$mobile',
    '$home',
    $emp_cat_id,
    $marital_status,
    " . ($reporting_officer_id ? $reporting_officer_id : "NULL") . ",
    " . ($branch_id ? $branch_id : "NULL") . ",
    '$date_created',
    $created_by,
    '$system_generated_id',
    '$bank_code',
    '$bank_code',
    '$account_number',
    '$bank_branch_name'
    )";
    
    
        //error_log("[" . date("Y-m-d H:i:s") . "] ".$sql." No employees to transfer.\n", 3, __DIR__."/cron_sync.log");
        
    
        if (mysqli_query($conn, $sql)) {
            $lastId = mysqli_insert_id($conn);
            $insertedIds[] =  $emp_id;
    
            if ($emp_cat_id == '1' || $emp_cat_id == '2') { // If employee type is permanent
                        $update_query = "UPDATE companies SET permanent_sequence = permanent_sequence + 1 WHERE company_code = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("i", $company_code);
                        $stmt->execute();
                        $stmt->close();
                    }
                    else {
                        $update_query = "UPDATE companies SET fl_sequence = fl_sequence + 1 WHERE company_code = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("i", $company_code);
                        $stmt->execute();
                        $stmt->close();
                    }
        }
    }
}

echo json_encode([
    "status" => "success",
    "message" => count($insertedIds) . " employees inserted/updated.",
    "inserted_ids" => $insertedIds
]);
