<?php
session_start();
include('../backend/connection.php');

// Fetch all daerahs
$stmtDaerah = $conn->prepare("SELECT daerah_id, daerah_name FROM daerah ORDER BY daerah_name ASC");
$stmtDaerah->execute();
$daerahs = $stmtDaerah->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php require '../include/header.php'; ?>

<div class="container mt-4">
    <div class="text-center">
        <h2>Meeting History</h2>
    </div>

    <!-- Filter Form -->
    <form id="filterForm" class="mb-3">
        <div class="row">
            <div class="col-md-5">
                <label for="daerah" class="form-label">Select Daerah:</label>
                <select id="daerah" name="daerah_id" class="form-select">
                    <option value="">All Daerah</option>
                    <?php foreach ($daerahs as $daerah): ?>
                        <option value="<?= htmlspecialchars($daerah['daerah_id']) ?>"><?= htmlspecialchars($daerah['daerah_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="masjid" class="form-label">Select Masjid:</label>
                <select id="masjid" name="masjid_id" class="form-select">
                    <option value="">All Masjid</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <!-- Meeting Table (Loaded via AJAX) -->
    <div id="meetingResults"></div>

    <div class="text-center">
        <button onclick="window.location.href = 'form_JHEPP.php'" class="btn btn-secondary">Back</button>
    </div>
</div>

<?php require '../include/footer.php'; ?>

<script>
$(document).ready(function () {
    // Load initial meetings
    loadMeetings();

    // When daerah is selected, update masjid dropdown
    $('#daerah').change(function () {
        var daerah_id = $(this).val();
        $('#masjid').html('<option value="">All Masjid</option>');

        if (daerah_id) {
            $.ajax({
                url: 'fetch_masjid.php',
                type: 'GET',
                data: { daerah_id: daerah_id },
                success: function (response) {
                    $('#masjid').append(response);
                }
            });
        }
    });

    // When filter form is submitted
    $('#filterForm').submit(function (event) {
        event.preventDefault();
        loadMeetings();
    });

    function loadMeetings() {
        var formData = $('#filterForm').serialize();

        $.ajax({
            url: 'fetch_meetings.php',
            type: 'GET',
            data: formData,
            success: function (response) {
                $('#meetingResults').html(response);
            }
        });
    }
});
</script>

</body>
</html>
