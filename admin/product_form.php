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
<div class="admin-topbar">
    <h1><?= $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm' ?></h1>
    <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">← Quay lại danh sách</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert error">
        <?= e(implode(' ', $errors)) ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-grid">
    <label>Tên sản phẩm
        <input type="text" name="product_name" value="<?= e($product['product_name']) ?>" required>
    </label>

    <label>Mã sản phẩm
        <input type="text" value="<?= e($product['product_code']) ?>" readonly>
        <small class="hint">Mã được tự sinh tự động khi thêm mới sản phẩm.</small>
    </label>

    <label>Danh mục <span class="required-mark">*</span>
        <select name="category_id" id="categorySelect" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Loại sản phẩm <span class="required-mark">*</span>
        <select name="product_type_id" id="productTypeSelect" required>
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
        <small class="hint">Sau khi chọn danh mục, hệ thống sẽ chỉ hiển thị các loại thuộc đúng danh mục đó.</small>
    </label>

    <label>Phong cách
        <select name="style_id">
            <option value="">-- Chọn phong cách --</option>
            <?php foreach ($styles as $style): ?>
                <option value="<?= (int)$style['id'] ?>" <?= (int)$product['style_id'] === (int)$style['id'] ? 'selected' : '' ?>><?= e($style['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Giới tính
        <select name="gender" required>
            <?php foreach (product_gender_options() as $gender): ?>
                <option value="<?= e($gender) ?>" <?= $product['gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Giá gốc
        <input
            type="text"
            name="original_price"
            class="money-input"
            inputmode="numeric"
            autocomplete="off"
            value="<?= e($formatPriceInput($product['original_price'])) ?>"
            required
        >
    </label>

    <label>Giá sale
        <input
            type="text"
            name="sale_price"
            class="money-input"
            inputmode="numeric"
            autocomplete="off"
            value="<?= e($formatPriceInput($product['sale_price'])) ?>"
        >
    </label>

    <label>Chất liệu
        <input type="text" name="material" value="<?= e($product['material']) ?>">
    </label>

    <label>Size
        <input type="text" name="size" placeholder="S, M, L, XL" value="<?= e($product['size']) ?>">
    </label>

    <label>Số lượng
        <input type="number" name="quantity" min="0" value="<?= e((string)$product['quantity']) ?>">
    </label>

    <label>Màu sắc
        <input type="text" name="color" placeholder="Đen, Trắng, Xám" value="<?= e($product['color']) ?>">
    </label>

    <label>Link nhập / liên hệ
        <input type="text" name="import_link" value="<?= e($product['import_link']) ?>" placeholder="https://zalo.me/...">
    </label>

    <label class="checkbox-inline">
        <input type="checkbox" name="is_active" value="1" <?= !empty($product['is_active']) ? 'checked' : '' ?>>
        <span>Hiển thị sản phẩm trên website</span>
    </label>

    <label class="full">Tình trạng sản phẩm
        <div class="checkbox-list">
            <?php if (empty($productConditions)): ?>
                <div class="hint">Chưa có tình trạng nào. Hãy thêm tại mục Quản lý tình trạng.</div>
            <?php else: ?>
                <?php foreach ($productConditions as $condition): ?>
                    <label class="checkbox-chip">
                        <input type="checkbox" name="condition_ids[]" value="<?= (int)$condition['id'] ?>" <?= in_array((int)$condition['id'], $selectedConditions, true) ? 'checked' : '' ?>>
                        <span><?= e($condition['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </label>

    <label class="full">Mô tả ngắn
        <textarea name="short_description" rows="3"><?= e($product['short_description']) ?></textarea>
    </label>

    <label class="full">Thông tin chi tiết
        <textarea name="information" rows="6"><?= e($product['information']) ?></textarea>
    </label>

    <label class="full">Ảnh sản phẩm
        <input type="file" name="gallery_files[]" accept="image/png,image/jpeg,image/webp" multiple <?= $isEdit ? '' : 'required' ?>>
        <small class="hint">Chọn nhiều ảnh cùng lúc. Ảnh đầu tiên bạn chọn sẽ tự động được dùng làm ảnh đại diện.</small>
    </label>

    <?php if (!empty($images)): ?>
        <div class="full existing-gallery">
            <h3>Ảnh hiện có</h3>
            <div class="existing-gallery-grid">
                <?php foreach ($images as $index => $image): ?>
                    <label class="existing-gallery-item">
                        <img src="<?= e(resolve_media_url($image['image_url'])) ?>" alt="Ảnh sản phẩm">
                        <div class="existing-gallery-meta">
                            <?php if ($index === 0): ?>
                                <span class="thumb-badge">Ảnh đại diện hiện tại</span>
                            <?php endif; ?>
                            <span class="hint">Tích để xoá ảnh này</span>
                            <input type="checkbox" name="remove_image_ids[]" value="<?= (int)$image['id'] ?>">
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="full card-actions">
        <button class="btn" type="submit">Lưu sản phẩm</button>
        <a class="btn btn-light" href="<?= BASE_URL ?>/admin/products.php">Hủy</a>
    </div>
</form>

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