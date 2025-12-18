<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/services/GithubClient.php';
require_once __DIR__ . '/services/AccessControl.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Требуется вход.';
    exit;
}

$path = trim($_GET['path'] ?? '');
if ($path === '') {
    http_response_code(400);
    echo 'Не указан путь файла.';
    exit;
}

$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$userStmt->execute(['id' => $_SESSION['user_id']]);
$user = $userStmt->fetch();

if (!$user) {
    http_response_code(403);
    echo 'Нет доступа.';
    exit;
}

$config = require __DIR__ . '/config/github.php';
$access = new AccessControl($pdo, $user);

if (!$access->canRead($path)) {
    http_response_code(403);
    echo 'Нет прав на скачивание этого файла.';
    exit;
}

$github = new GithubClient($config);

try {
    $content = $github->downloadDocument($path);
    $fileName = basename($path) ?: 'document';
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $mimeMap = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain; charset=utf-8',
        'md' => 'text/markdown; charset=utf-8',
    ];

    $mime = $mimeMap[$extension] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    echo $content;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Не удалось скачать файл: ' . $e->getMessage();
    exit;
}
