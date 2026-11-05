<?php
/**
 * Template Name: Trang Liên Hệ Biblio
 * Template for displaying the Contact page (slug: lien-he)
 */

get_header();

// Process contact form submission via POST
$form_sent = false;
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nb_contact_nonce']) && wp_verify_nonce($_POST['nb_contact_nonce'], 'nb_submit_contact_action')) {
    $name    = sanitize_text_field($_POST['contact_name'] ?? '');
    $phone   = sanitize_text_field($_POST['contact_phone'] ?? '');
    $email   = sanitize_email($_POST['contact_email'] ?? '');
    $topic   = sanitize_text_field($_POST['contact_topic'] ?? 'Hỗ trợ chung');
    $message = sanitize_textarea_field($_POST['contact_message'] ?? '');

    if (empty($name) || empty($phone) || empty($email) || empty($message)) {
        $form_error = 'Vui lòng điền đầy đủ tất cả các trường bắt buộc.';
    } elseif (!is_email($email)) {
        $form_error = 'Địa chỉ email không hợp lệ, vui lòng kiểm tra lại.';
    } else {
        // Send email to admin or log inquiry
        $to      = get_option('admin_email');
        $subject = "[Biblio Liên Hệ] $topic - từ $name";
        $body    = "Bạn nhận được yêu cầu liên hệ mới từ website Biblio:\n\n"
                 . "Họ tên: $name\n"
                 . "Số điện thoại: $phone\n"
                 . "Email: $email\n"
                 . "Chủ đề: $topic\n\n"
                 . "Nội dung:\n$message\n\n"
                 . "Thời gian gửi: " . current_time('mysql');
        $headers = ['Content-Type: text/plain; charset=UTF-8', "Reply-To: $name <$email>"];

        wp_mail($to, $subject, $body, $headers);
        if (is_user_logged_in() && function_exists('nbook_add_notification')) {
            nbook_add_notification(get_current_user_id(), 'Đã nhận phản hồi liên hệ', 'Biblio đã nhận được yêu cầu liên hệ của bạn và sẽ sớm phản hồi.', home_url('/lien-he/'), 'contact');
        }
        $form_sent = true;
    }
}
?>

