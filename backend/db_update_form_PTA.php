<?php
session_start();
include('connection.php');

date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to GMT+8
$currentDate = date('Y-m-d'); // Store only the date (YYYY-MM-DD)

$level_id = $_SESSION['ulevel'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_all'])) {
    if (isset($_POST['users']) && is_array($_POST['users'])) {
        echo "<pre style='color: blue;'>DEBUG: Received Data:</pre>";
        echo "<pre>";
        print_r($_POST['users']);  // Debug: Show all sent data
        echo "</pre>";

        try {
            $conn->beginTransaction(); // Start DB transaction

            foreach ($_POST['users'] as $key => $user) {
                // Determine if key is `form_id` or `ic`
                if (is_numeric($key)) {
                    $form_id = $key; // It's a `form_id`
                    $ic = $user['ic']; // Get IC from user data
                } else {
                    $form_id = null; // No form_id available
                    $ic = $key; // Key is the `ic`
                }

                    $name = $user['name'];
                    $masjid_id = $user['masjid_id'];
                    $phone = $user['phone'];
                    $address = $user['address'];
                    $job = $user['job'];
                    $totalVote = intval($user['total_vote']);
                    $role = $user['role'];
                    $status = $level_id;
                    $verify1 = $user_id;
                    $date = date('Y-m-d', strtotime($user['reg_date']));
                    
                // Check if an entry with the same IC and date exists
                $stmt = $conn->prepare("
                    SELECT form_id, total_vote FROM form_2 
                    WHERE ic = :ic AND DATE(date) = :reg_date
                ");
                $stmt->bindParam(':ic', $ic, PDO::PARAM_STR);
                $stmt->bindParam(':reg_date', $date, PDO::PARAM_STR);
                $stmt->execute();
                $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingRecord) {
                    $form_id = $existingRecord['form_id']; // Get the form_id if exists

                    // If record exists, update total_vote and status_code
                    $updatedVote = $totalVote;
                    if($level_id == 2){
                        $stmt = $conn->prepare("
                        UPDATE form_2 
                        SET total_vote = :total_vote, status_code = :status_code, verify_id_1 = :user_id, role = :role
                        WHERE form_id = :form_id
                    ");
                    $stmt->bindParam(':user_id', $verify1, PDO::PARAM_INT);
                    }
                    elseif($level_id == 3){
                        $stmt = $conn->prepare("
                        UPDATE form_2 
                        SET total_vote = :total_vote, status_code = :status_code, verify_id_2 = :user_id, role = :role
                        WHERE form_id = :form_id
                    ");
                    $stmt->bindParam(':user_id', $verify1, PDO::PARAM_INT);
                    }
                    else{
                        $stmt = $conn->prepare("
                        UPDATE form_2 
                        SET total_vote = :total_vote, status_code = :status_code, verify_id_3 = :user_id, role = :role
                        WHERE form_id = :form_id
                    ");
                    $stmt->bindParam(':user_id', $verify1, PDO::PARAM_INT);
                    }
                  

                    $stmt->bindParam(':total_vote', $updatedVote, PDO::PARAM_INT);
                    $stmt->bindParam(':status_code', $status, PDO::PARAM_INT);
                    $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);
                    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                    $stmt->execute();
                } else {
                    // If no record exists, insert a new entry
                    
                    if ($level_id == 2) {
                        $stmt = $conn->prepare("
                            INSERT INTO form_2 (masjid_id, ic, name, date, phone_num, address, job, total_vote, status_code, role, verify_id_1)
                            VALUES (:masjid_id, :ic, :name, NOW(), :phone, :address, :job, :total_vote, :status_code, :role, :verify_id)
                        ");
                        $verify_id = $verify1; // Set correct value
                    } elseif ($level_id == 3) {
                        $stmt = $conn->prepare("
                            INSERT INTO form_2 (masjid_id, ic, name, date, phone_num, address, job, total_vote, status_code, role, verify_id_2)
                            VALUES (:masjid_id, :ic, :name, NOW(), :phone, :address, :job, :total_vote, :status_code, :role, :verify_id)
                        ");
                        $verify_id = $verify2;
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO form_2 (masjid_id, ic, name, date, phone_num, address, job, total_vote, status_code, role, verify_id_3)
                            VALUES (:masjid_id, :ic, :name, NOW(), :phone, :address, :job, :total_vote, :status_code, :role, :verify_id)
                        ");
                        $verify_id = $verify3;
                    }
                
                    $stmt->bindParam(':masjid_id', $ic, PDO::PARAM_STR);
                    $stmt->bindParam(':ic', $ic, PDO::PARAM_STR);
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                    $stmt->bindParam(':job', $job, PDO::PARAM_STR);
                    $stmt->bindParam(':total_vote', $totalVote, PDO::PARAM_INT);
                    $stmt->bindParam(':status_code', $status, PDO::PARAM_INT);
                    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                    $stmt->bindParam(':verify_id', $verify_id, PDO::PARAM_INT);
                
                    // Execute the statement
                    $stmt->execute();
                }                
            }

            $conn->commit(); // Commit transaction
            echo "<pre style='color: green;'>Data successfully processed!</pre>";

            $_SESSION['forwarded_data'] = $_POST;

            // Clear session after successful insert/update
            $_SESSION['search_results'] = [];

            // Redirect to meeting_PTA.php after successful insertion
            header("Location: " . $_SERVER['HTTP_REFERER']); 
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
