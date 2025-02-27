<?php
session_start();
include('connection.php');

$searchIC = '';
// Initialize session array if not set
if (isset($_SESSION['search_results'])) {
    $_SESSION['search_results'] = [];
}

// Retrieve masjid_id from URL
$masjid_id = isset($_GET['masjid_id']) ? intval($_GET['masjid_id']) : null;

date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
// Get the first and last day of the current month
$firstDay = date('Y-m-01'); // Example: 2024-02-01
$lastDay = date('Y-m-t');   // Example: 2024-02-29
$current_date = date('Y-m-d'); // Get current date and time in GMT+8


// Query to check if a form in the month
$sql = "SELECT f.*, u.nama_penuh AS name, u.no_ic AS ic, u.no_hp AS phone, 
        u.alamat_terkini AS address, u.pekerjaan AS job, u.id_masjid AS masjid_id, m.masjid_name
        FROM form f 
        JOIN sej6x_data_peribadi u ON f.ic = u.no_ic
        JOIN masjid m ON u.id_masjid = m.masjid_id 
        WHERE DATE(f.reg_date) BETWEEN :firstdate AND :lastdate
        AND m.masjid_id = :masjid_id 
        ORDER BY f.total_vote DESC LIMIT 10";

// Generate debug query by replacing placeholders with actual values
$debug_sql = str_replace(
    [':firstdate', ':lastdate', ':masjid_id'],
    ["'$firstDay'", "'$lastDay'", "'$masjid_id'"],
    $sql
);

