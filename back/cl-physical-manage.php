<?php
session_start();
require "connection/connection.php";
//edit item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    $edit_id = filter_var($_POST['edit_id'], FILTER_VALIDATE_INT);
    $item_name = isset($_POST['item_name']) ? htmlspecialchars(trim($_POST['item_name'])) : '';
    $item_type = isset($_POST['item_type']) ? filter_var($_POST['item_type'], FILTER_VALIDATE_INT) : 0;
    $department = filter_var($_POST['department'], FILTER_VALIDATE_INT);

    // Check if inputs are valid
    if (empty($item_name) || !$department) {
        $_SESSION['error'] = "Invalid inputs found.";
        header("Location: ../pages/settings/cl-physical-list.php");
        exit();
    }

    if ($edit_id) {
        $sql = "UPDATE cl_physical_items SET item_name = ?, bd_id = ?, item_type = ? WHERE cl_physical_item_id = ?";
        $params = [$item_name, $department, $item_type , $edit_id];
        $types = 'siii';

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Item updated successfully.";
            } else {
                $_SESSION['error'] = "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Invalid Item ID.";
    }

    header("Location: ../pages/settings/cl-physical-list.php");
    exit();
}


//add item
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $item_name = isset($_POST['item_name']) ? trim(filter_var($_POST['item_name'], FILTER_SANITIZE_STRING)) : '';
        $department = isset($_POST['department']) ? filter_var($_POST['department'], FILTER_VALIDATE_INT) : null;
        $item_type = isset($_POST['item_type']) ? filter_var($_POST['item_type'], FILTER_VALIDATE_INT) : 0;

        // Check if any value is empty or null
        if (empty($item_name) || is_null($department) || empty($item_type)) {
                $_SESSION['error'] = "Invalid inputs found.";
                header("Location: ../pages/settings/cl-physical-list.php");
                exit();
        }
    
        $sql = "INSERT INTO cl_physical_items (item_name , bd_id, item_type) 
        VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('sii', $item_name, $department, $item_type);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "Item added successfully.";
                header("Location: ../pages/settings/cl-physical-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/cl-physical-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/settings/cl-physical-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_branch'])) {
    $bd_id = $_POST['cl_physical_item_id'];

    $user = "UPDATE cl_physical_items SET status = 0 WHERE cl_physical_item_id = '$bd_id'";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Item deleted successfully.";
        header("Location: ../pages/settings/cl-physical-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete item.";
        header("Location: ../pages/settings/cl-physical-list.php");
        exit();
    }
}

//search item

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $id = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT * FROM cl_physical_items WHERE cl_physical_item_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}