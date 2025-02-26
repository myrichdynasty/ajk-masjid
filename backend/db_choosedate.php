<?php
session_start();
include('connection.php');

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in."); // Debug message
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $user_id = $_SESSION['user_id']; // Now it should be set
    $date = $_POST['date'];
    $time = $_POST['time'];
    $place = $_POST['place'];
    $nama_pengerusi = $_POST['nama_pengerusi'];
    $no_ic = $_POST['no_ic'];
    $no_phone = $_POST['no_phone'];
    $email = $_POST['email'];
    $status_code = 'pending';
    $masjid_id = $_SESSION['masjid_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO booking (user_id, date, time, place, nama_pengerusi, no_ic, no_phone, email, status_code, masjid_id) 
                                VALUES (:user_id, :date, :time, :place, :nama_pengerusi, :no_ic, :no_phone, :email, :status_code, :masjid_id)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':place', $place);
        $stmt->bindParam(':nama_pengerusi', $nama_pengerusi);
        $stmt->bindParam(':no_ic', $no_ic);
        $stmt->bindParam(':no_phone', $no_phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':status_code', $status_code);
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
