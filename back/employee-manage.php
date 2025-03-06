<?php
session_start();
require "connection/connection.php";
require "functions.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['company_code'])) {
    $company_code = intval($_POST['company_code']);
    
    $sql = "SELECT bd_id , bd_name FROM branch_departments WHERE company_code = ? GROUP BY bd_id, is_branch ORDER BY is_branch ASC, bd_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $company_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    
    echo json_encode($branches);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['query'])) {
    // Get search term
    $search = $_POST['query'];

    // Fetch employees matching search term
    $sql = "SELECT emp_id , title, name_with_initials, nic, epf_no, code FROM employees 
    WHERE name_with_initials LIKE '%$search%' OR 
    nic LIKE '%$search%' OR 
    epf_no LIKE '%$search%' OR 
    code LIKE '%$search%' OR 
    name_in_full LIKE '%$search%' LIMIT 10";
    $result = $conn->query($sql);

        $output = ""; 

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<a href='#' class='list-group-item list-group-item-action employee-option' data-id='".$row['emp_id']."'>".$row['title']." ".$row['name_with_initials']." (".$row['nic']." ".$row['code']." ".$row['epf_no'].")</a>";
            }
        } else {
            $output = "<a href='#' class='list-group-item list-group-item-action disabled'>No results found</a>";
        }

        echo $output;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['queryDesignation'])) {
    // Get search term
    $search = $_POST['queryDesignation'];

    // Fetch desig matching search term
    $sql = "SELECT * FROM designations 
    WHERE designation LIKE '%$search%' LIMIT 10";
    $result = $conn->query($sql);

        $output = ""; 

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<a href='#' class='list-group-item list-group-item-action designation-option' data-id='".$row['desig_id']."'>".$row['designation']."</a>";
            }
        } else {
            $output = "<a href='#' class='list-group-item list-group-item-action disabled'>No results found</a>";
        }

        echo $output;
}

