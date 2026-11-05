<?php
/**
 * My Account Navigation - Biblio Modern E-Commerce Style (Shopee / Biblio)
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
$display_name = $user ? $user->display_name : 'Quý khách';
$user_id = get_current_user_id();

// Check Admin Status & View Mode
$is_admin = class_exists('Biblio_Frontend_Admin_Hub') 
    ? Biblio_Frontend_Admin_Hub::is_admin_user() 
    : (current_user_can('manage_options') || current_user_can('manage_woocommerce') || current_user_can('manage_biblio_orders'));

$is_viewing_as_customer = class_exists('Biblio_Frontend_Admin_Hub')
    ? Biblio_Frontend_Admin_Hub::is_viewing_as_customer()
    : (isset($_GET['view_as']) && $_GET['view_as'] === 'customer');

if ($is_admin && !$is_viewing_as_customer) {
    $hub_metrics = Biblio_Frontend_Admin_Hub::get_hub_metrics();
    $active_tab = isset($_GET['admin_tab']) ? sanitize_key($_GET['admin_tab']) : 'overview';
    $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
    ?>
    <nav class="woocommerce-MyAccount-navigation nb-admin-sidebar-nav" aria-label="Menu Quản Trị Biblio">
        <!-- Profile Header -->
        <div class="nb-account-sidebar-profile nb-admin-sb-profile">
            <div class="nb-sidebar-avatar-wrap">
                <div class="nb-sidebar-avatar">
                    <?php echo get_avatar($user_id, 120, '', esc_attr($display_name)); ?>
                </div>
                <span class="nb-avatar-crown-icon" title="Quản trị viên"><i class="bi bi-patch-check-fill"></i></span>
            </div>
            <div class="nb-sidebar-info">
                <h4 class="nb-sidebar-name" title="<?php echo esc_attr($display_name); ?>"><?php echo esc_html($display_name); ?></h4>
                <div class="nb-sidebar-badges">
                    <span class="nb-sidebar-tier-badge nb-badge-admin-role" style="border-color: #ee4d2d; color: #ee4d2d; background: #fff5f2;">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Quản Trị Viên Biblio</span>
                    </span>
                </div>
                <div class="nb-admin-email-tag small text-muted"><i class="bi bi-envelope"></i> <?php echo esc_html($user->user_email); ?></div>
            </div>
        </div>

        <!-- Admin Quick Metric Strip -->
        <div class="nb-sidebar-wallet-strip nb-admin-strip">
            <a href="<?php echo esc_url(add_query_arg('admin_tab', 'overview', $account_url)); ?>" class="nb-sws-item nb-admin-tab-link" data-tab="overview">
                <i class="bi bi-cash-stack text-danger"></i>
                <div class="nb-sws-meta">
                    <span class="nb-sws-val text-danger" style="font-size: 13px;"><?php echo wp_strip_all_tags(wc_price($hub_metrics['rev_today'])); ?></span>
                    <span class="nb-sws-label">Hôm nay</span>
                </div>
            </a>
            <div class="nb-sws-divider"></div>
            <a href="<?php echo esc_url(add_query_arg('admin_tab', 'orders', $account_url)); ?>" class="nb-sws-item nb-admin-tab-link" data-tab="orders">
                <i class="bi bi-bell-fill text-warning"></i>
                <div class="nb-sws-meta">
                    <span class="nb-sws-val text-warning"><?php echo esc_html($hub_metrics['approval_needed_count']); ?></span>
                    <span class="nb-sws-label">Đơn cần duyệt</span>
                </div>
            </a>
        </div>

        <!-- Admin Navigation Groups -->
        <div class="nb-sidebar-menu-groups">
            <!-- Group 1: Quản Trị Nghiệp Vụ -->
            <div class="nb-nav-group">
                <div class="nb-nav-group-title">
                    <span>TRUNG TÂM QUẢN TRỊ BIBLIO</span>
                </div>
                <ul class="nb-nav-group-list">
                    <li class="<?php echo ($active_tab === 'overview') ? 'is-active active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('admin_tab', 'overview', $account_url)); ?>" class="nb-nav-link nb-admin-tab-link" data-tab="overview">
                            <span class="nb-nav-icon-box" style="--icon-color: #3b82f6;">
                                <i class="bi bi-bar-chart-line-fill"></i>
                            </span>
                            <span class="nb-nav-text">Báo Cáo & Doanh Thu</span>
                        </a>
                    </li>
                    <li class="<?php echo ($active_tab === 'orders') ? 'is-active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('admin_tab', 'orders', $account_url)); ?>" class="nb-nav-link nb-admin-tab-link" data-tab="orders">
                            <span class="nb-nav-icon-box" style="--icon-color: #ef4444;">
                                <i class="bi bi-inbox-fill"></i>
                            </span>
                            <span class="nb-nav-text">Tiếp Nhận & Duyệt Đơn</span>
                            <?php if ($hub_metrics['approval_needed_count'] > 0): ?>
                                <span class="nb-nav-badge nb-badge-orange"><?php echo esc_html($hub_metrics['approval_needed_count']); ?> đơn</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?php echo ($active_tab === 'books') ? 'is-active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('admin_tab', 'books', $account_url)); ?>" class="nb-nav-link nb-admin-tab-link" data-tab="books">
                            <span class="nb-nav-icon-box" style="--icon-color: #10b981;">
                                <i class="bi bi-book-half"></i>
                            </span>
                            <span class="nb-nav-text">Quản Lý Sách & Kho</span>
                        </a>
                    </li>
                    <li class="<?php echo ($active_tab === 'posts') ? 'is-active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('admin_tab', 'posts', $account_url)); ?>" class="nb-nav-link nb-admin-tab-link" data-tab="posts">
                            <span class="nb-nav-icon-box" style="--icon-color: #8b5cf6;">
                                <i class="bi bi-newspaper"></i>
                            </span>
                            <span class="nb-nav-text">Đăng Tin Tức - Sự Kiện</span>
                        </a>
                    </li>
                    <li class="<?php echo ($active_tab === 'customers') ? 'is-active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('admin_tab', 'customers', $account_url)); ?>" class="nb-nav-link nb-admin-tab-link" data-tab="customers">
                            <span class="nb-nav-icon-box" style="--icon-color: #06b6d4;">
                                <i class="bi bi-people-fill"></i>
                            </span>
                            <span class="nb-nav-text">Quản Lý Khách Hàng</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 2: Cá Nhân & Chuyển Đổi Chế Độ -->
            <div class="nb-nav-group nb-nav-group-system">
                <div class="nb-nav-group-title">
                    <span>HỆ THỐNG & CÁ NHÂN</span>
                </div>
                <ul class="nb-nav-group-list">
                    <li>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="nb-nav-link">
                            <span class="nb-nav-icon-box" style="--icon-color: #6366f1;">
                                <i class="bi bi-person-bounding-box"></i>
                            </span>
                            <span class="nb-nav-text">Hồ Sơ & Mật Khẩu Admin</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('view_as', 'customer', $account_url)); ?>" class="nb-nav-link">
                            <span class="nb-nav-icon-box" style="--icon-color: #f59e0b;">
                                <i class="bi bi-eye"></i>
                            </span>
                            <span class="nb-nav-text">Xem Chế Độ Khách Hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(admin_url()); ?>" target="_blank" class="nb-nav-link">
                            <span class="nb-nav-icon-box" style="--icon-color: #0284c7;">
                                <i class="bi bi-speedometer2"></i>
                            </span>
                            <span class="nb-nav-text">Trang Quản Trị WP-Admin</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('customer-logout')); ?>" 
                           class="nb-nav-link nb-logout-link nb-logout-trigger" 
                           data-logout-url="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" 
                           data-logout-nonce="<?php echo esc_attr(wp_create_nonce('log-out')); ?>">
                            <span class="nb-nav-icon-box" style="--icon-color: #ef4444;">
                                <i class="bi bi-box-arrow-right"></i>
                            </span>
                            <span class="nb-nav-text">Đăng Xuất Tài Khoản</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php
    do_action('woocommerce_after_account_navigation');
    return;
}

// Dynamic metrics
$order_count = 0;
if (function_exists('wc_get_customer_order_count')) {
    $order_count = wc_get_customer_order_count($user_id);
} else {
    $customer = new WC_Customer($user_id);
    $order_count = $customer ? $customer->get_order_count() : 0;
}

$points_count = 0;
$voucher_count = 0;
$tier_info = [
    'tier' => 'Thành viên',
    'color' => '#ee4d2d',
    'icon' => 'bi-patch-check-fill'
];

if (class_exists('Biblio_Points_Voucher')) {
    $pv = Biblio_Points_Voucher::instance();
    $points_count = $pv->get_user_points($user_id);
    $voucher_count = count($pv->get_user_vouchers($user_id));
    $tier_info = $pv->get_tier_info($user_id);
}

// Đồng bộ cùng hệ thống Hạng thành viên Shopee Rewards
if (function_exists('nbook_get_customer_loyalty_data')) {
    $loyalty_nav = nbook_get_customer_loyalty_data($user_id);
    if ($loyalty_nav && !empty($loyalty_nav['current_tier'])) {
        $tier_info = [
            'tier'       => $loyalty_nav['current_tier']['name'],
            'short_name' => $loyalty_nav['current_tier']['short_name'],
            'color'      => $loyalty_nav['current_tier']['color'],
            'icon'       => $loyalty_nav['current_tier']['badge_icon'],
            'emoji'      => $loyalty_nav['current_tier']['emoji'],
        ];
    }
}

// Menu groups configuration
$groups = [
    'overview' => [
        'title' => 'Tổng quan',
        'items' => ['dashboard'],
    ],
    'shopping' => [
        'title' => 'Đơn hàng & Mua sắm',
        'items' => ['orders', 'downloads'],
    ],
    'rewards' => [
        'title' => 'Ưu đãi & Tích điểm',
        'items' => ['voucher', 'diem-thuong'],
    ],
    'account' => [
        'title' => 'Tài khoản của tôi',
        'items' => ['edit-address', 'edit-account'],
    ],
    'system' => [
        'title' => '',
        'items' => ['customer-logout'],
    ],
];

// Item configuration details
$item_meta = [
    'dashboard' => [
        'label' => 'Bảng điều khiển',
        'icon'  => 'bi-grid-1x2-fill',
        'color' => '#3b82f6',
    ],
    'orders' => [
        'label' => 'Đơn mua của tôi',
        'icon'  => 'bi-bag-check-fill',
        'color' => '#10b981',
        'badge' => $order_count > 0 ? $order_count . ' đơn' : '',
        'badge_class' => 'nb-badge-neutral',
    ],
    'downloads' => [
        'label' => 'Sách số & Tải xuống',
        'icon'  => 'bi-cloud-arrow-down-fill',
        'color' => '#06b6d4',
    ],
    'voucher' => [
        'label' => 'Kho Voucher của tôi',
        'icon'  => 'bi-ticket-perforated-fill',
        'color' => '#ee4d2d',
        'badge' => $voucher_count > 0 ? $voucher_count . ' mã' : '',
        'badge_class' => 'nb-badge-orange',
    ],
    'diem-thuong' => [
        'label' => 'Biblio Xu & Thưởng',
        'icon'  => 'bi-coin',
        'color' => '#f59e0b',
        'badge' => number_format_i18n($points_count) . ' Xu',
        'badge_class' => 'nb-badge-gold',
    ],
    'edit-address' => [
        'label' => 'Sổ địa chỉ nhận hàng',
        'icon'  => 'bi-geo-alt-fill',
        'color' => '#8b5cf6',
    ],
    'edit-account' => [
        'label' => 'Hồ sơ & Mật khẩu',
        'icon'  => 'bi-person-bounding-box',
        'color' => '#6366f1',
    ],
    'customer-logout' => [
        'label' => 'Đăng xuất tài khoản',
        'icon'  => 'bi-box-arrow-right',
        'color' => '#ef4444',
    ],
];

$all_menu_items = wc_get_account_menu_items();

do_action('woocommerce_before_account_navigation');
?>

<nav class="woocommerce-MyAccount-navigation" aria-label="<?php esc_html_e('Trang tài khoản', 'woocommerce'); ?>">
    
    <!-- Profile Mini Card in Sidebar -->
    <div class="nb-account-sidebar-profile">
        <div class="nb-sidebar-avatar-wrap">
            <div class="nb-sidebar-avatar">
                <?php 
                $avatar_url = get_avatar_url($user_id, ['size' => 120]);
                if ($avatar_url && !strpos($avatar_url, 'gravatar.com/avatar/?d=') && !strpos($avatar_url, 'd=mm')): ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                <?php else: ?>
                    <span><?php echo esc_html(mb_strtoupper(mb_substr($display_name, 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
                <?php endif; ?>
            </div>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="nb-avatar-edit-badge" title="Sửa hồ sơ">
                <i class="bi bi-pencil-fill"></i>
            </a>
        </div>

        <div class="nb-sidebar-info">
            <h4 class="nb-sidebar-name" title="<?php echo esc_attr($display_name); ?>"><?php echo esc_html($display_name); ?></h4>
            <div class="nb-sidebar-badges">
                <span class="nb-sidebar-tier-badge" style="border-color: <?php echo esc_attr($tier_info['color'] ?? '#ee4d2d'); ?>; color: <?php echo esc_attr($tier_info['color'] ?? '#ee4d2d'); ?>;">
                    <i class="bi <?php echo esc_attr($tier_info['icon'] ?? 'bi-patch-check-fill'); ?>"></i>
                    <span><?php 
                        $raw_tier = $tier_info['tier'] ?? 'Thành viên';
                        if (mb_strpos($raw_tier, 'Hạng ') === 0 || mb_strpos($raw_tier, 'Thành ') === 0) {
                            echo esc_html($raw_tier);
                        } else {
                            echo 'Hạng ' . esc_html($raw_tier);
                        }
                    ?></span>
                </span>
            </div>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="nb-sidebar-edit-link">
                <i class="bi bi-pencil-square"></i>&nbsp;&nbsp;Chỉnh sửa hồ sơ
            </a>
        </div>
    </div>

    <!-- Quick Wallet Summary Strip -->
    <div class="nb-sidebar-wallet-strip">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('diem-thuong')); ?>" class="nb-sws-item">
            <i class="bi bi-coin text-warning"></i>
            <div class="nb-sws-meta">
                <span class="nb-sws-val"><?php echo esc_html(number_format_i18n($points_count)); ?></span>
                <span class="nb-sws-label">Biblio Xu</span>
            </div>
        </a>
        <div class="nb-sws-divider"></div>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('voucher')); ?>" class="nb-sws-item">
            <i class="bi bi-ticket-perforated-fill text-danger"></i>
            <div class="nb-sws-meta">
                <span class="nb-sws-val"><?php echo esc_html($voucher_count); ?></span>
                <span class="nb-sws-label">Voucher</span>
            </div>
        </a>
    </div>

    <?php if ($is_admin && $is_viewing_as_customer): ?>
        <div class="nb-admin-sb-switch-back">
            <a href="<?php echo esc_url(add_query_arg('view_as', 'admin', function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/'))); ?>" class="nb-btn-return-admin">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Về Bảng Quản Trị Admin</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Grouped Navigation Menu -->
    <div class="nb-sidebar-menu-groups">
        <?php foreach ($groups as $group_key => $group): 
            // Check if any items in this group exist in WooCommerce menu items
            $valid_items = array_filter($group['items'], function($ep) use ($all_menu_items) {
                return isset($all_menu_items[$ep]);
            });
            if (empty($valid_items)) continue;
        ?>
            <div class="nb-nav-group <?php echo esc_attr($group_key === 'system' ? 'nb-nav-group-system' : ''); ?>">
                <?php if (!empty($group['title'])): ?>
                    <div class="nb-nav-group-title">
                        <span><?php echo esc_html($group['title']); ?></span>
                    </div>
                <?php endif; ?>

                <ul class="nb-nav-group-list">
                    <?php foreach ($valid_items as $endpoint): 
                        $cfg = $item_meta[$endpoint] ?? [
                            'label' => $all_menu_items[$endpoint] ?? $endpoint,
                            'icon'  => 'bi-chevron-right',
                            'color' => '#6b7280'
                        ];
                        $label = $cfg['label'];
                        $icon = $cfg['icon'];
                        $is_current = wc_is_current_account_menu_item($endpoint);
                        $is_logout = ($endpoint === 'customer-logout');
                    ?>
                        <li class="<?php echo wc_get_account_menu_item_classes($endpoint); ?> <?php echo $is_current ? 'is-active active' : ''; ?>">
                            <?php if ($is_logout): ?>
                                <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" 
                                   class="nb-nav-link nb-logout-link nb-logout-trigger" 
                                   data-logout-url="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" 
                                   data-logout-nonce="<?php echo esc_attr(wp_create_nonce('log-out')); ?>">
                                    <span class="nb-nav-icon-box" style="--icon-color: <?php echo esc_attr($cfg['color']); ?>;">
                                        <i class="bi <?php echo esc_attr($icon); ?>"></i>
                                    </span>
                                    <span class="nb-nav-text"><?php echo esc_html($label); ?></span>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" 
                                   class="nb-nav-link <?php echo $is_current ? 'is-active' : ''; ?>" 
                                   <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
                                    <span class="nb-nav-icon-box" style="--icon-color: <?php echo esc_attr($cfg['color']); ?>;">
                                        <i class="bi <?php echo esc_attr($icon); ?>"></i>
                                    </span>
                                    <span class="nb-nav-text"><?php echo esc_html($label); ?></span>
                                    
                                    <?php if (!empty($cfg['badge'])): ?>
                                        <span class="nb-nav-badge <?php echo esc_attr($cfg['badge_class'] ?? 'nb-badge-neutral'); ?>">
                                            <?php echo esc_html($cfg['badge']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($is_current): ?>
                                        <i class="bi bi-chevron-right nb-active-arrow"></i>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

</nav>

<?php do_action('woocommerce_after_account_navigation'); ?>
