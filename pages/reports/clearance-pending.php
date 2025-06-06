<?php session_start();
$dept = $_SESSION['bd_id'];
$user_level = $_SESSION['ulvl'];
?>
<!DOCTYPE html>
<html lang="en">

<?php
        include "../../back/credential-check.php";
        if (!checkAccess([1,2])) {
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
                      <p class="card-title"><h4 id="title-name">Clearance Pending Report</h4></p>
                      <hr id="title-hr">

                    <div class="row">
                        <div class="col-md-3 grid-margin">
                                <div class="card bg-info d-flex align-items-center">
                                <div class="card-body py-5">
                                    <div
                                    class="d-flex flex-row align-items-center flex-wrap justify-content-md-center justify-content-xl-start py-1">
                                    <i class="mdi mdi-format-list-bulleted-type text-white icon-lg"></i>
                                    <div class="ms-3 ml-md-0 ml-xl-3">
                                        <?php
                                        $sql = "SELECT * FROM cl_requests cr 
                                                INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
                                                INNER JOIN step_pending sp ON crs.cl_step_id = sp.cl_step_id 
                                                WHERE cr.status = '1' GROUP BY crs.cl_step_id";

                                        $result = mysqli_query($conn, $sql);
                                        $total_requests = mysqli_num_rows($result);
                                        ?>
                                        <h5 class="text-white font-weight-bold">
                                        <?php echo $total_requests; ?> All Time Pending
                                        </h5>
                                        <p class="mt-2 text-white card-text">Requests</p>
                                    </div>
                                    </div>
                                </div>
                                </div>
                        </div>

                        <div class="col-md-3 grid-margin">
                                <div class="card bg-warning d-flex align-items-center">
                                <div class="card-body py-5">
                                    <div
                                    class="d-flex flex-row align-items-center flex-wrap justify-content-md-center justify-content-xl-start py-1">
                                    <i class="mdi mdi-format-list-bulleted-type text-white icon-lg"></i>
                                    <div class="ms-3 ml-md-0 ml-xl-3">
                                        <?php
                                        $sql = "SELECT * FROM cl_requests cr 
                                                INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
                                                INNER JOIN step_pending sp ON crs.cl_step_id = sp.cl_step_id 
                                                WHERE cr.status = '1' AND sp.is_pending_completed = 0 GROUP BY crs.cl_step_id";
                                        $result = mysqli_query($conn, $sql);
                                        $total_requests = mysqli_num_rows($result);
                                        ?>
                                        <h5 class="text-white font-weight-bold">
                                        <?php echo $total_requests; ?> Current Pending
                                        </h5>
                                        <p class="mt-2 text-white card-text">Requests</p>
                                    </div>
                                    </div>
                                </div>
                                </div>
                        </div>
                    </div>

                    <div class="row">
                        

                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-title">Departments Clearance Pending Metrics</p>
                                    <small>Number of clearance</small>
                                    <canvas id="dcpm-chart"></canvas>
                                </div> <!-- End card-body -->
                            </div> <!-- End card -->
                        </div>

                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-title">Branches Clearance Pending Metrics</p>
                                    <small>Number of clearance</small>
                                    <canvas id="bcpm-chart"></canvas>
                                </div> <!-- End card-body -->
                            </div> <!-- End card -->
                        </div>
                    </div>

                    <div class="row">
                        

                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-title">Departments Clearance Pending Metrics</p>
                                    <small>Average Number of Dates</small>
                                    <canvas id="dcpmd-chart"></canvas>
                                </div> <!-- End card-body -->
                            </div> <!-- End card -->
                        </div>

                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-title">Branches Clearance Pending Metrics</p>
                                    <small>Average Number of Dates</small>
                                    <canvas id="bcpmd-chart"></canvas>
                                </div> <!-- End card-body -->
                            </div> <!-- End card -->
                        </div>
                    </div>

                      <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="searchEmployee">Select Department</label>
                            <select class="form-control mt-3" id="department" name="department" style="border: 1px solid #ccc; border-radius: 4px; padding: 10px;">
                            
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
                                        if ($i==1) {
                                          echo "<option value='' ".$selected.">All</option>";
                                        } 
                                        echo "<option value='".$row['bd_code']."'>".$row['bd_name']."</option>";
                                        
                                        
                                        $i++;
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="searchEmployee">Date from</label>
                            <input type="date" class="form-control mt-3" id="fromdate" name="fromdate" style="border: 1px solid #ccc; border-radius: 4px; padding: 10px;" required>

                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="searchEmployee">Date to</label>
                            <input type="date" class="form-control mt-3" id="todate" name="todate" style="border: 1px solid #ccc; border-radius: 4px; padding: 10px;" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="searchEmployee">PDF Report</label>
                            <button class="btn btn-primary mt-3" id="generateReport" style="width: 100%;">Download</button>
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
                                    <th>Note</th>
                                    <th>Selected Dept</th>
                                    <th>Pending start</th>
                                    <th>Pending cleared</th>
                                    <th>Pending Dates</th>
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
            "url": "../../back/clearance-pending-fetch.php",
            "type": "POST",
            "data": function (d) {
                // Send department value to server
                d.department = $('#department').val();
                d.fromdate = $('#fromdate').val();
                d.todate = $('#todate').val();
            }
        },
        "columns": [
            { "data": "row_id" },
            { "data": "req_id" },
            { "data": "ini_name" },
            { "data": "resignation_date" },
            { "data": "epf_no" },
            { "data": "code" },
            { "data": "pending_note" },
            { "data": "selected_branch" },
            { "data": "pending_created_date" },
            { "data": "last_pending_date" },
            { "data": "days_taken" }
        ]
    });

    // Trigger reload on department change
    $('#department').on('change', function () {
        table.ajax.reload();
    });

    $('#fromdate').on('change', function () {
        table.ajax.reload();
    });

    $('#todate').on('change', function () {
        table.ajax.reload();
    });
    
});


    
</script>
<script>
  $(function () {
    // Fetch and display the department pending counts
    $.ajax({
      url: '../../back/clearance-pending-department-fetch.php', // Adjust path as needed
      method: 'GET',
      success: function(response) {
        console.log("Response:", response);

        // Parse if response is JSON string
        if (typeof response === 'string') {
          try {
            response = JSON.parse(response);
          } catch (e) {
            console.error("Invalid JSON response:", e);
            return;
          }
        }

        if (!response.labels || !response.data || !Array.isArray(response.labels) || !Array.isArray(response.data)) {
          console.error("Unexpected response format");
          return;
        }

        // Generate as many colors as needed
        function generateColors(n) {
          const bgColors = [], borderColors = [];
          for (let i = 0; i < n; i++) {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            bgColors.push(`rgba(${r}, ${g}, ${b}, 0.5)`);
            borderColors.push(`rgba(${r}, ${g}, ${b}, 1)`);
          }
          return { bgColors, borderColors };
        }

        const { bgColors, borderColors } = generateColors(response.labels.length);

        const doughnutPieData = {
          labels: response.labels,
          datasets: [{
            data: response.data,
            backgroundColor: bgColors,
            borderColor: borderColors,
            borderWidth: 1
          }]
        };

        const doughnutPieOptions = {
          responsive: true,
          animation: {
            animateScale: true,
            animateRotate: true
          }
        };

        if ($("#dcpm-chart").length) {
          const doughnutChartCanvas = $("#dcpm-chart").get(0).getContext("2d");
          new Chart(doughnutChartCanvas, {
            type: 'doughnut',
            data: doughnutPieData,
            options: doughnutPieOptions
          });
        }
      },
      error: function(error) {
        console.error("Failed to load pending counts:", error);
      }
    });


    // Fetch and display the branch pending counts
    $.ajax({
      url: '../../back/clearance-pending-branch-fetch.php', // Adjust path as needed
      method: 'GET',
      success: function(response) {
        console.log("Response:", response);

        // Parse if response is JSON string
        if (typeof response === 'string') {
          try {
            response = JSON.parse(response);
          } catch (e) {
            console.error("Invalid JSON response:", e);
            return;
          }
        }

        if (!response.labels || !response.data || !Array.isArray(response.labels) || !Array.isArray(response.data)) {
          console.error("Unexpected response format");
          return;
        }

        // Generate as many colors as needed
        function generateColors(n) {
          const bgColors = [], borderColors = [];
          for (let i = 0; i < n; i++) {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            bgColors.push(`rgba(${r}, ${g}, ${b}, 0.5)`);
            borderColors.push(`rgba(${r}, ${g}, ${b}, 1)`);
          }
          return { bgColors, borderColors };
        }

        const { bgColors, borderColors } = generateColors(response.labels.length);

        const doughnutPieData = {
          labels: response.labels,
          datasets: [{
            data: response.data,
            backgroundColor: bgColors,
            borderColor: borderColors,
            borderWidth: 1
          }]
        };

        const doughnutPieOptions = {
          responsive: true,
          animation: {
            animateScale: true,
            animateRotate: true
          }
        };

        if ($("#bcpm-chart").length) {
          const doughnutChartCanvas = $("#bcpm-chart").get(0).getContext("2d");
          new Chart(doughnutChartCanvas, {
            type: 'doughnut',
            data: doughnutPieData,
            options: doughnutPieOptions
          });
        }
      },
      error: function(error) {
        console.error("Failed to load pending counts:", error);
      }
    });

    // Fetch and display the deparment pending dates counts
    $.ajax({
      url: '../../back/clearance-pending-dept-date-fetch.php', // Adjust path as needed
      method: 'GET',
      success: function(response) {
        console.log("Response:", response);

        // Parse if response is JSON string
        if (typeof response === 'string') {
          try {
            response = JSON.parse(response);
          } catch (e) {
            console.error("Invalid JSON response:", e);
            return;
          }
        }

        if (!response.labels || !response.data || !Array.isArray(response.labels) || !Array.isArray(response.data)) {
          console.error("Unexpected response format");
          return;
        }

        // Generate as many colors as needed
        function generateColors(n) {
          const bgColors = [], borderColors = [];
          for (let i = 0; i < n; i++) {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            bgColors.push(`rgba(${r}, ${g}, ${b}, 0.5)`);
            borderColors.push(`rgba(${r}, ${g}, ${b}, 1)`);
          }
          return { bgColors, borderColors };
        }

        const { bgColors, borderColors } = generateColors(response.labels.length);

        const doughnutPieData = {
          labels: response.labels,
          datasets: [{
            data: response.data,
            backgroundColor: bgColors,
            borderColor: borderColors,
            borderWidth: 1
          }]
        };

        const doughnutPieOptions = {
          responsive: true,
          animation: {
            animateScale: true,
            animateRotate: true
          }
        };

        if ($("#dcpmd-chart").length) {
          const doughnutChartCanvas = $("#dcpmd-chart").get(0).getContext("2d");
          new Chart(doughnutChartCanvas, {
            type: 'doughnut',
            data: doughnutPieData,
            options: doughnutPieOptions
          });
        }
      },
      error: function(error) {
        console.error("Failed to load pending counts:", error);
      }
    });

    // Fetch and display the branch pending dates counts
    $.ajax({
      url: '../../back/clearance-pending-branch-date-fetch.php', // Adjust path as needed
      method: 'GET',
      success: function(response) {
        console.log("Response:", response);

        // Parse if response is JSON string
        if (typeof response === 'string') {
          try {
            response = JSON.parse(response);
          } catch (e) {
            console.error("Invalid JSON response:", e);
            return;
          }
        }

        if (!response.labels || !response.data || !Array.isArray(response.labels) || !Array.isArray(response.data)) {
          console.error("Unexpected response format");
          return;
        }

        // Generate as many colors as needed
        function generateColors(n) {
          const bgColors = [], borderColors = [];
          for (let i = 0; i < n; i++) {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            bgColors.push(`rgba(${r}, ${g}, ${b}, 0.5)`);
            borderColors.push(`rgba(${r}, ${g}, ${b}, 1)`);
          }
          return { bgColors, borderColors };
        }

        const { bgColors, borderColors } = generateColors(response.labels.length);

        const doughnutPieData = {
          labels: response.labels,
          datasets: [{
            data: response.data,
            backgroundColor: bgColors,
            borderColor: borderColors,
            borderWidth: 1
          }]
        };

        const doughnutPieOptions = {
          responsive: true,
          animation: {
            animateScale: true,
            animateRotate: true
          }
        };

        if ($("#bcpmd-chart").length) {
          const doughnutChartCanvas = $("#bcpmd-chart").get(0).getContext("2d");
          new Chart(doughnutChartCanvas, {
            type: 'doughnut',
            data: doughnutPieData,
            options: doughnutPieOptions
          });
        }
      },
      error: function(error) {
        console.error("Failed to load pending counts:", error);
      }
    });
  });
</script>

<script>
$('#generateReport').on('click', function (e) {
    e.preventDefault();

    var department = $('#department').val();
    var fromdate = $('#fromdate').val();
    var todate = $('#todate').val();

    // Redirect to PHP script with GET parameters (so it triggers PDF download)
    window.location.href = '../../back/download-pending-report.php?department=' + encodeURIComponent(department) + '&fromdate=' + encodeURIComponent(fromdate) + '&todate=' + encodeURIComponent(todate);
});
</script>

</body>

</html>