<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<?php
        include "../../back/credential-check.php";
        if (!checkAccess([1])) {
            header("Location: index.php");
            exit();
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
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Name *</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="name" name="name" placeholder="John" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">User Level *</label>
                            <div class="col-sm-10">
                                <?php
                                    $stmt = $conn->prepare("SELECT * FROM user_levels WHERE is_active = 1");
                                    $stmt->execute(); // Execute the query
                                    $user_levels = $stmt->get_result(); // Fetch the result
                                ?>
                            <select class="form-control" id="exampleFormControlSelect2" name ="user_level" required>
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
                            <label for="inputPassword3" class="col-sm-2 col-form-label">Username *</label>
                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="username" name="username" placeholder="someone@sadaharitha.com" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">Password *</label>
                            <div class="col-sm-10">
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
                $('#exampleFormControlSelect2').val(response.data.user_level); // Set the selected value
                $('#username').val(response.data.username);
                $('#password').val(response.data.password);
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
                $('#exampleFormControlSelect2').val(''); // Set the selected value
                $('#username').val('');
                $('#password').val('');
}
  </script>
</body>

</html>