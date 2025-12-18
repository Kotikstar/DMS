<?php require_once __DIR__ . '/../components/header.php'; ?>
<section class="relative overflow-hidden bg-white">
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-white to-purple-50"></div>
    <div class="relative max-w-6xl mx-auto px-4 py-20">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <span class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold text-blue-700 bg-blue-100 rounded-full">Надежная авторизация</span>
                <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900">
                    Управляйте доступом
                    <span class="text-blue-600">быстро и безопасно</span>
                </h1>
                <p class="text-lg text-gray-600 leading-relaxed">
                    LC System — это современная панель для авторизации по пасс-ключам, управления ролями и мониторинга действий пользователей. Всё, что нужно команде безопасности, в одном интерфейсе.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="/pages/login.php" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-lg shadow-blue-200 hover:bg-blue-500 transition">Войти в систему</a>
                    <a href="/pages/dashboard.php" class="px-6 py-3 bg-white text-blue-700 font-semibold rounded-lg border border-blue-200 hover:border-blue-400 hover:shadow-md transition">Открыть панель</a>
                </div>
                <div class="flex items-center gap-6 pt-4 text-sm text-gray-500">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                        Шифрование соединений
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                        Контроль ролей
                    </div>
                </div>
            </div>
            <div class="bg-gray-900 text-white rounded-2xl shadow-2xl p-8 space-y-6">
                <div class="flex items-center justify-between">
                    <p class="text-lg font-semibold">Панель администратора</p>
                    <span class="px-3 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-200">Live</span>
                </div>
                <div class="rounded-xl bg-gray-800 p-6 space-y-4 border border-white/5">
                    <div class="flex items-center justify-between text-sm text-gray-300">
                        <span>Пользователей в системе</span>
                        <span class="font-semibold text-white">24</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-gray-300">
                        <span>Активные роли</span>
                        <span class="font-semibold text-white">5</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-gray-300">
                        <span>Последний вход</span>
                        <span class="font-semibold text-white">сегодня, 10:24</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-white/5 bg-gray-800 p-4">
                        <p class="text-sm text-gray-300">Управление ролями</p>
                        <p class="text-2xl font-semibold">RBAC</p>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-gray-800 p-4">
                        <p class="text-sm text-gray-300">Пасс-ключи</p>
                        <p class="text-2xl font-semibold">FIDO2</p>
                    </div>
                </div>
                <div class="rounded-xl bg-blue-600 text-white p-5 flex items-center justify-between shadow-lg shadow-blue-400/40">
                    <div>
                        <p class="text-sm text-blue-100">Обновлённая безопасность</p>
                        <p class="text-lg font-semibold">Защита без паролей</p>
                    </div>
                    <span class="text-2xl">🔒</span>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="max-w-6xl mx-auto px-4 pb-16">
    <h2 class="text-3xl font-bold text-gray-900 mb-8">Что внутри LC System?</h2>
    <div class="grid md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-blue-600 text-3xl mb-4">🗝️</div>
            <h3 class="text-xl font-semibold mb-2">Вход по пасс-ключу</h3>
            <p class="text-gray-600">Поддержка безопасной аутентификации без паролей с быстрой валидацией и защищёнными сессиями.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-purple-600 text-3xl mb-4">🛡️</div>
            <h3 class="text-xl font-semibold mb-2">Гибкие роли</h3>
            <p class="text-gray-600">Управляйте правами пользователей в пару кликов: администраторы, редакторы или кастомные роли.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-lg border border-gray-100">
            <div class="text-amber-600 text-3xl mb-4">📊</div>
            <h3 class="text-xl font-semibold mb-2">Прозрачная статистика</h3>
            <p class="text-gray-600">Мониторинг активности, логирование входов и наглядные карточки состояния системы.</p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
