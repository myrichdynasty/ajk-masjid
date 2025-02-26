<?php
session_start();
include('../backend/connection.php'); // Include database connection

$daerah_id = $_SESSION['daerah_id'];

try {
    // Fetch the specific daerah name of the logged-in user
    $stmt = $conn->prepare("SELECT daerah_name FROM daerah WHERE daerah_id = :daerah_id");
    $stmt->bindParam(':daerah_id', $daerah_id, PDO::PARAM_INT);
    $stmt->execute();
    $daerah = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch all masjids only for the logged-in user's daerah
    $stmt = $conn->prepare("SELECT m.*, d.daerah_name FROM masjid m
        JOIN daerah d ON m.daerah_id = d.daerah_id
        WHERE m.daerah_id = :daerah_id
        ORDER BY m.masjid_id");
    $stmt->bindParam(':daerah_id', $daerah_id, PDO::PARAM_INT);
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
                <ul>
                    <?php if (!empty($masjids)): ?>
                        <?php foreach ($masjids as $masjid): ?>
                            <li onclick="openFormSelection('<?php echo htmlspecialchars($masjid['masjid_name']); ?>')">
                                <?php echo htmlspecialchars($masjid['masjid_id'] . " - " . $masjid['masjid_name']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>TIADA MASJID TERSEDIA</li>
                    <?php endif; ?>
                </ul>
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
