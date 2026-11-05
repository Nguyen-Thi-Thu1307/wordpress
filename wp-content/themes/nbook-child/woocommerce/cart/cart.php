<?php
/**
 * Custom Biblio Cart Template
 * Designed after Shopee / Biblio Modern E-Commerce Standard
 * Fully AJAX-driven with real-time recalculations and Modal confirmations
 */

defined('ABSPATH') || exit;

// Free shipping threshold: 250.000 VND
$freeship_threshold = 250000;
$cart_subtotal_raw   = WC()->cart->get_subtotal();
$is_freeship         = ($cart_subtotal_raw >= $freeship_threshold);
$remaining_freeship  = max(0, $freeship_threshold - $cart_subtotal_raw);
$freeship_percent    = min(100, round(($cart_subtotal_raw / $freeship_threshold) * 100));

// Fetch recommended products for cross-sell section
$rec_products = wc_get_products([
    'limit'   => 4,
    'status'  => 'publish',
    'orderby' => 'popularity',
    'order'   => 'DESC',
]);
if (empty($rec_products)) {
    $rec_products = wc_get_products([
        'limit'   => 4,
        'status'  => 'publish',
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);
}
?>

<div class="nb-cart-page-wrapper" id="nbCartPageWrap">
    
    <!-- ===================================================================
         1. BREADCRUMBS & CART HEADER
         =================================================================== -->
    <div class="nb-cart-header-strip">
        <nav class="nb-cart-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
            <span class="nb-bc-sep">&gt;</span>
            <span class="nb-bc-current">Giỏ hàng (<span class="nb-cart-total-badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span> sản phẩm)</span>
        </nav>
    </div>

    <?php if (is_user_logged_in() && (current_user_can('manage_options') || current_user_can('manage_woocommerce') || current_user_can('manage_biblio_orders'))): 
        $acc_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
        $pending_orders_count = (int) wc_orders_count('pending') + (int) wc_orders_count('processing');
    ?>
        <!-- Admin Smart Strip in Cart -->
        <div class="nb-cart-admin-strip">
            <div class="nb-cas-left">
                <span class="nb-cas-badge"><i class="bi bi-shield-lock-fill"></i> Quản Trị Viên</span>
                <span>Bạn đang xem giỏ hàng với quyền Admin. Hệ thống hiện có <strong class="text-danger"><?php echo esc_html($pending_orders_count); ?> đơn hàng</strong> cần tiếp nhận &amp; duyệt.</span>
            </div>
            <div class="nb-cas-right">
                <a href="<?php echo esc_url(add_query_arg('admin_tab', 'orders', $acc_url)); ?>" class="nb-cas-btn nb-cas-btn-danger">
                    <i class="bi bi-inbox-fill"></i> Duyệt Đơn Hàng Ngay
                </a>
                <a href="<?php echo esc_url(add_query_arg('admin_tab', 'overview', $acc_url)); ?>" class="nb-cas-btn nb-cas-btn-primary">
                    <i class="bi bi-speedometer2"></i> Trung Tâm Quản Trị
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!is_user_logged_in()): 
        $acc_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
    ?>
        <!-- Guest Cart Reminder Strip -->
        <div class="nb-cart-guest-strip">
            <div class="nb-cgs-left">
                <i class="bi bi-info-circle-fill text-warning"></i>
                <span>Bạn đang mua sách với tư cách khách. <strong>Đăng nhập</strong> để áp dụng voucher ưu đãi và lưu giỏ hàng của bạn.</span>
            </div>
            <div class="nb-cgs-right">
                <a href="<?php echo esc_url($acc_url); ?>#login" class="nb-cgs-btn nb-cgs-login nb-open-auth-modal" data-tab="login">
                    <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                </a>
                <a href="<?php echo esc_url($acc_url); ?>#register" class="nb-cgs-btn nb-cgs-reg nb-open-auth-modal" data-tab="register">
                    <i class="bi bi-person-plus"></i> Đăng ký
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         2. FREE SHIPPING PROGRESS BAR
         =================================================================== -->
    <div class="nb-freeship-bar-card" id="nbFreeshipCard">
        <div class="nb-fs-content">
            <div class="nb-fs-icon-wrap <?php echo $is_freeship ? 'success' : ''; ?>">
                <i class="bi <?php echo $is_freeship ? 'bi-patch-check-fill' : 'bi-truck'; ?>"></i>
            </div>
            <div class="nb-fs-text-wrap">
                <?php if ($is_freeship): ?>
                    <span class="nb-fs-msg success">
                        🎉 Chúc mừng! Đơn hàng của bạn đã đủ điều kiện <strong>MIỄN PHÍ VẬN CHUYỂN</strong> toàn quốc!
                    </span>
                <?php else: ?>
                    <span class="nb-fs-msg">
                        Mua thêm <strong class="nb-fs-amount"><?php echo wc_price($remaining_freeship); ?></strong> để được <strong>MIỄN PHÍ VẬN CHUYỂN</strong> toàn quốc!
                    </span>
                <?php endif; ?>
                <div class="nb-fs-track">
                    <div class="nb-fs-fill <?php echo $is_freeship ? 'full' : ''; ?>" style="width: <?php echo esc_attr($freeship_percent); ?>%;"></div>
                </div>
            </div>
            <?php if (!$is_freeship): ?>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="nb-fs-action-link">
                    Mua thêm <i class="bi bi-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===================================================================
         3. CART ITEMS TABLE (SHOPEE ROW STYLE)
         =================================================================== -->
    <div class="nb-cart-table-card">
        
        <!-- Table Column Headers -->
        <div class="nb-cart-table-head">
            <div class="nb-th-col nb-th-check">
                <label class="nb-custom-checkbox">
                    <input type="checkbox" id="nbCheckAllTop" class="nb-select-all-input" checked />
                    <span class="nb-checkmark-box"></span>
                    <span class="nb-th-label">Sản phẩm</span>
                </label>
            </div>
            <div class="nb-th-col nb-th-price">Đơn giá</div>
            <div class="nb-th-col nb-th-qty">Số lượng</div>
            <div class="nb-th-col nb-th-total">Số tiền</div>
            <div class="nb-th-col nb-th-actions">Thao tác</div>
        </div>

        <!-- Table Product Rows -->
        <div class="nb-cart-items-list" id="nbCartItemsList">
            <?php
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item):
                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)):
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                    $product_name = $_product->get_name();
                    $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('medium', ['class' => 'nb-cart-thumb']), $cart_item, $cart_item_key);

                    // Pricing
                    $current_price = (float) $_product->get_price();
                    $regular_price = (float) $_product->get_regular_price();
                    $line_total_raw = $current_price * $cart_item['quantity'];
                    $has_discount = ($regular_price > $current_price && $regular_price > 0);
                    $saving_amount = $has_discount ? ($regular_price - $current_price) * $cart_item['quantity'] : 0;

                    // Stock limit
                    $max_qty = $_product->get_max_purchase_quantity();
                    if ($max_qty <= 0) $max_qty = 99;
                    $min_qty = 1;

                    // Categories or taxonomy
                    $cats = wc_get_product_category_list($product_id, ', ');
            ?>
                <div class="nb-cart-item-row" id="nbCartRow-<?php echo esc_attr($cart_item_key); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                    
                    <!-- 1. Checkbox & Product Details -->
                    <div class="nb-td-col nb-td-product">
                        <label class="nb-custom-checkbox">
                            <input type="checkbox" 
                                   class="nb-item-checkbox" 
                                   data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                   data-unit-price="<?php echo esc_attr($current_price); ?>"
                                   data-regular-price="<?php echo esc_attr($regular_price > 0 ? $regular_price : $current_price); ?>"
                                   data-qty="<?php echo esc_attr($cart_item['quantity']); ?>"
                                   checked />
                            <span class="nb-checkmark-box"></span>
                        </label>
                        <div class="nb-item-thumb-box">
                            <?php if ($product_permalink): ?>
                                <a href="<?php echo esc_url($product_permalink); ?>"><?php echo $thumbnail; ?></a>
                            <?php else: ?>
                                <?php echo $thumbnail; ?>
                            <?php endif; ?>
                        </div>
                        <div class="nb-item-meta-box">
                            <h4 class="nb-item-title">
                                <?php if ($product_permalink): ?>
                                    <a href="<?php echo esc_url($product_permalink); ?>"><?php echo esc_html($product_name); ?></a>
                                <?php else: ?>
                                    <?php echo esc_html($product_name); ?>
                                <?php endif; ?>
                            </h4>
                            <?php if ($cats): ?>
                                <div class="nb-item-category">Thể loại: <?php echo wp_strip_all_tags($cats); ?></div>
                            <?php endif; ?>
                            <div class="nb-item-badges">
                                <span class="nb-badge-authentic"><i class="bi bi-shield-check"></i> 100% Chính hãng</span>
                                <?php if ($has_discount): ?>
                                    <span class="nb-badge-onsale">⚡ Tiết kiệm <?php echo round((($regular_price - $current_price) / $regular_price) * 100); ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Unit Price -->
                    <div class="nb-td-col nb-td-price">
                        <div class="nb-price-wrap">
                            <span class="nb-current-price"><?php echo wc_price($current_price); ?></span>
                            <?php if ($has_discount): ?>
                                <span class="nb-regular-price"><?php echo wc_price($regular_price); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 3. Quantity Controls -->
                    <div class="nb-td-col nb-td-qty">
                        <div class="nb-qty-control-box">
                            <button type="button" 
                                    class="nb-qty-btn nb-qty-dec" 
                                    data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                    aria-label="Giảm số lượng"
                                    <?php echo ($cart_item['quantity'] <= 1) ? 'disabled' : ''; ?>>
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" 
                                   class="nb-qty-num-input" 
                                   value="<?php echo esc_attr($cart_item['quantity']); ?>" 
                                   min="<?php echo esc_attr($min_qty); ?>" 
                                   max="<?php echo esc_attr($max_qty); ?>" 
                                   data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                   aria-label="Số lượng sách" />
                            <button type="button" 
                                    class="nb-qty-btn nb-qty-inc" 
                                    data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                    aria-label="Tăng số lượng"
                                    <?php echo ($cart_item['quantity'] >= $max_qty) ? 'disabled' : ''; ?>>
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 4. Line Total -->
                    <div class="nb-td-col nb-td-total">
                        <span class="nb-line-total-amount" id="nbLineTotal-<?php echo esc_attr($cart_item_key); ?>">
                            <?php echo wc_price($line_total_raw); ?>
                        </span>
                    </div>

                    <!-- 5. Actions / Remove -->
                    <div class="nb-td-col nb-td-actions">
                        <button type="button" 
                                class="nb-item-remove-trigger" 
                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>" 
                                data-product-title="<?php echo esc_attr($product_name); ?>"
                                title="Xóa sách khỏi giỏ hàng">
                            <i class="bi bi-trash3"></i> <span class="nb-del-txt">Xóa</span>
                        </button>
                    </div>

                </div>
            <?php
                endif;
            endforeach;
            ?>
        </div>

    </div>

    <!-- ===================================================================
         4. VOUCHER & COUPON APPLICATION SECTION
         =================================================================== -->
    <div class="nb-cart-coupon-card">
        <div class="nb-coupon-header">
            <span class="nb-coupon-title"><i class="bi bi-ticket-perforated-fill text-danger me-2"></i> Biblio Voucher &amp; Mã Giảm Giá</span>
            <span class="nb-coupon-hint">Chọn hoặc nhập mã ưu đãi bên dưới</span>
        </div>
        
        <!-- Input Form -->
        <div class="nb-coupon-form-row">
            <div class="nb-coupon-input-wrap">
                <i class="bi bi-ticket-detailed nb-coupon-icon"></i>
                <input type="text" id="nbCouponCodeInput" placeholder="Nhập mã giảm giá (ví dụ: FREESHIP, BIBLIO50K)..." class="nb-coupon-input" />
            </div>
            <button type="button" id="nbApplyCouponBtn" class="nb-coupon-apply-btn">
                <span>Áp Dụng</span>
            </button>
        </div>

        <!-- Feedback Alert -->
        <div id="nbCouponFeedback" class="nb-coupon-feedback" style="display:none;"></div>

        <!-- Quick Select Preset Vouchers -->
        <div class="nb-preset-vouchers-list">
            <?php
            $vouchers_presets = [
                ['code' => 'FREESHIP', 'title' => 'FREESHIP 25K', 'desc' => 'Đơn từ 250K'],
                ['code' => 'BIBLIO50K', 'title' => 'GIẢM 50K', 'desc' => 'Đơn từ 400K'],
                ['code' => 'SINHVIEN10', 'title' => 'SINH VIÊN -10%', 'desc' => 'Đơn từ 100K'],
                ['code' => 'BOOKWORM20K', 'title' => 'MỌT SÁCH 20K', 'desc' => 'Đơn từ 200K'],
            ];
            $applied_coupons = WC()->cart->get_applied_coupons();
            foreach ($vouchers_presets as $vp):
                $is_applied = in_array(strtolower($vp['code']), array_map('strtolower', $applied_coupons));
            ?>
                <div class="nb-voucher-pill-card <?php echo $is_applied ? 'applied' : ''; ?>" data-code="<?php echo esc_attr($vp['code']); ?>">
                    <div class="nb-vpc-left">
                        <strong class="nb-vpc-code"><?php echo esc_html($vp['title']); ?></strong>
                        <span class="nb-vpc-desc"><?php echo esc_html($vp['desc']); ?></span>
                    </div>
                    <div class="nb-cart-available-vouchers">
                        <h4>Voucher khả dụng</h4>
                        <?php echo do_shortcode('[nbook_available_vouchers]'); ?>
                    </div>
                    <?php if ($is_applied): ?>
                        <span class="nb-vpc-status"><i class="bi bi-check-circle-fill"></i> Đã dùng</span>
                    <?php else: ?>
                        <button type="button" class="nb-vpc-apply-btn" data-code="<?php echo esc_attr($vp['code']); ?>">Dùng ngay</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Currently Applied Coupons Chips -->
        <?php if (!empty($applied_coupons)): ?>
            <div class="nb-applied-coupons-wrap">
                <span class="nb-ac-label">Mã đang áp dụng:</span>
                <div class="nb-ac-chips">
                    <?php foreach ($applied_coupons as $code): ?>
                        <span class="nb-coupon-chip">
                            <i class="bi bi-tag-fill me-1"></i> <?php echo esc_html(strtoupper($code)); ?>
                            <button type="button" class="nb-remove-coupon-btn" data-code="<?php echo esc_attr($code); ?>" title="Hủy mã">&times;</button>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===================================================================
         5. STICKY SUMMARY BOTTOM BAR (SHOPEE STYLE)
         =================================================================== -->
    <div class="nb-cart-sticky-bar" id="nbCartStickyBar">
        <div class="nb-sticky-inner">
            
            <!-- Left Actions -->
            <div class="nb-sticky-left">
                <label class="nb-custom-checkbox">
                    <input type="checkbox" id="nbCheckAllBottom" class="nb-select-all-input" checked />
                    <span class="nb-checkmark-box"></span>
                    <span class="nb-sticky-label">Chọn tất cả (<span id="nbStickyTotalCount"><?php echo WC()->cart->get_cart_contents_count(); ?></span>)</span>
                </label>
                <button type="button" id="nbDeleteSelectedBtn" class="nb-btn-delete-selected">
                    <i class="bi bi-trash3 me-1"></i> Xóa đã chọn
                </button>
            </div>

            <!-- Right Pricing & Checkout Button -->
            <div class="nb-sticky-right">
                <div class="nb-sticky-price-info">
                    <div class="nb-sp-total-row">
                        <span class="nb-sp-text">Tổng thanh toán (<span id="nbSelectedCount">0</span> sản phẩm):</span>
                        <strong class="nb-sp-amount" id="nbSelectedTotalDisplay">0₫</strong>
                    </div>
                    <div class="nb-sp-savings-row" id="nbSavingsRow" style="display:none;">
                        Tiết kiệm <span id="nbSavingsDisplay">0₫</span>
                    </div>
                </div>
                <button type="button" id="nbProceedCheckoutBtn" class="nb-btn-buy-now">
                    <span>Mua Hàng</span> <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- ===================================================================
         6. CROSS-SELLS: "CÓ THỂ BẠN CŨNG THÍCH"
         =================================================================== -->
    <?php if (!empty($rec_products)): ?>
        <div class="nb-cart-cross-sells">
            <div class="nb-cross-sells-header">
                <h3 class="nb-cross-sells-title">
                    <i class="bi bi-stars text-warning me-2"></i> Có Thể Bạn Cũng Thích
                </h3>
                <span class="nb-cross-sells-subtitle">Các tựa sách được mua cùng nhiều nhất</span>
            </div>
            <div class="nb-cross-sells-grid">
                <?php foreach ($rec_products as $product): 
                    $pid = $product->get_id();
                    $title = $product->get_name();
                    $permalink = $product->get_permalink();
                    $price_html = $product->get_price_html();
                    $img_url = wp_get_attachment_image_url($product->get_image_id(), 'medium');
                    if (!$img_url) {
                        $img_url = wc_placeholder_img_src('medium');
                    }
                    $reg_price  = (float) $product->get_regular_price();
                    $sale_price = (float) $product->get_sale_price();
                    $discount_pct = 0;
                    if ($reg_price && $sale_price && $reg_price > $sale_price) {
                        $discount_pct = round((($reg_price - $sale_price) / $reg_price) * 100);
                    }
                    $author = get_post_meta($pid, '_biblio_author', true) ?: get_post_meta($pid, '_nbook_author', true);
                ?>
                    <div class="nb-rec-card">
                        <a href="<?php echo esc_url($permalink); ?>" class="nb-rec-thumb-wrap">
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($title); ?>" class="nb-rec-thumb" loading="lazy" />
                            <?php if ($discount_pct > 0): ?>
                                <span class="nb-rec-discount-badge">-<?php echo esc_html($discount_pct); ?>%</span>
                            <?php endif; ?>
                        </a>
                        <div class="nb-rec-body">
                            <?php if ($author): ?>
                                <div class="nb-rec-author"><?php echo esc_html($author); ?></div>
                            <?php endif; ?>
                            <h4 class="nb-rec-title">
                                <a href="<?php echo esc_url($permalink); ?>" title="<?php echo esc_attr($title); ?>">
                                    <?php echo esc_html($title); ?>
                                </a>
                            </h4>
                            <div class="nb-rec-price"><?php echo $price_html; ?></div>
                            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                               data-quantity="1" 
                               class="nb-rec-add-btn add_to_cart_button ajax_add_to_cart" 
                               data-product_id="<?php echo esc_attr($pid); ?>" 
                               aria-label="Thêm vào giỏ hàng">
                                <i class="bi bi-cart-plus-fill me-1"></i> Thêm vào giỏ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>