<main id="primary" class="site-main nb-contact-page-main">

    <!-- ===================================================================
         1. HERO BANNER TRUNG TÂM HỖ TRỢ & LIÊN HỆ
         =================================================================== -->
    <section class="nb-contact-hero-banner" aria-label="Trung tâm liên hệ Biblio">
        <div class="nb-container">
            <div class="nb-contact-hero-content">
                <nav class="nb-contact-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
                    <span class="nb-bc-sep">&gt;</span>
                    <span class="nb-bc-current">Liên hệ</span>
                </nav>
                <div class="nb-contact-hero-badge">📬 HỖ TRỢ BẠN ĐỌC 24/7</div>
                <h1 class="nb-contact-hero-title">Trung Tâm Trợ Giúp &amp; Liên Hệ Biblio</h1>
                <p class="nb-contact-hero-desc">
                    Đội ngũ chăm sóc khách hàng và tư vấn viên Biblio luôn sẵn sàng đồng hành, lắng nghe và giải đáp mọi thắc mắc từ bạn đọc, sinh viên và đối tác xuất bản trên toàn quốc.
                </p>
                <div class="nb-contact-hero-features">
                    <span class="nb-hf-item"><i class="bi bi-lightning-charge-fill" style="color: #ea580c; margin-right: 5px;"></i> Phản hồi trong 15 phút</span>
                    <span class="nb-hf-item"><i class="bi bi-shield-check" style="color: #16a34a; margin-right: 5px;"></i> 100% Sách Thật &amp; Đổi Mới 7 Ngày</span>
                    <span class="nb-hf-item"><i class="bi bi-geo-alt-fill" style="color: #ee4d2d; margin-right: 5px;"></i> Showroom Trực Tiếp Tại NEU Hà Nội</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         2. 4 THẺ THÔNG TIN LIÊN HỆ TRỰC TIẾP (CARDS GRID)
         =================================================================== -->
    <section class="nb-contact-channels-section">
        <div class="nb-container">
            <div class="nb-contact-cards-grid">
                
                <!-- Card 1: Địa chỉ Showroom -->
                <div class="nb-channel-card">
                    <div class="nb-channel-icon-wrap icon-loc">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div class="nb-channel-body">
                        <span class="nb-channel-tag">SHOWROOM &amp; VĂN PHÒNG</span>
                        <h3 class="nb-channel-title">Đại Học Kinh Tế Quốc Dân</h3>
                        <p class="nb-channel-desc">Tòa A1, ĐH Kinh tế Quốc dân (NEU), 207 Giải Phóng, Phường Bạch Mai, TP. Hà Nội</p>
                        <a href="#nbInteractiveMap" class="nb-channel-link">
                            <span>Chỉ đường trên bản đồ</span> <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Card 2: Hotline CSKH -->
                <div class="nb-channel-card highlight-card">
                    <div class="nb-channel-icon-wrap icon-phone">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div class="nb-channel-body">
                        <span class="nb-channel-tag">TỔNG ĐÀI HỖ TRỢ MIỄN PHÍ</span>
                        <h3 class="nb-channel-title">0933.109.009</h3>
                        <p class="nb-channel-desc">Hotline CSKH &amp; Zalo tư vấn giáo trình: <strong>0933.109.009</strong> (08:00 – 21:00 hàng ngày, kể cả T7 &amp; CN)</p>
                        <a href="tel:0933109009" class="nb-channel-link btn-hotline-call">
                            <i class="bi bi-telephone-outbound-fill"></i> <span>Gọi ngay cho chúng tôi</span>
                        </a>
                    </div>
                </div>

                <!-- Card 3: Email Hỗ Trợ -->
                <div class="nb-channel-card">
                    <div class="nb-channel-icon-wrap icon-mail">
                        <i class="bi bi-envelope-paper-fill"></i>
                    </div>
                    <div class="nb-channel-body">
                        <span class="nb-channel-tag">HỘP THƯ ĐIỆN TỬ</span>
                        <h3 class="nb-channel-title">support@biblio.vn</h3>
                        <p class="nb-channel-desc">Tiếp nhận khiếu nại, hỗ trợ đơn hàng và dịch vụ khách hàng: <strong>cskh@biblio.vn</strong> (Phản hồi trong 2-4h)</p>
                        <a href="mailto:support@biblio.vn" class="nb-channel-link">
                            <span>Gửi email hỗ trợ</span> <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Card 4: Đối Tác Doanh Nghiệp & CLB -->
                <div class="nb-channel-card">
                    <div class="nb-channel-icon-wrap icon-b2b">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <div class="nb-channel-body">
                        <span class="nb-channel-tag">HỢP TÁC B2B &amp; XUẤT BẢN</span>
                        <h3 class="nb-channel-title">b2b@biblio.vn</h3>
                        <p class="nb-channel-desc">Cung ứng sách thư viện, giáo trình số lượng lớn cho khoa/viện và câu lạc bộ sinh viên với chiết khấu tới 35%</p>
                        <a href="mailto:b2b@biblio.vn" class="nb-channel-link">
                            <span>Liên hệ hợp tác</span> <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================
         3. BỐ CỤC CHÍNH: FORM GỬI TIN NHẮN (TRÁI) & THÔNG TIN LÀM VIỆC (PHẢI)
         =================================================================== -->
    <section class="nb-contact-main-grid-section">
        <div class="nb-container">
            <div class="nb-contact-layout-split">
                
                <!-- Cột Trái: Form Liên Hệ Trực Tiếp -->
                <div class="nb-contact-form-col">
                    <div class="nb-contact-card-box">
                        <div class="nb-form-header">
                            <span class="nb-form-badge"><i class="bi bi-pencil-square"></i> GỬI YÊU CẦU TRỰC TIẾP</span>
                            <h2 class="nb-form-heading">Gửi Tin Nhắn Cho Đội Ngũ Biblio</h2>
                            <p class="nb-form-subheading">
                                Hãy chia sẻ thắc mắc, yêu cầu tư vấn hoặc góp ý của bạn. Đội ngũ chăm sóc khách hàng Biblio sẽ liên hệ lại qua điện thoại hoặc email sớm nhất.
                            </p>
                        </div>

                        <?php if ($form_sent): ?>
                            <div class="nb-form-alert nb-alert-success" role="alert">
                                <i class="bi bi-check-circle-fill"></i>
                                <div>
                                    <strong>Gửi tin nhắn thành công!</strong>
                                    <p>Cảm ơn bạn đã liên hệ với Biblio. Chuyên viên chăm sóc khách hàng của chúng tôi sẽ phản hồi trong vòng 15-30 phút làm việc.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($form_error)): ?>
                            <div class="nb-form-alert nb-alert-error" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <strong>Chưa thể gửi tin nhắn!</strong>
                                    <p><?php echo esc_html($form_error); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form class="nb-interactive-contact-form" method="post" action="#primary">
                            <?php wp_nonce_field('nb_submit_contact_action', 'nb_contact_nonce'); ?>

                            <div class="nb-form-row nb-form-2cols">
                                <div class="nb-form-group">
                                    <label for="contact_name" class="nb-form-label">
                                        Họ và tên <span class="nb-required">*</span>
                                    </label>
                                    <div class="nb-input-with-icon">
                                        <i class="bi bi-person"></i>
                                        <input type="text" id="contact_name" name="contact_name" required class="nb-form-input" style="padding-left: 46px !important;" placeholder="Ví dụ: Nguyễn Văn An" value="<?php echo isset($_POST['contact_name']) ? esc_attr($_POST['contact_name']) : ''; ?>" />
                                    </div>
                                </div>

                                <div class="nb-form-group">
                                    <label for="contact_phone" class="nb-form-label">
                                        Số điện thoại <span class="nb-required">*</span>
                                    </label>
                                    <div class="nb-input-with-icon">
                                        <i class="bi bi-telephone"></i>
                                        <input type="tel" id="contact_phone" name="contact_phone" required class="nb-form-input" style="padding-left: 46px !important;" placeholder="Ví dụ: 0987 654 321" value="<?php echo isset($_POST['contact_phone']) ? esc_attr($_POST['contact_phone']) : ''; ?>" />
                                    </div>
                                </div>
                            </div>

                            <div class="nb-form-row nb-form-2cols">
                                <div class="nb-form-group">
                                    <label for="contact_email" class="nb-form-label">
                                        Địa chỉ Email <span class="nb-required">*</span>
                                    </label>
                                    <div class="nb-input-with-icon">
                                        <i class="bi bi-envelope"></i>
                                        <input type="email" id="contact_email" name="contact_email" required class="nb-form-input" style="padding-left: 46px !important;" placeholder="name@example.com" value="<?php echo isset($_POST['contact_email']) ? esc_attr($_POST['contact_email']) : ''; ?>" />
                                    </div>
                                </div>

                                <div class="nb-form-group">
                                    <label for="contact_topic" class="nb-form-label">
                                        Chủ đề cần hỗ trợ
                                    </label>
                                    <div class="nb-input-with-icon">
                                        <i class="bi bi-tag"></i>
                                        <select id="contact_topic" name="contact_topic" class="nb-form-select" style="padding-left: 46px !important;">
                                            <option value="Tư vấn chọn sách / Giáo trình NEU">📖 Tư vấn chọn sách / Giáo trình NEU</option>
                                            <option value="Tra cứu đơn hàng &amp; Vận chuyển hỏa tốc">🚚 Tra cứu đơn hàng &amp; Vận chuyển</option>
                                            <option value="Yêu cầu đổi trả / Hoàn tiền">🔄 Yêu cầu đổi trả / Hoàn tiền sách lỗi</option>
                                            <option value="Hợp tác xuất bản / Đặt sách số lượng lớn">🏢 Đặt sách số lượng lớn / Hợp tác CLB</option>
                                            <option value="Góp ý &amp; Phản ánh dịch vụ">💡 Góp ý &amp; Phản ánh chất lượng dịch vụ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="nb-form-group">
                                <label for="contact_message" class="nb-form-label">
                                    Nội dung tin nhắn chi tiết <span class="nb-required">*</span>
                                </label>
                                <textarea id="contact_message" name="contact_message" rows="5" required class="nb-form-textarea" placeholder="Vui lòng mô tả chi tiết yêu cầu của bạn (Mã đơn hàng, tựa sách cần tìm, số lượng,...)..."><?php echo isset($_POST['contact_message']) ? esc_textarea($_POST['contact_message']) : ''; ?></textarea>
                            </div>

                            <div class="nb-form-policy-note">
                                <label class="nb-checkbox-label">
                                    <input type="checkbox" required checked />
                                    <span>Tôi đồng ý để Biblio lưu trữ thông tin liên hệ phục vụ công tác giải đáp và tư vấn đơn hàng theo <a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>" target="_blank">Chính sách bảo mật</a>.</span>
                                </label>
                            </div>

                            <button type="submit" class="nb-btn-submit-contact">
                                <span>Gửi Tin Nhắn Ngay</span>
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Cột Phải: Thời Gian Làm Việc & Kênh Hỗ Trợ -->
                <div class="nb-contact-side-col">
                    
                    <!-- Card: Giờ Hoạt Động -->
                    <div class="nb-side-info-card">
                        <div class="nb-side-card-header">
                            <span class="nb-side-icon"><i class="bi bi-clock-history"></i></span>
                            <div>
                                <h3 class="nb-side-card-title">Thời Gian Phục Vụ</h3>
                                <span class="nb-side-card-desc">Lịch trực CSKH &amp; Showroom trực tiếp</span>
                            </div>
                        </div>
                        <ul class="nb-schedule-list">
                            <li class="nb-schedule-item">
                                <span class="nb-day-label">Thứ Hai – Thứ Sáu</span>
                                <strong class="nb-time-val">08:00 – 21:00</strong>
                            </li>
                            <li class="nb-schedule-item">
                                <span class="nb-day-label">Thứ Bảy</span>
                                <strong class="nb-time-val">08:30 – 18:30</strong>
                            </li>
                            <li class="nb-schedule-item">
                                <span class="nb-day-label">Chủ Nhật &amp; Ngày Lễ</span>
                                <strong class="nb-time-val">09:00 – 17:00</strong>
                            </li>
                        </ul>
                        <div class="nb-schedule-badge">
                            <i class="bi bi-check-circle-fill text-success"></i> Hệ thống tiếp nhận đơn hàng online <strong>24/7/365</strong>
                        </div>
                    </div>

                    <!-- Card: Kênh Hỗ Trợ Trực Tuyến -->
                    <div class="nb-side-info-card">
                        <div class="nb-side-card-header">
                            <span class="nb-side-icon"><i class="bi bi-chat-dots-fill"></i></span>
                            <div>
                                <h3 class="nb-side-card-title">Kênh Chat Trực Tuyến</h3>
                                <span class="nb-side-card-desc">Kết nối tức thì cùng tư vấn viên</span>
                            </div>
                        </div>
                        <div class="nb-social-channels-list">
                            <a href="https://zalo.me" target="_blank" rel="noopener noreferrer" class="nb-soc-channel-btn zalo-btn">
                                <span class="nb-soc-icon">💬</span>
                                <div class="nb-soc-text">
                                    <strong>Zalo Official Account</strong>
                                    <span>Tư vấn giáo trình &amp; sách nhanh</span>
                                </div>
                                <i class="bi bi-chevron-right ms-auto"></i>
                            </a>
                            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="nb-soc-channel-btn fb-btn">
                                <span class="nb-soc-icon"><i class="bi bi-facebook"></i></span>
                                <div class="nb-soc-text">
                                    <strong>Facebook Fanpage Biblio</strong>
                                    <span>Cập nhật khuyến mãi &amp; hội sách</span>
                                </div>
                                <i class="bi bi-chevron-right ms-auto"></i>
                            </a>
                            <a href="tel:0933109009" class="nb-soc-channel-btn hotline-btn">
                                <span class="nb-soc-icon"><i class="bi bi-telephone-fill"></i></span>
                                <div class="nb-soc-text">
                                    <strong>Hotline Khẩn Cấp</strong>
                                    <span>0933 109 009 (Bấm gọi ngay)</span>
                                </div>
                                <i class="bi bi-chevron-right ms-auto"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Card: Cam Kết Dịch Vụ -->
                    <div class="nb-side-info-card trust-card">
                        <div class="nb-trust-strip-side">
                            <div class="nb-tss-item">
                                <i class="bi bi-patch-check-fill text-danger"></i>
                                <div>
                                    <strong>100% Sách Thật Bản Quyền</strong>
                                    <span>Tuyệt đối không bán sách lậu, sách photocopy kém chất lượng</span>
                                </div>
                            </div>
                            <div class="nb-tss-item">
                                <i class="bi bi-truck text-warning"></i>
                                <div>
                                    <strong>Giao Hỏa Tốc 2 Giờ Nội Thành</strong>
                                    <span>Đóng gói 3 lớp chống va đập, bảo vệ gáy sách nguyên vẹn</span>
                                </div>
                            </div>
                            <div class="nb-tss-item">
                                <i class="bi bi-arrow-repeat text-success"></i>
                                <div>
                                    <strong>Đổi Trả Miễn Phí 7 Ngày</strong>
                                    <span>Đổi mới tận nơi nếu sách có lỗi in ấn hoặc gập rách</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================
         4. SHOWROOM TRỰC TIẾP & BẢN ĐỒ TƯƠNG TÁC GOOGLE MAPS
         =================================================================== -->
    <section class="nb-showroom-map-section" id="nbInteractiveMap" aria-label="Bản đồ vị trí showroom Biblio">
        <div class="nb-container">
            <div class="nb-section-head-center">
                <span class="nb-sec-tag">📍 KHÔNG GIAN ĐỌC SÁCH TRỰC TIẾP</span>
                <h2 class="nb-sec-title">Ghé Thăm Showroom &amp; Văn Phòng Biblio</h2>
                <p class="nb-sec-desc">
                    Tọa lạc ngay trong khuôn viên <strong>Đại học Kinh tế Quốc dân (NEU)</strong>, không gian trưng bày hơn 1.000+ tựa sách bản quyền cùng khu vực trải nghiệm đọc sách yên tĩnh, tiện nghi dành riêng cho sinh viên và độc giả.
                </p>
            </div>

            <div class="nb-map-card-wrapper">
                <div class="nb-map-info-pane">
                    <h3 class="nb-mip-title">Trụ Sở Sàn Biblio Việt Nam</h3>
                    <div class="nb-mip-address">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                        <span>Tòa Nhà A1 – Đại Học Kinh Tế Quốc Dân, Số 207 Đường Giải Phóng, Phường Bạch Mai, TP. Hà Nội</span>
                    </div>

                    <div class="nb-mip-features">
                        <div class="nb-mf-item">
                            <i class="bi bi-book-half text-primary"></i>
                            <div>
                                <strong>Kho Sách Mở</strong>
                                <span>Trải nghiệm đọc thử trước khi mua</span>
                            </div>
                        </div>
                        <div class="nb-mf-item">
                            <i class="bi bi-wifi text-success"></i>
                            <div>
                                <strong>Wifi Tốc Độ Cao &amp; Chỗ Ngồi Miễn Phí</strong>
                                <span>Không gian học tập &amp; làm việc nhóm lý tưởng</span>
                            </div>
                        </div>
                        <div class="nb-mf-item">
                            <i class="bi bi-qr-code-scan text-warning"></i>
                            <div>
                                <strong>Nhận Hàng Tự Động (Click &amp; Collect)</strong>
                                <span>Đặt sách online và nhận trực tiếp tại showroom trong 15 phút</span>
                            </div>
                        </div>
                    </div>

                    <div class="nb-mip-actions">
                        <a href="https://maps.google.com/?q=Dai+Hoc+Kinh+Te+Quoc+Dan+207+Giai+Phong+Ha+Noi" target="_blank" rel="noopener noreferrer" class="nb-btn-gmaps">
                            <i class="bi bi-compass"></i>
                            <span>Mở Bản Đồ Google Maps</span>
                        </a>
                    </div>
                </div>

                <div class="nb-map-iframe-pane">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.733568803456!2d105.84074577596956!3d20.99988778964724!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ac71752d8f79%3A0xd2ec575c01017aa4!2zxJDhuqFpIGjhu41jIEtpbmg tOG6vyBRdeG7kWMgZMOibiAoTkVVKQ!5e0!3m2!1svi!2svn!4v1711000000000!5m2!1svi!2svn" 
                        width="100%" 
                        height="420" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Bản đồ vị trí Showroom Biblio NEU">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         5. KHỐI CÂU HỎI THƯỜNG GẶP (FAQ ACCORDION)
         =================================================================== -->
    <section class="nb-contact-faq-section" aria-label="Câu hỏi thường gặp">
        <div class="nb-container">
            <div class="nb-section-head-center">
                <span class="nb-sec-tag">❓ GIẢI ĐÁP NHANH</span>
                <h2 class="nb-sec-title">Câu Hỏi Thường Gặp Khi Mua Sắm Tại Biblio</h2>
                <p class="nb-sec-desc">
                    Tổng hợp các câu hỏi được bạn đọc quan tâm nhiều nhất trong quá trình đặt hàng và sử dụng dịch vụ:
                </p>
            </div>

            <div class="nb-contact-accordion" id="nbContactAccordion">
                
                <div class="nb-cacc-item active">
                    <button type="button" class="nb-cacc-btn" aria-expanded="true">
                        <span>1. Tôi có thể đến xem và mua sách trực tiếp tại Showroom không?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="nb-cacc-content" style="max-height: 400px;">
                        <p>Có. Showroom Biblio tại Tòa A1 – Đại học Kinh tế Quốc dân (NEU) mở cửa đón bạn đọc từ 08:00 đến 21:00 từ Thứ 2 đến Thứ 6 và từ 08:30 đến 18:30 Thứ 7. Bạn hoàn toàn có thể đến đọc thử, trải nghiệm không gian và thanh toán trực tiếp tại quầy hoặc nhận sách đã đặt trước qua website.</p>
                    </div>
                </div>

                <div class="nb-cacc-item">
                    <button type="button" class="nb-cacc-btn" aria-expanded="false">
                        <span>2. Thời gian giao hàng hỏa tốc trong nội thành Hà Nội là bao lâu?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="nb-cacc-content">
                        <p>Với các đơn hàng trong khu vực nội thành Hà Nội (các quận Hai Bà Trưng, Đống Đa, Hoàn Kiếm, Hoàng Mai, Cầu Giấy, Thanh Xuân,...), Biblio hỗ trợ giao hỏa tốc chỉ từ <strong>1 đến 2 giờ</strong> kể từ khi xác nhận đơn. Đối với các tỉnh thành khác trên toàn quốc, thời gian giao hàng tiêu chuẩn là từ 1 – 3 ngày làm việc.</p>
                    </div>
                </div>

                <div class="nb-cacc-item">
                    <button type="button" class="nb-cacc-btn" aria-expanded="false">
                        <span>3. Chính sách đổi trả sách được áp dụng như thế nào?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="nb-cacc-content">
                        <p>Biblio áp dụng chính sách đổi mới <strong>100% miễn phí trong vòng 7 ngày</strong> kể từ khi nhận hàng đối với các sản phẩm có lỗi từ nhà xuất bản (trùng trang, thiếu trang, rách bìa, lem mực) hoặc hư hại trong quá trình vận chuyển. Biblio chịu toàn bộ chi phí vận chuyển 2 chiều khi thực hiện đổi trả.</p>
                    </div>
                </div>

                <div class="nb-cacc-item">
                    <button type="button" class="nb-cacc-btn" aria-expanded="false">
                        <span>4. Sinh viên NEU và các trường đại học có được ưu đãi đặc biệt không?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="nb-cacc-content">
                        <p>Sinh viên Đại học Kinh tế Quốc dân (NEU) và bạn đọc là sinh viên các trường đại học được hưởng chương trình ưu đãi độc quyền: tặng thêm mã giảm giá 10% cho sách chuyên ngành và giáo trình, freeship khi nhận sách tại Showroom NEU và ưu tiên mượn đọc miễn phí tại kho sách mở Biblio.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<script>
// Interactive Accordion for Contact FAQ
document.addEventListener("DOMContentLoaded", () => {
    const accButtons = document.querySelectorAll(".nb-cacc-btn");
    accButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const item = btn.closest(".nb-cacc-item");
            const content = item.querySelector(".nb-cacc-content");
            const isOpen = item.classList.contains("active");

            // Close all
            document.querySelectorAll(".nb-cacc-item").forEach((el) => {
                el.classList.remove("active");
                el.querySelector(".nb-cacc-btn").setAttribute("aria-expanded", "false");
                const panel = el.querySelector(".nb-cacc-content");
                if (panel) panel.style.maxHeight = null;
            });

            // Toggle current
            if (!isOpen) {
                item.classList.add("active");
                btn.setAttribute("aria-expanded", "true");
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });
});
</script>

<?php
get_footer();
