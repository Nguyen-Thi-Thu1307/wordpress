<?php
/**
 * Orders - Biblio Shopee-Style Order Cards & Status Tabs
 *
 * Giao diện Quản lý Đơn mua chuyên nghiệp phong cách sàn TMĐT sách Biblio:
 * - Thanh Tabs lọc theo trạng thái (Tất cả, Chờ thanh toán, Đang đóng gói, Đang giao, Đã giao, Đã hủy)
 * - Ô tìm kiếm nhanh theo mã đơn / tên sách
 * - Danh sách đơn hàng dạng Thẻ (Order Card) hiện đại kèm ảnh bìa sách
 * - Nút xem chi tiết, mua lại (Re-order), thanh toán ngay, hủy đơn và liên hệ Chatbot
 * - Empty state minh họa trực quan
 */

defined('ABSPATH') || exit;

$user_id = get_current_user_id();

// 1. Lấy toàn bộ đơn hàng của khách hàng để tính toán trạng thái & số lượng
$all_orders = wc_get_orders([
    'customer_id' => $user_id,
    'limit'       => -1,
    'orderby'     => 'date',
    'order'       => 'DESC',
]);

$total_all = count($all_orders);
$has_orders = $total_all > 0;
$count_pending = 0;
$count_processing = 0;
$count_shipping = 0;
$count_completed = 0;
$count_cancelled = 0;

foreach ($all_orders as $ord) {
    $st = $ord->get_status();
    if ($st === 'pending') {
        $count_pending++;
    } elseif ($st === 'processing') {
        $count_processing++;
    } elseif (in_array($st, ['on-hold', 'shipping'], true)) {
        $count_shipping++;
    } elseif ($st === 'completed') {
        $count_completed++;
    } elseif (in_array($st, ['cancelled', 'refunded', 'failed'], true)) {
        $count_cancelled++;
    }
}

// 2. Xác định tab trạng thái hiện tại (qua URL param ?order_status=...)
$current_tab = isset($_GET['order_status']) ? sanitize_key($_GET['order_status']) : 'all';
$valid_tabs = ['all', 'pending', 'processing', 'shipping', 'completed', 'cancelled'];
if (!in_array($current_tab, $valid_tabs, true)) {
    $current_tab = 'all';
}

// 3. Lọc danh sách đơn theo tab hiện tại
$filtered_orders = [];
foreach ($all_orders as $ord) {
    $st = $ord->get_status();
    if ($current_tab === 'all') {
        $filtered_orders[] = $ord;
    } elseif ($current_tab === 'pending' && $st === 'pending') {
        $filtered_orders[] = $ord;
    } elseif ($current_tab === 'processing' && $st === 'processing') {
        $filtered_orders[] = $ord;
    } elseif ($current_tab === 'shipping' && in_array($st, ['on-hold', 'shipping'], true)) {
        $filtered_orders[] = $ord;
    } elseif ($current_tab === 'completed' && $st === 'completed') {
        $filtered_orders[] = $ord;
    } elseif ($current_tab === 'cancelled' && in_array($st, ['cancelled', 'refunded', 'failed'], true)) {
        $filtered_orders[] = $ord;
    }
}

$orders_base_url = wc_get_endpoint_url('orders');

do_action('woocommerce_before_account_orders', $has_orders);
?>

