<!-- Required meta tags -->
<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>HRM</title>
  <!-- base:css -->
  <link rel="stylesheet" href="../../vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- plugin css for this page -->
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="../../css/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="../../images/favicon.png" />
  <link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.dataTables.css" />

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

      
  <?php date_default_timezone_set("Asia/Colombo"); ?>

  <script>
        // Display custom alert
        document.addEventListener("DOMContentLoaded", () => {
            const errorMessage = "<?php echo isset($_SESSION['error']) ? $_SESSION['error'] : ''; ?>";
            if (errorMessage) {
                const alertBox = document.getElementById('customAlert');
                alertBox.textContent = errorMessage;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);

                <?php unset($_SESSION['error']); ?>
            }

            const successMessage = "<?php echo isset($_SESSION['success']) ? $_SESSION['success'] : ''; ?>";
            if (successMessage) {
              
                const alertBox = document.getElementById('customAlertSuccess');
                alertBox.textContent = successMessage;
                alertBox.style.display = 'block';

                // Hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);

                <?php unset($_SESSION['success']); ?>
            }
        });
    </script>

<style>
        .custom-alert {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2050;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }

        .custom-alert-success {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2050;
            padding: 15px;
            border-radius: 5px;
            background-color:rgb(165, 239, 180);
            color:rgb(8, 100, 26);
            border: 1px solid rgb(165, 239, 180);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>