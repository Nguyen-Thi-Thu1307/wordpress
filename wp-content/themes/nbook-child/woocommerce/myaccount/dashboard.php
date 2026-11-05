<?php
/**
 * My Account Dashboard - Biblio E-Commerce Hub
 * 
 * Bố cục tổng quan trang "Tài khoản của tôi" chuẩn sàn thương mại điện tử sách:
 * - Hồ sơ thành viên & huy hiệu khách hàng
 * - Thẻ chỉ số nhanh: Biblio Xu, Voucher khả dụng, Tổng đơn mua, Chi tiêu tích lũy
 * - Thanh tiến độ đơn hàng (Pipeline Tracker) 5 trạng thái
 * - Danh sách đơn mua gần nhất dạng thẻ Card
 * - Sổ địa chỉ & Lối tắt bảo mật, CSKH
 * - Đặc quyền thành viên Biblio
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user has admin privileges
$is_admin = class_exists('Biblio_Frontend_Admin_Hub') 
    ? Biblio_Frontend_Admin_Hub::is_admin_user() 
    : (current_user_can('manage_options') || current_user_can('manage_woocommerce') || current_user_can('manage_biblio_orders'));

$is_viewing_as_customer = class_exists('Biblio_Frontend_Admin_Hub')
    ? Biblio_Frontend_Admin_Hub::is_viewing_as_customer()
    : (isset($_GET['view_as']) && $_GET['view_as'] === 'customer');

if ($is_admin && !$is_viewing_as_customer) {
    include get_stylesheet_directory() . '/woocommerce/myaccount/admin-hub-dashboard.php';
    return;
}

$user = wp_get_current_user();
$user_id = get_current_user_id();
$display_name = $user ? $user->display_name : 'Quý khách';
$user_email = $user ? $user->user_email : '';
$registered_date = $user ? date_i18n('d/m/Y', strtotime($user->user_registered)) : '';

// 1. Dữ liệu Xu & Voucher
$points = 0;
if (class_exists('Biblio_Points_Voucher')) {
    $points = Biblio_Points_Voucher::instance()->get_user_points($user_id);
    $vouchers_list = Biblio_Points_Voucher::instance()->get_user_vouchers($user_id);
    $vouchers_count = count($vouchers_list);
} else {
    $points = (int) get_user_meta($user_id, '_nbook_reward_points', true);
    $saved_coupons = (array) get_user_meta($user_id, '_nbook_saved_coupons', true);
    $vouchers_count = count($saved_coupons);
}

// Kiểm tra trạng thái điểm danh hôm nay
$last_checkin_date = get_user_meta($user_id, '_nbook_last_checkin_date', true);
$is_checked_today = ($last_checkin_date === current_time('Y-m-d'));
$checkin_streak = (int) get_user_meta($user_id, '_nbook_checkin_streak', true);
$yesterday = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));
$next_day_in_cycle = (($checkin_streak) % 7) + 1;
if (!$is_checked_today && $last_checkin_date !== $yesterday) {
    $next_day_in_cycle = 1;
}
$today_xu_reward = ($next_day_in_cycle == 7) ? 200 : 100;
$checkin_nonce = wp_create_nonce('nbook_points_action');

// 2. Dữ liệu Đơn hàng
$all_orders = wc_get_orders([
    'customer_id' => $user_id,
    'limit'       => -1,
    'orderby'     => 'date',
    'order'       => 'DESC',
]);

$total_orders = count($all_orders);
$total_spent = wc_get_customer_total_spent($user_id);

$pending_count = 0;
$processing_count = 0;
$shipping_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($all_orders as $ord) {
    $st = $ord->get_status();
    if ($st === 'pending') {
        $pending_count++;
    } elseif ($st === 'processing') {
        $processing_count++;
    } elseif (in_array($st, ['on-hold', 'shipping'], true)) {
        $shipping_count++;
    } elseif ($st === 'completed') {
        $completed_count++;
    } elseif (in_array($st, ['cancelled', 'refunded', 'failed'], true)) {
        $cancelled_count++;
    }
}

// 3. Địa chỉ giao hàng mặc định
$shipping_address = wc_get_account_formatted_address('shipping');
if (empty($shipping_address) || trim(strip_tags($shipping_address)) === '') {
    $shipping_address = wc_get_account_formatted_address('billing');
}

$orders_endpoint_url = wc_get_endpoint_url('orders');

// 4. Dữ liệu Hạng thành viên Shopee-style (Biblio Rewards)
$loyalty = function_exists('nbook_get_customer_loyalty_data') ? nbook_get_customer_loyalty_data($user_id) : null;
$current_tier = $loyalty ? $loyalty['current_tier'] : [
    'key' => 'new', 'name' => 'Thành Viên Mới', 'badge_icon' => 'bi-person-check-fill', 'emoji' => '🌱',
    'color' => '#64748b', 'cashback_label' => '0.5%', 'voucher_monthly' => 'Voucher Chào Bạn Mới 15k',
    'freeship_perk' => 'Mã Freeship chào mừng 20k', 'birthday_perk' => 'Lời chúc sinh nhật',
];
$next_tier = $loyalty ? $loyalty['next_tier'] : null;
?>

<?php if ($is_admin && $is_viewing_as_customer): ?>
    <div class="nb-admin-test-mode-banner">
        <div class="nb-atmb-content">
            <i class="bi bi-eye-fill"></i>
            <span>Bạn đang xem giao diện ở <strong>Chế độ Khách hàng (Thử nghiệm)</strong>.</span>
        </div>
        <a href="<?php echo esc_url(add_query_arg('view_as', 'admin', wc_get_page_permalink('myaccount'))); ?>" class="nb-btn-back-to-admin">
            <i class="bi bi-shield-lock-fill"></i> Quay lại Bảng Quản trị Admin Hub
        </a>
    </div>
<?php endif; ?>

<div class="nb-dashboard-wrap">

    <!-- Floating On-Screen Toast Notification -->
    <div id="nbDashScreenToast" class="nb-screen-toast" role="alert" aria-live="assertive">
        <div class="nb-toast-icon-wrap">
            <i class="bi bi-patch-check-fill"></i>
        </div>
        <div class="nb-toast-body">
            <h4 id="nbDashToastTitle" class="nb-toast-title">🎉 Điểm danh nhận Xu thành công!</h4>
            <p id="nbDashToastMsg" class="nb-toast-msg">+100 Biblio Xu đã được cộng vào tài khoản của bạn.</p>
        </div>
        <button type="button" class="nb-toast-close" onclick="document.getElementById('nbDashScreenToast').classList.remove('show');">&times;</button>
    </div>

    <!-- 1. USER PROFILE HEADER CARD -->
    <div class="nb-dash-profile-card">
        <div class="nb-dash-user-main">
            <div class="nb-dash-avatar-lg">
                <?php 
                $avatar_url = get_avatar_url($user_id, ['size' => 128]);
                if ($avatar_url && !strpos($avatar_url, 'gravatar.com/avatar/?d=') && !strpos($avatar_url, 'd=mm')): ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                <?php else: ?>
                    <span><?php echo esc_html(mb_strtoupper(mb_substr($display_name, 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
                <?php endif; ?>
            </div>
            <div class="nb-dash-user-details">
                <h2>Xin chào, <?php echo esc_html($display_name); ?>! 👋</h2>
                <div class="nb-dash-user-meta">
                    <span class="nb-dash-user-badge nb-tier-pill nb-tier-pill-<?php echo esc_attr($current_tier['key']); ?>">
                        <i class="bi <?php echo esc_attr($current_tier['badge_icon']); ?>"></i> <?php echo esc_html($current_tier['name']); ?>
                    </span>
                    <span><i class="bi bi-envelope"></i> <?php echo esc_html($user_email); ?></span>
                    <?php if ($registered_date): ?>
                        <span><i class="bi bi-calendar3"></i> Thành viên từ: <?php echo esc_html($registered_date); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <a href="<?php echo esc_url(wc_get_endpoint_url('edit-account')); ?>" class="nb-dash-edit-btn">
                <i class="bi bi-pencil-square"></i> Cập nhật hồ sơ
            </a>
        </div>
    </div>

    <!-- 2. SHOPEE-STYLE MEMBERSHIP REWARDS CARD (BIBLIO REWARDS) -->
    <div class="nb-shopee-rewards-card nb-rewards-card-<?php echo esc_attr($current_tier['key']); ?>">
        <div class="nb-rewards-shine"></div>
        
        <div class="nb-rewards-card-top">
            <div class="nb-rewards-tier-info">
                <div class="nb-rewards-badge-icon">
                    <span class="nb-rewards-emoji"><?php echo esc_html($current_tier['emoji']); ?></span>
                </div>
                <div class="nb-rewards-title-group">
                    <div class="nb-rewards-tag">
                        <span>BIBLIO REWARDS</span>
                        <span class="nb-rewards-tag-dot">•</span>
                        <span>KHÁCH HÀNG THÂN THIẾT</span>
                    </div>
                    <h3 class="nb-rewards-tier-name">
                        <?php echo esc_html($current_tier['name']); ?>
                    </h3>
                    <div class="nb-rewards-tenure">
                        <i class="bi bi-clock-history"></i> Đã đồng hành: <strong><?php echo esc_html($loyalty ? $loyalty['tenure_string'] : 'Mới tham gia'); ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="nb-rewards-top-actions">
                <button type="button" class="nb-btn-tier-rules nb-trigger-tier-modal">
                    <i class="bi bi-shield-check"></i> Quy tắc thăng hạng & Quyền lợi <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Progress Bar Section -->
        <div class="nb-rewards-progress-block">
            <div class="nb-rewards-progress-header">
                <div class="nb-rewards-progress-title">
                    <?php if ($next_tier): ?>
                        <span>Tiến độ thăng <strong><?php echo esc_html($next_tier['name']); ?></strong> <?php echo esc_html($next_tier['emoji']); ?></span>
                    <?php else: ?>
                        <span><i class="bi bi-patch-check-fill text-warning"></i> Bạn đang sở hữu thứ hạng cao nhất!</span>
                    <?php endif; ?>
                </div>
                <div class="nb-rewards-progress-percent">
                    <span><?php echo (int)($loyalty ? $loyalty['progress_percent'] : 0); ?>%</span>
                </div>
            </div>

            <div class="nb-rewards-bar-track">
                <div class="nb-rewards-bar-fill" style="width: <?php echo (int)($loyalty ? $loyalty['progress_percent'] : 0); ?>%;"></div>
            </div>

            <!-- 3 Mini Metric Indicators -->
            <?php if ($next_tier && $loyalty): ?>
                <div class="nb-rewards-metrics-row">
                    <!-- Metric 1: Orders -->
                    <div class="nb-rewards-metric-pill <?php echo ($loyalty['completed_orders_count'] >= $next_tier['req_orders']) ? 'is-completed' : ''; ?>">
                        <span class="nb-rmp-icon"><i class="bi bi-bag-check"></i></span>
                        <div class="nb-rmp-content">
                            <span class="nb-rmp-label">Đơn hoàn tất:</span>
                            <span class="nb-rmp-val"><strong><?php echo esc_html($loyalty['completed_orders_count']); ?></strong> / <?php echo esc_html($next_tier['req_orders']); ?> đơn</span>
                        </div>
                        <?php if ($loyalty['completed_orders_count'] >= $next_tier['req_orders']): ?>
                            <i class="bi bi-check-circle-fill nb-rmp-check"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Metric 2: Spent -->
                    <div class="nb-rewards-metric-pill <?php echo ($loyalty['total_spent'] >= $next_tier['req_spent']) ? 'is-completed' : ''; ?>">
                        <span class="nb-rmp-icon"><i class="bi bi-wallet2"></i></span>
                        <div class="nb-rmp-content">
                            <span class="nb-rmp-label">Chi tiêu tích lũy:</span>
                            <span class="nb-rmp-val"><strong><?php echo number_format($loyalty['total_spent'], 0, ',', '.'); ?>₫</strong> / <?php echo number_format($next_tier['req_spent'], 0, ',', '.'); ?>₫</span>
                        </div>
                        <?php if ($loyalty['total_spent'] >= $next_tier['req_spent']): ?>
                            <i class="bi bi-check-circle-fill nb-rmp-check"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Metric 3: Days -->
                    <div class="nb-rewards-metric-pill <?php echo ($loyalty['account_days'] >= $next_tier['req_days']) ? 'is-completed' : ''; ?>">
                        <span class="nb-rmp-icon"><i class="bi bi-calendar-check"></i></span>
                        <div class="nb-rmp-content">
                            <span class="nb-rmp-label">Thời gian dùng:</span>
                            <span class="nb-rmp-val"><strong><?php echo esc_html($loyalty['account_days']); ?></strong> / <?php echo esc_html($next_tier['req_days']); ?> ngày</span>
                        </div>
                        <?php if ($loyalty['account_days'] >= $next_tier['req_days']): ?>
                            <i class="bi bi-check-circle-fill nb-rmp-check"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="nb-rewards-callout-msg">
                    <i class="bi bi-lightbulb-fill"></i>
                    <span><?php echo wp_kses_post($loyalty['callout_message']); ?></span>
                </div>
            <?php elseif ($loyalty): ?>
                <div class="nb-rewards-callout-msg is-max">
                    <i class="bi bi-gem"></i>
                    <span><?php echo wp_kses_post($loyalty['callout_message']); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Perks Strip -->
        <div class="nb-rewards-perks-strip">
            <span class="nb-perks-strip-title"><i class="bi bi-gift"></i> Quyền lợi hiện tại:</span>
            <div class="nb-perks-pills">
                <span class="nb-perk-item"><i class="bi bi-coin text-warning"></i> Tích <?php echo esc_html($current_tier['cashback_label']); ?> Xu</span>
                <span class="nb-perk-item"><i class="bi bi-ticket-perforated text-danger"></i> <?php echo esc_html($current_tier['voucher_monthly']); ?></span>
                <span class="nb-perk-item"><i class="bi bi-truck text-primary"></i> <?php echo esc_html($current_tier['freeship_perk']); ?></span>
                <span class="nb-perk-item"><i class="bi bi-cake2 text-pink"></i> <?php echo esc_html($current_tier['birthday_perk']); ?></span>
            </div>
        </div>
    </div>

    <!-- 3. 4 QUICK METRIC CARDS -->
    <div class="nb-dash-metrics-grid">
        <!-- Metric 1: Biblio Xu -->
        <div class="nb-metric-card">
            <div class="nb-metric-header">
                <span class="nb-metric-title">Số dư Biblio Xu</span>
                <span class="nb-metric-icon nb-icon-xu"><i class="bi bi-coin"></i></span>
            </div>
            <div class="nb-metric-value">
                <span id="nbDashPointsVal"><?php echo number_format_i18n($points); ?></span>
                <span style="font-size: 15px; font-weight: 600; color: #d97706;">Xu</span>
            </div>
            <div class="nb-metric-footer">
                <?php if (!$is_checked_today): ?>
                    <button type="button" class="nb-dash-checkin-btn" id="nbDashCheckinBtn" data-nonce="<?php echo esc_attr($checkin_nonce); ?>" data-reward="<?php echo esc_attr($today_xu_reward); ?>" title="Bấm để nhận ngay +<?php echo esc_attr($today_xu_reward); ?> Xu">
                        <i class="bi bi-calendar2-check-fill"></i> Điểm danh (+<?php echo esc_html($today_xu_reward); ?> Xu)
                    </button>
                    <a href="<?php echo esc_url(wc_get_endpoint_url('diem-thuong')); ?>" class="nb-metric-link" title="Xem chuỗi 7 ngày">Chuỗi 7N <i class="bi bi-chevron-right"></i></a>
                <?php else: ?>
                    <span class="nb-dash-checked-badge"><i class="bi bi-check-circle-fill"></i> Đã nhận hôm nay</span>
                    <a href="<?php echo esc_url(wc_get_endpoint_url('diem-thuong')); ?>" class="nb-metric-link">Ví Xu <i class="bi bi-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metric 2: Voucher của tôi -->
        <div class="nb-metric-card">
            <div class="nb-metric-header">
                <span class="nb-metric-title">Voucher khả dụng</span>
                <span class="nb-metric-icon nb-icon-voucher"><i class="bi bi-ticket-perforated"></i></span>
            </div>
            <div class="nb-metric-value"><?php echo esc_html($vouchers_count); ?> <span style="font-size: 15px; font-weight: 600; color: #dc2626;">Mã ưu đãi</span></div>
            <div class="nb-metric-footer">
                <span class="nb-metric-sub">Freeship & Giảm tới 50k</span>
                <a href="<?php echo esc_url(wc_get_endpoint_url('voucher')); ?>" class="nb-metric-link">Xem ví voucher <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>

        <!-- Metric 3: Đơn mua -->
        <div class="nb-metric-card">
            <div class="nb-metric-header">
                <span class="nb-metric-title">Đơn hàng của tôi</span>
                <span class="nb-metric-icon nb-icon-orders"><i class="bi bi-bag-check"></i></span>
            </div>
            <div class="nb-metric-value"><?php echo esc_html($total_orders); ?> <span style="font-size: 15px; font-weight: 600; color: #0284c7;">Đơn hàng</span></div>
            <div class="nb-metric-footer">
                <span class="nb-metric-sub">Theo dõi vận chuyển</span>
                <a href="<?php echo esc_url($orders_endpoint_url); ?>" class="nb-metric-link">Quản lý đơn <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>

        <!-- Metric 4: Chi tiêu tích lũy -->
        <div class="nb-metric-card">
            <div class="nb-metric-header">
                <span class="nb-metric-title">Chi tiêu tích lũy</span>
                <span class="nb-metric-icon nb-icon-spent"><i class="bi bi-wallet2"></i></span>
            </div>
            <div class="nb-metric-value"><?php echo wp_strip_all_tags(wc_price($loyalty ? $loyalty['total_spent'] : $total_spent)); ?></div>
            <div class="nb-metric-footer">
                <span class="nb-metric-sub"><?php echo esc_html($current_tier['name']); ?> (Tích <?php echo esc_html($current_tier['cashback_label']); ?> Xu)</span>
                <a href="javascript:void(0)" class="nb-metric-link nb-trigger-tier-modal">Xem quyền lợi <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
    </div>

    <!-- 3. ORDER PIPELINE / STATUS TRACKER (SHOPEE STYLE) -->
    <div class="nb-dash-pipeline-card">
        <div class="nb-dash-section-header">
            <h3 class="nb-dash-section-title"><i class="bi bi-truck text-orange" style="color: #ee4d2d;"></i> Tiến Độ Đơn Hàng Của Bạn</h3>
            <a href="<?php echo esc_url($orders_endpoint_url); ?>" class="nb-dash-section-link">Xem tất cả đơn mua <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="nb-pipeline-steps">
            <!-- Chờ thanh toán -->
            <a href="<?php echo esc_url(add_query_arg('order_status', 'pending', $orders_endpoint_url)); ?>" class="nb-pipeline-step">
                <div class="nb-pipeline-icon-wrap">
                    <i class="bi bi-hourglass-split"></i>
                    <?php if ($pending_count > 0): ?>
                        <span class="nb-pipeline-badge"><?php echo esc_html($pending_count); ?></span>
                    <?php endif; ?>
                </div>
                <span class="nb-pipeline-label">Chờ thanh toán</span>
            </a>

            <!-- Đang xử lý / Đóng gói -->
            <a href="<?php echo esc_url(add_query_arg('order_status', 'processing', $orders_endpoint_url)); ?>" class="nb-pipeline-step">
                <div class="nb-pipeline-icon-wrap">
                    <i class="bi bi-box-seam"></i>
                    <?php if ($processing_count > 0): ?>
                        <span class="nb-pipeline-badge"><?php echo esc_html($processing_count); ?></span>
                    <?php endif; ?>
                </div>
                <span class="nb-pipeline-label">Đang đóng gói</span>
            </a>

            <!-- Đang vận chuyển -->
            <a href="<?php echo esc_url(add_query_arg('order_status', 'shipping', $orders_endpoint_url)); ?>" class="nb-pipeline-step">
                <div class="nb-pipeline-icon-wrap">
                    <i class="bi bi-truck"></i>
                    <?php if ($shipping_count > 0): ?>
                        <span class="nb-pipeline-badge"><?php echo esc_html($shipping_count); ?></span>
                    <?php endif; ?>
                </div>
                <span class="nb-pipeline-label">Đang giao hàng</span>
            </a>

            <!-- Đã giao thành công -->
            <a href="<?php echo esc_url(add_query_arg('order_status', 'completed', $orders_endpoint_url)); ?>" class="nb-pipeline-step">
                <div class="nb-pipeline-icon-wrap">
                    <i class="bi bi-check2-circle"></i>
                    <?php if ($completed_count > 0): ?>
                        <span class="nb-pipeline-badge"><?php echo esc_html($completed_count); ?></span>
                    <?php endif; ?>
                </div>
                <span class="nb-pipeline-label">Đã giao</span>
            </a>

            <!-- Đã hủy / Đổi trả -->
            <a href="<?php echo esc_url(add_query_arg('order_status', 'cancelled', $orders_endpoint_url)); ?>" class="nb-pipeline-step">
                <div class="nb-pipeline-icon-wrap" style="color: #6b7280;">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <?php if ($cancelled_count > 0): ?>
                        <span class="nb-pipeline-badge" style="background: #9ca3af;"><?php echo esc_html($cancelled_count); ?></span>
                    <?php endif; ?>
                </div>
                <span class="nb-pipeline-label">Đã hủy / Trả</span>
            </a>
        </div>
    </div>

    <!-- 4. RECENT ORDERS PREVIEW -->
    <div class="nb-dash-recent-orders">
        <div class="nb-dash-section-header">
            <h3 class="nb-dash-section-title"><i class="bi bi-clock-history" style="color: #ee4d2d;"></i> Đơn Mua Gần Đây</h3>
            <?php if (!empty($all_orders)): ?>
                <a href="<?php echo esc_url($orders_endpoint_url); ?>" class="nb-dash-section-link">Xem tất cả (<?php echo esc_html($total_orders); ?>) <i class="bi bi-arrow-right"></i></a>
            <?php endif; ?>
        </div>

        <?php if (!empty($all_orders)): 
            $recent_orders = array_slice($all_orders, 0, 2);
            foreach ($recent_orders as $order):
                $order_id = $order->get_id();
                $order_status = $order->get_status();
                $order_date = $order->get_date_created() ? $order->get_date_created()->date('d/m/Y H:i') : '';
                $items = $order->get_items();
                $total_items = $order->get_item_count();

                $badge_class = 'nb-status-processing';
                $status_name = wc_get_order_status_name($order_status);
                if ($order_status === 'pending') $badge_class = 'nb-status-pending';
                elseif ($order_status === 'completed') $badge_class = 'nb-status-completed';
                elseif (in_array($order_status, ['cancelled', 'failed'])) $badge_class = 'nb-status-cancelled';
                elseif ($order_status === 'refunded') $badge_class = 'nb-status-refunded';
                elseif (in_array($order_status, ['on-hold', 'shipping'])) $badge_class = 'nb-status-shipping';
                ?>
                <div class="nb-order-card">
                    <div class="nb-order-card-header">
                        <div class="nb-order-id-group">
                            <span class="nb-order-code">Đơn hàng #<?php echo esc_html($order->get_order_number()); ?></span>
                            <span class="nb-order-date"><i class="bi bi-calendar3"></i> <?php echo esc_html($order_date); ?></span>
                        </div>
                        <span class="nb-status-badge <?php echo esc_attr($badge_class); ?>">
                            <?php echo esc_html($status_name); ?>
                        </span>
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
                                <img src="<?php echo esc_url($p_thumb); ?>" alt="<?php echo esc_attr($p_name); ?>" class="nb-order-item-thumb" loading="lazy">
                                <div class="nb-order-item-details">
                                    <a href="<?php echo esc_url($p_link); ?>" class="nb-order-item-title"><?php echo esc_html($p_name); ?></a>
                                    <?php if ($p_author): ?>
                                        <div class="nb-order-item-author">Tác giả: <?php echo esc_html($p_author); ?></div>
                                    <?php endif; ?>
                                    <div class="nb-order-item-qty">Số lượng: x<?php echo esc_html($item->get_quantity()); ?></div>
                                </div>
                                <div class="nb-order-item-price-col">
                                    <span class="nb-order-item-price"><?php echo wp_strip_all_tags(wc_price($item->get_total())); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="nb-order-card-footer">
                        <div class="nb-order-summary-col">
                            <span class="nb-order-payment-method"><i class="bi bi-credit-card"></i> <?php echo esc_html($order->get_payment_method_title() ?: 'Thanh toán COD'); ?></span>
                            <div class="nb-order-total-text">Thành tiền: <span class="nb-order-total-amount"><?php echo wp_strip_all_tags($order->get_formatted_order_total()); ?></span></div>
                        </div>
                        <div class="nb-order-actions-group">
                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="nb-btn-view-order">
                                Xem chi tiết
                            </a>
                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('order_again', $order_id, wc_get_cart_url()), 'woocommerce-order_again')); ?>" class="nb-btn-reorder">
                                <i class="bi bi-arrow-repeat"></i> Mua lại
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="nb-orders-empty-state" style="padding: 36px 16px;">
                <div class="nb-orders-empty-icon"><i class="bi bi-bag-x"></i></div>
                <h4 class="nb-orders-empty-title">Bạn chưa có đơn hàng nào tại Biblio</h4>
                <p class="nb-orders-empty-desc">Khám phá hàng ngàn tựa sách giáo trình NEU, sách kinh tế, văn học kinh điển và kỹ năng sống hay nhất với ưu đãi độc quyền hôm nay!</p>
                <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-orders-empty-btn">
                    <i class="bi bi-book"></i> Khám phá sách ngay
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- 5. UTILITIES & SUPPORT -->
    <div class="nb-dash-utils-grid">
        <!-- Box 1: Sổ địa chỉ nhận hàng -->
        <div class="nb-util-box">
            <div class="nb-util-header">
                <h3><i class="bi bi-geo-alt-fill" style="color: #ee4d2d;"></i> Sổ Địa Chỉ Mặc Định</h3>
                <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address')); ?>" class="nb-dash-section-link">Thay đổi</a>
            </div>
            <div class="nb-util-content">
                <?php if (!empty($shipping_address) && trim(strip_tags($shipping_address)) !== ''): ?>
                    <div style="font-weight: 600; color: #111827; margin-bottom: 4px;"><?php echo esc_html($display_name); ?></div>
                    <div style="color: #4b5563;"><?php echo wp_kses_post($shipping_address); ?></div>
                <?php else: ?>
                    <p style="color: #9ca3af; margin: 0 0 10px 0;">Bạn chưa thiết lập địa chỉ nhận hàng mặc định.</p>
                    <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address')); ?>" class="nb-btn-view-order" style="display: inline-block;">+ Thêm địa chỉ</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Box 2: Hỗ trợ khách hàng -->
        <div class="nb-util-box">
            <div class="nb-util-header">
                <h3><i class="bi bi-headset" style="color: #ee4d2d;"></i> Trung Tâm Hỗ Trợ 24/7</h3>
                <span class="nb-dash-user-badge"><i class="bi bi-shield-check"></i> An tâm mua sắm</span>
            </div>
            <div class="nb-util-content">
                <div style="margin-bottom: 8px;">
                    📞 <strong>Hotline CSKH:</strong> <a href="tel:0933109009" style="color: #ee4d2d; font-weight: 700; text-decoration: none;">0933 109 009</a> (08:00 - 21:30)
                </div>
                <div style="margin-bottom: 8px;">
                    💬 <strong>Zalo hỗ trợ:</strong> Hỗ trợ đổi trả, tra cứu vận chuyển thần tốc.
                </div>
                <div>
                    🤖 <strong>Trợ lý ảo Biblio Assistant:</strong> Trò chuyện ngay góc phải dưới màn hình!
                </div>
            </div>
        </div>
    </div>

    <!-- 6. BIBLIO PRIVILEGES BANNER -->
    <div style="background: #fff8f5; border: 1px dashed #fcd5c8; border-radius: 14px; padding: 20px 22px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
            <div style="font-weight: 700; color: #ee4d2d; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-stars"></i> Đặc Quyền Dành Riêng Cho Bạn (<?php echo esc_html($current_tier['name']); ?>):
            </div>
            <button type="button" class="nb-trigger-tier-modal" style="background: none; border: none; color: #ee4d2d; font-size: 13px; font-weight: 600; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 4px;">
                Xem bảng thăng hạng & quyền lợi VIP <i class="bi bi-arrow-right"></i>
            </button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; font-size: 13px; color: #4b5563;">
            <?php if (!empty($current_tier['privileges'])): ?>
                <?php foreach ($current_tier['privileges'] as $priv): ?>
                    <div style="display: flex; align-items: flex-start; gap: 6px;">
                        <i class="bi bi-check-circle-fill" style="color: #10b981; flex-shrink: 0; margin-top: 2px;"></i>
                        <span><?php echo esc_html($priv); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div><i class="bi bi-check-circle-fill" style="color: #10b981;"></i> 100% Sách chính hãng bản quyền</div>
                <div><i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Bọc sách & tặng kèm bookmark miễn phí</div>
                <div><i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Đổi trả miễn phí trong 7 ngày</div>
                <div><i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Tích lũy Biblio Xu cho mỗi đơn hàng</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 7. MODAL QUY TẮC THĂNG HẠNG & QUYỀN LỢI THÀNH VIÊN (SHOPEE REWARDS STYLE) -->
    <div class="nb-tier-modal-overlay" id="nbTierModalOverlay" style="display: none;">
        <div class="nb-tier-modal-wrapper">
            <div class="nb-tier-modal-card">
                <!-- Modal Header -->
                <div class="nb-tier-modal-header">
                    <div class="nb-tier-modal-title-box">
                        <div class="nb-tier-modal-badge"><i class="bi bi-award-fill"></i> BIBLIO REWARDS</div>
                        <h3>Quy Tắc Thăng Hạng & Đặc Quyền Khách Hàng Thân Thiết</h3>
                        <p>Tích lũy chi tiêu và số đơn hàng trọn đời – Mua càng nhiều, đặc quyền càng cao!</p>
                    </div>
                    <button type="button" class="nb-tier-modal-close" id="nbCloseTierModalBtn" aria-label="Đóng">&times;</button>
                </div>

                <!-- Modal Body -->
                <div class="nb-tier-modal-body">
                    <!-- Current User Status Alert -->
                    <div class="nb-tier-user-banner nb-tub-<?php echo esc_attr($current_tier['key']); ?>">
                        <div class="nb-tub-left">
                            <span class="nb-tub-icon"><?php echo esc_html($current_tier['emoji']); ?></span>
                            <div>
                                <div class="nb-tub-title">Cấp bậc hiện tại của bạn: <strong><?php echo esc_html($current_tier['name']); ?></strong></div>
                                <div class="nb-tub-meta">
                                    <span><i class="bi bi-bag-check"></i> Đơn hoàn tất: <strong><?php echo esc_html($loyalty ? $loyalty['completed_orders_count'] : 0); ?></strong></span>
                                    <span>•</span>
                                    <span><i class="bi bi-wallet2"></i> Chi tiêu: <strong><?php echo number_format($loyalty ? $loyalty['total_spent'] : 0, 0, ',', '.'); ?>₫</strong></span>
                                    <span>•</span>
                                    <span><i class="bi bi-calendar3"></i> Đồng hành: <strong><?php echo esc_html($loyalty ? $loyalty['tenure_string'] : ''); ?> (<?php echo esc_html($loyalty ? $loyalty['account_days'] : 0); ?> ngày)</strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="nb-tub-right">
                            <span class="nb-tub-rate">Tích lũy <strong><?php echo esc_html($current_tier['cashback_label']); ?> Xu</strong></span>
                        </div>
                    </div>

                    <!-- Comparison Table -->
                    <div class="nb-tier-table-responsive">
                        <table class="nb-tier-table">
                            <thead>
                                <tr>
                                    <th style="width: 17%; min-width: 140px;">Hạng thành viên</th>
                                    <th style="width: 22%; min-width: 160px;">Số đơn & Chi tiêu<br><span style="font-weight: 400; font-size: 11px; opacity: 0.85;">(Đạt 1 trong 2 điều kiện)</span></th>
                                    <th style="width: 15%; min-width: 125px;">Thời gian dùng<br><span style="font-weight: 400; font-size: 11px; opacity: 0.85;">(Kể từ đăng ký)</span></th>
                                    <th style="width: 13%; min-width: 105px;">Tích lũy Xu</th>
                                    <th style="width: 33%; min-width: 240px;">Voucher & Đặc quyền riêng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($loyalty && !empty($loyalty['all_tiers'])): ?>
                                    <?php foreach ($loyalty['all_tiers'] as $t_key => $t_info): 
                                        $is_current = ($t_key === $current_tier['key']);
                                    ?>
                                        <tr class="<?php echo $is_current ? 'is-current-tier-row' : ''; ?>">
                                            <td class="nb-td-rank">
                                                <div class="nb-rank-box">
                                                    <span class="nb-rank-emoji"><?php echo esc_html($t_info['emoji']); ?></span>
                                                    <div>
                                                        <strong class="nb-rank-name"><?php echo esc_html($t_info['name']); ?></strong>
                                                        <?php if ($is_current): ?>
                                                            <span class="nb-badge-current">Hạng của bạn</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="nb-td-req">
                                                <?php if ($t_info['req_orders'] == 0 && $t_info['req_spent'] == 0): ?>
                                                    <span class="nb-req-free">Mặc định khi đăng ký</span>
                                                <?php else: ?>
                                                    <div>Từ <strong><?php echo esc_html($t_info['req_orders']); ?> đơn hàng</strong></div>
                                                    <div style="font-size: 11.5px; color: #6b7280; margin: 2px 0;">HOẶC</div>
                                                    <div>Từ <strong><?php echo number_format($t_info['req_spent'], 0, ',', '.'); ?>₫</strong> chi tiêu</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="nb-td-days">
                                                <?php if ($t_info['req_days'] == 0): ?>
                                                    <span class="nb-req-free">0 ngày</span>
                                                <?php else: ?>
                                                    <strong><?php echo esc_html($t_info['req_days']); ?> ngày</strong>
                                                    <span class="nb-days-sub">
                                                        <?php 
                                                        if ($t_info['req_days'] == 7) echo '(1 tuần)';
                                                        elseif ($t_info['req_days'] == 30) echo '(1 tháng)';
                                                        elseif ($t_info['req_days'] == 90) echo '(3 tháng)';
                                                        elseif ($t_info['req_days'] == 180) echo '(6 tháng)';
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="nb-td-cashback">
                                                <span class="nb-cashback-badge"><?php echo esc_html($t_info['cashback_label']); ?></span>
                                                <div style="font-size: 11px; color: #6b7280; margin-top: 3px;">
                                                    <?php echo ($t_info['cashback_rate'] * 10); ?> Xu / 1k₫
                                                </div>
                                            </td>
                                            <td class="nb-td-perks">
                                                <ul class="nb-perks-list">
                                                    <?php foreach ($t_info['privileges'] as $priv): ?>
                                                        <li><i class="bi bi-check2 text-success"></i> <?php echo esc_html($priv); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footnotes / Shopee-style FAQ notes -->
                    <div class="nb-tier-modal-notes">
                        <h4><i class="bi bi-info-circle-fill text-primary"></i> Giải đáp chi tiết quy tắc thăng hạng (Shopee Style):</h4>
                        <ol>
                            <li><strong>Mua bao nhiêu đơn, trị giá bao nhiêu thì lên hạng?</strong> Bạn chỉ cần đạt đủ <strong>Số đơn hàng HOẶC Tổng chi tiêu</strong> (Ví dụ: Hạng Đồng chỉ cần 3 đơn hoặc 300.000₫; Hạng Bạc từ 8 đơn hoặc 1.000.000₫; Hạng Vàng từ 20 đơn hoặc 3.000.000₫; Hạng Kim Cương từ 40 đơn hoặc 6.000.000₫).</li>
                            <li><strong>Thời gian dùng bao lâu mới lên đồng, bạc, vàng?</strong> Để đảm bảo tính xác thực của tài khoản và tri ân khách hàng gắn bó, Hạng Đồng yêu cầu thời gian từ <strong>7 ngày</strong> kể từ ngày đăng ký, Hạng Bạc từ <strong>30 ngày (1 tháng)</strong>, Hạng Vàng từ <strong>90 ngày (3 tháng)</strong> và Hạng Kim Cương từ <strong>180 ngày (6 tháng)</strong>.</li>
                            <li><strong>Khi nào đơn hàng được tính thăng hạng?</strong> Hệ thống tự động ghi nhận ngay khi đơn hàng chuyển sang trạng thái <em>"Đã giao thành công" (Completed)</em>. Các đơn hủy hoặc trả hàng sẽ không được tính.</li>
                            <li><strong>Có bị hạ hạng nếu không mua hàng tiếp không?</strong> Tại Biblio, cấp bậc thành viên của bạn được <strong>bảo lưu và cộng dồn trọn đời</strong>, tuyệt đối không bị hạ hạng hay trừ điểm định kỳ như các sàn thông thường!</li>
                        </ol>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="nb-tier-modal-footer">
                    <button type="button" class="nb-btn-close-modal" id="nbBtnCloseModalBottom">Đã hiểu</button>
                    <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-btn-shop-now"><i class="bi bi-bag-plus"></i> Mua sắm tích lũy ngay</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Interaction Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalOverlay = document.getElementById('nbTierModalOverlay');
        var closeBtnTop = document.getElementById('nbCloseTierModalBtn');
        var closeBtnBottom = document.getElementById('nbBtnCloseModalBottom');
        var triggers = document.querySelectorAll('.nb-trigger-tier-modal');

        function openModal() {
            if (modalOverlay) {
                modalOverlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal() {
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        triggers.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });
        });

        if (closeBtnTop) closeBtnTop.addEventListener('click', closeModal);
        if (closeBtnBottom) closeBtnBottom.addEventListener('click', closeModal);

        if (modalOverlay) {
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalOverlay && modalOverlay.style.display === 'flex') {
                closeModal();
            }
        });

        // Điểm danh 1-click ngay trên Dashboard
        var btnDashCheckin = document.getElementById('nbDashCheckinBtn');
        if (btnDashCheckin) {
            btnDashCheckin.addEventListener('click', function(e) {
                e.preventDefault();
                btnDashCheckin.disabled = true;
                btnDashCheckin.innerHTML = '<span class="spinner-border spinner-border-sm" style="display:inline-block;width:12px;height:12px;border:2px solid #fff;border-right-color:transparent;border-radius:50%;animation:spin 0.75s linear infinite;"></span> Đang nhận...';

                var formData = new FormData();
                formData.append('action', 'nbook_daily_checkin_streak');
                formData.append('nonce', btnDashCheckin.getAttribute('data-nonce'));

                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        var toast = document.getElementById('nbDashScreenToast');
                        if (toast) {
                            var titleEl = document.getElementById('nbDashToastTitle');
                            var msgEl = document.getElementById('nbDashToastMsg');
                            if (titleEl) titleEl.textContent = '🎉 Điểm danh nhận Xu thành công!';
                            if (msgEl) {
                                msgEl.innerHTML = '<strong>+' + data.data.points_added + ' Biblio Xu</strong> đã được cộng vào tài khoản của bạn (Chuỗi: <strong>' + data.data.streak + ' ngày</strong>).';
                            }
                            toast.classList.add('show');
                            setTimeout(function() { toast.classList.remove('show'); }, 5000);
                        }
                        var pointsVal = document.getElementById('nbDashPointsVal');
                        if (pointsVal && data.data.balance_formatted) {
                            pointsVal.textContent = data.data.balance_formatted;
                            pointsVal.classList.add('nb-points-bump');
                            setTimeout(function() { pointsVal.classList.remove('nb-points-bump'); }, 1200);
                        }
                        btnDashCheckin.outerHTML = '<span class="nb-dash-checked-badge"><i class="bi bi-check-circle-fill"></i> Đã nhận hôm nay</span>';
                    } else {
                        var toast = document.getElementById('nbDashScreenToast');
                        if (toast) {
                            var titleEl = document.getElementById('nbDashToastTitle');
                            var msgEl = document.getElementById('nbDashToastMsg');
                            if (titleEl) titleEl.textContent = 'Thông báo điểm danh';
                            if (msgEl) msgEl.textContent = data.data.message || 'Có lỗi xảy ra.';
                            toast.classList.add('show');
                            setTimeout(function() { toast.classList.remove('show'); }, 4000);
                        }
                        btnDashCheckin.disabled = false;
                        btnDashCheckin.innerHTML = '<i class="bi bi-calendar2-check-fill"></i> Điểm danh (+' + btnDashCheckin.getAttribute('data-reward') + ' Xu)';
                    }
                })
                .catch(function() {
                    var toast = document.getElementById('nbDashScreenToast');
                    if (toast) {
                        var titleEl = document.getElementById('nbDashToastTitle');
                        var msgEl = document.getElementById('nbDashToastMsg');
                        if (titleEl) titleEl.textContent = 'Lỗi kết nối';
                        if (msgEl) msgEl.textContent = 'Không thể kết nối đến máy chủ, vui lòng thử lại sau.';
                        toast.classList.add('show');
                        setTimeout(function() { toast.classList.remove('show'); }, 4000);
                    }
                    btnDashCheckin.disabled = false;
                    btnDashCheckin.innerHTML = '<i class="bi bi-calendar2-check-fill"></i> Điểm danh (+' + btnDashCheckin.getAttribute('data-reward') + ' Xu)';
                });
            });
        }
    });
    </script>

</div>
