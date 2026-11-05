<?php
/**
 * Custom Empty Cart Template for Biblio
 * Designed after Shopee / Biblio Modern E-Commerce Standard
 */

defined('ABSPATH') || exit;

$is_logged = is_user_logged_in();
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/san-pham/');

// Fetch recommended books for empty cart
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

<div class="nb-cart-page-wrapper nb-cart-empty-page" id="nbCartEmptyPage">

    <!-- ===================================================================
         1. BREADCRUMBS STRIP
         =================================================================== -->
    <div class="nb-cart-header-strip">
        <nav class="nb-cart-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
            <span class="nb-bc-sep">&gt;</span>
            <span class="nb-bc-current">Giỏ hàng (<span class="nb-cart-total-badge">0</span> sản phẩm)</span>
        </nav>
    </div>

    <!-- ===================================================================
         2. KHỐI CHÍNH: KHÔNG GIAN GIỎ HÀNG TRỐNG (SHOPEE STYLE)
         =================================================================== -->
    <div class="nb-cart-empty-card">
        
        <!-- Art Icon Minh Họa -->
        <div class="nb-empty-art-wrap">
            <div class="nb-empty-cart-circle">
                <i class="bi bi-cart-x-fill"></i>
            </div>
            <div class="nb-empty-cart-sparkle">
                <i class="bi bi-stars"></i>
            </div>
        </div>

        <h2 class="nb-empty-title">Giỏ hàng của bạn còn trống</h2>
        <p class="nb-empty-desc">
            Không có cuốn sách nào trong giỏ hàng. Hãy khám phá hàng ngàn tựa sách mới cùng các chương trình khuyến mãi và ưu đãi độc quyền tại Biblio!
        </p>

        <?php if (!$is_logged): ?>
            <!-- ===============================================================
                 KHU VỰC DÀNH CHO KHÁCH CHƯA ĐĂNG NHẬP / ĐĂNG KÝ
                 =============================================================== -->
            <div class="nb-guest-cart-box">
                <div class="nb-gcb-badge">
                    <i class="bi bi-shield-lock-fill text-warning"></i>
                    <span>Tài khoản Biblio</span>
                </div>
                <h3 class="nb-gcb-title">Bạn đã có tài khoản tại Biblio chưa?</h3>
                <p class="nb-gcb-desc">
                    <strong>Đăng nhập</strong> để xem lại sản phẩm đã thêm vào giỏ hàng trước đó hoặc đồng bộ giỏ hàng từ thiết bị khác.<br>
                    Chưa có tài khoản? <strong>Đăng ký ngay</strong> để nhận ngay voucher giảm <strong>50.000₫</strong> và tích lũy Biblio Xu cho mọi đơn sách!
                </p>
                <div class="nb-gcb-actions">
                    <a href="<?php echo esc_url($account_url); ?>#login" class="nb-btn-gcb-login nb-open-auth-modal" data-tab="login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Đăng Nhập</span>
                    </a>
                    <a href="<?php echo esc_url($account_url); ?>#register" class="nb-btn-gcb-register nb-open-auth-modal" data-tab="register">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Đăng Ký Nhận 50.000₫</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Nút Mua Sắm Ngay -->
        <div class="nb-empty-cta-wrap">
            <a href="<?php echo esc_url($shop_url); ?>" class="nb-btn-explore-shop">
                <i class="bi bi-bag-check-fill me-1"></i>
                <span>MUA SẮM NGAY</span>
            </a>
        </div>

    </div>

    <!-- ===================================================================
         3. KHỐI GỢI Ý: CÓ THỂ BẠN CŨNG THÍCH (CROSS-SELLS)
         =================================================================== -->
    <?php if (!empty($rec_products)): ?>
        <div class="nb-cart-cross-sells">
            <div class="nb-cross-sells-header">
                <h3 class="nb-cross-sells-title">
                    <i class="bi bi-fire text-danger me-2"></i> Gợi Ý Sách Hay Dành Riêng Cho Bạn
                </h3>
                <span class="nb-cross-sells-subtitle">Những tựa sách được bạn đọc yêu thích và đặt mua nhiều nhất</span>
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
                ?>
                    <div class="nb-rec-card">
                        <a href="<?php echo esc_url($permalink); ?>" class="nb-rec-thumb-wrap">
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($title); ?>" class="nb-rec-thumb" loading="lazy" />
                        </a>
                        <div class="nb-rec-body">
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
                                <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>
