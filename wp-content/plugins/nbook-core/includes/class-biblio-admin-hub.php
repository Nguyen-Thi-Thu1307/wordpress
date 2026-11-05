<?php
/**
 * Biblio Admin Hub - Trung Tâm Quản Trị Hợp Nhất Biblio
 *
 * Quản trị toàn diện:
 * 1. Tổng quan (Dashboard với Canvas Chart)
 * 2. Quản lý đơn hàng (Lọc, cập nhật hàng loạt, ghi lịch sử, gửi thông báo)
 * 3. Quản lý sách & kho (Sửa nhanh giá/tồn kho, Nhập/Xuất CSV)
 * 4. Quản lý khách hàng (Danh sách, Biblio Xu, Khóa/Mở tài khoản)
 * 5. Quản lý nội dung (Voucher, Banner, Tin tức, Chatbot FAQ, Liên hệ, Đánh giá)
 * 6. Cài đặt chung (Hotline, email, MXH, freeship, quy tắc điểm)
 * 7. Phân quyền (Vai trò "Nhân viên cửa hàng")
 *
 * @package Nbook_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Biblio_Admin_Hub {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', [$this, 'init_roles_and_tables']);
        add_action('admin_menu', [$this, 'register_admin_menus'], 9);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('authenticate', [$this, 'check_account_locked'], 30, 3);

        // Action POST handlers
        add_action('admin_post_biblio_bulk_orders', [$this, 'handle_bulk_orders']);
        add_action('admin_post_biblio_send_order_notice', [$this, 'handle_send_order_notice']);
        add_action('admin_post_biblio_save_quick_books', [$this, 'handle_save_quick_books']);
        add_action('admin_post_biblio_export_books_csv', [$this, 'handle_export_books_csv']);
        add_action('admin_post_biblio_import_books_csv', [$this, 'handle_import_books_csv']);
        add_action('admin_post_biblio_adjust_customer_points', [$this, 'handle_adjust_customer_points']);
        add_action('admin_post_biblio_toggle_customer_lock', [$this, 'handle_toggle_customer_lock']);
        add_action('admin_post_biblio_review_action', [$this, 'handle_review_action']);
        add_action('admin_post_biblio_quick_add_voucher', [$this, 'handle_quick_add_voucher']);
        add_action('admin_post_biblio_delete_voucher', [$this, 'handle_delete_voucher']);
        add_action('admin_post_biblio_save_general_settings', [$this, 'handle_save_general_settings']);
        add_action('admin_post_biblio_assign_staff_role', [$this, 'handle_assign_staff_role']);
        add_action('admin_post_biblio_mark_contact_read', [$this, 'handle_mark_contact_read']);
    }

    /**
     * Khởi tạo vai trò "Nhân viên cửa hàng" và bảng CSDL cần thiết
     */
    public function init_roles_and_tables() {
        // 1. Đăng ký vai trò "Nhân viên cửa hàng" (biblio_staff)
        if (!get_role('biblio_staff')) {
            add_role('biblio_staff', 'Nhân viên cửa hàng Biblio', [
                'read'                    => true,
                'edit_posts'              => true,
                'upload_files'            => true,
                'biblio_staff_access'     => true,
                'view_biblio_dashboard'   => true,
                'manage_biblio_orders'    => true,
                'manage_biblio_books'     => true,
                'manage_biblio_customers' => true,
                'manage_biblio_content'   => true,
            ]);
        }

        // Cấp quyền biblio cho administrator & shop_manager
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $caps = [
                'biblio_staff_access', 'view_biblio_dashboard', 'manage_biblio_orders',
                'manage_biblio_books', 'manage_biblio_customers', 'manage_biblio_content',
                'manage_biblio_settings', 'manage_biblio_roles'
            ];
            foreach ($caps as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
                }
            }
        }

        $shop_mgr = get_role('shop_manager');
        if ($shop_mgr) {
            $caps = [
                'biblio_staff_access', 'view_biblio_dashboard', 'manage_biblio_orders',
                'manage_biblio_books', 'manage_biblio_customers', 'manage_biblio_content'
            ];
            foreach ($caps as $cap) {
                if (!$shop_mgr->has_cap($cap)) {
                    $shop_mgr->add_cap($cap);
                }
            }
        }

        // 2. Tạo bảng wp_nbook_contacts lưu thư liên hệ / phản hồi
        global $wpdb;
        $table_contacts = $wpdb->prefix . 'nbook_contacts';
        if (get_option('nbook_contacts_db_version') !== '1') {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $charset = $wpdb->get_charset_collate();
            dbDelta("CREATE TABLE {$table_contacts} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                phone varchar(30) NOT NULL DEFAULT '',
                subject varchar(200) NOT NULL,
                message text NOT NULL,
                is_read tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY is_read_created (is_read, created_at)
            ) {$charset};");
            update_option('nbook_contacts_db_version', '1');

            // Seed mẫu tin nhắn liên hệ nếu rỗng
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_contacts}");
            if ($count === 0) {
                $now = current_time('mysql');
                $wpdb->insert($table_contacts, [
                    'name'       => 'Nguyễn Hoàng Long (K64 NEU)',
                    'email'      => 'long.nh@st.neu.edu.vn',
                    'phone'      => '0912 345 678',
                    'subject'    => 'Hỏi về sách Giáo trình Kinh tế lượng tái bản mới',
                    'message'    => 'Em chào nhà sách, cho em hỏi sách giáo trình Kinh tế lượng NEU tái bản 2026 khi nào có đợt hàng mới ạ? Lớp em đang cần đặt khoảng 35 cuốn.',
                    'is_read'    => 0,
                    'created_at' => $now,
                ]);
                $wpdb->insert($table_contacts, [
                    'name'       => 'Trần Thảo Linh',
                    'email'      => 'thaolinh.tran@gmail.com',
                    'phone'      => '0988 776 655',
                    'subject'    => 'Hỗ trợ bọc sách plastic số lượng lớn',
                    'message'    => 'Mình muốn đặt 10 cuốn làm quà tặng CLB, nhà sách có hỗ trợ viết thiệp và bọc sách từng cuốn giúp mình được không ạ?',
                    'is_read'    => 1,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                ]);
            }
        }
    }

    /**
     * Chặn đăng nhập đối với tài khoản khách hàng bị khóa
     */
    public function check_account_locked($user, $username, $password) {
        if ($user instanceof WP_User) {
            $is_locked = get_user_meta($user->ID, '_nbook_account_locked', true);
            if ($is_locked === '1') {
                $hotline = get_option('nbook_hotline', '0933 109 009');
                return new WP_Error(
                    'account_locked',
                    sprintf('<strong>Tài khoản bị tạm khóa:</strong> Tài khoản của bạn đã bị khóa bởi quản trị viên Biblio. Vui lòng liên hệ Hotline CSKH <strong>%s</strong> để được kiểm tra và kích hoạt lại.', esc_html($hotline))
                );
            }
        }
        return $user;
    }

    /**
     * Đăng ký cấu trúc Menu Biblio Quản Trị
     */
    public function register_admin_menus() {
        if (!current_user_can('biblio_staff_access') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            return;
        }

        $main_cap = current_user_can('manage_options') ? 'manage_options' : (current_user_can('manage_woocommerce') ? 'manage_woocommerce' : 'biblio_staff_access');

        // Menu Cha: BIBLIO
        add_menu_page(
            'Biblio Quản Trị',
            'Biblio',
            $main_cap,
            'biblio-admin',
            [$this, 'render_dashboard_page'],
            'dashicons-book-alt',
            2
        );

        // 1. Tổng quan
        add_submenu_page(
            'biblio-admin',
            'Tổng Quan - Biblio',
            'Tổng quan',
            $main_cap,
            'biblio-admin',
            [$this, 'render_dashboard_page']
        );

        // 2. Quản lý đơn hàng
        add_submenu_page(
            'biblio-admin',
            'Quản Lý Đơn Hàng - Biblio',
            'Đơn hàng',
            'manage_biblio_orders',
            'biblio-orders',
            [$this, 'render_orders_page']
        );

        // 3. Quản lý sách & kho
        add_submenu_page(
            'biblio-admin',
            'Quản Lý Sách & Kho - Biblio',
            'Sách & Kho',
            'manage_biblio_books',
            'biblio-books',
            [$this, 'render_books_page']
        );

        // 4. Quản lý khách hàng
        add_submenu_page(
            'biblio-admin',
            'Quản Lý Khách Hàng - Biblio',
            'Khách hàng',
            'manage_biblio_customers',
            'biblio-customers',
            [$this, 'render_customers_page']
        );

        // 5. Quản lý nội dung & đánh giá
        add_submenu_page(
            'biblio-admin',
            'Nội Dung & Đánh Giá - Biblio',
            'Nội dung & Đánh giá',
            'manage_biblio_content',
            'biblio-content',
            [$this, 'render_content_page']
        );

        // 6. Cài đặt chung (Admin & Shop Manager)
        add_submenu_page(
            'biblio-admin',
            'Cài Đặt Chung - Biblio',
            'Cài đặt chung',
            'manage_options',
            'biblio-general-settings',
            [$this, 'render_settings_page']
        );

        // 7. Phân quyền nhân viên (Chỉ Quản trị viên)
        add_submenu_page(
            'biblio-admin',
            'Phân Quyền Nhân Viên - Biblio',
            'Phân quyền',
            'manage_options',
            'biblio-roles',
            [$this, 'render_roles_page']
        );
    }

    /**
     * Enqueue CSS & Script cho giao diện quản trị Biblio
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'biblio-') === false) {
            return;
        }

        // CSS Quản trị màu Cam Biblio
        ?>
        <style>
        :root {
            --bb-primary: #ee4d2d;
            --bb-primary-dark: #d73211;
            --bb-primary-light: #fff5f2;
            --bb-text: #2c3e50;
            --bb-border: #e2e8f0;
            --bb-bg-card: #ffffff;
        }
        .biblio-admin-wrap {
            margin: 20px 20px 0 2px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--bb-text);
        }
        .biblio-header-bar {
            background: linear-gradient(135deg, #ee4d2d 0%, #ff5722 100%);
            color: #ffffff;
            padding: 16px 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 14px rgba(238, 77, 45, 0.22);
            margin-bottom: 24px;
        }
        .biblio-header-bar h1 {
            color: #ffffff;
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .biblio-header-tagline {
            font-size: 13px;
            opacity: 0.92;
            color: #fff;
        }
        .biblio-badge-role {
            background: rgba(255,255,255,0.25);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        /* KPI Cards Grid */
        .biblio-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .biblio-kpi-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 18px 20px;
            border: 1px solid var(--bb-border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .biblio-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.07);
        }
        .biblio-kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--bb-primary);
        }
        .biblio-kpi-title {
            font-size: 12.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .biblio-kpi-value {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
        }
        .biblio-kpi-sub {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
        }
        /* Panels Grid */
        .biblio-panels-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 1080px) {
            .biblio-panels-grid {
                grid-template-columns: 1fr;
            }
        }
        .biblio-panel {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--bb-border);
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .biblio-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .biblio-panel-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Buttons */
        .biblio-btn-primary {
            background: var(--bb-primary) !important;
            border-color: var(--bb-primary) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            padding: 6px 14px !important;
            transition: all 0.2s !important;
        }
        .biblio-btn-primary:hover {
            background: var(--bb-primary-dark) !important;
            border-color: var(--bb-primary-dark) !important;
            transform: translateY(-1px);
        }
        /* Status Badges */
        .biblio-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
        }
        .biblio-badge-pending { background: #fef3c7; color: #b45309; }
        .biblio-badge-processing { background: #e0f2fe; color: #0369a1; }
        .biblio-badge-on-hold, .biblio-badge-shipping { background: #ede9fe; color: #6d28d9; }
        .biblio-badge-completed { background: #dcfce7; color: #15803d; }
        .biblio-badge-cancelled { background: #fee2e2; color: #b91c1c; }
        .biblio-badge-locked { background: #dc2626; color: #ffffff; }
        .biblio-badge-active { background: #16a34a; color: #ffffff; }
        /* Tables */
        .biblio-table {
            width: 100%;
            border-collapse: collapse;
        }
        .biblio-table th {
            text-align: left;
            padding: 10px 12px;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        .biblio-table td {
            padding: 11px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .biblio-table tr:hover td {
            background: #fbfcfe;
        }
        .biblio-filter-bar {
            background: #ffffff;
            border: 1px solid var(--bb-border);
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        /* Canvas Chart Styling */
        .biblio-chart-wrap {
            position: relative;
            width: 100%;
            height: 260px;
            margin-top: 10px;
        }
        canvas#biblioRevenueCanvas {
            width: 100%;
            height: 100%;
            display: block;
        }
        /* Tabs Bar */
        .biblio-tabs-nav {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .biblio-tab-btn {
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .biblio-tab-btn:hover {
            color: var(--bb-primary);
        }
        .biblio-tab-btn.is-active {
            color: var(--bb-primary);
            border-bottom-color: var(--bb-primary);
        }
        </style>
        <?php
    }

    /**
     * Helper: Hiển thị Flash Notice thành công hoặc lỗi
     */
    private function render_notices() {
        if (!empty($_GET['biblio_notice'])) {
            $type = sanitize_key($_GET['biblio_type'] ?? 'success');
            $msg = sanitize_text_field(wp_unslash($_GET['biblio_notice']));
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible" style="border-left-color: #ee4d2d;"><p><strong>' . esc_html($msg) . '</strong></p></div>';
        }
    }

    // =========================================================================
    // 1. TỔNG QUAN (DASHBOARD)
    // =========================================================================
    public function render_dashboard_page() {
        if (!current_user_can('view_biblio_dashboard') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang này.');
        }

        global $wpdb;
        $now = current_time('mysql');
        $today_start = current_time('Y-m-d 00:00:00');
        $seven_days_ago = date('Y-m-d 00:00:00', strtotime('-6 days', current_time('timestamp')));
        $thirty_days_ago = date('Y-m-d 00:00:00', strtotime('-29 days', current_time('timestamp')));

        // Tính doanh thu từ bảng wp_posts & wp_wc_order_stats hoặc postmeta
        $revenue_today = $this->calculate_revenue($today_start, $now);
        $revenue_7d    = $this->calculate_revenue($seven_days_ago, $now);
        $revenue_30d   = $this->calculate_revenue($thirty_days_ago, $now);

        // Số đơn hàng theo trạng thái
        $counts = wc_get_order_statuses();
        $order_counts = [];
        foreach ($counts as $status_key => $status_name) {
            $clean_status = str_replace('wc-', '', $status_key);
            $order_counts[$clean_status] = wc_orders_count($clean_status);
        }
        $total_orders = array_sum($order_counts);

        // Sách sắp hết hàng (tồn kho <= 5 hoặc outofstock)
        $low_stock_products = $this->get_low_stock_products(5);

        // Top 5 sách bán chạy
        $bestsellers = $this->get_top_bestsellers(5);

        // 5 Đơn hàng mới nhất
        $recent_orders = wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC']);

        // 5 Người dùng mới nhất
        $recent_users = get_users(['number' => 5, 'orderby' => 'registered', 'order' => 'DESC']);

        // Dữ liệu doanh thu 7 ngày cho biểu đồ đường Canvas
        $chart_data = $this->get_chart_revenue_7days();

        ?>
        <div class="wrap biblio-admin-wrap">
            <div class="biblio-header-bar">
                <div>
                    <h1>📚 BẢNG QUẢN TRỊ BIBLIO</h1>
                    <div class="biblio-header-tagline">Hệ thống điều hành và phân tích kinh doanh sàn sách trực tuyến Biblio</div>
                </div>
                <div>
                    <span class="biblio-badge-role">
                        <?php 
                        $current_user = wp_get_current_user();
                        $role_names = ['administrator' => 'Quản trị viên cấp cao', 'shop_manager' => 'Quản lý cửa hàng', 'biblio_staff' => 'Nhân viên cửa hàng'];
                        echo esc_html($role_names[$current_user->roles[0] ?? ''] ?? 'Nhân viên');
                        ?>
                    </span>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <!-- 1. KPI Cards Row -->
            <div class="biblio-kpi-grid">
                <div class="biblio-kpi-card">
                    <span class="biblio-kpi-title">Doanh Thu Hôm Nay</span>
                    <span class="biblio-kpi-value" style="color: #ee4d2d;"><?php echo wp_strip_all_tags(wc_price($revenue_today)); ?></span>
                    <span class="biblio-kpi-sub">Cập nhật lúc <?php echo esc_html(current_time('H:i')); ?></span>
                </div>
                <div class="biblio-kpi-card">
                    <span class="biblio-kpi-title">Doanh Thu 7 Ngày Qua</span>
                    <span class="biblio-kpi-value"><?php echo wp_strip_all_tags(wc_price($revenue_7d)); ?></span>
                    <span class="biblio-kpi-sub">7 ngày gần nhất</span>
                </div>
                <div class="biblio-kpi-card">
                    <span class="biblio-kpi-title">Doanh Thu 30 Ngày</span>
                    <span class="biblio-kpi-value"><?php echo wp_strip_all_tags(wc_price($revenue_30d)); ?></span>
                    <span class="biblio-kpi-sub">Tháng hiện tại</span>
                </div>
                <div class="biblio-kpi-card">
                    <span class="biblio-kpi-title">Đơn Cần Xử Lý</span>
                    <span class="biblio-kpi-value" style="color: #0284c7;"><?php echo esc_html($order_counts['processing'] ?? 0); ?> đơn</span>
                    <span class="biblio-kpi-sub">Đang giao: <?php echo esc_html(($order_counts['shipping'] ?? 0) + ($order_counts['on-hold'] ?? 0)); ?> đơn</span>
                </div>
                <div class="biblio-kpi-card">
                    <span class="biblio-kpi-title">Sách Cảnh Báo Kho</span>
                    <span class="biblio-kpi-value" style="color: #dc2626;"><?php echo count($low_stock_products); ?> cuốn</span>
                    <span class="biblio-kpi-sub">Tồn kho &le; 5 hoặc hết hàng</span>
                </div>
            </div>

            <!-- 2. Biểu đồ đường Doanh Thu & Thống kê đơn -->
            <div class="biblio-panels-grid">
                <!-- Cột Trái: Canvas Line Chart -->
                <div class="biblio-panel">
                    <div class="biblio-panel-header">
                        <h2 class="biblio-panel-title">📈 Biểu Đồ Doanh Thu 7 Ngày Qua</h2>
                        <span style="font-size: 12px; color: #64748b;">Đơn vị: VNĐ</span>
                    </div>
                    <div class="biblio-chart-wrap">
                        <canvas id="biblioRevenueCanvas"></canvas>
                    </div>
                </div>

                <!-- Cột Phải: Phân bổ trạng thái đơn -->
                <div class="biblio-panel">
                    <div class="biblio-panel-header">
                        <h2 class="biblio-panel-title">📦 Trạng Thái Đơn Hàng</h2>
                        <span style="font-size: 12px; color: #64748b;">Tổng: <?php echo esc_html($total_orders); ?> đơn</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #f1f5f9;">
                            <span><span class="biblio-badge biblio-badge-processing">Đang xử lý</span></span>
                            <strong><?php echo esc_html($order_counts['processing'] ?? 0); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #f1f5f9;">
                            <span><span class="biblio-badge biblio-badge-shipping">Đang vận chuyển</span></span>
                            <strong><?php echo esc_html(($order_counts['shipping'] ?? 0) + ($order_counts['on-hold'] ?? 0)); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #f1f5f9;">
                            <span><span class="biblio-badge biblio-badge-completed">Hoàn thành</span></span>
                            <strong><?php echo esc_html($order_counts['completed'] ?? 0); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #f1f5f9;">
                            <span><span class="biblio-badge biblio-badge-pending">Chờ thanh toán</span></span>
                            <strong><?php echo esc_html($order_counts['pending'] ?? 0); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0;">
                            <span><span class="biblio-badge biblio-badge-cancelled">Đã hủy / Hoàn</span></span>
                            <strong><?php echo esc_html(($order_counts['cancelled'] ?? 0) + ($order_counts['refunded'] ?? 0)); ?></strong>
                        </div>
                    </div>
                    <div style="margin-top: 18px; text-align: center;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-orders')); ?>" class="button biblio-btn-primary" style="width: 100%; text-align: center;">
                            Xem và xử lý tất cả đơn hàng &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- 3. Hai bảng: Đơn mới & Top 5 sách bán chạy -->
            <div class="biblio-panels-grid">
                <!-- Đơn hàng mới -->
                <div class="biblio-panel">
                    <div class="biblio-panel-header">
                        <h2 class="biblio-panel-title">🛒 Đơn Hàng Mới Nhất</h2>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-orders')); ?>" style="font-size: 12.5px; color: #ee4d2d; text-decoration: none; font-weight: 600;">Xem tất cả</a>
                    </div>
                    <?php if (empty($recent_orders)): ?>
                        <p style="color: #888; text-align: center; padding: 20px 0;">Chưa có đơn hàng nào.</p>
                    <?php else: ?>
                        <table class="biblio-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $ord): 
                                    $st = $ord->get_status();
                                ?>
                                    <tr>
                                        <td><strong><a href="<?php echo esc_url(admin_url('admin.php?page=biblio-orders&search=' . $ord->get_id())); ?>" style="color: #ee4d2d;">#<?php echo esc_html($ord->get_order_number()); ?></a></strong></td>
                                        <td><?php echo esc_html($ord->get_formatted_billing_full_name() ?: 'Khách vãng lai'); ?></td>
                                        <td><strong style="color: #ee4d2d;"><?php echo wp_strip_all_tags(wc_price($ord->get_total())); ?></strong></td>
                                        <td><span class="biblio-badge biblio-badge-<?php echo esc_attr($st); ?>"><?php echo esc_html(wc_get_order_status_name($st)); ?></span></td>
                                        <td style="color: #64748b; font-size: 12px;"><?php echo esc_html($ord->get_date_created() ? $ord->get_date_created()->date('d/m H:i') : ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Top 5 Sách bán chạy -->
                <div class="biblio-panel">
                    <div class="biblio-panel-header">
                        <h2 class="biblio-panel-title">🔥 Top 5 Sách Bán Chạy</h2>
                    </div>
                    <?php if (empty($bestsellers)): ?>
                        <p style="color: #888; text-align: center; padding: 20px 0;">Đang cập nhật số liệu bán hàng.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($bestsellers as $idx => $book): 
                                $img = get_the_post_thumbnail_url($book->get_id(), 'thumbnail') ?: wc_placeholder_img_src();
                            ?>
                                <div style="display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                                    <span style="font-weight: 800; color: #ee4d2d; width: 18px; font-size: 14px;">#<?php echo esc_html($idx + 1); ?></span>
                                    <img src="<?php echo esc_url($img); ?>" style="width: 36px; height: 48px; object-fit: cover; border-radius: 4px;" alt="">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 12.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr($book->get_name()); ?>">
                                            <?php echo esc_html($book->get_name()); ?>
                                        </div>
                                        <div style="font-size: 11.5px; color: #ee4d2d; font-weight: 700;">
                                            <?php echo $book->get_price_html(); ?>
                                        </div>
                                    </div>
                                    <span style="font-size: 11.5px; background: #fff5f2; color: #ee4d2d; padding: 2px 7px; border-radius: 10px; font-weight: 600;">
                                        Đã bán <?php echo (int) $book->get_total_sales(); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. Sách sắp hết hàng & Khách hàng mới -->
            <div class="biblio-panels-grid">
                <!-- Cảnh báo kho -->
                <div class="biblio-panel">
                    <div class="biblio-panel-header">
                        <h2 class="biblio-panel-title">⚠️ Sách Sắp Hết Hàng (Cần Nhập Kho)</h2>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-books&stock_status=low')); ?>" style="font-size: 12.5px; color: #ee4d2d; text-decoration: none; font-weight: 600;">Xem kho sách</a>
                    </div>
                    <?php if (empty($low_stock_products)): ?>
                        <p style="color: #16a34a; text-align: center; padding: 20px 0;">✓ Kho hàng ổn định, chưa có sách nào dưới mức tồn kho tối thiểu.</p>
                    <?php else: ?>
                        <table class="biblio-table">
                            <thead>
                                <tr>
                                    <th>Tựa sách</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $prod): 
                                    $qty = $prod->get_stock_quantity();
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($prod->get_name()); ?></strong></td>
                                        <td><?php echo $prod->get_price_html(); ?></td>
                                        <td><strong style="color: #dc2626;"><?php echo is_null($qty) ? '0' : esc_html($qty); ?> cuốn</strong></td>
                                        <td><span class="biblio-badge biblio-badge-cancelled"><?php echo $prod->is_in_stock() ? 'Sắp hết' : 'Hết hàng'; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Khách hàng mới đăng ký -->
                <div class="biblio-panel">
                    <div class="biblio-panel-header">
                        <h2 class="biblio-panel-title">👤 Khách Hàng Mới Tham Gia</h2>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-customers')); ?>" style="font-size: 12.5px; color: #ee4d2d; text-decoration: none; font-weight: 600;">Xem tất cả</a>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($recent_users as $u): 
                            $pts = nbook_get_points_balance($u->ID);
                        ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                                <div>
                                    <strong style="font-size: 13px;"><?php echo esc_html($u->display_name); ?></strong>
                                    <div style="font-size: 11.5px; color: #64748b;"><?php echo esc_html($u->user_email); ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 11.5px; color: #ee4d2d; font-weight: 600;"><?php echo esc_html(number_format_i18n($pts)); ?> Xu</span>
                                    <div style="font-size: 10.5px; color: #94a3b8;"><?php echo esc_html(mysql2date('d/m/Y', $u->user_registered)); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script vẽ biểu đồ đường Canvas mượt mà (Pure Vanilla HTML5 Canvas - Không phụ thuộc CDN) -->
        <script>
        (function() {
            var chartData = <?php echo json_encode($chart_data); ?>;
            var canvas = document.getElementById('biblioRevenueCanvas');
            if (!canvas) return;

            var ctx = canvas.getContext('2d');
            var dpr = window.devicePixelRatio || 1;
            var rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);

            var width = rect.width;
            var height = rect.height;
            var padding = { top: 25, right: 30, bottom: 35, left: 65 };

            var labels = chartData.labels || [];
            var values = chartData.values || [];
            var maxVal = Math.max.apply(null, values);
            if (maxVal === 0) maxVal = 100000;
            // Round max to neat ceiling
            maxVal = Math.ceil(maxVal * 1.15 / 10000) * 10000;

            var chartWidth = width - padding.left - padding.right;
            var chartHeight = height - padding.top - padding.bottom;

            // Draw horizontal grid lines
            var gridLines = 4;
            ctx.lineWidth = 1;
            ctx.strokeStyle = '#f1f5f9';
            ctx.fillStyle = '#94a3b8';
            ctx.font = '11px -apple-system, BlinkMacSystemFont, sans-serif';
            ctx.textAlign = 'right';

            for (var i = 0; i <= gridLines; i++) {
                var y = padding.top + (chartHeight / gridLines) * i;
                var val = Math.round(maxVal - (maxVal / gridLines) * i);
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();

                var valText = (val >= 1000000) ? (val / 1000000).toFixed(1) + 'M' : ((val >= 1000) ? Math.round(val / 1000) + 'k' : val);
                ctx.fillText(valText + 'đ', padding.left - 8, y + 4);
            }

            if (values.length === 0) return;

            // Compute coordinates
            var points = [];
            var stepX = chartWidth / (values.length - 1 || 1);
            for (var j = 0; j < values.length; j++) {
                var px = padding.left + j * stepX;
                var py = padding.top + chartHeight - (values[j] / maxVal) * chartHeight;
                points.push({ x: px, y: py, val: values[j], label: labels[j] });
            }

            // Fill gradient below line
            var grad = ctx.createLinearGradient(0, padding.top, 0, height - padding.bottom);
            grad.addColorStop(0, 'rgba(238, 77, 45, 0.28)');
            grad.addColorStop(1, 'rgba(238, 77, 45, 0.00)');

            ctx.beginPath();
            ctx.moveTo(points[0].x, height - padding.bottom);
            for (var k = 0; k < points.length; k++) {
                ctx.lineTo(points[k].x, points[k].y);
            }
            ctx.lineTo(points[points.length - 1].x, height - padding.bottom);
            ctx.closePath();
            ctx.fillStyle = grad;
            ctx.fill();

            // Draw Orange Line
            ctx.beginPath();
            ctx.strokeStyle = '#ee4d2d';
            ctx.lineWidth = 3;
            ctx.lineJoin = 'round';
            for (var m = 0; m < points.length; m++) {
                if (m === 0) ctx.moveTo(points[m].x, points[m].y);
                else ctx.lineTo(points[m].x, points[m].y);
            }
            ctx.stroke();

            // Draw dots & labels on X-axis
            ctx.textAlign = 'center';
            for (var n = 0; n < points.length; n++) {
                var p = points[n];

                // Outer circle
                ctx.beginPath();
                ctx.arc(p.x, p.y, 5, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
                ctx.strokeStyle = '#ee4d2d';
                ctx.lineWidth = 2.5;
                ctx.stroke();

                // X label
                ctx.fillStyle = '#64748b';
                ctx.font = '11.5px sans-serif';
                ctx.fillText(p.label, p.x, height - 12);
            }
        })();
        </script>
        <?php
    }

    /**
     * Tính tổng doanh thu theo khoảng thời gian
     */
    private function calculate_revenue($from, $to) {
        global $wpdb;
        $sql = "
            SELECT COALESCE(SUM(pm.meta_value), 0)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_order_total')
            WHERE p.post_type = 'shop_order'
              AND p.post_status IN ('wc-completed', 'wc-processing')
              AND p.post_date >= %s
              AND p.post_date <= %s
        ";
        return (float) $wpdb->get_var($wpdb->prepare($sql, $from, $to));
    }

    /**
     * Lấy dữ liệu doanh thu 7 ngày cho biểu đồ
     */
    private function get_chart_revenue_7days() {
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $ts = strtotime("-{$i} days", current_time('timestamp'));
            $start = date('Y-m-d 00:00:00', $ts);
            $end = date('Y-m-d 23:59:59', $ts);
            $labels[] = date('d/m', $ts);
            $values[] = $this->calculate_revenue($start, $end);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Lấy danh sách sách sắp hết hàng (tồn kho <= 5)
     */
    private function get_low_stock_products($limit = 5) {
        return wc_get_products([
            'limit'        => $limit,
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
    }

    /**
     * Lấy top sách bán chạy nhất
     */
    private function get_top_bestsellers($limit = 5) {
        return wc_get_products([
            'limit'    => $limit,
            'status'   => 'publish',
            'meta_key' => 'total_sales',
            'orderby'  => 'meta_value_num',
            'order'    => 'DESC',
        ]);
    }

    // =========================================================================
    // 2. QUẢN LÝ ĐƠN HÀNG (ORDERS)
    // =========================================================================
    public function render_orders_page() {
        if (!current_user_can('manage_biblio_orders') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang quản lý đơn hàng.');
        }

        $filter_status = sanitize_key($_GET['order_status'] ?? '');
        $filter_search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $date_from     = sanitize_text_field($_GET['date_from'] ?? '');
        $date_to       = sanitize_text_field($_GET['date_to'] ?? '');
        $paged         = max(1, absint($_GET['paged'] ?? 1));
        $per_page      = 15;

        // Xây dựng query
        $query_args = [
            'limit'    => $per_page,
            'page'     => $paged,
            'paginate' => true,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ];

        if ($filter_status) {
            $query_args['status'] = $filter_status;
        }

        if ($date_from) {
            $query_args['date_after'] = $date_from . ' 00:00:00';
        }
        if ($date_to) {
            $query_args['date_before'] = $date_to . ' 23:59:59';
        }

        if ($filter_search) {
            // Tìm theo ID hoặc chuỗi
            if (is_numeric($filter_search)) {
                $query_args['post__in'] = [absint($filter_search)];
            } else {
                $query_args['customer'] = $filter_search;
            }
        }

        $results = wc_get_orders($query_args);
        $orders = $results->orders;
        $total_pages = $results->max_num_pages;
        $total_orders = $results->total;

        $statuses = wc_get_order_statuses();

        ?>
        <div class="wrap biblio-admin-wrap">
            <div class="biblio-header-bar">
                <div>
                    <h1>📦 QUẢN LÝ ĐƠN HÀNG</h1>
                    <div class="biblio-header-tagline">Theo dõi tiến độ, cập nhật trạng thái hàng loạt và gửi thông báo đơn hàng cho khách</div>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <!-- Thanh Lọc & Tìm Kiếm -->
            <form method="get" class="biblio-filter-bar">
                <input type="hidden" name="page" value="biblio-orders">
                
                <input type="text" name="search" value="<?php echo esc_attr($filter_search); ?>" placeholder="Tìm mã đơn, tên khách..." class="regular-text" style="width: 200px;">
                
                <select name="order_status">
                    <option value="">-- Tất cả trạng thái --</option>
                    <?php foreach ($statuses as $st_key => $st_label): 
                        $clean = str_replace('wc-', '', $st_key);
                    ?>
                        <option value="<?php echo esc_attr($clean); ?>" <?php selected($filter_status, $clean); ?>>
                            <?php echo esc_html($st_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Từ: <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>"></label>
                <label>Đến: <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>"></label>

                <button type="submit" class="button">Lọc đơn hàng</button>
                <?php if ($filter_status || $filter_search || $date_from || $date_to): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-orders')); ?>" class="button">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>

            <!-- Form Thao Tác Hàng Loạt -->
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="biblio-orders-form">
                <input type="hidden" name="action" value="biblio_bulk_orders">
                <?php wp_nonce_field('biblio_bulk_orders_nonce', 'bulk_nonce'); ?>

                <div style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <select name="bulk_action_status" required style="min-width: 200px;">
                            <option value="">-- Cập nhật trạng thái hàng loạt --</option>
                            <option value="processing">Chuyển sang: Đang xử lý</option>
                            <option value="shipping">Chuyển sang: Đang giao hàng</option>
                            <option value="completed">Chuyển sang: Đã giao hoàn thành</option>
                            <option value="cancelled">Chuyển sang: Đã hủy đơn</option>
                        </select>
                        <button type="submit" class="button biblio-btn-primary" onclick="return confirm('Bạn có chắc muốn cập nhật trạng thái các đơn hàng đã chọn?');">
                            Áp dụng cho các đơn đã chọn
                        </button>
                    </div>
                    <span style="font-size: 13px; color: #64748b;">Tìm thấy <strong><?php echo esc_html($total_orders); ?></strong> đơn hàng</span>
                </div>

                <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
                    <table class="biblio-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;"><input type="checkbox" id="biblio-select-all-orders"></th>
                                <th style="width: 80px;">Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Chi tiết sản phẩm</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th style="text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="9" style="text-align: center; padding: 24px; color: #888;">Không tìm thấy đơn hàng nào phù hợp với bộ lọc.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): 
                                    $st = $ord->get_status();
                                    $items = $ord->get_items();
                                    $items_summary = [];
                                    foreach ($items as $item) {
                                        $items_summary[] = esc_html($item->get_name()) . ' (x' . $item->get_quantity() . ')';
                                    }
                                    $items_text = implode(', ', $items_summary);
                                ?>
                                    <tr>
                                        <td><input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($ord->get_id()); ?>" class="biblio-order-checkbox"></td>
                                        <td><strong>#<?php echo esc_html($ord->get_order_number()); ?></strong></td>
                                        <td>
                                            <strong><?php echo esc_html($ord->get_formatted_billing_full_name() ?: 'Khách vãng lai'); ?></strong>
                                            <div style="font-size: 11.5px; color: #64748b;"><?php echo esc_html($ord->get_billing_phone()); ?></div>
                                        </td>
                                        <td style="max-width: 250px;">
                                            <div style="font-size: 12px; line-height: 1.4; color: #334155; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?php echo esc_attr($items_text); ?>">
                                                <?php echo $items_text ?: 'Không có sản phẩm'; ?>
                                            </div>
                                        </td>
                                        <td><strong style="color: #ee4d2d;"><?php echo wp_strip_all_tags(wc_price($ord->get_total())); ?></strong></td>
                                        <td><span style="font-size: 12px; color: #475569;"><?php echo esc_html($ord->get_payment_method_title() ?: 'COD'); ?></span></td>
                                        <td><span class="biblio-badge biblio-badge-<?php echo esc_attr($st); ?>"><?php echo esc_html(wc_get_order_status_name($st)); ?></span></td>
                                        <td style="font-size: 12px; color: #64748b;"><?php echo esc_html($ord->get_date_created() ? $ord->get_date_created()->date('d/m/Y H:i') : ''); ?></td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="button button-small biblio-open-notify-modal" 
                                                    data-order-id="<?php echo esc_attr($ord->get_id()); ?>" 
                                                    data-order-num="<?php echo esc_attr($ord->get_order_number()); ?>"
                                                    data-user-id="<?php echo esc_attr($ord->get_user_id()); ?>"
                                                    data-customer-name="<?php echo esc_attr($ord->get_formatted_billing_full_name()); ?>"
                                                    title="Gửi thông báo đến tài khoản khách hàng">
                                                🔔 Nhắn tin
                                            </button>
                                            <a href="<?php echo esc_url($ord->get_edit_order_url()); ?>" class="button button-small" target="_blank" title="Xem chi tiết đơn trong WooCommerce">
                                                Chi tiết →
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 16px; text-align: right;">
                        <?php 
                        echo wp_kses_post(paginate_links([
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '',
                            'current'   => $paged,
                            'total'     => $total_pages,
                            'prev_text' => '← Trước',
                            'next_text' => 'Sau →',
                        ]));
                        ?>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Modal Gửi Thông Báo Riêng Cho Khách Hàng -->
            <div id="biblio-notify-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;">
                <div style="background: #fff; width: 480px; max-width: 90%; border-radius: 12px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <h3 style="margin-top: 0; color: #ee4d2d;" id="biblio-modal-title">Gửi thông báo đơn hàng</h3>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="biblio_send_order_notice">
                        <input type="hidden" name="order_id" id="modal_order_id" value="">
                        <input type="hidden" name="user_id" id="modal_user_id" value="">
                        <?php wp_nonce_field('biblio_send_notice_nonce', 'notice_nonce'); ?>

                        <p>
                            <label><strong>Tiêu đề thông báo:</strong></label><br>
                            <input type="text" name="notice_title" id="modal_notice_title" class="large-text" required value="Cập nhật tiến độ đơn hàng">
                        </p>
                        <p>
                            <label><strong>Nội dung thông báo gửi khách hàng:</strong></label><br>
                            <textarea name="notice_message" id="modal_notice_message" rows="4" class="large-text" required placeholder="Nhập nội dung nhắn gửi (ví dụ: Đơn hàng của bạn đã được đóng gói và giao cho bưu tá...)"></textarea>
                        </p>
                        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                            <button type="button" class="button" id="biblio-modal-cancel">Hủy</button>
                            <button type="submit" class="button biblio-btn-primary">Gửi thông báo ngay</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectAll = document.getElementById('biblio-select-all-orders');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    var cbs = document.querySelectorAll('.biblio-order-checkbox');
                    for (var i = 0; i < cbs.length; i++) {
                        cbs[i].checked = selectAll.checked;
                    }
                });
            }

            // Modal Gửi thông báo
            var modal = document.getElementById('biblio-notify-modal');
            var cancelBtn = document.getElementById('biblio-modal-cancel');
            var modalOrderId = document.getElementById('modal_order_id');
            var modalUserId = document.getElementById('modal_user_id');
            var modalTitle = document.getElementById('biblio-modal-title');
            var modalNoticeTitle = document.getElementById('modal_notice_title');

            document.querySelectorAll('.biblio-open-notify-modal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    modalOrderId.value = this.dataset.orderId;
                    modalUserId.value = this.dataset.userId;
                    modalTitle.textContent = 'Gửi thông báo cho Đơn #' + this.dataset.orderNum + ' (' + this.dataset.customerName + ')';
                    modalNoticeTitle.value = 'Thông tin cập nhật đơn hàng #' + this.dataset.orderNum;
                    modal.style.display = 'flex';
                });
            });

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Xử lý cập nhật hàng loạt trạng thái đơn hàng
     */
    public function handle_bulk_orders() {
        if (!current_user_can('manage_biblio_orders') || !check_admin_referer('biblio_bulk_orders_nonce', 'bulk_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $order_ids = isset($_POST['order_ids']) ? array_map('absint', (array) $_POST['order_ids']) : [];
        $status = sanitize_key($_POST['bulk_action_status'] ?? '');

        if (empty($order_ids) || empty($status)) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-orders', 'biblio_notice' => 'Vui lòng chọn đơn hàng và trạng thái cần cập nhật.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        $current_user = wp_get_current_user();
        $user_name = $current_user->display_name ?: 'Quản trị viên';
        $updated_count = 0;

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status($status, sprintf('Cập nhật trạng thái hàng loạt sang %s bởi %s.', wc_get_order_status_name($status), $user_name));
                
                // Gửi thông báo đến tài khoản khách hàng nếu có user_id
                if ($order->get_user_id()) {
                    nbook_add_notification(
                        $order->get_user_id(),
                        'Đơn hàng #' . $order->get_order_number() . ' cập nhật',
                        sprintf('Đơn hàng #%s của bạn vừa được cập nhật sang trạng thái: %s.', $order->get_order_number(), wc_get_order_status_name($status)),
                        $order->get_view_order_url(),
                        'order'
                    );
                }
                $updated_count++;
            }
        }

        wp_safe_redirect(add_query_arg([
            'page'          => 'biblio-orders',
            'biblio_notice' => "Đã cập nhật trạng thái thành công cho {$updated_count} đơn hàng.",
            'biblio_type'   => 'success'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Xử lý gửi thông báo riêng đến khách hàng từ đơn hàng
     */
    public function handle_send_order_notice() {
        if (!current_user_can('manage_biblio_orders') || !check_admin_referer('biblio_send_notice_nonce', 'notice_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $order_id = absint($_POST['order_id'] ?? 0);
        $user_id  = absint($_POST['user_id'] ?? 0);
        $title    = sanitize_text_field(wp_unslash($_POST['notice_title'] ?? ''));
        $message  = sanitize_textarea_field(wp_unslash($_POST['notice_message'] ?? ''));

        if (!$order_id || empty($title) || empty($message)) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-orders', 'biblio_notice' => 'Thông tin không hợp lệ.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        $order = wc_get_order($order_id);
        if ($order) {
            // Thêm ghi chú vào đơn hàng
            $order->add_order_note('Đã gửi thông báo cho khách: ' . $message);

            if ($user_id > 0) {
                nbook_add_notification(
                    $user_id,
                    $title,
                    $message,
                    $order->get_view_order_url(),
                    'order'
                );
            }
        }

        wp_safe_redirect(add_query_arg([
            'page'          => 'biblio-orders',
            'biblio_notice' => 'Đã gửi thông báo thành công tới khách hàng của đơn #' . ($order ? $order->get_order_number() : $order_id) . '.',
            'biblio_type'   => 'success'
        ], admin_url('admin.php')));
        exit;
    }

    // =========================================================================
    // 3. QUẢN LÝ SÁCH VÀ KHO (BOOKS & INVENTORY)
    // =========================================================================
    public function render_books_page() {
        if (!current_user_can('manage_biblio_books') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang quản lý sách và kho.');
        }

        $filter_cat   = sanitize_text_field($_GET['book_cat'] ?? '');
        $filter_stock = sanitize_key($_GET['stock_status'] ?? '');
        $search_term  = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $paged        = max(1, absint($_GET['paged'] ?? 1));
        $per_page     = 20;

        $args = [
            'status'   => 'publish',
            'limit'    => $per_page,
            'page'     => $paged,
            'paginate' => true,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ];

        if ($filter_cat) {
            $args['category'] = [$filter_cat];
        }

        if ($filter_stock === 'low') {
            $args['meta_query'] = [
                'relation' => 'OR',
                ['key' => '_stock', 'value' => 5, 'compare' => '<=', 'type' => 'NUMERIC'],
                ['key' => '_stock_status', 'value' => 'outofstock', 'compare' => '='],
            ];
        } elseif ($filter_stock === 'outofstock') {
            $args['stock_status'] = 'outofstock';
        } elseif ($filter_stock === 'instock') {
            $args['stock_status'] = 'instock';
        }

        if ($search_term) {
            $args['s'] = $search_term;
        }

        $results = wc_get_products($args);
        $products = $results->products;
        $total_pages = $results->max_num_pages;
        $total_books = $results->total;

        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

        ?>
        <div class="wrap biblio-admin-wrap">
            <div class="biblio-header-bar">
                <div>
                    <h1>📚 QUẢN LÝ SÁCH & KHO HÀNG</h1>
                    <div class="biblio-header-tagline">Sửa nhanh giá bán, giá sale, tồn kho sản phẩm trực tiếp và Nhập/Xuất file CSV</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=biblio_export_books_csv'), 'biblio_export_csv')); ?>" class="button" style="font-weight: 600;">
                        📥 Xuất CSV Kho Sách
                    </a>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <!-- Hộp Nhập CSV Kho Sách -->
            <div class="biblio-panel" style="margin-bottom: 20px; background: #fff8f6; border-color: #ffdcd2;">
                <h3 style="margin-top: 0; color: #ee4d2d; font-size: 15px;">📤 Nhập file CSV cập nhật Giá & Tồn Kho hàng loạt</h3>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="biblio_import_books_csv">
                    <?php wp_nonce_field('biblio_import_csv_nonce', 'import_nonce'); ?>
                    
                    <input type="file" name="books_csv_file" accept=".csv" required style="background: #fff; padding: 4px; border-radius: 4px; border: 1px solid #ccc;">
                    <button type="submit" class="button biblio-btn-primary">Tải lên & Cập nhật ngay</button>
                    <span class="description" style="font-size: 12px; color: #666;">Hỗ trợ file CSV có các cột: <code>ID, Regular_Price, Sale_Price, Stock_Quantity</code>.</span>
                </form>
            </div>

            <!-- Bộ lọc & Tìm kiếm -->
            <form method="get" class="biblio-filter-bar">
                <input type="hidden" name="page" value="biblio-books">

                <input type="text" name="search" value="<?php echo esc_attr($search_term); ?>" placeholder="Tên sách, tác giả, SKU..." class="regular-text" style="width: 220px;">

                <select name="book_cat">
                    <option value="">-- Tất cả danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($filter_cat, $cat->slug); ?>>
                            <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="stock_status">
                    <option value="">-- Tất cả tình trạng kho --</option>
                    <option value="instock" <?php selected($filter_stock, 'instock'); ?>>Còn hàng</option>
                    <option value="low" <?php selected($filter_stock, 'low'); ?>>Sắp hết hàng (&le; 5 cuốn)</option>
                    <option value="outofstock" <?php selected($filter_stock, 'outofstock'); ?>>Hết hàng</option>
                </select>

                <button type="submit" class="button">Lọc sách</button>
                <?php if ($filter_cat || $filter_stock || $search_term): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-books')); ?>" class="button">Xóa lọc</a>
                <?php endif; ?>
            </form>

            <!-- Form Sửa Nhanh Giá & Kho Hàng -->
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="biblio_save_quick_books">
                <?php wp_nonce_field('biblio_quick_books_nonce', 'quick_nonce'); ?>

                <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; color: #64748b;">Tổng số: <strong><?php echo esc_html($total_books); ?></strong> cuốn sách</span>
                    <button type="submit" class="button biblio-btn-primary" style="padding: 6px 18px !important;">
                        💾 LƯU THAY ĐỔI GIÁ & TỒN KHO
                    </button>
                </div>

                <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
                    <table class="biblio-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Bìa</th>
                                <th>Tên sách & Tác giả</th>
                                <th style="width: 100px;">SKU</th>
                                <th style="width: 130px;">Giá gốc (VNĐ)</th>
                                <th style="width: 130px;">Giá sale (VNĐ)</th>
                                <th style="width: 100px;">Tồn kho</th>
                                <th style="width: 110px;">Trạng thái kho</th>
                                <th style="width: 80px; text-align: right;">Xem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="8" style="text-align: center; padding: 24px; color: #888;">Không tìm thấy cuốn sách nào phù hợp.</td></tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): 
                                    $pid = $p->get_id();
                                    $thumb = get_the_post_thumbnail_url($pid, 'thumbnail') ?: wc_placeholder_img_src();
                                    $author = get_post_meta($pid, '_biblio_author', true) ?: (get_post_meta($pid, '_nbook_author', true) ?: '');
                                    $reg_price = $p->get_regular_price();
                                    $sale_price = $p->get_sale_price();
                                    $stock_qty = $p->get_stock_quantity();
                                    $st_status = $p->get_stock_status();
                                ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo esc_url($thumb); ?>" style="width: 38px; height: 50px; object-fit: cover; border-radius: 4px;" alt="">
                                            <input type="hidden" name="book_ids[]" value="<?php echo esc_attr($pid); ?>">
                                        </td>
                                        <td>
                                            <strong><a href="<?php echo esc_url(get_edit_post_link($pid)); ?>" target="_blank" style="color: #1e293b;"><?php echo esc_html($p->get_name()); ?></a></strong>
                                            <?php if ($author): ?>
                                                <div style="font-size: 11.5px; color: #64748b;">Tác giả: <?php echo esc_html($author); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code style="font-size: 11px;"><?php echo esc_html($p->get_sku() ?: '—'); ?></code>
                                        </td>
                                        <td>
                                            <input type="number" step="1000" min="0" name="regular_price[<?php echo esc_attr($pid); ?>]" value="<?php echo esc_attr($reg_price); ?>" style="width: 110px; font-weight: 600;">
                                        </td>
                                        <td>
                                            <input type="number" step="1000" min="0" name="sale_price[<?php echo esc_attr($pid); ?>]" value="<?php echo esc_attr($sale_price); ?>" style="width: 110px; color: #ee4d2d; font-weight: 600;">
                                        </td>
                                        <td>
                                            <input type="number" min="0" name="stock_qty[<?php echo esc_attr($pid); ?>]" value="<?php echo esc_attr(is_null($stock_qty) ? '' : $stock_qty); ?>" placeholder="Tồn" style="width: 80px; font-weight: 600;">
                                        </td>
                                        <td>
                                            <select name="stock_status[<?php echo esc_attr($pid); ?>]" style="font-size: 12px;">
                                                <option value="instock" <?php selected($st_status, 'instock'); ?>>Còn hàng</option>
                                                <option value="outofstock" <?php selected($st_status, 'outofstock'); ?>>Hết hàng</option>
                                            </select>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="<?php echo esc_url($p->get_permalink()); ?>" target="_blank" class="button button-small" title="Xem trên web">
                                                🔗
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <button type="submit" class="button biblio-btn-primary" style="padding: 6px 18px !important;">
                        💾 LƯU THAY ĐỔI GIÁ & TỒN KHO
                    </button>
                    <?php if ($total_pages > 1): ?>
                        <div>
                            <?php 
                            echo wp_kses_post(paginate_links([
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'current'   => $paged,
                                'total'     => $total_pages,
                                'prev_text' => '← Trước',
                                'next_text' => 'Sau →',
                            ]));
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Xử lý lưu nhanh giá và tồn kho
     */
    public function handle_save_quick_books() {
        if (!current_user_can('manage_biblio_books') || !check_admin_referer('biblio_quick_books_nonce', 'quick_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $book_ids      = isset($_POST['book_ids']) ? array_map('absint', (array) $_POST['book_ids']) : [];
        $regular_price = (array) ($_POST['regular_price'] ?? []);
        $sale_price    = (array) ($_POST['sale_price'] ?? []);
        $stock_qty     = (array) ($_POST['stock_qty'] ?? []);
        $stock_status  = (array) ($_POST['stock_status'] ?? []);

        $saved_count = 0;
        foreach ($book_ids as $pid) {
            $product = wc_get_product($pid);
            if ($product) {
                if (isset($regular_price[$pid])) {
                    $reg = sanitize_text_field($regular_price[$pid]);
                    $product->set_regular_price($reg);
                }
                if (isset($sale_price[$pid])) {
                    $sale = sanitize_text_field($sale_price[$pid]);
                    $product->set_sale_price($sale);
                }
                if (isset($stock_qty[$pid]) && $stock_qty[$pid] !== '') {
                    $qty = absint($stock_qty[$pid]);
                    $product->set_manage_stock(true);
                    $product->set_stock_quantity($qty);
                }
                if (isset($stock_status[$pid])) {
                    $st = sanitize_key($stock_status[$pid]);
                    $product->set_stock_status($st);
                }
                $product->save();
                $saved_count++;
            }
        }

        wp_safe_redirect(add_query_arg([
            'page'          => 'biblio-books',
            'biblio_notice' => "Đã lưu cập nhật nhanh giá và tồn kho cho {$saved_count} cuốn sách.",
            'biblio_type'   => 'success'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Xuất file CSV danh sách sách
     */
    public function handle_export_books_csv() {
        if (!current_user_can('manage_biblio_books') || !check_admin_referer('biblio_export_csv')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $products = wc_get_products([
            'status' => 'publish',
            'limit'  => -1,
        ]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="biblio-books-export-' . current_time('Ymd-His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel Windows compatibility
        fputs($out, "\xEF\xBB\xBF");

        // Header CSV
        fputcsv($out, ['ID', 'Title', 'Author', 'SKU', 'Regular_Price', 'Sale_Price', 'Stock_Quantity', 'Stock_Status', 'Categories']);

        foreach ($products as $p) {
            $pid = $p->get_id();
            $author = get_post_meta($pid, '_biblio_author', true) ?: (get_post_meta($pid, '_nbook_author', true) ?: '');
            $cats = wp_strip_all_tags(wc_get_product_category_list($pid));
            
            fputcsv($out, [
                $pid,
                $p->get_name(),
                $author,
                $p->get_sku(),
                $p->get_regular_price(),
                $p->get_sale_price(),
                $p->get_stock_quantity() !== null ? $p->get_stock_quantity() : '',
                $p->get_stock_status(),
                $cats
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * Nhập file CSV cập nhật giá và tồn kho
     */
    public function handle_import_books_csv() {
        if (!current_user_can('manage_biblio_books') || !check_admin_referer('biblio_import_csv_nonce', 'import_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        if (empty($_FILES['books_csv_file']['tmp_name']) || !is_uploaded_file($_FILES['books_csv_file']['tmp_name'])) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-books', 'biblio_notice' => 'Vui lòng chọn file CSV để tải lên.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        $handle = fopen($_FILES['books_csv_file']['tmp_name'], 'r');
        if (!$handle) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-books', 'biblio_notice' => 'Không thể mở file CSV.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        $headers = fgetcsv($handle);
        $header_map = [];
        if ($headers) {
            foreach ($headers as $col_idx => $col_name) {
                $clean_header = strtolower(trim(preg_replace('/[\xEF\xBB\xBF]/', '', $col_name)));
                $header_map[$clean_header] = $col_idx;
            }
        }

        $updated = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $id = isset($header_map['id']) ? absint($row[$header_map['id']]) : 0;
            $sku = isset($header_map['sku']) ? trim($row[$header_map['sku']]) : '';

            $product = null;
            if ($id > 0) {
                $product = wc_get_product($id);
            } elseif (!empty($sku)) {
                $found_id = wc_get_product_id_by_sku($sku);
                if ($found_id) $product = wc_get_product($found_id);
            }

            if ($product) {
                if (isset($header_map['regular_price']) && isset($row[$header_map['regular_price']])) {
                    $reg = trim($row[$header_map['regular_price']]);
                    if ($reg !== '') $product->set_regular_price($reg);
                }
                if (isset($header_map['sale_price']) && isset($row[$header_map['sale_price']])) {
                    $sale = trim($row[$header_map['sale_price']]);
                    if ($sale !== '') $product->set_sale_price($sale);
                }
                if (isset($header_map['stock_quantity']) && isset($row[$header_map['stock_quantity']])) {
                    $stock = trim($row[$header_map['stock_quantity']]);
                    if ($stock !== '') {
                        $product->set_manage_stock(true);
                        $product->set_stock_quantity(absint($stock));
                    }
                }
                $product->save();
                $updated++;
            }
        }

        fclose($handle);

        wp_safe_redirect(add_query_arg([
            'page'          => 'biblio-books',
            'biblio_notice' => "Đã nhập thành công file CSV và cập nhật {$updated} cuốn sách.",
            'biblio_type'   => 'success'
        ], admin_url('admin.php')));
        exit;
    }

    // =========================================================================
    // 4. QUẢN LÝ KHÁCH HÀNG (CUSTOMERS)
    // =========================================================================
    public function render_customers_page() {
        if (!current_user_can('manage_biblio_customers') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang quản lý khách hàng.');
        }

        $search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $paged  = max(1, absint($_GET['paged'] ?? 1));
        $per_page = 20;

        $user_query_args = [
            'number'  => $per_page,
            'paged'   => $paged,
            'orderby' => 'registered',
            'order'   => 'DESC',
            'role__in' => ['customer', 'subscriber'],
        ];

        if ($search) {
            $user_query_args['search'] = '*' . $search . '*';
            $user_query_args['search_columns'] = ['user_login', 'user_email', 'user_nicename', 'display_name'];
        }

        $user_query = new WP_User_Query($user_query_args);
        $customers = $user_query->get_results();
        $total_customers = $user_query->get_total();
        $total_pages = ceil($total_customers / $per_page);

        ?>
        <div class="wrap biblio-admin-wrap">
            <div class="biblio-header-bar">
                <div>
                    <h1>👥 QUẢN LÝ KHÁCH HÀNG</h1>
                    <div class="biblio-header-tagline">Theo dõi hồ sơ thành viên, số dư điểm Biblio Xu, và Khóa / Mở khóa tài khoản</div>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <!-- Tìm kiếm khách hàng -->
            <form method="get" class="biblio-filter-bar">
                <input type="hidden" name="page" value="biblio-customers">
                <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Tìm tên, email, username..." class="regular-text" style="width: 250px;">
                <button type="submit" class="button">Tìm kiếm</button>
                <?php if ($search): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-customers')); ?>" class="button">Xóa lọc</a>
                <?php endif; ?>
                <span style="margin-left: auto; font-size: 13px; color: #64748b;">Tổng số: <strong><?php echo esc_html($total_customers); ?></strong> khách hàng</span>
            </form>

            <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
                <table class="biblio-table">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Điểm Biblio Xu</th>
                            <th>Tổng chi tiêu</th>
                            <th>Hạng thẻ</th>
                            <th>Trạng thái tài khoản</th>
                            <th>Ngày tham gia</th>
                            <th style="text-align: right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr><td colspan="9" style="text-align: center; padding: 24px; color: #888;">Chưa có khách hàng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): 
                                $uid = $c->ID;
                                $spent = (float) get_user_meta($uid, '_nbook_total_spent', true);
                                $phone = get_user_meta($uid, 'billing_phone', true) ?: '—';
                                $pts = nbook_get_points_balance($uid);
                                $is_locked = get_user_meta($uid, '_nbook_account_locked', true) === '1';
                                $tier = ($spent >= 10000000) ? 'Vàng' : (($spent >= 3000000) ? 'Bạc' : 'Đồng');
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($c->display_name); ?></strong>
                                        <div style="font-size: 11px; color: #94a3b8;">ID: #<?php echo esc_html($uid); ?> (<?php echo esc_html($c->user_login); ?>)</div>
                                    </td>
                                    <td><a href="mailto:<?php echo esc_attr($c->user_email); ?>"><?php echo esc_html($c->user_email); ?></a></td>
                                    <td><?php echo esc_html($phone); ?></td>
                                    <td><strong style="color: #ee4d2d;"><?php echo esc_html(number_format_i18n($pts)); ?> Xu</strong></td>
                                    <td><?php echo wp_strip_all_tags(wc_price($spent)); ?></td>
                                    <td>
                                        <span class="biblio-badge" style="background: <?php echo $tier === 'Vàng' ? '#fef08a' : ($tier === 'Bạc' ? '#e2e8f0' : '#fed7aa'); ?>; color: #000;">
                                            <?php echo esc_html($tier); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($is_locked): ?>
                                            <span class="biblio-badge biblio-badge-locked">ĐÃ BỊ KHÓA</span>
                                        <?php else: ?>
                                            <span class="biblio-badge biblio-badge-active">Hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: #64748b;"><?php echo esc_html(mysql2date('d/m/Y', $c->user_registered)); ?></td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <!-- Nút Điều chỉnh Xu -->
                                        <button type="button" class="button button-small biblio-open-points-modal" 
                                                data-user-id="<?php echo esc_attr($uid); ?>"
                                                data-user-name="<?php echo esc_attr($c->display_name); ?>"
                                                data-current-points="<?php echo esc_attr($pts); ?>"
                                                title="Cộng hoặc trừ điểm thưởng Biblio Xu">
                                            🪙 Điểm
                                        </button>

                                        <!-- Nút Khóa / Mở khóa -->
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                                            <input type="hidden" name="action" value="biblio_toggle_customer_lock">
                                            <input type="hidden" name="customer_id" value="<?php echo esc_attr($uid); ?>">
                                            <?php wp_nonce_field('biblio_customer_lock_nonce_' . $uid, 'lock_nonce'); ?>
                                            
                                            <?php if ($is_locked): ?>
                                                <button type="submit" class="button button-small" style="color: #16a34a;" onclick="return confirm('Mở khóa tài khoản cho khách hàng này?');">
                                                    🔓 Mở khóa
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="button button-small" style="color: #dc2626;" onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản khách hàng này? Khách hàng sẽ không thể đăng nhập được nữa.');">
                                                    🔒 Khóa
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 16px; text-align: right;">
                    <?php 
                    echo wp_kses_post(paginate_links([
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => $total_pages,
                        'prev_text' => '← Trước',
                        'next_text' => 'Sau →',
                    ]));
                    ?>
                </div>
            <?php endif; ?>

            <!-- Modal Điều Chỉnh Điểm Xu -->
            <div id="biblio-points-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;">
                <div style="background: #fff; width: 440px; max-width: 90%; border-radius: 12px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <h3 style="margin-top: 0; color: #ee4d2d;" id="modal-pts-title">Điều chỉnh Biblio Xu</h3>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="biblio_adjust_customer_points">
                        <input type="hidden" name="customer_id" id="modal_pts_user_id" value="">
                        <?php wp_nonce_field('biblio_adjust_points_nonce', 'pts_nonce'); ?>

                        <p>Số dư hiện tại: <strong id="modal_current_pts" style="color: #ee4d2d;">0</strong> Xu</p>
                        <p>
                            <label><strong>Số điểm điều chỉnh (+ để cộng, - để trừ):</strong></label><br>
                            <input type="number" name="amount" class="regular-text" required placeholder="Ví dụ: 100 hoặc -50">
                        </p>
                        <p>
                            <label><strong>Lý do điều chỉnh:</strong></label><br>
                            <input type="text" name="description" class="large-text" required placeholder="Thưởng sinh nhật, hỗ trợ CSKH, bù điểm...">
                        </p>
                        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                            <button type="button" class="button" id="modal-pts-cancel">Hủy</button>
                            <button type="submit" class="button biblio-btn-primary">Lưu điều chỉnh</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ptsModal = document.getElementById('biblio-points-modal');
            var cancelPts = document.getElementById('modal-pts-cancel');
            var modalUserId = document.getElementById('modal_pts_user_id');
            var modalTitle = document.getElementById('modal-pts-title');
            var modalCurrentPts = document.getElementById('modal_current_pts');

            document.querySelectorAll('.biblio-open-points-modal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    modalUserId.value = this.dataset.userId;
                    modalTitle.textContent = 'Điều chỉnh điểm Xu cho: ' + this.dataset.userName;
                    modalCurrentPts.textContent = this.dataset.currentPoints;
                    ptsModal.style.display = 'flex';
                });
            });

            if (cancelPts) {
                cancelPts.addEventListener('click', function() {
                    ptsModal.style.display = 'none';
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Xử lý điều chỉnh điểm Biblio Xu cho khách
     */
    public function handle_adjust_customer_points() {
        if (!current_user_can('manage_biblio_customers') || !check_admin_referer('biblio_adjust_points_nonce', 'pts_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $user_id = absint($_POST['customer_id'] ?? 0);
        $amount  = (int) ($_POST['amount'] ?? 0);
        $desc    = sanitize_text_field(wp_unslash($_POST['description'] ?? 'Điều chỉnh bởi quản trị viên'));

        if (!$user_id || $amount === 0) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-customers', 'biblio_notice' => 'Số điểm điều chỉnh không hợp lệ.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        $core = $GLOBALS['nbook_core_instance'] ?? null;
        if ($core && method_exists($core, 'change_points')) {
            $ref = 'admin-manual-' . get_current_user_id() . '-' . wp_generate_uuid4();
            $core->change_points($user_id, $amount, 'admin-adjustment', $ref, $desc, false);
        }

        wp_safe_redirect(add_query_arg([
            'page'          => 'biblio-customers',
            'biblio_notice' => sprintf('Đã điều chỉnh %s%s Xu cho khách hàng.', ($amount > 0 ? '+' : ''), number_format_i18n($amount)),
            'biblio_type'   => 'success'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Xử lý Khóa / Mở khóa tài khoản khách hàng
     */
    public function handle_toggle_customer_lock() {
        $user_id = absint($_POST['customer_id'] ?? 0);
        if (!current_user_can('manage_biblio_customers') || !check_admin_referer('biblio_customer_lock_nonce_' . $user_id, 'lock_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $is_locked = get_user_meta($user_id, '_nbook_account_locked', true) === '1';
        if ($is_locked) {
            delete_user_meta($user_id, '_nbook_account_locked');
            $msg = 'Đã mở khóa tài khoản thành công.';
        } else {
            update_user_meta($user_id, '_nbook_account_locked', '1');
            $msg = 'Đã khóa tài khoản thành công. Người dùng sẽ bị chặn đăng nhập.';
        }

        wp_safe_redirect(add_query_arg([
            'page'          => 'biblio-customers',
            'biblio_notice' => $msg,
            'biblio_type'   => 'success'
        ], admin_url('admin.php')));
        exit;
    }

    // =========================================================================
    // 5. QUẢN LÝ NỘI DUNG & ĐÁNH GIÁ (CONTENT & REVIEWS)
    // =========================================================================
    public function render_content_page() {
        if (!current_user_can('manage_biblio_content') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang quản lý nội dung.');
        }

        $active_tab = sanitize_key($_GET['tab'] ?? 'reviews');

        ?>
        <div class="wrap biblio-admin-wrap">
            <div class="biblio-header-bar">
                <div>
                    <h1>📝 QUẢN LÝ NỘI DUNG & ĐÁNH GIÁ</h1>
                    <div class="biblio-header-tagline">Duyệt đánh giá sách, quản lý mã voucher, banner khuyến mãi, tin tức, FAQ chatbot và liên hệ</div>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <!-- Tabs Navigation -->
            <div class="biblio-tabs-nav">
                <a href="?page=biblio-content&tab=reviews" class="biblio-tab-btn <?php echo $active_tab === 'reviews' ? 'is-active' : ''; ?>">⭐ Đánh Giá Sách</a>
                <a href="?page=biblio-content&tab=vouchers" class="biblio-tab-btn <?php echo $active_tab === 'vouchers' ? 'is-active' : ''; ?>">🎟️ Mã Giảm Giá (Vouchers)</a>
                <a href="?page=biblio-content&tab=banners" class="biblio-tab-btn <?php echo $active_tab === 'banners' ? 'is-active' : ''; ?>">🖼️ Banners Khuyến Mãi</a>
                <a href="?page=biblio-content&tab=posts" class="biblio-tab-btn <?php echo $active_tab === 'posts' ? 'is-active' : ''; ?>">📰 Tin Tức & Sự Kiện</a>
                <a href="?page=biblio-content&tab=chatbot" class="biblio-tab-btn <?php echo $active_tab === 'chatbot' ? 'is-active' : ''; ?>">🤖 Chatbot FAQ</a>
                <a href="?page=biblio-content&tab=contacts" class="biblio-tab-btn <?php echo $active_tab === 'contacts' ? 'is-active' : ''; ?>">📬 Hộp Thư Liên Hệ</a>
            </div>

            <!-- Tab Content -->
            <div class="biblio-tab-body">
                <?php if ($active_tab === 'reviews'): ?>
                    <?php $this->render_tab_reviews(); ?>
                <?php elseif ($active_tab === 'vouchers'): ?>
                    <?php $this->render_tab_vouchers(); ?>
                <?php elseif ($active_tab === 'banners'): ?>
                    <?php $this->render_tab_banners(); ?>
                <?php elseif ($active_tab === 'posts'): ?>
                    <?php $this->render_tab_posts(); ?>
                <?php elseif ($active_tab === 'chatbot'): ?>
                    <?php $this->render_tab_chatbot(); ?>
                <?php elseif ($active_tab === 'contacts'): ?>
                    <?php $this->render_tab_contacts(); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tab 1: Đánh giá sách (Reviews)
     */
    private function render_tab_reviews() {
        $reviews = get_comments([
            'post_type' => 'product',
            'number'    => 30,
            'status'    => 'all',
        ]);

        ?>
        <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
            <table class="biblio-table">
                <thead>
                    <tr>
                        <th>Người đánh giá</th>
                        <th>Cuốn sách</th>
                        <th>Điểm sao</th>
                        <th>Nội dung nhận xét</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 24px; color: #888;">Chưa có đánh giá nào từ khách hàng.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): 
                            $rating = get_comment_meta($rev->comment_ID, 'rating', true) ?: 5;
                            $approved = $rev->comment_approved;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($rev->comment_author); ?></strong>
                                    <div style="font-size: 11px; color: #64748b;"><?php echo esc_html($rev->comment_author_email); ?></div>
                                </td>
                                <td>
                                    <strong><a href="<?php echo esc_url(get_permalink($rev->comment_post_ID)); ?>" target="_blank" style="color: #ee4d2d;">
                                        <?php echo esc_html(get_the_title($rev->comment_post_ID)); ?>
                                    </a></strong>
                                </td>
                                <td>
                                    <span style="color: #f59e0b; font-size: 13px;">
                                        <?php echo str_repeat('★', (int) $rating) . str_repeat('☆', 5 - (int) $rating); ?>
                                    </span>
                                </td>
                                <td style="max-width: 300px;">
                                    <div style="font-size: 12.5px; line-height: 1.4; color: #334155;">
                                        <?php echo nl2br(esc_html($rev->comment_content)); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($approved === '1'): ?>
                                        <span class="biblio-badge biblio-badge-completed">Đã duyệt</span>
                                    <?php elseif ($approved === '0'): ?>
                                        <span class="biblio-badge biblio-badge-pending">Chờ duyệt</span>
                                    <?php else: ?>
                                        <span class="biblio-badge biblio-badge-cancelled">Spam/Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px; color: #64748b;"><?php echo esc_html(mysql2date('d/m/Y H:i', $rev->comment_date)); ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                                        <input type="hidden" name="action" value="biblio_review_action">
                                        <input type="hidden" name="comment_id" value="<?php echo esc_attr($rev->comment_ID); ?>">
                                        <?php wp_nonce_field('biblio_review_action_nonce_' . $rev->comment_ID, 'rev_nonce'); ?>

                                        <?php if ($approved !== '1'): ?>
                                            <button type="submit" name="review_status" value="approve" class="button button-small" style="color: #16a34a;">
                                                ✓ Duyệt
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($approved === '1'): ?>
                                            <button type="submit" name="review_status" value="hold" class="button button-small" style="color: #b45309;">
                                                ✕ Ẩn
                                            </button>
                                        <?php endif; ?>

                                        <button type="submit" name="review_status" value="delete" class="button button-small button-link-delete" onclick="return confirm('Bạn có chắc muốn xóa vĩnh viễn nhận xét này?');">
                                            Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Xử lý duyệt / ẩn / xóa đánh giá
     */
    public function handle_review_action() {
        $cid = absint($_POST['comment_id'] ?? 0);
        if (!current_user_can('manage_biblio_content') || !check_admin_referer('biblio_review_action_nonce_' . $cid, 'rev_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $act = sanitize_key($_POST['review_status'] ?? '');
        if ($act === 'approve') {
            wp_set_comment_status($cid, 'approve');
            $msg = 'Đã duyệt đánh giá sách thành công.';
        } elseif ($act === 'hold') {
            wp_set_comment_status($cid, 'hold');
            $msg = 'Đã ẩn đánh giá khỏi trang sản phẩm.';
        } elseif ($act === 'delete') {
            wp_delete_comment($cid, true);
            $msg = 'Đã xóa đánh giá thành công.';
        }

        wp_safe_redirect(add_query_arg(['page' => 'biblio-content', 'tab' => 'reviews', 'biblio_notice' => $msg, 'biblio_type' => 'success'], admin_url('admin.php')));
        exit;
    }

    /**
     * Tab 2: Quản lý mã giảm giá (Vouchers)
     */
    private function render_tab_vouchers() {
        $coupons = get_posts([
            'post_type'      => 'shop_coupon',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        ?>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <!-- Form Tạo Nhanh Voucher -->
            <div style="flex: 1; min-width: 320px;" class="biblio-panel">
                <h3 style="margin-top: 0; color: #ee4d2d;">🎟️ Thêm Nhanh Mã Giảm Giá</h3>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="biblio_quick_add_voucher">
                    <?php wp_nonce_field('biblio_quick_voucher_nonce', 'vouch_nonce'); ?>

                    <p>
                        <label><strong>Mã voucher (viết liền, in hoa):</strong></label><br>
                        <input type="text" name="coupon_code" class="large-text" required placeholder="Ví dụ: SALE50K, BIBLIO2026" style="text-transform: uppercase;">
                    </p>
                    <p>
                        <label><strong>Loại giảm giá:</strong></label><br>
                        <select name="discount_type" class="large-text">
                            <option value="fixed_cart">Giảm tiền cố định (VNĐ)</option>
                            <option value="percent">Giảm phần trăm (%)</option>
                            <option value="shipping">Miễn phí vận chuyển (Freeship)</option>
                        </select>
                    </p>
                    <p>
                        <label><strong>Số tiền / % giảm:</strong></label><br>
                        <input type="number" name="coupon_amount" class="large-text" required placeholder="Ví dụ: 50000 hoặc 15">
                    </p>
                    <p>
                        <label><strong>Đơn hàng tối thiểu (VNĐ):</strong></label><br>
                        <input type="number" name="minimum_amount" class="large-text" value="0" placeholder="0 nếu không yêu cầu">
                    </p>
                    <p>
                        <label><strong>Hạn dùng (ngày hết hạn):</strong></label><br>
                        <input type="date" name="expiry_date" class="large-text">
                    </p>
                    <button type="submit" class="button biblio-btn-primary" style="width: 100%;">Tạo Mã Giảm Giá Ngay</button>
                </form>
            </div>

            <!-- Danh sách Vouchers -->
            <div style="flex: 2; min-width: 480px;" class="biblio-panel" style="padding: 0; overflow-x: auto;">
                <table class="biblio-table">
                    <thead>
                        <tr>
                            <th>Mã Voucher</th>
                            <th>Mức giảm</th>
                            <th>Đơn tối thiểu</th>
                            <th>Đã dùng</th>
                            <th>Hết hạn</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 24px; color: #888;">Chưa có mã giảm giá nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $cpost): 
                                $cp = new WC_Coupon($cpost->ID);
                                $type = $cp->get_discount_type();
                                $amt = $cp->get_amount();
                                $type_label = ($type === 'percent') ? $amt . '%' : wp_strip_all_tags(wc_price($amt));
                                $expires = $cp->get_date_expires();
                            ?>
                                <tr>
                                    <td><strong style="color: #ee4d2d; font-size: 13.5px;"><?php echo esc_html(strtoupper($cp->get_code())); ?></strong></td>
                                    <td><span class="biblio-badge biblio-badge-processing"><?php echo esc_html($type_label); ?></span></td>
                                    <td><?php echo $cp->get_minimum_amount() ? wp_strip_all_tags(wc_price($cp->get_minimum_amount())) : 'Mọi đơn'; ?></td>
                                    <td><?php echo (int) $cp->get_usage_count(); ?> lượt</td>
                                    <td style="font-size: 12px; color: #64748b;"><?php echo $expires ? esc_html($expires->date('d/m/Y')) : 'Vô thời hạn'; ?></td>
                                    <td style="text-align: right;">
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=biblio_delete_voucher&id=' . $cpost->ID), 'biblio_del_vouch_' . $cpost->ID)); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('Bạn có chắc muốn xóa mã giảm giá này?');">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Xử lý thêm nhanh voucher
     */
    public function handle_quick_add_voucher() {
        if (!current_user_can('manage_biblio_content') || !check_admin_referer('biblio_quick_voucher_nonce', 'vouch_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $code = strtoupper(sanitize_text_field($_POST['coupon_code'] ?? ''));
        $type = sanitize_key($_POST['discount_type'] ?? 'fixed_cart');
        $amount = (float) ($_POST['coupon_amount'] ?? 0);
        $min_spend = (float) ($_POST['minimum_amount'] ?? 0);
        $expiry = sanitize_text_field($_POST['expiry_date'] ?? '');

        if (empty($code) || $amount <= 0) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-content', 'tab' => 'vouchers', 'biblio_notice' => 'Vui lòng nhập đầy đủ mã và mức giảm giá.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        if (wc_get_coupon_id_by_code($code)) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-content', 'tab' => 'vouchers', 'biblio_notice' => 'Mã voucher này đã tồn tại.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        $id = wp_insert_post([
            'post_title'   => $code,
            'post_type'    => 'shop_coupon',
            'post_status'  => 'publish',
        ]);

        if ($id && !is_wp_error($id)) {
            $coupon = new WC_Coupon($id);
            if ($type === 'shipping') {
                $coupon->set_discount_type('fixed_cart');
                $coupon->set_amount(25000);
                $coupon->set_free_shipping(true);
            } else {
                $coupon->set_discount_type($type);
                $coupon->set_amount($amount);
            }
            if ($min_spend > 0) $coupon->set_minimum_amount($min_spend);
            if ($expiry) $coupon->set_date_expires(strtotime($expiry));
            $coupon->set_usage_limit(1000);
            $coupon->set_usage_limit_per_user(1);
            $coupon->save();
        }

        wp_safe_redirect(add_query_arg(['page' => 'biblio-content', 'tab' => 'vouchers', 'biblio_notice' => 'Đã tạo mã giảm giá ' . $code . ' thành công!', 'biblio_type' => 'success'], admin_url('admin.php')));
        exit;
    }

    /**
     * Xử lý xóa voucher
     */
    public function handle_delete_voucher() {
        $id = absint($_GET['id'] ?? 0);
        if (!current_user_can('manage_biblio_content') || !check_admin_referer('biblio_del_vouch_' . $id)) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        wp_delete_post($id, true);
        wp_safe_redirect(add_query_arg(['page' => 'biblio-content', 'tab' => 'vouchers', 'biblio_notice' => 'Đã xóa mã voucher thành công.', 'biblio_type' => 'success'], admin_url('admin.php')));
        exit;
    }

    /**
     * Tab 3: Banners Khuyến Mãi
     */
    private function render_tab_banners() {
        $banners = get_posts([
            'post_type'      => 'banner',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ]);

        ?>
        <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
            <div style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9;">
                <h3 style="margin: 0; color: #1e293b;">Danh sách Banner Khuyến Mãi Trang Chủ</h3>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=banner')); ?>" class="button biblio-btn-primary" target="_blank">+ Thêm Banner Mới</a>
            </div>
            <table class="biblio-table">
                <thead>
                    <tr>
                        <th>Tiêu đề Banner</th>
                        <th>Huy hiệu (Tag)</th>
                        <th>Nút CTA</th>
                        <th>Đường dẫn URL</th>
                        <th>Vị trí</th>
                        <th style="text-align: right;">Sửa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($banners)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 24px; color: #888;">Chưa có banner nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($banners as $b): 
                            $badge = get_post_meta($b->ID, '_nbook_banner_badge', true) ?: 'HOT';
                            $btn = get_post_meta($b->ID, '_nbook_banner_btn_text', true) ?: 'Khám Phá';
                            $url = get_post_meta($b->ID, '_nbook_banner_btn_url', true) ?: '/san-pham/';
                            $pos = get_post_meta($b->ID, '_nbook_banner_position', true) ?: 'main';
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($b->post_title); ?></strong></td>
                                <td><span class="biblio-badge biblio-badge-processing"><?php echo esc_html($badge); ?></span></td>
                                <td><?php echo esc_html($btn); ?></td>
                                <td><code><?php echo esc_html($url); ?></code></td>
                                <td><?php echo $pos === 'main' ? 'Banner chính' : 'Banner phụ cột phải'; ?></td>
                                <td style="text-align: right;">
                                    <a href="<?php echo esc_url(get_edit_post_link($b->ID)); ?>" class="button button-small" target="_blank">Chỉnh sửa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Tab 4: Tin tức & Sự kiện
     */
    private function render_tab_posts() {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        ?>
        <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
            <div style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9;">
                <h3 style="margin: 0; color: #1e293b;">Tin Tức, Sự Kiện & Blog Sách</h3>
                <a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="button biblio-btn-primary" target="_blank">+ Viết Bài Tin Mới</a>
            </div>
            <table class="biblio-table">
                <thead>
                    <tr>
                        <th>Tiêu đề bài viết</th>
                        <th>Chuyên mục</th>
                        <th>Tác giả</th>
                        <th>Ngày xuất bản</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): 
                        $cats = wp_get_post_categories($p->ID, ['fields' => 'names']);
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($p->post_title); ?></strong></td>
                            <td><?php echo esc_html(implode(', ', $cats) ?: 'Tin tức'); ?></td>
                            <td><?php echo esc_html(get_the_author_meta('display_name', $p->post_author)); ?></td>
                            <td style="font-size: 12px; color: #64748b;"><?php echo esc_html(mysql2date('d/m/Y', $p->post_date)); ?></td>
                            <td style="text-align: right;">
                                <a href="<?php echo esc_url(get_permalink($p->ID)); ?>" class="button button-small" target="_blank">Xem</a>
                                <a href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>" class="button button-small" target="_blank">Sửa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Tab 5: Chatbot FAQ
     */
    private function render_tab_chatbot() {
        global $wpdb;
        $faqs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nbook_chatbot_faq ORDER BY priority DESC, id DESC LIMIT 20");

        ?>
        <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
            <div style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9;">
                <h3 style="margin: 0; color: #1e293b;">Ngân Hàng Câu Hỏi Tự Động Trợ Lý Ảo Chatbot</h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=nbook-chatbot&tab=faq')); ?>" class="button biblio-btn-primary" target="_blank">
                    Quản Trị Chi Tiết Chatbot &rarr;
                </a>
            </div>
            <table class="biblio-table">
                <thead>
                    <tr>
                        <th>Câu hỏi</th>
                        <th>Từ khóa nhận diện</th>
                        <th>Ưu tiên</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faqs as $f): ?>
                        <tr>
                            <td><strong><?php echo esc_html($f->question); ?></strong></td>
                            <td><code><?php echo esc_html($f->keywords); ?></code></td>
                            <td><?php echo esc_html($f->priority); ?></td>
                            <td>
                                <?php if ($f->is_active): ?>
                                    <span class="biblio-badge biblio-badge-completed">Đang bật</span>
                                <?php else: ?>
                                    <span class="biblio-badge biblio-badge-cancelled">Đã tắt</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Tab 6: Hộp Thư Liên Hệ (Contacts)
     */
    private function render_tab_contacts() {
        global $wpdb;
        $table = $wpdb->prefix . 'nbook_contacts';
        $contacts = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 30");

        ?>
        <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
            <table class="biblio-table">
                <thead>
                    <tr>
                        <th>Khách liên hệ</th>
                        <th>Tiêu đề / Học phần</th>
                        <th>Nội dung tin nhắn</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 24px; color: #888;">Chưa có thư liên hệ nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $ct): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($ct->name); ?></strong>
                                    <div style="font-size: 11px; color: #64748b;"><?php echo esc_html($ct->email); ?> | <?php echo esc_html($ct->phone); ?></div>
                                </td>
                                <td><strong><?php echo esc_html($ct->subject); ?></strong></td>
                                <td style="max-width: 320px;">
                                    <div style="font-size: 12px; line-height: 1.4; color: #334155;">
                                        <?php echo nl2br(esc_html($ct->message)); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($ct->is_read): ?>
                                        <span class="biblio-badge biblio-badge-completed">Đã xử lý</span>
                                    <?php else: ?>
                                        <span class="biblio-badge biblio-badge-pending">Chưa xử lý</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px; color: #64748b;"><?php echo esc_html(mysql2date('d/m/Y H:i', $ct->created_at)); ?></td>
                                <td style="text-align: right;">
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="biblio_mark_contact_read">
                                        <input type="hidden" name="contact_id" value="<?php echo esc_attr($ct->id); ?>">
                                        <?php wp_nonce_field('biblio_contact_nonce_' . $ct->id, 'ct_nonce'); ?>
                                        <button type="submit" class="button button-small">
                                            <?php echo $ct->is_read ? 'Đánh dấu chưa đọc' : 'Đã xử lý ✓'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Xử lý đánh dấu liên hệ đã đọc
     */
    public function handle_mark_contact_read() {
        $id = absint($_POST['contact_id'] ?? 0);
        if (!current_user_can('manage_biblio_content') || !check_admin_referer('biblio_contact_nonce_' . $id, 'ct_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'nbook_contacts';
        $curr = $wpdb->get_var($wpdb->prepare("SELECT is_read FROM {$table} WHERE id = %d", $id));
        $new_val = $curr ? 0 : 1;
        $wpdb->update($table, ['is_read' => $new_val], ['id' => $id], ['%d'], ['%d']);

        wp_safe_redirect(add_query_arg(['page' => 'biblio-content', 'tab' => 'contacts', 'biblio_notice' => 'Đã cập nhật trạng thái liên hệ.', 'biblio_type' => 'success'], admin_url('admin.php')));
        exit;
    }

    // =========================================================================
    // 6. CÀI ĐẶT CHUNG (GENERAL SETTINGS)
    // =========================================================================
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Chỉ Quản trị viên cấp cao mới có quyền truy cập Cài Đặt Chung.');
        }

        ?>
        <div class="wrap biblio-admin-wrap">
            <div class="biblio-header-bar">
                <div>
                    <h1>⚙️ CÀI ĐẶT CHUNG SÀN BIBLIO</h1>
                    <div class="biblio-header-tagline">Quản lý đường dây nóng, email, mạng xã hội, ngưỡng freeship và hệ thống điểm thưởng Biblio Xu</div>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <div class="biblio-panel" style="max-width: 860px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="biblio_save_general_settings">
                    <?php wp_nonce_field('biblio_general_settings_nonce', 'settings_nonce'); ?>

                    <h2 style="color: #ee4d2d; font-size: 16px; border-bottom: 2px solid #fff5f2; padding-bottom: 8px;">1. Thông Tin Thương Hiệu & Chăm Sóc Khách Hàng</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="nbook_top_announcement">Dòng thông báo Header:</label></th>
                            <td>
                                <input type="text" id="nbook_top_announcement" name="nbook_top_announcement" value="<?php echo esc_attr(get_option('nbook_top_announcement', 'Miễn phí vận chuyển đơn từ 299.000đ')); ?>" class="large-text">
                                <p class="description">Hiển thị trên thanh màu đen/cam trên cùng của website.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_hotline">Số Hotline CSKH:</label></th>
                            <td>
                                <input type="text" id="nbook_hotline" name="nbook_hotline" value="<?php echo esc_attr(get_option('nbook_hotline', '0933 109 009')); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_zalo">Số / Link Zalo hỗ trợ:</label></th>
                            <td>
                                <input type="text" id="nbook_zalo" name="nbook_zalo" value="<?php echo esc_attr(get_option('nbook_zalo', '0933 109 009')); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_email">Email CSKH:</label></th>
                            <td>
                                <input type="email" id="nbook_email" name="nbook_email" value="<?php echo esc_attr(get_option('nbook_email', 'cskh@biblio.vn')); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_address">Địa chỉ nhà sách:</label></th>
                            <td>
                                <input type="text" id="nbook_address" name="nbook_address" value="<?php echo esc_attr(get_option('nbook_address', '207 Giải Phóng, Phường Bạch Mai, TP. Hà Nội (Đại học Kinh tế Quốc dân NEU)')); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>

                    <h2 style="color: #ee4d2d; font-size: 16px; border-bottom: 2px solid #fff5f2; padding-bottom: 8px; margin-top: 30px;">2. Kênh Mạng Xã Hội</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="nbook_social_facebook">Facebook Fanpage URL:</label></th>
                            <td>
                                <input type="url" id="nbook_social_facebook" name="nbook_social_facebook" value="<?php echo esc_attr(get_option('nbook_social_facebook', 'https://facebook.com/biblio.vn')); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_social_tiktok">Kênh TikTok URL:</label></th>
                            <td>
                                <input type="url" id="nbook_social_tiktok" name="nbook_social_tiktok" value="<?php echo esc_attr(get_option('nbook_social_tiktok', 'https://tiktok.com/@biblio.vn')); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_social_youtube">Kênh YouTube / Instagram:</label></th>
                            <td>
                                <input type="url" id="nbook_social_youtube" name="nbook_social_youtube" value="<?php echo esc_attr(get_option('nbook_social_youtube', 'https://youtube.com/@biblio.vn')); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>

                    <h2 style="color: #ee4d2d; font-size: 16px; border-bottom: 2px solid #fff5f2; padding-bottom: 8px; margin-top: 30px;">3. Ngưỡng Vận Chuyển & Thời Gian Giao Hàng</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="nbook_freeship_threshold">Ngưỡng Miễn Phí Vận Chuyển (Freeship):</label></th>
                            <td>
                                <input type="number" id="nbook_freeship_threshold" name="nbook_freeship_threshold" value="<?php echo esc_attr(get_option('nbook_freeship_threshold', 299000)); ?>" class="regular-text"> VNĐ
                                <p class="description">Đơn hàng đạt giá trị từ mức này trở lên sẽ tự động được miễn phí vận chuyển.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_delivery_time">Cam kết thời gian giao hàng:</label></th>
                            <td>
                                <input type="text" id="nbook_delivery_time" name="nbook_delivery_time" value="<?php echo esc_attr(get_option('nbook_delivery_time', 'Hỏa tốc 2h nội thành Hà Nội, 1 - 3 ngày toàn quốc')); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>

                    <h2 style="color: #ee4d2d; font-size: 16px; border-bottom: 2px solid #fff5f2; padding-bottom: 8px; margin-top: 30px;">4. Quy Tắc Điểm Thưởng Biblio Xu</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="nbook_points_per_vnd">Quy đổi mua hàng tích Xu:</label></th>
                            <td>
                                <input type="number" id="nbook_points_per_vnd" name="nbook_points_per_vnd" value="<?php echo esc_attr(get_option('nbook_points_per_vnd', 1000)); ?>" class="small-text"> VNĐ thanh toán = 1 Biblio Xu
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_points_signup">Thưởng đăng ký tài khoản:</label></th>
                            <td>
                                <input type="number" id="nbook_points_signup" name="nbook_points_signup" value="<?php echo esc_attr(get_option('nbook_points_signup', 100)); ?>" class="small-text"> Xu
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_points_review">Thưởng viết đánh giá sách:</label></th>
                            <td>
                                <input type="number" id="nbook_points_review" name="nbook_points_review" value="<?php echo esc_attr(get_option('nbook_points_review', 50)); ?>" class="small-text"> Xu
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_points_checkin">Thưởng điểm danh hằng ngày:</label></th>
                            <td>
                                <input type="number" id="nbook_points_checkin" name="nbook_points_checkin" value="<?php echo esc_attr(get_option('nbook_points_checkin', 100)); ?>" class="small-text"> Xu / ngày
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_points_expiry_months">Thời hạn hiệu lực của Xu:</label></th>
                            <td>
                                <input type="number" id="nbook_points_expiry_months" name="nbook_points_expiry_months" value="<?php echo esc_attr(get_option('nbook_points_expiry_months', 12)); ?>" class="small-text"> tháng
                            </td>
                        </tr>
                    </table>

                    <h2 style="color: #ee4d2d; font-size: 16px; border-bottom: 2px solid #fff5f2; padding-bottom: 8px; margin-top: 30px;">5. Tích Hợp Đăng Nhập Bằng Google (Google Sign-In)</h2>
                    <div style="background: #f8fafc; border-left: 4px solid #4285f4; padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; font-size: 13px; color: #334155; line-height: 1.6;">
                        <strong><i class="dashicons dashicons-google" style="color: #4285f4; vertical-align: middle;"></i> Hướng dẫn kết nối Google Cloud:</strong><br>
                        1. Truy cập <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color: #0284c7; font-weight: 600;">Google Cloud Console &rarr; Credentials</a> và tạo <em>OAuth 2.0 Client ID</em> (loại <strong>Web application</strong>).<br>
                        2. Thêm vào mục <strong>Authorized JavaScript origins</strong>: <code><?php echo esc_html(home_url()); ?></code><br>
                        3. Thêm vào mục <strong>Authorized redirect URIs</strong>: <code><?php echo esc_html(home_url('/tai-khoan/')); ?></code><br>
                        4. Dán <strong>Client ID</strong> nhận được vào ô bên dưới. Khách hàng sẽ có thể đăng nhập bằng tài khoản Google chỉ với 1 click!
                    </div>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="nbook_google_login_enabled">Kích hoạt đăng nhập Google:</label></th>
                            <td>
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                                    <input type="checkbox" id="nbook_google_login_enabled" name="nbook_google_login_enabled" value="1" <?php checked(get_option('nbook_google_login_enabled', 1), 1); ?>>
                                    Bật tính năng "Đăng nhập với Google" trên toàn bộ website
                                </label>
                                <p class="description">Hiển thị nút Đăng nhập với Google trong Modal Đăng nhập / Đăng ký và trang Tài khoản WooCommerce.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nbook_google_client_id">Google OAuth Client ID:</label></th>
                            <td>
                                <input type="text" id="nbook_google_client_id" name="nbook_google_client_id" value="<?php echo esc_attr(get_option('nbook_google_client_id', '')); ?>" class="large-text" placeholder="Ví dụ: 1234567890-abcdefghij.apps.googleusercontent.com">
                                <p class="description">Chuỗi Client ID định dạng <code>*.apps.googleusercontent.com</code> từ dự án Google Cloud của bạn.</p>
                            </td>
                        </tr>
                    </table>

                    <p style="margin-top: 24px;">
                        <button type="submit" class="button biblio-btn-primary" style="padding: 8px 24px !important; font-size: 14px !important;">
                            LƯU TẤT CẢ CÀI ĐẶT BIBLIO
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Xử lý lưu cài đặt chung
     */
    public function handle_save_general_settings() {
        if (!current_user_can('manage_options') || !check_admin_referer('biblio_general_settings_nonce', 'settings_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $fields = [
            'nbook_top_announcement'     => 'sanitize_text_field',
            'nbook_hotline'              => 'sanitize_text_field',
            'nbook_zalo'                 => 'sanitize_text_field',
            'nbook_email'                => 'sanitize_email',
            'nbook_address'              => 'sanitize_text_field',
            'nbook_social_facebook'      => 'esc_url_raw',
            'nbook_social_tiktok'        => 'esc_url_raw',
            'nbook_social_youtube'       => 'esc_url_raw',
            'nbook_delivery_time'        => 'sanitize_text_field',
            'nbook_freeship_threshold'   => 'absint',
            'nbook_points_per_vnd'       => 'absint',
            'nbook_points_signup'        => 'absint',
            'nbook_points_review'        => 'absint',
            'nbook_points_checkin'       => 'absint',
            'nbook_points_expiry_months' => 'absint',
            'nbook_google_client_id'     => 'sanitize_text_field',
        ];

        // Handle checkboxes
        update_option('nbook_google_login_enabled', !empty($_POST['nbook_google_login_enabled']) ? 1 : 0);

        foreach ($fields as $field => $sanitizer) {
            if (isset($_POST[$field])) {
                $val = call_user_func($sanitizer, wp_unslash($_POST[$field]));
                update_option($field, $val);
            }
        }

        wp_safe_redirect(add_query_arg(['page' => 'biblio-general-settings', 'biblio_notice' => 'Đã lưu cài đặt chung thành công!', 'biblio_type' => 'success'], admin_url('admin.php')));
        exit;
    }

    // =========================================================================
    // 7. PHÂN QUYỀN NHÂN VIÊN CỬA HÀNG (STAFF & ROLES)
    // =========================================================================
    public function render_roles_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Chỉ Quản trị viên cấp cao mới có quyền phân quyền nhân viên.');
        }

        // Lấy danh sách nhân viên
        $staff_users = get_users([
            'role__in' => ['biblio_staff', 'shop_manager', 'administrator'],
            'orderby'  => 'registered',
            'order'    => 'ASC',
        ]);

        $all_users = get_users(['number' => 100, 'role__not_in' => ['administrator']]);

        ?>
        <div class="wrap biblio-admin-wrap">
            <div class="biblio-header-bar">
                <div>
                    <h1>🔐 PHÂN QUYỀN NHÂN VIÊN CỬA HÀNG</h1>
                    <div class="biblio-header-tagline">Quản lý tài khoản và phân quyền vai trò "Nhân viên cửa hàng" (Biblio Staff)</div>
                </div>
            </div>

            <?php $this->render_notices(); ?>

            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                <!-- Cột Trái: Bảng Ma Trận Quyền Hạn -->
                <div style="flex: 2; min-width: 480px;">
                    <div class="biblio-panel" style="margin-bottom: 24px; padding: 0; overflow-x: auto;">
                        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                            <h3 style="margin: 0; color: #1e293b;">📋 Ma Trận Phân Quyền Vai Trò Hệ Thống</h3>
                        </div>
                        <table class="biblio-table">
                            <thead>
                                <tr>
                                    <th>Chức năng quản trị</th>
                                    <th style="text-align: center;">Quản trị viên<br>(Administrator)</th>
                                    <th style="text-align: center;">Quản lý cửa hàng<br>(Shop Manager)</th>
                                    <th style="text-align: center; color: #ee4d2d;">Nhân viên cửa hàng<br>(Biblio Staff)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>1. Tổng quan (Dashboard & Báo cáo)</strong></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓ Xem báo cáo</td>
                                </tr>
                                <tr>
                                    <td><strong>2. Quản lý đơn hàng (Đổi trạng thái, thông báo)</strong></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓ Xử lý đơn</td>
                                </tr>
                                <tr>
                                    <td><strong>3. Quản lý sách & tồn kho (Sửa giá, CSV)</strong></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓ Cập nhật kho</td>
                                </tr>
                                <tr>
                                    <td><strong>4. Quản lý khách hàng & Xu (Xem, chỉnh Xu)</strong></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓ Hỗ trợ điểm</td>
                                </tr>
                                <tr>
                                    <td><strong>5. Quản lý nội dung (Duyệt đánh giá, FAQ)</strong></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓ Duyệt bài</td>
                                </tr>
                                <tr>
                                    <td><strong>6. Cài đặt chung sàn Biblio</strong></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #dc2626; font-weight: bold;">✕ Không có quyền</td>
                                    <td style="text-align: center; color: #dc2626; font-weight: bold;">✕ Khóa</td>
                                </tr>
                                <tr>
                                    <td><strong>7. Phân quyền & Tạo tài khoản nhân viên</strong></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: bold;">✓</td>
                                    <td style="text-align: center; color: #dc2626; font-weight: bold;">✕ Không có quyền</td>
                                    <td style="text-align: center; color: #dc2626; font-weight: bold;">✕ Khóa</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Danh Sách Nhân Viên Hiện Tại -->
                    <div class="biblio-panel" style="padding: 0; overflow-x: auto;">
                        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                            <h3 style="margin: 0; color: #1e293b;">👥 Danh Sách Tài Khoản Nhân Viên & Quản Trị</h3>
                        </div>
                        <table class="biblio-table">
                            <thead>
                                <tr>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                    <th>Ngày tạo</th>
                                    <th style="text-align: right;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff_users as $su): 
                                    $role = $su->roles[0] ?? '';
                                    $role_badge = ($role === 'administrator') ? ['Quản trị viên', 'biblio-badge-cancelled'] : (($role === 'shop_manager') ? ['Quản lý cửa hàng', 'biblio-badge-on-hold'] : ['Nhân viên cửa hàng', 'biblio-badge-processing']);
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($su->display_name); ?></strong></td>
                                        <td><?php echo esc_html($su->user_email); ?></td>
                                        <td><span class="biblio-badge <?php echo esc_attr($role_badge[1]); ?>"><?php echo esc_html($role_badge[0]); ?></span></td>
                                        <td style="font-size: 12px; color: #64748b;"><?php echo esc_html(mysql2date('d/m/Y', $su->user_registered)); ?></td>
                                        <td style="text-align: right;">
                                            <?php if ($role === 'biblio_staff'): ?>
                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                                                    <input type="hidden" name="action" value="biblio_assign_staff_role">
                                                    <input type="hidden" name="user_id" value="<?php echo esc_attr($su->ID); ?>">
                                                    <input type="hidden" name="new_role" value="customer">
                                                    <?php wp_nonce_field('biblio_assign_role_nonce', 'role_nonce'); ?>
                                                    <button type="submit" class="button button-small" style="color: #dc2626;" onclick="return confirm('Thu hồi quyền nhân viên của tài khoản này?');">
                                                        Thu hồi quyền
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cột Phải: Form Cấp Quyền Nhân Viên Mới -->
                <div style="flex: 1; min-width: 320px;">
                    <div class="biblio-panel">
                        <h3 style="margin-top: 0; color: #ee4d2d;">➕ Cấp Quyền Nhân Viên Cửa Hàng</h3>
                        <p style="font-size: 13px; color: #64748b;">Chọn một tài khoản người dùng có sẵn để chỉ định làm <strong>Nhân viên cửa hàng Biblio</strong>:</p>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="biblio_assign_staff_role">
                            <input type="hidden" name="new_role" value="biblio_staff">
                            <?php wp_nonce_field('biblio_assign_role_nonce', 'role_nonce'); ?>

                            <p>
                                <label><strong>Chọn người dùng:</strong></label><br>
                                <select name="user_id" class="large-text" required style="margin-top: 4px;">
                                    <option value="">-- Chọn tài khoản --</option>
                                    <?php foreach ($all_users as $au): ?>
                                        <option value="<?php echo esc_attr($au->ID); ?>">
                                            <?php echo esc_html($au->display_name); ?> (<?php echo esc_html($au->user_email); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <div style="background: #fff5f2; border: 1px solid #ffdcd2; border-radius: 8px; padding: 12px; margin: 16px 0; font-size: 12.5px; color: #555;">
                                <strong>Quyền hạn được cấp:</strong>
                                <ul style="margin: 6px 0 0 16px; list-style: disc;">
                                    <li>Xem bảng điều khiển doanh số & đơn hàng</li>
                                    <li>Cập nhật trạng thái đơn & gửi thông báo</li>
                                    <li>Chỉnh sửa giá và tồn kho sách, xuất CSV</li>
                                    <li>Duyệt đánh giá và hỗ trợ điểm thưởng</li>
                                    <li><em>Không được vào Cài đặt chung & Phân quyền</em></li>
                                </ul>
                            </div>

                            <button type="submit" class="button biblio-btn-primary" style="width: 100%;">Cấp Quyền Nhân Viên Cửa Hàng</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Xử lý gán / thu hồi quyền nhân viên
     */
    public function handle_assign_staff_role() {
        if (!current_user_can('manage_options') || !check_admin_referer('biblio_assign_role_nonce', 'role_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        $user_id = absint($_POST['user_id'] ?? 0);
        $new_role = sanitize_key($_POST['new_role'] ?? 'biblio_staff');

        if (!$user_id) {
            wp_safe_redirect(add_query_arg(['page' => 'biblio-roles', 'biblio_notice' => 'Vui lòng chọn người dùng.', 'biblio_type' => 'error'], admin_url('admin.php')));
            exit;
        }

        $user = get_userdata($user_id);
        if ($user) {
            // Không cho phép hạ quyền administrator qua form này
            if (in_array('administrator', $user->roles, true)) {
                wp_die('Không thể thay đổi quyền của Quản trị viên cấp cao.');
            }

            $user->set_role($new_role);
            $msg = ($new_role === 'biblio_staff') ? "Đã cấp vai trò Nhân viên cửa hàng cho tài khoản {$user->display_name}." : "Đã thu hồi quyền nhân viên của tài khoản {$user->display_name}.";
        }

        wp_safe_redirect(add_query_arg(['page' => 'biblio-roles', 'biblio_notice' => $msg, 'biblio_type' => 'success'], admin_url('admin.php')));
        exit;
    }
}
