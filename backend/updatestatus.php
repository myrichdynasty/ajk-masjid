<?php
session_start();
require '../backend/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = $_POST['booking_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $status_code = $_POST['status_code'];
    $comment = $_POST['comment'];

    try {
        $stmt = $conn->prepare("UPDATE booking SET date = :date, time = :time, status_code = :status_code, comment = :comment WHERE booking_id = :booking_id");
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':status_code', $status_code);
        $stmt->bindParam(':comment', $comment);
        $stmt->bindParam(':booking_id', $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Booking updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update booking.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    // Redirect back to review_booking.php
    header("Location: PejabatAgamaDaerah.php");
    exit();
}
?>
