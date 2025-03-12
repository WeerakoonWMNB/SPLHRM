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
                      <p class="card-title"><h4 id="title-name">Employee Category List</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add_emp_type()"><i class="mdi mdi-playlist-plus me-1"></i> Add Employee Category</button>
                        <?php 
                            $stmt = $conn->prepare("SELECT * FROM emp_category WHERE status = 1");
     
                            $stmt->execute(); // Execute the query
                            $emp_categories = $stmt->get_result(); // Fetch the result

                        ?>
                            <table id="myTable" class="display" width="100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Category Code</th>
                                            <th>Category Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $i = 1;
                                            foreach($emp_categories as $emp_category)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $emp_category['cat_code'] ?> </td>
                                                    <td><?= $emp_category['cat_name'] ?></td>
                                                    <td>
                                                    <form method="POST" action="../../back/type-manage.php" >
                                                        <input type="hidden" name="cat_id" value="<?= $emp_category['cat_id'] ?>">
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="type_set(<?= $emp_category['cat_id'] ?>)"><i class="mdi mdi-playlist-check"></i></button>
                                                    
                                                        <button 
                                                        type="submit" 
                                                        name="delete_type" 
                                                        class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this category?');">
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
        <h4 class="modal-title">Add Employee Category</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <form id="myform" method="POST" action="../../back/type-manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Category Code *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="category_code" name="category_code" placeholder="back-office" required>
                            </div>
                        </div>
                                            
                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Category Name *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="category_name" name="category_name" placeholder="Back Office" required>
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

        function type_set(cat_id) {
    $.ajax({
        url: '../../back/type-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: cat_id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('.modal-title').html('Edit Employee Category');
                $('#edit_id').val(response.data.cat_id );
                $('#category_code').val(response.data.cat_code);
                $('#category_code').attr('readonly', true);
                $('#category_name').val(response.data.cat_name);
                $('#myModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch category details: ' + response.message;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching category details:', error);
        }
    });
}

function add_emp_type() {
                $('.modal-title').html('Add Employee Category');
                $('#edit_id').val('');
                $('#category_code').val('');
                $('#category_code').attr('readonly', false);
                $('#category_name').val('');
}
  </script>
</body>

</html>