<?php 
session_start();
require "connection/connection.php";
    
            if(isset($_POST['btn_login']))
            {
                extract($_POST);
                $unma = $_POST['username'];
                $pword = $_POST['password'];
                
                $sql = "SELECT users.*, branch_departments.company_id FROM users 
                INNER JOIN branch_departments ON branch_departments.bd_code = users.bd_id
                WHERE users.username = '$unma' AND users.password = '$pword' AND users.is_active=1 LIMIT 1";
                //echo $sql ;exit;
                $name = "";
                $uid = "";
                $ulvl = "";
                $bd_id = "";
                $u_roll = "";
                $u_company_id = "";

                foreach ($conn->query($sql) as $row)
                {
                    $name = $row['name'];
                    $uid = $row['user_id'];
                    $ulvl = $row['user_level'];
                    $bd_id = $row['bd_id'];
                    $u_roll = $row['process_level'];
                    $u_company_id = $row['company_id'];
                }
                
                if(!empty($uid))
                {
                    $_SESSION['name'] = $name; 
                    $_SESSION['uid'] = $uid;
                    $_SESSION['ulvl'] = $ulvl;
                    $_SESSION['bd_id'] = $bd_id;
                    $_SESSION['u_roll'] = $u_roll;
                    $_SESSION['company_id'] = $u_company_id;

                     header("Location: ../pages/general/dashboard.php");
                     exit();
                }
                else
                {
                    $_SESSION['error'] = "Your username or password is incorrect.";
                    header("Location: ../index.php");
                    exit();
                }
            }
    
    
    ?>