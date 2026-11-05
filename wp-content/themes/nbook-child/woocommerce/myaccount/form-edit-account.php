<?php
/**
 * Edit account form - Biblio Modern E-Commerce Style (Shopee / Biblio)
 */

defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$user = wp_get_current_user();
$display_name = $user ? $user->display_name : '';
$email = $user ? $user->user_email : '';
$first_name = $user ? $user->first_name : '';
$last_name = $user ? $user->last_name : '';

$tier_info = [
    'tier' => 'Thành viên',
    'color' => '#ee4d2d',
    'icon' => 'bi-patch-check-fill'
];
if (class_exists('Biblio_Points_Voucher')) {
    $tier_info = Biblio_Points_Voucher::instance()->get_tier_info($user_id);
}

do_action('woocommerce_before_edit_account_form'); ?>

<div class="nb-myaccount-panel nb-edit-account-panel">

    <!-- Top Header -->
    <div class="nb-panel-header">
        <div class="nb-ph-left">
            <h2 class="nb-ph-title"><i class="bi bi-person-bounding-box text-danger me-2"></i> Hồ Sơ Của Tôi</h2>
            <p class="nb-ph-subtitle">Quản lý thông tin hồ sơ và mật khẩu để bảo vệ an toàn tài khoản Biblio.</p>
        </div>
    </div>

    <div class="nb-profile-layout">

        <!-- Main Form Column (Left) -->
        <div class="nb-profile-main-col">
            <form class="woocommerce-EditAccountForm edit-account nb-styled-form" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?> >

                <?php do_action('woocommerce_edit_account_form_start'); ?>

                <!-- SECTION 1: THÔNG TIN CƠ BẢN -->
                <div class="nb-form-card">
                    <div class="nb-fc-header">
                        <i class="bi bi-person-vcard text-primary"></i>
                        <h3>Thông tin cá nhân</h3>
                    </div>

                    <div class="nb-fc-body">
                        <div class="nb-form-row-2col">
                            <div class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
                                <label for="account_first_name">Họ & tên đệm&nbsp;<span class="required">*</span></label>
                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($first_name); ?>" required />
                            </div>
                            <div class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
                                <label for="account_last_name">Tên&nbsp;<span class="required">*</span></label>
                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($last_name); ?>" required />
                            </div>
                        </div>

                        <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label for="account_display_name">Tên hiển thị công khai&nbsp;<span class="required">*</span></label>
                            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr($display_name); ?>" required />
                            <span class="nb-form-hint">
                                <i class="bi bi-info-circle"></i>&nbsp;&nbsp;Tên này sẽ xuất hiện trong phần đánh giá sách, bình luận và lời chào tài khoản của bạn.
                            </span>
                        </div>

                        <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label for="account_email">Địa chỉ Email&nbsp;<span class="required">*</span></label>
                            <div class="nb-input-with-badge">
                                <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($email); ?>" required />
                                <span class="nb-verified-badge"><i class="bi bi-check2-circle"></i>&nbsp;&nbsp;Đã kích hoạt</span>
                            </div>
                            <span class="nb-form-hint">Email nhận thông báo đơn hàng sách, mã voucher độc quyền và hóa đơn điện tử.</span>
                        </div>

                        <?php do_action('woocommerce_edit_account_form_fields'); ?>
                    </div>
                </div>

                <!-- SECTION 2: THAY ĐỔI MẬT KHẨU -->
                <div class="nb-form-card nb-password-card">
                    <div class="nb-fc-header">
                        <i class="bi bi-shield-lock-fill text-warning"></i>
                        <div>
                            <h3>Thay đổi mật khẩu</h3>
                            <span class="nb-fc-subtitle">Để trống tất cả các ô dưới đây nếu bạn không muốn đổi mật khẩu</span>
                        </div>
                    </div>

                    <div class="nb-fc-body">
                        <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label for="password_current">Mật khẩu hiện tại</label>
                            <div class="nb-password-field-wrap">
                                <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" placeholder="Nhập mật khẩu đang dùng" />
                                <button type="button" class="nb-eye-toggle" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="nb-form-row-2col">
                            <div class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
                                <label for="password_1">Mật khẩu mới</label>
                                <div class="nb-password-field-wrap">
                                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" placeholder="Tối thiểu 8 ký tự" />
                                    <button type="button" class="nb-eye-toggle" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
                                <label for="password_2">Xác nhận mật khẩu mới</label>
                                <div class="nb-password-field-wrap">
                                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới" />
                                    <button type="button" class="nb-eye-toggle" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php do_action('woocommerce_edit_account_form'); ?>

                <!-- ACTIONS -->
                <div class="nb-form-actions">
                    <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
                    <button type="submit" class="woocommerce-Button nb-btn-save-profile" name="save_account_details" value="<?php esc_attr_e('Save changes', 'woocommerce'); ?>">
                        <i class="bi bi-check2-circle"></i>&nbsp;&nbsp;Lưu thay đổi hồ sơ
                    </button>
                    <input type="hidden" name="action" value="save_account_details" />
                </div>

                <?php do_action('woocommerce_after_edit_account_form'); ?>

            </form>
        </div>

        <!-- Side Card Column (Right) -->
        <div class="nb-profile-side-col">
            
            <!-- Mini Profile Box -->
            <div class="nb-side-profile-box">
                <?php 
                $custom_avatar_url = get_user_meta($user_id, 'nbook_custom_avatar_url', true);
                $avatar_url = $custom_avatar_url ? $custom_avatar_url : get_avatar_url($user_id, ['size' => 160]);
                $has_custom = !empty($custom_avatar_url);
                $has_img = ($avatar_url && !strpos($avatar_url, 'gravatar.com/avatar/?d=') && !strpos($avatar_url, 'd=mm'));
                ?>
                <div class="nb-spb-avatar-wrap" data-nonce="<?php echo esc_attr(wp_create_nonce('nbook_avatar_upload_nonce')); ?>">
                    <div class="nb-spb-avatar" id="nbSpbAvatarPreview" title="Nhấp để thay đổi ảnh đại diện">
                        <?php if ($has_img): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" id="nbAvatarImg">
                        <?php else: ?>
                            <span id="nbAvatarInitial"><?php echo esc_html(mb_strtoupper(mb_substr($display_name, 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="nb-avatar-camera-btn" id="nbBtnCameraUpload" title="Tải ảnh đại diện từ thiết bị">
                        <i class="bi bi-camera-fill"></i>
                    </button>
                    
                    <input type="file" id="nbAvatarFileInput" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;" />
                </div>

                <div class="nb-avatar-actions">
                    <button type="button" class="nb-btn-choose-avatar" id="nbBtnChooseAvatar">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Tải ảnh từ máy
                    </button>
                    <button type="button" class="nb-btn-remove-avatar <?php echo $has_custom ? '' : 'd-none'; ?>" id="nbBtnRemoveAvatar" title="Xóa ảnh và dùng mặc định">
                        <i class="bi bi-trash3-fill me-1"></i> Xóa
                    </button>
                </div>

                <div id="nbAvatarUploadStatus" class="nb-avatar-status" style="display: none;"></div>

                <h4 class="nb-spb-name"><?php echo esc_html($display_name); ?></h4>
                <p class="nb-spb-email text-muted"><?php echo esc_html($email); ?></p>
                <div class="nb-spb-tier">
                    <span class="nb-sidebar-tier-badge" style="border-color: <?php echo esc_attr($tier_info['color'] ?? '#ee4d2d'); ?>; color: <?php echo esc_attr($tier_info['color'] ?? '#ee4d2d'); ?>;">
                        <i class="bi <?php echo esc_attr($tier_info['icon'] ?? 'bi-patch-check-fill'); ?>"></i>&nbsp;&nbsp;<span>Hạng <?php echo esc_html($tier_info['tier'] ?? 'Thành viên'); ?></span>
                    </span>
                </div>
                <div class="nb-spb-avatar-hint">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Dung lượng tối đa: 5MB.<br>Định dạng hỗ trợ: JPG, PNG, WEBP.
                </div>
            </div>

            <!-- Security Checklist Box -->
            <div class="nb-side-security-box">
                <h5 class="nb-ssb-title"><i class="bi bi-shield-check text-success"></i>&nbsp;&nbsp;Tình trạng an toàn</h5>
                <ul class="nb-ssb-list">
                    <li>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div class="nb-ssb-item-meta">
                            <strong>Email chính thức</strong>
                            <span>Đã kích hoạt nhận thông báo</span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div class="nb-ssb-item-meta">
                            <strong>Mật khẩu bảo vệ</strong>
                            <span>Đã thiết lập mật mã cá nhân</span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <div class="nb-ssb-item-meta">
                            <strong>Bảo mật định kỳ</strong>
                            <span>Khuyến nghị đổi mật khẩu mỗi 6 tháng</span>
                        </div>
                    </li>
                </ul>
            </div>

        </div>

    </div>

</div>


