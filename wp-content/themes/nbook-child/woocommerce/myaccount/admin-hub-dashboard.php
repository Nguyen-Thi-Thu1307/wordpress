<?php
/**
 * Frontend Admin Hub Dashboard Template
 * 
 * Bảng điều khiển Quản trị viên trực quan ngay trên giao diện web (/tai-khoan/)
 * Không cần truy cập /wp-admin/ để duyệt đơn, xem doanh thu, quản lý sách hay đăng bài.
 * 
 * @package Nbook_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$admin_display_name = $current_user->display_name ?: 'Quản trị viên';
$admin_email = $current_user->user_email;

// Fetch metrics
$metrics = Biblio_Frontend_Admin_Hub::get_hub_metrics();

// Get active tab from URL parameter (?admin_tab=overview|orders|books|posts|customers)
$active_tab = isset($_GET['admin_tab']) ? sanitize_key($_GET['admin_tab']) : 'overview';
$valid_tabs = ['overview', 'orders', 'books', 'posts', 'customers'];
if (!in_array($active_tab, $valid_tabs, true)) {
    $active_tab = 'overview';
}

// 1. Fetch Orders for Order Approval Tab
$orders_list = wc_get_orders([
    'limit'   => 50,
    'orderby' => 'date',
    'order'   => 'DESC',
]);

// 2. Fetch Books for Book Management Tab
$books_list = wc_get_products([
    'limit'   => 50,
    'status'  => 'publish',
    'orderby' => 'date',
    'order'   => 'DESC',
]);

// 3. Fetch Posts for News & Events Tab
$posts_list = get_posts([
    'numberposts' => 30,
    'post_status' => 'publish',
    'orderby'     => 'date',
    'order'       => 'DESC',
]);

// 4. Fetch Customers for Customer Tab
$customers_list = get_users([
    'number'  => 30,
    'role__in'=> ['customer', 'subscriber'],
    'orderby' => 'registered',
    'order'   => 'DESC',
]);

// 5. Product categories for modal
$product_cats = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
]);

// 6. Post categories for modal
$post_cats = get_categories([
    'hide_empty' => false,
]);

// Top 5 bestsellers
$top_bestsellers = wc_get_products([
    'limit'    => 5,
    'status'   => 'publish',
    'meta_key' => 'total_sales',
    'orderby'  => 'meta_value_num',
    'order'    => 'DESC',
]);

// Top 5 low stock books
$low_stock_books = wc_get_products([
    'limit'        => 5,
    'status'       => 'publish',
    'meta_query'   => [
        'relation' => 'OR',
        [
            'key'     => '_stock',
            'value'   => 5,
            'compare' => '<=',
            'type'    => 'NUMERIC',
        ],
        [
            'key'     => '_stock_status',
            'value'   => 'outofstock',
            'compare' => '=',
        ],
    ],
    'orderby'      => 'meta_value_num',
    'meta_key'     => '_stock',
    'order'        => 'ASC',
]);

$hub_nonce = wp_create_nonce('nbook_admin_hub_nonce');
?>

<div class="nb-admin-hub-wrapper" id="nbAdminHub" data-nonce="<?php echo esc_attr($hub_nonce); ?>">

    <!-- ===================================================================
         1. TOP ADMIN WELCOME BANNER & ACTION BAR
         =================================================================== -->
    <div class="nb-ah-top-bar">
        <div class="nb-ah-welcome-left">
            <div class="nb-ah-avatar-wrap">
                <?php echo get_avatar($user_id, 56, '', esc_attr($admin_display_name), ['class' => 'nb-ah-avatar-img']); ?>
                <span class="nb-ah-crown-badge" title="Quản trị viên cấp cao"><i class="bi bi-patch-check-fill"></i></span>
            </div>
            <div class="nb-ah-welcome-meta">
                <div class="nb-ah-role-tag">
                    <i class="bi bi-shield-lock-fill"></i> BẢNG ĐIỀU KHIỂN QUẢN TRỊ VIÊN BIBLIO
                </div>
                <h2 class="nb-ah-title">Xin chào, <?php echo esc_html($admin_display_name); ?>!</h2>
                <div class="nb-ah-subtitle">
                    <span><i class="bi bi-calendar3"></i> <?php echo date_i18n('l, d/m/Y'); ?></span>
                    <span class="nb-ah-sep">•</span>
                    <span><i class="bi bi-bell-fill text-warning"></i> Có <strong class="text-white bg-danger px-2 py-0 rounded"><?php echo esc_html($metrics['approval_needed_count']); ?> đơn</strong> đang chờ tiếp nhận & xử lý</span>
                </div>
            </div>
        </div>

        <div class="nb-ah-top-actions">
            <a href="<?php echo esc_url(add_query_arg('view_as', 'customer', wc_get_page_permalink('myaccount'))); ?>" class="nb-btn-ah nb-btn-ah-outline" title="Chuyển sang xem giao diện như một khách hàng mua sắm">
                <i class="bi bi-eye"></i> <span>Xem Chế Độ Khách Hàng</span>
            </a>
            <a href="<?php echo esc_url(admin_url()); ?>" target="_blank" class="nb-btn-ah nb-btn-ah-secondary" title="Vào trang quản trị WordPress gốc">
                <i class="bi bi-speedometer2"></i> <span>WP-Admin</span>
            </a>
        </div>
    </div>

    <!-- ===================================================================
         2. KPI CARDS STRIP (THỐNG KÊ DOANH THU & CHỈ SỐ NHANH)
         =================================================================== -->
    <div class="nb-ah-kpi-grid">
        <!-- Doanh thu hôm nay -->
        <div class="nb-kpi-card nb-kpi-highlight">
            <div class="nb-kpi-header">
                <span class="nb-kpi-title">Doanh Thu Hôm Nay</span>
                <span class="nb-kpi-icon"><i class="bi bi-cash-coin"></i></span>
            </div>
            <div class="nb-kpi-value text-danger" id="kpiRevToday">
                <?php echo wp_strip_all_tags(wc_price($metrics['rev_today'])); ?>
            </div>
            <div class="nb-kpi-footer">
                <span class="nb-kpi-badge"><i class="bi bi-clock-history"></i> Cập nhật tức thì</span>
            </div>
        </div>

        <!-- Doanh thu 7 ngày -->
        <div class="nb-kpi-card">
            <div class="nb-kpi-header">
                <span class="nb-kpi-title">Doanh Thu 7 Ngày Qua</span>
                <span class="nb-kpi-icon bg-info-light"><i class="bi bi-graph-up-arrow text-info"></i></span>
            </div>
            <div class="nb-kpi-value" id="kpiRev7d">
                <?php echo wp_strip_all_tags(wc_price($metrics['rev_7d'])); ?>
            </div>
            <div class="nb-kpi-footer">
                <span class="text-muted small">7 ngày gần nhất</span>
            </div>
        </div>

        <!-- Doanh thu 30 ngày -->
        <div class="nb-kpi-card">
            <div class="nb-kpi-header">
                <span class="nb-kpi-title">Doanh Thu 30 Ngày</span>
                <span class="nb-kpi-icon bg-primary-light"><i class="bi bi-wallet2 text-primary"></i></span>
            </div>
            <div class="nb-kpi-value" id="kpiRev30d">
                <?php echo wp_strip_all_tags(wc_price($metrics['rev_30d'])); ?>
            </div>
            <div class="nb-kpi-footer">
                <span class="text-muted small">Tháng hiện tại</span>
            </div>
        </div>

        <!-- Đơn cần tiếp nhận & duyệt -->
        <div class="nb-kpi-card nb-kpi-urgent nb-switch-tab-card" data-tab="orders" style="cursor: pointer;">
            <div class="nb-kpi-header">
                <span class="nb-kpi-title">Đơn Chờ Tiếp Nhận / Duyệt</span>
                <span class="nb-kpi-icon bg-danger-light"><i class="bi bi-inbox-fill text-danger"></i></span>
            </div>
            <div class="nb-kpi-value text-danger" id="kpiPendingOrders">
                <?php echo esc_html($metrics['approval_needed_count']); ?> <small style="font-size: 15px;">đơn</small>
            </div>
            <div class="nb-kpi-footer">
                <span class="text-danger small font-weight-bold"><i class="bi bi-hand-index-thumb"></i> Bấm để duyệt đơn ngay</span>
            </div>
        </div>

        <!-- Cảnh báo tồn kho -->
        <div class="nb-kpi-card nb-switch-tab-card" data-tab="books" style="cursor: pointer;">
            <div class="nb-kpi-header">
                <span class="nb-kpi-title">Cảnh Báo Sắp Hết Kho</span>
                <span class="nb-kpi-icon bg-warning-light"><i class="bi bi-exclamation-triangle-fill text-warning"></i></span>
            </div>
            <div class="nb-kpi-value text-warning" id="kpiLowStock">
                <?php echo esc_html($metrics['low_stock_count']); ?> <small style="font-size: 15px;">cuốn</small>
            </div>
            <div class="nb-kpi-footer">
                <span class="text-muted small">Tồn kho &le; 5 cuốn</span>
            </div>
        </div>

        <!-- Khách hàng đã đăng ký -->
        <div class="nb-kpi-card nb-switch-tab-card" data-tab="customers" style="cursor: pointer;">
            <div class="nb-kpi-header">
                <span class="nb-kpi-title">Tổng Số Khách Hàng</span>
                <span class="nb-kpi-icon bg-success-light"><i class="bi bi-people-fill text-success"></i></span>
            </div>
            <div class="nb-kpi-value text-success" id="kpiCustomers">
                <?php echo esc_html(number_format_i18n($metrics['total_customers'])); ?>
            </div>
            <div class="nb-kpi-footer">
                <span class="text-muted small">Tài khoản thành viên</span>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         3. MAIN ADMIN NAVIGATION TABS
         =================================================================== -->
    <div class="nb-ah-tabs-header">
        <button type="button" class="nb-ah-tab-btn <?php echo ($active_tab === 'overview') ? 'is-active' : ''; ?>" data-target="nbTabOverview">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Báo Cáo & Doanh Thu</span>
        </button>

        <button type="button" class="nb-ah-tab-btn <?php echo ($active_tab === 'orders') ? 'is-active' : ''; ?>" data-target="nbTabOrders">
            <i class="bi bi-inbox-fill"></i>
            <span>Tiếp Nhận & Duyệt Đơn</span>
            <span class="nb-ah-badge-count" id="tabOrdersBadge"><?php echo esc_html($metrics['approval_needed_count']); ?></span>
        </button>

        <button type="button" class="nb-ah-tab-btn <?php echo ($active_tab === 'books') ? 'is-active' : ''; ?>" data-target="nbTabBooks">
            <i class="bi bi-book-half"></i>
            <span>Quản Lý Sách & Kho</span>
            <span class="nb-ah-badge-count neutral"><?php echo count($books_list); ?></span>
        </button>

        <button type="button" class="nb-ah-tab-btn <?php echo ($active_tab === 'posts') ? 'is-active' : ''; ?>" data-target="nbTabPosts">
            <i class="bi bi-newspaper"></i>
            <span>Đăng Tin Tức - Sự Kiện</span>
        </button>

        <button type="button" class="nb-ah-tab-btn <?php echo ($active_tab === 'customers') ? 'is-active' : ''; ?>" data-target="nbTabCustomers">
            <i class="bi bi-people-fill"></i>
            <span>Khách Hàng</span>
        </button>
    </div>

    <!-- ===================================================================
         4. TAB PANES
         =================================================================== -->
    <div class="nb-ah-tab-panes">

        <!-- ===============================================================
             TAB 1: BÁO CÁO & DOANH THU (OVERVIEW)
             =============================================================== -->
        <div class="nb-ah-pane <?php echo ($active_tab === 'overview') ? 'is-active' : ''; ?>" id="nbTabOverview">
            
            <!-- Chart Section -->
            <div class="nb-ah-card mb-4">
                <div class="nb-ah-card-header">
                    <div>
                        <h3 class="nb-ah-card-title"><i class="bi bi-graph-up text-primary"></i> Biểu Đồ Doanh Thu 7 Ngày Gần Nhất</h3>
                        <p class="nb-ah-card-sub">Thống kê doanh số bán sách đã thanh toán và đang xử lý theo từng ngày</p>
                    </div>
                    <div class="nb-ah-chart-legend">
                        <span class="nb-chart-dot"></span> Doanh thu (VND)
                    </div>
                </div>
                <div class="nb-ah-chart-container">
                    <canvas id="nbAdminRevenueChart" width="800" height="240"></canvas>
                </div>
            </div>

            <!-- Two-column: Bestsellers & Low Stock -->
            <div class="nb-ah-grid-split">
                
                <!-- Top Bestsellers -->
                <div class="nb-ah-card">
                    <div class="nb-ah-card-header">
                        <h4 class="nb-ah-card-title"><i class="bi bi-trophy-fill text-warning"></i> Top 5 Sách Bán Chạy Nhất</h4>
                    </div>
                    <div class="nb-ah-card-body p-0">
                        <?php if (!empty($top_bestsellers)): ?>
                            <ul class="nb-ah-mini-list">
                                <?php foreach ($top_bestsellers as $idx => $prod): 
                                    $sales = (int) $prod->get_total_sales();
                                    $author = get_post_meta($prod->get_id(), '_biblio_author', true) ?: get_post_meta($prod->get_id(), '_nbook_author', true) ?: 'Nhiều tác giả';
                                ?>
                                    <li class="nb-ah-mini-item">
                                        <span class="nb-ah-rank rank-<?php echo esc_attr($idx + 1); ?>"><?php echo esc_html($idx + 1); ?></span>
                                        <div class="nb-ah-item-img">
                                            <?php echo $prod->get_image('thumbnail'); ?>
                                        </div>
                                        <div class="nb-ah-item-meta">
                                            <a href="<?php echo esc_url($prod->get_permalink()); ?>" target="_blank" class="nb-ah-item-title"><?php echo esc_html($prod->get_name()); ?></a>
                                            <span class="nb-ah-item-author"><?php echo esc_html($author); ?></span>
                                        </div>
                                        <div class="nb-ah-item-stat text-right">
                                            <span class="nb-ah-sales-badge"><?php echo esc_html($sales); ?> đã bán</span>
                                            <span class="nb-ah-item-price"><?php echo wc_price($prod->get_price()); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted p-4 text-center">Chưa có dữ liệu sách bán chạy.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="nb-ah-card">
                    <div class="nb-ah-card-header">
                        <h4 class="nb-ah-card-title"><i class="bi bi-exclamation-octagon-fill text-danger"></i> Cảnh Báo Sắp Hết Hàng Trong Kho</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary nb-switch-tab-card" data-tab="books">
                            Quản lý kho
                        </button>
                    </div>
                    <div class="nb-ah-card-body p-0">
                        <?php if (!empty($low_stock_books)): ?>
                            <ul class="nb-ah-mini-list">
                                <?php foreach ($low_stock_books as $prod): 
                                    $stock = (int) $prod->get_stock_quantity();
                                    $is_out = ($stock <= 0 || $prod->get_stock_status() === 'outofstock');
                                ?>
                                    <li class="nb-ah-mini-item">
                                        <div class="nb-ah-item-img">
                                            <?php echo $prod->get_image('thumbnail'); ?>
                                        </div>
                                        <div class="nb-ah-item-meta">
                                            <a href="<?php echo esc_url($prod->get_permalink()); ?>" target="_blank" class="nb-ah-item-title"><?php echo esc_html($prod->get_name()); ?></a>
                                            <span class="nb-ah-item-price"><?php echo wc_price($prod->get_price()); ?></span>
                                        </div>
                                        <div class="nb-ah-item-stat text-right">
                                            <?php if ($is_out): ?>
                                                <span class="nb-status-badge nb-badge-cancelled">Hết hàng</span>
                                            <?php else: ?>
                                                <span class="nb-status-badge nb-badge-pending">Còn <?php echo esc_html($stock); ?> cuốn</span>
                                            <?php endif; ?>
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-xs btn-outline-secondary nb-quick-stock-btn" data-id="<?php echo esc_attr($prod->get_id()); ?>" data-stock="<?php echo esc_attr($stock); ?>">
                                                    + Thêm kho
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-success p-4 text-center"><i class="bi bi-check-circle"></i> Tuyệt vời! Tất cả các đầu sách đều đang đủ tồn kho.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Order Status Breakdown Row -->
            <div class="nb-ah-card mt-4">
                <div class="nb-ah-card-header">
                    <h4 class="nb-ah-card-title"><i class="bi bi-pie-chart-fill text-success"></i> Phân Bổ Trạng Thái Đơn Hàng Toàn Hệ Thống</h4>
                </div>
                <div class="nb-ah-status-grid">
                    <div class="nb-as-item">
                        <span class="nb-as-label">Chờ tiếp nhận</span>
                        <strong class="nb-as-num text-warning"><?php echo esc_html($metrics['pending_count']); ?></strong>
                        <span class="nb-as-desc">Cần xác nhận</span>
                    </div>
                    <div class="nb-as-item">
                        <span class="nb-as-label">Đang chuẩn bị</span>
                        <strong class="nb-as-num text-info"><?php echo esc_html($metrics['processing_count']); ?></strong>
                        <span class="nb-as-desc">Đang đóng gói</span>
                    </div>
                    <div class="nb-as-item">
                        <span class="nb-as-label">Đang giao hàng</span>
                        <strong class="nb-as-num text-primary"><?php echo esc_html($metrics['onhold_count'] + $metrics['shipping_count']); ?></strong>
                        <span class="nb-as-desc">Shipper đang giao</span>
                    </div>
                    <div class="nb-as-item">
                        <span class="nb-as-label">Đã hoàn thành</span>
                        <strong class="nb-as-num text-success"><?php echo esc_html($metrics['completed_count']); ?></strong>
                        <span class="nb-as-desc">Giao thành công</span>
                    </div>
                    <div class="nb-as-item">
                        <span class="nb-as-label">Đã hủy / Thất bại</span>
                        <strong class="nb-as-num text-danger"><?php echo esc_html($metrics['cancelled_count']); ?></strong>
                        <span class="nb-as-desc">Đơn hủy</span>
                    </div>
                    <div class="nb-as-item">
                        <span class="nb-as-label">Tổng cộng</span>
                        <strong class="nb-as-num"><?php echo esc_html($metrics['total_orders']); ?></strong>
                        <span class="nb-as-desc">Mọi đơn hàng</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===============================================================
             TAB 2: TIẾP NHẬN & DUYỆT ĐƠN HÀNG (ORDERS)
             =============================================================== -->
        <div class="nb-ah-pane <?php echo ($active_tab === 'orders') ? 'is-active' : ''; ?>" id="nbTabOrders">
            
            <div class="nb-ah-card">
                <div class="nb-ah-card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h3 class="nb-ah-card-title"><i class="bi bi-inbox-fill text-danger"></i> Tiếp Nhận & Duyệt Đơn Hàng Trực Tiếp</h3>
                        <p class="nb-ah-card-sub">Duyệt nhanh trạng thái đơn hàng chỉ với 1 click, không cần vào trang quản trị WP</p>
                    </div>
                    <div class="nb-ah-order-filter-pills">
                        <button type="button" class="nb-pill-filter is-active" data-filter="all">Tất cả (<?php echo count($orders_list); ?>)</button>
                        <button type="button" class="nb-pill-filter" data-filter="pending">Chờ tiếp nhận (<?php echo esc_html($metrics['pending_count']); ?>)</button>
                        <button type="button" class="nb-pill-filter" data-filter="processing">Đang chuẩn bị (<?php echo esc_html($metrics['processing_count']); ?>)</button>
                        <button type="button" class="nb-pill-filter" data-filter="shipping">Đang giao</button>
                        <button type="button" class="nb-pill-filter" data-filter="completed">Hoàn thành (<?php echo esc_html($metrics['completed_count']); ?>)</button>
                        <button type="button" class="nb-pill-filter" data-filter="cancelled">Đã hủy</button>
                    </div>
                </div>

                <div class="nb-ah-card-body p-0">
                    <div class="nb-ah-table-wrap">
                        <table class="nb-ah-table" id="nbAdminOrdersTable">
                            <thead>
                                <tr>
                                    <th>Mã Đơn</th>
                                    <th>Khách Hàng</th>
                                    <th>Ngày Đặt</th>
                                    <th>Sản Phẩm Trong Đơn</th>
                                    <th>Tổng Tiền</th>
                                    <th>Trạng Thái</th>
                                    <th class="text-right">Hành Động Duyệt Nhanh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders_list)): 
                                    $status_labels = [
                                        'pending'    => 'Chờ tiếp nhận',
                                        'processing' => 'Đang chuẩn bị',
                                        'on-hold'    => 'Đang giao hàng',
                                        'shipping'   => 'Đang giao hàng',
                                        'completed'  => 'Hoàn thành',
                                        'cancelled'  => 'Đã hủy',
                                        'refunded'   => 'Hoàn tiền',
                                        'failed'     => 'Thất bại',
                                    ];
                                    $status_classes = [
                                        'pending'    => 'nb-badge-pending',
                                        'processing' => 'nb-badge-processing',
                                        'on-hold'    => 'nb-badge-shipping',
                                        'shipping'   => 'nb-badge-shipping',
                                        'completed'  => 'nb-badge-completed',
                                        'cancelled'  => 'nb-badge-cancelled',
                                        'refunded'   => 'nb-badge-cancelled',
                                        'failed'     => 'nb-badge-cancelled',
                                    ];

                                    foreach ($orders_list as $ord): 
                                        $oid = $ord->get_id();
                                        $ostatus = $ord->get_status();
                                        $cust_name = trim($ord->get_formatted_billing_full_name()) ?: 'Khách mua sách';
                                        $cust_phone = $ord->get_billing_phone() ?: '—';
                                        $item_count = $ord->get_item_count();
                                        $items = $ord->get_items();
                                        $first_item = reset($items);
                                        $first_item_name = $first_item ? $first_item->get_name() : 'Sách';
                                        $other_count = $item_count - 1;
                                ?>
                                    <tr class="nb-order-row" data-status="<?php echo esc_attr($ostatus); ?>" id="order-row-<?php echo esc_attr($oid); ?>">
                                        <td class="font-weight-bold text-primary">
                                            #<?php echo esc_html($oid); ?>
                                        </td>
                                        <td>
                                            <div class="nb-ord-cust-name"><?php echo esc_html($cust_name); ?></div>
                                            <div class="nb-ord-cust-phone text-muted"><i class="bi bi-telephone"></i> <?php echo esc_html($cust_phone); ?></div>
                                        </td>
                                        <td>
                                            <div class="nb-ord-date"><?php echo esc_html($ord->get_date_created() ? $ord->get_date_created()->date_i18n('d/m/Y') : ''); ?></div>
                                            <div class="nb-ord-time text-muted small"><?php echo esc_html($ord->get_date_created() ? $ord->get_date_created()->date_i18n('H:i') : ''); ?></div>
                                        </td>
                                        <td>
                                            <div class="nb-ord-item-preview" title="<?php echo esc_attr($first_item_name); ?>">
                                                <strong><?php echo esc_html(wp_trim_words($first_item_name, 5)); ?></strong>
                                                <?php if ($other_count > 0): ?>
                                                    <span class="badge badge-light text-secondary">+<?php echo esc_html($other_count); ?> cuốn khác</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted small">Tổng: <?php echo esc_html($item_count); ?> cuốn</div>
                                        </td>
                                        <td class="font-weight-bold text-danger">
                                            <?php echo wc_price($ord->get_total()); ?>
                                        </td>
                                        <td>
                                            <span class="nb-status-badge <?php echo esc_attr($status_classes[$ostatus] ?? 'nb-badge-neutral'); ?>" id="order-badge-<?php echo esc_attr($oid); ?>">
                                                <?php echo esc_html($status_labels[$ostatus] ?? $ostatus); ?>
                                            </span>
                                        </td>
                                        <td class="text-right nb-order-actions-cell" id="order-actions-<?php echo esc_attr($oid); ?>">
                                            <div class="nb-order-btn-group">
                                                <!-- Action buttons according to status -->
                                                <?php if ($ostatus === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary nb-act-order-status" data-order-id="<?php echo esc_attr($oid); ?>" data-status="processing" title="Tiếp nhận & chuẩn bị đóng gói sách">
                                                        <i class="bi bi-check-lg"></i> Tiếp nhận
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger nb-act-order-status" data-order-id="<?php echo esc_attr($oid); ?>" data-status="cancelled" title="Hủy đơn">
                                                        <i class="bi bi-x-lg"></i> Hủy
                                                    </button>
                                                <?php elseif ($ostatus === 'processing'): ?>
                                                    <button type="button" class="btn btn-sm btn-purple nb-act-order-status" data-order-id="<?php echo esc_attr($oid); ?>" data-status="on-hold" title="Duyệt giao cho đơn vị vận chuyển">
                                                        <i class="bi bi-truck"></i> Giao hàng
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success nb-act-order-status" data-order-id="<?php echo esc_attr($oid); ?>" data-status="completed" title="Hoàn thành đơn">
                                                        <i class="bi bi-check2-all"></i> Xong
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger nb-act-order-status" data-order-id="<?php echo esc_attr($oid); ?>" data-status="cancelled" title="Hủy đơn">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                <?php elseif (in_array($ostatus, ['on-hold', 'shipping'], true)): ?>
                                                    <button type="button" class="btn btn-sm btn-success nb-act-order-status" data-order-id="<?php echo esc_attr($oid); ?>" data-status="completed" title="Khách đã nhận được hàng">
                                                        <i class="bi bi-check2-all"></i> Hoàn thành
                                                    </button>
                                                <?php endif; ?>

                                                <!-- View Details Modal Button -->
                                                <button type="button" class="btn btn-sm btn-outline-secondary nb-view-order-details-btn" data-order-id="<?php echo esc_attr($oid); ?>" title="Xem chi tiết đơn hàng & khách hàng">
                                                    <i class="bi bi-eye"></i> Chi tiết
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center p-4 text-muted">Chưa có đơn hàng nào trong hệ thống.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===============================================================
             TAB 3: QUẢN LÝ SÁCH & TỒN KHO (BOOKS CRUD)
             =============================================================== -->
        <div class="nb-ah-pane <?php echo ($active_tab === 'books') ? 'is-active' : ''; ?>" id="nbTabBooks">
            
            <div class="nb-ah-card">
                <div class="nb-ah-card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h3 class="nb-ah-card-title"><i class="bi bi-book-half text-primary"></i> Quản Lý Kho Sách Biblio (Thêm, Sửa, Xoá)</h3>
                        <p class="nb-ah-card-sub">Thêm sách mới, điều chỉnh giá bán & tồn kho trực tiếp tại chỗ</p>
                    </div>
                    <div class="nb-ah-header-actions d-flex gap-2">
                        <button type="button" class="btn btn-primary nb-open-add-book-modal">
                            <i class="bi bi-plus-circle-fill"></i> Thêm Sách Mới
                        </button>
                    </div>
                </div>

                <!-- Book Filter Strip -->
                <div class="nb-ah-filter-strip p-3 bg-light border-bottom d-flex gap-3 align-items-center">
                    <div class="nb-search-box flex-grow-1">
                        <input type="text" id="nbAdminBookSearch" class="form-control" placeholder="Tìm theo tên sách, tác giả..." autocomplete="off">
                    </div>
                    <div class="nb-cat-filter">
                        <select id="nbAdminBookCatFilter" class="form-control">
                            <option value="">Tất cả thể loại</option>
                            <?php foreach ($product_cats as $pcat): ?>
                                <option value="<?php echo esc_attr($pcat->slug); ?>"><?php echo esc_html($pcat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="nb-ah-card-body p-0">
                    <div class="nb-ah-table-wrap">
                        <table class="nb-ah-table" id="nbAdminBooksTable">
                            <thead>
                                <tr>
                                    <th>Ảnh Bìa</th>
                                    <th>Tên Sách & Tác Giả</th>
                                    <th>Thể Loại</th>
                                    <th>Giá Bán (VND)</th>
                                    <th>Tồn Kho</th>
                                    <th>Trạng Thái</th>
                                    <th class="text-right">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody id="nbAdminBooksTbody">
                                <?php if (!empty($books_list)): 
                                    foreach ($books_list as $prod): 
                                        $pid = $prod->get_id();
                                        $pname = $prod->get_name();
                                        $author = get_post_meta($pid, '_biblio_author', true) ?: get_post_meta($pid, '_nbook_author', true) ?: 'Nhiều tác giả';
                                        $terms = get_the_terms($pid, 'product_cat');
                                        $term_names = $terms && !is_wp_error($terms) ? wp_list_pluck($terms, 'name') : ['Sách'];
                                        $term_slugs = $terms && !is_wp_error($terms) ? wp_list_pluck($terms, 'slug') : [''];
                                        $reg_price = $prod->get_regular_price();
                                        $sale_price = $prod->get_sale_price();
                                        $stock = (int) $prod->get_stock_quantity();
                                        $is_in_stock = ($stock > 0 && $prod->get_stock_status() === 'instock');
                                ?>
                                    <tr class="nb-book-row" id="book-row-<?php echo esc_attr($pid); ?>" data-cat="<?php echo esc_attr(implode(' ', $term_slugs)); ?>" data-title="<?php echo esc_attr(mb_strtolower($pname . ' ' . $author)); ?>">
                                        <td class="nb-book-thumb-cell">
                                            <?php echo $prod->get_image('thumbnail', ['class' => 'nb-book-row-thumb']); ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url($prod->get_permalink()); ?>" target="_blank" class="nb-book-row-title"><?php echo esc_html($pname); ?></a>
                                            <div class="nb-book-row-author text-muted small"><i class="bi bi-pen"></i> <?php echo esc_html($author); ?></div>
                                        </td>
                                        <td>
                                            <span class="nb-cat-tag"><?php echo esc_html(implode(', ', array_slice($term_names, 0, 2))); ?></span>
                                        </td>
                                        <td class="nb-price-cell">
                                            <div class="d-flex align-items-center gap-1">
                                                <input type="number" class="form-control form-control-sm nb-inline-price" value="<?php echo esc_attr($reg_price); ?>" style="width: 105px;" title="Giá niêm yết">
                                            </div>
                                            <?php if ($sale_price): ?>
                                                <small class="text-danger">Sale: <?php echo wc_price($sale_price); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="nb-stock-cell">
                                            <input type="number" class="form-control form-control-sm nb-inline-stock" value="<?php echo esc_attr($stock); ?>" style="width: 75px;" title="Số lượng tồn kho">
                                        </td>
                                        <td>
                                            <?php if ($is_in_stock): ?>
                                                <span class="nb-status-badge nb-badge-completed">Còn hàng</span>
                                            <?php else: ?>
                                                <span class="nb-status-badge nb-badge-cancelled">Hết hàng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-success nb-save-inline-book" data-id="<?php echo esc_attr($pid); ?>" title="Lưu nhanh giá & tồn kho">
                                                    <i class="bi bi-check2"></i> Lưu
                                                </button>
                                                <button type="button" class="btn btn-outline-primary nb-edit-book-btn" 
                                                    data-id="<?php echo esc_attr($pid); ?>"
                                                    data-title="<?php echo esc_attr($pname); ?>"
                                                    data-author="<?php echo esc_attr($author); ?>"
                                                    data-price="<?php echo esc_attr($reg_price); ?>"
                                                    data-sale="<?php echo esc_attr($sale_price); ?>"
                                                    data-stock="<?php echo esc_attr($stock); ?>"
                                                    title="Sửa chi tiết">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger nb-delete-book-btn" data-id="<?php echo esc_attr($pid); ?>" data-name="<?php echo esc_attr($pname); ?>" title="Xóa sách">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center p-4 text-muted">Chưa có sản phẩm sách nào trong hệ thống.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===============================================================
             TAB 4: ĐĂNG TIN TỨC - SỰ KIỆN (POSTS CRUD)
             =============================================================== -->
        <div class="nb-ah-pane <?php echo ($active_tab === 'posts') ? 'is-active' : ''; ?>" id="nbTabPosts">
            
            <div class="nb-ah-card">
                <div class="nb-ah-card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h3 class="nb-ah-card-title"><i class="bi bi-newspaper text-success"></i> Đăng Tin Tức, Sự Kiện & Review Sách</h3>
                        <p class="nb-ah-card-sub">Xuất bản bài viết trực tiếp lên trang Tin tức & Trang chủ Biblio</p>
                    </div>
                    <div class="nb-ah-header-actions d-flex gap-2">
                        <button type="button" class="btn btn-success nb-open-add-post-modal">
                            <i class="bi bi-pencil-square"></i> Đăng Bài Viết Mới
                        </button>
                        <a href="<?php echo esc_url(home_url('/tin-tuc-su-kien/')); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right"></i> Xem Trang Tin Tức
                        </a>
                    </div>
                </div>

                <div class="nb-ah-card-body p-0">
                    <div class="nb-ah-table-wrap">
                        <table class="nb-ah-table" id="nbAdminPostsTable">
                            <thead>
                                <tr>
                                    <th>Ảnh Đại Diện</th>
                                    <th>Tiêu Đề Bài Viết</th>
                                    <th>Chuyên Mục</th>
                                    <th>Ngày Đăng</th>
                                    <th>Tác Giả</th>
                                    <th class="text-right">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody id="nbAdminPostsTbody">
                                <?php if (!empty($posts_list)): 
                                    foreach ($posts_list as $pst): 
                                        $post_id = $pst->ID;
                                        $post_cats_list = get_the_category($post_id);
                                        $cat_name = !empty($post_cats_list) ? $post_cats_list[0]->name : 'Tin tức';
                                        $post_thumb = get_the_post_thumbnail($post_id, 'thumbnail', ['class' => 'nb-post-row-thumb']);
                                        if (!$post_thumb) {
                                            $post_thumb = '<div class="nb-post-thumb-placeholder"><i class="bi bi-image"></i></div>';
                                        }
                                        $author_name = get_the_author_meta('display_name', $pst->post_author);
                                ?>
                                    <tr class="nb-post-row" id="post-row-<?php echo esc_attr($post_id); ?>">
                                        <td class="nb-post-thumb-cell">
                                            <?php echo $post_thumb; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" target="_blank" class="nb-post-row-title font-weight-bold">
                                                <?php echo esc_html($pst->post_title); ?>
                                            </a>
                                            <div class="text-muted small"><?php echo esc_html(wp_trim_words($pst->post_excerpt ?: $pst->post_content, 12)); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-info bg-info text-white px-2 py-1"><?php echo esc_html($cat_name); ?></span>
                                        </td>
                                        <td>
                                            <div><?php echo esc_html(get_the_date('d/m/Y', $post_id)); ?></div>
                                            <small class="text-muted"><?php echo esc_html(get_the_date('H:i', $post_id)); ?></small>
                                        </td>
                                        <td>
                                            <i class="bi bi-person"></i> <?php echo esc_html($author_name); ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" target="_blank" class="btn btn-outline-primary" title="Xem bài viết trên web">
                                                    <i class="bi bi-eye"></i> Xem
                                                </a>
                                                <button type="button" class="btn btn-outline-danger nb-delete-post-btn" data-id="<?php echo esc_attr($post_id); ?>" data-title="<?php echo esc_attr($pst->post_title); ?>" title="Xóa bài viết">
                                                    <i class="bi bi-trash"></i> Xóa
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center p-4 text-muted">Chưa có bài viết tin tức nào được đăng.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===============================================================
             TAB 5: QUẢN LÝ KHÁCH HÀNG (CUSTOMERS)
             =============================================================== -->
        <div class="nb-ah-pane <?php echo ($active_tab === 'customers') ? 'is-active' : ''; ?>" id="nbTabCustomers">
            
            <div class="nb-ah-card">
                <div class="nb-ah-card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h3 class="nb-ah-card-title"><i class="bi bi-people-fill text-primary"></i> Quản Lý Khách Hàng Biblio</h3>
                        <p class="nb-ah-card-sub">Danh sách khách hàng, lịch sử mua sắm và tích điểm Biblio Xu</p>
                    </div>
                    <div class="text-muted small">
                        Tổng số: <strong><?php echo esc_html($metrics['total_customers']); ?></strong> khách hàng
                    </div>
                </div>

                <div class="nb-ah-card-body p-0">
                    <div class="nb-ah-table-wrap">
                        <table class="nb-ah-table" id="nbAdminCustomersTable">
                            <thead>
                                <tr>
                                    <th>Khách Hàng</th>
                                    <th>Email & SĐT</th>
                                    <th>Số Đơn Đã Đặt</th>
                                    <th>Tổng Chi Tiêu</th>
                                    <th>Biblio Xu Tích Lũy</th>
                                    <th>Ngày Tham Gia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($customers_list)): 
                                    foreach ($customers_list as $cust): 
                                        $cid = $cust->ID;
                                        $cname = $cust->display_name ?: $cust->user_login;
                                        $cemail = $cust->user_email;
                                        $cphone = get_user_meta($cid, 'billing_phone', true) ?: 'Chưa cập nhật';
                                        $order_count = wc_get_customer_order_count($cid);
                                        $total_spent = wc_get_customer_total_spent($cid);
                                        $points = (int) get_user_meta($cid, '_nbook_reward_points', true);
                                ?>
                                    <tr class="nb-cust-row">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php echo get_avatar($cid, 36, '', esc_attr($cname), ['class' => 'rounded-circle']); ?>
                                                <div>
                                                    <div class="font-weight-bold"><?php echo esc_html($cname); ?></div>
                                                    <div class="text-muted small">ID: #<?php echo esc_html($cid); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?php echo esc_html($cemail); ?></div>
                                            <div class="text-muted small"><i class="bi bi-telephone"></i> <?php echo esc_html($cphone); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border font-weight-bold px-2 py-1"><?php echo esc_html($order_count); ?> đơn</span>
                                        </td>
                                        <td class="font-weight-bold text-success">
                                            <?php echo wc_price($total_spent); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning text-dark font-weight-bold"><i class="bi bi-coin"></i> <?php echo number_format_i18n($points); ?> Xu</span>
                                        </td>
                                        <td>
                                            <div><?php echo date_i18n('d/m/Y', strtotime($cust->user_registered)); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center p-4 text-muted">Chưa có khách hàng nào đăng ký.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ===================================================================
         5. MODALS OVERLAYS
         =================================================================== -->
    
    <!-- Modal 1: Chi Tiết Đơn Hàng (#nbModalOrderDetails) -->
    <div class="nb-ah-modal-overlay" id="nbModalOrderDetails">
        <div class="nb-ah-modal-dialog nb-modal-lg">
            <div class="nb-ah-modal-header">
                <h4 class="nb-ah-modal-title"><i class="bi bi-receipt text-primary"></i> Chi Tiết Đơn Hàng</h4>
                <button type="button" class="nb-ah-modal-close nb-close-modal-trigger">&times;</button>
            </div>
            <div class="nb-ah-modal-body" id="nbModalOrderDetailsContent">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Đang tải dữ liệu đơn hàng...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2: Thêm / Sửa Sách (#nbModalBookForm) -->
    <div class="nb-ah-modal-overlay" id="nbModalBookForm">
        <div class="nb-ah-modal-dialog">
            <div class="nb-ah-modal-header">
                <h4 class="nb-ah-modal-title" id="nbBookModalTitle"><i class="bi bi-plus-circle-fill text-primary"></i> Thêm Sách Mới Vào Cửa Hàng</h4>
                <button type="button" class="nb-ah-modal-close nb-close-modal-trigger">&times;</button>
            </div>
            <form id="nbAdminBookForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="nb_admin_quick_save_book">
                <input type="hidden" name="nonce" value="<?php echo esc_attr($hub_nonce); ?>">
                <input type="hidden" name="book_id" id="nbBookId" value="0">

                <div class="nb-ah-modal-body">
                    <div class="form-group mb-3">
                        <label for="nbBookTitle" class="form-label font-weight-bold">Tên Tựa Sách <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="nbBookTitle" class="form-control" placeholder="Ví dụ: Đắc Nhân Tâm (Tái Bản Đặc Biệt)" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nbBookAuthor" class="form-label font-weight-bold">Tác Giả Sách</label>
                            <input type="text" name="author" id="nbBookAuthor" class="form-control" placeholder="Ví dụ: Dale Carnegie">
                        </div>
                        <div class="col-md-6">
                            <label for="nbBookCategory" class="form-label font-weight-bold">Thể Loại / Chuyên Mục</label>
                            <select name="category_id" id="nbBookCategory" class="form-control">
                                <option value="0">-- Chọn thể loại --</option>
                                <?php foreach ($product_cats as $pcat): ?>
                                    <option value="<?php echo esc_attr($pcat->term_id); ?>"><?php echo esc_html($pcat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="nbBookPrice" class="form-label font-weight-bold">Giá Niêm Yết (VND) <span class="text-danger">*</span></label>
                            <input type="number" name="regular_price" id="nbBookPrice" class="form-control" placeholder="Ví dụ: 120000" required min="1000">
                        </div>
                        <div class="col-md-4">
                            <label for="nbBookSalePrice" class="form-label font-weight-bold">Giá Khuyến Mãi (Sale)</label>
                            <input type="number" name="sale_price" id="nbBookSalePrice" class="form-control" placeholder="Tùy chọn (ví dụ: 99000)">
                        </div>
                        <div class="col-md-4">
                            <label for="nbBookStock" class="form-label font-weight-bold">Số Lượng Kho</label>
                            <input type="number" name="stock_quantity" id="nbBookStock" class="form-control" value="50" min="0">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Ảnh Bìa Sách</label>
                        <div class="nb-file-upload-wrap">
                            <input type="file" name="book_image" id="nbBookImage" class="form-control mb-2" accept="image/*">
                            <div class="small text-muted">Hoặc dán đường dẫn ảnh:</div>
                            <input type="url" name="image_url" id="nbBookImageUrl" class="form-control form-control-sm mt-1" placeholder="https://example.com/anh-bia-sach.jpg">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="nbBookDesc" class="form-label font-weight-bold">Mô Tả Ngắn Tóm Tắt</label>
                        <textarea name="short_description" id="nbBookDesc" class="form-control" rows="3" placeholder="Giới thiệu đôi nét về tác phẩm..."></textarea>
                    </div>
                </div>

                <div class="nb-ah-modal-footer">
                    <button type="button" class="btn btn-secondary nb-close-modal-trigger">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="nbBtnSubmitBook">
                        <i class="bi bi-save-fill"></i> Lưu & Đăng Sách
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Đăng Tin Tức - Sự Kiện (#nbModalPostForm) -->
    <div class="nb-ah-modal-overlay" id="nbModalPostForm">
        <div class="nb-ah-modal-dialog">
            <div class="nb-ah-modal-header">
                <h4 class="nb-ah-modal-title"><i class="bi bi-pencil-square text-success"></i> Đăng Bài Viết Tin Tức / Sự Kiện Mới</h4>
                <button type="button" class="nb-ah-modal-close nb-close-modal-trigger">&times;</button>
            </div>
            <form id="nbAdminPostForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="nb_admin_quick_save_post">
                <input type="hidden" name="nonce" value="<?php echo esc_attr($hub_nonce); ?>">
                <input type="hidden" name="post_id" id="nbPostId" value="0">

                <div class="nb-ah-modal-body">
                    <div class="form-group mb-3">
                        <label for="nbPostTitle" class="form-label font-weight-bold">Tiêu Đề Bài Viết <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="nbPostTitle" class="form-control" placeholder="Ví dụ: Ra mắt hội sách mùa thu 2026 - Giảm giá tới 50%" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nbPostCategory" class="form-label font-weight-bold">Chuyên Mục Bài Viết</label>
                            <select name="category_id" id="nbPostCategory" class="form-control">
                                <?php foreach ($post_cats as $pcat): ?>
                                    <option value="<?php echo esc_attr($pcat->term_id); ?>"><?php echo esc_html($pcat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Ảnh Đại Diện Bài Viết</label>
                            <input type="file" name="post_image" id="nbPostImage" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="nbPostExcerpt" class="form-label font-weight-bold">Tóm Tắt Ngắn (Mô tả hiển thị bên ngoài)</label>
                        <textarea name="excerpt" id="nbPostExcerpt" class="form-control" rows="2" placeholder="Tóm lược ngắn gọn nội dung sự kiện hoặc tin tức..."></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label for="nbPostContent" class="form-label font-weight-bold">Nội Dung Chi Tiết <span class="text-danger">*</span></label>
                        <textarea name="content" id="nbPostContent" class="form-control" rows="6" placeholder="Nhập đầy đủ thông tin bài viết, thể lệ sự kiện, chương trình tặng sách..." required></textarea>
                    </div>
                </div>

                <div class="nb-ah-modal-footer">
                    <button type="button" class="btn btn-secondary nb-close-modal-trigger">Hủy</button>
                    <button type="submit" class="btn btn-success" id="nbBtnSubmitPost">
                        <i class="bi bi-send-fill"></i> Xuất Bản Bài Viết
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: Xác nhận xóa (#nbModalConfirm) -->
    <div class="nb-ah-modal-overlay" id="nbModalConfirm">
        <div class="nb-ah-modal-dialog nb-modal-sm">
            <div class="nb-ah-modal-header bg-danger text-white">
                <h4 class="nb-ah-modal-title text-white"><i class="bi bi-exclamation-triangle-fill"></i> Xác Nhận Xóa</h4>
                <button type="button" class="nb-ah-modal-close text-white nb-close-modal-trigger">&times;</button>
            </div>
            <div class="nb-ah-modal-body p-4 text-center">
                <p id="nbConfirmMessage" class="mb-0">Bạn có chắc chắn muốn xóa mục này?</p>
            </div>
            <div class="nb-ah-modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary nb-close-modal-trigger">Hủy bỏ</button>
                <button type="button" class="btn btn-danger" id="nbConfirmDeleteBtn">Đồng ý xóa</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="nb-ah-toast" id="nbAdminToast">
        <div class="nb-toast-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="nb-toast-msg">Thông báo thành công!</div>
    </div>

</div>

<!-- Inline Chart Data Script -->
<script>
window.nbAdminChartData = <?php echo json_encode($metrics['chart_data']); ?>;
</script>
