<?php
require_once __DIR__ . '/includes/functions.php';

function render_product_cards(array $products): string
{
    ob_start();
    foreach ($products as $index => $product):
        $productUrl = BASE_URL . '/product.php?id=' . (int)$product['id'];
        $imageUrl   = e(resolve_media_url($product['thumbnail']));
        $imageAlt   = e($product['product_name']);
        $isPriority = $index < 4;
        ?>
        <article class="product-card-pro">
            <a class="product-image-wrap" href="<?= $productUrl ?>">
                <img
                    src="<?= $imageUrl ?>"
                    alt="<?= $imageAlt ?>"
                    loading="<?= $isPriority ? 'eager' : 'lazy' ?>"
                    fetchpriority="<?= $isPriority ? 'high' : 'auto' ?>"
                    decoding="async"
                    width="600"
                    height="750"
                >
                <span class="product-badge"><?= e($product['category_name'] ?: 'Chưa phân loại') ?></span>
            </a>

            <div class="product-card-content">
                <div class="product-top">
                    <div class="product-category">
                        <?= e($product['category_name'] ?: 'Danh mục') ?>
                        •
                        <?= e($product['product_type_name'] ?: 'Loại đang cập nhật') ?>
                        •
                        <?= e($product['gender'] ?: 'Unisex') ?>
                    </div>

                    <h3 class="product-title">
                        <a href="<?= $productUrl ?>">
                            <?= e($product['product_name']) ?>
                        </a>
                    </h3>

                    <div class="product-code">Mã SP: <?= e($product['product_code']) ?></div>
                </div>

                <div class="product-bottom">
                    <div class="price-stack">
                        <?php if (!empty($product['sale_price'])): ?>
                            <div class="price-old"><?= format_price($product['original_price']) ?></div>
                            <div class="price"><?= format_price($product['sale_price']) ?></div>
                        <?php else: ?>
                            <div class="price"><?= format_price($product['original_price']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="card-actions">
                        <a class="btn btn-light" href="<?= $productUrl ?>">Xem chi tiết</a>
                        <a class="btn" target="_blank" href="<?= e(ZALO_LINK) ?>">Mua qua Zalo</a>
                    </div>
                </div>
            </div>
        </article>
    <?php endforeach;

    if (empty($products)): ?>
        <div class="empty-state">
            <h3>Chưa có sản phẩm nào</h3>
            <p>Hiện chưa có sản phẩm phù hợp với bộ lọc bạn chọn. Vui lòng thử lại.</p>
        </div>
    <?php endif;

    return ob_get_clean();
}

$priceRange = trim($_GET['price_range'] ?? '');

$priceMin = null;
$priceMax = null;

switch ($priceRange) {
    case 'under_200':
        $priceMin = null;
        $priceMax = 200000;
        break;
    case '200_500':
        $priceMin = 200000;
        $priceMax = 500000;
        break;
    case '500_1000':
        $priceMin = 500000;
        $priceMax = 1000000;
        break;
    case 'over_1000':
        $priceMin = 1000000;
        $priceMax = null;
        break;
}

$filters = [
    'category_id' => (isset($_GET['category']) && $_GET['category'] !== '') ? (int)$_GET['category'] : null,
    'type_id'     => (isset($_GET['type']) && $_GET['type'] !== '') ? (int)$_GET['type'] : null,
    'gender'      => (isset($_GET['gender']) && $_GET['gender'] !== '') ? trim($_GET['gender']) : null,
    'price_min'   => $priceMin,
    'price_max'   => $priceMax,
    'q'           => trim($_GET['q'] ?? ''),
];

$products = get_products($filters);
$categories = get_categories();
$productTypes = get_product_types();

$visibleProductTypes = array_filter($productTypes, function ($type) use ($filters) {
    if (empty($filters['category_id'])) {
        return true;
    }
    return (int)$type['category_id'] === (int)$filters['category_id'];
});

$productTypesForJs = array_map(function ($type) {
    return [
        'id' => (int)$type['id'],
        'name' => $type['name'],
        'category_id' => (int)$type['category_id'],
    ];
}, $productTypes);

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'count' => count($products),
        'html'  => render_product_cards($products),
    ]);
    exit;
}

$brandLogoUrl    = resolve_media_url('uploads/logo.jpg');
$heroBannerUrl   = resolve_media_url('uploads/logoduongmotmi.jpg');
$tiktokIconUrl   = resolve_media_url('uploads/tt.png');
$facebookIconUrl = resolve_media_url('uploads/fb.png');
$instagramIconUrl = resolve_media_url('uploads/ig.png');
$zaloIconUrl     = resolve_media_url('uploads/zl.png');
$pageTitle = 'Trang chủ';
require_once __DIR__ . '/includes/header.php';

