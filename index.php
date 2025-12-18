<?php require_once __DIR__ . '/components/header.php'; ?>
<section class="relative overflow-hidden bg-white">
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-blue-50"></div>
    <div class="relative max-w-6xl mx-auto px-4 py-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <p class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold text-indigo-700 bg-indigo-100 rounded-full">Digital Management System</p>
                <h1 class="text-4xl lg:text-5xl font-bold leading-tight text-gray-900">
                    Документы под контролем, коммиты в GitHub
                </h1>
                <p class="text-lg text-gray-600 leading-relaxed">
                    LC System связывает ваши документы с GitHub, сохраняет версии через коммиты и управляет правами через MySQL-роли и ACL.
                    Авторизация по пасс-ключам гарантирует вход без паролей, а интерфейс на Tailwind — адаптивный и быстрый.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="/pages/login.php" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:bg-indigo-500 transition">Перейти ко входу</a>
                    <a href="/pages/dashboard.php" class="px-6 py-3 bg-white text-indigo-700 font-semibold rounded-lg border border-indigo-200 hover:border-indigo-400 hover:shadow-md transition">Открыть документы</a>
                </div>
                <div class="flex flex-wrap gap-6 text-sm text-gray-500">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        Пасс-ключи (FIDO2/Windows Hello)
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                        RBAC + ACL на документы
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                        GitHub API & версии
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-tr from-indigo-100 to-purple-100 blur-3xl"></div>
                <div class="relative bg-gray-900 text-white rounded-2xl shadow-2xl border border-white/5 p-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-lg font-semibold">Сводка репозитория</p>
                        <span class="px-3 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-200">GitHub Live</span>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="rounded-xl bg-gray-800 p-4 border border-white/5">
                            <p class="text-sm text-gray-300">Документы</p>
                            <p class="text-3xl font-bold">∞</p>
                        </div>
                        <div class="rounded-xl bg-gray-800 p-4 border border-white/5">
                            <p class="text-sm text-gray-300">Коммиты</p>
                            <p class="text-3xl font-bold">история</p>
                        </div>
                        <div class="rounded-xl bg-gray-800 p-4 border border-white/5">
                            <p class="text-sm text-gray-300">ACL-записей</p>
                            <p class="text-3xl font-bold">по ролям</p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-indigo-600 p-5 flex items-center justify-between shadow-lg shadow-indigo-400/40">
                        <div>
                            <p class="text-sm text-indigo-100">Подключение за минуты</p>
                            <p class="text-lg font-semibold">Укажите токен GitHub и работайте</p>
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
            <h3 class="text-xl font-semibold mb-2">Без паролей</h3>
            <p class="text-gray-600">Вход по пасс-ключу, защита сессии и безопасные редиректы.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-emerald-600 text-3xl mb-4">🗂️</div>
            <h3 class="text-xl font-semibold mb-2">Версии в GitHub</h3>
            <p class="text-gray-600">Создавайте, редактируйте и коммитьте документы прямо из панели.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-amber-600 text-3xl mb-4">🛡️</div>
            <h3 class="text-xl font-semibold mb-2">ACL на уровне файла</h3>
            <p class="text-gray-600">Ролевые правила и точечные разрешения для конкретных путей.</p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/components/footer.php'; ?>
