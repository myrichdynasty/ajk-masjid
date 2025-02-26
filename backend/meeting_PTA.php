<?php
session_start();
include('connection.php');
$level_id = $_SESSION['ulevel'];

$users = $_SESSION['forwarded_data']['users'];
foreach ($users as $form_id => $user) {
    // Check if form_id is the same as ic
    if ($form_id == $user['ic']) {
        // If so, retrieve the actual form_id from the database
        $stmt = $conn->prepare("
            SELECT form_id 
            FROM form 
            WHERE ic = :ic And reg_date = :date
            LIMIT 1
        ");
        $stmt->bindParam(':ic', $user['ic'], PDO::PARAM_STR);
        $stmt->bindParam(':date', $user['reg_date'], PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // If a form_id is found, update the array key
        if ($result) {
            $correct_form_id = $result['form_id'];

            // Update the array with the correct form_id
            unset($users[$form_id]);  // Remove the old entry
            $users[$correct_form_id] = $user; // Reinsert with correct form_id
        }
    }
}

// Debugging: Check if session data exists
if (isset($_SESSION['forwarded_data'])) {
    //echo "<pre style='color: blue;'>DEBUG: Forwarded Data from Previous Page</pre>";
/*
    echo "<pre>";
    print_r($_SESSION['forwarded_data']); // Print all session data
    echo "</pre>";
*/
}
if (isset($_SESSION['forwarded_data']['users'])) {
    $users = $_SESSION['forwarded_data']['users'];

   // echo "<h2>Received Users Data:</h2>";
    foreach ($users as $form_id => $user) {  // Now $form_id is used automatically
        // Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user inputs
    $meetingDate = htmlspecialchars($_POST['meeting_date']);
    $meetingTime = htmlspecialchars($_POST['meeting_time']);
    $meetingPlace = htmlspecialchars($_POST['meeting_place']);
    $formId = $form_id;
    $meetingParts = htmlspecialchars($_POST['meeting_part']);

    // Validate input data
    if (empty($meetingDate) || empty($meetingTime) || empty($meetingPlace) || empty($meetingParts) || empty($formId)) {
        echo "<script>alert('All fields are required.');</script>";
    } else {
        $participants = explode(',', $meetingParts); // Split participants by comma

        try {
            // Check if form_id exists
            $stmt = $conn->prepare("SELECT form_id FROM form WHERE form_id = :form_id");
            $stmt->bindParam(':form_id', $formId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Insert each participant
                foreach ($participants as $participant) {
                    $participant = trim($participant); // Remove spaces

                    $sql = "INSERT INTO meeting (meeting_date, meeting_time, meeting_place, form_id, meeting_part) 
                            VALUES (:meeting_date, :meeting_time, :meeting_place, :form_id, :meeting_part)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        'meeting_date' => $meetingDate,
                        'meeting_time' => $meetingTime,
                        'meeting_place' => $meetingPlace,
                        'form_id' => $formId,
                        'meeting_part' => $participant
                    ]);
                }

                echo "<script>alert('Meeting added successfully!'); window.location.href='meeting_history.php';</script>";
            } else {
                echo "<script>alert('Invalid Form ID.');</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}
    }
} else {
    echo "<p style='color: red;'>No users data received!</p>";
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAKLUMAT MESYAURAT PTA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
            margin-top: 50px;
        }
        h2 {
            text-align: center;
            color: #007bff;
        }
        .form-label {
            font-weight: bold;
        }
        textarea {
            resize: none;
        }
        .btn-primary {
            width: 100%;
        }
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
        }
    </style>
</head>
<body>
<?php require '../include/header.php'; ?>
<div class="container">
    <h2>MAKLUMAT MESYAURAT - PTA</h2>
    <form method="POST" action="">
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

        <div class="mb-3">
            <label for="meeting_part" class="form-label">AHLI MESYUARAT (comma-separated):</label>
            <textarea class="form-control" id="meeting_part" name="meeting_part" rows="3" placeholder="Enter participant names separated by commas" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">SIMAPN MESYUARAT</button>
    </form>
    
    <?php
if ($level_id == 2) {
    echo '<a href="form_PTA.php" class="back-btn">← KEMBALI KE MENU UTAMA</a>';
} elseif ($level_id == 3) {
    echo '<a href="form_JHEPP.php" class="back-btn">← KEMBALI KE MENU UTAMA</a>';
} else {
    echo '<a href="form_JHEPP.php" class="back-btn">← KEMBALI KE MENU UTAMA</a>';
}
?>

</div>
<?php require '../include/footer.php'; ?>
</body>
</html>
