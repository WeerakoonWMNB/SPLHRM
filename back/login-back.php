<?php 
session_start();
require "connection/connection.php";
    
            if(isset($_POST['btn_login']))
            {
                extract($_POST);
                $unma = $_POST['username'];
                $pword = $_POST['password'];
                
                $sql = "SELECT * FROM users WHERE username = '$unma' AND password = '$pword' AND is_active=1 LIMIT 1";
                //echo $sql ;exit;
                $name = "";
                $uid = "";
                $ulvl = "";
                $bd_id = "";

                foreach ($conn->query($sql) as $row)
                {
                    $name = $row['name'];
                    $uid = $row['user_id'];
                    $ulvl = $row['user_level'];
                    $bd_id = $row['bd_id'];
                }
                
                if(!empty($uid))
                {
                    $_SESSION['name'] = $name; 
                    $_SESSION['uid'] = $uid;
                    $_SESSION['ulvl'] = $ulvl;
                    $_SESSION['bd_id'] = $bd_id;

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