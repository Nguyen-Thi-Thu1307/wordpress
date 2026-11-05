<?php
/**
 * Plugin Name: Biblio Theme Addons (Shopee Professional UI)
 * Description: Giao diện Header, Footer, Trang sản phẩm & Danh mục chuẩn Shopee - Màu cam chủ đạo #EE4D2D, hiện đại, tối ưu trải nghiệm người dùng và tỷ lệ chuyển đổi.
 * Version: 2.5.0
 * Author: Nhóm 3 TMĐT NEU - Biblio
 */

if (!defined('ABSPATH')) {
    exit;
}

class Biblio_Theme_UI {

    public function __construct() {
        // Enqueue Google Fonts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Replace Astra Header & Footer
        add_action('wp', [$this, 'setup_header_footer_hooks']);

        // WooCommerce AJAX Cart Fragment update
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'cart_fragment']);

        // Single product page custom hooks
        add_action('woocommerce_single_product_summary', [$this, 'product_rating_sold_bar'], 6);
        add_action('woocommerce_single_product_summary', [$this, 'product_vouchers_shipping'], 15);
        add_action('woocommerce_single_product_summary', [$this, 'product_meta_details'], 25);
        add_action('woocommerce_single_product_summary', [$this, 'product_trust_badges'], 35);

        // Shopee "Mua Ngay" (Buy Now) button next to Add to Cart
        add_action('woocommerce_after_add_to_cart_button', [$this, 'render_buy_now_button']);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'buy_now_redirect']);

        // Related products
        add_filter('woocommerce_product_related_posts_heading', function() {
            return 'CÓ THỂ BẠN CŨNG THÍCH (GỢI Ý TỪ BIBLIO)';
        });
        add_filter('woocommerce_output_related_products_args', function($args) {
            $args['posts_per_page'] = 5;
            $args['columns'] = 5;
            return $args;
        });

        // Single product & global scripts/styles
        add_action('wp_head', [$this, 'single_product_styles']);
        add_filter('woocommerce_product_tabs', [$this, 'rename_product_tabs'], 98);

        // Interactive scripts (Quantity stepper, Cart toast)
        add_action('wp_footer', [$this, 'render_interactive_scripts']);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'biblio-fonts',
            'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,400;1,700&display=swap',
            [],
            null
        );
    }

    public function setup_header_footer_hooks() {
        // Remove default Astra header & builder
        remove_action('astra_header', 'astra_header_markup');
        if (class_exists('Astra_Builder_Header')) {
            remove_action('astra_header', [Astra_Builder_Header::get_instance(), 'global_astra_header'], 0);
        }
        
        // Remove default Astra footer & builder
        remove_action('astra_footer', 'astra_footer_markup');
        if (class_exists('Astra_Builder_Footer')) {
            remove_action('astra_footer', [Astra_Builder_Footer::get_instance(), 'footer_markup'], 10);
        }

        // Add custom Shopee Header & Footer
        add_action('astra_header', [$this, 'render_header'], 10);
        add_action('astra_footer', [$this, 'render_footer'], 10);
    }

    public function cart_fragment($fragments) {
        $count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
        $fragments['span.sp-cart-count-badge'] = '<span class="sp-cart-count-badge">' . esc_html($count) . '</span>';
        return $fragments;
    }

    // =====================================================================
    // SINGLE PRODUCT: Rename tabs
    // =====================================================================
    public function rename_product_tabs($tabs) {
        if (isset($tabs['description'])) {
            $tabs['description']['title'] = 'MÔ TẢ SẢN PHẨM';
            $tabs['description']['priority'] = 10;
        }
        if (isset($tabs['reviews'])) {
            $tabs['reviews']['title'] = 'ĐÁNH GIÁ SẢN PHẨM';
            $tabs['reviews']['priority'] = 30;
        }
        if (isset($tabs['additional_information'])) {
            unset($tabs['additional_information']);
        }
        return $tabs;
    }

    // =====================================================================
    // SINGLE PRODUCT: Rating & Sold Bar (Shopee Style)
    // =====================================================================
    public function product_rating_sold_bar() {
        global $product;
        if (!$product) return;
        $rating_count = $product->get_rating_count() ?: 128;
        $sold_count = (int)$product->get_total_sales() ?: (rand(350, 2400));
        ?>
        <div class="sp-rating-sold-bar">
            <div class="sp-rating-score">
                <span class="sp-score-num">4.9</span>
                <span class="sp-stars">★★★★★</span>
            </div>
            <span class="sp-bar-sep"></span>
            <div class="sp-review-count">
                <span class="sp-stat-num"><?php echo esc_html($rating_count); ?></span>
                <span class="sp-stat-label">Đánh Giá</span>
            </div>
            <span class="sp-bar-sep"></span>
            <div class="sp-sold-count">
                <span class="sp-stat-num"><?php echo number_format($sold_count); ?></span>
                <span class="sp-stat-label">Đã Bán</span>
            </div>
            <div class="sp-report-btn">
                <a href="#report" onclick="alert('Cảm ơn bạn đã phản hồi. Ban quản trị Biblio sẽ xem xét!'); return false;">Tố cáo</a>
            </div>
        </div>
        <?php
    }

    // =====================================================================
    // SINGLE PRODUCT: Vouchers & Shipping (Shopee Style)
    // =====================================================================
    public function product_vouchers_shipping() {
        ?>
        <div class="sp-voucher-shipping-section">
            <!-- Shop Vouchers -->
            <div class="sp-meta-row">
                <div class="sp-meta-label">Mã Giảm Giá</div>
                <div class="sp-meta-value sp-vouchers-list">
                    <span class="sp-voucher-tag">Giảm 15.000₫</span>
                    <span class="sp-voucher-tag">Giảm 30.000₫</span>
                    <span class="sp-voucher-tag">Giảm 10% đơn từ 200k</span>
                </div>
            </div>

            <!-- Shipping Info -->
            <div class="sp-meta-row">
                <div class="sp-meta-label">Vận Chuyển</div>
                <div class="sp-meta-value sp-shipping-info">
                    <div class="sp-ship-line">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00bfa5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        <strong style="color: #00bfa5;">Miễn phí vận chuyển</strong>
                        <span class="sp-ship-sub">Miễn phí vận chuyển cho đơn hàng từ 150.000₫</span>
                    </div>
                    <div class="sp-ship-line" style="margin-top: 5px;">
                        <span style="color: #757575;">Vận chuyển tới:</span>
                        <strong style="color: #222;">Toàn Quốc</strong>
                        <span style="color: #757575; font-size: 12px;">(Giao nhanh SPX / GHN 1 - 3 ngày)</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // =====================================================================
    // SINGLE PRODUCT: Book Specifications Table (Shopee Style)
    // =====================================================================
    public function product_meta_details() {
        global $product;
        if (!$product || !is_a($product, 'WC_Product')) return;

        $pid = $product->get_id();
        $author      = get_post_meta($pid, '_biblio_author', true);
        $translator  = get_post_meta($pid, '_biblio_translator', true);
        $publisher   = get_post_meta($pid, '_biblio_publisher', true);
        $distributor = get_post_meta($pid, '_biblio_distributor', true);
        $size        = get_post_meta($pid, '_biblio_size', true);
        $pages       = get_post_meta($pid, '_biblio_pages', true);
        $weight      = get_post_meta($pid, '_biblio_weight', true);
        $date        = get_post_meta($pid, '_biblio_date', true);

        if (!$author && !$publisher && !$translator) {
            $author = 'Nhiều tác giả';
            $publisher = 'NXB Trẻ / NXB Kim Đồng';
        }
        ?>
        <div class="sp-specs-box">
            <h4 class="sp-specs-title">CHI TIẾT SẢN PHẨM</h4>
            <div class="sp-specs-table">
                <?php if ($author): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Tác giả</span>
                    <span class="sp-spec-val"><strong><?php echo esc_html($author); ?></strong></span>
                </div>
                <?php endif; ?>

                <?php if ($translator): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Dịch giả</span>
                    <span class="sp-spec-val"><?php echo esc_html($translator); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($publisher): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Nhà xuất bản</span>
                    <span class="sp-spec-val"><?php echo esc_html($publisher); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($distributor): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Nhà phát hành</span>
                    <span class="sp-spec-val"><?php echo esc_html($distributor); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($date): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Năm xuất bản</span>
                    <span class="sp-spec-val"><?php echo esc_html($date); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($pages): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Số trang</span>
                    <span class="sp-spec-val"><?php echo esc_html($pages); ?> trang</span>
                </div>
                <?php endif; ?>

                <?php if ($size): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Kích thước</span>
                    <span class="sp-spec-val"><?php echo esc_html($size); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($weight): ?>
                <div class="sp-spec-row">
                    <span class="sp-spec-name">Trọng lượng</span>
                    <span class="sp-spec-val"><?php echo esc_html($weight); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // =====================================================================
    // SINGLE PRODUCT: Trust Badges (Shopee Mall Guarantee)
    // =====================================================================
    public function product_trust_badges() {
        ?>
        <div class="sp-shopee-guarantee-bar">
            <div class="sp-sg-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#ee4d2d" stroke="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span><strong>Biblio Đảm Bảo</strong> 7 Ngày Trả Hàng / Hoàn Tiền Miễn Phí</span>
            </div>
            <div class="sp-sg-sub">
                <span>🛡️ 100% Sách Thật</span>
                <span>⚡ Giao Nhanh Toàn Quốc</span>
                <span>🎁 Tặng Bookmark</span>
            </div>
        </div>
        <?php
    }

    // =====================================================================
    // SINGLE PRODUCT: Buy Now Button
    // =====================================================================
    public function render_buy_now_button() {
        ?>
        <button type="submit" name="biblio_buy_now" value="1" class="sp-buy-now-btn">Mua Ngay</button>
        <?php
    }

    public function buy_now_redirect($url) {
        if (isset($_REQUEST['biblio_buy_now']) && $_REQUEST['biblio_buy_now'] == '1') {
            return function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/thanh-toan/');
        }
        return $url;
    }

    // =====================================================================
    // SINGLE PRODUCT: CSS Styles
    // =====================================================================
    public function single_product_styles() {
        if (!is_product()) return;
        ?>
        <style>
        .woocommerce div.product {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            background: #ffffff;
            border-radius: 4px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .woocommerce div.product div.images {
            flex: 0 0 380px;
            max-width: 380px;
        }
        .woocommerce div.product div.images .woocommerce-product-gallery__image img {
            border-radius: 4px;
            border: 1px solid #f0f0f0;
            width: 100%;
            height: auto;
            max-height: 420px;
            object-fit: contain;
        }
        .woocommerce div.product div.summary {
            flex: 1 1 500px;
            min-width: 0;
            padding: 0;
        }
        /* Product Title with Shopee Yêu Thích Badge */
        .woocommerce div.product .product_title {
            font-size: 20px !important;
            font-weight: 500 !important;
            color: #222222 !important;
            line-height: 1.4 !important;
            margin-bottom: 12px !important;
        }
        .woocommerce div.product .product_title::before {
            content: "Yêu thích";
            background: #ee4d2d;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 2px;
            margin-right: 8px;
            display: inline-block;
            vertical-align: middle;
        }
        /* Rating & Sold Bar */
        .sp-rating-sold-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f2f2f2;
            margin-bottom: 14px;
            font-size: 14px;
        }
        .sp-rating-score {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sp-score-num {
            color: #ee4d2d;
            font-weight: 700;
            border-bottom: 1px solid #ee4d2d;
        }
        .sp-stars {
            color: #ffce3d;
            letter-spacing: 1px;
            font-size: 13px;
        }
        .sp-bar-sep {
            height: 14px;
            width: 1px;
            background: #ddd;
        }
        .sp-stat-num {
            color: #222;
            font-weight: 600;
            border-bottom: 1px solid #222;
            margin-right: 4px;
        }
        .sp-stat-label {
            color: #757575;
            font-size: 13px;
        }
        .sp-report-btn {
            margin-left: auto;
        }
        .sp-report-btn a {
            color: #888;
            font-size: 12px;
            text-decoration: none;
        }

        /* Shopee Price Banner */
        .woocommerce div.product p.price,
        .woocommerce div.product span.price {
            background: #fafafa !important;
            padding: 16px 20px !important;
            border-radius: 4px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            margin-bottom: 16px !important;
        }
        .woocommerce div.product p.price del {
            color: #929292 !important;
            font-size: 16px !important;
            font-weight: 400 !important;
        }
        .woocommerce div.product p.price ins,
        .woocommerce div.product p.price > span.woocommerce-Price-amount {
            color: #ee4d2d !important;
            font-size: 30px !important;
            font-weight: 700 !important;
            text-decoration: none !important;
        }

        /* Vouchers & Shipping */
        .sp-voucher-shipping-section {
            padding: 8px 0;
            margin-bottom: 16px;
        }
        .sp-meta-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .sp-meta-label {
            flex: 0 0 110px;
            color: #757575;
        }
        .sp-meta-value {
            flex: 1;
        }
        .sp-vouchers-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .sp-voucher-tag {
            background: #fff5f1;
            color: #ee4d2d;
            border: 1px dashed #ee4d2d;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 2px;
        }
        .sp-shipping-info {
            color: #333;
        }
        .sp-ship-line {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .sp-ship-sub {
            color: #757575;
            font-size: 12px;
        }

        /* Book Specifications Table */
        .sp-specs-box {
            background: #fbfbfb;
            border: 1px solid #ebebeb;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .sp-specs-title {
            font-size: 13px;
            font-weight: 700;
            color: #222;
            margin: 0 0 10px 0;
            letter-spacing: 0.5px;
        }
        .sp-specs-table {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 20px;
            font-size: 13px;
        }
        .sp-spec-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        .sp-spec-name {
            color: #757575;
            flex: 0 0 110px;
        }
        .sp-spec-val {
            color: #222;
        }

        /* Shopee Dual Buttons (Thêm vào giỏ + Mua ngay) */
        .woocommerce div.product form.cart {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            padding-top: 15px;
            border-top: 1px solid #f2f2f2;
            flex-wrap: wrap;
        }
        
        /* Stepper input */
        .woocommerce div.product form.cart .quantity {
            display: inline-flex !important;
            align-items: center !important;
            border: 1px solid #ddd;
            border-radius: 2px;
            overflow: hidden;
            height: 44px;
        }
        .woocommerce div.product form.cart .quantity input.qty {
            height: 42px !important;
            border: none !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            width: 48px !important;
            text-align: center !important;
            padding: 0 !important;
        }
        .sp-qty-btn {
            width: 32px;
            height: 42px;
            background: #f7f7f7;
            border: none;
            color: #555;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            transition: background 0.2s;
        }
        .sp-qty-btn:hover {
            background: #e9e9e9;
        }

        /* Thêm vào giỏ button */
        .woocommerce div.product form.cart button.single_add_to_cart_button {
            height: 44px !important;
            background: #fff5f1 !important;
            border: 1px solid #ee4d2d !important;
            color: #ee4d2d !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            padding: 0 20px !important;
            border-radius: 2px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
        }
        .woocommerce div.product form.cart button.single_add_to_cart_button:hover {
            background: #fee7df !important;
        }

        /* Mua ngay button */
        .sp-buy-now-btn {
            height: 44px;
            background: #ee4d2d;
            border: 1px solid #ee4d2d;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            padding: 0 28px;
            border-radius: 2px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .sp-buy-now-btn:hover {
            background: #d73211;
            border-color: #d73211;
        }

        /* Shopee Guarantee Box */
        .sp-shopee-guarantee-bar {
            background: #fff8f5;
            border: 1px solid #fbd9cf;
            border-radius: 4px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 13px;
            margin-top: 15px;
        }
        .sp-sg-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ee4d2d;
        }
        .sp-sg-sub {
            display: flex;
            gap: 15px;
            color: #555;
            font-size: 12px;
            font-weight: 500;
        }

        /* Tabs (Mô tả & Đánh giá) */
        .woocommerce-tabs.wc-tabs-wrapper {
            background: #ffffff;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            width: 100%;
        }
        .woocommerce-tabs ul.tabs {
            border-bottom: 2px solid #ee4d2d !important;
            padding: 0 !important;
            display: flex !important;
            gap: 0 !important;
            background: #fafafa !important;
        }
        .woocommerce-tabs ul.tabs li {
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            margin: 0 !important;
        }
        .woocommerce-tabs ul.tabs li a {
            color: #555 !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            padding: 12px 24px !important;
            display: block !important;
        }
        .woocommerce-tabs ul.tabs li.active {
            background: #ee4d2d !important;
        }
        .woocommerce-tabs ul.tabs li.active a {
            color: #ffffff !important;
        }
        .woocommerce-tabs .panel {
            padding: 20px 0 0 0 !important;
            font-size: 14px !important;
            line-height: 1.7 !important;
            color: #333 !important;
        }
        @media (max-width: 768px) {
            .woocommerce div.product div.images {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
            .sp-specs-table {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }

    // =====================================================================
    // SHOPEE HEADER
    // =====================================================================
    public function render_header() {
        $cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/gio-hang/');
        $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
        $shop_url = home_url('/san-pham/');
        $order_url = home_url('/don-hang/');
        $promo_url = home_url('/khuyen-mai/');
        $news_url = home_url('/tin-tuc-su-kien/');
        $contact_url = home_url('/lien-he/');
        $is_logged = is_user_logged_in();
        $current_user = wp_get_current_user();
        ?>
        <!-- ================= SHOPEE HEADER ================= -->
        <header id="shopee-header" class="sp-header-wrap">
            
            <!-- TIER 1: TOPBAR (Shopee Orange Gradient) -->
            <div class="sp-topbar">
                <div class="sp-container sp-topbar-inner">
                    <!-- Topbar Left -->
                    <div class="sp-topbar-left">
                        <span class="sp-top-link">Tải ứng dụng</span>
                        <span class="sp-top-sep">|</span>
                        <span class="sp-top-link">
                            Kết nối:
                            <a href="https://facebook.com" target="_blank" rel="nofollow" class="sp-top-soc" title="Facebook">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </a>
                            <a href="https://instagram.com" target="_blank" rel="nofollow" class="sp-top-soc" title="Instagram">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                            </a>
                        </span>
                    </div>

                    <!-- Topbar Right -->
                    <div class="sp-topbar-right">
                        <a href="<?php echo esc_url($news_url); ?>" class="sp-top-link">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            <span>Thông Báo</span>
                        </a>
                        <a href="<?php echo esc_url($contact_url); ?>" class="sp-top-link">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <span>Hỗ Trợ</span>
                        </a>
                        <span class="sp-top-link">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                            <span>Tiếng Việt</span>
                        </span>
                        <span class="sp-top-sep">|</span>

                        <?php if ($is_logged): ?>
                            <a href="<?php echo esc_url($account_url); ?>" class="sp-top-link sp-user-pill">
                                <span class="sp-user-avatar">👤</span>
                                <strong><?php echo esc_html($current_user->display_name); ?></strong>
                            </a>
                            <span class="sp-top-sep">|</span>
                            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="sp-top-link">Đăng xuất</a>
                        <?php else: ?>
                            <a href="<?php echo esc_url($account_url); ?>" class="sp-top-link"><strong>Đăng Ký</strong></a>
                            <span class="sp-top-sep">|</span>
                            <a href="<?php echo esc_url($account_url); ?>" class="sp-top-link"><strong>Đăng Nhập</strong></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TIER 2: MAIN SHOPEE BAR -->
            <div class="sp-main-bar">
                <div class="sp-container sp-main-inner">
                    <!-- Shopee Brand Logo: BIBLIO -->
                    <div class="sp-brand">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="sp-logo-link" title="Biblio.vn - Nhà Sách Trực Tuyến">
                            <span class="sp-logo-icon">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    <line x1="12" y1="6" x2="16" y2="6"></line>
                                    <line x1="12" y1="10" x2="16" y2="10"></line>
                                </svg>
                            </span>
                            <div class="sp-logo-text-group">
                                <span class="sp-logo-title">Biblio<span class="sp-logo-vn">.vn</span></span>
                                <span class="sp-logo-sub">SÁCH &amp; TRI THỨC</span>
                            </div>
                        </a>
                    </div>

                    <!-- Shopee Center Search Bar -->
                    <div class="sp-search-container">
                        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="sp-search-form">
                            <input type="text" name="s" class="sp-search-input" placeholder="Biblio bao ship 0Đ - Tìm tên sách, tác giả, thể loại bạn yêu thích..." value="<?php echo get_search_query(); ?>" autocomplete="off" />
                            <input type="hidden" name="post_type" value="product" />
                            <button type="submit" class="sp-search-btn" aria-label="Tìm kiếm">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                        </form>

                        <!-- Hot Search Keywords -->
                        <div class="sp-hot-keywords">
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ky-nang/')); ?>">Sách Kỹ Năng</a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-kinh-te/')); ?>">Sách Kinh Tế</a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ngoai-ngu/')); ?>">IELTS 2026</a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-van-hoc/')); ?>">Văn Học Kinh Điển</a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-giao-duc/')); ?>">Giáo Trình NEU</a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-thieu-nhi/')); ?>">Sách Thiếu Nhi</a>
                        </div>
                    </div>

                    <!-- Shopping Cart Icon -->
                    <div class="sp-cart-area">
                        <a href="<?php echo esc_url($cart_url); ?>" class="sp-cart-link" title="Xem giỏ hàng của bạn">
                            <div class="sp-cart-icon-wrap">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <span class="sp-cart-count-badge"><?php echo esc_html($cart_count); ?></span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- TIER 3: SUB-NAV BAR (White Bar) -->
            <div class="sp-nav-bar">
                <div class="sp-container sp-nav-inner">
                    <!-- Categories Dropdown Trigger -->
                    <div class="sp-cat-dropdown-wrap">
                        <a href="<?php echo esc_url($shop_url); ?>" class="sp-cat-main-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                            <span>DANH MỤC SÁCH</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </a>

                        <!-- Dropdown Menu Items -->
                        <div class="sp-cat-popup-menu">
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ky-nang/')); ?>">
                                <span class="sp-c-icon">📘</span>
                                <span>Sách Kỹ Năng Sống</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-kinh-te/')); ?>">
                                <span class="sp-c-icon">📊</span>
                                <span>Sách Kinh Tế & Khởi Nghiệp</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-van-hoc/')); ?>">
                                <span class="sp-c-icon">🖋️</span>
                                <span>Sách Văn Học & Tiểu Thuyết</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ngoai-ngu/')); ?>">
                                <span class="sp-c-icon">🌐</span>
                                <span>Sách Ngoại Ngữ & IELTS</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-giao-duc/')); ?>">
                                <span class="sp-c-icon">🎓</span>
                                <span>Sách Giáo Dục & Giáo Trình</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-thieu-nhi/')); ?>">
                                <span class="sp-c-icon">🧸</span>
                                <span>Sách Thiếu Nhi</span>
                            </a>
                            <a href="<?php echo esc_url($shop_url); ?>" class="sp-c-all">
                                <span>Xem Tất Cả 130+ Tựa Sách →</span>
                            </a>
                        </div>
                    </div>

                    <!-- Quick Navigation Links -->
                    <div class="sp-nav-links">
                        <a href="<?php echo esc_url(home_url('/#flash-sale')); ?>" class="sp-nav-item sp-nav-flash">
                            <span class="sp-fire-ic">⚡</span>
                            <span>FLASH SALE GIÁ SỐC</span>
                        </a>
                        <a href="<?php echo esc_url($shop_url); ?>" class="sp-nav-item">
                            <span class="sp-mall-badge">Mall</span>
                            <span>Biblio Mall</span>
                        </a>
                        <a href="<?php echo esc_url($promo_url); ?>" class="sp-nav-item">
                            <span>Mã Giảm Giá &amp; Freeship</span>
                        </a>
                        <a href="<?php echo esc_url($shop_url); ?>" class="sp-nav-item">
                            <span>Sách Bán Chạy</span>
                        </a>
                        <a href="<?php echo esc_url($order_url); ?>" class="sp-nav-item">
                            <span>Tra Cứu Đơn Hàng</span>
                        </a>
                        <a href="<?php echo esc_url($news_url); ?>" class="sp-nav-item">
                            <span>Tin Tức NEU</span>
                        </a>
                    </div>

                    <!-- Right Sale Pill -->
                    <div class="sp-nav-pill">
                        <a href="<?php echo esc_url($shop_url); ?>" class="sp-sale-pill-btn">
                            🔥 Xả Kho - Giảm Đến 50%
                        </a>
                    </div>
                </div>
            </div>

        </header>

        <style>
            /* HIDE DEFAULT ASTRA HEADER & FOOTER */
            #ast-desktop-header, 
            #ast-mobile-header, 
            .site-header, 
            .ast-mobile-header-wrap,
            .site-footer,
            .ast-footer-builder-wrap {
                display: none !important;
            }

            /* SHOPEE HEADER CONTAINER */
            .sp-header-wrap {
                background: #ee4d2d;
                background: linear-gradient(-180deg, #f53d2d, #f63);
                color: #ffffff;
                font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
                position: sticky !important;
                top: 0 !important;
                z-index: 999999 !important;
                width: 100% !important;
                box-shadow: 0 2px 10px rgba(0,0,0,0.14) !important;
            }
            body.admin-bar .sp-header-wrap {
                top: 32px !important;
            }
            @media screen and (max-width: 782px) {
                body.admin-bar .sp-header-wrap {
                    top: 46px !important;
                }
            }
            .sp-container {
                max-width: 1240px;
                margin: 0 auto;
                padding: 0 15px;
            }

            /* TIER 1: TOPBAR */
            .sp-topbar {
                font-size: 13px;
                padding: 3px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            }
            .sp-topbar-inner {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .sp-topbar-left, .sp-topbar-right {
                display: flex;
                align-items: center;
                gap: 7px;
            }
            .sp-top-link {
                color: #ffffff;
                text-decoration: none;
                opacity: 0.92;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 12.5px;
                transition: opacity 0.2s ease;
            }
            .sp-top-link:hover {
                opacity: 1;
                color: #ffffff;
            }
            .sp-top-sep {
                opacity: 0.4;
                font-size: 11px;
            }
            .sp-top-soc {
                color: #ffffff;
                display: inline-flex;
                align-items: center;
                margin-left: 4px;
                opacity: 0.9;
            }
            .sp-top-soc:hover {
                opacity: 1;
            }
            .sp-user-pill {
                background: rgba(255, 255, 255, 0.15);
                padding: 2px 8px;
                border-radius: 12px;
            }

            /* TIER 2: MAIN BAR */
            .sp-main-bar {
                padding: 14px 0 10px 0;
            }
            .sp-main-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 30px;
            }
            /* Brand Logo */
            .sp-brand {
                flex: 0 0 200px;
            }
            .sp-logo-link {
                display: flex;
                align-items: center;
                gap: 10px;
                text-decoration: none;
                color: #ffffff;
            }
            .sp-logo-text-group {
                display: flex;
                flex-direction: column;
            }
            .sp-logo-title {
                font-size: 28px;
                font-weight: 900;
                line-height: 1;
                letter-spacing: -0.5px;
                color: #ffffff;
            }
            .sp-logo-vn {
                font-size: 18px;
                font-weight: 300;
                opacity: 0.9;
            }
            .sp-logo-sub {
                font-size: 9.5px;
                font-weight: 700;
                letter-spacing: 1.5px;
                opacity: 0.85;
                margin-top: 2px;
            }

            /* Shopee Wide Search Bar */
            .sp-search-container {
                flex: 1;
                max-width: 780px;
            }
            .sp-search-form {
                background: #ffffff;
                border: 0 !important;
                border-radius: 0;
                padding: 0;
                display: flex;
                align-items: center;
                box-shadow: none !important;
                position: relative;
            }
            .sp-search-input {
                flex: 1;
                border: none !important;
                outline: none !important;
                background: transparent !important;
                color: #222222 !important;
                font-size: 14px !important;
                padding: 8px 6px !important;
                font-family: inherit;
            }
            .sp-search-input::placeholder {
                color: #888888;
                font-size: 13.5px;
            }
            .sp-search-btn {
                background: #ee4d2d !important;
                border: none !important;
                border-radius: 2px !important;
                width: 60px !important;
                height: 36px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                cursor: pointer !important;
                transition: background 0.2s ease !important;
            }
            .sp-search-btn:hover {
                background: #d73211 !important;
            }
            .sp-search-btn .bi-search {
                color: #ffffff;
                font-size: 18px;
                line-height: 1;
            }

            /* Hot Keywords */
            .sp-hot-keywords {
                display: flex;
                gap: 12px;
                margin-top: 6px;
                font-size: 11.5px;
                overflow: hidden;
                white-space: nowrap;
            }
            .sp-hot-keywords a {
                color: #ffffff;
                text-decoration: none;
                opacity: 0.88;
                transition: opacity 0.2s;
            }
            .sp-hot-keywords a:hover {
                opacity: 1;
                text-decoration: underline;
            }

            /* Shopping Cart Button */
            .sp-cart-area {
                flex: 0 0 80px;
                display: flex;
                justify-content: flex-end;
            }
            .sp-cart-link {
                text-decoration: none;
                position: relative;
                padding: 6px;
            }
            .sp-cart-icon-wrap {
                position: relative;
                display: inline-block;
            }
            .sp-cart-count-badge {
                position: absolute;
                top: -6px;
                right: -10px;
                background: #ffffff;
                color: #ee4d2d;
                font-size: 12px;
                font-weight: 800;
                padding: 1px 7px;
                border-radius: 12px;
                border: 2px solid #ee4d2d;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }

            /* TIER 3: SUB-NAV BAR (White Bar) */
            .sp-nav-bar {
                background: #ffffff;
                border-bottom: 1px solid #e5e5e5;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            }
            .sp-nav-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                height: 44px;
            }
            /* Categories Trigger */
            .sp-cat-dropdown-wrap {
                position: relative;
            }
            .sp-cat-main-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #ee4d2d;
                color: #ffffff;
                text-decoration: none;
                font-size: 13px;
                font-weight: 700;
                padding: 8px 16px;
                border-radius: 3px;
                letter-spacing: 0.3px;
                transition: background 0.2s ease;
            }
            .sp-cat-main-btn:hover {
                background: #d73211;
                color: #ffffff;
            }
            /* Category Pop-up Menu */
            .sp-cat-popup-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 250px;
                background: #ffffff;
                border: 1px solid #e0e0e0;
                border-top: 2px solid #ee4d2d;
                box-shadow: 0 8px 24px rgba(0,0,0,0.14);
                border-radius: 0 0 4px 4px;
                z-index: 99999;
                margin-top: 2px;
            }
            .sp-cat-dropdown-wrap:hover .sp-cat-popup-menu {
                display: block;
            }
            .sp-cat-popup-menu a {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 16px;
                font-size: 13px;
                color: #333333;
                text-decoration: none;
                border-bottom: 1px solid #f2f2f2;
                transition: all 0.2s ease;
            }
            .sp-cat-popup-menu a:hover {
                background: #fff5f1;
                color: #ee4d2d;
                padding-left: 20px;
            }
            .sp-c-icon {
                font-size: 16px;
            }
            .sp-c-all {
                background: #fbfbfb !important;
                color: #ee4d2d !important;
                font-weight: 700 !important;
            }

            /* Nav Links */
            .sp-nav-links {
                display: flex;
                align-items: center;
                gap: 20px;
            }
            .sp-nav-item {
                display: flex;
                align-items: center;
                gap: 6px;
                color: #333333;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
                transition: color 0.2s ease;
            }
            .sp-nav-item:hover {
                color: #ee4d2d;
            }
            .sp-nav-flash {
                color: #ee4d2d;
                font-weight: 700;
            }
            .sp-fire-ic {
                font-size: 14px;
            }
            .sp-mall-badge {
                background: #d0011b;
                color: #ffffff;
                font-size: 10px;
                font-weight: 800;
                padding: 1px 5px;
                border-radius: 2px;
                text-transform: uppercase;
            }

            /* Right Pill */
            .sp-sale-pill-btn {
                background: #ee4d2d;
                color: #ffffff;
                text-decoration: none;
                font-size: 12px;
                font-weight: 700;
                padding: 5px 14px;
                border-radius: 20px;
                transition: transform 0.2s, background 0.2s;
                display: inline-block;
            }
            .sp-sale-pill-btn:hover {
                background: #d73211;
                color: #ffffff;
                transform: scale(1.04);
            }

            /* Shopee Toast Popup */
            .sp-toast-box {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0.9);
                background: rgba(0, 0, 0, 0.78);
                color: #ffffff;
                padding: 18px 28px;
                border-radius: 6px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                font-weight: 500;
                z-index: 999999;
                opacity: 0;
                pointer-events: none;
                transition: all 0.25s cubic-bezier(0.18, 0.89, 0.32, 1.28);
                box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            }
            .sp-toast-box.sp-show {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
            .sp-toast-check {
                background: #00bfa5;
                color: #fff;
                width: 38px;
                height: 38px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                font-weight: bold;
            }

            @media (max-width: 900px) {
                .sp-nav-links, .sp-topbar-left {
                    display: none;
                }
                .sp-hot-keywords {
                    display: none;
                }
                .sp-brand {
                    flex: 0 0 140px;
                }
                .sp-logo-title {
                    font-size: 22px;
                }
            }
        </style>
        <?php
    }

    // =====================================================================
    // SHOPEE FOOTER
    // =====================================================================
    public function render_footer() {
        ?>
        <!-- ================= SHOPEE FOOTER ================= -->
        <footer id="shopee-footer" class="sp-footer-wrap">

            <!-- SECTION 1: NEWSLETTER BANNER -->
            <div class="sp-newsletter-bar">
                <div class="sp-container sp-newsletter-inner">
                    <div class="sp-nl-left">
                        <span class="sp-nl-icon">📬</span>
                        <div>
                            <h3 class="sp-nl-title">ĐĂNG KÝ NHẬN BẢN TIN BIBLIO - NHẬN VOUCHER 50.000₫</h3>
                            <p class="sp-nl-sub">Cập nhật tựa sách mới nhất, mã freeship và ưu đãi độc quyền dành cho bạn.</p>
                        </div>
                    </div>
                    <form class="sp-nl-form" onsubmit="alert('Cảm ơn bạn đã đăng ký! Mã voucher BIBLIO50K đã được gửi tới email của bạn.'); return false;">
                        <input type="email" placeholder="Nhập địa chỉ email của bạn..." class="sp-nl-input" required />
                        <button type="submit" class="sp-nl-btn">Đăng Ký Ngay</button>
                    </form>
                </div>
            </div>

            <!-- SECTION 2: 5 COLUMNS SHOPEE STANDARD -->
            <div class="sp-footer-cols">
                <div class="sp-container sp-cols-inner">
                    <!-- Column 1 -->
                    <div class="sp-col">
                        <h4 class="sp-col-title">CHĂM SÓC KHÁCH HÀNG</h4>
                        <ul class="sp-col-links">
                            <li><a href="<?php echo esc_url(home_url('/lien-he/')); ?>">Trung Tâm Trợ Giúp</a></li>
                            <li><a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/')); ?>">Biblio Blog</a></li>
                            <li><a href="<?php echo esc_url(home_url('/don-hang/')); ?>">Hướng Dẫn Mua Hàng</a></li>
                            <li><a href="<?php echo esc_url(home_url('/don-hang/')); ?>">Hướng Dẫn Bán Hàng</a></li>
                            <li><a href="<?php echo esc_url(home_url('/thanh-toan/')); ?>">Thanh Toán &amp; Ví BiblioPay</a></li>
                            <li><a href="<?php echo esc_url(home_url('/don-hang/')); ?>">Chính Sách Đổi Trả &amp; Hoàn Tiền</a></li>
                            <li><a href="<?php echo esc_url(home_url('/lien-he/')); ?>">Chăm Sóc Khách Hàng: <strong>1900-BIBLIO</strong></a></li>
                        </ul>
                    </div>

                    <!-- Column 2 -->
                    <div class="sp-col">
                        <h4 class="sp-col-title">VỀ BIBLIO VIỆT NAM</h4>
                        <ul class="sp-col-links">
                            <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Giới Thiệu Về Biblio</a></li>
                            <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Tuyển Dụng Nhân Sự</a></li>
                            <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Điều Khoản Biblio</a></li>
                            <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Chính Sách Bảo Mật</a></li>
                            <li><a href="<?php echo esc_url(home_url('/san-pham/')); ?>">Biblio Mall Chính Hãng</a></li>
                            <li><a href="<?php echo esc_url(home_url('/san-pham/')); ?>">Tác Giả &amp; Tác Phẩm Nổi Bật</a></li>
                            <li><a href="<?php echo esc_url(home_url('/khuyen-mai/')); ?>">Flash Sale Hôm Nay</a></li>
                        </ul>
                    </div>

                    <!-- Column 3 -->
                    <div class="sp-col">
                        <h4 class="sp-col-title">THANH TOÁN &amp; VẬN CHUYỂN</h4>
                        <div class="sp-badge-grid">
                            <span class="sp-pay-tag">VietQR</span>
                            <span class="sp-pay-tag">Visa</span>
                            <span class="sp-pay-tag">MasterCard</span>
                            <span class="sp-pay-tag">MoMo</span>
                            <span class="sp-pay-tag">ShopeePay</span>
                            <span class="sp-pay-tag">ZaloPay</span>
                            <span class="sp-pay-tag">COD</span>
                        </div>
                        <h4 class="sp-col-title" style="margin-top: 18px;">ĐƠN VỊ VẬN CHUYỂN</h4>
                        <div class="sp-badge-grid">
                            <span class="sp-ship-tag">SPX Express</span>
                            <span class="sp-ship-tag">Giao Hàng Nhanh</span>
                            <span class="sp-ship-tag">Viettel Post</span>
                            <span class="sp-ship-tag">GHTK</span>
                            <span class="sp-ship-tag">Hỏa Tốc 2H</span>
                        </div>
                    </div>

                    <!-- Column 4 -->
                    <div class="sp-col">
                        <h4 class="sp-col-title">THEO DÕI CHÚNG TÔI TRÊN</h4>
                        <ul class="sp-col-links">
                            <li><a href="https://facebook.com" target="_blank" rel="nofollow">📘 Facebook: Biblio Bookstore</a></li>
                            <li><a href="https://instagram.com" target="_blank" rel="nofollow">📸 Instagram: @biblio.vn</a></li>
                            <li><a href="https://tiktok.com" target="_blank" rel="nofollow">🎵 TikTok: @biblio_books</a></li>
                            <li><a href="https://shopee.vn" target="_blank" rel="nofollow">🛍️ Shopee: Biblio Official Store</a></li>
                        </ul>
                    </div>

                    <!-- Column 5 -->
                    <div class="sp-col">
                        <h4 class="sp-col-title">TẢI ỨNG DỤNG BIBLIO</h4>
                        <div class="sp-app-download">
                            <div class="sp-qr-box">
                                <svg width="70" height="70" viewBox="0 0 100 100" fill="#222">
                                    <rect width="100" height="100" fill="#fff"/>
                                    <rect x="10" y="10" width="30" height="30" fill="#222"/>
                                    <rect x="15" y="15" width="20" height="20" fill="#fff"/>
                                    <rect x="20" y="20" width="10" height="10" fill="#ee4d2d"/>
                                    <rect x="60" y="10" width="30" height="30" fill="#222"/>
                                    <rect x="65" y="15" width="20" height="20" fill="#fff"/>
                                    <rect x="70" y="20" width="10" height="10" fill="#ee4d2d"/>
                                    <rect x="10" y="60" width="30" height="30" fill="#222"/>
                                    <rect x="15" y="65" width="20" height="20" fill="#fff"/>
                                    <rect x="20" y="70" width="10" height="10" fill="#ee4d2d"/>
                                    <rect x="45" y="45" width="10" height="10" fill="#222"/>
                                    <rect x="60" y="60" width="15" height="15" fill="#222"/>
                                    <rect x="80" y="80" width="10" height="10" fill="#222"/>
                                </svg>
                            </div>
                            <div class="sp-app-buttons">
                                <span class="sp-app-btn">🍏 App Store</span>
                                <span class="sp-app-btn">🤖 Google Play</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: COPYRIGHT & REGISTRATION -->
            <div class="sp-footer-bottom">
                <div class="sp-container sp-bottom-inner">
                    <div class="sp-legal-text">
                        <p><strong>© 2026 - Bản quyền thuộc về Công ty Cổ phần Sách &amp; Tri Thức Biblio Việt Nam</strong></p>
                        <p>Địa chỉ: Tòa nhà A1, Đại học Kinh tế Quốc dân (NEU), 207 Giải Phóng, Đồng Tâm, Hai Bà Trưng, Hà Nội.</p>
                        <p>Giấy chứng nhận Đăng ký Doanh nghiệp số 0108992384 do Sở Kế hoạch và Đầu tư TP. Hà Nội cấp.</p>
                    </div>
                    <div class="sp-moit-cert">
                        <span class="sp-cert-badge">ĐÃ ĐĂNG KÝ BỘ CÔNG THƯƠNG</span>
                    </div>
                </div>
            </div>

            <!-- FLOATING BACK TO TOP BUTTON -->
            <a href="#shopee-header" class="sp-floating-top" title="Lên đầu trang">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>
            </a>

            <!-- SHOPEE TOAST NOTIFICATION CONTAINER -->
            <div id="sp-toast" class="sp-toast-box">
                <div class="sp-toast-check">✓</div>
                <div id="sp-toast-msg">Đã thêm sản phẩm vào Giỏ hàng!</div>
            </div>
        </footer>

        <style>
            /* SHOPEE FOOTER STYLES */
            .sp-footer-wrap {
                background: #fbfbfb;
                border-top: 4px solid #ee4d2d;
                font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
                color: #555555;
            }
            .sp-newsletter-bar {
                background: #fff5f1;
                border-bottom: 1px solid #fbd9cf;
                padding: 24px 0;
            }
            .sp-newsletter-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 20px;
            }
            .sp-nl-left {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .sp-nl-icon {
                font-size: 38px;
            }
            .sp-nl-title {
                font-size: 16px;
                font-weight: 800;
                color: #ee4d2d;
                margin: 0 0 4px 0;
            }
            .sp-nl-sub {
                font-size: 13px;
                color: #555555;
                margin: 0;
            }
            .sp-nl-form {
                display: flex;
                gap: 8px;
            }
            .sp-nl-input {
                width: 320px;
                padding: 10px 14px;
                border: 1px solid #ddd;
                border-radius: 3px;
                font-size: 13px;
                outline: none;
            }
            .sp-nl-btn {
                background: #ee4d2d;
                color: #ffffff;
                border: none;
                font-size: 13px;
                font-weight: 700;
                padding: 10px 20px;
                border-radius: 3px;
                cursor: pointer;
                transition: background 0.2s;
            }
            .sp-nl-btn:hover {
                background: #d73211;
            }

            .sp-footer-cols {
                background: #fbfbfb;
                padding: 40px 0 30px 0;
                border-bottom: 1px solid #ebebeb;
            }
            .sp-cols-inner {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 30px;
            }
            .sp-col-title {
                font-size: 12px;
                font-weight: 800;
                color: #222222;
                text-transform: uppercase;
                margin: 0 0 16px 0;
                letter-spacing: 0.5px;
            }
            .sp-col-links {
                list-style: none;
                padding: 0;
                margin: 0;
                font-size: 12.5px;
                line-height: 2.1;
            }
            .sp-col-links a {
                color: #666666;
                text-decoration: none;
                transition: color 0.2s;
            }
            .sp-col-links a:hover {
                color: #ee4d2d;
            }
            .sp-badge-grid {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
            }
            .sp-pay-tag, .sp-ship-tag {
                background: #ffffff;
                border: 1px solid #e0e0e0;
                border-radius: 2px;
                padding: 4px 8px;
                font-size: 11px;
                font-weight: 600;
                color: #333333;
                box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            }
            .sp-app-download {
                display: flex;
                gap: 12px;
                align-items: center;
            }
            .sp-qr-box {
                border: 1px solid #ddd;
                padding: 4px;
                background: #fff;
                border-radius: 3px;
            }
            .sp-app-buttons {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .sp-app-btn {
                background: #ffffff;
                border: 1px solid #ddd;
                padding: 6px 12px;
                font-size: 11px;
                font-weight: 600;
                border-radius: 3px;
                cursor: pointer;
            }

            .sp-footer-bottom {
                background: #f5f5f5;
                padding: 24px 0;
                font-size: 12px;
                color: #757575;
                text-align: center;
            }
            .sp-bottom-inner {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }
            .sp-legal-text p {
                margin: 4px 0;
            }
            .sp-cert-badge {
                border: 2px solid #0055aa;
                color: #0055aa;
                padding: 6px 16px;
                border-radius: 20px;
                font-weight: 800;
                font-size: 11px;
                display: inline-block;
                letter-spacing: 0.5px;
            }

            .sp-floating-top {
                position: fixed;
                bottom: 25px;
                right: 25px;
                width: 44px;
                height: 44px;
                background: #ee4d2d;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 14px rgba(238, 77, 45, 0.4);
                z-index: 9999;
                transition: transform 0.2s, background 0.2s;
            }
            .sp-floating-top:hover {
                background: #d73211;
                transform: translateY(-3px);
            }
        </style>
        <?php
    }

    // =====================================================================
    // INTERACTIVE SCRIPTS: Quantity Stepper & Cart Toast
    // =====================================================================
    public function render_interactive_scripts() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. SHOPEE QUANTITY STEPPER
            var qtyWraps = document.querySelectorAll('.woocommerce div.product form.cart .quantity');
            qtyWraps.forEach(function(wrap) {
                var input = wrap.querySelector('input.qty');
                if (!input || wrap.classList.contains('sp-stepper-ready')) return;
                wrap.classList.add('sp-stepper-ready');

                var minusBtn = document.createElement('button');
                minusBtn.type = 'button';
                minusBtn.className = 'sp-qty-btn sp-qty-minus';
                minusBtn.textContent = '−';

                var plusBtn = document.createElement('button');
                plusBtn.type = 'button';
                plusBtn.className = 'sp-qty-btn sp-qty-plus';
                plusBtn.textContent = '+';

                wrap.insertBefore(minusBtn, input);
                wrap.appendChild(plusBtn);

                minusBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var val = parseInt(input.value) || 1;
                    var min = parseInt(input.getAttribute('min')) || 1;
                    if (val > min) {
                        input.value = val - 1;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                plusBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var val = parseInt(input.value) || 1;
                    var max = parseInt(input.getAttribute('max')) || 999;
                    if (val < max) {
                        input.value = val + 1;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });

            // 2. SHOPEE CART TOAST
            function showToast(msg) {
                var toast = document.getElementById('sp-toast');
                var msgEl = document.getElementById('sp-toast-msg');
                if (!toast) return;
                if (msg && msgEl) msgEl.textContent = msg;
                toast.classList.add('sp-show');
                setTimeout(function() {
                    toast.classList.remove('sp-show');
                }, 2800);
            }

            // Hook jQuery added_to_cart if available
            if (window.jQuery) {
                window.jQuery(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                    showToast('Đã thêm sản phẩm vào Giỏ hàng!');
                });
            }

            // Check if page loaded with WC notice
            if (window.location.search.indexOf('add-to-cart=') !== -1) {
                showToast('Đã thêm sản phẩm vào Giỏ hàng!');
            }
        });
        </script>
        <?php
    }
}

new Biblio_Theme_UI();
