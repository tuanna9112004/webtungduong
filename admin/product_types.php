<?php
require_once __DIR__ . '/../includes/functions.php';
admin_require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if ($name !== '' && $categoryId > 0) {
        insert_product_type($name, $categoryId, $sort);
        redirect('/admin/product_types.php');
    }

    $error = 'Vui lòng nhập tên loại và chọn đúng danh mục.';
}

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        db()->prepare('DELETE FROM product_types WHERE id = ?')->execute([$deleteId]);
        redirect('/admin/product_types.php');
    } catch (Throwable $e) {
        $error = 'Không thể xóa loại sản phẩm này vì đang có sản phẩm sử dụng.';
    }
}

$pageTitle = 'Loại sản phẩm';
$categories = get_categories();
$productTypes = get_product_types();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-topbar">
    <h1>Loại sản phẩm theo danh mục</h1>
    <div class="card-actions">
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/categories.php">Danh mục</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_conditions.php">Tình trạng</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/styles.php">Phong cách</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">← Quay lại sản phẩm</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="admin-two-col">
    <form method="post" class="form-grid narrow card-box">
        <h3>Thêm loại sản phẩm</h3>
        <label>Danh mục <span class="required-mark">*</span>
            <select name="category_id" required>
                <option value="">-- Chọn danh mục --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>"><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Tên loại
            <input type="text" name="name" required placeholder="Ví dụ: Áo Khoác, Sơ Mi, Quần Jean...">
        </label>
        <label>Thứ tự hiển thị
            <input type="number" name="sort_order" value="0">
        </label>
        <button class="btn" type="submit">Lưu loại sản phẩm</button>
    </form>

    <div class="card-box">
        <h3>Danh sách loại sản phẩm</h3>
        <table>
            <thead><tr><th>Danh mục</th><th>Tên loại</th><th>Slug</th><th>Thứ tự</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($productTypes as $type): ?>
                <tr>
                    <td><?= e($type['category_name']) ?></td>
                    <td><?= e($type['name']) ?></td>
                    <td><?= e($type['slug'] ?? '') ?></td>
                    <td><?= (int)$type['sort_order'] ?></td>
                    <td><a class="btn btn-danger" onclick="return confirm('Xóa loại sản phẩm này?');" href="<?= BASE_URL ?>/admin/product_types.php?delete=<?= (int)$type['id'] ?>">Xóa</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
