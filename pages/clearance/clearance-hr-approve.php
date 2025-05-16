<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<?php
        include "../../back/credential-check.php";
        if (!checkAccess([1,2,3])) {
            echo "<script>window.location.href = '../general/dashboard.php';</script>";
                    exit;
        }
        include "../../back/connection/connection.php";
        include "../../back/functions.php";

        $cl_id = null;
        if (isset($_GET['id'])) {
            if (!empty($_GET['id'])) {
                $cl_id = base64_decode($_GET['id']);

                $clearance = $conn->query("SELECT cl_requests.resignation_date,
                     cl_requests.is_complete, 
                     employees.name_with_initials, 
                     employees.code, 
                     employees.system_emp_no, 
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
              AND ((cl_requests_steps.step = (
                        SELECT MIN(step) FROM cl_requests_steps 
                        WHERE cl_requests_steps.request_id = cl_requests.cl_req_id
                        AND (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2)
                    )) OR cl_requests_steps.step = 0)
              INNER JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
              INNER JOIN designations ON employees.designation_id = designations.desig_id 
              INNER JOIN users ON users.user_id = cl_requests.created_by
              LEFT JOIN uploads letter ON letter.request_id = cl_requests.cl_req_id AND letter.document_type = '1'
              WHERE cl_requests.cl_req_id = '$cl_id' AND cl_requests.status = 1 ORDER BY cl_requests_steps.step DESC LIMIT 1");

                if ($clearance->num_rows != 1) {
                    //header("Location: clearance-list.php");
                    echo "<script>window.location.href = 'clearance-list.php';</script>";
                    exit;
                }   

                $clearance = $clearance->fetch_assoc();

                $referenceDate = !empty($clearance['last_completed_date']) ? $clearance['last_completed_date'] : $clearance['created_date'];
                //$daysGap = (new DateTime($referenceDate))->diff(new DateTime())->days;
                $daysGap = getWeekdaysDiff(date('Y-m-d', strtotime($referenceDate)), date('Y-m-d'));

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

                if ($clearance['is_complete'] == '0' && ($clearance['step_complete'] == '1' && $clearance['step'] == "0") && $clearance['department'] != "") {
                    $completion_percentage = 10;
                }

                $prog = '<div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: ' . ($completion_percentage ?: 0) . '%" aria-valuenow="' . ($completion_percentage ?: 0) . '" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>';
                
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
                                            <td><b>Employee EPF/Code : </b></td>
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
                                            <td><b>Employee Re-Join Status : </b></td>
                                            <td><?php if (empty($clearance['rejoin_or_not'])) {
                                                echo 'No';
                                            } else {
                                                echo 'Yes';
                                            } ?></td>
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
                                            <td><b>Customer Visit Report : </b></td>
                                            <td>
                                            <?php
                                                // Search for the files in the database
                                                $queryDoc = "SELECT * FROM uploads WHERE request_id = '$cl_id' AND document_type = '2'";
                                                $resultDoc = $conn->query($queryDoc);

                                                if ($resultDoc->num_rows > 0) {
                                                    $i=1;
                                                    while ($clearanceDoc = $resultDoc->fetch_assoc()) {
                                                        $url = $clearanceDoc['location'];
                                                        $fileExtension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

                                                        echo '<div style="margin-bottom: 20px;">';

                                                        if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                                                            // Display Image
                                                            echo '<div style="max-width: 500px; max-height: 600px; overflow: hidden;">';
                                                            echo '<img src="' . htmlspecialchars($url) . '" style="width: 100%; height: auto; display: block; border: 1px solid #ddd; border-radius: 8px; padding: 5px;">';
                                                            echo '</div>';
                                                        } elseif ($fileExtension === 'pdf') {
                                                            // Display PDF Link
                                                            echo '<a href="' . htmlspecialchars($url) . '" target="_blank" style="font-weight: bold; color: blue; text-decoration: underline;">'.$i.'. View Document (PDF)</a>';
                                                            $i++;
                                                        } else {
                                                            // Unsupported Format
                                                            echo '<p>Unsupported file format.</p>';
                                                        }

                                                        echo '</div>';
                                                    }
                                                } else {
                                                    echo '<div><p>No file uploaded.</p></div>';
                                                }
                                            ?>
                                            </td>
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
                <?php } 
                else if (
                    (($clearance['step'] == '0' && $clearance['step_complete'] == '1') && ($_SESSION['ulvl']=='1' || $_SESSION['ulvl']=='2'))
                    || ($_SESSION['ulvl']=='1' || $_SESSION['ulvl']=='2')
                    ){
                    ?>
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                            <h4 class="card-title">Department Allocation</h4>
                            <div class="media">
                                
                                <div class="media-body">
                                <button type="button" id="addRow" class="btn btn-info btn-sm mt-2 mb-2">Add Row</button>
                                <form action="../../back/clearance-manage.php" method="post" id="department_alocation_form">
                                    <input type="hidden" name="cl_id" id="cl_id" value="<?= $cl_id ?>">
                                    <table width="100%" class="table table-bordered">
                                        <th>#</th>
                                        <th>Department</th>
                                        <th>Order</th>
                                        <th>Preparer</th>
                                        <th>Checker</th>
                                        <th>Approver</th>
                                        <th>Action</th>
                                        <tbody id="department_allocate_table">
                                            
                                        </tbody>
                                    </table>
                                        
                                
                                    
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <button type="button" name="allocate" id="allocate" class="btn btn-success btn-sm">Allocate</button>
                                        </div>
                                        <a href="clearance-list.php" class="btn btn-info btn-sm">Back</a>
                                    </div>

                                    
                                </form>
                                </div>

                            </div>
                            </div>
                        </div>
                        </div>
                <?php }
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
<script>
$(document).ready(function () {
    let rowCount = 0;
let departments = [];
let cl_id_for_fetch = $("#cl_id_for_fetch").val();

// Fetch departments via AJAX
$.ajax({
    url: "../../back/fetch_departments.php",
    type: "GET",
    dataType: "json",
    success: function (data) {
        departments = data;
        fetchExistingRecords(); // Fetch existing records once departments are loaded
    }
});

// Function to fetch and populate existing records
function fetchExistingRecords() {
    $.ajax({
        url: "../../back/clearance-manage.php",
        type: "GET",
        data: { cl_id_for_fetch: cl_id_for_fetch },
        dataType: "json",
        success: function (data) {
            data.forEach(async (record) => {
                rowCount++;

                let selectedDepartments = getSelectedValues(".department");
                let selectedSequences = getSelectedValues(".sequence");

                let departmentOptions = getDepartmentOptions(selectedDepartments, record.department_id);
                let sequenceOptions = getSequenceOptions(selectedSequences, record.sequence);
                
                // Fetch employees for the selected department
                let employees = await getEmployees(record.department_id);
                //console.log(record.assigned_preparer_user_id);
                
                let preparerOptions = getEmployeeOptions(employees, record.assigned_preparer_user_id);
                let checkerOptions = getEmployeeOptions(employees, record.assigned_checker_user_id);
                let approverOptions = getEmployeeOptions(employees, record.assigned_approver_user_id);

                let disable = record.is_complete == '1' ? "disabled" : "";

                let newRow = `
                    <tr>
                        <td class="row-number">${rowCount}</td>
                        <td>
                            <select class="department form-control" name="selectedDepartments[]" ${disable}>${departmentOptions}</select>
                        </td>
                        <td>
                            <select class="sequence form-control" name="selectedSequence[]" ${disable}>${sequenceOptions}</select>
                        </td>
                        <td>
                            <select class="preparer form-control" name="selectedPreparer[]" ${disable}>${preparerOptions}</select>
                        </td>
                        <td>
                            <select class="checker form-control" name="selectedChecker[]" ${disable}>${checkerOptions}</select>
                        </td>
                        <td>
                            <select class="approver form-control" name="selectedApprover[]" ${disable}>${approverOptions}</select>
                        </td>
                        <td>
                            <button type="button" class="deleteRow btn btn-danger btn-sm" ${disable}><i class="mdi mdi-delete-forever"></i></button>
                        </td>
                    </tr>`;

                $("#department_allocate_table").append(newRow);
                updateDropdowns();
            });
        }
    });
}

// Function to get selected values
function getSelectedValues(selector) {
    let selectedValues = [];
    $(selector).each(function () {
        let val = $(this).val();
        if (val) selectedValues.push(val);
    });
    return selectedValues;
}

// Function to generate department dropdown options while preserving selected values
function getDepartmentOptions(selectedDepartments, currentVal) {
    let options = `<option value="">Select Department</option>`;
    departments.forEach(dept => {
        let disabled = selectedDepartments.includes(dept.bd_code) && dept.bd_code !== currentVal ? "disabled" : "";
        let selected = dept.bd_code === currentVal ? "selected" : "";
        options += `<option value="${dept.bd_code}" ${disabled} ${selected}>${dept.bd_name}</option>`;
    });
    return options;
}

// Function to generate sequence dropdown options while preserving selected values
function getSequenceOptions(selectedSequences, currentVal) {
    let options = `<option value="">Select Sequence</option>`;
    for (let i = 1; i <= rowCount; i++) {
        let disabled = selectedSequences.includes(i.toString()) && i.toString() != currentVal ? "disabled" : "";
        let selected = i.toString() == currentVal ? "selected" : "";
        options += `<option value="${i}" ${disabled} ${selected}>${i}</option>`;
    }
    return options;
}

// Function to fetch employees of a department
async function getEmployees(bd_id) {
    try {
        let response = await $.ajax({
            url: "../../back/clearance-manage.php",
            type: "GET",
            data: { bd_id: bd_id },
            dataType: "json",
        });
        return response;
    } catch (error) {
        console.error("Error fetching employees", error);
        return [];
    }
}

// Function to generate employee dropdown options
function getEmployeeOptions(employees, selectedEmployee) {
    let options = `<option value="">Select Employee</option>`;
    employees.forEach(emp => {
        let selected = emp.user_id == selectedEmployee ? "selected" : "";
        options += `<option value="${emp.user_id}" ${selected}> ${emp.name}</option>`;
    });
    return options;
}

// Function to add a new row
$("#addRow").click(async function () {
    rowCount++;

    let selectedDepartments = getSelectedValues(".department");
    let selectedSequences = getSelectedValues(".sequence");

    let departmentOptions = getDepartmentOptions(selectedDepartments, "");
    let sequenceOptions = getSequenceOptions(selectedSequences, "");
    
    let preparerOptions = `<option value="">Select Employee</option>`;
    let checkerOptions = `<option value="">Select Employee</option>`;
    let approverOptions = `<option value="">Select Employee</option>`;

    let newRow = `
        <tr>
            <td class="row-number">${rowCount}</td>
            <td>
                <select class="department form-control" name="selectedDepartments[]">${departmentOptions}</select>
            </td>
            <td>
                <select class="sequence form-control" name="selectedSequence[]">${sequenceOptions}</select>
            </td>
            <td>
                <select class="preparer form-control" name="selectedPreparer[]">${preparerOptions}</select>
            </td>
            <td>
                <select class="checker form-control" name="selectedChecker[]">${checkerOptions}</select>
            </td>
            <td>
                <select class="approver form-control" name="selectedApprover[]">${approverOptions}</select>
            </td>
            <td>
                <button type="button" class="deleteRow btn btn-danger btn-sm"><i class="mdi mdi-delete-forever"></i></button>
            </td>
        </tr>`;

    $("#department_allocate_table").append(newRow);
    updateDropdowns();
});

// Update dropdowns when department changes
$(document).on("change", ".department", async function () {
    let row = $(this).closest("tr");
    let departmentId = $(this).val();
    
    if (departmentId) {
        let employees = await getEmployees(departmentId);
        
        row.find(".preparer").html(getEmployeeOptions(employees, ""));
        row.find(".checker").html(getEmployeeOptions(employees, ""));
        row.find(".approver").html(getEmployeeOptions(employees, ""));
    } else {
        row.find(".preparer, .checker, .approver").html(`<option value="">Select Employee</option>`);
    }
    
    updateDropdowns();
});

// Function to update department and sequence dropdowns dynamically
function updateDropdowns() {
    let selectedDepartments = getSelectedValues(".department");
    let selectedSequences = getSelectedValues(".sequence");

    $(".department").each(function () {
        let currentVal = $(this).val();
        $(this).html(getDepartmentOptions(selectedDepartments, currentVal));
    });

    $(".sequence").each(function () {
        let currentVal = $(this).val();
        $(this).html(getSequenceOptions(selectedSequences, currentVal));
    });

    updateRowNumbers();
}


    // Restrict duplicate sequence selection and preserve selected values
    $(document).on("change", ".sequence", function () {
        updateDropdowns();
    });

    // Delete row and update numbering
    $(document).on("click", ".deleteRow", function () {
        $(this).closest("tr").remove();
        rowCount--;
        updateRowNumbers();
        updateDropdowns();
    });

    // Function to update row numbers
    function updateRowNumbers() {
        let count = 1;
        $(".row-number").each(function () {
            $(this).text(count);
            count++;
        });
    }
});


$(document).ready(function () {
    $("#allocate").click(function () {
        let formData = $("#department_alocation_form").serialize();
        $("#allocate").prop("disabled", true);

        $.ajax({
            url: "../../back/clearance-manage.php",
            type: "POST",
            data: formData + "&action=allocate",
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    //alert(response.message);
                    location.reload(); // Refresh the page after successful submission
                } else {
                    //alert(response.message);
                    $("#allocate").prop("disabled", false);
                    const alertBox = document.getElementById('customAlert');
                        alertBox.innerHTML  =  response.message;
                        alertBox.style.display = 'block';

                        // Hide the alert after 3 seconds
                        setTimeout(() => {
                            alertBox.style.display = 'none';
                        }, 3000);
                }
            },
            error: function () {
                $("#allocate").prop("disabled", false);
                
                const alertBox = document.getElementById('customAlert');
                        alertBox.innerHTML  =  "An error occurred. Please try again.";
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