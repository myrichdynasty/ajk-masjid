<?php
session_start();
include('connection.php');

$searchIC = '';
// Initialize session array if not set
if (!isset($_SESSION['search_results'])) {
    $_SESSION['search_results'] = [];
}

/*echo "<pre>Debug Search Results:";
print_r($_SESSION['search_results']);
echo "</pre>";*/

$level_id = $_SESSION['ulevel'];

// Retrieve masjid_id from URL
$masjid_id = isset($_GET['masjid_id']) ? intval($_GET['masjid_id']) : null;

date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
// Get the first and last day of the current month
$firstDay = date('Y-m-01'); // Example: 2024-02-01
$lastDay = date('Y-m-t');   // Example: 2024-02-29


// Query to check if a form in the month
$sql = "SELECT f.*, u.nama_penuh AS name, u.no_ic AS ic, u.no_hp AS phone, u.alamat_terkini AS address, 
        u.pekerjaan AS job, u.id_masjid AS masjid_id, m.masjid_name
        FROM form f 
        JOIN sej6x_data_peribadi u ON f.ic = u.no_ic
        JOIN masjid m ON u.id_masjid = m.masjid_id 
        WHERE DATE(f.reg_date) BETWEEN :firstdate AND :lastdate
        AND m.masjid_id = :masjid_id AND f.status_code != 5 
        ORDER BY f.total_vote DESC LIMIT 10";

// Generate debug query by replacing placeholders with actual values
$debug_sql = str_replace(
    [':firstdate', ':lastdate', ':masjid_id'],
    ["'$firstDay'", "'$lastDay'", "'$masjid_id'"],
    $sql
);

// Print Debug Query
//echo "<pre>Debug SQL Query: " . $debug_sql . "</pre>";

