<?php
session_start();
include('connection.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PILIH TARIKH CADANGAN</title>
    <style>
        .form-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
    </style>
</head>
<body>
<?php require '../include/header.php'; ?>
<div class="container mt-5">
        <div class="card form-card mx-auto" style="max-width: 500px;">
            <h2 class="text-center mb-4">CADANGAN TARIKH MESYUARAT</h2>
            <form action="../backend/db_choosedate.php" method="POST">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="date" class="form-label">PILIH TARIKH:</label>
                            <input type="date" id="date" name="date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="time" class="form-label">PILIH MASA:</label>
                            <input type="time" id="time" name="time" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="place" class="form-label">PILIH TEMPAT:</label>
                            <input type="text" id="place" name="place" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <b><label for="place" class="form-label">NAMA CADANGAN PENGERUSI MESYUARAT(1):</label></b>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="nama_cadangan1">NAMA PENUH:</label>
                            <input class="form-control" name="nama_cadangan1" id="nama_cadangan1" value="" type="text" required/>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="ic_cadangan1">NO KAD PENGENALAN:</label>
                            <input class="form-control" name="ic_cadangan1" id="ic_cadangan1" value="" type="text" required/>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone_cadangan1">NO TELEFON:</label>
                            <input class="form-control" name="phone_cadangan1" id="phone_cadangan1" value="" type="text" required/>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email_cadangan1">EMAIL:</label>
                            <input class="form-control" name="email_cadangan1" id="email_cadangan1" value="" type="text" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <b><label for="place" class="form-label">NAMA CADANGAN PENGERUSI MESYUARAT(2):</label></b>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="nama_cadangan2">NAMA PENUH:</label>
                            <input class="form-control" name="nama_cadangan2" id="nama_cadangan2" value="" type="text" required/>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="ic_cadangan2">NO KAD PENGENALAN:</label>
                            <input class="form-control" name="ic_cadangan2" id="ic_cadangan2" value="" type="text" required/>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone_cadangan2">NO TELEFON:</label>
                            <input class="form-control" name="phone_cadangan2" id="phone_cadangan2" value="" type="text" required/>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email_cadangan2">EMAIL:</label>
                            <input class="form-control" name="email_cadangan2" id="email_cadangan2" value="" type="text" />
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" name="submit" class="btn btn-primary mt-3">HANTAR</button>
                </div>
            </form>
        </div>
    </div>
    <?php require '../include/footer.php'; ?>
</body>
</html>

<script src="js/jquery.js"></script>
<script src="js/app.js"></script>

<script>
	//define template
	var template = $('#sections .section:first').clone();

	//define counter
	var sectionsCount = 1;

	//add new section
	$('body').on('click', '.addsection', function() {

		//increment
		sectionsCount++;

		//loop through each input
		var section = template.clone().find(':input').each(function(){

			//set id to store the updated section number
			var newId = this.id + sectionsCount;

			//update for label
			$(this).prev().attr('for', newId);

			//update id
			this.id = newId;

		}).end()

		//inject new section
		.appendTo('#sections');
		return false;
	});

	//remove section
	$('#sections').on('click', '.remove', function() {
		//fade out section
		$(this).parent().fadeOut(300, function(){
			//remove parent element (main section)
			$(this).parent().parent().empty();
			return false;
		});
		return false;
	});
</script>