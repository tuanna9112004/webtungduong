<?php
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = get_product($id);

if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}

$images = get_product_images($id);
$pageTitle = $product['product_name'];
require_once __DIR__ . '/includes/header.php';

$displayPrice = !empty($product['sale_price']) ? $product['sale_price'] : $product['original_price'];
$zaloText = rawurlencode(
    'Tôi muốn mua sản phẩm: ' . $product['product_name'] .
    ' - Mã: ' . $product['product_code'] .
    ' - Giá: ' . format_price($displayPrice)
);

$baseBuyLink = !empty($product['import_link']) ? $product['import_link'] : ZALO_LINK;
$buyLink = $baseBuyLink . (strpos($baseBuyLink, '?') !== false ? '&' : '?') . 'text=' . $zaloText;

$galleryImages = [];
if (!empty($product['thumbnail'])) {
    $galleryImages[] = resolve_media_url($product['thumbnail']);
}

foreach ($images as $img) {
    if (!empty($img['image_url'])) {
        $resolved = resolve_media_url($img['image_url']);
        if (!in_array($resolved, $galleryImages, true)) {
            $galleryImages[] = $resolved;
        }
    }
}

if (empty($galleryImages)) {
    $galleryImages[] = 'https://placehold.co/800x900?text=No+Image';
}
?>

<div class="breadcrumbs">
    <a href="<?= BASE_URL ?>/index.php">Trang chủ</a> / <?= e($product['product_name']) ?>
</div>

<section class="detail-layout">
    <div>
        <div class="detail-image-shell">
            <div class="detail-slider" id="detailSlider">
                <img
                    id="mainDetailImage"
                    class="detail-main-image"
                    src="<?= e($galleryImages[0]) ?>"
                    alt="<?= e($product['product_name']) ?>"
                >
            </div>
        </div>

        <?php if (count($galleryImages) > 1): ?>
            <div class="thumb-grid" id="thumbGrid">
                <?php foreach ($galleryImages as $index => $imgUrl): ?>
                    <img
                        class="detail-thumb <?= $index === 0 ? 'active' : '' ?>"
                        src="<?= e($imgUrl) ?>"
                        data-index="<?= $index ?>"
                        alt="Ảnh phụ <?= e($product['product_name']) ?>"
                    >
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-box detail-box-pro">
        <div class="meta-row meta-row-wrap">
            <span class="meta-pill">Danh mục: <?= e($product['category_name'] ?: 'Chưa phân loại') ?></span>
            <?php if (!empty($product['product_type_name'])): ?>
                <span class="meta-pill meta-pill-light">Loại: <?= e($product['product_type_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($product['condition_names'])): ?>
                <?php foreach (explode(', ', $product['condition_names']) as $conditionName): ?>
                    <span class="meta-pill meta-pill-accent"><?= e($conditionName) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h1><?= e($product['product_name']) ?></h1>
        <div class="product-code detail-code">Mã SP: <?= e($product['product_code']) ?></div>

      <div class="price-stack detail-price-stack">
    <?php if (!empty($product['sale_price'])): ?>
        <div class="price-old price-original same-price-size"><?= format_price($product['original_price']) ?></div>
        <div class="price price-sale same-price-size"><?= format_price($product['sale_price']) ?></div>
    <?php else: ?>
        <div class="price price-sale same-price-size"><?= format_price($product['original_price']) ?></div>
    <?php endif; ?>
</div>

        <p class="lead-text">
            <?= nl2br(e($product['short_description'] ?: 'Chưa có mô tả ngắn.')) ?>
        </p>

        <div class="spec-grid">
            <div class="spec-item">
                <span class="spec-label">Giới tính</span>
                <strong><?= e($product['gender'] ?: 'Đang cập nhật') ?></strong>
            </div>
            <div class="spec-item">
                <span class="spec-label">Chất liệu</span>
                <strong><?= e($product['material'] ?: 'Đang cập nhật') ?></strong>
            </div>
            <div class="spec-item">
                <span class="spec-label">Size</span>
                <strong><?= e($product['size'] ?: 'Đang cập nhật') ?></strong>
            </div>
            <div class="spec-item">
                <span class="spec-label">Phong cách</span>
                <strong><?= e($product['style_name'] ?: 'Đang cập nhật') ?></strong>
            </div>
            <div class="spec-item">
                <span class="spec-label">Màu sắc</span>
                <strong><?= e($product['color'] ?: 'Đang cập nhật') ?></strong>
            </div>
            <div class="spec-item">
                <span class="spec-label">Số lượng</span>
                <strong><?= (int)$product['quantity'] ?></strong>
            </div>
        </div>

        <div class="spec-grid spec-grid-bottom">
            <div class="spec-item full-width-spec">
                <span class="spec-label">Thông tin</span>
                <strong><?= e($product['information'] ?: 'Đang cập nhật') ?></strong>
            </div>
        </div>

        <div class="card-actions detail-actions">
            <a class="btn btn-big" target="_blank" href="<?= e($buyLink) ?>">Mua ngay qua Zalo</a>
            <a class="btn btn-light btn-big" href="<?= BASE_URL ?>/index.php">Quay lại gian hàng</a>
        </div>

        <div class="description-box">
            <h3>Mô tả chi tiết</h3>
            <p><?= nl2br(e($product['information'] ?: 'Chưa có thông tin chi tiết.')) ?></p>
        </div>
    </div>
</section>

<?php if (count($galleryImages) > 1): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const images = <?= json_encode(array_values($galleryImages), JSON_UNESCAPED_SLASHES) ?>;
    const mainImage = document.getElementById('mainDetailImage');
    const thumbs = document.querySelectorAll('.detail-thumb');

    let currentIndex = 0;
    let sliderInterval = null;

    function showImage(index) {
        currentIndex = index;
        mainImage.src = images[index];

        thumbs.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });
    }

    function startAutoSlide() {
        sliderInterval = setInterval(() => {
            const nextIndex = (currentIndex + 1) % images.length;
            showImage(nextIndex);
        }, 3000);
    }

    function resetAutoSlide() {
        clearInterval(sliderInterval);
        startAutoSlide();
    }

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', function () {
            showImage(index);
            resetAutoSlide();
        });
    });

    startAutoSlide();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
