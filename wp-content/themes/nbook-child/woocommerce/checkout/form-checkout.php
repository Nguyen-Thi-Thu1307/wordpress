<?php
/**
 * Custom Biblio Checkout Template
 * Block-based Modern E-Commerce Standard (Shopee / Biblio)
 * 
 * Features:
 * 1. Delivery Address Block (with quick edit modal / inline editor)
 * 2. Purchased Products Review Block
 * 3. Shipping Methods (Tiêu chuẩn & Nhanh with dynamic estimated delivery dates) + Seller Note
 * 4. Voucher & Student Reward Points Block
 * 5. Payment Methods (Radio cards with icons: COD, Bank Transfer with VietQR, Simulated E-Wallet)
 * 6. Sticky / Prominent Order Summary with Terms Agreement & Orange Place Order Button
 */

defined('ABSPATH') || exit;

// If cart is empty, do not show checkout form
if (WC()->cart->is_empty()) {
    wc_get_template('cart/cart-empty.php');
    return;
}

$checkout = WC()->checkout();

// Get customer data if logged in
$current_user = wp_get_current_user();
$user_id = get_current_user_id();

$billing_first_name = $checkout->get_value('billing_first_name');
if (!$billing_first_name && $user_id) {
    $billing_first_name = $current_user->display_name ?: $current_user->user_login;
}
$billing_phone = $checkout->get_value('billing_phone');
if (!$billing_phone && $user_id) {
    $billing_phone = get_user_meta($user_id, 'billing_phone', true) ?: '';
}
$billing_email = $checkout->get_value('billing_email');
if (!$billing_email && $user_id) {
    $billing_email = $current_user->user_email ?: '';
}
$billing_address_1 = $checkout->get_value('billing_address_1');
if (!$billing_address_1 && $user_id) {
    $billing_address_1 = get_user_meta($user_id, 'billing_address_1', true) ?: '';
}
$billing_city = $checkout->get_value('billing_city');
if (!$billing_city && $user_id) {
    $billing_city = get_user_meta($user_id, 'billing_city', true) ?: 'Hà Nội';
}
$billing_state = $checkout->get_value('billing_state');
if (!$billing_state && $user_id) {
    $billing_state = get_user_meta($user_id, 'billing_state', true) ?: 'Hai Bà Trưng';
}

$has_default_address = (!empty($billing_first_name) && !empty($billing_phone) && !empty($billing_address_1));

// Shipping estimated dates calculation
$today = current_time('timestamp');
$date_std_from = wp_date('d/m', strtotime('+2 days', $today));
$date_std_to   = wp_date('d/m', strtotime('+4 days', $today));
$date_exp      = wp_date('d/m', strtotime('+1 day', $today));

// Free shipping threshold
$subtotal_raw = WC()->cart->get_subtotal();
$is_freeship = ($subtotal_raw >= 250000);
$applied_coupons = WC()->cart->get_applied_coupons();
if (in_array('freeship', array_map('strtolower', $applied_coupons))) {
    $is_freeship = true;
}

// Points discount in session
$use_points = (bool) (WC()->session ? WC()->session->get('nbook_use_reward_points', false) : false);
$nbook_points_balance = function_exists('nbook_get_points_balance') && is_user_logged_in() ? nbook_get_points_balance(get_current_user_id()) : 0;
$nbook_points_cap = min($nbook_points_balance, (int) floor((float) WC()->cart->get_subtotal() * 0.5));

// Chosen payment method
$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
$chosen_gateway = WC()->session ? WC()->session->get('chosen_payment_method') : 'cod';
if (empty($chosen_gateway) || !isset($available_gateways[$chosen_gateway])) {
    $chosen_gateway = 'cod';
}
?>

