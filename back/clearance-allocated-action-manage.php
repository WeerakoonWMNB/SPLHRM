<?php
session_start();
require "connection/connection.php";
require "functions.php";
include "mail-function.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cl_id = $_POST['cl_id'];
    $cl_step_id = $_POST['cl_step_id'];
    $note = trim($_POST['note']);
    $physical_items = $_POST['physical_items'] ?? [];
    $monetary_items = $_POST['monetary_items'] ?? [];
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");

    //print_r($monetary_items);exit;
    // Process monetary items
    $existing_monetary_ids = [];
    if (!empty($monetary_items)) {
        foreach ($monetary_items as $item) {
            $item_id = $item['cl_amount_item_id'];
            $quantity = !empty($item['quantity']) ? $item['quantity'] : 1;
            $amount = is_numeric($item['amount']) && $item['amount'] >= 0 ? $item['amount'] : 0.00;
            $issued_date = $item['issued_date'];
            if (!empty($issued_date)) {
                $date = DateTime::createFromFormat('Y-m-d', $issued_date);
                $is_valid_date = $date && $date->format('Y-m-d') === $issued_date;
                $issued_date = $is_valid_date ? "'$issued_date'" : "NULL";
            } else {
                $issued_date = "NULL";
            }

            $remark = $item['remark'];
            $type = $item['item_type'];
            $return_status = $item['return_status'];
            $existing_monetary_ids[] = $item_id;
            

            // Validate amount if needed
            if (($type == "1" || $type == "2") && (!$amount || $amount <= 0)) {
                echo json_encode(["error" => "Amount is required when Deduct or Pay is selected."]);
                exit;
            }

            $result = $conn->query("SELECT * FROM cl_request_step_amunt_items WHERE cl_amount_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
            
            if ($result->num_rows > 0) {
                    $destination = '';
                    // Check if the file for this itemId exists
                    if (isset($_FILES['files']['name'][$item_id])) {
                        
                        $row = $result->fetch_assoc();
                        $document_path = $row['document_path'];

                        // Remove existing file before uploading a new one
                        if ($document_path && file_exists(substr($document_path,3))) {
                            unlink(substr($document_path,3));
                        }

                        // File information for the specific itemId
                        $fileName = $_FILES['files']['name'][$item_id]; // Original file name
                        $fileTmpPath = $_FILES['files']['tmp_name'][$item_id]; // Temporary file path
                        $fileSize = $_FILES['files']['size'][$item_id]; // File size
                        $fileType = $_FILES['files']['type'][$item_id]; // File MIME type

                        // Define the upload directory and file path
                        $uploadDir = '../uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $newFileName = $item_id . '_' . time() . '_' . basename($fileName);
                        $destination = $uploadDir . $newFileName;

                        // Move the uploaded file to the desired location
                        if (move_uploaded_file($fileTmpPath, $destination)) {
                            $destination = '../'.$destination;
                        } 
                    }
                    $update_query = "UPDATE cl_request_step_amunt_items SET quantity = '$quantity', amount = '$amount', 
                                    issued_date = $issued_date, item_type = '$type',";

                                    if (!empty($destination)) {
                                        $update_query .= "document_path = '$destination',";
                                    }
                                     
                    $update_query .= "return_status = '$return_status', remark = '$remark', last_updated_by = '$by', 
                                    last_updated_date = '$datetime' WHERE cl_amount_item_id = '$item_id' AND 
                                    request_id = '$cl_id' AND step_id = '$cl_step_id'";
                    $conn->query($update_query);
            } else {
                $destination = '';
                    // Check if the file for this itemId exists
                    if (isset($_FILES['files']['name'][$item_id])) {
                        
                        // File information for the specific itemId
                        $fileName = $_FILES['files']['name'][$item_id]; // Original file name
                        $fileTmpPath = $_FILES['files']['tmp_name'][$item_id]; // Temporary file path
                        $fileSize = $_FILES['files']['size'][$item_id]; // File size
                        $fileType = $_FILES['files']['type'][$item_id]; // File MIME type

                        // Define the upload directory and file path
                        $uploadDir = '../uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $newFileName = $item_id . '_' . time() . '_' . basename($fileName);
                        $destination = $uploadDir . $newFileName;

                        // Move the uploaded file to the desired location
                        if (move_uploaded_file($fileTmpPath, $destination)) {
                            $destination = '../'.$destination;
                        } 
                    }
                $conn->query("INSERT INTO cl_request_step_amunt_items (cl_amount_item_id, request_id, step_id, item_type, quantity, amount, issued_date, return_status, remark, created_by, created_date, document_path) VALUES ('$item_id', '$cl_id', '$cl_step_id', '$type', '$quantity', '$amount', $issued_date, '$return_status', '$remark', '$by', '$datetime', '$destination')");
            }
        }
        // Delete unmatched monetery records
        if (!empty($existing_monetary_ids)) {
            $conn->query("DELETE FROM cl_request_step_amunt_items WHERE request_id = '$cl_id' AND 
            step_id = '$cl_step_id' AND cl_amount_item_id NOT IN ('" . implode("','", $existing_monetary_ids) . "')");
        }
        if (!empty($existing_monetary_ids)) {
            // Create a string of IDs to delete
            $ids_to_delete = implode("','", $existing_monetary_ids);
            
            // Step 1: Get the paths of documents to delete
            $result = $conn->query("SELECT document_path FROM cl_request_step_amunt_items WHERE request_id = '$cl_id' 
            AND step_id = '$cl_step_id' AND cl_amount_item_id NOT IN ('$ids_to_delete')");
        
            // Loop through the results and delete the documents
            while ($row = $result->fetch_assoc()) {
                $document_path = $row['document_path'];

                if ($document_path && file_exists(substr($document_path,3))) {
                    unlink(substr($document_path,3));
                }
                
            }
        
            // Step 2: Delete the records from the database
            $conn->query("DELETE FROM cl_request_step_amunt_items WHERE request_id = '$cl_id' AND step_id = '$cl_step_id' AND cl_amount_item_id NOT IN ('$ids_to_delete')");
        }
        
    }

    // Process physical items
    $existing_physical_ids = [];
    if (!empty($physical_items)) {
        foreach ($physical_items as $item) {
            $item_id = $item['cl_physical_item_id'];
            $quantity = !empty($item['quantity']) ? $item['quantity'] : 1;
            $remark = $item['remark'];
            $type = $item['item_type'];
            $existing_physical_ids[] = $item_id;

            $result = $conn->query("SELECT * FROM cl_request_step_physical_items WHERE cl_physical_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
            if ($result->num_rows > 0) {
                $conn->query("UPDATE cl_request_step_physical_items SET quantity = '$quantity', remark = '$remark', 
                last_updated_by = '$by', last_updated_date = '$datetime', item_type = '$type' WHERE cl_physical_item_id = '$item_id' 
                AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
            } else {
                $conn->query("INSERT INTO cl_request_step_physical_items (cl_physical_item_id, request_id, step_id, item_type, quantity, remark, created_by, created_date) VALUES ('$item_id', '$cl_id', '$cl_step_id', '$type', '$quantity', '$remark', '$by', '$datetime')");
            }
        }

        // Delete unmatched physical records
        if (!empty($existing_physical_ids)) {
            $conn->query("DELETE FROM cl_request_step_physical_items WHERE request_id = '$cl_id' AND step_id = '$cl_step_id' AND cl_physical_item_id NOT IN ('" . implode("','", $existing_physical_ids) . "')");
        }
    }

    
    if (isset($_FILES['customervisitreport'])) {
        $file_path_cvr = null; // Default null, will store path if valid file uploaded
    
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
    
                if (move_uploaded_file($file_tmp, $file_path_cvr)) {

                    $existing_file_cvr = null;
                    $stmt = $conn->prepare("SELECT location FROM uploads WHERE request_id = ? AND document_type = 2");  //2 for customer visit report
                    $stmt->bind_param("i", $cl_id);
                    $stmt->execute();
                    $stmt->bind_result($existing_file_cvr);
                    $stmt->fetch();
                    $stmt->close();

                    if ($existing_file_cvr && file_exists(substr($existing_file_cvr,3))) {
                        unlink(substr($existing_file_cvr,3));
                    }

                    $check_stmt = $conn->prepare("SELECT uploads_id FROM uploads WHERE request_id = ? AND document_type = 2");
                    $check_stmt->bind_param("i", $cl_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    
                    if ($check_stmt->num_rows > 0) {
                        // **Update existing upload record**
                        $upload_sql = "UPDATE uploads SET location = ? WHERE request_id = ? AND document_type = 2";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('si', $file_path_cvr1, $cl_id);
                    } else {
                        // **Insert new upload record**
                        $upload_sql = "INSERT INTO uploads (document_type, location, request_id, created_by, created_date) VALUES (2, ?, ?, ?, ?)";
                        $upload_stmt = $conn->prepare($upload_sql);
                        $upload_stmt->bind_param('siss', $file_path_cvr1, $cl_id, $by, $datetime);
                    }
    
                    if ($upload_stmt->execute()) {
                        $upload_stmt->close();
                    } 
    
                    $check_stmt->close();
                }
            }
        
    } 


    // Update clearance step
    $sql = "UPDATE cl_requests_steps SET prepare_check_approve = 1, prepared_by = ?, prepared_date = ?, last_updated_date = ?, last_updated_by = ? WHERE cl_step_id = ? AND prepare_check_approve = 0";
    $stmt = $conn->prepare($sql);   
    $stmt->bind_param("issii", $by, $datetime, $datetime, $by, $cl_step_id);
    $stmt->execute();
    $stmt->close();

    // // Select next user to be attended
    $query = "SELECT assigned_preparer_user_id, assigned_checker_user_id, assigned_approver_user_id, prepared_by, checked_by, approved_by 
              FROM cl_requests_steps WHERE request_id = ? AND step !='0' AND is_complete !='1' ORDER BY step ASC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cl_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $row = $result->fetch_assoc();
    $next_user = null;

    // if (!empty($row['assigned_preparer_user_id']) && empty($row['prepared_by'])) {
    //     $next_user = $row['assigned_preparer_user_id'];
    // } else 
    if (!empty($row['assigned_checker_user_id']) && empty($row['checked_by']) && $row['assigned_checker_user_id'] != $by ) {
        $next_user = $row['assigned_checker_user_id'];
    } else if (!empty($row['assigned_approver_user_id']) && empty($row['approved_by']) && $row['assigned_approver_user_id'] != $by ) {
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

        clearanceRequestStepNotice($cl_id, $next_user); // Send email notification
    }

    $_SESSION['success'] = "Successfully Saved.";
    echo json_encode(["message" => "Data saved successfully."]);
    exit;
}

