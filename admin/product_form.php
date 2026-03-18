<?php
require_once __DIR__ . '/../includes/functions.php';
admin_require_login();

$formatPriceInput = static function ($value): string {
    if ($value === null || $value === '') {
        return '';
    }

    $number = (float)$value;
    if ($number <= 0) {
        return '0';
    }

    return number_format($number, 0, ',', '.');
};

$normalizePriceInput = static function ($value): float {
    $clean = preg_replace('/[^\d]/', '', (string)$value);
    return $clean === '' ? 0 : (float)$clean;
};

$moveImageToFront = static function (array $items, ?string $target): array {
    $items = array_values(array_filter($items, static fn($value) => $value !== null && $value !== ''));

    if ($target === null || $target === '') {
        return $items;
    }

    $index = array_search($target, $items, true);
    if ($index === false) {
        return $items;
    }

    unset($items[$index]);
    array_unshift($items, $target);

    return array_values($items);
};

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$product = [
    'product_name' => '',
    'product_code' => next_product_code_preview(),
    'category_id' => '',
    'product_type_id' => '',
    'style_id' => '',
    'gender' => 'Nam',
    'original_price' => '',
    'sale_price' => '',
    'material' => '',
    'size' => '',
    'information' => '',
    'short_description' => '',
    'quantity' => '0',
    'color' => '',
    'import_link' => '',
    'thumbnail' => '',
    'is_active' => 1,
];

$images = [];
$selectedConditions = [];
$errors = [];
$stagedUploadPaths = [];
$stagedUploadPathsJson = '[]';