//delete employee
if (isset($_POST['delete_employee'])) {
    $emp_id = $_POST['emp_id'];
    $edit_user = $_SESSION['uid'];

    $user = "UPDATE employees SET status = 0, last_updated_date = '$datetime' , updated_by = $edit_user  WHERE emp_id = '$emp_id' ";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Employee deleted successfully.";
        header("Location: ../pages/employees/employee-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete Employee.";
        header("Location: ../pages/employees/employee-list.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set JSON header
    
    if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
        
        function clean_input($data) {
            return htmlspecialchars(strip_tags(trim($data)));
        }

        $branch = clean_input($_POST['branch']);
        $title = clean_input($_POST['title']);
        $init_name = clean_input($_POST['init_name']);
        $full_name = clean_input($_POST['full_name']);
        $emp_type = clean_input($_POST['emp_type']); 
        $emp_code = clean_input($_POST['emp_code']);
        $emp_designation = clean_input($_POST['emp_designation']);
        $address1 = clean_input($_POST['address1']);
        $address2 = clean_input($_POST['address2']);
        $epf_no = clean_input($_POST['epf']);
        $nic = clean_input($_POST['nic']);
        $gender = clean_input($_POST['gender']);
        $marital_status = clean_input($_POST['marital']);
        $birthday = clean_input($_POST['birthday']); 
        $appointment_date = clean_input($_POST['appointment']);
        $lp_date = clean_input($_POST['lp_date']);
        $email = clean_input($_POST['email']);
        $mobile = clean_input($_POST['mobile']);
        $work_phone = clean_input($_POST['work']);
        $home_phone = clean_input($_POST['home']);
        $reporting_to = clean_input($_POST['reporting'] ?? null);
        $employee_id = clean_input($_POST['employee_id']);
        $company_code = clean_input($_POST['company']);
        $bankBranchId = clean_input($_POST['bankBranchId'] ?? null);
        $account_number = clean_input($_POST['account_number']);
        $bank_code = null;
        $branch_code = null;
        $bank_name = null;

        $system_generated_id = null;

        $added_by = $_SESSION['uid'];
        $datetime = date("Y-m-d H:i:s");

        $errors = [];

        if (empty($branch)) $errors[] = "Branch/Department is required.";
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($init_name)) $errors[] = "Initial name is required.";
        if (empty($full_name)) $errors[] = "Full name is required.";
        if (empty($emp_type)) $errors[] = "Employee type is required.";
        if (in_array($emp_type, ['2', '3']) && (empty($emp_code))) {
            $errors[] = "Valid Employee Marketer Code is required.";
        }
        if (empty($emp_designation)) $errors[] = "Employee Designation is required.";
        if (empty($address1)) $errors[] = "Employee Address is required.";
        if (empty($employee_id)) $errors[] = "Valid EMP ID is required.";
        if (empty($nic) || !preg_match('/^[0-9]{9}[vVxX]$|^[0-9]{12}$/', $nic)) $errors[] = "Valid NIC is required.";
        if (!in_array($gender, ['0', '1'])) $errors[] = "Valid gender selection is required.";
        if (!in_array($marital_status, ['0', '1'])) $errors[] = "Marital status is required.";
        if (empty($birthday) || !strtotime($birthday)) $errors[] = "Valid birthday date is required.";
        if (empty($appointment_date) || !strtotime($appointment_date)) $errors[] = "Valid appointment date is required.";
        if (empty($lp_date) || !strtotime($lp_date)) $errors[] = "Valid promoted date is required.";
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        if (empty($mobile) || !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid mobile number is required.";
        if (!empty($work_phone) && !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid Phone(Work) is required.";
        if (!empty($home_phone) && !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid Phone(home) is required.";

        if (!empty($mobile)) {
            $check_mobile_number_query = "SELECT * FROM employees WHERE mobile = ?";
            $check_mobile_number_stmt = $conn->prepare($check_mobile_number_query);
            $check_mobile_number_stmt->bind_param('s', $mobile);
            $check_mobile_number_stmt->execute();
            $check_mobile_number_stmt->store_result();

            if ($check_mobile_number_stmt->num_rows > 0) {
                $errors[] = "Mobile number already exists.";
            }
        }

        $file_path1 = null; // Default null, will store path if a valid file is uploaded
        $file_path2 = null; // Default null, will store path if a valid file is uploaded
        $upload_dir = "../uploads/"; // Upload directory
    
        // **Handle file upload if a new file is provided**
        if (!empty($_FILES['nicCopy']['name'])) {
            
            $file = $_FILES['nicCopy'];
            $file_name = time() . "_" . basename($file['name']); // Unique filename
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
    
            // Validate file type
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Invalid file format. Only PDF, JPG, JPEG, PNG allowed.";
            }
    
            // Validate file size (Max: 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "File size exceeds 5MB limit.";
            }

            // Move new file if no errors
            if (empty($errors)) {
                $file_path1 = "../../uploads/" . $file_name; // Path for DB
                $file_path = $upload_dir . $file_name; // Actual server path
    
                if (!move_uploaded_file($file_tmp, $file_path)) {
                    $errors[] = "Failed to upload file.";
                }
            }
        }

        if (!empty($_FILES['passbookCopy']['name'])) {
                
            $file = $_FILES['passbookCopy'];
            $file_name = time() . "_" . basename($file['name']); // Unique filename
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
    
            // Validate file type
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Invalid file format. Only PDF, JPG, JPEG, PNG allowed.";
            }
    
            // Validate file size (Max: 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "File size exceeds 5MB limit.";
            }

            // Move new file if no errors
            if (empty($errors)) {
                $file_path2 = "../../uploads/" . $file_name; // Path for DB
                $file_path_passbook = $upload_dir . $file_name; // Actual server path
    
                if (!move_uploaded_file($file_tmp, $file_path_passbook)) {
                    $errors[] = "Failed to upload file.";
                }
            }
        }

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        if ($emp_type == '1' || $emp_type == '2') { // If employee type is permanent
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

        if(!empty($bankBranchId)) {
            $sqlbb = "SELECT * FROM bank_branch WHERE code = ?";
            $stmtbb = $conn->prepare($sqlbb);
            $stmtbb->bind_param("s", $bankBranchId);
            $stmtbb->execute();
            $resultbb = $stmtbb->get_result();
            $rowbb = $resultbb->fetch_assoc();
            $bank_code = $rowbb['bank_code'];
            $branch_code = $rowbb['branch_code'];
            $bank_name = $rowbb['bank_name'].' - '.$rowbb['branch_name'];
            $stmtbb->close();
        }

        $sql = "INSERT INTO employees (code, epf_no, employee_id, title, name_with_initials, 
        name_in_full, nic, gender, birthday, appointment_date, lp_date, designation_id, address_line_1, 
        address_line_2, mobile, work, home, email, emp_cat_id, marital_status, reporting_officer_emp_id, 
        bd_id, created_date, created_by, system_emp_no, bank_code, branch_code, account_number, bank_branch_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('sssssssisssissssssiiiisssiiss', 
                $emp_code, $epf_no, $employee_id, $title, $init_name, $full_name, $nic, $gender, $birthday, 
                $appointment_date, $lp_date , $emp_designation, $address1, $address2, $mobile, $work_phone, $home_phone, $email, 
                $emp_type, $marital_status, $reporting_to, $branch, $datetime, $added_by,$system_generated_id,$bank_code,$branch_code,$account_number,$bank_name
            );

            if ($stmt->execute()) {
                $request_id = $stmt->insert_id;
                if ($emp_type == '1' || $emp_type == '2') { // If employee type is permanent
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

                if (!empty($file_path)) {
                        // **Insert new upload record**
                        $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (3, ?, ?, ?, ?)";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('siss', $file_path1, $request_id, $added_by, $datetime);
                        $upload_stmt->execute();
                }

                if (!empty($file_path_passbook)) {
                    // **Insert new upload record**
                    $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (4, ?, ?, ?, ?)";
                    $upload_stmt = $conn->prepare($upload_sql);
                    $upload_stmt->bind_param('siss', $file_path2, $request_id, $added_by, $datetime);
                    $upload_stmt->execute();
                }

                $_SESSION['success'] = "Employee added successfully.";
                echo json_encode(["status" => "success", "message" => "Employee added successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing statement."]);
        }

        $conn->close();
    }


    //employee edit
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        
        // Sanitize and validate input
        function clean_input($data) {
            return htmlspecialchars(strip_tags(trim($data)));
        }

        // Get employee data from POST request
        $edit_id = clean_input($_POST['edit_id']);
        $branch = clean_input($_POST['branch']);
        $title = clean_input($_POST['title']);
        $init_name = clean_input($_POST['init_name']);
        $full_name = clean_input($_POST['full_name']);
        $emp_type = clean_input($_POST['emp_type']); 
        $emp_code = clean_input($_POST['emp_code']);
        //$emp_designation = clean_input($_POST['emp_designation']);
        $address1 = clean_input($_POST['address1']);
        $address2 = clean_input($_POST['address2']);
        $epf_no = clean_input($_POST['epf']);
        $employee_id = clean_input($_POST['employee_id']);
        $nic = clean_input($_POST['nic']);
        $gender = clean_input($_POST['gender']);
        $marital_status = clean_input($_POST['marital']);
        $birthday = clean_input($_POST['birthday']); 
        $appointment_date = clean_input($_POST['appointment']);
        $email = clean_input($_POST['email']);
        $mobile = clean_input($_POST['mobile']);
        $work_phone = clean_input($_POST['work']);
        $home_phone = clean_input($_POST['home']);
        $reporting_to = clean_input($_POST['reporting']);
        $company_code = clean_input($_POST['company']);
        $bankBranchId = clean_input($_POST['bankBranchId'] ?? null);
        $account_number = clean_input($_POST['account_number']);
        $bank_code = null;
        $branch_code = null;
        $bank_name = null;

        $updated_by = $_SESSION['uid'];
        $datetime = date("Y-m-d H:i:s");

        // Validation checks
        $errors = [];

        if (empty($branch)) $errors[] = "Branch/Department is required.";
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($init_name)) $errors[] = "Initial name is required.";
        if (empty($full_name)) $errors[] = "Full name is required.";
        if (empty($emp_type)) $errors[] = "Employee type is required.";
        if (in_array($emp_type, ['2', '3']) && (empty($emp_code))) {
            $errors[] = "Valid Employee Code is required.";
        }
        //if (empty($emp_designation)) $errors[] = "Employee Designation is required.";
        if (empty($address1)) $errors[] = "Employee Address is required.";
        if (empty($employee_id)) $errors[] = "Valid EMP ID is required.";
        if (empty($nic) || !preg_match('/^[0-9]{9}[vVxX]$|^[0-9]{12}$/', $nic)) $errors[] = "Valid NIC is required.";
        if (!in_array($gender, ['0', '1'])) $errors[] = "Valid gender selection is required.";
        if (!in_array($marital_status, ['0', '1'])) $errors[] = "Marital status is required.";
        if (empty($birthday) || !strtotime($birthday)) $errors[] = "Valid birthday date is required.";
        if (empty($appointment_date) || !strtotime($appointment_date)) $errors[] = "Valid appointment date is required.";
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        if (!empty($mobile) && !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid mobile number is required.";
        if (!empty($work_phone) && !preg_match('/^\d{10}$/', $work_phone)) $errors[] = "Valid Phone(Work) is required.";
        if (!empty($home_phone) && !preg_match('/^\d{10}$/', $home_phone)) $errors[] = "Valid Phone(home) is required.";

        if (!empty($mobile)) {
            $check_mobile_number_query = "SELECT * FROM employees WHERE mobile = ? AND emp_id != ?";
            $check_mobile_number_stmt = $conn->prepare($check_mobile_number_query);
            $check_mobile_number_stmt->bind_param('si', $mobile, $edit_id);
            $check_mobile_number_stmt->execute();
            $check_mobile_number_stmt->store_result();

            if ($check_mobile_number_stmt->num_rows > 0) {
                $errors[] = "Mobile number already exists.";
            }
        }

        $file_path1 = null; // Default null, will store path if a valid file is uploaded
        $file_path2 = null; // Default null, will store path if a valid file is uploaded
        $file_path_passbook = null; // Default null, will store path if a valid file is uploaded
        $upload_dir = "../uploads/"; // Upload directory
    
        // **Handle file upload if a new file is provided**
        if (!empty($_FILES['nicCopy']['name'])) {

            $existing_file = null;
            $stmt = $conn->prepare("SELECT location FROM uploads WHERE request_id = ? AND document_type = 3");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $stmt->bind_result($existing_file);
            $stmt->fetch();
            $stmt->close();
            
            $file = $_FILES['nicCopy'];
            $file_name = time() . "_" . basename($file['name']); // Unique filename
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
    
            // Validate file type
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Invalid file format. Only PDF, JPG, JPEG, PNG allowed.";
            }
    
            // Validate file size (Max: 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "File size exceeds 5MB limit.";
            }

            // Remove existing file before uploading a new one
            if ($existing_file && file_exists(substr($existing_file,3))) {
                unlink(substr($existing_file,3));
            }

            // Move new file if no errors
            if (empty($errors)) {
                $file_path1 = "../../uploads/" . $file_name; // Path for DB
                $file_path = $upload_dir . $file_name; // Actual server path
    
                if (!move_uploaded_file($file_tmp, $file_path)) {
                    $errors[] = "Failed to upload file.";
                }
            }
        }

        if (!empty($_FILES['passbookCopy']['name'])) {
            $existing_file = null;
            $stmt = $conn->prepare("SELECT location FROM uploads WHERE request_id = ? AND document_type = 4");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $stmt->bind_result($existing_file);
            $stmt->fetch();
            $stmt->close();
                
            $file = $_FILES['passbookCopy'];
            $file_name = time() . "_" . basename($file['name']); // Unique filename
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
    
            // Validate file type
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Invalid file format. Only PDF, JPG, JPEG, PNG allowed.";
            }
    
            // Validate file size (Max: 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "File size exceeds 5MB limit.";
            }

            // Remove existing file before uploading a new one
            if ($existing_file && file_exists(substr($existing_file,3))) {
                unlink(substr($existing_file,3));
            }

            // Move new file if no errors
            if (empty($errors)) {
                $file_path2 = "../../uploads/" . $file_name; // Path for DB
                $file_path_passbook = $upload_dir . $file_name; // Actual server path
    
                if (!move_uploaded_file($file_tmp, $file_path_passbook)) {
                    $errors[] = "Failed to upload file.";
                }
            }
        }

        // If errors, return them
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        // Fetch current values from the database to compare
        $current_values = [
            'bd_id' => null,
            'emp_cat_id' => null,
            'code' => null,
            'epf_no' => null,
            'nic' => null,
            'reporting_officer_emp_id' => null
        ];

        $current_query = "SELECT bd_id, emp_cat_id, code, epf_no, nic, reporting_officer_emp_id 
                        FROM employees WHERE emp_id = ?";
        $current_stmt = $conn->prepare($current_query);

        if (!$current_stmt) {
            die(json_encode(["status" => "error", "message" => "Error preparing statement: " . $conn->error]));
        }

        $current_stmt->bind_param('i', $edit_id);
        $current_stmt->execute();
        $current_stmt->bind_result(
            $current_values['bd_id'], 
            $current_values['emp_cat_id'],  
            $current_values['code'], 
            $current_values['epf_no'], 
            $current_values['nic'], 
            $current_values['reporting_officer_emp_id']
        );
        $current_stmt->fetch();
        $current_stmt->close();

        // Array to track changed fields
        $changes = [];

        // Mapping of change types
        $change_types = [
            'bd_id' => 1,
            'emp_cat_id' => 2,
            'code' => 4,
            'epf_no' => 5,
            'nic' => 6,
            'reporting_officer_emp_id' => 7
        ];

        // Fetch old names for category and designation
        $old_cat_name = null;
        $old_designation_name = null;

        // Fetch category name
        if (!empty($current_values['emp_cat_id'])) {
            $cat_query = "SELECT cat_name FROM emp_category WHERE cat_id  = ?";
            $cat_stmt = $conn->prepare($cat_query);
            $cat_stmt->bind_param('i', $current_values['emp_cat_id']);
            $cat_stmt->execute();
            $cat_stmt->bind_result($old_cat_name);
            $cat_stmt->fetch();
            $cat_stmt->close();
        }

        // Fetch designation name
        // if (!empty($current_values['designation_id'])) {
        //     $designation_query = "SELECT designation FROM designations WHERE desig_id  = ?";
        //     $designation_stmt = $conn->prepare($designation_query);
        //     $designation_stmt->bind_param('i', $current_values['designation_id']);
        //     $designation_stmt->execute();
        //     $designation_stmt->bind_result($old_designation_name);
        //     $designation_stmt->fetch();
        //     $designation_stmt->close();
        // }

        // Fetch old branch name
        $old_bd_name = null;
        if (!empty($current_values['bd_id'])) {
            $bd_query = "SELECT bd_name FROM branch_departments WHERE bd_id = ?";
            $bd_stmt = $conn->prepare($bd_query);
            $bd_stmt->bind_param('i', $current_values['bd_id']);
            $bd_stmt->execute();
            $bd_stmt->bind_result($old_bd_name);
            $bd_stmt->fetch();
            $bd_stmt->close();
        }

        // Compare and log changes
        $fields = [
            'bd_id' => ['new' => $branch, 'old' => $old_bd_name],
            'emp_cat_id' => ['new' => $emp_type, 'old' => $old_cat_name],
            'code' => ['new' => $emp_code, 'old' => $current_values['code']],
            'epf_no' => ['new' => $epf_no, 'old' => $current_values['epf_no']],
            'nic' => ['new' => $nic, 'old' => $current_values['nic']],
            'reporting_officer_emp_id' => ['new' => $reporting_to, 'old' => $current_values['reporting_officer_emp_id']]
        ];

        foreach ($fields as $field => $values) {
            if ($values['new'] != $current_values[$field]) {
                insertEmployeeHistoryLog($edit_id, $change_types[$field], $values['old']);
            }
        }



        // Prepare SQL statement for update
        $sql = "UPDATE employees SET 
                employee_id = ?,
                code = ?, 
                epf_no = ?, 
                title = ?, 
                name_with_initials = ?, 
                name_in_full = ?, 
                nic = ?, 
                gender = ?, 
                birthday = ?, 
                appointment_date = ?,
                address_line_1 = ?, 
                address_line_2 = ?, 
                mobile = ?, 
                work = ?, 
                home = ?, 
                email = ?, 
                emp_cat_id = ?, 
                marital_status = ?, 
                reporting_officer_emp_id = ?, 
                bd_id = ?, 
                last_updated_date = ?, 
                updated_by = ? 
                WHERE emp_id = ?";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind parameters for update
            $stmt->bind_param('sssssssissssssssiiiisii', 
                $employee_id, $emp_code, $epf_no, $title, $init_name, $full_name, $nic, $gender, $birthday, 
                $appointment_date, $address1, $address2, $mobile, $work_phone, $home_phone, $email, 
                $emp_type, $marital_status, $reporting_to, $branch, $datetime, $updated_by, $edit_id
            );

            // Execute update
            if ($stmt->execute()) {
                if (!empty($bankBranchId)) {
                    $sqlbb = "SELECT * FROM bank_branch WHERE code = ?";
                    $stmtbb = $conn->prepare($sqlbb);
                    $stmtbb->bind_param("s", $bankBranchId);
                    $stmtbb->execute();
                    $resultbb = $stmtbb->get_result();
                    $rowbb = $resultbb->fetch_assoc();
                    $bank_code = $rowbb['bank_code'];
                    $branch_code = $rowbb['branch_code'];
                    $bank_name = $rowbb['bank_name'].' - '.$rowbb['branch_name'];
                    $stmtbb->close();

                    $sql = "UPDATE employees SET bank_code = ?, branch_code = ?, account_number = ?, bank_branch_name = ? WHERE emp_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssssi', $bank_code, $branch_code, $account_number, $bank_name, $edit_id);    
                    $stmt->execute();
                    
                }

                // **Update or Insert into uploads table**
                if (!empty($file_path)) {
                    // Check if a record already exists for this request
                    $check_stmt = $conn->prepare("SELECT uploads_id FROM uploads WHERE request_id = ? AND document_type = 3");
                    $check_stmt->bind_param("i", $edit_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    
                    if ($check_stmt->num_rows > 0) {
                        // **Update existing upload record**
                        $upload_sql = "UPDATE uploads SET location = ? WHERE request_id = ? AND document_type = 3";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('si', $file_path1, $edit_id);
                    } else {
                        // **Insert new upload record**
                        $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (3, ?, ?, ?, ?)";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('siss', $file_path1, $edit_id, $added_by, $datetime);
                    }
    
                    if ($upload_stmt->execute()) {
                        $upload_stmt->close();
                    } else {
                        $errors[] = "Error updating upload record.";
                    }
    
                    $check_stmt->close();
                }

                // **Update or Insert into uploads table**
                if ($file_path_passbook) {
                    // Check if a record already exists for this request
                    $check_stmt = $conn->prepare("SELECT uploads_id FROM uploads WHERE request_id = ? AND document_type = 4");
                    $check_stmt->bind_param("i", $edit_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    
                    if ($check_stmt->num_rows > 0) {
                        // **Update existing upload record**
                        $upload_sql = "UPDATE uploads SET location = ? WHERE request_id = ? AND document_type = 4";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('si', $file_path2, $edit_id);
                    } else {
                        // **Insert new upload record**
                        $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (4, ?, ?, ?, ?)";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('siss', $file_path2, $edit_id, $added_by, $datetime);
                    }
    
                    if ($upload_stmt->execute()) {
                        $upload_stmt->close();
                    } else {
                        $errors[] = "Error updating upload record.";
                    }
    
                    $check_stmt->close();
                }
                $_SESSION['success'] = "Employee updated successfully.";
                echo json_encode(["status" => "success", "message" => "Employee updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing statement."]);
        }

        $stmt->close();
        $conn->close();
    }


    if (isset($_POST['edit_id_p']) && !empty($_POST['edit_id_p'])) {
        
        // Sanitize and validate input
        function clean_input($data) {
            return htmlspecialchars(strip_tags(trim($data)));
        }

        // Get employee data from POST request
        $edit_id = clean_input($_POST['edit_id_p']);
        $emp_designation = clean_input($_POST['emp_designation_p']);
        //$promote = clean_input($_POST['promote']);
        $effective_date = clean_input($_POST['effective_date']);

        $updated_by = $_SESSION['uid'];
        $datetime = date("Y-m-d H:i:s");

        // Validation checks
        $errors = [];

        if (empty($emp_designation)) $errors[] = "Employee Designation is required.";
        //if (!in_array($promote, ['0', '1'])) $errors[] = "Valid promote/demote selection is required.";
        if (empty($effective_date) || !strtotime($effective_date)) $errors[] = "Valid effective date is required.";

        // If errors, return them
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $current_designation_id = null;
        $lp_date = null;
        $current_query = "SELECT designation_id,lp_date FROM employees WHERE emp_id = ?";
        $current_stmt = $conn->prepare($current_query);

        if (!$current_stmt) {
            die(json_encode(["status" => "error", "message" => "Error preparing statement: " . $conn->error]));
        }

        $current_stmt->bind_param('i', $edit_id);
        $current_stmt->execute();
        $current_stmt->bind_result($current_designation_id,$lp_date);

        if (!$current_stmt->fetch()) {
            $current_designation_id = null; // Ensuring null if no result found
        }
        $current_stmt->close();

        // Fetch old names for category and designation
        $old_designation_name = "Unknown";

        // Fetch designation name
        if (!empty($current_designation_id)) {
            $designation_query = "SELECT designation FROM designations WHERE desig_id  = ?";
            $designation_stmt = $conn->prepare($designation_query);
            $designation_stmt->bind_param('i', $current_designation_id);
            $designation_stmt->execute();
            $designation_stmt->bind_result($old_designation_name);
            $designation_stmt->fetch();
            $designation_stmt->close();
        }

        
        if ($current_designation_id != $emp_designation) {
            $old_value = $old_designation_name.' - effective date '.$lp_date;
            insertEmployeeHistoryLog($edit_id, 3, $old_value);
        }
        

        // Prepare SQL statement for update
        $sql = "UPDATE employees SET 
                designation_id = ?, 
                lp_date = ?, 
                last_updated_date = ?, 
                updated_by = ?
                WHERE emp_id = ?";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind parameters for update
            $stmt->bind_param('issii', 
                $emp_designation, $effective_date, $datetime, $updated_by, $edit_id
            );

            // Execute update
            if ($stmt->execute()) {
                $_SESSION['success'] = "Employee updated successfully.";
                echo json_encode(["status" => "success", "message" => "Employee updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing statement."]);
        }

        $stmt->close();
        $conn->close();
    }
}

//search user

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $emp_id = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT employees.*,branch_departments.company_code,designations.designation,r.name_with_initials as reporting 
    , nic.location as nic_location, passbook.location as passbook_location, bb.code FROM employees 
    INNER JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
    INNER JOIN designations ON employees.designation_id = designations.desig_id
    LEFT JOIN employees r ON employees.reporting_officer_emp_id = r.emp_id
    LEFT JOIN uploads nic ON nic.request_id = employees.emp_id AND nic.document_type = 3
    LEFT JOIN uploads passbook ON passbook.request_id = employees.emp_id AND passbook.document_type = 4  
    LEFT JOIN bank_branch bb ON employees.bank_code = bb.bank_code AND employees.branch_code = bb.branch_code
    WHERE employees.emp_id = ?");
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found.']);
    }
}

?>
