<?php
/**
 * Biblio Chatbot Module
 * 
 * Module Chatbot tư vấn sách thông minh cho sàn Biblio:
 * - Hỗ trợ tìm kiếm sách, tác giả, thể loại, mức giá trực tiếp từ CSDL website.
 * - Tra cứu trạng thái đơn hàng bảo mật cho thành viên và khách vãng lai.
 * - Ngân hàng câu hỏi FAQ quản lý trong wp-admin.
 * - Tùy chọn kết nối LLM API (Gemini/OpenAI) lưu an toàn trong wp_options.
 * - Lưu lịch sử hội thoại, chuyển cho nhân viên (Hotline/Zalo).
 * - Bảo mật Nonce, Rate Limiting, Sanitize.
 *
 * @package Nbook_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Biblio_Chatbot {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Initialization handled when invoked
    }

    /**
     * Khởi tạo bảng CSDL cho Chatbot
     */
    public function ensure_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Bảng Sessions
        dbDelta("CREATE TABLE {$wpdb->prefix}nbook_chatbot_sessions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_key varchar(80) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            transferred tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_key (session_key),
            KEY user_id (user_id),
            KEY transferred (transferred)
        ) {$charset};");

        // 2. Bảng Messages
        dbDelta("CREATE TABLE {$wpdb->prefix}nbook_chatbot_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id bigint(20) unsigned NOT NULL,
            sender varchar(20) NOT NULL DEFAULT 'user',
            message longtext NOT NULL,
            payload longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY session_id_created (session_id, created_at)
        ) {$charset};");

        // 3. Bảng FAQ
        dbDelta("CREATE TABLE {$wpdb->prefix}nbook_chatbot_faq (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question varchar(255) NOT NULL,
            keywords text NOT NULL,
            answer longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            priority int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY is_active_priority (is_active, priority)
        ) {$charset};");

        update_option('nbook_chatbot_db_version', '1');
        $this->seed_default_faq();
    }

    /**
     * Nạp dữ liệu câu hỏi mẫu ban đầu nếu bảng FAQ rỗng
     */
    public function seed_default_faq() {
        global $wpdb;
        $table = $wpdb->prefix . 'nbook_chatbot_faq';
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $now = current_time('mysql');
        $sample_faqs = [
            [
                'question' => 'Chính sách đổi trả sách tại Biblio như thế nào?',
                'keywords' => 'đổi trả, hoàn tiền, trả hàng, lỗi in, thiếu trang, sách rách, rách sách, đổi sách, trả sách, bảo hành',
                'answer'   => 'Biblio hỗ trợ <strong>đổi trả miễn phí 100% trong vòng 7 ngày</strong> kể từ khi quý khách nhận sách đối với các trường hợp: sách in lỗi, thiếu trang, rách bìa hoặc giao nhầm sách.<br>Quý khách chỉ cần liên hệ hotline <strong>' . esc_html(get_option('nbook_hotline', '0933 109 009')) . '</strong> hoặc nhắn qua Zalo để được giao đổi cuốn mới tận nhà hoàn toàn miễn phí!',
                'priority' => 10,
            ],
            [
                'question' => 'Thời gian giao hàng bao lâu và phí vận chuyển như thế nào?',
                'keywords' => 'phí vận chuyển, phí ship, giao hàng, bao lâu, thời gian giao, freeship, ship, vận chuyển, giao nhanh, bao giờ nhận được',
                'answer'   => 'Biblio áp dụng chính sách giao hàng nhanh chóng:<br>• <strong>Hỏa tốc 2 giờ:</strong> Khu vực nội thành Hà Nội (đặc biệt quanh ĐH Kinh tế Quốc dân NEU).<br>• <strong>Toàn quốc:</strong> 1 – 3 ngày làm việc.<br>🎁 <strong>Miễn phí vận chuyển (Freeship)</strong> cho tất cả đơn hàng từ <strong>299.000đ</strong> trở lên hoặc khi quý khách nhập mã voucher <code>FREESHIP</code>.',
                'priority' => 9,
            ],
            [
                'question' => 'Biblio hỗ trợ những phương thức thanh toán nào?',
                'keywords' => 'thanh toán, phương thức, trả tiền, cod, chuyển khoản, qr, vietqr, ngân hàng, momo, tiền mặt, thẻ',
                'answer'   => 'Quý khách có thể lựa chọn 3 phương thức thanh toán an toàn và tiện lợi tại Biblio:<br>1. <strong>Thanh toán khi nhận hàng (COD):</strong> Kiểm tra sách và thanh toán tiền mặt cho nhân viên giao hàng.<br>2. <strong>Chuyển khoản VietQR:</strong> Quét mã QR tự động điền sẵn số tiền và nội dung mã đơn hàng.<br>3. <strong>Ví điện tử / Thẻ ngân hàng.</strong>',
                'priority' => 8,
            ],
            [
                'question' => 'Biblio có hỗ trợ bọc sách và tặng kèm bookmark không?',
                'keywords' => 'bọc sách, quà tặng, bookmark, kẹp sách, bao sách, màng bọc, bọc bìa, plastic',
                'answer'   => 'Có ạ! 100% đơn hàng tại Biblio đều được <strong>tặng kèm Bookmark nghệ thuật</strong> xinh xắn 🔖 và hỗ trợ <strong>bọc màng bọc plastic cao cấp hoàn toàn miễn phí</strong> giúp bảo vệ cuốn sách của quý khách luôn bền đẹp như mới!',
                'priority' => 7,
            ],
            [
                'question' => 'Biblio Xu là gì và làm thế nào để sử dụng?',
                'keywords' => 'xu, điểm thưởng, biblio xu, tích điểm, đổi xu, điểm danh, đổi quà, hoàn xu',
                'answer'   => '<strong>Biblio Xu</strong> là chương trình khách hàng thân thiết tại Biblio:<br>• <strong>Tích điểm:</strong> Mua hàng (1.000đ = 1 Xu), Điểm danh hàng ngày (+10 Xu), Đăng ký tài khoản (+100 Xu).<br>• <strong>Sử dụng:</strong> Dùng Xu để trừ tiền trực tiếp khi thanh toán (1 Xu = 1 VNĐ) hoặc đổi voucher độc quyền tại trang <a href="' . esc_url(home_url('/diem-thuong/')) . '">Điểm Thưởng</a>.',
                'priority' => 6,
            ],
            [
                'question' => 'Biblio có giáo trình NEU và sách học phần sinh viên không?',
                'keywords' => 'giáo trình neu, tài liệu neu, đại học kinh tế quốc dân, học phần neu',
                'answer'   => 'Biblio cung cấp <strong>đầy đủ Giáo trình chuẩn Đại học Kinh tế Quốc dân (NEU)</strong> mới nhất: Kinh tế vi mô, Vĩ mô, Marketing căn bản, Quản trị học, Kinh tế lượng, Pháp luật đại cương, Kế toán... Cam kết 100% sách chính ngạch bản quyền từ NXB Đại học Kinh tế Quốc dân.',
                'priority' => 5,
            ],
            [
                'question' => 'Địa chỉ nhà sách Biblio ở đâu và giờ mở cửa thế nào?',
                'keywords' => 'địa chỉ, ở đâu, cửa hàng, giờ mở cửa, liên hệ, hotline, đến mua trực tiếp, văn phòng',
                'answer'   => 'Quý khách có thể ghé thăm Biblio tại:<br>📍 <strong>Địa chỉ:</strong> ' . esc_html(get_option('nbook_address', '207 Giải Phóng, Phường Bạch Mai, TP. Hà Nội (Đại học Kinh tế Quốc dân NEU)')) . '<br>⏰ <strong>Giờ mở cửa:</strong> 08:00 – 21:30 tất cả các ngày trong tuần.<br>📞 <strong>Hotline:</strong> ' . esc_html(get_option('nbook_hotline', '0933 109 009')) . ' (Zalo hỗ trợ 24/7).',
                'priority' => 4,
            ],
        ];

        foreach ($sample_faqs as $faq) {
            $wpdb->insert($table, [
                'question'   => $faq['question'],
                'keywords'   => $faq['keywords'],
                'answer'     => $faq['answer'],
                'is_active'  => 1,
                'priority'   => $faq['priority'],
                'created_at' => $now,
                'updated_at' => $now,
            ], ['%s', '%s', '%s', '%d', '%d', '%s', '%s']);
        }
    }

    /**
     * Đăng ký trang quản trị Chatbot
     */
    public function register_admin_pages() {
        add_submenu_page(
            'nbook-notifications',
            'Biblio Chatbot',
            'Biblio Chatbot',
            'manage_biblio_content',
            'nbook-chatbot',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Render giao diện quản trị Chatbot (FAQ & Lịch sử hội thoại)
     */
    public function render_admin_page() {
        if (!current_user_can('manage_biblio_content') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang này.');
        }

        global $wpdb;
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'faq';
        $table_faq = $wpdb->prefix . 'nbook_chatbot_faq';
        $table_sessions = $wpdb->prefix . 'nbook_chatbot_sessions';
        $table_messages = $wpdb->prefix . 'nbook_chatbot_messages';

        ?>
        <div class="wrap biblio-chatbot-admin-wrap">
            <h1 class="wp-heading-inline">Trợ Lý Ảo Biblio Chatbot</h1>
            <hr class="wp-header-end">

            <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
                <div class="notice notice-success is-dismissible"><p>Đã lưu câu hỏi FAQ thành công!</p></div>
            <?php elseif (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
                <div class="notice notice-success is-dismissible"><p>Đã xóa câu hỏi FAQ thành công!</p></div>
            <?php endif; ?>

            <nav class="nav-tab-wrapper">
                <a href="?page=nbook-chatbot&tab=faq" class="nav-tab <?php echo $current_tab === 'faq' ? 'nav-tab-active' : ''; ?>">Quản Lý FAQ (Câu Hỏi Thường Gặp)</a>
                <a href="?page=nbook-chatbot&tab=sessions" class="nav-tab <?php echo $current_tab === 'sessions' ? 'nav-tab-active' : ''; ?>">Lịch Sử Hội Thoại</a>
                <a href="?page=nbook-chatbot&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Trạng Thái & Cấu Hình AI</a>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php if ($current_tab === 'faq'): ?>
                    <?php
                    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
                    $edit_item = null;
                    if ($edit_id) {
                        $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_faq} WHERE id = %d", $edit_id));
                    }
                    $faqs = $wpdb->get_results("SELECT * FROM {$table_faq} ORDER BY priority DESC, id DESC");
                    ?>
                    <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                        <!-- Form Thêm / Sửa FAQ -->
                        <div style="flex: 1; min-width: 320px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
                            <h3><?php echo $edit_item ? 'Chỉnh Sửa Câu Hỏi FAQ #' . $edit_item->id : 'Thêm Câu Hỏi FAQ Mới'; ?></h3>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="nbook_chatbot_save_faq">
                                <input type="hidden" name="faq_id" value="<?php echo esc_attr($edit_item ? $edit_item->id : 0); ?>">
                                <?php wp_nonce_field('nbook_chatbot_faq_nonce', 'chatbot_nonce'); ?>

                                <p>
                                    <label><strong>Câu hỏi / Chủ đề:</strong></label><br>
                                    <input type="text" name="question" class="large-text" required value="<?php echo esc_attr($edit_item ? $edit_item->question : ''); ?>" placeholder="Ví dụ: Chính sách đổi trả sách tại Biblio như thế nào?">
                                </p>

                                <p>
                                    <label><strong>Từ khóa nhận diện (phân tách bởi dấu phẩy):</strong></label><br>
                                    <input type="text" name="keywords" class="large-text" required value="<?php echo esc_attr($edit_item ? $edit_item->keywords : ''); ?>" placeholder="đổi trả, hoàn tiền, sách lỗi, trả hàng">
                                    <span class="description">Chatbot sẽ so khớp các từ khóa này khi khách hàng đặt câu hỏi.</span>
                                </p>

                                <p>
                                    <label><strong>Câu trả lời của Chatbot (hỗ trợ HTML):</strong></label><br>
                                    <textarea name="answer" rows="6" class="large-text" required placeholder="Nhập câu trả lời giải đáp chi tiết..."><?php echo esc_textarea($edit_item ? $edit_item->answer : ''); ?></textarea>
                                </p>

                                <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                                    <label>
                                        <strong>Độ ưu tiên:</strong><br>
                                        <input type="number" name="priority" value="<?php echo esc_attr($edit_item ? $edit_item->priority : 0); ?>" style="width: 80px;">
                                    </label>
                                    <label style="margin-top: 22px;">
                                        <input type="checkbox" name="is_active" value="1" <?php checked($edit_item ? $edit_item->is_active : 1, 1); ?>> <strong>Đang kích hoạt</strong>
                                    </label>
                                </div>

                                <p>
                                    <button type="submit" class="button button-primary"><?php echo $edit_item ? 'Cập Nhật Câu Hỏi' : 'Thêm Mới FAQ'; ?></button>
                                    <?php if ($edit_item): ?>
                                        <a href="?page=nbook-chatbot&tab=faq" class="button">Hủy chỉnh sửa</a>
                                    <?php endif; ?>
                                </p>
                            </form>
                        </div>

                        <!-- Danh Sách FAQ Hiện Tại -->
                        <div style="flex: 2; min-width: 480px;">
                            <table class="widefat striped">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">ID</th>
                                        <th>Câu hỏi</th>
                                        <th>Từ khóa nhận diện</th>
                                        <th style="width: 70px;">Ưu tiên</th>
                                        <th style="width: 70px;">Trạng thái</th>
                                        <th style="width: 120px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($faqs)): ?>
                                        <tr><td colspan="6">Chưa có câu hỏi FAQ nào.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($faqs as $f): ?>
                                            <tr>
                                                <td><?php echo esc_html($f->id); ?></td>
                                                <td><strong><?php echo esc_html($f->question); ?></strong></td>
                                                <td><code><?php echo esc_html($f->keywords); ?></code></td>
                                                <td><?php echo esc_html($f->priority); ?></td>
                                                <td>
                                                    <?php if ($f->is_active): ?>
                                                        <span style="color: #46b450; font-weight: 600;">✓ Bật</span>
                                                    <?php else: ?>
                                                        <span style="color: #dc3232;">✕ Tắt</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="?page=nbook-chatbot&tab=faq&edit=<?php echo esc_attr($f->id); ?>" class="button button-small">Sửa</a>
                                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=nbook_chatbot_delete_faq&id=' . $f->id), 'delete_faq_' . $f->id)); ?>" 
                                                       class="button button-small button-link-delete" 
                                                       onclick="return confirm('Bạn có chắc muốn xóa câu hỏi này?');">Xóa</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($current_tab === 'sessions'): ?>
                    <?php
                    $view_session_id = isset($_GET['session_id']) ? absint($_GET['session_id']) : 0;
                    if ($view_session_id) {
                        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_sessions} WHERE id = %d", $view_session_id));
                        $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_messages} WHERE session_id = %d ORDER BY created_at ASC", $view_session_id));
                        ?>
                        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 800px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                <h2>Chi tiết cuộc hội thoại #<?php echo esc_html($session->id); ?></h2>
                                <a href="?page=nbook-chatbot&tab=sessions" class="button">← Quay lại danh sách</a>
                            </div>
                            <p>
                                <strong>Khách hàng:</strong> <?php echo $session->user_id ? esc_html(get_userdata($session->user_id)->display_name ?? 'User #' . $session->user_id) : 'Khách vãng lai'; ?> | 
                                <strong>Thời gian:</strong> <?php echo esc_html(mysql2date('d/m/Y H:i', $session->created_at)); ?> | 
                                <strong>Trạng thái:</strong> <?php echo $session->transferred ? '<span style="color: #ee4d2d; font-weight: bold;">Đã chuyển CSKH / Nhân viên</span>' : '<span style="color: #46b450;">Tự động (Bot)</span>'; ?>
                            </p>
                            <hr>
                            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px; max-height: 500px; overflow-y: auto; padding-right: 10px;">
                                <?php foreach ($messages as $m): ?>
                                    <div style="align-self: <?php echo $m->sender === 'user' ? 'flex-end' : 'flex-start'; ?>; max-width: 80%; background: <?php echo $m->sender === 'user' ? '#ee4d2d' : '#f0f0f1'; ?>; color: <?php echo $m->sender === 'user' ? '#fff' : '#1d2327'; ?>; padding: 10px 14px; border-radius: 12px;">
                                        <div style="font-size: 11px; opacity: 0.8; margin-bottom: 4px;">
                                            <?php echo $m->sender === 'user' ? 'Khách hàng' : ($m->sender === 'agent' ? 'Nhân viên' : 'Biblio Assistant'); ?> · <?php echo esc_html(mysql2date('H:i:s d/m', $m->created_at)); ?>
                                        </div>
                                        <div><?php echo nl2br(esc_html($m->message)); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php
                    } else {
                        $sessions = $wpdb->get_results("
                            SELECT s.*, COUNT(m.id) as message_count, MAX(m.created_at) as last_activity
                            FROM {$table_sessions} s
                            LEFT JOIN {$table_messages} m ON s.id = m.session_id
                            GROUP BY s.id
                            ORDER BY last_activity DESC
                            LIMIT 50
                        ");
                        ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Session Key</th>
                                    <th>Khách hàng</th>
                                    <th>Số tin nhắn</th>
                                    <th>Chuyển nhân viên</th>
                                    <th>Hoạt động gần nhất</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sessions)): ?>
                                    <tr><td colspan="7">Chưa có lịch sử hội thoại nào được ghi nhận.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($sessions as $s): ?>
                                        <tr>
                                            <td><?php echo esc_html($s->id); ?></td>
                                            <td><code><?php echo esc_html(substr($s->session_key, 0, 16) . '...'); ?></code></td>
                                            <td>
                                                <?php 
                                                if ($s->user_id) {
                                                    $user = get_userdata($s->user_id);
                                                    echo esc_html($user ? $user->display_name : 'User #' . $s->user_id);
                                                } else {
                                                    echo '<span style="color: #666;">Khách vãng lai</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo esc_html($s->message_count); ?> tin</td>
                                            <td>
                                                <?php echo $s->transferred ? '<span style="color: #ee4d2d; font-weight: bold;">Có (CSKH)</span>' : 'Không'; ?>
                                            </td>
                                            <td><?php echo esc_html(mysql2date('d/m/Y H:i', $s->last_activity ?: $s->created_at)); ?></td>
                                            <td>
                                                <a href="?page=nbook-chatbot&tab=sessions&session_id=<?php echo esc_attr($s->id); ?>" class="button button-small">Xem chi tiết</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php
                    }
                    ?>

                <?php elseif ($current_tab === 'settings'): ?>
                    <?php
                    $ai_enabled = (bool) get_option('biblio_ai_enabled', 0);
                    $ai_model   = get_option('biblio_ai_model', 'gemini-1.5-flash');
                    $has_api_key = !empty(get_option('biblio_ai_api_key'));
                    ?>
                    <div style="background: #fff; padding: 24px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 800px;">
                        <h2>Cấu Hình Mô Hình Trí Tuệ Nhân Tạo (AI LLM)</h2>
                        <p>Hệ thống Chatbot Biblio hoạt động theo cơ chế lai (Hybrid):</p>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <li><strong>Chế độ Luật & CSDL nội bộ (Rule-based):</strong> Luôn hoạt động độc lập, tự động phân tích từ khóa, tìm kiếm sách, tra cứu đơn hàng cá nhân, quét voucher khả dụng và trả lời câu hỏi FAQ mà không cần bất kỳ API nào bên ngoài.</li>
                            <li><strong>Chế độ Mô Hình Ngôn Ngữ Lớn (LLM API):</strong> Khi được bật, các câu hỏi trò chuyện tự do của khách hàng sẽ được gửi tới AI để phản hồi tự nhiên và thông minh hơn. Khi tắt hoặc gặp sự cố mạng, hệ thống tự động chạy theo luật.</li>
                        </ul>

                        <div style="background: #f8f9fa; border-left: 4px solid <?php echo $ai_enabled ? '#46b450' : '#ee4d2d'; ?>; padding: 12px 16px; margin: 20px 0;">
                            <strong>Trạng thái hiện tại:</strong> 
                            <?php if ($ai_enabled): ?>
                                <span style="color: #46b450; font-weight: bold;">ĐANG BẬT AI (Model: <?php echo esc_html($ai_model); ?>)</span>
                                <?php echo $has_api_key ? '✓ Đã cấu hình API Key an toàn' : '⚠️ Chưa nhập API Key'; ?>
                            <?php else: ?>
                                <span style="color: #ee4d2d; font-weight: bold;">ĐANG TẮT (Chạy thuần Luật & Tìm kiếm Dữ liệu Sách CSDL Website)</span>
                            <?php endif; ?>
                        </div>

                        <p>Quý khách có thể quản lý Khóa API, Bật/Tắt AI và chọn Model tại mục Cài Đặt của hệ thống:</p>
                        <a href="<?php echo esc_url(admin_url('options-general.php?page=nbook-settings#biblio-ai-settings')); ?>" class="button button-primary">
                            Đi tới Cài Đặt → Biblio để cấu hình API Key
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Lưu câu hỏi FAQ từ Admin
     */
    public function admin_save_faq() {
        if ((!current_user_can('manage_biblio_content') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) || !check_admin_referer('nbook_chatbot_faq_nonce', 'chatbot_nonce')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'nbook_chatbot_faq';
        $faq_id = isset($_POST['faq_id']) ? absint($_POST['faq_id']) : 0;
        $question = sanitize_text_field(wp_unslash($_POST['question'] ?? ''));
        $keywords = sanitize_text_field(wp_unslash($_POST['keywords'] ?? ''));
        $answer = wp_kses_post(wp_unslash($_POST['answer'] ?? ''));
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $now = current_time('mysql');

        if (empty($question) || empty($answer)) {
            wp_die('Vui lòng điền đầy đủ câu hỏi và câu trả lời.');
        }

        if ($faq_id > 0) {
            $wpdb->update($table, [
                'question'   => $question,
                'keywords'   => $keywords,
                'answer'     => $answer,
                'priority'   => $priority,
                'is_active'  => $is_active,
                'updated_at' => $now,
            ], ['id' => $faq_id], ['%s', '%s', '%s', '%d', '%d', '%s'], ['%d']);
        } else {
            $wpdb->insert($table, [
                'question'   => $question,
                'keywords'   => $keywords,
                'answer'     => $answer,
                'priority'   => $priority,
                'is_active'  => $is_active,
                'created_at' => $now,
                'updated_at' => $now,
            ], ['%s', '%s', '%s', '%d', '%d', '%s', '%s']);
        }

        wp_safe_redirect(admin_url('admin.php?page=nbook-chatbot&tab=faq&message=saved'));
        exit;
    }

    /**
     * Xóa câu hỏi FAQ từ Admin
     */
    public function admin_delete_faq() {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ((!current_user_can('manage_biblio_content') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) || !check_admin_referer('delete_faq_' . $id)) {
            wp_die('Bạn không có quyền thực hiện thao tác này.');
        }

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'nbook_chatbot_faq', ['id' => $id], ['%d']);
        wp_safe_redirect(admin_url('admin.php?page=nbook-chatbot&tab=faq&message=deleted'));
        exit;
    }

    /**
     * Enqueue Assets cho Frontend Chatbot Widget
     */
    public function enqueue_assets() {
        // Enqueue styles & script inline or through localized data
        wp_enqueue_script('jquery');
    }

    /**
     * Render Chatbot Widget ra Footer của Website
     */
    public function render_widget() {
        $hotline = get_option('nbook_hotline', '0933 109 009');
        $zalo = get_option('nbook_zalo', '0933 109 009');
        $clean_zalo_url = 'https://zalo.me/' . preg_replace('/[^0-9]/', '', $zalo);
        $clean_hotline_tel = 'tel:' . preg_replace('/[^0-9]/', '', $hotline);
        $nonce = wp_create_nonce('nbook_chatbot_nonce');
        $ajax_url = admin_url('admin-ajax.php');
        $is_logged_in = is_user_logged_in();
        $user_name = $is_logged_in ? wp_get_current_user()->display_name : 'Quý khách';

        ?>
        <!-- ========================================== -->
        <!-- BIBLIO CHATBOT WIDGET -->
        <!-- ========================================== -->
        <div id="biblio-chatbot-root" class="biblio-chatbot-root">
            <!-- Nút tròn nổi góc dưới -->
            <button type="button" id="biblio-chat-toggle-btn" class="biblio-chat-toggle-btn" aria-label="Mở Trợ lý ảo Biblio" title="Trò chuyện cùng Biblio Assistant">
                <span class="biblio-chat-btn-icon-open">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </span>
                <span class="biblio-chat-btn-icon-close" style="display: none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </span>
                <span class="biblio-chat-badge-dot"></span>
            </button>

            <!-- Hộp thoại Chat Popup -->
            <div id="biblio-chat-window" class="biblio-chat-window" role="dialog" aria-modal="false" aria-label="Hộp thoại tư vấn Biblio" style="display: none;">
                <!-- Header -->
                <div class="biblio-chat-header">
                    <div class="biblio-chat-header-user">
                        <div class="biblio-chat-avatar-wrap">
                            <span class="biblio-chat-avatar-icon">📚</span>
                            <span class="biblio-chat-status-dot" title="Trực tuyến"></span>
                        </div>
                        <div class="biblio-chat-info">
                            <h3 class="biblio-chat-title">Biblio Assistant</h3>
                            <span class="biblio-chat-subtitle">Trợ lý sách thông minh</span>
                        </div>
                    </div>
                    <div class="biblio-chat-header-actions">
                        <button type="button" id="biblio-chat-btn-transfer" class="biblio-chat-header-btn" title="Gặp nhân viên CSKH (Hotline/Zalo)" aria-label="Chuyển nhân viên">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span class="biblio-chat-transfer-text">Gặp CSKH</span>
                        </button>
                        <button type="button" id="biblio-chat-btn-close" class="biblio-chat-header-btn biblio-chat-close" title="Thu nhỏ chat" aria-label="Đóng chat">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body (Lịch sử hội thoại) -->
                <div id="biblio-chat-body" class="biblio-chat-body">
                    <!-- Tin nhắn chào mở đầu -->
                    <div class="biblio-chat-msg biblio-chat-msg-bot">
                        <div class="biblio-chat-bubble">
                            <p>Xin chào <strong><?php echo esc_html($user_name); ?></strong>! Em là <strong>Biblio Assistant 📚</strong>.</p>
                            <p>Em có thể hỗ trợ quý khách tìm sách hay, giáo trình NEU, tra cứu đơn hàng, voucher hoặc chính sách mua sắm. Quý khách cần hỗ trợ gì ạ?</p>
                        </div>
                        <span class="biblio-chat-msg-time">Vừa xong</span>
                    </div>

                    <!-- Gợi ý nhanh (Quick Chips) -->
                    <div class="biblio-chat-chips-wrap" id="biblio-chat-quick-chips">
                        <div class="biblio-chat-chips-label">Gợi ý nhanh cho bạn:</div>
                        <div class="biblio-chat-chips">
                            <button type="button" class="biblio-chat-chip" data-query="Tìm sách theo chủ đề">🔍 Tìm sách theo chủ đề</button>
                            <button type="button" class="biblio-chat-chip" data-query="Sách giáo trình">📖 Sách giáo trình</button>
                            <button type="button" class="biblio-chat-chip" data-query="Theo dõi đơn hàng">📦 Theo dõi đơn hàng</button>
                            <button type="button" class="biblio-chat-chip" data-query="Chính sách đổi trả">🔄 Chính sách đổi trả</button>
                            <button type="button" class="biblio-chat-chip" data-query="Voucher đang có">🎟️ Voucher đang có</button>
                        </div>
                    </div>

                    <!-- Vùng tin nhắn đang trả lời (Typing indicator) -->
                    <div id="biblio-chat-typing" class="biblio-chat-msg biblio-chat-msg-bot biblio-chat-typing" style="display: none;">
                        <div class="biblio-chat-bubble">
                            <span class="biblio-typing-dot"></span>
                            <span class="biblio-typing-dot"></span>
                            <span class="biblio-typing-dot"></span>
                        </div>
                    </div>
                </div>

                <!-- Footer (Khung nhập tin nhắn) -->
                <div class="biblio-chat-footer">
                    <form id="biblio-chat-form" class="biblio-chat-form">
                        <input type="text" id="biblio-chat-input" class="biblio-chat-input" placeholder="Hỏi tên sách, tác giả, mã đơn hàng..." autocomplete="off" required>
                        <button type="submit" id="biblio-chat-btn-send" class="biblio-chat-btn-send" aria-label="Gửi tin nhắn" title="Gửi tin nhắn">
                            <svg class="biblio-chat-send-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" fill="#ffffff"></path>
                            </svg>
                        </button>
                    </form>
                    <div class="biblio-chat-footer-note">
                        Biblio AI Assistant · Hotline <a href="<?php echo esc_url($clean_hotline_tel); ?>" target="_blank"><?php echo esc_html($hotline); ?></a> · <a href="<?php echo esc_url($clean_zalo_url); ?>" target="_blank">Zalo CSKH</a>
                    </div>
                </div>
            </div>
        </div>

        <style>
        /* CSS CHO BIBLIO CHATBOT WIDGET */
        .biblio-chatbot-root {
            position: fixed;
            bottom: 24px;
            right: 20px;
            z-index: 999990;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #333333;
            transition: bottom 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.25s ease;
        }
        .biblio-chatbot-root.shifted-up {
            bottom: 84px;
        }
        .biblio-chat-toggle-btn {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ee4d2d 0%, #ff5722 100%);
            color: #ffffff;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(238, 77, 45, 0.42);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            outline: none;
            user-select: none;
        }
        .biblio-chat-toggle-btn:hover {
            transform: scale(1.06) translateY(-2px);
            box-shadow: 0 6px 22px rgba(238, 77, 45, 0.55);
        }
        .biblio-chat-badge-dot {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 12px;
            height: 12px;
            background: #00c853;
            border: 2px solid #ffffff;
            border-radius: 50%;
        }
        .biblio-chat-window {
            position: fixed;
            bottom: 92px;
            right: 20px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 570px;
            max-height: calc(100vh - 170px);
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18), 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: biblioChatSlideUp 0.26s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(0,0,0,0.08);
            z-index: 999991;
            transition: bottom 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .biblio-chat-window.shifted-up {
            bottom: 152px;
        }
        @keyframes biblioChatSlideUp {
            from { opacity: 0; transform: translateY(18px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .biblio-chat-header {
            background: linear-gradient(135deg, #ee4d2d 0%, #ff5722 100%);
            color: #ffffff;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .biblio-chat-header-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .biblio-chat-avatar-wrap {
            position: relative;
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.22);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .biblio-chat-status-dot {
            position: absolute;
            bottom: 0px;
            right: 0px;
            width: 10px;
            height: 10px;
            background: #00e676;
            border: 2px solid #ee4d2d;
            border-radius: 50%;
        }
        .biblio-chat-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
        }
        .biblio-chat-subtitle {
            font-size: 11px;
            opacity: 0.9;
            color: #ffffff;
        }
        .biblio-chat-header-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .biblio-chat-header-btn {
            background: rgba(255,255,255,0.18);
            color: #ffffff;
            border: none;
            padding: 5px 9px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }
        .biblio-chat-header-btn:hover {
            background: rgba(255,255,255,0.32);
        }
        .biblio-chat-header-btn.biblio-chat-close {
            padding: 5px 6px;
            border-radius: 50%;
        }
        .biblio-chat-body {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            gap: 14px;
            scroll-behavior: smooth;
        }
        .biblio-chat-msg {
            display: flex;
            flex-direction: column;
            max-width: 86%;
        }
        .biblio-chat-msg-bot {
            align-self: flex-start;
        }
        .biblio-chat-msg-user {
            align-self: flex-end;
        }
        .biblio-chat-bubble {
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13.5px;
            line-height: 1.45;
            word-break: break-word;
        }
        .biblio-chat-msg-bot .biblio-chat-bubble {
            background: #ffffff;
            color: #2c3e50;
            border-bottom-left-radius: 4px;
            border: 1px solid #ebebeb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .biblio-chat-msg-user .biblio-chat-bubble {
            background: linear-gradient(135deg, #ee4d2d 0%, #ff5722 100%);
            color: #ffffff;
            border-bottom-right-radius: 4px;
            box-shadow: 0 1px 4px rgba(238, 77, 45, 0.25);
        }
        .biblio-chat-bubble p {
            margin: 0 0 6px 0;
        }
        .biblio-chat-bubble p:last-child {
            margin-bottom: 0;
        }
        .biblio-chat-bubble code {
            background: rgba(238, 77, 45, 0.1);
            color: #ee4d2d;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        .biblio-chat-msg-time {
            font-size: 10.5px;
            color: #9e9e9e;
            margin-top: 3px;
            padding: 0 4px;
        }
        .biblio-chat-msg-user .biblio-chat-msg-time {
            text-align: right;
        }
        /* Quick Chips */
        .biblio-chat-chips-wrap {
            margin: 4px 0 6px 0;
        }
        .biblio-chat-chips-label {
            font-size: 11.5px;
            color: #888;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .biblio-chat-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .biblio-chat-chip {
            background: #ffffff;
            border: 1px solid #ffccba;
            color: #ee4d2d;
            border-radius: 16px;
            padding: 5px 11px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            line-height: 1.2;
            text-align: left;
            display: inline-flex;
            align-items: center;
        }
        .biblio-chat-chip i {
            margin-right: 6px;
            font-size: 12.5px;
        }
        .biblio-btn-transfer i {
            margin-right: 6px;
        }
        .biblio-chat-chip:hover {
            background: #fff5f2;
            border-color: #ee4d2d;
            transform: translateY(-1px);
        }
        /* Product Cards trong Chat */
        .biblio-chat-products-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 8px;
            width: 100%;
        }
        .biblio-chat-product-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #ebebeb;
            border-radius: 10px;
            padding: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: border-color 0.2s;
        }
        .biblio-chat-product-card:hover {
            border-color: #ee4d2d;
        }
        .biblio-chat-product-img {
            width: 52px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
            background: #f0f0f0;
        }
        .biblio-chat-product-info {
            flex: 1;
            min-width: 0;
        }
        .biblio-chat-product-title {
            font-size: 12.5px;
            font-weight: 600;
            margin: 0 0 2px 0;
            line-height: 1.3;
            color: #222;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .biblio-chat-product-author {
            font-size: 11px;
            color: #777;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .biblio-chat-product-price {
            font-size: 13px;
            font-weight: 700;
            color: #ee4d2d;
        }
        .biblio-chat-product-actions {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-shrink: 0;
        }
        .biblio-chat-btn-view, .biblio-chat-btn-add {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 6px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-weight: 500;
            white-space: nowrap;
        }
        .biblio-chat-btn-view {
            background: #f1f3f5;
            color: #495057;
        }
        .biblio-chat-btn-view:hover {
            background: #e9ecef;
            color: #212529;
        }
        .biblio-chat-btn-add {
            background: #ee4d2d;
            color: #ffffff;
        }
        .biblio-chat-btn-add:hover {
            background: #d73211;
        }
        .biblio-chat-btn-add.is-added {
            background: #28a745;
            color: #ffffff;
        }
        /* Voucher Cards trong Chat */
        .biblio-chat-voucher-item {
            background: #fff9f6;
            border: 1px dashed #ee4d2d;
            border-radius: 8px;
            padding: 8px 10px;
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .biblio-chat-voucher-code {
            font-weight: 700;
            color: #ee4d2d;
            font-size: 13px;
        }
        .biblio-chat-voucher-desc {
            font-size: 11.5px;
            color: #555;
            margin-top: 2px;
        }
        .biblio-chat-voucher-copy {
            background: #ee4d2d;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            padding: 4px 8px;
            cursor: pointer;
        }
        /* Order Card trong Chat */
        .biblio-chat-order-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            margin-top: 8px;
        }
        .biblio-chat-order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .biblio-chat-order-status {
            padding: 2px 7px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .biblio-status-processing { background: #e0f2fe; color: #0284c7; }
        .biblio-status-completed { background: #dcfce7; color: #16a34a; }
        .biblio-status-on-hold, .biblio-status-pending { background: #fef3c7; color: #d97706; }
        .biblio-status-cancelled { background: #fee2e2; color: #dc2626; }
        /* Human Transfer Banner */
        .biblio-chat-transfer-card {
            background: #fff8f6;
            border: 1px solid #ffd5cb;
            border-radius: 10px;
            padding: 12px;
            margin-top: 8px;
            text-align: center;
        }
        .biblio-chat-transfer-btn-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 10px;
        }
        .biblio-chat-hotline-btn, .biblio-chat-zalo-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .biblio-chat-hotline-btn { background: #ee4d2d; color: #fff; }
        .biblio-chat-zalo-btn { background: #0068ff; color: #fff; }
        /* Typing Indicator */
        .biblio-chat-typing .biblio-chat-bubble {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 10px 14px;
        }
        .biblio-typing-dot {
            width: 7px;
            height: 7px;
            background: #9e9e9e;
            border-radius: 50%;
            display: inline-block;
            animation: biblioTypingBounce 1.4s infinite ease-in-out both;
        }
        .biblio-typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .biblio-typing-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes biblioTypingBounce {
            0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }
        /* Footer Form */
        .biblio-chat-footer {
            background: #ffffff;
            border-top: 1px solid #f0f0f0;
            padding: 10px 14px 10px 14px;
        }
        .biblio-chat-form {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        .biblio-chat-input {
            flex: 1;
            border: 1.5px solid #e5e7eb;
            border-radius: 22px;
            padding: 9px 16px;
            font-size: 13.5px;
            line-height: 1.4;
            outline: none;
            transition: all 0.2s ease;
            background: #f8fafc;
            color: #1f2937;
        }
        .biblio-chat-input:focus {
            border-color: #ee4d2d;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(238, 77, 45, 0.12);
        }
        .biblio-chat-input::placeholder {
            color: #9ca3af;
            font-size: 13px;
        }
        .biblio-chat-btn-send {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff5722 0%, #ee4d2d 100%);
            color: #ffffff;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(238, 77, 45, 0.35);
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            padding: 0;
            outline: none;
            position: relative;
        }
        .biblio-chat-btn-send .biblio-chat-send-icon {
            display: block;
            width: 17px;
            height: 17px;
            fill: #ffffff;
            margin-left: 2px;
            transition: transform 0.2s ease;
        }
        .biblio-chat-btn-send:hover {
            background: linear-gradient(135deg, #f44336 0%, #d73211 100%);
            transform: translateY(-1px) scale(1.06);
            box-shadow: 0 5px 14px rgba(238, 77, 45, 0.48);
        }
        .biblio-chat-btn-send:hover .biblio-chat-send-icon {
            transform: translateX(1px);
        }
        .biblio-chat-btn-send:active {
            transform: translateY(0) scale(0.95);
            box-shadow: 0 2px 6px rgba(238, 77, 45, 0.3);
        }
        .biblio-chat-btn-send:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .biblio-chat-footer-note {
            font-size: 10.5px;
            color: #999;
            text-align: center;
            margin-top: 6px;
        }
        .biblio-chat-footer-note a {
            color: #ee4d2d;
            text-decoration: none;
            font-weight: 500;
        }
        @media (max-width: 480px) {
            .biblio-chat-window {
                right: 12px;
                left: 12px;
                bottom: 80px;
                width: calc(100vw - 24px);
                height: calc(100vh - 96px);
                border-radius: 14px;
            }
            .biblio-chat-window.shifted-up {
                bottom: 138px;
                height: calc(100vh - 154px);
            }
            .biblio-chatbot-root {
                bottom: 18px;
                right: 16px;
            }
            .biblio-chatbot-root.shifted-up {
                bottom: 76px;
                right: 16px;
            }
        }
        </style>

        <script>
        (function() {
            var toggleBtn = document.getElementById('biblio-chat-toggle-btn');
            var chatWindow = document.getElementById('biblio-chat-window');
            var closeBtn = document.getElementById('biblio-chat-btn-close');
            var transferBtn = document.getElementById('biblio-chat-btn-transfer');
            var chatForm = document.getElementById('biblio-chat-form');
            var chatInput = document.getElementById('biblio-chat-input');
            var sendBtn = document.getElementById('biblio-chat-btn-send');
            var chatBody = document.getElementById('biblio-chat-body');
            var typingIndicator = document.getElementById('biblio-chat-typing');
            var iconOpen = toggleBtn.querySelector('.biblio-chat-btn-icon-open');
            var iconClose = toggleBtn.querySelector('.biblio-chat-btn-icon-close');
            var badgeDot = toggleBtn.querySelector('.biblio-chat-badge-dot');

            var ajaxUrl = <?php echo json_encode($ajax_url); ?>;
            var nonce = <?php echo json_encode($nonce); ?>;
            var sessionKey = localStorage.getItem('biblio_chat_session') || ('s_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36));
            localStorage.setItem('biblio_chat_session', sessionKey);

            var isOpen = false;
            var isSending = false;

            function toggleChat(show) {
                isOpen = typeof show === 'boolean' ? show : !isOpen;
                if (isOpen) {
                    chatWindow.style.display = 'flex';
                    iconOpen.style.display = 'none';
                    iconClose.style.display = 'block';
                    if (badgeDot) badgeDot.style.display = 'none';
                    setTimeout(function() {
                        chatInput.focus();
                    }, 80);
                    scrollToBottom();
                } else {
                    chatWindow.style.display = 'none';
                    iconOpen.style.display = 'block';
                    iconClose.style.display = 'none';
                }
            }

            // Click vào nút tròn mở/đóng chat
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleChat();
            });

            // Click nút X thu nhỏ chat
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleChat(false);
            });

            // Đóng cửa sổ chat khi click ra ngoài màn hình (Click Outside)
            document.addEventListener('click', function(e) {
                if (!isOpen) return;
                // Nếu click ngoài chatWindow và không thuộc nút toggleBtn
                if (!chatWindow.contains(e.target) && !toggleBtn.contains(e.target)) {
                    toggleChat(false);
                }
            });

            // Đóng cửa sổ chat khi nhấn phím Escape (Esc)
            document.addEventListener('keydown', function(e) {
                if ((e.key === 'Escape' || e.keyCode === 27) && isOpen) {
                    toggleChat(false);
                }
            });

            transferBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sendUserMessage('Gặp nhân viên CSKH');
            });

            // Tự động nâng Chatbot lên khi nút cuộn lên đầu (#spBackToTop) xuất hiện
            var rootEl = document.querySelector('.biblio-chatbot-root');
            function syncChatbotWithBackToTop() {
                var backToTop = document.getElementById('spBackToTop');
                var y = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                var isTopBtnVisible = (y > 220) || (backToTop && (backToTop.classList.contains('visible') || backToTop.offsetParent !== null && window.getComputedStyle(backToTop).visibility === 'visible'));
                if (rootEl) {
                    if (isTopBtnVisible) {
                        rootEl.classList.add('shifted-up');
                    } else {
                        rootEl.classList.remove('shifted-up');
                    }
                }
                if (chatWindow) {
                    if (isTopBtnVisible) {
                        chatWindow.classList.add('shifted-up');
                    } else {
                        chatWindow.classList.remove('shifted-up');
                    }
                }
            }
            window.addEventListener('scroll', syncChatbotWithBackToTop, { passive: true });
            document.addEventListener('DOMContentLoaded', syncChatbotWithBackToTop);
            syncChatbotWithBackToTop();

            // Lắng nghe click vào nút cuộn lên đầu để hạ Chatbot xuống đồng thời
            var spBackToTopBtn = document.getElementById('spBackToTop');
            if (spBackToTopBtn) {
                spBackToTopBtn.addEventListener('click', function() {
                    setTimeout(syncChatbotWithBackToTop, 150);
                    setTimeout(syncChatbotWithBackToTop, 400);
                });
            }

            function scrollToBottom() {
                setTimeout(function() {
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 60);
            }

            function appendMessage(sender, text, htmlPayload) {
                var msgDiv = document.createElement('div');
                msgDiv.className = 'biblio-chat-msg biblio-chat-msg-' + sender;

                var bubble = document.createElement('div');
                bubble.className = 'biblio-chat-bubble';

                if (text) {
                    var p = document.createElement('p');
                    p.innerHTML = text;
                    bubble.appendChild(p);
                }

                if (htmlPayload) {
                    var payloadDiv = document.createElement('div');
                    payloadDiv.innerHTML = htmlPayload;
                    bubble.appendChild(payloadDiv);
                }

                var timeSpan = document.createElement('span');
                timeSpan.className = 'biblio-chat-msg-time';
                var now = new Date();
                timeSpan.textContent = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

                msgDiv.appendChild(bubble);
                msgDiv.appendChild(timeSpan);

                // Chèn trước typing indicator
                chatBody.insertBefore(msgDiv, typingIndicator);
                scrollToBottom();
            }

            function showTyping(show) {
                typingIndicator.style.display = show ? 'flex' : 'none';
                if (show) scrollToBottom();
            }

            function sendUserMessage(message) {
                message = (message || '').trim();
                if (!message || isSending) return;

                isSending = true;
                if (sendBtn) {
                    sendBtn.disabled = true;
                }

                appendMessage('user', escapeHtml(message));
                chatInput.value = '';
                showTyping(true);

                var formData = new FormData();
                formData.append('action', 'nbook_chatbot_message');
                formData.append('nonce', nonce);
                formData.append('session_key', sessionKey);
                formData.append('message', message);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    isSending = false;
                    if (sendBtn) sendBtn.disabled = false;
                    showTyping(false);
                    if (data && data.success && data.data) {
                        appendMessage('bot', data.data.reply, data.data.payload_html);
                    } else {
                        var err = (data && data.data && data.data.message) ? data.data.message : 'Dạ em đang bị nghẽn mạng một chút, quý khách vui lòng nhắn lại giúp em nhé!';
                        appendMessage('bot', err);
                    }
                    setTimeout(function() {
                        chatInput.focus();
                    }, 50);
                })
                .catch(function(err) {
                    isSending = false;
                    if (sendBtn) sendBtn.disabled = false;
                    showTyping(false);
                    appendMessage('bot', 'Dạ hệ thống đang phản hồi chậm, quý khách có thể liên hệ nhanh qua hotline <strong><?php echo esc_js($hotline); ?></strong> ạ!');
                    setTimeout(function() {
                        chatInput.focus();
                    }, 50);
                });
            }

            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var msg = (chatInput.value || '').trim();
                if (!msg || isSending) return;
                sendUserMessage(msg);
            });

            // Gợi ý nhanh Quick Chips (Hỗ trợ click vào bất kỳ chip nào trong cả đoạn chat)
            document.addEventListener('click', function(e) {
                var chip = e.target.closest('.biblio-chat-chip');
                if (chip && chatWindow.contains(chip)) {
                    e.preventDefault();
                    var q = chip.getAttribute('data-query');
                    if (q && !isSending) {
                        sendUserMessage(q);
                    }
                }
            });

            // Click vào nút sao chép mã voucher trong khung chat
            document.addEventListener('click', function(e) {
                var copyBtn = e.target.closest('.biblio-chat-voucher-copy');
                if (copyBtn && chatWindow.contains(copyBtn)) {
                    var code = copyBtn.getAttribute('data-code');
                    if (code && navigator.clipboard) {
                        navigator.clipboard.writeText(code).then(function() {
                            var old = copyBtn.textContent;
                            copyBtn.textContent = 'Đã chép ✓';
                            setTimeout(function() { copyBtn.textContent = old; }, 2000);
                        });
                    }
                }

                // Click thêm vào giỏ ngay trong chat
                var addCartBtn = e.target.closest('.biblio-chat-btn-add');
                if (addCartBtn && chatWindow.contains(addCartBtn)) {
                    e.preventDefault();
                    var productId = addCartBtn.getAttribute('data-product-id');
                    if (!productId) return;

                    var oldText = addCartBtn.textContent;
                    addCartBtn.textContent = 'Đang thêm...';
                    addCartBtn.disabled = true;

                    var cartData = new FormData();
                    cartData.append('action', 'woocommerce_add_to_cart');
                    cartData.append('product_id', productId);
                    cartData.append('quantity', 1);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: cartData
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(res) {
                        addCartBtn.textContent = 'Đã thêm ✓';
                        addCartBtn.classList.add('is-added');
                        // Trigger standard WooCommerce fragment refresh if available
                        if (window.jQuery) {
                            window.jQuery(document.body).trigger('wc_fragment_refresh');
                            window.jQuery(document.body).trigger('added_to_cart', [res ? res.fragments : null, res ? res.cart_hash : null, addCartBtn]);
                        }
                        // Cập nhật số hiển thị giỏ hàng trên header nếu có
                        var countEls = document.querySelectorAll('.cart-count, .header-cart-count, .nb-cart-count, .mini-cart-count, .cart-contents-count');
                        countEls.forEach(function(el) {
                            var current = parseInt(el.textContent.replace(/[^0-9]/g, '') || '0', 10);
                            el.textContent = current + 1;
                            el.style.display = 'inline-block';
                        });
                    })
                    .catch(function() {
                        addCartBtn.textContent = 'Đã thêm ✓';
                        addCartBtn.classList.add('is-added');
                    });
                }
            });

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }
        })();
        </script>
        <?php
    }

    /**
     * AJAX Xử Lý Tin Nhắn Chatbot
     */
    public function ajax_message() {
        check_ajax_referer('nbook_chatbot_nonce', 'nonce');

        $session_key = sanitize_text_field(wp_unslash($_POST['session_key'] ?? ''));
        $raw_message = sanitize_text_field(wp_unslash($_POST['message'] ?? ''));

        if (empty($session_key) || empty($raw_message)) {
            wp_send_json_error(['message' => 'Nội dung tin nhắn không hợp lệ.']);
        }

        // 1. Rate Limiting: Giới hạn 20 tin / phút cho mỗi Session & IP
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $rl_key = 'nbook_chat_rl_' . md5($session_key . '_' . $ip);
        $count = (int) get_transient($rl_key);
        if ($count >= 20) {
            wp_send_json_error(['message' => 'Quý khách đang gửi tin nhắn quá nhanh. Vui lòng thử lại sau giây lát nhé!']);
        }
        set_transient($rl_key, $count + 1, 60);

        // 2. Lấy hoặc tạo Session trong Database
        global $wpdb;
        $table_sessions = $wpdb->prefix . 'nbook_chatbot_sessions';
        $table_messages = $wpdb->prefix . 'nbook_chatbot_messages';
        $now = current_time('mysql');
        $user_id = get_current_user_id();

        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_sessions} WHERE session_key = %s", $session_key));
        if (!$session) {
            $wpdb->insert($table_sessions, [
                'session_key' => $session_key,
                'user_id'     => $user_id,
                'transferred' => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], ['%s', '%d', '%d', '%s', '%s']);
            $session_id = $wpdb->insert_id;
        } else {
            $session_id = $session->id;
            if ($user_id && !$session->user_id) {
                $wpdb->update($table_sessions, ['user_id' => $user_id, 'updated_at' => $now], ['id' => $session_id], ['%d', '%s'], ['%d']);
            } else {
                $wpdb->update($table_sessions, ['updated_at' => $now], ['id' => $session_id], ['%s'], ['%d']);
            }
        }

        // Lưu tin nhắn của khách vào bảng messages
        $wpdb->insert($table_messages, [
            'session_id' => $session_id,
            'sender'     => 'user',
            'message'    => $raw_message,
            'payload'    => null,
            'created_at' => $now,
        ], ['%d', '%s', '%s', '%s', '%s']);

        // 3. Xử lý Logic Phản Hồi (Rule-based engine & CSDL Website)
        $reply_data = $this->process_response($raw_message, $session_id, $user_id);

        // Lưu tin nhắn phản hồi của Bot
        $wpdb->insert($table_messages, [
            'session_id' => $session_id,
            'sender'     => 'bot',
            'message'    => wp_strip_all_tags($reply_data['reply']),
            'payload'    => $reply_data['payload_html'] ?? null,
            'created_at' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s', '%s']);

        wp_send_json_success($reply_data);
    }

    /**
     * AJAX Chuyển Cho Nhân Viên CSKH
     */
    public function ajax_transfer() {
        check_ajax_referer('nbook_chatbot_nonce', 'nonce');
        $session_key = sanitize_text_field(wp_unslash($_POST['session_key'] ?? ''));
        global $wpdb;
        $table_sessions = $wpdb->prefix . 'nbook_chatbot_sessions';
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_sessions} WHERE session_key = %s", $session_key));
        if ($session) {
            $wpdb->update($table_sessions, ['transferred' => 1, 'updated_at' => current_time('mysql')], ['id' => $session->id], ['%d', '%s'], ['%d']);
        }
        $hotline = get_option('nbook_hotline', '0933 109 009');
        $zalo = get_option('nbook_zalo', '0933 109 009');
        wp_send_json_success([
            'hotline' => $hotline,
            'zalo'    => $zalo,
            'message' => 'Đã chuyển thông tin tới nhân viên chăm sóc khách hàng.',
        ]);
    }

    /**
     * Engine xử lý thông minh theo Luật & CSDL
     */
    private function process_response($message, $session_id, $user_id) {
        $msg_lower = mb_strtolower(trim($message), 'UTF-8');
        $msg_normalized = $this->remove_accents($msg_lower);

        // -------------------------------------------------------------
        // 0. Ý ĐỊNH: CHÀO HỎI (Greeting)
        // -------------------------------------------------------------
        if (preg_match('/^(?:xin\s+chao|chao|hello|hi|alo|chao\s+ban|chao\s+shop|chao\s+em|hey|yo)(?:\s+bot|\s+biblio|\s+shop|\s+nhe|\s+nha|\s+a)?$/iu', $msg_normalized)) {
            $user_obj = $user_id ? get_userdata($user_id) : null;
            $user_display = ($user_obj && !empty($user_obj->display_name)) ? $user_obj->display_name : 'quý khách';
            return [
                'reply'        => 'Dạ em chào ' . esc_html($user_display) . '! Em là <strong>Biblio Assistant 📚</strong>.<br>Em luôn sẵn sàng hỗ trợ ' . esc_html($user_display) . ' tìm sách hay, giáo trình NEU chuẩn, kiểm tra đơn hàng hoặc nhận mã voucher freeship hôm nay.<br>Quý khách cần em hỗ trợ gì ạ?',
                'payload_html' => '
                <div style="margin-top: 8px;">
                    <div class="biblio-chat-chips">
                        <button type="button" class="biblio-chat-chip" data-query="Tìm sách theo chủ đề">🔍 Tìm sách theo chủ đề</button>
                        <button type="button" class="biblio-chat-chip" data-query="Sách giáo trình">📖 Sách giáo trình NEU</button>
                        <button type="button" class="biblio-chat-chip" data-query="Theo dõi đơn hàng">📦 Tra cứu đơn hàng</button>
                        <button type="button" class="biblio-chat-chip" data-query="Voucher đang có">🎟️ Voucher giảm giá</button>
                    </div>
                </div>',
            ];
        }

        // -------------------------------------------------------------
        // 1. Ý ĐỊNH: CẢM ƠN (Thank you)
        // -------------------------------------------------------------
        if (preg_match('/^(?:cam\s+on|thank|thanks|cam\s+on\s+shop|cam\s+on\s+em|cam\s+on\s+bot|ok\s+cam\s+on)(?:\s+nhe|\s+nha|\s+a|\s+nhieu)?$/iu', $msg_normalized)) {
            return [
                'reply'        => 'Dạ không có gì ạ! Rất vui được đồng hành cùng quý khách. Chúc quý khách có những trải nghiệm đọc sách thật tuyệt vời tại Biblio nhé! 📖✨ Nếu cần thêm hỗ trợ, quý khách cứ nhắn cho em bất kỳ lúc nào ạ.',
                'payload_html' => '',
            ];
        }

        // -------------------------------------------------------------
        // A. Ý ĐỊNH: CHUYỂN CHO NHÂN VIÊN / GẶP CSKH / HOTLINE / ZALO
        // -------------------------------------------------------------
        if ($this->match_any($msg_normalized, ['nhan vien', 'cskh', 'gap nguoi', 'tu van vien', 'hotline', 'zalo', 'tong dai', 'so dien thoai', 'cham soc khach hang', 'gap nhan vien'])) {
            return $this->handle_transfer_support($session_id);
        }

        // -------------------------------------------------------------
        // B. Ý ĐỊNH: TRA CỨU ĐƠN HÀNG (Order Tracking)
        // -------------------------------------------------------------
        $is_order_intent = $this->match_any($msg_normalized, ['don hang', 'tra don', 'kiem tra don', 'tinh trang don', 'order', 'ma don', 'don cua toi'])
            || preg_match('/^#\s*([0-9]{1,8})$/', trim($message))
            || preg_match('/\b(?:don|order|ma don)\s*#?([0-9]{1,8})\b/i', $message)
            || (preg_match('/^[0-9\s#]{2,16}$/', trim($message)) && !preg_match('/[kK]/', $message));

        if ($is_order_intent) {
            return $this->handle_order_tracking($message, $user_id);
        }

        // -------------------------------------------------------------
        // C. Ý ĐỊNH: VOUCHER / MÃ GIẢM GIÁ
        // -------------------------------------------------------------
        if ($this->match_any($msg_normalized, ['voucher', 'ma giam', 'khuyen mai', 'ma giam gia', 'giam gia', 'coupon', 'ma uu dai', 'co ma giam khong'])) {
            return $this->handle_vouchers_query();
        }

        // -------------------------------------------------------------
        // D. Ý ĐỊNH: TÌM SÁCH THEO CHỦ ĐỀ HOẶC TỪ KHÓA SÁCH
        // -------------------------------------------------------------
        if ($msg_normalized === 'tim sach theo chu de' || $this->match_any($msg_normalized, ['chu de', 'cac the loai', 'danh muc sach'])) {
            return $this->handle_topics_menu();
        }

        // -------------------------------------------------------------
        // E. SO KHỚP CÂU HỎI THƯỜNG GẶP FAQ (Database table wp_nbook_chatbot_faq)
        // -------------------------------------------------------------
        $faq_result = $this->handle_faq_match($message, $msg_normalized);
        if ($faq_result !== false) {
            return $faq_result;
        }

        // -------------------------------------------------------------
        // F. TÌM KIẾM SÁCH TRONG WOOCOMMERCE (Tên sách, Tác giả, Thể loại, Mức giá)
        // -------------------------------------------------------------
        $book_search_result = $this->handle_book_search($message, $msg_normalized);
        if ($book_search_result !== false) {
            return $book_search_result;
        }

        // -------------------------------------------------------------
        // G. TÙY CHỌN GỌI LLM API (nếu được bật ở Settings → Biblio)
        // -------------------------------------------------------------
        if ((bool) get_option('biblio_ai_enabled', 0)) {
            $ai_reply = $this->call_llm_api($message);
            if ($ai_reply !== false) {
                return [
                    'reply'        => $ai_reply,
                    'payload_html' => '',
                ];
            }
        }

        // -------------------------------------------------------------
        // H. PHẢN HỒI MẶC ĐỊNH THÔNG MINH (Default Fallback)
        // -------------------------------------------------------------
        return $this->handle_default_fallback();
    }

    /**
     * Xử lý chuyển cho nhân viên
     */
    private function handle_transfer_support($session_id) {
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'nbook_chatbot_sessions', ['transferred' => 1], ['id' => $session_id], ['%d'], ['%d']);

        $hotline = get_option('nbook_hotline', '0933 109 009');
        $zalo = get_option('nbook_zalo', '0933 109 009');
        $clean_hotline = preg_replace('/[^0-9]/', '', $hotline);
        $clean_zalo = preg_replace('/[^0-9]/', '', $zalo);

        $html = '
        <div class="biblio-chat-transfer-card">
            <p style="margin: 0 0 6px 0; font-size: 13px;">Dạ vâng! Yêu cầu của quý khách đã được chuyển tới chuyên viên tư vấn Biblio. Quý khách có thể kết nối ngay qua:</p>
            <div class="biblio-chat-transfer-btn-row">
                <a href="tel:' . esc_attr($clean_hotline) . '" class="biblio-chat-hotline-btn" target="_blank">📞 Gọi Hotline ' . esc_html($hotline) . '</a>
                <a href="https://zalo.me/' . esc_attr($clean_zalo) . '" class="biblio-chat-zalo-btn" target="_blank">💬 Chat Zalo CSKH</a>
            </div>
            <p style="margin: 8px 0 0 0; font-size: 11px; color: #888;">Đội ngũ CSKH trực từ 08:00 đến 21:30 hàng ngày.</p>
        </div>';

        return [
            'reply'        => 'Dạ, em đã chuyển thông tin tới bộ phận chăm sóc khách hàng ạ! Quý khách có thể bấm liên hệ ngay bên dưới:',
            'payload_html' => $html,
        ];
    }

    /**
     * Xử lý tra cứu đơn hàng
     */
    private function handle_order_tracking($message, $user_id) {
        if (!function_exists('wc_get_order')) {
            return [
                'reply'        => 'Dạ chức năng tra cứu đơn hàng đang bảo trì một chút, quý khách vui lòng liên hệ hotline để kiểm tra nhé!',
                'payload_html' => '',
            ];
        }

        // Bắt mã đơn hàng cụ thể từ chuỗi (ví dụ: #456 hoặc 456)
        preg_match('/#?([0-9]{1,8})/i', $message, $matches);
        $order_id = !empty($matches[1]) ? absint($matches[1]) : 0;

        if ($user_id > 0) {
            // Trường hợp khách đã đăng nhập
            if ($order_id > 0) {
                $order = wc_get_order($order_id);
                if (!$order || $order->get_user_id() !== $user_id) {
                    return [
                        'reply'        => 'Dạ em không tìm thấy đơn hàng <strong>#' . esc_html($order_id) . '</strong> trong tài khoản của quý khách. Quý khách vui lòng kiểm tra lại mã đơn nhé!',
                        'payload_html' => '',
                    ];
                }
                return $this->format_order_response([$order], 'Thông tin chi tiết đơn hàng #' . $order->get_id() . ':');
            } else {
                // Liệt kê 2-3 đơn hàng gần nhất của user
                $orders = wc_get_orders([
                    'customer_id' => $user_id,
                    'limit'       => 3,
                    'orderby'     => 'date',
                    'order'       => 'DESC',
                ]);

                if (empty($orders)) {
                    return [
                        'reply'        => 'Quý khách hiện chưa có đơn hàng nào tại Biblio. Quý khách có muốn em gợi ý một số cuốn sách hay bán chạy không ạ?',
                        'payload_html' => '',
                    ];
                }

                return $this->format_order_response($orders, 'Dưới đây là các đơn hàng gần nhất của quý khách:');
            }
        } else {
            // Trường hợp khách chưa đăng nhập (Khách vãng lai)
            // Kiểm tra xem khách có cung cấp kèm số điện thoại để bảo mật không (ví dụ: "105 0912345678")
            preg_match('/(0[3|5|7|8|9][0-9]{8})/i', $message, $phone_matches);
            $phone = !empty($phone_matches[1]) ? $phone_matches[1] : '';

            if ($order_id > 0 && !empty($phone)) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order_phone = preg_replace('/[^0-9]/', '', $order->get_billing_phone());
                    if ($order_phone === $phone) {
                        return $this->format_order_response([$order], 'Thông tin đơn hàng #' . $order->get_id() . ':');
                    }
                }
                return [
                    'reply'        => 'Dạ thông tin mã đơn hàng và số điện thoại không trùng khớp. Quý khách vui lòng kiểm tra lại số điện thoại đặt hàng nhé!',
                    'payload_html' => '',
                ];
            }

            $login_url = esc_url(wp_login_url(home_url('/tai-khoan/')));
            return [
                'reply'        => 'Để tra cứu trạng thái đơn hàng bảo mật, quý khách vui lòng:<br>' .
                                  '1. Nhắn theo cú pháp: <strong>[Mã đơn hàng] [Số điện thoại đặt]</strong> (ví dụ: <code>105 0988123456</code>).<br>' .
                                  '2. Hoặc <a href="' . $login_url . '" target="_blank"><strong>Đăng nhập tài khoản</strong></a> để xem toàn bộ danh sách đơn hàng của mình.',
                'payload_html' => '',
            ];
        }
    }

    /**
     * Định dạng hiển thị danh sách đơn hàng
     */
    private function format_order_response($orders, $title) {
        $html = '<div style="margin-top: 8px;">';
        $status_labels = [
            'pending'    => ['Chờ thanh toán', 'biblio-status-pending'],
            'processing' => ['Đang xử lý / Đóng gói', 'biblio-status-processing'],
            'on-hold'    => ['Tạm giữ', 'biblio-status-on-hold'],
            'completed'  => ['Giao thành công', 'biblio-status-completed'],
            'cancelled'  => ['Đã hủy', 'biblio-status-cancelled'],
            'refunded'   => ['Đã hoàn tiền', 'biblio-status-cancelled'],
            'shipping'   => ['Đang giao hàng', 'biblio-status-processing'],
        ];

        foreach ($orders as $order) {
            $st = $order->get_status();
            $label_info = $status_labels[$st] ?? [wc_get_order_status_name($st), 'biblio-status-processing'];
            $items_count = $order->get_item_count();

            $html .= '
            <div class="biblio-chat-order-card">
                <div class="biblio-chat-order-header">
                    <strong>Đơn hàng #' . esc_html($order->get_order_number()) . '</strong>
                    <span class="biblio-chat-order-status ' . esc_attr($label_info[1]) . '">' . esc_html($label_info[0]) . '</span>
                </div>
                <div style="font-size: 12px; color: #555; line-height: 1.5;">
                    <div>• Ngày đặt: ' . esc_html($order->get_date_created() ? $order->get_date_created()->date('d/m/Y H:i') : '') . '</div>
                    <div>• Số lượng: ' . esc_html($items_count) . ' cuốn sách</div>
                    <div>• Tổng tiền: <strong style="color: #ee4d2d;">' . wp_strip_all_tags(wc_price($order->get_total())) . '</strong></div>
                </div>
                <div style="margin-top: 8px; text-align: right;">
                    <a href="' . esc_url($order->get_view_order_url()) . '" class="biblio-chat-btn-view" target="_blank" style="display: inline-block;">Xem chi tiết đơn →</a>
                </div>
            </div>';
        }
        $html .= '</div>';

        return [
            'reply'        => $title,
            'payload_html' => $html,
        ];
    }

    /**
     * Xử lý truy vấn danh sách voucher khả dụng
     */
    private function handle_vouchers_query() {
        $coupons = get_posts([
            'post_type'      => 'shop_coupon',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if (empty($coupons)) {
            return [
                'reply'        => 'Hiện tại Biblio đang chuẩn bị các mã ưu đãi mới. Quý khách nhớ đăng ký tài khoản để nhận ngay <strong>100 Biblio Xu</strong> nhé!',
                'payload_html' => '',
            ];
        }

        $html = '<div style="margin-top: 6px;">';
        foreach ($coupons as $post) {
            $coupon = new WC_Coupon($post->ID);
            $code = strtoupper($coupon->get_code());
            $amount = $coupon->get_amount();
            $type = $coupon->get_discount_type();
            $min_spend = $coupon->get_minimum_amount();

            $discount_text = ($type === 'percent') ? 'Giảm ' . $amount . '%' : 'Giảm ' . wp_strip_all_tags(wc_price($amount));
            $condition = $min_spend ? 'Đơn từ ' . wp_strip_all_tags(wc_price($min_spend)) : 'Tất cả đơn hàng';

            $html .= '
            <div class="biblio-chat-voucher-item">
                <div>
                    <div class="biblio-chat-voucher-code">' . esc_html($code) . '</div>
                    <div class="biblio-chat-voucher-desc">' . esc_html($discount_text) . ' (' . esc_html($condition) . ')</div>
                </div>
                <button type="button" class="biblio-chat-voucher-copy" data-code="' . esc_attr($code) . '">Sao chép</button>
            </div>';
        }
        $html .= '</div>';

        return [
            'reply'        => 'Dưới đây là các <strong>Mã Giảm Giá Biblio</strong> đang áp dụng hôm nay. Quý khách bấm "Sao chép" để áp dụng ở trang Giỏ hàng nhé:',
            'payload_html' => $html,
        ];
    }

    /**
     * Xử lý hiển thị menu các chủ đề sách
     */
    private function handle_topics_menu() {
        $chips_html = '
        <div style="margin-top: 8px;">
            <p style="margin: 0 0 6px 0; font-size: 13px;">Dưới đây là các thể loại sách nổi bật tại Biblio, quý khách bấm vào chủ đề để xem sách nhé:</p>
            <div class="biblio-chat-chips" style="margin-top: 6px;">
                <button type="button" class="biblio-chat-chip" data-query="Sách giáo trình">📖 Sách Giáo Trình NEU</button>
                <button type="button" class="biblio-chat-chip" data-query="Sách kinh tế">📈 Sách Kinh Tế & Khởi Nghiệp</button>
                <button type="button" class="biblio-chat-chip" data-query="Sách văn học">📚 Sách Văn Học Kinh Điển</button>
                <button type="button" class="biblio-chat-chip" data-query="Sách ngoại ngữ">🎓 Sách Ngoại Ngữ & IELTS</button>
                <button type="button" class="biblio-chat-chip" data-query="Sách kỹ năng">💡 Sách Kỹ Năng Sống</button>
                <button type="button" class="biblio-chat-chip" data-query="Sách thiếu nhi">🧸 Sách Thiếu Nhi</button>
            </div>
        </div>';

        return [
            'reply'        => 'Biblio có rất nhiều chủ đề sách chọn lọc dành cho sinh viên và bạn đọc trẻ:',
            'payload_html' => $chips_html,
        ];
    }

    /**
     * Tìm kiếm sách thông minh trong CSDL WooCommerce
     */
    private function handle_book_search($message, $msg_normalized) {
        if (!function_exists('wc_get_products')) {
            return false;
        }

        $is_search_intent = false;
        $category_slug = '';
        $max_price = 0;
        $min_price = 0;

        // 1. Nhận diện lọc theo giá: "dưới 100k", "< 150000", "từ 50k đến 100k", "dưới 100.000"
        if (preg_match('/(?:duoi|<)\s*([0-9\.]+)\s*(k|nghin|ngan|000)?/iu', $msg_normalized, $price_m)) {
            $num_str = str_replace('.', '', $price_m[1]);
            $num = (int) $num_str;
            $max_price = ($num < 1000) ? $num * 1000 : $num;
            $is_search_intent = true;
        } elseif (preg_match('/tu\s*([0-9\.]+)\s*k?\s*den\s*([0-9\.]+)\s*k?/iu', $msg_normalized, $range_m)) {
            $num1 = (int) str_replace('.', '', $range_m[1]);
            $num2 = (int) str_replace('.', '', $range_m[2]);
            $min_price = ($num1 < 1000) ? $num1 * 1000 : $num1;
            $max_price = ($num2 < 1000) ? $num2 * 1000 : $num2;
            $is_search_intent = true;
        }

        // 2. Nhận diện thể loại
        if ($this->match_any($msg_normalized, ['giao trinh', 'sach giao trinh', 'giao trinh neu', 'hoc phan'])) {
            $category_slug = 'sach-giao-duc';
            $is_search_intent = true;
        } elseif ($this->match_any($msg_normalized, ['kinh te', 'sach kinh te', 'khoi nghiep', 'kinh doanh'])) {
            $category_slug = 'sach-kinh-te';
            $is_search_intent = true;
        } elseif ($this->match_any($msg_normalized, ['van hoc', 'sach van hoc', 'tieu thuyet', 'kinh dien'])) {
            $category_slug = 'sach-van-hoc';
            $is_search_intent = true;
        } elseif ($this->match_any($msg_normalized, ['ngoai ngu', 'sach ngoai ngu', 'ielts', 'toeic', 'tieng anh'])) {
            $category_slug = 'sach-ngoai-ngu';
            $is_search_intent = true;
        } elseif ($this->match_any($msg_normalized, ['ky nang', 'sach ky nang', 'tu duy', 'phat trien ban than'])) {
            $category_slug = 'sach-ky-nang';
            $is_search_intent = true;
        } elseif ($this->match_any($msg_normalized, ['thieu nhi', 'sach thieu nhi', 'truyen tranh'])) {
            $category_slug = 'sach-thieu-nhi';
            $is_search_intent = true;
        }

        // 3. Làm sạch từ khóa tìm sách
        $norm_term = $msg_normalized;
        $norm_term = preg_replace('/^(?:tim\s+sach|sach|cuon|muon\s+mua|mua)\s+/iu', '', $norm_term);
        $norm_term = preg_replace('/(?:duoi|<)\s*[0-9\.]+\s*(?:k|nghin|ngan|000|dong|vnd|d)?/iu', '', $norm_term);
        $norm_term = preg_replace('/tu\s*[0-9\.]+\s*k?\s*den\s*[0-9\.]+\s*k?/iu', '', $norm_term);
        if ($category_slug) {
            $norm_term = preg_replace('/(?:giao trinh|hoc phan|kinh te|khoi nghiep|kinh doanh|van hoc|tieu thuyet|kinh dien|ngoai ngu|ielts|toeic|tieng anh|ky nang|tu duy|thieu nhi|truyen tranh)/iu', '', $norm_term);
        }
        $norm_term = trim($norm_term);

        // Lấy từ khóa cụ thể nếu có
        $search_kw = '';
        if (!empty($norm_term) && mb_strlen($norm_term, 'UTF-8') >= 2) {
            $search_kw = preg_replace('/^(?:tìm\s+sách|sách|cuốn|muốn\s+mua|mua)\s+/iu', '', trim($message));
            $search_kw = preg_replace('/(?:dưới|<)\s*[0-9\.]+\s*(?:k|nghìn|ngàn|000|đồng|đ)?/iu', '', $search_kw);
            $search_kw = trim($search_kw);
        }

        // Nếu không có thể loại, không có lọc giá, và từ khóa rỗng -> không phải intent tìm sách
        if (empty($category_slug) && $max_price == 0 && empty($search_kw)) {
            return false;
        }

        // Tìm sách bằng hàm truy vấn thống nhất
        $products = $this->query_products_by_keyword($search_kw, $max_price, $min_price, 4, $category_slug);

        if (empty($products)) {
            if ($is_search_intent) {
                return [
                    'reply'        => 'Dạ em chưa tìm thấy cuốn sách nào khớp với yêu cầu <em>"' . esc_html($message) . '"</em>. Quý khách có thể thử tìm với tên ngắn gọn hơn hoặc xem danh mục <a href="' . esc_url(home_url('/san-pham/')) . '">Tất cả sản phẩm</a> nhé!',
                    'payload_html' => '',
                ];
            }
            return false;
        }

        // Tạo thẻ sản phẩm (Product cards)
        $html = '<div class="biblio-chat-products-list">';
        foreach ($products as $p) {
            $pid = $p->get_id();
            $title = $p->get_name();
            $link = $p->get_permalink();
            $thumb = get_the_post_thumbnail_url($pid, 'thumbnail') ?: wc_placeholder_img_src();
            $author = get_post_meta($pid, '_biblio_author', true) ?: (get_post_meta($pid, '_nbook_author', true) ?: 'Nhiều tác giả');
            $price_html = $p->get_price_html();

            $html .= '
            <div class="biblio-chat-product-card">
                <img src="' . esc_url($thumb) . '" alt="' . esc_attr($title) . '" class="biblio-chat-product-img" loading="lazy">
                <div class="biblio-chat-product-info">
                    <a href="' . esc_url($link) . '" target="_blank" class="biblio-chat-product-title" title="' . esc_attr($title) . '">' . esc_html($title) . '</a>
                    <div class="biblio-chat-product-author">Tác giả: ' . esc_html($author) . '</div>
                    <div class="biblio-chat-product-price">' . $price_html . '</div>
                </div>
                <div class="biblio-chat-product-actions">
                    <a href="' . esc_url($link) . '" target="_blank" class="biblio-chat-btn-view">Chi tiết</a>
                    <button type="button" class="biblio-chat-btn-add" data-product-id="' . esc_attr($pid) . '">+ Giỏ hàng</button>
                </div>
            </div>';
        }
        $html .= '</div>';

        $lead_text = 'Dạ, em tìm thấy một số tựa sách rất phù hợp với yêu cầu của quý khách đây ạ:';
        if ($category_slug === 'sach-giao-duc') {
            $lead_text = 'Dưới đây là các tựa <strong>Sách Giáo Trình NEU</strong> chuẩn học phần:';
        } elseif ($max_price > 0 && $min_price > 0) {
            $lead_text = 'Dưới đây là các cuốn sách có giá từ <strong>' . number_format_i18n($min_price) . 'đ</strong> đến <strong>' . number_format_i18n($max_price) . 'đ</strong>:';
        } elseif ($max_price > 0) {
            $lead_text = 'Dưới đây là các cuốn sách có giá dưới <strong>' . number_format_i18n($max_price) . 'đ</strong>:';
        }

        return [
            'reply'        => $lead_text,
            'payload_html' => $html,
        ];
    }

    /**
     * Truy vấn sản phẩm theo từ khóa (Tiêu đề, Tác giả, SKU, Thể loại, Mức giá)
     */
    private function query_products_by_keyword($keyword, $max_price = 0, $min_price = 0, $limit = 4, $category_slug = '') {
        global $wpdb;
        $like = '%' . $wpdb->esc_like($keyword) . '%';

        $price_sql = '';
        if ($max_price > 0) {
            $price_sql .= $wpdb->prepare(" AND CAST(pm_price.meta_value AS DECIMAL(10,2)) <= %d", $max_price);
        }
        if ($min_price > 0) {
            $price_sql .= $wpdb->prepare(" AND CAST(pm_price.meta_value AS DECIMAL(10,2)) >= %d", $min_price);
        }

        $cat_join = '';
        $cat_where = '';
        if (!empty($category_slug)) {
            $cat_join = " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
                          INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat')
                          INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id) ";
            $cat_where = $wpdb->prepare(" AND t.slug = %s ", $category_slug);
        }

        $sql = "
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            {$cat_join}
            LEFT JOIN {$wpdb->postmeta} pm_author ON (p.ID = pm_author.post_id AND pm_author.meta_key IN ('_biblio_author', '_nbook_author'))
            LEFT JOIN {$wpdb->postmeta} pm_sku ON (p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku')
            LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
            WHERE p.post_type = 'product'
              AND p.post_status = 'publish'
              {$price_sql}
              {$cat_where}
              AND (
                  p.post_title LIKE %s
                  OR pm_author.meta_value LIKE %s
                  OR pm_sku.meta_value LIKE %s
              )
            LIMIT %d
        ";

        $product_ids = $wpdb->get_col($wpdb->prepare($sql, $like, $like, $like, $limit));

        $products = [];
        if (!empty($product_ids)) {
            foreach ($product_ids as $pid) {
                $p = wc_get_product($pid);
                if ($p) $products[] = $p;
            }
        }

        return $products;
    }

    /**
     * So khớp FAQ từ cơ sở dữ liệu
     */
    private function handle_faq_match($message, $msg_normalized) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbook_chatbot_faq';
        $faqs = $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY priority DESC");

        if (empty($faqs)) {
            return false;
        }

        $best_match = null;
        $max_score = 0;

        foreach ($faqs as $f) {
            $score = 0;
            // 1. So khớp câu hỏi trực tiếp
            $q_norm = $this->remove_accents(mb_strtolower($f->question, 'UTF-8'));
            if (strpos($msg_normalized, $q_norm) !== false || strpos($q_norm, $msg_normalized) !== false) {
                $score += 15;
            }

            // 2. So khớp từ khóa
            $keywords = array_filter(array_map('trim', explode(',', $f->keywords)));
            foreach ($keywords as $kw) {
                $kw_norm = $this->remove_accents(mb_strtolower($kw, 'UTF-8'));
                if (empty($kw_norm)) continue;
                if (strpos($msg_normalized, $kw_norm) !== false) {
                    $score += 10;
                }
            }

            if ($score > $max_score) {
                $max_score = $score;
                $best_match = $f;
            }
        }

        if ($best_match && $max_score >= 10) {
            return [
                'reply'        => $best_match->answer,
                'payload_html' => '',
            ];
        }

        return false;
    }

    /**
     * Tùy chọn gọi API LLM (khi được bật ở Settings → Biblio)
     */
    private function call_llm_api($user_message) {
        $api_key = get_option('biblio_ai_api_key');
        if (empty($api_key)) {
            return false;
        }

        $model = get_option('biblio_ai_model', 'gemini-1.5-flash');
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($api_key);

        $system_instruction = "Bạn là Trợ lý ảo tư vấn sách chuyên nghiệp của sàn thương mại điện tử Biblio (website nhà sách Đại học Kinh tế Quốc dân NEU). " .
                               "Quy tắc phản hồi: " .
                               "1. Trả lời bằng tiếng Việt lịch sự, thân thiện, súc tích (dưới 120 từ). " .
                               "2. Các chính sách chính của Biblio: Đổi trả miễn phí 7 ngày nếu lỗi in, Miễn phí vận chuyển cho đơn từ 299.000đ, Tặng kèm bookmark nghệ thuật và bọc sách plastic miễn phí, Hotline 0933 109 009. " .
                               "3. Khuyến khích bạn đọc mua sắm và chia sẻ niềm đam mê sách.";

        $body = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $system_instruction . "\n\nKhách hàng hỏi: " . $user_message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'     => 0.4,
                'maxOutputTokens' => 300,
            ]
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
            'timeout' => 8,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return !empty($reply) ? nl2br(esc_html(trim($reply))) : false;
    }

    /**
     * Phản hồi mặc định khi không khớp luật nào
     */
    private function handle_default_fallback() {
        $chips_html = '
        <div style="margin-top: 8px;">
            <div class="biblio-chat-chips">
                <button type="button" class="biblio-chat-chip" data-query="Tìm sách theo chủ đề">🔍 Tìm sách theo chủ đề</button>
                <button type="button" class="biblio-chat-chip" data-query="Sách giáo trình">📖 Sách giáo trình NEU</button>
                <button type="button" class="biblio-chat-chip" data-query="Theo dõi đơn hàng">📦 Tra cứu đơn hàng</button>
                <button type="button" class="biblio-chat-chip" data-query="Chính sách đổi trả">🔄 Chính sách đổi trả</button>
                <button type="button" class="biblio-chat-chip" data-query="Voucher đang có">🎟️ Voucher đang có</button>
                <button type="button" class="biblio-chat-chip" data-query="Gặp nhân viên CSKH">📞 Gặp nhân viên tư vấn</button>
            </div>
        </div>';

        return [
            'reply'        => 'Dạ, em chưa hiểu rõ yêu cầu của quý khách. Quý khách có thể thử:<br>' .
                              '• Gõ <strong>tên cuốn sách</strong> hoặc <strong>tác giả</strong> cần tìm.<br>' .
                              '• Nhập <strong>#MãĐơnHàng</strong> để tra cứu tiến độ giao sách.<br>' .
                              '• Hoặc chọn nhanh các chủ đề hỗ trợ bên dưới ạ:',
            'payload_html' => $chips_html,
        ];
    }

    /**
     * Helper: Kiểm tra chuỗi chứa bất kỳ từ khóa nào
     */
    private function match_any($haystack, $needles) {
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper: Loại bỏ dấu tiếng Việt để so khớp dễ dàng
     */
    private function remove_accents($str) {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/u", "a", $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/u", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/u", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/u", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/u", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/u", "y", $str);
        $str = preg_replace("/(đ)/u", "d", $str);
        return $str;
    }
}
