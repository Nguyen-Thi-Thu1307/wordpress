<?php
/**
 * Template Name: Trang Tin Tức - Sự Kiện Biblio
 * Template for displaying the News & Events page (slug: tin-tuc-su-kien)
 */

get_header();

// 1. Pagination & URL parameters
$paged = max(1, (int)get_query_var('paged'), (int)get_query_var('page'));
$base_news_url  = home_url('/tin-tuc-su-kien/');
$selected_cat   = isset($_GET['chuyen_muc']) ? sanitize_text_field($_GET['chuyen_muc']) : '';
$search_query   = isset($_GET['s_news']) ? sanitize_text_field($_GET['s_news']) : (isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '');

// 2. Fetch Defined News Categories
$news_cats = get_terms([
    'taxonomy'   => 'category',
    'hide_empty' => false,
    'slug'       => ['review-sach', 'kien-thuc-tips', 'su-kien-sach', 'tin-biblio'],
]);
$total_published = wp_count_posts('post')->publish;

// 3. Build Main Query
$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 9,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

if (!empty($selected_cat)) {
    $args['category_name'] = $selected_cat;
}
if (!empty($search_query)) {
    $args['s'] = $search_query;
}

$main_query = new WP_Query($args);

// 4. Fetch Trending/Popular Posts for Sidebar
$trending_query = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

// 5. Active category info
$current_cat_obj = !empty($selected_cat) ? get_category_by_slug($selected_cat) : null;
$filter_active   = (!empty($selected_cat) || !empty($search_query));
?>