// CỜ HIỂN THỊ POPUP: Chỉ hiện khi người dùng KHÔNG sử dụng bất kỳ filter nào (Trang chủ thuần)
$isFiltering = (!empty($filters['category_id']) || !empty($filters['type_id']) || !empty($filters['gender']) || !empty($filters['price_max']) || !empty($filters['price_min']) || !empty($filters['q']));
$showPopup = !$isFiltering;
?>

<style>
/* =========================================================
   CSS RIÊNG CHO TRANG INDEX TỐI ƯU GỌN NHẸ
   ========================================================= */
.intro-open-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--primary-color);
    color: var(--bg-white);
    border: none;
    padding: 12px 20px;
    border-radius: var(--radius-pill);
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 100;
    transition: transform 0.2s;
}

.intro-open-btn:hover {
    transform: scale(1.05);
}

body.popup-open {
    overflow: hidden;
}

.store-intro-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;    
    justify-content: center; 
    z-index: var(--z-index-modal);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.store-intro-overlay.show {
    opacity: 1;
    visibility: visible;
}

.store-intro-modal {
    position: relative;
    background: var(--bg-white);
    width: 900px;
    max-width: 90%;
    max-height: 85vh; 
    border-radius: 20px;
    overflow-y: auto; 
    transform: scale(0.95) translateY(20px);
    transition: transform 0.3s ease;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.store-intro-overlay.show .store-intro-modal {
    transform: scale(1) translateY(0);
}

.popup-close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 36px;
    height: 36px;
    background: rgba(0, 0, 0, 0.1);
    border: none;
    border-radius: 50%;
    font-size: 24px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: background 0.2s;
}

.popup-close-btn:hover {
    background: rgba(0, 0, 0, 0.2);
}

.hero-brand-layout-home {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #f8fafc;
}

.hero-brand-content {
    padding: 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.hero-brand-content h1 {
    font-size: 32px;
    line-height: 1.2;
    margin-bottom: 16px;
    color: var(--primary-color);
}

.hero-brand-content p {
    font-size: 16px;
    color: var(--text-muted);
    margin-bottom: 24px;
}

.hero-feature-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
}

.hero-feature-tags span {
    background: #e2e8f0;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}

.hero-socials-home {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.social-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--bg-white);
    padding: 12px;
    border-radius: 12px;
    border: 1px solid var(--line-light);
    transition: transform 0.2s, box-shadow 0.2s;
}

.social-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-color: var(--line-strong);
}

.social-icon img {
    border-radius: 6px;
}

.social-text {
    display: flex;
    flex-direction: column;
}

.social-text strong {
    font-size: 14px;
    color: var(--text-main);
}

.social-text span {
    font-size: 12px;
    color: var(--text-muted);
}

.hero-brand-banner {
    height: 100%;
}

.hero-brand-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Ẩn Filter button Desktop */
.mobile-filter-toggle {
    display: none;
    width: 100%;
    margin-bottom: 15px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #fff;
    border: 1px solid var(--line-strong);
    color: var(--text-main);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    font-weight: 600;
    border-radius: var(--radius-md);
}

.mobile-filter-toggle svg {
    transition: transform 0.3s ease;
}

