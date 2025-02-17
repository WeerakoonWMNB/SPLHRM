<?php
session_start();
require "connection/connection.php";
//edit user
if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $edit_id = intval($_POST['edit_id']);
        $name = trim($_POST['name']);
        $user_level = intval($_POST['user_level']);
        $process_level = intval($_POST['process_level']); // 1=hod, 2=rsm, 3=bsm, 4=cro, 5=executive
        $branch = isset($_POST['branch']) ? implode(',', $_POST['branch']) : '';
        $emp_id = intval($_POST['emp']);
        $username = trim($_POST['username']);
        $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
        $edit_user = $_SESSION['uid'];
    
        if ($edit_id) {
            $sql = "UPDATE users SET name = ?, user_level = ?, process_level = ?, bd_id = ?, emp_id = ?, username = ?, updated_by = ? , updated_date = ?";
            $params = [$name, $user_level, $process_level, $branch, $emp_id, $username, $edit_user, $datetime];
            $types = 'siisisis';
    
            // Only update password if it's provided
            if ($password) {
                $sql .= ", password = ?";
                $params[] = $password;
                $types .= 's';
            }
    
            $sql .= " WHERE user_id  = ?";
            $params[] = $edit_id;
            $types .= 'i';
    
            $stmt = $conn->prepare($sql);
    
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
    
                if ($stmt->execute()) {
                    
                    $_SESSION['success'] = "User updated successfully.";
                header("Location: ../pages/users/user-list.php");
                exit();
                } else {
                    
                    $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/users/user-list.php");
                exit();
                }
    
                $stmt->close();
            } else {
                
                $_SESSION['error'] = "Error preparing statement: " . $conn->error;
                header("Location: ../pages/users/user-list.php");
                exit();
            }
        } else {
            
            $_SESSION['error'] = "Invalid user ID.";
                header("Location: ../pages/users/user-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//add user
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $user_level = intval($_POST['user_level']);
        $process_level = intval($_POST['process_level']); // 1=hod, 2=rsm, 3=bsm, 4=cro, 5=executive
        $branch = isset($_POST['branch']) ? implode(',', $_POST['branch']) : '';
        $emp_id = intval($_POST['emp']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']); 
        $add_user = $_SESSION['uid'];
    
        $sql = "INSERT INTO users (name, user_level, username, password, process_level, bd_id, emp_id, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('sissisiis', $name, $user_level, $username, $password, $process_level, $branch, $emp_id , $add_user, $datetime);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "User added successfully.";
                header("Location: ../pages/users/user-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/users/user-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/users/user-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $edit_user = $_SESSION['uid'];

    $user = "UPDATE users SET is_active = 0, updated_by = $edit_user , updated_date = '$datetime'  WHERE user_id = '$user_id'";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User deleted successfully.";
        header("Location: ../pages/users/user-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete user.";
        header("Location: ../pages/users/user-list.php");
        exit();
    }
}

//search user

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $user_id = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT users.user_id, users.name, users.user_level, users.username, users.password, r.name_with_initials as employee_name, users.bd_id, users.emp_id, users.process_level FROM users 
    LEFT JOIN employees r ON users.emp_id = r.emp_id WHERE users.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}