<main id="primary" class="site-main nb-news-page-main">

    <!-- ===================================================================
         1. HERO BANNER & BREADCRUMBS (ĐỒNG NHẤT VỚI PHẦN ĐẦU TRANG GIỚI THIỆU)
         =================================================================== -->
    <section class="nb-news-hero-banner" aria-label="Banner Tin tức Biblio">
        <div class="nb-container">
            <div class="nb-news-hero-content">
                <nav class="nb-news-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
                    <span class="nb-bc-sep">&gt;</span>
                    <?php if ($filter_active): ?>
                        <a href="<?php echo esc_url($base_news_url); ?>">Tin tức – Sự kiện</a>
                        <span class="nb-bc-sep">&gt;</span>
                        <span class="nb-bc-current">
                            <?php 
                            if ($current_cat_obj) {
                                echo esc_html($current_cat_obj->name);
                            } elseif (!empty($search_query)) {
                                echo 'Tìm kiếm: &ldquo;' . esc_html($search_query) . '&rdquo;';
                            }
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="nb-bc-current">Tin tức – Sự kiện</span>
                    <?php endif; ?>
                </nav>
                <div class="nb-news-hero-badge">
                    <i class="bi bi-newspaper"></i> BẢN TIN TRI THỨC &amp; SỰ KIỆN BIBLIO
                </div>
                <h1 class="nb-news-hero-title">
                    <?php 
                    if ($current_cat_obj) {
                        echo 'Chuyên Mục: ' . esc_html($current_cat_obj->name);
                    } elseif (!empty($search_query)) {
                        echo 'Tìm kiếm: ' . esc_html($search_query);
                    } else {
                        echo 'Tin Tức &amp; Sự Kiện Biblio';
                    }
                    ?>
                </h1>
                <p class="nb-news-hero-desc">Khám phá các bài viết review sách hay, phương pháp học tập hiệu quả, tin tức văn hóa đọc và các chương trình ưu đãi độc quyền dành cho bạn đọc Biblio.</p>
                <div class="nb-news-hero-features">
                    <span class="nb-hf-item">📚 <strong style="color: #ee4d2d;"><?php echo esc_html($total_published); ?>+</strong> Bài viết chọn lọc</span>
                    <span class="nb-hf-item">🏷️ <strong style="color: #ee4d2d;"><?php echo esc_html(count($news_cats)); ?></strong> Chuyên mục đặc sắc</span>
                    <span class="nb-hf-item">🎓 <strong style="color: #ee4d2d;">25K+</strong> Độc giả sinh viên</span>
                    <span class="nb-hf-item">⚡ Cập nhật theo thời gian thực</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         2. LIVE REAL-TIME SYSTEM TICKER & STATUS BAR
         =================================================================== -->
    <section class="nb-news-live-bar" aria-label="Đồng bộ thời gian thực hệ thống">
        <div class="nb-container">
            <div class="nb-live-ticker-wrap">
                <div class="nb-live-ticker-left">
                    <div class="nb-live-pulse-badge">
                        <span class="nb-live-pulse-ring"></span>
                        <span class="nb-live-pulse-core"></span>
                        <span class="nb-live-badge-txt">TRỰC TIẾP HỆ THỐNG</span>
                    </div>
                    <div class="nb-live-clock-block">
                        <i class="bi bi-clock-fill text-danger me-1"></i>
                        <span class="nb-live-clock-label">Thời gian thực hệ thống:</span>
                        <strong id="nbRealtimeClock" class="nb-live-clock-value" data-server-time="<?php echo esc_attr(time()); ?>">
                            <?php 
                            $vn_days = [
                                'Sunday' => 'Chủ Nhật',
                                'Monday' => 'Thứ Hai',
                                'Tuesday' => 'Thứ Ba',
                                'Wednesday' => 'Thứ Tư',
                                'Thursday' => 'Thứ Năm',
                                'Friday' => 'Thứ Sáu',
                                'Saturday' => 'Thứ Bảy',
                            ];
                            $w_day = wp_date('l');
                            $vn_day = $vn_days[$w_day] ?? $w_day;
                            echo esc_html($vn_day . ', ' . wp_date('d/m/Y') . ' – ' . wp_date('H:i:s'));
                            ?>
                        </strong>
                    </div>
                </div>
                <div class="nb-live-ticker-right">
                    <div class="nb-live-sync-indicator">
                        <span class="nb-sync-dot"></span>
                        <span>Đang đồng bộ tin tức &amp; sự kiện liên tục</span>
                    </div>
                    <button type="button" class="nb-live-btn-refresh" id="nbBtnRefreshFeed" title="Làm mới bảng tin theo thời gian thực">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Làm mới</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         3. CATEGORY PILLS FILTER & SEARCH BAR
         =================================================================== -->
    <section class="nb-news-filter-section" aria-label="Bộ lọc chuyên mục bài viết">
        <div class="nb-container">
            <div class="nb-news-filter-bar">
                
                <!-- Category Pills -->
                <div class="nb-news-pills-list">
                    <a href="<?php echo esc_url($base_news_url); ?>" class="nb-news-pill <?php echo empty($selected_cat) ? 'active' : ''; ?>">
                        <i class="bi bi-grid-fill"></i>
                        <span>Tất cả bài viết</span>
                        <span class="nb-pill-badge"><?php echo esc_html($total_published); ?></span>
                    </a>
                    <?php if (!empty($news_cats)): foreach ($news_cats as $cat): 
                        $is_active = ($selected_cat === $cat->slug);
                        $icon = 'bi-bookmark-fill';
                        if ($cat->slug === 'review-sach') $icon = 'bi-book-half';
                        elseif ($cat->slug === 'kien-thuc-tips') $icon = 'bi-lightbulb-fill';
                        elseif ($cat->slug === 'su-kien-sach') $icon = 'bi-calendar-event-fill';
                        elseif ($cat->slug === 'tin-biblio') $icon = 'bi-megaphone-fill';
                    ?>
                        <a href="<?php echo esc_url(add_query_arg('chuyen_muc', $cat->slug, $base_news_url)); ?>" class="nb-news-pill <?php echo $is_active ? 'active' : ''; ?>">
                            <i class="bi <?php echo esc_attr($icon); ?>"></i>
                            <span><?php echo esc_html($cat->name); ?></span>
                            <span class="nb-pill-badge"><?php echo esc_html($cat->count); ?></span>
                        </a>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Fast Search Form -->
                <form method="get" action="<?php echo esc_url($base_news_url); ?>" class="nb-news-search-form">
                    <?php if (!empty($selected_cat)): ?>
                        <input type="hidden" name="chuyen_muc" value="<?php echo esc_attr($selected_cat); ?>" />
                    <?php endif; ?>
                    <div class="nb-news-search-inner">
                        <i class="bi bi-search nb-search-icon"></i>
                        <input 
                            type="text" 
                            name="s_news" 
                            value="<?php echo esc_attr($search_query); ?>" 
                            placeholder="Tìm bài viết, review sách..." 
                            class="nb-news-search-input"
                        />
                        <?php if (!empty($search_query)): ?>
                            <a href="<?php echo esc_url(remove_query_arg('s_news', add_query_arg('chuyen_muc', $selected_cat ?: null, $base_news_url))); ?>" class="nb-search-clear" title="Xóa từ khóa">✕</a>
                        <?php endif; ?>
                    </div>
                </form>

            </div>
        </div>
    </section>

    <!-- ===================================================================
         4. MAIN CONTENT AREA (2 COLUMNS: ARTICLES + SIDEBAR)
         =================================================================== -->
    <div class="nb-news-main-wrap">
        <div class="nb-container">
            <div class="nb-news-layout">

                <!-- Left Column: Articles Feed -->
                <div class="nb-news-feed-col">

                    <?php if ($main_query->have_posts()): ?>

                        <!-- Spotlight / Featured Article (Top article on Page 1 without search filter) -->
                        <?php 
                        if ($paged === 1 && empty($search_query)): 
                            $main_query->the_post();
                            $feat_id         = get_the_ID();
                            $feat_title      = get_the_title();
                            $feat_link       = get_permalink();
                            $feat_img        = get_the_post_thumbnail_url($feat_id, 'large') ?: get_stylesheet_directory_uri() . '/assets/images/news-placeholder.jpg';
                            $feat_date       = get_the_date('d/m/Y');
                            $feat_excerpt    = get_the_excerpt() ?: wp_trim_words(get_the_content(), 32, '...');
                            $feat_categories = get_the_category($feat_id);
                            $feat_cat_name   = !empty($feat_categories) ? $feat_categories[0]->name : 'Tin tức Biblio';
                        ?>
                            <article class="nb-spotlight-card">
                                <a href="<?php echo esc_url($feat_link); ?>" class="nb-spotlight-thumb-wrap">
                                    <img src="<?php echo esc_url($feat_img); ?>" alt="<?php echo esc_attr($feat_title); ?>" class="nb-spotlight-thumb" loading="lazy" />
                                    <span class="nb-spotlight-badge"><i class="bi bi-star-fill"></i> BÀI VIẾT NỔI BẬT</span>
                                </a>
                                <div class="nb-spotlight-body">
                                    <div class="nb-news-meta">
                                        <span class="nb-meta-cat"><i class="bi bi-tag-fill"></i> <?php echo esc_html($feat_cat_name); ?></span>
                                        <span class="nb-meta-dot">•</span>
                                        <?php nbook_render_news_date_badge($feat_id, 'spotlight'); ?>
                                        <span class="nb-meta-dot">•</span>
                                        <span class="nb-meta-read"><i class="bi bi-clock"></i> 4 phút đọc</span>
                                    </div>
                                    <h2 class="nb-spotlight-title">
                                        <a href="<?php echo esc_url($feat_link); ?>" title="<?php echo esc_attr($feat_title); ?>">
                                            <?php echo esc_html($feat_title); ?>
                                        </a>
                                    </h2>
                                    <p class="nb-spotlight-excerpt">
                                        <?php echo esc_html($feat_excerpt); ?>
                                    </p>
                                    <div class="nb-spotlight-footer">
                                        <div class="nb-author-block">
                                            <span class="nb-author-avatar"><i class="bi bi-person-fill"></i></span>
                                            <span class="nb-author-name">Ban Biên Tập Biblio</span>
                                        </div>
                                        <a href="<?php echo esc_url($feat_link); ?>" class="nb-btn-read-more">
                                            <span>Đọc bài viết</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endif; ?>

                        <!-- Standard Articles Grid Heading -->
                        <div class="nb-news-grid-header">
                            <h2 class="nb-grid-title">
                                <i class="bi bi-newspaper text-danger me-1"></i>
                                <?php 
                                if ($current_cat_obj) {
                                    echo 'Bài Viết: ' . esc_html($current_cat_obj->name);
                                } elseif (!empty($search_query)) {
                                    echo 'Kết Quả Tìm Kiếm: "' . esc_html($search_query) . '"';
                                } else {
                                    echo 'Tất Cả Bài Viết Mới Nhất';
                                }
                                ?>
                            </h2>
                            <span class="nb-grid-count">
                                Hiển thị <strong><?php echo esc_html($main_query->found_posts); ?></strong> bài viết
                            </span>
                        </div>

                        <!-- Articles Grid -->
                        <div class="nb-news-articles-grid">
                            <?php while ($main_query->have_posts()): $main_query->the_post(); 
                                $post_id     = get_the_ID();
                                $title       = get_the_title();
                                $link        = get_permalink();
                                $img_url     = get_the_post_thumbnail_url($post_id, 'medium_large') ?: get_stylesheet_directory_uri() . '/assets/images/news-placeholder.jpg';
                                $post_date   = get_the_date('d/m/Y');
                                $excerpt     = get_the_excerpt() ?: wp_trim_words(get_the_content(), 22, '...');
                                $categories  = get_the_category($post_id);
                                $cat_name    = !empty($categories) ? $categories[0]->name : 'Tin tức Biblio';
                                $cat_slug    = !empty($categories) ? $categories[0]->slug : 'tin-biblio';
                            ?>
                                <article class="nb-article-card">
                                    <div class="nb-article-thumb-wrap">
                                        <a href="<?php echo esc_url($link); ?>" class="nb-article-thumb-link">
                                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($title); ?>" class="nb-article-thumb" loading="lazy" />
                                        </a>
                                        <span class="nb-article-cat-tag nb-cat-<?php echo esc_attr($cat_slug); ?>">
                                            <?php echo esc_html($cat_name); ?>
                                        </span>
                                    </div>
                                    <div class="nb-article-body">
                                        <div class="nb-news-meta">
                                            <?php nbook_render_news_date_badge($post_id, 'card'); ?>
                                            <span class="nb-meta-dot">•</span>
                                            <span class="nb-meta-read"><i class="bi bi-clock"></i> 3 phút đọc</span>
                                        </div>
                                        <h3 class="nb-article-title">
                                            <a href="<?php echo esc_url($link); ?>" title="<?php echo esc_attr($title); ?>">
                                                <?php echo esc_html($title); ?>
                                            </a>
                                        </h3>
                                        <p class="nb-article-excerpt">
                                            <?php echo esc_html($excerpt); ?>
                                        </p>
                                        <div class="nb-article-footer">
                                            <span class="nb-article-author"><i class="bi bi-pen"></i> Biblio</span>
                                            <a href="<?php echo esc_url($link); ?>" class="nb-article-link">
                                                <span>Xem chi tiết</span>
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>

                        <!-- Numbered Pagination -->
                        <?php 
                        $total_pages = $main_query->max_num_pages;
                        if ($total_pages > 1):
                        ?>
                            <div class="nb-news-pagination-wrap">
                                <?php
                                echo paginate_links([
                                    'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                                    'format'    => '?paged=%#%',
                                    'current'   => $paged,
                                    'total'     => $total_pages,
                                    'prev_text' => '&larr; Trước',
                                    'next_text' => 'Sau &rarr;',
                                    'type'      => 'list',
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Empty Search / Filter State -->
                        <div class="nb-news-empty-state">
                            <i class="bi bi-journal-x nb-empty-icon"></i>
                            <h3 class="nb-empty-title">Không tìm thấy bài viết nào</h3>
                            <p class="nb-empty-desc">
                                Rất tiếc, không có bài viết nào phù hợp với chuyên mục hoặc từ khóa tìm kiếm của bạn.
                            </p>
                            <a href="<?php echo esc_url($base_news_url); ?>" class="nb-btn-empty-reset">
                                <i class="bi bi-arrow-counterclockwise"></i> Xem tất cả bài viết
                            </a>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Right Column: Sidebar Widgets -->
                <aside class="nb-news-sidebar-col">

                    <!-- Widget 1: Categories -->
                    <div class="nb-sidebar-widget">
                        <h4 class="nb-widget-title">
                            <i class="bi bi-bookmarks text-danger"></i> Chuyên Mục Tin Tức
                        </h4>
                        <ul class="nb-widget-cat-list">
                            <li class="<?php echo empty($selected_cat) ? 'active' : ''; ?>">
                                <a href="<?php echo esc_url($base_news_url); ?>">
                                    <span class="nb-wcat-name"><i class="bi bi-grid me-2 text-muted"></i> Tất cả bài viết</span>
                                    <span class="nb-wcat-count"><?php echo esc_html($total_published); ?></span>
                                </a>
                            </li>
                            <?php if (!empty($news_cats)): foreach ($news_cats as $cat): 
                                $is_active = ($selected_cat === $cat->slug);
                            ?>
                                <li class="<?php echo $is_active ? 'active' : ''; ?>">
                                    <a href="<?php echo esc_url(add_query_arg('chuyen_muc', $cat->slug, $base_news_url)); ?>">
                                        <span class="nb-wcat-name"><i class="bi bi-chevron-right me-2 text-muted"></i> <?php echo esc_html($cat->name); ?></span>
                                        <span class="nb-wcat-count"><?php echo esc_html($cat->count); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </div>

                    <!-- Widget 2: Popular / Trending Posts -->
                    <?php if ($trending_query->have_posts()): ?>
                        <div class="nb-sidebar-widget">
                            <h4 class="nb-widget-title">
                                <i class="bi bi-fire text-danger"></i> Bài Viết Mới Nhất
                            </h4>
                            <div class="nb-widget-posts-list">
                                <?php while ($trending_query->have_posts()): $trending_query->the_post(); 
                                    $t_id    = get_the_ID();
                                    $t_title = get_the_title();
                                    $t_link  = get_permalink();
                                    $t_img   = get_the_post_thumbnail_url($t_id, 'thumbnail') ?: get_stylesheet_directory_uri() . '/assets/images/news-placeholder.jpg';
                                    $t_date  = get_the_date('d/m/Y');
                                ?>
                                    <div class="nb-wpost-item">
                                        <a href="<?php echo esc_url($t_link); ?>" class="nb-wpost-thumb-wrap">
                                            <img src="<?php echo esc_url($t_img); ?>" alt="<?php echo esc_attr($t_title); ?>" class="nb-wpost-thumb" loading="lazy" />
                                        </a>
                                        <div class="nb-wpost-info">
                                            <?php nbook_render_news_date_badge($t_id, 'sidebar'); ?>
                                            <h5 class="nb-wpost-title">
                                                <a href="<?php echo esc_url($t_link); ?>" title="<?php echo esc_attr($t_title); ?>">
                                                    <?php echo esc_html($t_title); ?>
                                                </a>
                                            </h5>
                                        </div>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Widget 3: Book Promotion Banner -->
                    <div class="nb-sidebar-promo-card">
                        <div class="nb-promo-badge">🔥 ĐẶC QUYỀN SINH VIÊN</div>
                        <h4 class="nb-promo-title">Tủ Sách Tri Thức &amp; Khởi Nghiệp</h4>
                        <p class="nb-promo-desc">Hơn 145+ đầu sách kinh điển, giáo trình NEU và kỹ năng sống chính hãng trợ giá đến 40%.</p>
                        <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-btn-promo">
                            <span>Khám phá ngay</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Widget 4: Newsletter Subscription -->
                    <div class="nb-sidebar-widget nb-widget-newsletter">
                        <div class="nb-nl-icon-wrap"><i class="bi bi-envelope-paper-heart-fill"></i></div>
                        <h4 class="nb-nl-title">Nhận Bản Tin Tri Thức</h4>
                        <p class="nb-nl-desc">Đăng ký thành viên Biblio để nhận thông báo bài review sách mới nhất và voucher giảm 50.000₫.</p>
                        <?php $acc_reg = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') . '#register' : home_url('/tai-khoan/#register'); ?>
                        <a href="<?php echo esc_url($acc_reg); ?>" class="nb-nl-submit nb-open-auth-modal" data-tab="register" style="display:inline-flex; align-items:center; justify-content:center; gap:6px; text-decoration:none;">
                            <i class="bi bi-person-plus-fill"></i> Đăng ký thành viên ngay
                        </a>
                    </div>

                </aside>

            </div>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var clockEl = document.getElementById('nbRealtimeClock');
    if (!clockEl) return;

    var serverTs = parseInt(clockEl.getAttribute('data-server-time'), 10) || Math.floor(Date.now() / 1000);
    var clientStart = Math.floor(Date.now() / 1000);
    var daysVn = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];

    function padZero(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function updateLiveClock() {
        var elapsed = Math.floor(Date.now() / 1000) - clientStart;
        var currentUnix = serverTs + elapsed;
        var d = new Date(currentUnix * 1000);

        var dayName = daysVn[d.getDay()];
        var dateStr = padZero(d.getDate()) + '/' + padZero(d.getMonth() + 1) + '/' + d.getFullYear();
        var timeStr = padZero(d.getHours()) + ':' + padZero(d.getMinutes()) + ':' + padZero(d.getSeconds());

        clockEl.textContent = dayName + ', ' + dateStr + ' – ' + timeStr;
    }

    // Ticking every 1000ms
    setInterval(updateLiveClock, 1000);

    // Dynamic relative time updater for articles every 30 seconds
    function updateRelativeTimes() {
        var elapsed = Math.floor(Date.now() / 1000) - clientStart;
        var currentUnix = serverTs + elapsed;

        var items = document.querySelectorAll('.nb-realtime-item[data-ts]');
        items.forEach(function(item) {
            var postTs = parseInt(item.getAttribute('data-ts'), 10);
            if (!postTs) return;

            var diff = Math.max(0, currentUnix - postTs);
            var txtEl = item.querySelector('.nb-rt-txt') || item.querySelector('time');
            if (!txtEl) return;

            var human = '';
            if (diff < 90) {
                human = 'Vừa xong';
            } else if (diff < 3600) {
                human = Math.max(1, Math.round(diff / 60)) + ' phút trước';
            } else {
                var d = new Date(postTs * 1000);
                var curD = new Date(currentUnix * 1000);
                var isToday = (d.getDate() === curD.getDate() && d.getMonth() === curD.getMonth() && d.getFullYear() === curD.getFullYear());
                if (isToday) {
                    var hrs = Math.max(1, Math.round(diff / 3600));
                    human = 'Hôm nay lúc ' + padZero(d.getHours()) + ':' + padZero(d.getMinutes()) + ' (' + hrs + ' giờ trước)';
                } else if (diff < 86400 * 2) {
                    human = 'Hôm qua lúc ' + padZero(d.getHours()) + ':' + padZero(d.getMinutes());
                } else if (diff < 7 * 86400) {
                    var days = Math.max(1, Math.round(diff / 86400));
                    human = days + ' ngày trước (' + padZero(d.getDate()) + '/' + padZero(d.getMonth() + 1) + '/' + d.getFullYear() + ')';
                }
            }

            if (human) {
                txtEl.textContent = human;
            }
        });
    }

    setInterval(updateRelativeTimes, 30000);

    // Refresh button click
    var refreshBtn = document.getElementById('nbBtnRefreshFeed');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var icon = refreshBtn.querySelector('i');
            if (icon) icon.classList.add('nb-spin-fast');
            refreshBtn.disabled = true;

            updateLiveClock();
            updateRelativeTimes();

            setTimeout(function() {
                if (icon) icon.classList.remove('nb-spin-fast');
                refreshBtn.disabled = false;
                
                var syncLabel = document.querySelector('.nb-live-sync-indicator span');
                if (syncLabel) {
                    var orig = syncLabel.textContent;
                    syncLabel.textContent = 'Đã đồng bộ dữ liệu thời gian thực mới nhất!';
                    syncLabel.style.color = '#10b981';
                    syncLabel.style.fontWeight = 'bold';
                    setTimeout(function() {
                        syncLabel.textContent = orig;
                        syncLabel.style.color = '';
                        syncLabel.style.fontWeight = '';
                    }, 4000);
                }
            }, 600);
        });
    }
});
</script>

<?php
get_footer();