$stmt = $conn->prepare($sql);
$stmt->execute([
    'firstdate' => $firstDay,
    'lastdate' => $lastDay,
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

// Check if level_id is 4 and the request is valid - Done for MIPP side of Rejection Sets Status code to 5
if ($level_id == 4 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {
    

    
    if (isset($_POST['ic']) && !empty($_POST['ic'])) {
        $ic = $_POST['ic'];
        $date = $_POST['date'];
        $form = $_POST['form_id'];

        try {
            $stmt = $conn->prepare("UPDATE form f SET f.status_code = 5 WHERE f.ic = :ic AND f.date = :form_date");
            $stmt->execute([
                'ic' => $ic,
                'form_date' => $date
            ]);            
            // Remove the user from the session array
            if (isset($_SESSION['search_results'])) {
                foreach ($_SESSION['search_results'] as $index => $user) {
                    if ($user['ic'] == $ic) {
                        unset($_SESSION['search_results'][$index]);
                        $_SESSION['search_results'] = array_values($_SESSION['search_results']); // Re-index array
                        break;
                    }
                }
            }

            // echo json_encode(['success' => true, 'message' => 'User rejected successfully.']);
        } catch (PDOException $e) {
            //echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        //echo json_encode(['success' => false, 'message' => 'Invalid or missing IC.']);
    }

} else {
    //echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request.']);
}
?>
<?php

// Handle search request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_ic'])) {
    $searchIC = trim($_POST['search_ic']);

    if (!empty($searchIC)) {
        try {
            $stmt = $conn->prepare("SELECT username, pswd, name, masjid_id, ic, phone, address, job FROM user WHERE ic = :ic");
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
/*
    echo "<pre>Debug POST Data:\n";
    print_r($_POST);
    echo "</pre>";
*/
   // exit; // Stop execution to check output

    // Extract form_id dynamically
    $updateform = key($_POST['users']); // Get the first key in the users array
    //$newVote = intval($_POST['total_vote']);
    $newRole = $_POST['role']; // Get selected role

    /* Debug: Check if values are being received
    echo "<pre>Debug Update Form ID: " . $updateform . "</pre>";
    echo "<pre>Debug New Vote Count: " . $newVote . "</pre>";
    echo "<pre>Debug New Role: " . $newRole . "</pre>";
*/
    // Update total_vote and role in the session array
    foreach ($_SESSION['search_results'] as &$user) {
        if ($user['form_id'] == $updateform || $user['ic'] == $_POST['users'][$updateform]['ic']) { 
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
    <title>Form 2 JHEPP / MIPP</title>
    <script src="../Script/reject.js"></script>

</head>
<body>
<?php require '../include/header.php'; 
?>
<div class="container d-flex flex-column align-items-center justify-content-center min-vh-80">
    <h1 class="text-center mb-4">Search User Data by IC</h1> <!-- Added mb-4 for spacing -->

<!-- Existing search section -->
<div class="search-section text-center mb-4"> <!-- Added mb-4 for spacing -->
    <form method="POST" action="" class="d-flex justify-content-center align-items-center gap-2 w-100 mx-auto">
        <div class="d-flex align-items-center">
            <label for="search_ic" class="me-2 mb-0">Enter IC:</label>
            <input type="text" id="search_ic" name="search_ic" pattern="\d{12,}" maxlength="12" required class="form-control text-center w-75">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>
    <?php 
    if (!empty($_SESSION['search_results'])): ?>
        <h2>Search Results:</h2>
        <table class="table table-bordered text-center">
        <thead class="table-primary text-white">
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Form No</th>
                        <th>IC</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                        <th>Job</th>
                        <th>Total Vote</th>
                        <th>Role</th>
                        <th>Action</th>
                        <?php if ($level_id == 4) { ?> 
                        <th>Approval</th>
                        <?php } ?>
                        </thead>
                        <tbody>
                    </tr>

                    <?php
                    $counter = 1; // Initialize counter
                    foreach ($_SESSION['search_results'] as $row): ?>
                        <tr>
                        <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['form_id']); ?></td>
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
        <select name="role">
        <option value="" disabled <?php echo (!isset($row['role']) || empty($row['role'])) ? 'selected' : ''; ?>>Please select a role</option>
            <option value="Pengerusi" <?php echo (isset($row['role']) && $row['role'] == 'Pengerusi') ? 'selected' : ''; ?>>Pengerusi</option>
            <option value="Timbalan Pengerusi" <?php echo (isset($row['role']) && $row['role'] == 'Timbalan Pengerusi') ? 'selected' : ''; ?>>Timbalan Pengerusi</option>
            <option value="Setiausaha" <?php echo (isset($row['role']) && $row['role'] == 'Setiausaha') ? 'selected' : ''; ?>>Setiausaha</option>
            <option value="AJK" <?php echo (isset($row['role']) && $row['role'] == 'AJK') ? 'selected' : ''; ?>>AJK</option>
            <option value="AJK Wanita" <?php echo (isset($row['role']) && $row['role'] == 'AJK Wanita') ? 'selected' : ''; ?>>AJK Wanita</option>
            <option value="Pemeriksa Kira-Kira" <?php echo (isset($row['role']) && $row['role'] == 'Pemeriksa Kira-Kira') ? 'selected' : ''; ?>>Pemeriksa Kira-Kira</option>
        </select>
    </td>

    <td>
    <button type="submit" name="update_vote" class="btn btn-primary mb-2" value="1">Update Role</button>
    <?php if ($level_id == 4) { ?> 
    <td>
    <form method="POST" action="" onsubmit="return doubleConfirmReject()">
    <?php if (!empty($row['form_id'])): ?>
        <input type="hidden" name="form_id" value="<?= htmlspecialchars($row['form_id']) ?>">
    <?php endif; ?>  
    <input type="hidden" name="ic" value="<?= isset($row['ic']) ? htmlspecialchars($row['ic']) : '' ?>">
    <input type="hidden" name="date" value="<?= htmlspecialchars($row['date']) ?>">

    <button type="submit" name="reject" class="btn btn-danger" onclick="return doubleConfirmReject()">Reject</button>
</form>
    </td>
<?php } ?>
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
            <input type="hidden" name="users[<?php echo $key; ?>][reg_date]" value="<?php echo htmlspecialchars($row['reg_date']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][masjid_id]" value="<?php echo htmlspecialchars($row['masjid_id']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][phone]" value="<?php echo htmlspecialchars($row['phone']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][address]" value="<?php echo htmlspecialchars($row['address']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][job]" value="<?php echo htmlspecialchars($row['job']); ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][role]" value="<?php echo isset($row['role']) ? htmlspecialchars($row['role']) : ''; ?>">
            <input type="hidden" name="users[<?php echo $key; ?>][total_vote]" value="<?php echo isset($row['total_vote']) ? htmlspecialchars($row['total_vote']) : '0'; ?>" required>
        <?php endforeach; ?>
        <button type="submit" name="update_all" class="btn btn-success mb-2">Save and Verify</button>
        </form>
    <?php else: ?>
        <p>No results found for the entered IC.</p>
    <?php endif; ?>
    <div class="d-flex justify-content-center gap-2 mt-3">
    <a href="form2_PTA_pdf.php">
        <button type="button" class="btn btn-primary mb-2">Export to PDF</button>
    </a>
    <a href="form2_PTA_excel.php">
        <button type="button" class="btn btn-primary mb-2">Export to Excel</button>
    </a>
    <button onclick="window.location.href = 'form_JHEPP.php'" class="btn btn-primary mb-2">Back</button>
</div>

<?php require '../include/footer.php'; ?>

</body>
</html>