<?php
session_start();
include('../backend/connection.php'); // Include database connection

// Get the selected masjid from the URL
$masjidName = isset($_GET['masjid']) ? htmlspecialchars($_GET['masjid']) : 'Masjid Tidak Dikenal';
$level_id = $_SESSION['ulevel'];

// Get the first and last day of the current month
$firstDay = date('Y-m-01'); // Example: 2024-02-01
$lastDay = date('Y-m-t');   // Example: 2024-02-29

// Default values
$formAvailable = false;
$masjidID = null;
$historyAvailable = false; // To check if meeting history exists

try {
    if ($level_id == 2){
        // Query to get masjid_id and check if ANY form exists for the current month
        $stmt = $conn->prepare("SELECT m.masjid_id, COUNT(f.ic) AS form_count
        FROM masjid m
        LEFT JOIN sej6x_data_peribadi u ON u.id_masjid = m.masjid_id
        LEFT JOIN form f ON f.ic = u.no_ic AND f.date BETWEEN ? AND ?
        WHERE m.masjid_name = ?
        GROUP BY m.masjid_id");
    }
    else{
        // Query to get masjid_id and check if ANY form exists for the current month
        $stmt = $conn->prepare("SELECT m.masjid_id, COUNT(f.ic) AS form_count
        FROM masjid m
        LEFT JOIN sej6x_data_peribadi u ON u.id_masjid = m.masjid_id
        LEFT JOIN form f ON f.ic = u.no_ic AND f.date BETWEEN ? AND ?
        WHERE m.masjid_name = ?
        GROUP BY m.masjid_id");
    }

    $stmt->execute([$firstDay, $lastDay, $masjidName]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $masjidID = $result['masjid_id'];
        $formAvailable = $result['form_count'] > 0; // Check if any forms exist
    }

    // Query to check if meeting history exists for the mosque
    $stmtHistory = $conn->prepare("SELECT COUNT(*) AS history_count FROM meeting WHERE masjid_id = ?");
    $stmtHistory->execute([$masjidID]);
    $historyResult = $stmtHistory->fetch(PDO::FETCH_ASSOC);

    if ($historyResult) {
        $historyAvailable = $historyResult['history_count'] > 0;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PILIH BORANG PENCALONAN</title>
    <link rel="stylesheet" href="../Styles/styles.css">
</head>
<body>

<div class="popup-container-PTA">
    <div class="popup-content-PTA">
        <h2>ANDA TELAH MEMILIH:</h2>
        <p><strong><?php echo $masjidName; ?></strong></p>
        <p>SILA PILIH BORANG PENCALONAN:</p>

        <?php if ($level_id == 2): ?>
            <!-- PTA Forms -->
            <button 
                onclick="redirectToForm('form1_PTA.php', '<?php echo $masjidID; ?>')" 
                <?php echo ($formAvailable) ? '' : 'disabled'; ?> 
                class="<?php echo ($formAvailable) ? 'active-button' : 'disabled-button'; ?>">
                BORANG PENCALONAN 1(PTA)
            </button>

            <button 
                onclick="redirectToForm('form2_PTA - Copy.php', '<?php echo $masjidID; ?>')" 
                <?php echo ($formAvailable) ? '' : 'disabled'; ?> 
                class="<?php echo ($formAvailable) ? 'active-button' : 'disabled-button'; ?>">
                BORANG PENCALONAN 2(PTA)
            </button>

        <?php else: ?>
            <!-- Non-PTA Forms -->
            <button 
                onclick="redirectToForm('form1_JHEPP.php', '<?php echo $masjidID; ?>')" 
                <?php echo ($formAvailable) ? '' : 'disabled'; ?> 
                class="<?php echo ($formAvailable) ? 'active-button' : 'disabled-button'; ?>">
                BORANG PENCALONAN 1(Non-PTA)
            </button>

            <button 
                onclick="redirectToForm('form2_JHEPP.php', '<?php echo $masjidID; ?>')" 
                <?php echo ($formAvailable) ? '' : 'disabled'; ?> 
                class="<?php echo ($formAvailable) ? 'active-button' : 'disabled-button'; ?>">
                BORANG PENCALONAN 2(Non-PTA)
            </button>
        <?php endif; ?>

        <!-- History Button -->
        <button 
            onclick="redirectToHistory('<?php echo $masjidID; ?>')" 
            <?php echo ($historyAvailable) ? '' : 'disabled'; ?> 
            class="<?php echo ($historyAvailable) ? 'active-button' : 'disabled-button'; ?>">
            SEJARAH
        </button>

        <br><br>
        <button class="close-btn" onclick="closePopup()">TUTUP</button>
    </div>
</div>

<script>
    function redirectToForm(formPage, masjidID) {
        if (masjidID) {
            window.location.href = formPage + "?masjid_id=" + encodeURIComponent(masjidID);
        }
    }

    function redirectToHistory(masjidID) {
        if (masjidID) {
            window.location.href = "meeting_history.php?masjid_id=" + encodeURIComponent(masjidID);
        }
    }

    function closePopup() {
        window.history.back(); // Go back to previous page
    }
</script>

<style>
    button {
        padding: 10px;
        margin: 10px;
        font-size: 16px;
        cursor: pointer;
        border: none;
        border-radius: 5px;
    }

    .active-button {
        background: blue;
        color: white;
        cursor: pointer;
    }

    .disabled-button {
        background: grey;
        color: white;
        cursor: not-allowed;
    }

    .close-btn {
        background: red;
        color: white;
    }
</style>

</body>
</html>
