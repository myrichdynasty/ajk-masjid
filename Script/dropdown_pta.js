function toggleMasjidList(name) {
    var list = document.getElementById("masjid-list-" + name);
    if (list.style.display === "none" || list.style.display === "") {
        list.style.display = "block";
    } else {
        list.style.display = "none";
    }
}
function openFormSelection(masjidName) {
    window.location.href = `select_form_PTA.php?masjid=${encodeURIComponent(masjidName)}`;
}
