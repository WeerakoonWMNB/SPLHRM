<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<?php
        include "../../back/credential-check.php";
        if (!checkAccess([1])) {
            echo "<script>window.location.href = '../general/dashboard.php';</script>";
                    exit;
        }
        include "../../back/connection/connection.php";
?>

<head>
  <?php require '../../partials/head.php'; ?>
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
                      <p class="card-title"><h4 id="title-name">Employee List</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add_employee()"><i class="mdi mdi-playlist-plus me-1"></i> Add Employee</button>
                      
                      <table id="employeeTable" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <!-- <th>Name </th> -->
                                    <th>Full Name</th>
                                    <th>Code</th>
                                    <th>EMP ID</th>
                                    <th>NIC</th>
                                    <th>Department</th>
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
    <div class="modal fade" id="myModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Add Employee</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 col-xl-12">
                            <div class="bg-light rounded h-100 p-4">
                                <form id="myform" method="POST" action="../../back/employee-manage.php" enctype="multipart/form-data">
                                    <input type="hidden" name="edit_id" id="edit_id" value="">

                                    <div class="row mb-3">
                                        <label for="inputEmail3" class="col-sm-3 col-form-label">Company *</label>
                                        <div class="col-sm-9">
                                            <?php
                                                $stmt = $conn->prepare("SELECT * FROM companies WHERE status = 1");
                                                $stmt->execute(); // Execute the query
                                                $companies = $stmt->get_result(); // Fetch the result
                                            ?>
                                        <select class="form-control" id="company" name ="company" required>
                                            <option value=''>Select</option>
                                            <?php
                                                foreach($companies as $company)
                                                {
                                                    ?>
                                                    <option value="<?= $company['company_code'] ?>"><?= $company['comany_name'] ?></option>
                                                    <?php
                                                }
                                            ?>
                                        </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputEmail3" class="col-sm-3 col-form-label">Branch/Department *</label>
                                        <div class="col-sm-9">

                                        <select class="form-control" id="branch" name ="branch" required>
                                            <option value=''>Select</option>
                                        </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputEmail3" class="col-sm-3 col-form-label">Title *</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="title" name ="title" required>
                                            <option value=''>Select</option>
                                            <option value='Mr.'>Mr.</option>
                                            <option value='Mrs.'>Mrs.</option>
                                            <option value='Miss.'>Miss.</option>
                                            <option value='Ms.'>Ms.</option>
                                            <option value='Dr.'>Dr.</option>
                                            <option value='Ven.'>Ven.</option>
                                        </select>
                                        </div>
                                    </div>
                                                        
                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Name with Initials *</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="init_name" name="init_name" placeholder="A K Dissanayake" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Full Name *</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="full_name" name="full_name" placeholder="Amith Kumara Dissanayake" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputEmail3" class="col-sm-3 col-form-label">Emp Category *</label>
                                        <div class="col-sm-9">
                                        <?php
                                                $stmt_cat = $conn->prepare("SELECT * FROM emp_category WHERE status = 1");
                                                $stmt_cat->execute(); // Execute the query
                                                $categories = $stmt_cat->get_result(); // Fetch the result
                                            ?>
                                        <select class="form-control" id="emp_type" name ="emp_type" required>
                                            <option value=''>Select</option>
                                            <?php
                                                foreach($categories as $category)
                                                {
                                                    ?>
                                                    <option value="<?= $category['cat_id'] ?>"><?= $category['cat_name'] ?></option>
                                                    <?php
                                                }
                                            ?>
                                        </select>
                                        </div>
                                    </div>


                                    <div class="row mb-3">
                                        <label for="emp_designation" class="col-sm-3 col-form-label">Emp Designation *</label>
                                        <div class="col-sm-9 position-relative">
                                            <input type="text" id="searchDesignation" class="form-control" autocomplete='off' placeholder="Search Designation">
                                            <div id="designationList" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                                            
                                            <select class="form-control mt-2" autocomplete='off' id="designationDropdown" name="emp_designation" style="display: none;">
                                                <option value="">Select</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">EMP ID *</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="employee_id" name="employee_id" placeholder="2516" readonly>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label" id="emp_code_lable">Marketer Code </label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="emp_code" name="emp_code" placeholder="DKD1-7985P-5652">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">EPF No </label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="epf" name="epf" placeholder="MHMUM00127800000001313">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">NIC *</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="nic" name="nic" placeholder="875615320V" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Gender *</label>
                                        <div class="col-sm-9">
                                            <label class="form-check-label mt-2 me-2">
                                            <input type="radio" class="form-check-input" name="gender" id="genderm" value="0" required>
                                                Male
                                            </label>
                                            <label class="form-check-label mt-2">
                                            <input type="radio" class="form-check-input" name="gender" id="genderf" value="1">
                                                Female
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Marital Status *</label>
                                        <div class="col-sm-9">
                                            <label class="form-check-label mt-2 me-2">
                                            <input type="radio" class="form-check-input" name="marital" id="maritals" value="0" required>
                                                Single
                                            </label>
                                            <label class="form-check-label mt-2">
                                            <input type="radio" class="form-check-input" name="marital" id="maritalm" value="1">
                                                Married
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Birthday *</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="birthday" name="birthday"  required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Appointment Date *</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="appointment" name="appointment"  required>
                                        </div>
                                    </div>

                                    <div class="row mb-3" style="display:none;">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Last Promoted Date *</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="lp_date" name="lp_date">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Address Line 1 *</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="address1" name="address1" placeholder="Alfred Place"  required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Address Line 2</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' id="address2" name="address2" placeholder="Colombo 03" >
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">E-Mail </label>
                                        <div class="col-sm-9">
                                            <input type="email" class="form-control" autocomplete='off' id="email" name="email" placeholder="someone@gmail.com" >
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Phone (Mobile) *</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' minlength="10" maxlength="10" id="mobile" name="mobile" placeholder="0715689542" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Phone (Work) </label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' minlength="10" maxlength="10" id="work" name="work" placeholder="0112565326" >
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Phone (Home) </label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' minlength="10" maxlength="10" id="home" name="home" placeholder="0112215456" >
                                        </div>
                                    </div>

                                    <div class="row mb-3" style="display:none;">
                                        <label for="reporting" class="col-sm-3 col-form-label">Reporting Officer</label>
                                        <div class="col-sm-9 position-relative">
                                            <input type="text" id="searchEmployee" autocomplete='off' class="form-control" placeholder="Search Reporting Officer">
                                            <div id="employeeList" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                                            
                                            <select class="form-control mt-2" id="reporting" name="reporting" style="display: none;">
                                                <option value="">Select</option>
                                            </select>
                                        </div>
                                    </div>
                                <hr>
                                    <div class="row mb-3">
                                        <label for="bankBranch" class="col-sm-3 col-form-label">Bank Branch</label>
                                        <div class="col-sm-9 position-relative">
                                            <input type="text" id="bankBranch" autocomplete='off' class="form-control" placeholder="Search Bank and Branch">
                                            <div id="bankBranchList" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                                            
                                            <select class="form-control mt-2" id="bankBranchId" name="bankBranchId" style="display: none;">
                                                <option value="">Select</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="account_number" class="col-sm-3 col-form-label">Account Number</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" autocomplete='off' minlength="5" id="account_number" name="account_number" placeholder="0002215456" >
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="nicCopy" class="col-sm-3 col-form-label">NIC Copy</label>
                                        <div class="col-sm-9">
                                            <input type="file" class="form-control" id="nicCopy" name="nicCopy" accept=".pdf, .jpg, .jpeg, .png" >
                                            <div id="previewContainer" class="mt-2"></div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="passbookCopy" class="col-sm-3 col-form-label">Passbook Copy</label>
                                        <div class="col-sm-9">
                                            <input type="file" class="form-control" id="passbookCopy" name="passbookCopy" accept=".pdf, .jpg, .jpeg, .png" >
                                            <div id="previewContainer1" class="mt-2"></div>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button form="myform" type="button" id="submitBtn" class="btn btn-success">Submit</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                </div>

            </div>  
        </div>
    </div>

    <div class="modal fade" id="myPromoModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title-p">Employee Promote/Demote</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 col-xl-12">
                            <div class="bg-light rounded h-100 p-4">
                                <form id="myformP" method="POST" action="../../back/employee-manage.php" enctype="multipart/form-data">
                                    <input type="hidden" name="edit_id_p" id="edit_id_p" value="">

                                    <!-- <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Promote/Demote *</label>
                                        <div class="col-sm-9">
                                            <label class="form-check-label mt-2 me-2">
                                            <input type="radio" class="form-check-input" name="promote" id="promoteP" value="1" required>
                                                Promote
                                            </label>
                                            <label class="form-check-label mt-2">
                                            <input type="radio" class="form-check-input" name="promote" id="promoteD" value="0">
                                                Demote
                                            </label>
                                        </div>
                                    </div> -->

                                    <div class="row mb-3">
                                        <label for="emp_designation" class="col-sm-3 col-form-label">Emp Designation *</label>
                                        <div class="col-sm-9 position-relative">
                                            <input type="text" id="searchDesignationP" class="form-control" placeholder="Search Designation">
                                            <div id="designationListP" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                                            
                                            <select class="form-control mt-2" id="designationDropdownP" name="emp_designation_p" style="display: none;">
                                                <option value="">Select</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputPassword3" class="col-sm-3 col-form-label">Effective Date *</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="effective_date" name="effective_date"  required>
                                        </div>
                                    </div>


                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button form="myformP" type="button" id="submitBtnP" class="btn btn-success">Submit</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
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
        "ajax": {
            "url": "../../back/employees-fetch.php",
            "type": "POST"
        },
        "columns": [
            { "data": "row_id" },
            // { "data": "ini_name" },
            { "data": "f_name" },
            { "data": "code" },
            { "data": "system_emp_no" },
            { "data": "nic" },
            { "data": "bd_name" },
            { "data": "action" }
        ]
    });
});

