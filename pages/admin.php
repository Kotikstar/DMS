<?php
session_start();
include 'db.php';

if ($_SESSION['role_id'] != 1) { // Admin role 1
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];

    $query = $pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :user_id");
    $query->execute(['role_id' => $role_id, 'user_id' => $user_id]);
    echo "<script>alert('User role updated successfully!');</script>";
}

$query = $pdo->query("SELECT * FROM users");
$users = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $pdo->query("SELECT * FROM roles");
$roles = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'components/header.php'; ?>
<div class="container mx-auto py-20">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Admin Panel</h1>
    <form method="POST">
        <label for="user_id" class="block text-gray-700 mb-2">Select User:</label>
        <select name="user_id" id="user_id" class="w-full p-3 border rounded-lg mb-4">
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id']; ?>"><?= $user['username']; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="role_id" class="block text-gray-700 mb-2">Select Role:</label>
        <select name="role_id" id="role_id" class="w-full p-3 border rounded-lg mb-4">
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role['id']; ?>"><?= $role['role_name']; ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Role</button>
    </form>
</div>
<?php include 'components/footer.php'; ?>