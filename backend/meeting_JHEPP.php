<?php
session_start();
include('connection.php');

// Debugging: Check if masjid_id is set in the session
if (!isset($_SESSION['masjid_id'])) {
    die("Masjid ID is not set in the session. Please log in again.");
}

// Debugging: Check the value of masjid_id
$masjid_id = $_SESSION['masjid_id'];
echo "<pre>Masjid ID: ";
print_r($masjid_id);
echo "</pre>";

// Check if masjid_id exists in the database
$stmt = $conn->prepare("SELECT masjid_id FROM masjid WHERE masjid_id = :masjid_id");
$stmt->bindParam(':masjid_id', $masjid_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die("Masjid ID is invalid or does not exist in the database.");
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user inputs
    $meetingDate = htmlspecialchars($_POST['meeting_date']);
    $meetingTime = htmlspecialchars($_POST['meeting_time']);
    $meetingPlace = htmlspecialchars($_POST['meeting_place']);
    $meetingNama = $_POST['meeting_nama_ahli']; // Array of names
    $meetingJabatan = $_POST['meeting_jabatanAhli']; // Array of departments
    $meetingJawatan = $_POST['meeting_jawatanAhli']; // Array of positions

    // Validate input data
    if (empty($meetingDate) || empty($meetingTime) || empty($meetingPlace) || empty($meetingNama) || empty($meetingJabatan) || empty($meetingJawatan)) {
        echo "<script>alert('All fields are required.');</script>";
    } else {
        try {
            // Loop through each participant and insert into the database
            for ($i = 0; $i < count($meetingNama); $i++) {
                $nama = htmlspecialchars($meetingNama[$i]);
                $jabatan = htmlspecialchars($meetingJabatan[$i]);
                $jawatan = htmlspecialchars($meetingJawatan[$i]);

                $sql = "INSERT INTO meeting (meeting_date, meeting_time, meeting_place, meeting_nama_ahli, meeting_jabatanAhli, meeting_jawatanAhli, masjid_id) 
                        VALUES (:meeting_date, :meeting_time, :meeting_place, :meeting_nama_ahli, :meeting_jabatanAhli, :meeting_jawatanAhli, :masjid_id)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'meeting_date' => $meetingDate,
                    'meeting_time' => $meetingTime,
                    'meeting_place' => $meetingPlace,
                    'meeting_nama_ahli' => $nama,
                    'meeting_jabatanAhli' => $jabatan,
                    'meeting_jawatanAhli' => $jawatan,
                    'masjid_id' => $masjid_id
                ]);
            }
            echo "<script>alert('Meeting added successfully!'); window.location.href='mainpage3.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}
?>