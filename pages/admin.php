<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/AccessControl.php';
require_once __DIR__ . '/../services/GithubClient.php';

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
$docsFolders = [];
$config = require __DIR__ . '/../config/github.php';
$docsRoot = $config['docs_path'] ?? 'docs';
$github = new GithubClient($config);

$collectFolders = static function (array $nodes, string $root) use (&$collectFolders): array {
    $folders = [$root];

    foreach ($nodes as $node) {
        if (($node['type'] ?? '') === 'dir') {
            $folders[] = $node['path'];
            $folders = array_merge($folders, $collectFolders($node['children'] ?? [], $root));
        }
    }

    $folders = array_values(array_unique(array_filter($folders)));
    sort($folders);

    return $folders;
};

try {
    $tree = $github->getDocsTree($docsRoot);
    $docsFolders = $collectFolders($tree, $docsRoot);
} catch (Throwable $e) {
    $error = 'Не удалось загрузить дерево документов: ' . $e->getMessage();
}

$normalizePath = static function (string $path, string $base): string {
    $cleanBase = trim($base, '/');
    $clean = trim($path, '/');

    if ($clean === '' || $clean === '.') {
        return $cleanBase;
    }

    if ($cleanBase && stripos($clean, $cleanBase . '/') !== 0 && $clean !== $cleanBase) {
        $clean = $cleanBase . '/' . $clean;
    }

    return $clean;
};

