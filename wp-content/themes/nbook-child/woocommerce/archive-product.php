<?php
/**
 * The Template for displaying product archives (Shop page & Category pages)
 * Designed exactly after Biblio Catalog Layout (http://127.0.0.1:5000/books/)
 */

defined('ABSPATH') || exit;

global $wp_query;

get_header('shop');

// Current queried object
$current_term = is_product_taxonomy() ? get_queried_object() : null;
$shop_url     = wc_get_page_permalink('shop');
$base_url     = $current_term ? get_term_link($current_term) : $shop_url;

// Query parameters
$search_query = get_search_query();
$price_preset = isset($_GET['nbook_price_preset']) ? sanitize_text_field($_GET['nbook_price_preset']) : 'all';
$in_stock     = !empty($_GET['nbook_in_stock']);
$sort         = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'menu_order';

// Total books count across store
$total_books_count = wp_count_posts('product')->publish;

// Fetch canonical categories for Biblio
$canonical_slugs = ['van-hoc', 'tieu-thuyet', 'kinh-te-ky-nang', 'sach-ky-nang', 'thieu-nhi', 'sach-moi', 'sach-giao-duc', 'sach-ngoai-ngu'];
$categories = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'slug'       => $canonical_slugs,
    'orderby'    => 'count',
    'order'      => 'DESC',
]);

// Determine if any filters are active
$has_filters = (!empty($search_query) || ($price_preset !== 'all' && !empty($price_preset)) || $in_stock || !empty($current_term));
?>

