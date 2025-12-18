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
$docsTree = [];
$flatDocuments = [];
$selectedPath = $_GET['path'] ?? '';
$selectedDocument = null;
$history = [];
$errors = [];
$success = null;

$normalizePath = static function (string $path, string $base): string {
    $cleanBase = trim($base, '/');
    $cleanPath = trim($path, '/');

    if ($cleanPath === '') {
        return $cleanBase;
    }

    if ($cleanBase && stripos($cleanPath, $cleanBase . '/') !== 0 && $cleanPath !== $cleanBase) {
        $cleanPath = $cleanBase . '/' . $cleanPath;
    }

    return $cleanPath;
};

$extractDocxText = static function (string $binary): ?string {
    $tmp = tempnam(sys_get_temp_dir(), 'docx');
    if ($tmp === false) {
        return null;
    }

    file_put_contents($tmp, $binary);
    $zip = new ZipArchive();
    $text = null;

    if ($zip->open($tmp) === true) {
        $xml = $zip->getFromName('word/document.xml');
        if ($xml !== false) {
            $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
            $xml = str_replace(['</w:p>', '</w:tab>'], "\n", $xml);
            $text = trim(strip_tags($xml));
        }
        $zip->close();
    }

    @unlink($tmp);
    return $text;
};

$buildDocxFromText = static function (string $text): string {
    $tmp = tempnam(sys_get_temp_dir(), 'docx-build');
    if ($tmp === false) {
        throw new RuntimeException('Не удалось подготовить файл DOCX');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Не удалось создать DOCX архив');
    }

    $paragraphs = array_map('trim', preg_split('/\r?\n/', $text));
    $body = '';
    foreach ($paragraphs as $p) {
        $escaped = htmlspecialchars($p ?: ' ', ENT_XML1);
        $body .= '<w:p><w:r><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
    }

    $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"'
        . ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"'
        . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
        . ' xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"'
        . ' xmlns:v="urn:schemas-microsoft-com:vml"'
        . ' xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"'
        . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
        . ' xmlns:w10="urn:schemas-microsoft-com:office:word"'
        . ' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
        . ' xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"'
        . ' xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"'
        . ' xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"'
        . ' xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"'
        . ' xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"'
        . ' mc:Ignorable="w14 wp14">'
        . '<w:body>' . $body . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
        . '</w:body></w:document>';

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '</Relationships>');

    $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');

    $zip->addFromString('word/document.xml', $documentXml);
    $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
        . '<Application>LC System</Application></Properties>');

    $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>Документ LC System</dc:title></cp:coreProperties>');

    $zip->close();
    $binary = file_get_contents($tmp) ?: '';
    @unlink($tmp);

    return $binary;
};

$collectFiles = static function (array $nodes) use (&$collectFiles): array {
    $files = [];

    foreach ($nodes as $node) {
        if (($node['type'] ?? '') === 'file') {
            $files[] = $node;
        }

        if (($node['type'] ?? '') === 'dir') {
            $files = array_merge($files, $collectFiles($node['children'] ?? []));
        }
    }

    return $files;
};

try {
    $docsTree = $github->getDocsTree($docsPath);
    $flatDocuments = $collectFiles($docsTree);
} catch (Throwable $e) {
    $errors[] = 'Не удалось загрузить список документов: ' . $e->getMessage();
}

