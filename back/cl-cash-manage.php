<?php
session_start();
require "connection/connection.php";
//edit item
if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $edit_id = isset($_POST['edit_id']) ? filter_var($_POST['edit_id'], FILTER_VALIDATE_INT) : null;
        $item_name = isset($_POST['item_name']) ? trim(filter_var($_POST['item_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
        $department = isset($_POST['department']) ? filter_var($_POST['department'], FILTER_VALIDATE_INT) : null;

        // Check if any value is empty or null
        if (empty($item_name) || is_null($department)) {
                $_SESSION['error'] = "Invalid inputs found.";
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
        }

    
        if ($edit_id) {
            $sql = "UPDATE cl_amount_items SET item_name = ?, bd_id = ?";
            $params = [$item_name, $department];
            $types = 'si';
    
    
            $sql .= " WHERE cl_amount_item_id = ?";
            $params[] = $edit_id;
            $types .= 'i';
    
            $stmt = $conn->prepare($sql);
    
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
    
                if ($stmt->execute()) {
                    
                    $_SESSION['success'] = "Item updated successfully.";
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
                } else {
                    
                    $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
                }
    
                $stmt->close();
            } else {
                
                $_SESSION['error'] = "Error preparing statement: " . $conn->error;
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
            }
        } else {
            
            $_SESSION['error'] = "Invalid Item ID.";
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//add item
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $item_name = isset($_POST['item_name']) ? trim(filter_var($_POST['item_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
        $department = isset($_POST['department']) ? filter_var($_POST['department'], FILTER_VALIDATE_INT) : null;

        // Check if any value is empty or null
        if (empty($item_name) || is_null($department)) {
                $_SESSION['error'] = "Invalid inputs found.";
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
        }
    
        $sql = "INSERT INTO cl_amount_items (item_name , bd_id) 
        VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('si', $item_name, $department);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "Item added successfully.";
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/settings/cl-cash-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_branch'])) {
    $bd_id = $_POST['cl_amount_item_id'];

    $user = "UPDATE cl_amount_items SET status = 0 WHERE cl_amount_item_id = '$bd_id'";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Item deleted successfully.";
        header("Location: ../pages/settings/cl-cash-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete item.";
        header("Location: ../pages/settings/cl-cash-list.php");
        exit();
    }
}

//search item

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $id = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT * FROM cl_amount_items WHERE cl_amount_item_id = ?");
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