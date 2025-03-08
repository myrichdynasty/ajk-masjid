<?php
session_start();
include('connection.php');

$daerah_id = $_SESSION['daerah_id'];

try {
    date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
    // Get the first and last day of the current month
    $firstDay = date('Y-m-01'); // Example: 2024-02-01
    $lastDay = date('Y-m-t');   // Example: 2024-02-29
    $current_date = date('Y-m-d'); // Get current date and time in GMT+8
    
    // Fetch all daerah names
    $stmt = $conn->prepare("SELECT * FROM daerah ORDER BY daerah_id");
    $stmt->execute();
    $daerahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all masjids along with their daerah names
    // $stmt = $conn->prepare("SELECT m.*, d.daerah_name FROM masjid m
    //     JOIN daerah d ON m.daerah_id = d.daerah_id
    //     ORDER BY d.daerah_id, m.masjid_id");
    $stmt = $conn->prepare("SELECT m.*, d.daerah_name,
                            (SELECT COUNT(*) FROM form f JOIN booking b ON f.booking_id = b.booking_id ) AS form_1,
                            (SELECT COUNT(*) FROM form_2 ff JOIN booking bb ON ff.booking_id = bb.booking_id ) AS form_2
                            FROM masjid m JOIN daerah d ON m.daerah_id = d.daerah_id
                            ORDER BY d.daerah_id, m.masjid_id");
    $stmt->execute();
    $masjids = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Initialize daerahMasjids with all available daerah names
$daerahMasjids = [];
foreach ($daerahs as $daerah) {
    $daerahMasjids[$daerah['daerah_name']] = []; // Ensure all daerahs appear
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
    <title>MENU UTAMA JHEIPP</title>
    <link rel="stylesheet" href="../Styles/styles.css">
    <script src="<?php echo '../Script/dropdown_pta.js'; ?>"></script>
</head>
<body>
<?php require '../include/header.php'; ?>
    <div class="header">
        <h1>SENARAI MASJID MENGIKUT DAERAH</h1>
    </div>

    <div class="container">
        <?php foreach ($daerahMasjids as $daerahName => $masjids): ?>
            <button class="daerah-button" onclick="toggleMasjidList('<?php echo htmlspecialchars($daerahName); ?>')">
                <?php echo htmlspecialchars($daerahName); ?>
            </button>
            <div class="masjid-list" id="masjid-list-<?php echo htmlspecialchars($daerahName); ?>" style="list-style-type: none; padding: 0;">
                <?php if (!empty($masjids)): ?>
                    <ul style="list-style-type: none; padding: 0;">
                        <?php foreach ($masjids as $masjid): ?>
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ccc;">
                                <span class="masjid-name" style="flex: 1;"><?php echo !empty($masjid['masjid_name']) ? htmlspecialchars($masjid['masjid_name']) : '-'; ?></span>
                                <div class="button-group" style="display: flex; gap: 10px;">
                                    <a href="../backend/form1_JHEPP.php?masjid_id=<?php echo $masjid['masjid_id']; ?>" class="mb-2 ml-2">
                                        <button type="button" class="btn btn-primary">BORANG PENCALONAN 1 <?php if($masjid['form_1'] > 0){ ?><span class="badge bg-danger"><?php echo "Baru"; ?></span><?php } ?></button>
                                    </a>
                                    <a href="../backend/form2_JHEPP.php?masjid_id=<?php echo $masjid['masjid_id']; ?>" class="mb-2 ml-2">
                                        <button type="button" class="btn btn-primary">BORANG PENCALONAN 2 <?php if($masjid['form_2'] > 0){ ?><span class="badge bg-danger"><?php echo "Baru"; ?></span><?php } ?></button>
                                    </a>
                                    <a href="../backend/form2_JHEPP_view.php?masjid_id=<?php echo $masjid['masjid_id']; ?>" class="mb-2 ml-2">
                                        <button type="button" class="btn btn-primary">VIEW<?php if($masjid['form_2'] > 0){ ?><span class="badge bg-danger"><?php echo "Baru"; ?></span><?php } ?></button>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>TIADA MASJID TERSEDIA</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php require '../include/footer.php'; ?>
</body>
</html>
