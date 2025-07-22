<?php
$host = "localhost";
$username = "root";
$password = "";  // Empty string since no password is set
$database = "lawfirm";

$conn = mysqli_connect("localhost", "root", "", "lawfirm", 3307);


// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Email sender config for OTP/notifications
if (!defined('MAIL_HOST')) define('MAIL_HOST', 'smtp.gmail.com');
if (!defined('MAIL_USERNAME')) define('MAIL_USERNAME', 'mirandakianandrei25@gmail.com'); // <-- Palitan ng Gmail mo
if (!defined('MAIL_PASSWORD')) define('MAIL_PASSWORD', 'yqau wbue blah gcpj');    // <-- Palitan ng App Password mo
if (!defined('MAIL_FROM')) define('MAIL_FROM', 'mirandakianandrei25@gmail.com');         // <-- Palitan ng Gmail mo
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Opiña Law Office');
?>
