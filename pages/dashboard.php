<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/GithubClient.php';
require_once __DIR__ . '/../services/AccessControl.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}

$userStmt = $pdo->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id');
$userStmt->execute(['id' => $_SESSION['user_id']]);
$user = $userStmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /pages/login.php');
    exit;
}

$config = require __DIR__ . '/../config/github.php';
$github = new GithubClient($config);
$access = new AccessControl($pdo, $user);

$docsPath = $config['docs_path'];
$documents = [];
$selectedPath = $_GET['path'] ?? '';
$selectedDocument = null;
$history = [];
$errors = [];
$success = null;

try {
    $documents = $github->listDocuments($docsPath);
} catch (Throwable $e) {
    $errors[] = 'Не удалось загрузить список документов: ' . $e->getMessage();
}

if (!$selectedPath && !empty($documents[0]['path'])) {
    $selectedPath = $documents[0]['path'];
}

if ($selectedPath) {
    try {
        $selectedDocument = $github->getDocument($selectedPath);
        $history = $github->getHistory($selectedPath, 5);
    } catch (Throwable $e) {
        $errors[] = 'Не удалось загрузить документ: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $path = trim($_POST['path'] ?? '');

    if ($action === 'create' || $action === 'update') {
        if (!$access->canWrite($path ?: $docsPath)) {
            $errors[] = 'У вас нет прав на запись для этого документа.';
        } else {
            $content = (string)($_POST['content'] ?? '');
            $message = trim($_POST['message'] ?? 'Обновление документа');
            $sha = $action === 'update' ? ($_POST['sha'] ?? null) : null;

            try {
                $github->saveDocument($path, $content, $message, $sha ?: null);
                $success = 'Изменения отправлены в GitHub.';
                $selectedPath = $path;
                $selectedDocument = $github->getDocument($path);
                $history = $github->getHistory($path, 5);
            } catch (Throwable $e) {
                $errors[] = 'Не удалось сохранить документ: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php require_once __DIR__ . '/../components/header.php'; ?>
<div class="max-w-6xl mx-auto px-4 py-12">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Личный кабинет</h1>
            <p class="text-gray-600">Документы из GitHub, управление версиями и правами.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm">Роль: <?= htmlspecialchars($user['role_name']); ?></span>
            <span class="px-3 py-2 rounded-lg bg-green-50 text-green-700 text-sm">Пасс-ключ активен</span>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Документы</h2>
                <a class="text-sm text-blue-600 hover:text-blue-500" href="?">Обновить</a>
            </div>
            <?php if (empty($documents)): ?>
                <p class="text-gray-500 text-sm">Нет документов или не настроен GitHub токен.</p>
            <?php else: ?>
                <ul class="divide-y divide-gray-100">
                    <?php foreach ($documents as $doc): ?>
                        <li class="py-2 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($doc['name']); ?></p>
                                <p class="text-xs text-gray-500 truncate max-w-[200px]"><?= htmlspecialchars($doc['path']); ?></p>
                            </div>
                            <a class="text-blue-600 text-sm hover:text-blue-500" href="?path=<?= urlencode($doc['path']); ?>">Открыть</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="mt-4 p-3 rounded-lg bg-blue-50 text-blue-800 text-sm">
                Все версии фиксируются коммитами в GitHub.
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Текущий документ</p>
                    <h3 class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($selectedDocument['name'] ?? 'Не выбран'); ?></h3>
                </div>
                <?php if ($selectedPath): ?>
                    <span class="px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-sm">Путь: <?= htmlspecialchars($selectedPath); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($selectedDocument && $access->canRead($selectedPath)): ?>
                <div class="space-y-4">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="path" value="<?= htmlspecialchars($selectedDocument['path']); ?>">
                        <input type="hidden" name="sha" value="<?= htmlspecialchars($selectedDocument['sha']); ?>">

                        <label class="block text-sm font-semibold text-gray-700">Содержимое</label>
                        <textarea name="content" rows="12" class="w-full p-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" <?= $access->canWrite($selectedPath) ? '' : 'readonly'; ?>><?= htmlspecialchars($selectedDocument['content']); ?></textarea>

                        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                            <input type="text" name="message" class="flex-1 p-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Комментарий к коммиту" value="Обновление <?= htmlspecialchars($selectedDocument['name']); ?>">
                            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-500 transition" <?= $access->canWrite($selectedPath) ? '' : 'disabled'; ?>>Сохранить в GitHub</button>
                        </div>
                    </form>

                    <div class="border-t border-gray-100 pt-4">
                        <h4 class="text-lg font-semibold mb-2">История версий</h4>
                        <?php if ($history): ?>
                            <ul class="space-y-2 text-sm text-gray-700">
                                <?php foreach ($history as $commit): ?>
                                    <li class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                        <p class="font-semibold"><?= htmlspecialchars($commit['commit']['message'] ?? ''); ?></p>
                                        <p class="text-gray-500">Автор: <?= htmlspecialchars($commit['commit']['author']['name'] ?? ''); ?> • <?= htmlspecialchars(substr($commit['commit']['author']['date'] ?? '', 0, 10)); ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">История не найдена.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($selectedPath): ?>
                <div class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                    Нет доступа на чтение этого файла. Попросите администратора настроить ACL.
                </div>
            <?php else: ?>
                <div class="text-gray-500">Выберите документ слева или создайте новый.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-10 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Создать новый документ</h3>
        <form method="POST" class="grid md:grid-cols-4 gap-4 items-center">
            <input type="hidden" name="action" value="create">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Путь в репозитории</label>
                <input type="text" name="path" required placeholder="docs/new-file.md" class="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Комментарий к коммиту</label>
                <input type="text" name="message" value="Создание документа" class="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:col-span-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Содержимое</label>
                <textarea name="content" rows="6" class="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="# Новый документ\nОписание ..."></textarea>
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-500 transition">Создать и закоммитить</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
