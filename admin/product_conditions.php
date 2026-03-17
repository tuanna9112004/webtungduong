<?php
require_once __DIR__ . '/../includes/functions.php';
admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    if ($name !== '') {
        insert_lookup_item('product_conditions', $name, $sort);
    }
    redirect('/admin/product_conditions.php');
}

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    db()->prepare('DELETE FROM product_conditions WHERE id = ?')->execute([$deleteId]);
    redirect('/admin/product_conditions.php');
}

$pageTitle = 'Tình trạng sản phẩm';
$productConditions = get_product_conditions();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-topbar">
    <h1>Tình trạng sản phẩm</h1>
    <div class="card-actions">
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/categories.php">Danh mục</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_types.php">Loại sản phẩm</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/styles.php">Phong cách</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">← Quay lại sản phẩm</a>
    </div>
</div>
<div class="admin-two-col">
    <form method="post" class="form-grid narrow card-box">
        <h3>Thêm tình trạng</h3>
        <label>Tên tình trạng
            <input type="text" name="name" required>
        </label>
        <label>Thứ tự hiển thị
            <input type="number" name="sort_order" value="0">
        </label>
        <button class="btn" type="submit">Lưu tình trạng</button>
    </form>

    <div class="card-box">
        <h3>Danh sách tình trạng</h3>
        <table>
            <thead><tr><th>Tên</th><th>Slug</th><th>Thứ tự</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($productConditions as $condition): ?>
                <tr>
                    <td><?= e($condition['name']) ?></td>
                    <td><?= e($condition['slug'] ?? '') ?></td>
                    <td><?= (int)$condition['sort_order'] ?></td>
                    <td><a class="btn btn-danger" onclick="return confirm('Xóa tình trạng này?');" href="<?= BASE_URL ?>/admin/product_conditions.php?delete=<?= (int)$condition['id'] ?>">Xóa</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