// ✅ Move `load_branches` here so it’s globally accessible
function load_branches(companyCode,branchCode=null) {
    $.ajax({
        url: "../../back/employee-manage.php", // Change this to your server-side script
        type: "POST",
        data: { company_code: companyCode },
        dataType: "json",
        success: function(response) {
            $("#branch").empty().append('<option value="">Select</option>');
            $.each(response, function(index, item) {
                branchCode == item.bd_id ? $("#branch").append(`<option value="${item.bd_id}" selected>${item.bd_name}</option>`) :
                $("#branch").append(`<option value="${item.bd_id}">${item.bd_name}</option>`);
            });
        },
        error: function() {
            alert("Error fetching branches");
        }
    });
}

// ✅ Keep `emp_set` outside too
function emp_set(empId) {
    $.ajax({
        url: '../../back/employee-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: empId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('.modal-title').html('Edit Employee');
                $('#edit_id').val(response.data.emp_id);
                $('#company').val(response.data.company_code);
                load_branches(response.data.company_code,response.data.bd_id);
                $('#branch').val(response.data.bd_id);
                $('#title').val(response.data.title);
                $('#init_name').val(response.data.name_with_initials);
                $('#full_name').val(response.data.name_in_full);
                $('#emp_type').val(response.data.emp_cat_id); 
                $('#employee_id').val(response.data.system_emp_no); 
                $('#emp_code').val(response.data.code);
                $('#designationDropdown').html('<option value="'+response.data.designation_id+'" selected>'+response.data.designation+'</option>');
                $('#searchDesignation').val(response.data.designation);
                $('#searchDesignation').attr('readonly', true);
                $('#epf').val(response.data.epf_no);
                $('#nic').val(response.data.nic);
                $("input[name='gender'][value='" + response.data.gender + "']").prop("checked", true);
                $("input[name='marital'][value='" + response.data.marital_status + "']").prop("checked", true);
                $('#birthday').val(response.data.birthday); 
                $('#appointment').val(response.data.appointment_date);
                $('#lp_date').val(response.data.lp_date);
                $('#lp_date').attr('readonly', true);
                $('#address1').val(response.data.address_line_1);
                $('#address2').val(response.data.address_line_2);
                $('#email').val(response.data.email);
                $('#mobile').val(response.data.mobile);
                $('#work').val(response.data.work);
                $('#home').val(response.data.home);
                $('#searchEmployee').val(response.data.reporting);
                $('#bankBranch').val(response.data.bank_branch_name);
                $('#reporting').html('<option value="'+response.data.reporting_officer_emp_id+'" selected>'+response.data.reporting+'</option>');
                $('#account_number').val(response.data.account_number);
                $('#bankBranchId').html('<option value="'+response.data.code+'" selected>'+response.data.bank_branch_name+'</option>');
                if (response.data?.nic_location) {
                    previewFile(response.data.nic_location);
                }

                if (response.data?.passbook_location) {
                    previewFile1(response.data.passbook_location);
                }
                $('#myModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch Employee details: ' + response.message;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching Employee details:', error);
        }
    });
}

