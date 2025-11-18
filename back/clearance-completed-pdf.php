<?php
// completed-clearance-pdf.php
// Generates a PDF for Completed Clearances (style matches the Delayed Details Report provided)

session_start();
require_once __DIR__ . '/../vendor/autoload.php'; // TCPDF autoload (adjust path as needed)
require __DIR__ . '/connection/connection.php';
require __DIR__ . '/functions.php'; // optional helper functions

// Get parameters (called from frontend: dept, fromdate, todate)
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$fromdate = isset($_GET['fromdate']) ? $_GET['fromdate'] : '';
$todate = isset($_GET['todate']) ? $_GET['todate'] : '';

// Sanitize inputs
$deptEsc = $conn->real_escape_string($dept);
$fromEsc = $conn->real_escape_string($fromdate);
$toEsc = $conn->real_escape_string($todate);

// Build query
$sql = "SELECT cr.cl_req_id, cr.resignation_date, cr.completed_date, cr.status, cr.is_complete,
               e.name_with_initials, e.title, e.code, e.epf_no,
               bd.bd_name AS emp_dept, cr.check_number
        FROM cl_requests cr
        INNER JOIN employees e ON cr.emp_id = e.emp_id
        LEFT JOIN branch_departments bd ON bd.bd_id = e.bd_id
        WHERE cr.status = '1' AND cr.is_complete = '1'";

if (!empty($deptEsc)) {
    // dept may be comma-separated codes or ids depending on your system. Keep behavior consistent with your fetch.
    $sql .= " AND e.bd_id IN ('" . str_replace(",", "','", $deptEsc) . "')";
}

if (!empty($fromEsc)) {
    $sql .= " AND DATE(cr.completed_date) >= '" . date('Y-m-d', strtotime($fromEsc)) . "'";
}
if (!empty($toEsc)) {
    $sql .= " AND DATE(cr.completed_date) <= '" . date('Y-m-d', strtotime($toEsc)) . "'";
}

$sql .= " ORDER BY cr.cl_req_id DESC";

$result = $conn->query($sql);

// Initialize TCPDF
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetCreator('HRM System');
$pdf->SetAuthor('Sadaharitha Group');
$pdf->SetTitle('Completed Clearances Report');
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

// Logo (adjust path)
$logoPath = __DIR__ . '/../images/Sadaharitha-group-logo-ai.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 35);
}

// Header
$pdf->SetXY(100, 10);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(200, 10, 'Completed Clearances Report', 0, 1, 'L');

// Date range text
$pdf->SetFont('helvetica', '', 11);
$rangeText = '';
if (!empty($fromdate) && !empty($todate)) {
    $rangeText = 'From ' . date('d M Y', strtotime($fromdate)) . ' To ' . date('d M Y', strtotime($todate));
} elseif (!empty($fromdate)) {
    $rangeText = 'From ' . date('d M Y', strtotime($fromdate));
} elseif (!empty($todate)) {
    $rangeText = 'To ' . date('d M Y', strtotime($todate));
}

if ($rangeText !== '') {
    $pdf->SetXY(110, 18);
    $pdf->Cell(200, 10, $rangeText, 0, 1, 'L');
}

$pdf->SetXY(220, 18);
$pdf->Cell(200, 10, 'Report Date : ' . date('Y-m-d H:i:s'), 0, 1, 'L');

$pdf->Ln(12);

// Table HTML (match style of delayed report)
$html = <<<HTML
<style>
    table { border-collapse: collapse; width: 100%; font-size: 10pt; }
    thead { background-color: #f2f2f2; }
    th { background-color: green; color: white; font-weight: bold; padding: 6px; border: 1px solid #ccc; text-align: center; }
    td { padding: 6px; border: 1px solid #ddd; }
    tr:nth-child(even) { background-color: #f9f9f9; }
</style>

<table>
<thead>
<tr>
    <th>#</th>
    <th>Clearance ID</th>
    <th>Name</th>
    <th>Department</th>
    <th>EPF No</th>
    <th>Sales Code</th>
    <th>Resignation Date</th>
    <th>Completed Date</th>
    <th>Check Number</th>
</tr>
</thead>
<tbody>
HTML;

// Loop rows
$counter = 1;
while ($row = $result->fetch_assoc()) {
    $clId = htmlspecialchars($row['cl_req_id'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($row['title'] . ' ' . $row['name_with_initials'], ENT_QUOTES, 'UTF-8');
    $deptName = htmlspecialchars($row['emp_dept'] ?? '', ENT_QUOTES, 'UTF-8');
    $epf = htmlspecialchars($row['epf_no'] ?? '', ENT_QUOTES, 'UTF-8');
    $code = htmlspecialchars($row['code'] ?? '', ENT_QUOTES, 'UTF-8');
    $resDate = !empty($row['resignation_date']) ? date('Y-m-d', strtotime($row['resignation_date'])) : '';
    $compDate = !empty($row['completed_date']) ? date('Y-m-d', strtotime($row['completed_date'])) : '';
    $checkNum = htmlspecialchars($row['check_number'] ?? '', ENT_QUOTES, 'UTF-8');

    $html .= '<tr>';
    $html .= '<td>' . $counter . '</td>';
    $html .= '<td>' . $clId . '</td>';
    $html .= '<td>' . $name . '</td>';
    $html .= '<td>' . $deptName . '</td>';
    $html .= '<td>' . $epf . '</td>';
    $html .= '<td>' . $code . '</td>';
    $html .= '<td>' . $resDate . '</td>';
    $html .= '<td>' . $compDate . '</td>';
    $html .= '<td>' . $checkNum . '</td>';
    $html .= '</tr>';

    $counter++;
}

$html .= '</tbody></table>';

// Write HTML and output
$pdf->writeHTML($html, true, false, true, false, '');

$filename = 'completed_clearances_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D'); // Force download. Use 'I' to open inline

exit;
?>
