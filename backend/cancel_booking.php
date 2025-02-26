<?php
session_start();
require '../backend/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = $_POST['booking_id'];

    try {
        // Delete booking from database
        $stmt = $conn->prepare("DELETE FROM booking WHERE booking_id = :booking_id AND status_code = 0");
        $stmt->bindParam(':booking_id', $booking_id);
        $stmt->execute();

        $_SESSION['message'] = "Booking cancelled successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

header("Location: ../backend/mainpage.php");
exit();
?>
