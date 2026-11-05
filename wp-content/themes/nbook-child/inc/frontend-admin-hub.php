<?php
/**
 * Biblio Frontend Admin Hub - Core Engine
 * 
 * Provides backend logic, metric calculations, and AJAX endpoints for:
 * 1. Revenue & Order Reporting
 * 2. Order Approval & Status Transition (Pending -> Processing -> Shipping -> Completed -> Cancelled)
 * 3. Book / Product Management (Add, Edit, Inline Stock/Price, Delete)
 * 4. News & Event Publishing (Add, Delete, View)
 * 5. Customer Management
 * 
 * @package Nbook_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class Biblio_Frontend_Admin_Hub {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // AJAX Endpoints
        add_action('wp_ajax_nb_admin_update_order_status', [$this, 'ajax_update_order_status']);
        add_action('wp_ajax_nb_admin_get_order_details', [$this, 'ajax_get_order_details']);
        add_action('wp_ajax_nb_admin_quick_save_book', [$this, 'ajax_quick_save_book']);
        add_action('wp_ajax_nb_admin_quick_update_stock_price', [$this, 'ajax_quick_update_stock_price']);
        add_action('wp_ajax_nb_admin_delete_book', [$this, 'ajax_delete_book']);
        add_action('wp_ajax_nb_admin_quick_save_post', [$this, 'ajax_quick_save_post']);
        add_action('wp_ajax_nb_admin_delete_post', [$this, 'ajax_delete_post']);

        // Handle view mode cookie setting via GET
        add_action('init', [$this, 'handle_view_mode_toggle']);
    }

    /**
     * Check if user has admin/management privileges
     */
    public static function is_admin_user() {
        if (!is_user_logged_in()) {
            return false;
        }
        return current_user_can('manage_options') 
            || current_user_can('manage_woocommerce') 
            || current_user_can('manage_biblio_orders')
            || current_user_can('biblio_staff_access');
    }

    /**
     * Check if admin is currently in Customer View mode
     */
    public static function is_viewing_as_customer() {
        if (!self::is_admin_user()) {
            return false;
        }
        if (isset($_GET['view_as'])) {
            return sanitize_text_field($_GET['view_as']) === 'customer';
        }
        if (isset($_COOKIE['nb_admin_view_as'])) {
            return sanitize_text_field($_COOKIE['nb_admin_view_as']) === 'customer';
        }
        return false;
    }

    /**
     * Handle toggle query parameter
     */
    public function handle_view_mode_toggle() {
        if (isset($_GET['view_as']) && self::is_admin_user()) {
            $mode = sanitize_text_field($_GET['view_as']);
            if ($mode === 'customer') {
                setcookie('nb_admin_view_as', 'customer', time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
                $_COOKIE['nb_admin_view_as'] = 'customer';
            } elseif ($mode === 'admin') {
                setcookie('nb_admin_view_as', 'admin', time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
                $_COOKIE['nb_admin_view_as'] = 'admin';
            }
        }
    }

    /**
     * Calculate revenue within a date/time range
     */
    public static function calculate_revenue($from, $to) {
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
     * Get 7 days revenue data for Canvas Chart
     */
    public static function get_chart_revenue_7days() {
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $ts = strtotime("-{$i} days", current_time('timestamp'));
            $start = date('Y-m-d 00:00:00', $ts);
            $end = date('Y-m-d 23:59:59', $ts);
            $labels[] = date('d/m', $ts);
            $values[] = self::calculate_revenue($start, $end);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get aggregate metrics for Admin Hub
     */
    public static function get_hub_metrics() {
        $now = current_time('mysql');
        $today_start = current_time('Y-m-d 00:00:00');
        $seven_days_ago = date('Y-m-d 00:00:00', strtotime('-6 days', current_time('timestamp')));
        $thirty_days_ago = date('Y-m-d 00:00:00', strtotime('-29 days', current_time('timestamp')));

        $rev_today = self::calculate_revenue($today_start, $now);
        $rev_7d    = self::calculate_revenue($seven_days_ago, $now);
        $rev_30d   = self::calculate_revenue($thirty_days_ago, $now);

        // Order counts
        $all_statuses     = wc_get_order_statuses();
        $pending_count    = wc_orders_count('pending');
        $processing_count = wc_orders_count('processing');
        $onhold_count     = wc_orders_count('on-hold');
        $shipping_count   = array_key_exists('wc-shipping', $all_statuses) ? wc_orders_count('shipping') : 0;
        $completed_count  = wc_orders_count('completed');
        $cancelled_count  = wc_orders_count('cancelled') + wc_orders_count('refunded') + wc_orders_count('failed');
        $total_orders     = $pending_count + $processing_count + $onhold_count + $shipping_count + $completed_count + $cancelled_count;

        // Pending approval count (orders waiting for admin action)
        $approval_needed_count = $pending_count + $processing_count;

        // Low stock count (<= 5) - Fast SQL query for instant performance
        global $wpdb;
        $low_stock_count = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE (meta_key = '_stock' AND CAST(meta_value AS SIGNED) <= 5) 
               OR (meta_key = '_stock_status' AND meta_value = 'outofstock')
        ");

        // Customers count
        $customer_query = new WP_User_Query([
            'role__in' => ['customer', 'subscriber'],
            'count_total' => true,
        ]);
        $total_customers = $customer_query->get_total();

        return [
            'rev_today'             => $rev_today,
            'rev_7d'                => $rev_7d,
            'rev_30d'               => $rev_30d,
            'pending_count'         => $pending_count,
            'processing_count'      => $processing_count,
            'onhold_count'          => $onhold_count,
            'shipping_count'        => $shipping_count,
            'completed_count'       => $completed_count,
            'cancelled_count'       => $cancelled_count,
            'total_orders'          => $total_orders,
            'approval_needed_count' => $approval_needed_count,
            'low_stock_count'       => $low_stock_count,
            'total_customers'       => $total_customers,
            'chart_data'            => self::get_chart_revenue_7days(),
        ];
    }

    /**
     * AJAX: Update Order Status (Tiếp nhận / Duyệt đơn / Hoàn thành / Hủy)
     */
    public function ajax_update_order_status() {
        check_ajax_referer('nbook_admin_hub_nonce', 'nonce');

        if (!self::is_admin_user()) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';

        if (!$order_id || !$new_status) {
            wp_send_json_error(['message' => 'Dữ liệu yêu cầu không hợp lệ.']);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Không tìm thấy đơn hàng #' . $order_id]);
        }

        $allowed_statuses = ['pending', 'processing', 'on-hold', 'shipping', 'completed', 'cancelled'];
        if (!in_array($new_status, $allowed_statuses, true)) {
            wp_send_json_error(['message' => 'Trạng thái đơn hàng không hợp lệ.']);
        }

        $current_user = wp_get_current_user();
        $admin_name = $current_user->display_name ?: 'Quản trị viên';

        $status_labels = [
            'pending'    => 'Chờ tiếp nhận',
            'processing' => 'Đang chuẩn bị hàng',
            'on-hold'    => 'Đang giao hàng',
            'shipping'   => 'Đang giao hàng',
            'completed'  => 'Đã hoàn thành',
            'cancelled'  => 'Đã hủy',
        ];

        $status_badges = [
            'pending'    => 'nb-badge-pending',
            'processing' => 'nb-badge-processing',
            'on-hold'    => 'nb-badge-shipping',
            'shipping'   => 'nb-badge-shipping',
            'completed'  => 'nb-badge-completed',
            'cancelled'  => 'nb-badge-cancelled',
        ];

        $note = sprintf('Cập nhật trạng thái sang "%s" bởi %s qua Trung tâm Quản trị Frontend.', $status_labels[$new_status] ?? $new_status, $admin_name);

        $order->update_status($new_status, $note);

        $metrics = self::get_hub_metrics();

        wp_send_json_success([
            'order_id'      => $order_id,
            'new_status'    => $new_status,
            'status_label'  => $status_labels[$new_status] ?? $new_status,
            'badge_class'   => $status_badges[$new_status] ?? 'nb-badge-neutral',
            'message'       => sprintf('Đơn hàng #%d đã được chuyển sang "%s" thành công!', $order_id, $status_labels[$new_status] ?? $new_status),
            'metrics'       => $metrics,
        ]);
    }

    /**
     * AJAX: Get Order Details for Modal
     */
    public function ajax_get_order_details() {
        check_ajax_referer('nbook_admin_hub_nonce', 'nonce');

        if (!self::is_admin_user()) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Không tìm thấy đơn hàng #' . $order_id]);
        }

        $status_labels = [
            'pending'    => 'Chờ tiếp nhận',
            'processing' => 'Đang chuẩn bị hàng',
            'on-hold'    => 'Đang giao hàng',
            'shipping'   => 'Đang giao hàng',
            'completed'  => 'Đã hoàn thành',
            'cancelled'  => 'Đã hủy',
            'refunded'   => 'Đã hoàn tiền',
            'failed'     => 'Thất bại',
        ];

        $status_badges = [
            'pending'    => 'nb-badge-pending',
            'processing' => 'nb-badge-processing',
            'on-hold'    => 'nb-badge-shipping',
            'shipping'   => 'nb-badge-shipping',
            'completed'  => 'nb-badge-completed',
            'cancelled'  => 'nb-badge-cancelled',
            'refunded'   => 'nb-badge-cancelled',
            'failed'     => 'nb-badge-cancelled',
        ];

        $items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $items[] = [
                'name'      => $item->get_name(),
                'qty'       => $item->get_quantity(),
                'total'     => wc_price($item->get_total()),
                'image'     => $product ? ($product->get_image('thumbnail') ?: '<img src="' . esc_url(wc_placeholder_img_src()) . '" alt="">') : '',
                'author'    => $product ? (get_post_meta($product->get_id(), '_biblio_author', true) ?: get_post_meta($product->get_id(), '_nbook_author', true) ?: 'Nhiều tác giả') : '',
                'link'      => $product ? get_permalink($product->get_id()) : '#',
            ];
        }

        $customer_name  = trim($order->get_formatted_billing_full_name());
        if (empty($customer_name)) {
            $customer_name = 'Khách vãng lai';
        }
        $customer_phone = $order->get_billing_phone() ?: 'Chưa cập nhật';
        $customer_email = $order->get_billing_email() ?: 'Chưa cập nhật';
        $shipping_addr  = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
        if (empty($shipping_addr)) {
            $shipping_addr = 'Tại cửa hàng / Không có địa chỉ giao hàng';
        }
        $customer_note  = $order->get_customer_note() ?: 'Không có ghi chú từ khách';
        $payment_method = $order->get_payment_method_title() ?: 'Thanh toán khi nhận hàng (COD)';

        ob_start();
        ?>
        <div class="nb-aod-modal-body">
            <div class="nb-aod-header-strip">
                <div class="nb-aod-title-wrap">
                    <h3>Đơn Hàng #<?php echo esc_html($order_id); ?></h3>
                    <span class="nb-status-badge <?php echo esc_attr($status_badges[$order->get_status()] ?? 'nb-badge-neutral'); ?>">
                        <?php echo esc_html($status_labels[$order->get_status()] ?? $order->get_status()); ?>
                    </span>
                </div>
                <div class="nb-aod-date-wrap">
                    <i class="bi bi-clock"></i> Đặt lúc: <?php echo esc_html($order->get_date_created() ? $order->get_date_created()->date_i18n('H:i - d/m/Y') : ''); ?>
                </div>
            </div>

            <div class="nb-aod-grid-two">
                <!-- Customer Info -->
                <div class="nb-aod-info-card">
                    <h4><i class="bi bi-person-circle text-primary"></i> Thông Tin Khách Hàng</h4>
                    <p><strong>Họ & Tên:</strong> <?php echo esc_html($customer_name); ?></p>
                    <p><strong>Điện thoại:</strong> <a href="tel:<?php echo esc_attr($customer_phone); ?>" class="text-primary font-weight-bold"><?php echo esc_html($customer_phone); ?></a></p>
                    <p><strong>Email:</strong> <?php echo esc_html($customer_email); ?></p>
                    <p><strong>Địa chỉ nhận hàng:</strong> <?php echo wp_kses_post($shipping_addr); ?></p>
                    <p><strong>Phương thức:</strong> <?php echo esc_html($payment_method); ?></p>
                    <p><strong>Ghi chú:</strong> <em class="text-muted"><?php echo esc_html($customer_note); ?></em></p>
                </div>

                <!-- Order Action Strip -->
                <div class="nb-aod-action-card">
                    <h4><i class="bi bi-lightning-charge-fill text-warning"></i> Thao Tác Duyệt Đơn Nhanh</h4>
                    <p class="text-muted small">Cập nhật trạng thái trực tiếp mà không cần vào trang quản trị WP:</p>
                    <div class="nb-aod-action-buttons">
                        <?php if ($order->get_status() === 'pending'): ?>
                            <button type="button" class="nb-btn-hub nb-btn-hub-primary nb-act-order-status" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="processing">
                                <i class="bi bi-check-circle-fill"></i> Tiếp Nhận Đơn Hàng
                            </button>
                            <button type="button" class="nb-btn-hub nb-btn-hub-danger nb-act-order-status" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="cancelled">
                                <i class="bi bi-x-circle-fill"></i> Hủy Đơn Hàng
                            </button>
                        <?php elseif ($order->get_status() === 'processing'): ?>
                            <button type="button" class="nb-btn-hub nb-btn-hub-purple nb-act-order-status" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="on-hold">
                                <i class="bi bi-truck"></i> Duyệt Giao Hàng
                            </button>
                            <button type="button" class="nb-btn-hub nb-btn-hub-success nb-act-order-status" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="completed">
                                <i class="bi bi-patch-check-fill"></i> Hoàn Thành Đơn
                            </button>
                            <button type="button" class="nb-btn-hub nb-btn-hub-danger nb-act-order-status" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="cancelled">
                                <i class="bi bi-x-circle-fill"></i> Hủy Đơn Hàng
                            </button>
                        <?php elseif (in_array($order->get_status(), ['on-hold', 'shipping'], true)): ?>
                            <button type="button" class="nb-btn-hub nb-btn-hub-success nb-act-order-status" data-order-id="<?php echo esc_attr($order_id); ?>" data-status="completed">
                                <i class="bi bi-patch-check-fill"></i> Hoàn Thành Đơn
                            </button>
                        <?php else: ?>
                            <div class="nb-aod-status-fixed">
                                Đơn hàng đang ở trạng thái <strong><?php echo esc_html($status_labels[$order->get_status()] ?? $order->get_status()); ?></strong>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="nb-aod-items-card">
                <h4><i class="bi bi-book-half text-success"></i> Danh Sách Sách Trong Đơn (<?php echo count($items); ?> tựa sách)</h4>
                <div class="nb-aod-table-responsive">
                    <table class="nb-aod-table">
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>Tựa Sách & Tác Giả</th>
                                <th>Số Lượng</th>
                                <th class="text-right">Thành Tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="nb-aod-item-img"><?php echo $item['image']; ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($item['link']); ?>" target="_blank" class="nb-aod-item-title"><?php echo esc_html($item['name']); ?></a>
                                        <div class="nb-aod-item-author"><i class="bi bi-pen"></i> <?php echo esc_html($item['author']); ?></div>
                                    </td>
                                    <td><strong><?php echo esc_html($item['qty']); ?></strong> cuốn</td>
                                    <td class="text-right font-weight-bold text-danger"><?php echo $item['total']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Tạm tính:</strong></td>
                                <td class="text-right"><?php echo wc_price($order->get_subtotal()); ?></td>
                            </tr>
                            <?php if ($order->get_discount_total() > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-right text-success"><strong>Giảm giá voucher:</strong></td>
                                    <td class="text-right text-success">-<?php echo wc_price($order->get_discount_total()); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Phí vận chuyển:</strong></td>
                                <td class="text-right"><?php echo wc_price($order->get_shipping_total()); ?></td>
                            </tr>
                            <tr class="nb-aod-total-row">
                                <td colspan="3" class="text-right"><strong>TỔNG THANH TOÁN:</strong></td>
                                <td class="text-right"><strong class="text-danger nb-aod-big-price"><?php echo wc_price($order->get_total()); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Quick Save Book (Create or Update Product)
     */
    public function ajax_quick_save_book() {
        check_ajax_referer('nbook_admin_hub_nonce', 'nonce');

        if (!self::is_admin_user()) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $book_id = isset($_POST['book_id']) ? absint($_POST['book_id']) : 0;
        $title   = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $author  = isset($_POST['author']) ? sanitize_text_field(wp_unslash($_POST['author'])) : '';
        $price   = isset($_POST['regular_price']) ? floatval($_POST['regular_price']) : 0;
        $sale_price = isset($_POST['sale_price']) && $_POST['sale_price'] !== '' ? floatval($_POST['sale_price']) : '';
        $stock   = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 50;
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        $desc    = isset($_POST['short_description']) ? wp_kses_post(wp_unslash($_POST['short_description'])) : '';
        $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';

        if (empty($title)) {
            wp_send_json_error(['message' => 'Vui lòng nhập tên tựa sách.']);
        }
        if ($price <= 0) {
            wp_send_json_error(['message' => 'Vui lòng nhập giá bán hợp lệ (> 0đ).']);
        }

        if ($book_id > 0) {
            $product = wc_get_product($book_id);
            if (!$product) {
                wp_send_json_error(['message' => 'Không tìm thấy sách #' . $book_id]);
            }
        } else {
            $product = new WC_Product_Simple();
            $product->set_status('publish');
        }

        $product->set_name($title);
        $product->set_regular_price($price);
        if ($sale_price !== '' && $sale_price < $price) {
            $product->set_sale_price($sale_price);
            $product->set_price($sale_price);
        } else {
            $product->set_sale_price('');
            $product->set_price($price);
        }

        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock);
        $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');

        if (!empty($desc)) {
            $product->set_short_description($desc);
        }

        if ($category_id > 0) {
            $product->set_category_ids([$category_id]);
        }

        // Handle Image Upload if provided
        if (!empty($_FILES['book_image']) && !empty($_FILES['book_image']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attach_id = media_handle_upload('book_image', 0);
            if (!is_wp_error($attach_id)) {
                $product->set_image_id($attach_id);
            }
        } elseif (!empty($image_url)) {
            // Save image URL or fetch
            $product_id_temp = $product->save();
            update_post_meta($product_id_temp, '_nbook_custom_image_url', $image_url);
        }

        $saved_id = $product->save();

        if ($saved_id) {
            if (!empty($author)) {
                update_post_meta($saved_id, '_biblio_author', $author);
                update_post_meta($saved_id, '_book_author', $author);
                update_post_meta($saved_id, '_nbook_author', $author);
            }

            wp_send_json_success([
                'book_id' => $saved_id,
                'title'   => $title,
                'author'  => $author ?: 'Nhiều tác giả',
                'price'   => wc_price($price),
                'sale_price' => $sale_price !== '' ? wc_price($sale_price) : '',
                'stock'   => $stock,
                'message' => $book_id > 0 ? 'Đã cập nhật sách thành công!' : 'Đã thêm tựa sách mới thành công!',
            ]);
        } else {
            wp_send_json_error(['message' => 'Có lỗi xảy ra khi lưu sách.']);
        }
    }

    /**
     * AJAX: Quick Update Stock & Price inline
     */
    public function ajax_quick_update_stock_price() {
        check_ajax_referer('nbook_admin_hub_nonce', 'nonce');

        if (!self::is_admin_user()) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $book_id = isset($_POST['book_id']) ? absint($_POST['book_id']) : 0;
        $price   = isset($_POST['regular_price']) ? floatval($_POST['regular_price']) : 0;
        $stock   = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;

        $product = wc_get_product($book_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Không tìm thấy sách #' . $book_id]);
        }

        if ($price > 0) {
            $product->set_regular_price($price);
            $sale_price = $product->get_sale_price();
            if ($sale_price && $sale_price < $price) {
                $product->set_price($sale_price);
            } else {
                $product->set_sale_price('');
                $product->set_price($price);
            }
        }

        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock);
        $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
        $product->save();

        wp_send_json_success([
            'book_id' => $book_id,
            'price'   => wc_price($product->get_price()),
            'stock'   => $stock,
            'message' => sprintf('Đã cập nhật giá & tồn kho cho sách #%d thành công!', $book_id),
        ]);
    }

    /**
     * AJAX: Delete Book
     */
    public function ajax_delete_book() {
        check_ajax_referer('nbook_admin_hub_nonce', 'nonce');

        if (!self::is_admin_user()) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $book_id = isset($_POST['book_id']) ? absint($_POST['book_id']) : 0;
        $product = wc_get_product($book_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Không tìm thấy sách #' . $book_id]);
        }

        $product_title = $product->get_name();
        $product->delete(false); // Move to trash

        wp_send_json_success([
            'book_id' => $book_id,
            'message' => sprintf('Đã chuyển sách "%s" vào thùng rác thành công!', $product_title),
        ]);
    }

    /**
     * AJAX: Quick Save Post (News & Events)
     */
    public function ajax_quick_save_post() {
        check_ajax_referer('nbook_admin_hub_nonce', 'nonce');

        if (!self::is_admin_user()) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $post_id     = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $title       = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        $content     = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $excerpt     = isset($_POST['excerpt']) ? sanitize_textarea_field(wp_unslash($_POST['excerpt'])) : '';

        if (empty($title)) {
            wp_send_json_error(['message' => 'Vui lòng nhập tiêu đề bài viết tin tức / sự kiện.']);
        }
        if (empty($content)) {
            wp_send_json_error(['message' => 'Vui lòng nhập nội dung bài viết.']);
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ];

        if ($post_id > 0) {
            $post_data['ID'] = $post_id;
            $new_post_id = wp_update_post($post_data);
        } else {
            $new_post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($new_post_id) || !$new_post_id) {
            wp_send_json_error(['message' => 'Lỗi khi lưu bài viết: ' . ($is_wp_error ? $new_post_id->get_error_message() : '')]);
        }

        if ($category_id > 0) {
            wp_set_post_categories($new_post_id, [$category_id]);
        }

        // Handle Image Upload if provided
        if (!empty($_FILES['post_image']) && !empty($_FILES['post_image']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attach_id = media_handle_upload('post_image', $new_post_id);
            if (!is_wp_error($attach_id)) {
                set_post_thumbnail($new_post_id, $attach_id);
            }
        }

        wp_send_json_success([
            'post_id'   => $new_post_id,
            'title'     => $title,
            'link'      => get_permalink($new_post_id),
            'message'   => $post_id > 0 ? 'Đã cập nhật bài viết thành công!' : 'Đã xuất bản bài viết Tin tức / Sự kiện mới thành công!',
        ]);
    }

    /**
     * AJAX: Delete Post
     */
    public function ajax_delete_post() {
        check_ajax_referer('nbook_admin_hub_nonce', 'nonce');

        if (!self::is_admin_user()) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            wp_send_json_error(['message' => 'Không tìm thấy bài viết #' . $post_id]);
        }

        $post_title = $post->post_title;
        wp_trash_post($post_id);

        wp_send_json_success([
            'post_id' => $post_id,
            'message' => sprintf('Đã xóa bài viết "%s" thành công!', $post_title),
        ]);
    }
}

// Instantiate singleton
Biblio_Frontend_Admin_Hub::instance();
