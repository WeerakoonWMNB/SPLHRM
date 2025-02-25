<?php
session_start();
require "connection/connection.php";

$query = "SELECT * FROM branch_departments WHERE status = 1 GROUP BY bd_id, is_branch ORDER BY is_branch ASC, bd_name ASC";
$result = $conn->query($query);

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

echo json_encode($departments);
?>
