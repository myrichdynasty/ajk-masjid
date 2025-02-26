<?php
session_start(); // Start session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../frontend/login.html"); // Redirect to login if not logged in
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
$firstDay = date('Y-m-01'); // First day of current month
$lastDay = date('Y-m-t');   // Last day of current month

$masjid_id = $_SESSION['masjid_id'];

// Include database connection
require '../backend/connection.php';

// Fetch bookings for the logged-in user
try {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Fetch the status of Form 1
try {
    $sql = "
        SELECT f.*, u.nama_penuh AS user_name, u.no_ic AS user_ic, u.no_hp AS user_phone, 
        u.alamat_terkini AS user_address, u.pekerjaan AS user_job, u.id_masjid AS masjid_id, m.masjid_name, 
        v1.name AS verify_name_1, v2.name AS verify_name_2, v3.name AS verify_name_3
        FROM form f 
        JOIN sej6x_data_peribadi u ON f.ic = u.no_ic
        JOIN masjid m ON u.id_masjid = m.masjid_id 
        LEFT JOIN user v1 ON f.verify_id_1 = v1.user_id
        LEFT JOIN user v2 ON f.verify_id_2 = v2.user_id
        LEFT JOIN user v3 ON f.verify_id_3 = v3.user_id
        WHERE DATE(f.reg_date) BETWEEN :firstdate AND :lastdate
        AND m.masjid_id = :masjid_id
        ORDER BY f.total_vote DESC
    ";
    $sql2 = "
        SELECT f.*, u.nama_penuh AS user_name, u.no_ic AS user_ic, u.no_hp AS user_phone, 
        u.alamat_terkini AS user_address, u.pekerjaan AS user_job, u.id_masjid AS masjid_id, m.masjid_name, 
        v1.name AS verify_name_1, v2.name AS verify_name_2, v3.name AS verify_name_3
        FROM form f 
        JOIN sej6x_data_peribadi u ON f.ic = u.no_ic
        JOIN masjid m ON u.id_masjid = m.masjid_id 
        LEFT JOIN user v1 ON f.verify_id_1 = v1.user_id
        LEFT JOIN user v2 ON f.verify_id_2 = v2.user_id
        LEFT JOIN user v3 ON f.verify_id_3 = v3.user_id
        WHERE DATE(f.reg_date) BETWEEN :firstdate AND :lastdate
        AND m.masjid_id = :masjid_id
        ORDER BY f.total_vote DESC LIMIT 10
    ";
    $sql3 = "
        SELECT l.*, m.masjid_name
        FROM log l
        JOIN masjid m ON l.id_masjid = m.masjid_id
        AND m.masjid_id = :masjid_id
    ";

    $stmt = $conn->prepare($sql);
    $stmt2 = $conn->prepare($sql2);
    $stmt3 = $conn->prepare($sql3);

    $stmt->execute([
        'firstdate' => $firstDay,
        'lastdate' => $lastDay,
        'masjid_id' => $masjid_id
    ]);
    $stmt2->execute([
        'firstdate' => $firstDay,
        'lastdate' => $lastDay,
        'masjid_id' => $masjid_id
    ]);
    $stmt3->execute([
        'masjid_id' => $masjid_id
    ]);

    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $forms2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $forms3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MASJID</title>
</head>
<body>
<?php require '../include/header.php'; ?>
<div class="d-flex justify-content-center flex-wrap gap-3 text-center mt-4 mb-4">
    <button type="button" class="btn btn-primary mb-2" disabled>MESYUARAT AGUNG TAHUNAN</button>
    <a href="choosedate.php" class="mb-2">
        <button type="button" class="btn btn-primary">MESYUARAT AGUNG PENCALONAN JAWATANKUASA KARIAH</button>
    </a>
</div>

    <h2 style="text-align: center; margin-top: 30px;">STATUS CADANGAN TARIKH MESYUARAT</h2>

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
        <tr>
            <th>NO.</th>
            <th>TARIKH</th>
            <th>MASA</th>
            <th>TEMPAT</th>
            <th>STATUS</th>
            <th>KOMEN</th>
            <th>TINDAKAN</th>
    </thead>
    </tbody>
        </tr>
        <?php if (empty($bookings)): ?>
            <tr><td colspan="7">TIADA TEMPAHAN DIJUMPAI.</td></tr>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                    <td><?php echo htmlspecialchars($booking['date']); ?></td>
                    <td><?php echo date('H:i', strtotime($booking['time'])); ?></td>
                    <td><?php echo htmlspecialchars($booking['place']); ?></td>
                    <td>
                        <?php
                        if ($booking['status_code'] == 0) {
                            echo "MENUNGGU KELULUSAN";
                        } elseif ($booking['status_code'] == 1) {
                            echo "DILULUSKAN";
                        } elseif ($booking['status_code'] == 2) {
                            echo "DITOLAK";
                        } else {
                            echo "DIKEMASKINI";
                        }
                        ?>
                    </td>
                    <td><?php echo !empty($booking['comment']) ? htmlspecialchars($booking['comment']) : '-'; ?></td>
                    <td>
                        <a href="../backend/form1_masjid.php" class="mb-2 ml-2">
                            <button type="button" class="btn btn-primary">BORANG PENCALONAN 1</button>
                        </a>
                        <a href="../backend/form2_masjid.php">
                            <button type="button" class="btn btn-primary">BORANG PENCALONAN 2</button>
                        </a>
                        <?php if ($booking['status_code'] == 0): ?>
                            <form action="../backend/cancel_booking.php" method="POST">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                <button type="submit" class="btn btn-danger px-4 py-2 fw-bold rounded">Cancel</button>

                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- <h2 style="text-align: center; margin-top: 30px;">BORANG PENCALONAN 1</h2>

    <?php if (!empty($forms)): ?>
        
    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
                <tr>
                    <th>NAMA</th>
                    <th>NO KAD PENGENALAN</th>
                    <th>NO TELEFON</th>
                    <th>NAMA MASJID</th>
                    <th>UNDI</th>
                    <th>JAWATAN</th>
                    </thead>
                    <tbody>
                </tr>
                <?php foreach ($forms as $form): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($form['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($form['user_ic']); ?></td>
                        <td><?php echo htmlspecialchars($form['user_phone']); ?></td>
                        <td><?php echo htmlspecialchars($form['masjid_name']); ?></td>
                        <td><?php echo htmlspecialchars($form['total_vote']); ?></td>
                        <td><?php echo htmlspecialchars($form['role']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center;">TIADA REKOD BORANG PENCALONAN 1 DIJUMPAI.</p>
    <?php endif; ?>
    </table> -->

    <!-- <h2 style="text-align: center; margin-top: 30px;">STATUS BORANG PENCALANON 2</h2>
    
    <?php if (!empty($forms2)): ?>
        <table class="table table-bordered text-center">
        <thead class="table-primary text-white">
                <tr>
                <th>NO</th>
                <th>NAMA</th>
                <th>NO KAD PENGENALAN</th>
                <th>NO TELEFON</th>
                <th>ALAMAT</th>
                <th>PEKERJAAN</th>
                <th>NAMA MASJID</th>
                <th>NAMA PENGESAH 1</th>
                <th>NAMA PENGESAH 2</th>
                <th>NAMA PENGESAH 3</th>
                <th>JAWATAN</th>
                <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                foreach ($forms2 as $form): 
                    // Conditional display based on status_code
                    $statusText = 'TIADA';
                    if ($form['status_code'] == 2) {
                        $statusText = 'DILULUSKAN OLEH PTA';
                    } elseif ($form['status_code'] == 3) {
                        $statusText = 'DILULUSKAN OLEH JHEPP';
                    }
                ?>
                    <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($form['name']); ?></td>
                    <td><?php echo htmlspecialchars($form['ic']); ?></td>
                    <td><?php echo htmlspecialchars($form['phone_num']); ?></td>
                    <td><?php echo htmlspecialchars($form['address']); ?></td>
                    <td><?php echo htmlspecialchars($form['job']); ?></td>
                    <td><?php echo htmlspecialchars($form['masjid_name']); ?></td>
                    <td><?php echo htmlspecialchars($form['verify_name_1']); ?></td>
                    <td><?php echo htmlspecialchars($form['verify_name_2']); ?></td>
                    <td><?php echo htmlspecialchars($form['verify_name_3']); ?></td>
                    <td><?php echo htmlspecialchars($form['role']); ?></td>
                    <td><?php echo $statusText; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; margin-top: 30px;">TIADA DATA DIJUMPAI BAGI TARIKH YANG TERPILIH.</p>
    <?php endif; ?> -->

    <?php require '../include/footer.php'; ?>
</body>
</html>