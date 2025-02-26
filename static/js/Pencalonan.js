document.getElementById('calendar').addEventListener('change', function () {
    const calendar = document.getElementById('calendar');
    const formSection = document.getElementById('formSection');

    if (calendar.value) {
        formSection.style.display = 'block';
    } else {
        formSection.style.display = 'none';
    }
});
