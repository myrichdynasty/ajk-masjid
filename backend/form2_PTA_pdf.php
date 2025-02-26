<?php
require('fpdf.php'); // Ensure fpdf.php is in the same directory

class PDF extends FPDF {
    private $date;

    // Constructor to receive date
    function __construct($date) {
        parent::__construct('L', 'mm', 'A4'); // Landscape mode
        $this->date = $date;
    }

    // Table Header
    function Header() {
        // Set font
        $this->SetFont('Arial', 'B', 14);
        // Title
        $this->Cell(0, 10, 'Form 2 PTA Report', 0, 1, 'C');

        // Set font for date
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 8, 'Meeting Date: ' . $this->date, 0, 1, 'L'); // Only display the date
        $this->Ln(5);

        // Column headers
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(200, 200, 200); // Light gray background for headers
        $this->Cell(50, 10, 'Name', 1, 0, 'C', true);
        $this->Cell(40, 10, 'IC', 1, 0, 'C', true);
        $this->Cell(35, 10, 'Phone', 1, 0, 'C', true);
        $this->Cell(50, 10, 'Address', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Job', 1, 0, 'C', true);
        $this->Cell(20, 10, 'Vote', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Role', 1, 1, 'C', true);
    }

    // Table Footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Get Date (Exclude location)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Create PDF instance with Date
$pdf = new PDF($date);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Connect to database
include('connection.php');

// Fetch data from session
session_start();
if (!isset($_SESSION['search_results']) || empty($_SESSION['search_results'])) {
    $pdf->Cell(0, 10, 'No data available.', 1, 1, 'C');
} else {
    // Display table data
    foreach ($_SESSION['search_results'] as $row) {
        $pdf->Cell(50, 10, htmlspecialchars($row['name']), 1, 0, 'L');
        $pdf->Cell(40, 10, htmlspecialchars($row['ic']), 1, 0, 'L');
        $pdf->Cell(35, 10, htmlspecialchars($row['phone']), 1, 0, 'L');
        $pdf->Cell(50, 10, htmlspecialchars($row['address']), 1, 0, 'L');
        $pdf->Cell(40, 10, htmlspecialchars($row['job']), 1, 0, 'L');
        $pdf->Cell(20, 10, isset($row['total_vote']) ? $row['total_vote'] : 0, 1, 0, 'C');
        $pdf->Cell(40, 10, isset($row['role']) ? $row['role'] : '-', 1, 1, 'C');
    }
}

// Output PDF for download
$pdf->Output('I', 'Form2_PTA_Report.pdf'); // 'I' opens in browser
