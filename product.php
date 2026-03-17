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

<style>
/* ==========================================================================
   CSS CHO TRANG CHI TIẾT SẢN PHẨM (NÊN CHUYỂN VÀO STYLE.CSS NẾU CẦN)
   ========================================================================== */
.breadcrumbs {
    padding: 15px 0;
    font-size: 14px;
    color: var(--text-muted, #6b7280);
    margin-bottom: 20px;
    border-bottom: 1px solid var(--line-light, #e5e7eb);
}
.breadcrumbs a {
    color: var(--primary-color, #000);
    font-weight: 500;
}
.breadcrumbs a:hover {
    text-decoration: underline;
}

.detail-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    margin-bottom: 60px;
}

@media (min-width: 992px) {
    .detail-layout {
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: start;
    }
}

/* Khối Hình Ảnh */
/* Khối Hình Ảnh */
.detail-image-wrap {
    position: relative; /* Mặc định trên điện thoại không dính */
    z-index: 10;
}

@media (min-width: 992px) {
    .detail-layout {
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: start; /* Quan trọng: giúp sticky chạy đúng giới hạn trên máy tính */
    }
    
    .detail-image-wrap {
        position: sticky;
        top: 80px; /* Chỉ dính khi cuộn trên màn hình máy tính */
    }
}

.detail-image-shell {
    background: var(--bg-white, #fff);
    border-radius: var(--radius-lg, 12px);
    overflow: hidden;
    border: 1px solid var(--line-light, #e5e7eb);
    margin-bottom: 15px;
    position: relative;
    cursor: zoom-in;
    /* Loại bỏ aspect-ratio để khung tự động ôm sát ảnh */
    display: flex;
    align-items: center;
    justify-content: center;
}

.detail-main-image {
    width: 100%;
    height: auto; /* Chiều cao tự động để giữ nguyên tỉ lệ gốc của ảnh */
    max-height: 80vh; /* Giới hạn chiều cao tối đa để ảnh không bị quá lớn trên desktop */
    object-fit: contain; /* Contain giúp ảnh luôn hiển thị trọn vẹn, không bị cắt xén */
    transition: transform 0.4s ease, opacity 0.2s ease;
}

.detail-image-shell:hover .detail-main-image {
    transform: scale(1.05); /* Zoom nhẹ khi hover mượt hơn */
}

.thumb-grid {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 8px;
    scrollbar-width: thin; /* Firefox */
}
.thumb-grid::-webkit-scrollbar {
    height: 6px;
}
.thumb-grid::-webkit-scrollbar-thumb {
    background: var(--line-strong, #d1d5db);
    border-radius: 4px;
}

.detail-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    opacity: 0.6;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.detail-thumb:hover,
.detail-thumb.active {
    opacity: 1;
    border-color: var(--primary-color, #000);
}

/* Khối Thông Tin */
.detail-box-pro {
    display: flex;
    flex-direction: column;
}

.meta-row-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.meta-pill {
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    background: var(--secondary-color, #f3f4f6);
    color: var(--text-main, #1f2440);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.meta-pill-accent {
    background: #fee2e2;
    color: #ef4444;
}

.detail-box-pro h1 {
    font-size: 26px;
    line-height: 1.3;
    margin-bottom: 8px;
    color: var(--text-main, #1f2440);
    font-weight: 700;
}

.detail-code {
    font-size: 14px;
    color: var(--text-muted, #6b7280);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-code::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    background: var(--text-muted);
    border-radius: 50%;
}

.detail-price-stack {
    display: flex;
    align-items: baseline;
    gap: 15px;
    margin-bottom: 25px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border-left: 4px solid var(--danger-color, #ef4444);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.detail-price-stack .price-sale {
    font-size: 32px;
    font-weight: 800;
    color: var(--danger-color, #ef4444);
    letter-spacing: -0.5px;
}

.detail-price-stack .price-original {
    font-size: 18px;
    color: var(--text-muted, #6b7280);
    text-decoration: line-through;
    font-weight: 500;
}

.lead-text {
    font-size: 16px;
    line-height: 1.6;
    color: var(--text-main, #374151);
    margin-bottom: 30px;
}

/* Lưới Thông số */
.spec-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.spec-item {
    background: var(--bg-white, #fff);
    padding: 14px 16px;
    border: 1px solid var(--line-light, #e5e7eb);
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    transition: border-color 0.2s;
}

.spec-item:hover {
    border-color: var(--line-strong);
}

.spec-label {
    font-size: 12px;
    color: var(--text-muted, #6b7280);
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.spec-item strong {
    font-size: 15px;
    color: var(--text-main, #111827);
}

.full-width-spec {
    grid-column: 1 / -1;
}

/* Nút bấm mua hàng */
.detail-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 10px;
    margin-bottom: 40px;
}

.btn-big {
    padding: 16px 24px;
    font-size: 16px;
    font-weight: 700;
    border-radius: 10px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-zalo {
    background-color: #0068ff;
    border-color: #0068ff;
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 104, 255, 0.2);
}

.btn-zalo:hover {
    background-color: #0052cc;
    border-color: #0052cc;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 104, 255, 0.3);
}

/* Chi tiết mô tả */
.description-box {
    border-top: 1px solid var(--line-light, #e5e7eb);
    padding-top: 35px;
}

.description-box h3 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--text-main);
    display: inline-block;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 8px;
}

.description-box p {
    font-size: 16px;
    line-height: 1.8;
    color: var(--text-main, #374151);
}

@media (max-width: 768px) {
    .detail-box-pro h1 { font-size: 22px; }
    .detail-price-stack { padding: 15px; gap: 10px; }
    .detail-price-stack .price-sale { font-size: 26px; }
    .detail-price-stack .price-original { font-size: 16px; }
    .spec-grid { gap: 10px; }
    .spec-item { padding: 12px; }
}
</style>

<div class="container">
    <div class="breadcrumbs">
        <a href="<?= BASE_URL ?>/index.php">Trang chủ</a> / <?= e($product['product_name']) ?>
    </div>

    <section class="detail-layout">
        <div class="detail-image-wrap">
            <div class="detail-image-shell">
                <div class="detail-slider" id="detailSlider">
                    <img
                        id="mainDetailImage"
                        class="detail-main-image"
                        src="<?= e($galleryImages[0]) ?>"
                        alt="<?= e($product['product_name']) ?>"
                        loading="eager"
                        decoding="async"
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
                            loading="lazy"
                        >
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-box detail-box-pro">
            <div class="meta-row-wrap">
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
            <div class="detail-code">Mã SP: <?= e($product['product_code']) ?></div>

            <div class="detail-price-stack">
                <?php if (!empty($product['sale_price'])): ?>
                    <div class="price-sale"><?= format_price($product['sale_price']) ?></div>
                    <div class="price-original"><?= format_price($product['original_price']) ?></div>
                <?php else: ?>
                    <div class="price-sale"><?= format_price($product['original_price']) ?></div>
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
                <!-- <div class="spec-item">
                    <span class="spec-label">Tình trạng kho</span>
                    <strong style="color: <?= ((int)$product['quantity'] > 0) ? 'inherit' : 'var(--danger-color)' ?>;">
                        <?= ((int)$product['quantity'] > 0) ? 'Còn ' . (int)$product['quantity'] . ' sản phẩm' : 'Hết hàng' ?>
                    </strong>
                </div> -->
            </div>

            <div class="detail-actions">
                <a class="btn btn-big btn-zalo" target="_blank" href="<?= e($buyLink) ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    Mua ngay qua Zalo
                </a>
                <a class="btn btn-light btn-big" href="<?= BASE_URL ?>/index.php">Quay lại gian hàng</a>
            </div>

            <div class="description-box">
                <h3>Mô tả chi tiết</h3>
                <p><?= nl2br(e($product['information'] ?: 'Chưa có thông tin chi tiết.')) ?></p>
            </div>
        </div>
    </section>
</div>

<?php if (count($galleryImages) > 1): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const images = <?= json_encode(array_values($galleryImages), JSON_UNESCAPED_SLASHES) ?>;
    const mainImage = document.getElementById('mainDetailImage');
    const thumbs = document.querySelectorAll('.detail-thumb');
    const imageShell = document.querySelector('.detail-image-shell');

    let currentIndex = 0;
    let sliderInterval = null;
    const intervalTime = 4000;

    function showImage(index) {
        currentIndex = index;
        
        mainImage.style.opacity = '0.5';
        setTimeout(() => {
            mainImage.src = images[index];
            mainImage.style.opacity = '1';
        }, 200);

        thumbs.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
            if(i === index) {
                thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        });
    }

    function startAutoSlide() {
        if (!sliderInterval) {
            sliderInterval = setInterval(() => {
                const nextIndex = (currentIndex + 1) % images.length;
                showImage(nextIndex);
            }, intervalTime);
        }
    }

    function stopAutoSlide() {
        clearInterval(sliderInterval);
        sliderInterval = null;
    }

    function resetAutoSlide() {
        stopAutoSlide();
        startAutoSlide();
    }

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', function () {
            if(currentIndex !== index) {
                showImage(index);
                resetAutoSlide();
            }
        });
    });

    imageShell.addEventListener('mouseenter', stopAutoSlide);
    imageShell.addEventListener('mouseleave', startAutoSlide);
    imageShell.addEventListener('touchstart', stopAutoSlide, {passive: true});
    imageShell.addEventListener('touchend', startAutoSlide, {passive: true});

    startAutoSlide();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>