function emp_promote(empId) {
    $.ajax({
        url: '../../back/employee-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: empId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('#edit_id_p').val(response.data.emp_id);
                $('#designationDropdownP').html('<option value="'+response.data.designation_id+'" selected>'+response.data.designation+'</option>');
                $('#searchDesignationP').val(response.data.designation);
                $('#myPromoModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch Employee details: ' + response.message;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching Employee details:', error);
        }
    });
}


function add_employee() {
                $('.modal-title').html('Add Employee');
                $('#edit_id').val('');
                $('#company').val('');
                $('#branch').val('');
                $('#title').val('');
                $('#init_name').val('');
                $('#full_name').val('');
                $('#emp_type').val(''); 
                $('#emp_code').val('');
                $('#employee_id').val('');
                $('#designationDropdown').val('');
                $('#searchDesignation').val('');
                $('#searchDesignation').attr('readonly', false);
                $('#epf').val('');
                $('#nic').val('');
                $("input[name='gender']").prop("checked", false);
                $("input[name='marital']").prop("checked", false);
                $('#birthday').val(''); 
                $('#appointment').val('');
                $('#lp_date').val('');
                $('#lp_date').attr('readonly', false);
                $('#address1').val('');
                $('#address2').val('');
                $('#email').val('');
                $('#mobile').val('');
                $('#work').val('');
                $('#home').val('');
                $('#searchEmployee').val('');
                $('#reporting').val('');
                $('#bankBranch').val('');
                $('#bankBranchId').val('');
                $('#account_number').val('');
                $('#nicCopy').val('');
                $('#passbookCopy').val('');
                $('#previewContainer1').html('');
                $('#previewContainer').html('');
}

