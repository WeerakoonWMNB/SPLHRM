<?php

// $db = new PDO('mysql:host=localhost;dbname=clearance;charset=utf8', 'root', '');
// $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$conn = mysqli_connect("localhost", "root", "", "clearance");
$now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$datetime = $now->format('Y-m-d H:i:s');

if ($conn->connect_error) {

    die("Connection failed: " . $conn->connect_error);
  
  }else{
      
      $conn->query("SET SESSION sql_mode = ''");
      
  }
?>