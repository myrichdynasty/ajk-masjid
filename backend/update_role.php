<?php
session_start();
require '../backend/connection.php'; // Adjust path if needed

header('Content-Type: text/plain'); // Ensure plain text response

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ic'], $_POST['role'])) {
    $ic = $_POST['ic'];
    $role = $_POST['role'];

    try {
        $stmt = $conn->prepare("UPDATE form SET role = :role WHERE ic = :ic");
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':ic', $ic);

        if ($stmt->execute()) {
            echo "success"; // Send "success" only
        } else {
            http_response_code(500);
            echo "error";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "error"; // Avoid exposing database errors
    }
} else {
    http_response_code(400);
    echo "invalid_request";
}
?>
