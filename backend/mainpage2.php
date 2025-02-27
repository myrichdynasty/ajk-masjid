<?php
session_start();
include('connection.php');


// Fetch all booking records with masjid_name
try {
    $stmt = $conn->prepare("
        SELECT b.*, m.masjid_name, u.name
        FROM booking b
        JOIN masjid m ON b.masjid_id = m.masjid_id
        JOIN user u on u.user_id = b.user_id
        Where b.status_code = 0
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
//print_r($bookings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PEJABAT AGAMA</title>
</head>
<body>
<?php require '../include/header.php'; ?>

<div class="d-flex justify-content-center flex-wrap gap-3 text-center mt-4 mb-4">
        <!-- Button for Mesyuarat Agung Tahunan (No action) -->
        <!-- <button type="button" class="btn btn-primary mb-2"  disabled>Mesyuarat Agung Tahunan</button> -->

        <!-- Button for Mesyuarat Agung Pencalonan Jawatankuasa Kariah (Redirect to choosedate.html) -->
        <a href="../backend/PejabatAgamaDaerah.php">
            <button type="button" class="btn btn-primary mb-2">SENARAI MESYUARAT AGUNG PENCALONAN JAWATANKUASA KARIAH</button>
        </a>

        <a href="../backend/form_PTA.php">
            <button type="button" class="btn btn-primary mb-2" >SENARAI CALON JAWATANKUASA KARIAH</button>
        </a>
    </div>
    
    <h2 class="text-center mt-4">SENARAI CADANGAN TARIKH MESYUARAT</h2>

    <div class="table-responsive">
    <table class="table table-bordered text-center">
    <thead class="table-primary text-white">
    <tr>
        <th>NO.</th>
        <th>NAMA MASJID</th>
        <th>CADANGAN TARIKH</th>
        <th>KEMASKINI TARIKH BARU</th>
        <th>CADANGAN MASA</th>
        <th>KEMASKINI MASA BARU</th>
        <th>TEMPAT</th>
        <th>STATUS</th>
        <th>KOMEN</th>
        <th>NAMA CADANGAN PENGERUSI MESYUARAT</th>
        <th>TINDAKAN</th>
</thead>
    </tr>
    <?php foreach ($bookings as $booking): ?>
        <tr>
            <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
            <td><?php echo htmlspecialchars($booking['masjid_name']); ?></td>
            <td><?php echo htmlspecialchars($booking['date']); ?></td>
            <td>
                <form action="updatestatus.php" method="POST">
                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($booking['date']); ?>">
            </td>
            <td><?php echo htmlspecialchars(date('H:i', strtotime($booking['time']))); ?></td>
            <td>
                    <input type="time" class="form-control" name="time" value="<?php echo htmlspecialchars($booking['time']); ?>">
            </td>
            <td><?php echo htmlspecialchars($booking['place']); ?></td>
            <td>
                    <select class="form-control" name="status_code" style="width: 158px;">
                        <option value="0" <?php echo ($booking['status_code'] == 0) ? 'selected' : ''; ?>>MENUNGGU KELULUSAN</option>
                        <option value="1" <?php echo ($booking['status_code'] == 1) ? 'selected' : ''; ?>>LULUS</option>
                        <option value="2" <?php echo ($booking['status_code'] == 2) ? 'selected' : ''; ?>>TOLAK</option>
                        <option value="3" <?php echo ($booking['status_code'] == 3) ? 'selected' : ''; ?>>DIKEMASKINI</option>
                    </select>
            </td>
            <td>
                    <textarea class="form-control" name="comment" rows="2" cols="20"><?php echo htmlspecialchars($booking['comment']); ?></textarea>
            </td>
            <td>
                1.<?php echo htmlspecialchars($booking['nama_cadangan1']); ?>
                <br>
                2.<?php echo htmlspecialchars($booking['nama_cadangan2']); ?></td>
            <td>
                    <button type="submit" class="btn btn-primary mb-2">KEMASKINI</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

    <?php require '../include/footer.php'; ?>
<!-- </body> -->
</html>