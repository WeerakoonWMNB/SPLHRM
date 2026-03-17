<?php
session_start();
require "connection/connection.php";

//add user
if (isset($_POST['edit_id']) && empty($_POST['edit_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $add_user = trim($_POST['user_id']);
        //$add_user = $_SESSION['uid'];
    
        $sql = "INSERT INTO resign_mail_receivers_list (user_id) VALUES (?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {
            $stmt->bind_param('i', $add_user);
    
            if ($stmt->execute()) {
                
                $_SESSION['success'] = "User added successfully.";
                header("Location: ../pages/settings/mail-user-list.php");
                exit();
            } else {
                
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("Location: ../pages/settings/mail-user-list.php");
                exit();
            }
    
            $stmt->close();
        } else {
            
            $_SESSION['error'] = "Error preparing statement: " . $db->error;
                header("Location: ../pages/settings/mail-user-list.php");
                exit();
        }
    
        $conn->close();
    }
}

//delete user
if (isset($_POST['delete_rl'])) {
    $edit_user = $_POST['rl_id'];

    $user = "DELETE FROM resign_mail_receivers_list WHERE id = $edit_user ";
    $stmt = $conn->prepare($user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User deleted successfully.";
        header("Location: ../pages/settings/mail-user-list.php");
        exit();
    }
    else {
        $_SESSION['error'] = "Failed to delete User.";
        header("Location: ../pages/settings/mail-user-list.php");
        exit();
    }
}
