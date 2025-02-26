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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit'])) {
    $cl_id = $_POST['cl_id'];
    $cl_step_id = $_POST['cl_step_id'];
    $note = $_POST['note'];
    $physical_items = $_POST['physical_items'] ?? [];
    $amount_items = $_POST['amount_items'] ?? [];
    $by = $_SESSION['uid'];
    $datetime = date("Y-m-d H:i:s");

    if (!empty($amount_items)) {
        foreach ($amount_items as $item) {
            if (!isset($item['amount']) || $item['amount'] === '') {
                echo json_encode(["error" => "Amount cannot be empty."]);
                exit;
            }
        }
    }


    $response = ["message" => "Data saved successfully."];

    // Process physical items
    $existing_physical_ids = [];
    if (!empty($physical_items)) {
        
        foreach ($physical_items as $item) {
            $item_id = $item['item_id'];
            $quantity = !empty($item['quantity']) ? $item['quantity'] : 1;
            $remark = $item['remark'];
            $existing_physical_ids[] = $item_id;
            $type = $item['type'];

            $result = $conn->query("SELECT * FROM cl_request_step_physical_items WHERE cl_physical_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
            if ($result->num_rows > 0) {
                $conn->query("UPDATE cl_request_step_physical_items SET quantity = '$quantity', remark = '$remark', last_updated_by = '$by', last_updated_date = '$datetime' WHERE cl_physical_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
            } else {
                $conn->query("INSERT INTO cl_request_step_physical_items (cl_physical_item_id, request_id, step_id, item_type, quantity, remark, created_by, created_date) VALUES ('$item_id', '$cl_id', '$cl_step_id', '$type', '$quantity', '$remark', '$by', '$datetime')");
            }
        }

        // Delete unmatched physical records
        if (!empty($existing_physical_ids)) {
            $conn->query("DELETE FROM cl_request_step_physical_items WHERE request_id = '$cl_id' AND step_id = '$cl_step_id' AND cl_physical_item_id NOT IN ('" . implode("','", $existing_physical_ids) . "')");
        }
    
    }
    // Process amount items
    $existing_amount_ids = [];
    if (!empty($amount_items)) {
        foreach ($amount_items as $item) {
            if (!isset($item['amount']) || $item['amount'] === '') {
                echo json_encode(["error" => "Amount cannot be empty."]);
                exit;
            }
            
            $item_id = $item['item_id'];
            $quantity = !empty($item['quantity']) ? $item['quantity'] : 1;
            $amount = is_numeric($item['amount']) && $item['amount'] >= 0 ? $item['amount'] : 0.00;
            $issued_date = $item['issued_date'];
            $remark = $item['remark'];
            $type = $item['type'];
            $existing_amount_ids[] = $item_id;

            $result = $conn->query("SELECT * FROM cl_request_step_amunt_items WHERE cl_amount_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
            if ($result->num_rows > 0) {
                $conn->query("UPDATE cl_request_step_amunt_items SET quantity = '$quantity', amount = '$amount', issued_date = '$issued_date', remark = '$remark', last_updated_by = '$by', last_updated_date = '$datetime' WHERE cl_amount_item_id = '$item_id' AND request_id = '$cl_id' AND step_id = '$cl_step_id'");
            } else {
                $conn->query("INSERT INTO cl_request_step_amunt_items (cl_amount_item_id, request_id, step_id, item_type, quantity, amount, issued_date, remark, created_by, created_date) VALUES ('$item_id', '$cl_id', '$cl_step_id', '$type', '$quantity', '$amount', '$issued_date', '$remark', '$by', '$datetime')");
            }
        }

        // Delete unmatched amount records
        if (!empty($existing_amount_ids)) {
            $conn->query("DELETE FROM cl_request_step_amunt_items WHERE request_id = '$cl_id' AND step_id = '$cl_step_id' AND cl_amount_item_id NOT IN ('" . implode("','", $existing_amount_ids) . "')");
        }
    }

    echo json_encode($response);
    exit;
}


?>
