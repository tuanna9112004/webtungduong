<?php
require_once __DIR__ . '/functions.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isHome = (
    strpos($currentUri, 'index.php') !== false ||
    $currentUri === '/' ||
    $currentUri === '/shop/'
);
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">

    <style>
        /* ==========================================================================
           LUXURY HEADER & FLOATING ELEMENTS STYLESHEET
           ========================================================================== */
        
        /* HEADER GLASSMORPHISM */
        .site-header {
            background-color: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 15px 0;
            transition: all 0.4s ease;
        }

        .header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            color: var(--primary-color, #111);
            text-decoration: none;
        }

        .logo-mark-img {
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .logo:hover .logo-mark-img {
            transform: scale(1.05) rotate(5deg);
        }

        .site-header .menu {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .site-header .menu a {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted, #8e8e9f);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .site-header .menu a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: var(--primary-color, #111);
            transition: width 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .site-header .menu a:hover {
            color: var(--primary-color, #111);
        }

        .site-header .menu a:hover::after {
            width: 100%;
        }

        /* Nút menu mobile */
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--primary-color, #111);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        
        .mobile-menu-toggle:hover {
            background: rgba(0,0,0,0.05);
        }

        /* Ẩn hoàn toàn tab bar cũ */
        .tab-bar-mobile {
            display: none !important;
        }

        /* FLOATING MUSIC TOGGLE - LUXURY GLASS */
        .floating-music-toggle {
            position: fixed;
            right: 25px;
            bottom: 100px;
            z-index: 999;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.85);
            color: var(--primary-color, #111);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
        }

        .floating-music-toggle:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            background: #ffffff;
        }

        .floating-music-toggle:active {
            transform: translateY(0) scale(0.95);
        }

        .floating-music-toggle svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            transition: transform 0.3s;
        }

        .floating-music-toggle .music-icon-off {
            display: none;
            color: var(--text-muted, #8e8e9f);
        }

        /* Trạng thái đang phát nhạc */
        .floating-music-toggle.is-playing {
            background: rgba(17, 17, 17, 0.9);
            color: #ffffff;
            border-color: rgba(0,0,0,0.5);
            box-shadow: 0 10px 30px rgba(17, 17, 17, 0.3);
        }

        .floating-music-toggle.is-playing .music-icon-on {
            display: none;
        }

        .floating-music-toggle.is-playing .music-icon-off {
            display: block;
            color: #ffffff;
        }

        /* Animation sóng âm khi nhạc phát */
        .music-waves {
            display: none;
            align-items: center;
            gap: 3px;
            height: 15px;
        }
        
        .floating-music-toggle.is-playing .music-waves {
            display: flex;
        }
        
        .floating-music-toggle.is-playing .music-icon-off {
            display: none; /* Ẩn icon off thay bằng sóng âm */
        }

        .wave {
            width: 3px;
            height: 100%;
            background-color: var(--danger-color, #ff3366);
            border-radius: 3px;
            animation: bounce 1.2s ease-in-out infinite;
        }

        .wave:nth-child(1) { animation-delay: 0.0s; }
        .wave:nth-child(2) { animation-delay: -0.2s; }
        .wave:nth-child(3) { animation-delay: -0.4s; }
        .wave:nth-child(4) { animation-delay: -0.6s; }

        @keyframes bounce {
            0%, 100% { transform: scaleY(0.3); opacity: 0.6; }
            50% { transform: scaleY(1); opacity: 1; }
        }

        .floating-music-toggle .music-toggle-text {
            line-height: 1;
            white-space: nowrap;
        }

        /* MOBILE OPTIMIZATION */
        @media (max-width: 768px) {
            .site-header {
                padding: 15px;
            }

            .header-wrap {
                justify-content: space-between !important;
                position: relative;
            }

            .logo span {
                font-size: 18px;
            }

            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .site-header .menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: rgba(255,255,255,0.95);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
                border-top: 1px solid rgba(0,0,0,0.05);
                border-radius: 0 0 24px 24px;
                gap: 0;
            }

            .site-header .menu a {
                padding: 15px 10px;
                display: block;
                width: 100%;
                border-bottom: 1px solid rgba(0,0,0,0.05);
                font-size: 15px;
            }

            .site-header .menu a:last-child {
                border-bottom: none;
            }

            .site-header .menu.is-open {
                display: flex;
                animation: slideDownMenu 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            }

            body {
                padding-bottom: 0 !important;
            }

            .floating-music-toggle {
                right: 20px;
                bottom: 90px;
                padding: 12px;
                border-radius: 50%; /* Nút tròn trên mobile */
                width: 45px;
                height: 45px;
                justify-content: center;
            }

            .floating-music-toggle .music-toggle-text {
                display: none;
            }
        }

        @keyframes slideDownMenu {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<?php if (!$isAdminPage): ?>
    <audio id="siteBgMusic" preload="auto" autoplay loop playsinline style="display:none;">
        <source src="/abc.mp3" type="audio/mpeg">
    </audio>

    <button
        class="floating-music-toggle"
        type="button"
        data-music-toggle
        aria-pressed="false"
        aria-label="Bật hoặc tắt nhạc nền"
        title="Bật / Tắt nhạc nền"
    >
        <svg class="music-icon-on" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
        </svg>

        <svg class="music-icon-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <line x1="23" y1="9" x2="17" y2="15"></line>
            <line x1="17" y1="9" x2="23" y2="15"></line>
        </svg>

        <div class="music-waves" aria-hidden="true">
            <div class="wave"></div>
            <div class="wave"></div>
            <div class="wave"></div>
            <div class="wave"></div>
        </div>

        <span class="music-toggle-text">Bật nhạc</span>
    </button>
<?php endif; ?>

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

        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Mở menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <nav class="menu" id="headerMenu">
            <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>
            <a href="<?= BASE_URL ?>/index.php#product-list">Sản phẩm</a>
            <a target="_blank" rel="noopener noreferrer" href="<?= e(ZALO_LINK) ?>">Zalo</a>
        </nav>
    </div>
</header>

<main class="container main-content">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const headerMenu = document.getElementById('headerMenu');

    if (mobileMenuToggle && headerMenu) {
        mobileMenuToggle.addEventListener('click', function () {
            headerMenu.classList.toggle('is-open');
        });

        document.addEventListener('click', function (event) {
            if (!headerMenu.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                headerMenu.classList.remove('is-open');
            }
        });
    }

    const audio = document.getElementById('siteBgMusic');
    if (!audio) return;

    const KEY_ENABLED = 'shop_bg_music_enabled_v3';
    const KEY_TIME = 'shop_bg_music_time_v3';

    if (localStorage.getItem(KEY_ENABLED) === null) {
        localStorage.setItem(KEY_ENABLED, '1');
    }

    function isEnabled() {
        return localStorage.getItem(KEY_ENABLED) === '1';
    }

    function setEnabled(value) {
        localStorage.setItem(KEY_ENABLED, value ? '1' : '0');
        updateMusicButtons();
    }

    function saveCurrentTime() {
        try {
            if (Number.isFinite(audio.currentTime)) {
                localStorage.setItem(KEY_TIME, String(audio.currentTime));
            }
        } catch (error) {}
    }

    function restoreCurrentTime() {
        const saved = parseFloat(localStorage.getItem(KEY_TIME) || '0');
        if (Number.isFinite(saved) && saved > 0) {
            try {
                audio.currentTime = saved;
            } catch (error) {}
        }
    }

    function updateMusicButtons() {
        const buttons = document.querySelectorAll('[data-music-toggle]');
        const isPlaying = !audio.paused && !audio.ended;

        buttons.forEach((button) => {
            const textNode = button.querySelector('.music-toggle-text');

            if (textNode) {
                textNode.textContent = isPlaying ? 'Tắt nhạc' : 'Bật nhạc';
            } else {
                button.textContent = isPlaying ? 'Tắt nhạc' : 'Bật nhạc';
            }

            button.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
            button.classList.toggle('is-playing', isPlaying);
        });
    }

    async function tryPlayMusic() {
        if (!isEnabled()) {
            updateMusicButtons();
            return false;
        }

        try {
            await audio.play();
            updateMusicButtons();
            return true;
        } catch (error) {
            updateMusicButtons();
            return false;
        }
    }

    function pauseMusic() {
        audio.pause();
        saveCurrentTime();
        updateMusicButtons();
    }

    function bindMusicButtons() {
        const buttons = document.querySelectorAll('[data-music-toggle]');

        buttons.forEach((button) => {
            if (button.dataset.musicBound === '1') return;
            button.dataset.musicBound = '1';

            button.addEventListener('click', async function () {
                if (!audio.paused) {
                    setEnabled(false);
                    pauseMusic();
                    return;
                }

                setEnabled(true);
                restoreCurrentTime();
                await tryPlayMusic();
            });
        });

        updateMusicButtons();
    }

    async function bootstrapAutoplay() {
        if (!isEnabled()) {
            updateMusicButtons();
            return;
        }

        restoreCurrentTime();
        await tryPlayMusic();
    }

    bindMusicButtons();
    bootstrapAutoplay();

    window.addEventListener('load', bootstrapAutoplay);

    window.addEventListener('pageshow', function () {
        bindMusicButtons();
        bootstrapAutoplay();
    });

    const resumeOnFirstInteraction = async function () {
        if (isEnabled() && audio.paused) {
            restoreCurrentTime();
            await tryPlayMusic();
        }
    };

    document.addEventListener('click', resumeOnFirstInteraction, { once: true, capture: true });
    document.addEventListener('touchstart', resumeOnFirstInteraction, { once: true, passive: true });
    document.addEventListener('keydown', resumeOnFirstInteraction, { once: true });

    audio.addEventListener('play', updateMusicButtons);
    audio.addEventListener('pause', updateMusicButtons);

    audio.addEventListener('timeupdate', function () {
        saveCurrentTime();
    });

    audio.addEventListener('ended', function () {
        localStorage.setItem(KEY_TIME, '0');
        updateMusicButtons();
    });

    window.addEventListener('beforeunload', saveCurrentTime);
    window.addEventListener('pagehide', saveCurrentTime);
});
</script>