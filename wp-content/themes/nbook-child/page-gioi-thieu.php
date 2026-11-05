<?php
/**
 * Template Name: Trang Giới Thiệu Biblio
 * Template for displaying the About Us page (slug: gioi-thieu)
 */

get_header();

// Fetch About Items from Custom Post Type 'nbook_about'
$about_query = new WP_Query([
    'post_type'      => 'nbook_about',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);
?>

<main id="primary" class="site-main nb-about-page-main">

    <!-- ===================================================================
         1. HERO BANNER NHỎ ĐẦU TRANG ("VỀ BIBLIO")
         =================================================================== -->
    <section class="nb-about-hero-banner" aria-label="Banner Về Biblio">
        <div class="nb-container">
            <div class="nb-about-hero-content">
                <nav class="nb-about-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
                    <span class="nb-bc-sep">&gt;</span>
                    <span class="nb-bc-current">Giới thiệu</span>
                </nav>
                <div class="nb-about-hero-badge">📖 VỀ CHÚNG TÔI</div>
                <h1 class="nb-about-hero-title">Về Biblio</h1>
                <p class="nb-about-hero-desc">
                    Sàn Thương Mại Điện Tử Sách &amp; Tri Thức Trực Tuyến Hàng Đầu Dành Cho Sinh Viên – Đồng Hành Cùng Bạn Đọc Trên Hành Trình Học Tập &amp; Khởi Nghiệp.
                </p>
                <div class="nb-about-hero-features">
                    <span class="nb-hf-item">🛡️ 100% Sách Thật Có Bản Quyền</span>
                    <span class="nb-hf-item">⚡ Giao Hỏa Tốc 2H Toàn Quốc</span>
                    <span class="nb-hf-item">🎓 Ưu Đãi Độc Quyền Sinh Viên NEU</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         2. CÁC MỤC GIỚI THIỆU (XEN KẼ ẢNH TRÁI / PHẢI TRÊN MÁY TÍNH, DỌC TRÊN ĐIỆN THOẠI)
         =================================================================== -->
    <section class="nb-about-items-section" aria-label="Nội dung giới thiệu chi tiết">
        <div class="nb-container">
            <?php if ($about_query->have_posts()): ?>
                <div class="nb-about-items-list">
                    <?php
                    $step_idx = 0;
                    $fallback_images = [
                        'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=1000&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=1000&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?w=1000&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=1000&auto=format&fit=crop&q=80',
                        'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1000&auto=format&fit=crop&q=80',
                    ];

                    while ($about_query->have_posts()): $about_query->the_post();
                        $step_idx++;
                        $is_even = ($step_idx % 2 === 0);
                        $row_modifier = $is_even ? 'nb-row-reverse' : 'nb-row-normal';

                        // Get image: Featured thumbnail > custom meta > fallback
                        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
                        if (!$thumb) {
                            $thumb = get_post_meta(get_the_ID(), '_nbook_about_image_url', true);
                        }
                        if (!$thumb) {
                            $thumb = $fallback_images[($step_idx - 1) % count($fallback_images)];
                        }
                    ?>
                        <article class="nb-about-card <?php echo esc_attr($row_modifier); ?>" id="about-section-<?php the_ID(); ?>">
                            
                            <!-- Cột Hình Ảnh -->
                            <div class="nb-about-col-img">
                                <div class="nb-about-img-frame">
                                    <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" class="nb-about-img" loading="lazy" />
                                    <span class="nb-about-num-badge">0<?php echo esc_html($step_idx); ?></span>
                                </div>
                            </div>

                            <!-- Cột Nội Dung -->
                            <div class="nb-about-col-text">
                                <div class="nb-about-cat-tag">MỤC 0<?php echo esc_html($step_idx); ?> / VỀ BIBLIO</div>
                                <h2 class="nb-about-item-title"><?php the_title(); ?></h2>
                                <div class="nb-about-item-content">
                                    <?php the_content(); ?>
                                </div>
                            </div>

                        </article>
                    <?php
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            <?php else: ?>
                <div class="nb-about-empty">
                    <p>Hiện chưa có mục giới thiệu nào được xuất bản. Vui lòng thêm mục trong trang quản trị <strong>wp-admin → Mục Giới Thiệu</strong>.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>



    <!-- ===================================================================
         3. KHỐI CHÍNH SÁCH MỞ RỘNG (ACCORDION BẰNG JS THUẦN)
         (Chính sách chung, bảo mật, thanh toán, đổi/hủy giao dịch)
         =================================================================== -->
    <section class="nb-policy-section" aria-label="Chính sách hoạt động của Biblio">
        <div class="nb-container">
            <div class="nb-section-head-center">
                <span class="nb-sec-tag">🛡️ CHÍNH SÁCH MINH BẠCH</span>
                <h2 class="nb-sec-title">Chính Sách Hoạt Động &amp; Bảo Vệ Quyền Lợi Khách Hàng</h2>
                <p class="nb-sec-desc">
                    Biblio cam kết xây dựng môi trường mua sắm văn minh, an toàn và bảo vệ tối đa quyền lợi của bạn đọc qua các chính sách dịch vụ minh bạch dưới đây:
                </p>
            </div>

            <div class="nb-accordion-wrap" id="nbPolicyAccordion">

                <!-- Accordion 1: Chính sách chung -->
                <div class="nb-accordion-card active">
                    <button type="button" class="nb-acc-trigger" aria-expanded="true">
                        <div class="nb-acc-title-group">
                            <span class="nb-acc-icon">📜</span>
                            <span class="nb-acc-title">1. Chính Sách Chung &amp; Quy Chế Hoạt Động Sàn Biblio</span>
                        </div>
                        <span class="nb-acc-arrow">▼</span>
                    </button>
                    <div class="nb-acc-panel" style="max-height: 800px;">
                        <div class="nb-acc-content">
                            <p>Sàn Thương mại Điện tử <strong>Biblio</strong> hoạt động theo nguyên tắc công khai, minh bạch và tuân thủ tuyệt đối quy định pháp luật Việt Nam về thương mại điện tử.</p>
                            <ul>
                                <li><strong>Cam kết chất lượng:</strong> 100% đầu sách phân phối trên Biblio đều là sách thật, có bản quyền xuất bản chính thức, có hóa đơn chứng từ đầy đủ từ các NXB uy tín.</li>
                                <li><strong>Trách nhiệm người dùng:</strong> Khách hàng cung cấp thông tin liên hệ chính xác để phục vụ công tác giao hàng và bảo hành đơn hàng.</li>
                                <li><strong>Bảo vệ người tiêu dùng:</strong> Mọi khiếu nại về chất lượng sách, thái độ phục vụ hoặc sự cố giao hàng được tiếp nhận và xử lý trong vòng <strong>24 giờ làm việc</strong> qua Hotline <code>0933 109 009</code> hoặc email <code>cskh@biblio.vn</code>.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Accordion 2: Chính sách bảo mật -->
                <div class="nb-accordion-card">
                    <button type="button" class="nb-acc-trigger" aria-expanded="false">
                        <div class="nb-acc-title-group">
                            <span class="nb-acc-icon">🔒</span>
                            <span class="nb-acc-title">2. Chính Sách Bảo Mật Thông Tin Khách Hàng</span>
                        </div>
                        <span class="nb-acc-arrow">▼</span>
                    </button>
                    <div class="nb-acc-panel">
                        <div class="nb-acc-content">
                            <p>Biblio tôn trọng và cam kết bảo vệ dữ liệu cá nhân của mọi khách hàng theo đúng chuẩn Nghị định số 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân.</p>
                            <ul>
                                <li><strong>Mục đích thu thập:</strong> Chỉ thu thập họ tên, số điện thoại, địa chỉ và email nhằm mục đích duy nhất là xác nhận đơn hàng, giao nhận sách và gửi thông báo ưu đãi khi được cho phép.</li>
                                <li><strong>Bảo mật kỹ thuật số:</strong> Toàn bộ kết nối trên Biblio được mã hóa bằng chứng chỉ bảo mật SSL 256-bit cao cấp. Thông tin tài khoản và mật khẩu được mã hóa một chiều an toàn tuyệt đối.</li>
                                <li><strong>Không chia sẻ với bên thứ ba:</strong> Biblio cam đoan không bán, trao đổi hoặc tiết lộ thông tin người dùng cho bất kỳ bên thứ ba nào vì mục đích thương mại ngoài các đối tác vận chuyển phục vụ đơn hàng.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Accordion 3: Chính sách thanh toán -->
                <div class="nb-accordion-card">
                    <button type="button" class="nb-acc-trigger" aria-expanded="false">
                        <div class="nb-acc-title-group">
                            <span class="nb-acc-icon">💳</span>
                            <span class="nb-acc-title">3. Chính Sách Thanh Toán Linh Hoạt &amp; Tiện Lợi</span>
                        </div>
                        <span class="nb-acc-arrow">▼</span>
                    </button>
                    <div class="nb-acc-panel">
                        <div class="nb-acc-content">
                            <p>Nhằm tạo điều kiện thuận lợi nhất cho sinh viên và độc giả, Biblio hỗ trợ đa dạng phương thức thanh toán an toàn:</p>
                            <ul>
                                <li><strong>Thanh toán khi nhận hàng (COD):</strong> Quý khách được quyền đồng kiểm gói hàng trước khi thanh toán tiền mặt cho nhân viên giao hàng.</li>
                                <li><strong>Chuyển khoản VietQR siêu tốc:</strong> Tự động hiển thị mã QR theo từng đơn hàng, xác nhận tức thì không cần chụp ảnh sao kê.</li>
                                <li><strong>Thẻ thanh toán quốc tế &amp; Nội địa:</strong> Hỗ trợ thẻ Visa, MasterCard, JCB và thẻ ATM nội địa qua cổng thanh toán tiêu chuẩn PCI DSS.</li>
                                <li><strong>Ví điện tử tiện ích:</strong> Tích hợp thanh toán nhanh qua MoMo, ShopeePay và ZaloPay với nhiều voucher hoàn tiền hấp dẫn.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Accordion 4: Chính sách đổi trả & hủy giao dịch -->
                <div class="nb-accordion-card">
                    <button type="button" class="nb-acc-trigger" aria-expanded="false">
                        <div class="nb-acc-title-group">
                            <span class="nb-acc-icon">🔄</span>
                            <span class="nb-acc-title">4. Chính Sách Đổi / Hủy Giao Dịch &amp; Hoàn Tiền Trong 7 Ngày</span>
                        </div>
                        <span class="nb-acc-arrow">▼</span>
                    </button>
                    <div class="nb-acc-panel">
                        <div class="nb-acc-content">
                            <p>Biblio áp dụng chính sách đổi trả hàng linh hoạt và tối đa hóa quyền lợi bạn đọc:</p>
                            <ul>
                                <li><strong>Thời hạn đổi trả:</strong> Trong vòng <strong>07 ngày</strong> kể từ ngày quý khách nhận được bưu phẩm.</li>
                                <li><strong>Điều kiện chấp nhận đổi trả:</strong> Sách bị lỗi kỹ thuật in ấn từ nhà xuất bản (trùng trang, thiếu trang, mất chữ), sách bị ướt/rách trong quá trình vận chuyển, hoặc Biblio gửi nhầm tựa sách/phiên bản.</li>
                                <li><strong>Chi phí đổi trả:</strong> Biblio chịu <strong>100% chi phí vận chuyển 2 chiều</strong> nếu lỗi phát sinh từ phía chúng tôi.</li>
                                <li><strong>Quy trình hoàn tiền:</strong> Nếu khách hàng có yêu cầu hoàn tiền, khoản tiền sẽ được chuyển trả vào tài khoản ngân hàng của quý khách trong vòng <strong>24 – 48 giờ</strong> sau khi xác nhận nhận lại sản phẩm.</li>
                                <li><strong>Hủy đơn hàng:</strong> Quý khách có thể tự hủy đơn hàng dễ dàng trong mục <em>Tra Cứu Đơn Hàng</em> khi đơn hàng chưa chuyển sang trạng thái đang vận chuyển.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================
         4. KHỐI KÊU GỌI HÀNH ĐỘNG (CTA BOTTOM BAR)
         =================================================================== -->
    <section class="nb-about-cta-section">
        <div class="nb-container">
            <div class="nb-about-cta-box">
                <div class="nb-about-cta-text">
                    <h3>Khám Phá Ngay Hơn 130+ Tựa Sách Chọn Lọc Tại Biblio</h3>
                    <p>Trợ giá học phần sinh viên, miễn phí vận chuyển từ 299.000đ và tặng bookmark nghệ thuật cho mọi đơn hàng.</p>
                </div>
                <div class="nb-about-cta-actions">
                    <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-btn-cta-primary">Xem Tất Cả Sách →</a>
                    <a href="<?php echo esc_url(home_url('/lien-he/')); ?>" class="nb-btn-cta-outline">Liên Hệ Hỗ Trợ</a>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Vanilla JS for Policy Accordion -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var accordion = document.getElementById('nbPolicyAccordion');
    if (!accordion) return;

    var triggers = accordion.querySelectorAll('.nb-acc-trigger');
    triggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            var card = this.closest('.nb-accordion-card');
            var panel = card.querySelector('.nb-acc-panel');
            var isExpanded = this.getAttribute('aria-expanded') === 'true';

            // Optional: Close other cards
            var allCards = accordion.querySelectorAll('.nb-accordion-card');
            allCards.forEach(function(otherCard) {
                if (otherCard !== card) {
                    otherCard.classList.remove('active');
                    var otherTrigger = otherCard.querySelector('.nb-acc-trigger');
                    var otherPanel = otherCard.querySelector('.nb-acc-panel');
                    if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
                    if (otherPanel) otherPanel.style.maxHeight = null;
                }
            });

            // Toggle current card
            if (isExpanded) {
                card.classList.remove('active');
                this.setAttribute('aria-expanded', 'false');
                panel.style.maxHeight = null;
            } else {
                card.classList.add('active');
                this.setAttribute('aria-expanded', 'true');
                panel.style.maxHeight = panel.scrollHeight + 30 + 'px';
            }
        });
    });
});
</script>

<?php
get_footer();
