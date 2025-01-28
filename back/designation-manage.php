<?php
session_start();
require "connection/connection.php";
//edit user
if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $edit_id = intval($_POST['edit_id']);
        $designation_name = trim($_POST['designation_name']);
        $edit_user = $_SESSION['uid'];
    
        if ($edit_id) {
            $sql = "UPDATE designations SET designation = ?";
            $params = [$designation_name];
            $types = 's';
    
            $sql .= " WHERE desig_id  = ?";
            $params[] = $edit_id;
            $types .= 'i';
    
            $stmt = $conn->prepare($sql);
    
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
    
                if ($stmt->execute()) {
                    
                    $_SESSION['success'] = "Designation updated successfully.";
                header("Location: ../pages/settings/designation-list.php");
                exit();
                } else {
                    
                    $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/designation-list.php");
                exit();
                }
    
                $stmt->close();
            } else {
                
                $_SESSION['error'] = "Error preparing statement: " . $conn->error;
                header("Location: ../pages/settings/designation-list.php");
                exit();
            }
        } else {
            
            $_SESSION['error'] = "Invalid Designation ID.";
                header("Location: ../pages/settings/designation-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//add user
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $designation_code = trim($_POST['designation_code']);
        $designation_name = trim($_POST['designation_name']); 
        $add_user = $_SESSION['uid'];
    
        $sql = "INSERT INTO designations (desig_code , designation) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('ss', $designation_code, $designation_name);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "Designation added successfully.";
                header("Location: ../pages/settings/designation-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/designation-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/settings/designation-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_designation'])) {
    $desig_id = $_POST['desig_id'];
    $edit_user = $_SESSION['uid'];

    $user = "UPDATE designations SET status = 0 WHERE desig_id = '$desig_id'";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Designation deleted successfully.";
        header("Location: ../pages/settings/designation-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete Designation.";
        header("Location: ../pages/settings/designation-list.php");
        exit();
    }
}

//search user

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $designation_id  = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT desig_id , desig_code , designation FROM designations WHERE desig_id = ?");
    $stmt->bind_param("i", $designation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $designations = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $designations]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Designation not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}