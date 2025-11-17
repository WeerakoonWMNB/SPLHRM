<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<?php
    include "../../back/connection/connection.php";
?>

<head>
    <?php 
    require '../../partials/head.php'; 
    include "../../back/credential-check.php";
    ?>
</head>

<body>
    <div class="container-scroller d-flex">
        <?php require '../../partials/nav-bar.php'; ?>
        <div class="container-fluid page-body-wrapper">
            <?php require '../../partials/top-nav.php'; ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="card">
                        <h5 class="card-header" style="background-color:#e6f7ff;">Dashboard</h5>
                        <div class="card-body">

                        <div class="row">

                          <div class="col-md-3 grid-margin stretch-card">
                            <div class="card bg-twitter d-flex align-items-center">
                              <div class="card-body py-5">
                                <div
                                  class="d-flex flex-row align-items-center flex-wrap justify-content-md-center justify-content-xl-start py-1">
                                  <i class="mdi mdi-format-list-bulleted-type text-white icon-lg"></i>
                                  <div class="ms-3 ml-md-0 ml-xl-3">
                                    <?php
                                      $sql = "SELECT COUNT(*) as total FROM cl_requests WHERE status = '1'";
                                      $result = mysqli_query($conn, $sql);
                                      $row = mysqli_fetch_assoc($result);
                                      $total_requests = $row['total'];
                                    ?>
                                    <h5 class="text-white font-weight-bold">
                                      <?php echo $total_requests; ?> Clearances
                                    </h5>
                                    <p class="mt-2 text-white card-text">Total</p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="col-md-3 grid-margin stretch-card">
                            <div class="card bg-warning d-flex align-items-center">
                              <div class="card-body py-5">
                                <div
                                  class="d-flex flex-row align-items-center flex-wrap justify-content-md-center justify-content-xl-start py-1">
                                  <i class="mdi mdi-playlist-play text-white icon-lg"></i>
                                  <div class="ms-3 ml-md-0 ml-xl-3">
                                    <?php
                                      $sql = "SELECT COUNT(*) as total FROM cl_requests WHERE status = '1' AND is_complete = '0'";
                                      $result = mysqli_query($conn, $sql);
                                      $row = mysqli_fetch_assoc($result);
                                      $total_pending = $row['total'];
                                    ?>
                                    <h5 class="text-white font-weight-bold">
                                      <?php echo $total_pending; ?> Clearances
                                    </h5>
                                    <p class="mt-2 text-white card-text">Ongoing</p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="col-md-3 grid-margin stretch-card">
                            <div class="card" style="background-color:#800000; color:white;">
                              <div class="card-body py-5">
                                <div class="d-flex flex-row align-items-center flex-wrap justify-content-md-center justify-content-xl-start py-1">
                                  <i class="mdi mdi-format-indent-increase text-white icon-lg"></i>
                                  <div class="ms-3 ml-md-0 ml-xl-3">
                                    <?php
                                      $sql = "SELECT COUNT(*) as total FROM cl_requests 
                                        INNER JOIN cl_requests_steps ON cl_requests.cl_req_id = cl_requests_steps.request_id 
                                        WHERE cl_requests.status = '1' AND cl_requests_steps.is_complete = '2'";
                                      $result = mysqli_query($conn, $sql);
                                      $row = mysqli_fetch_assoc($result);
                                      $total_pending_approval = $row['total'];
                                    ?>
                                    <h5 class="text-white font-weight-bold">
                                      <?php echo $total_pending_approval; ?> Clearances
                                    </h5>
                                    <p class="mt-2 text-white card-text">Pending</p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>


                          <div class="col-md-3 grid-margin stretch-card">
                            <div class="card bg-success d-flex align-items-center">
                              <div class="card-body py-5">
                                <div
                                  class="d-flex flex-row align-items-center flex-wrap justify-content-md-center justify-content-xl-start py-1">
                                  <i class="mdi mdi-format-list-bulleted text-white icon-lg"></i>
                                  <div class="ms-3 ml-md-0 ml-xl-3">
                                    <?php
                                      $sql = "SELECT COUNT(*) as total FROM cl_requests WHERE status = '1' AND is_complete = '1'";
                                      $result = mysqli_query($conn, $sql);
                                      $row = mysqli_fetch_assoc($result);
                                      $total_completed = $row['total'];
                                    ?>
                                    <h5 class="text-white font-weight-bold">
                                      <?php echo $total_completed; ?> Clearances
                                    </h5>
                                    <p class="mt-2 text-white card-text">Completed</p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                        </div> <!-- End row -->

                            <div class="row">
                                <div class="col-12 col-xl-6 grid-margin stretch-card">
                                    <div class="row w-100 flex-grow">
                                        <div class="col-md-12 grid-margin stretch-card">
                                            <div class="card">
                                                <div class="card-body">
                                                    <p class="card-title">Last 6 Months Clearance Completed Metrics</p>
                                                    <canvas id="areaChart"></canvas>
                                                </div> <!-- End card-body -->
                                            </div> <!-- End card -->
                                        </div> <!-- End col-md-12 -->
                                    </div> <!-- End row w-100 -->
                                </div> <!-- End col-12 col-xl-6 -->


                                <div class="col-12 col-xl-6 grid-margin stretch-card">
                                    <div class="row w-100 flex-grow">
                                        <div class="col-md-12 grid-margin stretch-card">
                                            <div class="card">
                                                <div class="card-body">
                                                    <p class="card-title">Last 6 Months Clearance Metrics</p>
                                                    <canvas id="audience-chart"></canvas>
                                                </div> <!-- End card-body -->
                                            </div> <!-- End card -->
                                        </div> <!-- End col-md-12 -->
                                    </div> <!-- End row w-100 -->
                                </div> <!-- End col-12 col-xl-6 -->

                            </div> <!-- End row -->
                        </div> <!-- End card-body -->
                    </div> <!-- End card -->
                </div> <!-- End content-wrapper -->
            </div> <!-- End main-panel -->
        </div> <!-- End page-body-wrapper -->
    </div> <!-- End container-scroller -->
    
    <?php require '../../partials/scripts.php'; ?>
    <script>
       fetch('../../back/clearance_data.php')
      .then(response => response.json())
      .then(data => {
        const areaData = {
          labels: data.labels,
          datasets: [{
            label: 'Clearance Completed',
            data: data.values,
            backgroundColor: [
              'rgba(54, 162, 235, 0.2)',
              'rgba(255, 206, 86, 0.2)',
              'rgba(75, 192, 192, 0.2)',
              'rgba(153, 102, 255, 0.2)',
              'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
              'rgba(54, 162, 235, 1)',
              'rgba(255, 206, 86, 1)',
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)',
              'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1,
            fill: true,
          }]
        };

        var areaOptions = {
        plugins: {
          filler: {
            propagate: true
          }
        }
      };

        const ctx = $("#areaChart").get(0).getContext("2d");
        new Chart(ctx, {
          type: 'line', // or 'bar'
          data: areaData,
          options: {
            scales: {
              y: {
                beginAtZero: true,
                min: 0,  
              }
            }
          }
        });
      })
      .catch(error => console.error('Error fetching clearance data:', error));

    </script>

<script>
  fetch('../../back/clearance_counts.php')
  .then(response => response.json())
  .then(data => {
    const AudienceChartCanvas = document.getElementById("audience-chart").getContext("2d");
    const AudienceChart = new Chart(AudienceChartCanvas, {
      type: 'bar',
      data: {
        labels: data.labels,
        datasets: [
          {
            label: 'Clearances Created',
            data: data.created,
            backgroundColor: '#6640b2'
          },
          {
            label: 'Clearances Completed',
            data: data.completed,
            backgroundColor: '#1cbccd'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true, // ✅ Correct to avoid endless height
        layout: {
          padding: { left: 0, right: 0, top: 20, bottom: 0 }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              color: "#b1b0b0",
              font: { size: 10 }
            },
            grid: {
              color: "#f8f8f8"
            }
          },
          x: {
            ticks: {
              color: "#b1b0b0",
              font: { size: 10 }
            },
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: {
            display: true,
            labels: {
              color: "#333"
            }
          }
        }
      }
    });
  })
  .catch(error => console.error('Error fetching clearance counts:', error));

</script>
</body>

</html>
