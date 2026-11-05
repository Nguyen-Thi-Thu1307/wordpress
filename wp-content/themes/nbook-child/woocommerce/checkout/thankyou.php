<?php
/**
 * Custom Biblio Thank You (Order Received) Template
 * Designed after Shopee / Biblio Modern E-Commerce Standard
 */

defined('ABSPATH') || exit;

// Clear cart selected keys session after order is received
if (WC()->session) {
    WC()->session->__unset('nbook_selected_cart_keys');
    WC()->session->__unset('nbook_use_reward_points');
    WC()->session->__unset('nbook_points_to_use');
}
?>

<div class="nb-thankyou-page-wrapper" id="nbThankYouPage">
    
    <?php if ($order): ?>

        <?php
        $order_id           = $order->get_id();
        $order_number       = $order->get_order_number();
        $order_date         = wc_format_datetime($order->get_date_created());
        $order_total        = $order->get_formatted_order_total();
        $order_total_raw    = (float) $order->get_total();
        $payment_method_id  = $order->get_payment_method();
        $payment_title      = $order->get_payment_method_title();
        $billing_phone      = $order->get_billing_phone();
        $customer_name      = $order->get_formatted_billing_full_name() ?: 'Quý khách';
        $customer_email     = $order->get_billing_email();
        $customer_address   = $order->get_formatted_billing_address();
        
        $my_account_orders_url = wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'));
        $tracking_url = home_url('/don-hang/');
        if (!get_page_by_path('don-hang')) {
            $tracking_url = $my_account_orders_url;
        }
        ?>

        <!-- BREADCRUMB -->
        <nav class="nb-checkout-breadcrumb mb-3" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><i class="bi bi-house-door" style="margin-right: 5px;"></i> Trang chủ</a>
            <span class="nb-bc-sep">&gt;</span>
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>">Giỏ hàng</a>
            <span class="nb-bc-sep">&gt;</span>
            <span class="nb-bc-current">Đặt hàng thành công</span>
        </nav>

        <!-- 1. SUCCESS BANNER -->
        <div class="nb-ty-banner-card">
            <div class="nb-ty-icon-circle">
                <svg class="nb-checkmark-svg" viewBox="0 0 52 52">
                    <circle class="nb-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="nb-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h1 class="nb-ty-title">Đặt Hàng Thành Công!</h1>
            <p class="nb-ty-subtitle">
                Cảm ơn <strong><?php echo esc_html($customer_name); ?></strong> đã tin tưởng lựa chọn Nhà sách Biblio. Mã đơn hàng của bạn là <span class="nb-ty-order-badge">#<?php echo esc_html($order_number); ?></span>
            </p>
            <p class="nb-ty-email-note">
                Thông tin chi tiết về đơn hàng đã được gửi đến hộp thư <strong class="text-primary"><?php echo esc_html($customer_email); ?></strong>
            </p>
        </div>

        <!-- 2. ORDER OVERVIEW PILLS -->
        <div class="nb-ty-overview-grid">
            <div class="nb-tyo-item">
                <span class="nb-tyo-label"><i class="bi bi-hash"></i> Mã Đơn Hàng</span>
                <strong class="nb-tyo-val">#<?php echo esc_html($order_number); ?></strong>
            </div>
            <div class="nb-tyo-item">
                <span class="nb-tyo-label"><i class="bi bi-calendar3"></i> Ngày Đặt Hàng</span>
                <strong class="nb-tyo-val"><?php echo esc_html($order_date); ?></strong>
            </div>
            <div class="nb-tyo-item">
                <span class="nb-tyo-label"><i class="bi bi-cash-coin"></i> Tổng Thanh Toán</span>
                <strong class="nb-tyo-val text-danger"><?php echo $order_total; ?></strong>
            </div>
            <div class="nb-tyo-item">
                <span class="nb-tyo-label"><i class="bi bi-credit-card"></i> Phương Thức</span>
                <strong class="nb-tyo-val"><?php echo esc_html($payment_title); ?></strong>
            </div>
        </div>

        <!-- 3. PAYMENT METHOD INSTRUCTIONS / VIETQR -->
        <?php if ($payment_method_id === 'bacs'): ?>
            <?php
            $vietqr_amount = round($order_total_raw);
            $vietqr_memo   = 'BIBLIO_' . $order_id;
            $vietqr_url    = "https://img.vietqr.io/image/MB-0987654321-compact2.png?amount={$vietqr_amount}&addInfo={$vietqr_memo}&accountName=CONG%20TY%20CP%20SACH%20BIBLIO";
            ?>
            <div class="nb-ty-payment-guide-card bacs-guide">
                <div class="nb-typg-header">
                    <div class="nb-typg-title">
                        <i class="bi bi-qr-code-scan text-primary me-2"></i>
                        <span>Quét Mã VietQR Để Thanh Toán Đơn Hàng #<?php echo esc_html($order_number); ?></span>
                    </div>
                    <span class="nb-typg-badge">Chuyển khoản 24/7 tự động</span>
                </div>

                <div class="nb-ty-vietqr-flex">
                    <div class="nb-ty-qr-box">
                        <img src="<?php echo esc_url($vietqr_url); ?>" alt="VietQR Đơn Hàng #<?php echo esc_attr($order_number); ?>" class="nb-ty-qr-img" />
                        <div class="nb-ty-qr-hint"><i class="bi bi-camera me-1"></i> Quét bằng mọi app Ngân hàng / Mobile Banking</div>
                    </div>

                    <div class="nb-ty-bank-details">
                        <h4 class="nb-tybd-heading">Thông Tin Chuyển Khoản Chính Xác:</h4>
                        
                        <div class="nb-tybd-row">
                            <span class="nb-tybd-k">Ngân hàng thụ hưởng:</span>
                            <span class="nb-tybd-v"><strong>MBBank (Ngân hàng TMCP Quân Đội)</strong></span>
                        </div>

                        <div class="nb-tybd-row">
                            <span class="nb-tybd-k">Số tài khoản:</span>
                            <span class="nb-tybd-v highlight-acc">
                                <strong>0987654321</strong>
                                <button type="button" class="nb-btn-copy-tag" data-copy="0987654321" title="Sao chép STK">
                                    <i class="bi bi-copy me-1"></i> Sao chép
                                </button>
                            </span>
                        </div>

                        <div class="nb-tybd-row">
                            <span class="nb-tybd-k">Chủ tài khoản:</span>
                            <span class="nb-tybd-v"><strong>CÔNG TY CỔ PHẦN SÁCH BIBLIO</strong></span>
                        </div>

                        <div class="nb-tybd-row">
                            <span class="nb-tybd-k">Số tiền cần chuyển:</span>
                            <span class="nb-tybd-v text-danger highlight-amt">
                                <strong><?php echo number_format($vietqr_amount, 0, ',', '.'); ?>₫</strong>
                                <button type="button" class="nb-btn-copy-tag" data-copy="<?php echo esc_attr($vietqr_amount); ?>" title="Sao chép số tiền">
                                    <i class="bi bi-copy me-1"></i> Sao chép
                                </button>
                            </span>
                        </div>

                        <div class="nb-tybd-row">
                            <span class="nb-tybd-k">Nội dung chuyển khoản:</span>
                            <span class="nb-tybd-v highlight-memo">
                                <strong><?php echo esc_html($vietqr_memo); ?></strong>
                                <button type="button" class="nb-btn-copy-tag" data-copy="<?php echo esc_attr($vietqr_memo); ?>" title="Sao chép nội dung">
                                    <i class="bi bi-copy me-1"></i> Sao chép
                                </button>
                            </span>
                        </div>

                        <div class="nb-tybd-tip">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            <em>Vui lòng giữ nguyên nội dung chuyển khoản để hệ thống tự động nhận diện và cập nhật trạng thái đơn ngay lập tức.</em>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($payment_method_id === 'biblio_simulated_ewallet'): ?>
            <div class="nb-ty-payment-guide-card sim-guide">
                <div class="nb-typg-header">
                    <div class="nb-typg-title text-success">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        <span>Thanh Toán Ví Điện Tử Mô Phỏng Thành Công</span>
                    </div>
                    <span class="nb-sim-tag-pill">Chức năng mô phỏng</span>
                </div>
                <p class="mb-0 text-muted">
                    Hệ thống nghiệm thu đã ghi nhận thanh toán mô phỏng cho đơn hàng <strong>#<?php echo esc_html($order_number); ?></strong>. Đơn hàng đã chuyển sang trạng thái <strong>Đang xử lý (Processing)</strong> và sẽ sớm được bàn giao cho đơn vị vận chuyển.
                </p>
            </div>

        <?php else: // COD ?>
            <div class="nb-ty-payment-guide-card cod-guide">
                <div class="nb-typg-header">
                    <div class="nb-typg-title">
                        <i class="bi bi-truck text-success me-2"></i>
                        <span>Thanh Toán Khi Nhận Hàng (COD)</span>
                    </div>
                </div>
                <p class="mb-0">
                    Nhân viên giao hàng sẽ liên hệ với bạn qua số điện thoại <strong><?php echo esc_html($billing_phone); ?></strong> trước khi giao. Vui lòng chuẩn bị số tiền <strong><?php echo $order_total; ?></strong> khi nhận sách.
                </p>
            </div>
        <?php endif; ?>

        <!-- 4. ORDER DETAILS TABLE -->
        <div class="nb-ty-details-card">
            <h3 class="nb-tyd-title">
                <i class="bi bi-bag-check me-2 text-primary"></i> Chi Tiết Đơn Hàng #<?php echo esc_html($order_number); ?>
            </h3>

            <div class="nb-tyd-items-list">
                <?php
                foreach ($order->get_items() as $item_id => $item):
                    $product = $item->get_product();
                    $item_name = $item->get_name();
                    $item_qty = $item->get_quantity();
                    $item_total = $order->get_formatted_line_subtotal($item);
                    $thumbnail = $product ? $product->get_image('thumbnail', ['class' => 'nb-ty-thumb']) : '';
                ?>
                    <div class="nb-tyd-item-row">
                        <div class="nb-tyd-thumb-box"><?php echo $thumbnail; ?></div>
                        <div class="nb-tyd-info-box">
                            <h4 class="nb-tyd-pname"><?php echo esc_html($item_name); ?></h4>
                            <span class="nb-tyd-pqty">Số lượng: x<?php echo esc_html($item_qty); ?></span>
                        </div>
                        <div class="nb-tyd-total-box">
                            <strong><?php echo $item_total; ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Totals breakdown -->
            <div class="nb-tyd-totals-box">
                <div class="nb-tydt-row">
                    <span>Tổng tiền hàng:</span>
                    <span><?php echo wc_price($order->get_subtotal()); ?></span>
                </div>
                <div class="nb-tydt-row">
                    <span>Phí vận chuyển:</span>
                    <span><?php echo wc_price($order->get_shipping_total()); ?></span>
                </div>
                <?php if ($order->get_discount_total() > 0): ?>
                    <div class="nb-tydt-row discount">
                        <span>Giảm giá:</span>
                        <span class="text-success">-<?php echo wc_price($order->get_discount_total()); ?></span>
                    </div>
                <?php endif; ?>
                <div class="nb-tydt-row grand-total">
                    <strong>Tổng cộng:</strong>
                    <strong class="text-danger fs-5"><?php echo $order_total; ?></strong>
                </div>
            </div>
        </div>

        <!-- 5. DELIVERY ADDRESS & CUSTOMER INFO -->
        <div class="nb-ty-address-card">
            <div class="nb-tya-title">
                <i class="bi bi-geo-alt text-danger me-2"></i> Địa Chỉ Nhận Hàng
            </div>
            <div class="nb-tya-content">
                <strong><?php echo esc_html($customer_name); ?></strong> - <?php echo esc_html($billing_phone); ?><br />
                <?php echo esc_html(wp_strip_all_tags($customer_address)); ?>
            </div>
        </div>

        <!-- 6. ACTION BUTTONS (THEO DÕI ĐƠN HÀNG & TIẾP TỤC MUA SẮM) -->
        <div class="nb-ty-actions-bar">
            <a href="<?php echo esc_url($tracking_url); ?>" class="nb-btn-track-order">
                <i class="bi bi-clock-history me-2"></i> Theo Dõi Đơn Hàng
            </a>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="nb-btn-continue-shopping">
                <i class="bi bi-bag me-2"></i> Tiếp Tục Mua Sắm
            </a>
        </div>

    <?php else: ?>

        <div class="nb-ty-banner-card">
            <h1 class="nb-ty-title">Không Tìm Thấy Đơn Hàng</h1>
            <p class="nb-ty-subtitle">Đơn hàng không tồn tại hoặc bạn không có quyền xem thông tin này.</p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nb-btn-continue-shopping mt-3">Về Trang Chủ</a>
        </div>

    <?php endif; ?>

</div>
