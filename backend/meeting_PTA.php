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
            FROM form_2
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