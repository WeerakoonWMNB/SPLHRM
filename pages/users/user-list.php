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
                      <p class="card-title"><h4 id="title-name">System Users List</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add_user()"><i class="mdi mdi-account-plus me-1"></i> Add User</button>
                        <?php 
                            $stmt = $conn->prepare("SELECT users.*, user_levels.level_name 
                            FROM users 
                            INNER JOIN user_levels 
                            ON users.user_level = user_levels.level_id 
                            WHERE users.is_active = 1");
     
                            $stmt->execute(); // Execute the query
                            $users = $stmt->get_result(); // Fetch the result

                        ?>
                            <table id="myTable" class="display" width="100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>User Level</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $i = 1;
                                            foreach($users as $user)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $user['name'] ?> </td>
                                                    <td><?= $user['username'] ?></td>
                                                    <td><?= $user['level_name'] ?></td>
                                                    <td>
                                                    <form method="POST" action="../../back/user-manage.php" >
                                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="user_set(<?= $user['user_id'] ?>)"><i class="mdi mdi-account-convert"></i></button>
                                                    
                                                        <button 
                                                        type="submit" 
                                                        name="delete_user" 
                                                        class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this user?');">
                                                        <i class="mdi mdi-account-remove"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    </td>
                                                </tr>
                                        <?php
                                            $i++;
                                            } 
                                            ?>
                                    </tbody>
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
        <h4 class="modal-title">Add System User</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <form id="myform" method="POST" action="../../back/user-manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Name *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="name" name="name" placeholder="John" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">User Level *</label>
                            <div class="col-sm-9">
                                <?php
                                    $stmt = $conn->prepare("SELECT * FROM user_levels WHERE is_active = 1");
                                    $stmt->execute(); // Execute the query
                                    $user_levels = $stmt->get_result(); // Fetch the result
                                ?>
                            <select class="form-control" id="user_level" name ="user_level" required>
                                <option value=''>Select</option>
                                <?php
                                    foreach($user_levels as $user_level)
                                    {
                                        ?>
                                        <option value="<?= $user_level['level_id'] ?>"><?= $user_level['level_name'] ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">User Role *</label>
                            <div class="col-sm-9">
                               
                            <select class="form-control" id="process_level" name ="process_level" required>
                                <option value=''>Select</option>
                                <option value="1">HOD</option>
                                <option value="2">RSM</option>
                                <option value="3">BSM</option>
                                <option value="4">CRO</option>
                                <option value="5">User</option>
                            </select>
                            </div>
                        </div>
                             
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Branch/Department *</label>
                            <?php
                                    $stmt = $conn->prepare("SELECT * FROM branch_departments WHERE status = 1 
                                    GROUP BY bd_id,is_branch ORDER BY is_branch ASC, bd_name ASC");
                                    $stmt->execute(); // Execute the query
                                    $branches = $stmt->get_result(); // Fetch the result
                                ?>
                            <div class="col-sm-9">
                            <select class="form-control" id="multiSelect" name ="branch[]" multiple required>
                                <?php
                                    foreach($branches as $branch)
                                    {
                                        ?>
                                        <option value="<?= $branch['bd_code'] ?>"><?= $branch['bd_name'] ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="reporting" class="col-sm-3 col-form-label">Tag Emp.</label>
                            <div class="col-sm-9 position-relative">
                                <input type="text" id="searchEmployee" class="form-control" placeholder="Search Employee">
                                <div id="employeeList" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                                
                                <select class="form-control mt-2" id="emp" name="emp" style="display: none;">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Username *</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control" id="username" name="username" placeholder="someone@sadaharitha.com" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Password *</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="password" name="password" placeholder="*****" required>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button form="myform" type="submit" class="btn btn-success">Submit</button>
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<div id="customAlert" class="custom-alert"></div>
<div id="customAlertSuccess" class="custom-alert-success"></div>
  <?php require '../../partials/scripts.php'; ?>
  <script>
    $(document).ready( function () {
                new DataTable('#myTable', {
                    scrollX: true
                });

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
                  $("#emp").html(`<option value="${employeeId}" selected>${employeeName}</option>`);
              });

              // Hide the suggestion list when clicking outside
              $(document).click(function(e) {
                  if (!$(e.target).closest("#searchEmployee, #employeeList").length) {
                      $("#employeeList").hide();
                  }
              });
    
        });

        function user_set(userId) {

    $.ajax({
        url: '../../back/user-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: userId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('.modal-title').html('Edit System User');
                $('#edit_id').val(response.data.user_id);
                $('#name').val(response.data.name);
                $('#user_level').val(response.data.user_level); // Set the selected value
                $('#process_level').val(response.data.process_level);
                $('#searchEmployee').val(response.data.employee_name);
                $('#emp').html('<option value="'+response.data.emp_id+'" selected>'+response.data.employee_name+'</option>');
                $('#username').val(response.data.username);
                $('#password').val(response.data.password);
                
                let savedBranches = response.data.bd_id ? response.data.bd_id.split(',') : [];

                // Remove existing selected items
                window.choicesInstance.removeActiveItems();

                // Set new selected values
                savedBranches.forEach(branch => {
                    window.choicesInstance.setChoiceByValue(branch);
                });

                $('#myModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch user details: ' + response.message;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching user details:', error);
        }
    });
}

function add_user() {
                $('.modal-title').html('Add System User');
                $('#edit_id').val('');
                $('#name').val('');
                $('#user_level').val(''); // Set the selected value
                $('#process_level').val('');
                $('#multiSelect').val('');
                // Remove existing selected items
                window.choicesInstance.removeActiveItems();
                $('#emp').val('');
                $('#username').val('');
                $('#password').val('');
}
  </script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Get the select element
    const multiSelect = document.getElementById('multiSelect');

    // Initialize Choices.js globally
    window.choicesInstance = new Choices(multiSelect, {
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholderValue: 'Select options...',
        noResultsText: 'No results found',
        itemSelectText: 'Click to select',
    });

  });
</script>

</body>

</html>