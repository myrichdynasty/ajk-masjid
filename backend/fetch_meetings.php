<?php
include('../backend/connection.php');

$daerah_id = isset($_GET['daerah_id']) ? $_GET['daerah_id'] : '';
$masjid_id = isset($_GET['masjid_id']) ? $_GET['masjid_id'] : '';

try {
    // Base query
    $sql = "SELECT meeting_date, meeting_time, meeting_place, form_id, meeting_part 
            FROM meeting m
            LEFT JOIN masjid ms ON m.meeting_place = ms.masjid_id
            LEFT JOIN daerah d ON ms.daerah_id = d.daerah_id
            WHERE (:daerah_id = '' OR d.daerah_id = :daerah_id)
            AND (:masjid_id = '' OR ms.masjid_id = :masjid_id)
            ORDER BY meeting_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':daerah_id', $daerah_id);
    $stmt->bindParam(':masjid_id', $masjid_id);
    $stmt->execute();
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($meetings) {
        echo '<table class="table table-bordered text-center">';
        echo '<thead class="table-primary text-white">
                <tr>
                    <th>Meeting Date</th>
                    <th>Meeting Time</th>
                    <th>Meeting Place</th>
                    <th>Form ID</th>
                    <th>Participants</th>
                </tr>
              </thead>';
        echo '<tbody>';
        foreach ($meetings as $meeting) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($meeting['meeting_date']) . '</td>';
            echo '<td>' . date('H:i', strtotime($meeting['meeting_time'])) . '</td>';
            echo '<td>' . htmlspecialchars($meeting['meeting_place']) . '</td>';
            echo '<td>' . htmlspecialchars($meeting['form_id']) . '</td>';
            echo '<td>' . htmlspecialchars($meeting['meeting_part']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="text-center text-muted">No meeting history available.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="text-danger text-center">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
