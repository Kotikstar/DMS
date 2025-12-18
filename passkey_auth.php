<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $passkey = $_POST['passkey'];
    $query = $pdo->prepare("SELECT * FROM users WHERE passkey = :passkey");
    $query->execute(['passkey' => $passkey]);

    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        header('Location: dashboard.php');
        exit;
    } else {
        echo "<script>alert('Invalid Passkey!');window.location.href='login.php';</script>";
    }
}
?>