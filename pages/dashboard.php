<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}

$query = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$query->execute(['id' => $_SESSION['user_id']]);
$user = $query->fetch(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../components/header.php'; ?>
<div class="max-w-6xl mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Привет, <?= htmlspecialchars($user['username']); ?></h1>
        <p class="text-gray-600 mb-6">Ваша почта: <?= htmlspecialchars($user['email']); ?></p>
        <div class="grid sm:grid-cols-3 gap-4 mb-8">
            <div class="p-4 rounded-xl bg-blue-50 text-blue-800">
                <p class="text-sm">Статус</p>
                <p class="text-xl font-semibold">Онлайн</p>
            </div>
            <div class="p-4 rounded-xl bg-green-50 text-green-800">
                <p class="text-sm">Роль</p>
                <p class="text-xl font-semibold"><?= htmlspecialchars($user['role_id']); ?></p>
            </div>
            <div class="p-4 rounded-xl bg-amber-50 text-amber-800">
                <p class="text-sm">Последний вход</p>
                <p class="text-xl font-semibold">Недавно</p>
            </div>
        </div>
        <a href="/logout.php" class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-500 transition">Выйти</a>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
