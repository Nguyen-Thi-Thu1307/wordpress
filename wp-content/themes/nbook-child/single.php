<?php
/**
 * Single Post Template - Biblio Literary Magazine & News Format
 */

get_header();

while (have_posts()) : the_post();

$post_id = get_the_ID();
$categories = get_the_category();
$primary_cat = !empty($categories) ? $categories[0] : null;
$cat_name = $primary_cat ? $primary_cat->name : 'Tin tức – Sự kiện';
$cat_link = $primary_cat ? get_category_link($primary_cat->term_id) : home_url('/tin-tuc-su-kien/');

// Reading time
$content_raw = get_the_content();
$word_count = str_word_count(strip_tags($content_raw));
$reading_time = max(2, ceil($word_count / 160));

// Thumbnail
$thumb_url = get_the_post_thumbnail_url($post_id, 'full');
$post_date = get_the_date('d/m/Y');
$current_url = get_permalink();

// Previous & Next posts
$prev_post = get_previous_post();
$next_post = get_next_post();

// Related posts (cùng chuyên mục)
$related_posts = new WP_Query([
    'category__in'   => $primary_cat ? [$primary_cat->term_id] : [],
    'post__not_in'   => [$post_id],
    'posts_per_page' => 3,
    'orderby'        => 'rand',
]);

