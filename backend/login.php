<?php
// Initialize session
session_start();

// Include database connection settings
require '../backend/connection.php'; // Ensure this file exists and connects correctly

if (isset($_POST['login'])) {

    // Capture values from the HTML form
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Prepare the SQL statement (No hashing, matches plaintext)
        $sql = "SELECT * FROM user u 
        JOIN masjid m ON u.masjid_id = m.masjid_id
        JOIN daerah d ON m.daerah_id = d.daerah_id 
        WHERE u.username = :username AND u.pswd = :password";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password); // Note: This is NOT secure

        // Execute the query
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Redirect if login fails
            header('Location: ../frontend/login.html');
            exit();
        } else {
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['ulevel'] = $user['level_id'];
            $_SESSION['user_id'] = $user['user_id']; // Ensure 'user_id' column exists in the database
            $_SESSION['masjid_id'] = $user['masjid_id'];
            $_SESSION['daerah_id'] = $user['daerah_id'];

            // Redirect based on user level
            switch ($user['level_id']) {
                case 1: 
                    header('Location: ../backend/mainpage.php');
                    break;
                case 2:
                    header('Location: ../backend/mainpage2.php');
                    break;
                case 3:
                    header('Location: ../backend/mainpage3.php');
                    break;
                case 4:
                    header('Location: ../backend/mainpage4.php');
                    break;
                default:
                    header('Location: index.html');
            }
            exit();
        }
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

// Close the connection
$conn = null;
?>
