<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (
        $name === '' ||
        $email === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";

    } else {

        try {

            // Check whether email already exists
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            $stmt->execute([$email]);

            if ($stmt->fetch()) {

                $error = "This email is already registered.";

            } else {

                // Hash the password securely
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Register as a normal customer
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (name, email, phone, password_hash, role)
                    VALUES (?, ?, ?, ?, 'customer')"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $password_hash
                ]);

                $success = "Registration successful! You can now log in.";
            }

        } catch (PDOException $e) {

            $error = "Registration failed. Please try again.";

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

    <title>Register - Community Hall Booking System</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <h1>Community Hall Booking System</h1>

    <div class="register-container">

        <h2>Create an Account</h2>

        <?php if ($error !== ''): ?>
            <p style="color: red;">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p style="color: green;">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p>
                <a href="login.php">Login Now</a>
            </p>

        <?php else: ?>

            <form method="POST" action="register.php">

                <label for="name">Full Name:</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    required
                >

                <br><br>

                <label for="email">Email Address:</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="190"
                    required
                >

                <br><br>

                <label for="phone">Phone Number:</label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="25"
                >

                <br><br>

                <label for="password">Password:</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >

                <br><br>

                <label for="confirm_password">
                    Confirm Password:
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                >

                <br><br>

                <button type="submit">Register</button>

            </form>

        <?php endif; ?>

        <p>
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

        <p>
            <a href="index.php">Back to Home</a>
        </p>

    </div>

</body>
</html>