$success = null;
$error = $error ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $username = trim($_POST['new_username'] ?? '');
        $email = trim($_POST['new_email'] ?? '');
        $passkey = trim($_POST['new_passkey'] ?? '');
        $role_id = (int) ($_POST['new_role_id'] ?? 3);

        if ($username === '' || $email === '' || $passkey === '') {
            $error = 'Заполните имя, email и пасс-ключ.';
        } else {
            try {
                $hash = password_hash($passkey, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (username, email, passkey_label, passkey_hash, role_id) VALUES (:username, :email, :label, :hash, :role_id)');
                $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'label' => $passkey,
                    'hash' => $hash,
                    'role_id' => $role_id,
                ]);

                $newUserId = (int) $pdo->lastInsertId();

                $fullAccess = $_POST['full_access_paths'] ?? [];
                $readOnly = $_POST['read_access_paths'] ?? [];
                $hidden = $_POST['hidden_paths'] ?? [];

                $insertAcl = $pdo->prepare('INSERT INTO document_acl (document_path, user_id, role_id, can_read, can_write) VALUES (:path, :user_id, NULL, :can_read, :can_write)
                    ON DUPLICATE KEY UPDATE can_read = VALUES(can_read), can_write = VALUES(can_write)');

                foreach ($fullAccess as $path) {
                    $cleanPath = $normalizePath((string) $path, $docsRoot);
                    $insertAcl->execute([
                        'path' => $cleanPath,
                        'user_id' => $newUserId,
                        'can_read' => 1,
                        'can_write' => 1,
                    ]);
                }

                foreach ($readOnly as $path) {
                    $cleanPath = $normalizePath((string) $path, $docsRoot);
                    $insertAcl->execute([
                        'path' => $cleanPath,
                        'user_id' => $newUserId,
                        'can_read' => 1,
                        'can_write' => 0,
                    ]);
                }

                foreach ($hidden as $path) {
                    $cleanPath = $normalizePath((string) $path, $docsRoot);
                    $insertAcl->execute([
                        'path' => $cleanPath,
                        'user_id' => $newUserId,
                        'can_read' => 0,
                        'can_write' => 0,
                    ]);
                }

                $success = 'Пользователь создан и права применены.';
                $users = $pdo->query('SELECT u.id, u.username, u.email, r.role_name FROM users u JOIN roles r ON r.id = u.role_id')->fetchAll();
            } catch (Throwable $e) {
                $error = 'Не удалось создать пользователя: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'update_role' && isset($_POST['user_id'], $_POST['role_id'])) {
        $user_id = (int) $_POST['user_id'];
        $role_id = (int) $_POST['role_id'];
        $query = $pdo->prepare('UPDATE users SET role_id = :role_id WHERE id = :user_id');
        $query->execute(['role_id' => $role_id, 'user_id' => $user_id]);
        $success = 'Роль пользователя обновлена.';
    }

    if ($action === 'save_acl' && isset($_POST['document_path'])) {
        $path = $normalizePath(trim($_POST['document_path']), $docsRoot);
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
<div class="max-w-7xl mx-auto px-4 py-12 relative">
    <div class="absolute inset-0 rounded-[32px] bg-gradient-to-b from-emerald-500/5 via-blue-500/5 to-transparent blur-3xl pointer-events-none"></div>

    <div class="relative flex items-center justify-between mb-10">
        <div class="space-y-3">
            <div class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-white/10 border border-white/10 text-xs uppercase tracking-[0.2em] text-emerald-200">Secure Control</div>
            <div class="flex items-center gap-3">
                <h1 class="text-4xl md:text-5xl font-black text-white drop-shadow">Администрирование</h1>
                <span class="px-3 py-1 rounded-xl bg-gradient-to-r from-emerald-400/20 to-blue-500/20 border border-white/10 text-sm text-emerald-100">Zero Trust</span>
            </div>
            <p class="text-slate-200/80 text-lg max-w-3xl">Управляйте ролями, правами и правилами доступа к документам через удобные hi-tech панели без потери контроля и прозрачности.</p>
        </div>
        <div class="hidden md:flex items-center gap-3 bg-white/5 border border-white/10 rounded-2xl px-6 py-4 shadow-lg shadow-emerald-500/15">
            <div>
                <p class="text-xs uppercase text-slate-300/70">Текущий администратор</p>
                <p class="text-lg font-semibold text-white"><?= htmlspecialchars($currentUser['username'] ?? 'Admin'); ?></p>
            </div>
            <div class="h-10 w-px bg-white/10"></div>
            <a href="/pages/dashboard.php" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-400 to-blue-500 text-slate-900 font-semibold shadow hover:shadow-lg transition">Назад</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="relative mb-4 rounded-2xl bg-emerald-500/10 border border-emerald-400/30 text-emerald-100 px-4 py-3 shadow-lg shadow-emerald-500/15">
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="relative mb-4 rounded-2xl bg-red-500/10 border border-red-400/30 text-red-100 px-4 py-3 shadow-lg shadow-red-500/15">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="relative grid md:grid-cols-3 gap-4 mb-8">
        <div class="p-4 rounded-2xl bg-white/5 border border-white/10 shadow-inner shadow-emerald-500/10">
            <p class="text-sm text-slate-300/80">Пользователи</p>
            <p class="text-3xl font-bold text-white"><?= count($users); ?></p>
            <p class="text-xs text-slate-400">Подключены к GitHub ACL</p>
        </div>
        <div class="p-4 rounded-2xl bg-white/5 border border-white/10 shadow-inner shadow-blue-500/10">
            <p class="text-sm text-slate-300/80">Ролей</p>
            <p class="text-3xl font-bold text-white"><?= count($roles); ?></p>
            <p class="text-xs text-slate-400">Модели доступа</p>
        </div>
        <div class="p-4 rounded-2xl bg-white/5 border border-white/10 shadow-inner shadow-indigo-500/10">
            <p class="text-sm text-slate-300/80">ACL правил</p>
            <p class="text-3xl font-bold text-white"><?= count($aclRows); ?></p>
            <p class="text-xs text-slate-400">Тонкая настройка прав</p>
        </div>
    </div>

    <div class="relative mb-6 p-6 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl shadow-xl shadow-emerald-500/10">
        <div class="flex flex-col lg:flex-row lg:items-start gap-4 mb-4">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center text-slate-900 font-black">+</span>
                <div>
                    <h2 class="text-xl font-semibold text-white">Создание пользователя</h2>
                    <p class="text-slate-300/80 text-sm">Пасскей вход, роль и предустановленные права на папки docs.</p>
                </div>
            </div>
            <div class="flex-1 lg:text-right text-slate-300/70 text-sm">Папки/подпапки можно выбрать сразу: где полный доступ, только чтение или скрыть.</div>
        </div>

        <form method="POST" class="grid lg:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="create_user">
            <div class="space-y-3 lg:col-span-2">
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Имя пользователя</label>
                        <input type="text" name="new_username" required class="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-400/60" placeholder="Иван Иваныч">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Email</label>
                        <input type="email" name="new_email" required class="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-400/60" placeholder="user@example.com">
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Пасс-ключ</label>
                        <input type="text" name="new_passkey" required class="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-400/60" placeholder="скан пальца / код">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Роль</label>
                        <select name="new_role_id" class="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400/60">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id']; ?>" class="bg-slate-900"><?= htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Полный доступ</label>
                        <select name="full_access_paths[]" multiple class="w-full min-h-[140px] p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-400/60">
                            <?php foreach ($docsFolders as $folder): ?>
                                <option value="<?= htmlspecialchars($folder); ?>" class="bg-slate-900"><?= htmlspecialchars($folder); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Чтение + запись.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Только чтение</label>
                        <select name="read_access_paths[]" multiple class="w-full min-h-[140px] p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-400/60">
                            <?php foreach ($docsFolders as $folder): ?>
                                <option value="<?= htmlspecialchars($folder); ?>" class="bg-slate-900"><?= htmlspecialchars($folder); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Видит, но не редактирует.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Скрыть</label>
                        <select name="hidden_paths[]" multiple class="w-full min-h-[140px] p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-pink-400/60">
                            <?php foreach ($docsFolders as $folder): ?>
                                <option value="<?= htmlspecialchars($folder); ?>" class="bg-slate-900"><?= htmlspecialchars($folder); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Не увидит в дереве и списках.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-3 justify-between">
                <div class="p-4 rounded-2xl border border-white/10 bg-white/5 text-sm text-slate-200 shadow-inner shadow-emerald-500/10">
                    <p class="font-semibold text-white mb-2">Где это хранится?</p>
                    <p class="text-slate-300/80">Права кладём в ACL MySQL, а документы — в GitHub внутри <?= htmlspecialchars($docsRoot); ?>.</p>
                </div>
                <button type="submit" class="w-full px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-400 via-cyan-400 to-blue-500 text-slate-900 font-semibold shadow-lg shadow-emerald-500/25 hover:shadow-xl transition">Создать аккаунт</button>
            </div>
        </form>
    </div>

    <div class="relative grid lg:grid-cols-2 gap-6">
        <div class="p-6 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl shadow-xl shadow-emerald-500/10">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-blue-500 flex items-center justify-center text-slate-900 font-black">R</span>
                <div>
                    <h2 class="text-xl font-semibold text-white">Роли пользователей</h2>
                    <p class="text-slate-300/80 text-sm">Назначайте роли без паролей и синхронизируйте доступы.</p>
                </div>
            </div>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="action" value="update_role">
                <div>
                    <label for="user_id" class="block text-sm font-semibold text-slate-200 mb-2">Выберите пользователя</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">👤</span>
                        <select name="user_id" id="user_id" class="w-full pl-10 p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-400/60">
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id']; ?>" class="bg-slate-900"><?= htmlspecialchars($user['username']); ?> — <?= htmlspecialchars($user['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="role_id" class="block text-sm font-semibold text-slate-200 mb-2">Назначить роль</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">🛡️</span>
                        <select name="role_id" id="role_id" class="w-full pl-10 p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-400/60">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id']; ?>" class="bg-slate-900"><?= htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-400 to-blue-500 text-slate-900 font-semibold shadow-lg shadow-emerald-500/25 hover:shadow-xl transition">Обновить роль</button>
            </form>

            <div class="mt-6 rounded-2xl border border-white/5 bg-white/5 p-4 text-sm text-slate-200">
                <p class="font-semibold text-white mb-2">Совет</p>
                <p class="text-slate-300/80">Храните роли в минимальном наборе и опирайтесь на ACL для точечной настройки доступа к документам.</p>
            </div>
        </div>

        <div class="p-6 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl shadow-xl shadow-indigo-500/10">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-indigo-400 to-violet-600 flex items-center justify-center text-slate-900 font-black">A</span>
                <div>
                    <h2 class="text-xl font-semibold text-white">ACL документов</h2>
                    <p class="text-slate-300/80 text-sm">Определяйте, кто читает и редактирует файлы репозитория.</p>
                </div>
            </div>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="action" value="save_acl">
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Путь документа</label>
                    <input type="text" name="document_path" list="docs-paths" required placeholder="docs/specs/security.md" class="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-400/60">
                    <datalist id="docs-paths">
                        <?php foreach ($docsFolders as $folder): ?>
                            <option value="<?= htmlspecialchars($folder); ?>/"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Пользователь</label>
                        <select name="acl_user_id" class="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-400/60">
                            <option value="" class="bg-slate-900">—</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id']; ?>" class="bg-slate-900"><?= htmlspecialchars($user['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Роль</label>
                        <select name="acl_role_id" class="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-400/60">
                            <option value="" class="bg-slate-900">—</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id']; ?>" class="bg-slate-900"><?= htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-sm text-slate-200">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="can_read" class="rounded border-white/20 bg-transparent text-emerald-400 focus:ring-emerald-400"> Чтение
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="can_write" class="rounded border-white/20 bg-transparent text-blue-400 focus:ring-blue-400"> Запись
                    </label>
                </div>
                <button type="submit" class="w-full px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-400 via-sky-500 to-violet-500 text-slate-900 font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-xl transition">Сохранить ACL</button>
            </form>

            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between text-sm text-slate-300/80">
                    <span>Текущие правила</span>
                    <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs text-white/80"><?= count($aclRows); ?></span>
                </div>
                <?php if ($aclRows): ?>
                    <div class="grid gap-3 max-h-80 overflow-y-auto pr-1">
                        <?php foreach ($aclRows as $row): ?>
                            <div class="p-3 rounded-2xl bg-white/5 border border-white/10">
                                <p class="font-semibold text-white flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                    <?= htmlspecialchars($row['document_path']); ?>
                                </p>
                                <p class="text-slate-300/80 text-xs mt-1">Пользователь: <?= $row['user_id'] ?: '—'; ?> • Роль: <?= $row['role_id'] ?: '—'; ?></p>
                                <div class="flex items-center gap-2 mt-2 text-xs">
                                    <span class="px-2 py-1 rounded-lg border border-white/10 bg-white/5 <?= $row['can_read'] ? 'text-emerald-300' : 'text-slate-400'; ?>">Чтение: <?= $row['can_read'] ? 'да' : 'нет'; ?></span>
                                    <span class="px-2 py-1 rounded-lg border border-white/10 bg-white/5 <?= $row['can_write'] ? 'text-blue-200' : 'text-slate-400'; ?>">Запись: <?= $row['can_write'] ? 'да' : 'нет'; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-slate-400 text-sm">Пока нет правил. Добавьте их выше.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
