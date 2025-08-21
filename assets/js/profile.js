
$(document).ready(function () {
    // Handle profile update
    $('#updateProfileForm').submit(function (e) {
        e.preventDefault();

        $.ajax({
            url: '../../api/profile/update_profile.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert("Profile updated successfully!");
                    location.reload(); // Refresh to reflect changes
                } else {
                    alert("Failed to update profile.");
                }
            },
            error: function () {
                alert("An error occurred while updating profile.");
            }
        });
    });
});


// Handle password change
$('#changePasswordForm').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
        url: '../../api/profile/update_password.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                alert('Password updated successfully!');
                $('#changePasswordForm')[0].reset();
            } else {
                alert(response.message);
            }
        },
        error: function () {
            alert('Error changing password.');
        }
    });
});

// Handle profile image upload
$('#uploadImageForm').on('submit', function (e) {
    e.preventDefault();
    let formData = new FormData(this);
    $.ajax({
        url: '../../api/profile/update_image.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                alert('Image uploaded!');
                location.reload();
            } else {
                alert('Image upload failed.');
            }
        },
        error: function () {
            alert('Error uploading image.');
        }
    });
});