// Fetch branches based on selected company
$(document).ready(function() {
    $("#company").change(function() {
        let companyCode = $(this).val();
        
        if (companyCode) {
            load_branches(companyCode);
        } else {
            $("#branch").empty().append('<option value="">Select</option>');
        }
    });

    
});


$(document).ready(function(){
    $("#searchEmployee").on("keyup", function() {
        let searchText = $(this).val();
        if(searchText.length > 3) { // Trigger search on 2+ characters
            $.ajax({
                url: "../../back/employee-manage.php", 
                method: "POST",
                data: {query: searchText},
                dataType: "html", // Expecting an HTML response
                success: function(response) {
                    $("#employeeList").html(response).show();
                }
            });
        } else {
            $("#employeeList").hide();
        }
    });

    // Handle click on suggestion
    $(document).on("click", ".employee-option", function(){
        let employeeName = $(this).text();
        let employeeId = $(this).data("id");

        $("#searchEmployee").val(employeeName); // Set input field
        $("#employeeList").hide(); // Hide suggestion box
        
        // Add selected employee to dropdown and select it
        $("#reporting").html(`<option value="${employeeId}" selected>${employeeName}</option>`);
    });

    // Hide the suggestion list when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest("#searchEmployee, #employeeList").length) {
            $("#employeeList").hide();
        }
    });

    $("#searchDesignation").on("keyup", function() {
    let searchText = $(this).val();
    if (searchText.length > 3) { // Trigger search on 2+ characters
        $.ajax({
            url: "../../back/employee-manage.php",
            method: "POST",
            data: { queryDesignation: searchText },
            dataType: "html", // Expecting an HTML response
            success: function(response) {
                console.log(response);
                
                $("#designationList").html(response).show();
            }
        });
    } else {
        $("#designationList").hide();
    }
});

