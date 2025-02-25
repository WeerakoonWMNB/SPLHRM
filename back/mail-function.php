<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require '../vendor/autoload.php';

function sendMail($mailids, $mailheading, $mailbody)
{
    include 'connection/connection.php';

    if ($_SERVER['HTTP_HOST'] === 'hrinfo.sadaharitha.com' || $_SERVER['HTTP_HOST'] === 'localhost') {
        
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = 0;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'nish1993522@gmail.com';                     //SMTP username
            $mail->Password   = 'vwfz iuwu mvbf rawa';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('nish1993522@gmail.com', 'HRM System (do not reply)');
            foreach ($mailids as $mailid) {
                $mail->addAddress($mailid);
            }
            //$mail->addAddress('ellen@example.com');               //Name is optional
            //$mail->addReplyTo('info@example.com', 'Information');
            //$mail->addCC('weerakoonnb@gmail.com');
            //$mail->addBCC('bcc@example.com');

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $mailheading;
            $mail->Body    = $mailbody;
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            //echo 'Message has been sent';
        } catch (Exception $e) {
            //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}

//clearance request placed email
function clearanceRequest($cl_id) {
    
    include 'connection/connection.php';

    $sql = "SELECT employees.title, employees.name_with_initials, employees.epf_no, employees.code, cl_requests.resignation_date,
    cl_requests.cl_req_id, users.name AS created_by, branch_departments.bd_name, branch_departments.bd_code
    FROM cl_requests
    INNER JOIN employees ON employees.emp_id = cl_requests.emp_id
    INNER JOIN users ON users.user_id = cl_requests.created_by
    Inner JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
    WHERE cl_requests.status=1 AND cl_requests.cl_req_id = '$cl_id'";

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $content = '<!doctype html>
    <html lang="en">
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Clearance Request Notification</title>
        <style media="all" type="text/css">
          @media all {
            .btn-primary table td:hover {
              background-color: #ec0867 !important;
            }

            .btn-primary a:hover {
              background-color: #ec0867 !important;
              border-color: #ec0867 !important;
            }
          }
          @media only screen and (max-width: 640px) {
            .main p, .main td, .main span {
              font-size: 16px !important;
            }
            .wrapper {
              padding: 8px !important;
            }
            .content {
              padding: 0 !important;
            }
            .container {
              padding: 0 !important;
              padding-top: 8px !important;
              width: 100% !important;
            }
            .main {
              border-left-width: 0 !important;
              border-radius: 0 !important;
              border-right-width: 0 !important;
            }
            .btn table {
              max-width: 100% !important;
              width: 100% !important;
            }
            .btn a {
              font-size: 16px !important;
              max-width: 100% !important;
              width: 100% !important;
            }
          }
        </style>
      </head>

      <body style="font-family: Helvetica, sans-serif; background-color: #f4f5f6; margin: 0; padding: 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="width: 100%; background-color: #f4f5f6;">
          <tr>
            <td></td>
            <td class="container" style="max-width: 600px; width: 600px; margin: 0 auto; padding: 24px;">
              <div class="content">

                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main" style="background: #ffffff; border: 1px solid #eaebed; border-radius: 16px; width: 100%;">
                  
                  <tr>
                    <td class="wrapper" style="padding: 24px;">
                      <p>Dear Team,</p>
                      <p>This is an automated notification. A new clearance request has been submitted for the following employee:</p>
                      
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="width: 100%;">
                        <tbody>
                          <tr>
                            <td align="left">
                              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                                <tbody>
                                  <tr><td><strong>Clearance ID:</strong> #'.$row['cl_req_id'].' </td></tr>
                                  <tr><td><strong>Employee Name:</strong> '.$row['title'].' '.$row['name_with_initials'].'</td></tr>
                                  <tr><td><strong>Employee No/Code:</strong> '.$row['epf_no'].'/'.$row['code'].'</td></tr>
                                  <tr><td><strong>Department/Branch:</strong> '.$row['bd_name'].'</td></tr>
                                  <tr><td><strong>Resign Date:</strong> '.$row['resignation_date'].'</td></tr>
                                  <tr><td><strong>Requested By:</strong> '.$row['created_by'].'</td></tr>
                                </tbody>
                              </table>
                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <p><strong>Next Steps:</strong></p>
                      <ul>
                        <li><strong>HR Team:</strong> Please review and confirm once the clearance process has been initiated.</li>
                        <li><strong>Requesting Party:</strong> Ensure all necessary details are provided for a smooth process.</li>
                      </ul>

                      <p>This is a system-generated email—no action is required in response to this notification. For any concerns, please contact HR Department.</p>

                      <p style="font-size: 12px; text-align: center;"><i>*** Please note that this is an automated email. Do not reply to it. ***</i></p>
                    </td>
                  </tr>
                </table>

                <div class="footer" style="padding-top: 24px; text-align: center;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                      <td class="content-block" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        Sadaharitha Plantations Ltd, 6A Alfred Pl, Colombo 03.
                      </td>
                    </tr>
                    <tr>
                      <td class="content-block powered-by" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        @ SPL IT
                      </td>
                    </tr>
                  </table>
                </div>

              </div>
            </td>
            <td></td>
          </tr>
        </table>
      </body>
    </html>';

    //$mailid = ['nishshankaw@sadaharitha.com'];
    // Branch code to search
    $branch_code = $row['bd_code']; 

    // SQL query to find exact matches in bd_id column
    $sql = "SELECT username FROM users WHERE process_level IN (1,2,3,4) AND (bd_id = ? 
            OR bd_id LIKE CONCAT(? , ',%') 
            OR bd_id LIKE CONCAT('%,', ? , ',%') 
            OR bd_id LIKE CONCAT('%,', ? ))";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $branch_code, $branch_code, $branch_code, $branch_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results into an array
    $mailid = [];
    while ($row1 = $result->fetch_assoc()) {
        $mailid[] = $row1['username'];
    }

    $mailheading = 'Clearance Request Notification (#'.$row['cl_req_id'].')';

    sendMail($mailid, $mailheading, $content);
}

//clearance request pending email
function clearanceRequestPending($cl_id,$pending_note) {
    
    include 'connection/connection.php';

    $sql = "SELECT employees.title, employees.name_with_initials, employees.epf_no, employees.code, cl_requests.resignation_date,
    cl_requests.cl_req_id, users.name AS created_by, branch_departments.bd_name, branch_departments.bd_code
    FROM cl_requests
    INNER JOIN employees ON employees.emp_id = cl_requests.emp_id
    INNER JOIN users ON users.user_id = cl_requests.created_by
    Inner JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
    WHERE cl_requests.status=1 AND cl_requests.cl_req_id = '$cl_id'";

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $content = '<!doctype html>
    <html lang="en">
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Clearance Request Notification</title>
        <style media="all" type="text/css">
          @media all {
            .btn-primary table td:hover {
              background-color: #ec0867 !important;
            }

            .btn-primary a:hover {
              background-color: #ec0867 !important;
              border-color: #ec0867 !important;
            }
          }
          @media only screen and (max-width: 640px) {
            .main p, .main td, .main span {
              font-size: 16px !important;
            }
            .wrapper {
              padding: 8px !important;
            }
            .content {
              padding: 0 !important;
            }
            .container {
              padding: 0 !important;
              padding-top: 8px !important;
              width: 100% !important;
            }
            .main {
              border-left-width: 0 !important;
              border-radius: 0 !important;
              border-right-width: 0 !important;
            }
            .btn table {
              max-width: 100% !important;
              width: 100% !important;
            }
            .btn a {
              font-size: 16px !important;
              max-width: 100% !important;
              width: 100% !important;
            }
          }
        </style>
      </head>

      <body style="font-family: Helvetica, sans-serif; background-color: #f4f5f6; margin: 0; padding: 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="width: 100%; background-color: #f4f5f6;">
          <tr>
            <td></td>
            <td class="container" style="max-width: 600px; width: 600px; margin: 0 auto; padding: 24px;">
              <div class="content">

                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main" style="background: #ffffff; border: 1px solid #eaebed; border-radius: 16px; width: 100%;">
                  
                  <tr>
                    <td class="wrapper" style="padding: 24px;">
                      <p>Dear Team,</p>
                      <p>This is an automated notification. A clearance request has been moved to pending stage for the following employee:</p>
                      
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="width: 100%;">
                        <tbody>
                          <tr>
                            <td align="left">
                              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                                <tbody>
                                  <tr><td><strong>Clearance ID:</strong> #'.$row['cl_req_id'].' </td></tr>
                                  <tr><td><strong>Employee Name:</strong> '.$row['title'].' '.$row['name_with_initials'].'</td></tr>
                                  <tr><td><strong>Employee No/Code:</strong> '.$row['epf_no'].'/'.$row['code'].'</td></tr>
                                  <tr><td><strong>Department/Branch:</strong> '.$row['bd_name'].'</td></tr>
                                  <tr><td><strong>Resign Date:</strong> '.$row['resignation_date'].'</td></tr>
                                  <tr><td><strong>Requested By:</strong> '.$row['created_by'].'</td></tr>
                                  <tr><td><strong>Pending Note:</strong> '.$pending_note.'</td></tr>
                                </tbody>
                              </table>
                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <p><strong>Next Steps:</strong></p>
                      <ul>
                        <li><strong>Requesting Party:</strong> Ensure all necessary details are provided for a smooth process.</li>
                      </ul>

                      <p>This is a system-generated email—no action is required in response to this notification. For any concerns, please contact HR Department.</p>

                      <p style="font-size: 12px; text-align: center;"><i>*** Please note that this is an automated email. Do not reply to it. ***</i></p>
                    </td>
                  </tr>
                </table>

                <div class="footer" style="padding-top: 24px; text-align: center;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                      <td class="content-block" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        Sadaharitha Plantations Ltd, 6A Alfred Pl, Colombo 03.
                      </td>
                    </tr>
                    <tr>
                      <td class="content-block powered-by" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        @ SPL IT
                      </td>
                    </tr>
                  </table>
                </div>

              </div>
            </td>
            <td></td>
          </tr>
        </table>
      </body>
    </html>';

    //$mailid = ['nishshankaw@sadaharitha.com'];
    // Branch code to search
    $branch_code = $row['bd_code']; 

    // SQL query to find exact matches in bd_id column
    $sql = "SELECT username FROM users WHERE process_level IN (1,2,3,4) AND (bd_id = ? 
            OR bd_id LIKE CONCAT(? , ',%') 
            OR bd_id LIKE CONCAT('%,', ? , ',%') 
            OR bd_id LIKE CONCAT('%,', ? ))";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $branch_code, $branch_code, $branch_code, $branch_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results into an array
    $mailid = [];
    while ($row1 = $result->fetch_assoc()) {
        $mailid[] = $row1['username'];
    }

    $mailheading = 'Clearance Request Pending Notification (#'.$row['cl_req_id'].')';

    sendMail($mailid, $mailheading, $content);
}

