<?php
session_start();
include('connection.php');

$searchIC = '';
// Initialize session array if not set
if (!isset($_SESSION['search_results'])) {
    $_SESSION['search_results'] = [];
}

$masjid_id = $_SESSION['masjid_id'];

date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
$current_date = date('Y-m-d'); // Get current date an   d time in GMT+8

// Query to check if a booking exists for today
$sql = "SELECT * FROM booking b 
JOIN user u ON b.user_id = u.user_id
JOIN masjid m ON u.masjid_id = m.masjid_id 
WHERE b.date = :booking_date AND m.masjid_id = :masjid_id AND b.status_code = 1 ";

$stmt = $conn->prepare($sql);
$stmt->execute([
    'booking_date' => $current_date,
    'masjid_id' => $masjid_id
]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: mainpage.php");
    exit();
}

// Fetch bookings for the logged-in user
try {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Query to check if a form exists for today
$sql = "SELECT f.*, u.name, u.ic, u.phone, u.address, u.job, u.masjid_id FROM form f 
        JOIN user u ON f.ic = u.ic
        JOIN masjid m ON u.masjid_id = m.masjid_id 
        WHERE DATE(f.reg_date) = :booking_date AND m.masjid_id = :masjid_id AND f.status_code = 1";

$stmt = $conn->prepare($sql);
$stmt->execute([
    'booking_date' => $current_date,
    'masjid_id' => $masjid_id
]);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Append today's forms to search_results, avoiding duplicates
$existingICs = array_column($_SESSION['search_results'], 'ic');

foreach ($forms as &$form) {
    if (!isset($form['total_vote'])) { 
        $form['total_vote'] = 0; // Ensure total_vote is always set
    }
    if (!in_array($form['ic'], $existingICs)) {
        $_SESSION['search_results'][] = $form;
    }
}

// Handle search request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_ic'])) {
    $searchIC = trim($_POST['search_ic']);

    if (!empty($searchIC)) {
        try {
            $stmt = $conn->prepare("SELECT nama_penuh AS username, 
                                    nama_penuh AS pswd, nama_penuh AS name, id_masjid AS masjid_id, jantina AS gender,
                                    no_ic AS ic, no_hp AS phone, alamat_terkini AS address, pekerjaan AS job 
                                    FROM sej6x_data_peribadi WHERE no_ic = :ic");
            $stmt->execute(['ic' => $searchIC]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if ($results) {
                foreach ($results as &$user) {
                    // Check if the masjid_id is different
                    if ($user['masjid_id'] != $masjid_id) {
                        echo "<script>alert('Anda Bukan Ahli Qaryah');</script>";
                        continue; // Skip adding this user
                    }
    
                    // Ensure total_vote is set
                    if (!isset($user['total_vote'])) { 
                        $user['total_vote'] = 0;
                    }
    
                    // Check for duplicates before adding
                    $existingICs = array_column($_SESSION['search_results'], 'ic');
                    if (!in_array($user['ic'], $existingICs)) {
                        $_SESSION['search_results'][] = $user;
                    } else {
                        echo "<script>alert('Warning: This IC is already in the table!');</script>";
                    }
                }
            } else {
                echo "<script>alert('No user found with this IC!');</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Error fetching data: " . addslashes($e->getMessage()) . "');</script>";
        }
    }    
}

// Handle total_vote update for individual users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vote'])) {
    $updateIC = $_POST['ic'];
    $newVote = intval($_POST['total_vote']);

    foreach ($_SESSION['search_results'] as &$user) {
        if ($user['ic'] === $updateIC) {
            $user['total_vote'] = $newVote;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARIAN NAMA-NAMA CALON</title>
</head>
<body>
    <?php require '../include/header.php'; ?>
    <div class="container d-flex flex-column align-items-center justify-content-center min-vh-80">
    <h1 class="text-center mb-4">MASJID JAMEK AL-HIDAYAH</h1> <!-- Added mb-4 for spacing -->
    <h1 class="text-center mb-4">MESYUARAT AGUNG PENCALONAN JAWATANKUASA BAGI PENGGAL 2025-2028</h1>

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
        <tr>
            <th>TARIKH</th>
            <th>MASA</th>
            <th>TEMPAT</th>
            <th>NAMA CADANGAN PENGERUSI MESYUARAT</th>
    </thead>
    </tbody>
        </tr>
        <?php if (empty($bookings)): ?>
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

<!-- Existing search section -->
<div class="search-section text-center mb-4"> <!-- Added mb-4 for spacing -->
    <form method="POST" action="" class="d-flex justify-content-center align-items-center gap-2 w-100 mx-auto">
        <div class="d-flex align-items-center">
            <label for="search_ic" class="me-2 mb-0">MASUKKAN NO KAD PENGENALAN:</label>
            <input type="text" id="search_ic" name="search_ic" pattern="\d{12,}" maxlength="12" required class="form-control text-center w-75">
        </div>
        <button type="submit" class="btn btn-primary">CARI</button>
    </form>
</div>



    <?php if (!empty($_SESSION['search_results'])): ?>
        <h2 class="text-center mt-4">HASIL CARIAN:</h2>
        <div class="table-responsive">
        <table class="table table-bordered text-center">
    <thead class="table-primary text-white">
        <tr>
            <th>NAMA</th>
            <th>JANTINA</th>
            <th>NO KAD PENGENALAN</th>
            <th>NO TELEFON</th>
            <th>ALAMAT</th>
            <th>PEKERJAAN</th>
            <th>JUMLAH UNDI</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($_SESSION['search_results'] as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['gender']); ?></td>
                <td><?php echo htmlspecialchars($row['ic']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['address']); ?></td>
                <td><?php echo htmlspecialchars($row['job']); ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="ic" value="<?php echo htmlspecialchars($row['ic']); ?>">
                        <input type="number" name="total_vote" min="1" value="<?php echo isset($row['total_vote']) ? htmlspecialchars($row['total_vote']) : '0'; ?>" required class="form-control w-50 mx-auto">
                        <button type="submit" name="update_vote" class="btn btn-success btn-sm mt-2">SET UNDI</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
        </div>

        <form method="POST" action="db_insert_form.php" class="text-center">
            <?php foreach ($_SESSION['search_results'] as $row): ?>
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][ic]" value="<?php echo $row['ic']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][name]" value="<?php echo $row['name']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][masjid_id]" value="<?php echo $row['masjid_id']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][gender]" value="<?php echo $row['gender']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][phone]" value="<?php echo $row['phone']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][address]" value="<?php echo $row['address']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][job]" value="<?php echo $row['job']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][booking_id]" value="<?php echo $_GET['booking_id']; ?>">
                <input type="hidden" name="users[<?php echo $row['ic']; ?>][total_vote]" min="1" value="<?php echo isset($row['total_vote']) ? $row['total_vote'] : '0'; ?>" required>
                
            <?php endforeach; ?>
            <button type="submit" name="update_all" class="btn btn-primary mt-3">SIMPAN</button>
        </form>
    <?php else: ?>
        <p class="text-center text-muted mt-4">MAKLUMAT CALON AKAN DIPAPARKAN DISINI.</p>
    <?php endif; ?>

    <?php require '../include/footer.php'; ?>
</div>
</body>
</html>