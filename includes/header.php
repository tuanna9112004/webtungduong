<?php 
require_once __DIR__ . '/functions.php'; 

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
        /* CSS Nút 3 gạch (Mặc định ẩn trên máy tính) */
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--primary-color, #000);
            cursor: pointer;
            padding: 4px;
        }

        /* Ẩn hoàn toàn Tab Bar dưới đáy */
        .tab-bar-mobile {
            display: none !important;
        }

        @media (max-width: 768px) {
            .header-wrap {
                justify-content: space-between !important;
                position: relative;
            }
            
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Chuyển menu thành dạng Dropdown trên Mobile */
            .site-header .menu {
                display: none; 
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--bg-white, #fff);
                flex-direction: column;
                padding: 10px 15px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                border-top: 1px solid var(--line-light, #e5e7eb);
                gap: 15px;
            }

            .site-header .menu a {
                padding: 10px 5px;
                display: block;
                width: 100%;
                border-bottom: 1px solid #f3f4f6;
                font-size: 16px;
            }
            
            .site-header .menu a:last-child {
                border-bottom: none;
            }

            .site-header .menu.is-open {
                display: flex;
                animation: slideDownMenu 0.3s ease forwards;
            }

            /* Xóa khoảng trống thừa dưới đáy vì đã bỏ Tab Bar */
            body {
                padding-bottom: 0 !important; 
            }
        }

        @keyframes slideDownMenu {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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

        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Mở menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <nav class="menu" id="headerMenu">
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

<main class="container main-content">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const headerMenu = document.getElementById('headerMenu');

    if (mobileMenuToggle && headerMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            headerMenu.classList.toggle('is-open');
        });
        
        // Tự động đóng menu nếu người dùng click ra ngoài khu vực menu
        document.addEventListener('click', function(event) {
            if (!headerMenu.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                headerMenu.classList.remove('is-open');
            }
        });
    }
});
</script>