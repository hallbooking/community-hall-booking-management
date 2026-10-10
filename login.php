<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = "Please enter email and password.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify(
            $password,
            $user['password_hash']
        )) {

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role']
            ];

            if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }

            exit;

        } else {

            $error = "Invalid email or password.";

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

    <title>Login - Community Hall Booking System</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <h1>Community Hall Booking System</h1>

    <div class="login-container">

        <h2>User Login</h2>

        <?php if ($error !== ''): ?>
            <p style="color: red;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label for="email">Email Address:</label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <br><br>

            <label for="password">Password:</label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <br><br>

            <button type="submit">Login</button>

        </form>

        <p>
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

        <p>
            <a href="index.php">Back to Home</a>
        </p>

    </div>

</body>
</html>