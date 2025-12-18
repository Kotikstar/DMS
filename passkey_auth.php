<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passkey = trim((string)($_POST['passkey'] ?? ''));

    $query = $pdo->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.passkey_hash IS NOT NULL AND u.passkey_label = :passkey_label');
    $query->execute(['passkey_label' => $passkey]);
    $user = $query->fetch();

    if ($user && password_verify($passkey, $user['passkey_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $user['username'];
        header('Location: /pages/dashboard.php');
        exit;
    }

    echo "<script>alert('Неверный пасс-ключ. Попробуйте снова.');window.location.href='/pages/login.php';</script>";
    exit;
}

header('Location: /pages/login.php');
exit;