//clearance request accept email
function clearanceRequestAccept($cl_id,$accept_note) {
    
    include 'connection/connection.php';

    $sql = "SELECT employees.title, employees.name_with_initials, employees.epf_no, employees.code, cl_requests.resignation_date,
    cl_requests.cl_req_id, users.name AS created_by, branch_departments.bd_name, branch_departments.bd_code
    FROM cl_requests
    INNER JOIN employees ON employees.emp_id = cl_requests.emp_id
    INNER JOIN users ON users.user_id = cl_requests.created_by
    Inner JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
    WHERE cl_requests.status=1 AND cl_requests.cl_req_id = '$cl_id'";

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $content = '<!doctype html>
    <html lang="en">
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Clearance Request Notification</title>
        <style media="all" type="text/css">
          @media all {
            .btn-primary table td:hover {
              background-color: #ec0867 !important;
            }

            .btn-primary a:hover {
              background-color: #ec0867 !important;
              border-color: #ec0867 !important;
            }
          }
          @media only screen and (max-width: 640px) {
            .main p, .main td, .main span {
              font-size: 16px !important;
            }
            .wrapper {
              padding: 8px !important;
            }
            .content {
              padding: 0 !important;
            }
            .container {
              padding: 0 !important;
              padding-top: 8px !important;
              width: 100% !important;
            }
            .main {
              border-left-width: 0 !important;
              border-radius: 0 !important;
              border-right-width: 0 !important;
            }
            .btn table {
              max-width: 100% !important;
              width: 100% !important;
            }
            .btn a {
              font-size: 16px !important;
              max-width: 100% !important;
              width: 100% !important;
            }
          }
        </style>
      </head>

      <body style="font-family: Helvetica, sans-serif; background-color: #f4f5f6; margin: 0; padding: 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="width: 100%; background-color: #f4f5f6;">
          <tr>
            <td></td>
            <td class="container" style="max-width: 600px; width: 600px; margin: 0 auto; padding: 24px;">
              <div class="content">

                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main" style="background: #ffffff; border: 1px solid #eaebed; border-radius: 16px; width: 100%;">
                  
                  <tr>
                    <td class="wrapper" style="padding: 24px;">
                      <p>Dear Team,</p>
                      <p>This is an automated notification. A clearance request has been accepted and HR department will continue clearence process for the following employee:</p>
                      
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="width: 100%;">
                        <tbody>
                          <tr>
                            <td align="left">
                              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                                <tbody>
                                  <tr><td><strong>Clearance ID:</strong> #'.$row['cl_req_id'].' </td></tr>
                                  <tr><td><strong>Employee Name:</strong> '.$row['title'].' '.$row['name_with_initials'].'</td></tr>
                                  <tr><td><strong>Employee No/Code:</strong> '.$row['epf_no'].'/'.$row['code'].'</td></tr>
                                  <tr><td><strong>Department/Branch:</strong> '.$row['bd_name'].'</td></tr>
                                  <tr><td><strong>Resign Date:</strong> '.$row['resignation_date'].'</td></tr>
                                  <tr><td><strong>Requested By:</strong> '.$row['created_by'].'</td></tr>
                                  <tr><td><strong>Acceptance Note:</strong> '.$accept_note.'</td></tr>
                                </tbody>
                              </table>
                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <p><strong>Next Steps:</strong></p>
                      <ul>
                        <li><strong>HR Department:</strong> Allocate relevant branch and departments for the clearance.</li>
                      </ul>

                      <p>This is a system-generated email—no action is required in response to this notification. For any concerns, please contact HR Department.</p>

                      <p style="font-size: 12px; text-align: center;"><i>*** Please note that this is an automated email. Do not reply to it. ***</i></p>
                    </td>
                  </tr>
                </table>

                <div class="footer" style="padding-top: 24px; text-align: center;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                      <td class="content-block" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        Sadaharitha Plantations Ltd, 6A Alfred Pl, Colombo 03.
                      </td>
                    </tr>
                    <tr>
                      <td class="content-block powered-by" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        @ SPL IT
                      </td>
                    </tr>
                  </table>
                </div>

              </div>
            </td>
            <td></td>
          </tr>
        </table>
      </body>
    </html>';

    //$mailid = ['nishshankaw@sadaharitha.com'];
    // Branch code to search
    $branch_code = $row['bd_code']; 

    // SQL query to find exact matches in bd_id column
    $sql = "SELECT username FROM users WHERE process_level IN (1,2,3,4) AND (bd_id = ? 
            OR bd_id LIKE CONCAT(? , ',%') 
            OR bd_id LIKE CONCAT('%,', ? , ',%') 
            OR bd_id LIKE CONCAT('%,', ? ))";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $branch_code, $branch_code, $branch_code, $branch_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results into an array
    $mailid = [];
    while ($row1 = $result->fetch_assoc()) {
        $mailid[] = $row1['username'];
    }

    $mailheading = 'Clearance Request Accepted (#'.$row['cl_req_id'].')';

    sendMail($mailid, $mailheading, $content);
}

