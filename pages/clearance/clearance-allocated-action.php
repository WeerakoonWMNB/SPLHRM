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
        $prepare_check_approve = null;
        $cl_step_id = null;
        $user_level = $_SESSION['ulvl'];

        if (isset($_GET['id'])) {
            if (!empty($_GET['id'])) {
                $cl_id = base64_decode($_GET['id']);
                $user_id = $_SESSION['uid'];

                $query = "SELECT cl_requests.resignation_date,
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
                     cl_requests_steps.bd_code,
                     cl_requests_steps.cl_step_id,
                     cl_requests_steps.assigned_preparer_user_id,
                     cl_requests_steps.assigned_checker_user_id,
                     cl_requests_steps.assigned_approver_user_id,
                     cl_requests_steps.prepared_by,
                     cl_requests_steps.checked_by,
                     cl_requests_steps.approved_by,
                     cl_requests_steps.prepare_check_approve,
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
                  SELECT MIN(step) FROM cl_requests_steps 
                  WHERE cl_requests_steps.request_id = cl_requests.cl_req_id
                  AND (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2)
                  AND cl_requests_steps.step != '0'
              )
              INNER JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
              INNER JOIN designations ON employees.designation_id = designations.desig_id 
              INNER JOIN users ON users.user_id = cl_requests.created_by
              LEFT JOIN uploads ON uploads.request_id = cl_requests.cl_req_id AND uploads.document_type = '1'
              WHERE cl_requests.cl_req_id = '$cl_id' AND cl_requests.status = 1 AND cl_requests.is_complete = 0";

              if ($user_level != 1 && $user_level != 2) {
                $query .= " AND
                    (
                        (cl_requests_steps.assigned_preparer_user_id != 0 AND cl_requests_steps.prepared_by IS NULL AND cl_requests_steps.assigned_preparer_user_id = $user_id) 
                        OR
                        (cl_requests_steps.assigned_checker_user_id != 0 AND cl_requests_steps.checked_by IS NULL AND cl_requests_steps.assigned_checker_user_id = $user_id)
                        OR
                        (cl_requests_steps.assigned_approver_user_id != 0 AND cl_requests_steps.approved_by IS NULL AND cl_requests_steps.assigned_approver_user_id = $user_id)
                    )";
                }

                $clearance = $conn->query($query);

                if ($clearance->num_rows != 1) {
                    header("Location: clearance-allocated.php");
                }   

                $clearance = $clearance->fetch_assoc();
                $prepare_check_approve = $clearance['prepare_check_approve'];

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
                header("Location: clearance-allocated.php");
            }
        }
        else {
            header("Location: clearance-allocated.php");
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
                            <input type="hidden" id="cl_id_for_fetch" value="<?= $cl_id ?>">
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

            <div class="row">
                <div class="container mt-4">
                <form action="" method="post">
                    <?php
                    $d_code = $clearance['bd_code'];
                    $b_or_d = $conn->query("SELECT is_branch FROM branch_departments WHERE bd_code = '$d_code'")->fetch_assoc();
                    $cl_step_id = $clearance['cl_step_id'];

                    function getItems($conn, $d_code, $item_type) {
                        global $b_or_d;
                        if ($b_or_d['is_branch'] == 1) {
                            return $conn->query("SELECT * FROM cl_amount_items WHERE bd_id = '9999999' AND item_type = $item_type AND status = 1");
                        } else {
                            return $conn->query("SELECT * FROM cl_amount_items WHERE bd_id = 
                            (SELECT bd_id FROM branch_departments WHERE bd_code ='$d_code') AND item_type = $item_type AND status = 1");
                        }
                    }

                    function getSavedItems($conn, $item_type, $cl_step_id) {
                        $saved = [];
                        $result = $conn->query("SELECT cl_amount_item_id, amount, quantity, issued_date, remark FROM cl_request_step_amunt_items 
                                                WHERE item_type = $item_type AND step_id = '$cl_step_id'");
                        while ($row = $result->fetch_assoc()) {
                            $saved[$row['cl_amount_item_id']] = $row;
                        }
                        return $saved;
                    }

                    function generateTable($conn, $d_code, $category, $prefix, $item_type, $cl_step_id) {
                        global $prepare_check_approve;
                        $dis = '';
                        
                        if ($prepare_check_approve != '0' && $prepare_check_approve != '1') {
                            $dis = 'disabled';
                        }
                        $items = getItems($conn, $d_code, $item_type);
                        $savedItems = getSavedItems($conn, $item_type, $cl_step_id);

                        if ($items->num_rows > 0) {
                            echo "<div class='card mt-2'>";
                            echo "<div class='card-header' role='button' data-bs-toggle='collapse' data-bs-target='#{$prefix}Card'>";
                            echo "<h5 class='mb-0'>$category Items <i class='mdi mdi-chevron-down'></i></h5>";
                            echo "</div>";
                            echo "<div id='{$prefix}Card' class='collapse'>";
                            echo "<div class='card-body'>";
                            echo "<table class='table table-bordered'>";
                            echo "<thead><tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Issued Date</th>
                                    <th>Remarks</th>
                                </tr></thead>";
                            echo "<tbody>";

                            while ($item = $items->fetch_assoc()) {
                                $itemId = $item['cl_amount_item_id'];
                                $checked = isset($savedItems[$itemId]) ? "checked" : "";
                                $quantity = isset($savedItems[$itemId]) ? $savedItems[$itemId]['quantity'] : "1";
                                $issuedDate = isset($savedItems[$itemId]) ? $savedItems[$itemId]['issued_date'] : "";
                                $amount = isset($savedItems[$itemId]) ? $savedItems[$itemId]['amount'] : "0.00";
                                $remarks = isset($savedItems[$itemId]) ? $savedItems[$itemId]['remark'] : "";
                                
                                echo "<tr>";
                                echo "<td><input type='checkbox' class='{$prefix}-check' name='{$prefix}_check[]' value='{$itemId}' $checked $dis></td>";
                                echo "<td>{$item['item_name']}</td>";
                                echo "<td><input type='number' class='form-control {$prefix}-quantity' name='{$prefix}_quantity[{$itemId}]' min='1' step='1' value='$quantity'></td>";
                                echo "<td><input type='number' class='form-control {$prefix}-amount' name='{$prefix}_amount[{$itemId}]' min='0' step='0.01' value='$amount' required></td>";
                                echo "<td><input type='date' class='form-control {$prefix}-issued-date' name='{$prefix}_issued_date[{$itemId}]' value='$issuedDate'></td>";
                                echo "<td>
                                <input type='text' class='form-control' name='{$prefix}_note[{$itemId}]' value='$remarks'>
                                <input type='hidden' class='form-control' name='{$prefix}_type[{$itemId}]' value='{$item['item_type']}'>
                                </td>";
                                echo "</tr>";
                            }

                            echo "</tbody>";
                            echo "</table>";
                            echo "</div></div></div>";
                        }
                    }

                    // Generate tables with existing records
                    generateTable($conn, $d_code, 'Deduction', 'deduct', 2, $cl_step_id);
                    generateTable($conn, $d_code, 'Payable', 'payable', 1, $cl_step_id);
                    generateTable($conn, $d_code, 'Hold', 'hold', 3, $cl_step_id);

                    function getPhysicalItems($conn, $d_code, $item_type) {
                        global $b_or_d;
                        if ($b_or_d['is_branch'] == 1) {
                            return $conn->query("SELECT * FROM cl_physical_items WHERE bd_id = '9999999' AND item_type = $item_type AND status = 1");
                        } else {
                            return $conn->query("SELECT * FROM cl_physical_items WHERE bd_id = 
                            (SELECT bd_id FROM branch_departments WHERE bd_code ='$d_code') AND item_type = $item_type AND status = 1");
                        }
                    }

                    function getSavedPhysicalItems($conn, $item_type, $cl_step_id) {
                        $saved = [];
                        $result = $conn->query("SELECT cl_physical_item_id, quantity, remark FROM cl_request_step_physical_items 
                                                WHERE item_type = $item_type AND step_id = '$cl_step_id'");
                        while ($row = $result->fetch_assoc()) {
                            $saved[$row['cl_physical_item_id']] = $row;
                        }
                        return $saved;
                    }

                    function generatePhysicalTable($conn, $d_code, $category, $prefix, $item_type, $cl_step_id) {
                        global $prepare_check_approve;
                        $dis = '';
                        if ($prepare_check_approve != '0' && $prepare_check_approve != '1') {
                            $dis = 'disabled';
                        }
                        $items = getPhysicalItems($conn, $d_code, $item_type);
                        $savedItems = getSavedPhysicalItems($conn, $item_type, $cl_step_id);

                        if ($items->num_rows > 0) {
                            echo "<div class='card mt-2'>";
                            echo "<div class='card-header' role='button' data-bs-toggle='collapse' data-bs-target='#{$prefix}Card'>";
                            echo "<h5 class='mb-0'>$category Items <i class='mdi mdi-chevron-down'></i></h5>";
                            echo "</div>";
                            echo "<div id='{$prefix}Card' class='collapse'>";
                            echo "<div class='card-body'>";
                            echo "<table class='table table-bordered'>";
                            echo "<thead><tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Remarks</th>
                                </tr></thead>";
                            echo "<tbody>";

                            while ($item = $items->fetch_assoc()) {
                                $itemId = $item['cl_physical_item_id'];
                                $checked = isset($savedItems[$itemId]) ? "checked" : "";
                                $quantity = isset($savedItems[$itemId]) ? $savedItems[$itemId]['quantity'] : "1";
                                $remarks = isset($savedItems[$itemId]) ? $savedItems[$itemId]['remark'] : "";

                                echo "<tr>";
                                echo "<td><input type='checkbox' class='{$prefix}-check' name='{$prefix}_check[]' value='{$itemId}' $checked $dis></td>";
                                echo "<td>{$item['item_name']}</td>";
                                echo "<td><input type='number' class='form-control {$prefix}-quantity' name='{$prefix}_quantity[{$itemId}]' min='1' step='1' value='$quantity'></td>";
                                echo "<td>
                                <input type='text' class='form-control' name='{$prefix}_note[{$itemId}]' value='$remarks'>
                                <input type='hidden' class='form-control' name='{$prefix}_type[{$itemId}]' value='{$item['item_type']}'>
                                </td>
                                ";
                                echo "</tr>";
                            }

                            echo "</tbody>";
                            echo "</table>";
                            echo "</div></div></div>";
                        }
                    }

                    // Generate tables with existing records
                    generatePhysicalTable($conn, $d_code, 'Received', 'Received', 1, $cl_step_id);
                    generatePhysicalTable($conn, $d_code, 'Issued', 'Issued', 2, $cl_step_id);
                    ?>

                    <div class="mt-4">
                        <h5>Amount Totals</h5>
                        <div class="mt-2"> <b> <i class="mdi mdi-cash"></i> Deduction Total :  </b>Rs. <span id="deduct-total">0.00</span></div>
                        <div class="mt-2"> <b> <i class="mdi mdi-cash"></i> Payable Total :  </b>Rs. <span id="payable-total">0.00</span></div>
                        <div class="mt-2"> <b> <i class="mdi mdi-cash"></i> Hold Total :  </b>Rs. <span id="hold-total">0.00</span></div>
                    </div>

                    <div class="form-group mt-3">
                        <label for="note">Note</label>
                        <textarea class="form-control" id="note" name="note" rows="3" placeholder="Please write note here.."></textarea>
                    </div>

                    <input type="hidden" name="cl_id" id="cl_id" value="<?= $cl_id ?>">
                    <input type="hidden" name="cl_step_id" id="cl_step_id" value="<?= $cl_step_id ?>">
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <?php
                                $disabled = '';
                                if($clearance['assigned_preparer_user_id'] != $user_id && $clearance['assigned_checker_user_id'] != $user_id && $clearance['assigned_approver_user_id'] != $user_id) {
                                    $disabled = 'disabled';
                                }
                                if ($clearance['prepare_check_approve']=='1' && 
                                (empty($clearance['assigned_checker_user_id']) && $clearance['assigned_approver_user_id'] != $user_id)) {
                                    $disabled = 'disabled';
                                }
                                if ($clearance['prepare_check_approve']=='2' && ($clearance['assigned_approver_user_id'] != $user_id)) {
                                    $disabled = 'disabled';
                                }
                                if ($clearance['prepare_check_approve']=='3') {
                                    $disabled = 'disabled';
                                }

                                if ($clearance['prepare_check_approve']=='0') {
                                    if ($clearance['assigned_preparer_user_id'] == $user_id || 
                                    (empty($clearance['assigned_preparer_user_id']) && $clearance['assigned_checker_user_id'] == $user_id) ||
                                    ( empty($clearance['assigned_preparer_user_id']) && empty($clearance['assigned_checker_user_id']) && $clearance['assigned_approver_user_id'] == $user_id)) {
                                        echo '<button type="button" name="submit" id="submit" class="btn btn-success btn-sm" '. $disabled .' >Submit</button>';
                                    }
                                }
                                if ($clearance['prepare_check_approve']=='1') {
                                    if ($clearance['assigned_checker_user_id'] == $user_id || 
                                    (empty($clearance['assigned_checker_user_id']) && $clearance['assigned_approver_user_id'] == $user_id)) {
                                        echo '<button type="button" name="submit" id="submit" class="btn btn-success btn-sm me-1" '. $disabled .' >Submit</button>';
                                        echo '<button type="button" name="che" id="che" class="btn btn-success btn-sm" '. $disabled .' >Checked</button>';
                                    }
                                }
                                if ($clearance['prepare_check_approve']=='2') {
                                    if ($clearance['assigned_approver_user_id'] == $user_id) {
                                        echo '<button type="button" name="approve" id="approve" class="btn btn-success btn-sm" '. $disabled .' >Approve</button>';
                                    }
                                }
                            ?>
                            <button type="button" id="pending" class="btn btn-warning btn-sm" <?= $disabled ?> >Pending</button>
                        </div>
                        <a href="clearance-allocated.php" class="btn btn-info btn-sm">Back</a>
                    </div>

                </form>
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
    document.addEventListener("DOMContentLoaded", function () {
        function calculateTotal(prefix) {
            let total = 0;
            document.querySelectorAll(`.${prefix}-check`).forEach((checkbox, index) => {
                if (checkbox.checked) {
                    let amountInput = document.querySelectorAll(`.${prefix}-amount`)[index];
                    total += parseFloat(amountInput.value) || 0;
                }
            });
            document.getElementById(`${prefix}-total`).textContent = total.toFixed(2);
        }

        ['deduct', 'payable', 'hold'].forEach(prefix => {
            document.querySelectorAll(`.${prefix}-check, .${prefix}-amount`).forEach(element => {
                element.addEventListener('input', () => calculateTotal(prefix));
            });
        });

        // Initial Calculation on Page Load
        ['deduct', 'payable', 'hold'].forEach(prefix => calculateTotal(prefix));
    });


    $(document).ready(function () {
        $('#pending').click(function () {
            $("#pending").prop("disabled", true);

            let pending_note = document.getElementById('note').value;
            let cl_id = document.getElementById('cl_id').value;
            let cl_step_id = document.getElementById('cl_step_id').value;

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
                url: '../../back/clearance-allocated-manage.php',
                type: 'POST',
                data: {pending_note: pending_note, cl_id: cl_id, cl_step_id:cl_step_id, pending: 'pending'},
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        
                         //location.reload();
                         location.href = 'clearance-allocated.php';

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

        
        $(document).ready(function () {
            $("#submit").on("click", function (event) {
                event.preventDefault(); // Prevent form submission in case it's inside a form
                $("#submit").prop("disabled", true);

                let formData = {
                    submit: true,  
                    cl_id: $("#cl_id").val(),
                    cl_step_id: $("#cl_step_id").val(),
                    note: $("#note").val(),
                    physical_items: [],
                    amount_items: []
                };

                let validationFailed = false; // Flag to track validation status

                // Collect physical items
                $(".Received-check:checked, .Issued-check:checked").each(function () {
                    let itemId = $(this).val();
                    let prefix = $(this).hasClass("Received-check") ? "Received" : "Issued";

                    formData.physical_items.push({
                        item_id: itemId,
                        quantity: $(`.${prefix}-quantity[name='${prefix}_quantity[${itemId}]']`).val() || 1,
                        remark: $(`input[name='${prefix}_note[${itemId}]']`).val(),
                        type: $(`input[name='${prefix}_type[${itemId}]']`).val()
                    });
                });

                // Collect amount items with validation
                $(".deduct-check:checked, .payable-check:checked, .hold-check:checked").each(function () {
                    let itemId = $(this).val();
                    let prefix = $(this).hasClass("deduct-check") ? "deduct" :
                                $(this).hasClass("payable-check") ? "payable" : "hold";

                    let amount = $(`.${prefix}-amount[name='${prefix}_amount[${itemId}]']`).val();
                    if (!amount || isNaN(amount) || amount <= 0) {
                        
                        $("#submit").prop("disabled", false);
                        const alertBox = document.getElementById('customAlert');
                        alertBox.textContent = 'Amount must be a valid number greater than zero.';
                        alertBox.style.display = 'block';

                        // Hide the alert after 3 seconds
                        setTimeout(() => {
                            alertBox.style.display = 'none';
                        }, 3000);

                        validationFailed = true; // Set flag to true
                        return false; // Break out of the `.each()` loop
                    }

                    formData.amount_items.push({
                        item_id: itemId,
                        quantity: $(`.${prefix}-quantity[name='${prefix}_quantity[${itemId}]']`).val() || 1,
                        amount: amount,
                        issued_date: $(`.${prefix}-issued-date[name='${prefix}_issued_date[${itemId}]']`).val(),
                        remark: $(`input[name='${prefix}_note[${itemId}]']`).val(),
                        type: $(`input[name='${prefix}_type[${itemId}]']`).val()
                    });
                });

                // If validation failed, do not send AJAX request
                if (validationFailed) {
                    return;
                }

                // AJAX request
                $.ajax({
                    url: "../../back/clearance-allocated-manage.php",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function (response) {
                        if (response.error) {
                            //alert(response.error);
                            $("#submit").prop("disabled", false);
                            const alertBox = document.getElementById('customAlert');
                            alertBox.textContent = response.error;
                            alertBox.style.display = 'block';

                            // Hide the alert after 3 seconds
                            setTimeout(() => {
                                alertBox.style.display = 'none';
                            }, 3000);
                        } else {
                            //alert(response.message);
                            
                            location.reload(); // Reload page on success
                        }
                    },
                    error: function () {
                        $("#submit").prop("disabled", false);
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



});

$('#approve').click(function () {
    $("#approve").prop("disabled", true);
    let approve_note = document.getElementById('note').value;
    let cl_step_id = document.getElementById('cl_step_id').value;
    let cl_id = document.getElementById('cl_id').value;

    $.ajax({
        url: "../../back/clearance-allocated-manage.php",
        type: "POST",
        data: {approve_note: approve_note, cl_step_id: cl_step_id, approve: 'approve', cl_id: cl_id},
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

$('#che').click(function () {
    $("#che").prop("disabled", true);
    let note = document.getElementById('note').value;
    let cl_step_id = document.getElementById('cl_step_id').value;
    let cl_id = document.getElementById('cl_id').value;

    $.ajax({
        url: "../../back/clearance-allocated-manage.php",
        type: "POST",
        data: {note: note, cl_step_id: cl_step_id, che: 'che', cl_id: cl_id},
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                
                location.reload();
            } else {
                //alert("Error: " + response.message);
                $("#che").prop("disabled", false);
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
            $("#che").prop("disabled", false);
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
  </script>

  
</body>

</html>