$stmt = $conn->prepare($sql);
$stmt->execute([
    'firstdate' => $firstDay,
    'lastdate' => $lastDay,
    'masjid_id' => $masjid_id
]);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Fetch bookings for the logged-in user
try {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE masjid_id = :masjid_id");
    $stmt->bindParam(':masjid_id', $_SESSION['masjid_id']);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

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
                                    nama_penuh AS pswd, nama_penuh AS name, id_masjid AS masjid_id,
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

// Handle total_vote and role update for individual users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vote'])) {

    // Extract the first key in the users array (which is the IC number)
    $updateKey = key($_POST['users']); 

    // Use form_id if it exists, otherwise use IC to prevent errors
    $updateform = $_POST['users'][$updateKey]['form_id'] ?? $updateKey;


    //$newVote = intval($_POST['total_vote']);
    $newRole = $_POST['role']; // Get selected role

    // Update total_vote and role in the session array
    foreach ($_SESSION['search_results'] as &$user) {
        if ((isset($user['form_id']) && $user['form_id'] == $updateform) || 
            (isset($user['ic']) && $user['ic'] == $updateform)) {
            //$user['total_vote'] = $newVote;
            $user['role'] = $newRole;
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
    <title>BORANG PENCALONAN 2</title>
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


    <?php 
    if (!empty($_SESSION['search_results'])): ?>
        <h2>HASIL CARIAN:</h2>
        <table class="table table-bordered text-center">
        <thead class="table-primary text-white">
                    <tr>
                        <th>NO</th>
                        <th>NAMA</th>
                        <th>ID BORANG</th>
                        <th>NO KAD PENGENALAN</th>
                        <th>NO TELEFON</th>
                        <th>ALAMAT</th>
                        <th>PEKERJAAN</th>
                        <th>JUMLAH UNDI</th>
                        <th>JAWATAN</th>
                        <th>TINDAKAN</th>
                    </thead>
                    <tbody>
                    </tr>

                    <?php
                    $counter = 1; // Initialize counter
                    foreach ($_SESSION['search_results'] as $row): ?>
                        <tr>
							<td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo isset($row['form_id']) && !empty($row['form_id']) ? htmlspecialchars($row['form_id']) : 'Not determined yet'; ?></td>
                            <td><?php echo htmlspecialchars($row['ic']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo htmlspecialchars($row['job']); ?></td>
    <form method="POST" action="">
        <?php if (!isset($row['total_vote'])): ?>
    <td>
    <?php $key = isset($row['form_id']) ? $row['form_id'] : $row['ic']; ?>
    <input type="hidden" name="users[<?php echo $key; ?>][ic]" value="<?php echo htmlspecialchars($row['ic']); ?>">
    <?php echo htmlspecialchars($row['total_vote']); ?></td>
    </td>
<?php else: ?>
    <td>
    <?php $key = isset($row['form_id']) ? $row['form_id'] : $row['ic']; ?>
        <input type="hidden" name="users[<?php echo $key; ?>][ic]" value="<?php echo htmlspecialchars($row['ic']); ?>">
        <?php echo htmlspecialchars($row['total_vote']); ?></td>
    </td>
<?php endif; ?>

    <td>
        <!-- Ensure role exists, default to "Please select a role" -->
        <select class="form-control" style="width: 158px;" name="role">
            <option value="" disabled <?php echo (!isset($row['role']) || empty($row['role'])) ? 'selected' : ''; ?>>SILA PILIH JAWATAN</option>
            <option value="PENGERUSI" <?php echo (isset($row['role']) && $row['role'] == 'PENGERUSI') ? 'selected' : ''; ?>>PENGERUSI</option>
            <option value="TIMBALAN PENGERUSI" <?php echo (isset($row['role']) && $row['role'] == 'TIMBALAN PENGERUSI') ? 'selected' : ''; ?>>TIMBALAN PENGERUSI</option>
            <option value="SETIAUSAHA" <?php echo (isset($row['role']) && $row['role'] == 'SETIAUSAHA') ? 'selected' : ''; ?>>SETIAUSAHA</option>
            <option value="BENDAHARI" <?php echo (isset($row['role']) && $row['role'] == 'BENDAHARI') ? 'selected' : ''; ?>>BENDAHARI</option>
            <option value="AJK" <?php echo (isset($row['role']) && $row['role'] == 'AJK') ? 'selected' : ''; ?>>AJK</option>
            <option value="AJK WANITA" <?php echo (isset($row['role']) && $row['role'] == 'AJK WANITA') ? 'selected' : ''; ?>>AJK WANITA</option>
            <option value="PEMERIKSA KIRA-KIRA" <?php echo (isset($row['role']) && $row['role'] == 'PEMERIKSA KIRA-KIRA') ? 'selected' : ''; ?>>PEMERIKSA KIRA-KIRA</option>

        </select>
    </td>

    <td>
    <button type="submit" name="update_vote" class="btn btn-primary mb-2" value="1">KEMASKINI JAWATAN</button>
    </form>
</td>

                                </tr>
                           
                    <?php endforeach; ?>
                </tbody>
            </table>
            <form method="POST" action="db_update_form_PTA.php">
    <?php foreach ($_SESSION['search_results'] as $row): ?>

        <?php $key = isset($row['form_id']) ? $row['form_id'] : $row['ic']; ?>
            <input type="hidden" name="users[<?php echo $key; ?>][ic]" value="<?php echo htmlspecialchars($row['ic']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][name]" value="<?php echo htmlspecialchars($row['name']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][reg_date]" value="<?php echo isset($row['reg_date']) ? htmlspecialchars($row['reg_date']) : date('Y-m-d'); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][masjid_id]" value="<?php echo htmlspecialchars($row['masjid_id']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][phone]" value="<?php echo htmlspecialchars($row['phone']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][address]" value="<?php echo htmlspecialchars($row['address']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][job]" value="<?php echo htmlspecialchars($row['job']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][role]" value="<?php echo isset($row['role']) ? htmlspecialchars($row['role']) : ''; ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][total_vote]" value="<?php echo isset($row['total_vote']) ? htmlspecialchars($row['total_vote']) : '0'; ?>" required>
        <?php endforeach; ?>
        
        <div class="export-buttons text-center">
            <button type="submit" name="update_all" class="btn btn-primary mb-2">SIMPAN DAN HANTAR</button>
        </div>
        </form>
    <?php else: ?>
        <p>TIADA KEPUTUSAN DIJUMPAI UNTUK TARIKH YANG DIBERIKAN.</p>
    <?php endif; ?>
    <div class="export-buttons text-center">
        <button onclick="window.location.href = 'form_PTA.php'" class="btn btn-primary mb-2">KEMBALI</button>
    </div>
    <?php require '../include/footer.php'; ?>
</body>
</html>