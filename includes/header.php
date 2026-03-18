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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">

    <style>
        /* Nút menu mobile */
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--primary-color, #000);
            cursor: pointer;
            padding: 4px;
        }

        /* Ẩn hoàn toàn tab bar cũ */
        .tab-bar-mobile {
            display: none !important;
        }

        /* Nút nhạc nổi */
        .floating-music-toggle {
            position: fixed;
            right: 18px;
            bottom: 88px; /* nằm phía trên nút Giới thiệu shop */
            z-index: 999;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            border-radius: 999px;
            padding: 12px 16px;
            background: rgba(17, 24, 39, 0.92);
            color: #fff;
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.22);
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            transition: transform 0.2s ease, background 0.2s ease, opacity 0.2s ease;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .floating-music-toggle:hover {
            transform: translateY(-2px);
        }

        .floating-music-toggle:active {
            transform: translateY(0);
        }

        .floating-music-toggle svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .floating-music-toggle .music-icon-off {
            display: none;
        }

        .floating-music-toggle.is-playing {
            background: rgba(0, 0, 0, 0.92);
        }

        .floating-music-toggle.is-playing .music-icon-on {
            display: none;
        }

        .floating-music-toggle.is-playing .music-icon-off {
            display: block;
        }

        .floating-music-toggle .music-toggle-text {
            line-height: 1;
            white-space: nowrap;
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

            body {
                padding-bottom: 0 !important;
            }

            .floating-music-toggle {
                right: 14px;
                bottom: 78px;
                padding: 11px 13px;
                border-radius: 16px;
            }

            .floating-music-toggle .music-toggle-text {
                display: none;
            }
        }

        @keyframes slideDownMenu {
            from {
                opacity: 0;
                transform: translateY(-10px);
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
                style="border-radius: 12px; object-fit: cover;"
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