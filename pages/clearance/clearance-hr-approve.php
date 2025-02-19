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

        $cl_id = null;
        if (isset($_GET['id'])) {
            if (!empty($_GET['id'])) {
                $cl_id = base64_decode($_GET['id']);

                $clearance = $conn->query("SELECT cl_requests.resignation_date,
                     cl_requests.is_complete, 
                     employees.name_with_initials, 
                     employees.code, 
                     employees.epf_no, 
                     employees.title, 
                     employees.nic,
                     employees.appointment_date,
                     cl_requests_steps.is_complete AS step_complete, 
                     cl_requests_steps.step,
                     cl_requests_steps.pending_note,
                     cl_requests_steps.created_date,
                     cl_requests_steps.max_dates,
                     branch_departments.bd_name,
                     designations.designation,
                     cl_requests.created_date as request_date,
                     users.name AS request_by,
                     uploads.location AS url,                     
                     (SELECT bd_name 
                        FROM branch_departments
                        INNER JOIN cl_requests_steps ON cl_requests_steps.bd_code = branch_departments.bd_code
                        WHERE (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2) 
                              AND cl_requests_steps.request_id = cl_requests.cl_req_id 
                        ORDER BY cl_requests_steps.step ASC 
                        LIMIT 1) AS department,
                    (SELECT complete_date 
                        FROM cl_requests_steps
                        WHERE cl_requests_steps.is_complete = 1 
                              AND cl_requests_steps.request_id = cl_requests.cl_req_id 
                        ORDER BY cl_requests_steps.step DESC 
                        LIMIT 1) AS last_completed_date
              FROM cl_requests 
              INNER JOIN employees ON cl_requests.emp_id = employees.emp_id 
              INNER JOIN cl_requests_steps ON cl_requests_steps.request_id = cl_requests.cl_req_id
              AND cl_requests_steps.step = (
                  SELECT MAX(step) FROM cl_requests_steps 
                  WHERE cl_requests_steps.request_id = cl_requests.cl_req_id
              )
              INNER JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
              INNER JOIN designations ON employees.designation_id = designations.desig_id 
              INNER JOIN users ON users.user_id = cl_requests.created_by
              LEFT JOIN uploads ON uploads.request_id = cl_requests.cl_req_id AND uploads.document_type = '1'
              WHERE cl_requests.cl_req_id = '$cl_id' AND cl_requests.status = 1");

                if ($clearance->num_rows != 1) {
                    header("Location: clearance-list.php");
                }   

                $clearance = $clearance->fetch_assoc();

                $referenceDate = !empty($clearance['last_completed_date']) ? $clearance['last_completed_date'] : $clearance['created_date'];
                $daysGap = (new DateTime($referenceDate))->diff(new DateTime())->days;

                $delay_status = '<span class="gap-2"><span class="status-dot green"></span> </span>';

                if ($clearance['step_complete'] == '2') {
                    $delay_status = '<span class="gap-2"><span class="status-dot yellow"></span> </span>';
                }

                if ($daysGap > $clearance['max_dates'] && $clearance['step_complete'] == '2') {
                    $delay_status = '<span class="gap-2"><span class="status-dot yellow"></span> <span class="status-dot red"></span> </span>';
                }

                if ($daysGap <= $clearance['max_dates'] && $clearance['step_complete'] == '2') {
                    $delay_status = '<span class="gap-2"><span class="status-dot yellow"></span> <span class="status-dot green"></span> </span>';
                }

                if ($daysGap > $clearance['max_dates'] && $clearance['step_complete'] != '2') {
                    $delay_status = '<span class="gap-2"><span class="status-dot red"></span> </span>';
                }


                // Progress Bar Calculation
                    $progressQuery = "SELECT 
                    ROUND((SUM(CASE WHEN is_complete = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*))) AS completion_percentage
                FROM cl_requests_steps
                WHERE request_id = ?";
                $stmtProgress = $conn->prepare($progressQuery);
                $stmtProgress->bind_param("i", $cl_id);
                $stmtProgress->execute();
                $stmtProgress->bind_result($completion_percentage);
                $stmtProgress->fetch();
                $stmtProgress->close();

                if ($clearance['is_complete'] == '0' && ($clearance['step_complete'] == '1' && $clearance['step'] == "0")) {
                    $completion_percentage = 10;
                }

                $prog = '<div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: ' . ($completion_percentage ?: 0) . '%" aria-valuenow="' . ($completion_percentage ?: 0) . '" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>';
                
            }
            else {
                header("Location: clearance-list.php");
            }
        }
        else {
            header("Location: clearance-list.php");
        }
        
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
                <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="card-title"><h4 id="title-name">Clearance Request</h4></p>
                        <hr id="title-hr">

                        <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                            <h4 class="card-title">Clearance Request ID #<?= $cl_id ?>  <?= $delay_status ?></h4>
                            <div class="media">
                                
                                <div class="media-body">
                                    <table width="100%" class="table table-bordered">
                                        <tr>
                                            <td><b>Employee Name : </b></td>
                                            <td><?= $clearance['title'].' '.$clearance['name_with_initials'] ?></td>
                                        
                                            <td><b>Employee Designation : </b></td>
                                            <td><?= $clearance['designation'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Employee No/Code : </b></td>
                                            <td><?= $clearance['epf_no'] ?> 
                                            <?php if (!empty($clearance['code'])) {
                                                echo ' / '.$clearance['code'];
                                            } ?></td>
                            
                                            <td><b>Employee NIC : </b></td>
                                            <td><?= $clearance['nic'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Employee Branch/Department : </b></td>
                                            <td><?= $clearance['bd_name'] ?></td>
                                        
                                            <td><b>Employee Appointment Date : </b></td>
                                            <td><?= $clearance['appointment_date'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Employee Resignation Date : </b></td>
                                            <td><?= $clearance['resignation_date'] ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><b>Requested Date : </b></td>
                                            <td><?= $clearance['request_date'] ?></td>
                                            <td><b>Requested By : </b></td>
                                            <td><?= $clearance['request_by'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Progress : </b></td>
                                            <td><?= $prog ?></td>
                                            <td><b>Current Department : </b></td>
                                            <td><?= $clearance['department'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Resignation Letter : </b></td>
                                            <td>
                                                <?php
                                                if (!empty($clearance['url'])) {
                                                    $url = $clearance['url'];
                                                    $fileExtension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

                                                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                                                        // Display Image with improved size
                                                        echo '<div id="previewContainer" style="max-width: 500px; max-height: 600px; overflow: hidden;">';
                                                        echo '<img src="' . htmlspecialchars($url) . '" style="width: 100%; height: auto; display: block; border: 1px solid #ddd; border-radius: 8px; padding: 5px;">';
                                                        echo '</div>';
                                                    } elseif ($fileExtension === 'pdf') {
                                                        // Display PDF Link
                                                        echo '<div id="previewContainer">';
                                                        echo '<a href="' . htmlspecialchars($url) . '" target="_blank" style="font-weight: bold; color: blue; text-decoration: underline;">View Resignation Letter (PDF)</a>';
                                                        echo '</div>';
                                                    } else {
                                                        // Unsupported Format
                                                        echo '<div id="previewContainer"><p>Unsupported file format.</p></div>';
                                                    }
                                                } else {
                                                    echo '<div id="previewContainer"><p>No file uploaded.</p></div>';
                                                }
                                                ?>
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>

                                    </table>
                                    
                                </div>

                            </div>
                            </div>
                        </div>
                        </div>
                <?php
                    if ($clearance['step'] == '0' && ($clearance['step_complete'] == '0' || $clearance['step_complete'] == '2') && ($_SESSION['ulvl']=='1' || $_SESSION['ulvl']=='2')) { ?>
                        <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                            <h4 class="card-title">Action</h4>
                            <div class="media">
                                
                                <div class="media-body">
                                <form action="../../back/clearance-manage.php" method="post" id="myform">
                                    <div class="form-group">
                                        <label for="note">Note</label>
                                        <textarea class="form-control" id="note" name="note" rows="3" placeholder="Please write note here.."></textarea>
                                    </div>

                                    <input type="hidden" name="cl_id" id="cl_id" value="<?= $cl_id ?>">
                                    
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <button type="button" name="approve" id="approve" class="btn btn-success btn-sm">Approve</button>
                                            <button type="button" name="pending" id="pending" class="btn btn-warning btn-sm">Pending</button>
                                        </div>
                                        <a href="clearance-list.php" class="btn btn-info btn-sm">Back</a>
                                    </div>

                                    
                                </form>
                                </div>

                            </div>
                            </div>
                        </div>
                        </div>
                <?php    } 
                else {
                    echo '<div class="d-flex justify-content-end"><a href="clearance-list.php" class="btn btn-info btn-sm float-right">Back</a></div>';
                }
                ?>
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
    //submit form
    $(document).ready(function () {
        $('#pending').click(function () {
            $("#pending").prop("disabled", true);

            let pending_note = document.getElementById('note').value;
            let cl_id = document.getElementById('cl_id').value;

            if (pending_note.trim() === '') {
                    $("#pending").prop("disabled", false);
                    const alertBox = document.getElementById('customAlert');
                    alertBox.textContent = 'Please enter reason for pending.';
                    alertBox.style.display = 'block';

                    // Hide the alert after 3 seconds
                    setTimeout(() => {
                        alertBox.style.display = 'none';
                    }, 3000);
                    return;
            }

            $.ajax({
                url: $('#myform').attr('action'),
                type: $('#myform').attr('method'),
                data: {pending_note: pending_note, cl_id: cl_id, pending: 'pending'},
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        
                         location.reload();
                    } else {
                        //alert("Error: " + response.message);
                        $("#pending").prop("disabled", false);
                        const alertBox = document.getElementById('customAlert');
                        alertBox.innerHTML  =  response.message.join('<br>');
                        alertBox.style.display = 'block';

                        // Hide the alert after 3 seconds
                        setTimeout(() => {
                            alertBox.style.display = 'none';
                        }, 3000);
                    }
                },
                error: function () {
                    //alert('An error occurred. Please try again.');
                    $("#pending").prop("disabled", false);
                    const alertBox = document.getElementById('customAlert');
                    alertBox.textContent = 'An error occurred. Please try again.';
                    alertBox.style.display = 'block';

                    // Hide the alert after 3 seconds
                    setTimeout(() => {
                        alertBox.style.display = 'none';
                    }, 3000);
                }
            });
        });

        $('#approve').click(function () {
            $("#approve").prop("disabled", true);
           let approve_note = document.getElementById('note').value;
           let cl_id = document.getElementById('cl_id').value;

           $.ajax({
               url: $('#myform').attr('action'),
               type: $('#myform').attr('method'),
               data: {approve_note: approve_note, cl_id: cl_id, approve: 'approve'},
               dataType: 'json',
               success: function (response) {
                   if (response.status === 'success') {
                       
                        location.reload();
                   } else {
                       //alert("Error: " + response.message);
                       $("#approve").prop("disabled", false);
                       const alertBox = document.getElementById('customAlert');
                       alertBox.innerHTML  =  response.message.join('<br>');
                       alertBox.style.display = 'block';

                       // Hide the alert after 3 seconds
                       setTimeout(() => {
                           alertBox.style.display = 'none';
                       }, 3000);
                   }
               },
               error: function () {
                   //alert('An error occurred. Please try again.');
                   $("#approve").prop("disabled", false);
                   const alertBox = document.getElementById('customAlert');
                   alertBox.textContent = 'An error occurred. Please try again.';
                   alertBox.style.display = 'block';

                   // Hide the alert after 3 seconds
                   setTimeout(() => {
                       alertBox.style.display = 'none';
                   }, 3000);
               }
           });
       });
        
    });
</script>

</body>

</html>