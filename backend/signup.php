<?php
// Include the database connection file
include('connection.php');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
        $newUsername = $_POST['username'];
        $newPassword = $_POST['password']; // No hashing here!

        // Check if username already exists
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = :username");
        $stmt->bindParam(':username', $newUsername);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Username already taken. Please choose another one.'); window.location.href='signup.html';</script>";
        } else {
            // Insert new user into the database WITHOUT hashing
            $insertStmt = $conn->prepare("INSERT INTO user (username, pswd) VALUES (:username, :pswd)");
            $insertStmt->bindParam(':username', $newUsername);
            $insertStmt->bindParam(':pswd', $newPassword); // Stores password as plain text
            $insertStmt->execute();

            echo "<script>alert('Account created successfully!'); window.location.href='login.html';</script>";
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
