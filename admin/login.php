<?php
require_once __DIR__ . '/../includes/functions.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        redirect('/admin/products.php');
    }
    $error = 'Sai tài khoản hoặc mật khẩu.';
}
$pageTitle = 'Đăng nhập quản trị';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-auth-box">
    <h1>Đăng nhập quản trị</h1>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid narrow">
        <label>Tài khoản
            <input type="text" name="username" required>
        </label>
        <label>Mật khẩu
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">Đăng nhập</button>
    </form>
    <p class="hint">Tài khoản mẫu: <strong>admin</strong> / <strong>admin123</strong></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
