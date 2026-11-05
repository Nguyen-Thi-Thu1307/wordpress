<?php
/**
 * Edit address form - Biblio Modern E-Commerce Style (Shopee / Biblio)
 */

defined('ABSPATH') || exit;

$page_title = ('billing' === $load_address) ? 'Địa chỉ thanh toán & Hóa đơn' : 'Địa chỉ giao hàng nhận sách';
$back_url = wc_get_account_endpoint_url('edit-address');

do_action('woocommerce_before_edit_account_address_form'); ?>

<?php if (!$load_address) : ?>
    <?php wc_get_template('myaccount/my-address.php'); ?>
<?php else : ?>

    <div class="nb-myaccount-panel nb-edit-address-panel">

        <!-- Navigation Back Bar -->
        <div class="nb-panel-back-bar">
            <a href="<?php echo esc_url($back_url); ?>" class="nb-back-link">
                <i class="bi bi-arrow-left"></i>&nbsp;&nbsp;Quay lại Sổ địa chỉ
            </a>
        </div>

        <!-- Header -->
        <div class="nb-panel-header">
            <div class="nb-ph-left">
                <h2 class="nb-ph-title">
                    <i class="bi <?php echo $load_address === 'billing' ? 'bi-receipt' : 'bi-truck'; ?> text-danger me-2"></i>
                    <?php echo esc_html($page_title); ?>
                </h2>
                <p class="nb-ph-subtitle">
                    <?php echo $load_address === 'billing' ? 'Thông tin người trả tiền và địa chỉ xuất hóa đơn chứng từ mua hàng.' : 'Thông tin người nhận sách tận nơi, số điện thoại liên lạc khi bưu tá giao hàng.'; ?>
                </p>
            </div>
        </div>

        <!-- Edit Form -->
        <form method="post" novalidate class="nb-styled-form nb-address-form">

            <div class="woocommerce-address-fields">
                <?php do_action("woocommerce_before_edit_address_form_{$load_address}"); ?>

                <div class="woocommerce-address-fields__field-wrapper nb-form-grid">
                    <?php
                    foreach ($address as $key => $field) {
                        $val = wc_get_post_data_by_key($key, $field['value'] ?? '');
                        woocommerce_form_field($key, $field, $val);
                    }
                    ?>
                </div>

                <?php do_action("woocommerce_after_edit_address_form_{$load_address}"); ?>

                <div class="nb-form-actions">
                    <button type="submit" class="nb-btn-save-address" name="save_address" value="<?php esc_attr_e('Save address', 'woocommerce'); ?>">
                        <i class="bi bi-check2-circle"></i>&nbsp;&nbsp;Lưu thông tin địa chỉ
                    </button>
                    <a href="<?php echo esc_url($back_url); ?>" class="nb-btn-cancel-address">
                        Hủy
                    </a>
                    <?php wp_nonce_field('woocommerce-edit_address', 'woocommerce-edit-address-nonce'); ?>
                    <input type="hidden" name="action" value="edit_address" />
                </div>
            </div>

        </form>

    </div>

<?php endif; ?>

<?php do_action('woocommerce_after_edit_account_address_form'); ?>
