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
                      <p class="card-title"><h4 id="title-name">Designation List</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add_designation()"><i class="mdi mdi-playlist-plus me-1"></i> Add Designation</button>
                        <?php 
                            $stmt = $conn->prepare("SELECT * FROM designations WHERE status = 1");
     
                            $stmt->execute(); // Execute the query
                            $designations = $stmt->get_result(); // Fetch the result

                        ?>
                            <table id="myTable" class="display" width="100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Designation Code</th>
                                            <th>Designation</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $i = 1;
                                            foreach($designations as $designation)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $designation['desig_code'] ?> </td>
                                                    <td><?= $designation['designation'] ?></td>
                                                    <td>
                                                    <form method="POST" action="../../back/designation-manage.php" >
                                                        <input type="hidden" name="desig_id" value="<?= $designation['desig_id'] ?>">
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="designation_set(<?= $designation['desig_id'] ?>)"><i class="mdi mdi-playlist-check"></i></button>
                                                    
                                                        <button 
                                                        type="submit" 
                                                        name="delete_designation" 
                                                        class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this designation?');">
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
        <h4 class="modal-title">Add Designation</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <form id="myform" method="POST" action="../../back/designation-manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Designation Code *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="designation_code" name="designation_code" placeholder="senior-executive" required>
                            </div>
                        </div>
                                            
                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Designation Name *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="designation_name" name="designation_name" placeholder="Senior Executive" required>
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

        function designation_set(designationId) {
    $.ajax({
        url: '../../back/designation-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: designationId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('.modal-title').html('Edit Designation');
                $('#edit_id').val(response.data.desig_id );
                $('#designation_code').val(response.data.desig_code);
                $('#designation_code').attr('readonly', true);
                $('#designation_name').val(response.data.designation);
                $('#myModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch designation details: ' + response.message;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching designation details:', error);
        }
    });
}

function add_designation() {
                $('.modal-title').html('Add Designation');
                $('#edit_id').val('');
                $('#designation_code').val('');
                $('#designation_code').attr('readonly', false);
                $('#designation_name').val('');
}
  </script>
</body>

</html>