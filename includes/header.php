<?php 
require_once __DIR__ . '/functions.php'; 

// Lấy URI hiện tại để xử lý trạng thái Active cho Tab Bar
$currentUri = $_SERVER['REQUEST_URI'];
$isHome = (strpos($currentUri, 'index.php') !== false || $currentUri === '/' || $currentUri === '/shop/');
$isAdminPage = strpos($currentUri, '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($pageTitle) ? e($pageTitle) : 'Shop quần áo'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
    
    <style>
        /* CSS Ẩn menu cũ trên Mobile và hiển thị Tab Bar */
        @media (max-width: 768px) {
            .site-header .menu {
                display: none; /* Ẩn menu chữ trên header */
            }
            .site-header {
                justify-content: center; /* Đưa logo ra giữa trên mobile */
            }
            .header-wrap {
                justify-content: center;
            }
            body {
                /* Tạo khoảng trống dưới đáy để nội dung không bị Tab Bar đè lên */
                /* Cộng thêm phần safe-area cho các dòng iPhone tai thỏ */
                padding-bottom: calc(75px + env(safe-area-inset-bottom)) !important; 
            }
            .tab-bar-mobile {
                display: flex !important;
            }
        }

        /* Thiết kế Tab Bar dưới đáy chuẩn App */
        .tab-bar-mobile {
            display: none; /* Ẩn trên máy tính */
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            z-index: 9999;
            justify-content: space-around;
            align-items: center;
            /* Tối ưu cho màn hình iPhone tai thỏ/Dynamic Island + lề an toàn */
            padding: 8px 0 calc(8px + env(safe-area-inset-bottom)); 
            box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.04);
        }

        .tab-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            flex: 1;
            color: var(--muted, #8e8e93); /* Màu xám dịu chuẩn mobile */
            text-decoration: none;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            -webkit-tap-highlight-color: transparent; /* Bỏ viền xanh khi tap trên mobile */
        }

        .tab-item span {
            font-size: 11px;
            font-weight: 600;
        }

        .tab-item svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hiệu ứng khi chạm hoặc đang active */
        .tab-item:active svg {
            transform: scale(0.85); /* Hiệu ứng lún xuống khi bấm */
        }
        
        .tab-item.active {
            color: var(--primary-color, #000000); /* Chuyển sang màu đen hoặc màu thương hiệu của bạn */
        }

        /* Nút Zalo tách biệt màu sắc */
        .tab-item.tab-zalo {
            color: #0068ff;
        }
        .tab-item.tab-zalo:active {
            color: #0052cc;
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container header-wrap">
        <a class="logo" href="<?= BASE_URL ?>/index.php">
            <img
                class="logo-mark-img"
                src="<?= e(resolve_media_url('uploads/logo.jpg')) ?>"
                alt="Logo Duong Mot Mi SHOP"
                width="42"
                height="42"
                loading="eager"
                decoding="async"
                style="border-radius: 12px; object-fit: cover;"
            >
            <span>Duong Mot Mi SHOP</span>
        </a>

        <nav class="menu">
            <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>
            <a href="<?= BASE_URL ?>/index.php#product-list">Sản phẩm</a>
            <?php if (is_admin_logged_in()): ?>
                <a href="<?= BASE_URL ?>/admin/products.php">Quản trị</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/admin/login.php">Admin</a>
            <?php endif; ?>
            <a target="_blank" href="<?= e(ZALO_LINK) ?>">Zalo</a>
        </nav>
    </div>
</header>

<nav class="tab-bar-mobile">
    <a href="<?= BASE_URL ?>/index.php" class="tab-item <?= $isHome ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Trang chủ</span>
    </a>

    <a href="<?= BASE_URL ?>/index.php#product-list" class="tab-item">
        <svg viewBox="0 0 24 24">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Sản phẩm</span>
    </a>

    <a target="_blank" href="<?= e(ZALO_LINK) ?>" class="tab-item tab-zalo">
        <svg viewBox="0 0 24 24" stroke="currentColor">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
        </svg>
        <span>Zalo</span>
    </a>

    <?php if (is_admin_logged_in()): ?>
        <a href="<?= BASE_URL ?>/admin/products.php" class="tab-item <?= $isAdminPage ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            <span>Quản trị</span>
        </a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/admin/login.php" class="tab-item <?= $isAdminPage ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Admin</span>
        </a>
    <?php endif; ?>
</nav>

<main class="container main-content">