if ($isEdit) {
    $existing = get_product($id);
    if ($existing) {
        $product = $existing;
        $images = get_product_images($id);
        $selectedConditions = get_product_condition_ids($id);
    } else {
        $errors[] = 'Không tìm thấy sản phẩm cần sửa.';
        $isEdit = false;
        $id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genderOptions = product_gender_options();
    $postedGender = trim($_POST['gender'] ?? 'Nam');

    $data = [
        'product_name' => trim($_POST['product_name'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'product_type_id' => (int)($_POST['product_type_id'] ?? 0),
        'style_id' => ($_POST['style_id'] ?? '') !== '' ? (int)$_POST['style_id'] : null,
        'gender' => in_array($postedGender, $genderOptions, true) ? $postedGender : 'Nam',
        'original_price' => $normalizePriceInput($_POST['original_price'] ?? 0),
        'sale_price' => trim($_POST['sale_price'] ?? '') !== '' ? $normalizePriceInput($_POST['sale_price'] ?? 0) : null,
        'material' => trim($_POST['material'] ?? ''),
        'size' => trim($_POST['size'] ?? ''),
        'information' => trim($_POST['information'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'color' => trim($_POST['color'] ?? ''),
        'import_link' => trim($_POST['import_link'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    $selectedConditions = array_values(
        array_unique(
            array_filter(
                array_map('intval', $_POST['condition_ids'] ?? []),
                fn($value) => $value > 0
            )
        )
    );

    $removedImageIds = array_values(
        array_unique(
            array_filter(
                array_map('intval', $_POST['remove_image_ids'] ?? []),
                fn($value) => $value > 0
            )
        )
    );

    $primaryImageInput = trim($_POST['primary_image'] ?? '');

    if ($data['product_name'] === '') {
        $errors[] = 'Vui lòng nhập tên sản phẩm.';
    }

    if ($data['category_id'] <= 0) {
        $errors[] = 'Vui lòng chọn danh mục.';
    }

    if ($data['product_type_id'] <= 0) {
        $errors[] = 'Vui lòng chọn loại sản phẩm.';
    }

    if (
        $data['category_id'] > 0 &&
        $data['product_type_id'] > 0 &&
        !product_type_exists_for_category($data['product_type_id'], $data['category_id'])
    ) {
        $errors[] = 'Loại sản phẩm không thuộc danh mục đã chọn.';
    }

    if ($data['original_price'] <= 0) {
        $errors[] = 'Giá gốc phải lớn hơn 0.';
    }

    if ($data['sale_price'] !== null && $data['sale_price'] > 0 && $data['sale_price'] > $data['original_price']) {
        $errors[] = 'Giá sale không được lớn hơn giá gốc.';
    }

    $stagedUploadPaths = normalize_posted_uploaded_paths($_POST['uploaded_gallery_paths'] ?? []);
    $directUploadedImages = handle_multiple_image_uploads($_FILES['gallery_files'] ?? null, [
        'destination' => 'uploads',
        'optimize' => true,
        'max_width' => 1400,
        'jpeg_quality' => 82,
        'webp_quality' => 80,
    ]);

    if (!empty($directUploadedImages)) {
        $stagedUploadPaths = array_values(array_unique(array_merge($stagedUploadPaths, $directUploadedImages)));
    }

    if (!$isEdit && empty($stagedUploadPaths)) {
        $errors[] = 'Khi thêm mới, bạn phải upload ít nhất 1 ảnh sản phẩm.';
    }

    if ($isEdit) {
        $currentExistingIds = array_map(
            'intval',
            array_column(get_product_images($id), 'id')
        );

        $remainingExistingIds = array_values(array_diff($currentExistingIds, $removedImageIds));

        if (empty($remainingExistingIds) && empty($stagedUploadPaths)) {
            $errors[] = 'Bạn phải giữ lại hoặc thêm ít nhất 1 ảnh sản phẩm.';
        }
    }

    if (empty($errors)) {
        $uploadedImages = finalize_temp_uploaded_images($stagedUploadPaths);

        if ($isEdit) {
            $existingImages = get_product_images($id);
            $existingImageMap = [];

            foreach ($existingImages as $imageRow) {
                $existingImageMap[(int)$imageRow['id']] = $imageRow['image_url'];
            }

            if (!empty($removedImageIds)) {
                $placeholders = implode(',', array_fill(0, count($removedImageIds), '?'));
                $params = array_merge([$id], $removedImageIds);
                $stmt = db()->prepare("DELETE FROM product_images WHERE product_id = ? AND id IN ($placeholders)");
                $stmt->execute($params);
            }

            $existingImages = get_product_images($id);
            $existingImageMap = [];

            foreach ($existingImages as $imageRow) {
                $existingImageMap[(int)$imageRow['id']] = $imageRow['image_url'];
            }

            $galleryImages = array_values(array_merge(array_values($existingImageMap), $uploadedImages));

            $primaryTarget = null;

            if (strpos($primaryImageInput, 'existing:') === 0) {
                $primaryId = (int)substr($primaryImageInput, 9);
                $primaryTarget = $existingImageMap[$primaryId] ?? null;
            } elseif (strpos($primaryImageInput, 'new:') === 0) {
                $newIndex = (int)substr($primaryImageInput, 4);
                $primaryTarget = $uploadedImages[$newIndex] ?? null;
            }

            $galleryImages = $moveImageToFront($galleryImages, $primaryTarget);
            $thumbnail = $galleryImages[0] ?? null;

            $stmt = db()->prepare('
                UPDATE products
                SET product_name = ?, category_id = ?, product_type_id = ?, style_id = ?, gender = ?,
                    original_price = ?, sale_price = ?, material = ?, size = ?, information = ?,
                    short_description = ?, quantity = ?, color = ?, import_link = ?, thumbnail = ?, is_active = ?
                WHERE id = ?
            ');

            $stmt->execute([
                $data['product_name'],
                $data['category_id'],
                $data['product_type_id'],
                $data['style_id'],
                $data['gender'],
                $data['original_price'],
                $data['sale_price'],
                $data['material'],
                $data['size'],
                $data['information'],
                $data['short_description'],
                $data['quantity'],
                $data['color'],
                $data['import_link'],
                $thumbnail,
                $data['is_active'],
                $id,
            ]);

            replace_product_gallery($id, $galleryImages);
            sync_product_conditions($id, $selectedConditions);
        } else {
            $productCode = generate_unique_product_code();

            $galleryImages = $uploadedImages;
            $primaryTarget = null;

            if (strpos($primaryImageInput, 'new:') === 0) {
                $newIndex = (int)substr($primaryImageInput, 4);
                $primaryTarget = $uploadedImages[$newIndex] ?? null;
            }

            $galleryImages = $moveImageToFront($galleryImages, $primaryTarget);
            $thumbnail = $galleryImages[0] ?? null;

            $stmt = db()->prepare('
                INSERT INTO products (
                    product_name, product_code, category_id, product_type_id, style_id, gender,
                    original_price, sale_price, material, size, information, short_description,
                    quantity, color, import_link, thumbnail, is_active
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $stmt->execute([
                $data['product_name'],
                $productCode,
                $data['category_id'],
                $data['product_type_id'],
                $data['style_id'],
                $data['gender'],
                $data['original_price'],
                $data['sale_price'],
                $data['material'],
                $data['size'],
                $data['information'],
                $data['short_description'],
                $data['quantity'],
                $data['color'],
                $data['import_link'],
                $thumbnail,
                $data['is_active'],
            ]);

            $newId = (int)db()->lastInsertId();

            replace_product_gallery($newId, $galleryImages);
            sync_product_conditions($newId, $selectedConditions);
        }

        redirect('/admin/products.php');
    }

    $product = array_merge($product, $data, [
        'product_code' => $isEdit ? ($product['product_code'] ?? '') : next_product_code_preview(),
        'thumbnail' => $product['thumbnail'] ?? '',
    ]);

    if ($isEdit) {
        $images = get_product_images($id);
    }
}

$pageTitle = $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
$categories = get_categories();
$styles = get_styles();
$productTypes = get_product_types();
$productConditions = get_product_conditions();

$stagedUploadPathsJson = json_encode($stagedUploadPaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($stagedUploadPathsJson === false) {
    $stagedUploadPathsJson = '[]';
}

$currentPrimaryImage = trim($_POST['primary_image'] ?? '');

if ($currentPrimaryImage === '' && !empty($images)) {
    $currentPrimaryImage = 'existing:' . (int)$images[0]['id'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ==========================================================================
   CSS DÀNH CHO FORM SẢN PHẨM
   ========================================================================== */
.admin-wrapper {
    padding-top: 20px;
    padding-bottom: 60px;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--line-light, #e5e7eb);
    gap: 12px;
    flex-wrap: wrap;
}

.admin-header h1 {
    font-size: 24px;
    color: var(--text-main);
    margin: 0;
}

.card-box {
    background: var(--bg-white, #fff);
    border-radius: var(--radius-lg, 12px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid var(--line-light, #e5e7eb);
    padding: 24px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.col-full {
    grid-column: 1 / -1;
}

@media (min-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted, #6b7280);
    margin-bottom: 8px;
}

.form-group .hint {
    display: block;
    font-size: 12px;
    color: #9ca3af;
    margin-top: 6px;
    font-weight: 400;
    line-height: 1.5;
}

.required-mark {
    color: var(--danger-color, #ef4444);
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--line-strong, #d1d5db);
    border-radius: var(--radius-md, 8px);
    font-size: 14px;
    color: var(--text-main, #111827);
    background-color: var(--bg-white, #fff);
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    min-height: 42px;
}

.form-control:focus {
    border-color: var(--primary-color, #000);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.06);
}

.form-control[readonly] {
    background-color: #f3f4f6;
    cursor: not-allowed;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.checkbox-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
}

.checkbox-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.2s;
}

.checkbox-chip input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--primary-color, #000);
    cursor: pointer;
    flex-shrink: 0;
}

.checkbox-chip:hover {
    border-color: var(--line-strong, #d1d5db);
}

.checkbox-inline {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-main, #111827);
    cursor: pointer;
    padding: 10px 15px;
    background: #f8fafc;
    border: 1px solid var(--line-light, #e5e7eb);
    border-radius: 8px;
}

.checkbox-inline input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color, #000);
    flex-shrink: 0;
}

.upload-box {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border: 1px dashed var(--line-strong, #d1d5db);
}

.upload-box label.upload-title {
    color: var(--primary-color, #000);
    font-size: 15px;
    margin-bottom: 8px;
}

.upload-box input[type="file"] {
    margin-top: 10px;
    width: 100%;
}

.upload-status {
    margin-top: 8px;
    color: #2563eb;
    min-height: 20px;
}

.existing-gallery {
    margin-top: 10px;
    padding-top: 20px;
    border-top: 1px dashed var(--line-strong, #d1d5db);
}

.preview-gallery {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}

.existing-gallery h3 {
    font-size: 16px;
    margin-bottom: 15px;
}

.existing-gallery-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.existing-gallery-item {
    width: 140px;
    position: relative;
    border: 1px solid var(--line-light, #e5e7eb);
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.existing-gallery-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.existing-gallery-meta {
    padding: 8px;
    text-align: center;
    background: #f8fafc;
    border-top: 1px solid var(--line-light, #e5e7eb);
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
}

.thumb-badge {
    background: var(--primary-color, #000);
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
    align-self: center;
}

.mini-option {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    cursor: pointer;
    color: var(--text-main, #111827);
    line-height: 1.4;
    text-align: left;
}

.mini-option input[type="radio"],
.mini-option input[type="checkbox"] {
    accent-color: var(--primary-color, #000);
    flex-shrink: 0;
}

.mini-option.danger {
    color: #b91c1c;
}

.preview-file-name {
    font-size: 11px;
    color: #6b7280;
    line-height: 1.4;
    word-break: break-word;
    text-align: left;
}

.existing-gallery-item.is-removing {
    opacity: 0.55;
    border-color: #fca5a5;
    background: #fff5f5;
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.6;
}

.alert.error {
    background-color: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--line-light, #e5e7eb);
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>

<div class="container admin-wrapper">
    <div class="admin-header">
        <h1><?= $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm mới' ?></h1>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">← Hủy & Quay lại</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom; margin-right: 4px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= e(implode(' ', $errors)) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card-box" id="productForm">
        <div class="form-grid">

            <div class="form-group col-full">
                <label for="product_name">Tên sản phẩm <span class="required-mark">*</span></label>
                <input
                    id="product_name"
                    type="text"
                    name="product_name"
                    class="form-control"
                    value="<?= e($product['product_name']) ?>"
                    required
                    placeholder="VD: Áo Thun Nam Có Cổ"
                >
            </div>

            <div class="form-group">
                <label>Mã sản phẩm</label>
                <input type="text" class="form-control" value="<?= e($product['product_code']) ?>" readonly>
                <span class="hint">Hệ thống tự động tạo mã SP.</span>
            </div>

            <div class="form-group">
                <label for="categorySelect">Danh mục <span class="required-mark">*</span></label>
                <select name="category_id" id="categorySelect" class="form-control" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option
                            value="<?= (int)$cat['id'] ?>"
                            <?= (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>
                        >
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="productTypeSelect">Loại sản phẩm <span class="required-mark">*</span></label>
                <select name="product_type_id" id="productTypeSelect" class="form-control" required>
                    <option value="">-- Chọn loại sản phẩm --</option>
                    <?php foreach ($productTypes as $type): ?>
                        <option
                            value="<?= (int)$type['id'] ?>"
                            data-category-id="<?= (int)$type['category_id'] ?>"
                            <?= (int)$product['product_type_id'] === (int)$type['id'] ? 'selected' : '' ?>
                        >
                            <?= e($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="hint">Chỉ hiển thị các loại thuộc danh mục đã chọn.</span>
            </div>

            <div class="form-group">
                <label for="style_id">Phong cách</label>
                <select name="style_id" id="style_id" class="form-control">
                    <option value="">-- Chọn phong cách --</option>
                    <?php foreach ($styles as $style): ?>
                        <option
                            value="<?= (int)$style['id'] ?>"
                            <?= (int)$product['style_id'] === (int)$style['id'] ? 'selected' : '' ?>
                        >
                            <?= e($style['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="gender">Giới tính <span class="required-mark">*</span></label>
                <select name="gender" id="gender" class="form-control" required>
                    <?php foreach (product_gender_options() as $gender): ?>
                        <option value="<?= e($gender) ?>" <?= $product['gender'] === $gender ? 'selected' : '' ?>>
                            <?= e($gender) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="original_price">Giá gốc (VNĐ) (đây là giá gạch) <span class="required-mark">*</span></label>
                <input
                    id="original_price"
                    type="text"
                    name="original_price"
                    class="form-control money-input"
                    inputmode="numeric"
                    autocomplete="off"
                    value="<?= e($formatPriceInput($product['original_price'])) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="sale_price">Giá khuyến mãi (VNĐ) (đây là giá bán)</label>
                <input
                    id="sale_price"
                    type="text"
                    name="sale_price"
                    class="form-control money-input"
                    inputmode="numeric"
                    autocomplete="off"
                    value="<?= e($formatPriceInput($product['sale_price'])) ?>"
                >
                <span class="hint">Để trống nếu không sale.</span>
            </div>

            <div class="form-group">
                <label for="material">Chất liệu</label>
                <input
                    id="material"
                    type="text"
                    name="material"
                    class="form-control"
                    value="<?= e($product['material']) ?>"
                    placeholder="VD: Cotton, Kaki..."
                >
            </div>

            <div class="form-group">
                <label for="size">Kích thước (Size)</label>
                <input
                    id="size"
                    type="text"
                    name="size"
                    class="form-control"
                    placeholder="VD: S, M, L, XL"
                    value="<?= e($product['size']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="color">Màu sắc</label>
                <input
                    id="color"
                    type="text"
                    name="color"
                    class="form-control"
                    placeholder="VD: Đen, Trắng, Xám"
                    value="<?= e($product['color']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="quantity">Số lượng trong kho</label>
                <input
                    id="quantity"
                    type="number"
                    name="quantity"
                    class="form-control"
                    min="0"
                    value="<?= e((string)$product['quantity']) ?>"
                >
            </div>

            <div class="form-group col-full">
                <label for="import_link">Link Zalo nhập hàng / Nguồn / SĐT</label>
                <input
                    id="import_link"
                    type="text"
                    name="import_link"
                    class="form-control"
                    value="<?= e($product['import_link']) ?>"
                    placeholder="Nhập link (https://...) hoặc số điện thoại nguồn hàng"
                >
                <span class="hint">Bạn có thể điền link Zalo, link nguồn hàng hoặc số điện thoại đều được.</span>
            </div>

            <div class="form-group col-full">
                <label>Tình trạng sản phẩm</label>
                <div class="checkbox-list">
                    <?php if (empty($productConditions)): ?>
                        <span class="hint">Chưa có tình trạng nào. Hãy thêm tại mục Quản lý tình trạng.</span>
                    <?php else: ?>
                        <?php foreach ($productConditions as $condition): ?>
                            <label class="checkbox-chip">
                                <input
                                    type="checkbox"
                                    name="condition_ids[]"
                                    value="<?= (int)$condition['id'] ?>"
                                    <?= in_array((int)$condition['id'], $selectedConditions, true) ? 'checked' : '' ?>
                                >
                                <span><?= e($condition['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group col-full">
                <label for="short_description">Mô tả ngắn</label>
                <textarea
                    id="short_description"
                    name="short_description"
                    class="form-control"
                    rows="3"
                    placeholder="Đoạn văn ngắn gọn giới thiệu điểm nổi bật của SP..."
                ><?= e($product['short_description']) ?></textarea>
            </div>

            <div class="form-group col-full">
                <label for="information">Thông tin chi tiết</label>
                <textarea
                    id="information"
                    name="information"
                    class="form-control"
                    rows="6"
                    placeholder="Mô tả chi tiết về sản phẩm, hướng dẫn bảo quản, nguồn gốc..."
                ><?= e($product['information']) ?></textarea>
            </div>

            <div class="form-group col-full">
                <label>Trạng thái hiển thị</label>
                <label class="checkbox-inline">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        <?= !empty($product['is_active']) ? 'checked' : '' ?>
                    >
                    Hiển thị sản phẩm này trên gian hàng website
                </label>
            </div>

            <div class="form-group col-full upload-box">
                <label class="upload-title" for="galleryFiles">
                    Thư viện ảnh sản phẩm <span class="required-mark">*</span>
                </label>

                <input
                    id="galleryFiles"
                    type="file"
                    name="gallery_files[]"
                    accept="image/png,image/jpeg,image/webp"
                    multiple
                    <?= $isEdit ? '' : 'required' ?>
                >

                <input
                    type="hidden"
                    name="uploaded_gallery_paths"
                    id="uploadedGalleryPaths"
                    value='<?= e($stagedUploadPathsJson) ?>'
                >

                <div id="uploadHint" class="hint">
                    Bạn có thể chọn <strong>nhiều ảnh</strong> cùng lúc. Hệ thống sẽ nén nhẹ và tải nền ngay sau khi chọn để lúc bấm <strong>Lưu</strong> gần như tức thì.
                    Nên chọn tối đa <strong>8 ảnh/lần</strong>.
                </div>

                <div id="uploadStatus" class="hint upload-status"></div>
            </div>

            <div class="col-full existing-gallery preview-gallery" id="newPreviewBlock" style="display:none;">
                <h3>Ảnh mới vừa chọn</h3>
                <div class="existing-gallery-grid" id="newPreviewGrid"></div>
            </div>

            <?php if (!empty($images)): ?>
                <div class="col-full existing-gallery">
                    <h3>Quản lý ảnh hiện tại</h3>
                    <div class="existing-gallery-grid">
                        <?php foreach ($images as $index => $image): ?>
                            <?php $existingId = (int)$image['id']; ?>
                            <div class="existing-gallery-item" data-existing-id="<?= $existingId ?>">
                                <img src="<?= e(resolve_media_url($image['image_url'])) ?>" alt="Ảnh SP">

                                <div class="existing-gallery-meta">
                                    <?php if ($index === 0): ?>
                                        <span class="thumb-badge">Đang là ảnh chính</span>
                                    <?php endif; ?>

                                    <label class="mini-option">
                                        <input
                                            type="radio"
                                            name="primary_image"
                                            value="existing:<?= $existingId ?>"
                                            class="primary-radio"
                                            data-existing-id="<?= $existingId ?>"
                                            <?= $currentPrimaryImage === 'existing:' . $existingId ? 'checked' : '' ?>
                                        >
                                        <span>Chọn làm ảnh chính</span>
                                    </label>

                                    <label class="mini-option danger">
                                        <input
                                            type="checkbox"
                                            name="remove_image_ids[]"
                                            value="<?= $existingId ?>"
                                            class="remove-image-checkbox"
                                            data-existing-id="<?= $existingId ?>"
                                        >
                                        <span>Xóa ảnh</span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <div class="form-actions">
            <button class="btn btn-big" type="submit" id="submitBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Lưu thông tin sản phẩm
            </button>

            <a class="btn btn-light btn-big" href="<?= BASE_URL ?>/admin/products.php">Hủy thao tác</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('categorySelect');
    const typeSelect = document.getElementById('productTypeSelect');
    const galleryInput = document.getElementById('galleryFiles');
    const uploadedGalleryPathsInput = document.getElementById('uploadedGalleryPaths');
    const uploadStatus = document.getElementById('uploadStatus');
    const form = document.getElementById('productForm') || document.querySelector('form[enctype="multipart/form-data"]');
    const submitBtn = document.getElementById('submitBtn') || (form ? form.querySelector('button[type="submit"]') : null);
    const newPreviewBlock = document.getElementById('newPreviewBlock');
    const newPreviewGrid = document.getElementById('newPreviewGrid');
    const tempUploadEndpoint = <?= json_encode(BASE_URL . '/admin/upload_temp_images.php') ?>;
    const baseUrl = <?= json_encode(BASE_URL) ?>;

    let compressedFilesCache = [];
    let isCompressing = false;
    let isUploading = false;
    let originalSubmitHtml = submitBtn ? submitBtn.innerHTML : '';
    let lastSelectionKey = '';
    let previewObjectUrls = [];
    let stagedUploads = [];

    function setUploadStatus(message, type = '') {
        if (!uploadStatus) return;
        uploadStatus.textContent = message || '';
        uploadStatus.style.color = type === 'error' ? '#b91c1c' : (type === 'success' ? '#047857' : '#2563eb');
    }

    function setSubmitBusyState() {
        if (!submitBtn) return;

        if (isCompressing) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Đang chuẩn bị ảnh...';
            return;
        }

        if (isUploading) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Đang tải ảnh lên...';
            return;
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = originalSubmitHtml;
    }

    function resolveMediaUrl(value) {
        if (!value) return '';
        if (/^(https?:)?\/\//i.test(value) || value.startsWith('/')) {
            return value;
        }

        const cleanBase = (baseUrl || '').replace(/\/$/, '');
        const cleanValue = String(value).replace(/^\/+/, '');
        return cleanBase ? `${cleanBase}/${cleanValue}` : `/${cleanValue}`;
    }

    function syncHiddenUploadedPaths() {
        if (!uploadedGalleryPathsInput) return;
        uploadedGalleryPathsInput.value = JSON.stringify(stagedUploads.map(item => item.path));
    }

    function syncTypeOptions() {
        if (!categorySelect || !typeSelect) return;

        const categoryId = categorySelect.value;
        let hasVisibleSelected = false;

        Array.from(typeSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            const optionCategoryId = option.dataset.categoryId || '';
            const shouldShow = !!categoryId && optionCategoryId === categoryId;

            option.hidden = !shouldShow;

            if (!shouldShow && option.selected) {
                option.selected = false;
            }

            if (shouldShow && option.selected) {
                hasVisibleSelected = true;
            }
        });

        if (!hasVisibleSelected) {
            typeSelect.value = '';
        }
    }

    function formatMoney(value) {
        const digits = String(value).replace(/\D/g, '');
        if (!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatBytes(bytes) {
        if (!bytes) return '0 KB';
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        let size = bytes;

        while (size >= 1024 && i < units.length - 1) {
            size /= 1024;
            i++;
        }

        return `${size.toFixed(size >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
    }

    function getFilesKey(files) {
        return files.map(file => `${file.name}__${file.size}__${file.lastModified}`).join('||');
    }

    function getSelectedPrimaryValue() {
        const checked = document.querySelector('input[name="primary_image"]:checked:not(:disabled)');
        return checked ? checked.value : '';
    }

    function ensureAnyPrimarySelected() {
        const checked = document.querySelector('input[name="primary_image"]:checked:not(:disabled)');
        if (checked) return;

        const firstAvailable = document.querySelector('input[name="primary_image"]:not(:disabled)');
        if (firstAvailable) {
            firstAvailable.checked = true;
        }
    }

    function syncExistingImageControls() {
        const removeCheckboxes = document.querySelectorAll('.remove-image-checkbox');

        removeCheckboxes.forEach((checkbox) => {
            const id = checkbox.getAttribute('data-existing-id');
            const radio = document.querySelector('.primary-radio[data-existing-id="' + id + '"]');
            const card = checkbox.closest('.existing-gallery-item');

            if (radio) {
                radio.disabled = checkbox.checked;

                if (checkbox.checked && radio.checked) {
                    radio.checked = false;
                }
            }

            if (card) {
                card.classList.toggle('is-removing', checkbox.checked);
            }
        });

        ensureAnyPrimarySelected();
    }

    function clearPreviewUrls() {
        previewObjectUrls.forEach((url) => URL.revokeObjectURL(url));
        previewObjectUrls = [];
    }

    function clearNewPreview() {
        clearPreviewUrls();

        if (newPreviewGrid) {
            newPreviewGrid.innerHTML = '';
        }

        if (newPreviewBlock) {
            newPreviewBlock.style.display = 'none';
        }
    }

    function renderPreviewItems(items) {
        if (!newPreviewBlock || !newPreviewGrid) return;

        clearPreviewUrls();
        newPreviewGrid.innerHTML = '';

        const displayItems = Array.isArray(items) ? items : [];
        if (!displayItems.length) {
            newPreviewBlock.style.display = 'none';
            ensureAnyPrimarySelected();
            return;
        }

        newPreviewBlock.style.display = 'block';

        const selectedPrimary = getSelectedPrimaryValue();
        let hasChecked = !!document.querySelector('input[name="primary_image"]:checked:not(:disabled)');

        displayItems.forEach((item, index) => {
            const value = `new:${index}`;
            const previewSrc = item.previewSrc || item.url || '';

            const card = document.createElement('div');
            card.className = 'existing-gallery-item';

            const img = document.createElement('img');
            img.src = previewSrc;
            img.alt = item.name || `Ảnh ${index + 1}`;

            const meta = document.createElement('div');
            meta.className = 'existing-gallery-meta';

            const name = document.createElement('div');
            name.className = 'preview-file-name';
            name.textContent = item.label || `${item.name || `Ảnh ${index + 1}`} • ${formatBytes(item.size || 0)}`;

            const primaryLabel = document.createElement('label');
            primaryLabel.className = 'mini-option';

            const primaryRadio = document.createElement('input');
            primaryRadio.type = 'radio';
            primaryRadio.name = 'primary_image';
            primaryRadio.value = value;
            primaryRadio.className = 'primary-radio';

            let shouldCheck = false;
            if (selectedPrimary) {
                shouldCheck = selectedPrimary === value;
            } else if (!hasChecked && index === 0) {
                shouldCheck = true;
            }

            if (shouldCheck) {
                primaryRadio.checked = true;
                hasChecked = true;
            }

            const primaryText = document.createElement('span');
            primaryText.textContent = 'Chọn làm ảnh chính';

            primaryLabel.appendChild(primaryRadio);
            primaryLabel.appendChild(primaryText);

            meta.appendChild(name);
            meta.appendChild(primaryLabel);
            card.appendChild(img);
            card.appendChild(meta);

            newPreviewGrid.appendChild(card);
        });

        ensureAnyPrimarySelected();
    }

    function renderNewPreviewFromFiles(files) {
        const items = Array.from(files || []).map((file) => {
            const objectUrl = URL.createObjectURL(file);
            previewObjectUrls.push(objectUrl);

            return {
                previewSrc: objectUrl,
                name: file.name,
                size: file.size || 0,
                label: `${file.name} • ${formatBytes(file.size || 0)}`
            };
        });

        renderPreviewItems(items);
    }

    function renderNewPreviewFromUploads(items) {
        const previewItems = Array.from(items || []).map((item) => ({
            previewSrc: item.url,
            name: item.name || 'Ảnh mới',
            size: item.size || 0,
            label: `${item.name || 'Ảnh mới'}${item.size ? ` • ${formatBytes(item.size)}` : ''}`
        }));

        renderPreviewItems(previewItems);
    }

    function loadImageFromFile(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();

            img.onload = function () {
                URL.revokeObjectURL(url);
                resolve(img);
            };

            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Không thể đọc ảnh'));
            };

            img.src = url;
        });
    }

    async function compressImage(file, options = {}) {
        const {
            maxWidth = 1280,
            quality = 0.78
        } = options;

        if (!file || !file.type || !file.type.startsWith('image/')) {
            return file;
        }

        if ((file.size || 0) < 250 * 1024) {
            return file;
        }

        let srcWidth = 0;
        let srcHeight = 0;
        let drawSource = null;

        try {
            if ('createImageBitmap' in window) {
                const bitmap = await createImageBitmap(file);
                srcWidth = bitmap.width;
                srcHeight = bitmap.height;
                drawSource = bitmap;
            } else {
                const img = await loadImageFromFile(file);
                srcWidth = img.naturalWidth || img.width;
                srcHeight = img.naturalHeight || img.height;
                drawSource = img;
            }
        } catch (error) {
            console.error(error);
            return file;
        }

        let targetWidth = srcWidth;
        let targetHeight = srcHeight;

        if (srcWidth > maxWidth) {
            targetWidth = maxWidth;
            targetHeight = Math.round((srcHeight / srcWidth) * targetWidth);
        }

        const canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;

        const ctx = canvas.getContext('2d', { alpha: false });
        if (!ctx) {
            if (drawSource && typeof drawSource.close === 'function') {
                drawSource.close();
            }
            return file;
        }

        ctx.drawImage(drawSource, 0, 0, targetWidth, targetHeight);

        if (drawSource && typeof drawSource.close === 'function') {
            drawSource.close();
        }

        const outputType = file.type === 'image/png' ? 'image/webp' : 'image/jpeg';
        const blob = await new Promise((resolve) => {
            canvas.toBlob((result) => resolve(result || null), outputType, quality);
        });

        if (!(blob instanceof Blob)) {
            return file;
        }

        if ((blob.size || 0) >= (file.size || 0)) {
            return file;
        }

        const ext = outputType === 'image/webp' ? 'webp' : 'jpg';
        const cleanName = file.name.replace(/\.[^.]+$/, '');

        return new File([blob], `${cleanName}.${ext}`, {
            type: outputType,
            lastModified: Date.now()
        });
    }

    async function compressSelectedFiles(files) {
        const originalTotal = files.reduce((sum, file) => sum + (file.size || 0), 0);
        const compressedFiles = new Array(files.length);
        const concurrency = Math.min(3, Math.max(1, files.length));
        let cursor = 0;

        async function worker() {
            while (cursor < files.length) {
                const index = cursor++;
                setUploadStatus(`Đang chuẩn bị ảnh ${index + 1}/${files.length}...`);
                compressedFiles[index] = await compressImage(files[index], {
                    maxWidth: 1280,
                    quality: 0.78
                });
            }
        }

        await Promise.all(Array.from({ length: concurrency }, worker));
        const compressedTotal = compressedFiles.reduce((sum, file) => sum + (file.size || 0), 0);

        return {
            files: compressedFiles,
            originalTotal,
            compressedTotal
        };
    }

    async function uploadPreparedFiles(files) {
        const formData = new FormData();
        files.forEach((file) => {
            formData.append('gallery_files[]', file, file.name);
        });

        const response = await fetch(tempUploadEndpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const payload = await response.json().catch(() => null);
        if (!response.ok || !payload || !payload.success) {
            throw new Error(payload && payload.message ? payload.message : 'Không thể tải ảnh lên máy chủ.');
        }

        return Array.isArray(payload.files) ? payload.files : [];
    }

    async function prepareImagesNow(fileList) {
        if (!galleryInput) return;

        const files = Array.from(fileList || []);
        compressedFilesCache = [];

        if (!files.length) {
            stagedUploads = [];
            syncHiddenUploadedPaths();
            setUploadStatus('');
            clearNewPreview();
            return;
        }

        if (files.length > 8) {
            galleryInput.value = '';
            stagedUploads = [];
            syncHiddenUploadedPaths();
            clearNewPreview();
            setUploadStatus('Chỉ nên chọn tối đa 8 ảnh mỗi lần.', 'error');
            return;
        }

        const selectionKey = getFilesKey(files);
        lastSelectionKey = selectionKey;
        renderNewPreviewFromFiles(files);

        try {
            isCompressing = true;
            setSubmitBusyState();

            const result = await compressSelectedFiles(files);
            if (selectionKey !== lastSelectionKey) {
                return;
            }

            compressedFilesCache = result.files;
            isCompressing = false;
            isUploading = true;
            setSubmitBusyState();
            setUploadStatus(`Đang tải ${files.length} ảnh lên máy chủ...`);

            const uploaded = await uploadPreparedFiles(result.files);
            if (selectionKey !== lastSelectionKey) {
                return;
            }

            stagedUploads = uploaded.map((item) => ({
                path: item.path,
                url: resolveMediaUrl(item.path),
                name: item.name || (item.path ? item.path.split('/').pop() : 'Ảnh mới'),
                size: item.size || 0
            }));

            syncHiddenUploadedPaths();
            renderNewPreviewFromUploads(stagedUploads);
            galleryInput.value = '';
            compressedFilesCache = [];

            setUploadStatus(
                `Đã chuẩn bị ${stagedUploads.length} ảnh: ${formatBytes(result.originalTotal)} → ${formatBytes(result.compressedTotal)}. Khi bấm Lưu sẽ nhanh hơn nhiều.`,
                'success'
            );
        } catch (error) {
            console.error(error);
            stagedUploads = [];
            syncHiddenUploadedPaths();

            if (window.DataTransfer) {
                const dt = new DataTransfer();
                compressedFilesCache.forEach(file => dt.items.add(file));
                galleryInput.files = dt.files;
                renderNewPreviewFromFiles(Array.from(galleryInput.files || []));
            }

            setUploadStatus('Tải nền không thành công. Bạn vẫn có thể bấm Lưu để upload theo cách cũ.', 'error');
        } finally {
            isCompressing = false;
            isUploading = false;
            setSubmitBusyState();
        }
    }

    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach((input) => {
        input.addEventListener('input', function () {
            this.value = formatMoney(this.value);
        });

        input.value = formatMoney(input.value);
    });

    if (categorySelect) {
        categorySelect.addEventListener('change', syncTypeOptions);
        syncTypeOptions();
    }

    document.querySelectorAll('.remove-image-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', syncExistingImageControls);
    });

    syncExistingImageControls();

    if (uploadedGalleryPathsInput) {
        try {
            const initialPaths = JSON.parse(uploadedGalleryPathsInput.value || '[]');
            stagedUploads = Array.isArray(initialPaths)
                ? initialPaths.map((path) => ({
                    path,
                    url: resolveMediaUrl(path),
                    name: String(path).split('/').pop() || 'Ảnh mới',
                    size: 0
                }))
                : [];
        } catch (error) {
            stagedUploads = [];
        }

        syncHiddenUploadedPaths();
        if (stagedUploads.length) {
            renderNewPreviewFromUploads(stagedUploads);
            setUploadStatus('Đã khôi phục ảnh mới đã chọn trước đó.', 'success');
        }
    }

    if (galleryInput) {
        galleryInput.addEventListener('change', async function () {
            await prepareImagesNow(this.files);
        });
    }

    if (form && galleryInput) {
        form.addEventListener('submit', function (e) {
            if (isCompressing || isUploading) {
                e.preventDefault();
                setUploadStatus('Ảnh vẫn đang được xử lý. Đợi xong rồi bấm Lưu.', 'error');
                return;
            }

            const hiddenPaths = (() => {
                try {
                    const parsed = JSON.parse((uploadedGalleryPathsInput && uploadedGalleryPathsInput.value) || '[]');
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            })();

            const files = Array.from(galleryInput.files || []);
            if (!hiddenPaths.length && files.length && window.DataTransfer && compressedFilesCache.length) {
                const dt = new DataTransfer();
                compressedFilesCache.forEach(file => dt.items.add(file));
                galleryInput.files = dt.files;
            }

            if (hiddenPaths.length) {
                galleryInput.disabled = true;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Đang lưu sản phẩm...';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>