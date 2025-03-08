<?php
session_start();
include('connection.php');

date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
$currentDate = date('Y-m-d'); // Store only the date (YYYY-MM-DD)

require '../include/function.php';

// Function to fetch and insert top rows into form_2
function fetchAndInsertTopRows($conn, $currentDate, $gender, $limit, $booking_id) {
    // Fetch top rows based on gender and limit
    $stmtSelect = $conn->prepare("
        SELECT masjid_id, ic, name, gender, date, phone_num, address, job, total_vote, status_code, booking_id
        FROM form
        WHERE DATE(date) = :currentDate AND gender = :gender AND booking_id = :booking_id
        ORDER BY total_vote DESC
        LIMIT :limit
    ");
    $stmtSelect->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $stmtSelect->bindParam(':gender', $gender, PDO::PARAM_INT);
    $stmtSelect->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmtSelect->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmtSelect->execute();
    $topRows = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    // Clear existing rows in `form_2` for the current date and gender
    // $stmtDelete = $conn->prepare("
    //     DELETE FROM form_2 WHERE DATE(date) = :currentDate AND gender = :gender
    // ");
    // $stmtDelete->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    // $stmtDelete->bindParam(':gender', $gender, PDO::PARAM_INT);
    // $stmtDelete->execute();

    // Insert the top rows into `form_2`
    foreach ($topRows as $row) {
        $stmtInsert = $conn->prepare("
            INSERT INTO form_2 (masjid_id, ic, name, gender, date, phone_num, address, job, total_vote, status_code, booking_id)
            VALUES (:masjid_id, :ic, :name, :gender, :date, :phone, :address, :job, :total_vote, :status_code, :booking_id)
        ");
        $stmtInsert->bindParam(':masjid_id', $row['masjid_id'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':ic', $row['ic'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':name', $row['name'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':gender', $row['gender'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':date', $row['date'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':phone', $row['phone_num'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':address', $row['address'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':job', $row['job'], PDO::PARAM_STR);
        $stmtInsert->bindParam(':total_vote', $row['total_vote'], PDO::PARAM_INT);
        $stmtInsert->bindParam(':status_code', $row['status_code'], PDO::PARAM_INT);
        $stmtInsert->bindParam(':booking_id', $row['booking_id'], PDO::PARAM_STR);
        $stmtInsert->execute();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_all'])) {
    if (isset($_POST['users']) && is_array($_POST['users'])) {
        echo "<pre style='color: blue;'>DEBUG: Received Data:</pre>";
        echo "<pre>";
        print_r($_POST['users']);  // Debug: Show all sent data
        echo "</pre>";

        // Check if there are at least 2 women in the list
        $womenCount = 0;
        foreach ($_POST['users'] as $ic => $user) {
            if ($user['gender'] == 2) {
                $womenCount++;
            }
        }

        if ($womenCount < 2) {
            echo "<script>
                alert('PERLUKAN SEKURANG-KURANGNYA 2 CALON WANITA');
                history.back(); // Go back to the previous page
            </script>";
            exit(); // Stop further processing
        }

        try {
            $conn->beginTransaction(); // Start DB transaction

            foreach ($_POST['users'] as $ic => $user) {
                $name = $user['name'];
                $gender = $user['gender'];
                $masjid_id = $user['masjid_id'];
                $phone = $user['phone'];
                $address = $user['address'];
                $job = $user['job'];
                $booking_id = $user['booking_id'];
                $totalVote = intval($user['total_vote']);
                $status = 1;
            
                // Check if an entry with the same IC and date exists
                $stmt = $conn->prepare("
                    SELECT total_vote FROM form WHERE ic = :ic AND DATE(date) = :currentDate
                ");
                $stmt->bindParam(':ic', $ic, PDO::PARAM_STR);
                $stmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
                $stmt->execute();
                $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
                if ($existingRecord) {
                    // If record exists, update total_vote and status_code in `form`
                    $updatedVote = $totalVote;
                    $stmt = $conn->prepare("
                        UPDATE form 
                        SET total_vote = :total_vote, status_code = :status_code
                        WHERE ic = :ic AND DATE(date) = :currentDate
                    ");
                    $stmt->bindParam(':total_vote', $updatedVote, PDO::PARAM_INT);
                    $stmt->bindParam(':status_code', $status, PDO::PARAM_INT);
                    $stmt->bindParam(':ic', $ic, PDO::PARAM_STR);
                    $stmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
                    $stmt->execute();
                } else {
                    // If no record exists, insert new entry into `form`
                    $stmt = $conn->prepare("
                        INSERT INTO form (masjid_id, ic, name, gender, date, phone_num, address, job, total_vote, status_code, booking_id)
                        VALUES (:masjid_id, :ic, :name, :gender, NOW(), :phone, :address, :job, :total_vote, :status_code, :booking_id)
                    ");
                    $stmt->bindParam(':masjid_id', $masjid_id, PDO::PARAM_STR);
                    $stmt->bindParam(':ic', $ic, PDO::PARAM_STR);
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
                    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                    $stmt->bindParam(':job', $job, PDO::PARAM_STR);
                    $stmt->bindParam(':total_vote', $totalVote, PDO::PARAM_INT);
                    $stmt->bindParam(':status_code', $status, PDO::PARAM_INT);
                    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_STR);
                    $stmt->execute();
                }
            }

            // After processing all users, update `form_2` with the top 7 (Man) and top 2 (Woman) rows from `form`
            fetchAndInsertTopRows($conn, $currentDate, 1, 8, $booking_id); // For men (gender = 1)
            fetchAndInsertTopRows($conn, $currentDate, 2, 2, $booking_id); // For women (gender = 2)

            // Update booking status to 'tindakan_code' = 2
            $tindakan_code = 2;
            $stmt = $conn->prepare("
                UPDATE booking 
                SET tindakan_code = :tindakan_code
                WHERE booking_id = :booking_id
            ");
            $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_STR);
            $stmt->bindParam(':tindakan_code', $tindakan_code, PDO::PARAM_STR);
            $stmt->execute();

            $conn->commit(); // Commit transaction
            echo "<pre style='color: green;'>Data successfully processed!</pre>";

            // Clear session after successful insert/update
            $_SESSION['search_results'] = [];

            // Redirect to another page after successful insertion
            echo '<script> location.replace("mainpage.php"); </script>';
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack(); // Rollback on error
            echo "<pre style='color: red;'>Error inserting/updating data: " . $e->getMessage() . "</pre>";
        }
    } else {
        echo "<pre style='color: red;'>No users data received!</pre>";
    }
}
?>