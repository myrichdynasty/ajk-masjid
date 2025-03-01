<?php
session_start();
include('../backend/connection.php'); // Include database connection

$daerah_id = $_SESSION['daerah_id'];

try {
    date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
    // Get the first and last day of the current month
    $firstDay = date('Y-m-01'); // Example: 2024-02-01
    $lastDay = date('Y-m-t');   // Example: 2024-02-29
    $current_date = date('Y-m-d'); // Get current date and time in GMT+8

    // Fetch the specific daerah name of the logged-in user
    $stmt = $conn->prepare("SELECT daerah_name FROM daerah WHERE daerah_id = :daerah_id");
    $stmt->bindParam(':daerah_id', $daerah_id, PDO::PARAM_INT);
    $stmt->execute();
    $daerah = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch all masjids only for the logged-in user's daerah
    $stmt = $conn->prepare("SELECT m.*, d.daerah_name,
        (SELECT COUNT(*) 
        FROM form f 
        JOIN booking b ON f.booking_id = b.booking_id 
        WHERE DATE(b.reg_date) BETWEEN '".$firstDay."' AND '".$lastDay."' AND b.masjid_id = m.masjid_id) AS form_1,
        (SELECT COUNT(*) 
        FROM form_2 ff 
        JOIN booking bb ON ff.booking_id = bb.booking_id 
        WHERE DATE(bb.reg_date) BETWEEN '".$firstDay."' AND '".$lastDay."' AND bb.masjid_id = m.masjid_id) AS form_2
        FROM masjid m
        JOIN daerah d ON m.daerah_id = d.daerah_id
        WHERE m.daerah_id = ".$daerah_id."
        ORDER BY m.masjid_id");
    // $stmt->bindParam(':daerah_id', $daerah_id, PDO::PARAM_INT);
    // $stmt->bindParam(':firstdate', $firstDay, PDO::PARAM_INT);
    // $stmt->bindParam(':lastdate', $lastDay, PDO::PARAM_INT);
    // print_r($stmt);
    // exit;
    $stmt->execute();
    $masjids = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Initialize daerahMasjids for the logged-in user's daerah
$daerahMasjids = [];
if ($daerah) {
    $daerahMasjids[$daerah['daerah_name']] = []; // Ensure the daerah appears
}

// Organize masjids by daerah_name
foreach ($masjids as $masjid) {
    $daerahMasjids[$masjid['daerah_name']][] = $masjid;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENARAI MASJID MENGIKUT DAERAH</title>
    <link rel="stylesheet" href="../Styles/styles.css">
    <script src="../Script/dropdown_pta.js"></script>
</head>
<body>
<?php require '../include/header.php'; ?>
    <div class="header">
        <h1>SENARAI MASJID MENGIKUT DAERAH</h1>
    </div>

    <div class="container">
        <?php foreach ($daerahMasjids as $daerahName => $masjids): ?>
            <div class="masjid-section">
                <h2><?php echo htmlspecialchars($daerahName); ?></h2>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead class="table-primary text-white">
                    <tr>
                        <th>NAMA MASJID</th>
                        <th>TINDAKAN</th>
                </thead>
                </tbody>
                    </tr>
                    <?php if (empty($masjids)): ?>
                        <tr><td colspan="7">TIADA DATA DIJUMPAI.</td></tr>
                    <?php else: ?>
                        <?php foreach ($masjids as $masjid): ?>
                            <tr>
                                <td><?php echo !empty($masjid['masjid_name']) ? htmlspecialchars($masjid['masjid_name']) : '-'; ?></td>
                                <td>
                                    <a href="../backend/form1_PTA.php?masjid_id=<?php echo $masjid['masjid_id']; ?>" class="mb-2 ml-2">
                                        <button type="button" class="btn btn-primary">BORANG PENCALONAN 1 <?php if($masjid['form_1'] > 0){ ?><span class="badge bg-danger"><?php echo "Baru"; ?></span><?php } ?></button>
                                    </a>
                                    <a href="../backend/form2_PTA - Copy.php?masjid_id=<?php echo $masjid['masjid_id']; ?>" class="mb-2 ml-2">
                                        <button type="button" class="btn btn-primary">BORANG PENCALONAN 2 <?php if($masjid['form_2'] > 0){ ?><span class="badge bg-danger"><?php echo "Baru"; ?></span><?php } ?></button>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Back Button -->
    <div class="back-button-container" style="text-align: center; margin-top: 20px;">
        <a href="../backend/mainpage2.php" style="text-decoration: none;">
            <button style="padding: 10px 20px; font-size: 16px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px;">
                ⬅ KEMBALI KE MENU UTAMA
            </button>
        </a>
    </div>
    <?php require '../include/footer.php'; ?>
</body>
</html>
