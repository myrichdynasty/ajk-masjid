<?php
session_start();
include('connection.php');

$searchIC = '';

// Initialize session array if not set
if (!isset($_SESSION['search_results'])) {
    $_SESSION['search_results'] = [];
}

$level_id = $_SESSION['ulevel'];
$masjid_id = isset($_GET['masjid_id']) ? intval($_GET['masjid_id']) : null;

date_default_timezone_set('Asia/Kuala_Lumpur');
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');
$current_date = date('Y-m-d'); // Get current date and time in GMT+8

$sql = "SELECT f.*, u.nama_penuh AS name, u.no_ic AS ic, u.no_hp AS phone, u.alamat_terkini AS address, 
        u.pekerjaan AS job, u.id_masjid AS masjid_id, m.masjid_name
        FROM form_2 f 
        JOIN sej6x_data_peribadi u ON f.ic = u.no_ic
        JOIN masjid m ON u.id_masjid = m.masjid_id 
        WHERE DATE(f.reg_date) BETWEEN :firstdate AND :lastdate
        AND m.masjid_id = :masjid_id AND f.status_code != 5 
        ORDER BY f.total_vote DESC";

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
        WHERE m.masjid_id = :masjid_id AND b.status_code = 1";

$stmt = $conn->prepare($sql);
$stmt->execute([
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

$existingICs = array_column($_SESSION['search_results'], 'ic');
foreach ($forms as &$form) {
    if (!isset($form['total_vote'])) { 
        $form['total_vote'] = 0;
    }
    if (!in_array($form['ic'], $existingICs)) {
        $_SESSION['search_results'][] = $form;
    }
}

if ($level_id == 4 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {
    if (isset($_POST['ic']) && !empty($_POST['ic'])) {
        $ic = $_POST['ic'];
        $date = $_POST['date'];
        $form = $_POST['form_id'];

        try {
            $stmt = $conn->prepare("UPDATE form_2 f SET f.status_code = 5 WHERE f.ic = :ic AND f.date = :form_date");
            $stmt->execute([
                'ic' => $ic,
                'form_date' => $date
            ]);            
            if (isset($_SESSION['search_results'])) {
                foreach ($_SESSION['search_results'] as $index => $user) {
                    if ($user['ic'] == $ic) {
                        unset($_SESSION['search_results'][$index]);
                        $_SESSION['search_results'] = array_values($_SESSION['search_results']);
                        break;
                    }
                }
            }
        } catch (PDOException $e) {
        }
    }
}

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
                    if ($user['masjid_id'] != $masjid_id) {
                        echo "<script>alert('Anda Bukan Ahli Qaryah');</script>";
                        continue;
                    }
    
                    if (!isset($user['total_vote'])) { 
                        $user['total_vote'] = 0;
                    }
    
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

    $newRole = $_POST['role']; // Get selected role

    // Update total_vote and role in the session array
    foreach ($_SESSION['search_results'] as &$user) {
        if ((isset($user['form_id']) && $user['form_id'] == $updateform) || 
            (isset($user['ic']) && $user['ic'] == $updateform)) {
            $user['role'] = $newRole;
            break;
        }
    }
}

try {
    // Get the Masjid Name
    $stmtMasjid = $conn->prepare("SELECT masjid_name FROM masjid WHERE masjid_id = $masjid_id");
    $stmtMasjid->execute();
    $masjidData = $stmtMasjid->fetch(PDO::FETCH_ASSOC);

    if (!$masjidData) {
        die("Error: Masjid not found.");
    }
    $masjidName = $masjidData['masjid_name'];
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BORANG PENCALONAN 2 JHEPP / MIPP</title>
    <script src="../Script/reject.js"></script>
</head>
<body>
<?php require '../include/header.php'; ?>

<div class="container d-flex flex-column align-items-center justify-content-center min-vh-80">
    <h1 class="text-center mb-4"><?php echo htmlspecialchars($masjidName); ?></h1>
    <h1 class="text-center mb-4">MESYUARAT AGUNG PENCALONAN JAWATANKUASA BAGI PENGGAL 2025-2028</h1>

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
                <tr>
                    <th>TARIKH</th>
                    <th>MASA</th>
                    <th>TEMPAT</th>
                    <th>NAMA CADANGAN PENGERUSI MESYUARAT</th>
                </tr>
            </thead>
            <tbody>
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
            </tbody>
        </table>
    </div>

    <div class="search-section text-center mb-4">
        <form method="POST" action="" class="d-flex justify-content-center align-items-center gap-2 w-100 mx-auto">
            <div class="d-flex align-items-center">
                <label for="search_ic" class="me-2 mb-0">MASUKKAN NO KAD PENGENALAN:</label>
                <input type="text" id="search_ic" name="search_ic" pattern="\d{12,}" maxlength="12" required class="form-control text-center w-75">
            </div>
            <button type="submit" class="btn btn-primary">CARI</button>
        </form>
    </div>
    <?php if (!empty($_SESSION['search_results'])): ?>
        <h2>HASIL CARIAN:</h2>
        <table class="table table-bordered text-center">
            <thead class="table-primary text-white">
                <tr>
                    <th>NO</th>
                    <th>NAMA</th>
                    <th>NO KAD PENGENALAN</th>
                    <th>NO TELEFON</th>
                    <th>ALAMAT</th>
                    <th>PEKERJAAN</th>
                    <th>JUMLAH UNDI</th>
                    <th>JAWATAN</th>
                    <th>TINDAKAN</th>
                    <?php if ($level_id == 4) { ?> 
                    <th>KELULUSAN</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach ($_SESSION['search_results'] as $row): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
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
                            <select class="form-control" style="width: 158px;" name="role">
                                <option value="" disabled <?php echo (!isset($row['role']) || empty($row['role'])) ? 'selected' : ''; ?>>SILA PILIH JAWATAN:</option>
                                <option value="PENGERUSI" <?php echo (isset($row['role']) && $row['role'] == 'PENGERUSI') ? 'selected' : ''; ?>>PENGERUSI</option>
                                <option value="TIMBALAN PENGERUSI" <?php echo (isset($row['role']) && $row['role'] == 'TIMBALAN PENGERUSI') ? 'selected' : ''; ?>>TIMBALAN PENGERUSI</option>
                                <option value="SETIAUSAHA" <?php echo (isset($row['role']) && $row['role'] == 'SETIAUSAHA') ? 'selected' : ''; ?>>SETIAUSHA</option>
                                <option value="BENDAHARI" <?php echo (isset($row['role']) && $row['role'] == 'BENDAHARI') ? 'selected' : ''; ?>>BENDAHARI</option>
                                <option value="AJK" <?php echo (isset($row['role']) && $row['role'] == 'AJK') ? 'selected' : ''; ?>>AJK</option>
                                <option value="AJK WANITA" <?php echo (isset($row['role']) && $row['role'] == 'AJK WANITA') ? 'selected' : ''; ?>>AJK WANITA</option>
                                <option value="PEMERIKSA KIRA-KIRA" <?php echo (isset($row['role']) && $row['role'] == 'PEMERIKSA KIRA-KIRA') ? 'selected' : ''; ?>>PEMERIKSA KIRA-KIRA</option>
                            </select>
                        </td>
                        <td>
                            <button type="submit" name="update_vote" class="btn btn-primary mb-2" value="1">KEMASKINI JAWATAN</button>
                            <?php if ($level_id == 4) { ?> 
                            <td>
                                <form method="POST" action="" onsubmit="return doubleConfirmReject()">
                                    <?php if (!empty($row['form_id'])): ?>
                                        <input type="hidden" name="form_id" value="<?= htmlspecialchars($row['form_id']) ?>">
                                    <?php endif; ?>  
                                    <input type="hidden" name="ic" value="<?= isset($row['ic']) ? htmlspecialchars($row['ic']) : '' ?>">
                                    <!-- <input type="hidden" name="date" value="<?= htmlspecialchars($row['date']) ?>"> -->
                                    <button type="submit" name="reject" class="btn btn-danger" onclick="return doubleConfirmReject()">TOLAK</button>
                                </form>
                            </td>
                            <?php } ?>
                        </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form method="POST" action="db_update_form_JHEPP.php">
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
            <button type="submit" name="update_all" class="btn btn-success mb-2">SIMPAN</button>
        </form>
    <?php else: ?>
        <p>TIADA DATA DIJUMPAI BAGI NO KAD PENGENALAN YANG DIMASUKKAN.</p>
    <?php endif; ?>

    <div class="container">
        <h2>MAKLUMAT MESYAURAT - JHEIPP/MAINPP</h2>
        <form method="POST" action="meeting_JHEPP.php" id="meetingForm">
            <div class="mb-3">
                <label for="meeting_date" class="form-label">TARIKH MESYUARAT:</label>
                <input type="date" class="form-control" id="meeting_date" name="meeting_date" required>
            </div>

            <div class="mb-3">
                <label for="meeting_time" class="form-label">MASA MESYUARAT:</label>
                <input type="time" class="form-control" id="meeting_time" name="meeting_time" required>
            </div>

            <div class="mb-3">
                <label for="meeting_place" class="form-label">TEMPAT MESYUARAT:</label>
                <input type="text" class="form-control" id="meeting_place" name="meeting_place" placeholder="Enter location" required>
            </div>

            <div id="sections">
                <div class="section">
                    <fieldset>
                        <legend>MAKLUMAT AHLI MESYUARAT:</legend>
                        <p>
                            <label for="meeting_nama_ahli">NAMA:</label>
                            <input class="form-control" name="meeting_nama_ahli[]" type="text" required/>
                        </p>
                        <p>
                            <label for="meeting_jabatanAhli">JABATAN:</label>
                            <input class="form-control" name="meeting_jabatanAhli[]" type="text" required/>
                        </p>
                        <p>
                            <label for="meeting_jawatanAhli">JAWATAN:</label>
                            <input class="form-control" name="meeting_jawatanAhli[]" type="text" required/>
                        </p>
                        <p>
                            <a href="#" class="remove">BUANG</a>
                            <a href="#" id="addSection">TAMBAH</a>
                        </p>
                    </fieldset>
                </div>
            </div>

            <div class="export-buttons text-center">
                <button type="submit" name="update_all" class="btn btn-primary mb-2">HANTAR</button>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-center gap-2 mt-3">
        <a href="form2_PTA_pdf.php">
            <button type="button" class="btn btn-primary mb-2">EKSPORT KE PDF</button>
        </a>
        <a href="form2_PTA_excel.php">
            <button type="button" class="btn btn-primary mb-2">EKSPORT KE EXCEL</button>
        </a>
        <button onclick="window.location.href = 'mainpage3.php'" class="btn btn-primary mb-2">KEMBALI</button>
    </div>
</div>
<?php require '../include/footer.php'; ?>
</body>
</html>

<script src="js/jquery.js"></script>
<script src="js/app.js"></script>

<script>
    // JavaScript to dynamically add and remove sections
    document.getElementById('addSection').addEventListener('click', function (e) {
        e.preventDefault();
        const sections = document.getElementById('sections');
        const newSection = sections.children[0].cloneNode(true); // Clone the first section
        newSection.querySelectorAll('input').forEach(input => input.value = ''); // Clear input values
        sections.appendChild(newSection); // Append the new section
    });

    // Remove section
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove')) {
            e.preventDefault();
            const section = e.target.closest('.section');
            if (section && document.querySelectorAll('.section').length > 1) {
                section.remove(); // Remove the section
            }
        }
    });
</script>