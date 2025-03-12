<?php
if (!isset($_SESSION['uid'])) {
    //header("Location: ../../index.php");
    echo "<script>window.location.href = '../../index.php';</script>";
    exit;
}

function checkAccess($allowedLevels) {

    // Ensure the user level is set
    if (!isset($_SESSION['ulvl'])) {
        return false;
        // header("Location: index.php");
        // exit();
    }

    // Check if the user's level is in the allowed levels
    if (!in_array($_SESSION['ulvl'], $allowedLevels)) {
        return false;
        // header("Location: index.php");
        // exit();
    }
    else {
        return true;
    }
}
?>