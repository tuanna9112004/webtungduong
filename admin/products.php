<?php
require_once __DIR__ . '/../includes/functions.php';
admin_require_login();
$pageTitle = 'Quản lý sản phẩm';
$products = get_products(null, false);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* CSS DÀNH RIÊNG CHO KHU VỰC ADMIN */
.admin-container {
    background: var(--bg-white, #fff);
    border-radius: var(--radius-lg, 12px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    padding: 24px;
    margin-bottom: 40px;
}

.admin-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--line-light, #e5e7eb);
}

.admin-header-title h1 {
    font-size: 24px;
    color: var(--text-main);
    margin-bottom: 4px;
}

.admin-header-title p {
    color: var(--text-muted);
    font-size: 14px;
}

.admin-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

/* Custom riêng các nút trên topbar cho nhỏ gọn */
.admin-nav .btn {
    padding: 8px 14px;
    font-size: 13px;
    min-height: unset;
}

.btn-danger {
    background-color: #fef2f2;
    color: var(--danger-color, #ef4444);
    border: 1px solid #fca5a5;
}
.btn-danger:hover {
    background-color: var(--danger-color, #ef4444);
    color: #fff;
    border-color: var(--danger-color, #ef4444);
}

/* Bảng hiển thị dữ liệu */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 8px;
    border: 1px solid var(--line-light, #e5e7eb);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    min-width: 1000px; /* Bắt buộc bảng không co dúm lại làm gãy chữ */
}

.admin-table th {
    background-color: #f8fafc;
    color: var(--text-muted, #6b7280);
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    padding: 12px 16px;
    border-bottom: 2px solid var(--line-light, #e5e7eb);
    white-space: nowrap;
}

.admin-table td {
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--line-light, #e5e7eb);
    font-size: 14px;
    color: var(--text-main);
}

.admin-table tr:hover td {
    background-color: #f8fafc;
}

.admin-table tr:last-child td {
    border-bottom: none;
}

.table-thumb {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--line-light);
}

.cell-product-info {
    max-width: 250px;
}

.cell-product-name {
    font-weight: 600;
    color: var(--text-main);
    display: block;
    margin-bottom: 4px;
}

.cell-product-code {
    font-size: 12px;
    color: var(--text-muted);
}

/* Tình trạng hiển thị / trạng thái */
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 999px;
}
.status-active { background: #dcfce7; color: #166534; }
.status-inactive { background: #f3f4f6; color: #4b5563; }

/* Nút thao tác trong bảng */
.table-actions {
    display: flex;
    gap: 8px;
}
.table-actions .btn {
    padding: 6px 12px;
    font-size: 12px;
    min-height: unset;
}

@media (max-width: 768px) {
    .admin-container { padding: 15px; margin-top: 15px; }
    .admin-nav { width: 100%; }
    .admin-nav .btn { flex: 1; text-align: center; }
}
</style>

<div class="container">
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-header-title">
                <h1>Quản lý sản phẩm</h1>
                <p>Xin chào, <?= e($_SESSION['admin_name'] ?? 'Admin') ?></p>
            </div>
            <div class="admin-nav">
                <a class="btn btn-light" href="<?= BASE_URL ?>/admin/categories.php">Danh mục</a>
                <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_types.php">Loại sản phẩm</a>
                <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_conditions.php">Tình trạng</a>
                <a class="btn btn-light" href="<?= BASE_URL ?>/admin/styles.php">Phong cách</a>
                <a class="btn" href="<?= BASE_URL ?>/admin/product_form.php">+ Thêm SP</a>
                <a class="btn btn-danger" href="<?= BASE_URL ?>/admin/logout.php">Đăng xuất</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sản phẩm</th>
                        <th>Phân loại</th>
                        <th>Tình trạng</th>
                        <th>Giá bán</th>
                        <th>Kho</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Chưa có sản phẩm nào trong hệ thống.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong>#<?= (int)$product['id'] ?></strong></td>
                        
                        <td style="display: flex; align-items: center; gap: 12px; border-bottom: none; padding-right: 0;">
                            <img class="table-thumb" src="<?= e(resolve_media_url($product['thumbnail'])) ?>" alt="Thumb">
                            <div class="cell-product-info">
                                <span class="cell-product-name"><?= e($product['product_name']) ?></span>
                                <span class="cell-product-code">Mã: <?= e($product['product_code']) ?></span>
                            </div>
                        </td>

                        <td>
                            <div style="font-size: 13px; color: var(--text-main); margin-bottom: 4px;">
                                <strong>DM:</strong> <?= e($product['category_name'] ?: '-') ?>
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                <?= e($product['product_type_name'] ?: '-') ?> • <?= e($product['gender'] ?: '-') ?>
                            </div>
                        </td>

                        <td>
                            <?php if (!empty($product['condition_names'])): ?>
                                <span style="font-size: 13px; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;">
                                    <?= e($product['condition_names']) ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($product['sale_price'])): ?>
                                <div style="font-weight: 700; color: var(--danger-color);"><?= format_price($product['sale_price']) ?></div>
                                <div style="font-size: 12px; color: var(--text-muted); text-decoration: line-through;"><?= format_price($product['original_price']) ?></div>
                            <?php else: ?>
                                <div style="font-weight: 600; color: var(--text-main);"><?= format_price($product['original_price']) ?></div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php $qty = (int)$product['quantity']; ?>
                            <strong style="color: <?= $qty > 0 ? 'inherit' : 'var(--danger-color)' ?>;">
                                <?= $qty ?>
                            </strong>
                        </td>

                        <td>
                            <span class="status-badge <?= !empty($product['is_active']) ? 'status-active' : 'status-inactive' ?>">
                                <?= !empty($product['is_active']) ? 'Đang hiện' : 'Đã ẩn' ?>
                            </span>
                        </td>

                        <td>
                            <div class="table-actions">
                                <a class="btn btn-light" href="<?= BASE_URL ?>/admin/product_form.php?id=<?= (int)$product['id'] ?>">Sửa</a>
                                <a class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không? Hành động này không thể hoàn tác.');" href="<?= BASE_URL ?>/admin/product_delete.php?id=<?= (int)$product['id'] ?>">Xóa</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>