<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<?php
        include "../../back/credential-check.php";
        if (!checkAccess([1,2,3])) {
            header("Location: ../general/dashboard.php");
            exit();
        }
        include "../../back/connection/connection.php";
?>

<head>
  <?php require '../../partials/head.php'; ?>
  <style>
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            margin-top:5px;
        }
        .green { background-color: #28a745; }  /* Active */
        .yellow { background-color: #ffc107; } /* Idle */
        .red { background-color: #dc3545; }   /* Busy */
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

            <div class="col-12 col-xl-12 grid-margin stretch-card">
              <div class="row w-100 flex-grow">
                <div class="col-md-12 grid-margin stretch-card">
                  <div class="card">
                    <div class="card-body">
                      <p class="card-title"><h4 id="title-name">Allocated Clearance List</h4></p>
                      <hr id="title-hr">
                      
                      <table id="employeeTable" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Clearance ID </th>
                                    <th>Name</th>
                                    <!-- <th>Code</th> -->
                                    <th>EMP ID</th>
                                    <th>Resignation Date</th>
                                    <th>Notes</th>
                                    <th>Current Dept</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                                    
                    </div>
                  </div>
                </div>  
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  </div>


<div id="customAlert" class="custom-alert"></div>
<div id="customAlertSuccess" class="custom-alert-success"></div>
  <?php require '../../partials/scripts.php'; ?>
  <script>

$(document).ready(function () {
    // Fetch employees
    new DataTable('#employeeTable', {
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "order": [], // Disable default sorting
        "ajax": {
            "url": "../../back/clearance-allocated-fetch.php",
            "type": "POST"
        },
        "columns": [
            { "data": "row_id" },
            { "data": "req_id" },
            { "data": "ini_name" },
            // { "data": "code" },
            { "data": "system_emp_no" },
            { "data": "resignation_date" },
            { "data": "notes" },
            { "data": "department" },
            { "data": "progress" },
            { "data": "action" }
        ]
    });
});

</script>
</body>

</html>