// Handle click on suggestion
$(document).on("click", ".designation-option", function() {
    let designationName = $(this).text();
    let designationId = $(this).data("id");

    $("#searchDesignation").val(designationName); // Set input field
    $("#designationList").hide(); // Hide suggestion box
    
    // Add selected designation to dropdown and select it
    $("#designationDropdown").html(`<option value="${designationId}" selected>${designationName}</option>`);

    $("#searchDesignationP").val(designationName); // Set input field
    $("#designationListP").hide(); // Hide suggestion box
    
    // Add selected designation to dropdown and select it
    $("#designationDropdownP").html(`<option value="${designationId}" selected>${designationName}</option>`);
});

// Hide the suggestion list when clicking outside
$(document).click(function(e) {
    if (!$(e.target).closest("#searchDesignation, #designationList").length) {
        $("#designationList").hide();
    }
});



$("#searchDesignationP").on("keyup", function() {
    let searchText = $(this).val();
    if (searchText.length > 3) { // Trigger search on 2+ characters
        $.ajax({
            url: "../../back/employee-manage.php",
            method: "POST",
            data: { queryDesignation: searchText },
            dataType: "html", // Expecting an HTML response
            success: function(response) {
                //console.log(response);
                
                $("#designationListP").html(response).show();
            }
        });
    } else {
        $("#designationListP").hide();
    }
});

// Hide the suggestion list when clicking outside
$(document).click(function(e) {
    if (!$(e.target).closest("#searchDesignationP, #designationListP").length) {
        $("#designationListP").hide();
    }
});

