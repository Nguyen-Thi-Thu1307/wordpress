<?php
/**
 * My Addresses - Biblio Modern E-Commerce Style (Shopee / Biblio)
 */

defined('ABSPATH') || exit;

$customer_id = get_current_user_id();
$customer = new WC_Customer($customer_id);

$shipping_name = trim($customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name());
if (empty($shipping_name)) {
    $shipping_name = trim($customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name());
}
if (empty($shipping_name)) {
    $shipping_name = $customer->get_display_name() ?: 'Chưa cập nhật tên người nhận';
}

$shipping_phone = $customer->get_shipping_phone();
if (empty($shipping_phone)) {
    $shipping_phone = $customer->get_billing_phone();
}

$billing_name = trim($customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name()) ?: ($customer->get_display_name() ?: 'Chưa cập nhật tên');
$billing_phone = $customer->get_billing_phone();
$billing_email = $customer->get_billing_email() ?: $customer->get_email();

$formatted_shipping = wc_get_account_formatted_address('shipping');
$formatted_billing = wc_get_account_formatted_address('billing');

$edit_shipping_url = wc_get_endpoint_url('edit-address', 'shipping');
$edit_billing_url = wc_get_endpoint_url('edit-address', 'billing');
?>

<div class="nb-myaccount-panel nb-address-book-panel">

    <!-- Top Header -->
    <div class="nb-panel-header">
        <div class="nb-ph-left">
            <h2 class="nb-ph-title"><i class="bi bi-geo-alt-fill text-danger me-2"></i> Sổ Địa Chỉ Nhận Hàng</h2>
            <p class="nb-ph-subtitle">Quản lý địa chỉ giao hàng và thông tin thanh toán khi mua sách tại Biblio.</p>
        </div>
        <div class="nb-ph-right">
            <a href="<?php echo esc_url($edit_shipping_url); ?>" class="nb-btn-add-address">
                <i class="bi bi-plus-lg"></i>&nbsp;&nbsp;Cập nhật địa chỉ
            </a>
        </div>
    </div>

    <!-- Notice info bar -->
    <div class="nb-address-info-tip">
        <i class="bi bi-info-circle-fill"></i>
        <span>Các địa chỉ dưới đây sẽ được tự động điền sẵn khi bạn tiến hành thanh toán giỏ hàng sách để tiết kiệm thời gian.</span>
    </div>

    <!-- Address Cards Grid -->
    <div class="nb-address-cards-grid">

        <!-- Card 1: Địa chỉ giao hàng (Mặc định) -->
        <div class="nb-address-card <?php echo $formatted_shipping ? 'is-active is-default' : 'is-empty'; ?>">
            <div class="nb-ac-header">
                <div class="nb-ac-title-wrap">
                    <span class="nb-ac-icon"><i class="bi bi-truck"></i></span>
                    <h3 class="nb-ac-title">Địa chỉ giao hàng</h3>
                </div>
                <span class="nb-ac-badge badge-default">
                    <i class="bi bi-check-circle-fill"></i>&nbsp;&nbsp;Mặc định nhận sách
                </span>
            </div>

            <div class="nb-ac-body">
                <div class="nb-ac-recipient">
                    <strong class="nb-ac-name"><?php echo esc_html($shipping_name); ?></strong>
                    <?php if ($shipping_phone): ?>
                        <span class="nb-ac-phone"><i class="bi bi-telephone-fill"></i>&nbsp;&nbsp;<?php echo esc_html($shipping_phone); ?></span>
                    <?php else: ?>
                        <span class="nb-ac-phone-empty text-muted"><i class="bi bi-telephone"></i>&nbsp;&nbsp;Chưa có SĐT nhận hàng</span>
                    <?php endif; ?>
                </div>

                <div class="nb-ac-location">
                    <?php if ($formatted_shipping): ?>
                        <address class="nb-ac-formatted-address">
                            <?php echo wp_kses_post($formatted_shipping); ?>
                        </address>
                    <?php else: ?>
                        <div class="nb-ac-empty-prompt">
                            <i class="bi bi-geo-alt"></i>
                            <p>Bạn chưa thiết lập địa chỉ giao hàng mặc định.</p>
                            <span class="nb-prompt-sub">Thêm địa chỉ ngay để nhận sách tận nhà với tốc độ nhanh nhất!</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nb-ac-footer">
                <a href="<?php echo esc_url($edit_shipping_url); ?>" class="nb-ac-btn-edit">
                    <i class="bi bi-pencil-fill"></i>&nbsp;&nbsp;<?php echo $formatted_shipping ? 'Chỉnh sửa địa chỉ' : 'Thêm địa chỉ giao hàng'; ?>
                </a>
            </div>
        </div>

        <!-- Card 2: Địa chỉ thanh toán / Hóa đơn -->
        <div class="nb-address-card <?php echo $formatted_billing ? 'is-billing' : 'is-empty'; ?>">
            <div class="nb-ac-header">
                <div class="nb-ac-title-wrap">
                    <span class="nb-ac-icon icon-billing"><i class="bi bi-receipt"></i></span>
                    <h3 class="nb-ac-title">Địa chỉ thanh toán</h3>
                </div>
                <span class="nb-ac-badge badge-billing">
                    <i class="bi bi-file-earmark-text"></i>&nbsp;&nbsp;Hóa đơn VAT
                </span>
            </div>

            <div class="nb-ac-body">
                <div class="nb-ac-recipient">
                    <strong class="nb-ac-name"><?php echo esc_html($billing_name); ?></strong>
                    <?php if ($billing_phone): ?>
                        <span class="nb-ac-phone"><i class="bi bi-telephone-fill"></i>&nbsp;&nbsp;<?php echo esc_html($billing_phone); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($billing_email): ?>
                    <div class="nb-ac-email text-muted">
                        <i class="bi bi-envelope-fill"></i>&nbsp;&nbsp;<?php echo esc_html($billing_email); ?>
                    </div>
                <?php endif; ?>

                <div class="nb-ac-location">
                    <?php if ($formatted_billing): ?>
                        <address class="nb-ac-formatted-address">
                            <?php echo wp_kses_post($formatted_billing); ?>
                        </address>
                    <?php else: ?>
                        <div class="nb-ac-empty-prompt">
                            <i class="bi bi-receipt"></i>
                            <p>Bạn chưa thiết lập địa chỉ thanh toán hoặc xuất hóa đơn.</p>
                            <span class="nb-prompt-sub">Mặc định sẽ áp dụng thông tin của địa chỉ giao hàng.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nb-ac-footer">
                <a href="<?php echo esc_url($edit_billing_url); ?>" class="nb-ac-btn-edit nb-btn-outline-edit">
                    <i class="bi bi-pencil-fill"></i>&nbsp;&nbsp;<?php echo $formatted_billing ? 'Chỉnh sửa thông tin' : 'Thiết lập hóa đơn'; ?>
                </a>
            </div>
        </div>

    </div>

    <!-- Shipping Assurance Feature Box -->
    <div class="nb-address-assurance-box">
        <h4 class="nb-aab-title"><i class="bi bi-shield-check text-success me-2"></i> Cam kết đóng gói & Giao nhận sách tại Biblio</h4>
        <div class="nb-aab-grid">
            <div class="nb-aab-item">
                <div class="nb-aab-icon"><i class="bi bi-box2-fill text-warning"></i></div>
                <div class="nb-aab-meta">
                    <strong>Đóng gói 3 lớp tiêu chuẩn</strong>
                    <span>Bọc màng co, xốp bóng khí và hộp carton cứng giữ nguyên vẹn gáy & mép sách.</span>
                </div>
            </div>
            <div class="nb-aab-item">
                <div class="nb-aab-icon"><i class="bi bi-eye-fill text-primary"></i></div>
                <div class="nb-aab-meta">
                    <strong>Đồng kiểm khi nhận hàng</strong>
                    <span>Bạn được kiểm tra kiện sách cùng nhân viên bưu tá trước khi nhận hàng.</span>
                </div>
            </div>
            <div class="nb-aab-item">
                <div class="nb-aab-icon"><i class="bi bi-arrow-repeat text-danger"></i></div>
                <div class="nb-aab-meta">
                    <strong>Đổi trả 1-1 miễn phí 7 ngày</strong>
                    <span>Hỗ trợ đổi cuốn mới tận nhà nếu có lỗi nhà xuất bản hoặc hư hỏng do vận chuyển.</span>
                </div>
            </div>
        </div>
    </div>

</div>
