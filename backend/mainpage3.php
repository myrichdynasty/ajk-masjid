<?php
session_start();
include('connection.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Page</title>
</head>
<body>
<?php require '../include/header.php'; ?>

<div class="d-flex justify-content-center flex-wrap gap-3 text-center mt-4 mb-4">
        <!-- Button for Mesyuarat Agung Tahunan (No action) -->
        <button type="button" class="btn btn-primary mb-2" disabled>Mesyuarat Agung Tahunan</button>

        <!-- Button for Mesyuarat Agung Pencalonan Jawatankuasa Kariah (Redirect to choosedate.html) -->
        <a href="../backend/PejabatAgamaDaerah.php">
            <button type="button" class="btn btn-primary mb-2" disabled>Mesyuarat Agung Pencalonan Jawatankuasa Kariah</button>
        </a>

        <a href="../backend/form_JHEPP.php">
            <button type="button" class="btn btn-primary mb-2">Form</button>
        </a>
    </div>

    <?php require '../include/footer.php'; ?>
<!-- </body> -->
</html>
