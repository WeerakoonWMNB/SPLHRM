<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<?php
        include "../../back/credential-check.php";
        if (!checkAccess([1])) {
            header("Location: ../general/dashboard.php");
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
                      <p class="card-title"><h4 id="title-name">Material Items</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add_branch()"><i class="mdi mdi-playlist-plus me-1"></i> Add Item</button>
                        <?php 
                            $stmt = $conn->prepare("SELECT cl_physical_items.*,branch_departments.bd_name FROM cl_physical_items 
                            LEFT JOIN branch_departments ON branch_departments.bd_id = cl_physical_items.bd_id 
                            WHERE cl_physical_items.status = 1");
     
                            $stmt->execute(); // Execute the query
                            $branches = $stmt->get_result(); // Fetch the result

                        ?>
                            <table id="myTable" class="display" width="100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Item Name</th>
                                            <th>Type</th>
                                            <th>Branch/Department Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $i = 1;
                                            foreach($branches as $branch)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $branch['item_name'] ?> </td>
                                                    <td><?php if ($branch['item_type'] == 1) {
                                                        echo "Receive";
                                                    }
                                                    if ($branch['item_type'] == 2) {
                                                        echo "Issue";
                                                    }
                                                      ?></td>
                                                    <td><?php if ($branch['bd_id'] == 9999999) {
                                                        echo "All Branches";
                                                    } 
                                                    else {
                                                        echo $branch['bd_name'];
                                                    } ?></td>
                                                    <td>
                                                        <form method="POST" action="../../back/cl-physical-manage.php" style="display: flex; gap: 5px;">
                                                            <input type="hidden" name="cl_physical_item_id" value="<?= $branch['cl_physical_item_id'] ?>">
                                                            <button type="button" class="btn btn-warning btn-sm" onclick="branch_set(<?= $branch['cl_physical_item_id'] ?>)">
                                                                <i class="mdi mdi-playlist-check"></i>
                                                            </button>

                                                            <button 
                                                                type="submit" 
                                                                name="delete_branch" 
                                                                class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Are you sure you want to delete this item?');">
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
        <h4 class="modal-title">Add Material Items</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <form id="myform" method="POST" action="../../back/cl-physical-manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Branch/Department *</label>
                            <div class="col-sm-9">
                                <?php
                                    $stmt = $conn->prepare("SELECT * FROM branch_departments WHERE status = 1 AND is_branch=0 GROUP BY bd_id, is_branch ORDER BY is_branch ASC"); // Fetch all branchesi
                                    $stmt->execute(); // Execute the query
                                    $departments = $stmt->get_result(); // Fetch the result
                                ?>
                            <select class="form-control" id="department" name ="department" required>
                                <option value=''>Select</option>
                                <?php
                                    foreach($departments as $department)
                                    {
                                        ?>
                                        <option value="<?= $department['bd_id'] ?>"><?= $department['bd_name'] ?></option>
                                        <?php
                                    }
                                ?>
                                <option value='9999999'>All Branches</option>
                            </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Item Type *</label>
                            <div class="col-sm-9">
                            <select class="form-control" id="item_type" name ="item_type" required>
                                <option value=''>Select</option>
                                <option value="1">Receive</option>  
                                <option value="2">Issue</option>
                            </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Item Name *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Payment for company T-Shirt" required>
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

        function branch_set(branchId) {
    $.ajax({
        url: '../../back/cl-physical-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: branchId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('.modal-title').html('Edit Material Items');
                $('#edit_id').val(response.data.cl_physical_item_id);
                $('#department').val(response.data.bd_id);
                $('#item_name').val(response.data.item_name);
                $('#item_type').val(response.data.item_type);
                $('#myModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch Material items details: ' + response.message;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching items details:', error);
        }
    });
}

function add_branch() {
      $('.modal-title').html('Add Material Items');
      $('#edit_id').val('');
      $('#department').val('');
      $('#item_name').val('');
}
  </script>
</body>

</html>