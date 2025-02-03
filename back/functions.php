<?php
function insertEmployeeHistoryLog($emp_id, $change_type, $last_data) {
    require "connection/connection.php";
    $changed_by = $_SESSION['uid'];
    // SQL query to insert data into employee_history_log table
    $sql = "INSERT INTO employee_history_log (emp_id, change_type, last_data, change_date, changed_by)
            VALUES (?, ?, ?, ?, ?)";

    // Prepare statement
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind parameters to the statement
        mysqli_stmt_bind_param($stmt, "iissi", $emp_id, $change_type, $last_data, $datetime, $changed_by);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            return true;
        } else {
            return false;
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        return false;
    }

}


?>
