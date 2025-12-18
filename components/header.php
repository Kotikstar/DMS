<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LC System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen" style="font-family: 'Inter', sans-serif;">
    <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_10%_20%,rgba(59,130,246,0.08),transparent_25%),radial-gradient(circle_at_90%_10%,rgba(16,185,129,0.08),transparent_20%),radial-gradient(circle_at_50%_80%,rgba(167,139,250,0.08),transparent_25%)]"></div>
    <nav class="backdrop-blur-md bg-slate-900/70 text-white border-b border-white/10 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 text-2xl font-bold tracking-tight">
                <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-blue-600 flex items-center justify-center text-slate-900 font-black shadow-lg shadow-emerald-500/30">LC</span>
                <span class="hidden sm:block">System</span>
            </a>
            <div class="flex items-center gap-2 text-sm">
                <a href="/pages/dashboard.php" class="px-4 py-2 rounded-xl hover:bg-white/10 border border-transparent hover:border-white/10 transition">Документы</a>
                <a href="/pages/admin.php" class="px-4 py-2 rounded-xl hover:bg-white/10 border border-transparent hover:border-white/10 transition">Администрирование</a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <span class="hidden sm:inline px-3 py-2 rounded-xl bg-white/5 text-white/80 border border-white/10">
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Пользователь'); ?>
                    </span>
                    <a href="/logout.php" class="px-4 py-2 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 text-white shadow hover:shadow-lg transition">Выйти</a>
                <?php else: ?>
                    <a href="/pages/login.php" class="px-4 py-2 rounded-xl border border-white/20 hover:bg-white/10 transition">Войти</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
