<nav class="sidebar sidebar-offcanvas" id="sidebar">
      <ul class="nav">
        <li class="nav-item sidebar-category">
          <p></p>
          <span></span>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../../pages/general/dashboard.php">
            <i class="mdi mdi-view-quilt menu-icon"></i>
            <span class="menu-title">Dashboard</span>
          </a>
        </li>
        <!-- <li class="nav-item sidebar-category">
          <p>Components</p>
          <span></span>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
            <i class="mdi mdi-palette menu-icon"></i>
            <span class="menu-title">UI Elements</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="ui-basic">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"> <a class="nav-link" href="pages/ui-features/buttons.html">Buttons</a></li>
              <li class="nav-item"> <a class="nav-link" href="pages/ui-features/typography.html">Typography</a></li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="pages/forms/basic_elements.html">
            <i class="mdi mdi-view-headline menu-icon"></i>
            <span class="menu-title">Form elements</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="pages/charts/chartjs.html">
            <i class="mdi mdi-chart-pie menu-icon"></i>
            <span class="menu-title">Charts</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="pages/tables/basic-table.html">
            <i class="mdi mdi-grid-large menu-icon"></i>
            <span class="menu-title">Tables</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="pages/icons/mdi.html">
            <i class="mdi mdi-emoticon menu-icon"></i>
            <span class="menu-title">Icons</span>
          </a>
        </li>
        <li class="nav-item sidebar-category">
          <p>Pages</p>
          <span></span>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#auth" aria-expanded="false" aria-controls="auth">
            <i class="mdi mdi-account menu-icon"></i>
            <span class="menu-title">User Pages</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="auth">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"> <a class="nav-link" href="pages/samples/login.html"> Login </a></li>
              <li class="nav-item"> <a class="nav-link" href="pages/samples/login-2.html"> Login 2 </a></li>
              <li class="nav-item"> <a class="nav-link" href="pages/samples/register.html"> Register </a></li>
              <li class="nav-item"> <a class="nav-link" href="pages/samples/register-2.html"> Register 2 </a></li>
              <li class="nav-item"> <a class="nav-link" href="pages/samples/lock-screen.html"> Lockscreen </a></li>
            </ul>
          </div>
        </li> -->

        <?php
          if (checkAccess([1,2,3])) {
        ?>
        <li class="nav-item sidebar-category">
          <p>Clearance</p>
          <span></span>
        </li> 

        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#clearance" aria-expanded="false" aria-controls="clearance">
            <i class="mdi mdi-account-settings menu-icon"></i>
            <span class="menu-title">Clearance</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="clearance">
            <ul class="nav flex-column sub-menu">
              <!-- <li class="nav-item"> <a class="nav-link" href="../../pages/clearance/summary.php"> Summary</a></li> -->
              <?php
                if (checkAccess([1,2,3])) {
                  if (in_array($_SESSION['u_roll'], [1,4]) || in_array($_SESSION['ulvl'], [1,2])) {//HOD and CRO || Admin and HR
              ?>
              <li class="nav-item"> 
                <a class="nav-link" href="../../pages/clearance/clearance-list.php"> Clearance List 
                <?php
                    if (in_array($_SESSION['ulvl'], [1, 2])) { // Admin and HR
                        $query = "SELECT count(*) as lcount FROM cl_requests_steps 
                        INNER JOIN cl_requests ON cl_requests.cl_req_id = cl_requests_steps.request_id
                        WHERE cl_requests_steps.step='0' AND cl_requests_steps.is_complete !='1' AND cl_requests.status='1' ";
                        $result = $conn->query($query); // Use $query instead of $sql

                        if ($result) {
                            $row = $result->fetch_assoc();
                            if($row['lcount']>0){
                            ?>
                            <span class="status-dot red ms-1 mb-2">
                                <small><?= htmlspecialchars($row['lcount'] ?? 0) ?></small>
                            </span>
                            <?php
                        }
                        } 
                    }
                ?>

                  
                </a>
              </li>
              <?php
                }
                }
              ?>  
              <?php
                if (checkAccess([1,2,3])) {
              ?>
              <li class="nav-item"> 
                  <a class="nav-link" href="../../pages/clearance/clearance-allocated.php"> Clearance to Attend 
                  <?php
                        $uid = $_SESSION['uid'];
                        $count = 0;
                        $query = "SELECT cl_requests.* FROM cl_requests  
                        INNER JOIN cl_requests_steps ON cl_requests.cl_req_id = cl_requests_steps.request_id
                        WHERE cl_requests_steps.step !='0' AND cl_requests_steps.is_complete !='1' AND cl_requests.status='1' AND 
                        (cl_requests_steps.assigned_preparer_user_id = $uid OR cl_requests_steps.assigned_checker_user_id = $uid OR cl_requests_steps.assigned_approver_user_id = $uid)
                        AND cl_requests_steps.step = (
                            SELECT MIN(step) FROM cl_requests_steps 
                            WHERE cl_requests_steps.request_id = cl_requests.cl_req_id
                            AND (cl_requests_steps.is_complete = 0 OR cl_requests_steps.is_complete = 2)
                            AND cl_requests_steps.step != '0'
                        )
                        GROUP BY cl_requests.cl_req_id";
                        $result = $conn->query($query); 

                        if ($result) {
                          if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                              $req_id = $row['cl_req_id'];

                              $sql = "SELECT * FROM cl_requests_steps WHERE cl_requests_steps.step !='0' AND 
                              cl_requests_steps.is_complete !='1'
                              AND 
                              (cl_requests_steps.assigned_preparer_user_id = $uid OR cl_requests_steps.assigned_checker_user_id = $uid OR cl_requests_steps.assigned_approver_user_id = $uid)
                              AND request_id = $req_id ORDER BY step ASC LIMIT 1";
                              $result_sql = $conn->query($sql);

                              if ($result_sql) {
                                if ($result_sql->num_rows > 0) {
                                  while ($row_sql = $result_sql->fetch_assoc()) {
                                    
                                      if ($row_sql['prepare_check_approve']=='0' && $row_sql['assigned_preparer_user_id']==$uid) {
                                        $count++;
                                        
                                      }

                                      if ($row_sql['prepare_check_approve']=='1' && $row_sql['assigned_checker_user_id']==$uid
                                      || ($row_sql['prepare_check_approve']=='0' && empty($row_sql['assigned_preparer_user_id']) && $row_sql['assigned_checker_user_id']==$uid) ) {
                                        $count++;
                                        
                                      }

                                      if ($row_sql['prepare_check_approve']=='2' && $row_sql['assigned_approver_user_id']==$uid
                                      || (($row_sql['prepare_check_approve']=='0' || $row_sql['prepare_check_approve']=='1') && empty($row_sql['assigned_preparer_user_id']) && empty($row_sql['assigned_checker_user_id']) && $row_sql['assigned_approver_user_id']==$uid)
                                      || (($row_sql['prepare_check_approve']=='1') && empty($row_sql['assigned_checker_user_id']) && $row_sql['assigned_approver_user_id']==$uid)) {
                                        $count++;
                                        
                                      }

                                  }
                                }
                              }

                            }
                          }

                          if ($count>0) {
                            ?>
                            <span class="status-dot red ms-1 mb-2">
                                <small><?= htmlspecialchars($count ?? 0) ?></small>
                            </span>
                            <?php
                          }
                        } 
                ?>
                </a>
              </li>
              <?php
                }
              ?>

              <?php
                if (checkAccess([1,2,3])) {
                  
                  if (in_array('FINANCE', explode(',', $_SESSION['bd_id'])) || checkAccess([1,2])) {//Finance
              ?>
              <li class="nav-item"> 
                  <a class="nav-link" href="../../pages/clearance/clearance-final.php"> Final Clearance (FD)
                  <?php
                        $count = 0;
                        $query = "SELECT * FROM cl_requests WHERE cl_requests.status='1' AND cl_requests.is_complete ='0' AND cl_requests.allocated_to_finance='1'";
                        $result = $conn->query($query); 

                        if ($result) {
                          if ($result->num_rows > 0) {
                            $count = $result->num_rows;
                          }

                          if ($count>0) {
                            ?>
                            <span class="status-dot red ms-1 mb-2">
                                <small><?= htmlspecialchars($count ?? 0) ?></small>
                            </span>
                            <?php
                          }
                        } 
                ?>
                </a>
              </li>
              <?php
                  }
                }
              ?>
            </ul>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#clearance-reports" aria-expanded="false" aria-controls="clearance-reports">
            <i class="mdi mdi-account-settings menu-icon"></i>
            <span class="menu-title">Clearance Reports</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="clearance-reports">
            <ul class="nav flex-column sub-menu">

              <?php
                if (checkAccess([1,2,3])) {
              ?>
              <li class="nav-item"> 
                <a class="nav-link" href="../../pages/clearance/individual-report.php"> Individual </a>
              </li>
              <?php
                }
              ?>  

              <?php
                if (checkAccess([1,2])) {
              ?>
              <li class="nav-item"> 
                <a class="nav-link" href="../../pages/reports/clearance-pending.php"> Pending </a>
              </li>
              <?php
                }
              ?>

            </ul>
          </div>
        </li>

        <?php
          }
        ?>

        <?php
          if (checkAccess([1,2])) {
        ?>
        <li class="nav-item sidebar-category">
          <p>Employees Manage</p>
          <span></span>
        </li> 

        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#employees" aria-expanded="false" aria-controls="employees">
            <i class="mdi mdi-account-settings menu-icon"></i>
            <span class="menu-title">Employees</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="employees">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"> <a class="nav-link" href="../../pages/employees/employee-list.php"> Employee List</a></li>
            </ul>
          </div>
        </li>

        <?php
          }
        ?>
        <?php
          if (checkAccess([1])) {
        ?>
          <li class="nav-item sidebar-category">
            <p>System Users</p>
            <span></span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../../pages/users/user-list.php">
            <i class="mdi mdi-account menu-icon"></i>
              <span class="menu-title">Users</span>
            </a>
          </li>

        <?php
          }
        ?>


        <?php
          if (checkAccess([1,2])) {
        ?>
        <li class="nav-item sidebar-category">
          <p>Settings</p>
          <span></span>
        </li>

        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#clsettings" aria-expanded="false" aria-controls="settings">
            <i class="mdi mdi-settings menu-icon"></i>
            <span class="menu-title">Clearance Settings</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="clsettings">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"> <a class="nav-link" href="../../pages/settings/cl-cash-list.php"> Monetary Items </a></li>
              <li class="nav-item"> <a class="nav-link" href="../../pages/settings/cl-physical-list.php"> Non Monetary Items </a></li>
            </ul>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#settings" aria-expanded="false" aria-controls="settings">
            <i class="mdi mdi-settings menu-icon"></i>
            <span class="menu-title">System Settings</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="settings">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"> <a class="nav-link" href="../../pages/settings/company-list.php"> Companies </a></li>
              <li class="nav-item"> <a class="nav-link" href="../../pages/settings/department-list.php"> Branch/Dept </a></li>
              <li class="nav-item"> <a class="nav-link" href="../../pages/settings/designation-list.php"> Designations </a></li>
              <li class="nav-item"> <a class="nav-link" href="../../pages/settings/type-list.php"> Emp. Categories </a></li>
            </ul>
          </div>
        </li>

        <?php
          }
        ?>

          <li class="nav-item sidebar-category">
            <p>Profile</p>
            <span></span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../../pages/users/change-password.php">
            <i class="mdi mdi-account menu-icon"></i>
              <span class="menu-title">Change Password</span>
            </a>
          </li>

        <li class="nav-item">
          <a class="nav-link" href="../general/logout.php">
            <button class="btn bg-danger btn-sm menu-title"> <i class="mdi mdi-logout"></i> Log Out</button>
          </a>
        </li>
      </ul>
    </nav>