<?php
session_start();
require "connection/connection.php";
//edit user
if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $edit_id = intval($_POST['edit_id']);
        $company_code = trim($_POST['company']);
        $is_branch = intval($_POST['is_branch']);
        $cluster = trim($_POST['cluster']) == '' ? NULL : trim($_POST['cluster']);
        $branch_code = trim($_POST['branch_code']);
        $branch_name = trim($_POST['branch_name']);
        $max_dates_office = intval($_POST['max_dates_office']);
        $max_dates_marketing = intval($_POST['max_dates_marketing']);

        $company_id = 0;
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT company_id FROM companies WHERE company_code = ?");
        $stmt->bind_param("s", $company_code); // "s" indicates string type
        $stmt->execute();
        $stmt->bind_result($company_id);
        $stmt->fetch();
        $stmt->close();

    
        if ($edit_id) {
            $sql = "UPDATE branch_departments SET bd_code = ?, bd_name = ?, is_branch = ?, company_id = ? , company_code = ?, seiling_dates_for_backoffice = ?, ceiling_dates_for_marketing = ?, cluster = ? ";
            $params = [$branch_code, $branch_name, $is_branch, $company_id, $company_code, $max_dates_office, $max_dates_marketing, $cluster];
            $types = 'ssiisiii';
    
    
            $sql .= " WHERE bd_id = ?";
            $params[] = $edit_id;
            $types .= 'i';
    
            $stmt = $conn->prepare($sql);
    
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
    
                if ($stmt->execute()) {
                    
                    $_SESSION['success'] = "Branch/Department updated successfully.";
                header("Location: ../pages/settings/department-list.php");
                exit();
                } else {
                    
                    $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/department-list.php");
                exit();
                }
    
                $stmt->close();
            } else {
                
                $_SESSION['error'] = "Error preparing statement: " . $conn->error;
                header("Location: ../pages/settings/department-list.php");
                exit();
            }
        } else {
            
            $_SESSION['error'] = "Invalid branch/department ID.";
                header("Location: ../pages/settings/department-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//add user
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $company_code = trim($_POST['company']);
        $is_branch = intval($_POST['is_branch']);
        $branch_code = trim($_POST['branch_code']);
        $branch_name = trim($_POST['branch_name']);
        $max_dates_office = intval($_POST['max_dates_office']);
        $max_dates_marketing = intval($_POST['max_dates_marketing']);
        $cluster = trim($_POST['cluster']) == '' ? NULL : trim($_POST['cluster']);

        $company_id = 0;
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT company_id FROM companies WHERE company_code = ?");
        $stmt->bind_param("s", $company_code); // "s" indicates string type
        $stmt->execute();
        $stmt->bind_result($company_id);
        $stmt->fetch();
        $stmt->close();
    
        $sql = "INSERT INTO branch_departments (bd_code , bd_name, is_branch, company_id, company_code, seiling_dates_for_backoffice, ceiling_dates_for_marketing, cluster) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('ssiisiii', $branch_code, $branch_name, $is_branch, $company_id, $company_code, $max_dates_office, $max_dates_marketing, $cluster);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "Branch/Department added successfully.";
                header("Location: ../pages/settings/department-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/department-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/settings/department-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_branch'])) {
    $bd_id = $_POST['bd_id'];

    $user = "UPDATE branch_departments SET status = 0 WHERE bd_id = '$bd_id'";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Branch/Department deleted successfully.";
        header("Location: ../pages/settings/department-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete Branch/Department.";
        header("Location: ../pages/settings/department-list.php");
        exit();
    }
}

//search user

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $user_id = intval($_POST['search_id']);

    $stmt = $conn->prepare("SELECT * FROM branch_departments WHERE bd_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Branch/Department not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}