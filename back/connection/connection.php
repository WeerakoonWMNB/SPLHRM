<?php

// $db = new PDO('mysql:host=localhost;dbname=clearance;charset=utf8', 'root', '');
// $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$conn = mysqli_connect("localhost", "root", "", "clearance");
$now = new DateTime(null, new DateTimeZone('Asia/Colombo'));
$datetime = $now->format('Y-m-d H:i:s');
?>