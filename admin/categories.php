<?php
require_once __DIR__ . '/../includes/functions.php';
admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    if ($name !== '') {
        insert_lookup_item('categories', $name, $sort);
    }
    redirect('/admin/categories.php');
}

$error = '';

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$deleteId]);
        redirect('/admin/categories.php');
    } catch (Throwable $e) {
        $error = 'Không thể xóa danh mục này vì đang có loại sản phẩm hoặc sản phẩm liên kết.';
    }
}

$pageTitle = 'Danh mục';
$categories = get_categories();
require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($error !== ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>
<div class="admin-topbar">
    <h1>Danh mục sản phẩm</h1>
    <div class="card-actions">
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_types.php">Loại sản phẩm</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_conditions.php">Tình trạng</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/styles.php">Phong cách</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">← Quay lại sản phẩm</a>
    </div>
</div>
<div class="alert">Danh mục chính đang dùng cấu trúc 2 tầng. Ví dụ: <strong>Áo</strong> là danh mục, còn <strong>Áo Khoác</strong>, <strong>Áo Polo</strong>, <strong>Sơ Mi</strong> là các loại thuộc danh mục Áo.</div>
<div class="admin-two-col">
    <form method="post" class="form-grid narrow card-box">
        <h3>Thêm danh mục chính</h3>
        <label>Tên danh mục
            <input type="text" name="name" required>
        </label>
        <label>Thứ tự hiển thị
            <input type="number" name="sort_order" value="0">
        </label>
        <button class="btn" type="submit">Lưu danh mục</button>
    </form>

    <div class="card-box">
        <h3>Danh sách danh mục chính</h3>
        <table>
            <thead><tr><th>Tên</th><th>Slug</th><th>Thứ tự</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= e($cat['name']) ?></td>
                    <td><?= e($cat['slug'] ?? '') ?></td>
                    <td><?= (int)$cat['sort_order'] ?></td>
                    <td><a class="btn btn-danger" onclick="return confirm('Xóa danh mục này?');" href="<?= BASE_URL ?>/admin/categories.php?delete=<?= (int)$cat['id'] ?>">Xóa</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