<div class="nb-catalog-wrapper py-3">
    <div class="container nb-catalog-container">

        <!-- Breadcrumb Navigation -->
        <nav class="nb-shop-breadcrumb mb-3" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
            <span class="nb-bc-sep">&gt;</span>
            <?php if ($current_term): ?>
                <a href="<?php echo esc_url($shop_url); ?>">Sản phẩm</a>
                <span class="nb-bc-sep">&gt;</span>
                <span class="nb-bc-current"><?php echo esc_html($current_term->name); ?></span>
            <?php else: ?>
                <span class="nb-bc-current">Sản phẩm</span>
            <?php endif; ?>
        </nav>

        <!-- 2-Column Catalog Layout -->
        <div class="catalog-layout">

            <!-- Left Filter Sidebar -->
            <aside class="catalog-sidebar">
                <form id="catalogFilterForm" method="get" action="<?php echo esc_url($base_url); ?>" class="filter-sidebar-pro">
                    
                    <!-- Search Keyword inside form -->
                    <div class="filter-sidebar-block filter-keyword-block">
                        <div class="filter-group-heading">
                            <span><i class="bi bi-search me-1 text-primary"></i> Từ khóa</span>
                        </div>
                        <div class="filter-search-box">
                            <input
                                type="text"
                                name="s"
                                class="filter-search-input"
                                value="<?php echo esc_attr($search_query); ?>"
                                placeholder="Tên sách hoặc tác giả"
                            />
                            <i class="bi bi-search filter-search-icon"></i>
                        </div>
                    </div>

                    <!-- Category Filter Group -->
                    <div class="filter-sidebar-block">
                        <div class="filter-group-heading">
                            <span><i class="bi bi-bookmarks-fill me-1 text-danger"></i> Tất cả thể loại</span>
                        </div>
                        <ul class="filter-category-list">
                            <li class="filter-category-item <?php echo empty($current_term) ? 'active' : ''; ?>">
                                <a href="<?php echo esc_url(add_query_arg(array_filter(['s' => $search_query ?: null, 'nbook_price_preset' => $price_preset !== 'all' ? $price_preset : null, 'nbook_in_stock' => $in_stock ? '1' : null]), $shop_url)); ?>">
                                    <span>Tất cả thể loại</span>
                                    <span class="count-badge"><?php echo esc_html($total_books_count); ?></span>
                                </a>
                            </li>
                            <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                                <?php foreach ($categories as $cat): 
                                    $is_cat_active = ($current_term && $current_term->term_id === $cat->term_id);
                                    $cat_link = add_query_arg(array_filter(['s' => $search_query ?: null, 'nbook_price_preset' => $price_preset !== 'all' ? $price_preset : null, 'nbook_in_stock' => $in_stock ? '1' : null]), get_term_link($cat));
                                ?>
                                    <li class="filter-category-item <?php echo $is_cat_active ? 'active' : ''; ?>">
                                        <a href="<?php echo esc_url($cat_link); ?>">
                                            <span><?php echo esc_html($cat->name); ?></span>
                                            <span class="count-badge"><?php echo esc_html($cat->count); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Hidden inputs to preserve sort state and category -->
                    <input type="hidden" name="orderby" id="formSortInput" value="<?php echo esc_attr($sort); ?>" />
                    <?php if ($current_term): ?>
                        <input type="hidden" name="product_cat" value="<?php echo esc_attr($current_term->slug); ?>" />
                    <?php endif; ?>

                    <!-- Price Range Filter (Quick Client-Side Filter + Server-Side Fallback) -->
                    <div class="filter-sidebar-block">
                        <div class="filter-group-heading">
                            <span><i class="bi bi-cash-stack me-1 text-success"></i> Mức giá</span>
                        </div>
                        <div class="filter-price-options" id="priceFilterGroup">
                            <label class="price-option-label">
                                <input type="radio" name="nbook_price_preset" value="all" <?php checked($price_preset, 'all'); ?> class="form-check-input" />
                                <span>Tất cả mức giá</span>
                            </label>
                            <label class="price-option-label">
                                <input type="radio" name="nbook_price_preset" value="under_50k" <?php checked($price_preset, 'under_50k'); ?> class="form-check-input" />
                                <span>Dưới 50.000 ₫</span>
                            </label>
                            <label class="price-option-label">
                                <input type="radio" name="nbook_price_preset" value="50k_100k" <?php checked($price_preset, '50k_100k'); ?> class="form-check-input" />
                                <span>50.000 ₫ - 100.000 ₫</span>
                            </label>
                            <label class="price-option-label">
                                <input type="radio" name="nbook_price_preset" value="100k_200k" <?php checked($price_preset, '100k_200k'); ?> class="form-check-input" />
                                <span>100.000 ₫ - 200.000 ₫</span>
                            </label>
                            <label class="price-option-label">
                                <input type="radio" name="nbook_price_preset" value="above_200k" <?php checked($price_preset, 'above_200k'); ?> class="form-check-input" />
                                <span>Trên 200.000 ₫</span>
                            </label>
                        </div>
                    </div>

                    <!-- Stock Filter -->
                    <div class="filter-sidebar-block">
                        <div class="filter-group-heading">
                            <span><i class="bi bi-box-seam me-1 text-warning"></i> Trạng thái</span>
                        </div>
                        <label class="filter-stock-label" for="stockOnlyCheckbox">
                            <input class="form-check-input" type="checkbox" id="stockOnlyCheckbox" name="nbook_in_stock" value="1" <?php checked($in_stock); ?> />
                            <span>Chỉ hiện sách còn hàng</span>
                        </label>
                    </div>

                    <!-- Filter Actions -->
                    <div class="filter-actions-group">
                        <button type="submit" class="btn-filter-apply">
                            <i class="bi bi-funnel-fill"></i> Áp dụng bộ lọc
                        </button>
                        <a href="<?php echo esc_url($shop_url); ?>" class="btn-filter-reset">
                            <i class="bi bi-arrow-counterclockwise"></i> Xóa bộ lọc
                        </a>
                    </div>

                </form>
            </aside>

            <!-- Right Main Products Area -->
            <main class="catalog-main">

                <!-- Toolbar -->
                <div class="catalog-toolbar-pro">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <?php 
                        $displayed_count = (isset($wp_query) && isset($wp_query->found_posts)) ? $wp_query->found_posts : ($current_term ? $current_term->count : $total_books_count);
                        ?>
                        <span class="toolbar-result-count">
                            <?php if ($current_term): ?>
                                Danh mục <strong><?php echo esc_html($current_term->name); ?></strong>: có <strong id="displayedBookCount"><?php echo esc_html($displayed_count); ?></strong> sản phẩm
                            <?php else: ?>
                                Danh mục sách: có <strong id="displayedBookCount"><?php echo esc_html($displayed_count); ?></strong> sản phẩm
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Sort Select Control -->
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <label for="catalogSortSelect" class="small text-muted text-nowrap d-none d-sm-inline">
                            <i class="bi bi-sort-down"></i> Sắp xếp:
                        </label>
                        <select
                            id="catalogSortSelect"
                            class="form-select form-select-sm"
                            style="min-width: 170px;"
                            onchange="document.getElementById('formSortInput').value = this.value; document.getElementById('catalogFilterForm').submit();"
                        >
                            <option value="menu_order" <?php selected($sort, 'menu_order'); ?>>Mặc định</option>
                            <option value="title_asc" <?php selected($sort, 'title_asc'); ?>>Tên A → Z</option>
                            <option value="price_asc" <?php selected($sort, 'price_asc'); ?>>Giá tăng dần</option>
                            <option value="price_desc" <?php selected($sort, 'price_desc'); ?>>Giá giảm dần</option>
                            <option value="date" <?php selected($sort, 'date'); ?>>Mới nhất</option>
                            <option value="popularity" <?php selected($sort, 'popularity'); ?>>Bán chạy nhất</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (have_posts()): ?>
                    <div class="catalog-products-grid" id="catalogProductGrid">
                        <?php while (have_posts()): the_post();
                            global $product;
                            if (!$product) $product = wc_get_product(get_the_ID());
                            if (!$product) continue;

                            $pid           = $product->get_id();
                            $title         = $product->get_name();
                            $link          = $product->get_permalink();
                            $img_url       = get_the_post_thumbnail_url($pid, 'large') ?: wc_placeholder_img_src();
                            $price         = (float) $product->get_price();
                            $reg_price     = (float) $product->get_regular_price();
                            $sale_price    = (float) $product->get_sale_price();
                            $stock_qty     = $product->get_stock_quantity();
                            $is_in_stock   = $product->is_in_stock();
                            $stock_info    = function_exists('nbook_get_product_stock_info') ? nbook_get_product_stock_info($product) : [
                                'state'           => $is_in_stock ? 'instock' : 'outofstock',
                                'label'           => $is_in_stock ? 'Còn hàng' : 'Hết hàng',
                                'badge_html'      => '<span class="nb-stock-badge ' . ($is_in_stock ? 'nb-stock-instock' : 'nb-stock-outofstock') . '">' . ($is_in_stock ? 'Còn hàng' : 'Hết hàng') . '</span>',
                                'qty'             => $stock_qty,
                                'is_out_of_stock' => !$is_in_stock,
                            ];
                            $author        = get_post_meta($pid, '_biblio_author', true) ?: get_post_meta($pid, '_book_author', true) ?: get_post_meta($pid, '_nbook_author', true) ?: 'Nhiều tác giả';
                            $product_cats  = get_the_terms($pid, 'product_cat');
                            $prod_cat_slugs= (!empty($product_cats) && !is_wp_error($product_cats)) ? wp_list_pluck($product_cats, 'slug') : [];
                            $cat_name      = (!empty($product_cats) && !is_wp_error($product_cats)) ? $product_cats[0]->name : 'Sách Tuyển Chọn';

                            // Discount calculation
                            $discount_pct = 0;
                            if ($reg_price > 0 && $sale_price > 0 && $reg_price > $sale_price) {
                                $discount_pct = round((($reg_price - $sale_price) / $reg_price) * 100);
                            }

                            // Rating stars (generate consistent pleasant score based on pid)
                            $rating_score = '5.0';
                            $rating_count = 10 + ($pid % 39);
                        ?>
                            <div class="book-item-col" data-price="<?php echo esc_attr((int)$price); ?>" data-stock="<?php echo esc_attr($stock_info['qty'] !== null ? $stock_info['qty'] : ($is_in_stock ? 50 : 0)); ?>" data-stock-state="<?php echo esc_attr($stock_info['state']); ?>" data-categories="<?php echo esc_attr(implode(',', $prod_cat_slugs)); ?>">
                                <article class="product-card-pro">
                                    <div class="product-thumb-wrapper">
                                        <div class="product-badge-group">
                                            <?php if ($discount_pct > 0): ?>
                                                <span class="badge-tag-sale"><i class="bi bi-lightning-charge-fill"></i> GIẢM <?php echo esc_html($discount_pct); ?>%</span>
                                            <?php else: ?>
                                                <span class="badge-tag-sale">HOT</span>
                                            <?php endif; ?>
                                            <span class="badge-tag-freeship"><i class="bi bi-lightning-charge-fill"></i> FREESHIP</span>
                                        </div>
                                        <a href="<?php echo esc_url($link); ?>" class="d-flex align-items-center justify-content-center w-100 h-100 text-decoration-none">
                                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($title); ?>" class="<?php echo $stock_info['is_out_of_stock'] ? 'nb-thumb-dimmed' : ''; ?>" loading="lazy" />
                                        </a>
                                    </div>

                                    <div class="product-card-body">
                                        <div class="product-category-meta">
                                            <?php echo esc_html($cat_name); ?>
                                        </div>

                                        <a class="product-title-link" href="<?php echo esc_url($link); ?>" title="<?php echo esc_attr($title); ?>">
                                            <?php echo esc_html($title); ?>
                                        </a>

                                        <div class="product-author">
                                            <i class="bi bi-person me-1"></i><?php echo esc_html($author); ?>
                                        </div>

                                        <?php if (function_exists('nbook_render_product_rating')): ?>
                                            <?php nbook_render_product_rating($product, 'archive'); ?>
                                        <?php else: ?>
                                            <div class="product-rating" aria-label="Đánh giá 5 sao">
                                                <i class="bi bi-star-fill text-warning"></i>
                                                <i class="bi bi-star-fill text-warning"></i>
                                                <i class="bi bi-star-fill text-warning"></i>
                                                <i class="bi bi-star-fill text-warning"></i>
                                                <i class="bi bi-star-fill text-warning"></i>
                                                <span class="rating-count"><?php echo esc_html($rating_score); ?> (<?php echo esc_html($rating_count); ?>)</span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="product-card-footer">
                                            <div class="product-price-block">
                                                <strong class="product-current-price"><?php echo wc_price($price); ?></strong>
                                                <?php if ($sale_price > 0 && $reg_price > $sale_price): ?>
                                                    <del class="small text-muted text-decoration-line-through"><?php echo wc_price($reg_price); ?></del>
                                                <?php endif; ?>
                                                <div class="mt-1">
                                                    <?php echo $stock_info['badge_html']; ?>
                                                </div>
                                            </div>

                                            <?php if ($stock_info['is_out_of_stock']): ?>
                                                <button type="button" 
                                                        class="btn-card-add-cart btn-stock-notify-card nb-open-stock-notify-modal" 
                                                        data-product-id="<?php echo esc_attr($pid); ?>" 
                                                        data-product-title="<?php echo esc_attr($title); ?>" 
                                                        title="Thông báo khi có hàng" 
                                                        aria-label="Thông báo khi có hàng">
                                                    <i class="bi bi-bell-fill"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                                                   data-quantity="1" 
                                                   class="btn-card-add-cart button product_type_simple add_to_cart_button ajax_add_to_cart" 
                                                   data-product_id="<?php echo esc_attr($pid); ?>" 
                                                   data-product_sku="<?php echo esc_attr($product->get_sku()); ?>" 
                                                   title="Thêm vào giỏ" 
                                                   aria-label="Thêm vào giỏ">
                                                    <i class="bi bi-cart-plus"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Client-Side No Results Message -->
                    <div id="clientNoResults" class="d-none text-center py-5 my-4 bg-white rounded-3 border" style="display: none !important;">
                        <i class="bi bi-search text-muted display-4 mb-3 d-block"></i>
                        <h4 class="fw-bold text-dark">Không tìm thấy sản phẩm phù hợp</h4>
                        <p class="text-muted small">Vui lòng điều chỉnh lại mức giá hoặc bỏ chọn bộ lọc trạng thái kho.</p>
                        <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-ecom-primary btn-sm px-4 py-2 mt-2">Xem tất cả sản phẩm</a>
                    </div>

                    <!-- Pagination -->
                    <div class="nb-catalog-pagination-wrap" id="catalogPagination">
                        <?php
                        the_posts_pagination([
                            'prev_text' => '&larr; Trước',
                            'next_text' => 'Sau &rarr;',
                            'type'      => 'list',
                        ]);
                        ?>
                    </div>

                <?php else: ?>
                    <div class="text-center py-5 my-4 bg-white rounded-3 border">
                        <i class="bi bi-book-half text-muted display-4 mb-3 d-block"></i>
                        <h4 class="fw-bold text-dark">Chưa có sản phẩm nào</h4>
                        <p class="text-muted small">Không tìm thấy sản phẩm phù hợp với tiêu chí lọc của bạn.</p>
                        <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-ecom-primary btn-sm px-4 py-2 mt-2">Xem tất cả sản phẩm</a>
                    </div>
                <?php endif; ?>

            </main>
        </div>

    </div>
