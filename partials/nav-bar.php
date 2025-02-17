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
          if (checkAccess([1])) {
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
              <li class="nav-item"> <a class="nav-link" href="../../pages/clearance/summary.php"> Summary</a></li>
              <li class="nav-item"> <a class="nav-link" href="../../pages/clearance/clearance-list.php"> Clearance List</a></li>
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
          if (checkAccess([1])) {
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
              <li class="nav-item"> <a class="nav-link" href="../../pages/settings/cl-physical-list.php"> Material Items </a></li>
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

        <li class="nav-item">
          <a class="nav-link" href="../general/logout.php">
            <button class="btn bg-danger btn-sm menu-title"> <i class="mdi mdi-logout"></i> Log Out</button>
          </a>
        </li>
      </ul>
    </nav>