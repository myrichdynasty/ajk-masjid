<?php
session_start();
require '../backend/connection.php'; // Adjust path if needed

// Fetch all booking records with masjid_name
try {
    $stmt = $conn->prepare("
        SELECT b.*, m.masjid_name, u.name
        FROM booking b
        JOIN masjid m ON b.masjid_id = m.masjid_id
        JOIN user u on u.user_id = b.user_id
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
    <title>SEMAK TEMPAHAN</title>
</head>
<body>
<?php require '../include/header.php'; ?>


    <!-- Show Alert Message (if any) -->
    <?php
    if (isset($_SESSION['message'])) {
        echo "<script>alert('" . $_SESSION['message'] . "');</script>";
        unset($_SESSION['message']); // Clear the message after displaying
    }

    if (isset($_SESSION['error'])) {
        echo "<script>alert('" . $_SESSION['error'] . "');</script>";
        unset($_SESSION['error']); // Clear the error after displaying
    }
    ?>

    <h2 class="text-center mt-4">SENARAI CALON JAWATANKUASA KARIAH</h2>

    <div class="table-responsive">
    <table class="table table-bordered text-center">
    <thead class="table-primary text-white">
    <tr>
    <th>NO.</th>
        <th>NAMA</th>
        <th>CADANGAN TARIKH</th>
        <th>KEMASKINI TARIKH BARU</th>
        <th>CADANGAN MASA</th>
        <th>KEMASKINI MASA BARU</th>
        <th>TEMPAT</th>
        <th>STATUS</th>
        <th>KOMEN</th>
        <th>NAMA MASJID</th>
        <th>TINDAKAN</th>
</thead>
    </tr>
    <?php foreach ($bookings as $booking): ?>
        <tr>
            <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
            <td><?php echo htmlspecialchars($booking['name']); ?></td>
            <td><?php echo htmlspecialchars($booking['date']); ?></td>
            <td>
                <form action="updatestatus.php" method="POST">
                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($booking['date']); ?>">
            </td>
            <td><?php echo htmlspecialchars(date('H:i', strtotime($booking['time']))); ?></td>
            <td>
                    <input type="time" name="time" value="<?php echo htmlspecialchars($booking['time']); ?>">
            </td>
            <td><?php echo htmlspecialchars($booking['place']); ?></td>
            <td>
                    <select name="status_code">
                        <option value="0" <?php echo ($booking['status_code'] == 0) ? 'selected' : ''; ?>>MENUNGGU KELULUSAN</option>
                        <option value="1" <?php echo ($booking['status_code'] == 1) ? 'selected' : ''; ?>>LULUS</option>
                        <option value="2" <?php echo ($booking['status_code'] == 2) ? 'selected' : ''; ?>>TOLAK</option>
                        <option value="3" <?php echo ($booking['status_code'] == 3) ? 'selected' : ''; ?>>DIKEMASKINI</option>
                    </select>
            </td>
            <td>
                    <textarea name="comment" rows="2" cols="20"><?php echo htmlspecialchars($booking['comment']); ?></textarea>
            </td>
            <td><?php echo htmlspecialchars($booking['masjid_name']); ?></td> <!-- Display masjid name -->
            <td>
                    <button type="submit" class="btn btn-primary mb-2">KEMASKINI</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<div class="text-center mt-3">
    <a href="../backend/mainpage2.php" class="btn btn-primary mb-2">KEMBALI KE MENU UTAMA</a>
</div>

    <?php require '../include/footer.php'; ?>

</body>
</html>