function doubleConfirmReject() {
    // First confirmation
    let firstConfirm = confirm("Are you sure you want to reject this user?");
    if (!firstConfirm) return false; // Stop submission if canceled

    // Second confirmation
    let secondConfirm = confirm("This action is irreversible. Do you want to proceed?");
    return secondConfirm; // Stop submission if canceled
}