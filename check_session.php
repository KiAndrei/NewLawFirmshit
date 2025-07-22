<?php
session_start();
header('Content-Type: application/json');

$response = [
    'logged_in' => isset($_SESSION['user_id']) && isset($_SESSION['user_type']),
    'user_type' => $_SESSION['user_type'] ?? null
];

echo json_encode($response);
?> 