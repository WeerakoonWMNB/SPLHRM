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

//check date diff with time difference
// function getWeekdaysDiff($startDate, $endDate) {
//     $start = new DateTime($startDate);
//     $end = new DateTime($endDate);
//     $end->modify('+1 day'); // Include end date

//     $interval = new DateInterval('P1D');
//     $dateRange = new DatePeriod($start, $interval, $end);

//     $weekdays = 0;

//     foreach ($dateRange as $date) {
//         $dayOfWeek = $date->format('N'); // 6 = Saturday, 7 = Sunday
//         if ($dayOfWeek < 6) {
//             $weekdays++;
//         }
//     }

//     return $weekdays;
// }

// Function to calculate the number of weekdays between two dates
// This function ignores time and only considers the date part
function getWeekdaysDiff($startDate, $endDate) {
    // Keep only the date part (ignore time)
    $start = new DateTime(date('Y-m-d', strtotime($startDate)));
    $end = new DateTime(date('Y-m-d', strtotime($endDate)));
    $end->modify('+1 day'); // Include end date

    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($start, $interval, $end);

    $weekdays = 0;

    foreach ($dateRange as $date) {
        $dayOfWeek = $date->format('N'); // 6 = Saturday, 7 = Sunday
        if ($dayOfWeek < 6) {
            $weekdays++;
        }
    }

    return $weekdays;
}


?>
