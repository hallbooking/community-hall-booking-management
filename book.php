<?php
require_once 'config.php';

// Only logged-in users can book a hall
require_login();

$user_id = (int) $_SESSION['user']['id'];

$error = '';
$success = '';

// Get hall ID from URL
$hall_id = filter_input(INPUT_GET, 'hall_id', FILTER_VALIDATE_INT);

// Also accept the hall ID when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hall_id = filter_input(
        INPUT_POST,
        'hall_id',
        FILTER_VALIDATE_INT
    );
}

// Validate hall ID
if (!$hall_id || $hall_id < 1) {
    die("Invalid hall ID. Please select a hall from the home page.");
}

// Fetch active hall details
$stmt = $pdo->prepare(
    "SELECT id, name, location, capacity, price_per_day
     FROM halls
     WHERE id = ? AND status = 'active'"
);

$stmt->execute([$hall_id]);
$hall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hall) {
    die("Hall not found or currently unavailable.");
}

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_name = trim($_POST['event_name'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $guest_count = filter_input(
        INPUT_POST,
        'guest_count',
        FILTER_VALIDATE_INT
    );
    $notes = trim($_POST['notes'] ?? '');

    // Validate input
    if ($event_name === '' || $event_date === '') {

        $error = "Please enter the event name and date.";

    } elseif (
        strlen($event_name) > 120 ||
        strlen($notes) > 5000
    ) {

        $error = "The event name or notes are too long.";

    } elseif (
        !$guest_count ||
        $guest_count < 1 ||
        $guest_count > (int)$hall['capacity']
    ) {

        $error = "Guest count must be between 1 and "
               . (int)$hall['capacity'] . ".";

    } else {

        // Validate date and reject past dates
        $date_object = DateTime::createFromFormat(
            '!Y-m-d',
            $event_date
        );

        $date_errors = DateTime::getLastErrors();

        $valid_date = $date_object !== false
            && (
                $date_errors === false ||
                (
                    $date_errors['warning_count'] === 0 &&
                    $date_errors['error_count'] === 0
                )
            )
            && $date_object->format('Y-m-d') === $event_date;

        if (!$valid_date) {

            $error = "Please enter a valid event date.";

        } elseif ($event_date < date('Y-m-d')) {

            $error = "You cannot book a hall for a past date.";

        } else {

            try {

                // Start transaction to check availability and save booking
                $pdo->beginTransaction();

                // Lock hall row to prevent simultaneous booking requests
                $lock = $pdo->prepare(
                    "SELECT id
                     FROM halls
                     WHERE id = ?
                       AND status = 'active'
                     FOR UPDATE"
                );

                $lock->execute([$hall_id]);

                if (!$lock->fetch()) {

                    $pdo->rollBack();
                    $error = "This hall is no longer available.";

                } else {

                    // Check for an existing pending or approved booking
                    $check = $pdo->prepare(
                        "SELECT id
                         FROM bookings
                         WHERE hall_id = ?
                           AND event_date = ?
                           AND status IN ('pending', 'approved')
                         LIMIT 1"
                    );

                    $check->execute([
                        $hall_id,
                        $event_date
                    ]);

                    if ($check->fetch()) {

                        $pdo->rollBack();

                        $error = "This hall already has a booking "
                               . "request for that date. "
                               . "Please select another date.";

                    } else {

                        // Calculate booking amount
                        $total_amount =
                            (float)$hall['price_per_day'];

                        // Save booking request
                        $insert = $pdo->prepare(
                            "INSERT INTO bookings
                            (
                                user_id,
                                hall_id,
                                event_name,
                                event_date,
                                guest_count,
                                notes,
                                total_amount,
                                status
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
                        );

                        $insert->execute([
                            $user_id,
                            $hall_id,
                            $event_name,
                            $event_date,
                            $guest_count,
                            $notes,
                            $total_amount
                        ]);

                        $pdo->commit();

                        // Redirect to booking history
                        header(
                            "Location: my_bookings.php?booked=1"
                        );
                        exit;
                    }
                }

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = "Booking failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Book a Hall - Community Hall Booking System</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <h1>Community Hall Booking System</h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="container">

        <h2>Book a Community Hall</h2>

        <div class="hall-card">

            <h3><?= e($hall['name']) ?></h3>

            <p>
                Location:
                <?= e($hall['location']) ?>
            </p>

            <p>
                Maximum Capacity:
                <?= (int)$hall['capacity'] ?> guests
            </p>

            <p>
                Price per day: ₹<?= number_format(
                    (float)$hall['price_per_day'],
                    2
                ) ?>
            </p>

        </div>

        <?php if ($error !== ''): ?>

            <p style="color: red;">
                <?= e($error) ?>
            </p>

        <?php endif; ?>

        <form method="POST" action="book.php">

            <input
                type="hidden"
                name="hall_id"
                value="<?= (int)$hall['id'] ?>"
            >

            <label for="event_name">Event Name:</label>

            <input
                type="text"
                id="event_name"
                name="event_name"
                maxlength="120"
                placeholder="Example: Wedding Ceremony"
                required
            >

            <br><br>

            <label for="event_date">Event Date:</label>

            <input
                type="date"
                id="event_date"
                name="event_date"
                min="<?= date('Y-m-d') ?>"
                required
            >

            <br><br>

            <label for="guest_count">Number of Guests:</label>

            <input
                type="number"
                id="guest_count"
                name="guest_count"
                min="1"
                max="<?= (int)$hall['capacity'] ?>"
                required
            >

            <br><br>

            <label for="notes">Additional Requirements:</label>

            <textarea
                id="notes"
                name="notes"
                rows="4"
                maxlength="5000"
                placeholder="Enter any special requirements"
            ></textarea>

            <br><br>

            <button type="submit">
                Submit Booking Request
            </button>

        </form>

        <p>
            <a href="index.php">Back to Available Halls</a>
        </p>

    </div>

</body>
</html>