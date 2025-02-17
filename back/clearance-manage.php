<?php
session_start();
require "connection/connection.php";
require "functions.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set JSON header

    if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
        
        function clean_input($data) {
            return htmlspecialchars(strip_tags(trim($data)));
        }
    
        $employee = clean_input($_POST['employee']);
        $resignation_date = clean_input($_POST['resignation_date']);
        $added_by = $_SESSION['uid'];
        $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($employee)) $errors[] = "Employee is required.";
        if (empty($resignation_date)) $errors[] = "Resignation date is required.";
    
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
        
        
        // Stop execution if there are errors
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }
    
        // **Insert into cl_requests table**
        $sql = "INSERT INTO cl_requests (emp_id, resignation_date, created_date, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('issi', $employee, $resignation_date, $datetime, $added_by);
    
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

                //insert into steps table
                $step_sql = "INSERT INTO cl_requests_steps (request_id, bd_code, created_by, created_date) VALUES (?, ?, ?, ?)";
                $step_stmt = $conn->prepare($step_sql);

                if ($step_stmt) {
                    $bd_code = 'HRD'; // Define the string variable before binding
                    $step_stmt->bind_param('isis', $request_id, $bd_code, $added_by, $datetime);
                    $step_stmt->execute();
                    $step_stmt->close();
                }

    
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
    
        $edit_id = intval($_POST['edit_id']);
        $employee = clean_input($_POST['employee']);
        $resignation_date = clean_input($_POST['resignation_date']);
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
    
        // **Stop execution if there are errors**
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }
    
        // **Update cl_requests table**
        $sql = "UPDATE cl_requests SET emp_id = ?, resignation_date = ? WHERE cl_req_id = ?";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('isi', $employee, $resignation_date, $edit_id);
    
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
    
                $_SESSION['success'] = "Clearance updated successfully.";
                echo json_encode(["status" => "success", "message" => "Clearance updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing update statement."]);
        }
    
        $stmt->close();
        $conn->close();
    }
    
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $row_id = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT cl_requests.*, employees.name_with_initials, employees.code, employees.epf_no, employees.title, uploads.location 
                        FROM cl_requests 
                        INNER JOIN employees ON cl_requests.emp_id = employees.emp_id AND cl_requests.status = 1 
                        LEFT JOIN uploads ON uploads.request_id = cl_requests.cl_req_id AND uploads.document_type = 1
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
    $sql = "SELECT employees.emp_id, employees.title, employees.name_with_initials, employees.nic, employees.epf_no, employees.code 
    FROM employees 
    LEFT JOIN cl_requests ON employees.emp_id = cl_requests.emp_id
    WHERE (employees.name_with_initials LIKE '%$search%' 
    OR employees.nic LIKE '%$search%' 
    OR employees.epf_no LIKE '%$search%' 
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
                $output .= "<a href='#' class='list-group-item list-group-item-action employee-option' data-id='".$row['emp_id']."'>".$row['title']." ".$row['name_with_initials']." (".$row['nic']." ".$row['code']." ".$row['epf_no'].")</a>";
            }
        } else {
            $output = "<a href='#' class='list-group-item list-group-item-action disabled'>No results found</a>";
        }

        echo $output;
}

?>
