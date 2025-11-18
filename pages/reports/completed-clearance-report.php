<?php 
session_start();
$dept = $_SESSION['bd_id'];
$user_level = $_SESSION['ulvl'];
?>

<!DOCTYPE html>
<html lang="en">

<?php
    include "../../back/credential-check.php";
    if (!checkAccess([1,2,3])) {
        echo "<script>window.location.href = '../general/dashboard.php';</script>";
        exit;
    }
    include "../../back/connection/connection.php";
?>

<head>
    <?php require '../../partials/head.php'; ?>
    <style>
        #completedTable {
            width: 100% !important;
        }
    </style>
</head>

<body>
<div class="container-scroller d-flex">

    <?php require '../../partials/nav-bar.php'; ?>

    <div class="container-fluid page-body-wrapper">

        <?php require '../../partials/top-nav.php'; ?>

        <div class="main-panel">
            <div class="content-wrapper">

                <div class="row">
                    <div class="col-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">

                                <h4 class="card-title">Completed Clearance Report</h4>
                                <hr>

                                <!-- Filters -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label>Select Department</label>
                                        <select class="form-control mt-2" id="department" name="department">
                                            <?php
                                                $query = "SELECT * FROM branch_departments";
                                                
                                                if (!empty($dept) && ($user_level != '1' && $user_level != '2')) {
                                                    $dept = mysqli_real_escape_string($conn, $dept);
                                                    $dept = str_replace(",", "','", $dept);
                                                    $query .= " WHERE bd_code IN ('$dept')";
                                                }

                                                $query .= " ORDER BY is_branch ASC, bd_name ASC";
                                                $result = mysqli_query($conn, $query);

                                                if ($user_level == '1' || $user_level == '2') {
                                                     echo "<option value=''>All</option>";
                                                }

                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    $selected = '';
                                                    if ($user_level != '1' && $user_level != '2') {
                                                        if ($row['bd_code'] == $dept) {
                                                            $selected = 'selected';
                                                        }
                                                    }
                                                    echo "<option value='".$row['bd_id']."' $selected>".$row['bd_name']."</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mt-4">
                                        <button class="btn btn-success mt-3" id="downloadPDF">
                                            <i class="mdi mdi-file-pdf"></i> Download PDF
                                        </button>
                                    </div>
                                </div>

                                <!-- Table -->
                                <table id="completedTable" class="display nowrap table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Clearance ID</th>
                                            <th>Name</th>
                                            <th>Branch/Dept</th>
                                            <th>EPF No</th>
                                            <th>Sales Code</th>
                                            <th>Resignation Date</th>
                                            <th>Completed Date</th>
                                            <th>Check Number</th>
                                            <th>Actions</th>
                                            
                                        </tr>
                                    </thead>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- content-wrapper -->
        </div> <!-- main-panel -->

    </div>
</div>

<?php require '../../partials/scripts.php'; ?>

<script>
$(document).ready(function () {

    var table = new DataTable('#completedTable', {
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "ajax": {
            "url": "../../back/clearance-completed-fetch.php",
            "type": "POST",
            "data": function (d) {
                d.department = $('#department').val();
            }
        },
        "columns": [
            { "data": "row_id" },
            { "data": "cl_req_id" },
            { "data": "ini_name" },
            { "data": "emp_dept" },
            { "data": "epf_no" },
            { "data": "code" },
            { "data": "resignation_date" },
            { "data": "completed_date" },
            { "data": "check_number" },
            { "data": "action" },
            
        ]
    });

    $('#department').on('change', function () {
        table.ajax.reload();
    });

    $("#downloadPDF").click(function () {
        let dept = $("#department").val();
        window.open("../../back/clearance-completed-pdf.php?dept=" + dept, "_blank");
    });

});
</script>

</body>
</html>