</div>

<script>
// Instant Price Range & Stock Client-Side Filtering (Inherited from http://127.0.0.1:5000/books/)
document.addEventListener("DOMContentLoaded", () => {
    const priceRadios = document.querySelectorAll('input[name="nbook_price_preset"]');
    const stockCheckbox = document.getElementById("stockOnlyCheckbox");
    const bookCols = document.querySelectorAll(".book-item-col");
    const counter = document.getElementById("displayedBookCount");
    const noResults = document.getElementById("clientNoResults");
    const grid = document.getElementById("catalogProductGrid");
    const pagination = document.querySelector(".nb-catalog-pagination-wrap");

    function applyFilter() {
        const selectedRange = document.querySelector('input[name="nbook_price_preset"]:checked')?.value || "all";
        const onlyStock = stockCheckbox?.checked || false;
        let visibleCount = 0;

        bookCols.forEach((col) => {
            const price = parseInt(col.dataset.price || "0", 10);
            const stock = parseInt(col.dataset.stock || "0", 10);
            const state = col.dataset.stockState || "";

            let matchPrice = true;
            if (selectedRange === "under_50k") matchPrice = price < 50000;
            else if (selectedRange === "50k_100k") matchPrice = price >= 50000 && price <= 100000;
            else if (selectedRange === "100k_200k") matchPrice = price >= 100000 && price <= 200000;
            else if (selectedRange === "above_200k") matchPrice = price > 200000;

            let matchStock = true;
            if (onlyStock && (state === 'outofstock' || stock <= 0)) matchStock = false;

            if (matchPrice && matchStock) {
                col.style.display = "";
                visibleCount++;
            } else {
                col.style.display = "none";
            }
        });

        if (counter) counter.textContent = visibleCount;
        if (noResults && grid) {
            if (visibleCount === 0 && bookCols.length > 0) {
                noResults.style.setProperty("display", "block", "important");
                noResults.classList.remove("d-none");
                if (pagination) pagination.style.display = "none";
            } else {
                noResults.style.setProperty("display", "none", "important");
                noResults.classList.add("d-none");
                if (pagination) pagination.style.display = "";
            }
        }
    }

    priceRadios.forEach((radio) => radio.addEventListener("change", applyFilter));
    stockCheckbox?.addEventListener("change", applyFilter);
});
</script>

<?php
get_footer('shop');
