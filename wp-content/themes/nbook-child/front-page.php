<?php
/**
 * Template Name: Nbook Front Page
 * The template for displaying the Nbook homepage with all 9 modules.
 */

get_header();

// Fetch active banners from CPT banner.
$banner_query = new WP_Query([
    'post_type'      => 'banner',
    'posts_per_page' => 20,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);
$now = current_time('timestamp', true);
$banners = array_values(array_filter($banner_query->posts, static function ($banner) use ($now) {
    $start = get_post_meta($banner->ID, '_nbook_banner_start_at', true);
    $end = get_post_meta($banner->ID, '_nbook_banner_end_at', true);
    return (!$start || strtotime($start) <= $now) && (!$end || strtotime($end) >= $now);
}));
$main_banners = array_values(array_filter($banners, static function ($banner) {
    return get_post_meta($banner->ID, '_nbook_banner_position', true) !== 'side';
}));
$side_banners = array_values(array_filter($banners, static function ($banner) {
    return get_post_meta($banner->ID, '_nbook_banner_position', true) === 'side';
}));
$main_banners = array_slice($main_banners, 0, 4);
$side_banners = array_slice($side_banners, 0, 2);

// Contact info
$hotline = get_option('nbook_hotline', '0933 109 009');
$email = get_option('nbook_email', 'cskh@biblio.vn');
$address = get_option('nbook_address', '207 Giải Phóng, Phường Bạch Mai, TP. Hà Nội (Đại học Kinh tế Quốc dân NEU)');
$about_intro = get_option('nbook_about_intro', 'Biblio là sàn thương mại điện tử chuyên biệt ngành sách hàng đầu dành cho sinh viên và độc giả trẻ. Chúng tôi mang tri thức tinh hoa tới bạn đọc với trải nghiệm mua sắm nhanh chóng, tiện lợi và tiết kiệm nhất.');
$banner_link = static function ($url) {
    return preg_match('#^https?://#i', (string) $url) ? $url : home_url('/' . ltrim((string) $url, '/'));
};
?>

<main id="primary" class="site-main nb-homepage-main">
    <div class="nb-container">

        <!-- ===================================================================
             MODULE 3: SLIDE BANNER (3 BANNERS, 5S AUTO, PREV/NEXT, DOTS)
             =================================================================== -->
        <section class="nb-slider-section" aria-label="Khuyến Mãi Nổi Bật">
            <div class="nb-hero-banner-grid">
            <div class="nb-slider-container">
                <div class="nb-slider-track">
                    <?php if (!empty($main_banners)): ?>
                        <?php foreach ($main_banners as $idx => $b):
                            $desc     = get_post_meta($b->ID, '_nbook_banner_desc', true);
                            $btn_text = get_post_meta($b->ID, '_nbook_banner_btn_text', true) ?: 'Khám Phá Ngay';
                            $btn_url  = get_post_meta($b->ID, '_nbook_banner_btn_url', true) ?: '/san-pham/';
                            $badge    = get_post_meta($b->ID, '_nbook_banner_badge', true) ?: 'HOT DEAL';
                            $gradient = get_post_meta($b->ID, '_nbook_banner_bg_gradient', true) ?: 'linear-gradient(135deg, #ee4d2d 0%, #ff7337 100%)';
                            $thumb_url = get_the_post_thumbnail_url($b->ID, 'full');
                            $style = "background: {$gradient};";
                        ?>
                            <div class="nb-slide-item" style="<?php echo esc_attr($style); ?>">
                                <?php if ($thumb_url): ?><img class="nb-slide-image" src="<?php echo esc_url($thumb_url); ?>" alt="" loading="<?php echo $idx === 0 ? 'eager' : 'lazy'; ?>"><?php endif; ?>
                                <span class="nb-slide-pattern" aria-hidden="true">▱</span>
                                <?php if ($badge): ?>
                                    <span class="nb-slide-badge"><?php echo esc_html($badge); ?></span>
                                <?php endif; ?>
                                <h2 class="nb-slide-title"><?php echo esc_html($b->post_title); ?></h2>
                                <?php if ($desc): ?>
                                    <p class="nb-slide-desc"><?php echo esc_html($desc); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($banner_link($btn_url)); ?>" class="nb-slide-btn"><?php echo esc_html($btn_text); ?> →</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="nb-slide-item" style="background: linear-gradient(135deg, #ee4d2d 0%, #ff7337 100%);">
                            <span class="nb-slide-badge">SIÊU SALE 50%</span>
                            <h2 class="nb-slide-title">Đại Tiệc Mùa Thi 2026 – Giảm Đến 50%</h2>
                            <p class="nb-slide-desc">Hàng ngàn tựa sách giáo trình NEU, sách kỹ năng và ngoại ngữ giá cực ưu đãi.</p>
                            <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-slide-btn">Khám Phá Ngay →</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigation arrows -->
                <button type="button" class="nb-slider-prev" aria-label="Banner trước">‹</button>
                <button type="button" class="nb-slider-next" aria-label="Banner tiếp theo">›</button>

                <!-- Dot indicators -->
                <div class="nb-slider-dots">
                    <?php 
                    $count = !empty($main_banners) ? count($main_banners) : 1;
                    for ($i = 0; $i < $count; $i++): 
                    ?>
                        <span class="nb-dot <?php echo ($i === 0) ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                </div>
            </div>
            <aside class="nb-side-banners" aria-label="Ưu đãi nhanh">
                <?php if ($side_banners): foreach ($side_banners as $side): 
                    $desc = get_post_meta($side->ID, '_nbook_banner_desc', true);
                    $url = get_post_meta($side->ID, '_nbook_banner_btn_url', true) ?: '/san-pham/';
                    $badge = get_post_meta($side->ID, '_nbook_banner_badge', true);
                    $gradient = get_post_meta($side->ID, '_nbook_banner_bg_gradient', true) ?: 'linear-gradient(135deg, #fb923c, #ea580c)';
                    $image = get_the_post_thumbnail_url($side->ID, 'medium');
                ?>
                    <a class="nb-side-banner" href="<?php echo esc_url($banner_link($url)); ?>" style="background: <?php echo esc_attr($gradient); ?>">
                        <?php if ($image): ?><img src="<?php echo esc_url($image); ?>" alt="" loading="lazy"><?php endif; ?>
                        <span class="nb-side-pattern" aria-hidden="true">✦</span>
                        <?php if ($badge): ?><small><?php echo esc_html($badge); ?></small><?php endif; ?>
                        <strong><?php echo esc_html($side->post_title); ?></strong>
                        <?php if ($desc): ?><span><?php echo esc_html($desc); ?></span><?php endif; ?>
                        <em>Xem ngay →</em>
                    </a>
                <?php endforeach; else: ?>
                    <a class="nb-side-banner" href="<?php echo esc_url(home_url('/khuyen-mai/')); ?>" style="background:linear-gradient(135deg,#f97316,#c2410c)"><span class="nb-side-pattern">✦</span><small>ƯU ĐÃI HOT</small><strong>Voucher dành riêng cho bạn</strong><em>Săn ngay →</em></a>
                    <a class="nb-side-banner" href="<?php echo esc_url(home_url('/san-pham/')); ?>" style="background:linear-gradient(135deg,#fb923c,#ea580c)"><span class="nb-side-pattern">✦</span><small>SÁCH MỚI</small><strong>Khởi nguồn tri thức</strong><em>Khám phá →</em></a>
                <?php endif; ?>
            </aside>
            </div>
        </section>

        <section class="nb-voucher-strip" aria-label="Voucher cho bạn">
            <div class="nb-voucher-strip-heading"><div><span class="nb-sec-tag">🎁 ƯU ĐÃI HÔM NAY</span><h2>Voucher cho bạn</h2></div><a href="<?php echo esc_url(home_url('/khuyen-mai/')); ?>">Xem tất cả →</a></div>
            <div class="nb-voucher-list">
                <?php
                $voucher_posts = get_posts(['post_type' => 'shop_coupon', 'post_status' => 'publish', 'posts_per_page' => 4, 'orderby' => 'date', 'order' => 'DESC']);
                foreach ($voucher_posts as $voucher):
                    $code = strtoupper($voucher->post_title);
                    $amount = get_post_meta($voucher->ID, 'coupon_amount', true);
                    $discount_type = get_post_meta($voucher->ID, 'discount_type', true);
                    $label = $amount ? ($discount_type === 'percent' ? $amount . '% OFF' : wc_price($amount)) : 'ƯU ĐÃI';
                ?>
                    <div class="nb-voucher-card"><span class="nb-voucher-notch"></span><strong><?php echo wp_kses_post($label); ?></strong><span>Nhập mã <b><?php echo esc_html($code); ?></b></span><button type="button" class="nb-copy-voucher" data-code="<?php echo esc_attr($code); ?>">Sao chép</button></div>
                <?php endforeach; ?>
                <?php if (!$voucher_posts): ?><div class="nb-voucher-empty">Ưu đãi mới sẽ được cập nhật tại đây.</div><?php endif; ?>
            </div>
        </section>


        <!-- ===================================================================
             MODULE 4: MODULE GIỚI THIỆU ("VỀ BIBLIO")
             (2-3 câu, 3 điểm nổi bật: Sách chính thống, Giao nhanh, Ưu đãi sinh viên + Nút "Xem thêm")
             =================================================================== -->
        <section class="nb-about-section" aria-label="Giới Thiệu Về Biblio">
            <div class="nb-section-head-center" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <span class="nb-sec-tag" style="display: inline-block; text-align: center; margin: 0 auto 6px auto;">📖 VỀ BIBLIO</span>
                <h2 class="nb-sec-title" style="text-align: center; display: block; margin: 0 auto 8px auto; justify-content: center; width: 100%;">TRI THỨC KHÔNG GIỚI HẠN – ĐỒNG HÀNH CÙNG BẠN ĐỌC</h2>
                <p class="nb-sec-desc" style="text-align: center; margin: 0 auto;">
                    <?php echo esc_html($about_intro); ?>
                </p>
            </div>

            <!-- 3 Highlights -->
            <div class="nb-highlights-grid">
                <div class="nb-hl-card">
                    <div class="nb-hl-icon">🛡️</div>
                    <h3 class="nb-hl-title">Sách Chính Thống 100%</h3>
                    <p class="nb-hl-text">Cam kết 100% sách thật có bản quyền từ các NXB uy tín lớn: NXB Trẻ, Kim Đồng, Nhã Nam, Alpha Books, First News tại Biblio.</p>
                </div>

                <div class="nb-hl-card">
                    <div class="nb-hl-icon">⚡</div>
                    <h3 class="nb-hl-title">Giao Nhanh Siêu Tốc</h3>
                    <p class="nb-hl-text">Giao hỏa tốc trong 2 giờ tại nội thành Hà Nội, giao hàng toàn quốc từ 1-3 ngày với đóng gói chống sốc kỹ lưỡng từ Biblio.</p>
                </div>

                <div class="nb-hl-card">
                    <div class="nb-hl-icon">🎓</div>
                    <h3 class="nb-hl-title">Ưu Đãi Sinh Viên</h3>
                    <p class="nb-hl-text">Đặc quyền giảm 10-20% cho sinh viên NEU và các trường đại học với danh mục giáo trình, ngoại ngữ và kỹ năng toàn diện tại Biblio.</p>
                </div>
            </div>

            <div class="nb-about-action">
                <a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>" class="nb-btn-outline">Xem Thêm Về Biblio →</a>
            </div>
        </section>

        <!-- ===================================================================
             MODULE 5: MODULE SẢN PHẨM
             (Danh mục sách dạng lưới icon + Sách bán chạy + Sách mới)
             =================================================================== -->
        <!-- 5.1. Danh mục sách dạng lưới icon (Đồng nhất 8 danh mục) -->
        <section class="nb-categories-bar" aria-label="Danh Mục Sách">
            <div class="nb-cats-grid">
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/van-hoc/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">🖋️</div>
                    <span class="nb-cat-name">Văn Học</span>
                </a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/tieu-thuyet/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">📖</div>
                    <span class="nb-cat-name">Tiểu Thuyết</span>
                </a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/kinh-te-ky-nang/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">📊</div>
                    <span class="nb-cat-name">Kinh Tế - Kỹ Năng</span>
                </a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ky-nang/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">📘</div>
                    <span class="nb-cat-name">Sách Kỹ Năng</span>
                </a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/thieu-nhi/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">🧸</div>
                    <span class="nb-cat-name">Thiếu Nhi</span>
                </a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-moi/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">🔥</div>
                    <span class="nb-cat-name">Sách Mới</span>
                </a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-giao-duc/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">🎓</div>
                    <span class="nb-cat-name">Giáo Trình NEU</span>
                </a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ngoai-ngu/')); ?>" class="nb-cat-item">
                    <div class="nb-cat-icon-circle">🌐</div>
                    <span class="nb-cat-name">Ngoại Ngữ &amp; IELTS</span>
                </a>
            </div>
        </section>

        <!-- ===================================================================
             MODULE FLASH SALE: SĂN DEAL GIỜ VÀNG (THANH TIẾN ĐỘ "ĐÃ BÁN X")
             =================================================================== -->
        <section class="nb-flash-sale-section" id="flashSaleSection" aria-label="Flash Sale Giờ Vàng">
            <div class="nb-flash-header">
                <div class="nb-flash-title-wrap">
                    <div class="nb-flash-lightning"><i class="bi bi-lightning-charge-fill"></i></div>
                    <h2 class="nb-flash-title">FLASH SALE GIÁ SỐC</h2>
                    <div class="nb-flash-countdown" id="flashSaleTimer">
                        <span class="nb-timer-text">KẾT THÚC TRONG</span>
                        <span class="nb-time-box" id="fsHours">05</span> :
                        <span class="nb-time-box" id="fsMinutes">42</span> :
                        <span class="nb-time-box" id="fsSeconds">19</span>
                    </div>
                </div>
                <a href="<?php echo esc_url(home_url('/san-pham/?nbook_price_preset=under_50k')); ?>" class="nb-flash-viewall">Xem tất cả deal &rsaquo;</a>
            </div>

            <div class="nb-flash-grid">
                <?php
                $flash_query = new WP_Query([
                    'post_type'      => 'product',
                    'posts_per_page' => 5,
                    'post_status'    => 'publish',
                    'orderby'        => 'meta_value_num',
                    'meta_key'       => 'total_sales',
                    'order'          => 'DESC',
                ]);

                if ($flash_query->have_posts()):
                    while ($flash_query->have_posts()): $flash_query->the_post();
                        $fs_prod    = wc_get_product(get_the_ID());
                        if (!$fs_prod) continue;
                        $fs_id      = $fs_prod->get_id();
                        $fs_title   = $fs_prod->get_name();
                        $fs_link    = $fs_prod->get_permalink();
                        $fs_thumb   = get_the_post_thumbnail_url($fs_id, 'medium') ?: wc_placeholder_img_src();
                        $fs_price   = (float) $fs_prod->get_price();
                        $fs_reg     = (float) $fs_prod->get_regular_price();
                        $fs_sales   = (int) get_post_meta($fs_id, 'total_sales', true);
                        if ($fs_sales <= 0) {
                            $fs_sales = 12 + ($fs_id % 45);
                        }
                        $fs_stock_info = function_exists('nbook_get_product_stock_info') ? nbook_get_product_stock_info($fs_prod) : null;
                        $fs_out_of_stock = $fs_stock_info ? $fs_stock_info['is_out_of_stock'] : !$fs_prod->is_in_stock();

                        // Discount
                        $fs_discount = 0;
                        if ($fs_reg > 0 && $fs_price > 0 && $fs_reg > $fs_price) {
                            $fs_discount = round((($fs_reg - $fs_price) / $fs_reg) * 100);
                        } else {
                            $fs_discount = 25 + ($fs_id % 20);
                        }

                        // Flash target quota for progress calculation
                        $quota = max($fs_sales + 8, 40);
                        $percent = min(100, max(20, round(($fs_sales / $quota) * 100)));
                ?>
                    <div class="nb-flash-card">
                        <a href="<?php echo esc_url($fs_link); ?>" class="nb-flash-card-link">
                            <div class="nb-flash-thumb-wrap">
                                <img src="<?php echo esc_url($fs_thumb); ?>" alt="<?php echo esc_attr($fs_title); ?>" class="nb-flash-thumb <?php echo $fs_out_of_stock ? 'nb-thumb-dimmed' : ''; ?>" loading="lazy" />
                                <span class="nb-flash-badge-discount">-<?php echo esc_html($fs_discount); ?>%</span>
                                <?php if ($fs_stock_info): ?>
                                    <div class="nb-flash-stock-pos"><?php echo $fs_stock_info['badge_html']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="nb-flash-card-info">
                                <h4 class="nb-flash-prod-title" title="<?php echo esc_attr($fs_title); ?>"><?php echo esc_html($fs_title); ?></h4>
                                <div class="nb-flash-price-row">
                                    <span class="nb-flash-price"><?php echo wc_price($fs_price); ?></span>
                                    <?php if ($fs_reg > $fs_price): ?>
                                        <del class="nb-flash-del"><?php echo wc_price($fs_reg); ?></del>
                                    <?php endif; ?>
                                </div>

                                <!-- Progress Bar "Đã bán X" -->
                                <div class="nb-flash-progress-wrap">
                                    <div class="nb-flash-progress-bar" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                                    <span class="nb-flash-progress-text">
                                        <i class="bi bi-fire me-1"></i> Đã bán <?php echo esc_html($fs_sales); ?>
                                    </span>
                                </div>
                            </div>
                        </a>

                        <div class="nb-flash-btn-wrap">
                            <?php if ($fs_out_of_stock): ?>
                                <button type="button" class="nb-btn-flash-cart nb-btn-notify nb-open-stock-notify-modal" data-product-id="<?php echo esc_attr($fs_id); ?>" data-product-title="<?php echo esc_attr($fs_title); ?>">
                                    <i class="bi bi-bell-fill"></i>&nbsp;&nbsp;Báo khi có hàng
                                </button>
                            <?php else: ?>
                                <a href="<?php echo esc_url($fs_prod->add_to_cart_url()); ?>" 
                                   data-quantity="1" 
                                   class="nb-btn-flash-cart product_type_simple add_to_cart_button ajax_add_to_cart" 
                                   data-product_id="<?php echo esc_attr($fs_id); ?>" 
                                   data-product_sku="<?php echo esc_attr($fs_prod->get_sku()); ?>">
                                    <i class="bi bi-cart-plus-fill"></i>&nbsp;&nbsp;Mua ngay
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </section>

        <!-- 5.2. Sách Bán Chạy -->
        <section class="nb-prods-section" aria-label="Sách Bán Chạy">
            <div class="nb-sec-header-row">
                <h2 class="nb-sec-header-title">
                    <span>👑 SÁCH BÁN CHẠY NHẤT</span>
                </h2>
                <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-view-more-link">Xem tất cả ›</a>
            </div>
            <?php echo do_shortcode('[nbook_bestsellers limit="8" columns="4"]'); ?>
        </section>

        <!-- 5.3. Sách Mới -->
        <section class="nb-prods-section" aria-label="Sách Mới Lên Kệ">
            <div class="nb-sec-header-row">
                <h2 class="nb-sec-header-title">
                    <span>🔥 SÁCH MỚI VỪA LÊN KỆ</span>
                </h2>
                <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-view-more-link">Xem tất cả ›</a>
            </div>
            <?php echo do_shortcode('[nbook_new_books limit="8" columns="4"]'); ?>
        </section>

        <!-- ===================================================================
             MODULE 6: MODULE TIN TỨC (3 BÀI VIẾT MỚI NHẤT)
             (Ảnh, Tiêu đề, Trích dẫn, Ngày đăng)
             =================================================================== -->
        <section class="nb-news-section" aria-label="Tin Tức Và Sự Kiện">
            <div class="nb-sec-header-row">
                <h2 class="nb-sec-header-title">
                    <span>📰 BẢN TIN TRI THỨC &amp; SỰ KIỆN BIBLIO</span>
                </h2>
                <a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/')); ?>" class="nb-view-more-link">Xem tất cả bài viết ›</a>
            </div>

            <div class="nb-news-grid">
                <?php
                $news_query = new WP_Query([
                    'post_type'      => 'post',
                    'posts_per_page' => 3,
                    'post_status'    => 'publish',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ]);

                if ($news_query->have_posts()):
                    while ($news_query->have_posts()): $news_query->the_post();
                        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                        if (!$thumb) {
                            $thumb = 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=600&auto=format&fit=crop&q=80';
                        }
                        $excerpt = get_the_excerpt();
                        if (empty($excerpt)) {
                            $excerpt = wp_trim_words(get_the_content(), 18, '...');
                        }
                ?>
                        <article class="nb-news-card">
                            <a href="<?php the_permalink(); ?>" class="nb-news-thumb-link">
                                <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" class="nb-news-thumb" loading="lazy" />
                            </a>
                            <div class="nb-news-body">
                                <?php nbook_render_news_date_badge(get_the_ID(), 'home'); ?>
                                <h3 class="nb-news-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <p class="nb-news-excerpt"><?php echo esc_html($excerpt); ?></p>
                                <a href="<?php the_permalink(); ?>" class="nb-news-readmore">Đọc tiếp →</a>
                            </div>
                        </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                ?>
                    <p>Chưa có bài viết tin tức nào.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ===================================================================
             MODULE 8: MODULE LIÊN HỆ
             (Khối ngắn gồm Hotline, Email, Địa chỉ và nút "Liên hệ ngay")
             =================================================================== -->
        <section class="nb-contact-section" aria-label="Liên Hệ Biblio">
            <div class="nb-contact-grid">
                <div class="nb-contact-card">
                    <div class="nb-contact-icon">📞</div>
                    <div class="nb-contact-text">
                        <h4>HOTLINE TƯ VẤN</h4>
                        <p><?php echo esc_html($hotline); ?></p>
                    </div>
                </div>

                <div class="nb-contact-card">
                    <div class="nb-contact-icon">📧</div>
                    <div class="nb-contact-text">
                        <h4>HỘP THƯ HỖ TRỢ</h4>
                        <p><?php echo esc_html($email); ?></p>
                    </div>
                </div>

                <div class="nb-contact-card">
                    <div class="nb-contact-icon">📍</div>
                    <div class="nb-contact-text">
                        <h4>TRỤ SỞ BIBLIO</h4>
                        <p><?php echo esc_html($address); ?></p>
                    </div>
                </div>

                <div>
                    <a href="<?php echo esc_url(home_url('/lien-he/')); ?>" class="nb-btn-contact-now">Liên Hệ Ngay →</a>
                </div>
            </div>
        </section>

    </div>
</main>

<?php
get_footer();