<div class="nb-checkout-page-wrapper" id="nbCheckoutPageWrap">

    <!-- BREADCRUMB -->
    <nav class="nb-checkout-breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
        <span class="nb-bc-sep">&gt;</span>
        <a href="<?php echo esc_url(wc_get_cart_url()); ?>">Giỏ hàng</a>
        <span class="nb-bc-sep">&gt;</span>
        <span class="nb-bc-current">Thanh toán</span>
    </nav>

    <!-- NOTICES WRAPPER -->
    <div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
        <?php wc_print_notices(); ?>
    </div>

    <!-- MAIN CHECKOUT FORM -->
    <form name="checkout" method="post" class="checkout woocommerce-checkout nb-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" novalidate="novalidate">

        <div class="nb-checkout-grid">

            <!-- ===============================================================
                 LEFT / MAIN COLUMN: BLOCKS 1 TO 5
                 =============================================================== -->
            <div class="nb-checkout-main-col">

                <!-- -----------------------------------------------------------
                     KHỐI 1: ĐỊA CHỈ NHẬN HÀNG (SHOPEE STYLE WITH QUICK EDIT)
                     ----------------------------------------------------------- -->
                <div class="nb-co-card nb-block-address">
                    <div class="nb-co-card-header address-header">
                        <div class="nb-co-card-title">
                            <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                            <span>Địa Chỉ Nhận Hàng</span>
                        </div>
                        <button type="button" class="nb-btn-toggle-address" id="nbToggleAddressBtn">
                            <i class="bi bi-pencil-square me-1"></i> <span id="nbAddressBtnText"><?php echo $has_default_address ? 'Thay đổi' : 'Thêm địa chỉ'; ?></span>
                        </button>
                    </div>

                    <!-- Address Display Card -->
                    <div class="nb-address-display-box" id="nbAddressDisplayBox" style="<?php echo $has_default_address ? '' : 'display:none;'; ?>">
                        <div class="nb-address-row">
                            <span class="nb-addr-name-phone">
                                <strong id="nbDisplayFullName"><?php echo esc_html($billing_first_name ?: 'Khách hàng Biblio'); ?></strong>
                                <span class="nb-addr-sep">(</span><span id="nbDisplayPhone"><?php echo esc_html($billing_phone ?: 'Chưa có SĐT'); ?></span><span class="nb-addr-sep">)</span>
                            </span>
                            <span class="nb-badge-default-addr">Mặc định</span>
                        </div>
                        <div class="nb-address-detail-row">
                            <i class="bi bi-pin-map text-muted me-1"></i>
                            <span id="nbDisplayFullAddress">
                                <?php echo esc_html(($billing_address_1 ? $billing_address_1 . ', ' : '') . ($billing_state ? $billing_state . ', ' : '') . ($billing_city ?: 'Hà Nội')); ?>
                            </span>
                        </div>
                        <div class="nb-address-email-row">
                            <i class="bi bi-envelope text-muted me-1"></i>
                            <span id="nbDisplayEmail"><?php echo esc_html($billing_email ?: 'Chưa cập nhật email'); ?></span>
                        </div>
                    </div>

                    <!-- Address Input Form (Collapsible / Edit mode) -->
                    <div class="nb-address-edit-box" id="nbAddressEditBox" style="<?php echo $has_default_address ? 'display:none;' : ''; ?>">
                        <div class="nb-form-grid-2">
                            <div class="nb-form-group">
                                <label for="billing_first_name">Họ và tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="billing_first_name" id="billing_first_name" class="nb-co-input" value="<?php echo esc_attr($billing_first_name); ?>" placeholder="Ví dụ: Nguyễn Văn A" required />
                            </div>
                            <div class="nb-form-group">
                                <label for="billing_phone">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                                <input type="tel" name="billing_phone" id="billing_phone" class="nb-co-input" value="<?php echo esc_attr($billing_phone); ?>" placeholder="Ví dụ: 0912345678" required />
                            </div>
                        </div>

                        <div class="nb-form-group">
                            <label for="billing_email">Địa chỉ Email (nhận hóa đơn điện tử &amp; trạng thái đơn) <span class="text-danger">*</span></label>
                            <input type="email" name="billing_email" id="billing_email" class="nb-co-input" value="<?php echo esc_attr($billing_email); ?>" placeholder="name@example.com" required />
                        </div>

                        <div class="nb-form-group">
                            <label for="billing_address_1">Địa chỉ cụ thể (Số nhà, Tên đường, Tòa nhà, Ngõ/Ngách) <span class="text-danger">*</span></label>
                            <input type="text" name="billing_address_1" id="billing_address_1" class="nb-co-input" value="<?php echo esc_attr($billing_address_1); ?>" placeholder="Ví dụ: Số 207 Giải Phóng, P. Bạch Mai, Hà Nội" required />
                        </div>

                        <div class="nb-form-grid-2">
                            <div class="nb-form-group">
                                <label for="billing_city">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                                <input type="text" name="billing_city" id="billing_city" class="nb-co-input" value="<?php echo esc_attr($billing_city); ?>" placeholder="Ví dụ: Hà Nội" required />
                            </div>
                            <div class="nb-form-group">
                                <label for="billing_state">Quận / Huyện <span class="text-danger">*</span></label>
                                <input type="text" name="billing_state" id="billing_state" class="nb-co-input" value="<?php echo esc_attr($billing_state); ?>" placeholder="Ví dụ: Quận Hai Bà Trưng" />
                            </div>
                        </div>

                        <!-- Hidden fields to satisfy WooCommerce country and name validation -->
                        <input type="hidden" name="billing_last_name" id="billing_last_name" value="<?php echo esc_attr($billing_first_name); ?>" />
                        <input type="hidden" name="billing_postcode" value="100000" />
                        <input type="hidden" name="shipping_first_name" id="shipping_first_name" value="<?php echo esc_attr($billing_first_name); ?>" />
                        <input type="hidden" name="shipping_last_name" id="shipping_last_name" value="<?php echo esc_attr($billing_first_name); ?>" />
                        <input type="hidden" name="shipping_address_1" id="shipping_address_1" value="<?php echo esc_attr($billing_address_1); ?>" />
                        <input type="hidden" name="shipping_city" id="shipping_city" value="<?php echo esc_attr($billing_city); ?>" />
                        <input type="hidden" name="shipping_state" id="shipping_state" value="<?php echo esc_attr($billing_state); ?>" />
                        <input type="hidden" name="shipping_postcode" value="100000" />
                        <input type="hidden" name="ship_to_different_address" value="0" />
                        <input type="hidden" name="billing_country" value="VN" />
                        <input type="hidden" name="shipping_country" value="VN" />

                        <div class="nb-addr-save-bar">
                            <button type="button" class="nb-btn-save-address" id="nbSaveAddressBtn">
                                <i class="bi bi-check2 me-1"></i> Xác nhận địa chỉ này
                            </button>
                        </div>
                    </div>
                </div>

                <!-- -----------------------------------------------------------
                     KHỐI 2: SẢN PHẨM ĐẶT MUA
                     ----------------------------------------------------------- -->
                <div class="nb-co-card nb-block-products">
                    <div class="nb-co-card-header">
                        <div class="nb-co-card-title">
                            <i class="bi bi-book-half text-primary me-2"></i>
                            <span>Kiểm Tra Đơn Hàng (<?php echo WC()->cart->get_cart_contents_count(); ?> sản phẩm)</span>
                        </div>
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="nb-link-back-cart">
                            <i class="bi bi-arrow-left me-1"></i> Quay lại sửa giỏ hàng
                        </a>
                    </div>

                    <div class="nb-co-products-table">
                        <div class="nb-copt-head">
                            <div class="nb-copth-prod">Sản phẩm</div>
                            <div class="nb-copth-price">Đơn giá</div>
                            <div class="nb-copth-qty">Số lượng</div>
                            <div class="nb-copth-total">Thành tiền</div>
                        </div>

                        <div class="nb-copt-body">
                            <?php
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item):
                                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                if ($_product && $_product->exists() && $cart_item['quantity'] > 0):
                                    $product_name = $_product->get_name();
                                    $thumbnail = $_product->get_image('thumbnail', ['class' => 'nb-co-thumb']);
                                    $price = (float) $_product->get_price();
                                    $line_total = $price * $cart_item['quantity'];
                                    $cats = wc_get_product_category_list($_product->get_id(), ', ');
                            ?>
                                <div class="nb-copt-row">
                                    <div class="nb-copt-col nb-copt-col-prod">
                                        <div class="nb-cop-thumb-wrap"><?php echo $thumbnail; ?></div>
                                        <div class="nb-cop-info">
                                            <h4 class="nb-cop-title"><?php echo esc_html($product_name); ?></h4>
                                            <?php if ($cats): ?>
                                                <span class="nb-cop-cat"><?php echo wp_strip_all_tags($cats); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="nb-copt-col nb-copt-col-price">
                                        <span><?php echo wc_price($price); ?></span>
                                    </div>
                                    <div class="nb-copt-col nb-copt-col-qty">
                                        <span class="nb-cop-qty-pill">x<?php echo esc_html($cart_item['quantity']); ?></span>
                                    </div>
                                    <div class="nb-copt-col nb-copt-col-total">
                                        <strong><?php echo wc_price($line_total); ?></strong>
                                    </div>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>

                <!-- -----------------------------------------------------------
                     KHỐI 3: ĐƠN VỊ VẬN CHUYỂN & GHI CHÚ CHO NGƯỜI BÁN
                     ----------------------------------------------------------- -->
                <div class="nb-co-card nb-block-shipping">
                    <div class="nb-co-card-header">
                        <div class="nb-co-card-title">
                            <i class="bi bi-truck text-success me-2"></i>
                            <span>Hình Thức Vận Chuyển &amp; Ghi Chú</span>
                        </div>
                    </div>

                    <div class="nb-shipping-options-grid">
                        
                        <!-- Option 1: Tiêu chuẩn -->
                        <label class="nb-shipping-radio-card active" for="shipping_standard">
                            <input type="radio" 
                                   name="shipping_method[0]" 
                                   id="shipping_standard" 
                                   value="nbook_standard" 
                                   data-cost="<?php echo $is_freeship ? '0' : '25000'; ?>" 
                                   checked />
                            <div class="nb-src-content">
                                <div class="nb-src-top">
                                    <span class="nb-src-name"><i class="bi bi-box-seam me-1 text-primary"></i> Giao Hàng Tiêu Chuẩn</span>
                                    <strong class="nb-src-price <?php echo $is_freeship ? 'text-success' : ''; ?>">
                                        <?php echo $is_freeship ? 'MIỄN PHÍ' : '25.000₫'; ?>
                                    </strong>
                                </div>
                                <div class="nb-src-est">
                                    <i class="bi bi-clock-history me-1"></i> Dự kiến nhận hàng: <strong><?php echo $date_std_from; ?> - <?php echo $date_std_to; ?></strong>
                                </div>
                                <div class="nb-src-note">Phù hợp với đơn hàng toàn quốc, tiết kiệm chi phí tối đa.</div>
                            </div>
                        </label>

                        <!-- Option 2: Hỏa tốc / Nhanh -->
                        <label class="nb-shipping-radio-card" for="shipping_express">
                            <input type="radio" 
                                   name="shipping_method[0]" 
                                   id="shipping_express" 
                                   value="nbook_express" 
                                   data-cost="40000" />
                            <div class="nb-src-content">
                                <div class="nb-src-top">
                                    <span class="nb-src-name"><i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Giao Hàng Hỏa Tốc (Nhanh)</span>
                                    <strong class="nb-src-price">40.000₫</strong>
                                </div>
                                <div class="nb-src-est">
                                    <i class="bi bi-rocket-takeoff text-danger me-1"></i> Dự kiến nhận hàng: <strong><?php echo $date_exp; ?> (Sớm nhất)</strong>
                                </div>
                                <div class="nb-src-note">Giao ưu tiên nội thành &amp; trung tâm, đóng gói cẩn thận 3 lớp.</div>
                            </div>
                        </label>

                    </div>

                    <!-- Ghi chú cho người bán -->
                    <div class="nb-seller-note-wrap">
                        <label for="order_comments" class="nb-sn-label">
                            <i class="bi bi-chat-left-dots me-1 text-muted"></i> Lời nhắn cho người bán / Ghi chú giao hàng:
                        </label>
                        <textarea name="order_comments" 
                                  id="order_comments" 
                                  class="nb-co-textarea" 
                                  rows="2" 
                                  placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến 15 phút, để trước cửa nhà giúp tôi..."></textarea>
                    </div>
                </div>

                <!-- -----------------------------------------------------------
                     KHỐI 4: VOUCHER & ĐIỂM TÍCH LŨY
                     ----------------------------------------------------------- -->
                <div class="nb-co-card nb-block-voucher">
                    <div class="nb-co-card-header">
                        <div class="nb-co-card-title">
                            <i class="bi bi-ticket-perforated-fill text-danger me-2"></i>
                            <span>Biblio Voucher &amp; Điểm Tích Lũy</span>
                        </div>
                    </div>

                    <!-- Voucher Input in Checkout -->
                    <div class="nb-co-voucher-row">
                        <div class="nb-co-vinput-wrap">
                            <i class="bi bi-ticket-detailed"></i>
                            <input type="text" id="nbCheckoutCouponInput" placeholder="Nhập mã ưu đãi (FREESHIP, BIBLIO50K, SINHVIEN10...)" class="nb-co-vinput" />
                        </div>
                        <button type="button" id="nbCheckoutApplyCouponBtn" class="nb-co-vapply-btn">Áp dụng</button>
                    </div>
                    <div class="nb-checkout-available-vouchers">
                        <span class="nb-co-ac-label">Voucher khả dụng:</span>
                        <?php echo do_shortcode('[nbook_available_vouchers]'); ?>
                    </div>

                    <!-- Applied Voucher Chips -->
                    <?php if (!empty($applied_coupons)): ?>
                        <div class="nb-co-applied-chips">
                            <span class="nb-co-ac-label">Đang áp dụng:</span>
                            <?php foreach ($applied_coupons as $code): ?>
                                <span class="nb-co-chip">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i> <?php echo esc_html(strtoupper($code)); ?>
                                    <button type="button" class="nb-co-chip-remove" data-code="<?php echo esc_attr($code); ?>" title="Hủy mã">&times;</button>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Reward Points Toggle -->
                    <div class="nb-co-points-card">
                        <label class="nb-custom-checkbox">
                            <input type="checkbox" id="nbUsePointsCheckbox" <?php checked($use_points); ?> />
                            <span class="nb-checkmark-box"></span>
                            <div class="nb-points-info">
                                <span class="nb-points-title">
                                    <i class="bi bi-coin text-warning me-1"></i> Dùng <strong>Biblio Xu</strong>
                                </span>
                                <span class="nb-points-desc">Dùng tối đa 50% đơn hàng: <strong><?php echo esc_html(number_format_i18n($nbook_points_cap)); ?> điểm</strong> (1 điểm = 1₫).</span>
                            </div>
                        </label>
                        <span class="nb-points-value text-danger">-<?php echo wp_kses_post(wc_price($nbook_points_cap)); ?></span>
                    </div>
                </div>

                <!-- -----------------------------------------------------------
                     KHỐI 5: PHƯƠNG THỨC THANH TOÁN (RADIO CARDS CÓ ICON)
                     ----------------------------------------------------------- -->
                <div class="nb-co-card nb-block-payment">
                    <div class="nb-co-card-header">
                        <div class="nb-co-card-title">
                            <i class="bi bi-credit-card-2-front text-primary me-2"></i>
                            <span>Phương Thức Thanh Toán</span>
                        </div>
                        <span class="nb-pm-badge-secure"><i class="bi bi-shield-lock-fill text-success me-1"></i> Thanh toán an toàn 100%</span>
                    </div>

                    <div class="nb-payment-methods-list" id="nbPaymentMethodsList">

                        <!-- Method 1: COD -->
                        <div class="nb-payment-method-card <?php echo ($chosen_gateway === 'cod') ? 'selected' : ''; ?>" data-gateway="cod">
                            <label class="nb-pmc-label">
                                <input type="radio" name="payment_method" value="cod" <?php checked($chosen_gateway, 'cod'); ?> />
                                <div class="nb-pmc-icon-wrap icon-cod">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div class="nb-pmc-body">
                                    <div class="nb-pmc-name">
                                        Thanh toán khi nhận hàng (COD)
                                        <span class="nb-pmc-tag-popular">Phổ biến nhất</span>
                                    </div>
                                    <div class="nb-pmc-desc">Thanh toán bằng tiền mặt trực tiếp cho nhân viên giao hàng khi nhận và kiểm tra sách.</div>
                                </div>
                            </label>
                        </div>

                        <!-- Method 2: Bank Transfer (VietQR) -->
                        <div class="nb-payment-method-card <?php echo ($chosen_gateway === 'bacs') ? 'selected' : ''; ?>" data-gateway="bacs">
                            <label class="nb-pmc-label">
                                <input type="radio" name="payment_method" value="bacs" <?php checked($chosen_gateway, 'bacs'); ?> />
                                <div class="nb-pmc-icon-wrap icon-bacs">
                                    <i class="bi bi-qr-code-scan"></i>
                                </div>
                                <div class="nb-pmc-body">
                                    <div class="nb-pmc-name">
                                        Chuyển khoản ngân hàng 24/7 (VietQR)
                                        <span class="nb-pmc-tag-quick">Xác nhận tự động</span>
                                    </div>
                                    <div class="nb-pmc-desc">Quét mã QR qua ứng dụng ngân hàng hoặc Mobile Banking. Miễn phí chuyển khoản 24/7.</div>
                                </div>
                            </label>

                            <!-- VietQR Live Info Box (Expands when bacs selected) -->
                            <div class="nb-bacs-details-box" style="<?php echo ($chosen_gateway === 'bacs') ? 'display:block;' : 'display:none;'; ?>">
                                <div class="nb-vietqr-preview-card">
                                    <div class="nb-vietqr-qr-img-wrap">
                                        <?php
                                        $current_total_raw = WC()->cart->get_total('edit');
                                        $vietqr_url = "https://img.vietqr.io/image/MB-0987654321-compact2.png?amount=" . round($current_total_raw) . "&addInfo=BIBLIO_DH&accountName=CONG%20TY%20CP%20SACH%20BIBLIO";
                                        ?>
                                        <img src="<?php echo esc_url($vietqr_url); ?>" alt="Mã VietQR Biblio" class="nb-vietqr-img" id="nbVietQrImg" />
                                        <span class="nb-vietqr-hint"><i class="bi bi-camera"></i> Quét mã để thanh toán</span>
                                    </div>
                                    <div class="nb-vietqr-text-info">
                                        <div class="nb-vq-row">
                                            <span class="nb-vq-k">Ngân hàng:</span>
                                            <strong class="nb-vq-v">MBBank (Ngân hàng TMCP Quân Đội)</strong>
                                        </div>
                                        <div class="nb-vq-row">
                                            <span class="nb-vq-k">Số tài khoản:</span>
                                            <strong class="nb-vq-v text-primary">0987654321</strong>
                                            <button type="button" class="nb-btn-copy-sm" data-copy="0987654321" title="Sao chép STK"><i class="bi bi-copy"></i></button>
                                        </div>
                                        <div class="nb-vq-row">
                                            <span class="nb-vq-k">Chủ tài khoản:</span>
                                            <strong class="nb-vq-v">CÔNG TY CỔ PHẦN SÁCH BIBLIO</strong>
                                        </div>
                                        <div class="nb-vq-row">
                                            <span class="nb-vq-k">Nội dung CK:</span>
                                            <strong class="nb-vq-v text-danger">BIBLIO [SĐT Của Bạn]</strong>
                                            <button type="button" class="nb-btn-copy-sm" data-copy="BIBLIO <?php echo esc_attr($billing_phone ?: '0912345678'); ?>" title="Sao chép nội dung"><i class="bi bi-copy"></i></button>
                                        </div>
                                        <p class="nb-vq-sub">Hệ thống sẽ cập nhật tự động ngay khi nhận được giao dịch. Sau khi đặt hàng, mã QR chính xác theo mã đơn sẽ tiếp tục hiện tại màn hình Cảm ơn.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Method 3: Ví điện tử / VietQR Mô phỏng -->
                        <div class="nb-payment-method-card <?php echo ($chosen_gateway === 'biblio_simulated_ewallet') ? 'selected' : ''; ?>" data-gateway="biblio_simulated_ewallet">
                            <label class="nb-pmc-label">
                                <input type="radio" name="payment_method" value="biblio_simulated_ewallet" <?php checked($chosen_gateway, 'biblio_simulated_ewallet'); ?> />
                                <div class="nb-pmc-icon-wrap icon-wallet">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                                <div class="nb-pmc-body">
                                    <div class="nb-pmc-name">
                                        Ví Điện Tử / QR VietQR (Mô Phỏng)
                                        <span class="nb-pmc-tag-sim">Mô Phỏng</span>
                                    </div>
                                    <div class="nb-pmc-desc">Thanh toán thử nghiệm nhanh qua ví điện tử liên kết (MoMo, ZaloPay, ShopeePay).</div>
                                </div>
                            </label>

                            <!-- Simulation Notice -->
                            <div class="nb-simulated-alert-box" style="<?php echo ($chosen_gateway === 'biblio_simulated_ewallet') ? 'display:block;' : 'display:none;'; ?>">
                                <div class="nb-sim-alert">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
                                    <div>
                                        <strong>⚠️ LƯU Ý MÔ PHỎNG:</strong>
                                        <p class="mb-0">Đây là chức năng mô phỏng thanh toán ví điện tử phục vụ đồ án môn học &amp; nghiệm thu thử nghiệm. Đơn hàng sẽ được hệ thống tự động xác nhận hoàn tất mà không phát sinh giao dịch trừ tiền thật.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- ===============================================================
                 RIGHT COLUMN: KHỐI 6 - CHI TIẾT THANH TOÁN & ĐẶT HÀNG
                 =============================================================== -->
            <div class="nb-checkout-sidebar-col">
                <div class="nb-co-summary-card" id="nbCheckoutSummaryCard">
                    
                    <h3 class="nb-cos-title">
                        <i class="bi bi-receipt me-2"></i> Chi Tiết Thanh Toán
                    </h3>

                    <div class="nb-cos-breakdown">
                        <div class="nb-cos-row">
                            <span class="nb-cos-label">Tổng tiền hàng:</span>
                            <span class="nb-cos-val" id="nbCoSubtotalVal"><?php echo wc_price(WC()->cart->get_subtotal()); ?></span>
                        </div>

                        <div class="nb-cos-row">
                            <span class="nb-cos-label">Phí vận chuyển:</span>
                            <span class="nb-cos-val" id="nbCoShippingVal"><?php echo $is_freeship ? '<span class="text-success">0₫</span>' : '25.000₫'; ?></span>
                        </div>

                        <?php if (WC()->cart->get_discount_total() > 0): ?>
                            <div class="nb-cos-row discount-row">
                                <span class="nb-cos-label">Giảm giá voucher:</span>
                                <span class="nb-cos-val text-success" id="nbCoDiscountVal">-<?php echo wc_price(WC()->cart->get_discount_total()); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($use_points): ?>
                            <div class="nb-cos-row points-row" id="nbCoPointsRow">
                                <span class="nb-cos-label">Dùng điểm thưởng:</span>
                                <span class="nb-cos-val text-danger" id="nbCoPointsVal">-<?php echo wp_kses_post(wc_price($nbook_points_cap)); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="nb-cos-divider"></div>

                        <div class="nb-cos-total-row">
                            <div class="nb-cost-left">
                                <span class="nb-cost-txt">Tổng thanh toán:</span>
                                <span class="nb-cost-vat">(Đã bao gồm VAT nếu có)</span>
                            </div>
                            <strong class="nb-cost-total" id="nbCoGrandTotal">
                                <?php
                                $final_total = WC()->cart->total;
                                echo wc_price($final_total);
                                ?>
                            </strong>
                        </div>
                    </div>

                    <!-- Terms & Conditions Checkbox -->
                    <div class="nb-terms-box">
                        <label class="nb-custom-checkbox">
                            <input type="checkbox" name="terms" id="nbTermsCheckbox" checked required />
                            <span class="nb-checkmark-box"></span>
                            <span class="nb-terms-txt">
                                Tôi đã đọc và hoàn toàn đồng ý với <a href="#" target="_blank">Điều khoản sử dụng</a> và <a href="#" target="_blank">Chính sách mua hàng</a> của Nhà sách Biblio.
                            </span>
                        </label>
                    </div>

                    <!-- Place Order Button -->
                    <div class="nb-place-order-wrap">
                        <button type="submit" 
                                class="nb-btn-place-order" 
                                name="woocommerce_checkout_place_order" 
                                id="place_order" 
                                value="Đặt Hàng">
                            <span class="nb-btn-order-txt">Đặt Hàng</span>
                            <span class="nb-btn-order-spinner" style="display:none;"><i class="bi bi-arrow-repeat spin"></i> Đang xử lý...</span>
                        </button>
                    </div>

                    <!-- Trust Badges -->
                    <div class="nb-co-trust-badges">
                        <div class="nb-tb-item"><i class="bi bi-shield-check text-success"></i> Đảm bảo chất lượng sách 100%</div>
                        <div class="nb-tb-item"><i class="bi bi-arrow-counterclockwise text-primary"></i> Đổi trả miễn phí trong 7 ngày</div>
                        <div class="nb-tb-item"><i class="bi bi-headset text-warning"></i> Hỗ trợ độc giả 24/7 tận tâm</div>
                    </div>

                </div>
            </div>

        </div>

        <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
    </form>

</div>
