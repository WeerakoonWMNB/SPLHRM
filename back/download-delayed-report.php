<?php
session_start();
require_once '../vendor/autoload.php';
require "connection/connection.php";
require "functions.php"; // Ensure this includes getWeekdaysDiff()

// Get and sanitize parameters
$department = $_GET['department'] ?? '';
$fromdate = $_GET['fromdate'] ?? '';
$todate = $_GET['todate'] ?? '';

 $fromdateEsc = mysqli_real_escape_string($conn, $fromdate);
 $todateEsc = mysqli_real_escape_string($conn, $todate);

// Build SQL
$sql = "SELECT 
                cr.*, 
                e.name_with_initials, 
                e.code, 
                e.epf_no, 
                e.title, 
                crs.is_complete AS step_complete, 
                crs.step,
                crs.pending_note,
                crs.complete_note,
                crs.created_date AS step_created_date,
                crs.complete_date AS step_completed_date,
                crs.max_dates,
                bds.bd_name AS selected_branch,
                crs.allocated_date AS last_complete_date


            FROM cl_requests cr
            INNER JOIN employees e ON cr.emp_id = e.emp_id
            LEFT JOIN branch_departments bd ON bd.bd_id = e.bd_id
            INNER JOIN cl_requests_steps crs ON cr.cl_req_id = crs.request_id 
            INNER JOIN branch_departments bds ON bds.bd_code = crs.bd_code 
            WHERE cr.status = '1' 
            AND crs.allocated_date IS NOT NULL
            AND crs.step > 0 AND DATEDIFF(IFNULL(crs.complete_date, CURDATE()),IFNULL(crs.allocated_date,crs.created_date)) +1 - crs.max_dates > 0";

        if ($department) {
            $sql .= " AND crs.bd_code IN ('$department')";
        }

        if ($fromdateEsc) {
            $sql .= " AND crs.allocated_date >= '$fromdateEsc 00:00:00' ";
        }
        
        if ($todateEsc) {
            $sql .= " AND crs.allocated_date <= '$todateEsc 23:59:59' ";
        }
        

        $sql .= " ORDER BY cr.cl_req_id ASC";

$result = mysqli_query($conn, $sql);

// Initialize TCPDF
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetCreator('HRM System');
$pdf->SetAuthor('Sadaharitha Group');
$pdf->SetTitle('Delayed Details Report');
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

// Logo and header layout
$logoPath = __DIR__ . '/../images/Sadaharitha-group-logo-ai.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 10, 35); // x, y, width
}

// Report title and date
$pdf->SetXY(100, 10);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(200, 10, 'Clearance Delayed Details Report', 0, 1, 'L');


if ($fromdate && $todate) {
    $pdf->SetXY(110, 18);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(200, 10, 'From ' . date('d M Y', strtotime($fromdate)) . ' To ' . date('d M Y', strtotime($todate)), 0, 1, 'L');
}

else if ($fromdate) {
    $pdf->SetXY(130, 18);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(200, 10, 'From ' . date('d M Y', strtotime($fromdate)) , 0, 1, 'L');
}
else if ($todate) {
    $pdf->SetXY(130, 18);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(200, 10, 'To ' . date('d M Y', strtotime($todate)), 0, 1, 'L');
}



$pdf->SetXY(220, 18);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(200, 10, 'Report Date : ' . date("Y-m-d H:i:s") , 0, 1, 'L');

$pdf->Ln(12); // Space after header

// Table HTML with styling
$html = '
<style>
    table {
        border-collapse: collapse;
        width: 100%;
        font-size: 10pt;
    }
    thead {
        background-color: #f2f2f2;
    }
    th {
        background-color: green;
        color: white;
        font-weight: bold;
        padding: 6px;
        border: 1px solid #ccc;
        text-align: center;
    }
    td {
        padding: 6px;
        border: 1px solid #ddd;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
</style>

<table>
<thead>
<tr>
    <th>#</th>
    <th>Clearance ID </th>
    <th>Name</th>
    <th>Resignation Date</th>
    <th>EPF No</th>
    <th>Sales Code</th>
    <th>Note</th>
    <th>Selected Dept</th>
    <th>Allocated Date</th>
    <th>Completed Date</th>
    <th>Allocated Days</th>
    <th>Delayed Days</th>
</tr>
</thead>
<tbody>';

// Loop through data rows
$j = 1;
while ($row = mysqli_fetch_assoc($result)) {

    $assigned_date = $row['last_complete_date'] ?: $row['step_created_date'];
    $completed_date = $row['step_completed_date'] ?: date('Y-m-d');

    $date_diff = getWeekdaysDiff($assigned_date, $completed_date);
    $overdue_days = $date_diff - $row['max_dates'];

    if ($overdue_days > 0) {
        $cl_req_id = htmlspecialchars($row['cl_req_id'], ENT_QUOTES, 'UTF-8');

        $note = '';

        if (!empty($row['pending_note'])) {
            $note .= '* ' . $row['pending_note'];
        }

        if (!empty($row['complete_note'])) {
            if (!empty($note)) {
                $note .= '<br>'; // Add a break only if there's already content
            }
            $note .= '* ' . $row['complete_note'];
        }

        $note = trim($note); 

        $html .= '<tr>
        <td>' . $j . '</td>
        <td>' . $row['cl_req_id'] . '</td>
        <td>' . htmlspecialchars($row['title'] . ' ' . $row['name_with_initials'], ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . $row['resignation_date'] . '</td>
        <td>' . $row['epf_no'] . '</td>
        <td>' . $row['code'] . '</td>
        <td>' . $note . '</td>
        <td>' . $row['selected_branch'] . '</td>
        <td>' . $assigned_date . '</td>
        <td>' . $row['step_completed_date'] . '</td>
        <td>' . $row['max_dates'] . '</td>
        <td>' . $overdue_days . '</td>
    </tr>';
    $j++;
    }

    
}

$html .= '</tbody></table>';

// Write HTML to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF (inline)
$pdf->Output('delay_report_' . date('Ymd') . '.pdf', 'D'); // 'I' for inline, 'D' for download
exit;
