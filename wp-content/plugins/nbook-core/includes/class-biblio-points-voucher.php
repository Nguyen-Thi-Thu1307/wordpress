<?php
/**
 * Biblio Points & Voucher Hub
 *
 * Quản lý & Hoàn thiện toàn diện phân hệ:
 * - Điểm thưởng Biblio Xu (Giao diện chuẩn Shopee Xu, Thẻ ví Xu hoàng gia, Chuỗi điểm danh 7 ngày,
 *   Bảng nhiệm vụ kiếm Xu, Đặc quyền hạng thành viên, Kho đổi quà đa dạng, Lịch sử biến động Xu).
 * - Voucher của tôi (Ví voucher chuẩn Shopee Ticket răng cưa, Nhập mã lưu ví, Bộ lọc tabs,
 *   Tích hợp WooCommerce My Account, Tra cứu & Áp dụng mã nhanh).
 *
 * @package Nbook_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Biblio_Points_Voucher {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Khởi tạo các trang & endpoints
        add_action('init', [$this, 'register_endpoints']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('admin_init', [$this, 'seed_rewards_and_sample_data']);

        // WooCommerce My Account integration
        add_filter('woocommerce_get_query_vars', [$this, 'add_query_vars']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_account_menu_items'], 20);
        add_action('woocommerce_account_voucher_endpoint', [$this, 'output_my_vouchers']);
        add_action('woocommerce_account_diem-thuong_endpoint', [$this, 'output_points_page']);

        // Shortcodes
        add_shortcode('nbook_points', [$this, 'render_points_page']);
        add_shortcode('nbook_my_vouchers', [$this, 'render_my_vouchers']);

        // AJAX handlers
        add_action('wp_ajax_nbook_daily_checkin_streak', [$this, 'ajax_daily_checkin_streak']);
        add_action('wp_ajax_nbook_redeem_reward_v2', [$this, 'ajax_redeem_reward']);
        add_action('wp_ajax_nbook_save_voucher_code', [$this, 'ajax_save_voucher_code']);
        add_action('wp_ajax_nbook_remove_saved_voucher', [$this, 'ajax_remove_saved_voucher']);
    }

    /**
     * Đăng ký Endpoint cho WooCommerce My Account
     */
    public function register_endpoints() {
        add_rewrite_endpoint('voucher', EP_PAGES);
        add_rewrite_endpoint('diem-thuong', EP_PAGES);

        // Đảm bảo flush rewrite rules nếu chưa có flag v3
        if (!get_option('biblio_endpoints_flushed_v3')) {
            flush_rewrite_rules(false);
            update_option('biblio_endpoints_flushed_v3', '1');
        }
    }

    public function output_my_vouchers() {
        echo $this->render_my_vouchers();
    }

    public function output_points_page() {
        echo $this->render_points_page();
    }

    public function add_query_vars($vars) {
        $vars['voucher'] = 'voucher';
        $vars['diem-thuong'] = 'diem-thuong';
        return $vars;
    }

    /**
     * Thêm Voucher & Điểm Thưởng vào menu bên trái của WooCommerce Tài khoản
     */
    public function add_account_menu_items($items) {
        $new_items = [];
        $uid = get_current_user_id();
        $points = $this->get_user_points($uid);
        $vouchers = count($this->get_user_vouchers($uid));

        foreach ($items as $key => $title) {
            $new_items[$key] = $title;
            if ($key === 'orders') {
                $new_items['voucher'] = sprintf('Voucher của tôi (%d)', $vouchers);
                $new_items['diem-thuong'] = sprintf('Điểm thưởng Xu (%s)', number_format_i18n($points));
            }
        }
        return $new_items;
    }

    /**
     * Tự động tạo / kiểm tra 2 trang /diem-thuong/ và /voucher-cua-toi/
     */
    public function maybe_create_pages() {
        // Trang Điểm thưởng
        $p_points = get_page_by_path('diem-thuong');
        if (!$p_points) {
            wp_insert_post([
                'post_title'   => 'Điểm thưởng Biblio Xu',
                'post_name'    => 'diem-thuong',
                'post_content' => '[nbook_points]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);
        }

        // Trang Voucher của tôi
        $p_voucher = get_page_by_path('voucher-cua-toi');
        if (!$p_voucher) {
            wp_insert_post([
                'post_title'   => 'Voucher của tôi',
                'post_name'    => 'voucher-cua-toi',
                'post_content' => '[nbook_my_vouchers]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);
        }
    }

    /**
     * Lấy số dư Xu của user
     */
    public function get_user_points($user_id) {
        global $wpdb;
        $user_id = absint($user_id);
        if (!$user_id) return 0;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Xác định Hạng thành viên & tiến trình thăng hạng (Đồng bộ 100% với Shopee Rewards Biblio)
     */
    public function get_tier_info($user_id) {
        $user_id = absint($user_id);
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // 1. Đồng bộ trực tiếp với hệ thống nbook_get_customer_loyalty_data()
        if (function_exists('nbook_get_customer_loyalty_data')) {
            $loyalty = nbook_get_customer_loyalty_data($user_id);
            if ($loyalty && !empty($loyalty['current_tier'])) {
                $cur = $loyalty['current_tier'];
                $nxt = $loyalty['next_tier'];
                $spent = (float) $loyalty['total_spent'];

                // Chuẩn hoá tên ngắn: 'Đồng', 'Bạc', 'Vàng', 'Kim Cương'
                $tier_key = $cur['key'];
                $tier_name = $cur['short_name'] ?? $cur['name'];
                if ($tier_key === 'new') {
                    $tier_name = 'Đồng'; // Theo chuẩn sàn TMĐT, mọi thành viên mới khởi đầu từ Hạng Đồng
                }
                $tier_name = trim(str_replace('Hạng', '', $tier_name));

                $next_tier_str = $nxt ? $nxt['name'] : 'Tối Đa';
                if ($nxt && !empty($nxt['req_spent'])) {
                    $next_tier_str .= ' (' . number_format_i18n($nxt['req_spent']) . '₫)';
                }

                return [
                    'tier'               => $tier_name,
                    'tier_key'           => $tier_key,
                    'icon'               => $cur['badge_icon'] ?? 'bi-award-fill',
                    'color'              => $cur['color'] ?? '#b45309',
                    'spent'              => $spent,
                    'next_tier'          => $next_tier_str,
                    'next_amount'        => $loyalty['spent_needed'] ?? 0,
                    'progress'           => $loyalty['progress_percent'] ?? 100,
                    'rate_label'         => 'Tích ' . ($cur['cashback_label'] ?? '1%') . ' Xu',
                    'birthday_voucher'   => $cur['birthday_perk'] ?? 'Voucher sinh nhật 15.000₫',
                    'freeship_per_month' => $cur['freeship_perk'] ?? '1 Mã Freeship 10.000₫/tháng',
                    'callout_message'    => $loyalty['callout_message'] ?? '',
                ];
            }
        }

        // 2. Fallback dựa trên chi tiêu thực tế WooCommerce
        $spent = (float) wc_get_customer_total_spent($user_id);
        if ($spent >= 6000000) {
            return [
                'tier' => 'Kim Cương',
                'tier_key' => 'diamond',
                'icon' => 'bi-gem',
                'color' => '#0284c7',
                'spent' => $spent,
                'next_tier' => 'Tối Đa',
                'next_amount' => 0,
                'progress' => 100,
                'rate_label' => 'Tích 3% Xu',
                'birthday_voucher' => 'Quà tri ân đặc biệt + 200K',
                'freeship_per_month' => 'Freeship không giới hạn',
            ];
        } elseif ($spent >= 3000000) {
            return [
                'tier' => 'Vàng',
                'tier_key' => 'gold',
                'icon' => 'bi-trophy-fill',
                'color' => '#d97706',
                'spent' => $spent,
                'next_tier' => 'Kim Cương (6.000.000₫)',
                'next_amount' => 6000000 - $spent,
                'progress' => min(100, round(($spent - 3000000) / 3000000 * 100)),
                'rate_label' => 'Tích 2% Xu',
                'birthday_voucher' => 'Voucher sinh nhật 50K',
                'freeship_per_month' => '2 mã Freeship / tháng',
            ];
        } elseif ($spent >= 1000000) {
            return [
                'tier' => 'Bạc',
                'tier_key' => 'silver',
                'icon' => 'bi-shield-fill-check',
                'color' => '#475569',
                'spent' => $spent,
                'next_tier' => 'Vàng (3.000.000₫)',
                'next_amount' => 3000000 - $spent,
                'progress' => min(100, round(($spent - 1000000) / 2000000 * 100)),
                'rate_label' => 'Tích 1.5% Xu',
                'birthday_voucher' => 'Voucher sinh nhật 30K',
                'freeship_per_month' => '1 mã Freeship / tháng',
            ];
        } else {
            return [
                'tier' => 'Đồng',
                'tier_key' => 'bronze',
                'icon' => 'bi-award-fill',
                'color' => '#b45309',
                'spent' => $spent,
                'next_tier' => 'Bạc (1.000.000₫)',
                'next_amount' => max(0, 1000000 - $spent),
                'progress' => min(100, round($spent / 1000000 * 100)),
                'rate_label' => 'Tích 1% Xu',
                'birthday_voucher' => 'Voucher sinh nhật 15K',
                'freeship_per_month' => '1 mã Freeship / tháng',
            ];
        }
    }

    /**
     * Lấy danh sách voucher trong ví của user
     */
    public function get_user_vouchers($user_id) {
        $user_id = absint($user_id);
        if (!$user_id) return [];

        $saved = (array) get_user_meta($user_id, '_nbook_saved_coupons', true);
        $redeemed = (array) get_user_meta($user_id, '_nbook_vouchers', true);
        $codes = array_unique(array_filter(array_merge($saved, $redeemed)));

        // Tự động cấp voucher chào mừng nếu ví còn trống
        if (empty($codes)) {
            $codes = ['CHAOBIBLIO', 'FREESHIP', 'BIBLIO10', 'HOANXU5'];
            update_user_meta($user_id, '_nbook_saved_coupons', $codes);
        }

        $list = [];
        foreach ($codes as $code) {
            $code = strtoupper(trim($code));
            $cid = wc_get_coupon_id_by_code($code);
            if (!$cid) continue;

            $coupon = new WC_Coupon($cid);
            $type = get_post_meta($cid, '_nbook_coupon_type', true) ?: 'fixed';
            $expires = $coupon->get_date_expires();
            $is_expired = $expires && ($expires->getTimestamp() < current_time('timestamp', true));

            $list[] = [
                'id' => $cid,
                'code' => $code,
                'title' => $coupon->get_description() ?: get_the_title($cid),
                'amount' => $coupon->get_amount(),
                'discount_type' => $coupon->get_discount_type(),
                'custom_type' => $type,
                'min_amount' => (float) $coupon->get_minimum_amount(),
                'max_amount' => (float) get_post_meta($cid, 'maximum_amount', true),
                'expires_at' => $expires ? $expires->date_i18n('d/m/Y') : 'Không thời hạn',
                'expires_timestamp' => $expires ? $expires->getTimestamp() : 0,
                'is_expired' => $is_expired,
                'free_shipping' => $coupon->get_free_shipping(),
            ];
        }

        return $list;
    }

    /**
     * Khởi tạo dữ liệu mẫu cho Kho quà & Điểm nếu chưa có
     */
    public function seed_rewards_and_sample_data() {
        global $wpdb;

        // 1. Seed Quà đổi điểm vào wp_nbook_rewards nếu bảng đang rỗng
        $reward_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nbook_rewards");
        if ($reward_count === 0) {
            $rewards = [
                [
                    'name' => 'Voucher giảm 20.000₫ cho đơn từ 150K',
                    'description' => 'Áp dụng cho toàn bộ đầu sách Văn học, Kinh tế, Tâm lý và Giáo trình.',
                    'type' => 'voucher',
                    'points' => 200,
                    'stock' => 150,
                    'coupon_code' => 'BOOKWORM20K',
                    'active' => 1,
                ],
                [
                    'name' => 'Voucher Miễn phí vận chuyển 25.000₫',
                    'description' => 'Freeship toàn quốc cho đơn hàng từ 150.000₫, không giới hạn khoảng cách.',
                    'type' => 'voucher',
                    'points' => 250,
                    'stock' => 100,
                    'coupon_code' => 'FREESHIP',
                    'active' => 1,
                ],
                [
                    'name' => 'Voucher giảm 50.000₫ cho đơn từ 400K',
                    'description' => 'Ưu đãi đặc biệt giảm sâu khi mua combo sách bộ hoặc đơn sách giá trị.',
                    'type' => 'voucher',
                    'points' => 500,
                    'stock' => 80,
                    'coupon_code' => 'BIBLIO50K',
                    'active' => 1,
                ],
                [
                    'name' => 'Bookmark Kim loại mạ vàng Cổ điển hoa văn Hoàng Gia',
                    'description' => 'Thẻ kẹp sách bằng hợp kim không gỉ mạ vàng 18K tinh xảo, kèm dây tua rua sang trọng.',
                    'type' => 'physical',
                    'points' => 150,
                    'stock' => 120,
                    'coupon_code' => '',
                    'active' => 1,
                ],
                [
                    'name' => 'Bộ 10 Màng bọc sách chống nước & trầy xước cao cấp',
                    'description' => 'Decal dẻo trong suốt, bảo vệ gáy và mép sách luôn như mới xuất xưởng.',
                    'type' => 'physical',
                    'points' => 100,
                    'stock' => 300,
                    'coupon_code' => '',
                    'active' => 1,
                ],
                [
                    'name' => 'Sổ tay Biblio Vintage Bìa da cao cấp',
                    'description' => 'Sổ tay ghi chú 160 trang giấy kraft vàng chống lóa, dập chìm logo Biblio Booklover.',
                    'type' => 'physical',
                    'points' => 600,
                    'stock' => 45,
                    'coupon_code' => '',
                    'active' => 1,
                ],
                [
                    'name' => 'Túi vải Canvas "Người Yêu Sách" Biblio Edition',
                    'description' => 'Túi tote canvas cao cấp dày dặn có khóa kéo và ngăn phụ, chứa vừa laptop 14 inch và 5 cuốn sách dày.',
                    'type' => 'physical',
                    'points' => 750,
                    'stock' => 30,
                    'coupon_code' => '',
                    'active' => 1,
                ],
            ];

            foreach ($rewards as $r) {
                $wpdb->insert($wpdb->prefix . 'nbook_rewards', $r, ['%s', '%s', '%s', '%d', '%d', '%s', '%d']);
            }
        }

        // 2. Seed Điểm mẫu cho các user hiện có nếu họ chưa có bản ghi ledger nào
        $users = get_users(['number' => 10]);
        foreach ($users as $u) {
            $has_ledger = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id = %d",
                $u->ID
            ));

            if ($has_ledger === 0) {
                $now = current_time('mysql');
                $one_year = date('Y-m-d H:i:s', strtotime('+1 year'));

                $sample_ledger = [
                    ['amount' => 100, 'balance' => 100, 'type' => 'signup', 'ref' => 'signup-' . $u->ID, 'desc' => 'Thưởng chào mừng thành viên mới đăng ký', 'time' => date('Y-m-d H:i:s', strtotime('-15 days'))],
                    ['amount' => 350, 'balance' => 450, 'type' => 'order', 'ref' => 'order-welcome-' . $u->ID, 'desc' => 'Tích lũy 1% từ đơn hàng sách #1024', 'time' => date('Y-m-d H:i:s', strtotime('-10 days'))],
                    ['amount' => 50,  'balance' => 500, 'type' => 'review', 'ref' => 'review-sample-' . $u->ID, 'desc' => 'Thưởng đánh giá 5 sao sách "Nhà Giả Kim"', 'time' => date('Y-m-d H:i:s', strtotime('-5 days'))],
                    ['amount' => 30,  'balance' => 530, 'type' => 'checkin', 'ref' => 'checkin-streak-' . $u->ID, 'desc' => 'Thưởng điểm danh liên tục 3 ngày', 'time' => date('Y-m-d H:i:s', strtotime('-1 days'))],
                    ['amount' => 150, 'balance' => 680, 'type' => 'event', 'ref' => 'event-birthday-' . $u->ID, 'desc' => 'Tặng Xu tri ân Ngày Hội Sách Biblio Book Fest', 'time' => $now],
                ];

                foreach ($sample_ledger as $entry) {
                    $wpdb->insert(
                        $wpdb->prefix . 'nbook_points_ledger',
                        [
                            'user_id'     => $u->ID,
                            'amount'      => $entry['amount'],
                            'balance'     => $entry['balance'],
                            'type'        => $entry['type'],
                            'reference'   => $entry['ref'],
                            'description' => $entry['desc'],
                            'expires_at'  => $one_year,
                            'created_at'  => $entry['time'],
                        ],
                        ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
                    );
                }

                // Cấp luôn voucher chào mừng
                $default_coupons = ['CHAOBIBLIO', 'FREESHIP', 'BIBLIO10', 'HOANXU5'];
                update_user_meta($u->ID, '_nbook_saved_coupons', $default_coupons);
            }
        }
    }

    /**
     * AJAX Điểm Danh Nhận Xu & Chuỗi Streak 7 Ngày
     */
    public function ajax_daily_checkin_streak() {
        check_ajax_referer('nbook_points_action', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vui lòng đăng nhập để điểm danh nhận Xu.'], 401);
        }

        $uid = get_current_user_id();
        $today = current_time('Y-m-d');
        $ref_today = 'checkin-' . $uid . '-' . $today;

        global $wpdb;
        $already = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id = %d AND reference = %s",
            $uid, $ref_today
        ));

        if ($already) {
            wp_send_json_error(['message' => 'Bạn đã hoàn thành điểm danh hôm nay rồi! Hãy quay lại vào ngày mai nhé.']);
        }

        // Tính chuỗi streak liên tục
        $streak = (int) get_user_meta($uid, '_nbook_checkin_streak', true);
        $last_date = get_user_meta($uid, '_nbook_last_checkin_date', true);

        $yesterday = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));
        if ($last_date === $yesterday) {
            $streak++;
        } else {
            $streak = 1;
        }

        // Tính lượng Xu thưởng theo mốc streak: mỗi ngày 100 Xu, ngày thứ 7 nhận 200 Xu
        $day_in_cycle = (($streak - 1) % 7) + 1;
        $bonus_points = ($day_in_cycle == 7) ? 200 : 100;

        // Cập nhật Ledger
        $curr_balance = $this->get_user_points($uid);
        $new_balance = $curr_balance + $bonus_points;
        $one_year = date('Y-m-d H:i:s', strtotime('+1 year'));

        $wpdb->insert(
            $wpdb->prefix . 'nbook_points_ledger',
            [
                'user_id'     => $uid,
                'amount'      => $bonus_points,
                'balance'     => $new_balance,
                'type'        => 'checkin',
                'reference'   => $ref_today,
                'description' => sprintf('Điểm danh ngày %d/7 (+%d Xu)', $day_in_cycle, $bonus_points),
                'expires_at'  => $one_year,
                'created_at'  => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        update_user_meta($uid, '_nbook_checkin_streak', $streak);
        update_user_meta($uid, '_nbook_last_checkin_date', $today);

        // Gửi thông báo hệ thống
        if (class_exists('Nbook_Core')) {
            Nbook_Core::instance()->add_notification(
                $uid,
                'Điểm danh thành công 🎉',
                sprintf('Bạn vừa nhận được +%d Biblio Xu cho ngày %d. Tiếp tục duy trì để nhận thưởng lớn!', $bonus_points, $day_in_cycle),
                home_url('/diem-thuong/'),
                'reward'
            );
        }

        wp_send_json_success([
            'message' => sprintf('🎉 Chúc mừng! Bạn nhận được +%d Biblio Xu hôm nay.', $bonus_points),
            'points_added' => $bonus_points,
            'new_balance' => $new_balance,
            'balance_formatted' => number_format_i18n($new_balance),
            'day_in_cycle' => $day_in_cycle,
            'streak' => $streak,
        ]);
    }

    /**
     * AJAX Đổi Quà Tặng (Voucher hoặc Hiện vật)
     */
    public function ajax_redeem_reward() {
        check_ajax_referer('nbook_points_action', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vui lòng đăng nhập để đổi quà.'], 401);
        }

        $uid = get_current_user_id();
        $rid = absint($_POST['reward_id'] ?? 0);
        if (!$rid) {
            wp_send_json_error(['message' => 'Phần quà không hợp lệ.']);
        }

        global $wpdb;
        $reward = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nbook_rewards WHERE id = %d AND active = 1",
            $rid
        ));

        if (!$reward) {
            wp_send_json_error(['message' => 'Phần quà không tồn tại hoặc đã tạm dừng.']);
        }

        if ($reward->stock <= 0) {
            wp_send_json_error(['message' => 'Phần quà này hiện đã hết trong kho. Vui lòng chọn quà khác nhé!']);
        }

        $balance = $this->get_user_points($uid);
        if ($balance < $reward->points) {
            wp_send_json_error([
                'message' => sprintf('Bạn cần thêm %s Xu để đổi phần quà này (Số dư hiện tại: %s Xu).',
                    number_format_i18n($reward->points - $balance),
                    number_format_i18n($balance)
                )
            ]);
        }

        // Trừ tồn kho
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}nbook_rewards SET stock = stock - 1 WHERE id = %d AND stock > 0",
            $rid
        ));

        // Trừ Xu trong Ledger
        $new_balance = $balance - $reward->points;
        $ref = 'redeem-' . $uid . '-' . $rid . '-' . time();
        $wpdb->insert(
            $wpdb->prefix . 'nbook_points_ledger',
            [
                'user_id'     => $uid,
                'amount'      => -$reward->points,
                'balance'     => $new_balance,
                'type'        => 'redeem',
                'reference'   => $ref,
                'description' => sprintf('Đổi quà: %s', $reward->name),
                'expires_at'  => null,
                'created_at'  => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        $is_voucher = ($reward->type === 'voucher' && !empty($reward->coupon_code));
        $saved_code = '';

        if ($is_voucher) {
            $saved_code = strtoupper(trim($reward->coupon_code));
            $saved = (array) get_user_meta($uid, '_nbook_saved_coupons', true);
            if (!in_array($saved_code, $saved, true)) {
                $saved[] = $saved_code;
                update_user_meta($uid, '_nbook_saved_coupons', $saved);
            }
        }

        // Gửi thông báo
        if (class_exists('Nbook_Core')) {
            Nbook_Core::instance()->add_notification(
                $uid,
                'Đổi quà thành công 🎁',
                sprintf('Bạn đã đổi thành công "%s" bằng %s Biblio Xu.%s',
                    $reward->name,
                    number_format_i18n($reward->points),
                    $is_voucher ? ' Mã giảm giá đã được thêm vào "Voucher của tôi"!' : ' Nhân viên Biblio sẽ liên hệ gửi quà đến bạn.'
                ),
                $is_voucher ? home_url('/voucher-cua-toi/') : home_url('/diem-thuong/'),
                'reward'
            );
        }

        wp_send_json_success([
            'message' => sprintf('🎁 Chúc mừng bạn đã đổi thành công "%s"!%s',
                $reward->name,
                $is_voucher ? ' Mã giảm giá đã được chuyển vào "Voucher của tôi".' : ' Quà sẽ được gửi kèm đơn hàng tiếp theo.'
            ),
            'new_balance' => $new_balance,
            'balance_formatted' => number_format_i18n($new_balance),
            'is_voucher' => $is_voucher,
            'voucher_code' => $saved_code,
            'voucher_url' => home_url('/voucher-cua-toi/'),
        ]);
    }

    /**
     * AJAX Nhập mã voucher và lưu vào ví
     */
    public function ajax_save_voucher_code() {
        check_ajax_referer('nbook_voucher_action', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vui lòng đăng nhập để lưu voucher.'], 401);
        }

        $code = strtoupper(sanitize_text_field($_POST['code'] ?? ''));
        if (empty($code)) {
            wp_send_json_error(['message' => 'Vui lòng nhập mã voucher.']);
        }

        $cid = wc_get_coupon_id_by_code($code);
        if (!$cid) {
            wp_send_json_error(['message' => sprintf('Mã "%s" không tồn tại hoặc đã hết hạn áp dụng.', esc_html($code))]);
        }

        $coupon = new WC_Coupon($cid);
        if ($coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < current_time('timestamp', true)) {
            wp_send_json_error(['message' => sprintf('Mã "%s" đã hết hạn sử dụng.', esc_html($code))]);
        }

        $uid = get_current_user_id();
        $saved = (array) get_user_meta($uid, '_nbook_saved_coupons', true);

        if (in_array($code, array_map('strtoupper', $saved), true)) {
            wp_send_json_error(['message' => sprintf('Mã "%s" đã có sẵn trong Ví voucher của bạn rồi.', esc_html($code))]);
        }

        $saved[] = $code;
        update_user_meta($uid, '_nbook_saved_coupons', $saved);

        wp_send_json_success([
            'message' => sprintf('🎟️ Đã lưu thành công mã "%s" vào Ví voucher của bạn!', esc_html($code)),
            'code' => $code,
            'total_count' => count($saved),
        ]);
    }

    /**
     * AJAX Xóa mã voucher khỏi ví
     */
    public function ajax_remove_saved_voucher() {
        check_ajax_referer('nbook_voucher_action', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vui lòng đăng nhập.'], 401);
        }

        $code = strtoupper(sanitize_text_field($_POST['code'] ?? ''));
        $uid = get_current_user_id();
        $saved = (array) get_user_meta($uid, '_nbook_saved_coupons', true);

        $saved = array_values(array_filter($saved, function($item) use ($code) {
            return strtoupper(trim($item)) !== $code;
        }));

        update_user_meta($uid, '_nbook_saved_coupons', $saved);

        wp_send_json_success([
            'message' => sprintf('Đã gỡ mã "%s" khỏi ví.', esc_html($code)),
            'total_count' => count($saved),
        ]);
    }

    /**
     * =========================================================================
     * GIAO DIỆN HOÀN THIỆN: ĐIỂM THƯỞNG BIBLIO XU (Shopee Xu Style)
     * =========================================================================
     */
    public function render_points_page() {
        if (!is_user_logged_in()) {
            $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
            return '
            <div class="nb-auth-required-box">
                <div class="nb-arb-icon"><i class="bi bi-coin"></i></div>
                <h2>Chào mừng bạn đến với Biblio Xu</h2>
                <p>Vui lòng đăng nhập để xem số dư Xu, điểm danh mỗi ngày và đổi những phần quà độc quyền từ Biblio.</p>
                <a href="' . esc_url($account_url) . '#login" class="nb-btn-orange nb-open-auth-modal" data-tab="login"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập ngay</a>
            </div>';
        }

        global $wpdb;
        $uid = get_current_user_id();
        $user = wp_get_current_user();
        $balance = $this->get_user_points($uid);
        $tier = $this->get_tier_info($uid);
        $streak = (int) get_user_meta($uid, '_nbook_checkin_streak', true);
        $last_checkin = get_user_meta($uid, '_nbook_last_checkin_date', true);
        $is_checked_today = ($last_checkin === current_time('Y-m-d'));
        $day_in_cycle = (($streak - 1) % 7) + 1;
        if (!$is_checked_today && $last_checkin !== date('Y-m-d', strtotime('-1 day', current_time('timestamp')))) {
            // Chuỗi đã bị đứt nếu hôm qua không điểm danh
            $day_in_cycle = 1;
        }

        // Lấy danh sách quà tặng
        $rewards = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nbook_rewards WHERE active = 1 ORDER BY points ASC");

        // Lấy lịch sử biến động Xu
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id = %d ORDER BY created_at DESC, id DESC LIMIT 25",
            $uid
        ));

        $nonce = wp_create_nonce('nbook_points_action');

        ob_start();
        ?>
        <div class="nb-points-portal-wrap" id="nbPointsPortalWrap">

            <!-- Toast thông báo nhận Xu thành công trực tiếp trên màn hình -->
            <div id="nbCheckinScreenToast" class="nb-screen-toast" role="alert" aria-live="assertive">
                <div class="nb-st-icon-wrap">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <div class="nb-st-content">
                    <div class="nb-st-title" id="nbStTitle">🎉 Điểm danh nhận Xu thành công!</div>
                    <div class="nb-st-msg" id="nbStMsg">+100 Biblio Xu đã được cộng vào tài khoản của bạn.</div>
                </div>
                <button type="button" class="nb-st-close" onclick="document.getElementById('nbCheckinScreenToast').classList.remove('show')" aria-label="Đóng">&times;</button>
            </div>

            <!-- 1. HERO WALLET CARD (THẺ VÍ XU HOÀNG GIA) -->
            <div class="nb-points-hero-card">
                <div class="nb-phc-glow"></div>
                <div class="nb-phc-inner">
                    <div class="nb-phc-left">
                        <div class="nb-phc-badge-tier" style="border-color: <?php echo esc_attr($tier['color']); ?>;">
                            <i class="bi <?php echo esc_attr($tier['icon']); ?>" style="color: <?php echo esc_attr($tier['color']); ?>;"></i>
                            <span>Hạng <?php echo esc_html($tier['tier']); ?></span>
                        </div>
                        <div class="nb-phc-balance-title">Số dư Biblio Xu khả dụng</div>
                        <div class="nb-phc-balance-val">
                            <span class="nb-phc-num" id="nbUserPointsDisplay"><?php echo esc_html(number_format_i18n($balance)); ?></span>
                            <span class="nb-phc-unit">Xu</span>
                            <span class="nb-phc-vnd-equiv">≈ <?php echo esc_html(number_format_i18n($balance * 100)); ?>₫ (Giảm trực tiếp khi thanh toán)</span>
                        </div>
                        <div class="nb-phc-expiry">
                            <i class="bi bi-clock-history"></i> Hiệu lực sử dụng: 12 tháng kể từ ngày tích lũy.
                        </div>
                    </div>

                    <div class="nb-phc-right">
                        <div class="nb-phc-tier-card">
                            <div class="nb-ptc-header">
                                <strong>Tiến trình Hạng <?php echo esc_html($tier['tier']); ?></strong>
                                <span><?php echo esc_html($tier['rate_label']); ?></span>
                            </div>
                            <div class="nb-ptc-progress-track">
                                <div class="nb-ptc-progress-bar" style="width: <?php echo esc_attr($tier['progress']); ?>%;"></div>
                            </div>
                            <div class="nb-ptc-footer">
                                <?php if ($tier['next_amount'] > 0): ?>
                                    <span>Chi tiêu thêm <strong><?php echo esc_html(number_format_i18n($tier['next_amount'])); ?>₫</strong> để lên hạng <strong><?php echo esc_html($tier['next_tier']); ?></strong></span>
                                <?php else: ?>
                                    <span class="text-success"><i class="bi bi-check-circle-fill"></i> Bạn đã đạt cấp độ thành viên cao nhất!</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="nb-phc-quick-actions">
                            <a href="#nbStreakSection" class="nb-btn-phc-primary">
                                <i class="bi bi-calendar2-check-fill"></i>
                                <span><?php echo $is_checked_today ? 'Đã điểm danh hôm nay' : 'Điểm danh nhận +' . ($day_in_cycle == 7 ? '200' : '100') . ' Xu'; ?></span>
                            </a>
                            <a href="#nbRewardsStore" class="nb-btn-phc-secondary">
                                <i class="bi bi-gift-fill"></i>
                                <span>Đổi quà ngay</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. CHUỖI ĐIỂM DANH 7 NGÀY (7-DAY STREAK CHECK-IN) -->
            <section class="nb-points-section" id="nbStreakSection">
                <div class="nb-sec-header">
                    <div class="nb-sec-title">
                        <i class="bi bi-calendar-event text-warning"></i>
                        <span>Chuỗi Điểm Danh 7 Ngày Nhận Thưởng Lớn</span>
                    </div>
                    <div class="nb-sec-badge">
                        Chuỗi hiện tại: <strong id="nbStreakDaysText"><?php echo esc_html($streak); ?> ngày</strong>
                    </div>
                </div>

                <p class="nb-sec-sub">
                    Điểm danh liên tục mỗi ngày để nhận Biblio Xu. Mỗi ngày nhận <strong>100 Xu</strong>, duy trì liên tục đến ngày thứ 7 nhận thưởng lớn <strong>200 Xu</strong>!
                </p>

                <div class="nb-streak-days-grid">
                    <?php
                    $rewards_streak = [
                        1 => ['pts' => 100, 'label' => 'Ngày 1'],
                        2 => ['pts' => 100, 'label' => 'Ngày 2'],
                        3 => ['pts' => 100, 'label' => 'Ngày 3'],
                        4 => ['pts' => 100, 'label' => 'Ngày 4'],
                        5 => ['pts' => 100, 'label' => 'Ngày 5'],
                        6 => ['pts' => 100, 'label' => 'Ngày 6'],
                        7 => ['pts' => 200, 'label' => 'Ngày 7 (Thưởng lớn)'],
                    ];
                    for ($d = 1; $d <= 7; $d++):
                        $is_past = ($is_checked_today && $d <= $day_in_cycle) || (!$is_checked_today && $d < $day_in_cycle);
                        $is_current = ($d === $day_in_cycle);
                        $item_class = 'nb-streak-item';
                        if ($is_past) $item_class .= ' status-claimed';
                        elseif ($is_current) $item_class .= $is_checked_today ? ' status-claimed' : ' status-today';
                        else $item_class .= ' status-future';
                    ?>
                        <div class="<?php echo esc_attr($item_class); ?>" id="streakDay<?php echo $d; ?>">
                            <div class="nb-si-label"><?php echo esc_html($rewards_streak[$d]['label']); ?></div>
                            <div class="nb-si-coin">
                                <?php if ($d == 7): ?>
                                    <i class="bi bi-box2-heart-fill nb-si-box-icon"></i>
                                <?php else: ?>
                                    <i class="bi bi-coin"></i>
                                <?php endif; ?>
                            </div>
                            <div class="nb-si-reward">+<?php echo esc_html($rewards_streak[$d]['pts']); ?> Xu</div>
                            <div class="nb-si-status">
                                <?php if ($is_past || ($is_current && $is_checked_today)): ?>
                                    <span class="badge-claimed"><i class="bi bi-check2"></i> Đã nhận</span>
                                <?php elseif ($is_current && !$is_checked_today): ?>
                                    <span class="badge-today">Hôm nay</span>
                                <?php else: ?>
                                    <span class="badge-lock"><i class="bi bi-lock-fill"></i> Chưa mở</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="nb-streak-action-bar">
                    <button type="button" class="nb-btn-checkin-action" id="nbBtnDoCheckin" data-nonce="<?php echo esc_attr($nonce); ?>" <?php disabled($is_checked_today); ?>>
                        <i class="bi bi-hand-index-thumb-fill"></i>
                        <span id="nbCheckinBtnText"><?php echo $is_checked_today ? 'Hôm nay bạn đã điểm danh rồi ✓' : 'Điểm danh ngay hôm nay (+' . ($day_in_cycle == 7 ? '200' : '100') . ' Xu)'; ?></span>
                    </button>
                    <div id="nbCheckinAlertBox" class="nb-checkin-alert-box" style="display: none;"></div>
                    <div class="nb-checkin-tip">
                        <i class="bi bi-info-circle"></i> Điểm danh sẽ được làm mới lại vào 00:00 mỗi ngày.
                    </div>
                </div>
            </section>

            <!-- 3. BẢNG NHIỆM VỤ KIẾM XU (MISSIONS) -->
            <section class="nb-points-section">
                <div class="nb-sec-header">
                    <div class="nb-sec-title">
                        <i class="bi bi-lightning-charge-fill text-danger"></i>
                        <span>Nhiệm Vụ Kiếm Thêm Biblio Xu</span>
                    </div>
                    <span class="nb-sec-badge-alt">Tích Xu không giới hạn</span>
                </div>

                <div class="nb-missions-list">
                    <div class="nb-mission-card">
                        <div class="nb-mc-icon bg-orange"><i class="bi bi-person-plus-fill"></i></div>
                        <div class="nb-mc-info">
                            <div class="nb-mc-title">Đăng ký thành viên mới Biblio</div>
                            <div class="nb-mc-desc">Tạo tài khoản và kích hoạt email thành công nhận ngay Xu chào mừng.</div>
                        </div>
                        <div class="nb-mc-reward">+100 Xu</div>
                        <div class="nb-mc-btn">
                            <span class="nb-mc-done"><i class="bi bi-check-circle-fill"></i> Đã hoàn thành</span>
                        </div>
                    </div>

                    <div class="nb-mission-card">
                        <div class="nb-mc-icon bg-blue"><i class="bi bi-bag-check-fill"></i></div>
                        <div class="nb-mc-info">
                            <div class="nb-mc-title">Mua sắm sách yêu thích</div>
                            <div class="nb-mc-desc">Nhận 1 Xu cho mỗi 1.000₫ thanh toán đơn hàng thành công (Tích lũy theo từng hạng thành viên).</div>
                        </div>
                        <div class="nb-mc-reward">Tích 1% - 3%</div>
                        <div class="nb-mc-btn">
                            <a href="<?php echo esc_url(home_url('/san-pham/')); ?>" class="nb-btn-sm-orange">Mua sách ngay</a>
                        </div>
                    </div>

                    <div class="nb-mission-card">
                        <div class="nb-mc-icon bg-green"><i class="bi bi-star-fill"></i></div>
                        <div class="nb-mc-info">
                            <div class="nb-mc-title">Đánh giá sản phẩm sách đã mua</div>
                            <div class="nb-mc-desc">Chia sẻ cảm nhận chi tiết và hình ảnh thực tế sau khi đọc sách.</div>
                        </div>
                        <div class="nb-mc-reward">+50 Xu / cuốn</div>
                        <div class="nb-mc-btn">
                            <a href="<?php echo esc_url(function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount')) : home_url('/tai-khoan/')); ?>" class="nb-btn-sm-outline">Xem đơn mua</a>
                        </div>
                    </div>

                    <div class="nb-mission-card">
                        <div class="nb-mc-icon bg-purple"><i class="bi bi-cake2-fill"></i></div>
                        <div class="nb-mc-info">
                            <div class="nb-mc-title">Quà tặng sinh nhật thành viên</div>
                            <div class="nb-mc-desc">Nhận Xu và voucher độc quyền vào ngày sinh nhật của bạn trong tháng.</div>
                        </div>
                        <div class="nb-mc-reward">+200 Xu</div>
                        <div class="nb-mc-btn">
                            <span class="nb-mc-pending"><i class="bi bi-hourglass-split"></i> Tháng sinh nhật</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. KHO ĐỔI QUÀ THƯỞNG (REWARDS REDEMPTION STORE) -->
            <section class="nb-points-section" id="nbRewardsStore">
                <div class="nb-sec-header">
                    <div class="nb-sec-title">
                        <i class="bi bi-shop text-primary"></i>
                        <span>Kho Đổi Quà Biblio Xu</span>
                    </div>
                    <div class="nb-rewards-filter-tabs">
                        <button type="button" class="nb-rf-tab active" data-filter="all">Tất cả quà</button>
                        <button type="button" class="nb-rf-tab" data-filter="voucher">Mã Voucher</button>
                        <button type="button" class="nb-rf-tab" data-filter="physical">Phụ kiện & Hiện vật</button>
                    </div>
                </div>

                <div class="nb-rewards-grid">
                    <?php if (!empty($rewards)): ?>
                        <?php foreach ($rewards as $r):
                            $can_redeem = ($balance >= $r->points && $r->stock > 0);
                        ?>
                            <div class="nb-reward-card" data-type="<?php echo esc_attr($r->type); ?>">
                                <div class="nb-rc-badge-type">
                                    <?php if ($r->type === 'voucher'): ?>
                                        <i class="bi bi-ticket-perforated-fill"></i> Mã Giảm Giá
                                    <?php else: ?>
                                        <i class="bi bi-box-seam-fill"></i> Quà Tặng Độc Quyền
                                    <?php endif; ?>
                                </div>

                                <div class="nb-rc-image-wrap">
                                    <?php if ($r->type === 'voucher'): ?>
                                        <div class="nb-rc-voucher-thumb">
                                            <span class="nb-rc-vt-tag">VOUCHER</span>
                                            <strong><?php echo esc_html($r->coupon_code ?: 'GIẢM GIÁ'); ?></strong>
                                        </div>
                                    <?php else: ?>
                                        <div class="nb-rc-physical-thumb">
                                            <i class="bi bi-gift-fill"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="nb-rc-content">
                                    <h4 class="nb-rc-name"><?php echo esc_html($r->name); ?></h4>
                                    <p class="nb-rc-desc"><?php echo esc_html($r->description); ?></p>
                                    
                                    <div class="nb-rc-footer">
                                        <div class="nb-rc-price">
                                            <span class="nb-rc-pts"><?php echo esc_html(number_format_i18n($r->points)); ?></span>
                                            <span class="nb-rc-unit">Xu</span>
                                        </div>
                                        <div class="nb-rc-stock">
                                            Còn lại: <strong><?php echo esc_html($r->stock); ?></strong>
                                        </div>
                                    </div>

                                    <button type="button"
                                            class="nb-btn-redeem-action <?php echo $can_redeem ? 'btn-can-redeem' : 'btn-disabled'; ?>"
                                            data-id="<?php echo esc_attr($r->id); ?>"
                                            data-name="<?php echo esc_attr($r->name); ?>"
                                            data-points="<?php echo esc_attr($r->points); ?>"
                                            data-nonce="<?php echo esc_attr($nonce); ?>"
                                            <?php disabled(!$can_redeem); ?>>
                                        <?php if ($r->stock <= 0): ?>
                                            <span>Đã hết quà</span>
                                        <?php elseif ($balance < $r->points): ?>
                                            <span>Thiếu <?php echo esc_html(number_format_i18n($r->points - $balance)); ?> Xu</span>
                                        <?php else: ?>
                                            <i class="bi bi-bag-heart-fill"></i> <span>Đổi ngay</span>
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="nb-empty-state">Hiện tại kho quà đang được cập nhật thêm sản phẩm mới. Vui lòng quay lại sau!</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 5. BẢNG ĐẶC QUYỀN HẠNG THÀNH VIÊN -->
            <section class="nb-points-section">
                <div class="nb-sec-header">
                    <div class="nb-sec-title">
                        <i class="bi bi-shield-shaded text-warning"></i>
                        <span>Đặc Quyền 4 Hạng Thành Viên Biblio Club</span>
                    </div>
                </div>

                <div class="nb-tiers-table-grid">
                    <div class="nb-tier-col <?php echo $tier['tier'] === 'Đồng' ? 'active-tier' : ''; ?>">
                        <div class="nb-tc-head color-bronze">
                            <i class="bi bi-award-fill"></i>
                            <h3>Hạng Đồng</h3>
                            <div class="nb-tc-cond">Từ 3 đơn hoặc 300k & 7 ngày</div>
                        </div>
                        <ul class="nb-tc-features">
                            <li><i class="bi bi-check2"></i> Tích lũy <strong>1%</strong> Xu cho mỗi đơn</li>
                            <li><i class="bi bi-check2"></i> 1 Voucher 10.000₫ mỗi tháng</li>
                            <li><i class="bi bi-check2"></i> Voucher sinh nhật 15.000₫</li>
                            <li><i class="bi bi-check2"></i> Thứ 4 Sách Trợ Giá Sinh Viên</li>
                        </ul>
                    </div>

                    <div class="nb-tier-col <?php echo $tier['tier'] === 'Bạc' ? 'active-tier' : ''; ?>">
                        <div class="nb-tc-head color-silver">
                            <i class="bi bi-shield-fill-check"></i>
                            <h3>Hạng Bạc</h3>
                            <div class="nb-tc-cond">Từ 8 đơn hoặc 1.000.000₫ & 30 ngày</div>
                        </div>
                        <ul class="nb-tc-features">
                            <li><i class="bi bi-check2"></i> Tích lũy <strong>1.5%</strong> Xu cho mỗi đơn</li>
                            <li><i class="bi bi-check2"></i> <strong>1 Mã Freeship 15k</strong> mỗi tháng</li>
                            <li><i class="bi bi-check2"></i> Voucher sinh nhật 30.000₫</li>
                            <li><i class="bi bi-check2"></i> Ưu tiên bọc chống sốc 2 lớp</li>
                        </ul>
                    </div>

                    <div class="nb-tier-col <?php echo $tier['tier'] === 'Vàng' ? 'active-tier' : ''; ?>">
                        <div class="nb-tc-head color-gold">
                            <i class="bi bi-trophy-fill"></i>
                            <h3>Hạng Vàng</h3>
                            <div class="nb-tc-cond">Từ 20 đơn hoặc 3.000.000₫ & 90 ngày</div>
                        </div>
                        <ul class="nb-tc-features">
                            <li><i class="bi bi-check2"></i> Tích lũy <strong>2%</strong> Xu cho mỗi đơn</li>
                            <li><i class="bi bi-check2"></i> <strong>2 Mã Freeship 25k</strong> mỗi tháng</li>
                            <li><i class="bi bi-check2"></i> Voucher sinh nhật VIP 50.000₫</li>
                            <li><i class="bi bi-check2"></i> Flash Sale sớm 30 phút</li>
                        </ul>
                    </div>

                    <div class="nb-tier-col <?php echo $tier['tier'] === 'Kim Cương' ? 'active-tier' : ''; ?>">
                        <div class="nb-tc-head color-diamond">
                            <i class="bi bi-gem"></i>
                            <h3>Hạng Kim Cương</h3>
                            <div class="nb-tc-cond">Từ 40 đơn hoặc 6.000.000₫ & 180 ngày</div>
                        </div>
                        <ul class="nb-tc-features">
                            <li><i class="bi bi-check2"></i> Tích lũy <strong>3%</strong> Xu cao nhất hệ thống</li>
                            <li><i class="bi bi-check2"></i> <strong>4 Mã Freeship 40k</strong> toàn quốc</li>
                            <li><i class="bi bi-check2"></i> Voucher quà sinh nhật VIP 100.000₫</li>
                            <li><i class="bi bi-check2"></i> CSKH VIP 24/7 & Quà Tết độc quyền</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 6. LỊCH SỬ BIẾN ĐỘNG XU (LEDGER TIMELINE) -->
            <section class="nb-points-section">
                <div class="nb-sec-header">
                    <div class="nb-sec-title">
                        <i class="bi bi-clock-history text-secondary"></i>
                        <span>Lịch Sử Biến Động Biblio Xu</span>
                    </div>
                    <div class="nb-history-filter-tabs">
                        <button type="button" class="nb-hf-tab active" data-filter="all">Tất cả</button>
                        <button type="button" class="nb-hf-tab" data-filter="plus">Đã nhận (+)</button>
                        <button type="button" class="nb-hf-tab" data-filter="minus">Đã dùng (-)</button>
                    </div>
                </div>

                <div class="nb-ledger-history-table">
                    <?php if (!empty($history)): ?>
                        <?php foreach ($history as $item):
                            $is_plus = ($item->amount > 0);
                        ?>
                            <div class="nb-ledger-row" data-dir="<?php echo $is_plus ? 'plus' : 'minus'; ?>">
                                <div class="nb-lr-icon <?php echo $is_plus ? 'icon-plus' : 'icon-minus'; ?>">
                                    <i class="bi <?php echo $is_plus ? 'bi-plus-circle-fill' : 'bi-dash-circle-fill'; ?>"></i>
                                </div>
                                <div class="nb-lr-info">
                                    <div class="nb-lr-desc"><?php echo esc_html($item->description); ?></div>
                                    <div class="nb-lr-meta">
                                        <span><i class="bi bi-calendar3"></i> <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($item->created_at))); ?></span>
                                        <?php if ($item->reference): ?>
                                            <span>• Mã: <code><?php echo esc_html($item->reference); ?></code></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="nb-lr-amount <?php echo $is_plus ? 'val-plus' : 'val-minus'; ?>">
                                    <?php echo ($is_plus ? '+' : '') . esc_html(number_format_i18n($item->amount)); ?> Xu
                                </div>
                                <div class="nb-lr-balance">
                                    Số dư: <strong><?php echo esc_html(number_format_i18n($item->balance)); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="nb-empty-state">Bạn chưa có giao dịch biến động Xu nào.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 7. FAQ & ĐIỀU KHOẢN SỬ DỤNG XU -->
            <section class="nb-points-section nb-faq-section">
                <div class="nb-sec-title mb-3">
                    <i class="bi bi-question-circle-fill text-info"></i>
                    <span>Câu Hỏi Thường Gặp Về Biblio Xu</span>
                </div>
                <div class="nb-faq-accordion">
                    <details class="nb-faq-item">
                        <summary>1. Biblio Xu là gì và có giá trị như thế nào?</summary>
                        <div class="nb-faq-answer">
                            Biblio Xu là điểm thưởng thành viên độc quyền của sàn sách Biblio. Cứ mỗi 1 Xu tích lũy tương đương 100₫. Bạn có thể sử dụng trực tiếp Biblio Xu tại bước Thanh toán để giảm trừ trực tiếp tiền mua sách hoặc dùng để đổi voucher và quà tặng lưu niệm tại Kho Quà.
                        </div>
                    </details>
                    <details class="nb-faq-item">
                        <summary>2. Điểm danh hằng ngày hoạt động ra sao?</summary>
                        <div class="nb-faq-answer">
                            Mỗi ngày đăng nhập vào website và bấm "Điểm danh", bạn sẽ nhận ngay 100 Xu. Nếu duy trì chuỗi 7 ngày liên tiếp không ngắt quãng, bạn sẽ nhận được phần thưởng đặc biệt 200 Xu vào ngày thứ 7!
                        </div>
                    </details>
                    <details class="nb-faq-item">
                        <summary>3. Biblio Xu có hạn sử dụng không?</summary>
                        <div class="nb-faq-answer">
                            Biblio Xu có thời hạn sử dụng là 12 tháng kể từ ngày giao dịch phát sinh. Hệ thống sẽ gửi thông báo nhắc nhở trước 30 ngày nếu bạn có Xu sắp hết hạn.
                        </div>
                    </details>
                    <details class="nb-faq-item">
                        <summary>4. Làm sao để thăng hạng thành viên nhanh hơn?</summary>
                        <div class="nb-faq-answer">
                            Hạng thành viên (Đồng, Bạc, Vàng, Kim Cương) được tính tự động dựa trên tổng số tiền các đơn hàng giao thành công. Khi đạt mốc chi tiêu mới, tài khoản của bạn sẽ tự động được thăng hạng và hưởng thêm các đặc quyền tích Xu và mã Freeship.
                        </div>
                    </details>
                </div>
            </section>

        </div>

        <!-- MODAL XÁC NHẬN ĐỔI QUÀ -->
        <div class="nb-modal-overlay" id="nbRedeemModal" hidden style="display: none !important;" role="dialog" aria-modal="true">
            <div class="nb-modal-box">
                <div class="nb-modal-header">
                    <h3>Xác nhận đổi quà thưởng</h3>
                    <button type="button" class="nb-modal-close" id="nbCloseRedeemModal" aria-label="Đóng">&times;</button>
                </div>
                <div class="nb-modal-body">
                    <div class="nb-mb-icon"><i class="bi bi-gift-fill text-warning"></i></div>
                    <p class="nb-mb-text">
                        Bạn có chắc chắn muốn dùng <strong id="nbModalPointsText">0</strong> Biblio Xu để đổi phần quà:
                    </p>
                    <div class="nb-mb-reward-name" id="nbModalRewardName">...</div>
                    <div class="nb-mb-notice">
                        <i class="bi bi-info-circle"></i> Nếu là voucher, mã sẽ được chuyển ngay vào "Voucher của tôi" để bạn sử dụng ngay khi mua sách.
                    </div>
                </div>
                <div class="nb-modal-footer">
                    <button type="button" class="nb-btn-modal-cancel" id="nbCancelRedeemBtn">Hủy</button>
                    <button type="button" class="nb-btn-modal-confirm" id="nbConfirmRedeemBtn">
                        <i class="bi bi-check-lg"></i> Xác nhận đổi
                    </button>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
            var globalNonce = "<?php echo esc_js($nonce); ?>";

            // Xử lý điểm danh AJAX
            var btnCheckin = document.getElementById('nbBtnDoCheckin');
            if (btnCheckin) {
                btnCheckin.addEventListener('click', function() {
                    btnCheckin.disabled = true;
                    btnCheckin.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';

                    var formData = new FormData();
                    formData.append('action', 'nbook_daily_checkin_streak');
                    formData.append('nonce', globalNonce);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            // 1. Hiển thị thông báo Toast nổi bật trực tiếp trên màn hình
                            var toast = document.getElementById('nbCheckinScreenToast');
                            if (toast) {
                                var titleEl = document.getElementById('nbStTitle');
                                var msgEl = document.getElementById('nbStMsg');
                                if (titleEl) titleEl.textContent = '🎉 Điểm danh nhận Xu thành công!';
                                if (msgEl) {
                                    msgEl.innerHTML = '<strong>+' + data.data.points_added + ' Biblio Xu</strong> đã được cộng vào ví của bạn (Chuỗi liên tục: <strong>' + data.data.streak + ' ngày</strong>).';
                                }
                                toast.classList.add('show');
                                setTimeout(function() {
                                    toast.classList.remove('show');
                                }, 5000);
                            }

                            // 2. Hiển thị thông báo inline trực quan ngay dưới nút bấm
                            var alertBox = document.getElementById('nbCheckinAlertBox');
                            if (alertBox) {
                                alertBox.className = 'nb-checkin-alert-box alert-success show';
                                alertBox.innerHTML = '<i class="bi bi-patch-check-fill me-2 text-success"></i> ' + data.data.message;
                                alertBox.style.display = 'flex';
                            }

                            // 3. Cập nhật số dư Xu trên màn hình với hiệu ứng bump
                            var disp = document.getElementById('nbUserPointsDisplay');
                            if (disp) {
                                disp.textContent = data.data.balance_formatted;
                                disp.classList.add('nb-points-bump');
                                setTimeout(function() { disp.classList.remove('nb-points-bump'); }, 1200);
                            }

                            // 4. Cập nhật chuỗi streak
                            var strText = document.getElementById('nbStreakDaysText');
                            if (strText) strText.textContent = data.data.streak + ' ngày';

                            // 5. Cập nhật nút bấm
                            btnCheckin.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Hôm nay bạn đã nhận xu rồi ✓';
                            btnCheckin.disabled = true;
                            btnCheckin.classList.add('btn-claimed');

                            var heroCheckinBtn = document.querySelector('.nb-btn-phc-primary');
                            if (heroCheckinBtn) {
                                heroCheckinBtn.innerHTML = '<i class="bi bi-calendar2-check-fill"></i> <span>Đã điểm danh hôm nay</span>';
                            }

                            // 6. Đánh dấu ô ngày hiện tại
                            var todayItem = document.getElementById('streakDay' + data.data.day_in_cycle);
                            if (todayItem) {
                                todayItem.className = 'nb-streak-item status-claimed';
                                var statusSpan = todayItem.querySelector('.nb-si-status');
                                if (statusSpan) statusSpan.innerHTML = '<span class="badge-claimed"><i class="bi bi-check2"></i> Đã nhận</span>';
                            }
                        } else {
                            var alertBox = document.getElementById('nbCheckinAlertBox');
                            if (alertBox) {
                                alertBox.className = 'nb-checkin-alert-box alert-warning show';
                                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> ' + (data.data.message || 'Có lỗi xảy ra.');
                                alertBox.style.display = 'flex';
                            }
                            var toast = document.getElementById('nbCheckinScreenToast');
                            if (toast) {
                                var titleEl = document.getElementById('nbStTitle');
                                var msgEl = document.getElementById('nbStMsg');
                                if (titleEl) titleEl.textContent = 'Thông báo điểm danh';
                                if (msgEl) msgEl.textContent = data.data.message || 'Có lỗi xảy ra.';
                                toast.classList.add('show');
                                setTimeout(function() { toast.classList.remove('show'); }, 4000);
                            }
                            btnCheckin.disabled = false;
                            btnCheckin.innerHTML = '<i class="bi bi-hand-index-thumb-fill"></i> Điểm danh ngay hôm nay (+100 Xu)';
                        }
                    })
                    .catch(function(err) {
                        var alertBox = document.getElementById('nbCheckinAlertBox');
                        if (alertBox) {
                            alertBox.className = 'nb-checkin-alert-box alert-danger show';
                            alertBox.innerHTML = '<i class="bi bi-wifi-off me-2"></i> Lỗi kết nối máy chủ, vui lòng thử lại sau.';
                            alertBox.style.display = 'flex';
                        }
                        btnCheckin.disabled = false;
                        btnCheckin.innerHTML = '<i class="bi bi-hand-index-thumb-fill"></i> Điểm danh ngay hôm nay (+100 Xu)';
                    });
                });
            }

            // Xử lý Modal Đổi Quà
            var modal = document.getElementById('nbRedeemModal');
            var selectedReward = null;

            function openRedeemModal(reward) {
                selectedReward = reward;
                var elName = document.getElementById('nbModalRewardName');
                var elPoints = document.getElementById('nbModalPointsText');
                if (elName) elName.textContent = selectedReward.name;
                if (elPoints) elPoints.textContent = selectedReward.points;
                if (modal) {
                    modal.removeAttribute('hidden');
                    modal.classList.add('open');
                    modal.style.setProperty('display', 'flex', 'important');
                    document.body.style.overflow = 'hidden';
                }
            }

            function closeRedeemModal() {
                if (modal) {
                    modal.setAttribute('hidden', 'true');
                    modal.classList.remove('open');
                    modal.style.setProperty('display', 'none', 'important');
                    document.body.style.overflow = '';
                }
                selectedReward = null;
            }

            // Click vào các nút Đổi ngay
            document.querySelectorAll('.btn-can-redeem').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openRedeemModal({
                        id: btn.getAttribute('data-id'),
                        name: btn.getAttribute('data-name'),
                        points: btn.getAttribute('data-points')
                    });
                });
            });

            // Nút đóng dấu X
            var btnCloseM = document.getElementById('nbCloseRedeemModal');
            if (btnCloseM) {
                ['click', 'touchend'].forEach(function(evt) {
                    btnCloseM.addEventListener(evt, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeRedeemModal();
                    });
                });
            }

            // Nút Hủy
            var btnCancelM = document.getElementById('nbCancelRedeemBtn');
            if (btnCancelM) {
                ['click', 'touchend'].forEach(function(evt) {
                    btnCancelM.addEventListener(evt, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeRedeemModal();
                    });
                });
            }

            // Chống click/chạm bên trong hộp thoại làm đóng modal
            var modalBox = modal ? modal.querySelector('.nb-modal-box') : null;
            if (modalBox) {
                ['click', 'touchend'].forEach(function(evt) {
                    modalBox.addEventListener(evt, function(e) {
                        e.stopPropagation();
                    });
                });
            }

            // Chạm ra ngoài màn hình (vùng nền đen overlay) để đóng
            if (modal) {
                ['click', 'touchend'].forEach(function(evt) {
                    modal.addEventListener(evt, function(e) {
                        if (e.target === modal) {
                            e.preventDefault();
                            closeRedeemModal();
                        }
                    });
                });
            }

            // Phím Escape để thoát
            document.addEventListener('keydown', function(e) {
                if ((e.key === 'Escape' || e.keyCode === 27) && modal && (modal.classList.contains('open') || modal.style.display === 'flex' || !modal.hasAttribute('hidden'))) {
                    closeRedeemModal();
                }
            });

            var btnConfirmM = document.getElementById('nbConfirmRedeemBtn');
            if (btnConfirmM) {
                btnConfirmM.addEventListener('click', function() {
                    if (!selectedReward) return;
                    btnConfirmM.disabled = true;
                    btnConfirmM.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang đổi...';

                    var formData = new FormData();
                    formData.append('action', 'nbook_redeem_reward_v2');
                    formData.append('reward_id', selectedReward.id);
                    formData.append('nonce', globalNonce);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        btnConfirmM.disabled = false;
                        btnConfirmM.innerHTML = '<i class="bi bi-check-lg"></i> Xác nhận đổi';
                        closeRedeemModal();

                        if (data.success) {
                            alert(data.data.message);
                            if (data.data.is_voucher) {
                                if (confirm('Mã đã sẵn sàng trong "Voucher của tôi". Bạn có muốn xem ví voucher ngay bây giờ không?')) {
                                    window.location.href = data.data.voucher_url;
                                    return;
                                }
                            }
                            location.reload();
                        } else {
                            alert(data.data.message || 'Không thể đổi quà.');
                        }
                    })
                    .catch(function(err) {
                        btnConfirmM.disabled = false;
                        btnConfirmM.innerHTML = '<i class="bi bi-check-lg"></i> Xác nhận đổi';
                        alert('Lỗi kết nối máy chủ.');
                    });
                });
            }

            // Bộ lọc kho quà
            document.querySelectorAll('.nb-rf-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.nb-rf-tab').forEach(function(t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    var filter = tab.getAttribute('data-filter');

                    document.querySelectorAll('.nb-reward-card').forEach(function(card) {
                        if (filter === 'all' || card.getAttribute('data-type') === filter) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Bộ lọc lịch sử
            document.querySelectorAll('.nb-hf-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.nb-hf-tab').forEach(function(t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    var filter = tab.getAttribute('data-filter');

                    document.querySelectorAll('.nb-ledger-row').forEach(function(row) {
                        if (filter === 'all' || row.getAttribute('data-dir') === filter) {
                            row.style.display = 'flex';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });

        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * =========================================================================
     * GIAO DIỆN HOÀN THIỆN: VOUCHER CỦA TÔI (Shopee Voucher Wallet Style)
     * =========================================================================
     */
    public function render_my_vouchers() {
        if (!is_user_logged_in()) {
            $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
            return '
            <div class="nb-auth-required-box">
                <div class="nb-arb-icon"><i class="bi bi-ticket-perforated-fill"></i></div>
                <h2>Ví Voucher Biblio của bạn</h2>
                <p>Vui lòng đăng nhập để xem danh sách voucher đang có, lưu mã ưu đãi mới và áp dụng khi mua sách.</p>
                <a href="' . esc_url($account_url) . '#login" class="nb-btn-orange nb-open-auth-modal" data-tab="login"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập ngay</a>
            </div>';
        }

        $uid = get_current_user_id();
        $vouchers = $this->get_user_vouchers($uid);
        $nonce = wp_create_nonce('nbook_voucher_action');
        $promo_page_url = home_url('/khuyen-mai/');
        $shop_url = home_url('/san-pham/');
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/gio-hang/');

        // Phân loại đếm số lượng
        $count_all = count($vouchers);
        $count_shipping = 0;
        $count_discount = 0;
        $count_points = 0;
        $count_special = 0;

        foreach ($vouchers as $v) {
            if ($v['custom_type'] === 'shipping' || $v['free_shipping']) $count_shipping++;
            elseif ($v['custom_type'] === 'points') $count_points++;
            elseif (in_array($v['custom_type'], ['student', 'new_user', 'flash', 'event'], true)) $count_special++;
            else $count_discount++;
        }

        ob_start();
        ?>
        <div class="nb-voucher-wallet-wrap" id="nbVoucherWalletWrap">

            <!-- 1. HEADER & KHUNG NHẬP MÃ VOUCHER -->
            <div class="nb-vw-hero-box">
                <div class="nb-vw-hero-content">
                    <div class="nb-vw-title-row">
                        <div class="nb-vw-main-title">
                            <i class="bi bi-wallet2 text-warning"></i>
                            <span>Ví Voucher Của Tôi</span>
                            <span class="nb-vw-total-badge" id="nbVoucherTotalCount"><?php echo esc_html($count_all); ?> mã khả dụng</span>
                        </div>
                        <a href="<?php echo esc_url($promo_page_url); ?>" class="nb-btn-explore-vouchers">
                            <i class="bi bi-compass"></i> Tìm thêm mã tại Trạm Voucher
                        </a>
                    </div>
                    <p class="nb-vw-subtitle">
                        Toàn bộ mã giảm giá, voucher freeship và ưu đãi tích lũy được lưu tại đây. Hãy sao chép hoặc bấm "Dùng ngay" khi đặt mua sách!
                    </p>
                </div>

                <!-- Form nhập mã lưu vào ví -->
                <div class="nb-vw-input-bar">
                    <div class="nb-vw-input-inner">
                        <i class="bi bi-ticket-detailed-fill nb-vw-input-icon"></i>
                        <input type="text" id="nbInputVoucherCode" placeholder="Nhập mã giảm giá hoặc mã quà tặng Biblio..." autocomplete="off">
                        <button type="button" class="nb-btn-save-code" id="nbBtnSaveVoucherCode" data-nonce="<?php echo esc_attr($nonce); ?>">
                            <span>LƯU VÀO VÍ</span>
                        </button>
                    </div>
                    <div class="nb-vw-input-feedback" id="nbVoucherFeedback" hidden></div>
                </div>
            </div>

            <!-- 2. BỘ LỌC TABS PHÂN LOẠI VOUCHER CHUẨN SHOPEE -->
            <div class="nb-vw-tabs-nav">
                <button type="button" class="nb-vt-btn active" data-tab="all">
                    <span>Tất cả</span>
                    <b class="nb-vt-badge"><?php echo esc_html($count_all); ?></b>
                </button>
                <button type="button" class="nb-vt-btn" data-tab="shipping">
                    <span>Miễn phí vận chuyển</span>
                    <b class="nb-vt-badge"><?php echo esc_html($count_shipping); ?></b>
                </button>
                <button type="button" class="nb-vt-btn" data-tab="discount">
                    <span>Giảm giá sách</span>
                    <b class="nb-vt-badge"><?php echo esc_html($count_discount); ?></b>
                </button>
                <button type="button" class="nb-vt-btn" data-tab="points">
                    <span>Hoàn Xu Biblio</span>
                    <b class="nb-vt-badge"><?php echo esc_html($count_points); ?></b>
                </button>
                <button type="button" class="nb-vt-btn" data-tab="special">
                    <span>Ưu đãi độc quyền</span>
                    <b class="nb-vt-badge"><?php echo esc_html($count_special); ?></b>
                </button>
            </div>

            <!-- 3. DANH SÁCH THẺ VOUCHER RĂNG CƯA (SHOPEE TICKET CARDS) -->
            <div class="nb-vw-cards-grid" id="nbVouchersGrid">
                <?php if (!empty($vouchers)): ?>
                    <?php foreach ($vouchers as $v):
                        // Xác định nhóm tab
                        $tab_group = 'discount';
                        if ($v['custom_type'] === 'shipping' || $v['free_shipping']) $tab_group = 'shipping';
                        elseif ($v['custom_type'] === 'points') $tab_group = 'points';
                        elseif (in_array($v['custom_type'], ['student', 'new_user', 'flash', 'event'], true)) $tab_group = 'special';

                        // Định dạng nhãn và giá trị
                        $badge_class = 'type-' . $tab_group;
                        $badge_name = 'GIẢM GIÁ';
                        $amount_display = '';

                        if ($tab_group === 'shipping') {
                            $badge_name = 'FREESHIP';
                            $amount_display = '25.000₫';
                        } elseif ($tab_group === 'points') {
                            $badge_name = 'HOÀN XU';
                            $amount_display = 'HOÀN ' . $v['amount'] . '%';
                        } elseif ($v['discount_type'] === 'percent') {
                            $amount_display = 'GIẢM ' . $v['amount'] . '%';
                            if ($v['custom_type'] === 'student') $badge_name = 'SINH VIÊN';
                            elseif ($v['custom_type'] === 'new_user') $badge_name = 'BẠN ĐỌC MỚI';
                        } else {
                            $amount_display = 'GIẢM ' . number_format_i18n($v['amount']) . '₫';
                            if ($v['custom_type'] === 'new_user') $badge_name = 'BẠN ĐỌC MỚI';
                            elseif ($v['custom_type'] === 'flash') $badge_name = 'FLASH SALE';
                        }

                        $cond_text = $v['min_amount'] > 0 ? 'Đơn từ ' . number_format_i18n($v['min_amount']) . '₫' : 'Mọi đơn hàng';
                    ?>
                        <div class="nb-ticket-card <?php echo $v['is_expired'] ? 'card-expired' : ''; ?>" data-group="<?php echo esc_attr($tab_group); ?>">
                            <!-- Nửa trái: Mức giảm + Răng cưa -->
                            <div class="nb-tc-left <?php echo esc_attr($badge_class); ?>">
                                <div class="nb-tc-notch notch-top"></div>
                                <div class="nb-tc-notch notch-bottom"></div>
                                <div class="nb-tc-type-badge"><?php echo esc_html($badge_name); ?></div>
                                <div class="nb-tc-amount"><?php echo esc_html($amount_display); ?></div>
                                <div class="nb-tc-cond"><?php echo esc_html($cond_text); ?></div>
                            </div>

                            <!-- Nửa phải: Chi tiết, code & CTA -->
                            <div class="nb-tc-right">
                                <div class="nb-tc-header">
                                    <h4 class="nb-tc-title"><?php echo esc_html($v['title']); ?></h4>
                                    <button type="button" class="nb-btn-remove-voucher" title="Gỡ voucher khỏi ví" data-code="<?php echo esc_attr($v['code']); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>

                                <div class="nb-tc-code-row">
                                    <div class="nb-tc-code-wrap" title="Bấm để sao chép mã" data-code="<?php echo esc_attr($v['code']); ?>">
                                        <span class="nb-tc-code-text"><?php echo esc_html($v['code']); ?></span>
                                        <i class="bi bi-copy nb-tc-copy-icon"></i>
                                    </div>
                                    <div class="nb-tc-status-badge">
                                        <?php if ($v['is_expired']): ?>
                                            <span class="badge-exp text-danger"><i class="bi bi-exclamation-circle"></i> Đã hết hạn</span>
                                        <?php else: ?>
                                            <span class="badge-valid text-success"><i class="bi bi-check-circle"></i> Khả dụng</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="nb-tc-meta-row">
                                    <div class="nb-tc-exp">
                                        <i class="bi bi-clock"></i> HSD: <strong><?php echo esc_html($v['expires_at']); ?></strong>
                                    </div>
                                    <div class="nb-tc-actions">
                                        <button type="button" class="nb-btn-conditions-toggle" data-target="cond-<?php echo esc_attr($v['id']); ?>">
                                            Điều kiện <i class="bi bi-chevron-down"></i>
                                        </button>
                                        <a href="<?php echo esc_url($cart_url); ?>?apply_coupon=<?php echo esc_attr($v['code']); ?>" class="nb-btn-use-voucher">
                                            DÙNG NGAY
                                        </a>
                                    </div>
                                </div>

                                <!-- Accordion điều kiện chi tiết -->
                                <div class="nb-tc-conditions-box" id="cond-<?php echo esc_attr($v['id']); ?>" hidden>
                                    <ul>
                                        <li><strong>Đơn hàng tối thiểu:</strong> <?php echo esc_html($cond_text); ?>.</li>
                                        <?php if ($v['max_amount'] > 0): ?>
                                            <li><strong>Mức giảm tối đa:</strong> <?php echo esc_html(number_format_i18n($v['max_amount'])); ?>₫.</li>
                                        <?php endif; ?>
                                        <li><strong>Hạn sử dụng:</strong> Đến hết ngày <?php echo esc_html($v['expires_at']); ?>.</li>
                                        <li><strong>Phạm vi áp dụng:</strong> Áp dụng cho các đầu sách bán tại hệ thống Biblio.</li>
                                        <?php if ($v['custom_type'] === 'student'): ?>
                                            <li><strong>Lưu ý:</strong> Dành cho sinh viên NEU và các trường đại học đối tác.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="nb-empty-vouchers-box">
                        <i class="bi bi-ticket-perforated nb-ev-icon"></i>
                        <h3>Ví voucher của bạn đang trống</h3>
                        <p>Hãy lưu các mã giảm giá từ Trạm Voucher hoặc đổi quà từ điểm Biblio Xu để nhận ưu đãi mua sách nhé!</p>
                        <a href="<?php echo esc_url($promo_page_url); ?>" class="nb-btn-orange">
                            <i class="bi bi-gift-fill"></i> Khám phá Trạm Voucher Biblio
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 4. KHUNG HƯỚNG DẪN SỬ DỤNG VOUCHER -->
            <div class="nb-vw-guide-card">
                <div class="nb-gc-header">
                    <i class="bi bi-lightbulb-fill text-warning"></i>
                    <strong>Mẹo Sử Dụng Voucher Hiệu Quả Nhất Tại Biblio</strong>
                </div>
                <div class="nb-gc-grid">
                    <div class="nb-gc-item">
                        <div class="nb-gci-num">1</div>
                        <div class="nb-gci-text">
                            <strong>Chọn sách & Thêm vào giỏ</strong>
                            <span>Duyệt hơn 10.000 đầu sách hay và thêm vào giỏ hàng của bạn.</span>
                        </div>
                    </div>
                    <div class="nb-gc-item">
                        <div class="nb-gci-num">2</div>
                        <div class="nb-gci-text">
                            <strong>Bấm "Dùng ngay" hoặc sao chép mã</strong>
                            <span>Tại trang Giỏ hàng hoặc Thanh toán, chọn voucher từ ví hoặc dán mã vào ô ưu đãi.</span>
                        </div>
                    </div>
                    <div class="nb-gc-item">
                        <div class="nb-gci-num">3</div>
                        <div class="nb-gci-text">
                            <strong>Áp dụng kết hợp Biblio Xu</strong>
                            <span>Bạn hoàn toàn có thể kết hợp vừa dùng Voucher vừa trừ Điểm Xu để có giá sách tốt nhất!</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <script>
        (function() {
            var ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
            var voucherNonce = "<?php echo esc_js($nonce); ?>";

            // Sao chép mã voucher khi click
            document.querySelectorAll('.nb-tc-code-wrap').forEach(function(el) {
                el.addEventListener('click', function() {
                    var code = el.getAttribute('data-code');
                    if (!code) return;
                    navigator.clipboard.writeText(code).then(function() {
                        var originalHtml = el.innerHTML;
                        el.innerHTML = '<span class="text-success"><i class="bi bi-check2"></i> Đã chép mã!</span>';
                        setTimeout(function() {
                            el.innerHTML = originalHtml;
                        }, 2000);
                    }).catch(function() {
                        prompt('Sao chép mã voucher dưới đây:', code);
                    });
                });
            });

            // Toggle chi tiết điều kiện
            document.querySelectorAll('.nb-btn-conditions-toggle').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetId = btn.getAttribute('data-target');
                    var box = document.getElementById(targetId);
                    if (box) {
                        var isHidden = box.hasAttribute('hidden');
                        if (isHidden) {
                            box.removeAttribute('hidden');
                            btn.innerHTML = 'Thu gọn <i class="bi bi-chevron-up"></i>';
                        } else {
                            box.setAttribute('hidden', 'true');
                            btn.innerHTML = 'Điều kiện <i class="bi bi-chevron-down"></i>';
                        }
                    }
                });
            });

            // Bộ lọc Tab Voucher
            document.querySelectorAll('.nb-vt-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.nb-vt-btn').forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    var tab = btn.getAttribute('data-tab');

                    document.querySelectorAll('.nb-ticket-card').forEach(function(card) {
                        if (tab === 'all' || card.getAttribute('data-group') === tab) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Nhập mã lưu vào ví
            var btnSaveCode = document.getElementById('nbBtnSaveVoucherCode');
            var inputCode = document.getElementById('nbInputVoucherCode');
            var feedback = document.getElementById('nbVoucherFeedback');

            if (btnSaveCode && inputCode) {
                var doSave = function() {
                    var code = inputCode.value.trim();
                    if (!code) {
                        inputCode.focus();
                        return;
                    }

                    btnSaveCode.disabled = true;
                    btnSaveCode.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';
                    if (feedback) feedback.setAttribute('hidden', 'true');

                    var formData = new FormData();
                    formData.append('action', 'nbook_save_voucher_code');
                    formData.append('code', code);
                    formData.append('nonce', voucherNonce);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        btnSaveCode.disabled = false;
                        btnSaveCode.innerHTML = '<span>LƯU VÀO VÍ</span>';

                        if (feedback) {
                            feedback.removeAttribute('hidden');
                            if (data.success) {
                                feedback.className = 'nb-vw-input-feedback text-success';
                                feedback.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + data.data.message;
                                inputCode.value = '';
                                setTimeout(function() { location.reload(); }, 1200);
                            } else {
                                feedback.className = 'nb-vw-input-feedback text-danger';
                                feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> ' + data.data.message;
                            }
                        }
                    })
                    .catch(function(err) {
                        btnSaveCode.disabled = false;
                        btnSaveCode.innerHTML = '<span>LƯU VÀO VÍ</span>';
                        if (feedback) {
                            feedback.removeAttribute('hidden');
                            feedback.className = 'nb-vw-input-feedback text-danger';
                            feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Lỗi kết nối máy chủ.';
                        }
                    });
                };

                btnSaveCode.addEventListener('click', doSave);
                inputCode.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        doSave();
                    }
                });
            }

            // Gỡ mã khỏi ví
            document.querySelectorAll('.nb-btn-remove-voucher').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var code = btn.getAttribute('data-code');
                    if (!confirm('Bạn có chắc muốn gỡ mã "' + code + '" khỏi Ví voucher không?')) return;

                    var formData = new FormData();
                    formData.append('action', 'nbook_remove_saved_voucher');
                    formData.append('code', code);
                    formData.append('nonce', voucherNonce);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var card = btn.closest('.nb-ticket-card');
                            if (card) {
                                card.style.opacity = '0';
                                setTimeout(function() { card.remove(); }, 300);
                            }
                        } else {
                            alert(data.data.message || 'Không thể gỡ mã.');
                        }
                    });
                });
            });

        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
