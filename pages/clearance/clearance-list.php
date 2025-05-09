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
                      <p class="card-title"><h4 id="title-name">Clearance List</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add()"><i class="mdi mdi-playlist-plus me-1"></i> Add Clearance</button>
                      
                      <table id="employeeTable" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Clearance ID </th>
                                    <th>Name</th>
                                    <th>Resignation Date</th>
                                    <th>EPF No</th>
                                    <th>Code</th>
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
  <div class="modal fade" id="myModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Add Clearance</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <form id="myform" method="POST" action="../../back/clearance-manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" id="edit_id" value="">

                        <div class="row mb-3">
                            <label for="reporting" class="col-sm-3 col-form-label">Employee *</label>
                            <div class="col-sm-9 position-relative">
                                <input type="text" id="searchEmployee" class="form-control" placeholder="Search Employee" autocomplete="off">
                                <div id="employeeList" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                                
                                <select class="form-control mt-2" id="reporting" name="employee" style="display: none;" required>
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Resignation Date *</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control" id="resignation_date" name="resignation_date"  required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="resignationLetter" class="col-sm-3 col-form-label">Resignation Letter *</label>
                            <div class="col-sm-9">
                                <input type="file" class="form-control" id="resignationLetter" name="resignationLetter" accept=".pdf, .jpg, .jpeg, .png" required>
                                <div id="previewContainer" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="row mb-3" style="display:none;">
                            <label for="customerVisitReport" class="col-sm-3 col-form-label">Customer Visit Report</label>
                            <div class="col-sm-9">
                                <input type="file" class="form-control" id="customervisitreport" name="customervisitreport" accept=".pdf, .jpg, .jpeg, .png" >
                                <div id="previewContainer1" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Recommend for Service Letter *</label>
                            <div class="col-sm-9">
                                <label class="form-check-label mt-2 me-2">
                                <input type="radio" class="form-check-input" name="rsv" id="rsvn" value="0" required>
                                    No
                                </label>
                                <label class="form-check-label mt-2">
                                <input type="radio" class="form-check-input" name="rsv" id="rsvy" value="1">
                                    Yes
                                </label>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Rejoin or not *</label>
                            <div class="col-sm-9">
                                <label class="form-check-label mt-2 me-2">
                                <input type="radio" class="form-check-input" name="rejoin" id="rejoinn" value="0" required>
                                    No
                                </label>
                                <label class="form-check-label mt-2">
                                <input type="radio" class="form-check-input" name="rejoin" id="rejoiny" value="1">
                                    Yes
                                </label>
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
            "url": "../../back/clearance-fetch.php",
            "type": "POST"
        },
        "columns": [
            { "data": "row_id" },
            { "data": "req_id" },
            { "data": "ini_name" },
            { "data": "resignation_date" },
            { "data": "epf_no" },
            { "data": "code" },
            { "data": "notes" },
            { "data": "department" },
            { "data": "progress" },
            { "data": "action" }
        ]
    });
});



// ✅ Keep `emp_set` outside too
function data_set(Id) {
    $.ajax({
        url: '../../back/clearance-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: Id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('.modal-title').html('Edit Clearance');
                $('#edit_id').val(response.data.cl_req_id);
                $('#resignation_date').val(response.data.resignation_date);
                $("input[name='rsv'][value='" + response.data.service_letter_recommendation + "']").prop("checked", true);
                $("input[name='rejoin'][value='" + response.data.rejoin_or_not + "']").prop("checked", true);

                if (response.data?.letter_location) {
                    previewFile(response.data.letter_location);
                }

                if (response.data?.cvr_location) {
                    previewFile1(response.data.cvr_location);
                }
                
                $('#searchEmployee').val(response.data.title+' '+response.data.name_with_initials);
                $('#reporting').html('<option value="'+response.data.emp_id+'" selected>'+response.data.title+' '+response.data.name_with_initials+'</option>');
                $('#myModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch request details: ' + response.message;
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


function add() {
        $('.modal-title').html('Add Clearance');
        $('#edit_id').val('');
        $('#resignation_date').val('');
        $('#resignationLetter').val('');
        $('#customervisitreport').val('');
        $('#searchEmployee').val('');
        $('#reporting').val('');
        $('#previewContainer1').html('');
        $('#previewContainer').html('');
        $("input[name='rsv']").prop("checked", false);
        $("input[name='rejoin']").prop("checked", false);
}


$(document).ready(function(){
    $("#searchEmployee").on("keyup", function() {
        let searchText = $(this).val();
        if(searchText.length > 3) { // Trigger search on 2+ characters
            $.ajax({
                url: "../../back/clearance-manage.php", 
                method: "POST",
                data: {filteredEmps: searchText},
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


});


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

        
    });

    
</script>

<script>
    function previewFile(url) {
        const fileInput = document.getElementById('resignationLetter');
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
            link.textContent = 'View Resignation Letter (PDF)';
            previewContainer.appendChild(link);
        } else {
            previewContainer.innerHTML = '<p>Unsupported file format.</p>';
        }
    }

    function previewFile1(url) {
        const fileInput = document.getElementById('customervisitreport');
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
            link.textContent = 'View Customer Visit Report(PDF)';
            previewContainer.appendChild(link);
        } else {
            previewContainer.innerHTML = '<p>Unsupported file format.</p>';
        }
    }

</script>
</body>

</html>