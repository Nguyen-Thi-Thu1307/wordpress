<?php
/**
 * Downloads - Biblio Modern E-Commerce Style (Shopee / Biblio)
 */

if (!defined('ABSPATH')) {
    exit;
}

$downloads     = WC()->customer ? WC()->customer->get_downloadable_products() : [];
$has_downloads = (bool) $downloads;
$shop_url      = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/san-pham/');

do_action('woocommerce_before_account_downloads', $has_downloads); ?>

<div class="nb-myaccount-panel nb-downloads-panel">

    <!-- Top Header -->
    <div class="nb-panel-header">
        <div class="nb-ph-left">
            <h2 class="nb-ph-title"><i class="bi bi-cloud-arrow-down-fill text-info me-2"></i> Sách Số & Tài Liệu Tải Xuống</h2>
            <p class="nb-ph-subtitle">Quản lý các tài liệu PDF đính kèm, quà tặng ebook độc quyền và file tài liệu theo đơn mua sách của bạn.</p>
        </div>
    </div>

    <?php if ($has_downloads) : ?>

        <?php do_action('woocommerce_before_available_downloads'); ?>

        <div class="nb-downloads-list">
            <?php foreach ($downloads as $download) : ?>
                <div class="nb-download-card">
                    <div class="nb-dc-icon">
                        <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    </div>
                    <div class="nb-dc-info">
                        <h4 class="nb-dc-title">
                            <?php if ($download['product_url']) : ?>
                                <a href="<?php echo esc_url($download['product_url']); ?>"><?php echo esc_html($download['product_name']); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($download['product_name']); ?>
                            <?php endif; ?>
                        </h4>
                        <div class="nb-dc-meta">
                            <span class="nb-dc-file-name"><i class="bi bi-paperclip"></i>&nbsp;&nbsp;<?php echo esc_html($download['download_name']); ?></span>
                            <?php if (is_numeric($download['downloads_remaining'])) : ?>
                                <span class="nb-dc-remaining"><i class="bi bi-arrow-repeat"></i>&nbsp;&nbsp;Còn lại: <strong><?php echo esc_html($download['downloads_remaining']); ?></strong> lượt tải</span>
                            <?php endif; ?>
                            <?php if (!empty($download['access_expires'])) : ?>
                                <span class="nb-dc-expires"><i class="bi bi-clock"></i>&nbsp;&nbsp;Hết hạn: <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($download['access_expires']))); ?></span>
                            <?php else : ?>
                                <span class="nb-dc-expires text-success"><i class="bi bi-infinity"></i>&nbsp;&nbsp;Vô thời hạn</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="nb-dc-action">
                        <a href="<?php echo esc_url($download['download_url']); ?>" class="nb-btn-download">
                            <i class="bi bi-download"></i>&nbsp;&nbsp;Tải về máy
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php do_action('woocommerce_after_available_downloads'); ?>

    <?php else : ?>

        <!-- Empty State -->
        <div class="nb-downloads-empty-box">
            <div class="nb-deb-icon">
                <i class="bi bi-cloud-arrow-down"></i>
            </div>
            <h3 class="nb-deb-title">Bạn chưa có tài liệu hoặc sách số nào</h3>
            <p class="nb-deb-desc">
                Khi bạn đặt mua các bộ sách có đính kèm file tài liệu học tập, audio nghe, bookmark bản in hoặc ebook độc quyền từ Biblio, đường link tải an toàn với tốc độ cao sẽ tự động hiển thị tại đây.
            </p>
            <div class="nb-deb-actions">
                <a href="<?php echo esc_url($shop_url); ?>" class="nb-btn-browse-books">
                    <i class="bi bi-book-half"></i>&nbsp;&nbsp;Khám phá kho sách Biblio
                </a>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php do_action('woocommerce_after_account_downloads', $has_downloads); ?>
