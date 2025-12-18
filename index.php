<?php require_once __DIR__ . '/components/header.php'; ?>
<section class="relative overflow-hidden bg-white">
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-blue-50"></div>
    <div class="relative max-w-6xl mx-auto px-4 py-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <p class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold text-indigo-700 bg-indigo-100 rounded-full">Digital Management System</p>
                <h1 class="text-4xl lg:text-5xl font-bold leading-tight text-gray-900">
                    Добро пожаловать в <span class="text-indigo-600">LC System</span>
                </h1>
                <p class="text-lg text-gray-600 leading-relaxed">
                    Стартовая страница с быстрыми ссылками и красивым оформлением: заходите в личный кабинет, управляйте ролями и проверяйте активность без лишних кликов.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="/pages/login.php" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:bg-indigo-500 transition">Перейти ко входу</a>
                    <a href="/pages/admin.php" class="px-6 py-3 bg-white text-indigo-700 font-semibold rounded-lg border border-indigo-200 hover:border-indigo-400 hover:shadow-md transition">Админ-панель</a>
                </div>
                <div class="flex flex-wrap gap-6 text-sm text-gray-500">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        Поддержка пасс-ключей
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                        Ролевой доступ (RBAC)
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                        Современный UI
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-tr from-indigo-100 to-purple-100 blur-3xl"></div>
                <div class="relative bg-gray-900 text-white rounded-2xl shadow-2xl border border-white/5 p-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-lg font-semibold">Сводка безопасности</p>
                        <span class="px-3 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-200">Онлайн</span>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="rounded-xl bg-gray-800 p-4 border border-white/5">
                            <p class="text-sm text-gray-300">Активные сессии</p>
                            <p class="text-3xl font-bold">12</p>
                        </div>
                        <div class="rounded-xl bg-gray-800 p-4 border border-white/5">
                            <p class="text-sm text-gray-300">Ролей</p>
                            <p class="text-3xl font-bold">5</p>
                        </div>
                        <div class="rounded-xl bg-gray-800 p-4 border border-white/5">
                            <p class="text-sm text-gray-300">Запросов в час</p>
                            <p class="text-3xl font-bold">1.2k</p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-indigo-600 p-5 flex items-center justify-between shadow-lg shadow-indigo-400/40">
                        <div>
                            <p class="text-sm text-indigo-100">Быстрый старт</p>
                            <p class="text-lg font-semibold">Подключите команду за минуты</p>
                        </div>
                        <span class="text-2xl">⚡</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="max-w-6xl mx-auto px-4 pb-16">
    <div class="grid md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-indigo-600 text-3xl mb-4">🔐</div>
            <h3 class="text-xl font-semibold mb-2">Безопасность</h3>
            <p class="text-gray-600">Парольless-аутентификация и защита сессий по умолчанию.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-emerald-600 text-3xl mb-4">⚙️</div>
            <h3 class="text-xl font-semibold mb-2">Управление</h3>
            <p class="text-gray-600">Администрирование ролей и пользователей в пару кликов.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-amber-600 text-3xl mb-4">📈</div>
            <h3 class="text-xl font-semibold mb-2">Прозрачность</h3>
            <p class="text-gray-600">Сводка активности и ключевых показателей прямо на экране.</p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/components/footer.php'; ?>