<div class="nb-orders-wrap">

    <!-- 1. STATUS FILTER TABS (SHOPEE STYLE) -->
    <div class="nb-orders-tabs-nav" role="tablist">
        <a href="<?php echo esc_url($orders_base_url); ?>" 
           class="nb-order-tab <?php echo $current_tab === 'all' ? 'is-active' : ''; ?>"
           data-tab-status="all">
            <span>Tất cả</span>
            <span class="nb-order-tab-count"><?php echo esc_html($total_all); ?></span>
        </a>

        <a href="<?php echo esc_url(add_query_arg('order_status', 'pending', $orders_base_url)); ?>" 
           class="nb-order-tab <?php echo $current_tab === 'pending' ? 'is-active' : ''; ?>"
           data-tab-status="pending">
            <span>Chờ thanh toán</span>
            <?php if ($count_pending > 0): ?>
                <span class="nb-order-tab-count"><?php echo esc_html($count_pending); ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo esc_url(add_query_arg('order_status', 'processing', $orders_base_url)); ?>" 
           class="nb-order-tab <?php echo $current_tab === 'processing' ? 'is-active' : ''; ?>"
           data-tab-status="processing">
            <span>Đang xử lý</span>
            <?php if ($count_processing > 0): ?>
                <span class="nb-order-tab-count"><?php echo esc_html($count_processing); ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo esc_url(add_query_arg('order_status', 'shipping', $orders_base_url)); ?>" 
           class="nb-order-tab <?php echo $current_tab === 'shipping' ? 'is-active' : ''; ?>"
           data-tab-status="shipping">
            <span>Đang giao hàng</span>
            <?php if ($count_shipping > 0): ?>
                <span class="nb-order-tab-count"><?php echo esc_html($count_shipping); ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo esc_url(add_query_arg('order_status', 'completed', $orders_base_url)); ?>" 
           class="nb-order-tab <?php echo $current_tab === 'completed' ? 'is-active' : ''; ?>"
           data-tab-status="completed">
            <span>Hoàn thành</span>
            <?php if ($count_completed > 0): ?>
                <span class="nb-order-tab-count"><?php echo esc_html($count_completed); ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo esc_url(add_query_arg('order_status', 'cancelled', $orders_base_url)); ?>" 
           class="nb-order-tab <?php echo $current_tab === 'cancelled' ? 'is-active' : ''; ?>"
           data-tab-status="cancelled">
            <span>Đã hủy</span>
            <?php if ($count_cancelled > 0): ?>
                <span class="nb-order-tab-count"><?php echo esc_html($count_cancelled); ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- 2. QUICK SEARCH BAR -->
    <?php if ($total_all > 0): ?>
        <div class="nb-orders-search-bar">
            <i class="bi bi-search nb-orders-search-icon"></i>
            <input type="text" id="nbOrderSearchInput" placeholder="Tìm kiếm theo Mã đơn hàng hoặc Tên sách..." autocomplete="off">
        </div>
    <?php endif; ?>

    <!-- 3. ORDER CARDS LIST -->
    <div id="nbOrdersListContainer">
        <?php if (!empty($filtered_orders)): ?>
            <?php foreach ($filtered_orders as $order): 
                $order_id = $order->get_id();
                $order_number = $order->get_order_number();
                $order_status = $order->get_status();
                $order_date = $order->get_date_created() ? $order->get_date_created()->date('d/m/Y H:i') : '';
                $items = $order->get_items();
                $item_count = $order->get_item_count();

                // Status configuration
                $badge_class = 'nb-status-processing';
                $status_name = wc_get_order_status_name($order_status);
                $status_icon = 'bi-box-seam';

                if ($order_status === 'pending') {
                    $badge_class = 'nb-status-pending';
                    $status_icon = 'bi-hourglass-split';
                    $status_name = 'Chờ thanh toán';
                } elseif ($order_status === 'completed') {
                    $badge_class = 'nb-status-completed';
                    $status_icon = 'bi-check2-circle';
                    $status_name = 'Giao hàng thành công';
                } elseif (in_array($order_status, ['cancelled', 'failed'], true)) {
                    $badge_class = 'nb-status-cancelled';
                    $status_icon = 'bi-x-circle';
                    $status_name = 'Đã hủy đơn';
                } elseif ($order_status === 'refunded') {
                    $badge_class = 'nb-status-refunded';
                    $status_icon = 'bi-arrow-counterclockwise';
                    $status_name = 'Đã hoàn tiền';
                } elseif (in_array($order_status, ['on-hold', 'shipping'], true)) {
                    $badge_class = 'nb-status-shipping';
                    $status_icon = 'bi-truck';
                    $status_name = 'Đang giao hàng';
                }

                $payment_title = $order->get_payment_method_title() ?: 'Thanh toán COD';

                // Collect book names for quick search filter
                $searchable_text = '#' . $order_number;
                ?>
                <div class="nb-order-card" data-order-id="<?php echo esc_attr($order_number); ?>" data-search="<?php echo esc_attr(mb_strtolower($searchable_text, 'UTF-8')); ?>">
                    <!-- Header -->
                    <div class="nb-order-card-header">
                        <div class="nb-order-id-group">
                            <span class="nb-order-code">Đơn hàng #<?php echo esc_html($order_number); ?></span>
                            <span class="nb-order-date"><i class="bi bi-clock"></i> <?php echo esc_html($order_date); ?></span>
                            <span style="font-size: 12px; color: #888;">· <?php echo esc_html($item_count); ?> cuốn sách</span>
                        </div>
                        <span class="nb-status-badge <?php echo esc_attr($badge_class); ?>">
                            <i class="bi <?php echo esc_attr($status_icon); ?>"></i>
                            <?php echo esc_html($status_name); ?>
                        </span>
                    </div>

                    <!-- Items Body -->
                    <div class="nb-order-card-items">
                        <?php foreach ($items as $item): 
                            $product = $item->get_product();
                            $p_name = $item->get_name();
                            $p_link = $product ? $product->get_permalink() : '#';
                            $p_thumb = $product && get_the_post_thumbnail_url($product->get_id(), 'thumbnail') 
                                ? get_the_post_thumbnail_url($product->get_id(), 'thumbnail') 
                                : wc_placeholder_img_src();
                            $p_author = $product ? (get_post_meta($product->get_id(), '_biblio_author', true) ?: get_post_meta($product->get_id(), '_nbook_author', true)) : '';
                            $p_qty = $item->get_quantity();
                            $p_total = $item->get_total();
                            ?>
                            <div class="nb-order-item-row" data-book-title="<?php echo esc_attr(mb_strtolower($p_name, 'UTF-8')); ?>">
                                <img src="<?php echo esc_url($p_thumb); ?>" alt="<?php echo esc_attr($p_name); ?>" class="nb-order-item-thumb" loading="lazy">
                                <div class="nb-order-item-details">
                                    <a href="<?php echo esc_url($p_link); ?>" class="nb-order-item-title"><?php echo esc_html($p_name); ?></a>
                                    <?php if ($p_author): ?>
                                        <div class="nb-order-item-author">Tác giả: <strong><?php echo esc_html($p_author); ?></strong></div>
                                    <?php endif; ?>
                                    <div class="nb-order-item-qty">Số lượng: x<?php echo esc_html($p_qty); ?></div>
                                </div>
                                <div class="nb-order-item-price-col">
                                    <span class="nb-order-item-price"><?php echo wp_strip_all_tags(wc_price($p_total)); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Footer -->
                    <div class="nb-order-card-footer">
                        <div class="nb-order-summary-col">
                            <span class="nb-order-payment-method"><i class="bi bi-wallet2"></i>&nbsp;&nbsp;<?php echo esc_html($payment_title); ?></span>
                            <div class="nb-order-total-text">
                                Thành tiền: <span class="nb-order-total-amount"><?php echo wp_strip_all_tags($order->get_formatted_order_total()); ?></span>
                            </div>
                        </div>

                        <div class="nb-order-actions-group">
                            <!-- Nút Hỏi Chatbot về đơn hàng -->
                            <button type="button" class="nb-btn-chat-order" data-order-number="<?php echo esc_attr($order_number); ?>" title="Hỏi trợ lý ảo về đơn hàng này">
                                <i class="bi bi-chat-dots-fill" style="color: #ee4d2d;"></i>&nbsp;&nbsp;Tra cứu đơn
                            </button>

                            <!-- Nút Xem chi tiết đơn -->
                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="nb-btn-view-order">
                                Xem chi tiết
                            </a>

                            <!-- Nút Thanh toán ngay (nếu pending) -->
                            <?php if ($order->needs_payment()): ?>
                                <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="nb-btn-pay-now">
                                    <i class="bi bi-credit-card"></i>&nbsp;&nbsp;Thanh toán ngay
                                </a>
                            <?php endif; ?>

                            <!-- Nút Mua lại (Re-order) -->
                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('order_again', $order_id, wc_get_cart_url()), 'woocommerce-order_again')); ?>" class="nb-btn-reorder">
                                <i class="bi bi-arrow-repeat"></i>&nbsp;&nbsp;Mua lại
                            </a>

                            <!-- Nút Hủy đơn (chỉ khi chưa vận chuyển) -->
                            <?php if (function_exists('nbook_can_user_cancel_order') && nbook_can_user_cancel_order($order)): ?>
                                <button type="button" 
                                        class="nb-btn-cancel-order nb-trigger-cancel-modal" 
                                        data-order-id="<?php echo esc_attr($order_id); ?>" 
                                        data-order-number="<?php echo esc_attr($order_number); ?>"
                                        title="Hủy đơn hàng này">
                                    <i class="bi bi-x-circle me-1"></i> Hủy đơn
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- 4. EMPTY STATE -->
            <div class="nb-orders-empty-state">
                <div class="nb-orders-empty-icon"><i class="bi bi-inbox"></i></div>
                <h3 class="nb-orders-empty-title">
                    <?php 
                    if ($current_tab === 'all') {
                        echo 'Bạn chưa có đơn hàng nào tại Biblio';
                    } else {
                        echo 'Không có đơn hàng nào ở trạng thái này';
                    }
                    ?>
                </h3>
                <p class="nb-orders-empty-desc">
                    Hàng ngàn tựa sách giáo trình chuẩn NEU, sách kinh tế, văn học và kỹ năng sống hay nhất đang có sẵn tại Biblio với chính sách Freeship và bọc sách miễn phí!
                </p>
                <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-orders-empty-btn">
                    <i class="bi bi-bag-plus"></i> Khám phá và mua sách ngay
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- SCRIPT: TÌM KIẾM ĐƠN HÀNG NHANH & KẾT NỐI CHATBOT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tìm kiếm nhanh đơn hàng
    var searchInput = document.getElementById('nbOrderSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = (this.value || '').trim().toLowerCase();
            var cards = document.querySelectorAll('.nb-order-card');
            cards.forEach(function(card) {
                var orderId = (card.getAttribute('data-order-id') || '').toLowerCase();
                var searchMeta = (card.getAttribute('data-search') || '').toLowerCase();
                var bookRows = card.querySelectorAll('.nb-order-item-row');
                var matchBook = false;
                bookRows.forEach(function(row) {
                    var title = (row.getAttribute('data-book-title') || '').toLowerCase();
                    if (title.indexOf(q) !== -1) {
                        matchBook = true;
                    }
                });

                if (!q || orderId.indexOf(q) !== -1 || searchMeta.indexOf(q) !== -1 || matchBook) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // 2. Bấm "Tra cứu đơn" -> Tự động mở Biblio Chatbot và hỏi về mã đơn
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
                // Mở cửa sổ chat nếu đang đóng
                var chatWindow = document.getElementById('biblio-chat-window');
                if (chatWindow && chatWindow.style.display === 'none') {
                    toggleBtn.click();
                }
                // Điền mã đơn và kích hoạt gửi
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

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>
