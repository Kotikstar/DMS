<?php include 'components/header.php'; ?>
<div class="container mx-auto py-20">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold text-center mb-6">Login with Passkey</h2>
        <form action="passkey_auth.php" method="POST">
            <div class="mb-4">
                <label for="passkey" class="block text-gray-700">Passkey</label>
                <input type="text" id="passkey" name="passkey" required class="w-full p-3 border rounded-lg mt-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Login</button>
        </form>
    </div>
</div>
<?php include 'components/footer.php'; ?>