//clearance request step to attend email
function clearanceRequestStepNotice($cl_id,$email) {
    
    include 'connection/connection.php';

    $sql = "SELECT employees.title, employees.name_with_initials, employees.epf_no, employees.code, cl_requests.resignation_date,
    cl_requests.cl_req_id, users.name AS created_by, branch_departments.bd_name, branch_departments.bd_code
    FROM cl_requests
    INNER JOIN employees ON employees.emp_id = cl_requests.emp_id
    INNER JOIN users ON users.user_id = cl_requests.created_by
    Inner JOIN branch_departments ON employees.bd_id = branch_departments.bd_id
    WHERE cl_requests.status=1 AND cl_requests.cl_req_id = '$cl_id'";

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $content = '<!doctype html>
    <html lang="en">
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Clearance Request Notification</title>
        <style media="all" type="text/css">
          @media all {
            .btn-primary table td:hover {
              background-color: #ec0867 !important;
            }

            .btn-primary a:hover {
              background-color: #ec0867 !important;
              border-color: #ec0867 !important;
            }
          }
          @media only screen and (max-width: 640px) {
            .main p, .main td, .main span {
              font-size: 16px !important;
            }
            .wrapper {
              padding: 8px !important;
            }
            .content {
              padding: 0 !important;
            }
            .container {
              padding: 0 !important;
              padding-top: 8px !important;
              width: 100% !important;
            }
            .main {
              border-left-width: 0 !important;
              border-radius: 0 !important;
              border-right-width: 0 !important;
            }
            .btn table {
              max-width: 100% !important;
              width: 100% !important;
            }
            .btn a {
              font-size: 16px !important;
              max-width: 100% !important;
              width: 100% !important;
            }
          }
        </style>
      </head>

      <body style="font-family: Helvetica, sans-serif; background-color: #f4f5f6; margin: 0; padding: 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="width: 100%; background-color: #f4f5f6;">
          <tr>
            <td></td>
            <td class="container" style="max-width: 600px; width: 600px; margin: 0 auto; padding: 24px;">
              <div class="content">

                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main" style="background: #ffffff; border: 1px solid #eaebed; border-radius: 16px; width: 100%;">
                  
                  <tr>
                    <td class="wrapper" style="padding: 24px;">
                      <p>Dear Team,</p>
                      <p>This is an automated notification. A clearance request has been allocated to you for the following employee:</p>
                      
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="width: 100%;">
                        <tbody>
                          <tr>
                            <td align="left">
                              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                                <tbody>
                                  <tr><td><strong>Clearance ID:</strong> #'.$row['cl_req_id'].' </td></tr>
                                  <tr><td><strong>Employee Name:</strong> '.$row['title'].' '.$row['name_with_initials'].'</td></tr>
                                  <tr><td><strong>Employee No/Code:</strong> '.$row['epf_no'].'/'.$row['code'].'</td></tr>
                                  <tr><td><strong>Department/Branch:</strong> '.$row['bd_name'].'</td></tr>
                                  <tr><td><strong>Resign Date:</strong> '.$row['resignation_date'].'</td></tr>
                                  <tr><td><strong>Requested By:</strong> '.$row['created_by'].'</td></tr>
                                </tbody>
                              </table>
                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <p><strong>Next Steps:</strong></p>
                      <ul>
                        <li><strong>Kindly prioritize this task, and the rest can follow once it\'s done. Appreciate it!</strong></li>
                      </ul>

                      <p>This is a system-generated email—no action is required in response to this notification. For any concerns, please contact HR Department.</p>

                      <p style="font-size: 12px; text-align: center;"><i>*** Please note that this is an automated email. Do not reply to it. ***</i></p>
                    </td>
                  </tr>
                </table>

                <div class="footer" style="padding-top: 24px; text-align: center;">
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                      <td class="content-block" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        Sadaharitha Plantations Ltd, 6A Alfred Pl, Colombo 03.
                      </td>
                    </tr>
                    <tr>
                      <td class="content-block powered-by" style="color: #9a9ea6; font-size: 16px; text-align: center;">
                        @ SPL IT
                      </td>
                    </tr>
                  </table>
                </div>

              </div>
            </td>
            <td></td>
          </tr>
        </table>
      </body>
    </html>';

    //$mailid = ['nishshankaw@sadaharitha.com'];
    // Branch code to search
    $branch_code = $row['bd_code']; 

    // SQL query to find exact matches in bd_id column
    // $sql = "SELECT username FROM users WHERE process_level IN (1,2,3,4) AND (bd_id = ? 
    //         OR bd_id LIKE CONCAT(? , ',%') 
    //         OR bd_id LIKE CONCAT('%,', ? , ',%') 
    //         OR bd_id LIKE CONCAT('%,', ? ))";

    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("ssss", $branch_code, $branch_code, $branch_code, $branch_code);
    // $stmt->execute();
    // $result = $stmt->get_result();

    // Fetch results into an array
    // $mailid = [];
    // while ($row1 = $result->fetch_assoc()) {
    //     $mailid[] = $row1['username'];
    // }

    $mailid = [$email];
    $mailheading = 'Clearance Request to Attend (#'.$row['cl_req_id'].')';

    sendMail($mailid, $mailheading, $content);
}