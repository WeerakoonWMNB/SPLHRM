<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<style>
    /* Hide the third column when printing */
  @media print {
    .no-print-col {
      display: none;
    }

    /* Also hide matching <td>s in rows */
    td.no-print-col {
      display: none;
    }
  }
</style>
<?php
        include "../../back/credential-check.php";
        if (!checkAccess([1,2,3])) {
            echo "<script>window.location.href = '../general/dashboard.php';</script>";
                    exit;
        }
        include "../../back/connection/connection.php";

        $cl_id = null;
        if (isset($_GET['id'])) {
            if (!empty($_GET['id'])) {
                $cl_id = base64_decode($_GET['id']);

                $clearance = $conn->query("SELECT cl_requests.resignation_date,
                     cl_requests.is_complete, 
                     cl_requests.allocated_to_finance,
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
                     letter.location AS url,
                     bb.bank_name,
                     bb.bank_code,
                     bb.branch_name,
                     bb.branch_code,
                     employees.account_number,
                    (SELECT cl_step_id 
                        FROM cl_requests_steps
                        WHERE cl_requests_steps.is_complete != 1 
                              AND cl_requests_steps.request_id = cl_requests.cl_req_id 
                        ORDER BY cl_requests_steps.step DESC 
                        LIMIT 1) AS not_completed_steps
              FROM cl_requests 
              INNER JOIN employees ON cl_requests.emp_id = employees.emp_id 
              INNER JOIN cl_requests_steps ON cl_requests_steps.request_id = cl_requests.cl_req_id
              AND cl_requests_steps.step = (
                  SELECT MAX(step) FROM cl_requests_steps 
                  WHERE cl_requests_steps.request_id = cl_requests.cl_req_id
                  AND 
                  (
                    (cl_requests_steps.step != 0 AND (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2))
                    OR 
                    (cl_requests_steps.step = 0)
                    
                  )
              )
              INNER JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
              INNER JOIN designations ON employees.designation_id = designations.desig_id 
              INNER JOIN users ON users.user_id = cl_requests.created_by
              LEFT JOIN uploads letter ON letter.request_id = cl_requests.cl_req_id AND letter.document_type = '1'
              LEFT JOIN bank_branch bb ON bb.bank_code = employees.bank_code AND bb.branch_code = employees.branch_code
              WHERE cl_requests.cl_req_id = '$cl_id' AND cl_requests.status = 1 AND (cl_requests.is_complete = 0 OR cl_requests.is_complete = 1 OR cl_requests.is_complete = 2)");

                if ($clearance->num_rows != 1) {
                    //header("Location: clearance-list.php");
                    echo "<script>window.location.href = 'clearance-list.php';</script>";
                    exit;
                }   

                $clearance = $clearance->fetch_assoc();

            }
            else {
                //header("Location: clearance-list.php");
                echo "<script>window.location.href = 'clearance-list.php';</script>";
                    exit;
            }
        }
        else {
            //header("Location: clearance-list.php");
            echo "<script>window.location.href = 'clearance-list.php';</script>";
                    exit;
        }
?>

<head>
  <?php require '../../partials/head.php'; ?>
  <style>
    table ,tr, th, td {
        border : 1px solid #ccc;
        padding : 5px;
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
                <div class="col-12">
                <div class="card" >
                    <div class="card-body">
                        <p class="card-title"><h4 id="title-name">Clearance Request Summary</h4></p>
                        <hr id="title-hr">
                        <?php
                            if (isset($_GET['cl'])) {
                                echo '<a href="clearance-list.php" class="btn btn-secondary btn-sm mb-2">Back</a>';
                            }
                            if (isset($_GET['cf'])) {
                                echo '<a href="clearance-final.php" class="btn btn-secondary btn-sm mb-2">Back</a>';
                            }
                            if (isset($_GET['ca'])) {
                                echo '<a href="clearance-allocated.php" class="btn btn-secondary btn-sm mb-2">Back</a>';
                            }
                        ?>
                        
                        <button type="button" class="btn btn-info btn-sm mb-2" onclick="printDiv('printArea')">Print</button>
                        <?php
                            if ($clearance['is_complete'] != 1 && $clearance['allocated_to_finance'] == 0 && empty($clearance['not_completed_steps']) ) {
                                echo '<button type="button" id="allocate_finance" class="btn btn-success btn-sm mb-2">Allocate to Finance</button>';
                            }
                        ?>
                        <?php
                            if ($clearance['is_complete'] != 1 && $clearance['allocated_to_finance'] == 1) {
                                echo '<div class="d-flex">
                                            <div class="form-group me-3">
                                                <label><b>Payment Date :</b></label>
                                                <input type="date" class="form-control mb-1" style="width:250px; border:1px solid #ccc;" id="complete_date">
                                            </div>
                                            <div class="form-group">
                                                <label><b>Check Number :</b></label>
                                                <input type="text" class="form-control mb-1" style="width:350px; border:1px solid #ccc;" id="check_number">
                                            </div>
                                        </div>';


                                echo '<button type="button" id="finance_complete" class="btn btn-success btn-sm mb-2">Mark as Complited</button>';
                            }
                        ?>
                        <div class="col-md-12 grid-margin stretch-card" id="printArea">
                        <div class="card">
                            <div class="card-body">
                                <center>
                                    <h4 class="card-title">ASSETS & LIABILITIES CLEARANCE FORM</h4>
                                    <p>SADAHARITHA GROUP OF COMPANIES</p>
                                </center>

                            <div class="media">
                                
                                <div class="media-body">
                                    <table width="100%">
                                        <tr>
                                            <td><b>Employee Name : </b></td>
                                            <td><?= $clearance['title'].' '.$clearance['name_with_initials'] ?></td>
                                        
                                            <td><b>Employee Designation : </b></td>
                                            <td><?= $clearance['designation'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Employee EPF No/Code : </b></td>
                                            <td>
                                                <?= $clearance['epf_no'] ?>
                                                <?php 
                                                    if ($clearance['code']) {
                                                        echo '/ '.$clearance['code'];
                                                    }
                                                ?>
                                            </td>
                            
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
                                            <td><b>Clearance Request ID :</b></td>
                                            <td><?= $cl_id ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Requested Date : </b></td>
                                            <td><?= $clearance['request_date'] ?></td>
                                            <td><b>Requested By : </b></td>
                                            <td><?= $clearance['request_by'] ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Bank Name : </b></td>
                                            <td><?= $clearance['bank_name'] ?> <?php if($clearance['bank_code']) { echo ' - ('. $clearance['bank_code'] .')'; } ?> </td>
                                            <td><b>Branch Name : </b></td>
                                            <td><?= $clearance['branch_name'] ?> <?php if($clearance['branch_code']) { echo ' - ('. $clearance['branch_code'] .')'; } ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Acc. No : </b></td>
                                            <td><?= $clearance['account_number'] ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </table>
                                    
                                    <table width="100%" style="margin-top:15px;">
                                        <tr>
                                            <th>#</th>
                                            <th>Department</th>
                                            <th>Deductions (Rs.)</th>
                                            <th>Payable (Rs.)</th>
                                            <th>Remark</th>
                                            <th class="no-print-col">Details</th>
                                        </tr>
                                        <?php
                                        $query = "SELECT cl_requests_steps.*, branch_departments.bd_name,
                                        (SELECT SUM(amount) as deduction FROM cl_request_step_amunt_items WHERE item_type='1' AND step_id=cl_requests_steps.cl_step_id AND request_id= $cl_id) as deduction,
                                        (SELECT SUM(amount) as payable FROM cl_request_step_amunt_items WHERE item_type='2' AND step_id=cl_requests_steps.cl_step_id AND request_id= $cl_id) as payable  
                                        FROM cl_requests_steps 
                                        INNER JOIN branch_departments ON branch_departments.bd_code = cl_requests_steps.bd_code
                                        WHERE cl_requests_steps.request_id = $cl_id AND cl_requests_steps.step!=0 AND cl_requests_steps.is_complete=1
                                        ORDER BY cl_requests_steps.step ASC";
                                        $clearance = $conn->query($query);

                                        $deduction_sum = 0;
                                        $payable_sum = 0;

                                        if ($clearance->num_rows > 0) {
                                            $i=1;
                                            while ($item = $clearance->fetch_assoc()) {
                                                $deduction_sum += $item['deduction'] ? $item['deduction'] : 0;
                                                $payable_sum += $item['payable'] ? $item['payable'] : 0;
                                                $dept = $item['bd_code'];
                                                echo "<tr>
                                                <td>$i</td>
                                                <td>".$item['bd_name']."</td>
                                                <td>". number_format($item['deduction'] ? $item['deduction'] : 0, 2) ."</td>
                                                <td>". number_format($item['payable'] ? $item['payable'] : 0, 2)."</td>
                                                <td>".$item['pending_note']."</td>
                                                <td class='no-print-col'>
                                                <a target='_blank' href='individual-detail-summary.php?id=".base64_encode($cl_id)."&dept=".base64_encode($dept)."' class='btn btn-info btn-sm' data-bs-toggle='tooltip' title='View'>
                                                    <i class='mdi mdi-eye'></i>
                                                </a>
                                                </td>
                                                </tr>";
                                                $i++;
                                            }
                                        }  
                                        ?>
                                        <tr>
                                            <th colspan="2">Totals (Rs.)</th>
                                            <th ><?= number_format($deduction_sum ? $deduction_sum : 0, 2) ?></th>
                                            <th><?= number_format($payable_sum ? $payable_sum : 0, 2) ?></th>
                                            <th></th>
                                        </tr>
                                        
                                    </table>   

                                    <table width="100%" style="margin-top:15px;">
                                        <tr>
                                            <th>Amounts Difference (Rs.)</th>
                                            <th ><?= number_format($payable_sum ? $payable_sum : 0, 2) ?> - <?= number_format($deduction_sum ? $deduction_sum : 0, 2) ?></th>
                                            <th><u><?= number_format((($payable_sum ? $payable_sum : 0) - ($deduction_sum ? $deduction_sum : 0)), 2) ?></u></th>
                                            
                                        </tr>
                                        <tr>
                                            <th colspan="2">
                                                <?php
                                                    if ((($payable_sum ? $payable_sum : 0) - ($deduction_sum ? $deduction_sum : 0)) >= 0) {
                                                        echo 'Total Payable (Rs.)';
                                                    }
                                                    else {
                                                        echo 'Total Deduction (Rs.)';
                                                    }
                                                ?>
                                            </th>
                                            <th><u style="border-bottom: 1px solid #000;"><?= number_format(abs((($payable_sum ? $payable_sum : 0) - ($deduction_sum ? $deduction_sum : 0))), 2) ?></u></th>
                                            
                                        </tr>
                                    </table>

                                    <table width="100%" style="margin-top:15px;">
                                        <tr>
                                            <th width="25%">Checked by (Name) :</th>
                                            <th width="25%"></th>
                                        
                                            <th width="25%">Approved by (Name) :</th>
                                            <th width="25%"></th>
                                        </tr>
                                        <tr>
                                            <th width="25%">Designation :</th>
                                            <th width="25%"></th>
                                        
                                            <th width="25%">Designation :</th>
                                            <th width="25%"></th>
                                        </tr>
                                        <tr>
                                            <th width="25%">Signature :</th>
                                            <th ></th>
                                        
                                            <th width="25%">Signature :</th>
                                            <th width="25%"></th>
                                        </tr>
                                    </table>
                                    <p>Document date: <?= $datetime; ?></p>
                                </div>

                            </div>
                            </div>
                        </div>
                        </div>
                        <input type="hidden" id="cl_id" value="<?= $cl_id ?>">
                        
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
        function printDiv(divId) {
            var content = document.getElementById(divId).innerHTML;
            var originalContent = document.body.innerHTML;
            
            document.body.innerHTML = content;
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload(); // Reload to restore the original page
        }

        $('#allocate_finance').click(function () {
        $("#allocate_finance").prop("disabled", true);
        let cl_id = document.getElementById('cl_id').value;

            $.ajax({
                url: "../../back/clearance-allocated-manage.php",
                type: "POST",
                data: {allocate: 'allocate', cl_id: cl_id},
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        
                        location.reload();
                    } else {
                        //alert("Error: " + response.message);
                        $("#allocate_finance").prop("disabled", false);
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
                    $("#allocate_finance").prop("disabled", false);
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

        $('#finance_complete').click(function () {
        $("#finance_complete").prop("disabled", true);
        let cl_id = document.getElementById('cl_id').value;
        let complete_date = document.getElementById('complete_date').value;
        let check_number = document.getElementById('check_number').value;

        if (complete_date && check_number) {
            $.ajax({
                url: "../../back/clearance-allocated-manage.php",
                type: "POST",
                data: {fcomplete: 'fcomplete', cl_id: cl_id, complete_date: complete_date, check_number: check_number},
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        window.location.href = 'clearance-final.php';
                        //location.reload();
                    } else {
                        //alert("Error: " + response.message);
                        $("#finance_complete").prop("disabled", false);
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
                    $("#finance_complete").prop("disabled", false);
                    const alertBox = document.getElementById('customAlert');
                    alertBox.textContent = 'An error occurred. Please try again.';
                    alertBox.style.display = 'block';

                    // Hide the alert after 3 seconds
                    setTimeout(() => {
                        alertBox.style.display = 'none';
                    }, 3000);
                }
            });
        }
        else {
                    $("#finance_complete").prop("disabled", false);
                    const alertBox = document.getElementById('customAlert');
                    alertBox.textContent = 'Pament date and check number required.';
                    alertBox.style.display = 'block';

                    // Hide the alert after 3 seconds
                    setTimeout(() => {
                        alertBox.style.display = 'none';
                    }, 3000);
        }

            
            
        });
    </script>
</body>

</html>