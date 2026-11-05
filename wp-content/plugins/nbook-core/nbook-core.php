<?php
/**
 * Plugin Name: Nbook Core
 * Description: Plugin cốt lõi cho sàn sách Nbook - CPT Banner, Settings thông báo, Shortcode Sách bán chạy, Sách mới và AJAX tìm kiếm.
 * Version: 1.0.0
 * Author: Nbook Team
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-biblio-chatbot.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-biblio-admin-hub.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-biblio-points-voucher.php';

class Nbook_Core {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            if (!empty($GLOBALS['nbook_core_instance'])) {
                self::$instance = $GLOBALS['nbook_core_instance'];
            } else {
                self::$instance = new self();
            }
        }
        return self::$instance;
    }

    public function __construct() {
        self::$instance = $this;
        Biblio_Admin_Hub::instance();
        Biblio_Points_Voucher::instance();
        add_action('init', [$this, 'ensure_notifications_table']);
        add_action('init', [$this, 'ensure_points_tables']);
        add_action('init', [$this, 'ensure_chatbot_tables']);
        add_action('admin_menu', [$this, 'register_notifications_admin']);
        add_action('admin_menu', [$this, 'register_chatbot_admin_pages']);
        add_action('admin_post_nbook_send_notification', [$this, 'admin_send_notification']);
        add_action('admin_post_nbook_chatbot_save_faq', [$this, 'admin_chatbot_save_faq']);
        add_action('admin_post_nbook_chatbot_delete_faq', [$this, 'admin_chatbot_delete_faq']);
        add_action('wp_ajax_nbook_notifications', [$this, 'ajax_notifications']);
        add_action('wp_ajax_nopriv_nbook_notifications', [$this, 'ajax_notifications']);
        add_action('wp_ajax_nbook_mark_notifications_read', [$this, 'ajax_mark_notifications_read']);
        add_action('wp_ajax_nbook_mark_notification_read', [$this, 'ajax_mark_notification_read']);
        add_action('wp_ajax_nbook_chatbot_message', [$this, 'ajax_chatbot_message']);
        add_action('wp_ajax_nopriv_nbook_chatbot_message', [$this, 'ajax_chatbot_message']);
        add_action('wp_ajax_nbook_chatbot_transfer', [$this, 'ajax_chatbot_transfer']);
        add_action('wp_ajax_nopriv_nbook_chatbot_transfer', [$this, 'ajax_chatbot_transfer']);
        add_action('woocommerce_thankyou', [$this, 'notify_order_created'], 20);
        add_action('woocommerce_order_status_changed', [$this, 'notify_order_status_changed'], 20, 4);
        add_action('publish_post', [$this, 'notify_new_promotion'], 20, 2);
        add_action('add_meta_boxes_shop_coupon', [$this, 'add_coupon_meta_box']);
        add_action('save_post_shop_coupon', [$this, 'save_coupon_meta'], 10, 2);
        add_action('admin_init', [$this, 'seed_sample_coupons']);
        add_action('wp_ajax_nbook_save_coupon', [$this, 'ajax_save_coupon']);
        add_shortcode('nbook_available_vouchers', [$this, 'shortcode_available_vouchers']);
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_coupon_rules'], 20, 3);
        add_action('user_register', [$this, 'award_signup_points'], 20);
        add_action('transition_comment_status', [$this, 'award_review_points'], 20, 3);
        add_action('nbook_reward_points_earned', [$this, 'notify_reward_points'], 10, 2);
        add_action('nbook_voucher_awarded', [$this, 'notify_voucher'], 10, 2);
        add_shortcode('nbook_notifications', [$this, 'shortcode_notifications']);
        // Register CPT Banner
        add_action('init', [$this, 'register_banner_cpt']);
        add_action('add_meta_boxes', [$this, 'add_banner_meta_boxes']);
        add_action('save_post_banner', [$this, 'save_banner_meta']);

        // Register CPT About (nbook_about)
        add_action('init', [$this, 'register_about_cpt']);
        add_action('init', [$this, 'seed_default_about_items']);

        // Settings page
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_chatbot_assets']);
        add_action('wp_footer', [$this, 'render_chatbot_widget'], 999);

        // Shortcodes
        add_shortcode('nbook_bestsellers', [$this, 'shortcode_bestsellers']);
        add_shortcode('nbook_new_books', [$this, 'shortcode_new_books']);

        // AJAX Search
        add_action('wp_ajax_nbook_ajax_search', [$this, 'ajax_search_callback']);
        add_action('wp_ajax_nopriv_nbook_ajax_search', [$this, 'ajax_search_callback']);

        // Seed data on activation or first load
        add_action('admin_init', [$this, 'maybe_seed_data']);
        add_action('init', [$this, 'maybe_create_notifications_page'], 20);
        add_action('init', [$this, 'maybe_create_points_page'], 21);
        add_action('woocommerce_order_status_completed', [$this, 'award_order_points'], 20);
        add_action('woocommerce_checkout_create_order', [$this, 'save_checkout_points'], 20, 2);
        add_action('woocommerce_order_status_cancelled', [$this, 'refund_order_points'], 20);
        add_action('woocommerce_order_status_refunded', [$this, 'refund_order_points'], 20);
        add_action('wp_ajax_nbook_daily_checkin', [$this, 'ajax_daily_checkin']);
        add_action('wp_ajax_nbook_redeem_reward', [$this, 'ajax_redeem_reward']);
        add_action('admin_post_nbook_save_reward', [$this, 'admin_save_reward']);
        add_action('admin_post_nbook_adjust_points', [$this, 'admin_adjust_points']);
        add_action('admin_menu', [$this, 'register_points_admin']);
        add_shortcode('nbook_points', [$this, 'shortcode_points']);
        add_shortcode('nbook_my_vouchers', [$this, 'shortcode_my_vouchers']);
    }

    public function add_coupon_meta_box() {
        add_meta_box('nbook_coupon_rules', 'Điều kiện voucher Biblio', [$this, 'render_coupon_meta_box'], 'shop_coupon', 'normal', 'high');
    }

    public function render_coupon_meta_box($post) {
        wp_nonce_field('nbook_coupon_rules', 'nbook_coupon_rules_nonce');
        $fields = [
            'nbook_coupon_type' => ['Loại voucher', get_post_meta($post->ID, '_nbook_coupon_type', true) ?: 'percent', ['percent'=>'Giảm phần trăm','fixed'=>'Giảm tiền cố định','shipping'=>'Miễn phí/giảm phí vận chuyển','points'=>'Hoàn Xu/điểm thưởng','new_user'=>'Người dùng mới','student'=>'Sinh viên','category'=>'Theo danh mục/cuốn','flash'=>'Giờ vàng / Flash sale','event'=>'Sinh nhật / sự kiện']],
            'nbook_coupon_points_rate' => ['Tỷ lệ hoàn Xu', get_post_meta($post->ID, '_nbook_coupon_points_rate', true) ?: 0, null],
            'nbook_coupon_student_required' => ['Yêu cầu xác nhận sinh viên', get_post_meta($post->ID, '_nbook_coupon_student_required', true), null],
            'nbook_coupon_event_label' => ['Tên sự kiện', get_post_meta($post->ID, '_nbook_coupon_event_label', true), null],
            'nbook_coupon_flash_end' => ['Kết thúc giờ vàng', get_post_meta($post->ID, '_nbook_coupon_flash_end', true), null],
        ];
        ?>
        <p>WooCommerce quản lý mã, giá trị, mức giảm tối đa, đơn tối thiểu, phạm vi sản phẩm/danh mục, lượt dùng và hạn dùng ở phần Coupon Data.</p>
        <table class="form-table">
        <?php foreach ($fields as $key => $field): ?><tr><th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?></label></th><td>
        <?php if (is_array($field[2])): ?><select id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>"><?php foreach ($field[2] as $value => $label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($field[1], $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
        <?php elseif ($key === 'nbook_coupon_student_required'): ?><label><input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($field[1], '1'); ?>> Chỉ áp dụng khi email .edu.vn hoặc đã xác nhận</label>
        <?php elseif ($key === 'nbook_coupon_flash_end'): ?><input type="datetime-local" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($field[1] ? date('Y-m-d\TH:i', strtotime($field[1])) : ''); ?>">
        <?php else: ?><input class="regular-text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($field[1]); ?>"><?php endif; ?>
        </td></tr><?php endforeach; ?>
        </table>
        <?php
    }

    public function save_coupon_meta($post_id, $post) {
        if ($post->post_type !== 'shop_coupon' || !isset($_POST['nbook_coupon_rules_nonce']) || !wp_verify_nonce($_POST['nbook_coupon_rules_nonce'], 'nbook_coupon_rules') || !current_user_can('manage_woocommerce')) return;
        $keys = ['nbook_coupon_type','nbook_coupon_points_rate','nbook_coupon_event_label'];
        foreach ($keys as $key) update_post_meta($post_id, '_' . $key, sanitize_text_field($_POST[$key] ?? ''));
        update_post_meta($post_id, '_nbook_coupon_student_required', empty($_POST['nbook_coupon_student_required']) ? '0' : '1');
        $flash = sanitize_text_field($_POST['nbook_coupon_flash_end'] ?? '');
        update_post_meta($post_id, '_nbook_coupon_flash_end', $flash ? get_gmt_from_date(str_replace('T', ' ', $flash), 'Y-m-d H:i:s') : '');
    }

    public function validate_coupon_rules($valid, $coupon, $discounts = null) {
        if (!$valid || !$coupon instanceof WC_Coupon || !is_user_logged_in()) return $valid;
        $id = $coupon->get_id();
        $type = get_post_meta($id, '_nbook_coupon_type', true);
        $user = wp_get_current_user();
        if ($type === 'new_user' && wc_get_customer_order_count(get_current_user_id()) > 0) {
            wc_add_notice('Voucher này chỉ dành cho đơn hàng đầu tiên.', 'error'); return false;
        }
        if ($type === 'student' && get_post_meta($id, '_nbook_coupon_student_required', true) === '1' && !preg_match('/\.edu\.vn$/i', (string) $user->user_email) && get_user_meta($user->ID, '_nbook_student_verified', true) !== '1') {
            wc_add_notice('Voucher sinh viên yêu cầu email .edu.vn hoặc xác nhận sinh viên.', 'error'); return false;
        }
        $flash_end = get_post_meta($id, '_nbook_coupon_flash_end', true);
        if ($flash_end && strtotime($flash_end) < current_time('timestamp', true)) {
            wc_add_notice('Voucher giờ vàng đã hết thời gian.', 'error'); return false;
        }
        return $valid;
    }

    public function ajax_save_coupon() {
        check_ajax_referer('nbook_coupon', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Vui lòng đăng nhập.'], 401);
        $code = strtoupper(sanitize_text_field($_POST['code'] ?? ''));
        $id = wc_get_coupon_id_by_code($code);
        if (!$id) wp_send_json_error(['message' => 'Voucher không tồn tại.']);
        $saved = (array) get_user_meta(get_current_user_id(), '_nbook_saved_coupons', true);
        if (!in_array($code, $saved, true)) $saved[] = $code;
        update_user_meta(get_current_user_id(), '_nbook_saved_coupons', $saved);
        wp_send_json_success(['message' => 'Đã lưu voucher vào Voucher của tôi.']);
    }

    public function shortcode_available_vouchers() {
        if (!function_exists('wc_get_coupons')) return '';
        $coupons = get_posts(['post_type'=>'shop_coupon','post_status'=>'publish','posts_per_page'=>12,'orderby'=>'date','order'=>'DESC']);
        if (!$coupons) return '<p>Hiện chưa có voucher khả dụng.</p>';
        ob_start(); ?><div class="nb-available-vouchers">
        <?php foreach ($coupons as $post): $coupon = new WC_Coupon($post->ID); $type = get_post_meta($post->ID, '_nbook_coupon_type', true) ?: 'percent'; $flash = get_post_meta($post->ID, '_nbook_coupon_flash_end', true); if ($coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < current_time('timestamp', true)) continue; $saved = is_user_logged_in() && in_array(strtoupper($coupon->get_code()), array_map('strtoupper', (array) get_user_meta(get_current_user_id(), '_nbook_saved_coupons', true)), true); ?>
            <article class="nb-available-voucher" data-flash-end="<?php echo esc_attr($flash); ?>"><strong><?php echo esc_html(strtoupper($coupon->get_code())); ?></strong><span><?php echo esc_html($this->coupon_label($coupon, $type)); ?></span><?php if ($flash): ?><time class="nb-voucher-countdown"></time><?php endif; ?><button type="button" class="nb-save-voucher" data-code="<?php echo esc_attr($coupon->get_code()); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('nbook_coupon')); ?>" <?php disabled($saved); ?>><?php echo $saved ? 'Đã lưu' : 'Lưu'; ?></button><small>Điều kiện: <?php echo esc_html($coupon->get_minimum_amount() ? 'Đơn từ ' . wp_strip_all_tags(wc_price($coupon->get_minimum_amount())) : 'Không yêu cầu tối thiểu'); ?></small></article>
        <?php endforeach; ?></div><script>
        document.addEventListener('click',function(e){var b=e.target.closest('.nb-save-voucher');if(!b)return;fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=nbook_save_coupon&code='+encodeURIComponent(b.dataset.code)+'&nonce='+encodeURIComponent(b.dataset.nonce)}).then(function(r){return r.json();}).then(function(r){b.textContent=r.data&&r.data.message?'Đã lưu':'Lưu';});});
        document.querySelectorAll('.nb-available-voucher[data-flash-end]').forEach(function(card){var end=new Date(card.dataset.flashEnd.replace(' ','T')).getTime(), out=card.querySelector('.nb-voucher-countdown');var tick=function(){var left=Math.max(0,end-Date.now()),h=Math.floor(left/36e5),m=Math.floor(left%36e5/6e4),s=Math.floor(left%6e4/1e3);out.textContent=left?'Còn '+h+'h '+m+'p '+s+'s':'Đã hết hạn';};tick();setInterval(tick,1000);});
        </script><?php return ob_get_clean();
    }

    private function coupon_label($coupon, $type) {
        if ($type === 'shipping') return 'Miễn phí / giảm phí vận chuyển';
        if ($type === 'points') return 'Hoàn ' . get_post_meta($coupon->get_id(), '_nbook_coupon_points_rate', true) . ' Xu';
        if ($coupon->get_discount_type() === 'percent') return 'Giảm ' . $coupon->get_amount() . '%';
        return 'Giảm ' . wc_price($coupon->get_amount());
    }

    public function seed_sample_coupons() {
        if (!function_exists('WC') || get_option('nbook_sample_coupons_version') === '1') return;
        $samples = [
            ['BIBLIO10','percent',10,'percent'],['BIBLIO50K','fixed_cart',50000,'fixed'],['FREESHIP','fixed_cart',25000,'shipping'],
            ['HOANXU5','percent',5,'points'],['NEWUSER30','fixed_cart',30000,'new_user'],['SV2026','percent',15,'student'],
            ['VANHOC20','percent',20,'category'],['FLASH100K','fixed_cart',100000,'flash'],['SINHNHAT15','percent',15,'event'],
            ['TUSCHOOL25','percent',25,'event'],['TET2026','fixed_cart',80000,'event'],['BOOKWORM10','percent',10,'category'],
        ];
        foreach ($samples as $sample) {
            if (wc_get_coupon_id_by_code($sample[0])) continue;
            $id = wp_insert_post(['post_title'=>$sample[0],'post_status'=>'publish','post_type'=>'shop_coupon']);
            if (!$id || is_wp_error($id)) continue;
            update_post_meta($id, 'discount_type', $sample[1]); update_post_meta($id, 'coupon_amount', $sample[2]);
            update_post_meta($id, 'usage_limit', 1000); update_post_meta($id, 'usage_limit_per_user', 1);
            update_post_meta($id, 'date_expires', strtotime('+30 days')); update_post_meta($id, '_nbook_coupon_type', $sample[3]);
            update_post_meta($id, 'minimum_amount', 100000);
            if (in_array($sample[1], ['percent'], true)) update_post_meta($id, 'maximum_amount', 100000);
            if ($sample[3] === 'shipping') update_post_meta($id, 'free_shipping', 'yes');
            if ($sample[3] === 'student') update_post_meta($id, '_nbook_coupon_student_required', '1');
            if ($sample[3] === 'flash') update_post_meta($id, '_nbook_coupon_flash_end', gmdate('Y-m-d H:i:s', time() + 86400));
            if ($sample[3] === 'points') update_post_meta($id, '_nbook_coupon_points_rate', 5);
        }
        update_option('nbook_sample_coupons_version', '1');
    }

    public function ensure_notifications_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'nbook_notifications';
        if (get_option('nbook_notifications_db_version') === '1') {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            type varchar(40) NOT NULL DEFAULT 'general',
            title varchar(255) NOT NULL,
            message text NOT NULL,
            url text NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_read (user_id,is_read),
            KEY user_created (user_id,created_at)
        ) {$wpdb->get_charset_collate()};");
        update_option('nbook_notifications_db_version', '1');
    }

    public function ensure_points_tables() {
        global $wpdb;
        if (get_option('nbook_points_db_version') === '1') return;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}nbook_points_ledger (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT, user_id bigint(20) unsigned NOT NULL,
                amount bigint(20) NOT NULL, balance bigint(20) NOT NULL DEFAULT 0, type varchar(40) NOT NULL,
                reference varchar(100) NOT NULL DEFAULT '', description varchar(255) NOT NULL, expires_at datetime NULL,
                created_at datetime NOT NULL, PRIMARY KEY(id), UNIQUE KEY ref_unique(user_id,reference),
                KEY user_created(user_id,created_at)
        ) {$charset};");
        dbDelta("CREATE TABLE {$wpdb->prefix}nbook_rewards (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL,
                description text NOT NULL, type varchar(30) NOT NULL DEFAULT 'physical', points bigint(20) unsigned NOT NULL DEFAULT 0,
                stock bigint(20) NOT NULL DEFAULT 0, coupon_code varchar(100) NOT NULL DEFAULT '', active tinyint(1) NOT NULL DEFAULT 1,
                PRIMARY KEY(id)
        ) {$charset};");
        update_option('nbook_points_db_version', '1');
    }

    public function ensure_chatbot_tables() {
        Biblio_Chatbot::instance()->ensure_tables();
    }

    public function register_chatbot_admin_pages() {
        Biblio_Chatbot::instance()->register_admin_pages();
    }

    public function admin_chatbot_save_faq() {
        Biblio_Chatbot::instance()->admin_save_faq();
    }

    public function admin_chatbot_delete_faq() {
        Biblio_Chatbot::instance()->admin_delete_faq();
    }

    public function ajax_chatbot_message() {
        Biblio_Chatbot::instance()->ajax_message();
    }

    public function ajax_chatbot_transfer() {
        Biblio_Chatbot::instance()->ajax_transfer();
    }

    public function enqueue_chatbot_assets() {
        Biblio_Chatbot::instance()->enqueue_assets();
    }

    public function render_chatbot_widget() {
        Biblio_Chatbot::instance()->render_widget();
    }

    public function seed_chatbot_faq() {
        Biblio_Chatbot::instance()->seed_default_faq();
    }

        private function points_settings() {
            return [
                'per_vnd' => max(1, absint(get_option('nbook_points_per_vnd', 1000))),
                'signup' => max(0, absint(get_option('nbook_points_signup', 100))),
                'review' => max(0, absint(get_option('nbook_points_review', 50))),
                'checkin' => max(0, absint(get_option('nbook_points_checkin', 100))),
                'expiry_months' => max(1, absint(get_option('nbook_points_expiry_months', 12))),
            ];
        }

        public function points_balance($user_id) {
            global $wpdb;
            $this->expire_points($user_id);
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id=%d", $user_id));
        }

        private function expire_points($user_id) {
            global $wpdb;
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id, amount FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id=%d AND amount>0 AND expires_at IS NOT NULL AND expires_at<%s AND type NOT IN ('expired')", $user_id, current_time('mysql')));
            foreach ($rows as $row) {
                $this->change_points($user_id, -absint($row->amount), 'expired', 'expiry-' . $row->id, 'Điểm hết hạn sau 12 tháng', false);
                $wpdb->update($wpdb->prefix . 'nbook_points_ledger', ['type' => 'expired_source'], ['id' => $row->id], ['%s'], ['%d']);
            }
        }

        public function change_points($user_id, $amount, $type, $reference, $description, $expiring = true) {
            global $wpdb;
            $user_id = absint($user_id); $amount = (int) $amount;
            if (!$user_id || !$amount || !$reference) return false;
            $table = $wpdb->prefix . 'nbook_points_ledger';
            if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d AND reference=%s", $user_id, $reference))) return false;
            $balance = $this->points_balance_raw($user_id) + $amount;
            if ($balance < 0) return false;
            $expiry = ($amount > 0 && $expiring) ? date('Y-m-d H:i:s', strtotime('+' . $this->points_settings()['expiry_months'] . ' months')) : null;
            $ok = $wpdb->insert($table, ['user_id'=>$user_id,'amount'=>$amount,'balance'=>$balance,'type'=>sanitize_key($type),'reference'=>sanitize_text_field($reference),'description'=>sanitize_text_field($description),'expires_at'=>$expiry,'created_at'=>current_time('mysql')], ['%d','%d','%d','%s','%s','%s','%s','%s']);
            if ($ok) {
                $this->add_notification($user_id, $amount > 0 ? 'Bạn vừa nhận điểm thưởng' : 'Điểm Biblio Xu đã thay đổi', sprintf('%s (%s điểm).', $description, number_format_i18n(abs($amount))), home_url('/diem-thuong/'), 'reward');
            }
            return (bool) $ok;
        }

        private function points_balance_raw($user_id) {
            global $wpdb;
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id=%d", $user_id));
        }

        public function award_order_points($order_id) {
            $order = wc_get_order($order_id);
            if (!$order || !$order->get_user_id()) return;
            $settings = $this->points_settings();
            $points = (int) floor((float) $order->get_subtotal() / $settings['per_vnd']);
            if ($points > 0) $this->change_points($order->get_user_id(), $points, 'order', 'order-earned-' . $order_id, 'Thưởng đơn hàng #' . $order->get_order_number());
            foreach ($order->get_coupon_codes() as $code) {
                $coupon_id = wc_get_coupon_id_by_code($code);
                if (get_post_meta($coupon_id, '_nbook_coupon_type', true) === 'points') {
                    $rate = max(0, (float) get_post_meta($coupon_id, '_nbook_coupon_points_rate', true));
                    $bonus = (int) floor((float) $order->get_subtotal() * $rate / 100);
                    if ($bonus > 0) $this->change_points($order->get_user_id(), $bonus, 'coupon-points', 'coupon-points-' . $order_id . '-' . $coupon_id, 'Hoàn Xu từ voucher ' . strtoupper($code));
                }
            }
            $total_spent = (float) get_user_meta($order->get_user_id(), '_nbook_total_spent', true);
            update_user_meta($order->get_user_id(), '_nbook_total_spent', $total_spent + (float) $order->get_total());
            $this->apply_order_points_discount($order);
        }

        public function save_checkout_points($order, $data) {
            if (!$order->get_user_id() || !function_exists('WC') || !WC()->session || !WC()->session->get('nbook_use_reward_points')) return;
            $points = absint(WC()->session->get('nbook_points_to_use', 0));
            if ($points > 0) $order->update_meta_data('_nbook_points_used', $points);
        }

        public function apply_order_points_discount($order) {
            $used = absint($order->get_meta('_nbook_points_used'));
            if ($used && !$order->get_meta('_nbook_points_deducted')) {
                if ($this->change_points($order->get_user_id(), -$used, 'redeem-order', 'order-spent-' . $order->get_id(), 'Dùng điểm cho đơn hàng #' . $order->get_order_number(), false)) $order->update_meta_data('_nbook_points_deducted', 1);
                $order->save();
            }
        }

        public function refund_order_points($order_id) {
            $order = wc_get_order($order_id);
            if (!$order || !$order->get_user_id() || !$order->get_meta('_nbook_points_deducted') || $order->get_meta('_nbook_points_refunded')) return;
            $used = absint($order->get_meta('_nbook_points_used'));
            if ($used && $this->change_points($order->get_user_id(), $used, 'refund', 'order-refund-' . $order_id, 'Hoàn điểm đơn hàng #' . $order->get_order_number(), false)) {
                $order->update_meta_data('_nbook_points_refunded', 1); $order->save();
            }
        }

        public function ajax_daily_checkin() {
            check_ajax_referer('nbook_points', 'nonce');
            if (!is_user_logged_in()) wp_send_json_error(['message'=>'Vui lòng đăng nhập.'], 401);
            $ref = 'checkin-' . get_current_user_id() . '-' . current_time('Y-m-d');
            $ok = $this->change_points(get_current_user_id(), $this->points_settings()['checkin'], 'checkin', $ref, 'Thưởng điểm danh hằng ngày');
            wp_send_json_success(['success' => $ok, 'balance' => $this->points_balance(get_current_user_id()), 'message' => $ok ? 'Điểm danh thành công.' : 'Bạn đã điểm danh hôm nay.']);
        }

        public function award_signup_points($user_id) {
            $this->change_points($user_id, $this->points_settings()['signup'], 'signup', 'signup-' . absint($user_id), 'Thưởng đăng ký tài khoản');
        }

        public function award_review_points($new_status, $old_status, $comment) {
            if ($new_status !== 'approved' || $old_status === 'approved' || !$comment || !$comment->user_id || get_post_type($comment->comment_post_ID) !== 'product') return;
            $this->change_points($comment->user_id, $this->points_settings()['review'], 'review', 'review-' . $comment->comment_ID, 'Thưởng đánh giá sản phẩm');
        }

        public function maybe_create_points_page() {
            if (get_option('nbook_points_page_id')) return;
            $page = get_page_by_path('diem-thuong');
            $id = $page ? $page->ID : wp_insert_post(['post_title'=>'Điểm thưởng','post_name'=>'diem-thuong','post_content'=>'[nbook_points]','post_status'=>'publish','post_type'=>'page']);
            if ($id && !is_wp_error($id)) update_option('nbook_points_page_id', $id);
        }

        public function register_points_admin() {
            add_submenu_page('nbook-notifications', 'Biblio Xu', 'Biblio Xu', 'manage_options', 'nbook-points', [$this, 'render_points_admin']);
        }

        public function render_points_admin() {
            global $wpdb;
            $rewards = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nbook_rewards ORDER BY id DESC");
            ?>
            <div class="wrap"><h1>Quản lý Biblio Xu</h1>
            <h2>Điều chỉnh điểm khách hàng</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="nbook_adjust_points"><?php wp_nonce_field('nbook_adjust_points'); ?>
                <input name="user_id" type="number" min="1" placeholder="User ID" required><input name="amount" type="number" placeholder="Số điểm (+/-)" required><input name="description" placeholder="Lý do" required><button class="button">Lưu điều chỉnh</button>
            </form>
            <h2>Thêm quà đổi điểm</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="nbook_save_reward"><?php wp_nonce_field('nbook_save_reward'); ?>
                <input name="name" placeholder="Tên quà" required><input name="points" type="number" min="1" placeholder="Điểm" required><input name="stock" type="number" min="0" placeholder="Tồn kho" required>
                <select name="type"><option value="voucher">Voucher</option><option value="physical">Bookmark/Sổ tay</option><option value="shipping">Miễn phí vận chuyển</option></select>
                <input name="coupon_code" placeholder="Mã voucher (nếu có)"><button class="button button-primary">Thêm quà</button>
            </form><table class="widefat striped" style="margin-top:20px"><thead><tr><th>Quà</th><th>Loại</th><th>Điểm</th><th>Tồn kho</th><th>Mã voucher</th></tr></thead><tbody>
            <?php foreach ($rewards as $reward): ?><tr><td><?php echo esc_html($reward->name); ?></td><td><?php echo esc_html($reward->type); ?></td><td><?php echo esc_html($reward->points); ?></td><td><?php echo esc_html($reward->stock); ?></td><td><?php echo esc_html($reward->coupon_code); ?></td></tr><?php endforeach; ?>
            </tbody></table></div><?php
        }

        public function admin_save_reward() {
            if (!current_user_can('manage_options') || !check_admin_referer('nbook_save_reward')) wp_die('Không có quyền.');
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'nbook_rewards', ['name'=>sanitize_text_field($_POST['name'] ?? ''),'points'=>absint($_POST['points'] ?? 0),'stock'=>absint($_POST['stock'] ?? 0),'type'=>sanitize_key($_POST['type'] ?? 'physical'),'coupon_code'=>sanitize_text_field($_POST['coupon_code'] ?? ''),'active'=>1,'description'=>''], ['%s','%d','%d','%s','%s','%d','%s']);
            wp_safe_redirect(admin_url('admin.php?page=nbook-points')); exit;
        }

        public function admin_adjust_points() {
            if (!current_user_can('manage_options') || !check_admin_referer('nbook_adjust_points')) wp_die('Không có quyền.');
            $user_id = absint($_POST['user_id'] ?? 0); $amount = (int) ($_POST['amount'] ?? 0);
            if ($user_id && $amount) $this->change_points($user_id, $amount, 'admin-adjustment', 'admin-' . get_current_user_id() . '-' . wp_generate_uuid4(), sanitize_text_field($_POST['description'] ?? 'Điều chỉnh bởi quản trị viên'), false);
            wp_safe_redirect(admin_url('admin.php?page=nbook-points')); exit;
        }

        public function ajax_redeem_reward() {
            check_ajax_referer('nbook_points', 'nonce');
            if (!is_user_logged_in()) wp_send_json_error(['message'=>'Vui lòng đăng nhập.'], 401);
            global $wpdb; $id = absint($_POST['reward_id'] ?? 0); $table = $wpdb->prefix . 'nbook_rewards';
            $reward = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND active=1", $id));
            if (!$reward || $reward->stock < 1 || $this->points_balance(get_current_user_id()) < $reward->points) wp_send_json_error(['message'=>'Không đủ điểm hoặc quà đã hết.']);
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET stock=stock-1 WHERE id=%d AND stock>0", $id));
            if (!$this->change_points(get_current_user_id(), -$reward->points, 'redeem-reward', 'reward-' . wp_generate_uuid4(), 'Đổi quà: ' . $reward->name, false)) wp_send_json_error(['message'=>'Không thể trừ điểm.']);
            if ($reward->type === 'voucher' && $reward->coupon_code) {
                update_user_meta(get_current_user_id(), '_nbook_vouchers', array_merge((array) get_user_meta(get_current_user_id(), '_nbook_vouchers', true), [$reward->coupon_code]));
            }
            wp_send_json_success(['message'=>'Đổi quà thành công.','balance'=>$this->points_balance(get_current_user_id())]);
        }

        public function shortcode_points() {
            return Biblio_Points_Voucher::instance()->render_points_page();
        }

        public function shortcode_my_vouchers() {
            return Biblio_Points_Voucher::instance()->render_my_vouchers();
        }

        private function member_tier($user_id) {
            $spent = (float) get_user_meta($user_id, '_nbook_total_spent', true);
            return $spent >= 10000000 ? 'Vàng' : ($spent >= 3000000 ? 'Bạc' : 'Đồng');
        }

    public function maybe_create_notifications_page() {
        return;
    }

    public function add_notification($user_id, $title, $message, $url = '', $type = 'general') {
        global $wpdb;
        $user_id = absint($user_id);
        if (!$user_id || !$title) {
            return false;
        }
        return (bool) $wpdb->insert($wpdb->prefix . 'nbook_notifications', [
            'user_id' => $user_id,
            'type' => sanitize_key($type),
            'title' => sanitize_text_field($title),
            'message' => sanitize_textarea_field($message),
            'url' => esc_url_raw($url ?: home_url('/tai-khoan/')),
            'created_at' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s', '%s', '%s']);
    }

    public function add_notification_to_all($title, $message, $url = '', $type = 'general') {
        $user_ids = get_users(['fields' => 'ID']);
        foreach ($user_ids as $user_id) {
            $this->add_notification($user_id, $title, $message, $url, $type);
        }
    }

    private function notification_rows($user_id, $page = 1, $per_page = 8) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbook_notifications';
        $offset = max(0, (absint($page) - 1) * $per_page);
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, type, title, message, url, is_read, created_at FROM {$table} WHERE user_id = %d ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));
    }

    public function ajax_notifications() {
        check_ajax_referer('nbook_notifications', 'nonce', false);
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vui lòng đăng nhập.'], 401);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'nbook_notifications';
        $user_id = get_current_user_id();
        $rows = $this->notification_rows($user_id, 1, 100);
        $unread = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0", $user_id));
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row->id, 'title' => $row->title, 'message' => $row->message,
                'url' => esc_url($row->url), 'type' => $row->type, 'is_read' => (bool) $row->is_read,
                'date' => mysql2date('d/m/Y H:i', $row->created_at),
            ];
        }
        wp_send_json_success(['unread' => $unread, 'items' => $items, 'all_url' => home_url('/tai-khoan/')]);
    }

    public function ajax_mark_notifications_read() {
        check_ajax_referer('nbook_notifications', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vui lòng đăng nhập.'], 401);
        }
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'nbook_notifications', ['is_read' => 1], ['user_id' => get_current_user_id(), 'is_read' => 0], ['%d'], ['%d', '%d']);
        wp_send_json_success(['unread' => 0]);
    }

    public function ajax_mark_notification_read() {
        check_ajax_referer('nbook_notifications', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vui lòng đăng nhập.'], 401);
        }
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'nbook_notifications', ['is_read' => 1], ['id' => absint($_POST['id'] ?? 0), 'user_id' => get_current_user_id()], ['%d'], ['%d', '%d']);
        wp_send_json_success();
    }

    public function register_notifications_admin() {
        add_menu_page('Thông báo', 'Thông báo', 'manage_options', 'nbook-notifications', [$this, 'render_notifications_admin'], 'dashicons-bell', 26);
    }

    public function render_notifications_admin() {
        ?>
        <div class="wrap"><h1>Gửi thông báo chung</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="nbook_send_notification">
                <?php wp_nonce_field('nbook_send_notification'); ?>
                <table class="form-table">
                    <tr><th><label for="nbook-notification-title">Tiêu đề</label></th><td><input id="nbook-notification-title" name="title" class="regular-text" required></td></tr>
                    <tr><th><label for="nbook-notification-message">Nội dung</label></th><td><textarea id="nbook-notification-message" name="message" class="large-text" rows="5" required></textarea></td></tr>
                    <tr><th><label for="nbook-notification-url">Liên kết</label></th><td><input id="nbook-notification-url" name="url" type="url" class="regular-text" placeholder="<?php echo esc_attr(home_url('/khuyen-mai/')); ?>"></td></tr>
                </table>
                <?php submit_button('Gửi tới tất cả người dùng'); ?>
            </form>
        </div>
        <?php
    }

    public function admin_send_notification() {
        if (!current_user_can('manage_options') || !check_admin_referer('nbook_send_notification')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }
        $this->add_notification_to_all(
            sanitize_text_field($_POST['title'] ?? ''),
            sanitize_textarea_field($_POST['message'] ?? ''),
            esc_url_raw($_POST['url'] ?? ''),
            'general'
        );
        wp_safe_redirect(add_query_arg(['page' => 'nbook-notifications', 'sent' => 1], admin_url('admin.php')));
        exit;
    }

    public function notify_order_created($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_user_id()) {
            $this->add_notification($order->get_user_id(), 'Đặt hàng thành công', sprintf('Đơn hàng #%s đã được tiếp nhận.', $order->get_order_number()), $order->get_view_order_url(), 'order');
        }
    }

    public function notify_order_status_changed($order_id, $from, $to, $order) {
        if (!$order || !$order->get_user_id() || $from === $to) {
            return;
        }
        $labels = ['pending' => 'Chờ xử lý', 'processing' => 'Đã xác nhận', 'on-hold' => 'Đang xử lý', 'shipping' => 'Đang giao', 'completed' => 'Đã giao', 'cancelled' => 'Đã hủy', 'refunded' => 'Đã hoàn tiền'];
        $status = $labels[$to] ?? wc_get_order_status_name($to);
        $this->add_notification($order->get_user_id(), 'Đơn hàng cập nhật', sprintf('Đơn hàng #%s hiện ở trạng thái: %s.', $order->get_order_number(), $status), $order->get_view_order_url(), 'order');
    }

    public function notify_new_promotion($post_id, $post) {
        if (wp_is_post_revision($post_id) || !$post || $post->post_type !== 'post') {
            return;
        }
        $this->add_notification_to_all('Khuyến mãi mới', get_the_title($post_id), get_permalink($post_id), 'promotion');
    }

    public function notify_reward_points($user_id, $points) {
        $this->add_notification($user_id, 'Bạn vừa nhận điểm thưởng', sprintf('Tài khoản của bạn được cộng %s điểm thưởng.', absint($points)), home_url('/tai-khoan/#diem-thuong'), 'reward');
    }

    public function notify_voucher($user_id, $voucher) {
        $code = is_object($voucher) && method_exists($voucher, 'get_code') ? $voucher->get_code() : sanitize_text_field((string) $voucher);
        $this->add_notification($user_id, 'Bạn được tặng voucher', $code ? sprintf('Voucher %s đã được thêm vào tài khoản của bạn.', $code) : 'Một voucher mới đã được thêm vào tài khoản của bạn.', home_url('/tai-khoan/#voucher'), 'voucher');
    }

    public function shortcode_notifications() {
        if (!is_user_logged_in()) {
            return '<div class="nb-notifications-login">Vui lòng <a href="' . esc_url(wp_login_url(get_permalink())) . '">đăng nhập</a> để xem thông báo.</div>';
        }
        global $wpdb;
        $table = $wpdb->prefix . 'nbook_notifications';
        $user_id = get_current_user_id();
        $page = max(1, absint($_GET['notification_page'] ?? 1));
        $per_page = 12;
        $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $user_id));
        $rows = $this->notification_rows($user_id, $page, $per_page);
        ob_start();
        ?>
        <main class="nb-all-notifications"><div class="nb-container">
            <h1>Tất cả thông báo</h1>
            <div class="nb-notification-list">
            <?php if (!$rows): ?><p>Chưa có thông báo nào.</p><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <a class="nb-notification-page-item <?php echo $row->is_read ? '' : 'is-unread'; ?>" href="<?php echo esc_url($row->url); ?>" data-notification-id="<?php echo esc_attr($row->id); ?>">
                    <strong><?php echo esc_html($row->title); ?></strong><span><?php echo esc_html($row->message); ?></span><time><?php echo esc_html(mysql2date('d/m/Y H:i', $row->created_at)); ?></time>
                </a>
            <?php endforeach; ?>
            </div>
            <?php echo wp_kses_post(paginate_links(['base' => add_query_arg('notification_page', '%#%'), 'format' => '', 'current' => $page, 'total' => max(1, ceil($total / $per_page)), 'type' => 'list'])); ?>
        </div></main>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // 1. CPT BANNER
    // =========================================================================
    public function register_banner_cpt() {
        $labels = [
            'name'               => 'Banners Khuyến Mãi',
            'singular_name'      => 'Banner',
            'menu_name'          => 'Banners Biblio',
            'add_new'            => 'Thêm Banner Mới',
            'add_new_item'       => 'Thêm Banner Khuyến Mãi Mới',
            'edit_item'          => 'Chỉnh sửa Banner',
            'new_item'           => 'Banner Mới',
            'view_item'          => 'Xem Banner',
            'search_items'       => 'Tìm kiếm Banner',
            'not_found'          => 'Chưa có Banner nào',
            'not_found_in_trash' => 'Không có Banner trong thùng rác',
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-images-alt2',
            'supports'           => ['title', 'thumbnail', 'page-attributes'],
        ];

        register_post_type('banner', $args);
    }

    public function add_banner_meta_boxes() {
        add_meta_box(
            'nbook_banner_details',
            'Thông Tin Banner Khuyến Mãi',
            [$this, 'render_banner_meta_box'],
            'banner',
            'normal',
            'high'
        );
    }

    public function render_banner_meta_box($post) {
        wp_nonce_field('nbook_save_banner', 'nbook_banner_nonce');
        $desc = get_post_meta($post->ID, '_nbook_banner_desc', true);
        $btn_text = get_post_meta($post->ID, '_nbook_banner_btn_text', true) ?: 'Khám Phá Ngay';
        $btn_url = get_post_meta($post->ID, '_nbook_banner_btn_url', true) ?: '/san-pham/';
        $badge = get_post_meta($post->ID, '_nbook_banner_badge', true) ?: 'HOT DEAL';
        $gradient = get_post_meta($post->ID, '_nbook_banner_bg_gradient', true) ?: 'linear-gradient(135deg, #ee4d2d 0%, #ff7337 100%)';
        $position = get_post_meta($post->ID, '_nbook_banner_position', true) ?: 'main';
        $start_at = get_post_meta($post->ID, '_nbook_banner_start_at', true);
        $end_at = get_post_meta($post->ID, '_nbook_banner_end_at', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="nbook_banner_badge">Huy hiệu (Tag):</label></th>
                <td>
                    <input type="text" id="nbook_banner_badge" name="nbook_banner_badge" value="<?php echo esc_attr($badge); ?>" class="regular-text" placeholder="Ví dụ: HOT DEAL, SIÊU SALE, FREESHIP" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nbook_banner_desc">Mô tả ngắn banner:</label></th>
                <td>
                    <textarea id="nbook_banner_desc" name="nbook_banner_desc" rows="3" class="large-text" placeholder="Mô tả ngắn 1-2 câu về chương trình khuyến mãi..."><?php echo esc_textarea($desc); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nbook_banner_btn_text">Chữ trên nút (CTA):</label></th>
                <td>
                    <input type="text" id="nbook_banner_btn_text" name="nbook_banner_btn_text" value="<?php echo esc_attr($btn_text); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nbook_banner_btn_url">Đường dẫn nút (URL):</label></th>
                <td>
                    <input type="text" id="nbook_banner_btn_url" name="nbook_banner_btn_url" value="<?php echo esc_attr($btn_url); ?>" class="large-text" placeholder="/san-pham/ hoặc https://..." />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nbook_banner_bg_gradient">Dải màu nền (Gradient):</label></th>
                <td>
                    <input type="text" id="nbook_banner_bg_gradient" name="nbook_banner_bg_gradient" value="<?php echo esc_attr($gradient); ?>" class="large-text" />
                    <p class="description">Dùng khi không cài ảnh bìa hoặc tạo hiệu ứng nền cho banner.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nbook_banner_position">Vị trí hiển thị:</label></th>
                <td>
                    <select id="nbook_banner_position" name="nbook_banner_position">
                        <option value="main" <?php selected($position, 'main'); ?>>Banner chính (slide lớn)</option>
                        <option value="side" <?php selected($position, 'side'); ?>>Banner phụ (cột bên phải)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nbook_banner_start_at">Bắt đầu hiển thị:</label></th>
                <td><input type="datetime-local" id="nbook_banner_start_at" name="nbook_banner_start_at" value="<?php echo esc_attr($start_at ? date('Y-m-d\TH:i', strtotime($start_at)) : ''); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="nbook_banner_end_at">Kết thúc hiển thị:</label></th>
                <td><input type="datetime-local" id="nbook_banner_end_at" name="nbook_banner_end_at" value="<?php echo esc_attr($end_at ? date('Y-m-d\TH:i', strtotime($end_at)) : ''); ?>"></td>
            </tr>
        </table>
        <?php
    }

    public function save_banner_meta($post_id) {
        if (!isset($_POST['nbook_banner_nonce']) || !wp_verify_nonce($_POST['nbook_banner_nonce'], 'nbook_save_banner')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'nbook_banner_desc'        => '_nbook_banner_desc',
            'nbook_banner_btn_text'    => '_nbook_banner_btn_text',
            'nbook_banner_btn_url'     => '_nbook_banner_btn_url',
            'nbook_banner_badge'       => '_nbook_banner_badge',
            'nbook_banner_bg_gradient' => '_nbook_banner_bg_gradient',
            'nbook_banner_position'     => '_nbook_banner_position',
            'nbook_banner_start_at'     => '_nbook_banner_start_at',
            'nbook_banner_end_at'       => '_nbook_banner_end_at',
        ];

        foreach ($fields as $post_key => $meta_key) {
            if (isset($_POST[$post_key])) {
                $value = sanitize_text_field($_POST[$post_key]);
                if (in_array($post_key, ['nbook_banner_start_at', 'nbook_banner_end_at'], true)) {
                    $value = $value ? get_gmt_from_date(str_replace('T', ' ', $value), 'Y-m-d H:i:s') : '';
                }
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }

    // =========================================================================
    // 1.2. CPT MỤC GIỚI THIỆU (nbook_about)
    // =========================================================================
    public function register_about_cpt() {
        $labels = [
            'name'               => 'Mục Giới Thiệu',
            'singular_name'      => 'Mục Giới Thiệu',
            'menu_name'          => 'Mục Giới Thiệu',
            'name_admin_bar'     => 'Mục Giới Thiệu',
            'add_new'            => 'Thêm Mục Mới',
            'add_new_item'       => 'Thêm Mục Giới Thiệu Mới',
            'edit_item'          => 'Chỉnh Sửa Mục Giới Thiệu',
            'new_item'           => 'Mục Giới Thiệu Mới',
            'view_item'          => 'Xem Mục Giới Thiệu',
            'all_items'          => 'Tất Cả Mục Giới Thiệu',
            'search_items'       => 'Tìm kiếm Mục Giới Thiệu',
            'not_found'          => 'Chưa có mục giới thiệu nào.',
            'not_found_in_trash' => 'Không có mục giới thiệu nào trong thùng rác.',
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 21,
            'menu_icon'          => 'dashicons-info-outline',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes'],
        ];

        register_post_type('nbook_about', $args);
    }

    public function seed_default_about_items() {
        $existing = get_posts([
            'post_type'      => 'nbook_about',
            'post_status'    => 'any',
            'posts_per_page' => 1,
        ]);

        if (!empty($existing)) {
            return;
        }

        $default_items = [
            [
                'title'      => 'Giới thiệu tổng quan về Biblio',
                'menu_order' => 1,
                'content'    => '<p><strong>Biblio</strong> ra đời từ niềm đam mê tri thức và khát khao xây dựng một không gian mua sắm sách trực tuyến hiện đại, minh bạch và tối ưu nhất dành cho thế hệ trẻ. Khởi nguồn từ dự án thực hành môn <em>Hệ thống thương mại điện tử</em> do <strong>Nhóm 3 – Khoa Công nghệ thông tin, Đại học Kinh tế Quốc dân (NEU)</strong> nghiên cứu và phát triển, Biblio thấu hiểu sâu sắc những khó khăn mà sinh viên thường gặp phải: sách lậu kém chất lượng trôi nổi, giá thành cao và thời gian chờ giao hàng kéo dài.</p><p>Với sứ mệnh là người bạn đồng hành tin cậy, đối tượng phục vụ trọng tâm của Biblio là <strong>hơn 30.000 sinh viên Đại học Kinh tế Quốc dân (NEU)</strong>, học sinh – sinh viên các trường đại học, cao đẳng cùng toàn thể độc giả yêu tri thức trên toàn quốc. Biblio tự hào mang đến trải nghiệm mua sắm tiện nghi, nhanh gọn và tràn đầy cảm hứng học tập.</p>',
                'image'      => 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=1000&auto=format&fit=crop&q=80',
            ],
            [
                'title'      => 'Giới thiệu về sản phẩm & dịch vụ',
                'menu_order' => 2,
                'content'    => '<p>Hệ sinh thái sản phẩm tại Biblio được tuyển chọn khắt khe và cập nhật liên tục từ những nhà xuất bản uy tín hàng đầu Việt Nam như <em>NXB Trẻ, NXB Kim Đồng, Nhã Nam, Alpha Books, First News, NXB Tổng Hợp</em>. Danh mục sản phẩm phong phú bao gồm:</p><ul><li><strong>Giáo trình & Tài liệu học phần:</strong> Toàn bộ giáo trình chuẩn Đại học Kinh tế Quốc dân NEU, tài liệu tham khảo các học phần kinh tế, tài chính, quản trị kinh doanh, CNTT và luật.</li><li><strong>Sách Kinh tế & Khởi nghiệp:</strong> Cập nhật những tư duy quản trị đột phá, kinh tế vĩ mô, đầu tư chứng khoán và cẩm nang khởi nghiệp thực chiến.</li><li><strong>Sách Ngoại ngữ & Luyện thi:</strong> Bộ tài liệu IELTS, TOEIC, HSK, JLPT mới nhất năm 2026 kèm đáp án và giải thích chi tiết.</li><li><strong>Sách Kỹ năng sống & Văn học:</strong> Phát triển tư duy phản biện, kỹ năng giao tiếp, làm việc nhóm và các tác phẩm văn học kinh điển thế giới.</li></ul><p>Bên cạnh sản phẩm vượt trội, Biblio cung cấp các <strong>dịch vụ độc quyền</strong>: dịch vụ bọc sách miễn phí bằng màng bọc chống trầy cao cấp, tặng kèm bookmark nghệ thuật cho 100% đơn hàng, hỗ trợ tư vấn chọn sách cá nhân hóa theo chuyên ngành học và <strong>chính sách đổi trả miễn phí trong vòng 7 ngày</strong>.</p>',
                'image'      => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=1000&auto=format&fit=crop&q=80',
            ],
            [
                'title'      => 'Sứ mệnh và tầm nhìn',
                'menu_order' => 3,
                'content'    => '<p><strong>Sứ mệnh của chúng tôi:</strong> Kết nối sinh viên và độc giả với kho tàng tri thức nhân loại một cách dễ dàng, nhanh chóng và tiết kiệm nhất. Biblio không đơn thuần là nơi mua sắm, mà là bệ phóng nuôi dưỡng văn hóa đọc văn minh, góp phần nâng cao năng lực học tập và nghiên cứu khoa học cho giới trẻ Việt Nam.</p><p><strong>Tầm nhìn đến năm 2030:</strong> Xây dựng Biblio trở thành sàn thương mại điện tử chuyên biệt ngành sách số 1 dành cho cộng đồng sinh viên cả nước; tiên phong ứng dụng công nghệ số, trí tuệ nhân tạo và hệ thống logistics thông minh để tối ưu hóa hành trình bạn đọc từ tìm kiếm đến nhận sách trên tay.</p>',
                'image'      => 'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?w=1000&auto=format&fit=crop&q=80',
            ],
            [
                'title'      => 'Giá trị cốt lõi (Chính thống – Tận tâm – Đồng hành)',
                'menu_order' => 4,
                'content'    => '<p>Mọi hoạt động và định hướng phát triển của Biblio đều xoay quanh 3 giá trị cốt lõi nền tảng:</p><ul><li><strong>CHÍNH THỐNG:</strong> Cam kết 100% sách thật, có bản quyền, nguồn gốc xuất xứ rõ ràng từ các nhà xuất bản chính quy. Biblio kiên quyết nói KHÔNG với sách in lậu, sách vi phạm bản quyền.</li><li><strong>TẬN TÂM:</strong> Đặt sự hài lòng của bạn đọc lên hàng đầu. Mọi cuốn sách đều được kiểm tra kỹ lưỡng, đóng gói 3 lớp chống sốc chắc chắn và vận chuyển hỏa tốc trong 2 giờ nội thành, 1-3 ngày toàn quốc. Đội ngũ chăm sóc khách hàng luôn túc trực hỗ trợ 24/7.</li><li><strong>ĐỒNG HÀNH:</strong> Đồng hành cùng sinh viên trong suốt những năm tháng giảng đường thông qua chính sách trợ giá học tập, kho mã giảm giá học phần, chương trình đổi sách cũ lấy sách mới và các học bổng khuyến học thường niên.</li></ul>',
                'image'      => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=1000&auto=format&fit=crop&q=80',
            ],
            [
                'title'      => 'Nhóm phát triển dự án (Nhóm 3 – Hệ thống Thương mại Điện tử)',
                'menu_order' => 5,
                'content'    => '<p>Dự án sàn thương mại điện tử <strong>Biblio</strong> được nghiên cứu, thiết kế và phát triển toàn diện bởi <strong>Nhóm 3 – Lớp Học phần Hệ thống Thương mại Điện tử</strong>, trực thuộc <strong>Khoa Công nghệ thông tin – Đại học Kinh tế Quốc dân (NEU)</strong>.</p><p>Với mục tiêu ứng dụng kiến thức chuyên sâu về kiến trúc sàn TMĐT, chuẩn hóa trải nghiệm người dùng hiện đại tương tự Shopee và tối ưu hóa tỷ lệ chuyển đổi, Nhóm 3 đã hiện thực hóa mô hình sàn sách trực tuyến Biblio với 4 phân ban chuyên trách:</p><ul><li><strong>Ban Quản trị & Nghiệp vụ TMĐT:</strong> Lập kế hoạch dự án, phân tích hành trình khách hàng và xây dựng quy chuẩn vận hành minh bạch.</li><li><strong>Ban Thiết kế UI/UX & Frontend:</strong> Định hình phong cách thiết kế Shopee cam (#ee4d2d), bố cục chuẩn tỉ lệ vàng, responsive 100% trên thiết bị di động và tương tác mượt mà bằng Pure Vanilla JS.</li><li><strong>Ban Lập trình Hệ thống & CSDL:</strong> Kiến trúc plugin lõi nbook-core, tùy biến WooCommerce, tối ưu hóa cơ sở dữ liệu và bảo mật đa tầng.</li><li><strong>Ban Dữ liệu Sách & Trợ giá Sinh viên:</strong> Khảo sát nhu cầu giáo trình NEU, liên kết nguồn sách bản quyền chính ngạch và triển khai chính sách trợ giá cho học phần sinh viên.</li></ul><p>Dự án là tâm huyết của tập thể Nhóm 3 nhằm mang đến một giải pháp công nghệ thiết thực, đóng góp tích cực vào phong trào học tập, nghiên cứu và phát triển văn hóa đọc cho sinh viên NEU nói riêng và bạn đọc trẻ cả nước nói chung.</p>',
                'image'      => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1000&auto=format&fit=crop&q=80',
            ],
        ];

        foreach ($default_items as $item) {
            $pid = wp_insert_post([
                'post_title'   => $item['title'],
                'post_content' => $item['content'],
                'post_type'    => 'nbook_about',
                'post_status'  => 'publish',
                'menu_order'   => $item['menu_order'],
            ]);
            if ($pid && !is_wp_error($pid)) {
                update_post_meta($pid, '_nbook_about_image_url', $item['image']);
            }
        }
    }

    // =========================================================================
    // 2. SETTINGS -> BIBLIO
    // =========================================================================
    public function register_settings_page() {
        add_options_page(
            'Cài Đặt Biblio',
            'Biblio',
            'manage_options',
            'nbook-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('nbook_options_group', 'nbook_top_announcement');
        register_setting('nbook_options_group', 'nbook_hotline');
        register_setting('nbook_options_group', 'nbook_zalo');
        register_setting('nbook_options_group', 'nbook_email');
        register_setting('nbook_options_group', 'nbook_address');
        register_setting('nbook_options_group', 'nbook_about_intro');
        register_setting('nbook_options_group', 'nbook_points_per_vnd', ['sanitize_callback' => 'absint']);
        register_setting('nbook_options_group', 'nbook_points_signup', ['sanitize_callback' => 'absint']);
        register_setting('nbook_options_group', 'nbook_points_review', ['sanitize_callback' => 'absint']);
        register_setting('nbook_options_group', 'nbook_points_checkin', ['sanitize_callback' => 'absint']);
        register_setting('nbook_options_group', 'nbook_points_expiry_months', ['sanitize_callback' => 'absint']);
        // Chatbot AI Options
        register_setting('nbook_options_group', 'biblio_ai_enabled', ['sanitize_callback' => 'absint']);
        register_setting('nbook_options_group', 'biblio_ai_api_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('nbook_options_group', 'biblio_ai_model', ['sanitize_callback' => 'sanitize_text_field']);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>Cài Đặt Cấu Hình Biblio</h1>
            <p>Tùy chỉnh thanh thông báo trên cùng, thông tin liên hệ và kết nối mô hình trí tuệ nhân tạo (Chatbot AI).</p>
            <form method="post" action="options.php">
                <?php settings_fields('nbook_options_group'); ?>
                <?php do_settings_sections('nbook_options_group'); ?>
                <table class="form-table">
                    <tr><th colspan="2"><h2>Biblio Xu</h2></th></tr>
                    <tr><th>Quy đổi mua hàng</th><td><input type="number" min="1" name="nbook_points_per_vnd" value="<?php echo esc_attr(get_option('nbook_points_per_vnd', 1000)); ?>"> đồng = 1 điểm</td></tr>
                    <tr><th>Thưởng đăng ký</th><td><input type="number" min="0" name="nbook_points_signup" value="<?php echo esc_attr(get_option('nbook_points_signup', 100)); ?>"> điểm</td></tr>
                    <tr><th>Thưởng đánh giá</th><td><input type="number" min="0" name="nbook_points_review" value="<?php echo esc_attr(get_option('nbook_points_review', 50)); ?>"> điểm</td></tr>
                    <tr><th>Thưởng điểm danh</th><td><input type="number" min="0" name="nbook_points_checkin" value="<?php echo esc_attr(get_option('nbook_points_checkin', 100)); ?>"> điểm/ngày</td></tr>
                    <tr><th>Thời hạn điểm</th><td><input type="number" min="1" name="nbook_points_expiry_months" value="<?php echo esc_attr(get_option('nbook_points_expiry_months', 12)); ?>"> tháng</td></tr>
                    
                    <tr><th colspan="2"><h2>Thông Tin Liên Hệ & Header</h2></th></tr>
                    <tr valign="top">
                        <th scope="row"><strong>Thanh thông báo trên cùng:</strong></th>
                        <td>
                            <input type="text" name="nbook_top_announcement" value="<?php echo esc_attr(get_option('nbook_top_announcement', 'Miễn phí vận chuyển đơn từ 299.000đ')); ?>" class="large-text" />
                            <p class="description">Hiển thị ở dòng thông báo trên cùng của website (Header Top Announcement Bar).</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Số Hotline CSKH:</th>
                        <td>
                            <input type="text" name="nbook_hotline" value="<?php echo esc_attr(get_option('nbook_hotline', '0933 109 009')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Zalo Hỗ Trợ:</th>
                        <td>
                            <input type="text" name="nbook_zalo" value="<?php echo esc_attr(get_option('nbook_zalo', '0933 109 009')); ?>" class="regular-text" />
                            <p class="description">Số điện thoại hoặc link Zalo Official Account (ví dụ: 0933 109 009 hoặc https://zalo.me/0933109009).</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Email CSKH:</th>
                        <td>
                            <input type="email" name="nbook_email" value="<?php echo esc_attr(get_option('nbook_email', 'cskh@biblio.vn')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Địa chỉ trụ sở:</th>
                        <td>
                            <input type="text" name="nbook_address" value="<?php echo esc_attr(get_option('nbook_address', '207 Giải Phóng, Phường Bạch Mai, TP. Hà Nội (Đại học Kinh tế Quốc dân NEU)')); ?>" class="large-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Đoạn giới thiệu "Về Biblio":</th>
                        <td>
                            <textarea name="nbook_about_intro" rows="3" class="large-text"><?php echo esc_textarea(get_option('nbook_about_intro', 'Biblio là sàn thương mại điện tử chuyên biệt ngành sách hàng đầu dành cho sinh viên và độc giả trẻ. Chúng tôi mang tri thức tinh hoa tới bạn đọc với trải nghiệm mua sắm nhanh chóng, tiện lợi và tiết kiệm nhất.')); ?></textarea>
                        </td>
                    </tr>

                    <tr id="biblio-ai-settings"><th colspan="2"><h2>Cấu Hình Chatbot & Trí Tuệ Nhân Tạo (AI LLM)</h2></th></tr>
                    <tr valign="top">
                        <th scope="row">Kết nối Mô hình Ngôn ngữ (LLM API):</th>
                        <td>
                            <label>
                                <input type="checkbox" name="biblio_ai_enabled" value="1" <?php checked(get_option('biblio_ai_enabled', 0), 1); ?>>
                                <strong>Kích hoạt kết nối API mô hình ngôn ngữ lớn (LLM)</strong>
                            </label>
                            <p class="description">
                                <em>Mặc định tắt (0)</em>: Khi tắt, Chatbot sẽ chạy hoàn toàn bằng <strong>bộ quy tắc nội bộ + tìm kiếm trong dữ liệu website CSDL</strong> (tên sách, tác giả, thể loại, mức giá, tra đơn hàng và FAQ) mà không phụ thuộc vào bất kỳ dịch vụ hay API bên ngoài nào.<br>
                                Khi bật, hệ thống sẽ sử dụng API key bên dưới để sinh phản hồi tự nhiên cho các câu hỏi tự do. Nếu API lỗi hoặc quá hạn, hệ thống tự động fallback về engine luật.
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Khóa API (API Key):</th>
                        <td>
                            <input type="password" name="biblio_ai_api_key" value="<?php echo esc_attr(get_option('biblio_ai_api_key', '')); ?>" class="regular-text" placeholder="AIzaSy... / sk-..." autocomplete="new-password" />
                            <p class="description">Khóa API được lưu an toàn trong CSDL (<code>wp_options</code>), tuyệt đối không ghi cứng vào mã nguồn. Nhập khóa Google Gemini API hoặc nhà cung cấp tương thích.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Mô hình AI (Model):</th>
                        <td>
                            <select name="biblio_ai_model">
                                <?php $current_model = get_option('biblio_ai_model', 'gemini-1.5-flash'); ?>
                                <option value="gemini-1.5-flash" <?php selected($current_model, 'gemini-1.5-flash'); ?>>Google Gemini 1.5 Flash (Nhanh, tối ưu, miễn phí)</option>
                                <option value="gemini-2.0-flash" <?php selected($current_model, 'gemini-2.0-flash'); ?>>Google Gemini 2.0 Flash (Thế hệ mới)</option>
                                <option value="gemini-1.5-pro" <?php selected($current_model, 'gemini-1.5-pro'); ?>>Google Gemini 1.5 Pro (Nâng cao)</option>
                            </select>
                            <p class="description">Chọn mô hình ngôn ngữ muốn sử dụng khi chức năng AI được kích hoạt.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Lưu Cài Đặt Biblio'); ?>
            </form>
        </div>
        <?php
    }

    // =========================================================================
    // 3. SHORTCODES: SÁCH BÁN CHẠY & SÁCH MỚI
    // =========================================================================
    public function shortcode_bestsellers($atts) {
        $atts = shortcode_atts([
            'limit'   => 8,
            'columns' => 4,
        ], $atts, 'nbook_bestsellers');

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => intval($atts['limit']),
            'meta_key'       => 'total_sales',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ];

        return $this->render_product_grid($args, 'nb-bestsellers-grid', $atts['columns']);
    }

    public function shortcode_new_books($atts) {
        $atts = shortcode_atts([
            'limit'   => 8,
            'columns' => 4,
        ], $atts, 'nbook_new_books');

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ];

        return $this->render_product_grid($args, 'nb-newbooks-grid', $atts['columns']);
    }

    private function render_product_grid($query_args, $grid_class = '', $columns = 4) {
        if (!function_exists('wc_get_product')) return '';

        $query = new WP_Query($query_args);
        if (!$query->have_posts()) {
            return '<div class="nb-empty-prods">Đang cập nhật danh sách sách...</div>';
        }

        ob_start();
        ?>
        <div class="nb-product-grid nb-cols-<?php echo esc_attr($columns); ?> <?php echo esc_attr($grid_class); ?>">
            <?php
            while ($query->have_posts()): $query->the_post();
                $product = wc_get_product(get_the_ID());
                if (!$product) continue;

                $pid        = $product->get_id();
                $title      = $product->get_name();
                $link       = $product->get_permalink();
                $img_url    = get_the_post_thumbnail_url($pid, 'medium') ?: wc_placeholder_img_src();
                $price_html = $product->get_price_html();
                $reg_price  = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                $author     = get_post_meta($pid, '_biblio_author', true) ?: get_post_meta($pid, '_nbook_author', true);
                $stock_info = function_exists('nbook_get_product_stock_info') ? nbook_get_product_stock_info($product) : null;
                $is_out_of_stock = $stock_info ? $stock_info['is_out_of_stock'] : !$product->is_in_stock();

                // Calculate discount percent
                $discount_pct = 0;
                if ($reg_price && $sale_price && $reg_price > $sale_price) {
                    $discount_pct = round((($reg_price - $sale_price) / $reg_price) * 100);
                }
                ?>
                <div class="nb-product-card">
                    <a href="<?php echo esc_url($link); ?>" class="nb-card-link">
                        <div class="nb-card-thumb-wrap">
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($title); ?>" class="nb-card-img <?php echo $is_out_of_stock ? 'nb-thumb-dimmed' : ''; ?>" loading="lazy" />
                            <?php if ($discount_pct > 0): ?>
                                <span class="nb-badge-discount"><span class="nb-badge-bolt">⚡</span> GIẢM <?php echo esc_html($discount_pct); ?>%</span>
                            <?php endif; ?>
                            <span class="nb-badge-favorite">Yêu thích</span>
                        </div>
                        <div class="nb-card-body">
                            <?php if ($author): ?>
                                <div class="nb-card-author"><?php echo esc_html($author); ?></div>
                            <?php endif; ?>
                            <h3 class="nb-card-title" title="<?php echo esc_attr($title); ?>"><?php echo esc_html($title); ?></h3>
                            <?php if (function_exists('nbook_render_product_rating')): ?>
                                <?php nbook_render_product_rating($product, 'archive'); ?>
                            <?php endif; ?>
                            <div class="nb-card-price-row">
                                <?php echo $price_html; ?>
                            </div>
                            <?php if ($stock_info): ?>
                                <div class="nb-card-stock-row mt-1">
                                    <?php echo $stock_info['badge_html']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="nb-card-action">
                        <?php if ($is_out_of_stock): ?>
                            <button type="button" 
                                    class="nb-btn-add-cart nb-btn-stock-notify nb-open-stock-notify-modal" 
                                    data-product-id="<?php echo esc_attr($pid); ?>" 
                                    data-product-title="<?php echo esc_attr($title); ?>"
                                    aria-label="Thông báo khi có hàng">
                                <i class="bi bi-bell-fill"></i>&nbsp;&nbsp;<span>Báo khi có hàng</span>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                               data-quantity="1" 
                               class="nb-btn-add-cart button product_type_simple add_to_cart_button ajax_add_to_cart" 
                               data-product_id="<?php echo esc_attr($pid); ?>" 
                               data-product_sku="<?php echo esc_attr($product->get_sku()); ?>" 
                               aria-label="Thêm vào giỏ hàng">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                <span>Thêm vào giỏ</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // 4. AJAX SEARCH (Tên sách, Tác giả, Mã sách/SKU)
    // =========================================================================
    public function ajax_search_callback() {
        check_ajax_referer('nbook_search_nonce', 'nonce', false);

        $keyword = isset($_GET['term']) ? sanitize_text_field(trim($_GET['term'])) : '';
        if (empty($keyword) || strlen($keyword) < 2) {
            wp_send_json_success([]);
        }

        global $wpdb;
        $like = '%' . $wpdb->esc_like($keyword) . '%';

        // Query products matching title, SKU, or author meta
        $sql = "
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_author ON (p.ID = pm_author.post_id AND pm_author.meta_key IN ('_biblio_author', '_nbook_author'))
            LEFT JOIN {$wpdb->postmeta} pm_sku ON (p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku')
            WHERE p.post_type = 'product'
              AND p.post_status = 'publish'
              AND (
                  p.post_title LIKE %s
                  OR pm_author.meta_value LIKE %s
                  OR pm_sku.meta_value LIKE %s
              )
            LIMIT 8
        ";

        $results = $wpdb->get_col($wpdb->prepare($sql, $like, $like, $like));

        $output = [];
        if (!empty($results)) {
            foreach ($results as $pid) {
                $prod = wc_get_product($pid);
                if (!$prod) continue;

                $thumb = get_the_post_thumbnail_url($pid, 'thumbnail') ?: wc_placeholder_img_src();
                $author = get_post_meta($pid, '_biblio_author', true) ?: get_post_meta($pid, '_nbook_author', true);
                $sku = $prod->get_sku();

                $output[] = [
                    'id'     => $pid,
                    'title'  => $prod->get_name(),
                    'author' => $author ?: 'Nhiều tác giả',
                    'sku'    => $sku ?: '',
                    'price'  => $prod->get_price_html(),
                    'url'    => $prod->get_permalink(),
                    'thumb'  => $thumb,
                ];
            }
        }

        wp_send_json_success($output);
    }

    // =========================================================================
    // 5. AUTO SEED DEFAULT BANNERS & POSTS
    // =========================================================================
    public function maybe_seed_data() {
        // Seed default announcement if empty
        if (!get_option('nbook_top_announcement')) {
            update_option('nbook_top_announcement', 'Miễn phí vận chuyển đơn từ 299.000đ');
        }

        // Seed 3 promotional banners if 0 banners exist
        $banner_count = wp_count_posts('banner');
        if (empty($banner_count->publish)) {
            $banners = [
                [
                    'title'    => 'Đại Tiệc Mùa Thi 2026 – Giảm Đến 50%',
                    'desc'     => 'Hàng ngàn tựa sách giáo trình NEU, sách kỹ năng và ngoại ngữ giá cực ưu đãi.',
                    'btn_text' => 'Khám Phá Ngay',
                    'btn_url'  => '/san-pham/',
                    'badge'    => 'SIÊU SALE 50%',
                    'gradient' => 'linear-gradient(135deg, #ee4d2d 0%, #ff6f3c 100%)',
                ],
                [
                    'title'    => 'Sách Mới Lên Kệ – Khởi Nguồn Tri Thức',
                    'desc'     => 'Cập nhật tác phẩm kinh điển và best-seller mới nhất từ NXB Trẻ, Kim Đồng, Nhã Nam.',
                    'btn_text' => 'Xem Sách Mới',
                    'btn_url'  => '/san-pham/',
                    'badge'    => 'SÁCH MỚI',
                    'gradient' => 'linear-gradient(135deg, #e65100 0%, #ff8f00 100%)',
                ],
                [
                    'title'    => 'Freeship Toàn Quốc Đơn Từ 299.000đ',
                    'desc'     => 'Bọc sách miễn phí + Tặng kèm bookmark xinh xắn cho 100% đơn hàng tại Biblio.',
                    'btn_text' => 'Mua Sắm Ngay',
                    'btn_url'  => '/san-pham/',
                    'badge'    => 'FREESHIP XTRA',
                    'gradient' => 'linear-gradient(135deg, #d84315 0%, #f4511e 100%)',
                ],
            ];

            foreach ($banners as $idx => $b) {
                $bid = wp_insert_post([
                    'post_title'   => $b['title'],
                    'post_type'    => 'banner',
                    'post_status'  => 'publish',
                    'menu_order'   => $idx + 1,
                ]);
                if ($bid && !is_wp_error($bid)) {
                    update_post_meta($bid, '_nbook_banner_desc', $b['desc']);
                    update_post_meta($bid, '_nbook_banner_btn_text', $b['btn_text']);
                    update_post_meta($bid, '_nbook_banner_btn_url', $b['btn_url']);
                    update_post_meta($bid, '_nbook_banner_badge', $b['badge']);
                    update_post_meta($bid, '_nbook_banner_bg_gradient', $b['gradient']);
                }
            }
        }
    }
}

$GLOBALS['nbook_core_instance'] = new Nbook_Core();

function nbook_add_notification($user_id, $title, $message, $url = '', $type = 'general') {
    $core = $GLOBALS['nbook_core_instance'] ?? null;
    return $core instanceof Nbook_Core ? $core->add_notification($user_id, $title, $message, $url, $type) : false;
}

function nbook_get_points_balance($user_id) {
    $core = $GLOBALS['nbook_core_instance'] ?? null;
    return $core instanceof Nbook_Core ? $core->points_balance($user_id) : 0;
}
