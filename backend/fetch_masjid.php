<?php
include('../backend/connection.php');

if (isset($_GET['daerah_id']) && !empty($_GET['daerah_id'])) {
    $daerah_id = $_GET['daerah_id'];

    try {
        // Fetch masjids based on daerah_id
        $stmt = $conn->prepare("SELECT masjid_id, masjid_name FROM masjid WHERE daerah_id = :daerah_id ORDER BY masjid_name ASC");
        $stmt->bindParam(':daerah_id', $daerah_id, PDO::PARAM_INT);
        $stmt->execute();
        $masjids = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($masjids as $masjid) {
            echo "<option value='" . htmlspecialchars($masjid['masjid_id']) . "'>" . htmlspecialchars($masjid['masjid_name']) . "</option>";
        }
    } catch (PDOException $e) {
        echo "<option value=''>Error loading masjids</option>";
    }
}
?>
