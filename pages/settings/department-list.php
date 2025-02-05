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
                      <p class="card-title"><h4 id="title-name">Branch/Department List</h4></p>
                      <hr id="title-hr">

                      <button type="button" data-bs-toggle="modal" data-bs-target="#myModal" class="btn btn-success" onclick="add_branch()"><i class="mdi mdi-playlist-plus me-1"></i> Add Branch/Department</button>
                        <?php 
                            $stmt = $conn->prepare("SELECT * FROM branch_departments 
                            INNER JOIN companies ON branch_departments.company_code = companies.company_code 
                            WHERE branch_departments.status = 1");
     
                            $stmt->execute(); // Execute the query
                            $branches = $stmt->get_result(); // Fetch the result

                        ?>
                            <table id="myTable" class="display" width="100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Company</th>
                                            <th>Branch/Department Code</th>
                                            <th>Branch/Department Name</th>
                                            <th>Cluster</th>
                                            <th>Max Clearance Dates (back office)</th>
                                            <th>Max Clearance Dates (marketing)</th>
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
                                                    <td><?= $branch['comany_name'] ?> </td>
                                                    <td><?= $branch['bd_code'] ?></td>
                                                    <td><?= $branch['bd_name'] ?></td>
                                                    <td><?= $branch['cluster'] ?></td>
                                                    <td><?= $branch['seiling_dates_for_backoffice'] ?></td>
                                                    <td><?= $branch['ceiling_dates_for_marketing'] ?></td>
                                                    <td>
                                                        <form method="POST" action="../../back/branch-manage.php" style="display: flex; gap: 5px;">
                                                            <input type="hidden" name="bd_id" value="<?= $branch['bd_id'] ?>">
                                                            <button type="button" class="btn btn-warning btn-sm" onclick="branch_set(<?= $branch['bd_id'] ?>)">
                                                                <i class="mdi mdi-playlist-check"></i>
                                                            </button>

                                                            <button 
                                                                type="submit" 
                                                                name="delete_branch" 
                                                                class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Are you sure you want to delete this branch/department?');">
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
        <h4 class="modal-title">Add Branch/Department</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" ></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <form id="myform" method="POST" action="../../back/branch-manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" id="edit_id">

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
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Is Branch *</label>
                            <div class="col-sm-9">

                            <select class="form-control" id="is_branch" name ="is_branch" required>
                                <option value=''>Select</option>
                                <option value="1">Yes</option>  
                                <option value="0">No</option>
                            </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Cluster </label>
                            <div class="col-sm-9">

                            <select class="form-control" id="cluster" name ="cluster">
                                <option value=''>Select</option>
                                <option value="1">Cluster 1</option>  
                                <option value="2">Cluster 2</option>
                                <option value="3">Cluster 3</option>
                                <option value="4">Cluster 4</option>
                            </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-3 col-form-label">Branch/Department Code *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="branch_code" name="branch_code" placeholder="hr" required>
                            </div>
                        </div>
                                            
                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Branch/Department Name *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="branch_name" name="branch_name" placeholder="Human Resource" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Max Clearance Dates (back office) *</label>
                            <div class="col-sm-9">
                                <input type="number" min="1" class="form-control" id="max_dates_office" name="max_dates_office" placeholder="3" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputPassword3" class="col-sm-3 col-form-label">Max Clearance Dates (marketing) *</label>
                            <div class="col-sm-9">
                                <input type="number" min="1" class="form-control" id="max_dates_marketing" name="max_dates_marketing" placeholder="3" required>
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
        url: '../../back/branch-manage.php', // Replace with your endpoint that fetches user details
        type: 'POST',
        data: { search_id: branchId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the form fields with the fetched data
                $('.modal-title').html('Edit Branch/Department');
                $('#edit_id').val(response.data.bd_id);
                $('#branch_code').val(response.data.bd_code);
                $('#branch_code').attr('readonly', true);
                $('#branch_name').val(response.data.bd_name);
                $('#max_dates_office').val(response.data.seiling_dates_for_backoffice);
                $('#max_dates_marketing').val(response.data.ceiling_dates_for_marketing);
                $('#company').val(response.data.company_code);
                $('#is_branch').val(response.data.is_branch);
                $('#cluster').val(response.data.cluster);
                $('#myModal').modal('show');
            } else {              
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = 'Failed to fetch branch/department details: ' + response.message;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching branch/department details:', error);
        }
    });
}

function add_branch() {
                $('.modal-title').html('Add Branch/Department');
                $('#edit_id').val('');
                $('#branch_code').val('');
                $('#branch_code').attr('readonly', false);
                $('#branch_name').val('');
                $('#max_dates_office').val('');
                $('#max_dates_marketing').val('');
                $('#company').val(''); 
                $('#cluster').val('');
                $('#is_branch').val('');
}
  </script>
</body>

</html>