// Sidebar latest articles
$sidebar_posts = new WP_Query([
    'post_type'      => 'post',
    'posts_per_page' => 5,
    'post__not_in'   => [$post_id],
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

// Sidebar recommended books
$featured_books = [];
if (function_exists('wc_get_products')) {
    $featured_books = wc_get_products([
        'limit'   => 3,
        'status'  => 'publish',
        'orderby' => 'popularity',
    ]);
}
?>

<main id="primary" class="site-main nb-single-post-main">

    <!-- BREADCRUMBS BAR -->
    <div class="nb-single-breadcrumb-wrap">
        <div class="nb-container">
            <nav class="nb-single-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
                <span class="nb-bc-sep">&gt;</span>
                <a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/')); ?>">Tin tức – Sự kiện</a>
                <span class="nb-bc-sep">&gt;</span>
                <a href="<?php echo esc_url($cat_link); ?>"><?php echo esc_html($cat_name); ?></a>
                <span class="nb-bc-sep">&gt;</span>
                <span class="nb-bc-current"><?php echo esc_html(wp_trim_words(get_the_title(), 8, '...')); ?></span>
            </nav>
        </div>
    </div>

    <!-- MAIN ARTICLE CONTAINER -->
    <div class="nb-container">
        <div class="nb-article-layout">

            <!-- MAIN COLUMN (70%) -->
            <article class="nb-article-col">

                <!-- Article Header -->
                <header class="nb-article-header">
                    <div class="nb-article-cat-badge">
                        <a href="<?php echo esc_url($cat_link); ?>" class="nb-cat-badge-pill nb-cat-<?php echo esc_attr($primary_cat ? $primary_cat->slug : 'tin-biblio'); ?>">
                            <i class="bi bi-bookmark-fill"></i>&nbsp;&nbsp;<?php echo esc_html($cat_name); ?>
                        </a>
                    </div>

                    <h1 class="nb-article-title"><?php the_title(); ?></h1>

                    <div class="nb-article-meta-bar">
                        <div class="nb-meta-left">
                            <div class="nb-author-chip">
                                <span class="nb-author-avatar"><i class="bi bi-person-fill"></i></span>
                                <div class="nb-author-info">
                                    <span class="nb-author-name">Ban Biên Tập Biblio</span>
                                    <span class="nb-author-role">Chuyên mục Văn hóa đọc</span>
                                </div>
                            </div>
                            <span class="nb-meta-divider">•</span>
                            <?php nbook_render_news_date_badge($post_id, 'single'); ?>
                            <span class="nb-meta-divider">•</span>
                            <span class="nb-meta-item"><i class="bi bi-book"></i>&nbsp;&nbsp;<?php echo esc_html($reading_time); ?> phút đọc</span>
                        </div>

                        <!-- Top Quick Share -->
                        <div class="nb-meta-share">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" target="_blank" rel="noopener noreferrer" class="nb-share-btn share-fb" title="Chia sẻ Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="https://zalo.me/share?url=<?php echo urlencode($current_url); ?>" target="_blank" rel="noopener noreferrer" class="nb-share-btn share-zalo" title="Chia sẻ Zalo">
                                <span class="nb-zalo-txt">Zalo</span>
                            </a>
                            <button type="button" class="nb-share-btn share-copy" id="nbBtnCopyArticleLink" title="Sao chép liên kết" data-url="<?php echo esc_url($current_url); ?>">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                <?php if ($thumb_url): ?>
                    <div class="nb-article-featured-img-wrap">
                        <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>" class="nb-article-featured-img" />
                        <?php if (get_post(get_post_thumbnail_id())->post_excerpt): ?>
                            <figcaption class="nb-article-img-caption">
                                <i class="bi bi-camera"></i>&nbsp;&nbsp;<?php echo esc_html(get_post(get_post_thumbnail_id())->post_excerpt); ?>
                            </figcaption>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php 
                $cat_slug = $primary_cat ? $primary_cat->slug : '';
                if ($cat_slug === 'su-kien-sach'): 
                ?>
                    <!-- EVENT FACTS CARD -->
                    <div class="nb-event-facts-card">
                        <div class="nb-efc-header">
                            <div class="nb-efc-head-left">
                                <span class="nb-efc-live-dot"></span>
                                <span class="nb-efc-title">THÔNG TIN SỰ KIỆN BIBLIO</span>
                            </div>
                            <span class="nb-efc-badge"><i class="bi bi-broadcast"></i> MỞ CỬA TỰ DO</span>
                        </div>
                        <div class="nb-efc-grid">
                            <div class="nb-efc-item">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                                <div>
                                    <span class="nb-efc-label">Địa điểm tổ chức</span>
                                    <strong>Số 207 Giải Phóng, P. Bạch Mai, Hà Nội</strong>
                                </div>
                            </div>
                            <div class="nb-efc-item">
                                <i class="bi bi-clock-fill text-danger"></i>
                                <div>
                                    <span class="nb-efc-label">Thời gian mở cửa</span>
                                    <strong>08:00 – 22:00 (Tất cả các ngày trong tuần)</strong>
                                </div>
                            </div>
                            <div class="nb-efc-item">
                                <i class="bi bi-ticket-perforated-fill text-danger"></i>
                                <div>
                                    <span class="nb-efc-label">Vé vào cửa &amp; Workshop</span>
                                    <strong>Miễn phí 100% cho bạn đọc &amp; sinh viên toàn quốc</strong>
                                </div>
                            </div>
                            <div class="nb-efc-item">
                                <i class="bi bi-gift-fill text-danger"></i>
                                <div>
                                    <span class="nb-efc-label">Quà tặng đặc quyền</span>
                                    <strong>Tặng túi tote canvas, sổ tay &amp; voucher 40% cho độc giả ghé thăm</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($cat_slug === 'review-sach'): ?>
                    <!-- BOOK REVIEW FACTS CARD -->
                    <div class="nb-review-facts-card">
                        <div class="nb-rfc-header">
                            <div class="nb-rfc-head-left">
                                <span class="nb-rfc-icon"><i class="bi bi-award-fill"></i></span>
                                <div>
                                    <span class="nb-rfc-tag">GÓC ĐÁNH GIÁ SÁCH BIBLIO</span>
                                    <h4 class="nb-rfc-title">Thông Tin Tác Phẩm &amp; Điểm Thẩm Định</h4>
                                </div>
                            </div>
                            <div class="nb-rfc-rating">
                                <span class="nb-rfc-score">5.0</span>
                                <div class="nb-rfc-stars">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                </div>
                                <span class="nb-rfc-verdict">KHUYÊN ĐỌC</span>
                            </div>
                        </div>
                        <div class="nb-rfc-grid">
                            <div class="nb-rfc-item">
                                <span class="nb-rfc-label"><i class="bi bi-bookmark-check-fill text-danger"></i> Chuyên đề:</span>
                                <strong>Sách Phát Triển Bản Thân &amp; Tri Thức Đổi Đời</strong>
                            </div>
                            <div class="nb-rfc-item">
                                <span class="nb-rfc-label"><i class="bi bi-people-fill text-danger"></i> Đối tượng:</span>
                                <strong>Sinh viên, Bạn đọc trẻ &amp; Người khởi nghiệp</strong>
                            </div>
                            <div class="nb-rfc-item">
                                <span class="nb-rfc-label"><i class="bi bi-book-half text-danger"></i> Định dạng:</span>
                                <strong>Bìa mềm cao cấp, giấy chống lóa chuẩn xuất bản</strong>
                            </div>
                            <div class="nb-rfc-item">
                                <span class="nb-rfc-label"><i class="bi bi-shield-check text-danger"></i> Cam kết Biblio:</span>
                                <strong>100% Sách chính hãng, hoàn tiền 200% nếu sách giả</strong>
                            </div>
                        </div>
                    </div>
                <?php elseif ($cat_slug === 'kien-thuc-tips'): ?>
                    <!-- READING TIPS FACTS CARD -->
                    <div class="nb-tips-facts-card">
                        <div class="nb-tfc-header">
                            <div class="nb-tfc-head-left">
                                <span class="nb-tfc-icon"><i class="bi bi-lightbulb-fill"></i></span>
                                <div>
                                    <span class="nb-tfc-tag">CẨM NANG &amp; KỸ NĂNG BIBLIO</span>
                                    <h4 class="nb-tfc-title">Tóm Tắt Phương Pháp &amp; Lời Khuyên Ứng Dụng</h4>
                                </div>
                            </div>
                            <span class="nb-tfc-badge"><i class="bi bi-mortarboard-fill"></i> CHUẨN SINH VIÊN NEU</span>
                        </div>
                        <div class="nb-tfc-grid">
                            <div class="nb-tfc-item">
                                <i class="bi bi-check2-circle text-success"></i>
                                <span>Tối ưu hóa thời gian đọc sách từ 15 - 30 phút tập trung mỗi ngày</span>
                            </div>
                            <div class="nb-tfc-item">
                                <i class="bi bi-check2-circle text-success"></i>
                                <span>Ghi chú thông minh bằng phương pháp Cornell &amp; Sơ đồ tư duy</span>
                            </div>
                            <div class="nb-tfc-item">
                                <i class="bi bi-check2-circle text-success"></i>
                                <span>Lựa chọn giáo trình chuẩn xác theo đề cương môn học chính khóa</span>
                            </div>
                            <div class="nb-tfc-item">
                                <i class="bi bi-check2-circle text-success"></i>
                                <span>Áp dụng ngay kiến thức vào bài tập lớn, thi cử và dự án khởi nghiệp</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Article Content Body -->
                <div class="nb-single-content-body entry-content">
                    <?php 
                    the_content(); 
                    ?>
                </div>

                <!-- Article Callout / Quote of the day -->
                <div class="nb-article-quote-box">
                    <div class="nb-aqb-icon"><i class="bi bi-quote"></i></div>
                    <div class="nb-aqb-content">
                        <p>&ldquo;Một cuốn sách thực sự hay nên đọc trong tuổi trẻ, rồi đọc lại ở tuổi trưởng thành và một lần nữa ở tuổi già, giống như một tòa nhà đẹp nên ngắm lúc bình minh, lúc nắng trưa và lúc trăng tỏ.&rdquo;</p>
                        <cite>— Robertson Davies (Danh ngôn văn học Biblio tuyển chọn)</cite>
                    </div>
                </div>

                <!-- Article Tags & Social Engagement -->
                <div class="nb-article-footer-bar">
                    <div class="nb-afb-tags">
                        <span class="nb-tags-label"><i class="bi bi-tags-fill"></i>&nbsp;&nbsp;Chủ đề:</span>
                        <a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/?s_news=sach')); ?>" class="nb-tag-pill">#sách_hay</a>
                        <a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/?s_news=van_hoa_doc')); ?>" class="nb-tag-pill">#văn_hóa_đọc</a>
                        <a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/?s_news=tri_thuc')); ?>" class="nb-tag-pill">#tri_thức_biblio</a>
                    </div>

                    <div class="nb-afb-actions">
                        <button type="button" class="nb-btn-like" id="nbBtnLikePost">
                            <i class="bi bi-heart-fill"></i>&nbsp;&nbsp;<span id="nbLikeText">Yêu thích (<span id="nbLikeCount">48</span>)</span>
                        </button>
                    </div>
                </div>

                <!-- Author Box -->
                <div class="nb-article-author-card">
                    <div class="nb-aac-avatar">
                        <span class="nb-aac-avatar-badge"><i class="bi bi-person-fill"></i></span>
                    </div>
                    <div class="nb-aac-meta">
                        <div class="nb-aac-header">
                            <h4>Ban Biên Tập Sách Biblio</h4>
                            <span class="nb-aac-badge"><i class="bi bi-patch-check-fill"></i>&nbsp;&nbsp;Tác giả uy tín</span>
                        </div>
                        <p class="nb-aac-desc">
                            Đội ngũ biên tập viên và chuyên gia tuyển chọn đầu sách tại Biblio. Chúng tôi cam kết chia sẻ góc nhìn văn học sâu sắc, phân tích giáo trình chuẩn xác và đồng hành cùng thói quen đọc sách mỗi ngày của bạn.
                        </p>
                        <div class="nb-aac-links">
                            <a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/')); ?>"><i class="bi bi-grid-fill"></i>&nbsp;&nbsp;Xem thêm bài viết</a>
                            <a href="<?php echo esc_url(home_url('/san-pham/')); ?>"><i class="bi bi-bag-check-fill"></i>&nbsp;&nbsp;Khám phá kho sách</a>
                        </div>
                    </div>
                </div>

                <!-- Post Navigation (Prev / Next) -->
                <div class="nb-article-prev-next-nav">
                    <?php if ($prev_post): ?>
                        <a href="<?php echo esc_url(get_permalink($prev_post->ID)); ?>" class="nb-pnn-card nb-pnn-prev">
                            <span class="nb-pnn-sub"><i class="bi bi-arrow-left"></i>&nbsp;&nbsp;Bài viết trước</span>
                            <h4 class="nb-pnn-title"><?php echo esc_html(wp_trim_words($prev_post->post_title, 9, '...')); ?></h4>
                        </a>
                    <?php else: ?>
                        <div class="nb-pnn-card nb-pnn-empty"></div>
                    <?php endif; ?>

                    <?php if ($next_post): ?>
                        <a href="<?php echo esc_url(get_permalink($next_post->ID)); ?>" class="nb-pnn-card nb-pnn-next">
                            <span class="nb-pnn-sub">Bài viết kế tiếp&nbsp;&nbsp;<i class="bi bi-arrow-right"></i></span>
                            <h4 class="nb-pnn-title"><?php echo esc_html(wp_trim_words($next_post->post_title, 9, '...')); ?></h4>
                        </a>
                    <?php else: ?>
                        <div class="nb-pnn-card nb-pnn-empty"></div>
                    <?php endif; ?>
                </div>

            </article>

            <!-- SIDEBAR COLUMN (30%) -->
            <aside class="nb-article-sidebar-col">

                <!-- Widget 1: Categories List -->
                <div class="nb-article-widget">
                    <h3 class="nb-widget-title"><i class="bi bi-bookmarks-fill text-danger"></i>&nbsp;&nbsp;Chuyên Mục Tin Tức</h3>
                    <ul class="nb-widget-cat-list">
                        <?php 
                        $all_cats = get_categories(['hide_empty' => true]);
                        foreach ($all_cats as $c):
                            $is_current_c = ($primary_cat && $primary_cat->term_id === $c->term_id);
                        ?>
                            <li class="<?php echo $is_current_c ? 'active' : ''; ?>">
                                <a href="<?php echo esc_url(get_category_link($c->term_id)); ?>">
                                    <span><?php echo esc_html($c->name); ?></span>
                                    <span class="nb-wcl-count"><?php echo esc_html($c->count); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Widget 2: Latest Articles -->
                <?php if ($sidebar_posts->have_posts()): ?>
                    <div class="nb-article-widget">
                        <h3 class="nb-widget-title"><i class="bi bi-fire text-warning"></i>&nbsp;&nbsp;Bài Viết Mới Nhất</h3>
                        <div class="nb-widget-posts-list">
                            <?php while ($sidebar_posts->have_posts()): $sidebar_posts->the_post(); 
                                $s_id = get_the_ID();
                                $s_thumb = get_the_post_thumbnail_url($s_id, 'medium') ?: get_stylesheet_directory_uri() . '/assets/images/news-placeholder.jpg';
                                $s_date = get_the_date('d/m/Y');
                            ?>
                                <div class="nb-wpl-item">
                                    <a href="<?php the_permalink(); ?>" class="nb-wpl-thumb-wrap">
                                        <img src="<?php echo esc_url($s_thumb); ?>" alt="<?php the_title_attribute(); ?>" class="nb-wpl-thumb" loading="lazy" />
                                    </a>
                                    <div class="nb-wpl-info">
                                        <h4 class="nb-wpl-title">
                                            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                                <?php echo esc_html(wp_trim_words(get_the_title(), 10, '...')); ?>
                                            </a>
                                        </h4>
                                        <?php nbook_render_news_date_badge($s_id, 'sidebar'); ?>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Widget 3: Featured Books Recommendation -->
                <?php if (!empty($featured_books)): ?>
                    <div class="nb-article-widget nb-widget-books">
                        <h3 class="nb-widget-title"><i class="bi bi-book-half text-primary"></i>&nbsp;&nbsp;Sách Bán Chạy Đề Xuất</h3>
                        <div class="nb-widget-books-list">
                            <?php foreach ($featured_books as $book): 
                                $b_id = $book->get_id();
                                $b_title = $book->get_name();
                                $b_url = $book->get_permalink();
                                $b_img = wp_get_attachment_image_url($book->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src();
                                $b_price = $book->get_price_html();
                            ?>
                                <div class="nb-wbl-item">
                                    <a href="<?php echo esc_url($b_url); ?>" class="nb-wbl-thumb-wrap">
                                        <img src="<?php echo esc_url($b_img); ?>" alt="<?php echo esc_attr($b_title); ?>" class="nb-wbl-thumb" loading="lazy" />
                                    </a>
                                    <div class="nb-wbl-info">
                                        <h4 class="nb-wbl-title">
                                            <a href="<?php echo esc_url($b_url); ?>"><?php echo esc_html($b_title); ?></a>
                                        </h4>
                                        <div class="nb-wbl-price"><?php echo $b_price; ?></div>
                                        <a href="<?php echo esc_url($b_url); ?>" class="nb-wbl-btn">
                                            <i class="bi bi-cart-plus"></i>&nbsp;&nbsp;Xem sách
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Widget 4: Newsletter / Voucher Banner -->
                <div class="nb-article-widget nb-widget-promo-banner">
                    <div class="nb-wpb-icon"><i class="bi bi-ticket-perforated-fill"></i></div>
                    <h4>Đăng Ký Thành Viên Nhận 50.000₫</h4>
                    <p>Nhận ngay mã freeship và voucher giảm giá 50K khi kích hoạt tài khoản Biblio lần đầu.</p>
                    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="nb-btn-claim-voucher">
                        <i class="bi bi-gift-fill"></i>&nbsp;&nbsp;Nhận voucher ngay
                    </a>
                </div>

            </aside>

        </div>
    </div>

    <!-- RELATED POSTS CAROUSEL / GRID (3 CỘT) -->
    <?php if ($related_posts->have_posts()): ?>
        <section class="nb-related-posts-section">
            <div class="nb-container">
                <div class="nb-rps-header">
                    <div>
                        <span class="nb-rps-badge"><i class="bi bi-stars"></i>&nbsp;&nbsp;GỢI Ý ĐỌC THÊM</span>
                        <h2 class="nb-rps-title">Bài Viết Cùng Chuyên Mục Bạn Có Thể Thích</h2>
                    </div>
                    <a href="<?php echo esc_url($cat_link); ?>" class="nb-rps-view-all">
                        Xem tất cả&nbsp;&nbsp;<i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="nb-related-grid">
                    <?php while ($related_posts->have_posts()): $related_posts->the_post(); 
                        $r_id = get_the_ID();
                        $r_thumb = get_the_post_thumbnail_url($r_id, 'large') ?: get_stylesheet_directory_uri() . '/assets/images/news-placeholder.jpg';
                        $r_date = get_the_date('d/m/Y');
                        $r_cats = get_the_category();
                        $r_cat_name = !empty($r_cats) ? $r_cats[0]->name : 'Tin tức';
                    ?>
                        <article class="nb-related-card">
                            <a href="<?php the_permalink(); ?>" class="nb-rc-thumb-wrap">
                                <img src="<?php echo esc_url($r_thumb); ?>" alt="<?php the_title_attribute(); ?>" class="nb-rc-thumb" loading="lazy" />
                                <span class="nb-rc-cat-badge"><?php echo esc_html($r_cat_name); ?></span>
                            </a>
                            <div class="nb-rc-body">
                                <div class="nb-rc-meta">
                                    <?php nbook_render_news_date_badge($r_id, 'sidebar'); ?>
                                    <span>•</span>
                                    <span><i class="bi bi-clock"></i>&nbsp;&nbsp;3 phút đọc</span>
                                </div>
                                <h3 class="nb-rc-title">
                                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                        <?php echo esc_html(wp_trim_words(get_the_title(), 12, '...')); ?>
                                    </a>
                                </h3>
                                <p class="nb-rc-excerpt">
                                    <?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 18, '...')); ?>
                                </p>
                                <a href="<?php the_permalink(); ?>" class="nb-rc-read-more">
                                    Đọc bài viết&nbsp;&nbsp;<i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy link bài viết
    var copyBtn = document.getElementById('nbBtnCopyArticleLink');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var url = copyBtn.getAttribute('data-url') || window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
                    copyBtn.classList.add('copied');
                    setTimeout(function() {
                        copyBtn.innerHTML = '<i class="bi bi-link-45deg"></i>';
                        copyBtn.classList.remove('copied');
                    }, 2000);
                });
            }
        });
    }

    // Like bài viết
    var likeBtn = document.getElementById('nbBtnLikePost');
    var likeCountEl = document.getElementById('nbLikeCount');
    if (likeBtn && likeCountEl) {
        var pid = "<?php echo esc_js($post_id); ?>";
        var isLiked = localStorage.getItem('nb_liked_post_' + pid);
        if (isLiked) {
            likeBtn.classList.add('liked');
        }
        likeBtn.addEventListener('click', function() {
            var currentLikes = parseInt(likeCountEl.textContent, 10) || 48;
            if (likeBtn.classList.contains('liked')) {
                likeBtn.classList.remove('liked');
                likeCountEl.textContent = Math.max(0, currentLikes - 1);
                localStorage.removeItem('nb_liked_post_' + pid);
            } else {
                likeBtn.classList.add('liked');
                likeCountEl.textContent = currentLikes + 1;
                localStorage.setItem('nb_liked_post_' + pid, '1');
            }
        });
    }
});
</script>

<?php
endwhile;

get_footer();
