<?php require_once __DIR__ . '/../components/header.php'; ?>
<div class="max-w-6xl mx-auto px-4 py-16">
    <div class="max-w-md mx-auto bg-white p-10 rounded-2xl shadow-xl border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-900 text-center mb-2">Вход по пасс-ключу</h2>
        <p class="text-center text-gray-500 mb-8">Поддерживаются ключи безопасности (FIDO2/Windows Hello). Введите подписанный токен или код.</p>
        <form action="/passkey_auth.php" method="POST" class="space-y-6">
            <div>
                <label for="passkey" class="block text-sm font-semibold text-gray-700">Пасс-ключ</label>
                <input type="text" id="passkey" name="passkey" required class="mt-2 w-full p-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Например, admin-passkey">
            </div>
            <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-lg hover:bg-blue-500 transition">Войти</button>
        </form>
        <div class="mt-6 text-sm text-gray-500">
            <p>Права и ACL хранятся в MySQL. Обратитесь к администратору для выдачи ключа или назначения роли.</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
