<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['pending_registration'])) {
    header('Location: register_form.php');
    exit();
}
$pending = $_SESSION['pending_registration'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = $_POST['otp'] ?? '';
    if (time() > $pending['otp_expires']) {
        $error = 'OTP expired. Please register again.';
        unset($_SESSION['pending_registration']);
    } elseif ($input_otp == $pending['otp']) {
        // Insert user
        $hashed_password = password_hash($pending['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO user_form(name, email, phone_number, password, user_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $pending['name'], $pending['email'], $pending['phone'], $hashed_password, $pending['user_type']);
        if ($stmt->execute()) {
            unset($_SESSION['pending_registration']);
            echo '<script>alert("Registration successful! You can now login."); window.location="login_form.php";</script>';
            exit();
        } else {
            $error = 'Registration failed. Please try again.';
        }
    } else {
        $error = 'Invalid OTP. Please check your email and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Poppins', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .otp-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 36px 32px; max-width: 350px; width: 100%; }
        h2 { color: #1976d2; margin-bottom: 18px; }
        .info { color: #555; margin-bottom: 18px; }
        input[type="text"] { width: 100%; padding: 10px; font-size: 1.1em; border: 1.5px solid #1976d2; border-radius: 6px; margin-bottom: 18px; }
        button { width: 100%; background: #1976d2; color: #fff; border: none; padding: 12px; border-radius: 6px; font-size: 1.1em; font-weight: 600; cursor: pointer; }
        .error { color: #e74c3c; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="otp-container">
        <h2>Verify Your Email</h2>
        <div class="info">Enter the 6-digit OTP sent to <b><?= htmlspecialchars($pending['email']) ?></b></div>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="text" name="otp" maxlength="6" pattern="\d{6}" placeholder="Enter OTP" required autofocus>
            <button type="submit">Verify</button>
        </form>
    </div>
</body>
</html> 