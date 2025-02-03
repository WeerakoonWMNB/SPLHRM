<?php
session_start();
require "connection/connection.php";
//edit user
if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $edit_id = intval($_POST['edit_id']);
        $category_name = trim($_POST['category_name']);
        $edit_user = $_SESSION['uid'];
    
        if ($edit_id) {
            $sql = "UPDATE emp_category SET cat_name = ?";
            $params = [$category_name];
            $types = 's';
    
            $sql .= " WHERE cat_id  = ?";
            $params[] = $edit_id;
            $types .= 'i';
    
            $stmt = $conn->prepare($sql);
    
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
    
                if ($stmt->execute()) {
                    
                    $_SESSION['success'] = "Category updated successfully.";
                header("Location: ../pages/settings/type-list.php");
                exit();
                } else {
                    
                    $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/type-list.php");
                exit();
                }
    
                $stmt->close();
            } else {
                
                $_SESSION['error'] = "Error preparing statement: " . $conn->error;
                header("Location: ../pages/settings/type-list.php");
                exit();
            }
        } else {
            
            $_SESSION['error'] = "Invalid Category ID.";
                header("Location: ../pages/settings/type-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//add user
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $category_code = trim($_POST['category_code']);
        $category_name = trim($_POST['category_name']); 
        $add_user = $_SESSION['uid'];
    
        $sql = "INSERT INTO emp_category (cat_code , cat_name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('ss', $category_code, $category_name);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "Category added successfully.";
                header("Location: ../pages/settings/type-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/type-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/settings/type-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_type'])) {
    $cat_id = $_POST['cat_id'];
    $edit_user = $_SESSION['uid'];

    $user = "UPDATE emp_category SET status = 0 WHERE cat_id = '$cat_id'";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Category deleted successfully.";
        header("Location: ../pages/settings/type-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete Category.";
        header("Location: ../pages/settings/type-list.php");
        exit();
    }
}

//search user

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $id  = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT * FROM emp_category WHERE cat_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Designation not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}