.mobile-filter-toggle.is-open .chevron {
    transform: rotate(180deg);
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media screen and (max-width: 768px) {
    /* Popup Mobile */
    .store-intro-modal {
        width: 95% !important;
        max-height: 85vh !important;
        padding: 0; 
        border-radius: 16px;
    }

    .hero-brand-layout-home {
        display: flex !important;
        flex-direction: column-reverse; 
        background: var(--bg-white);
    }

    .hero-brand-content {
        padding: 20px 15px !important;
    }

    .hero-brand-content h1 {
        font-size: 22px !important;
    }

    .hero-brand-content p {
        font-size: 14px;
        margin-bottom: 15px;
    }

    .hero-feature-tags {
        margin-bottom: 20px;
    }

    .hero-feature-tags span {
        font-size: 12px;
        padding: 4px 8px;
    }

    .hero-socials-home {
        grid-template-columns: 1fr; 
    }

    .hero-brand-banner img {
        height: 250px; 
    }
    
    .popup-close-btn {
        background: rgba(255, 255, 255, 0.8);
        color: var(--primary-color);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* Logic bật tắt Filter Mobile */
    .mobile-filter-toggle {
        display: flex;
    }

    .filter-panel {
        display: none; 
        margin-bottom: 15px;
        padding: 15px;
    }

    .filter-panel.show-on-mobile {
        display: block;
        animation: slideDown 0.3s ease forwards;
    }

    .filter-grid {
        grid-template-columns: 1fr; 
        gap: 12px;
    }
    
    .filter-field label {
        margin-bottom: 4px;
    }
    
    .filter-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-actions .btn {
        width: 100%;
    }
}
</style>

<?php if ($showPopup): ?>
<div class="store-intro-overlay" id="storeIntroPopup" aria-hidden="true">
    <div class="store-intro-modal" role="dialog" aria-modal="true" aria-labelledby="storeIntroTitle">
        <button class="popup-close-btn" type="button" data-close-popup>&times;</button>

        <section class="hero-pro hero-pro-upgraded">
            <div class="hero-brand-layout hero-brand-layout-home">
                <div class="hero-brand-content">
                    <!-- <div class="hero-feature-tags">
                        <span>Thiết kế hiện đại</span>
                        <span>Lọc siêu nhanh</span>
                        <span>Mua qua Zalo tiện lợi</span>
                    </div> -->

                    <div class="hero-socials hero-socials-home">
                        <a class="social-card tiktok" href="https://www.tiktok.com/@duongmotmi2004?_r=1&_t=ZS-94ljEoOsGHP" target="_blank" rel="noopener noreferrer">
                            <div class="social-icon icon-image">
                                <img src="<?= e($tiktokIconUrl) ?>" alt="TikTok" width="24" height="24" loading="lazy" decoding="async">
                            </div>
                            <div class="social-text">
                                <strong>TikTok</strong>
                                <span>@duongmotmi2004</span>
                            </div>
                        </a>

                        <a class="social-card facebook" href="https://www.facebook.com/share/18LTswFoe7/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer">
                            <div class="social-icon icon-image">
                                <img src="<?= e($facebookIconUrl) ?>" alt="Facebook" width="24" height="24" loading="lazy" decoding="async">
                            </div>
                            <div class="social-text">
                                <strong>Facebook</strong>
                                <span>Liên hệ mua hàng</span>
                            </div>
                        </a>

                        <a class="social-card instagram" href="https://www.instagram.com/giuong_tung/" target="_blank" rel="noopener noreferrer">
                            <div class="social-icon icon-image">
                                <img src="<?= e($instagramIconUrl) ?>" alt="Instagram" width="24" height="24" loading="lazy" decoding="async">
                            </div>
                            <div class="social-text">
                                <strong>Instagram</strong>
                                <span>@giuong_tung</span>
                            </div>
                        </a>

                        <a class="social-card zalo" href="<?= e(ZALO_LINK) ?>" target="_blank" rel="noopener noreferrer">
                            <div class="social-icon icon-image">
                                <img src="<?= e($zaloIconUrl) ?>" alt="Zalo" width="24" height="24" loading="lazy" decoding="async">
                            </div>
                            <div class="social-text">
                                <strong>Zalo</strong>
                                <span>Liên hệ mua hàng</span>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="hero-brand-banner">
                    <img
                        src="<?= e($heroBannerUrl) ?>"
                        alt="Duong Mot Mi SHOP"
                        width="900"
                        height="900"
                        loading="eager"
                        decoding="async"
                    >
                </div>
            </div>
        </section>
    </div>
</div>
<?php endif; ?>

<button class="intro-open-btn" type="button" id="introOpenBtn">Giới thiệu shop</button>

<section class="shop-filter-wrap">
    <div class="category-pills">
        <a class="pill category-filter <?= !$filters['category_id'] ? 'active' : '' ?>"
           href="<?= BASE_URL ?>/index.php"
           data-category="">
            Tất cả
        </a>

        <?php foreach ($categories as $cat): ?>
            <a class="pill category-filter <?= $filters['category_id'] === (int)$cat['id'] ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/index.php?category=<?= (int)$cat['id'] ?>"
               data-category="<?= (int)$cat['id'] ?>">
                <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <button type="button" class="btn btn-outline mobile-filter-toggle" id="mobileFilterToggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
        </svg>
        Tùy chỉnh bộ lọc
        <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
    </button>

    <form id="filterForm" class="filter-panel" method="get" action="<?= BASE_URL ?>/index.php">
        <input type="hidden" name="category" id="categoryInput" value="<?= $filters['category_id'] ?? '' ?>">

        <div class="filter-grid">
            <div class="filter-field filter-field-search">
                <label for="q">Tìm kiếm</label>
                <input
                    id="q"
                    type="text"
                    name="q"
                    value="<?= e($filters['q']) ?>"
                    placeholder="Tên sản phẩm, mã SP, loại..."
                >
            </div>

            <div class="filter-field">
                <label for="type">Loại</label>
                <select name="type" id="type">
                    <option value="">Tất cả loại</option>
                    <?php foreach ($visibleProductTypes as $type): ?>
                        <option value="<?= (int)$type['id'] ?>" <?= $filters['type_id'] === (int)$type['id'] ? 'selected' : '' ?>>
                            <?= e($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="gender">Giới tính</label>
                <select name="gender" id="gender">
                    <option value="">Tất cả</option>
                    <option value="Nam" <?= $filters['gender'] === 'Nam' ? 'selected' : '' ?>>Nam</option>
                    <option value="Nữ" <?= $filters['gender'] === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                    <option value="Unisex" <?= $filters['gender'] === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                </select>
            </div>

            <div class="filter-field">
                <label for="price_range">Khoảng giá</label>
                <select name="price_range" id="price_range">
                    <option value="">Tất cả mức giá</option>
                    <option value="under_200" <?= $priceRange === 'under_200' ? 'selected' : '' ?>>Dưới 200K</option>
                    <option value="200_500" <?= $priceRange === '200_500' ? 'selected' : '' ?>>200K - 500K</option>
                    <option value="500_1000" <?= $priceRange === '500_1000' ? 'selected' : '' ?>>500K - 1 Triệu</option>
                    <option value="over_1000" <?= $priceRange === 'over_1000' ? 'selected' : '' ?>>Hơn 1 Triệu</option>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <button class="btn" type="submit">Lọc sản phẩm</button>
            <button class="btn btn-light" type="button" id="resetFilter">Xóa bộ lọc</button>
        </div>
    </form>
</section>

<section class="section-head" id="product-list">
    <div class="section-note" id="productCount"><?= count($products) ?> sản phẩm</div>
</section>

<div class="product-grid-pro" id="productGrid">
    <?= render_product_cards($products) ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    const productGrid = document.getElementById('productGrid');
    const productCount = document.getElementById('productCount');
    const categoryInput = document.getElementById('categoryInput');
    const categoryFilters = document.querySelectorAll('.category-filter');
    const resetFilterBtn = document.getElementById('resetFilter');
    const searchInput = document.getElementById('q');
    const typeSelect = document.getElementById('type');
    const genderSelect = document.getElementById('gender');
    const priceRangeSelect = document.getElementById('price_range');
    const mobileFilterToggle = document.getElementById('mobileFilterToggle');
    const baseUrl = '<?= BASE_URL ?>/index.php';
    const allProductTypes = <?= json_encode($productTypesForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    let typingTimer = null;
    let activeController = null;
    let requestId = 0;
    let lastQuery = '';

    if (mobileFilterToggle) {
        mobileFilterToggle.addEventListener('click', function() {
            filterForm.classList.toggle('show-on-mobile');
            this.classList.toggle('is-open');
        });
    }

    function updateActiveCategory(categoryId) {
        categoryFilters.forEach(link => {
            const value = link.dataset.category || '';
            link.classList.toggle('active', value === (categoryId || ''));
        });
    }

    function renderTypeOptions(categoryId, selectedType = '') {
        const normalizedCategory = categoryId ? String(categoryId) : '';
        const normalizedSelectedType = selectedType ? String(selectedType) : '';

        const filteredTypes = !normalizedCategory
            ? allProductTypes
            : allProductTypes.filter(type => String(type.category_id) === normalizedCategory);

        typeSelect.innerHTML = '<option value="">Tất cả loại</option>';
        let hasSelectedType = false;

        filteredTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = String(type.id);
            option.textContent = type.name;

            if (String(type.id) === normalizedSelectedType) {
                option.selected = true;
                hasSelectedType = true;
            }
            typeSelect.appendChild(option);
        });

        if (normalizedSelectedType && !hasSelectedType) {
            typeSelect.value = '';
        }
    }

    function buildQueryFromForm() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            const normalizedValue = String(value).trim();
            if (normalizedValue !== '') {
                params.set(key, normalizedValue);
            }
        }
        return params;
    }

    function setLoadingState(isLoading) {
        productGrid.classList.toggle('is-loading', isLoading);
        productGrid.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    async function loadProducts(force = false) {
        const params = buildQueryFromForm();
        const queryString = params.toString();

        if (!force && queryString === lastQuery) {
            return;
        }

        const ajaxParams = new URLSearchParams(queryString);
        ajaxParams.set('ajax', '1');

        const browserUrl = queryString ? `${baseUrl}?${queryString}` : baseUrl;

        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();
        const currentRequestId = ++requestId;

        setLoadingState(true);

        try {
            const response = await fetch(`${baseUrl}?${ajaxParams.toString()}`, {
                signal: activeController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Lỗi fetch');

            const data = await response.json();

            if (currentRequestId !== requestId) return;

            productGrid.classList.add('is-swapping');

            requestAnimationFrame(() => {
                productGrid.innerHTML = data.html;
                productCount.textContent = `${data.count} sản phẩm`;
                history.replaceState(null, '', browserUrl);
                updateActiveCategory(categoryInput.value);
                lastQuery = queryString;

                requestAnimationFrame(() => {
                    productGrid.classList.remove('is-swapping');
                });
            });
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error(error);
        } finally {
            if (currentRequestId === requestId) {
                setLoadingState(false);
            }
        }
    }

    // LOGIC MỚI: KHI ĐỔI DANH MỤC, RESET TẤT CẢ FILTER KHÁC
    categoryFilters.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Lấy ID danh mục mới
            const newCategoryId = this.dataset.category || '';
            categoryInput.value = newCategoryId;
            
            // XÓA TRỐNG TOÀN BỘ CÁC TRƯỜNG LỌC KHÁC
            if (searchInput) searchInput.value = '';
            if (genderSelect) genderSelect.value = '';
            if (priceRangeSelect) priceRangeSelect.value = '';
            
            // Render lại options Loại theo danh mục mới và để trống (không chọn loại nào)
            renderTypeOptions(newCategoryId, '');
            
            updateActiveCategory(newCategoryId);
            
            // Load sản phẩm với force=true
            loadProducts(true);
            
            if (window.innerWidth <= 768) {
                document.getElementById('product-list').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadProducts(true);
        if (window.innerWidth <= 768) {
             filterForm.classList.remove('show-on-mobile');
             mobileFilterToggle.classList.remove('is-open');
        }
    });

    filterForm.querySelectorAll('select').forEach(field => {
        field.addEventListener('change', function () {
            loadProducts(true);
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                loadProducts(true);
            }, 300);
        });
    }

    resetFilterBtn.addEventListener('click', function () {
        categoryInput.value = '';
        if (searchInput) searchInput.value = '';
        if (typeSelect) typeSelect.value = '';
        if (genderSelect) genderSelect.value = '';
        if (priceRangeSelect) priceRangeSelect.value = '';

        updateActiveCategory('');
        renderTypeOptions('', '');
        loadProducts(true);
    });

    renderTypeOptions(categoryInput.value, '<?= (int)($filters['type_id'] ?? 0) ?>');
    updateActiveCategory(categoryInput.value);
    lastQuery = buildQueryFromForm().toString();

    // POPUP LOGIC
    const popup = document.getElementById('storeIntroPopup');
    const openPopupBtn = document.getElementById('introOpenBtn');
    const closePopupBtns = document.querySelectorAll('[data-close-popup]');

    function openPopup() {
        if (!popup) return;
        popup.classList.add('show');
        document.body.classList.add('popup-open');
    }

    function closePopup() {
        if (!popup) return;
        popup.classList.remove('show');
        document.body.classList.remove('popup-open');
    }

    if (openPopupBtn) {
        openPopupBtn.addEventListener('click', openPopup);
    }

    closePopupBtns.forEach(btn => {
        btn.addEventListener('click', closePopup);
    });

    if (popup) {
        popup.addEventListener('click', function (e) {
            if (e.target === popup) {
                closePopup();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePopup();
        }
    });

    // Chỉ hiện tự động nếu PHP cờ $showPopup = true
    <?php if ($showPopup): ?>
        const schedulePopup = () => {
            setTimeout(() => {
                if (!document.hidden) {
                    openPopup();
                }
            }, 800);
        };
        if ('requestIdleCallback' in window) {
            requestIdleCallback(schedulePopup, { timeout: 1500 });
        } else {
            window.addEventListener('load', schedulePopup, { once: true });
        }
    <?php endif; ?>
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>