if (!$selectedPath && !empty($flatDocuments[0]['path'])) {
    $selectedPath = $flatDocuments[0]['path'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        if ($action === 'create') {
            $folder = trim($_POST['new_folder'] ?? $docsPath, '/');
            $filename = trim($_POST['new_filename'] ?? '');
            $path = $normalizePath(($folder ? $folder . '/' : '') . $filename, $docsPath);
        } else {
            $path = $normalizePath(trim($_POST['path'] ?? ''), $docsPath);
        }

        if (empty($path)) {
            $errors[] = 'Укажите имя документа.';
        }

        if (!$access->canWrite($path ?: $docsPath)) {
            $errors[] = 'У вас нет прав на запись для этого документа.';
        } else {
            $rawContent = (string)($_POST['content'] ?? '');
            $message = trim($_POST['message'] ?? 'Обновление документа');
            $sha = $action === 'update' ? ($_POST['sha'] ?? null) : null;

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $content = $rawContent;

            if (in_array($extension, ['doc', 'docx'], true)) {
                try {
                    $content = $buildDocxFromText($rawContent);
                } catch (Throwable $e) {
                    $errors[] = 'DOCX-конвертер: ' . $e->getMessage();
                }
            }

            if (!$errors) {
                try {
                    $github->saveDocument($path, $content, $message, $sha ?: null);
                    $success = 'Изменения отправлены в GitHub.';
                    $selectedPath = $path;
                    $selectedDocument = $github->getDocument($path);
                    $history = $github->getHistory($path, 5);
                    $docsTree = $github->getDocsTree($docsPath);
                    $flatDocuments = $collectFiles($docsTree);
                } catch (Throwable $e) {
                    $errors[] = 'Не удалось сохранить документ: ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'upload') {
        $targetFolder = trim($_POST['upload_folder'] ?? $docsPath);
        if ($targetFolder === '' || $targetFolder === '.') {
            $targetFolder = $docsPath;
        }
        $targetFolder = $normalizePath($targetFolder, $docsPath);
        $message = trim($_POST['upload_message'] ?? 'Загрузка документа');
        $file = $_FILES['document_file'] ?? null;

        if (!$access->canWrite($targetFolder ?: $docsPath)) {
            $errors[] = 'У вас нет прав на загрузку в этот путь.';
        } elseif (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'Файл не был загружен или произошла ошибка при загрузке.';
        } else {
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name'] ?? 'document');
            $uploadPath = $normalizePath(($targetFolder ? $targetFolder . '/' : '') . $safeName, $docsPath);
            $extension = strtolower(pathinfo($uploadPath, PATHINFO_EXTENSION));
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
                    $docsTree = $github->getDocsTree($docsPath);
                    $flatDocuments = $collectFiles($docsTree);
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
$isPdf = (bool) preg_match('/\.pdf$/i', $selectedPath);
$docxText = ($isWordDocument && $selectedDocument) ? $extractDocxText($selectedDocument['content']) : null;
$selectedSize = $selectedDocument ? strlen($selectedDocument['content']) : 0;
$downloadUrl = $selectedPath ? '/download.php?path=' . urlencode($selectedPath) : '';
$renderNode = static function (array $node, int $depth = 0) use (&$renderNode, $selectedPath) {
    $indent = max(0, $depth * 12);
    $isDir = ($node['type'] ?? '') === 'dir';
    $isActive = !$isDir && ($node['path'] ?? '') === $selectedPath;
    ?>
    <li class="pl-<?= $indent; ?>">
        <div class="flex items-center justify-between gap-3 rounded-2xl px-3 py-2 <?= $isDir ? 'bg-white/5 border border-white/5' : 'hover:bg-white/5 border border-transparent'; ?> <?= $isActive ? 'border-emerald-400/40 bg-emerald-500/10' : ''; ?>">
            <div class="flex items-center gap-3 min-w-0">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 border border-white/10 text-[11px] uppercase text-emerald-100">
                    <?= htmlspecialchars($isDir ? 'dir' : (pathinfo($node['name'] ?? '', PATHINFO_EXTENSION) ?: 'file')); ?>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($node['name'] ?? ''); ?></p>
                    <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($node['path'] ?? ''); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <?php if ($isDir): ?>
                    <span class="text-slate-400">Папка</span>
                <?php else: ?>
                    <a class="text-emerald-200 hover:text-emerald-100" href="?path=<?= urlencode($node['path'] ?? ''); ?>">Открыть</a>
                    <a class="text-slate-400 hover:text-white/80" href="/download.php?path=<?= urlencode($node['path'] ?? ''); ?>" title="Скачать">⇩</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($isDir && !empty($node['children'])): ?>
            <ul class="mt-2 space-y-2">
                <?php foreach ($node['children'] as $child): ?>
                    <?php $renderNode($child, $depth + 1); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
};
?>
<?php require_once __DIR__ . '/../components/header.php'; ?>
<div class="max-w-7xl mx-auto px-4 py-12 relative">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-emerald-500/10 via-blue-600/5 to-purple-500/10 blur-3xl"></div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <p class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs tracking-wide bg-emerald-500/10 text-emerald-200 border border-emerald-500/20">Secure • GitHub • Passkey</p>
            <h1 class="text-4xl font-bold text-white mt-3">Кабинет документов</h1>
            <p class="text-slate-300">Предпросмотр, скачивание приватных файлов, загрузка Word/PDF и ACL-защита.</p>
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
        <div class="lg:col-span-1">
            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900/80 via-slate-900/70 to-emerald-900/40 p-5 shadow-2xl shadow-emerald-900/30">
                <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_20%_20%,rgba(16,185,129,0.12),transparent_45%),radial-gradient(circle_at_80%_0%,rgba(59,130,246,0.12),transparent_40%)]"></div>
                <div class="flex items-center justify-between mb-5 relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-200/80">Docs index</p>
                        <h2 class="text-2xl font-semibold text-white">Документы</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-lg bg-white/5 border border-white/10 text-xs text-slate-200">Папка: <?= htmlspecialchars($docsPath); ?></span>
                        <a class="text-sm text-emerald-200 hover:text-emerald-100 px-3 py-2 rounded-xl bg-white/5 border border-white/10" href="?">Обновить</a>
                    </div>
                </div>

                <div class="relative z-10">
                    <?php if (empty($docsTree)): ?>
                        <p class="text-slate-400 text-sm">Нет документов или не настроен GitHub токен.</p>
                    <?php else: ?>
                        <ul class="space-y-2">
                            <?php foreach ($docsTree as $node): ?>
                                <?php $renderNode($node); ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="mt-4 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-50 text-sm flex items-start gap-3">
                        <span class="mt-0.5">✔</span>
                        <div>
                            <p class="font-semibold">Версионирование и ACL</p>
                            <p class="text-emerald-100/80">Все изменения фиксируются коммитами в GitHub, права доступа контролируются ACL. Индексация охватывает всю папку docs и вложенные каталоги.</p>
                        </div>
                    </div>
                </div>
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
                    <?php elseif ($isWordDocument && $docxText !== null): ?>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="path" value="<?= htmlspecialchars($selectedDocument['path']); ?>">
                            <input type="hidden" name="sha" value="<?= htmlspecialchars($selectedDocument['sha']); ?>">

                            <div class="rounded-2xl bg-blue-500/10 border border-blue-400/20 p-3 text-blue-100 text-sm">Быстрый текстовый режим DOCX. При сохранении создаётся новый документ Word без форматирования.</div>

                            <label class="block text-sm font-semibold text-slate-100">Текст документа</label>
                            <textarea name="content" rows="12" class="w-full p-4 bg-slate-900/40 text-slate-100 border border-white/10 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500" <?= $access->canWrite($selectedPath) ? '' : 'readonly'; ?>><?= htmlspecialchars($docxText); ?></textarea>

                            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                                <input type="text" name="message" class="flex-1 p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Комментарий к коммиту" value="Обновление <?= htmlspecialchars($selectedDocument['name']); ?>">
                                <button type="submit" class="px-6 py-3 bg-emerald-500 text-slate-900 font-semibold rounded-xl shadow-lg shadow-emerald-500/30 hover:bg-emerald-400 transition" <?= $access->canWrite($selectedPath) ? '' : 'disabled'; ?>>Сохранить DOCX</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5 text-slate-200 space-y-4">
                            <div class="flex flex-wrap gap-3 items-center">
                                <a class="px-4 py-2 rounded-xl bg-white/10 border border-white/20 hover:border-emerald-400/40 text-sm" href="<?= htmlspecialchars($downloadUrl); ?>">Скачать из GitHub</a>
                                <?php if ($selectedSize): ?>
                                    <span class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-xs text-slate-300">Размер: <?= number_format($selectedSize / 1024, 2); ?> КБ</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($isPdf): ?>
                                <div class="border border-white/10 rounded-xl overflow-hidden bg-black/40">
                                    <object data="<?= htmlspecialchars($downloadUrl); ?>" type="application/pdf" width="100%" height="480px">
                                        <p class="p-4 text-sm text-slate-300">PDF не удалось встроить, скачайте файл.</p>
                                    </object>
                                </div>
                            <?php elseif ($isWordDocument): ?>
                                <p class="text-sm text-slate-300">Предпросмотр Word недоступен. Скачайте файл или используйте текстовый режим выше.</p>
                            <?php else: ?>
                                <p class="text-sm text-slate-300">Файл не отображается в редакторе. Скачайте документ или загрузите новую версию.</p>
                            <?php endif; ?>
                            <?php if ($access->canWrite($selectedPath)): ?>
                                <form method="POST" enctype="multipart/form-data" class="mt-2 space-y-3">
                                    <input type="hidden" name="action" value="upload">
                                    <input type="hidden" name="upload_folder" value="<?= htmlspecialchars(dirname($selectedPath) === '.' ? $docsPath : dirname($selectedPath)); ?>">
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
        <div class="rounded-3xl bg-gradient-to-br from-slate-900/70 via-slate-900/60 to-blue-900/40 border border-white/10 backdrop-blur-xl p-6 shadow-xl shadow-emerald-900/30">
            <h3 class="text-xl font-semibold text-white mb-2">Создать новый документ</h3>
            <p class="text-sm text-slate-300 mb-4">Без ручного выбора пути: файл автоматически попадёт в папку docs.</p>
            <form method="POST" class="grid md:grid-cols-4 gap-4 items-center">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="new_folder" value="<?= htmlspecialchars($docsPath); ?>">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Имя файла</label>
                    <input type="text" name="new_filename" required placeholder="new-file.md" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Комментарий к коммиту</label>
                    <input type="text" name="message" value="Создание документа" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Содержимое</label>
                    <textarea name="content" rows="6" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="# Новый документ\nОписание ..."></textarea>
                </div>
                <div class="md:col-span-4 flex items-center justify-between text-xs text-emerald-100/80 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl px-4 py-3">
                    <span>Путь: <?= htmlspecialchars($docsPath); ?>/… • Коммиты формируют версии автоматически.</span>
                    <button type="submit" class="px-6 py-3 bg-emerald-500 text-slate-900 font-semibold rounded-xl hover:bg-emerald-400 transition">Создать и закоммитить</button>
                </div>
            </form>
        </div>

        <div class="rounded-3xl bg-gradient-to-br from-slate-900/70 via-slate-900/60 to-emerald-900/40 border border-white/10 backdrop-blur-xl p-6 shadow-xl shadow-emerald-900/30">
            <h3 class="text-xl font-semibold text-white mb-2">Загрузить файл (Word/PDF/TXT)</h3>
            <p class="text-sm text-slate-300 mb-4">Папка определяется автоматически. Поддержка DOC/DOCX, PDF, TXT и Markdown.</p>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="upload_folder" value="<?= htmlspecialchars($docsPath); ?>">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Комментарий к коммиту</label>
                        <input type="text" name="upload_message" value="Загрузка документа" class="w-full p-3 bg-slate-900/50 text-slate-100 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="flex items-end">
                        <div class="w-full text-right text-xs text-emerald-100 bg-white/5 border border-white/10 rounded-xl px-3 py-2">Целевая папка: <?= htmlspecialchars($docsPath); ?></div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Файл</label>
                    <input type="file" name="document_file" accept=".doc,.docx,.pdf,.txt,.md" class="w-full text-sm text-slate-200 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-500 file:text-slate-900 file:font-semibold">
                    <p class="text-xs text-slate-400 mt-2">Файл кладётся в docs автоматически, путь выбирать не нужно.</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-emerald-500 text-slate-900 font-semibold rounded-xl hover:bg-emerald-400 transition">Загрузить в GitHub</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
