<?php
function log_sejarah($id_masjid, $description){
    $stmt = $conn->prepare("INSERT INTO booking (user_id, date, time, place, status_code, masjid_id) VALUES (:user_id, :date, :time, :place, NOW(), :masjid_id)");
    $stmt->bindParam(':user_id', $id_masjid);
    $stmt->bindParam(':date', $description);
    
    $stmt->execute();
}
?>