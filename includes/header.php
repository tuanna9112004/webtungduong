<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) : 'Shop quần áo'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
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
<main class="container main-content">