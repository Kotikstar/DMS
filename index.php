<?php require_once __DIR__ . '/components/header.php'; ?>
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950"></div>
    <div class="absolute inset-0 opacity-60 bg-[radial-gradient(circle_at_10%_20%,rgba(59,130,246,0.14),transparent_25%),radial-gradient(circle_at_90%_10%,rgba(16,185,129,0.14),transparent_20%),radial-gradient(circle_at_50%_80%,rgba(99,102,241,0.12),transparent_25%)]"></div>
    <div class="relative max-w-7xl mx-auto px-4 py-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <p class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold text-emerald-200 bg-white/5 border border-white/10 rounded-full">Digital Document Hub</p>
                <h1 class="text-4xl lg:text-5xl font-bold leading-tight text-white">
                    GitHub как хранилище документов и версий
                </h1>
                <p class="text-lg text-slate-300 leading-relaxed">
                    LC System связывает ваши файлы с GitHub: создавайте, загружайте Word/PDF, фиксируйте изменения коммитами и управляйте доступом через MySQL-роли и ACL. Пасс-ключи дают вход без паролей, дизайн — чистый hi-tech на Tailwind.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="/pages/login.php" class="px-6 py-3 bg-gradient-to-r from-emerald-400 to-blue-500 text-slate-900 font-semibold rounded-xl shadow-lg shadow-emerald-500/40 hover:scale-[1.01] transition">Перейти ко входу</a>
                    <a href="/pages/dashboard.php" class="px-6 py-3 bg-white/10 text-emerald-100 font-semibold rounded-xl border border-white/10 hover:border-emerald-300/40 hover:text-white transition">Открыть документы</a>
                </div>
                <div class="flex flex-wrap gap-4 text-sm text-slate-400">
                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/10">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-400"></span>
                        Пасс-ключи (FIDO2/Windows Hello)
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/10">
                        <span class="inline-block h-2 w-2 rounded-full bg-blue-400"></span>
                        RBAC + ACL на документы
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/10">
                        <span class="inline-block h-2 w-2 rounded-full bg-violet-400"></span>
                        GitHub API & версии
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="absolute -inset-6 bg-gradient-to-tr from-emerald-500/20 via-blue-500/10 to-purple-500/30 blur-3xl"></div>
                <div class="relative bg-slate-900/70 text-white rounded-2xl shadow-2xl border border-white/10 p-8 space-y-6 backdrop-blur-xl">
                    <div class="flex items-center justify-between">
                        <p class="text-lg font-semibold">Сводка репозитория</p>
                        <span class="px-3 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">GitHub Live</span>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="rounded-xl bg-white/5 p-4 border border-white/10">
                            <p class="text-sm text-slate-300">Документы</p>
                            <p class="text-3xl font-bold">∞</p>
                        </div>
                        <div class="rounded-xl bg-white/5 p-4 border border-white/10">
                            <p class="text-sm text-slate-300">Коммиты</p>
                            <p class="text-3xl font-bold">живые</p>
                        </div>
                        <div class="rounded-xl bg-white/5 p-4 border border-white/10">
                            <p class="text-sm text-slate-300">ACL-записей</p>
                            <p class="text-3xl font-bold">по ролям</p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-gradient-to-r from-emerald-500 to-blue-500 p-5 flex items-center justify-between shadow-lg shadow-emerald-500/30 text-slate-900">
                        <div>
                            <p class="text-sm text-emerald-100">Подключение за минуты</p>
                            <p class="text-lg font-semibold">Укажите токен GitHub и работайте</p>
                        </div>
                        <span class="text-2xl">⚡</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="max-w-7xl mx-auto px-4 pb-16 relative z-10">
    <div class="grid md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xl">
            <div class="text-emerald-400 text-3xl mb-4">🔐</div>
            <h3 class="text-xl font-semibold mb-2 text-white">Без паролей</h3>
            <p class="text-slate-300">Вход по пасс-ключу, защита сессии и безопасные редиректы.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xl">
            <div class="text-blue-400 text-3xl mb-4">🗂️</div>
            <h3 class="text-xl font-semibold mb-2 text-white">Версии в GitHub</h3>
            <p class="text-slate-300">Создавайте, загружайте и коммитьте документы прямо из панели.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xl">
            <div class="text-violet-400 text-3xl mb-4">🛡️</div>
            <h3 class="text-xl font-semibold mb-2 text-white">ACL на уровне файла</h3>
            <p class="text-slate-300">Ролевые правила и точечные разрешения для конкретных путей.</p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/components/footer.php'; ?>
