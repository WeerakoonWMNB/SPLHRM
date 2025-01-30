<?php
session_start();
require "connection/connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['company_code'])) {
    $company_code = intval($_POST['company_code']);
    
    $sql = "SELECT bd_code, bd_name FROM branch_departments WHERE company_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $company_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    
    echo json_encode($branches);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['query'])) {
    // Get search term
    $search = $_POST['query'];

    // Fetch employees matching search term
    $sql = "SELECT emp_id , titile, name_with_initials, nic, epf_no, code FROM employees 
    WHERE name_with_initials LIKE '%$search%' OR 
    nic LIKE '%$search%' OR 
    epf_no LIKE '%$search%' OR 
    code LIKE '%$search%' OR 
    name_in_full LIKE '%$search%' LIMIT 10";
    $result = $conn->query($sql);

    // $options = "<option value=''>Select</option>"; // Default option

    // if ($result->num_rows > 0) {
    //     while ($row = $result->fetch_assoc()) {
    //         $options .= "<option value='".$row['emp_id']."'>".$row['titile']." ".$row['name_with_initials']." (".$row['nic']." ".$row['code']." ".$row['epf_no'].")</option>";
    //     }
    // }

    // echo $options;


        $output = ""; 

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<a href='#' class='list-group-item list-group-item-action employee-option' data-id='".$row['emp_id']."'>".$row['titile']." ".$row['name_with_initials']." (".$row['nic']." ".$row['code']." ".$row['epf_no'].")</a>";
            }
        } else {
            $output = "<a href='#' class='list-group-item list-group-item-action disabled'>No results found</a>";
        }

        echo $output;
}

$conn->close();

?>