</main>

<style>
/* ==========================================================================
   CSS CHO FOOTER CHUYÊN NGHIỆP
   ========================================================================== */
.site-footer {
    background-color: var(--bg-white, #ffffff);
    border-top: 1px solid var(--line-light, #e5e7eb);
    padding: 60px 0 20px;
    margin-top: 60px;
    color: var(--text-main, #1f2440);
}

.footer-top {
    display: grid;
    /* Tự động chia cột: Trên PC 4 cột, trên Tablet 2 cột, trên Mobile 1 cột */
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 40px;
    margin-bottom: 40px;
}

.footer-col h4 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-main);
}

.footer-col p {
    color: var(--text-muted, #6b7280);
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.footer-col ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-col ul li {
    margin-bottom: 12px;
}

.footer-col ul li a {
    color: var(--text-muted, #6b7280);
    font-size: 14px;
    transition: color 0.2s ease, padding-left 0.2s ease;
    display: inline-block;
}

.footer-col ul li a:hover {
    color: var(--primary-color, #000);
    padding-left: 4px; /* Hiệu ứng dịch mũi tên nhẹ khi hover */
}

/* Các icon mạng xã hội */
.footer-socials {
    display: flex;
    gap: 12px;
}

.footer-socials a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: var(--secondary-color, #f3f4f6);
    color: var(--text-main);
    transition: all 0.3s ease;
}

.footer-socials a:hover {
    background-color: var(--primary-color, #000);
    color: #fff;
    transform: translateY(-3px);
}

.footer-bottom {
    border-top: 1px solid var(--line-light, #e5e7eb);
    padding-top: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--text-muted, #6b7280);
}

/* Tối ưu khoảng cách trên Mobile */
@media (max-width: 768px) {
    .site-footer {
        padding: 40px 0 20px;
        margin-top: 40px;
        /* Phải cộng thêm khoảng trống cho Tab Bar Mobile (nếu có) để nội dung footer không bị che mất */
        padding-bottom: calc(20px + env(safe-area-inset-bottom) + 60px); 
    }
    .footer-top { gap: 30px; }
}
</style>

<footer class="site-footer">
    <div class="container">
        <div class="footer-top">
            
            <div class="footer-col">
                <h4>Duong Mot Mi SHOP</h4>
                <p>Khám phá phong cách thời trang trẻ trung, hiện đại. Chúng tôi cam kết mang đến cho bạn những sản phẩm chất lượng với dịch vụ chốt đơn nhanh chóng, tận tâm.</p>
                <div class="footer-socials">
                    <a href="https://www.facebook.com/share/18LTswFoe7/?mibextid=wwXIfr" target="_blank" title="Facebook">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                    </a>
                    <a href="https://www.instagram.com/giuong_tung/" target="_blank" title="Instagram">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                    </a>
                    <a href="<?= e(ZALO_LINK ?? '#') ?>" target="_blank" title="Zalo">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Về chúng tôi</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/index.php">Trang chủ</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#product-list">Tất cả sản phẩm</a></li>
                    <li><a href="#" id="footerIntroBtn">Giới thiệu shop</a></li>
                    <li><a href="<?= e(ZALO_LINK ?? '#') ?>" target="_blank">Liên hệ chốt đơn</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Chính sách</h4>
                <ul>
                    <li><a href="#">Hướng dẫn mua hàng</a></li>
                    <li><a href="#">Chính sách đổi trả</a></li>
                    <li><a href="#">Chính sách vận chuyển</a></li>
                    <li><a href="#">Bảo mật thông tin</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Thông tin liên hệ</h4>
                <ul style="color: var(--text-muted); font-size: 14px;">
                    <li style="margin-bottom: 12px;"><strong>Hotline/Zalo:</strong> 09xx.xxx.xxx (Cập nhật sau)</li>
                    <li style="margin-bottom: 12px;"><strong>Giờ làm việc:</strong> 08:00 - 22:00 (T2 - CN)</li>
                    <li><strong>Địa chỉ:</strong> (Thêm địa chỉ shop của bạn vào đây)</li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Duong Mot Mi SHOP. Tự hào thiết kế và vận hành.</p>
        </div>
    </div>
</footer>

<script>
// Thêm sự kiện cho nút "Giới thiệu shop" ở Footer để nó mở cái Popup Intro mà bạn đang dùng ở trang chủ
document.addEventListener('DOMContentLoaded', function() {
    const footerIntroBtn = document.getElementById('footerIntroBtn');
    const storeIntroPopup = document.getElementById('storeIntroPopup');
    
    if(footerIntroBtn && storeIntroPopup) {
        footerIntroBtn.addEventListener('click', function(e) {
            e.preventDefault();
            storeIntroPopup.classList.add('show');
            document.body.classList.add('popup-open');
        });
    }
});
</script>

</body>
</html>