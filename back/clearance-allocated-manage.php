<?php
session_start();
require "connection/connection.php";
require "functions.php";
include "mail-function.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pending'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_step_id = clean_input($_POST['cl_step_id']);//step id
    $cl_id = clean_input($_POST['cl_id']);//step id
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

        $sql = "UPDATE cl_requests_steps SET is_complete = 2, pending_note = ?, last_updated_by = ?, last_updated_date = ? WHERE cl_step_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $pending_note, $by, $datetime, $cl_step_id);

        if ($stmt->execute()) {
            clearanceRequestPending($cl_id,$pending_note); // Send email notification
            $_SESSION['success'] = "Request marked as pending.";
            echo json_encode(["status" => "success", "message" => "Request marked as pending."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark as pending."]);
        }
        

}

// if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit'])) {
//     $cl_id = $_POST['cl_id'];
//     $cl_step_id = $_POST['cl_step_id'];
//     $note = $_POST['note'];
//     $physical_items = $_POST['physical_items'] ?? [];
//     $amount_items = $_POST['amount_items'] ?? [];
//     $by = $_SESSION['uid'];
//     $datetime = date("Y-m-d H:i:s");

//     if (!empty($amount_items)) {
//         foreach ($amount_items as $item) {
//             if (!isset($item['amount']) || $item['amount'] === '') {
//                 echo json_encode(["error" => "Amount cannot be empty."]);
//                 exit;
//             }
//         }
//     }


//     $response = ["message" => "Data saved successfully."];

//     // Process physical items
//     $existing_physical_ids = [];
//     if (!empty($physical_items)) {
        
//         foreach ($physical_items as $item) {
//             $item_id = $item['item_id'];
//             $quantity = !empty($item['quantity']) ? $item['quantity'] : 1;
//             $remark = $item['remark'];
//             $existing_physical_ids[] = $item_id;
//             $type = $item['type'];

//             $result = $conn->query("SELECT * FROM cl_request_step_physical_items WHERE cl_physical_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
//             if ($result->num_rows > 0) {
//                 $conn->query("UPDATE cl_request_step_physical_items SET quantity = '$quantity', remark = '$remark', last_updated_by = '$by', last_updated_date = '$datetime' WHERE cl_physical_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
//             } else {
//                 $conn->query("INSERT INTO cl_request_step_physical_items (cl_physical_item_id, request_id, step_id, item_type, quantity, remark, created_by, created_date) VALUES ('$item_id', '$cl_id', '$cl_step_id', '$type', '$quantity', '$remark', '$by', '$datetime')");
//             }
//         }

//         // Delete unmatched physical records
//         if (!empty($existing_physical_ids)) {
//             $conn->query("DELETE FROM cl_request_step_physical_items WHERE request_id = '$cl_id' AND step_id = '$cl_step_id' AND cl_physical_item_id NOT IN ('" . implode("','", $existing_physical_ids) . "')");
//         }
    
//     }
//     // Process amount items
//     $existing_amount_ids = [];
//     if (!empty($amount_items)) {
//         foreach ($amount_items as $item) {
//             if (!isset($item['amount']) || $item['amount'] === '') {
//                 echo json_encode(["error" => "Amount cannot be empty."]);
//                 exit;
//             }
            
//             $item_id = $item['item_id'];
//             $quantity = !empty($item['quantity']) ? $item['quantity'] : 1;
//             $amount = is_numeric($item['amount']) && $item['amount'] >= 0 ? $item['amount'] : 0.00;
//             $issued_date = $item['issued_date'];
//             $remark = $item['remark'];
//             $type = $item['type'];
//             $existing_amount_ids[] = $item_id;

//             $result = $conn->query("SELECT * FROM cl_request_step_amunt_items WHERE cl_amount_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
//             if ($result->num_rows > 0) {
//                 $conn->query("UPDATE cl_request_step_amunt_items SET quantity = '$quantity', amount = '$amount', issued_date = '$issued_date', remark = '$remark', last_updated_by = '$by', last_updated_date = '$datetime' WHERE cl_amount_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
//             } else {
//                 $conn->query("INSERT INTO cl_request_step_amunt_items (cl_amount_item_id, request_id, step_id, item_type, quantity, amount, issued_date, remark, created_by, created_date) VALUES ('$item_id', '$cl_id', '$cl_step_id', '$type', '$quantity', '$amount', '$issued_date', '$remark', '$by', '$datetime')");
//             }
//         }

//         // Delete unmatched amount records
//         if (!empty($existing_amount_ids)) {
//             $conn->query("DELETE FROM cl_request_step_amunt_items WHERE request_id = '$cl_id' AND step_id = '$cl_step_id' AND cl_amount_item_id NOT IN ('" . implode("','", $existing_amount_ids) . "')");
//         }
//     }

//     // update clearance step
//     $sql = "UPDATE cl_requests_steps SET prepare_check_approve = 1, prepared_by = ?, prepared_date = ?, last_updated_date = ?, last_updated_by = ? WHERE cl_step_id = ? AND prepare_check_approve = 0";
//     $stmt = $conn->prepare($sql);   
//     $stmt->bind_param("issii", $by, $datetime, $datetime, $by, $cl_step_id);
//     $stmt->execute();
//     $stmt->close();

//     //select next user to be attended
//     $query = "SELECT assigned_preparer_user_id, assigned_checker_user_id, assigned_approver_user_id, prepared_by, checked_by, approved_by 
//     FROM cl_requests_steps WHERE request_id = ? AND step !='0' AND is_complete !='1' ORDER BY step ASC LIMIT 1";

//     $stmt = $conn->prepare($query);
//     $stmt->bind_param("i", $cl_id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     $row = $result->fetch_assoc();
//     $next_user = null;

//     if (!empty($row['assigned_preparer_user_id']) && empty($row['prepared_by'])) {
//         $next_user = $row['assigned_preparer_user_id'];
//     } else if (!empty($row['assigned_checker_user_id']) && empty($row['checked_by'])) {
//         $next_user = $row['assigned_checker_user_id'];
//     } else if (!empty($row['assigned_approver_user_id']) && empty($row['approved_by'])) {
//         $next_user = $row['assigned_approver_user_id'];
//     }

//     if ($next_user) {
//         $user = "SELECT username FROM users WHERE user_id = ?";
//         $stmt = $conn->prepare($user);
//         $stmt->bind_param("i", $next_user);
//         $stmt->execute();
//         $result = $stmt->get_result();
//         $user = $result->fetch_assoc();
//         $next_user = $user['username'];
        
//         clearanceRequestStepNotice($cl_id,$next_user); // Send email notification
//     }

//     $_SESSION['success'] = "Successfully Saved.";
//     echo json_encode($response);
//     exit;
// }

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_step_id = clean_input($_POST['cl_step_id']);
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

        $sql = "UPDATE cl_requests_steps SET prepare_check_approve = 3, is_complete = 1, complete_date = ?, approved_by = ?, approved_date = ?, is_complete = 1, pending_note = ?, last_updated_by = ?, last_updated_date = ? WHERE cl_step_id = ? AND is_complete != 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissisi", $datetime, $by, $datetime, $approve_note, $by, $datetime, $cl_step_id);

        if ($stmt->execute()) {
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
            else {
                // $sql = "UPDATE cl_requests SET is_complete = 1, completed_date = ? WHERE cl_req_id = ? AND is_complete != 1";
                // $stmt = $conn->prepare($sql);
                // $stmt->bind_param("si", $datetime, $cl_id);
                // $stmt->execute();

                clearanceRequestCompleteNotice($cl_id);
            }

            $_SESSION['success'] = "Request marked as approved.";
            echo json_encode(["status" => "success", "message" => "Request marked as approved."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark as approved."]);
        }
        

}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['che'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_step_id = clean_input($_POST['cl_step_id']);
    $cl_id = clean_input($_POST['cl_id']);
    $approve_note = clean_input($_POST['note']);
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($cl_step_id)) $errors[] = "Clearance id required.";

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $sql = "UPDATE cl_requests_steps SET prepare_check_approve = 2, checked_by = ?, checked_date = ?, pending_note = ?, last_updated_by = ?, last_updated_date = ? 
        WHERE cl_step_id = ? AND is_complete != 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issisi", $by, $datetime, $approve_note, $by, $datetime, $cl_step_id);

        if ($stmt->execute()) {
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

            $_SESSION['success'] = "Request marked as checked.";
            echo json_encode(["status" => "success", "message" => "Request marked as checked."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark as checked."]);
        }
        

}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reject'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_step_id = clean_input($_POST['cl_step_id']);
    $cl_id = clean_input($_POST['cl_id']);
    $approve_note = clean_input($_POST['note']);
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($cl_step_id)) $errors[] = "Clearance id required.";

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $sql = "UPDATE cl_requests_steps 
        SET prepare_check_approve = 1, 
            checked_by = ?, 
            checked_date = ?, 
            pending_note = ?, 
            last_updated_by = ?, 
            last_updated_date = ? 
        WHERE cl_step_id = ? AND is_complete != 1";

        $stmt = $conn->prepare($sql);

        // Define variables for NULL values
        $checked_by = null;
        $checked_date = null;

        $stmt->bind_param("issisi", $checked_by, $checked_date, $approve_note, $by, $datetime, $cl_step_id);

        if ($stmt->execute()) {
            //select next user to be attended
            $query = "SELECT assigned_preparer_user_id, assigned_checker_user_id, assigned_approver_user_id
            FROM cl_requests_steps WHERE cl_step_id = ? AND step !='0' AND is_complete !='1'";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $cl_step_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $row = $result->fetch_assoc();
            $next_user = null;

            if (!empty($row['assigned_checker_user_id'])) {
                $next_user = $row['assigned_checker_user_id'];
            } else if (!empty($row['assigned_approver_user_id']) && empty($row['assigned_checker_user_id'])) {
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

            $_SESSION['success'] = "Request Completed.";
            echo json_encode(["status" => "success", "message" => "Request Completed."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed."]);
        }
        

}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['allocate'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_id = clean_input($_POST['cl_id']);
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($cl_id)) $errors[] = "Clearance id required.";

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $sql = "UPDATE cl_requests SET allocated_to_finance = 1, finace_allocated_date = ?, finance_allocated_by = ? WHERE cl_req_id = ? AND is_complete != 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $datetime, $by, $cl_id);

        if ($stmt->execute()) {

            finalClearanceNotice($cl_id); // Send email notification

            $_SESSION['success'] = "Allocated to finance.";
            echo json_encode(["status" => "success", "message" => "Allocated to finance."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed."]);
        }
        

}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['fcomplete'])) {

    function clean_input($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $cl_id = clean_input($_POST['cl_id']);
    $complete_date = clean_input($_POST['complete_date']);
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");
    
        $errors = [];
    
        if (empty($cl_id)) $errors[] = "Clearance id required.";

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "message" => $errors]);
            exit();
        }

        $sql = "UPDATE cl_requests SET is_complete = 1, completed_date = ?, completed_by = ? WHERE cl_req_id = ? AND is_complete != 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $complete_date, $by, $cl_id);

        if ($stmt->execute()) {

            finalClearanceCompleteNotice($cl_id); // Send email notification

            $_SESSION['success'] = "Clearance Completed.";
            echo json_encode(["status" => "success", "message" => "Clearance Completed."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed."]);
        }
        

}

?>

