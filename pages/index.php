<?php require_once __DIR__ . '/../components/header.php'; ?>
<section class="relative bg-white">
    <div class="max-w-5xl mx-auto px-4 py-16">
        <div class="flex flex-col gap-6">
            <h1 class="text-4xl font-bold text-gray-900">Digital Management System</h1>
            <p class="text-lg text-gray-600">
                Управляйте документами через GitHub API, храните роли и ACL в MySQL и обеспечьте вход без паролей по пасс-ключам.
                Эта страница — короткое описание возможностей перед тем, как перейти к панели.
            </p>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="p-5 bg-white border border-gray-100 rounded-xl shadow-sm">
                    <p class="text-sm text-gray-500">GitHub</p>
                    <p class="text-xl font-semibold">Коммиты и версии</p>
                </div>
                <div class="p-5 bg-white border border-gray-100 rounded-xl shadow-sm">
                    <p class="text-sm text-gray-500">Права</p>
                    <p class="text-xl font-semibold">RBAC + ACL</p>
                </div>
                <div class="p-5 bg-white border border-gray-100 rounded-xl shadow-sm">
                    <p class="text-sm text-gray-500">Пасс-ключи</p>
                    <p class="text-xl font-semibold">Без паролей</p>
                </div>
            </div>
            <div class="flex gap-4 pt-2">
                <a href="/pages/login.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-500">Войти</a>
                <a href="/pages/dashboard.php" class="px-6 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200">Открыть документы</a>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
