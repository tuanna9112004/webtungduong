<?php
require_once __DIR__ . '/../includes/functions.php';
admin_require_login();
$pageTitle = 'Quản lý sản phẩm';
$products = get_products(null, false);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-topbar">
    <div>
        <h1>Quản lý sản phẩm</h1>
        <p>Xin chào, <?= e($_SESSION['admin_name'] ?? 'Admin') ?></p>
    </div>
    <div class="card-actions">
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/categories.php">Danh mục</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_types.php">Loại sản phẩm</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_conditions.php">Tình trạng</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/styles.php">Phong cách</a>
        <a class="btn" href="<?= BASE_URL ?>/admin/product_form.php">+ Thêm sản phẩm</a>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/logout.php">Đăng xuất</a>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên</th>
                <th>Mã SP</th>
                <th>Danh mục</th>
                <th>Loại</th>
                <th>Giới tính</th>
                <th>Tình trạng</th>
                <th>Giá</th>
                <th>Tồn</th>
                <th>Hiển thị</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= (int)$product['id'] ?></td>
                <td><img class="table-thumb" src="<?= e(resolve_media_url($product['thumbnail'])) ?>" alt=""></td>
                <td><?= e($product['product_name']) ?></td>
                <td><?= e($product['product_code']) ?></td>
                <td><?= e($product['category_name'] ?: '-') ?></td>
                <td><?= e($product['product_type_name'] ?: '-') ?></td>
                <td><?= e($product['gender'] ?: '-') ?></td>
                <td><?= e($product['condition_names'] ?: '-') ?></td>
                <td>
                    <?php if (!empty($product['sale_price'])): ?>
                        <div class="price-old"><?= format_price($product['original_price']) ?></div>
                        <div><?= format_price($product['sale_price']) ?></div>
                    <?php else: ?>
                        <?= format_price($product['original_price']) ?>
                    <?php endif; ?>
                </td>
                <td><?= (int)$product['quantity'] ?></td>
                <td><?= !empty($product['is_active']) ? 'Có' : 'Không' ?></td>
                <td>
                    <div class="card-actions">
                        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_form.php?id=<?= (int)$product['id'] ?>">Sửa</a>
                        <a class="btn btn-danger" onclick="return confirm('Xóa sản phẩm này?');" href="<?= BASE_URL ?>/admin/product_delete.php?id=<?= (int)$product['id'] ?>">Xóa</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
