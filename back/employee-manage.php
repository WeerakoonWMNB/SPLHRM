<?php
session_start();
require "connection/connection.php";
require "functions.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['company_code'])) {
    $company_code = intval($_POST['company_code']);
    
    $sql = "SELECT bd_id , bd_name FROM branch_departments WHERE company_code = ?";
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
    $sql = "SELECT emp_id , titile, name_with_initials, nic, epf_no, code FROM employees 
    WHERE name_with_initials LIKE '%$search%' OR 
    nic LIKE '%$search%' OR 
    epf_no LIKE '%$search%' OR 
    code LIKE '%$search%' OR 
    name_in_full LIKE '%$search%' LIMIT 10";
    $result = $conn->query($sql);

        $output = ""; 

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<a href='#' class='list-group-item list-group-item-action employee-option' data-id='".$row['emp_id']."'>".$row['titile']." ".$row['name_with_initials']." (".$row['nic']." ".$row['code']." ".$row['epf_no'].")</a>";
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
        $email = clean_input($_POST['email']);
        $mobile = clean_input($_POST['mobile']);
        $work_phone = clean_input($_POST['work']);
        $home_phone = clean_input($_POST['home']);
        $reporting_to = clean_input($_POST['reporting']);

        $added_by = $_SESSION['uid'];
        $datetime = date("Y-m-d H:i:s");

        $errors = [];

        if (empty($branch)) $errors[] = "Branch/Department is required.";
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($init_name)) $errors[] = "Initial name is required.";
        if (empty($full_name)) $errors[] = "Full name is required.";
        if (empty($emp_type)) $errors[] = "Employee type is required.";
        if (in_array($emp_type, ['2', '3']) && (empty($emp_code))) {
            $errors[] = "Valid Employee Code is required.";
        }
        if (empty($emp_designation)) $errors[] = "Employee Designation is required.";
        if (empty($address1)) $errors[] = "Employee Address is required.";
        if (empty($epf_no)) $errors[] = "Valid EPF number is required.";
        if (empty($nic) || !preg_match('/^[0-9]{9}[vVxX]$|^[0-9]{12}$/', $nic)) $errors[] = "Valid NIC is required.";
        if (!in_array($gender, ['0', '1'])) $errors[] = "Valid gender selection is required.";
        if (!in_array($marital_status, ['0', '1'])) $errors[] = "Marital status is required.";
        if (empty($birthday) || !strtotime($birthday)) $errors[] = "Valid birthday date is required.";
        if (empty($appointment_date) || !strtotime($appointment_date)) $errors[] = "Valid appointment date is required.";
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        if (!empty($mobile) && !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid mobile number is required.";
        if (!empty($work_phone) && !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid Phone(Work) is required.";
        if (!empty($home_phone) && !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid Phone(home) is required.";

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $sql = "INSERT INTO employees (code, epf_no, titile, name_with_initials, name_in_full, nic, gender, birthday, appointment_date, designation_id, address_line_1, address_line_2, mobile, work, home, email, emp_cat_id, marital_status, reporting_officer_emp_id, bd_id, created_date, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('ssssssississssssiiiiss', 
                $emp_code, $epf_no, $title, $init_name, $full_name, $nic, $gender, $birthday, 
                $appointment_date, $emp_designation, $address1, $address2, $mobile, $work_phone, $home_phone, $email, 
                $emp_type, $marital_status, $reporting_to, $branch, $datetime, $added_by
            );

            if ($stmt->execute()) {
                $_SESSION['success'] = "Employee added successfully.";
                echo json_encode(["status" => "success", "message" => "Employee added successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing statement."]);
        }

        $stmt->close();
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
        $emp_designation = clean_input($_POST['emp_designation']);
        $address1 = clean_input($_POST['address1']);
        $address2 = clean_input($_POST['address2']);
        $epf_no = clean_input($_POST['epf']);
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
        if (empty($emp_designation)) $errors[] = "Employee Designation is required.";
        if (empty($address1)) $errors[] = "Employee Address is required.";
        if (empty($epf_no)) $errors[] = "Valid EPF number is required.";
        if (empty($nic) || !preg_match('/^[0-9]{9}[vVxX]$|^[0-9]{12}$/', $nic)) $errors[] = "Valid NIC is required.";
        if (!in_array($gender, ['0', '1'])) $errors[] = "Valid gender selection is required.";
        if (!in_array($marital_status, ['0', '1'])) $errors[] = "Marital status is required.";
        if (empty($birthday) || !strtotime($birthday)) $errors[] = "Valid birthday date is required.";
        if (empty($appointment_date) || !strtotime($appointment_date)) $errors[] = "Valid appointment date is required.";
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        if (!empty($mobile) && !preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid mobile number is required.";
        if (!empty($work_phone) && !preg_match('/^\d{10}$/', $work_phone)) $errors[] = "Valid Phone(Work) is required.";
        if (!empty($home_phone) && !preg_match('/^\d{10}$/', $home_phone)) $errors[] = "Valid Phone(home) is required.";

        // If errors, return them
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        // Fetch current values from the database to compare
        $current_values = [
            'bd_id' => null,
            'emp_cat_id' => null,
            'designation_id' => null,
            'code' => null,
            'epf_no' => null,
            'nic' => null,
            'reporting_officer_emp_id' => null
        ];

        $current_query = "SELECT bd_id, emp_cat_id, designation_id, code, epf_no, nic, reporting_officer_emp_id 
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
            $current_values['designation_id'], 
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
            'designation_id' => 3,
            'code' => 4,
            'epf_no' => 5,
            'nic' => 6,
            'reporting_officer_emp_id' => 7
        ];

        // Fetch old names for category and designation
        $old_cat_name = "Unknown";
        $old_designation_name = "Unknown";

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
        if (!empty($current_values['designation_id'])) {
            $designation_query = "SELECT designation FROM designations WHERE desig_id  = ?";
            $designation_stmt = $conn->prepare($designation_query);
            $designation_stmt->bind_param('i', $current_values['designation_id']);
            $designation_stmt->execute();
            $designation_stmt->bind_result($old_designation_name);
            $designation_stmt->fetch();
            $designation_stmt->close();
        }

        // Fetch old branch name
        $old_bd_name = "Unknown";
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
            'designation_id' => ['new' => $emp_designation, 'old' => $old_designation_name],
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
                code = ?, 
                epf_no = ?, 
                titile = ?, 
                name_with_initials = ?, 
                name_in_full = ?, 
                nic = ?, 
                gender = ?, 
                birthday = ?, 
                appointment_date = ?, 
                designation_id = ?, 
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
            $stmt->bind_param('ssssssississssssiiiisii', 
                $emp_code, $epf_no, $title, $init_name, $full_name, $nic, $gender, $birthday, 
                $appointment_date, $emp_designation, $address1, $address2, $mobile, $work_phone, $home_phone, $email, 
                $emp_type, $marital_status, $reporting_to, $branch, $datetime, $updated_by, $edit_id
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

    $stmt = $conn->prepare("SELECT employees.*,branch_departments.company_code,designations.designation,r.name_with_initials as reporting FROM employees 
    INNER JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
    INNER JOIN designations ON employees.designation_id = designations.desig_id
    LEFT JOIN employees r ON employees.reporting_officer_emp_id = r.emp_id   
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
