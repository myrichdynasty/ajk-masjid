<?php
session_start();
include('connection.php');

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in."); // Debug message
}
echo($_SERVER['REQUEST_METHOD']."<br/>");
print_r($_POST);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'])) {
    $user_id = $_SESSION['user_id']; // Now it should be set
    $date = $_POST['date'];
    $time = $_POST['time'];
    $place = $_POST['place'];
    $nama_cadangan1 = $_POST['nama_cadangan1'];
    $ic_cadangan1 = $_POST['ic_cadangan1'];
    $phone_cadangan1 = $_POST['phone_cadangan1'];
    $email_cadangan1 = $_POST['email_cadangan1'];
    $nama_cadangan2 = $_POST['nama_cadangan2'];
    $ic_cadangan2 = $_POST['ic_cadangan2'];
    $phone_cadangan2 = $_POST['phone_cadangan2'];
    $email_cadangan2 = $_POST['email_cadangan2'];
    $status_code = 0;
    $tindakan_code = 0;
    $comment = "";
    $masjid_id = $_SESSION['masjid_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO booking (user_id, date, time, place, nama_cadangan1, ic_cadangan1, phone_cadangan1, email_cadangan1, nama_cadangan2, ic_cadangan2, phone_cadangan2, email_cadangan2, status_code, tindakan_code, comment, masjid_id) 
                                VALUES (:user_id, :date, :time, :place, :nama_cadangan1, :ic_cadangan1, :phone_cadangan1, :email_cadangan1, :nama_cadangan2, :ic_cadangan2, :phone_cadangan2, :email_cadangan2, :status_code, :tindakan_code, :comment, :masjid_id)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':place', $place);
        $stmt->bindParam(':nama_cadangan1', $nama_cadangan1);
        $stmt->bindParam(':ic_cadangan1', $ic_cadangan1);
        $stmt->bindParam(':phone_cadangan1', $phone_cadangan1);
        $stmt->bindParam(':email_cadangan1', $email_cadangan1);
        $stmt->bindParam(':nama_cadangan2', $nama_cadangan2);
        $stmt->bindParam(':ic_cadangan2', $ic_cadangan2);
        $stmt->bindParam(':phone_cadangan2', $phone_cadangan2);
        $stmt->bindParam(':email_cadangan2', $email_cadangan2);
        $stmt->bindParam(':status_code', $status_code);
        $stmt->bindParam(':tindakan_code', $tindakan_code);
        $stmt->bindParam(':comment', $comment);
        $stmt->bindParam(':masjid_id', $masjid_id);
        
        $stmt->execute();

        $_SESSION['message'] = "Booking request submitted successfully!";
        header("Location: ../backend/mainpage.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
