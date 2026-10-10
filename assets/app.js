// Community Hall Booking and Management System

document.addEventListener("DOMContentLoaded", function () {

    // 1. Registration form password validation
    const registerForm = document.querySelector(
        'form[action="register.php"]'
    );

    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {

            const password = document.getElementById("password");
            const confirmPassword = document.getElementById(
                "confirm_password"
            );

            if (password && confirmPassword) {

                if (password.value.length < 8) {
                    event.preventDefault();
                    alert("Password must contain at least 8 characters.");
                    password.focus();
                    return;
                }

                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    alert("Passwords do not match.");
                    confirmPassword.focus();
                }
            }
        });
    }

    // 2. Prevent selection of past event dates
    const eventDate = document.getElementById("event_date");

    if (eventDate) {

        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");

        const todayString = `${year}-${month}-${day}`;

        eventDate.min = todayString;

        eventDate.addEventListener("change", function () {

            if (eventDate.value && eventDate.value < todayString) {
                alert("Please select today or a future date.");
                eventDate.value = "";
            }
        });
    }

    // 3. Validate guest count against hall capacity
    const guestCount = document.getElementById("guest_count");

    if (guestCount) {

        guestCount.addEventListener("input", function () {

            const maximum = Number(guestCount.max);
            const current = Number(guestCount.value);

            if (guestCount.value !== "" && current > maximum) {
                guestCount.setCustomValidity(
                    "Guest count cannot exceed hall capacity of "
                    + maximum + "."
                );
            } else if (guestCount.value !== "" && current < 1) {
                guestCount.setCustomValidity(
                    "Guest count must be at least 1."
                );
            } else {
                guestCount.setCustomValidity("");
            }
        });
    }

    // 4. Confirm booking cancellation
    const cancelForms = document.querySelectorAll(
        'form[action="my_bookings.php"]'
    );

    cancelForms.forEach(function (form) {

        if (form.querySelector('[name="cancel_booking"]')) {

            form.addEventListener("submit", function (event) {

                const confirmed = confirm(
                    "Are you sure you want to cancel this booking?"
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        }
    });

    // 5. Confirm admin booking actions
    const adminForms = document.querySelectorAll(
        'form[action="admin.php"]'
    );

    adminForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const button = event.submitter;

            if (!button) {
                return;
            }

            let message = "";

            if (button.value === "approve") {
                message = "Are you sure you want to approve this booking?";
            } else if (button.value === "reject") {
                message = "Are you sure you want to reject this booking?";
            }

            if (message && !confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // 6. Automatically hide status messages
    const statusMessages = document.querySelectorAll(
        ".success, .error"
    );

    statusMessages.forEach(function (message) {

        setTimeout(function () {
            message.style.transition = "opacity 0.5s";
            message.style.opacity = "0";

            setTimeout(function () {
                message.style.display = "none";
            }, 500);

        }, 5000);
    });

});