$("#bankBranch").on("keyup", function() {
        let searchText = $(this).val();
        if(searchText.length > 2) { // Trigger search on 2+ characters
            $.ajax({
                url: "../../back/bank-branch.php", 
                method: "POST",
                data: {query: searchText},
                dataType: "html", // Expecting an HTML response
                success: function(response) {
                    $("#bankBranchList").html(response).show();
                }
            });
        } else {
            $("#bankBranchList").hide();
        }
    });

    // Handle click on suggestion
    $(document).on("click", ".bank-option", function(){
        let Name = $(this).text();
        let Id = $(this).data("id");

        $("#bankBranch").val(Name); // Set input field
        $("#bankBranchList").hide(); // Hide suggestion box
        
        // Add selected employee to dropdown and select it
        $("#bankBranchId").html(`<option value="${Id}" selected>${Name}</option>`);
    });

    $(document).click(function(e) {
        if (!$(e.target).closest("#bankBranch, #bankBranchList").length) {
            $("#bankBranchList").hide();
        }
    });

    // Hide the suggestion list when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest("#searchEmployee, #employeeList").length) {
            $("#employeeList").hide();
        }
    });


});

    $('#mobile').on('input', function () {
            // Remove non-numeric characters
        let cleanedValue = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(cleanedValue);

        // Change text color based on length
        if (cleanedValue.length === 10) {
            $(this).css('color', 'green');
        } else {
            $(this).css('color', 'red');
        }

        });
    $('#work').on('input', function () {
        let cleanedValue = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(cleanedValue);

        // Change text color based on length
        if (cleanedValue.length === 10) {
            $(this).css('color', 'green');
        } else {
            $(this).css('color', 'red');
        }
    });
    $('#home').on('input', function () {
        let cleanedValue = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(cleanedValue);

        // Change text color based on length
        if (cleanedValue.length === 10) {
            $(this).css('color', 'green');
        } else {
            $(this).css('color', 'red');
        }
    });

    // $(document).on("change", "#emp_type", function () {
    //     let employeeData = $(this).val();
        
    // if (employeeData == "2" || employeeData == "3") {
    //         $('#emp_code_lable').html('Emp Code *');
    //         $("input[name='emp_code']").prop("required", true);
    //     } else {
    //         $('#emp_code_lable').html('Emp Code');
    //         $("input[name='emp_code']").prop("required", false);
    //     }
    // });


    //submit form
    $(document).ready(function () {
        $('#submitBtn').click(function () {
            let form = document.getElementById("myform"); // Select form
            let formData = new FormData(form); // Create FormData manually
            $("#submitBtn").prop("disabled", true);
            $.ajax({
                url: $('#myform').attr('action'),
                type: $('#myform').attr('method'),
                data: formData,
                contentType: false,  // Prevent jQuery from setting content type
                processData: false,  // Prevent jQuery from processing data
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        
                         location.reload();
                    } else {
                        //alert("Error: " + response.message);
                        $("#submitBtn").prop("disabled", false);
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
                    $("#submitBtn").prop("disabled", false);
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

        $('#submitBtnP').click(function () {
            $("#submitBtnP").prop("disabled", true);
            $.ajax({
                url: $('#myformP').attr('action'),
                type: $('#myformP').attr('method'),
                data: $('#myformP').serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        
                         location.reload();
                    } else {
                        //alert("Error: " + response.message);
                        $("#submitBtnP").prop("disabled", false);
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
                    $("#submitBtnP").prop("disabled", false);
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
    function previewFile(url) {
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.innerHTML = ''; // Clear previous preview

            displayPreview(url);
        
    }

    function displayPreview(url) {
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.innerHTML = ''; // Clear previous preview

        if (url.endsWith('.jpg') || url.endsWith('.jpeg') || url.endsWith('.png')) {
            // Show image preview
            const img = document.createElement('img');
            img.src = url;
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            previewContainer.appendChild(img);
        } else if (url.endsWith('.pdf')) {
            // Show PDF link
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.textContent = 'View NIC Copy(PDF)';
            previewContainer.appendChild(link);
        } else {
            previewContainer.innerHTML = '<p>Unsupported file format.</p>';
        }
    }

    function previewFile1(url) {
        const previewContainer = document.getElementById('previewContainer1');
        previewContainer.innerHTML = ''; // Clear previous preview

            displayPreview1(url);
        
    }

    function displayPreview1(url) {
        const previewContainer = document.getElementById('previewContainer1');
        previewContainer.innerHTML = ''; // Clear previous preview

        if (url.endsWith('.jpg') || url.endsWith('.jpeg') || url.endsWith('.png')) {
            // Show image preview
            const img = document.createElement('img');
            img.src = url;
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            previewContainer.appendChild(img);
        } else if (url.endsWith('.pdf')) {
            // Show PDF link
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.textContent = 'View passbook Copy(PDF)';
            previewContainer.appendChild(link);
        } else {
            previewContainer.innerHTML = '<p>Unsupported file format.</p>';
        }
    }

</script>
</body>

</html>