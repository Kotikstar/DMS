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

$normalizePath = static function (string $path, string $base): string {
    $cleanBase = trim($base, '/');
    $cleanPath = ltrim($path, '/');

    if ($cleanBase && $cleanPath && stripos($cleanPath, $cleanBase . '/') !== 0 && $cleanPath !== $cleanBase) {
        $cleanPath = $cleanBase . '/' . $cleanPath;
    }

    return $cleanPath ?: $cleanBase;
};

try {
    $documents = $github->listDocuments($docsPath);
} catch (Throwable $e) {
    $errors[] = 'Не удалось загрузить список документов: ' . $e->getMessage();
}

if (!$selectedPath && !empty($documents[0]['path'])) {
    $selectedPath = $documents[0]['path'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $path = trim($_POST['path'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $path = $normalizePath($path, $docsPath);

        if (empty($path)) {
            $errors[] = 'Укажите путь для документа.';
        }

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
                $documents = $github->listDocuments($docsPath);
            } catch (Throwable $e) {
                $errors[] = 'Не удалось сохранить документ: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'upload') {
        $uploadPath = $normalizePath(trim($_POST['upload_path'] ?? ''), $docsPath);
        $message = trim($_POST['upload_message'] ?? 'Загрузка документа');
        $file = $_FILES['document_file'] ?? null;

        if (!$access->canWrite($uploadPath ?: $docsPath)) {
            $errors[] = 'У вас нет прав на загрузку в этот путь.';
        } elseif (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'Файл не был загружен или произошла ошибка при загрузке.';
        } elseif (empty($uploadPath)) {
            $errors[] = 'Введите путь, куда сохранить файл.';
        } else {
            $extension = strtolower(pathinfo($uploadPath ?: ($file['name'] ?? ''), PATHINFO_EXTENSION));
            $allowed = ['doc', 'docx', 'pdf', 'txt', 'md'];

            if (!in_array($extension, $allowed, true)) {
                $errors[] = 'Разрешены только файлы DOCX/DOC, PDF, TXT или MD.';
            } else {
                $content = file_get_contents($file['tmp_name']);
                $sha = null;

                try {
                    $existing = $github->getDocument($uploadPath);
                    $sha = $existing['sha'] ?? null;
                } catch (Throwable $ignored) {
                }

                try {
                    $github->saveDocument($uploadPath, $content, $message ?: 'Загрузка документа', $sha ?: null);
                    $success = 'Документ загружен и сохранен в GitHub.';
                    $selectedPath = $uploadPath;
                    $selectedDocument = $github->getDocument($uploadPath);
                    $history = $github->getHistory($uploadPath, 5);
                    $documents = $github->listDocuments($docsPath);
                } catch (Throwable $e) {
                    $errors[] = 'Не удалось загрузить документ: ' . $e->getMessage();
                }
            }
        }
    }
}

if (!$selectedDocument && $selectedPath) {
    try {
        $selectedDocument = $github->getDocument($selectedPath);
        $history = $github->getHistory($selectedPath, 5);
    } catch (Throwable $e) {
        $errors[] = 'Не удалось загрузить документ: ' . $e->getMessage();
    }
}

$selectedExtension = strtolower(pathinfo($selectedPath, PATHINFO_EXTENSION));
$isTextual = (bool) preg_match('/\.(md|txt|json|yaml|yml|csv|xml|html)$/i', $selectedPath);
$isWordDocument = (bool) preg_match('/\.(docx?|dotx?)$/i', $selectedPath);
$selectedSize = $selectedDocument ? strlen($selectedDocument['content']) : 0;
?>
<?php require_once __DIR__ . '/../components/header.php'; ?>
<div class="max-w-7xl mx-auto px-4 py-12">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <p class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs tracking-wide bg-emerald-500/10 text-emerald-200 border border-emerald-500/20">Secure • GitHub • Passkey</p>
            <h1 class="text-4xl font-bold text-white mt-3">Кабинет документов</h1>
            <p class="text-slate-300">Версии из GitHub, загрузка Word/PDF и контроль доступа через ACL.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-4 py-2 rounded-xl bg-white/5 text-slate-100 text-sm border border-white/10">Роль: <?= htmlspecialchars($user['role_name']); ?></span>
            <span class="px-4 py-2 rounded-xl bg-emerald-500/20 text-emerald-100 text-sm border border-emerald-400/30">Passkey</span>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-100 px-4 py-3">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-100 px-4 py-3">
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-white">Документы</h2>
                <a class="text-sm text-emerald-200 hover:text-emerald-100" href="?">Обновить</a>
            </div>
            <?php if (empty($documents)): ?>
                <p class="text-slate-400 text-sm">Нет документов или не настроен GitHub токен.</p>
            <?php else: ?>
                <ul class="divide-y divide-white/10">
                    <?php foreach ($documents as $doc): ?>
                        <?php $ext = strtolower(pathinfo($doc['name'] ?? '', PATHINFO_EXTENSION)); ?>
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-white flex items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/5 border border-white/10 text-xs uppercase"><?= htmlspecialchars($ext ?: 'file'); ?></span>
                                    <?= htmlspecialchars($doc['name']); ?>
                                </p>
                                <p class="text-xs text-slate-400 truncate max-w-[220px]"><?= htmlspecialchars($doc['path']); ?></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <a class="text-emerald-200 hover:text-emerald-100 text-sm" href="?path=<?= urlencode($doc['path']); ?>">Открыть</a>
                                <a class="text-slate-400 hover:text-white/80 text-sm" target="_blank" rel="noreferrer" href="<?= htmlspecialchars($github->getRawUrl($doc['path'])); ?>">⇩</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="mt-4 p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-100 text-sm">
                Все версии фиксируются коммитами в GitHub.
            </div>
        </div>

        <div class="lg:col-span-2 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl p-6 space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-slate-400">Текущий документ</p>
                    <h3 class="text-2xl font-semibold text-white"><?= htmlspecialchars($selectedDocument['name'] ?? 'Не выбран'); ?></h3>
                </div>
                <?php if ($selectedPath): ?>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 rounded-lg bg-white/5 text-slate-200 text-sm border border-white/10">Путь: <?= htmlspecialchars($selectedPath); ?></span>
                        <span class="px-3 py-1 rounded-lg bg-white/5 text-slate-200 text-sm border border-white/10">Тип: <?= $isWordDocument ? 'Word' : strtoupper($selectedExtension ?: 'txt'); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($selectedDocument && $access->canRead($selectedPath)): ?>
                <div class="space-y-4">
                    <?php if ($isTextual): ?>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="path" value="<?= htmlspecialchars($selectedDocument['path']); ?>">
                            <input type="hidden" name="sha" value="<?= htmlspecialchars($selectedDocument['sha']); ?>">

                            <label class="block text-sm font-semibold text-slate-100">Содержимое</label>
                            <textarea name="content" rows="12" class="w-full p-4 bg-slate-900/40 text-slate-100 border border-white/10 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500" <?= $access->canWrite($selectedPath) ? '' : 'readonly'; ?>><?= htmlspecialchars($selectedDocument['content']); ?></textarea>

                            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                                <input type="text" name="message" class="flex-1 p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Комментарий к коммиту" value="Обновление <?= htmlspecialchars($selectedDocument['name']); ?>">
                                <button type="submit" class="px-6 py-3 bg-emerald-500 text-slate-900 font-semibold rounded-xl shadow-lg shadow-emerald-500/30 hover:bg-emerald-400 transition" <?= $access->canWrite($selectedPath) ? '' : 'disabled'; ?>>Сохранить в GitHub</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5 text-slate-200 space-y-3">
                            <p class="text-lg font-semibold flex items-center gap-2"><span class="text-xl">📄</span> Просмотр бинарных файлов</p>
                            <p class="text-sm text-slate-400">Файл не отображается в редакторе. Скачайте документ или загрузите новую версию (DOCX/DOC/PDF).</p>
                            <div class="flex flex-wrap gap-3">
                                <a class="px-4 py-2 rounded-xl bg-white/10 border border-white/20 hover:border-emerald-400/40 text-sm" target="_blank" rel="noreferrer" href="<?= htmlspecialchars($github->getRawUrl($selectedPath)); ?>">Скачать из GitHub</a>
                                <?php if ($selectedSize): ?>
                                    <span class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-xs text-slate-300">Размер: <?= number_format($selectedSize / 1024, 2); ?> КБ</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($access->canWrite($selectedPath)): ?>
                                <form method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                                    <input type="hidden" name="action" value="upload">
                                    <input type="hidden" name="upload_path" value="<?= htmlspecialchars($selectedPath); ?>">
                                    <div>
                                        <label class="block text-sm text-slate-300 mb-2">Заменить файл</label>
                                        <input type="file" name="document_file" accept=".doc,.docx,.pdf,.txt,.md" class="w-full text-sm text-slate-200 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-500 file:text-slate-900 file:font-semibold">
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                                        <input type="text" name="upload_message" class="flex-1 p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Комментарий к коммиту" value="Обновление <?= htmlspecialchars($selectedDocument['name'] ?? 'документа'); ?>">
                                        <button type="submit" class="px-6 py-3 bg-emerald-500 text-slate-900 font-semibold rounded-xl shadow-lg shadow-emerald-500/30 hover:bg-emerald-400 transition">Загрузить новую версию</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="border-t border-white/10 pt-4">
                        <h4 class="text-lg font-semibold text-white mb-2">История версий</h4>
                        <?php if ($history): ?>
                            <ul class="space-y-2 text-sm text-slate-200">
                                <?php foreach ($history as $commit): ?>
                                    <li class="p-3 rounded-2xl bg-white/5 border border-white/10">
                                        <p class="font-semibold text-white"><?= htmlspecialchars($commit['commit']['message'] ?? ''); ?></p>
                                        <p class="text-slate-400">Автор: <?= htmlspecialchars($commit['commit']['author']['name'] ?? ''); ?> • <?= htmlspecialchars(substr($commit['commit']['author']['date'] ?? '', 0, 10)); ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-slate-400 text-sm">История не найдена.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($selectedPath): ?>
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-100 text-sm">
                    Нет доступа на чтение этого файла. Попросите администратора настроить ACL.
                </div>
            <?php else: ?>
                <div class="text-slate-400">Выберите документ слева или создайте новый.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-10 grid lg:grid-cols-2 gap-6">
        <div class="rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl p-6">
            <h3 class="text-xl font-semibold text-white mb-4">Создать новый документ</h3>
            <form method="POST" class="grid md:grid-cols-4 gap-4 items-center">
                <input type="hidden" name="action" value="create">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Путь в репозитории</label>
                    <input type="text" name="path" required placeholder="docs/new-file.md" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Комментарий к коммиту</label>
                    <input type="text" name="message" value="Создание документа" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Содержимое</label>
                    <textarea name="content" rows="6" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="# Новый документ\nОписание ..."></textarea>
                </div>
                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-emerald-500 text-slate-900 font-semibold rounded-xl hover:bg-emerald-400 transition">Создать и закоммитить</button>
                </div>
            </form>
        </div>

        <div class="rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl p-6">
            <h3 class="text-xl font-semibold text-white mb-4">Загрузить файл (Word/PDF)</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Путь сохранения</label>
                        <input type="text" name="upload_path" required placeholder="docs/contracts/contract.docx" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Комментарий к коммиту</label>
                        <input type="text" name="upload_message" value="Загрузка документа" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Файл</label>
                    <input type="file" name="document_file" accept=".doc,.docx,.pdf,.txt,.md" class="w-full text-sm text-slate-200 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-500 file:text-slate-900 file:font-semibold">
                    <p class="text-xs text-slate-400 mt-2">Файл будет сохранен в GitHub с коммитом и доступом по ACL.</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-emerald-500 text-slate-900 font-semibold rounded-xl hover:bg-emerald-400 transition">Загрузить в GitHub</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
