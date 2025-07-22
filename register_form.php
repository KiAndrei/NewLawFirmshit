<?php
session_start();
@include 'config.php';

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $pass = $_POST['password'];
    $cpass = $_POST['cpassword'];
    $user_type = $_POST['user_type'];

    // Phone number validation (server-side)
    if (!preg_match('/^\d{11}$/', $phone)) {
        $_SESSION['error'] = "Phone number must be exactly 11 digits.";
        header("Location: register_form.php");
        exit();
    }

    // Email must be @gmail.com only (server-side)
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        $_SESSION['error'] = "Email address must be a valid @gmail.com address only.";
        header("Location: register_form.php");
        exit();
    }

    // Password requirements check (server-side)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[_@#*%])[A-Za-z\d_@#*%]{8,}$/', $pass)) {
        $_SESSION['error'] = "Password must be at least 8 characters, include uppercase and lowercase letters, at least one number, and at least one special character (_ @ # * %).";
        header("Location: register_form.php");
        exit();
    }

    // Password match check
    if ($pass != $cpass) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: register_form.php");
        exit();
    }

    // Check if user already exists (email only)
    $select = "SELECT * FROM user_form WHERE email = ?";
    $stmt = mysqli_prepare($conn, $select);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "User already exists!";
        header("Location: register_form.php");
        exit();
    }

    // OTP logic
    require_once __DIR__ . '/vendor/autoload.php';
    $otp = rand(100000, 999999);
    $_SESSION['pending_registration'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => $pass,
        'user_type' => $user_type,
        'otp' => $otp,
        'otp_expires' => time() + 300 // 5 minutes
    ];
    // Send OTP email
    require_once 'send_otp_email.php';
    send_otp_email($email, $otp);
    header('Location: verify_otp.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: #f5f5f5;
        }

        .left-container {
            width: 45%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg,rgb(124, 13, 13),rgb(53, 20, 20));
            padding: 20px;
            position: relative;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .title-container {
            display: flex;
            align-items: center;
            position: absolute;
            top: 10px;
            left: 15px;
        }

        .title-container img {
            width: 25px;
            height: 25px;
            margin-right: 5px;
        }

        .title {
            font-size: 18px;
            font-weight: 600;
            color: #fff0f0;
            letter-spacing: 1px;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            gap: 6px;
        }

        .header-container img {
            width: 35px;
            height: 35px;
        }

        .law-office-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #fff0f0;
            font-family: 'Roboto Slab', serif;
            letter-spacing: 1px;
        }

        .form-header {
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 15px;
            color: #fff0f0;
        }

        .form-container {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
        }

        .form-container label {
            font-size: 12px;
            font-weight: 500;
            display: block;
            margin: 8px 0 2px;
            color: #ffffff;
            text-align: left;
        }

        .form-container input, .form-container select {
            width: 100%;
            padding: 8px 10px;
            font-size: 13px;
            border: none;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            background: transparent;
            color: #ffffff;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-container input:focus, .form-container select:focus {
            border-bottom: 2px solid #ffffff;
        }

        .form-container input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-container select option {
            background: #2c3e50;
            color: #ffffff;
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-container i:hover {
            color: #ffffff;
        }

        .form-container .form-btn {
            background: linear-gradient(90deg, #800000 60%, #a94442 100%);
            color: #fff0f0;
            border: none;
            cursor: pointer;
            padding: 10px;
            font-size: 14px;
            font-weight: 500;
            width: 100%;
            margin-top: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .form-container .form-btn:hover {
            background: linear-gradient(90deg, #a94442 60%, #800000 100%);
            color: #fff;
            transform: translateY(-2px);
        }

        .right-container {
            width: 55%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #2c3e50;
            text-align: center;
            padding: 20px;
            background: #ffffff;
            overflow: hidden;
        }
        .right-blur-img {
            position: absolute;
            right: 0;
            top: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: blur(2px) brightness(0.7);
            z-index: 0;
        }
        .login-box {
            position: relative;
            z-index: 1;
        }
        .login-btn {
            position: relative;
            z-index: 1;
        }
        .typing-effect {
            display: inline-block;
            border-right: 2px solid #800000;
            white-space: nowrap;
            overflow: hidden;
            animation: typing 2.5s steps(30, end), blink-caret 0.75s step-end infinite;
        }
        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }
        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: #800000; }
        }

        .error-popup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff6b6b;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            z-index: 1000;
            width: 90%;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translate(-50%, -20px);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .error-popup p {
            margin: 0;
            font-size: 14px;
        }

        .error-popup button {
            background: white;
            border: none;
            padding: 8px 15px;
            color: #ff6b6b;
            font-weight: 500;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .error-popup button:hover {
            background: #f0f0f0;
        }

        .login-box h1 {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .login-box h1, .login-box .typing-effect {
            color: #800000;
        }

        .login-btn {
            display: inline-block;
            background: linear-gradient(90deg, #800000 60%, #a94442 100%);
            color: #fff0f0;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .login-btn:hover {
            background: linear-gradient(90deg, #a94442 60%, #800000 100%);
            color: #fff;
            transform: translateY(-2px);
        }

        @media (max-width: 1024px) {
            .left-container {
                width: 50%;
            }

            .right-container {
                width: 50%;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .left-container, .right-container {
                width: 100%;
                padding: 40px 20px;
            }

            .form-header {
                font-size: 24px;
            }

            .login-box h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .title {
                font-size: 20px;
            }

            .form-header {
                font-size: 22px;
            }

            .form-container input {
                font-size: 14px;
                padding: 10px 12px;
            }

            .form-container .form-btn {
                padding: 12px;
                font-size: 15px;
            }

            .login-box h1 {
                font-size: 24px;
            }

            .login-btn {
                padding: 12px 25px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['ernoror'])): ?>
        <div class="error-popup">
            <p><?php echo $_SESSION['error']; ?></p>
            <button onclick="closePopup()">OK</button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="left-container">
        <div class="title-container">
            <img src="images/logo.jpg" alt="Logo">
            <div class="title">LawFirm.</div>
        </div>

        <div class="header-container">
            <h1 class="law-office-title">Opiña Law<br>Office</h1>
            <img src="images/justice.png" alt="Attorney Icon">
        </div>

        <div class="form-container">
            <h2 class="form-header">Register</h2>

            <form action="" method="post">
                <label for="name">Full Name</label>
                <input type="text" name="name" required placeholder="Enter your full name">

                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your email" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" title="Email must be a valid @gmail.com address only">

                <label for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone" required placeholder="Enter your phone number" maxlength="11" pattern="\d{11}" title="Phone number must be exactly 11 digits">

                <label for="user_type">User Type</label>
                <select name="user_type" required class="form-select">
                    <option value="">Select User Type</option>
                    <option value="client">Client</option>
                    <option value="attorney">Attorney</option>
                    <option value="admin">Admin</option>
                </select>

                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" required placeholder="Enter your password">
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
                <ul style="color:#fff; font-size:12px; margin-bottom:8px; margin-top:2px; padding-left:18px;">
                    <li>Password requirements:</li>
                    <li>At least 8 characters</li>
                    <li>Must include uppercase and lowercase letters</li>
                    <li>Must include at least one number</li>
                    <li>Must include at least one special character (_ @ # * %)</li>
                </ul>

                <label for="cpassword">Confirm Password</label>
                <div class="password-container">
                    <input type="password" name="cpassword" id="cpassword" required placeholder="Confirm your password">
                    <i class="fas fa-eye" id="toggleCPassword"></i>
                </div>

                <input type="submit" name="submit" value="Register" class="form-btn">
            </form>
        </div>
    </div>

    <div class="right-container">
        <img src="images/atty3.jpg" alt="Justice" class="right-blur-img">
        <div class="login-box">
            <h1><span class="typing-effect">Already have an account?</span></h1>
        </div>
        <a href="login_form.php" class="login-btn">Login Now</a>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            let passwordField = document.getElementById('password');
            let icon = this;
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('toggleCPassword').addEventListener('click', function () {
            let passwordField = document.getElementById('cpassword');
            let icon = this;
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        function closePopup() {
            document.querySelector('.error-popup').style.display = 'none';
        }

        // Password validation (client-side)
        document.querySelector('form').addEventListener('submit', function(e) {
            var pass = document.getElementById('password').value;
            var cpass = document.getElementById('cpassword').value;
            var requirements = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[_@#*%])[A-Za-z\d_@#*%]{8,}$/;
            if (!requirements.test(pass)) {
                alert('Password must be at least 8 characters, include uppercase and lowercase letters, at least one number, and at least one special character (_ @ # * %).');
                e.preventDefault();
                return false;
            }
            if (pass !== cpass) {
                alert('Confirm password does not match the password.');
                e.preventDefault();
                return false;
            }
        });

        // Limit phone input to 11 digits only (client-side)
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '').slice(0, 11);
        });
    </script>

    <script src="https://kit.fontawesome.com/cc86d7b31d.js" crossorigin="anonymous"></script>
</body>
</html>
