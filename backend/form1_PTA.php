<?php
session_start();
include('connection.php');

// Retrieve masjid_id from URL
$masjidID = isset($_GET['masjid_id']) ? intval($_GET['masjid_id']) : null;

// Check if masjid_id is valid
if (!$masjidID) {
    die("Error: Masjid ID is missing or invalid.");
}

// Get the first and last day of the current month
$firstDay = date('Y-m-01'); // Example: 2024-02-01
$lastDay = date('Y-m-t');   // Example: 2024-02-29

try {
    // Get the Masjid Name
    $stmtMasjid = $conn->prepare("SELECT masjid_name FROM masjid WHERE masjid_id = ?");
    $stmtMasjid->execute([$masjidID]);
    $masjidData = $stmtMasjid->fetch(PDO::FETCH_ASSOC);

    if (!$masjidData) {
        die("Error: Masjid not found.");
    }
    $masjidName = $masjidData['masjid_name'];

    // Query to retrieve form data for the selected masjid within the current month
    $stmt = $conn->prepare("
        SELECT f.*, u.nama_penuh AS name, u.no_ic AS ic, u.no_hp AS phone, u.alamat_terkini AS address, u.pekerjaan AS job, u.id_masjid AS masjid_id 
        FROM form f
        JOIN sej6x_data_peribadi u ON f.ic = u.no_ic
        WHERE u.id_masjid = ? 
        AND f.date BETWEEN ? AND ? 
        ORDER BY f.date DESC
    ");
    $stmt->execute([$masjidID, $firstDay, $lastDay]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DATA BORANG PENCALONAN 1 UNTUK <?php echo htmlspecialchars($masjidName); ?></title>
</head>
<body>
<?php require '../include/header.php'; ?>
    <h2 class="text-center">DATA BORANG PENCALONAN UNTUK <?php echo htmlspecialchars($masjidName); ?></h2>
    <p class="text-center">REKOD DARI <strong><?php echo $firstDay; ?></strong> HINGGA <strong><?php echo $lastDay; ?></strong></p>

    <?php if (!empty($searchResults)): ?>
        <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
                <tr>
                    <th>NAMA</th>
                    <th>NO KAD PENGENALAN</th>
                    <th>NO TELEFON</th>
                    <th>ALAMAT</th>
                    <th>PEKERJAAN</th>
                    <th>JUMLAH UNDI</th>
                    <th>TARIKH</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($searchResults as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['ic']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo htmlspecialchars($row['job']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_vote']); ?></td>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>TIADA REKOD DIJUMPAI UNTUK <?php echo htmlspecialchars($masjidName); ?> DALAM BULAN INI.</p>
    <?php endif; ?>

    <div class="text-center">
            <button onclick="window.location.href = 'form_PTA.php'" class="btn btn-primary mb-2">KEMBALI</button>
        </div>
        

        <?php require '../include/footer.php'; ?>
</body>
</html>
