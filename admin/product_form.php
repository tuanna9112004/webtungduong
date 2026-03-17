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
    $selectedConditions = array_values(array_unique(array_filter(array_map('intval', $_POST['condition_ids'] ?? []), fn($value) => $value > 0)));

    if ($data['product_name'] === '') {
        $errors[] = 'Vui lòng nhập tên sản phẩm.';
    }
    if ($data['category_id'] <= 0) {
        $errors[] = 'Vui lòng chọn danh mục.';
    }
    if ($data['product_type_id'] <= 0) {
        $errors[] = 'Vui lòng chọn loại sản phẩm.';
    }
    if ($data['category_id'] > 0 && $data['product_type_id'] > 0 && !product_type_exists_for_category($data['product_type_id'], $data['category_id'])) {
        $errors[] = 'Loại sản phẩm không thuộc danh mục đã chọn.';
    }
    if ($data['original_price'] <= 0) {
        $errors[] = 'Giá gốc phải lớn hơn 0.';
    }
    if ($data['sale_price'] !== null && $data['sale_price'] > 0 && $data['sale_price'] > $data['original_price']) {
        $errors[] = 'Giá sale không được lớn hơn giá gốc.';
    }

    $uploadedImages = handle_multiple_image_uploads($_FILES['gallery_files'] ?? null);

    if (!$isEdit && empty($uploadedImages)) {
        $errors[] = 'Khi thêm mới, bạn phải upload ít nhất 1 ảnh sản phẩm.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $existingImages = get_product_images($id);
            $existingImageUrls = array_column($existingImages, 'image_url');
            $removedImageIds = array_values(array_unique(array_filter(array_map('intval', $_POST['remove_image_ids'] ?? []), fn($value) => $value > 0)));

            if (!empty($removedImageIds)) {
                $placeholders = implode(',', array_fill(0, count($removedImageIds), '?'));
                $params = array_merge([$id], $removedImageIds);
                $stmt = db()->prepare("DELETE FROM product_images WHERE product_id = ? AND id IN ($placeholders)");
                $stmt->execute($params);
                $existingImages = get_product_images($id);
                $existingImageUrls = array_column($existingImages, 'image_url');
            }

            $galleryImages = !empty($uploadedImages)
                ? array_values(array_merge($uploadedImages, $existingImageUrls))
                : array_values($existingImageUrls);

            $thumbnail = !empty($galleryImages) ? $galleryImages[0] : null;

            $stmt = db()->prepare('UPDATE products SET product_name=?, category_id=?, product_type_id=?, style_id=?, gender=?, original_price=?, sale_price=?, material=?, size=?, information=?, short_description=?, quantity=?, color=?, import_link=?, thumbnail=?, is_active=? WHERE id=?');
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
            $thumbnail = $uploadedImages[0] ?? null;

            $stmt = db()->prepare('INSERT INTO products (product_name, product_code, category_id, product_type_id, style_id, gender, original_price, sale_price, material, size, information, short_description, quantity, color, import_link, thumbnail, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
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

            replace_product_gallery($newId, $uploadedImages);
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

/* Lưới Form: Mặc định 1 cột (Mobile), 2 cột (Desktop) */
.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 768px) {
    .form-grid { grid-template-columns: 1fr 1fr; }
    .col-full { grid-column: 1 / -1; }
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
}

.required-mark { color: var(--danger-color, #ef4444); }

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--line-strong, #d1d5db);
    border-radius: var(--radius-md, 8px);
    font-size: 14px;
    color: var(--text-main);
    background-color: var(--bg-white, #fff);
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}

.form-control:focus { border-color: var(--primary-color, #000); }
.form-control[readonly] { background-color: #f3f4f6; cursor: not-allowed; }

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

/* Custom Checkbox Tình Trạng */
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
    accent-color: var(--primary-color);
    cursor: pointer;
}

.checkbox-chip:hover { border-color: var(--line-strong); }

/* Checkbox hiển thị website */
.checkbox-inline {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-main);
    cursor: pointer;
    padding: 10px 15px;
    background: #f8fafc;
    border: 1px solid var(--line-light);
    border-radius: 8px;
}
.checkbox-inline input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color);
}

/* Thư viện ảnh */
.existing-gallery {
    margin-top: 10px;
    padding-top: 20px;
    border-top: 1px dashed var(--line-strong);
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
    width: 120px;
    position: relative;
    border: 1px solid var(--line-light);
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    cursor: pointer;
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
    border-top: 1px solid var(--line-light);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.thumb-badge {
    background: var(--primary-color);
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
}

/* Alerts & Buttons */
.alert { padding: 14px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
.alert.error { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--line-light);
}

@media (max-width: 768px) {
    .form-actions { flex-direction: column; }
    .form-actions .btn { width: 100%; }
}
</style>

<div class="container admin-wrapper">
    <div class="admin-header">
        <h1><?= $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm mới' ?></h1>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">← Hủy & Quay lại</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= e(implode(' ', $errors)) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card-box">
        <div class="form-grid">
            
            <div class="form-group col-full">
                <label>Tên sản phẩm <span class="required-mark">*</span></label>
                <input type="text" name="product_name" class="form-control" value="<?= e($product['product_name']) ?>" required placeholder="VD: Áo Thun Nam Có Cổ">
            </div>

            <div class="form-group">
                <label>Mã sản phẩm</label>
                <input type="text" class="form-control" value="<?= e($product['product_code']) ?>" readonly>
                <span class="hint">Hệ thống tự động tạo mã SP.</span>
            </div>

            <div class="form-group">
                <label>Danh mục <span class="required-mark">*</span></label>
                <select name="category_id" id="categorySelect" class="form-control" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Loại sản phẩm <span class="required-mark">*</span></label>
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
                <label>Phong cách</label>
                <select name="style_id" class="form-control">
                    <option value="">-- Chọn phong cách --</option>
                    <?php foreach ($styles as $style): ?>
                        <option value="<?= (int)$style['id'] ?>" <?= (int)$product['style_id'] === (int)$style['id'] ? 'selected' : '' ?>><?= e($style['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Giới tính <span class="required-mark">*</span></label>
                <select name="gender" class="form-control" required>
                    <?php foreach (product_gender_options() as $gender): ?>
                        <option value="<?= e($gender) ?>" <?= $product['gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
<br>
            <div class="form-group">
                <label>Giá gốc (VNĐ) (đây là giá ghạch) <span class="required-mark">*</span></label>
                <input type="text" name="original_price" class="form-control money-input" inputmode="numeric" autocomplete="off" value="<?= e($formatPriceInput($product['original_price'])) ?>" required>
            </div>

            <div class="form-group">
                <label>Giá khuyến mãi (VNĐ) (đây là giá bán)</label>
                <input type="text" name="sale_price" class="form-control money-input" inputmode="numeric" autocomplete="off" value="<?= e($formatPriceInput($product['sale_price'])) ?>">
                <span class="hint">Để trống nếu không Sale.</span>
            </div>

            <div class="form-group">
                <label>Chất liệu</label>
                <input type="text" name="material" class="form-control" value="<?= e($product['material']) ?>" placeholder="VD: Cotton, Kaki...">
            </div>

            <div class="form-group">
                <label>Kích thước (Size)</label>
                <input type="text" name="size" class="form-control" placeholder="VD: S, M, L, XL" value="<?= e($product['size']) ?>">
            </div>

            <div class="form-group">
                <label>Màu sắc</label>
                <input type="text" name="color" class="form-control" placeholder="VD: Đen, Trắng, Xám" value="<?= e($product['color']) ?>">
            </div>

            <div class="form-group">
                <label>Số lượng trong kho</label>
                <input type="number" name="quantity" class="form-control" min="0" value="<?= e((string)$product['quantity']) ?>">
            </div>

            <div class="form-group col-full">
    <label>Link Zalo nhập hàng / Nguồn / SĐT</label>
    <input 
        type="text" 
        name="import_link" 
        class="form-control" 
        value="<?= e($product['import_link']) ?>" 
        placeholder="Nhập link (https://...) hoặc Số điện thoại nguồn hàng"
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
                                <input type="checkbox" name="condition_ids[]" value="<?= (int)$condition['id'] ?>" <?= in_array((int)$condition['id'], $selectedConditions, true) ? 'checked' : '' ?>>
                                <span><?= e($condition['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group col-full">
                <label>Mô tả ngắn</label>
                <textarea name="short_description" class="form-control" rows="3" placeholder="Đoạn văn ngắn gọn giới thiệu điểm nổi bật của SP..."><?= e($product['short_description']) ?></textarea>
            </div>

            <div class="form-group col-full">
                <label>Thông tin chi tiết</label>
                <textarea name="information" class="form-control" rows="6" placeholder="Mô tả chi tiết về sản phẩm, hướng dẫn bảo quản, nguồn gốc..."><?= e($product['information']) ?></textarea>
            </div>

            <div class="form-group col-full">
                <label>Trạng thái hiển thị</label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($product['is_active']) ? 'checked' : '' ?>>
                    Hiển thị sản phẩm này trên gian hàng Website
                </label>
            </div>

            <div class="form-group col-full" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed var(--line-strong);">
                <label style="color: var(--primary-color); font-size: 15px;">Thư viện ảnh sản phẩm <span class="required-mark">*</span></label>
                <input type="file" name="gallery_files[]" accept="image/png,image/jpeg,image/webp" multiple <?= $isEdit ? '' : 'required' ?> style="margin-top: 10px; width: 100%;">
                <span class="hint">Bạn có thể chọn <strong>nhiều ảnh</strong> cùng lúc. Ảnh đầu tiên tải lên sẽ tự động làm <strong>Ảnh đại diện</strong> ngoài trang chủ. Hỗ trợ định dạng: JPG, PNG, WEBP.</span>
            </div>

            <?php if (!empty($images)): ?>
                <div class="col-full existing-gallery">
                    <h3>Quản lý ảnh hiện tại</h3>
                    <div class="existing-gallery-grid">
                        <?php foreach ($images as $index => $image): ?>
                            <label class="existing-gallery-item">
                                <img src="<?= e(resolve_media_url($image['image_url'])) ?>" alt="Ảnh SP">
                                <div class="existing-gallery-meta">
                                    <?php if ($index === 0): ?>
                                        <span class="thumb-badge">Ảnh chính</span>
                                    <?php endif; ?>
                                    <span class="hint" style="margin-top: 2px;">Tích để xoá</span>
                                    <input type="checkbox" name="remove_image_ids[]" value="<?= (int)$image['id'] ?>">
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <div class="form-actions">
            <button class="btn btn-big" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
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
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>