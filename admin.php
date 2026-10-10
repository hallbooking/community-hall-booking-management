<?php
require_once 'config.php';

// Only administrators can access this page
require_admin();

$error = '';
$message = '';

// Handle booking approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $booking_id = filter_input(
        INPUT_POST,
        'booking_id',
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';

    if (!$booking_id || $booking_id < 1) {

        $error = "Invalid booking ID.";

    } elseif (!in_array($action, ['approve', 'reject'], true)) {

        $error = "Invalid action.";

    } else {

        try {

            $new_status = ($action === 'approve')
                ? 'approved'
                : 'rejected';

            $pdo->beginTransaction();

            // Lock the booking row while processing the request
            $stmt = $pdo->prepare(
                "SELECT id, hall_id, event_date, status
                 FROM bookings
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {

                $pdo->rollBack();
                $error = "Booking not found.";

            } elseif ($booking['status'] !== 'pending') {

                $pdo->rollBack();
                $error = "Only pending bookings can be processed.";

            } else {

                // Check for a conflicting booking before approval
                if ($new_status === 'approved') {

                    $check = $pdo->prepare(
                        "SELECT id
                         FROM bookings
                         WHERE hall_id = ?
                           AND event_date = ?
                           AND status = 'approved'
                           AND id <> ?
                         LIMIT 1"
                    );

                    $check->execute([
                        $booking['hall_id'],
                        $booking['event_date'],
                        $booking_id
                    ]);

                    if ($check->fetch()) {

                        $pdo->rollBack();

                        $error = "Another booking for this hall "
                               . "and date is already approved.";

                    } else {

                        $update = $pdo->prepare(
                            "UPDATE bookings
                             SET status = ?
                             WHERE id = ?
                               AND status = 'pending'"
                        );

                        $update->execute([
                            $new_status,
                            $booking_id
                        ]);

                        $pdo->commit();

                        $message = "Booking approved successfully.";
                    }

                } else {

                    $update = $pdo->prepare(
                        "UPDATE bookings
                         SET status = ?
                         WHERE id = ?
                           AND status = 'pending'"
                    );

                    $update->execute([
                        $new_status,
                        $booking_id
                    ]);

                    $pdo->commit();

                    $message = "Booking rejected successfully.";
                }
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Unable to update the booking. Please try again.";
        }
    }
}

// Retrieve all bookings
$stmt = $pdo->query(
    "SELECT
        b.id,
        b.event_name,
        b.event_date,
        b.guest_count,
        b.total_amount,
        b.status,
        b.created_at,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        h.name AS hall_name,
        h.location AS hall_location
     FROM bookings b
     INNER JOIN users u ON b.user_id = u.id
     INNER JOIN halls h ON b.hall_id = h.id
     ORDER BY
        CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END,
        b.created_at DESC"
);

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dashboard counts
$stats = $pdo->query(
    "SELECT
        COUNT(*) AS total_bookings,
        COALESCE(SUM(status = 'pending'), 0) AS pending_bookings,
        COALESCE(SUM(status = 'approved'), 0) AS approved_bookings,
        COALESCE(SUM(status = 'rejected'), 0) AS rejected_bookings
     FROM bookings"
)->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Community Hall Booking System</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <h1>Community Hall Booking System</h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="admin.php">Admin Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="container">

        <h2>Administrator Dashboard</h2>

        <p>
            Welcome,
            <?= e($_SESSION['user']['name']) ?>!
        </p>

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

        <h3>Booking Statistics</h3>

        <p>
            Total Bookings:
            <?= (int)$stats['total_bookings'] ?>
        </p>

        <p>
            Pending Bookings:
            <?= (int)$stats['pending_bookings'] ?>
        </p>

        <p>
            Approved Bookings:
            <?= (int)$stats['approved_bookings'] ?>
        </p>

        <p>
            Rejected Bookings:
            <?= (int)$stats['rejected_bookings'] ?>
        </p>

        <h3>Manage Booking Requests</h3>

        <?php if (count($bookings) > 0): ?>

            <div style="overflow-x: auto;">

                <table border="1"
                       cellpadding="8"
                       cellspacing="0">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Hall</th>
                            <th>Location</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Guests</th>
                            <th>Amount</th>
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
                                <?= e($booking['customer_name']) ?>
                            </td>

                            <td>
                                <?= e($booking['customer_email']) ?>
                            </td>

                            <td>
                                <?= e($booking['customer_phone']) ?>
                            </td>

                            <td>
                                <?= e($booking['hall_name']) ?>
                            </td>

                            <td>
                                <?= e($booking['hall_location']) ?>
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

                                <?php if (
                                    $booking['status'] === 'pending'
                                ): ?>

                                    <form method="POST"
                                          action="admin.php"
                                          style="margin-bottom: 5px;">

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$booking['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve">
                                            Approve
                                        </button>

                                    </form>

                                    <form method="POST"
                                          action="admin.php">

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$booking['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject">
                                            Reject
                                        </button>

                                    </form>

                                <?php else: ?>

                                    No action available

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p>No booking requests are available.</p>

        <?php endif; ?>

        <p>
            <a href="index.php">Back to Home</a>
        </p>

    </div>

</body>
</html>