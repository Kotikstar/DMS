<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$query = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$query->execute(['id' => $_SESSION['user_id']]);
$user = $query->fetch(PDO::FETCH_ASSOC);
?>
<?php include 'components/header.php'; ?>
<div class="container mx-auto py-20 text-center">
    <h1 class="text-4xl font-bold text-gray-800">Welcome, <?= htmlspecialchars($user['username']); ?></h1>
    <p class="text-gray-600 mt-4">Your email: <?= htmlspecialchars($user['email']); ?></p>
    <a href="logout.php" class="mt-6 inline-block px-6 py-3 text-white bg-red-600 rounded-lg hover:bg-red-700">Logout</a>
</div>
<?php include 'components/footer.php'; ?>