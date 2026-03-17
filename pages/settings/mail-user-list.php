<?php session_start(); ?>
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
                      <p class="card-title"><h4 id="title-name">Resign Mail Receivers List</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add_user()"><i class="mdi mdi-playlist-plus me-1"></i> Add User</button>
                        <?php 
                            $stmt = $conn->prepare("SELECT rl.id, bd.company_code, bd.bd_name, u.name, u.username FROM resign_mail_receivers_list rl
                            INNER JOIN users u ON rl.user_id = u.user_id 
                            INNER JOIN branch_departments bd ON bd.bd_code = u.bd_id
                            WHERE u.is_active = 1");
     
                            $stmt->execute(); // Execute the query
                            $companies = $stmt->get_result(); // Fetch the result

                        ?>
                            <table id="myTable" class="display" width="100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User Name</th>
                                            <th>E-mail</th>
                                            <th>Company Code</th>
                                            <th>Department</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $i = 1;
                                            foreach($companies as $company)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $company['name'] ?> </td>
                                                    <td><?= $company['username'] ?></td>
                                                    <td><?= $company['company_code'] ?></td>
                                                    <td><?= $company['bd_name'] ?></td>
                                                    <td>
                                                    <form method="POST" action="../../back/resign-mail-user-manage.php" >
                                                        <input type="hidden" name="rl_id" value="<?= $company['id'] ?>">
                                                        
                                                        <button 
                                                        type="submit" 
                                                        name="delete_rl" 
                                                        class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this user?');">
                                                        <i class="mdi mdi-playlist-remove"></i>
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
        <h4 class="modal-title">Add User</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <form id="myform" method="POST" action="../../back/resign-mail-user-manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">User *</label>
                            <div class="col-sm-9">

                                <select class="form-control" name="user_id" id="user_id" style="width:100%" required>
                                    <option value="">Select User</option>

                                    <?php
                                    $sql = "SELECT u.user_id, u.name, bd.bd_name 
                                            FROM users u
                                            LEFT JOIN resign_mail_receivers_list rl 
                                                ON rl.user_id = u.user_id
                                            JOIN branch_departments bd 
                                                ON bd.bd_code = u.bd_id
                                            WHERE u.is_active = 1
                                            AND rl.user_id IS NULL
                                            ORDER BY u.name";

                                    $result = mysqli_query($conn,$sql);

                                    while($row = mysqli_fetch_assoc($result)){
                                        echo '<option value="'.$row['user_id'].'">'.$row['name'].' ('.$row['bd_name'].')</option>';
                                    }
                                    ?>

                                </select>

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



function add_company() {
                $('.modal-title').html('Add User');
                $('#edit_id').val('');
                $('#user_id').val('');
}
  </script>
</body>

</html>