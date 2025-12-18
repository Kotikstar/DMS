<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/AccessControl.php';

if (empty($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== 1) {
    header('Location: /pages/dashboard.php');
    exit;
}

$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$userStmt->execute(['id' => $_SESSION['user_id']]);
$currentUser = $userStmt->fetch();

$users = $pdo->query('SELECT u.id, u.username, u.email, r.role_name FROM users u JOIN roles r ON r.id = u.role_id')->fetchAll();
$roles = $pdo->query('SELECT * FROM roles')->fetchAll();
$aclRows = $pdo->query('SELECT * FROM document_acl ORDER BY document_path')->fetchAll();

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'], $_POST['role_id'])) {
        $user_id = (int) $_POST['user_id'];
        $role_id = (int) $_POST['role_id'];
        $query = $pdo->prepare('UPDATE users SET role_id = :role_id WHERE id = :user_id');
        $query->execute(['role_id' => $role_id, 'user_id' => $user_id]);
        $success = 'Роль пользователя обновлена.';
    }

    if (isset($_POST['document_path'])) {
        $path = trim($_POST['document_path']);
        $role_id = !empty($_POST['acl_role_id']) ? (int) $_POST['acl_role_id'] : null;
        $user_id = !empty($_POST['acl_user_id']) ? (int) $_POST['acl_user_id'] : null;
        $can_read = isset($_POST['can_read']) ? 1 : 0;
        $can_write = isset($_POST['can_write']) ? 1 : 0;

        if (!$role_id && !$user_id) {
            $error = 'Выберите роль или пользователя для ACL.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO document_acl (document_path, user_id, role_id, can_read, can_write) VALUES (:path, :user_id, :role_id, :can_read, :can_write)
                ON DUPLICATE KEY UPDATE can_read = VALUES(can_read), can_write = VALUES(can_write)');
            $stmt->execute([
                'path' => $path,
                'user_id' => $user_id,
                'role_id' => $role_id,
                'can_read' => $can_read,
                'can_write' => $can_write,
            ]);
            $success = 'ACL сохранён для ' . $path;
        }
    }

    $aclRows = $pdo->query('SELECT * FROM document_acl ORDER BY document_path')->fetchAll();
}
?>
<?php require_once __DIR__ . '/../components/header.php'; ?>
<div class="max-w-6xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Админ-панель</h1>
            <p class="text-gray-600">Управляйте ролями пользователей и ACL документов.</p>
        </div>
        <a href="/pages/dashboard.php" class="text-blue-600 hover:text-blue-500">Назад в кабинет</a>
    </div>

    <?php if ($success): ?>
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Роли пользователей</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label for="user_id" class="block text-sm font-semibold text-gray-700 mb-2">Выберите пользователя</label>
                    <select name="user_id" id="user_id" class="w-full p-3 border border-gray-200 rounded-lg">
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['username']); ?> — <?= htmlspecialchars($user['role_name']); ?></option>
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

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">ACL документов</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Путь документа</label>
                    <input type="text" name="document_path" required placeholder="docs/specs/security.md" class="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Пользователь</label>
                        <select name="acl_user_id" class="w-full p-3 border border-gray-200 rounded-lg">
                            <option value="">—</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Роль</label>
                        <select name="acl_role_id" class="w-full p-3 border border-gray-200 rounded-lg">
                            <option value="">—</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id']; ?>"><?= htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="can_read" class="rounded border-gray-300"> Чтение
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="can_write" class="rounded border-gray-300"> Запись
                    </label>
                </div>
                <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-500 transition">Сохранить ACL</button>
            </form>

            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-2">Текущие правила</h3>
                <?php if ($aclRows): ?>
                    <ul class="space-y-2 text-sm">
                        <?php foreach ($aclRows as $row): ?>
                            <li class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <p class="font-semibold"><?= htmlspecialchars($row['document_path']); ?></p>
                                <p class="text-gray-600">Пользователь: <?= $row['user_id'] ?: '—'; ?> | Роль: <?= $row['role_id'] ?: '—'; ?></p>
                                <p class="text-gray-600">Чтение: <?= $row['can_read'] ? 'да' : 'нет'; ?> • Запись: <?= $row['can_write'] ? 'да' : 'нет'; ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Пока нет правил. Добавьте их выше.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
