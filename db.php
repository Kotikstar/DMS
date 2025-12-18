<?php
$host = 'MySQL-8.4';
$db = 'DMS';
$user = 'root'; // Your database credentials
$pass = ''; // Your database credentials

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Connection failed: ' . htmlspecialchars($e->getMessage());
    exit;
}
