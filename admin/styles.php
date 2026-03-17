<?php
require_once __DIR__ . '/../includes/functions.php';
admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    if ($name !== '') {
        insert_lookup_item('styles', $name, $sort);
    }
    redirect('/admin/styles.php');
}

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    db()->prepare('DELETE FROM styles WHERE id = ?')->execute([$deleteId]);
    redirect('/admin/styles.php');
}

$pageTitle = 'Phong cách';
$styles = get_styles();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-topbar">
    <h1>Phong cách sản phẩm</h1>
    <div class="card-actions">
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/categories.php">Danh mục</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_types.php">Loại sản phẩm</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_conditions.php">Tình trạng</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">← Quay lại sản phẩm</a>
    </div>
</div>
<div class="admin-two-col">
    <form method="post" class="form-grid narrow card-box">
        <h3>Thêm phong cách</h3>
        <label>Tên phong cách
            <input type="text" name="name" required>
        </label>
        <label>Thứ tự hiển thị
            <input type="number" name="sort_order" value="0">
        </label>
        <button class="btn" type="submit">Lưu phong cách</button>
    </form>

    <div class="card-box">
        <h3>Danh sách phong cách</h3>
        <table>
            <thead><tr><th>Tên</th><th>Slug</th><th>Thứ tự</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($styles as $style): ?>
                <tr>
                    <td><?= e($style['name']) ?></td>
                    <td><?= e($style['slug'] ?? '') ?></td>
                    <td><?= (int)$style['sort_order'] ?></td>
                    <td><a class="btn btn-danger" onclick="return confirm('Xóa phong cách này?');" href="<?= BASE_URL ?>/admin/styles.php?delete=<?= (int)$style['id'] ?>">Xóa</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
