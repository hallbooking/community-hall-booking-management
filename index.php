<?php
require_once 'config.php';

$stmt = $pdo->query(
    "SELECT * FROM halls
     WHERE status = 'active'
     ORDER BY id DESC"
);

$halls = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Community Hall Booking System</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<h1>Community Hall Booking System</h1>

<nav>
    <a href="index.php">Home</a>

    <?php if (isset($_SESSION['user'])): ?>

        <a href="my_bookings.php">My Bookings</a>
        <a href="logout.php">Logout</a>

    <?php else: ?>

        <a href="register.php">Register</a>
        <a href="login.php">Login</a>

    <?php endif; ?>
</nav>

<h2>Available Community Halls</h2>

<div class="hall-container">

<?php foreach ($halls as $hall): ?>

    <div class="hall-card">

        <h3><?= e($hall['name']) ?></h3>

        <p>
            Location:
            <?= e($hall['location']) ?>
        </p>

        <p>
            Capacity:
            <?= (int)$hall['capacity'] ?> people
        </p>

        <p>
            Price per day: ₹
            <?= number_format(
                (float)$hall['price_per_day'],
                2
            ) ?>
        </p>

        <p>
            <?= e($hall['description']) ?>
        </p>

        <?php if (isset($_SESSION['user'])): ?>

            <a href="book.php?hall_id=<?= (int)$hall['id'] ?>">
                Book This Hall
            </a>

        <?php else: ?>

            <a href="login.php">
                Login to Book
            </a>

        <?php endif; ?>

    </div>

<?php endforeach; ?>

</div>

</body>
</html>