<?php
session_start();
require "connection/connection.php";
require "functions.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['query'])) {
    // Get search term
    $search = $_POST['query'];

    // Fetch bank branch matching search term
    $sql = "SELECT code, bank_name, branch_name, bank_code, branch_code FROM bank_branch 
    WHERE bank_name LIKE '%$search%' OR 
    branch_name LIKE '%$search%' OR 
    bank_code LIKE '%$search%' OR 
    branch_code LIKE '%$search%' ORDER BY branch_name ASC LIMIT 10";
    $result = $conn->query($sql);

        $output = ""; 

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<a href='#' class='list-group-item list-group-item-action bank-option' data-id='".$row['code']."'>".$row['bank_name']." (".$row['bank_code'].") ".$row['branch_name']." (".$row['branch_code'].")</a>";
            }
        } else {
            $output = "<a href='#' class='list-group-item list-group-item-action disabled'>No results found</a>";
        }

        echo $output;
}

?>