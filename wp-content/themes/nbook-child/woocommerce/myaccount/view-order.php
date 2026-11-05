<?php
/**
 * View Order - Biblio E-Commerce Detailed View
 *
 * Giao diện chi tiết đơn mua:
 * - Timeline tiến trình trạng thái
 * - Khối thông tin người nhận, vận chuyển & thanh toán
 * - Danh sách sách có ảnh bìa & tác giả
 * - Bảng tổng kết chi phí
 * - Lối tắt mua lại & kết nối trợ lý ảo
 */

defined('ABSPATH') || exit;

if (!isset($order) || !$order instanceof WC_Order) {
    return;
}

$order_id = $order->get_id();
$order_number = $order->get_order_number();
$order_status = $order->get_status();
$order_date = $order->get_date_created() ? $order->get_date_created()->date('d/m/Y H:i') : '';
$items = $order->get_items();

// Trạng thái timeline
$status_steps = [
    'pending'    => 1,
    'on-hold'    => 2,
    'processing' => 2,
    'shipping'   => 3,
    'completed'  => 4,
    'cancelled'  => -1,
    'refunded'   => -1,
];
$current_step = $status_steps[$order_status] ?? 2;

$badge_class = 'nb-status-processing';
$status_name = wc_get_order_status_name($order_status);
if ($order_status === 'pending') { $badge_class = 'nb-status-pending'; $status_name = 'Chờ thanh toán'; }
elseif ($order_status === 'completed') { $badge_class = 'nb-status-completed'; $status_name = 'Giao hàng thành công'; }
elseif (in_array($order_status, ['cancelled', 'failed'])) { $badge_class = 'nb-status-cancelled'; $status_name = 'Đã hủy đơn'; }
elseif ($order_status === 'refunded') { $badge_class = 'nb-status-refunded'; $status_name = 'Đã hoàn tiền'; }
elseif (in_array($order_status, ['on-hold', 'shipping'])) { $badge_class = 'nb-status-shipping'; $status_name = 'Đang giao hàng'; }

$orders_url = wc_get_endpoint_url('orders');
?>

