<?php
// Common site header with Tailwind CDN and basic meta tags.
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
<body class="bg-gray-50 text-gray-800" style="font-family: 'Inter', sans-serif;">
    <nav class="bg-gray-900 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="text-2xl font-bold tracking-tight">LC System</a>
            <div class="flex items-center gap-4">
                <a href="/pages/login.php" class="px-4 py-2 rounded-lg border border-white/20 hover:bg-white/10 transition">Войти</a>
                <a href="/pages/dashboard.php" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 transition">Личный кабинет</a>
            </div>
        </div>
    </nav>
