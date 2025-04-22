<?php session_start();
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
                      <p class="card-title"><h4 id="title-name">Clearance Individual Report</h4></p>
                      <hr id="title-hr">

                      <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="searchEmployee">Select Department</label>
                            <select class="form-control mt-3" id="department" name="department" style="border: 1px solid #ccc; border-radius: 4px; padding: 10px;">
                                <option value="">All Departments</option>
                                <?php
                                    $query = "SELECT * FROM branch_departments";
                                    if (!empty($dept) && ($user_level != '1' && $user_level != '2')) {
                                        $dept = mysqli_real_escape_string($conn, $dept);
                                        $dept = str_replace(",", "','", $dept); // Convert to a comma-separated string for SQL IN clause
                                        $query .= " WHERE bd_code IN ('$dept')";
                                    }
                                    $query .= " ORDER BY is_branch ASC, bd_name ASC";
                                        
                                    $result = mysqli_query($conn, $query);
                                    $i = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $selected = ($i == 1) ? 'selected' : '';
                                        echo "<option value='".$row['bd_code']."' ".$selected.">".$row['bd_name']."</option>";
                                        $i++;
                                    }
                                ?>
                            </select>
                        </div>
                      </div>
                      
                      <table id="employeeTable" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Clearance ID </th>
                                    <th>Name</th>
                                    <th>Resignation Date</th>
                                    <th>EPF No</th>
                                    <th>Code</th>
                                    <th>Selected Dept</th>
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
    var table = new DataTable('#employeeTable', {
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "order": [], // Disable default sorting
        "ajax": {
            "url": "../../back/clearance-individual-fetch.php",
            "type": "POST",
            "data": function (d) {
                // Send department value to server
                d.department = $('#department').val();
            }
        },
        "columns": [
            { "data": "row_id" },
            { "data": "req_id" },
            { "data": "ini_name" },
            { "data": "resignation_date" },
            { "data": "epf_no" },
            { "data": "code" },
            { "data": "department_name" },
            { "data": "action" }
        ]
    });

    // Trigger reload on department change
    $('#department').on('change', function () {
        table.ajax.reload();
    });
    
});


    
</script>

</body>

</html>