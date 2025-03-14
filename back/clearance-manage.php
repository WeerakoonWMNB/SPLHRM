<?php
session_start();
require "connection/connection.php";
require "functions.php";
include "mail-function.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set JSON header

    if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
        
        function clean_input($data) {
            return htmlspecialchars(strip_tags(trim($data)));
        }

        if (!isset($_POST['rsv'])) {
            echo json_encode(["status" => "error", "message" => ["Service letter recommendation is required."]]);
            exit();
        }

        if (!isset($_POST['rejoin'])) {
            echo json_encode(["status" => "error", "message" => ["Rejoin status is required."]]);
            exit();
        }
    
        $employee = clean_input($_POST['employee']);
        $resignation_date = clean_input($_POST['resignation_date']);
        $rsv = intval($_POST['rsv']);
        $rejoin = intval($_POST['rejoin']);
        $added_by = $_SESSION['uid'];
        $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($employee)) $errors[] = "Employee is required.";
        if (empty($resignation_date)) $errors[] = "Resignation date is required.";
        if (empty($_FILES['resignationLetter']['name'])) $errors[] = "Resignation Letter is required.";
    
        // **Handle File Upload Validation First**
        $file_path = null; // Default null, will store path if valid file uploaded
    
        if (!empty($_FILES['resignationLetter']['name'])) {
            $file = $_FILES['resignationLetter'];
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
    
            // Move file to uploads directory if no errors
            if (empty($errors)) {
                $upload_dir = "../uploads/";
                $file_path1 = "../../uploads/" . $file_name;
                $file_path = $upload_dir . $file_name;
    
                if (!move_uploaded_file($file_tmp, $file_path)) {
                    $errors[] = "Failed to upload file.";
                }
            }
        }
        
        $file_path_cvr = null; // Default null, will store path if valid file uploaded
    
        if (!empty($_FILES['customervisitreport']['name'])) {
            $file = $_FILES['customervisitreport'];
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
    
            // Move file to uploads directory if no errors
            if (empty($errors)) {
                $upload_dir = "../uploads/";
                $file_path_cvr1 = "../../uploads/" . $file_name;
                $file_path_cvr = $upload_dir . $file_name;
    
                if (!move_uploaded_file($file_tmp, $file_path_cvr)) {
                    $errors[] = "Failed to upload file.";
                }
            }
        }
        
        // Stop execution if there are errors
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }
    
        // **Insert into cl_requests table**
        $sql = "INSERT INTO cl_requests (emp_id, resignation_date, service_letter_recommendation, rejoin_or_not, created_date, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('isiisi', $employee, $resignation_date, $rsv, $rejoin, $datetime, $added_by);
    
            if ($stmt->execute()) {
                $request_id = $stmt->insert_id; // Get last inserted ID
    
                // **If a file was uploaded, insert into uploads table**
                if ($file_path) {
                    
                    $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (1, ?, ?, ?, ?)";
                    $upload_stmt = $conn->prepare($upload_sql);
    
                    if ($upload_stmt) {
                        $upload_stmt->bind_param('siss', $file_path1, $request_id, $added_by, $datetime);
                        $upload_stmt->execute();
                        $upload_stmt->close();
                    }
                    
                }

                if ($file_path_cvr) {
                    
                    $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (2, ?, ?, ?, ?)";
                    $upload_stmt = $conn->prepare($upload_sql);
    
                    if ($upload_stmt) {
                        $upload_stmt->bind_param('siss', $file_path_cvr1, $request_id, $added_by, $datetime);
                        $upload_stmt->execute();
                        $upload_stmt->close();
                    }
                    
                }

                //insert into steps table
                $step_sql = "INSERT INTO cl_requests_steps (request_id, bd_code, max_dates, created_by, created_date) VALUES (?, ?, ?, ?, ?)";
                $step_stmt = $conn->prepare($step_sql);
                $max_dates = 2; // Default 2 days for HRD
                if ($step_stmt) {
                    $bd_code = 'HRD'; // Define the string variable before binding
                    $step_stmt->bind_param('isiis', $request_id, $bd_code, $max_dates, $added_by, $datetime);
                    $step_stmt->execute();
                    $step_stmt->close();
                }

                clearanceRequest($request_id); // Send email notification
                $_SESSION['success'] = "Clearance added successfully.";
                echo json_encode(["status" => "success", "message" => "Clearance added successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing statement."]);
        }
    
        $stmt->close();
        $conn->close();
    }
    
    //clearance edit
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    
        function clean_input($data) {
            return htmlspecialchars(strip_tags(trim($data)));
        }

        if (!isset($_POST['rsv'])) {
            echo json_encode(["status" => "error", "message" => ["Service letter recommendation is required."]]);
            exit();
        }

        if (!isset($_POST['rejoin'])) {
            echo json_encode(["status" => "error", "message" => ["Rejoin status is required."]]);
            exit();
        }
        
        $edit_id = intval($_POST['edit_id']);
        $employee = clean_input($_POST['employee']);
        $resignation_date = clean_input($_POST['resignation_date']);
        $rsv = intval($_POST['rsv']);
        $rejoin = intval($_POST['rejoin']);
        $added_by = $_SESSION['uid'];
        $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($employee)) $errors[] = "Employee is required.";
        if (empty($resignation_date)) $errors[] = "Resignation date is required.";
    
        $file_path = null; // Default null, will store path if a valid file is uploaded
        $upload_dir = "../uploads/"; // Upload directory
    
        // **Fetch existing file before updating**
        $existing_file = null;
        $stmt = $conn->prepare("SELECT location FROM uploads WHERE request_id = ? AND document_type = 1");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $stmt->bind_result($existing_file);
        $stmt->fetch();
        $stmt->close();
        
        // **Handle file upload if a new file is provided**
        if (!empty($_FILES['resignationLetter']['name'])) {
            $file = $_FILES['resignationLetter'];
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

        //------------customer visit report upload----------------
        $file_path_cvr = null; // Default null, will store path if a valid file is uploaded
        $existing_file_cvr = null;
        $stmt = $conn->prepare("SELECT location FROM uploads WHERE request_id = ? AND document_type = 2");  //2 for customer visit report
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $stmt->bind_result($existing_file_cvr);
        $stmt->fetch();
        $stmt->close();

        // **Handle file upload if a new file is provided**
        if (!empty($_FILES['customervisitreport']['name'])) {
            $file = $_FILES['customervisitreport'];
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
            if ($existing_file_cvr && file_exists(substr($existing_file_cvr,3))) {
                unlink(substr($existing_file_cvr,3));
            }
    
            // Move new file if no errors
            if (empty($errors)) {
                $file_path_cvr1 = "../../uploads/" . $file_name; // Path for DB
                $file_path_cvr = $upload_dir . $file_name; // Actual server path
    
                if (!move_uploaded_file($file_tmp, $file_path_cvr)) {
                    $errors[] = "Failed to upload file.";
                }
            }
        }
    
        // **Stop execution if there are errors**
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }
    
        // **Update cl_requests table**
        $sql = "UPDATE cl_requests SET emp_id = ?, resignation_date = ?, service_letter_recommendation = ?, rejoin_or_not = ? WHERE cl_req_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('isiii', $employee, $resignation_date, $rsv, $rejoin, $edit_id);
    
            if ($stmt->execute()) {
                
                // **Update or Insert into uploads table**
                if ($file_path) {
                    // Check if a record already exists for this request
                    $check_stmt = $conn->prepare("SELECT uploads_id FROM uploads WHERE request_id = ? AND document_type = 1");
                    $check_stmt->bind_param("i", $edit_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    
                    if ($check_stmt->num_rows > 0) {
                        // **Update existing upload record**
                        $upload_sql = "UPDATE uploads SET location = ? WHERE request_id = ? AND document_type = 1";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('si', $file_path1, $edit_id);
                    } else {
                        // **Insert new upload record**
                        $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (1, ?, ?, ?, ?)";
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
                if ($file_path_cvr) {
                    // Check if a record already exists for this request
                    $check_stmt = $conn->prepare("SELECT uploads_id FROM uploads WHERE request_id = ? AND document_type = 2");
                    $check_stmt->bind_param("i", $edit_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    
                    if ($check_stmt->num_rows > 0) {
                        // **Update existing upload record**
                        $upload_sql = "UPDATE uploads SET location = ? WHERE request_id = ? AND document_type = 2";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('si', $file_path_cvr1, $edit_id);
                    } else {
                        // **Insert new upload record**
                        $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (2, ?, ?, ?, ?)";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('siss', $file_path_cvr1, $edit_id, $added_by, $datetime);
                    }
    
                    if ($upload_stmt->execute()) {
                        $upload_stmt->close();
                    } else {
                        $errors[] = "Error updating upload record.";
                    }
    
                    $check_stmt->close();
                }
    
                $_SESSION['success'] = "Clearance updated successfully.";
                echo json_encode(["status" => "success", "message" => "Clearance updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => ["Error: " . $stmt->error]]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => ["Error preparing update statement."]]);
        }
    
        $stmt->close();
        $conn->close();
    }
    
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $row_id = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT cl_requests.*, employees.name_with_initials, employees.code, employees.epf_no, employees.title, 
                            letter.location as letter_location, cvr.location as cvr_location
                        FROM cl_requests 
                        INNER JOIN employees ON cl_requests.emp_id = employees.emp_id AND cl_requests.status = 1 
                        LEFT JOIN uploads letter ON letter.request_id = cl_requests.cl_req_id AND letter.document_type = 1
                        LEFT JOIN uploads cvr ON cvr.request_id = cl_requests.cl_req_id AND cvr.document_type = 2
                        WHERE cl_requests.cl_req_id = ?");
    $stmt->bind_param("i", $row_id);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found.']);
    }
}

//delete request
if (isset($_POST['delete_data'])) {
    $row_id = $_POST['row_id'];
    $edit_user = $_SESSION['uid'];

    $user = "UPDATE cl_requests SET status = 0, status_change_date = '$datetime' , status_change_by = $edit_user  WHERE cl_req_id = '$row_id' ";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Request deleted successfully.";
        header("Location: ../pages/clearance/clearance-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete Request.";
        header("Location: ../pages/clearance/clearance-list.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['filteredEmps'])) {
    // Get search term
    $search = $_POST['filteredEmps'];
    $user_level = $_SESSION['ulvl'];
    $bd_id = $_SESSION['bd_id'];

    // Base query
    $sql = "SELECT employees.emp_id, employees.title, employees.name_with_initials, employees.nic, employees.system_emp_no, employees.code 
    FROM employees 
    LEFT JOIN cl_requests ON employees.emp_id = cl_requests.emp_id
    WHERE (employees.name_with_initials LIKE '%$search%' 
    OR employees.nic LIKE '%$search%' 
    OR employees.system_emp_no LIKE '%$search%' 
    OR employees.code LIKE '%$search%' 
    OR employees.name_in_full LIKE '%$search%') AND (cl_requests.status=0 OR cl_requests.status IS NULL) ";

    // Apply bd_id filter if user level is 1 or 2
    if ($user_level != 1 && $user_level != 2) {
        $sql .= " AND employees.bd_id IN ('$bd_id')";
    }

    $sql .= " LIMIT 10";

    $result = $conn->query($sql);

        $output = ""; 

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<a href='#' class='list-group-item list-group-item-action employee-option' data-id='".$row['emp_id']."'>".$row['title']." ".$row['name_with_initials']." (".$row['nic']." ".$row['code']." ".$row['system_emp_no'].")</a>";
            }
        } else {
            $output = "<a href='#' class='list-group-item list-group-item-action disabled'>No results found</a>";
        }

        echo $output;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pending'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_id = clean_input($_POST['cl_id']);
    $pending_note = clean_input($_POST['pending_note']);
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($cl_id)) $errors[] = "Clearance id required.";
        if (empty($pending_note)) $errors[] = "Pending note is required.";

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $sql = "UPDATE cl_requests_steps SET is_complete = 2, pending_note = ?, last_updated_by = ?, last_updated_date = ? WHERE request_id = ? AND step = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $pending_note, $by, $datetime, $cl_id);

        if ($stmt->execute()) {
            clearanceRequestPending($cl_id,$pending_note); // Send email notification
            $_SESSION['success'] = "Request marked as pending.";
            echo json_encode(["status" => "success", "message" => "Request marked as pending."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark as pending."]);
        }
        

}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_id = clean_input($_POST['cl_id']);
    $approve_note = clean_input($_POST['approve_note']);
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($cl_id)) $errors[] = "Clearance id required.";

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $sql = "UPDATE cl_requests_steps SET is_complete = 1, complete_note = ?, last_updated_by = ?, last_updated_date = ? WHERE request_id = ? AND step = 0 AND is_complete != 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $approve_note, $by, $datetime, $cl_id);

        if ($stmt->execute()) {
            clearanceRequestAccept($cl_id,$approve_note); // Send email notification
            $_SESSION['success'] = "Request marked as approved.";
            echo json_encode(["status" => "success", "message" => "Request marked as approved."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark as approved."]);
        }
        

}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'allocate') {

    $cl_id = isset($_POST['cl_id']) ? intval($_POST['cl_id']) : 0;
    $departments = $_POST['selectedDepartments'] ?? [];
    $sequences = $_POST['selectedSequence'] ?? [];
    $preparers = $_POST['selectedPreparer'] ?? [];
    $checkers = $_POST['selectedChecker'] ?? [];
    $approvers = $_POST['selectedApprover'] ?? [];
    $created_by = $_SESSION['uid']; // Replace with actual user session ID
    $created_date = date('Y-m-d H:i:s');

    if ($cl_id === 0 || empty($departments) || empty($sequences) || empty($approvers)) {
        echo json_encode(["status" => "error", "message" => "Invalid input data."]);
        exit;
    }

     // Ensure no empty values inside the arrays
     if (count(array_filter($departments)) !== count($departments) 
     || count(array_filter($sequences)) !== count($sequences)
     || count(array_filter($approvers)) !== count($approvers)
     ) {
         echo json_encode(["status" => "error", "message" => "One or more fields are empty."]);
         exit;
     }

    // Ensure all arrays have the same length
    if (count($departments) !== count($sequences) || 
        count($departments) !== count($preparers) || 
        count($departments) !== count($checkers) || 
        count($departments) !== count($approvers)) {
        echo json_encode(["status" => "error", "message" => "Mismatched input data."]);
        exit;
    }

    // Start MySQL Transaction
    mysqli_begin_transaction($conn);

    try {
        // Get existing records
        $existingRecords = [];
        $query = "SELECT bd_code, step FROM cl_requests_steps WHERE request_id = ? AND is_complete = 0";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $cl_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $existingRecords[$row['bd_code']] = $row['step'];
        }
        mysqli_stmt_close($stmt);

        // Prepare new records mapping
        $newRecords = [];
        foreach ($departments as $key => $bd_code) {
            $newRecords[$bd_code] = [
                'step' => intval($sequences[$key]),
                'preparer' => intval($preparers[$key] ?? 0),
                'checker' => intval($checkers[$key] ?? 0),
                'approver' => intval($approvers[$key] ?? 0)
            ];
        }
        
        $toUpdate = array_intersect_key($newRecords, $existingRecords); // Common records
        $toInsert = array_diff_key($newRecords, $existingRecords); // New records
        $toDelete = array_diff_key($existingRecords, $newRecords); // Records to delete

        // **Update Existing Records**
        foreach ($toUpdate as $bd_code => $data) {
            $step = $data['step'];
            $preparer = $data['preparer'];
            $checker = $data['checker'];
            $approver = $data['approver'];

            $query = "UPDATE cl_requests_steps 
                      SET step = ?, last_updated_by = ?, last_updated_date = ?, 
                          assigned_preparer_user_id = ?, assigned_checker_user_id = ?, assigned_approver_user_id = ? 
                      WHERE request_id = ? AND bd_code = ? AND is_complete != '1' AND step != 0";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iisiiiis", $step, $created_by, $created_date, $preparer, $checker, $approver, $cl_id, $bd_code);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
        }

        // **Insert New Records**
        foreach ($toInsert as $bd_code => $data) {
            $step = $data['step'];
            $preparer = $data['preparer'];
            $checker = $data['checker'];
            $approver = $data['approver'];

            // Fetch max_dates based on bd_code and emp_cat_id
            $max_dates_query = "SELECT seiling_dates_for_backoffice, ceiling_dates_for_marketing,
                (SELECT emp_cat_id FROM employees WHERE emp_id = (SELECT emp_id FROM cl_requests WHERE cl_req_id = ?)) as emp_cat_id
                FROM branch_departments WHERE bd_code = ?";
            $stmt = mysqli_prepare($conn, $max_dates_query);
            mysqli_stmt_bind_param($stmt, "is", $cl_id, $bd_code);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $max_dates_ar = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            $max_dates = $max_dates_ar['seiling_dates_for_backoffice'];
            if ($max_dates_ar['emp_cat_id'] != '1') {
                $max_dates = $max_dates_ar['ceiling_dates_for_marketing'];
            }

            $query = "INSERT INTO cl_requests_steps 
                      (request_id, step, bd_code, max_dates, created_date, created_by, 
                       assigned_preparer_user_id, assigned_checker_user_id, assigned_approver_user_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iisisiiii", $cl_id, $step, $bd_code, $max_dates, $created_date, $created_by, $preparer, $checker, $approver);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // **Delete Unmatched Records**
        if (!empty($toDelete)) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $query = "DELETE FROM cl_requests_steps WHERE request_id = ? AND bd_code IN ($placeholders) AND is_complete = 0 AND step != 0";

            $stmt = mysqli_prepare($conn, $query);
            $types = str_repeat('s', count($toDelete) + 1);
            mysqli_stmt_bind_param($stmt, $types, ...array_merge([$cl_id], array_keys($toDelete)));
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        mysqli_commit($conn);

        //select next user to be attended
        $query = "SELECT assigned_preparer_user_id, assigned_checker_user_id, assigned_approver_user_id, prepared_by, checked_by, approved_by 
        FROM cl_requests_steps WHERE request_id = ? AND step !='0' AND is_complete !='1' ORDER BY step ASC LIMIT 1";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $cl_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();
        $next_user = null;

        if (!empty($row['assigned_preparer_user_id']) && empty($row['prepared_by'])) {
            $next_user = $row['assigned_preparer_user_id'];
        } else if (!empty($row['assigned_checker_user_id']) && empty($row['checked_by'])) {
            $next_user = $row['assigned_checker_user_id'];
        } else if (!empty($row['assigned_approver_user_id']) && empty($row['approved_by'])) {
            $next_user = $row['assigned_approver_user_id'];
        }

        if ($next_user) {
            $user = "SELECT username FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($user);
            $stmt->bind_param("i", $next_user);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $next_user = $user['username'];
            
            clearanceRequestStepNotice($cl_id,$next_user); // Send email notification
        }

        $_SESSION['success'] = "Departments allocated successfully.";
        echo json_encode(["status" => "success", "message" => "Departments allocated successfully."]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['cl_id_for_fetch']) ) {
    // Get cl_id from the request
    $cl_id = isset($_GET['cl_id_for_fetch']) ? intval($_GET['cl_id_for_fetch']) : 0;

    // Fetch records by cl_id
    $query = "SELECT assigned_preparer_user_id, assigned_checker_user_id, assigned_approver_user_id, bd_code, step, is_complete 
    FROM cl_requests_steps WHERE request_id = ? AND step !='0' ORDER BY step ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cl_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = [
            'is_complete' => $row['is_complete'],
            'department_id' => $row['bd_code'],
            'sequence' => $row['step'],
            'assigned_preparer_user_id' => $row['assigned_preparer_user_id'],
            'assigned_checker_user_id' => $row['assigned_checker_user_id'],
            'assigned_approver_user_id' => $row['assigned_approver_user_id'],
        ];
    }

    echo json_encode($records);
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['bd_id'])) {

    $bd_id = $_GET['bd_id'];

    // Prepare the SQL statement to prevent SQL injection
    $query = "SELECT * FROM users
                WHERE users.is_active = 1 AND FIND_IN_SET(?, users.bd_id) > 0";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $bd_id); // "s" means string
        $stmt->execute();
        $result = $stmt->get_result();

        $emps = [];
        while ($row = $result->fetch_assoc()) {
            $emps[] = $row;
        }

        // Close the statement
        $stmt->close();

        // Return JSON response
        header("Content-Type: application/json");
        echo json_encode($emps);
    } else {
        // Handle SQL error
        echo json_encode(["error" => "Query preparation failed"]);
    }

    // Close database connection
    $conn->close();
}

?>

