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
        FROM form_2 f
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

// Query to check if a booking exists for the selected masjid
$sql = "SELECT * FROM booking b 
JOIN user u ON b.user_id = u.user_id
JOIN masjid m ON u.masjid_id = m.masjid_id 
WHERE m.masjid_id = :masjid_id AND b.status_code = 1 ";

$stmt = $conn->prepare($sql);
$stmt->execute([
    'masjid_id' => $masjidID
]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch bookings for the logged-in user
try {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE masjid_id = :masjid_id");
    $stmt->bindParam(':masjid_id', $_SESSION['masjid_id']);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Query to check if a meeting exists for the selected masjid
$sql = "SELECT * FROM meeting t
JOIN masjid m ON t.masjid_id = m.masjid_id 
WHERE m.masjid_id = :masjid_id ";

$stmt = $conn->prepare($sql);
$stmt->execute([
    'masjid_id' => $masjidID
]);
$meeting = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch meetings for the logged-in user
try {
    $stmt = $conn->prepare("SELECT * FROM meeting WHERE masjid_id = :masjid_id");
    $stmt->bindParam(':masjid_id', $_SESSION['masjid_id']);
    $stmt->execute();
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Group meetings by date, time, and place
$groupedMeetings = [];
foreach ($meetings as $meetingGroup) {
    $key = $meetingGroup['meeting_date'] . '|' . $meetingGroup['meeting_time'] . '|' . $meetingGroup['meeting_place'];
    if (!isset($groupedMeetings[$key])) {
        $groupedMeetings[$key] = [
            'meeting_date' => $meetingGroup['meeting_date'],
            'meeting_time' => $meetingGroup['meeting_time'],
            'meeting_place' => $meetingGroup['meeting_place'],
            'attendees' => [],
        ];
    }
    $groupedMeetings[$key]['attendees'][] = [
        'meeting_nama_ahli' => $meetingGroup['meeting_nama_ahli'],
        'meeting_jawatanAhli' => $meetingGroup['meeting_jawatanAhli'],
        'meeting_jabatanAhli' => $meetingGroup['meeting_jabatanAhli'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DATA CALON JAWATANKUASA UNTUK <?php echo htmlspecialchars($masjidName); ?></title>
</head>
<body>
<?php require '../include/header.php'; ?>
    <h2 class="text-center">DATA CALON JAWATANKUASA UNTUK <?php echo htmlspecialchars($masjidName); ?></h2>
    <p class="text-center">REKOD DARI <strong><?php echo $firstDay; ?></strong> HINGGA <strong><?php echo $lastDay; ?></strong></p>

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
        <tr>
            <th>TARIKH</th>
            <th>MASA</th>
            <th>TEMPAT</th>
            <th>NAMA PENGERUSI MESYUARAT</th>
    </thead>
    </tbody>
        </tr>
        <?php if (empty($booking)): ?>
            <tr><td colspan="7">TIADA DATA DIJUMPAI.</td></tr>
        <?php else: ?>
            <tr>
                <td><?php echo htmlspecialchars($booking['date']); ?></td>
                <td><?php echo date('H:i', strtotime($booking['time'])); ?></td>
                <td><?php echo htmlspecialchars($booking['place']); ?></td>
                <td>
                    1.<?php echo htmlspecialchars($booking['nama_cadangan1']); ?>
                    <br>
                    2.<?php echo htmlspecialchars($booking['nama_cadangan2']); ?>
                </td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
                <tr>
                    <th>TARIKH</th>
                    <th>MASA</th>
                    <th>TEMPAT</th>
                    <th>NAMA AHLI MESYUARAT</th>
                    <th>JAWATAN</th>
                    <th>JABATAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groupedMeetings)): ?>
                    <tr>
                        <td colspan="6">TIADA DATA DIJUMPAI.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($groupedMeetings as $group): ?>
                        <?php $rowCount = count($group['attendees']); ?>
                        <?php foreach ($group['attendees'] as $index => $attendee): ?>
                            <tr>
                                <?php if ($index === 0): ?>
                                    <!-- Display meeting details only in the first row -->
                                    <td rowspan="<?php echo $rowCount; ?>">
                                        <?php echo htmlspecialchars($group['meeting_date']); ?>
                                    </td>
                                    <td rowspan="<?php echo $rowCount; ?>">
                                        <?php echo date('H:i', strtotime($group['meeting_time'])); ?>
                                    </td>
                                    <td rowspan="<?php echo $rowCount; ?>">
                                        <?php echo htmlspecialchars($group['meeting_place']); ?>
                                    </td>
                                <?php endif; ?>
                                <!-- Display attendee details -->
                                <td><?php echo htmlspecialchars($attendee['meeting_nama_ahli']); ?></td>
                                <td><?php echo htmlspecialchars($attendee['meeting_jawatanAhli']); ?></td>
                                <td><?php echo htmlspecialchars($attendee['meeting_jabatanAhli']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($searchResults)): ?>
        <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
                <tr>
                    <th>NO.</th>
                    <th>NAMA</th>
                    <th>NO KAD PENGENALAN</th>
                    <th>NO TELEFON</th>
                    <th>ALAMAT</th>
                    <th>PEKERJAAN</th>
                    <th>JUMLAH UNDI</th>
                    <th>JAWATAN</th>
                    <th>TARIKH</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $counter = 1;
                    foreach ($searchResults as $row): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['ic']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo htmlspecialchars($row['job']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_vote']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">TIADA REKOD DIJUMPAI UNTUK <?php echo htmlspecialchars($masjidName); ?> DALAM BULAN INI.</p>
    <?php endif; ?>

    <div class="text-center">
            <button onclick="window.location.href = 'form_PTA.php'" class="btn btn-primary mb-2">KEMBALI</button>
        </div>
        

        <?php require '../include/footer.php'; ?>
</body>
</html>
