<?php
session_start();

// Check if session data exists
if (!isset($_SESSION['search_results']) || empty($_SESSION['search_results'])) {
    die("No data available for export.");
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Form2_PTA_Data.csv"');

// Open the output stream
$output = fopen('php://output', 'w');

// Set column headers
fputcsv($output, ["No", "Name", "IC", "Phone", "Address", "Job", "Total Vote", "Role"]);

// Write data to CSV
$counter = 1;
foreach ($_SESSION['search_results'] as $row) {
    fputcsv($output, [
        $counter++,
        $row['name'],
        "'" . $row['ic'],          // Preserve IC leading zeros
        "'0" . $row['phone'],      // Add an extra "0" in front of the phone number
        $row['address'],
        $row['job'],
        isset($row['total_vote']) ? $row['total_vote'] : 0,
        isset($row['role']) ? $row['role'] : ''
    ]);
}

// Close the output stream
fclose($output);
?>
