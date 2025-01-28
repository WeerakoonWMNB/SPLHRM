<?php
session_start();
require "connection/connection.php";
//edit user
if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $edit_id = intval($_POST['edit_id']);
        $company_name = trim($_POST['company_name']);
        $edit_user = $_SESSION['uid'];
    
        if ($edit_id) {
            $sql = "UPDATE companies SET comany_name = ?";
            $params = [$company_name];
            $types = 's';
    
            $sql .= " WHERE company_id  = ?";
            $params[] = $edit_id;
            $types .= 'i';
    
            $stmt = $conn->prepare($sql);
    
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
    
                if ($stmt->execute()) {
                    
                    $_SESSION['success'] = "Company updated successfully.";
                header("Location: ../pages/settings/company-list.php");
                exit();
                } else {
                    
                    $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/company-list.php");
                exit();
                }
    
                $stmt->close();
            } else {
                
                $_SESSION['error'] = "Error preparing statement: " . $conn->error;
                header("Location: ../pages/settings/company-list.php");
                exit();
            }
        } else {
            
            $_SESSION['error'] = "Invalid Company ID.";
                header("Location: ../pages/settings/company-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//add user
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $company_code = trim($_POST['company_code']);
        $company_name = trim($_POST['company_name']); 
        $add_user = $_SESSION['uid'];
    
        $sql = "INSERT INTO companies (company_code, comany_name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('ss', $company_code, $company_name);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "Company added successfully.";
                header("Location: ../pages/settings/company-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/company-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/settings/company-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_company'])) {
    $company_id = $_POST['company_id'];
    $edit_user = $_SESSION['uid'];

    $user = "UPDATE companies SET status = 0 WHERE company_id = '$company_id'";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Company deleted successfully.";
        header("Location: ../pages/settings/company-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete Company.";
        header("Location: ../pages/settings/company-list.php");
        exit();
    }
}

//search user

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $company_id  = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT company_id , company_code, comany_name FROM companies WHERE company_id  = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $companies = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $companies]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Company not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}