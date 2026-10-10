<?php
require_once 'config.php';

// Only logged-in users can view bookings
require_login();

$user_id = $_SESSION['user']['id'];
$message = '';
$error = '';

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cancel_booking'])) {

    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($booking_id > 0) {

        // Cancel only this user's pending booking
        $stmt = $pdo->prepare(
            "UPDATE bookings
             SET status = 'cancelled'
             WHERE id = ?
               AND user_id = ?
               AND status = 'pending'"
        );

        $stmt->execute([$booking_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $message = "Booking cancelled successfully.";
        } else {
            $error = "Booking could not be cancelled.";
        }

    } else {
        $error = "Invalid booking.";
    }
}

// Retrieve the logged-in user's bookings
$stmt = $pdo->prepare(
    "SELECT
        b.id,
        b.event_name,
        b.event_date,
        b.guest_count,
        b.total_amount,
        b.status,
        b.notes,
        h.name AS hall_name,
        h.location
     FROM bookings b
     INNER JOIN halls h ON b.hall_id = h.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);

$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Bookings - Community Hall Booking System</title>

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

        <h2>My Bookings</h2>

        <?php if ($message !== ''): ?>
            <p style="color: green;">
                <?= e($message) ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p style="color: red;">
                <?= e($error) ?>
            </p>
        <?php endif; ?>

        <?php if (count($bookings) > 0): ?>

            <table border="1" cellpadding="10"
                   cellspacing="0">

                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Hall Name</th>
                        <th>Location</th>
                        <th>Event Name</th>
                        <th>Event Date</th>
                        <th>Guests</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($bookings as $booking): ?>

                    <tr>

                        <td>
                            <?= (int)$booking['id'] ?>
                        </td>

                        <td>
                            <?= e($booking['hall_name']) ?>
                        </td>

                        <td>
                            <?= e($booking['location']) ?>
                        </td>

                        <td>
                            <?= e($booking['event_name']) ?>
                        </td>

                        <td>
                            <?= e($booking['event_date']) ?>
                        </td>

                        <td>
                            <?= (int)$booking['guest_count'] ?>
                        </td>

                        <td>
                            ₹<?= number_format(
                                (float)$booking['total_amount'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= e(ucfirst($booking['status'])) ?>
                        </td>

                        <td>

                            <?php
$booking_status = strtolower($booking['status'] ?? '');

if ($booking_status === 'pending') {
?>
    <form method="POST" action="my_bookings.php"
          onsubmit="return confirm('Are you sure you want to cancel this booking?');">
        <input type="hidden" name="booking_id"
               value="<?= (int)$booking['id'] ?>">
        <button type="submit" name="cancel_booking" value="1">
            Cancel Booking
        </button>
    </form>
<?php
} else {
    echo 'No actions available';
}
?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
    <p>You have no bookings yet.</p>
    <a href="index.php">Browse Halls</a>
<?php endif; ?>

</div>
</body>
</html>