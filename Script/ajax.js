function updateRole(selectElement, ic) {
    var selectedRole = selectElement.value;

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../backend/update_role.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200 && xhr.responseText.trim() === "success") { 
                alert("Role updated successfully!");
                
                // Refresh the page after a short delay
                setTimeout(function() {
                    location.reload();
                }, 500); // 500ms delay
            } else {
                alert("Error updating role. Please try again.");
            }
        }
    };

    xhr.send("ic=" + encodeURIComponent(ic) + "&role=" + encodeURIComponent(selectedRole));
}