<div class="nb-view-order-wrap">

    <!-- Top Navigation Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <a href="<?php echo esc_url($orders_url); ?>" class="nb-metric-link" style="font-size: 14px;">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách Đơn mua
        </a>
        <div style="display: flex; align-items: center; gap: 8px;">
            <button type="button" class="nb-btn-chat-order" data-order-number="<?php echo esc_attr($order_number); ?>">
                <i class="bi bi-chat-dots-fill" style="color: #ee4d2d;"></i> Hỏi trợ lý ảo
            </button>
            <a href="javascript:window.print();" class="nb-dash-edit-btn" style="padding: 6px 12px;">
                <i class="bi bi-printer"></i> In đơn
            </a>
        </div>
    </div>

    <!-- Cancelled Order Notice Banner -->
    <?php if ($order_status === 'cancelled'): ?>
        <div class="nb-cancelled-order-banner" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 18px 22px; margin-top: 18px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 14px;">
            <i class="bi bi-x-circle-fill" style="color: #ef4444; font-size: 26px; line-height: 1; flex-shrink: 0; margin-top: 2px;"></i>
            <div style="flex: 1;">
                <div style="font-weight: 700; color: #991b1b; font-size: 15px; margin-bottom: 5px;">Đơn hàng đã được hủy</div>
                <div style="font-size: 13.5px; color: #7f1d1d; line-height: 1.6;">
                    <?php 
                    $cancel_reason = $order->get_meta('_nb_cancel_reason') ?: get_post_meta($order_id, '_nb_cancel_reason', true);
                    $cancel_note = $order->get_meta('_nb_cancel_note') ?: get_post_meta($order_id, '_nb_cancel_note', true);
                    if (!empty($cancel_reason)) {
                        echo '<span><strong>Lý do hủy:</strong> ' . esc_html($cancel_reason) . '</span>';
                        if (!empty($cancel_note)) {
                            echo '<div style="margin-top: 4px; color: #991b1b;"><strong>Ghi chú:</strong> ' . esc_html($cancel_note) . '</div>';
                        }
                    } else {
                        echo 'Đơn hàng này đã bị hủy theo yêu cầu của bạn.';
                    }
                    ?>
                </div>
            </div>
            <span class="nb-status-badge nb-status-cancelled">
                <i class="bi bi-x-circle"></i> Đã hủy đơn
            </span>
        </div>
    <?php endif; ?>

    <!-- Timeline Progress Card -->
    <?php if ($order_status !== 'cancelled' && $order_status !== 'refunded'): ?>
        <div class="nb-order-timeline-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <span style="font-weight: 700; color: #111827; font-size: 15px;">
                    Chi tiết đơn hàng #<?php echo esc_html($order_number); ?>
                </span>
                <span class="nb-status-badge <?php echo esc_attr($badge_class); ?>">
                    <?php echo esc_html($status_name); ?>
                </span>
            </div>

            <div class="nb-timeline-steps">
                <!-- Step 1: Đặt hàng -->
                <div class="nb-timeline-step <?php echo $current_step >= 1 ? 'is-done' : ''; ?>">
                    <div class="nb-timeline-icon"><i class="bi bi-receipt"></i></div>
                    <div class="nb-timeline-label">Đã đặt hàng</div>
                    <div class="nb-timeline-time"><?php echo esc_html($order_date); ?></div>
                </div>

                <!-- Step 2: Đang đóng gói -->
                <div class="nb-timeline-step <?php echo $current_step >= 2 ? 'is-done' : ''; ?>">
                    <div class="nb-timeline-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="nb-timeline-label">Đang đóng gói</div>
                    <div class="nb-timeline-time"><?php echo $current_step >= 2 ? 'Đã duyệt sách' : 'Chờ xử lý'; ?></div>
                </div>

                <!-- Step 3: Đang giao hàng -->
                <div class="nb-timeline-step <?php echo $current_step >= 3 ? 'is-done' : ''; ?>">
                    <div class="nb-timeline-icon"><i class="bi bi-truck"></i></div>
                    <div class="nb-timeline-label">Đang giao hàng</div>
                    <div class="nb-timeline-time"><?php echo $current_step >= 3 ? 'Đang trên đường' : 'Dự kiến 1-2 ngày'; ?></div>
                </div>

                <!-- Step 4: Giao thành công -->
                <div class="nb-timeline-step <?php echo $current_step >= 4 ? 'is-done' : ''; ?>">
                    <div class="nb-timeline-icon"><i class="bi bi-check2-circle"></i></div>
                    <div class="nb-timeline-label">Đã nhận hàng</div>
                    <div class="nb-timeline-time"><?php echo $current_step >= 4 ? 'Hoàn tất' : 'Chờ giao'; ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recipient & Payment Info Row -->
    <div class="nb-order-info-grid">
        <!-- Card 1: Địa chỉ nhận hàng -->
        <div class="nb-info-card">
            <h4 class="nb-info-card-title"><i class="bi bi-geo-alt-fill" style="color: #ee4d2d;"></i> Địa Chỉ Nhận Hàng</h4>
            <div class="nb-info-card-content">
                <div style="font-weight: 700; color: #111827; margin-bottom: 2px;">
                    <?php echo esc_html($order->get_formatted_shipping_full_name() ?: $order->get_formatted_billing_full_name()); ?>
                </div>
                <div style="color: #6b7280; margin-bottom: 4px;">
                    📞 <?php echo esc_html($order->get_billing_phone() ?: 'Chưa cập nhật SĐT'); ?>
                </div>
                <div style="color: #374151;">
                    <?php echo wp_kses_post($order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address()); ?>
                </div>
            </div>
        </div>

        <!-- Card 2: Thanh toán & Vận chuyển -->
        <div class="nb-info-card">
            <h4 class="nb-info-card-title"><i class="bi bi-credit-card-2-front-fill" style="color: #ee4d2d;"></i> Thanh Toán & Giao Hàng</h4>
            <div class="nb-info-card-content">
                <div style="margin-bottom: 6px;">
                    💳 <strong>Phương thức:</strong> <?php echo esc_html($order->get_payment_method_title() ?: 'Thanh toán COD'); ?>
                </div>
                <div style="margin-bottom: 6px;">
                    🚚 <strong>Vận chuyển:</strong> <?php echo esc_html($order->get_shipping_method() ?: 'Giao hàng tiêu chuẩn Biblio'); ?>
                </div>
                <div>
                    🔖 <strong>Bọc sách:</strong> Miễn phí tặng kèm bookmark nghệ thuật
                </div>
            </div>
        </div>
    </div>

    <!-- Book Items List -->
    <div class="nb-order-card" style="margin-bottom: 0;">
        <div class="nb-order-card-header">
            <span style="font-weight: 700; color: #111827;">Danh sách tựa sách đã đặt (<?php echo esc_html($order->get_item_count()); ?> cuốn)</span>
            <span style="font-size: 13px; color: #6b7280;">Mã đơn: #<?php echo esc_html($order_number); ?></span>
        </div>

        <div class="nb-order-card-items">
            <?php foreach ($items as $item): 
                $product = $item->get_product();
                $p_name = $item->get_name();
                $p_link = $product ? $product->get_permalink() : '#';
                $p_thumb = $product && get_the_post_thumbnail_url($product->get_id(), 'thumbnail') 
                    ? get_the_post_thumbnail_url($product->get_id(), 'thumbnail') 
                    : wc_placeholder_img_src();
                $p_author = $product ? (get_post_meta($product->get_id(), '_biblio_author', true) ?: get_post_meta($product->get_id(), '_nbook_author', true)) : '';
                ?>
                <div class="nb-order-item-row">
                    <img src="<?php echo esc_url($p_thumb); ?>" alt="<?php echo esc_attr($p_name); ?>" class="nb-order-item-thumb">
                    <div class="nb-order-item-details">
                        <a href="<?php echo esc_url($p_link); ?>" class="nb-order-item-title"><?php echo esc_html($p_name); ?></a>
                        <?php if ($p_author): ?>
                            <div class="nb-order-item-author">Tác giả: <strong><?php echo esc_html($p_author); ?></strong></div>
                        <?php endif; ?>
                        <div class="nb-order-item-qty">Số lượng: x<?php echo esc_html($item->get_quantity()); ?></div>
                    </div>
                    <div class="nb-order-item-price-col">
                        <span class="nb-order-item-price"><?php echo wp_strip_all_tags(wc_price($item->get_total())); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Cost Breakdown Table -->
    <div class="nb-order-totals-card">
        <div class="nb-totals-row">
            <span>Tiền hàng (tạm tính):</span>
            <span style="font-weight: 600;"><?php echo wp_strip_all_tags(wc_price($order->get_subtotal())); ?></span>
        </div>

        <?php if ($order->get_discount_total() > 0): ?>
            <div class="nb-totals-row" style="color: #dc2626;">
                <span>Giảm giá Voucher:</span>
                <span style="font-weight: 600;">-<?php echo wp_strip_all_tags(wc_price($order->get_discount_total())); ?></span>
            </div>
        <?php endif; ?>

        <div class="nb-totals-row">
            <span>Phí vận chuyển:</span>
            <span style="font-weight: 600;">
                <?php 
                $shipping_total = $order->get_shipping_total();
                echo $shipping_total > 0 ? wp_strip_all_tags(wc_price($shipping_total)) : 'Miễn phí (Freeship)';
                ?>
            </span>
        </div>

        <div class="nb-totals-row nb-totals-final">
            <span>Tổng thanh toán:</span>
            <span class="nb-totals-value"><?php echo wp_strip_all_tags($order->get_formatted_order_total()); ?></span>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; flex-wrap: wrap;">
            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('order_again', $order_id, wc_get_cart_url()), 'woocommerce-order_again')); ?>" class="nb-btn-reorder">
                <i class="bi bi-arrow-repeat"></i> Mua lại đơn này
            </a>
            <?php if ($order->needs_payment()): ?>
                <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="nb-btn-pay-now">
                    <i class="bi bi-credit-card"></i> Thanh toán ngay
                </a>
            <?php endif; ?>
            <?php if (function_exists('nbook_can_user_cancel_order') && nbook_can_user_cancel_order($order)): ?>
                <button type="button" 
                        class="nb-btn-cancel-order nb-trigger-cancel-modal" 
                        data-order-id="<?php echo esc_attr($order_id); ?>" 
                        data-order-number="<?php echo esc_attr($order_number); ?>"
                        title="Hủy đơn hàng này">
                    <i class="bi bi-x-circle me-1"></i> Hủy đơn hàng
                </button>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- SCRIPT: TỰ ĐỘNG KẾT NỐI CHATBOT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        var chatOrderBtn = e.target.closest('.nb-btn-chat-order');
        if (chatOrderBtn) {
            e.preventDefault();
            var orderNum = chatOrderBtn.getAttribute('data-order-number');
            if (!orderNum) return;

            var toggleBtn = document.getElementById('biblio-chat-toggle-btn');
            var chatInput = document.getElementById('biblio-chat-input');
            var chatForm = document.getElementById('biblio-chat-form');

            if (toggleBtn) {
                var chatWindow = document.getElementById('biblio-chat-window');
                if (chatWindow && chatWindow.style.display === 'none') {
                    toggleBtn.click();
                }
                if (chatInput && chatForm) {
                    chatInput.value = '#' + orderNum;
                    setTimeout(function() {
                        var submitEvent = new Event('submit', { cancelable: true });
                        chatForm.dispatchEvent(submitEvent);
                    }, 200);
                }
            }
        }
    });
});
</script>
