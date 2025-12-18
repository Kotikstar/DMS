<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: /pages/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];

    $query = $pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :user_id");
    $query->execute(['role_id' => $role_id, 'user_id' => $user_id]);
    $success = true;
}

$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../components/header.php'; ?>
<div class="max-w-6xl mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Админ-панель</h1>
            <a href="/pages/dashboard.php" class="text-blue-600 hover:text-blue-500">Назад в кабинет</a>
        </div>
        <?php if (!empty($success)): ?>
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
                Роль пользователя успешно обновлена.
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="user_id" class="block text-sm font-semibold text-gray-700 mb-2">Выберите пользователя</label>
                <select name="user_id" id="user_id" class="w-full p-3 border border-gray-200 rounded-lg">
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="role_id" class="block text-sm font-semibold text-gray-700 mb-2">Назначить роль</label>
                <select name="role_id" id="role_id" class="w-full p-3 border border-gray-200 rounded-lg">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id']; ?>"><?= htmlspecialchars($role['role_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500 transition">Обновить роль</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
