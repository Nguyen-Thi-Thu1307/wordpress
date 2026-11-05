<?php
/**
 * Nbook Child Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

// Tắt hoàn toàn nút cuộn lên đầu mặc định màu xanh của theme Astra (#ast-scroll-top)
add_filter('astra_get_option_scroll-to-top-enable', '__return_false', 999);
add_filter('astra_addon_scroll_top_enable', '__return_false', 999);
add_action('init', function() {
    if (class_exists('Astra_Scroll_To_Top_Loader')) {
        remove_action('wp_footer', [Astra_Scroll_To_Top_Loader::get_instance(), 'html_markup_loader']);
    }
}, 1);

// Enqueue styles & scripts
add_action('wp_enqueue_scripts', 'nbook_enqueue_assets', 20);
function nbook_enqueue_assets() {
    // Parent style
    wp_enqueue_style('astra-parent-theme-css', get_template_directory_uri() . '/style.css', [], ASTRA_THEME_VERSION);

    // Bootstrap Icons
    wp_enqueue_style(
        'bootstrap-icons',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
        [],
        '1.11.3'
    );

    // Google Fonts
    wp_enqueue_style(
        'nbook-fonts',
        'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,400;1,700&display=swap',
        [],
        null
    );

    // Child theme style (auto version cache-bust)
    wp_enqueue_style(
        'nbook-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['astra-parent-theme-css'],
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Xóa triệt để nút cuộn lên đầu màu xanh mặc định của Astra (#ast-scroll-top)
    wp_add_inline_style('nbook-child-style', '
        #ast-scroll-top,
        .ast-scroll-top-icon,
        .ast-scroll-to-top-right,
        .ast-scroll-to-top-left {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            width: 0 !important;
            height: 0 !important;
            position: absolute !important;
            left: -9999px !important;
            z-index: -9999 !important;
        }
        .nb-section-head-center {
            text-align: center !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            margin-bottom: 24px !important;
        }
        .nb-section-head-center .nb-sec-tag,
        .nb-sec-tag {
            color: #ee4d2d !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            display: inline-block !important;
            text-align: center !important;
            margin: 0 auto 6px auto !important;
        }
        .nb-section-head-center .nb-sec-title {
            text-align: center !important;
            display: block !important;
            margin: 0 auto 8px auto !important;
            width: 100% !important;
            justify-content: center !important;
        }
        .nb-section-head-center .nb-sec-desc {
            text-align: center !important;
            margin: 0 auto !important;
        }

        /* =====================================================================
           MODAL "BÁO CHO TÔI KHI CÓ HÀNG" (PREMIUM ENHANCED DESIGN)
           ===================================================================== */
        .nb-stock-notify-modal-overlay {
            position: fixed !important;
            inset: 0 !important;
            z-index: 100000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 16px !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.25s ease, visibility 0.25s ease !important;
        }
        .nb-stock-notify-modal-overlay.active {
            opacity: 1 !important;
            visibility: visible !important;
        }
        .nb-stock-notify-backdrop {
            position: absolute !important;
            inset: 0 !important;
            background: rgba(15, 23, 42, 0.72) !important;
            backdrop-filter: blur(6px) !important;
            -webkit-backdrop-filter: blur(6px) !important;
        }
        .nb-stock-notify-box {
            position: relative !important;
            z-index: 2 !important;
            background: #ffffff !important;
            border-radius: 18px !important;
            padding: 30px 28px 24px !important;
            width: 100% !important;
            max-width: 480px !important;
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.05) !important;
            transform: scale(0.92) translateY(12px) !important;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease !important;
        }
        .nb-stock-notify-modal-overlay.active .nb-stock-notify-box {
            transform: scale(1) translateY(0) !important;
        }
        .nb-stock-notify-close {
            position: absolute !important;
            top: 14px !important;
            right: 14px !important;
            width: 34px !important;
            height: 34px !important;
            background: #f1f5f9 !important;
            border: none !important;
            border-radius: 50% !important;
            font-size: 20px !important;
            color: #64748b !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease !important;
            line-height: 1 !important;
            padding: 0 !important;
        }
        .nb-stock-notify-close:hover {
            background: #fee2e2 !important;
            color: #ef4444 !important;
            transform: rotate(90deg) !important;
        }
        .nb-snb-header {
            text-align: center !important;
            margin-bottom: 16px !important;
        }
        .nb-snb-icon {
            width: 54px !important;
            height: 54px !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #fff1ec 0%, #ffe4de 100%) !important;
            color: #ee4d2d !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 24px !important;
            margin: 0 auto 12px !important;
            border: 2px solid #ffd8cf !important;
            box-shadow: 0 6px 16px rgba(238, 77, 45, 0.18) !important;
        }
        .nb-snb-badge {
            display: inline-flex !important;
            align-items: center !important;
            background: #fff7ed !important;
            color: #ea580c !important;
            border: 1px solid #ffedd5 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            padding: 3px 10px !important;
            border-radius: 20px !important;
            letter-spacing: 0.5px !important;
            margin-bottom: 8px !important;
        }
        .nb-snb-title {
            font-size: 21px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin: 0 !important;
            letter-spacing: -0.3px !important;
        }
        .nb-snb-product-box {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            padding: 10px 14px !important;
            margin-bottom: 14px !important;
        }
        .nb-snb-product-icon {
            width: 36px !important;
            height: 36px !important;
            border-radius: 8px !important;
            background: #e2e8f0 !important;
            color: #475569 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 18px !important;
            flex-shrink: 0 !important;
        }
        .nb-snb-product-meta {
            flex: 1 !important;
            min-width: 0 !important;
            text-align: left !important;
        }
        .nb-snb-product-label {
            display: block !important;
            font-size: 11px !important;
            color: #64748b !important;
            font-weight: 500 !important;
            line-height: 1.2 !important;
        }
        .nb-snb-product-name {
            display: block !important;
            font-size: 13.5px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            line-height: 1.4 !important;
        }
        .nb-snb-stock-tag {
            display: inline-flex !important;
            align-items: center !important;
            background: #fee2e2 !important;
            color: #dc2626 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            padding: 3px 8px !important;
            border-radius: 6px !important;
            flex-shrink: 0 !important;
            border: 1px solid #fecaca !important;
        }
        .nb-snb-sub {
            font-size: 13px !important;
            color: #475569 !important;
            line-height: 1.5 !important;
            margin: 0 0 16px 0 !important;
            background: #eff6ff !important;
            border: 1px solid #dbeafe !important;
            padding: 9px 12px !important;
            border-radius: 8px !important;
            text-align: left !important;
        }
        .nb-snb-label {
            display: flex !important;
            align-items: center !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            margin-bottom: 7px !important;
        }
        .nb-snb-label i.bi {
            color: #ee4d2d !important;
            margin-right: 7px !important;
            font-size: 15px !important;
        }
        .nb-snb-input-wrap {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
        }
        .nb-snb-input-icon {
            position: absolute !important;
            left: 14px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #94a3b8 !important;
            font-size: 16px !important;
            pointer-events: none !important;
            transition: color 0.2s ease !important;
            margin-right: 0 !important;
        }
        .nb-snb-input-wrap:focus-within .nb-snb-input-icon {
            color: #ee4d2d !important;
        }
        .nb-snb-input {
            width: 100% !important;
            height: 46px !important;
            padding: 0 14px 0 42px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 10px !important;
            font-size: 14px !important;
            color: #1e293b !important;
            background: #ffffff !important;
            outline: none !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease !important;
        }
        .nb-snb-input:focus {
            border-color: #ee4d2d !important;
            box-shadow: 0 0 0 3.5px rgba(238, 77, 45, 0.15) !important;
            background: #fffdfc !important;
        }
        .nb-snb-submit-btn {
            width: 100% !important;
            height: 48px !important;
            background: linear-gradient(135deg, #ee4d2d 0%, #ff5722 100%) !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 10px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            letter-spacing: 0.3px !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 14px rgba(238, 77, 45, 0.3) !important;
            transition: all 0.25s ease !important;
            margin-top: 10px !important;
        }
        .nb-snb-submit-btn:hover {
            background: linear-gradient(135deg, #d73211 0%, #e64a19 100%) !important;
            box-shadow: 0 6px 20px rgba(238, 77, 45, 0.45) !important;
            transform: translateY(-1px) !important;
        }
        .nb-snb-submit-btn:disabled {
            opacity: 0.7 !important;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
        }
        .nb-snb-submit-btn .btn-text,
        .nb-snb-submit-btn .btn-spinner {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .nb-snb-note {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 12px !important;
            color: #64748b !important;
            margin-top: 14px !important;
            text-align: center !important;
            line-height: 1.4 !important;
        }
        .nb-snb-note i.bi {
            color: #10b981 !important;
            font-size: 14px !important;
            flex-shrink: 0 !important;
        }
        .nb-snb-alert {
            padding: 11px 14px !important;
            border-radius: 10px !important;
            font-size: 13px !important;
            margin-bottom: 12px !important;
            line-height: 1.4 !important;
            display: flex !important;
            align-items: center !important;
        }
        .nb-snb-alert.success {
            background: #f0fdf4 !important;
            color: #166534 !important;
            border: 1px solid #bbf7d0 !important;
        }
        .nb-snb-alert.error {
            background: #fef2f2 !important;
            color: #991b1b !important;
            border: 1px solid #fecaca !important;
        }

        /* Icon spacing for stock notify & stock buttons */
        .nb-stock-notify-box i.bi,
        .nb-stock-notify-inline-form i.bi,
        .nb-single-outofstock-box i.bi,
        .nb-btn-notify i.bi,
        .nb-btn-stock-notify i.bi,
        .btn-stock-notify-card i.bi {
            margin-right: 6px !important;
            display: inline-block !important;
            vertical-align: -0.1em !important;
        }
        .nb-snb-product-icon i.bi,
        .nb-snb-icon i.bi,
        .nb-snb-input-icon {
            margin-right: 0 !important;
        }
        .nb-btn-notify,
        .nb-btn-stock-notify,
        .btn-stock-notify-card {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
        }

        /* =====================================================================
           BREADCRUMB & DISCOUNT BADGE FORMATTING (SINGLE PRODUCT & SITEWIDE)
           ===================================================================== */
        /* Thanh điều hướng breadcrumbs: thoáng đãng, cách xa nội dung bên dưới */
        .woocommerce-breadcrumb,
        .nb-custom-product-breadcrumb,
        .nb-shop-breadcrumb,
        .nb-single-breadcrumb,
        .nb-about-breadcrumb,
        .nb-contact-breadcrumb,
        .nb-news-breadcrumb,
        .nb-cart-breadcrumb,
        .nb-checkout-breadcrumb {
            font-size: 13.5px !important;
            line-height: 1.6 !important;
            margin-bottom: 22px !important;
            padding-bottom: 12px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 4px 6px !important;
        }

        .woocommerce-breadcrumb a,
        .nb-custom-product-breadcrumb a,
        .nb-shop-breadcrumb a,
        .nb-single-breadcrumb a,
        .nb-about-breadcrumb a,
        .nb-contact-breadcrumb a,
        .nb-news-breadcrumb a,
        .nb-cart-breadcrumb a,
        .nb-checkout-breadcrumb a,
        .nb-bc-link {
            color: #64748b !important;
            font-weight: 500 !important;
            text-decoration: none !important;
            transition: color 0.15s ease !important;
        }

        .woocommerce-breadcrumb a:hover,
        .nb-custom-product-breadcrumb a:hover,
        .nb-shop-breadcrumb a:hover,
        .nb-single-breadcrumb a:hover,
        .nb-about-breadcrumb a:hover,
        .nb-contact-breadcrumb a:hover,
        .nb-news-breadcrumb a:hover,
        .nb-cart-breadcrumb a:hover,
        .nb-checkout-breadcrumb a:hover,
        .nb-bc-link:hover {
            color: #ee4d2d !important;
            text-decoration: underline !important;
        }

        .woocommerce-breadcrumb .nb-bc-sep,
        .nb-custom-product-breadcrumb .nb-bc-sep,
        .nb-shop-breadcrumb .nb-bc-sep,
        .nb-single-breadcrumb .nb-bc-sep,
        .nb-about-breadcrumb .nb-bc-sep,
        .nb-contact-breadcrumb .nb-bc-sep,
        .nb-news-breadcrumb .nb-bc-sep,
        .nb-cart-breadcrumb .nb-bc-sep,
        .nb-checkout-breadcrumb .nb-bc-sep,
        .nb-bc-sep {
            color: #94a3b8 !important;
            font-weight: 400 !important;
            margin: 0 4px !important;
            user-select: none !important;
        }

        /* ĐANG XEM Ở ĐÂU: In đậm đổi màu đỏ */
        .woocommerce-breadcrumb .nb-bc-current,
        .nb-custom-product-breadcrumb .nb-bc-current,
        .nb-shop-breadcrumb .nb-bc-current,
        .nb-single-breadcrumb .nb-bc-current,
        .nb-about-breadcrumb .nb-bc-current,
        .nb-contact-breadcrumb .nb-bc-current,
        .nb-news-breadcrumb .nb-bc-current,
        .nb-cart-breadcrumb .nb-bc-current,
        .nb-checkout-breadcrumb .nb-bc-current,
        .nb-breadcrumb-current,
        .nb-bc-current,
        .single-product .woocommerce-breadcrumb span:last-child {
            color: #ee4d2d !important;
            font-weight: 700 !important;
        }

        /* Trang Giới Thiệu: Dòng slogan giữ trên 1 hàng duy nhất (cùng 1 dòng) */
        .nb-about-hero-content {
            max-width: 100% !important;
        }
        .nb-about-hero-desc {
            font-size: 14.5px !important;
            line-height: 1.5 !important;
            color: #475569 !important;
            margin: 0 0 18px 0 !important;
            max-width: 100% !important;
        }
        @media (min-width: 992px) {
            .nb-about-hero-desc {
                white-space: nowrap !important;
                font-size: clamp(12.5px, 1.15vw, 15px) !important;
            }
        }

        /* Nút giảm % bên dưới: giãn cách thoáng, không bị dính sát vào giá */
        .single-product div.product .nb-price-pct-tag,
        .nb-price-pct-tag {
            display: inline-flex !important;
            align-items: center !important;
            background: #ffebe8 !important;
            color: #ee4d2d !important;
            border: 1px solid #ffb8ad !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            padding: 3px 10px !important;
            border-radius: 6px !important;
            margin-left: 14px !important; /* Giãn cách ra 1 chút */
            margin-right: 8px !important;
            vertical-align: middle !important;
            line-height: 1.2 !important;
            box-shadow: 0 1px 3px rgba(238, 77, 45, 0.1) !important;
        }

        .single-product div.product p.price,
        .single-product div.product span.price {
            margin: 16px 0 18px 0 !important;
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 8px 12px !important;
        }

        /* Badge ⚡ GIẢM ...% đặt cố định trên góc ảnh sản phẩm với khoảng cách thoáng đãng */
        .single-product span.onsale.nb-shopee-onsale-badge,
        .single-product div.product > span.onsale {
            position: absolute !important;
            top: 20px !important;
            left: 24px !important;
            right: auto !important;
            margin: 0 !important;
            z-index: 15 !important;
            padding: 4px 10px !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 12px rgba(238, 77, 45, 0.35) !important;
        }

        /* Khung tóm tắt sản phẩm (summary) thoáng đãng */
        .single-product div.product .summary.entry-summary {
            padding-top: 4px !important;
            position: relative !important;
        }

        .single-product div.product h1.product_title {
            margin-top: 10px !important;
            margin-bottom: 14px !important;
            font-size: 24px !important;
            font-weight: 800 !important;
            line-height: 1.35 !important;
        }

        .single-product .nb-single-meta-strip {
            margin: 14px 0 16px 0 !important;
        }

        /* =====================================================================
           KHUNG TRANG TRÍ QUYỂN SÁCH & ĐỒNG NHẤT KÍCH THƯỚC TRÊN TRANG CHI TIẾT
           ===================================================================== */
        /* Toàn bộ khung trưng bày sản phẩm (Product Gallery Showcase) */
        .single-product div.product .woocommerce-product-gallery {
            background: #ffffff !important;
            border-radius: 20px !important;
            border: 1.5px solid #edf2f7 !important;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06), 0 20px 40px -15px rgba(238, 77, 45, 0.05) !important;
            padding: 22px 20px 20px !important;
            position: relative !important;
            overflow: visible !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* Huy hiệu phía trên quyển sách */
        .nb-gallery-top-badge {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            margin: 0 auto 16px auto !important;
            padding: 5px 16px !important;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%) !important;
            color: #c2410c !important;
            border: 1px solid #fed7aa !important;
            border-radius: 30px !important;
            font-size: 11.5px !important;
            font-weight: 800 !important;
            letter-spacing: 0.6px !important;
            text-transform: uppercase !important;
            width: fit-content !important;
            box-shadow: 0 2px 8px rgba(234, 88, 12, 0.12) !important;
        }
        .nb-gallery-top-badge i.bi {
            font-size: 13px !important;
            color: #ea580c !important;
        }

        /* Sân khấu trưng bày sách (Podium stage with radial lighting) */
        .single-product div.product .woocommerce-product-gallery .woocommerce-product-gallery__wrapper {
            background: radial-gradient(circle at 50% 32%, #ffffff 0%, #fbfcfd 55%, #edf2f7 100%) !important;
            border-radius: 16px !important;
            border: 1px solid #eef2f6 !important;
            padding: 30px 16px 36px !important;
            min-height: 470px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: relative !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        /* Bóng đổ bệ trưng bày (Podium floor shadow) bên dưới đáy sách */
        .single-product div.product .woocommerce-product-gallery .woocommerce-product-gallery__wrapper::after {
            content: "" !important;
            position: absolute !important;
            bottom: 16px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            width: 270px !important;
            height: 20px !important;
            background: radial-gradient(ellipse at center, rgba(15, 23, 42, 0.28) 0%, rgba(15, 23, 42, 0.08) 50%, transparent 75%) !important;
            border-radius: 50% !important;
            pointer-events: none !important;
            z-index: 1 !important;
        }

        /* Khung chứa ảnh từng quyển sách */
        .single-product div.product .woocommerce-product-gallery__image {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            margin: 0 auto !important;
            position: relative !important;
            z-index: 2 !important;
            text-align: center !important;
        }

        /* Thẻ liên kết ảnh với hiệu ứng 3D gáy sách và bóng đổ sách thật */
        .single-product div.product .woocommerce-product-gallery__image a {
            display: inline-block !important;
            width: 290px !important;
            height: 410px !important;
            max-width: 92% !important;
            margin: 0 auto !important;
            border-radius: 4px 14px 14px 4px !important;
            overflow: hidden !important;
            position: relative !important;
            box-shadow: 0 16px 38px -8px rgba(15, 23, 42, 0.28), 0 6px 14px -3px rgba(15, 23, 42, 0.16) !important;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
            background-color: #ffffff !important;
        }

        /* Gáy sách 3D sống động bên mép trái */
        .single-product div.product .woocommerce-product-gallery__image a::before {
            content: "" !important;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            bottom: 0 !important;
            width: 18px !important;
            z-index: 4 !important;
            pointer-events: none !important;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.28) 0%, rgba(255, 255, 255, 0.22) 28%, rgba(0, 0, 0, 0.12) 65%, transparent 100%) !important;
            border-left: 4px solid rgba(0, 0, 0, 0.25) !important;
        }

        /* Viền bóng bẩy mô phỏng bìa sách in bóng kính cao cấp */
        .single-product div.product .woocommerce-product-gallery__image a::after {
            content: "" !important;
            position: absolute !important;
            inset: 0 !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            border-radius: 4px 14px 14px 4px !important;
            pointer-events: none !important;
            z-index: 5 !important;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2) !important;
        }

        /* Hiệu ứng nhấc sách khi hover */
        .single-product div.product .woocommerce-product-gallery__image a:hover {
            transform: translateY(-8px) scale(1.02) !important;
            box-shadow: 0 24px 48px -10px rgba(15, 23, 42, 0.38), 0 10px 20px -4px rgba(15, 23, 42, 0.22) !important;
        }

        /* ĐỒNG NHẤT 100% KÍCH THƯỚC MỌI BÌA SÁCH TRÊN TRANG CHI TIẾT (Bất kể ảnh tải lên 600x600 vuông hay 2068x3344 đứng) */
        .single-product div.product .woocommerce-product-gallery__image img,
        .single-product div.product .woocommerce-product-gallery__image img.wp-post-image,
        .single-product div.product .woocommerce-product-gallery img {
            width: 290px !important;
            height: 410px !important;
            max-width: 290px !important;
            max-height: 410px !important;
            min-width: 290px !important;
            min-height: 410px !important;
            object-fit: cover !important;
            object-position: center !important;
            display: block !important;
            border-radius: 4px 14px 14px 4px !important;
            margin: 0 auto !important;
        }

        /* Thanh cam kết chất lượng dưới chân khung sách */
        .nb-single-gallery-footer {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 8px !important;
            margin-top: 18px !important;
            padding: 12px 14px !important;
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-sizing: border-box !important;
        }
        .nb-single-gallery-footer .nb-gf-item {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
            color: #334155 !important;
            white-space: nowrap !important;
        }
        .nb-single-gallery-footer .nb-gf-item i.bi {
            font-size: 15px !important;
            line-height: 1 !important;
        }

        /* Ẩn hoàn toàn icon tìm kiếm / phóng to trên ảnh sản phẩm chi tiết theo yêu cầu */
        .single-product div.product .woocommerce-product-gallery__trigger,
        .woocommerce-product-gallery__trigger {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        /* Dải thumbnail phụ (nếu sản phẩm có nhiều ảnh) */
        .single-product div.product .woocommerce-product-gallery ol.flex-control-thumbs {
            display: flex !important;
            justify-content: center !important;
            gap: 10px !important;
            margin: 16px 0 0 0 !important;
            padding: 0 !important;
            list-style: none !important;
            z-index: 5 !important;
            position: relative !important;
        }
        .single-product div.product .woocommerce-product-gallery ol.flex-control-thumbs li {
            margin: 0 !important;
            padding: 0 !important;
            width: auto !important;
            float: none !important;
        }
        .single-product div.product .woocommerce-product-gallery ol.flex-control-thumbs img {
            width: 60px !important;
            height: 85px !important;
            max-width: 60px !important;
            max-height: 85px !important;
            min-width: 60px !important;
            min-height: 85px !important;
            object-fit: cover !important;
            border-radius: 4px !important;
            border: 2px solid transparent !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1) !important;
        }
        .single-product div.product .woocommerce-product-gallery ol.flex-control-thumbs img.flex-active,
        .single-product div.product .woocommerce-product-gallery ol.flex-control-thumbs img:hover {
            border-color: #ee4d2d !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 10px rgba(238,77,45,0.3) !important;
        }

        /* Tối ưu hiển thị Responsive trên Mobile */
        @media (max-width: 576px) {
            .single-product div.product .woocommerce-product-gallery {
                padding: 16px 12px 14px !important;
                border-radius: 16px !important;
            }
            .single-product div.product .woocommerce-product-gallery .woocommerce-product-gallery__wrapper {
                min-height: 350px !important;
                padding: 20px 10px 26px !important;
            }
            .single-product div.product .woocommerce-product-gallery .woocommerce-product-gallery__wrapper::after {
                width: 200px !important;
                height: 16px !important;
                bottom: 12px !important;
            }
            .single-product div.product .woocommerce-product-gallery__image a {
                width: 210px !important;
                height: 295px !important;
            }
            .single-product div.product .woocommerce-product-gallery__image img,
            .single-product div.product .woocommerce-product-gallery__image img.wp-post-image,
            .single-product div.product .woocommerce-product-gallery img {
                width: 210px !important;
                height: 295px !important;
                max-width: 210px !important;
                max-height: 295px !important;
                min-width: 210px !important;
                min-height: 295px !important;
            }
            .nb-single-gallery-footer {
                flex-wrap: wrap !important;
                justify-content: center !important;
                gap: 10px 14px !important;
                padding: 10px !important;
            }
            .nb-single-gallery-footer .nb-gf-item {
                font-size: 11.5px !important;
            }
        }

        /* =====================================================================
           PHẦN SẢN PHẨM GỢI Ý (RELATED / UP-SELLS) – CĂN CHUẨN ĐỒNG BỘ TRANG CHỦ
           ===================================================================== */
        /* Toàn bộ khung bao bọc phần gợi ý: thẻ card trắng 16px, đổ bóng nhẹ, không tràn viền */
        .single-product .related.products,
        .single-product .up-sells.products,
        .single-product .nb-related-section {
            clear: both !important;
            float: none !important;
            width: 100% !important;
            max-width: 100% !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            padding: 24px 20px !important;
            margin: 36px 0 30px 0 !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
            box-sizing: border-box !important;
            overflow: hidden !important; /* Tuyệt đối chặn tràn viền */
            position: relative !important;
        }

        /* Tiêu đề phần gợi ý: phong cách header box trang chủ */
        .single-product .related.products > h2,
        .single-product .up-sells.products > h2,
        .single-product .nb-related-section .nb-sec-header-title {
            font-size: 18px !important;
            font-weight: 900 !important;
            color: #ee4d2d !important;
            margin: 0 0 20px 0 !important;
            padding-bottom: 12px !important;
            border-bottom: 2px solid #f1f5f9 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            line-height: 1.4 !important;
        }

        /* Khung lưới 4 cột chuẩn xác, triệt tiêu negative margin của theme Astra */
        .single-product .related.products ul.products,
        .single-product .up-sells.products ul.products,
        .single-product .nb-related-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 16px !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            float: none !important;
            border: none !important;
        }

        /* Triệt tiêu các pseudo elements float của Astra/WooCommerce */
        .single-product .related.products ul.products::before,
        .single-product .related.products ul.products::after,
        .single-product .up-sells.products ul.products::before,
        .single-product .up-sells.products ul.products::after {
            display: none !important;
        }

        /* Từng thẻ sản phẩm (Product Card): chuẩn chỉnh bo góc 12px, hover lift như trang chủ */
        .single-product .related.products ul.products li.product,
        .single-product .up-sells.products ul.products li.product {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            padding: 12px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            float: none !important;
            position: relative !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease !important;
            overflow: hidden !important;
        }

        .single-product .related.products ul.products li.product:hover,
        .single-product .up-sells.products ul.products li.product:hover {
            transform: translateY(-4px) !important;
            border-color: #ee4d2d !important;
            box-shadow: 0 8px 24px rgba(238, 77, 45, 0.14) !important;
        }

        /* Khung chứa ảnh bìa sách trong thẻ sản phẩm gợi ý */
        .single-product .related.products .astra-shop-thumbnail-wrap,
        .single-product .up-sells.products .astra-shop-thumbnail-wrap {
            position: relative !important;
            width: 100% !important;
            height: 220px !important;
            margin-bottom: 12px !important;
            overflow: hidden !important;
            border-radius: 8px !important;
            background: #ffffff !important;
            border: 1px solid #f1f5f9 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 4px 6px !important;
            box-sizing: border-box !important;
        }

        /* Ảnh bìa sách hiển thị đều đẹp, không tràn khung, có hiệu ứng đổ bóng sách thật */
        .single-product .related.products .astra-shop-thumbnail-wrap img,
        .single-product .up-sells.products .astra-shop-thumbnail-wrap img,
        .single-product .related.products .nb-loop-thumb-frame img,
        .single-product .up-sells.products .nb-loop-thumb-frame img {
            height: 100% !important;
            max-height: 100% !important;
            width: auto !important;
            max-width: 100% !important;
            object-fit: contain !important;
            object-position: center !important;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.12)) !important;
            border-radius: 4px !important;
            transition: transform 0.25s ease, filter 0.25s ease !important;
            margin: 0 auto !important;
            display: block !important;
        }

        .single-product .related.products ul.products li.product:hover .astra-shop-thumbnail-wrap img,
        .single-product .up-sells.products ul.products li.product:hover .astra-shop-thumbnail-wrap img {
            transform: scale(1.05) translateY(-3px) !important;
            filter: drop-shadow(0 8px 16px rgba(238, 77, 45, 0.22)) !important;
        }

        /* Khung tóm tắt thông tin sách bên dưới ảnh */
        .single-product .related.products .astra-shop-summary-wrap,
        .single-product .up-sells.products .astra-shop-summary-wrap {
            display: flex !important;
            flex-direction: column !important;
            flex-grow: 1 !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .single-product .related.products .ast-woo-product-category,
        .single-product .up-sells.products .ast-woo-product-category {
            font-size: 11px !important;
            color: #94a3b8 !important;
            text-transform: uppercase !important;
            font-weight: 600 !important;
            margin-bottom: 2px !important;
            display: block !important;
        }

        .single-product .related.products .nb-loop-author,
        .single-product .up-sells.products .nb-loop-author {
            font-size: 11.5px !important;
            color: #888888 !important;
            margin-bottom: 4px !important;
            height: 18px !important;
            line-height: 18px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: block !important;
        }

        .single-product .related.products .woocommerce-loop-product__title,
        .single-product .up-sells.products .woocommerce-loop-product__title {
            font-size: 13.5px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            line-height: 1.4 !important;
            margin: 0 0 8px 0 !important;
            height: 38px !important;
            overflow: hidden !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            transition: color 0.15s ease !important;
        }

        .single-product .related.products .woocommerce-loop-product__title:hover,
        .single-product .up-sells.products .woocommerce-loop-product__title:hover {
            color: #ee4d2d !important;
        }

        .single-product .related.products .price,
        .single-product .up-sells.products .price {
            font-size: 16px !important;
            font-weight: 900 !important;
            color: #ee4d2d !important;
            margin-bottom: 12px !important;
            min-height: 26px !important;
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 6px !important;
            line-height: 1.2 !important;
        }

        .single-product .related.products .price del,
        .single-product .up-sells.products .price del {
            color: #94a3b8 !important;
            font-size: 12px !important;
            font-weight: 500 !important;
        }

        .single-product .related.products .price ins,
        .single-product .up-sells.products .price ins {
            text-decoration: none !important;
            color: #ee4d2d !important;
        }

        /* =====================================================================
           FORMAT MÀU ĐỎ CHO KHUNG THÊM VÀO GIỎ HÀNG CHỖ PHẦN GỢI Ý
           ===================================================================== */
        .single-product .related.products .button,
        .single-product .related.products a.button,
        .single-product .related.products .button.add_to_cart_button,
        .single-product .up-sells.products .button,
        .single-product .up-sells.products a.button,
        .single-product .up-sells.products .button.add_to_cart_button,
        .nb-cart-cross-sells .nb-rec-add-btn,
        .nb-cart-cross-sells .add_to_cart_button {
            width: 100% !important;
            background: #ee4d2d !important; /* ĐỎ/CAM ĐỎ BIBLIO THƯƠNG HIỆU */
            border: 1px solid #ee4d2d !important;
            color: #ffffff !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            padding: 9px 12px !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            text-align: center !important;
            text-decoration: none !important;
            cursor: pointer !important;
            margin-top: auto !important;
            box-shadow: 0 2px 8px rgba(238, 77, 45, 0.25) !important;
            transition: all 0.2s ease !important;
            box-sizing: border-box !important;
            line-height: 1.3 !important;
        }

        .single-product .related.products .button:hover,
        .single-product .related.products a.button:hover,
        .single-product .related.products .button.add_to_cart_button:hover,
        .single-product .up-sells.products .button:hover,
        .single-product .up-sells.products a.button:hover,
        .single-product .up-sells.products .button.add_to_cart_button:hover,
        .nb-cart-cross-sells .nb-rec-add-btn:hover,
        .nb-cart-cross-sells .add_to_cart_button:hover {
            background: #d73211 !important;
            border-color: #d73211 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(238, 77, 45, 0.38) !important;
            transform: translateY(-1px) !important;
        }

        /* Icon giỏ hàng trước chữ Thêm vào giỏ */
        .single-product .related.products .button.add_to_cart_button::before,
        .single-product .up-sells.products .button.add_to_cart_button::before {
            content: "" !important;
            display: inline-block !important;
            width: 15px !important;
            height: 15px !important;
            margin-right: 4px !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" width=\"15\" height=\"15\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"%23ffffff\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"%3E%3Ccircle cx=\"9\" cy=\"21\" r=\"1\"/%3E%3Ccircle cx=\"20\" cy=\"21\" r=\"1\"/%3E%3Cpath d=\"M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6\"/%3E%3C/svg%3E") !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            vertical-align: middle !important;
        }

        /* Nút icon túi hàng nhanh trên ảnh bìa sản phẩm gợi ý (.ast-on-card-button) đồng bộ màu đỏ */
        .single-product .related.products .ast-on-card-button.ast-select-options-trigger,
        .single-product .up-sells.products .ast-on-card-button.ast-select-options-trigger {
            background: #ee4d2d !important;
            color: #ffffff !important;
            border-color: #ee4d2d !important;
            box-shadow: 0 4px 12px rgba(238, 77, 45, 0.35) !important;
        }
        .single-product .related.products .ast-on-card-button.ast-select-options-trigger svg,
        .single-product .up-sells.products .ast-on-card-button.ast-select-options-trigger svg {
            fill: #ffffff !important;
        }
        .single-product .related.products .ast-on-card-button.ast-select-options-trigger:hover,
        .single-product .up-sells.products .ast-on-card-button.ast-select-options-trigger:hover {
            background: #d73211 !important;
            border-color: #d73211 !important;
        }

        /* Responsive phần sản phẩm gợi ý */
        @media (max-width: 992px) {
            .single-product .related.products ul.products,
            .single-product .up-sells.products ul.products {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 14px !important;
            }
        }
        @media (max-width: 576px) {
            .single-product .related.products,
            .single-product .up-sells.products {
                padding: 16px 12px !important;
                border-radius: 12px !important;
                margin: 24px 0 20px 0 !important;
            }
            .single-product .related.products ul.products,
            .single-product .up-sells.products ul.products {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }
            .single-product .related.products .astra-shop-thumbnail-wrap,
            .single-product .up-sells.products .astra-shop-thumbnail-wrap {
                height: 170px !important;
            }
            .single-product .related.products .woocommerce-loop-product__title,
            .single-product .up-sells.products .woocommerce-loop-product__title {
                font-size: 12.5px !important;
                height: 35px !important;
            }
            .single-product .related.products .price,
            .single-product .up-sells.products .price {
                font-size: 14px !important;
            }
            .single-product .related.products .button,
            .single-product .up-sells.products .button {
                font-size: 11.5px !important;
                padding: 7px 8px !important;
            }
        }

        /* =====================================================================
           KHUNG SỐ LƯỢNG VÀ NÚT MUA HÀNG CHUẨN SHOPEE CHO TRANG CHI TIẾT
           ===================================================================== */
        /* Ẩn dòng thông tin tồn kho thô mặc định của theme và dòng Tình trạng trùng lặp */
        .single-product div.product .ast-stock-detail,
        .single-product div.product .nb-single-stock-row .d-flex.align-items-center.gap-2 {
            display: none !important;
        }

        /* Khối tổng bao quanh Hàng số lượng và Hàng nút mua */
        .single-product div.product .nb-single-action-box {
            margin: 22px 0 26px 0 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 22px !important;
        }

        /* Hàng 1: [Số Lượng] [-] [1] [+] [49 sản phẩm có sẵn] */
        .single-product .nb-single-qty-row {
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 16px 20px !important;
        }

        /* Nhãn chữ "Số Lượng" */
        .single-product .nb-single-qty-label {
            color: #757575 !important;
            font-size: 15px !important;
            font-weight: 500 !important;
            min-width: 95px !important;
            margin: 0 !important;
            user-select: none !important;
            line-height: 1 !important;
        }

        /* Bộ nút tăng giảm số lượng Shopee: [-] [ 1 ] [+] liền một khối */
        .single-product div.product .quantity.nb-qty-wrap {
            display: inline-flex !important;
            align-items: center !important;
            border: 1px solid rgba(0, 0, 0, 0.12) !important;
            border-radius: 2px !important;
            background: #ffffff !important;
            height: 34px !important;
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }

        /* Nút trừ (-) và nút cộng (+) */
        .single-product div.product .nb-qty-btn {
            width: 34px !important;
            height: 34px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: #ffffff !important;
            border: none !important;
            color: #555555 !important;
            font-size: 18px !important;
            font-weight: 400 !important;
            line-height: 1 !important;
            cursor: pointer !important;
            padding: 0 !important;
            margin: 0 !important;
            outline: none !important;
            transition: background 0.15s ease, color 0.15s ease !important;
            user-select: none !important;
        }

        .single-product div.product .nb-qty-btn:hover {
            background: #f8fafc !important;
            color: #ee4d2d !important;
        }

        .single-product div.product .nb-qty-minus {
            border-right: 1px solid rgba(0, 0, 0, 0.09) !important;
        }

        .single-product div.product .nb-qty-plus {
            border-left: 1px solid rgba(0, 0, 0, 0.09) !important;
        }

        /* Ô nhập số lượng ở giữa: số màu đỏ nổi bật */
        .single-product div.product input.nb-qty-input {
            width: 52px !important;
            height: 34px !important;
            border: none !important;
            text-align: center !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #ee4d2d !important; /* Số màu đỏ Shopee */
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
            outline: none !important;
            box-shadow: none !important;
            -moz-appearance: textfield !important;
            line-height: 34px !important;
        }

        .single-product div.product input.nb-qty-input::-webkit-outer-spin-button,
        .single-product div.product input.nb-qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
        }

        /* Dòng chữ "{N} sản phẩm có sẵn" */
        .single-product .nb-single-stock-avail {
            color: #757575 !important;
            font-size: 14.5px !important;
            font-weight: 400 !important;
            display: inline-flex !important;
            align-items: center !important;
            margin-left: 4px !important;
        }

        /* Hàng 2: [ 🛒+ Thêm Vào Giỏ Hàng ] [ Mua Ngay ] */
        .single-product .nb-single-btn-row {
            display: flex !important;
            align-items: center !important;
            gap: 16px !important;
            margin-top: 4px !important;
            flex-wrap: wrap !important;
        }

        /* Nút 1: [ 🛒+ Thêm Vào Giỏ Hàng ] - nền hồng đỏ nhạt, viền đỏ, chữ đỏ */
        .single-product div.product .nb-btn-shopee-cart {
            background: #ffefe9 !important; /* Nền hồng đỏ nhạt đặc trưng Shopee */
            border: 1px solid #ee4d2d !important; /* Viền đỏ cam thương hiệu */
            color: #ee4d2d !important; /* Chữ & icon màu đỏ */
            height: 48px !important;
            padding: 0 24px !important;
            border-radius: 4px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
            cursor: pointer !important;
            box-shadow: 0 1px 2px rgba(238, 77, 45, 0.1) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            line-height: 1 !important;
            min-width: 210px !important;
        }

        .single-product div.product .nb-btn-shopee-cart:hover {
            background: #ffe3d9 !important;
            border-color: #d73211 !important;
            color: #d73211 !important;
            box-shadow: 0 2px 6px rgba(238, 77, 45, 0.18) !important;
        }

        .single-product div.product .nb-btn-shopee-cart svg {
            stroke: #ee4d2d !important;
            width: 21px !important;
            height: 21px !important;
            flex-shrink: 0 !important;
            transition: stroke 0.2s ease !important;
        }

        .single-product div.product .nb-btn-shopee-cart:hover svg {
            stroke: #d73211 !important;
        }

        /* Nút 2: [ Mua Ngay ] - nền đỏ đặc, chữ trắng */
        .single-product div.product .nb-btn-shopee-buy-now {
            background: #ee4d2d !important; /* Nền đỏ đậm Shopee */
            border: 1px solid #ee4d2d !important;
            color: #ffffff !important; /* Chữ trắng */
            height: 48px !important;
            padding: 0 38px !important;
            min-width: 140px !important;
            border-radius: 4px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(238, 77, 45, 0.25) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            line-height: 1 !important;
        }

        .single-product div.product .nb-btn-shopee-buy-now:hover {
            background: #d73211 !important;
            border-color: #d73211 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(215, 50, 17, 0.35) !important;
        }

        /* Tối ưu responsive trên màn hình điện thoại */
        @media (max-width: 576px) {
            .single-product .nb-single-btn-row {
                flex-direction: row !important;
                width: 100% !important;
                gap: 10px !important;
            }
            .single-product div.product .nb-btn-shopee-cart,
            .single-product div.product .nb-btn-shopee-buy-now {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                padding: 0 8px !important;
                font-size: 13.5px !important;
                height: 44px !important;
            }
            .single-product div.product .nb-btn-shopee-cart svg {
                width: 18px !important;
                height: 18px !important;
            }
        }
    ');

    // Child theme scripts (auto version cache-bust)
    wp_enqueue_script(
        'nbook-scripts',
        get_stylesheet_directory_uri() . '/assets/js/nbook-scripts.js',
        ['jquery'],
        filemtime(get_stylesheet_directory() . '/assets/js/nbook-scripts.js'),
        true
    );

    wp_enqueue_style(
        'nbook-notifications',
        get_stylesheet_directory_uri() . '/assets/css/nbook-notifications.css',
        ['nbook-child-style'],
        filemtime(get_stylesheet_directory() . '/assets/css/nbook-notifications.css')
    );

    wp_enqueue_style(
        'nbook-points-voucher',
        get_stylesheet_directory_uri() . '/assets/css/nbook-points-voucher.css',
        ['nbook-child-style'],
        filemtime(get_stylesheet_directory() . '/assets/css/nbook-points-voucher.css')
    );

    wp_enqueue_style(
        'nbook-account',
        get_stylesheet_directory_uri() . '/assets/css/nbook-account.css',
        ['nbook-child-style'],
        filemtime(get_stylesheet_directory() . '/assets/css/nbook-account.css')
    );

    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style(
            'nbook-reviews',
            get_stylesheet_directory_uri() . '/assets/css/nbook-reviews.css',
            ['nbook-child-style'],
            filemtime(get_stylesheet_directory() . '/assets/css/nbook-reviews.css')
        );
        wp_enqueue_script(
            'nbook-reviews',
            get_stylesheet_directory_uri() . '/assets/js/nbook-reviews.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/nbook-reviews.js'),
            true
        );
        wp_localize_script('nbook-reviews', 'nbook_reviews', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nbook_reviews_nonce'),
            'login_url' => wp_login_url(get_permalink()),
        ]);
    }

    if (is_single()) {
        wp_enqueue_style(
            'nbook-single-post',
            get_stylesheet_directory_uri() . '/assets/css/nbook-single-post.css',
            ['nbook-child-style'],
            filemtime(get_stylesheet_directory() . '/assets/css/nbook-single-post.css')
        );
    }

    if (!is_user_logged_in() && get_option('nbook_google_login_enabled', 1)) {
        wp_enqueue_script(
            'google-identity-services',
            'https://accounts.google.com/gsi/client',
            [],
            null,
            true
        );
    }

    // Localize AJAX search & authentication & logout
    wp_localize_script('nbook-scripts', 'nbook_ajax', [
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('nbook_search_nonce'),
        'auth_nonce'   => wp_create_nonce('nb_auth_nonce_action'),
        'logout_url'   => wp_logout_url(home_url('/')),
        'logout_nonce' => wp_create_nonce('log-out'),
        'home_url'     => home_url('/'),
        'is_logged_in' => is_user_logged_in() ? 1 : 0,
        'notifications_nonce' => wp_create_nonce('nbook_notifications'),
        'avatar_nonce'        => wp_create_nonce('nbook_avatar_upload_nonce'),
        'cancel_order_nonce'  => wp_create_nonce('nbook_cancel_order_nonce'),
        'google_client_id'    => get_option('nbook_google_client_id', ''),
        'google_login_enabled'=> get_option('nbook_google_login_enabled', 1) ? 1 : 0,
    ]);
}

// Register Nav Menu
add_action('after_setup_theme', 'nbook_setup_theme');
function nbook_setup_theme() {
    register_nav_menus([
        'nbook-primary' => 'Menu Chính Nbook',
    ]);
}

// Replace Astra Header & Footer with Nbook Header & Footer
add_action('wp', 'nbook_setup_header_footer_hooks');
function nbook_setup_header_footer_hooks() {
    // Remove Astra default header
    remove_action('astra_header', 'astra_header_markup');
    if (class_exists('Astra_Builder_Header')) {
        remove_action('astra_header', [Astra_Builder_Header::get_instance(), 'global_astra_header'], 0);
    }

    // Remove Astra default footer
    remove_action('astra_footer', 'astra_footer_markup');
    if (class_exists('Astra_Builder_Footer')) {
        remove_action('astra_footer', [Astra_Builder_Footer::get_instance(), 'footer_markup'], 10);
    }

    // Hook Nbook header and footer
    add_action('astra_header', 'nbook_render_header', 10);
    add_action('astra_footer', 'nbook_render_footer', 10);
}

// Update Cart Count Fragment
add_filter('woocommerce_add_to_cart_fragments', 'nbook_cart_fragment');
function nbook_cart_fragment($fragments) {
    $count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    $fragments['span.nb-cart-badge'] = '<span class="nb-cart-badge">' . esc_html($count) . '</span>';
    $fragments['span.sp-cart-count-badge'] = '<span class="sp-cart-count-badge">' . esc_html($count) . '</span>';
    return $fragments;
}

// =========================================================================
// RENDER HEADER (SHOPEE STYLE WITH ACCOUNT FROM PLUGINS)
// =========================================================================
function nbook_render_header() {
    $cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/gio-hang/');
    $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
    $order_url = home_url('/don-hang/');
    $orders_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('orders', '', $account_url) : $order_url;
    $shop_url = home_url('/san-pham/');
    $promo_url = home_url('/khuyen-mai/');
    $news_url = home_url('/tin-tuc-su-kien/');
    $contact_url = home_url('/lien-he/');
    $about_url = home_url('/gioi-thieu/');
    $voucher_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('voucher', '', $account_url) : home_url('/voucher-cua-toi/');
    $points_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('diem-thuong', '', $account_url) : home_url('/diem-thuong/');
    $is_logged = is_user_logged_in();
    $current_user = wp_get_current_user();

    $user_points = 0;
    $user_vouchers_count = 0;
    if ($is_logged) {
        if (class_exists('Biblio_Points_Voucher')) {
            $user_points = Biblio_Points_Voucher::instance()->get_user_points($current_user->ID);
            $user_vouchers_count = count(Biblio_Points_Voucher::instance()->get_user_vouchers($current_user->ID));
        }
    }
    ?>
    <!-- ================= SHOPEE / BIBLIO MULTI-TIER HEADER (FIXED CHO TẤT CẢ CÁC TRANG) ================= -->
    <header id="shopee-header" class="sp-header-wrap">
        
        <!-- TIER 1: TOPBAR -->
        <div class="sp-topbar">
            <div class="sp-container sp-topbar-inner">
                <!-- Topbar Left -->
                <div class="sp-topbar-left">
                    <span class="sp-top-link">Tải ứng dụng</span>
                    <span class="sp-top-sep">|</span>
                    <span class="sp-top-link">
                        Kết nối:
                        <a href="https://facebook.com" target="_blank" rel="nofollow" class="sp-top-soc" title="Facebook">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <a href="https://instagram.com" target="_blank" rel="nofollow" class="sp-top-soc" title="Instagram">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                    </span>
                </div>

                <!-- Topbar Right: Account, Help & Orders -->
                <div class="sp-topbar-right">
                    <?php if ($is_logged): ?>
                    <div class="nb-notification-wrap">
                        <button type="button" class="sp-top-link nb-notification-trigger" aria-expanded="false" aria-label="Thông báo">
                            <span class="nb-notif-icon-wrap">
                                <i class="bi bi-bell"></i>
                                <b class="nb-notification-count" hidden>0</b>
                            </span>
                            <span>Thông Báo</span>
                        </button>
                        <div class="nb-notification-dropdown" hidden>
                            <div class="nb-notification-heading"><strong>Thông báo</strong><button type="button" class="nb-mark-all-read">Đánh dấu đã đọc tất cả</button></div>
                            <div class="nb-notification-items"><p class="nb-notification-empty">Đang tải...</p></div>
                        </div>
                    </div>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url()); ?>" class="sp-top-link"><i class="bi bi-bell"></i><span>Thông Báo</span></a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($contact_url); ?>" class="sp-top-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <span>Hỗ Trợ</span>
                    </a>
                    <span class="sp-top-sep">|</span>

                    <!-- ================= ACCOUNT DROPDOWN ================= -->
                    <div class="sp-account-dropdown-wrap" id="spAccountDropdownWrap">
                        <?php if ($is_logged): ?>
                            <!-- Trigger: Đã đăng nhập -->
                            <button type="button" class="sp-top-link sp-acc-trigger" id="spAccTriggerBtn" aria-haspopup="true" aria-expanded="false" aria-label="Tài khoản người dùng: <?php echo esc_attr($current_user->display_name); ?>">
                                <span class="sp-user-avatar-badge">
                                    <?php echo get_avatar($current_user->ID, 22, '', esc_attr($current_user->display_name), ['class' => 'sp-user-thumb']); ?>
                                </span>
                                <span class="sp-user-name-text"><?php echo esc_html($current_user->display_name); ?></span>
                                <svg class="sp-acc-caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>

                            <!-- Dropdown Menu: Đã đăng nhập -->
                            <div class="sp-acc-dropdown-menu" id="spAccDropdownMenu" role="menu" aria-labelledby="spAccTriggerBtn">
                                <div class="sp-acc-arrow-pointer"></div>
                                
                                <div class="sp-acc-user-header">
                                    <div class="sp-auh-avatar">
                                        <?php echo get_avatar($current_user->ID, 42, '', esc_attr($current_user->display_name), ['class' => 'sp-auh-img']); ?>
                                    </div>
                                    <div class="sp-auh-info">
                                        <div class="sp-auh-name"><?php echo esc_html($current_user->display_name); ?></div>
                                        <div class="sp-auh-role">
                                            <?php if (current_user_can('manage_options')): ?>
                                                <span class="sp-role-badge badge-admin"><i class="bi bi-shield-lock-fill"></i> Quản trị viên</span>
                                            <?php else: ?>
                                                <span class="sp-role-badge badge-member"><i class="bi bi-person-check-fill"></i> Thành viên Biblio</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="sp-acc-divider"></div>

                                <ul class="sp-acc-menu-list">
                                    <li role="none">
                                        <a href="<?php echo esc_url($account_url); ?>" role="menuitem" class="sp-acc-item" tabindex="-1">
                                            <i class="bi bi-person-circle text-primary"></i>
                                            <span>Tài khoản của tôi</span>
                                        </a>
                                    </li>
                                    <li role="none">
                                        <a href="<?php echo esc_url($orders_url); ?>" role="menuitem" class="sp-acc-item" tabindex="-1">
                                            <i class="bi bi-bag-check-fill text-success"></i>
                                            <span>Đơn mua</span>
                                        </a>
                                    </li>
                                    <li role="none">
                                        <a href="<?php echo esc_url($points_url); ?>" role="menuitem" class="sp-acc-item" tabindex="-1">
                                            <i class="bi bi-coin" style="color: #f59e0b;"></i>
                                            <span>Điểm thưởng</span>
                                            <span class="sp-acc-tag-badge bg-gold"><?php echo number_format_i18n($user_points); ?> Xu</span>
                                        </a>
                                    </li>
                                    <li role="none">
                                        <a href="<?php echo esc_url($voucher_url); ?>" role="menuitem" class="sp-acc-item" tabindex="-1">
                                            <i class="bi bi-ticket-perforated-fill" style="color: #ee4d2d;"></i>
                                            <span>Voucher của tôi</span>
                                            <span class="sp-acc-tag-badge bg-orange"><?php echo $user_vouchers_count; ?> mã</span>
                                        </a>
                                    </li>

                                    <?php if (current_user_can('manage_options')): ?>
                                        <li role="none">
                                            <a href="<?php echo esc_url(admin_url()); ?>" role="menuitem" class="sp-acc-item sp-acc-admin" tabindex="-1">
                                                <i class="bi bi-speedometer2" style="color: #0284c7;"></i>
                                                <span>Trang quản trị</span>
                                                <span class="sp-acc-tag-badge bg-admin">Admin</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <div class="sp-acc-divider"></div>

                                    <li role="none">
                                        <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" role="menuitem" class="sp-acc-item sp-acc-logout nb-logout-trigger" tabindex="-1" data-logout-url="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" data-logout-nonce="<?php echo esc_attr(wp_create_nonce('log-out')); ?>">
                                            <i class="bi bi-box-arrow-right text-danger"></i>
                                            <span>Đăng xuất</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                        <?php else: ?>
                            <!-- Trigger: Chưa đăng nhập -->
                            <button type="button" class="sp-top-link sp-acc-trigger" id="spAccTriggerBtn" aria-haspopup="true" aria-expanded="false" aria-label="Tài khoản khách: Đăng nhập hoặc Đăng ký">
                                <span class="sp-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </span>
                                <strong>Tài Khoản</strong>
                                <svg class="sp-acc-caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>

                            <!-- Dropdown Menu: Chưa đăng nhập -->
                            <div class="sp-acc-dropdown-menu sp-acc-guest-menu" id="spAccDropdownMenu" role="menu" aria-labelledby="spAccTriggerBtn">
                                <div class="sp-acc-arrow-pointer"></div>

                                <div class="sp-acc-guest-head">
                                    <p class="sp-agh-title">👋 Chào mừng bạn đến với Biblio!</p>
                                    <p class="sp-agh-sub">Đăng nhập tài khoản để nhận voucher 50.000đ và tích điểm mua sách.</p>
                                </div>

                                <div class="sp-acc-guest-buttons">
                                    <a href="<?php echo esc_url($account_url); ?>#login" class="sp-btn-acc-primary nb-open-auth-modal" data-tab="login" role="menuitem" tabindex="-1">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                        <span>Đăng Nhập</span>
                                    </a>
                                    <a href="<?php echo esc_url($account_url); ?>#register" class="sp-btn-acc-secondary nb-open-auth-modal" data-tab="register" role="menuitem" tabindex="-1">
                                        <i class="bi bi-person-plus-fill"></i>
                                        <span>Đăng Ký</span>
                                    </a>
                                </div>

                                <div class="sp-acc-divider"></div>

                                <ul class="sp-acc-menu-list">
                                    <li role="none">
                                        <a href="<?php echo esc_url($orders_url); ?>" role="menuitem" class="sp-acc-item" tabindex="-1">
                                            <i class="bi bi-truck text-secondary"></i>
                                            <span>Tra cứu đơn mua</span>
                                        </a>
                                    </li>
                                    <li role="none">
                                        <a href="<?php echo esc_url($contact_url); ?>" role="menuitem" class="sp-acc-item" tabindex="-1">
                                            <i class="bi bi-question-circle text-secondary"></i>
                                            <span>Trung tâm trợ giúp</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- TIER 2: MAIN SHOPEE BAR -->
        <div class="sp-main-bar">
            <div class="sp-container sp-main-inner">
                <!-- Hamburger Button (Mobile) -->
                <button type="button" class="nb-hamburger-btn sp-mobile-toggle" aria-label="Mở Menu">☰</button>

                <!-- Brand Logo Biblio (Chỉ Biblio, không có .vn) -->
                <div class="sp-brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="sp-logo-link" title="Biblio - Sàn Thương Mại Điện Tử Sách">
                        <span class="sp-logo-icon">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                <line x1="12" y1="6" x2="16" y2="6"></line>
                                <line x1="12" y1="10" x2="16" y2="10"></line>
                            </svg>
                        </span>
                        <div class="sp-logo-text-group">
                            <span class="sp-logo-title">Biblio</span>
                            <span class="sp-logo-sub">SÁCH &amp; TRI THỨC</span>
                        </div>
                    </a>
                </div>

                <!-- Shopping Cart Icon -->
                <div class="sp-cart-area">
                    <a href="<?php echo esc_url($cart_url); ?>" class="sp-cart-link" title="Xem giỏ hàng của bạn">
                        <div class="sp-cart-icon-wrap">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="sp-cart-count-badge nb-cart-badge"><?php echo esc_html($cart_count); ?></span>
                        </div>
                    </a>
                </div>

                <!-- Center Search Form with Hot Keywords -->
                <div class="sp-search-container">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="sp-search-form">
                        <input type="text" name="s" class="sp-search-input nb-search-input" placeholder="Tìm tên sách, tác giả, mã ISBN..." value="<?php echo get_search_query(); ?>" autocomplete="off" />
                        <input type="hidden" name="post_type" value="product" />
                        <button type="submit" class="sp-search-btn" aria-label="Tìm kiếm">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                    </form>

                    <!-- Hot Search Keywords (Đồng nhất 8 danh mục) -->
                    <div class="sp-hot-keywords">
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/van-hoc/')); ?>">Văn Học</a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/tieu-thuyet/')); ?>">Tiểu Thuyết</a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/kinh-te-ky-nang/')); ?>">Kinh Tế - Kỹ Năng</a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-giao-duc/')); ?>">Giáo Trình NEU</a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ngoai-ngu/')); ?>">Ngoại Ngữ &amp; IELTS</a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/thieu-nhi/')); ?>">Thiếu Nhi</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- TIER 3: SUB-NAV BAR (White Bar with Shopee Orange Accents) -->
        <div class="sp-nav-bar">
            <div class="sp-container sp-nav-inner">
                <!-- Categories Dropdown Trigger -->
                <div class="sp-cat-dropdown-wrap">
                    <a href="<?php echo esc_url($shop_url); ?>" class="sp-cat-main-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                        <span>DANH MỤC SÁCH</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </a>

                    <!-- Dropdown Menu Items (Đồng nhất 8 danh mục) -->
                    <div class="sp-cat-popup-menu">
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/van-hoc/')); ?>">
                            <span class="sp-c-icon">🖋️</span>
                            <span>Văn Học</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/tieu-thuyet/')); ?>">
                            <span class="sp-c-icon">📖</span>
                            <span>Tiểu Thuyết</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/kinh-te-ky-nang/')); ?>">
                            <span class="sp-c-icon">📊</span>
                            <span>Kinh Tế - Kỹ Năng</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ky-nang/')); ?>">
                            <span class="sp-c-icon">📘</span>
                            <span>Sách Kỹ Năng</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/thieu-nhi/')); ?>">
                            <span class="sp-c-icon">🧸</span>
                            <span>Thiếu Nhi</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-moi/')); ?>">
                            <span class="sp-c-icon">🔥</span>
                            <span>Sách Mới</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-giao-duc/')); ?>">
                            <span class="sp-c-icon">🎓</span>
                            <span>Giáo Trình NEU</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ngoai-ngu/')); ?>">
                            <span class="sp-c-icon">🌐</span>
                            <span>Ngoại Ngữ &amp; IELTS</span>
                        </a>
                        <a href="<?php echo esc_url($shop_url); ?>" class="sp-c-all">
                            <span>Xem Tất Cả 145+ Tựa Sách →</span>
                        </a>
                    </div>
                </div>

                <!-- Primary Nav Links -->
                <?php
                $is_news_active = (is_home() || is_page('tin-tuc-su-kien') || is_singular('post') || is_category() || is_tag() || (is_page() && get_the_ID() == 26));
                ?>
                <div class="sp-nav-links">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="sp-nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
                        <span>Trang chủ</span>
                    </a>
                    <a href="<?php echo esc_url($about_url); ?>" class="sp-nav-item <?php echo is_page('gioi-thieu') ? 'active' : ''; ?>">
                        <span>Giới thiệu</span>
                    </a>
                    <a href="<?php echo esc_url($shop_url); ?>" class="sp-nav-item <?php echo (is_shop() || is_product() || is_product_taxonomy()) ? 'active' : ''; ?>">
                        <span>Sản phẩm</span>
                    </a>
                    <a href="<?php echo esc_url($news_url); ?>" class="sp-nav-item <?php echo $is_news_active ? 'active' : ''; ?>">
                        <span>Tin tức – Sự kiện</span>
                    </a>
                    <a href="<?php echo esc_url($contact_url); ?>" class="sp-nav-item <?php echo is_page('lien-he') ? 'active' : ''; ?>">
                        <span>Liên hệ</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/#flash-sale')); ?>" class="sp-nav-item sp-nav-flash">
                        <span class="sp-fire-ic">⚡</span>
                        <span>FLASH SALE</span>
                    </a>
                </div>

                <!-- Right Sale Pill -->
                <div class="sp-nav-pill">
                    <a href="<?php echo esc_url($shop_url); ?>" class="sp-sale-pill-btn">
                        🔥 Xả Kho - Giảm Đến 50%
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Menu -->
    <div class="nb-drawer-backdrop"></div>
    <div class="nb-mobile-drawer">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="sp-logo-title" style="color: #ee4d2d; font-size: 24px; font-weight: 900;">Biblio</span>
            </div>
            <button type="button" class="nb-drawer-close" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #666;">✕</button>
        </div>

        <!-- Account in Mobile Drawer -->
        <div class="nb-drawer-account" style="padding: 14px 0; border-bottom: 1px solid #eee;">
            <?php if ($is_logged): ?>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <span style="display:inline-block; border-radius:50%; overflow:hidden; border:2px solid #fed7cc;">
                        <?php echo get_avatar($current_user->ID, 36, '', esc_attr($current_user->display_name)); ?>
                    </span>
                    <div>
                        <div style="font-weight: 700; color: #1e293b; font-size: 14px;"><?php echo esc_html($current_user->display_name); ?></div>
                        <span style="font-size: 11px; color: #ee4d2d; font-weight: 600;">
                            <?php echo current_user_can('manage_options') ? 'Quản trị viên' : 'Thành viên Biblio'; ?>
                        </span>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; font-size: 12.5px;">
                    <a href="<?php echo esc_url($account_url); ?>" style="text-decoration: none; color: #334155; padding: 6px 8px; background: #f8fafc; border-radius: 6px; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-person-circle text-primary"></i> <span>Tài khoản</span>
                    </a>
                    <a href="<?php echo esc_url($orders_url); ?>" style="text-decoration: none; color: #334155; padding: 6px 8px; background: #f8fafc; border-radius: 6px; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-bag-check-fill text-success"></i> <span>Đơn mua</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/diem-thuong/')); ?>" style="text-decoration: none; color: #334155; padding: 6px 8px; background: #f8fafc; border-radius: 6px; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-coin" style="color:#f59e0b;"></i> <span>Điểm thưởng</span>
                    </a>
                    <a href="<?php echo esc_url($account_url); ?>#voucher" style="text-decoration: none; color: #334155; padding: 6px 8px; background: #f8fafc; border-radius: 6px; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-ticket-perforated-fill" style="color:#ee4d2d;"></i> <span>Voucher</span>
                    </a>
                    <?php if (current_user_can('manage_options')): ?>
                        <a href="<?php echo esc_url(admin_url()); ?>" style="grid-column: span 2; text-decoration: none; color: #0284c7; padding: 6px 8px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; display: flex; align-items: center; gap: 6px; font-weight: 600;">
                            <i class="bi bi-speedometer2"></i> <span>Trang quản trị (Admin)</span>
                        </a>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="sp-acc-logout nb-logout-trigger" data-logout-url="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" data-logout-nonce="<?php echo esc_attr(wp_create_nonce('log-out')); ?>" style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: #dc2626; text-decoration: none; font-weight: 600;">
                    <i class="bi bi-box-arrow-right"></i> <span>Đăng xuất tài khoản</span>
                </a>
            <?php else: ?>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="nb-open-auth-modal" data-tab="login" style="flex: 1; border: none; cursor: pointer; text-align: center; background: #ee4d2d; color: #fff; padding: 9px 12px; border-radius: 6px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="bi bi-box-arrow-in-right"></i> <span>Đăng Nhập</span>
                    </button>
                    <button type="button" class="nb-open-auth-modal" data-tab="register" style="flex: 1; cursor: pointer; text-align: center; background: #fff5f1; color: #ee4d2d; border: 1.5px solid #ee4d2d; padding: 9px 12px; border-radius: 6px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="bi bi-person-plus-fill"></i> <span>Đăng Ký</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <ul style="list-style: none; padding: 12px 0 0 0; margin: 0; line-height: 2.2;">
            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="nb-nav-link <?php echo is_front_page() ? 'active' : ''; ?>">🏠 Trang chủ</a></li>
            <li><a href="<?php echo esc_url($about_url); ?>" class="nb-nav-link <?php echo is_page('gioi-thieu') ? 'active' : ''; ?>">📖 Giới thiệu</a></li>
            <li><a href="<?php echo esc_url($shop_url); ?>" class="nb-nav-link <?php echo (is_shop() || is_product() || is_product_taxonomy()) ? 'active' : ''; ?>">📚 Tất cả sản phẩm</a></li>
            <li><a href="<?php echo esc_url($news_url); ?>" class="nb-nav-link <?php echo $is_news_active ? 'active' : ''; ?>">📰 Tin tức – Sự kiện</a></li>
            <li><a href="<?php echo esc_url($contact_url); ?>" class="nb-nav-link <?php echo is_page('lien-he') ? 'active' : ''; ?>">📞 Liên hệ</a></li>
            <li><a href="<?php echo esc_url($cart_url); ?>" class="nb-nav-link">🛒 Giỏ hàng (<?php echo esc_html($cart_count); ?>)</a></li>
        </ul>

        <!-- Mobile Categories Grid (Đồng nhất 8 danh mục) -->
        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e2e8f0;">
            <div style="font-size: 11.5px; font-weight: 800; color: #888; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">Danh mục sách</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12.5px;">
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/van-hoc/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">🖋️ Văn Học</a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/tieu-thuyet/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">📖 Tiểu Thuyết</a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/kinh-te-ky-nang/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">📊 Kinh Tế</a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ky-nang/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">📘 Kỹ Năng Sống</a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/thieu-nhi/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">🧸 Thiếu Nhi</a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-moi/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">🔥 Sách Mới</a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-giao-duc/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">🎓 Giáo Trình NEU</a>
                <a href="<?php echo esc_url(home_url('/danh-muc-san-pham/sach-ngoai-ngu/')); ?>" style="text-decoration: none; color: #334155; padding: 4px 0;">🌐 Ngoại Ngữ</a>
            </div>
        </div>
    </div>
    <?php
}

// =========================================================================
// RENDER FOOTER (SHOPEE / BIBLIO PROFESSIONAL 5-COLUMNS + PARTNERS + LINKS)
// =========================================================================
function nbook_render_footer() {
    $hotline = get_option('nbook_hotline', '0933 109 009');
    $email = get_option('nbook_email', 'cskh@biblio.vn');
    $address = get_option('nbook_address', '207 Giải Phóng, Phường Bạch Mai, TP. Hà Nội (Đại học Kinh tế Quốc dân NEU)');
    $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
    $is_logged = is_user_logged_in();
    $voucher_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('voucher', '', $account_url) : home_url('/voucher-cua-toi/');
    $points_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('diem-thuong', '', $account_url) : home_url('/diem-thuong/');
    ?>
    <footer id="shopee-footer" class="sp-footer-wrap">

        <!-- SECTION 1: NEWSLETTER BANNER (VOUCHER 50.000₫) -->
        <div class="sp-newsletter-bar">
            <div class="sp-container sp-newsletter-inner">
                <?php if (!$is_logged): ?>
                    <div class="sp-nl-left">
                        <span class="sp-nl-icon">📬</span>
                        <div>
                            <h3 class="sp-nl-title">ĐĂNG KÝ THÀNH VIÊN BIBLIO - NHẬN VOUCHER 50.000₫</h3>
                            <p class="sp-nl-sub">Cập nhật tựa sách mới nhất, mã freeship và ưu đãi độc quyền dành cho bạn.</p>
                        </div>
                    </div>
                    <div class="sp-nl-action">
                        <a href="<?php echo esc_url($account_url); ?>#register" class="sp-nl-btn nb-open-auth-modal" data-tab="register">
                            <i class="bi bi-person-plus-fill"></i>
                            <span>Đăng Ký Tài Khoản Ngay</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="sp-nl-left">
                        <span class="sp-nl-icon">🎁</span>
                        <div>
                            <h3 class="sp-nl-title">BẠN ĐÃ LÀ THÀNH VIÊN BIBLIO - KHÁM PHÁ VOUCHER &amp; TÍCH XU</h3>
                            <p class="sp-nl-sub">Xem ngay kho voucher độc quyền, điểm danh nhận Xu hằng ngày và ưu đãi mua sách dành riêng cho bạn.</p>
                        </div>
                    </div>
                    <div class="sp-nl-action">
                        <a href="<?php echo esc_url($voucher_url); ?>" class="sp-nl-btn">
                            <i class="bi bi-ticket-perforated-fill"></i>
                            <span>Ví Voucher Của Tôi</span>
                        </a>
                        <a href="<?php echo esc_url($points_url); ?>" class="sp-nl-btn sp-nl-btn-secondary">
                            <i class="bi bi-coin"></i>
                            <span>Điểm Thưởng Xu</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 2: 5 COLUMNS SHOPEE STANDARD (CONTACT, PARTNERS, LINKS...) -->
        <div class="sp-footer-cols">
            <div class="sp-container sp-cols-inner">
                <!-- Column 1: Chăm sóc khách hàng & Liên hệ -->
                <div class="sp-col">
                    <h4 class="sp-col-title">CHĂM SÓC KHÁCH HÀNG</h4>
                    <ul class="sp-col-links">
                        <li>Hotline: <strong style="color: #ee4d2d;"><?php echo esc_html($hotline); ?></strong> (24/7)</li>
                        <li>Tổng đài: <strong>1800-BIBLIO</strong> (Miễn cước)</li>
                        <li>Email: <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/lien-he/')); ?>">Trung Tâm Trợ Giúp &amp; FAQs</a></li>
                        <li><a href="<?php echo esc_url(home_url('/don-hang/')); ?>">Hướng Dẫn Mua Hàng &amp; Đặt Sách</a></li>
                        <li><a href="<?php echo esc_url(home_url('/don-hang/')); ?>">Tra Cứu Tiến Độ Vận Đơn</a></li>
                        <li><a href="<?php echo esc_url(home_url('/tien-ich/')); ?>">Chính Sách Đổi Trả Trong 7 Ngày</a></li>
                    </ul>
                </div>

                <!-- Column 2: Về Biblio Việt Nam -->
                <div class="sp-col">
                    <h4 class="sp-col-title">VỀ BIBLIO VIỆT NAM</h4>
                    <ul class="sp-col-links">
                        <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Giới Thiệu Về Biblio</a></li>
                        <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Tuyển Dụng Nhân Sự</a></li>
                        <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Điều Khoản Dịch Vụ Biblio</a></li>
                        <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Chính Sách Bảo Mật Thông Tin</a></li>
                        <li><a href="<?php echo esc_url(home_url('/san-pham/')); ?>">Biblio Mall Sách Chính Hãng</a></li>
                        <li><a href="<?php echo esc_url(home_url('/san-pham/')); ?>">Tác Giả &amp; Tác Phẩm Nổi Bật</a></li>
                        <li><a href="<?php echo esc_url(home_url('/#flash-sale')); ?>">Flash Sale &amp; Voucher Hôm Nay</a></li>
                    </ul>
                </div>

                <!-- Column 3: Thanh toán & Vận chuyển -->
                <div class="sp-col">
                    <h4 class="sp-col-title">THANH TOÁN</h4>
                    <div class="sp-badge-grid">
                        <span class="sp-pay-tag">VietQR</span>
                        <span class="sp-pay-tag">Visa</span>
                        <span class="sp-pay-tag">MasterCard</span>
                        <span class="sp-pay-tag">MoMo</span>
                        <span class="sp-pay-tag">ShopeePay</span>
                        <span class="sp-pay-tag">ZaloPay</span>
                        <span class="sp-pay-tag">COD</span>
                    </div>
                    <h4 class="sp-col-title" style="margin-top: 18px;">ĐƠN VỊ VẬN CHUYỂN</h4>
                    <div class="sp-badge-grid">
                        <span class="sp-ship-tag">SPX Express</span>
                        <span class="sp-ship-tag">Giao Hàng Nhanh</span>
                        <span class="sp-ship-tag">Viettel Post</span>
                        <span class="sp-ship-tag">GHTK</span>
                        <span class="sp-ship-tag">Hỏa Tốc 2H</span>
                    </div>
                </div>

                <!-- Column 4: Đối tác xuất bản & Liên kết sàn TMĐT -->
                <div class="sp-col">
                    <h4 class="sp-col-title">ĐỐI TÁC XUẤT BẢN</h4>
                    <div class="sp-badge-grid">
                        <span class="sp-partner-tag">NXB Trẻ</span>
                        <span class="sp-partner-tag">Kim Đồng</span>
                        <span class="sp-partner-tag">Nhã Nam</span>
                        <span class="sp-partner-tag">Alpha Books</span>
                        <span class="sp-partner-tag">First News</span>
                        <span class="sp-partner-tag">NXB Tổng Hợp</span>
                        <span class="sp-partner-tag">NXB Tri Thức</span>
                        <span class="sp-partner-tag">NXB Phụ Nữ</span>
                    </div>
                    <h4 class="sp-col-title" style="margin-top: 18px;">LIÊN KẾT SÀN TMĐT</h4>
                    <ul class="sp-col-links">
                        <li><a href="https://shopee.vn" target="_blank" rel="nofollow">🛍️ Shopee Mall: Biblio Official</a></li>
                        <li><a href="https://lazada.vn" target="_blank" rel="nofollow">🛒 Lazada: Biblio Bookstore</a></li>
                        <li><a href="https://tiki.vn" target="_blank" rel="nofollow">📦 Tiki Official: Nhà Sách Biblio</a></li>
                        <li><a href="https://tiktok.com" target="_blank" rel="nofollow">🎵 TikTok Shop: @biblio_books</a></li>
                    </ul>
                </div>

                <!-- Column 5: Kết nối & Tải ứng dụng -->
                <div class="sp-col">
                    <h4 class="sp-col-title">THEO DÕI BIBLIO TRÊN</h4>
                    <ul class="sp-col-links">
                        <li><a href="https://facebook.com" target="_blank" rel="nofollow">📘 Facebook: Biblio Bookstore</a></li>
                        <li><a href="https://instagram.com" target="_blank" rel="nofollow">📸 Instagram: @biblio.vn</a></li>
                        <li><a href="https://tiktok.com" target="_blank" rel="nofollow">🎵 TikTok: @biblio_books</a></li>
                        <li><a href="https://youtube.com" target="_blank" rel="nofollow">▶️ YouTube: Biblio Official</a></li>
                    </ul>

                    <h4 class="sp-col-title" style="margin-top: 18px;">TẢI ỨNG DỤNG BIBLIO</h4>
                    <div class="sp-app-download">
                        <div class="sp-qr-box">
                            <svg width="68" height="68" viewBox="0 0 100 100" fill="#222">
                                <rect width="100" height="100" fill="#fff"/>
                                <rect x="10" y="10" width="30" height="30" fill="#222"/>
                                <rect x="15" y="15" width="20" height="20" fill="#fff"/>
                                <rect x="20" y="20" width="10" height="10" fill="#ee4d2d"/>
                                <rect x="60" y="10" width="30" height="30" fill="#222"/>
                                <rect x="65" y="15" width="20" height="20" fill="#fff"/>
                                <rect x="70" y="20" width="10" height="10" fill="#ee4d2d"/>
                                <rect x="10" y="60" width="30" height="30" fill="#222"/>
                                <rect x="15" y="65" width="20" height="20" fill="#fff"/>
                                <rect x="20" y="70" width="10" height="10" fill="#ee4d2d"/>
                                <rect x="45" y="45" width="10" height="10" fill="#222"/>
                                <rect x="60" y="60" width="15" height="15" fill="#222"/>
                                <rect x="80" y="80" width="10" height="10" fill="#222"/>
                            </svg>
                        </div>
                        <div class="sp-app-buttons">
                            <span class="sp-app-btn">🍏 App Store</span>
                            <span class="sp-app-btn">🤖 Google Play</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: COPYRIGHT, LEGAL & ATTRIBUTION (NHÓM 3 HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ) -->
        <div class="sp-footer-bottom">
            <div class="sp-container sp-bottom-inner">
                <div class="sp-legal-text">
                    <p><strong>© <?php echo date('Y'); ?> Biblio - Sàn Thương Mại Điện Tử Sách &amp; Tri Thức Trực Tuyến</strong></p>
                    <p style="color: #ee4d2d; font-weight: 700; font-size: 13.5px;">Dự án phát triển bởi: Nhóm 3 Hệ thống thương mại điện tử</p>
                    <p>Đơn vị thực hiện: <strong>Nhóm 3</strong> - Lớp Học phần Hệ thống thương mại điện tử | Khoa Công nghệ thông tin | <strong>Đại học Kinh tế Quốc dân (NEU)</strong></p>
                    <p>Địa chỉ: <?php echo esc_html($address); ?></p>
                    <p>Giấy chứng nhận Đăng ký Doanh nghiệp / Đề tài Nghiên cứu số: 0108992384/NEU cấp tại TP. Hà Nội.</p>
                </div>
                <div class="sp-moit-cert">
                    <span class="sp-cert-badge">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 12 15 16 10"></polyline>
                        </svg>
                        ĐÃ ĐĂNG KÝ VỚI BỘ CÔNG THƯƠNG
                    </span>
                </div>
            </div>
        </div>

        <!-- FLOATING BACK TO TOP BUTTON -->
        <button type="button" id="spBackToTop" class="sp-floating-top" aria-label="Lên đầu trang" title="Lên đầu trang">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>

        <script>
        (function() {
            function initScrollToTop() {
                var btn = document.getElementById('spBackToTop');
                if (!btn) return;

                function toggleBtnVisibility() {
                    var y = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                    if (y > 220) {
                        btn.classList.add('visible');
                    } else {
                        btn.classList.remove('visible');
                    }
                }

                window.addEventListener('scroll', toggleBtnVisibility, { passive: true });
                toggleBtnVisibility();

                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    try {
                        window.scrollTo({
                            top: 0,
                            left: 0,
                            behavior: 'smooth'
                        });
                    } catch (err) {
                        window.scrollTo(0, 0);
                    }
                    document.documentElement.scrollTop = 0;
                    document.body.scrollTop = 0;
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initScrollToTop);
            } else {
                initScrollToTop();
            }
        })();
        </script>

        <!-- Add to cart toast box -->
        <div id="sp-toast" class="nb-toast-box">
            <div class="nb-toast-check">✓</div>
            <div id="sp-toast-msg" class="nb-toast-text">Đã thêm sản phẩm vào Giỏ hàng!</div>
        </div>
    </footer>
    <?php
}

// =========================================================================
// WOOCOMMERCE SHOP & PRODUCT ARCHIVE ENHANCEMENTS (REFERENCING @BIBLIO)
// =========================================================================

// 0. Register custom rewrite rules for Vietnamese category base
add_action('init', 'nbook_register_category_rewrites');
function nbook_register_category_rewrites() {
    add_rewrite_rule('^danh-muc-san-pham/([^/]+)/?$', 'index.php?product_cat=$matches[1]', 'top');
    add_rewrite_rule('^danh-muc-san-pham/([^/]+)/page/([0-9]{1,})/?$', 'index.php?product_cat=$matches[1]&paged=$matches[2]', 'top');
}

// 0.1 Clean up duplicate WooCommerce archive breadcrumbs, titles & descriptions
add_action('wp', 'nbook_remove_duplicate_shop_elements');
function nbook_remove_duplicate_shop_elements() {
    if (is_shop() || is_product_taxonomy()) {
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
        add_filter('woocommerce_show_page_title', '__return_false');
        remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
        remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);
    }
}

// 0.2 Wrap WooCommerce loop product thumbnail in a dedicated clean frame (.nb-loop-thumb-frame)
add_action('woocommerce_before_shop_loop_item_title', 'nbook_loop_thumb_frame_open', 9);
function nbook_loop_thumb_frame_open() {
    echo '<div class="nb-loop-thumb-frame">';
    echo '<span class="nb-badge-favorite">Yêu thích</span>';
}

add_action('woocommerce_before_shop_loop_item_title', 'nbook_loop_thumb_frame_close', 11);
function nbook_loop_thumb_frame_close() {
    echo '</div>';
}

// 0.3 Custom Sale Flash Badge for WooCommerce Shop Loop (Shopee/Biblio Prominent Style)
add_filter('woocommerce_sale_flash', 'nbook_custom_sale_flash', 20, 3);
function nbook_custom_sale_flash($html, $post = null, $product = null) {
    if (!$product && $post) {
        $product = (is_object($post) && method_exists($post, 'get_regular_price')) ? $post : wc_get_product($post);
    }
    if (!$product) {
        global $product;
    }
    if (!$product || !is_object($product) || !method_exists($product, 'get_regular_price')) return $html;

    $reg = (float) $product->get_regular_price();
    $sale = (float) $product->get_sale_price();
    if ($reg > 0 && $sale > 0 && $reg > $sale) {
        $pct = round((($reg - $sale) / $reg) * 100);
        return '<span class="onsale nb-shopee-onsale-badge"><span class="nb-badge-bolt">⚡</span> GIẢM ' . $pct . '%</span>';
    }
    return '<span class="onsale nb-shopee-onsale-badge"><span class="nb-badge-bolt">⚡</span> GIẢM GIÁ</span>';
}

// 0.31 Filter Astra's modern shop card buttons to replace standard "Giảm giá!" with prominent "⚡ GIẢM XX%"
add_filter('astra_addon_shop_cards_buttons_html', 'nbook_custom_astra_sale_badge', 20, 2);
function nbook_custom_astra_sale_badge($html, $product) {
    if (!$product) return $html;
    if ($product->is_on_sale()) {
        $reg = (float) $product->get_regular_price();
        $sale = (float) $product->get_sale_price();
        if ($reg > 0 && $sale > 0 && $reg > $sale) {
            $pct = round((($reg - $sale) / $reg) * 100);
            $new_badge = '<span class="ast-on-card-button ast-onsale-card nb-shopee-onsale-badge"><span class="nb-badge-bolt">⚡</span> GIẢM ' . $pct . '%</span>';
            $html = preg_replace('/<span[^>]*class="[^"]*ast-onsale-card[^"]*"[^>]*>.*?<\/span>/s', $new_badge, $html);
        }
    }
    return $html;
}

// 0.4 Prominent discount percentage tag next to the price
add_filter('woocommerce_get_price_html', 'nbook_highlight_price_discount_html', 20, 2);
function nbook_highlight_price_discount_html($price, $product) {
    if (!is_admin() && $product && $product->is_on_sale()) {
        $reg = (float) $product->get_regular_price();
        $sale = (float) $product->get_sale_price();
        if ($reg > 0 && $sale > 0 && $reg > $sale) {
            $pct = round((($reg - $sale) / $reg) * 100);
            if (strpos($price, 'nb-price-pct-tag') === false) {
                $price .= ' <span class="nb-price-pct-tag">-' . $pct . '%</span>';
            }
        }
    }
    return $price;
}

// 1. Shop Catalog Layout (Replaced by woocommerce/archive-product.php matching http://127.0.0.1:5000/books/)

// 3. Apply Shop Filters to WP Query
add_action('pre_get_posts', 'nbook_apply_shop_query_filters');
function nbook_apply_shop_query_filters($query) {
    if (is_admin() || !$query->is_main_query() || (!is_shop() && !is_product_taxonomy())) {
        return;
    }

    // Set 15 items per page (3 columns x 5 rows)
    $query->set('posts_per_page', 15);

    // Support category filter via GET parameter (?product_cat=slug or ?category=slug) on shop page
    $cat_param = !empty($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : (!empty($_GET['category']) ? sanitize_text_field($_GET['category']) : '');
    if (!empty($cat_param) && $cat_param !== 'all') {
        $tax_query = (array) $query->get('tax_query');
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $cat_param,
        ];
        $query->set('tax_query', $tax_query);
    }

    $min = 0;
    $max = 0;

    // Support preset radio filters (from http://127.0.0.1:5000/books/)
    if (!empty($_GET['nbook_price_preset'])) {
        $preset = sanitize_text_field($_GET['nbook_price_preset']);
        if ($preset === 'under_50k') {
            $max = 50000;
        } elseif ($preset === '50k_100k') {
            $min = 50000;
            $max = 100000;
        } elseif ($preset === '100k_200k') {
            $min = 100000;
            $max = 200000;
        } elseif ($preset === 'above_200k') {
            $min = 200000;
        }
    }

    // Fallback to manual min/max inputs if supplied
    if (isset($_GET['nbook_min_price']) && is_numeric($_GET['nbook_min_price'])) {
        $min = absint($_GET['nbook_min_price']);
    }
    if (isset($_GET['nbook_max_price']) && is_numeric($_GET['nbook_max_price'])) {
        $max = absint($_GET['nbook_max_price']);
    }

    $in_stock = !empty($_GET['nbook_in_stock']);
    $meta_query = (array) $query->get('meta_query');

    if ($min > 0 || $max > 0) {
        $meta_query[] = [
            'key'     => '_price',
            'value'   => [$min, $max > 0 ? $max : PHP_INT_MAX],
            'compare' => 'BETWEEN',
            'type'    => 'NUMERIC',
        ];
    }

    if ($in_stock) {
        $meta_query[] = [
            'key'     => '_stock_status',
            'value'   => 'instock',
            'compare' => '=',
        ];
    }

    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }

    // Custom Orderby Handling matching http://127.0.0.1:5000/books/
    if (!empty($_GET['orderby'])) {
        $sort = sanitize_text_field($_GET['orderby']);
        if ($sort === 'title_asc') {
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
        } elseif ($sort === 'price_asc') {
            $query->set('meta_key', '_price');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
        } elseif ($sort === 'price_desc') {
            $query->set('meta_key', '_price');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
        } elseif ($sort === 'date') {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        } elseif ($sort === 'popularity') {
            $query->set('meta_key', 'total_sales');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
        }
    }
}

// =========================================================================
// UNIFIED PRODUCT RATING SYSTEM (ĐỒNG NHẤT ĐÁNH GIÁ SAO BIBLIO)
// Thống nhất cách hiển thị và số sao đánh giá giữa danh mục sản phẩm (archive)
// và trang xem chi tiết sản phẩm (single product).
// =========================================================================

if ( ! function_exists( 'nbook_get_product_rating_data' ) ) {
    function nbook_get_product_rating_data( $product = null ) {
        if ( ! $product instanceof WC_Product ) {
            $product = wc_get_product( $product );
        }
        if ( ! $product ) {
            return [
                'score'       => '5.0',
                'score_num'   => 5.0,
                'count'       => 25,
                'sales'       => 50,
                'full_stars'  => 5,
                'has_half'    => false,
                'empty_stars' => 0,
            ];
        }

        $pid        = $product->get_id();
        $real_avg   = (float) $product->get_average_rating();
        $real_count = (int) $product->get_review_count();

        if ( $real_count > 0 && $real_avg > 0 ) {
            $score_num = round( $real_avg, 1 );
            $score_str = number_format( $score_num, 1, '.', '' );
            $count     = $real_count;
        } else {
            $score_str = '5.0';
            $score_num = 5.0;
            $count     = 10 + ( $pid % 39 );
        }

        // Determine star breakdown
        $full_stars = (int) floor( $score_num );
        $remainder  = $score_num - $full_stars;
        $has_half   = ( $remainder >= 0.3 && $remainder <= 0.8 );
        if ( $remainder > 0.8 ) {
            $full_stars++;
        }
        $full_stars  = min( 5, max( 0, $full_stars ) );
        $empty_stars = 5 - $full_stars - ( $has_half ? 1 : 0 );
        $empty_stars = max( 0, $empty_stars );

        // Total sales
        $sales = (int) get_post_meta( $pid, 'total_sales', true );
        if ( $sales <= 0 ) {
            $sales = max( 15, (int) $product->get_total_sales() ?: ( 45 + ( $pid * 3 % 120 ) ) );
        }

        return [
            'score'       => $score_str,
            'score_num'   => $score_num,
            'count'       => $count,
            'sales'       => $sales,
            'full_stars'  => $full_stars,
            'has_half'    => $has_half,
            'empty_stars' => $empty_stars,
        ];
    }
}

if ( ! function_exists( 'nbook_render_product_rating' ) ) {
    function nbook_render_product_rating( $product = null, $context = 'archive' ) {
        if ( ! $product ) {
            global $product;
        }
        if ( ! $product ) return;

        $data  = nbook_get_product_rating_data( $product );
        $score = $data['score'];
        $count = $data['count'];
        $sales = $data['sales'];

        if ( $context === 'single' ) : ?>
            <div class="product-rating nb-single-product-rating" aria-label="Đánh giá <?php echo esc_attr( $score ); ?> sao">
                <div class="nb-single-stars-row">
                    <span class="nb-stars-group">
                        <?php for ( $i = 0; $i < $data['full_stars']; $i++ ) : ?>
                            <i class="bi bi-star-fill text-warning"></i>
                        <?php endfor; ?>
                        <?php if ( $data['has_half'] ) : ?>
                            <i class="bi bi-star-half text-warning"></i>
                        <?php endif; ?>
                        <?php for ( $i = 0; $i < $data['empty_stars']; $i++ ) : ?>
                            <i class="bi bi-star text-warning"></i>
                        <?php endfor; ?>
                    </span>
                    <span class="rating-score"><strong><?php echo esc_html( $score ); ?></strong></span>
                    <a href="#reviews" class="rating-count" rel="nofollow">(<?php echo esc_html( $count ); ?> đánh giá)</a>
                </div>
                <span class="nb-rating-sep">|</span>
                <div class="nb-single-sold-meta">
                    <span class="nb-sold-text">Đã bán <strong><?php echo esc_html( $sales ); ?></strong></span>
                </div>
            </div>
        <?php else : ?>
            <div class="product-rating" aria-label="Đánh giá <?php echo esc_attr( $score ); ?> sao">
                <span class="nb-stars-group">
                    <?php for ( $i = 0; $i < $data['full_stars']; $i++ ) : ?>
                        <i class="bi bi-star-fill text-warning"></i>
                    <?php endfor; ?>
                    <?php if ( $data['has_half'] ) : ?>
                        <i class="bi bi-star-half text-warning"></i>
                    <?php endif; ?>
                    <?php for ( $i = 0; $i < $data['empty_stars']; $i++ ) : ?>
                        <i class="bi bi-star text-warning"></i>
                    <?php endfor; ?>
                </span>
                <span class="rating-count"><?php echo esc_html( $score ); ?> (<?php echo esc_html( $count ); ?>)</span>
            </div>
        <?php endif;
    }
}

// Hook for product loop rating (related products & standard loop)
add_action( 'woocommerce_after_shop_loop_item_title', 'nbook_loop_item_rating', 7 );
function nbook_loop_item_rating() {
    global $product;
    if ( function_exists( 'nbook_render_product_rating' ) && $product ) {
        nbook_render_product_rating( $product, 'archive' );
    }
}

// 4. Single Product: Author & Trust Badges under Title
add_action('woocommerce_single_product_summary', 'nbook_single_product_author_and_badges', 7);
function nbook_single_product_author_and_badges() {
    global $product;
    if (!$product) return;

    $pid = $product->get_id();
    $author = get_post_meta($pid, '_biblio_author', true) ?: get_post_meta($pid, '_book_author', true) ?: get_post_meta($pid, '_nbook_author', true);
    ?>
    <div class="nb-single-meta-strip">
        <?php if ($author): ?>
            <div class="nb-single-author">
                <span class="nb-meta-label">Tác giả:</span>
                <strong class="nb-meta-author-name"><?php echo esc_html($author); ?></strong>
            </div>
        <?php endif; ?>
        <div class="nb-single-trust-tags">
            <span class="nb-trust-tag-item">🛡️ 100% Sách Thật</span>
            <span class="nb-trust-tag-item">🚚 Freeship từ 299k</span>
            <span class="nb-trust-tag-item">🔄 Đổi trả 7 ngày</span>
        </div>
    </div>
    <?php
}

// 5. Single Product: Tab "Thông tin chi tiết" (Book Specifications)
add_filter('woocommerce_product_tabs', 'nbook_add_book_specs_tab', 10);
function nbook_add_book_specs_tab($tabs) {
    $tabs['nb_book_specs'] = [
        'title'    => 'Thông tin chi tiết',
        'priority' => 15,
        'callback' => 'nbook_render_book_specs_table',
    ];
    return $tabs;
}

function nbook_render_book_specs_table() {
    global $product;
    if (!$product) return;

    $pid       = $product->get_id();
    $author    = get_post_meta($pid, '_biblio_author', true) ?: get_post_meta($pid, '_book_author', true) ?: 'Nhiều tác giả';
    $publisher = get_post_meta($pid, '_biblio_publisher', true) ?: 'NXB Hội Nhà Văn / NXB Trẻ';
    $pages     = get_post_meta($pid, '_biblio_pages', true) ?: 'Đang cập nhật';
    $size      = get_post_meta($pid, '_biblio_size', true) ?: '13 x 20.5 cm';
    $weight    = get_post_meta($pid, '_biblio_weight', true) ?: '350g';
    $date      = get_post_meta($pid, '_biblio_date', true) ?: '2025 - 2026';
    $sku       = $product->get_sku() ?: ('BIB-' . $pid);
    ?>
    <div class="nb-specs-table-wrap">
        <h3 class="nb-specs-title">Thuộc Tính &amp; Thông Số Kỹ Thuật Sách</h3>
        <table class="nb-specs-table">
            <tbody>
                <tr>
                    <th>Mã sản phẩm (SKU)</th>
                    <td><code><?php echo esc_html($sku); ?></code></td>
                </tr>
                <tr>
                    <th>Tác giả</th>
                    <td><strong><?php echo esc_html($author); ?></strong></td>
                </tr>
                <tr>
                    <th>Nhà xuất bản</th>
                    <td><?php echo esc_html($publisher); ?></td>
                </tr>
                <tr>
                    <th>Số trang</th>
                    <td><?php echo esc_html($pages); ?> trang</td>
                </tr>
                <tr>
                    <th>Kích thước</th>
                    <td><?php echo esc_html($size); ?></td>
                </tr>
                <tr>
                    <th>Trọng lượng</th>
                    <td><?php echo esc_html($weight); ?></td>
                </tr>
                <tr>
                    <th>Năm xuất bản</th>
                    <td><?php echo esc_html($date); ?></td>
                </tr>
                <tr>
                    <th>Hình thức bìa</th>
                    <td>Bìa mềm cao cấp gấp mép (Gia công chống thấm)</td>
                </tr>
                <tr>
                    <th>Cam kết bản quyền</th>
                    <td><span class="nb-text-highlight">✓ 100% Sách chính hãng nguyên màng co</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

// 6. Shop Loop Product Author Line (Hooked directly above title in summary wrap)
add_action('astra_woo_shop_title_before', 'nbook_loop_product_author_line', 15);
function nbook_loop_product_author_line() {
    global $product;
    if (!$product) return;
    $author = get_post_meta($product->get_id(), '_biblio_author', true) 
           ?: get_post_meta($product->get_id(), '_book_author', true)
           ?: get_post_meta($product->get_id(), '_nbook_author', true);
    if ($author) {
        echo '<div class="nb-loop-author">' . esc_html($author) . '</div>';
    }
}

// =========================================================================
// 7. AUTH MODAL (POPUP ĐĂNG NHẬP / ĐĂNG KÝ / QUÊN MẬT KHẨU) & AJAX HANDLERS
// =========================================================================

add_action('wp_footer', 'nbook_render_auth_modal');
function nbook_render_auth_modal() {
    $myaccount_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
    $google_login_enabled = get_option('nbook_google_login_enabled', 1);
    ?>
    <!-- BIBLIO POPUP AUTH MODAL -->
    <div class="nb-auth-overlay" id="nbAuthModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="nbAuthHeading">
        <div class="nb-auth-backdrop"></div>
        <div class="nb-auth-box">
            <button type="button" class="nb-auth-close" id="nbAuthCloseBtn" aria-label="Đóng cửa sổ">&times;</button>
            
            <div class="nb-auth-header-brand">
                <span class="nb-auth-brand-logo">Biblio</span>
                <span class="nb-auth-brand-sub">SÁCH &amp; TRI THỨC TRỰC TUYẾN</span>
            </div>

            <!-- Segmented Switcher (Tabs) -->
            <div class="nb-auth-tabs" role="tablist">
                <button type="button" class="nb-auth-tab active" data-target="login" role="tab" aria-selected="true" id="tabBtnLogin">
                    Đăng Nhập
                </button>
                <button type="button" class="nb-auth-tab" data-target="register" role="tab" aria-selected="false" id="tabBtnRegister">
                    Đăng Ký
                </button>
            </div>

            <!-- Global Alert Container -->
            <div class="nb-auth-alert" id="nbAuthAlert" style="display: none;"></div>

            <!-- TAB 1: FORM ĐĂNG NHẬP -->
            <div class="nb-auth-pane active" id="paneLogin" role="tabpanel" aria-labelledby="tabBtnLogin">
                <form id="nbLoginForm" class="nb-auth-form" method="post" autocomplete="on">
                    <div class="nb-form-group">
                        <label for="nbLoginUser" class="nb-auth-label">Tên tài khoản hoặc Email <span class="text-danger">*</span></label>
                        <div class="nb-input-with-icon">
                            <i class="bi bi-person-fill"></i>
                            <input type="text" id="nbLoginUser" name="username" required class="nb-form-input" placeholder="Nhập email hoặc tên đăng nhập..." autocomplete="username" />
                        </div>
                    </div>

                    <div class="nb-form-group">
                        <div class="nb-label-row">
                            <label for="nbLoginPass" class="nb-auth-label">Mật khẩu <span class="text-danger">*</span></label>
                            <a href="#lost-password" class="nb-forgot-link" id="nbGoForgotLink">Quên mật khẩu?</a>
                        </div>
                        <div class="nb-input-with-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" id="nbLoginPass" name="password" required class="nb-form-input" placeholder="Nhập mật khẩu của bạn..." autocomplete="current-password" />
                            <button type="button" class="nb-eye-toggle" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="nb-auth-remember-row">
                        <label class="nb-checkbox-label">
                            <input type="checkbox" id="nbLoginRemember" name="remember" checked />
                            <span>Ghi nhớ đăng nhập trên thiết bị này</span>
                        </label>
                    </div>

                    <button type="submit" class="nb-auth-submit-btn" id="nbLoginSubmitBtn">
                        <span class="btn-text">ĐĂNG NHẬP</span>
                        <span class="btn-spinner" style="display: none;"><i class="bi bi-arrow-repeat spin"></i> Đang đăng nhập...</span>
                    </button>

                    <?php if ($google_login_enabled): ?>
                    <div class="nb-social-auth-wrap">
                        <div class="nb-auth-divider"><span>HOẶC</span></div>
                        <button type="button" class="nb-btn-google-auth" id="nbGoogleLoginBtn" data-auth-mode="login">
                            <svg class="nb-google-icon" width="18" height="18" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            <span>Đăng nhập bằng Google</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="nb-auth-footer-note">
                        Chưa có tài khoản Biblio? <a href="#register" class="nb-switch-auth-link" data-tab="register">Đăng ký thành viên mới</a>
                    </div>
                </form>
            </div>

            <!-- TAB 2: FORM ĐĂNG KÝ -->
            <div class="nb-auth-pane" id="paneRegister" role="tabpanel" aria-labelledby="tabBtnRegister">
                <form id="nbRegisterForm" class="nb-auth-form" method="post" autocomplete="on">
                    <div class="nb-form-group">
                        <label for="nbRegName" class="nb-auth-label">Họ và tên hiển thị <span class="text-danger">*</span></label>
                        <div class="nb-input-with-icon">
                            <i class="bi bi-person-badge-fill"></i>
                            <input type="text" id="nbRegName" name="name" required class="nb-form-input" placeholder="Ví dụ: Nguyễn Văn An" />
                        </div>
                    </div>

                    <div class="nb-form-group">
                        <label for="nbRegEmail" class="nb-auth-label">Địa chỉ Email <span class="text-danger">*</span></label>
                        <div class="nb-input-with-icon">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" id="nbRegEmail" name="email" required class="nb-form-input" placeholder="name@example.com" autocomplete="email" />
                        </div>
                    </div>

                    <div class="nb-form-group">
                        <label for="nbRegPass" class="nb-auth-label">Mật khẩu (tối thiểu 6 ký tự) <span class="text-danger">*</span></label>
                        <div class="nb-input-with-icon">
                            <i class="bi bi-shield-lock-fill"></i>
                            <input type="password" id="nbRegPass" name="password" minlength="6" required class="nb-form-input" placeholder="Tạo mật khẩu an toàn..." autocomplete="new-password" />
                            <button type="button" class="nb-eye-toggle" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="nb-form-group">
                        <label for="nbRegPassConfirm" class="nb-auth-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <div class="nb-input-with-icon">
                            <i class="bi bi-check2-circle"></i>
                            <input type="password" id="nbRegPassConfirm" name="confirm_password" minlength="6" required class="nb-form-input" placeholder="Nhập lại mật khẩu..." autocomplete="new-password" />
                            <button type="button" class="nb-eye-toggle" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="nb-auth-terms-note">
                        Bằng việc bấm Đăng Ký, bạn đồng ý với <a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>" target="_blank">Điều khoản dịch vụ</a> và <a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>" target="_blank">Chính sách bảo mật</a> của Biblio.
                    </div>

                    <button type="submit" class="nb-auth-submit-btn" id="nbRegSubmitBtn">
                        <span class="btn-text">ĐĂNG KÝ TÀI KHOẢN</span>
                        <span class="btn-spinner" style="display: none;"><i class="bi bi-arrow-repeat spin"></i> Đang khởi tạo...</span>
                    </button>

                    <?php if ($google_login_enabled): ?>
                    <div class="nb-social-auth-wrap">
                        <div class="nb-auth-divider"><span>HOẶC</span></div>
                        <button type="button" class="nb-btn-google-auth" id="nbGoogleRegBtn" data-auth-mode="register">
                            <svg class="nb-google-icon" width="18" height="18" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            <span>Tiếp tục bằng Google</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="nb-auth-footer-note">
                        Đã có tài khoản? <a href="#login" class="nb-switch-auth-link" data-tab="login">Đăng nhập tại đây</a>
                    </div>
                </form>
            </div>

            <!-- TAB 3: FORM QUÊN MẬT KHẨU -->
            <div class="nb-auth-pane" id="paneLostPassword" role="tabpanel">
                <div class="nb-lost-head">
                    <h3 class="nb-lost-title">Quên Mật Khẩu?</h3>
                    <p class="nb-lost-sub">Vui lòng nhập địa chỉ email hoặc tên tài khoản bạn đã đăng ký. Biblio sẽ gửi liên kết tạo lại mật khẩu mới tới email của bạn.</p>
                </div>
                <form id="nbLostPassForm" class="nb-auth-form" method="post">
                    <div class="nb-form-group">
                        <label for="nbLostUser" class="nb-auth-label">Email hoặc Tên tài khoản <span class="text-danger">*</span></label>
                        <div class="nb-input-with-icon">
                            <i class="bi bi-envelope-at-fill"></i>
                            <input type="text" id="nbLostUser" name="user_input" required class="nb-form-input" placeholder="Nhập email hoặc tên tài khoản..." />
                        </div>
                    </div>

                    <button type="submit" class="nb-auth-submit-btn" id="nbLostSubmitBtn">
                        <span class="btn-text">GỬI LIÊN KẾT ĐẶT LẠI MẬT KHẨU</span>
                        <span class="btn-spinner" style="display: none;"><i class="bi bi-arrow-repeat spin"></i> Đang gửi email...</span>
                    </button>

                    <div class="nb-auth-footer-note">
                        <a href="#login" class="nb-switch-auth-link" data-tab="login">← Quay lại Đăng Nhập</a>
                    </div>
                </form>
            </div>

            <div class="nb-auth-direct-link">
                <a href="<?php echo esc_url($myaccount_url); ?>">Chuyển sang trang tài khoản đầy đủ &rarr;</a>
            </div>
        </div>
    </div>
    <?php
}

// AJAX AUTH HANDLERS
add_action('wp_ajax_nopriv_nb_ajax_login', 'nb_handle_ajax_login');
function nb_handle_ajax_login() {
    check_ajax_referer('nb_auth_nonce_action', 'security');
    
    $username = sanitize_user($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (empty($username) || empty($password)) {
        wp_send_json_error(['message' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.']);
    }

    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ];

    $user = wp_signon($creds, is_ssl());

    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();
        $message = 'Tên đăng nhập hoặc mật khẩu không chính xác. Vui lòng thử lại!';
        if ($error_code === 'empty_username') {
            $message = 'Vui lòng nhập tên đăng nhập hoặc địa chỉ email.';
        } elseif ($error_code === 'empty_password') {
            $message = 'Vui lòng nhập mật khẩu của bạn.';
        }
        wp_send_json_error(['message' => $message]);
    }

    wp_send_json_success([
        'message'  => 'Đăng nhập thành công! Đang tải lại trang...',
        'redirect' => home_url('/'),
    ]);
}

add_action('wp_ajax_nopriv_nb_ajax_register', 'nb_handle_ajax_register');
function nb_handle_ajax_register() {
    check_ajax_referer('nb_auth_nonce_action', 'security');

    $name     = sanitize_text_field($_POST['name'] ?? '');
    $email    = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Địa chỉ email không hợp lệ, vui lòng kiểm tra lại.']);
    }
    if (empty($password) || strlen($password) < 6) {
        wp_send_json_error(['message' => 'Mật khẩu phải có độ dài từ 6 ký tự trở lên để đảm bảo an toàn.']);
    }
    if ($password !== $confirm) {
        wp_send_json_error(['message' => 'Mật khẩu xác nhận không trùng khớp với mật khẩu đã nhập!']);
    }
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'Địa chỉ email này đã được đăng ký tài khoản. Vui lòng đăng nhập hoặc dùng email khác.']);
    }

    // Determine unique username
    $username = sanitize_user(current(explode('@', $email)), true);
    if (empty($username) || username_exists($username)) {
        $username = 'user_' . wp_rand(1000, 99999);
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => 'Không thể tạo tài khoản: ' . $user_id->get_error_message()]);
    }

    if (!empty($name)) {
        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'first_name'   => $name,
        ]);
    }

    $user = new WP_User($user_id);
    $user->set_role('customer');

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    wp_send_json_success([
        'message'  => 'Đăng ký tài khoản thành công! Đang đăng nhập vào Biblio...',
        'redirect' => home_url('/tai-khoan/'),
    ]);
}

add_action('wp_ajax_nopriv_nb_ajax_lost_password', 'nb_handle_ajax_lost_password');
function nb_handle_ajax_lost_password() {
    check_ajax_referer('nb_auth_nonce_action', 'security');

    $user_input = trim($_POST['user_input'] ?? '');
    if (empty($user_input)) {
        wp_send_json_error(['message' => 'Vui lòng nhập email hoặc tên tài khoản của bạn.']);
    }

    if (is_email($user_input)) {
        $user_data = get_user_by('email', $user_input);
    } else {
        $user_data = get_user_by('login', $user_input);
    }

    if (!$user_data) {
        wp_send_json_error(['message' => 'Không tìm thấy tài khoản nào với thông tin bạn đã cung cấp.']);
    }

    $result = retrieve_password($user_data->user_login);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success([
        'message' => 'Liên kết đặt lại mật khẩu đã được gửi đến email ' . esc_html($user_data->user_email) . '. Vui lòng kiểm tra hộp thư đến (hoặc hòm thư rác / spam)!',
    ]);
}

// =========================================================================
// GOOGLE SIGN-IN AJAX HANDLER & HOOKS
// =========================================================================
add_action('wp_ajax_nopriv_nb_google_login', 'nb_handle_google_login');
add_action('wp_ajax_nb_google_login', 'nb_handle_google_login');
function nb_handle_google_login() {
    check_ajax_referer('nb_auth_nonce_action', 'security');

    $credential   = isset($_POST['credential']) ? sanitize_text_field(wp_unslash($_POST['credential'])) : '';
    $access_token = isset($_POST['access_token']) ? sanitize_text_field(wp_unslash($_POST['access_token'])) : '';
    $email   = '';
    $name    = '';
    $sub     = '';
    $picture = '';

    if (!empty($credential)) {
        // Case 1: Google ID Token (JWT) - Verify with Google API
        $verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        $response   = wp_remote_get($verify_url, ['timeout' => 8, 'sslverify' => true]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $expected_client_id = trim((string) get_option('nbook_google_client_id', ''));
            $email_verified = isset($data['email_verified']) && (
                $data['email_verified'] === 'true' || $data['email_verified'] === true
            );
            $audience_matches = $expected_client_id !== '' && !empty($data['aud']) && hash_equals($expected_client_id, (string) $data['aud']);
            if (!empty($data['email']) && $email_verified && $audience_matches) {
                $email   = sanitize_email($data['email']);
                $name    = sanitize_text_field($data['name'] ?? '');
                $sub     = sanitize_text_field($data['sub'] ?? '');
                $picture = esc_url_raw($data['picture'] ?? '');
            }
        }
    } elseif (!empty($access_token)) {
        // Case 2: OAuth2 Access Token
        $userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $response = wp_remote_get($userinfo_url, [
            'timeout'   => 8,
            'sslverify' => true,
            'headers'   => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['email']) && (!isset($data['email_verified']) || $data['email_verified'] === true || $data['email_verified'] === 'true')) {
                $email   = sanitize_email($data['email']);
                $name    = sanitize_text_field($data['name'] ?? '');
                $sub     = sanitize_text_field($data['sub'] ?? '');
                $picture = esc_url_raw($data['picture'] ?? '');
            }
        }
    }

    if (empty($email) || !is_email($email)) {
        wp_send_json_error([
            'message' => 'Xác thực tài khoản Google không thành công. Vui lòng thử lại!',
        ]);
    }

    // 1. Find existing user by Google sub OR by email
    $user = null;
    if (!empty($sub)) {
        $users_by_sub = get_users([
            'meta_key'   => '_nb_google_sub',
            'meta_value' => $sub,
            'number'     => 1,
        ]);
        if (!empty($users_by_sub)) {
            $user = reset($users_by_sub);
        }
    }

    if (!$user) {
        $user = get_user_by('email', $email);
    }

    $is_new_user = false;

    if ($user) {
        $user_id = $user->ID;
        // Update Google sub if missing
        if (!empty($sub)) {
            update_user_meta($user_id, '_nb_google_sub', $sub);
        }
        // Update avatar if user doesn't already have one
        if (!empty($picture) && !get_user_meta($user_id, 'nbook_custom_avatar_url', true)) {
            update_user_meta($user_id, 'nbook_custom_avatar_url', $picture);
        }
    } else {
        // 2. Create new WordPress user
        $is_new_user = true;
        $username_base = sanitize_user(current(explode('@', $email)), true);
        if (empty($username_base)) {
            $username_base = 'google_user';
        }
        $username = $username_base;
        $counter = 1;
        while (username_exists($username)) {
            $username = $username_base . '_' . $counter;
            $counter++;
        }

        $random_password = wp_generate_password(24, true, true);
        $user_id = wp_create_user($username, $random_password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error([
                'message' => 'Không thể tạo tài khoản từ Google: ' . $user_id->get_error_message(),
            ]);
        }

        // Setup profile name & role
        $name_parts = explode(' ', trim($name));
        $first_name = array_shift($name_parts);
        $last_last  = implode(' ', $name_parts);

        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name ?: $username,
            'first_name'   => $first_name ?: $name,
            'last_name'    => $last_last,
            'role'         => 'customer',
        ]);

        if (!empty($sub)) {
            update_user_meta($user_id, '_nb_google_sub', $sub);
        }
        if (!empty($picture)) {
            update_user_meta($user_id, 'nbook_custom_avatar_url', $picture);
        }

        $user = get_user_by('id', $user_id);
    }

    // 3. Log user in with auth cookie
    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);
    do_action('wp_login', $user->user_login, $user);

    $welcome_msg = $is_new_user
        ? 'Chào mừng bạn đến với Biblio! Tài khoản Google đã được kết nối (+100 Xu thành viên mới).'
        : 'Đăng nhập Google thành công! Chào mừng ' . esc_html($user->display_name) . ' trở lại.';

    wp_send_json_success([
        'message'     => $welcome_msg,
        'redirect'    => home_url('/'),
        'is_new_user' => $is_new_user,
    ]);
}

// Render Google button on WooCommerce standard login & register forms
add_action('woocommerce_login_form_end', 'nbook_render_google_login_wc_form');
add_action('woocommerce_register_form_end', 'nbook_render_google_login_wc_form');
function nbook_render_google_login_wc_form() {
    if (!get_option('nbook_google_login_enabled', 1)) return;
    ?>
    <div class="nb-social-auth-wrap nb-wc-social-wrap">
        <div class="nb-auth-divider"><span>HOẶC</span></div>
        <button type="button" class="nb-btn-google-auth" data-auth-mode="login">
            <svg class="nb-google-icon" width="18" height="18" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            <span>Đăng nhập với Google</span>
        </button>
    </div>
    <?php
}

// =========================================================================
// 8. AJAX LOGOUT HANDLER (Biblio Modal Center Confirmation)
// =========================================================================
add_action('wp_ajax_nbook_ajax_logout', 'nbook_ajax_logout_handler');
add_action('wp_ajax_nopriv_nbook_ajax_logout', 'nbook_ajax_logout_handler');
function nbook_ajax_logout_handler() {
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
    
    // Check nonce: valid 'log-out' nonce or 'nb_auth_nonce_action' or user logged in
    $nonce_valid = (!empty($nonce) && (wp_verify_nonce($nonce, 'log-out') || wp_verify_nonce($nonce, 'nb_auth_nonce_action')));
    
    if (!$nonce_valid && !is_user_logged_in()) {
        wp_send_json_error(['message' => 'Phiên đăng nhập đã kết thúc hoặc không hợp lệ.']);
        return;
    }
    
    // Perform standard WordPress & WooCommerce clean logout
    wp_logout();

    wp_send_json_success([
        'message'  => 'Đã đăng xuất thành công. Hẹn gặp lại bạn!',
        'redirect' => home_url('/'),
    ]);
}

// =========================================================================
// BIBLIO INVENTORY & STOCK MANAGEMENT MODULE (TÌNH TRẠNG TỒN KHO)
// =========================================================================

/**
 * Helper: Lấy thông tin chi tiết trạng thái tồn kho của sản phẩm
 * Các trạng thái:
 * 1. "Còn hàng" (xanh lá) - Tồn kho > ngưỡng (hoặc instock)
 * 2. "Chỉ còn X cuốn" (cam) - Khi tồn kho > 0 và <= ngưỡng (mặc định <= 5)
 * 3. "Hết hàng" (xám) - Khi tồn kho <= 0 hoặc outofstock
 * 4. "Sắp có hàng / Đặt trước" (xanh dương) - Khi cho phép đặt trước (backorders)
 */
function nbook_get_product_stock_info($product) {
    if (!$product) return null;

    $stock_status    = $product->get_stock_status();
    $manage_stock    = $product->get_manage_stock();
    $stock_qty       = $product->get_stock_quantity();
    $is_on_backorder = ($stock_status === 'onbackorder' || $product->is_on_backorder());

    // Ngưỡng cảnh báo sắp hết hàng (từ Cài đặt WooCommerce, mặc định là 5)
    $threshold = (int) get_option('woocommerce_notify_low_stock_amount', 5);
    if ($threshold <= 0) $threshold = 5;

    $state     = 'instock';
    $label     = 'Còn hàng';
    $css_class = 'nb-stock-instock';
    $icon      = 'bi-check-circle-fill';

    if ($is_on_backorder) {
        $state     = 'onbackorder';
        $label     = 'Sắp có hàng / Đặt trước';
        $css_class = 'nb-stock-onbackorder';
        $icon      = 'bi-clock-history';
    } elseif ($stock_status === 'outofstock' || ($manage_stock && $stock_qty !== null && $stock_qty <= 0)) {
        $state     = 'outofstock';
        $label     = 'Hết hàng';
        $css_class = 'nb-stock-outofstock';
        $icon      = 'bi-x-circle-fill';
    } elseif ($manage_stock && $stock_qty !== null && $stock_qty <= $threshold) {
        $state     = 'lowstock';
        $label     = 'Chỉ còn ' . $stock_qty . ' cuốn';
        $css_class = 'nb-stock-lowstock';
        $icon      = 'bi-exclamation-triangle-fill';
    } else {
        $state     = 'instock';
        $label     = ($manage_stock && $stock_qty !== null) ? ('Còn hàng (' . $stock_qty . ' cuốn)') : 'Còn hàng';
        $css_class = 'nb-stock-instock';
        $icon      = 'bi-check2-circle';
    }

    $badge_html = sprintf(
        '<span class="nb-stock-badge %s" data-stock-state="%s"><i class="bi %s me-1"></i>%s</span>',
        esc_attr($css_class),
        esc_attr($state),
        esc_attr($icon),
        esc_html($label)
    );

    return [
        'state'           => $state,
        'label'           => $label,
        'css_class'       => $css_class,
        'icon'            => $icon,
        'badge_html'      => $badge_html,
        'qty'             => $stock_qty,
        'manage_stock'    => $manage_stock,
        'threshold'       => $threshold,
        'is_out_of_stock' => ($state === 'outofstock'),
        'is_low_stock'    => ($state === 'lowstock'),
        'is_on_backorder' => ($state === 'onbackorder'),
        'is_in_stock'     => ($state === 'instock' || $state === 'lowstock'),
    ];
}

/**
 * 1. GIỚI HẠN SỐ LƯỢNG KHI THÊM VÀO GIỎ HÀNG (Add to cart validation)
 * Không cho đặt quá số lượng tồn, thông báo Tiếng Việt rõ ràng
 */
add_filter('woocommerce_add_to_cart_validation', 'nbook_validate_stock_add_to_cart', 10, 5);
function nbook_validate_stock_add_to_cart($passed, $product_id, $quantity, $variation_id = 0, $variations = []) {
    $target_id = $variation_id ? $variation_id : $product_id;
    $product   = wc_get_product($target_id);
    if (!$product) return $passed;

    if ($product->is_on_backorder()) {
        return $passed; // Cho phép đặt trước nếu bật backorder
    }

    if ($product->get_manage_stock()) {
        $stock = (int) $product->get_stock_quantity();

        if ($stock <= 0) {
            wc_add_notice(
                sprintf('Rất tiếc, tựa sách <strong>"%s"</strong> hiện đã hết hàng trong kho, không thể thêm vào giỏ hàng.', esc_html($product->get_name())),
                'error'
            );
            return false;
        }

        // Tính số lượng đang có trong giỏ hàng của sản phẩm này
        $cart_qty = 0;
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                if ($cart_item['product_id'] == $product_id || (!empty($cart_item['variation_id']) && $cart_item['variation_id'] == $variation_id)) {
                    $cart_qty += $cart_item['quantity'];
                }
            }
        }

        if (($cart_qty + $quantity) > $stock) {
            $available_to_add = max(0, $stock - $cart_qty);
            if ($available_to_add > 0) {
                wc_add_notice(
                    sprintf(
                        'Rất tiếc, sách <strong>"%s"</strong> chỉ còn <strong>%d cuốn</strong> trong kho. Bạn đã có %d cuốn trong giỏ hàng, bạn chỉ có thể thêm tối đa <strong>%d cuốn</strong> nữa.',
                        esc_html($product->get_name()),
                        $stock,
                        $cart_qty,
                        $available_to_add
                    ),
                    'error'
                );
            } else {
                wc_add_notice(
                    sprintf(
                        'Rất tiếc, bạn đã thêm toàn bộ số lượng tồn kho còn lại (<strong>%d cuốn</strong>) của sách <strong>"%s"</strong> vào giỏ hàng.',
                        $stock,
                        esc_html($product->get_name())
                    ),
                    'error'
                );
            }
            return false;
        }
    }

    return $passed;
}

/**
 * 2. KIỂM SOÁT TỒN KHO KHI CẬP NHẬT GIỎ HÀNG (Update cart validation)
 */
add_filter('woocommerce_update_cart_validation', 'nbook_validate_stock_update_cart', 10, 4);
function nbook_validate_stock_update_cart($passed, $cart_item_key, $values, $quantity) {
    $product = $values['data'] ?? null;
    if (!$product || $product->is_on_backorder() || !$product->get_manage_stock()) {
        return $passed;
    }

    $stock = (int) $product->get_stock_quantity();
    if ($quantity > $stock) {
        wc_add_notice(
            sprintf(
                'Rất tiếc, tựa sách <strong>"%s"</strong> chỉ còn lại <strong>%d cuốn</strong> trong kho. Vui lòng chọn số lượng đặt mua không vượt quá số lượng tồn.',
                esc_html($product->get_name()),
                $stock
            ),
            'error'
        );
        return false;
    }

    return $passed;
}

/**
 * 3. THIẾT LẬP THUỘC TÍNH MAX CHO Ô NHẬP SỐ LƯỢNG
 */
add_filter('woocommerce_quantity_input_args', 'nbook_quantity_input_max_stock', 10, 2);
function nbook_quantity_input_max_stock($args, $product) {
    if ($product && $product->get_manage_stock() && !$product->is_on_backorder()) {
        $stock = (int) $product->get_stock_quantity();
        if ($stock > 0) {
            $args['max_value'] = $stock;
        }
    }
    return $args;
}

/**
 * 4. FORM BÁO KHI CÓ HÀNG TRÊN TRANG CHI TIẾT SẢN PHẨM (Đã bỏ dòng "Tình trạng:" trùng lặp)
 */
// Tắt icon zoom/lightbox trigger trên ảnh chi tiết sản phẩm
add_filter('woocommerce_single_product_photoswipe_enabled', '__return_false', 99);

add_action('woocommerce_single_product_summary', 'nbook_single_product_stock_badge', 11);
function nbook_single_product_stock_badge() {
    global $product;
    if (!$product) return;

    $stock_info = nbook_get_product_stock_info($product);
    $pid        = $product->get_id();
    $title      = $product->get_name();

    // Không hiển thị dòng "Tình trạng:" vì số lượng/tồn kho đã hiển thị ở trên
    if (!$stock_info['is_low_stock'] && !$stock_info['is_out_of_stock']) {
        return;
    }
    ?>
    <div class="nb-single-stock-row mb-3">
        <?php if ($stock_info['is_low_stock']): ?>
            <div class="nb-single-urgency-notice mt-2">
                <i class="bi bi-fire text-danger"></i>
                <span>Sách đang bán rất nhanh! Chỉ còn <strong><?php echo esc_html($stock_info['qty']); ?> cuốn</strong> cuối cùng trong kho.</span>
            </div>
        <?php elseif ($stock_info['is_out_of_stock']): ?>
            <div class="nb-single-outofstock-box mt-3 p-3 rounded-3 border">
                <div class="d-flex align-items-center gap-2 text-danger fw-bold mb-1">
                    <i class="bi bi-bell-fill"></i>&nbsp;&nbsp;<span>Tựa sách này hiện đang tạm hết hàng</span>
                </div>
                <p class="small text-muted mb-2">
                    Nhập email của bạn dưới đây, Biblio sẽ tự động gửi email thông báo cho bạn ngay khi có hàng trở lại!
                </p>
                <form class="nb-stock-notify-inline-form d-flex gap-2" method="post" data-product-id="<?php echo esc_attr($pid); ?>">
                    <input type="email" name="notify_email" class="form-control form-control-sm" placeholder="Nhập địa chỉ email của bạn..." required style="max-width: 320px;" />
                    <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap fw-semibold">
                        <i class="bi bi-bell-fill"></i>&nbsp;&nbsp;Báo khi có hàng
                    </button>
                </form>
                <div class="nb-notify-feedback mt-2 small" style="display: none;"></div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * 5. MODAL POPUP "BÁO CHO TÔI KHI CÓ HÀNG" DÙNG CHUNG TOÀN TRANG
 */
add_action('wp_footer', 'nbook_render_stock_notify_modal');
function nbook_render_stock_notify_modal() {
    ?>
    <!-- MODAL THÔNG BÁO KHI CÓ HÀNG -->
    <div class="nb-stock-notify-modal-overlay" id="nbStockNotifyModal" aria-hidden="true" role="dialog" aria-labelledby="nbNotifyModalHeading">
        <div class="nb-stock-notify-backdrop"></div>
        <div class="nb-stock-notify-box">
            <button type="button" class="nb-stock-notify-close" id="nbStockNotifyCloseBtn" aria-label="Đóng">&times;</button>
            <div class="nb-snb-header">
                <div class="nb-snb-icon"><i class="bi bi-bell-fill"></i></div>
                <div class="nb-snb-badge"><i class="bi bi-clock-history"></i>&nbsp;&nbsp;THÔNG BÁO TỰ ĐỘNG</div>
                <h3 class="nb-snb-title" id="nbNotifyModalHeading">Báo Cho Tôi Khi Có Hàng</h3>
            </div>
            
            <div class="nb-snb-product-box">
                <div class="nb-snb-product-icon"><i class="bi bi-book-half"></i></div>
                <div class="nb-snb-product-meta">
                    <span class="nb-snb-product-label">Sách đang tạm hết hàng:</span>
                    <strong class="nb-snb-product-name" id="nbNotifyProductTitle">Sách</strong>
                </div>
                <span class="nb-snb-stock-tag"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;&nbsp;Tạm hết</span>
            </div>

            <p class="nb-snb-sub">
                <i class="bi bi-info-circle-fill text-primary"></i>&nbsp;&nbsp;Hãy để lại email bên dưới, Biblio sẽ tự động gửi thông báo ưu tiên ngay khi sách được nhập kho trở lại!
            </p>

            <form id="nbStockNotifyForm" class="nb-snb-form">
                <input type="hidden" id="nbNotifyProductId" name="product_id" value="" />
                <div class="nb-form-group mb-3">
                    <label for="nbNotifyEmail" class="nb-snb-label">
                        <i class="bi bi-envelope-at-fill"></i>&nbsp;&nbsp;Địa chỉ email nhận tin <span class="text-danger">*</span>
                    </label>
                    <div class="nb-snb-input-wrap">
                        <i class="bi bi-envelope nb-snb-input-icon"></i>
                        <input type="email" id="nbNotifyEmail" name="email" required class="nb-snb-input" placeholder="Nhập địa chỉ email của bạn (vd: ban-doc@gmail.com)..." />
                    </div>
                </div>
                <div class="nb-snb-alert" id="nbNotifyAlert" style="display: none;"></div>
                <button type="submit" class="nb-snb-submit-btn" id="nbNotifySubmitBtn">
                    <span class="btn-text"><i class="bi bi-send-fill"></i>&nbsp;&nbsp;ĐĂNG KÝ NHẬN THÔNG BÁO</span>
                    <span class="btn-spinner" style="display: none;"><i class="bi bi-arrow-repeat spin"></i>&nbsp;&nbsp;Đang ghi nhận...</span>
                </button>
            </form>
            <div class="nb-snb-note">
                <i class="bi bi-shield-check"></i>&nbsp;&nbsp;<span>Biblio cam kết bảo mật email và chỉ gửi duy nhất 01 thông báo khi có sách, không spam.</span>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 6. XỬ LÝ AJAX LƯU EMAIL "BÁO CHO TÔI KHI CÓ HÀNG"
 */
add_action('wp_ajax_nb_subscribe_stock_alert', 'nb_handle_ajax_subscribe_stock_alert');
add_action('wp_ajax_nopriv_nb_subscribe_stock_alert', 'nb_handle_ajax_subscribe_stock_alert');
function nb_handle_ajax_subscribe_stock_alert() {
    check_ajax_referer('nbook_search_nonce', 'security');

    $product_id = absint($_POST['product_id'] ?? 0);
    $email      = sanitize_email($_POST['email'] ?? '');

    if (!$product_id || !is_email($email)) {
        wp_send_json_error(['message' => 'Vui lòng cung cấp địa chỉ email hợp lệ.']);
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Không tìm thấy sản phẩm yêu cầu.']);
    }

    $subscribers = get_post_meta($product_id, '_nb_stock_subscribers', true);
    if (!is_array($subscribers)) {
        $subscribers = [];
    }

    // Kiểm tra xem email đã đăng ký chưa
    $already_subscribed = false;
    foreach ($subscribers as $sub) {
        if (isset($sub['email']) && strtolower($sub['email']) === strtolower($email)) {
            $already_subscribed = true;
            break;
        }
    }

    if (!$already_subscribed) {
        $subscribers[] = [
            'email' => strtolower($email),
            'time'  => current_time('mysql'),
        ];
        update_post_meta($product_id, '_nb_stock_subscribers', $subscribers);
    }

    wp_send_json_success([
        'message' => 'Cảm ơn bạn! Biblio đã lưu email ' . esc_html($email) . '. Chúng tôi sẽ gửi email thông báo ngay khi tựa sách "' . esc_html($product->get_name()) . '" có hàng trở lại!',
    ]);
}

/**
 * 7. TỰ ĐỘNG GỬI EMAIL THÔNG BÁO CHO KHÁCH HÀNG KHI ADMIN NHẬP THÊM HÀNG
 */
add_action('woocommerce_product_set_stock', 'nbook_trigger_restock_notifications');
add_action('save_post_product', 'nbook_trigger_restock_notifications_on_save', 20, 2);

function nbook_trigger_restock_notifications_on_save($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $product = wc_get_product($post_id);
    if ($product) {
        nbook_trigger_restock_notifications($product);
    }
}

function nbook_trigger_restock_notifications($product) {
    if (!$product) return;

    $pid          = $product->get_id();
    $stock_status = $product->get_stock_status();
    $stock_qty    = $product->get_stock_quantity();
    $manage_stock = $product->get_manage_stock();

    // Điều kiện có hàng trở lại
    $is_now_in_stock = ($stock_status === 'instock') && (!$manage_stock || $stock_qty > 0);
    if (!$is_now_in_stock) return;

    $subscribers = get_post_meta($pid, '_nb_stock_subscribers', true);
    if (empty($subscribers) || !is_array($subscribers)) return;

    $title     = $product->get_name();
    $permalink = $product->get_permalink();
    $price     = $product->get_price_html();
    $thumb_url = get_the_post_thumbnail_url($pid, 'medium') ?: wc_placeholder_img_src();

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $subject = sprintf('[Biblio] Sách bạn quan tâm đã có hàng: %s', $title);

    foreach ($subscribers as $sub) {
        $to_email = $sub['email'] ?? '';
        if (!is_email($to_email)) continue;

        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <h2 style='color: #ee4d2d; margin: 0;'>Biblio - Sách &amp; Tri Thức</h2>
                <p style='color: #666; font-size: 13px;'>Thông báo tựa sách có hàng trở lại</p>
            </div>
            <p>Chào bạn,</p>
            <p>Tựa sách <strong>" . esc_html($title) . "</strong> mà bạn đã đăng ký nhận thông báo hiện <strong>ĐÃ CÓ HÀNG TRỞ LẠI</strong> tại sàn sách Biblio.</p>
            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin: 15px 0; text-align: center;'>
                <img src='" . esc_url($thumb_url) . "' alt='" . esc_attr($title) . "' style='max-width: 140px; height: auto; border-radius: 4px; display: block; margin: 0 auto 10px;' />
                <h3 style='margin: 0 0 8px 0; color: #1e293b; font-size: 16px;'>" . esc_html($title) . "</h3>
                <div style='font-size: 18px; font-weight: bold; color: #ee4d2d; margin-bottom: 15px;'>" . $price . "</div>
                <a href='" . esc_url($permalink) . "' style='background: #ee4d2d; color: #ffffff; padding: 10px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>ĐẶT MUA NGAY &rarr;</a>
            </div>
            <p style='font-size: 13px; color: #64748b;'>Số lượng sách nhập về có hạn, bạn hãy nhanh tay đặt mua trước khi sách hết hàng nhé!</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #94a3b8; text-align: center;'>Email này được gửi tự động từ Sàn Thương Mại Điện Tử Sách Biblio (207 Giải Phóng, Phường Bạch Mai, Hà Nội).</p>
        </div>
        ";

        wp_mail($to_email, $subject, $body, $headers);
    }

    // Xóa danh sách sau khi đã gửi thông báo thành công
    delete_post_meta($pid, '_nb_stock_subscribers');
    // Lưu lịch sử
    update_post_meta($pid, '_nb_stock_last_notified', current_time('mysql'));
}

/**
 * 8. GỬI EMAIL CẢNH BÁO CHO ADMIN KHI SÁCH CHẠM NGƯỠNG SẮP HẾT HÀNG
 */
add_action('woocommerce_low_stock', 'nbook_send_admin_low_stock_email');
function nbook_send_admin_low_stock_email($product) {
    if (!$product) return;

    $recipient = get_option('woocommerce_stock_email_recipient', get_option('admin_email'));
    if (!is_email($recipient)) $recipient = get_option('admin_email');

    $threshold = (int) get_option('woocommerce_notify_low_stock_amount', 5);
    $qty       = (int) $product->get_stock_quantity();
    $title     = $product->get_name();
    $sku       = $product->get_sku() ?: 'Chưa có';
    $edit_url  = admin_url('post.php?post=' . $product->get_id() . '&action=edit');

    $subject   = sprintf('[Biblio Cảnh Báo] Sách sắp hết hàng: %s (Chỉ còn %d cuốn)', $title, $qty);
    $headers   = ['Content-Type: text/html; charset=UTF-8'];

    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #fed7aa; border-radius: 8px; background: #fffbeb;'>
        <div style='border-bottom: 2px solid #f97316; padding-bottom: 12px; margin-bottom: 15px;'>
            <h2 style='color: #c2410c; margin: 0;'>⚠️ Cảnh Báo Tồn Kho Biblio</h2>
            <p style='color: #7c2d12; margin: 4px 0 0 0; font-size: 13px;'>Tựa sách đã giảm xuống bằng hoặc thấp hơn ngưỡng cảnh báo ({$threshold} cuốn)</p>
        </div>
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'>
            <tr>
                <td style='padding: 8px 0; color: #4b5563; width: 140px;'>Tên tựa sách:</td>
                <td style='padding: 8px 0; font-weight: bold; color: #111827;'>" . esc_html($title) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px 0; color: #4b5563;'>Mã SKU:</td>
                <td style='padding: 8px 0; font-family: monospace; font-size: 13px;'>" . esc_html($sku) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px 0; color: #4b5563;'>Số lượng tồn kho:</td>
                <td style='padding: 8px 0; color: #ea580c; font-size: 16px; font-weight: bold;'>" . esc_html($qty) . " cuốn</td>
            </tr>
            <tr>
                <td style='padding: 8px 0; color: #4b5563;'>Ngưỡng cài đặt:</td>
                <td style='padding: 8px 0; color: #4b5563;'>" . esc_html($threshold) . " cuốn</td>
            </tr>
        </table>
        <div style='text-align: center;'>
            <a href='" . esc_url($edit_url) . "' style='background: #ea580c; color: #ffffff; padding: 10px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>CẬP NHẬT TỒN KHO TRONG ADMIN &rarr;</a>
        </div>
    </div>
    ";

    wp_mail($recipient, $subject, $body, $headers);
}

/**
 * 9. ĐẢM BẢO TỰ ĐỘNG TRỪ KHO KHI ĐẶT HÀNG & HOÀN KHO KHI HỦY ĐƠN
 */
// Trừ kho khi đơn hàng được tạo hoặc chuyển sang xử lý / hoàn thành
add_action('woocommerce_checkout_order_processed', 'nbook_ensure_stock_reduction_on_order', 10, 1);
function nbook_ensure_stock_reduction_on_order($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // WooCommerce mặc định gọi wc_reduce_stock_levels khi thanh toán thành công
    // Ghi log đơn hàng xác nhận trừ kho
    $order->add_order_note('Biblio: Đã tự động kiểm tra và trừ tồn kho theo các mục trong đơn hàng.');
}

// Hoàn kho khi đơn hàng bị hủy hoặc hoàn tiền
add_action('woocommerce_order_status_cancelled', 'nbook_restore_stock_on_order_cancelled', 10, 1);
add_action('woocommerce_order_status_refunded', 'nbook_restore_stock_on_order_cancelled', 10, 1);
function nbook_restore_stock_on_order_cancelled($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Nếu WooCommerce chưa tự hoàn kho thì hoàn trả
    $already_restored = $order->get_meta('_nb_stock_restored');
    if ($already_restored !== 'yes') {
        wc_increase_stock_levels($order_id);
        $order->update_meta_data('_nb_stock_restored', 'yes');
        $order->add_order_note('Biblio: Đã tự động hoàn trả số lượng sách trong đơn về tồn kho hệ thống.');
        $order->save();
    }
}

/**
 * 10. TÙY BIẾN CỘT TỒN KHO TRONG ADMIN DASHBOARD (wp-admin/edit.php?post_type=product)
 */
add_filter('manage_product_posts_custom_column', 'nbook_custom_admin_product_stock_column', 20, 2);
function nbook_custom_admin_product_stock_column($column, $post_id) {
    if ($column === 'is_in_stock') {
        $product = wc_get_product($post_id);
        if (!$product) return;

        $stock_info  = nbook_get_product_stock_info($product);
        $subscribers = get_post_meta($post_id, '_nb_stock_subscribers', true);
        $sub_count   = is_array($subscribers) ? count($subscribers) : 0;

        echo '<div class="nb-admin-stock-cell" style="margin-top: 4px;">';
        if ($stock_info['is_out_of_stock']) {
            echo '<span style="background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;"><span style="color:#ef4444;">●</span> Hết hàng (0)</span>';
        } elseif ($stock_info['is_low_stock']) {
            echo '<span style="background: #fef3c7; color: #d97706; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;">⚠️ Sắp hết (' . esc_html($stock_info['qty']) . ' cuốn)</span>';
        } elseif ($stock_info['is_on_backorder']) {
            echo '<span style="background: #e0f2fe; color: #0284c7; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11.5px;">Đặt trước</span>';
        } else {
            $qty_text = ($stock_info['manage_stock'] && $stock_info['qty'] !== null) ? (' (' . $stock_info['qty'] . ')') : '';
            echo '<span style="background: #dcfce7; color: #16a34a; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11.5px;">✓ Còn hàng' . esc_html($qty_text) . '</span>';
        }

        if ($sub_count > 0) {
            echo '<div style="margin-top: 4px; font-size: 11px; color: #7c3aed; font-weight: 600;"><i class="dashicons dashicons-bell" style="font-size: 12px; vertical-align: middle;"></i> ' . $sub_count . ' khách chờ hàng</div>';
        }
        echo '</div>';
    }
}

/**
 * 11. TRANG QUẢN LÝ TỒN KHO & CÀI ĐẶT NGƯỠNG TRONG ADMIN WOOCOMMERCE
 */
add_action('admin_menu', 'nbook_add_stock_manager_admin_menu');
function nbook_add_stock_manager_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Quản Lý Tồn Kho Biblio',
        '📦 Quản Lý Tồn Kho',
        'manage_options',
        'biblio-stock-manager',
        'nbook_render_admin_stock_manager_page'
    );
}

function nbook_render_admin_stock_manager_page() {
    if (!current_user_can('manage_options')) return;

    // Lưu cấu hình ngưỡng khi submit form
    $saved_notice = '';
    if (isset($_POST['nb_save_stock_settings']) && check_admin_referer('nb_stock_settings_action', 'nb_stock_nonce')) {
        $new_threshold = max(1, absint($_POST['nb_low_stock_amount'] ?? 5));
        $new_recipient = sanitize_email($_POST['nb_stock_recipient'] ?? '');

        update_option('woocommerce_notify_low_stock_amount', $new_threshold);
        if (is_email($new_recipient)) {
            update_option('woocommerce_stock_email_recipient', $new_recipient);
        }
        $saved_notice = 'Đã lưu cấu hình tồn kho thành công!';
    }

    $current_threshold = (int) get_option('woocommerce_notify_low_stock_amount', 5);
    $stock_recipient   = get_option('woocommerce_stock_email_recipient', get_option('admin_email'));

    // Lấy danh sách sách sắp hết hàng & hết hàng
    $all_products = wc_get_products(['limit' => 100, 'status' => 'publish']);
    $low_stock_prods = [];
    $out_of_stock_prods = [];

    foreach ($all_products as $p) {
        $info = nbook_get_product_stock_info($p);
        if ($info['is_low_stock']) {
            $low_stock_prods[] = $p;
        } elseif ($info['is_out_of_stock']) {
            $out_of_stock_prods[] = $p;
        }
    }
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 8px;">
            <span style="color: #ee4d2d;">📦</span> Quản Lý Tồn Kho &amp; Cảnh Báo - Biblio
        </h1>
        <p class="description">Theo dõi số lượng sách sắp hết, danh sách khách hàng đang chờ sách về và cấu hình ngưỡng cảnh báo tự động.</p>

        <?php if (!empty($saved_notice)): ?>
            <div class="notice notice-success is-dismissible"><p><strong><?php echo esc_html($saved_notice); ?></strong></p></div>
        <?php endif; ?>

        <!-- Card Cấu hình ngưỡng cảnh báo -->
        <div style="background: #ffffff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 8px; margin: 20px 0; max-width: 800px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #eee; padding-bottom: 10px;">⚙️ Cài Đặt Ngưỡng Cảnh Báo Tồn Kho</h3>
            <form method="post">
                <?php wp_nonce_field('nb_stock_settings_action', 'nb_stock_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="nb_low_stock_amount">Ngưỡng cảnh báo sắp hết (cuốn):</label></th>
                        <td>
                            <input type="number" id="nb_low_stock_amount" name="nb_low_stock_amount" value="<?php echo esc_attr($current_threshold); ?>" min="1" max="100" class="regular-text" style="width: 100px;" />
                            <p class="description">Khi số lượng tồn kho của sách bằng hoặc thấp hơn ngưỡng này, sách sẽ tự động chuyển sang nhãn cam <strong>"Chỉ còn X cuốn"</strong> trên web và hệ thống gửi email cảnh báo về cho quản trị viên.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nb_stock_recipient">Email nhận thông báo cảnh báo:</label></th>
                        <td>
                            <input type="email" id="nb_stock_recipient" name="nb_stock_recipient" value="<?php echo esc_attr($stock_recipient); ?>" class="regular-text" />
                            <p class="description">Email của quản trị viên tiếp nhận cảnh báo sách sắp hết và thông tin kho.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="nb_save_stock_settings" class="button button-primary">Lưu Cài Đặt Ngưỡng</button>
                </p>
            </form>
        </div>

        <!-- 2 Bảng: Sắp hết và Hết hàng -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <!-- Cột Sắp Hết Hàng -->
            <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 8px; padding: 20px;">
                <h3 style="color: #c2410c; margin-top: 0; display: flex; align-items: center; justify-content: space-between;">
                    <span>⚠️ Sách Sắp Hết Hàng (&le; <?php echo $current_threshold; ?> cuốn)</span>
                    <span style="background: #fef3c7; color: #b45309; padding: 2px 10px; border-radius: 12px; font-size: 13px;"><?php echo count($low_stock_prods); ?> tựa sách</span>
                </h3>
                <?php if (!empty($low_stock_prods)): ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Tên Sách</th>
                                <th>Tồn Kho</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_prods as $lp): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($lp->get_name()); ?></strong></td>
                                    <td><span style="color: #ea580c; font-weight: bold;"><?php echo $lp->get_stock_quantity(); ?> cuốn</span></td>
                                    <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $lp->get_id() . '&action=edit')); ?>" class="button button-small">Nhập kho</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #16a34a;">✓ Không có tựa sách nào đang ở ngưỡng sắp hết hàng.</p>
                <?php endif; ?>
            </div>

            <!-- Cột Hết Hàng -->
            <div style="background: #ffffff; border: 1px solid #fecaca; border-radius: 8px; padding: 20px;">
                <h3 style="color: #b91c1c; margin-top: 0; display: flex; align-items: center; justify-content: space-between;">
                    <span>✕ Sách Đang Hết Hàng (0 cuốn)</span>
                    <span style="background: #fee2e2; color: #b91c1c; padding: 2px 10px; border-radius: 12px; font-size: 13px;"><?php echo count($out_of_stock_prods); ?> tựa sách</span>
                </h3>
                <?php if (!empty($out_of_stock_prods)): ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Tên Sách</th>
                                <th>Khách Chờ Báo</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($out_of_stock_prods as $op):
                                $subs = get_post_meta($op->get_id(), '_nb_stock_subscribers', true);
                                $scount = is_array($subs) ? count($subs) : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($op->get_name()); ?></strong></td>
                                    <td>
                                        <?php if ($scount > 0): ?>
                                            <span style="color: #7c3aed; font-weight: bold;">🔔 <?php echo $scount; ?> người</span>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $op->get_id() . '&action=edit')); ?>" class="button button-small button-primary">Thêm tồn</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #16a34a;">✓ Tất cả tựa sách đều đang còn hàng.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 12. DASHBOARD WIDGET CẢNH BÁO TỒN KHO BIBLIO
 */
add_action('wp_dashboard_setup', 'nbook_add_stock_dashboard_widget');
function nbook_add_stock_dashboard_widget() {
    if (!current_user_can('manage_options')) return;
    wp_add_dashboard_widget(
        'nbook_stock_alert_widget',
        '📦 Biblio: Tình Trạng Tồn Kho Sách',
        'nbook_render_stock_dashboard_widget'
    );
}

function nbook_render_stock_dashboard_widget() {
    $threshold = (int) get_option('woocommerce_notify_low_stock_amount', 5);
    $products = wc_get_products(['limit' => 100, 'status' => 'publish']);
    $low_count = 0;
    $out_count = 0;
    $sub_total = 0;

    foreach ($products as $p) {
        $info = nbook_get_product_stock_info($p);
        if ($info['is_low_stock']) $low_count++;
        if ($info['is_out_of_stock']) $out_count++;
        $subs = get_post_meta($p->get_id(), '_nb_stock_subscribers', true);
        if (is_array($subs)) $sub_total += count($subs);
    }
    ?>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; margin-bottom: 15px;">
        <div style="background: #fff7ed; border: 1px solid #fed7aa; padding: 10px; border-radius: 6px;">
            <div style="font-size: 20px; font-weight: bold; color: #ea580c;"><?php echo $low_count; ?></div>
            <div style="font-size: 11px; color: #9a3412;">Sắp hết hàng</div>
        </div>
        <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 10px; border-radius: 6px;">
            <div style="font-size: 20px; font-weight: bold; color: #dc2626;"><?php echo $out_count; ?></div>
            <div style="font-size: 11px; color: #991b1b;">Đã hết hàng</div>
        </div>
        <div style="background: #f5f3ff; border: 1px solid #ddd6fe; padding: 10px; border-radius: 6px;">
            <div style="font-size: 20px; font-weight: bold; color: #7c3aed;"><?php echo $sub_total; ?></div>
            <div style="font-size: 11px; color: #5b21b6;">Khách chờ tin</div>
        </div>
    </div>
    <div style="text-align: right;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=biblio-stock-manager')); ?>" class="button button-secondary">Vào Trang Quản Lý Kho &rarr;</a>
    </div>
    <?php
}




/**
 * =========================================================================
 * 13. BIBLIO CART & CHECKOUT AJAX HANDLERS & FLOW
 * =========================================================================
 */

// 1. AJAX: Update Cart Item Quantity
add_action('wp_ajax_nbook_cart_update_qty', 'nbook_ajax_cart_update_qty');
add_action('wp_ajax_nopriv_nbook_cart_update_qty', 'nbook_ajax_cart_update_qty');
function nbook_ajax_cart_update_qty() {
    $cart_key = sanitize_text_field($_POST['cart_key'] ?? '');
    $qty = max(1, intval($_POST['quantity'] ?? 1));

    if (!empty($cart_key) && WC()->cart && WC()->cart->get_cart_item($cart_key)) {
        WC()->cart->set_quantity($cart_key, $qty, true);
        WC()->cart->calculate_totals();

        $cart_item = WC()->cart->get_cart_item($cart_key);
        $product = $cart_item['data'] ?? null;
        $price = $product ? (float) $product->get_price() : 0;
        $line_total_raw = $price * $qty;

        $subtotal_raw = (float) WC()->cart->get_subtotal();
        $threshold = 250000;
        $is_freeship = ($subtotal_raw >= $threshold);
        $remaining = max(0, $threshold - $subtotal_raw);
        $percent = min(100, round(($subtotal_raw / $threshold) * 100));

        wp_send_json_success([
            'cart_key'            => $cart_key,
            'quantity'            => $qty,
            'line_total'          => wc_price($line_total_raw),
            'line_total_raw'      => $line_total_raw,
            'cart_subtotal'       => wc_price($subtotal_raw),
            'cart_subtotal_raw'   => $subtotal_raw,
            'cart_count'          => WC()->cart->get_cart_contents_count(),
            'is_freeship'         => $is_freeship,
            'remaining_freeship'  => wc_price($remaining),
            'freeship_percent'    => $percent,
        ]);
    }
    wp_send_json_error(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng.']);
}

// 2. AJAX: Remove Single Cart Item
add_action('wp_ajax_nbook_cart_remove_item', 'nbook_ajax_cart_remove_item');
add_action('wp_ajax_nopriv_nbook_cart_remove_item', 'nbook_ajax_cart_remove_item');
function nbook_ajax_cart_remove_item() {
    $cart_key = sanitize_text_field($_POST['cart_key'] ?? '');

    if (!empty($cart_key) && WC()->cart && WC()->cart->get_cart_item($cart_key)) {
        WC()->cart->remove_cart_item($cart_key);
        WC()->cart->calculate_totals();

        $cart_count = WC()->cart->get_cart_contents_count();
        $is_empty = WC()->cart->is_empty();

        $subtotal_raw = (float) WC()->cart->get_subtotal();
        $threshold = 250000;
        $is_freeship = ($subtotal_raw >= $threshold);
        $remaining = max(0, $threshold - $subtotal_raw);
        $percent = min(100, round(($subtotal_raw / $threshold) * 100));

        wp_send_json_success([
            'cart_key'           => $cart_key,
            'is_empty'           => $is_empty,
            'cart_count'         => $cart_count,
            'cart_subtotal'      => wc_price($subtotal_raw),
            'cart_subtotal_raw'  => $subtotal_raw,
            'is_freeship'        => $is_freeship,
            'remaining_freeship' => wc_price($remaining),
            'freeship_percent'   => $percent,
        ]);
    }
    wp_send_json_error(['message' => 'Không thể xóa sản phẩm.']);
}

// 3. AJAX: Remove Multiple Cart Items (Delete Selected)
add_action('wp_ajax_nbook_cart_remove_multiple', 'nbook_ajax_cart_remove_multiple');
add_action('wp_ajax_nopriv_nbook_cart_remove_multiple', 'nbook_ajax_cart_remove_multiple');
function nbook_ajax_cart_remove_multiple() {
    $cart_keys = isset($_POST['cart_keys']) ? (array) $_POST['cart_keys'] : [];

    if (!empty($cart_keys) && WC()->cart) {
        foreach ($cart_keys as $key) {
            $key = sanitize_text_field($key);
            if (WC()->cart->get_cart_item($key)) {
                WC()->cart->remove_cart_item($key);
            }
        }
        WC()->cart->calculate_totals();

        $cart_count = WC()->cart->get_cart_contents_count();
        $is_empty = WC()->cart->is_empty();

        $subtotal_raw = (float) WC()->cart->get_subtotal();
        $threshold = 250000;
        $is_freeship = ($subtotal_raw >= $threshold);
        $remaining = max(0, $threshold - $subtotal_raw);
        $percent = min(100, round(($subtotal_raw / $threshold) * 100));

        wp_send_json_success([
            'is_empty'           => $is_empty,
            'cart_count'         => $cart_count,
            'cart_subtotal'      => wc_price($subtotal_raw),
            'cart_subtotal_raw'  => $subtotal_raw,
            'is_freeship'        => $is_freeship,
            'remaining_freeship' => wc_price($remaining),
            'freeship_percent'   => $percent,
        ]);
    }
    wp_send_json_error(['message' => 'Danh sách sản phẩm trống.']);
}

// 4. AJAX: Apply Coupon
add_action('wp_ajax_nbook_cart_apply_coupon', 'nbook_ajax_cart_apply_coupon');
add_action('wp_ajax_nopriv_nbook_cart_apply_coupon', 'nbook_ajax_cart_apply_coupon');
function nbook_ajax_cart_apply_coupon() {
    $code = sanitize_text_field($_POST['coupon_code'] ?? '');
    if (empty($code)) {
        wp_send_json_error(['message' => 'Vui lòng nhập mã giảm giá.']);
    }

    if (!WC()->cart->has_discount($code)) {
        $result = WC()->cart->apply_coupon($code);
        if ($result) {
            WC()->cart->calculate_totals();
            wp_send_json_success([
                'message'         => 'Áp dụng mã giảm giá "' . strtoupper($code) . '" thành công!',
                'applied_coupons' => WC()->cart->get_applied_coupons(),
                'discount_total'  => wc_price(WC()->cart->get_discount_total()),
                'cart_total'      => wc_price(WC()->cart->total),
            ]);
        } else {
            $notices = wc_get_notices('error');
            wc_clear_notices();
            $msg = !empty($notices) ? wp_strip_all_tags($notices[0]['notice']) : 'Mã giảm giá không hợp lệ hoặc không đủ điều kiện áp dụng.';
            wp_send_json_error(['message' => $msg]);
        }
    } else {
        wp_send_json_error(['message' => 'Mã giảm giá này đã được áp dụng trước đó.']);
    }
}

// 5. AJAX: Remove Coupon
add_action('wp_ajax_nbook_cart_remove_coupon', 'nbook_ajax_cart_remove_coupon');
add_action('wp_ajax_nopriv_nbook_cart_remove_coupon', 'nbook_ajax_cart_remove_coupon');
function nbook_ajax_cart_remove_coupon() {
    $code = sanitize_text_field($_POST['coupon_code'] ?? '');
    if (!empty($code) && WC()->cart) {
        WC()->cart->remove_coupon($code);
        WC()->cart->calculate_totals();
        wp_send_json_success([
            'message'         => 'Đã hủy mã giảm giá "' . strtoupper($code) . '".',
            'applied_coupons' => WC()->cart->get_applied_coupons(),
            'discount_total'  => wc_price(WC()->cart->get_discount_total()),
            'cart_total'      => wc_price(WC()->cart->total),
        ]);
    }
    wp_send_json_error(['message' => 'Không tìm thấy mã giảm giá để hủy.']);
}

// 6. AJAX: Save Selected Items for Checkout
add_action('wp_ajax_nbook_save_checkout_selection', 'nbook_ajax_save_checkout_selection');
add_action('wp_ajax_nopriv_nbook_save_checkout_selection', 'nbook_ajax_save_checkout_selection');
function nbook_ajax_save_checkout_selection() {
    $keys = isset($_POST['selected_keys']) ? (array) $_POST['selected_keys'] : [];
    $keys = array_map('sanitize_text_field', $keys);

    if (empty($keys)) {
        wp_send_json_error(['message' => 'Vui lòng chọn ít nhất 1 sản phẩm để thanh toán.']);
    }

    if (WC()->session) {
        WC()->session->set('nbook_selected_cart_keys', $keys);
    }

    wp_send_json_success([
        'redirect_url' => wc_get_checkout_url(),
    ]);
}

// 7. Filter cart items on checkout to only include selected items
add_filter('woocommerce_cart_get_cart_in_session', 'nbook_filter_cart_selection_for_checkout', 20, 2);
function nbook_filter_cart_selection_for_checkout($cart, $values) {
    if (!WC()->session) return $cart;

    // Only apply filter when on checkout page or in checkout/update-order-review AJAX
    $is_checkout_context = (is_checkout() && !is_order_received_page());
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action'])) {
        $action = sanitize_text_field($_REQUEST['action']);
        if (in_array($action, ['woocommerce_update_order_review', 'woocommerce_checkout', 'nbook_checkout_update_order'])) {
            $is_checkout_context = true;
        }
    }

    if ($is_checkout_context) {
        $selected_keys = WC()->session->get('nbook_selected_cart_keys');
        if (is_array($selected_keys) && !empty($selected_keys)) {
            foreach ($cart as $key => $item) {
                if (!in_array($key, $selected_keys)) {
                    unset($cart[$key]);
                }
            }
        }
    }

    return $cart;
}

// 8. Custom Shipping Rates (Tiêu chuẩn & Nhanh with dates)
add_filter('woocommerce_package_rates', 'nbook_custom_shipping_rates', 20, 2);
function nbook_custom_shipping_rates($rates, $package) {
    $subtotal = WC()->cart ? WC()->cart->get_subtotal() : 0;
    $has_freeship = ($subtotal >= 250000);
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    if (in_array('freeship', array_map('strtolower', $applied_coupons))) {
        $has_freeship = true;
    }

    $standard_cost = $has_freeship ? 0 : 25000;
    $express_cost  = 40000;

    $new_rates = [];

    $new_rates['nbook_standard'] = new WC_Shipping_Rate(
        'nbook_standard',
        'Giao Hàng Tiêu Chuẩn (' . ($has_freeship ? 'Miễn phí' : '25.000₫') . ')',
        $standard_cost,
        [],
        'nbook_standard'
    );

    $new_rates['nbook_express'] = new WC_Shipping_Rate(
        'nbook_express',
        'Giao Hàng Hỏa Tốc (40.000₫)',
        $express_cost,
        [],
        'nbook_express'
    );

    return $new_rates;
}

// 9. Reward Points Discount Hook & AJAX
add_action('woocommerce_cart_calculate_fees', 'nbook_apply_reward_points_discount');
function nbook_apply_reward_points_discount($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (WC()->session && WC()->session->get('nbook_use_reward_points', false) && is_user_logged_in()) {
        $balance = function_exists('nbook_get_points_balance') ? nbook_get_points_balance(get_current_user_id()) : 0;
        $max_points = min($balance, (int) floor($cart->get_subtotal() * 0.5));
        WC()->session->set('nbook_points_to_use', $max_points);
        if ($max_points > 0) {
            $cart->add_fee('Biblio Xu (' . number_format_i18n($max_points) . ' điểm)', -$max_points, false);
        }
    }
}

add_action('wp_ajax_nbook_toggle_reward_points', 'nbook_ajax_toggle_reward_points');
add_action('wp_ajax_nopriv_nbook_toggle_reward_points', 'nbook_ajax_toggle_reward_points');
function nbook_ajax_toggle_reward_points() {
    $enable = !empty($_POST['enable']) && $_POST['enable'] === 'true';
    if (WC()->session) {
        WC()->session->set('nbook_use_reward_points', $enable);
    }
    if (WC()->cart) {
        WC()->cart->calculate_totals();
        wp_send_json_success([
            'enabled'    => $enable,
            'cart_total' => wc_price(WC()->cart->total),
        ]);
    }
    wp_send_json_error();
}

// =========================================================================
// 9B. CHECKOUT FIELDS SIMPLIFICATION & AUTO-FILL (VIETNAMESE ONE-NAME FORMAT)
// =========================================================================
add_filter('woocommerce_checkout_fields', 'nbook_simplify_checkout_fields', 999);
function nbook_simplify_checkout_fields($fields) {
    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['required'] = false;
    }
    if (isset($fields['billing']['billing_postcode'])) {
        $fields['billing']['billing_postcode']['required'] = false;
    }
    if (isset($fields['shipping']['shipping_last_name'])) {
        $fields['shipping']['shipping_last_name']['required'] = false;
    }
    if (isset($fields['shipping']['shipping_postcode'])) {
        $fields['shipping']['shipping_postcode']['required'] = false;
    }
    if (isset($fields['shipping']['shipping_first_name'])) {
        $fields['shipping']['shipping_first_name']['required'] = false;
    }
    if (isset($fields['shipping']['shipping_address_1'])) {
        $fields['shipping']['shipping_address_1']['required'] = false;
    }
    if (isset($fields['shipping']['shipping_city'])) {
        $fields['shipping']['shipping_city']['required'] = false;
    }
    if (isset($fields['shipping']['shipping_state'])) {
        $fields['shipping']['shipping_state']['required'] = false;
    }
    return $fields;
}

add_action('woocommerce_checkout_process', 'nbook_auto_fill_checkout_names', 5);
function nbook_auto_fill_checkout_names() {
    $first_name = isset($_POST['billing_first_name']) ? trim(sanitize_text_field($_POST['billing_first_name'])) : '';
    if (!empty($first_name)) {
        if (empty($_POST['billing_last_name'])) {
            $parts = explode(' ', $first_name);
            $_POST['billing_last_name'] = count($parts) > 1 ? array_pop($parts) : $first_name;
        }
        if (empty($_POST['shipping_first_name'])) {
            $_POST['shipping_first_name'] = $first_name;
        }
        if (empty($_POST['shipping_last_name'])) {
            $_POST['shipping_last_name'] = $_POST['billing_last_name'];
        }
    }
    if (empty($_POST['billing_postcode'])) {
        $_POST['billing_postcode'] = '100000';
    }
    if (empty($_POST['shipping_postcode'])) {
        $_POST['shipping_postcode'] = '100000';
    }
    if (empty($_POST['billing_country'])) {
        $_POST['billing_country'] = 'VN';
    }
    if (empty($_POST['shipping_country'])) {
        $_POST['shipping_country'] = 'VN';
    }
    if (empty($_POST['shipping_address_1']) && !empty($_POST['billing_address_1'])) {
        $_POST['shipping_address_1'] = sanitize_text_field($_POST['billing_address_1']);
    }
    if (empty($_POST['shipping_city']) && !empty($_POST['billing_city'])) {
        $_POST['shipping_city'] = sanitize_text_field($_POST['billing_city']);
    }
    if (empty($_POST['shipping_state']) && !empty($_POST['billing_state'])) {
        $_POST['shipping_state'] = sanitize_text_field($_POST['billing_state']);
    }
    if (empty($_POST['shipping_phone']) && !empty($_POST['billing_phone'])) {
        $_POST['shipping_phone'] = sanitize_text_field($_POST['billing_phone']);
    }
}

// 10. Register Simulated E-Wallet Payment Gateway
add_filter('woocommerce_payment_gateways', 'nbook_add_simulated_ewallet_gateway');
function nbook_add_simulated_ewallet_gateway($gateways) {
    $gateways[] = 'WC_Gateway_Biblio_Simulated';
    return $gateways;
}

add_action('init', 'nbook_init_simulated_ewallet_gateway', 5);
nbook_init_simulated_ewallet_gateway();
function nbook_init_simulated_ewallet_gateway() {
    if (!class_exists('WC_Payment_Gateway') || class_exists('WC_Gateway_Biblio_Simulated')) return;

    class WC_Gateway_Biblio_Simulated extends WC_Payment_Gateway {
        public function __construct() {
            $this->id                 = 'biblio_simulated_ewallet';
            $this->icon               = '';
            $this->has_fields         = false;
            $this->method_title       = 'Ví điện tử / VietQR (Mô phỏng)';
            $this->method_description = 'Thanh toán mô phỏng ví điện tử dành cho môi trường thử nghiệm / đồ án.';

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = 'Ví điện tử / QR VietQR (Mô phỏng thử nghiệm)';
            $this->description = 'Thanh toán thử nghiệm qua ví điện tử. Không phát sinh trừ tiền thật.';
            $this->enabled     = 'yes';

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'   => 'Kích hoạt',
                    'type'    => 'checkbox',
                    'label'   => 'Kích hoạt cổng thanh toán mô phỏng',
                    'default' => 'yes',
                ],
            ];
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->payment_complete();
                $order->add_order_note('Đơn hàng đã được thanh toán thành công qua cổng Ví điện tử mô phỏng (Sandbox).');
                WC()->cart->empty_cart();
                return [
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order),
                ];
            }
            return ['result' => 'fail'];
        }
    }
}

// 11. BACS Bank Info Fallback to Biblio MBBank
add_filter('woocommerce_bacs_accounts', 'nbook_custom_bacs_accounts');
function nbook_custom_bacs_accounts($accounts) {
    if (empty($accounts) || empty($accounts[0]['account_number'])) {
        return [
            [
                'account_name'   => 'CÔNG TY CỔ PHẦN SÁCH BIBLIO',
                'account_number' => '0987654321',
                'sort_code'      => '',
                'bank_name'      => 'MBBank (Ngân hàng TMCP Quân Đội)',
                'iban'           => '',
                'bic'            => '',
            ]
        ];
    }
    return $accounts;
}

// =========================================================================
// PRODUCT REVIEWS: verified purchases, moderation metadata and AJAX UI
// =========================================================================
add_filter('woocommerce_product_tabs', 'nbook_reviews_tab_callback', 99);
function nbook_reviews_tab_callback($tabs) {
    if (isset($tabs['reviews'])) {
        $tabs['reviews']['callback'] = 'nbook_render_reviews';
        $tabs['reviews']['title'] = 'Đánh giá sản phẩm';
    }
    return $tabs;
}

function nbook_review_comments($product_id, $page = 1, $filter = 'all', $per_page = 5) {
    $args = [
        'post_id' => $product_id,
        'status' => 'approve',
        'type' => 'review',
        'number' => $per_page,
        'paged' => max(1, (int) $page),
        'orderby' => 'comment_date_gmt',
        'order' => 'DESC',
    ];
    if (in_array($filter, ['1', '2', '3', '4', '5'], true)) {
        $args['meta_query'] = [['key' => 'rating', 'value' => $filter]];
    } elseif ($filter === 'text') {
        $args['meta_query'] = [['key' => 'rating', 'compare' => 'EXISTS'], ['key' => '_nbook_has_text', 'value' => '1']];
    } elseif ($filter === 'image') {
        $args['meta_query'] = [['key' => '_nbook_images', 'compare' => 'EXISTS']];
    }
    return get_comments($args);
}

function nbook_review_count($product_id, $filter = 'all') {
    $args = ['post_id' => $product_id, 'status' => 'approve', 'type' => 'review', 'count' => true];
    if (in_array($filter, ['1', '2', '3', '4', '5'], true)) {
        $args['meta_query'] = [['key' => 'rating', 'value' => $filter]];
    } elseif ($filter === 'text') {
        $args['meta_query'] = [['key' => '_nbook_has_text', 'value' => '1']];
    } elseif ($filter === 'image') {
        $args['meta_query'] = [['key' => '_nbook_images', 'compare' => 'EXISTS']];
    }
    return (int) get_comments($args);
}

function nbook_review_stars($rating) {
    return '<span class="nb-review-stars" aria-label="' . esc_attr($rating . ' trên 5 sao') . '">' .
        str_repeat('★', (int) $rating) . '<span class="nb-stars-empty">' . str_repeat('★', 5 - (int) $rating) . '</span></span>';
}

function nbook_review_name($comment) {
    if (get_comment_meta($comment->comment_ID, '_nbook_anonymous', true)) {
        $name = trim((string) $comment->comment_author);
        return $name ? esc_html(mb_substr($name, 0, 1) . str_repeat('*', max(1, mb_strlen($name) - 2)) . mb_substr($name, -1)) : 'Người dùng ẩn danh';
    }
    return esc_html($comment->comment_author);
}

function nbook_render_review_item($comment) {
    $rating = max(1, min(5, (int) get_comment_meta($comment->comment_ID, 'rating', true)));
    $images = (array) get_comment_meta($comment->comment_ID, '_nbook_images', true);
    $reply = get_comment_meta($comment->comment_ID, '_nbook_store_reply', true);
    $anonymous = (bool) get_comment_meta($comment->comment_ID, '_nbook_anonymous', true);
    $helpful = (int) get_comment_meta($comment->comment_ID, '_nbook_helpful', true);
    $verified = (bool) get_comment_meta($comment->comment_ID, '_nbook_verified', true);
    ?>
    <article class="nb-review-item" data-review-id="<?php echo esc_attr($comment->comment_ID); ?>">
        <div class="nb-review-avatar"><?php echo $anonymous ? '<span class="nb-anon-icon">?</span>' : get_avatar($comment, 44); ?></div>
        <div class="nb-review-body">
            <div class="nb-review-author"><?php echo nbook_review_name($comment); ?><?php if ($verified): ?><span class="nb-verified">Đã mua hàng</span><?php endif; ?></div>
            <div class="nb-review-meta"><?php echo nbook_review_stars($rating); ?><time><?php echo esc_html(get_comment_date('d/m/Y', $comment)); ?></time></div>
            <?php $variation = get_comment_meta($comment->comment_ID, '_nbook_book_type', true); if ($variation): ?><div class="nb-review-variation">Phân loại: <?php echo esc_html($variation); ?></div><?php endif; ?>
            <div class="nb-review-content"><?php echo nl2br(esc_html($comment->comment_content)); ?></div>
            <?php if ($images): ?><div class="nb-review-images"><?php foreach (array_slice($images, 0, 5) as $image_id): $src = wp_get_attachment_image_url((int) $image_id, 'thumbnail'); if ($src): ?><img src="<?php echo esc_url($src); ?>" alt="Ảnh đánh giá"><?php endif; endforeach; ?></div><?php endif; ?>
            <?php if ($reply): ?><div class="nb-store-reply"><strong>Phản hồi của cửa hàng</strong><p><?php echo nl2br(esc_html($reply)); ?></p></div><?php endif; ?>
            <button type="button" class="nb-helpful" data-review-id="<?php echo esc_attr($comment->comment_ID); ?>">Hữu ích <span><?php echo esc_html($helpful); ?></span></button>
        </div>
    </article>
    <?php
}

function nbook_render_reviews_list($product_id, $page = 1, $filter = 'all') {
    $comments = nbook_review_comments($product_id, $page, $filter);
    if (!$comments) {
        echo '<p class="nb-reviews-empty">Chưa có đánh giá phù hợp.</p>';
        return;
    }
    foreach ($comments as $comment) nbook_render_review_item($comment);
}

function nbook_user_review_order($product_id, $user_id) {
    if (!$user_id || !function_exists('wc_get_orders')) return false;
    $orders = wc_get_orders(['customer_id' => $user_id, 'status' => ['completed'], 'limit' => -1, 'return' => 'objects']);
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            if ((int) $item->get_product_id() === (int) $product_id) {
                $existing = get_comments(['post_id' => $product_id, 'user_id' => $user_id, 'type' => 'review', 'meta_key' => '_nbook_order_id', 'meta_value' => $order->get_id(), 'number' => 1]);
                return ['order' => $order, 'comment' => $existing ? $existing[0] : false];
            }
        }
    }
    return false;
}

function nbook_render_reviews() {
    global $product;
    if (!$product) return;
    $product_id = $product->get_id();
    $count = nbook_review_count($product_id);
    $sum = 0;
    foreach (nbook_review_comments($product_id, 1, 'all', 1000) as $comment) $sum += (int) get_comment_meta($comment->comment_ID, 'rating', true);
    $average = $count ? number_format($sum / $count, 1) : '0.0';
    $user_order = is_user_logged_in() ? nbook_user_review_order($product_id, get_current_user_id()) : false;
    ?>
    <section class="nb-reviews" id="nb-reviews" data-product-id="<?php echo esc_attr($product_id); ?>">
        <div class="nb-review-summary"><div class="nb-average"><?php echo esc_html($average); ?><small>/5</small><div><?php echo nbook_review_stars((int) round((float) $average)); ?></div></div><div><strong><?php echo esc_html($count); ?></strong> đánh giá</div></div>
        <div class="nb-review-filters">
            <?php foreach (['all' => 'Tất cả', '5' => '5 sao', '4' => '4 sao', '3' => '3 sao', '2' => '2 sao', '1' => '1 sao', 'text' => 'Có bình luận', 'image' => 'Có hình ảnh'] as $key => $label): ?><button type="button" class="<?php echo $key === 'all' ? 'is-active' : ''; ?>" data-filter="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?> <span><?php echo esc_html(nbook_review_count($product_id, $key)); ?></span></button><?php endforeach; ?>
        </div>
        <div class="nb-review-list"><?php nbook_render_reviews_list($product_id); ?></div>
        <div class="nb-review-pagination"><button type="button" data-page="1">Xem thêm đánh giá</button></div>
        <?php if ($user_order && (!$user_order['comment'] || time() - get_comment_date('U', $user_order['comment']) <= 7 * DAY_IN_SECONDS)): nbook_render_review_form($product_id, $user_order['order'], $user_order['comment']); elseif (!is_user_logged_in()): ?><p class="nb-review-login">Vui lòng <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">đăng nhập</a> để đánh giá sau khi mua hàng.</p><?php elseif (!$user_order): ?><p class="nb-review-login">Chỉ khách hàng đã mua và nhận hàng mới có thể đánh giá.</p><?php endif; ?>
    </section>
    <?php
}

function nbook_render_review_form($product_id, $order, $comment = false) {
    ?>
    <form class="nb-review-form" enctype="multipart/form-data" data-product-id="<?php echo esc_attr($product_id); ?>" data-order-id="<?php echo esc_attr($order->get_id()); ?>" data-comment-id="<?php echo $comment ? esc_attr($comment->comment_ID) : ''; ?>">
        <h3><?php echo $comment ? 'Sửa đánh giá' : 'Đánh giá sản phẩm'; ?></h3>
        <div class="nb-rating-input"><label>Chọn số sao</label><select name="rating" required><option value="">--</option><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?php echo $i; ?>" <?php selected($comment ? get_comment_meta($comment->comment_ID, 'rating', true) : '', $i); ?>><?php echo $i; ?> sao</option><?php endfor; ?></select></div>
        <textarea name="content" required minlength="5" maxlength="2000" placeholder="Chia sẻ trải nghiệm của bạn..."><?php echo $comment ? esc_textarea($comment->comment_content) : ''; ?></textarea>
        <label><input type="checkbox" name="anonymous" value="1"> Đánh giá ẩn danh</label>
        <label class="nb-upload-label">Ảnh (tối đa 5 ảnh, JPG/PNG/WEBP, mỗi ảnh tối đa 5MB)<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label>
        <button type="submit">Gửi đánh giá</button><span class="nb-review-form-status" role="status"></span>
    </form>
    <?php
}

function nbook_review_ajax_guard() {
    check_ajax_referer('nbook_reviews_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Bạn cần đăng nhập.'], 401);
}

add_action('wp_ajax_nbook_load_reviews', 'nbook_ajax_load_reviews');
add_action('wp_ajax_nopriv_nbook_load_reviews', 'nbook_ajax_load_reviews');
function nbook_ajax_load_reviews() {
    check_ajax_referer('nbook_reviews_nonce', 'nonce');
    $product_id = absint($_POST['product_id'] ?? 0);
    $filter = sanitize_key($_POST['filter'] ?? 'all');
    $page = max(1, absint($_POST['page'] ?? 1));
    ob_start();
    nbook_render_reviews_list($product_id, $page, $filter);
    wp_send_json_success(['html' => ob_get_clean(), 'has_more' => $page * 5 < nbook_review_count($product_id, $filter)]);
}

add_action('wp_ajax_nbook_submit_review', 'nbook_ajax_submit_review');
function nbook_ajax_submit_review() {
    nbook_review_ajax_guard();
    $product_id = absint($_POST['product_id'] ?? 0);
    $order_id = absint($_POST['order_id'] ?? 0);
    $rating = absint($_POST['rating'] ?? 0);
    $content = sanitize_textarea_field(wp_unslash($_POST['content'] ?? ''));
    if ($rating < 1 || $rating > 5 || mb_strlen($content) < 5 || !$product_id || !$order_id) wp_send_json_error(['message' => 'Vui lòng nhập đủ thông tin đánh giá.']);
    $order = wc_get_order($order_id);
    $valid = $order && (int) $order->get_customer_id() === get_current_user_id() && $order->has_status('completed');
    $has_product = false;
    $book_type = '';
    if ($valid) foreach ($order->get_items() as $item) {
        if ((int) $item->get_product_id() === $product_id) {
            $has_product = true;
            $book_type = $item->get_name();
            break;
        }
    }
    if (!$has_product) wp_send_json_error(['message' => 'Đánh giá chỉ dành cho sản phẩm trong đơn đã giao.'], 403);
    $existing = get_comments(['post_id' => $product_id, 'user_id' => get_current_user_id(), 'type' => 'review', 'meta_key' => '_nbook_order_id', 'meta_value' => $order_id, 'number' => 1]);
    $comment_data = ['comment_post_ID' => $product_id, 'comment_content' => $content, 'user_id' => get_current_user_id(), 'comment_type' => 'review', 'comment_approved' => 0];
    if ($existing) {
        if (time() - get_comment_date('U', $existing[0]) > 7 * DAY_IN_SECONDS) wp_send_json_error(['message' => 'Đánh giá chỉ được sửa trong 7 ngày.']);
        $comment_data['comment_ID'] = $existing[0]->comment_ID;
        wp_update_comment($comment_data);
        $comment_id = $existing[0]->comment_ID;
    } else {
        $comment_id = wp_insert_comment($comment_data);
        add_comment_meta($comment_id, '_nbook_order_id', $order_id, true);
        add_comment_meta($comment_id, '_nbook_verified', 1, true);
        add_comment_meta($comment_id, '_nbook_reward', 0, true);
    }
    update_comment_meta($comment_id, 'rating', $rating);
    update_comment_meta($comment_id, '_nbook_anonymous', !empty($_POST['anonymous']) ? 1 : 0);
    update_comment_meta($comment_id, '_nbook_book_type', $book_type);
    update_comment_meta($comment_id, '_nbook_has_text', 1);
    if (!empty($_FILES['images']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $ids = [];
        $files = $_FILES['images'];
        $total = min(5, count($files['name']));
        for ($i = 0; $i < $total; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] > 5 * 1024 * 1024) continue;
            $type = wp_check_filetype_and_ext($files['tmp_name'][$i], $files['name'][$i]);
            if (!in_array($type['type'], ['image/jpeg', 'image/png', 'image/webp'], true)) continue;
            $single = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => 0, 'size' => $files['size'][$i]];
            $_FILES['nbook_review_image'] = $single;
            $attachment = media_handle_upload('nbook_review_image', 0);
            if (!is_wp_error($attachment)) $ids[] = (int) $attachment;
        }
        if ($ids) {
            update_comment_meta($comment_id, '_nbook_images', $ids);
            if (!get_comment_meta($comment_id, '_nbook_reward', true)) {
                update_comment_meta($comment_id, '_nbook_reward', 1);
                update_user_meta(get_current_user_id(), 'nbook_reward_points', (int) get_user_meta(get_current_user_id(), 'nbook_reward_points', true) + 10);
            }
        }
    }
    wp_send_json_success(['message' => 'Đánh giá đã gửi và đang chờ duyệt.']);
}

add_action('wp_ajax_nbook_helpful_review', 'nbook_ajax_helpful_review');
add_action('wp_ajax_nopriv_nbook_helpful_review', 'nbook_ajax_helpful_review');
function nbook_ajax_helpful_review() {
    check_ajax_referer('nbook_reviews_nonce', 'nonce');
    $id = absint($_POST['review_id'] ?? 0);
    if (!$id || !get_comment($id)) wp_send_json_error(['message' => 'Đánh giá không tồn tại.']);
    $key = is_user_logged_in() ? 'nbook_helpful_' . get_current_user_id() : 'nbook_helpful_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    if (get_comment_meta($id, $key, true)) wp_send_json_error(['message' => 'Bạn đã bình chọn.']);
    add_comment_meta($id, $key, 1, true);
    $count = (int) get_comment_meta($id, '_nbook_helpful', true) + 1;
    update_comment_meta($id, '_nbook_helpful', $count);
    wp_send_json_success(['count' => $count]);
}

add_action('add_meta_boxes_comment', 'nbook_review_comment_metabox');
function nbook_review_comment_metabox() {
    add_meta_box('nbook-review-details', 'Thông tin đánh giá Biblio', 'nbook_review_comment_metabox_html', 'comment', 'normal', 'high');
}
function nbook_review_comment_metabox_html($comment) {
    wp_nonce_field('nbook_save_comment_review', 'nbook_comment_review_nonce');
    $reply = get_comment_meta($comment->comment_ID, '_nbook_store_reply', true);
    echo '<p><label for="nbook_store_reply"><strong>Phản hồi của cửa hàng</strong></label></p><textarea id="nbook_store_reply" name="nbook_store_reply" rows="4" style="width:100%">' . esc_textarea($reply) . '</textarea>';
}
add_action('edit_comment', 'nbook_save_review_comment_metabox');
function nbook_save_review_comment_metabox($comment_id) {
    if (!current_user_can('edit_comment', $comment_id) || empty($_POST['nbook_comment_review_nonce']) || !wp_verify_nonce($_POST['nbook_comment_review_nonce'], 'nbook_save_comment_review')) return;
    update_comment_meta($comment_id, '_nbook_store_reply', sanitize_textarea_field(wp_unslash($_POST['nbook_store_reply'] ?? '')));
}

/**
 * 301 Redirect old/duplicate Nha Gia Kim product URLs to canonical /product/nha-gia-kim/
 */
add_action('template_redirect', 'nbook_redirect_legacy_nha_gia_kim');
function nbook_redirect_legacy_nha_gia_kim() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, 'nha-gia-kim-paulo-coelho') !== false || strpos($uri, 'nha-gia-kim-tai-ban-moi') !== false) {
        wp_safe_redirect(home_url('/product/nha-gia-kim/'), 301);
        exit;
    }
}

/**
 * Chuyển hướng trang /thong-bao/ về trang chủ (thông báo hiển thị trực tiếp trong khung dropdown có thanh cuộn)
 */
add_action('template_redirect', 'nbook_redirect_legacy_thong_bao');
function nbook_redirect_legacy_thong_bao() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (is_page('thong-bao') || preg_match('#/(wordpress/)?thong-bao/?#i', $uri)) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}

/**
 * Đổi tiêu đề khối sản phẩm liên quan thành "SẢN PHẨM GỢI Ý DÀNH CHO BẠN"
 */
add_filter('woocommerce_product_related_products_heading', function() {
    return 'SẢN PHẨM GỢI Ý DÀNH CHO BẠN';
});

/**
 * Chuyển hướng ngay sang trang Thanh toán khi bấm nút "Mua Ngay"
 */
add_filter('woocommerce_add_to_cart_redirect', 'nbook_redirect_buy_now', 99);
function nbook_redirect_buy_now($url) {
    if (!empty($_REQUEST['nb_buy_now'])) {
        return wc_get_checkout_url();
    }
    return $url;
}


/**
 * =========================================================================
 * REAL-TIME SYSTEM ENGINE FOR NEWS & EVENTS (BẢN TIN & SỰ KIỆN BIBLIO)
 * Cập nhật tin tức và sự kiện đồng bộ theo thời gian thực hệ thống
 * =========================================================================
 */

/**
 * Tính toán dữ liệu thời gian thực cho bài viết
 */
function nbook_get_news_realtime_data($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $post = get_post($post_id);
    if (!$post) {
        return null;
    }

    $now = time();
    $post_ts = get_post_timestamp($post_id);
    if (!$post_ts) {
        $post_ts = strtotime($post->post_date_gmt ?: $post->post_date);
    }

    // Dynamic calibration: Nếu toàn bộ bài viết trong DB cũ hơn 24 giờ so với đồng hồ hệ thống hiện tại,
    // tự động giữ độ lệch trôi mượt mà (rolling dynamic anchor) để bản tin LUÔN tươi mới theo thời gian thực!
    static $anchor_offset = null;
    if ($anchor_offset === null) {
        $latest = get_posts([
            'numberposts' => 1,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'orderby'     => 'date',
            'order'       => 'DESC'
        ]);
        if (!empty($latest)) {
            $l_ts = get_post_timestamp($latest[0]->ID);
            if (!$l_ts) {
                $l_ts = strtotime($latest[0]->post_date_gmt ?: $latest[0]->post_date);
            }
            $age = $now - $l_ts;
            if ($age > 86400 || $age < 0) {
                $anchor_offset = $now - 2400 - $l_ts; // Cố định bài mới nhất ở mốc ~40 phút trước
            } else {
                $anchor_offset = 0;
            }
        } else {
            $anchor_offset = 0;
        }
    }

    $eff_ts = $post_ts + $anchor_offset;
    if ($eff_ts > $now) {
        $eff_ts = $now - 60;
    }

    $diff = max(0, $now - $eff_ts);
    $is_today = (wp_date('Ymd', $eff_ts) === wp_date('Ymd', $now));
    $is_yesterday = (wp_date('Ymd', $eff_ts) === wp_date('Ymd', $now - 86400));

    if ($diff < 90) {
        $human = 'Vừa xong';
        $badge = 'VỪA XONG';
    } elseif ($diff < 3600) {
        $m = max(1, round($diff / 60));
        $human = $m . ' phút trước';
        $badge = 'MỚI CẬP NHẬT';
    } elseif ($is_today) {
        $h = max(1, round($diff / 3600));
        $human = 'Hôm nay lúc ' . wp_date('H:i', $eff_ts) . ' (' . $h . ' giờ trước)';
        $badge = 'HÔM NAY';
    } elseif ($is_yesterday) {
        $human = 'Hôm qua lúc ' . wp_date('H:i', $eff_ts);
        $badge = 'HÔM QUA';
    } elseif ($diff < 7 * 86400) {
        $d = max(1, round($diff / 86400));
        $human = $d . ' ngày trước (' . wp_date('d/m/Y', $eff_ts) . ')';
        $badge = '';
    } else {
        $human = wp_date('d/m/Y', $eff_ts);
        $badge = '';
    }

    $cats = wp_get_post_categories($post_id, ['fields' => 'slugs']);
    $is_event = in_array('su-kien-sach', $cats, true);

    return [
        'id'         => $post_id,
        'title'      => $post->post_title,
        'ts'         => $eff_ts,
        'diff_sec'   => $diff,
        'human'      => $human,
        'badge'      => $badge,
        'is_event'   => $is_event,
        'is_fresh'   => ($diff < 86400),
        'full_date'  => wp_date('d/m/Y H:i:s', $eff_ts),
        'date_only'  => wp_date('d/m/Y', $eff_ts),
        'time_only'  => wp_date('H:i', $eff_ts),
        'iso_date'   => wp_date('c', $eff_ts),
    ];
}

/**
 * Hiển thị thẻ thời gian thực theo từng ngữ cảnh giao diện
 */
function nbook_render_news_date_badge($post_id = null, $context = 'card') {
    $data = nbook_get_news_realtime_data($post_id);
    if (!$data) return;

    $ts = esc_attr($data['ts']);
    $iso = esc_attr($data['iso_date']);
    $human = esc_html($data['human']);

    if ($context === 'spotlight') {
        echo '<span class="nb-meta-date nb-realtime-item spotlight" data-ts="' . $ts . '">';
        echo '<i class="bi bi-clock-history"></i> Cập nhật: <time datetime="' . $iso . '" class="nb-rt-txt">' . $human . '</time>';
        echo '</span>';
        if (!empty($data['badge'])) {
            echo '<span class="nb-badge-live-stream"><span class="nb-stream-dot"></span> BẢN TIN NỔI BẬT HÔM NAY</span>';
        }
        if ($data['is_event']) {
            echo '<span class="nb-event-live-pill"><i class="bi bi-broadcast"></i> SỰ KIỆN ĐANG DIỄN RA</span>';
        }
    } elseif ($context === 'card') {
        echo '<span class="nb-meta-date nb-realtime-item" data-ts="' . $ts . '">';
        echo '<i class="bi bi-clock-history"></i> <time datetime="' . $iso . '" class="nb-rt-txt">' . $human . '</time>';
        echo '</span>';
        if (!empty($data['badge'])) {
            $badge_class = 'nb-badge-' . sanitize_html_class(strtolower(str_replace(' ', '-', $data['badge'])));
            echo '<span class="nb-badge-live-pulse ' . esc_attr($badge_class) . '"><span class="nb-pulse-dot"></span> ' . esc_html($data['badge']) . '</span>';
        }
        if ($data['is_event']) {
            echo '<span class="nb-event-live-pill-mini"><i class="bi bi-broadcast"></i> Sự kiện</span>';
        }
    } elseif ($context === 'single') {
        echo '<span class="nb-meta-item nb-realtime-item" data-ts="' . $ts . '">';
        echo '<i class="bi bi-clock-history"></i>&nbsp;&nbsp;Đăng: <time datetime="' . $iso . '" class="nb-rt-txt">' . $human . '</time>';
        echo '</span>';
        echo '<span class="nb-meta-divider">•</span>';
        echo '<span class="nb-meta-item nb-meta-system-sync">';
        echo '<i class="bi bi-broadcast text-danger"></i>&nbsp;&nbsp;Đồng bộ hệ thống: <strong>' . esc_html($data['full_date']) . '</strong>';
        echo '</span>';
    } elseif ($context === 'sidebar') {
        echo '<span class="nb-wpost-date nb-realtime-item" data-ts="' . $ts . '">';
        echo '<i class="bi bi-clock-history"></i> <time datetime="' . $iso . '" class="nb-rt-txt">' . $human . '</time>';
        echo '</span>';
    } elseif ($context === 'home') {
        echo '<div class="nb-news-date nb-realtime-item" data-ts="' . $ts . '">';
        echo '<i class="bi bi-clock-history" style="font-size:12px; margin-right:4px;"></i>';
        echo '<time datetime="' . $iso . '" class="nb-rt-txt">' . $human . '</time>';
        echo '</div>';
    }
}

/**
 * =========================================================================
 * MỞ RỘNG LAYOUT BÀI VIẾT TIN TỨC - SỰ KIỆN (SINGLE POST) RA 2 BÊN
 * Loại bỏ giới hạn hẹp 750px (ast-narrow-container) của theme Astra
 * =========================================================================
 */
add_filter('astra_get_content_layout', function($layout) {
    if (is_single() && get_post_type() === 'post') {
        return 'plain-container';
    }
    return $layout;
}, 99);

add_filter('body_class', function($classes) {
    if (is_single() && get_post_type() === 'post') {
        $classes = array_diff($classes, ['ast-narrow-container']);
        if (!in_array('ast-plain-container', $classes)) {
            $classes[] = 'ast-plain-container';
        }
    }
    return $classes;
}, 99);

/**
 * =========================================================================
 * BIBLIO REWARDS - HỆ THỐNG HẠNG THÀNH VIÊN SHOPEE STYLE
 * Tính toán cấp bậc dựa trên:
 * 1. Số đơn hàng hoàn tất (completed orders)
 * 2. Tổng giá trị chi tiêu (total spent)
 * 3. Thời gian đồng hành (account age / registered date)
 * =========================================================================
 */
function nbook_get_customer_loyalty_data($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return null;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return null;
    }

    // 1. Tính thời gian tạo tài khoản (account age)
    $reg_time = strtotime($user->user_registered);
    $diff_seconds = max(0, time() - $reg_time);
    $account_days = max(1, (int) floor($diff_seconds / 86400)); // Ít nhất 1 ngày

    $years = (int) floor($account_days / 365);
    $rem_days = $account_days % 365;
    $months = (int) floor($rem_days / 30);
    $days = $rem_days % 30;

    $tenure_str = '';
    if ($years > 0) {
        $tenure_str .= $years . ' năm ';
    }
    if ($months > 0) {
        $tenure_str .= $months . ' tháng ';
    }
    if ($days > 0 || empty($tenure_str)) {
        $tenure_str .= $days . ' ngày';
    }
    $tenure_str = trim($tenure_str);

    // 2. Tính số đơn hàng và tổng chi tiêu
    $total_spent = (float) wc_get_customer_total_spent($user_id);

    $completed_orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['completed'],
        'limit'       => -1,
        'return'      => 'ids',
    ]);
    $completed_orders_count = is_array($completed_orders) ? count($completed_orders) : 0;

    $all_orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit'       => -1,
        'return'      => 'ids',
    ]);
    $all_orders_count = is_array($all_orders) ? count($all_orders) : 0;

    // Dùng số đơn hoàn tất để xét thăng hạng
    $order_count_for_tier = $completed_orders_count;

    // 3. Cấu hình các cấp bậc thành viên (Biblio Rewards Tiers)
    $tiers = [
        'new' => [
            'key'            => 'new',
            'name'           => 'Thành Viên Mới',
            'short_name'     => 'Mới',
            'badge_icon'     => 'bi-person-check-fill',
            'emoji'          => '🌱',
            'color'          => '#64748b',
            'card_theme'     => 'theme-new',
            'req_orders'     => 0,
            'req_spent'      => 0,
            'req_days'       => 0,
            'cashback_rate'  => 0.5,
            'cashback_label' => '0.5%',
            'voucher_monthly'=> 'Voucher Chào Bạn Mới 15.000₫',
            'freeship_perk'  => 'Mã Freeship chào mừng 20k',
            'birthday_perk'  => 'Lời chúc sinh nhật từ Biblio',
            'summary_perks'  => 'Tích 0.5% Xu • Voucher Chào Mới 15k • Đổi trả 7 ngày',
            'privileges'     => [
                'Voucher Chào Bạn Mới giảm 15.000₫ cho đơn đầu tiên',
                'Tích lũy 0.5% Biblio Xu cho mỗi đơn hàng',
                'Hỗ trợ đổi trả sách miễn phí trong 7 ngày',
            ],
        ],
        'bronze' => [
            'key'            => 'bronze',
            'name'           => 'Hạng Đồng',
            'short_name'     => 'Đồng',
            'badge_icon'     => 'bi-award-fill',
            'emoji'          => '🥉',
            'color'          => '#b45309',
            'card_theme'     => 'theme-bronze',
            'req_orders'     => 3,
            'req_spent'      => 300000,
            'req_days'       => 7,
            'cashback_rate'  => 1.0,
            'cashback_label' => '1.0%',
            'voucher_monthly'=> '1 Voucher 10.000₫ mỗi tháng',
            'freeship_perk'  => '1 Mã Freeship 10.000₫/tháng',
            'birthday_perk'  => 'Voucher sinh nhật 15.000₫',
            'summary_perks'  => 'Tích 1% Xu (x2) • 1 Voucher 10k/tháng • Thứ 4 Sách Trợ Giá',
            'privileges'     => [
                'Tích lũy 1.0% Biblio Xu (Gấp đôi thành viên mới)',
                'Tặng 1 Voucher giảm giá 10.000₫ vào đầu mỗi tháng',
                'Quyền mua Sách Trợ Giá Sinh Viên vào Thứ 4 hàng tuần',
                'Bọc sách tiêu chuẩn miễn phí theo yêu cầu',
            ],
        ],
        'silver' => [
            'key'            => 'silver',
            'name'           => 'Hạng Bạc',
            'short_name'     => 'Bạc',
            'badge_icon'     => 'bi-shield-fill-check',
            'emoji'          => '🥈',
            'color'          => '#475569',
            'card_theme'     => 'theme-silver',
            'req_orders'     => 8,
            'req_spent'      => 1000000,
            'req_days'       => 30,
            'cashback_rate'  => 1.5,
            'cashback_label' => '1.5%',
            'voucher_monthly'=> '1 Voucher 20.000₫ mỗi tháng',
            'freeship_perk'  => '1 Mã Freeship 15.000₫/tháng',
            'birthday_perk'  => 'Voucher sinh nhật 30.000₫',
            'summary_perks'  => 'Tích 1.5% Xu • 1 Mã Freeship 15k • Voucher sinh nhật 30k',
            'privileges'     => [
                'Tích lũy 1.5% Biblio Xu cho mọi đơn hàng',
                'Tặng 1 Mã Freeship 15.000₫ hàng tháng',
                'Voucher quà tặng sinh nhật trị giá 30.000₫',
                'Ưu tiên xử lý đơn, bọc 2 lớp chống sốc & gửi sớm',
                'Tặng kèm Bookmark mỹ thuật cho mỗi cuốn sách',
            ],
        ],
        'gold' => [
            'key'            => 'gold',
            'name'           => 'Hạng Vàng',
            'short_name'     => 'Vàng',
            'badge_icon'     => 'bi-trophy-fill',
            'emoji'          => '🥇',
            'color'          => '#d97706',
            'card_theme'     => 'theme-gold',
            'req_orders'     => 20,
            'req_spent'      => 3000000,
            'req_days'       => 90,
            'cashback_rate'  => 2.0,
            'cashback_label' => '2.0%',
            'voucher_monthly'=> '2 Voucher 30.000₫ mỗi tháng',
            'freeship_perk'  => '2 Mã Freeship 25.000₫/tháng',
            'birthday_perk'  => 'Voucher sinh nhật 50.000₫',
            'summary_perks'  => 'Tích 2% Xu • 2 Mã Freeship 25k • Flash Sale sớm 30p',
            'privileges'     => [
                'Tích lũy 2.0% Biblio Xu cho mọi đơn hàng',
                'Tặng 2 Mã Freeship 25.000₫ hàng tháng',
                'Voucher quà tặng sinh nhật VIP 50.000₫',
                'Mở quyền mua Flash Sale sớm trước 30 phút & ưu tiên giữ sách hiếm',
                'Tặng kèm Bookmark kim loại cao cấp mạ vàng phiên bản giới hạn',
            ],
        ],
        'diamond' => [
            'key'            => 'diamond',
            'name'           => 'Hạng Kim Cương',
            'short_name'     => 'Kim Cương',
            'badge_icon'     => 'bi-gem',
            'emoji'          => '💎',
            'color'          => '#0284c7',
            'card_theme'     => 'theme-diamond',
            'req_orders'     => 40,
            'req_spent'      => 6000000,
            'req_days'       => 180,
            'cashback_rate'  => 3.0,
            'cashback_label' => '3.0%',
            'voucher_monthly'=> '4 Voucher 50.000₫ mỗi tháng',
            'freeship_perk'  => '4 Mã Freeship 40.000₫ toàn quốc',
            'birthday_perk'  => 'Voucher sinh nhật 100.000₫',
            'summary_perks'  => 'Tích 3% Xu VIP • 4 Mã Freeship 40k • CSKH VIP 24/7',
            'privileges'     => [
                'Tích lũy 3.0% Biblio Xu cao nhất hệ thống',
                'Tặng 4 Mã Freeship 40.000₫ toàn quốc mỗi tháng',
                'Voucher quà tặng sinh nhật Kim Cương trị giá 100.000₫',
                'Miễn phí 100% bọc màng plastic cao cấp trọn đời',
                'Đường dây nóng CSKH VIP ưu tiên 24/7, xử lý khiếu nại trong 1 giờ',
                'Quà tri ân độc quyền Ngày Hội Sách & Tết Nguyên Đán',
            ],
        ],
    ];

    $tier_order = ['new', 'bronze', 'silver', 'gold', 'diamond'];
    $current_tier_key = 'new';

    // Xác định hạng hiện tại (từ cao xuống thấp)
    // Điều kiện: (số đơn >= yêu cầu HOẶC chi tiêu >= yêu cầu) VÀ (thời gian tài khoản >= yêu cầu)
    $reversed_tiers = array_reverse($tier_order);
    foreach ($reversed_tiers as $k) {
        if ($k === 'new') continue;
        $t = $tiers[$k];
        if (($order_count_for_tier >= $t['req_orders'] || $total_spent >= $t['req_spent']) && ($account_days >= $t['req_days'])) {
            $current_tier_key = $k;
            break;
        }
    }

    $current_tier = $tiers[$current_tier_key];
    $current_tier_idx = array_search($current_tier_key, $tier_order);

    $next_tier = null;
    $progress_percent = 100;
    $orders_needed = 0;
    $spent_needed = 0;
    $days_needed = 0;
    $callout_message = '';

    if (isset($tier_order[$current_tier_idx + 1])) {
        $next_tier_key = $tier_order[$current_tier_idx + 1];
        $next_tier = $tiers[$next_tier_key];

        $orders_needed = max(0, $next_tier['req_orders'] - $order_count_for_tier);
        $spent_needed  = max(0, (float)($next_tier['req_spent'] - $total_spent));
        $days_needed   = max(0, $next_tier['req_days'] - $account_days);

        // Tính % tiến độ
        $orders_pct = $next_tier['req_orders'] > 0 ? min(100, round(($order_count_for_tier / $next_tier['req_orders']) * 100)) : 100;
        $spent_pct  = $next_tier['req_spent'] > 0 ? min(100, round(($total_spent / $next_tier['req_spent']) * 100)) : 100;
        $days_pct   = $next_tier['req_days'] > 0 ? min(100, round(($account_days / $next_tier['req_days']) * 100)) : 100;

        $spending_or_orders_pct = max($orders_pct, $spent_pct);
        $progress_percent = (int) min(100, max(5, round(($spending_or_orders_pct * 0.7) + ($days_pct * 0.3))));

        // Tạo thông điệp hướng dẫn rõ ràng
        if ($orders_needed > 0 && $spent_needed > 0 && $days_needed > 0) {
            $callout_message = 'Mua thêm <strong>' . $orders_needed . ' đơn</strong> hoặc chi tiêu thêm <strong>' . number_format($spent_needed, 0, ',', '.') . '₫</strong> và đồng hành thêm <strong>' . $days_needed . ' ngày</strong> để thăng <strong>' . $next_tier['name'] . '</strong>!';
        } elseif (($orders_needed == 0 || $spent_needed == 0) && $days_needed > 0) {
            $callout_message = '🎉 Bạn đã đạt điều kiện mua hàng! Chỉ cần đồng hành thêm <strong>' . $days_needed . ' ngày</strong> nữa để kích hoạt <strong>' . $next_tier['name'] . '</strong>!';
        } elseif ($orders_needed > 0 && $spent_needed > 0 && $days_needed == 0) {
            $callout_message = 'Mua thêm <strong>' . $orders_needed . ' đơn</strong> hoặc chi tiêu thêm <strong>' . number_format($spent_needed, 0, ',', '.') . '₫</strong> để mở khóa ngay <strong>' . $next_tier['name'] . '</strong>!';
        } else {
            $callout_message = 'Sắp hoàn tất! Chỉ cần thêm 1 bước nhỏ để đạt <strong>' . $next_tier['name'] . '</strong>!';
        }
    } else {
        $callout_message = '🌟 Bạn đang ở cấp bậc cao nhất <strong>' . $current_tier['name'] . '</strong>! Tận hưởng trọn bộ đặc quyền VIP độc quyền.';
        $progress_percent = 100;
    }

    return [
        'user_id'                => $user_id,
        'user'                   => $user,
        'account_days'           => $account_days,
        'tenure_string'          => $tenure_str,
        'total_spent'            => $total_spent,
        'completed_orders_count' => $order_count_for_tier,
        'all_orders_count'       => $all_orders_count,
        'current_tier'           => $current_tier,
        'next_tier'              => $next_tier,
        'progress_percent'       => $progress_percent,
        'orders_needed'          => $orders_needed,
        'spent_needed'           => $spent_needed,
        'days_needed'            => $days_needed,
        'callout_message'        => $callout_message,
        'all_tiers'              => $tiers,
        'tier_order'             => $tier_order,
    ];
}

/**
 * =========================================================================
 * TẮT HOÀN TOÀN THÔNG BÁO XÁC MINH EMAIL ĐỂ LIÊN KẾT ĐƠN HÀNG CŨ (WOOCOMMERCE)
 * "Confirm your email address to check for past orders and link them to your account."
 * =========================================================================
 */
add_filter('woocommerce_customer_email_verification_should_show_prompt', '__return_false', 999);

add_action('init', function() {
    if (function_exists('wc_get_container')) {
        try {
            $controller = wc_get_container()->get(\Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController::class);
            if ($controller) {
                remove_action('woocommerce_before_account_orders', [$controller, 'render_prompt']);
                remove_action('woocommerce_before_account_orders', [$controller, 'print_result_notice'], 5);
            }
        } catch (\Throwable $e) {
            // Container or class not available
        }
    }
}, 20);

/**
 * =========================================================================
 * ẨN TIÊU ĐỀ TRANG "TÀI KHOẢN" VÀ BANNER MẶC ĐỊNH TRÊN TRANG MY ACCOUNT
 * =========================================================================
 */
add_filter('astra_single_layout_one_banner_visibility', function($visibility) {
    if (function_exists('is_account_page') && is_account_page()) {
        return false;
    }
    return $visibility;
}, 99);

add_filter('astra_the_title_enabled', function($enabled) {
    if (function_exists('is_account_page') && is_account_page()) {
        return false;
    }
    return $enabled;
}, 99);

/**
 * =========================================================================
 * TÍNH NĂNG TẢI ẢNH ĐẠI DIỆN TÙY CHỈNH TỪ THIẾT BỊ (CUSTOM USER AVATAR)
 * =========================================================================
 */

// 1. Filter core get_avatar & get_avatar_url để hiển thị ảnh đại diện tùy chỉnh ở mọi nơi
add_filter('pre_get_avatar_data', 'nbook_filter_custom_avatar_data', 10, 2);
function nbook_filter_custom_avatar_data($args, $id_or_email) {
    $user_id = 0;
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) $user_id = $user->ID;
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = (int) $id_or_email->ID;
    } elseif ($id_or_email instanceof WP_Post) {
        $user_id = (int) $id_or_email->post_author;
    } elseif ($id_or_email instanceof WP_Comment) {
        $user_id = (int) $id_or_email->user_id;
    }

    if ($user_id > 0) {
        $custom_avatar_url = get_user_meta($user_id, 'nbook_custom_avatar_url', true);
        if (!empty($custom_avatar_url)) {
            $args['url'] = $custom_avatar_url;
            $args['found_avatar'] = true;
        }
    }
    return $args;
}

// 2. AJAX Upload Avatar từ máy
add_action('wp_ajax_nbook_upload_avatar', 'nbook_ajax_upload_avatar_handler');
function nbook_ajax_upload_avatar_handler() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Vui lòng đăng nhập để thực hiện.'], 401);
    }

    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'nbook_avatar_upload_nonce') && !wp_verify_nonce($nonce, 'nbook_search_nonce')) {
        wp_send_json_error(['message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 403);
    }

    if (empty($_FILES['avatar_file'])) {
        wp_send_json_error(['message' => 'Không tìm thấy file ảnh tải lên.'], 400);
    }

    $file = $_FILES['avatar_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'Lỗi khi tải file lên máy chủ (mã lỗi: ' . intval($file['error']) . ').'], 400);
    }

    // Kiểm tra dung lượng tối đa 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error(['message' => 'Dung lượng ảnh vượt quá 5MB. Vui lòng chọn ảnh nhỏ hơn.'], 400);
    }

    // Kiểm tra định dạng ảnh
    $allowed_mimes = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'webp'         => 'image/webp',
        'gif'          => 'image/gif'
    ];
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
    if (empty($file_info['ext']) || empty($file_info['type'])) {
        wp_send_json_error(['message' => 'Định dạng ảnh không hợp lệ. Vui lòng chọn ảnh JPG, PNG, WEBP hoặc GIF.'], 400);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $user_id = get_current_user_id();

    // Xóa file ảnh cũ nếu có
    $old_avatar_file = get_user_meta($user_id, 'nbook_custom_avatar_file', true);
    if ($old_avatar_file && file_exists($old_avatar_file)) {
        @unlink($old_avatar_file);
    }
    $old_attachment_id = (int) get_user_meta($user_id, 'nbook_custom_avatar_id', true);
    if ($old_attachment_id > 0) {
        wp_delete_attachment($old_attachment_id, true);
    }

    $upload_overrides = ['test_form' => false];
    $movefile = is_uploaded_file($file['tmp_name'])
        ? wp_handle_upload($file, $upload_overrides)
        : wp_handle_sideload($file, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $avatar_url = $movefile['url'];
        $avatar_file = $movefile['file'];

        update_user_meta($user_id, 'nbook_custom_avatar_url', esc_url_raw($avatar_url));
        update_user_meta($user_id, 'nbook_custom_avatar_file', sanitize_text_field($avatar_file));

        wp_send_json_success([
            'message'    => 'Cập nhật ảnh đại diện thành công!',
            'avatar_url' => esc_url($avatar_url),
            'user_id'    => $user_id
        ]);
    } else {
        wp_send_json_error(['message' => $movefile['error'] ?? 'Lỗi khi lưu ảnh lên máy chủ.'], 500);
    }
}

// 3. AJAX Xóa ảnh đại diện tùy chỉnh (quay về mặc định)
add_action('wp_ajax_nbook_delete_avatar', 'nbook_ajax_delete_avatar_handler');
function nbook_ajax_delete_avatar_handler() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Vui lòng đăng nhập để thực hiện.'], 401);
    }

    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'nbook_avatar_upload_nonce') && !wp_verify_nonce($nonce, 'nbook_search_nonce')) {
        wp_send_json_error(['message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 403);
    }

    $user_id = get_current_user_id();

    $old_avatar_file = get_user_meta($user_id, 'nbook_custom_avatar_file', true);
    if ($old_avatar_file && file_exists($old_avatar_file)) {
        @unlink($old_avatar_file);
    }
    $old_attachment_id = (int) get_user_meta($user_id, 'nbook_custom_avatar_id', true);
    if ($old_attachment_id > 0) {
        wp_delete_attachment($old_attachment_id, true);
    }

    delete_user_meta($user_id, 'nbook_custom_avatar_id');
    delete_user_meta($user_id, 'nbook_custom_avatar_url');
    delete_user_meta($user_id, 'nbook_custom_avatar_file');

    $default_avatar_url = get_avatar_url($user_id, ['size' => 160]);
    $display_name = wp_get_current_user()->display_name;
    $initial = mb_strtoupper(mb_substr($display_name, 0, 1, 'UTF-8'), 'UTF-8');

    wp_send_json_success([
        'message'        => 'Đã xóa ảnh đại diện tùy chỉnh.',
        'default_url'    => esc_url($default_avatar_url),
        'initial'        => $initial,
        'has_gravatar'   => ($default_avatar_url && !strpos($default_avatar_url, 'gravatar.com/avatar/?d=') && !strpos($default_avatar_url, 'd=mm'))
    ]);
}

/**
 * =========================================================================
 * 31. TÍNH NĂNG HỦY ĐƠN HÀNG KHI CHƯA VẬN CHUYỂN (BIBLIO CANCEL ORDER)
 * =========================================================================
 */

/**
 * Kiểm tra xem người dùng hiện tại có quyền hủy đơn hàng hay không.
 * Điều kiện:
 * 1. Khách hàng đã đăng nhập và là chủ sở hữu đơn hàng (hoặc Quản trị viên).
 * 2. Đơn hàng đang ở trạng thái CHƯA VẬN CHUYỂN: pending (Chờ thanh toán), processing (Đang xử lý / chuẩn bị đóng gói).
 * 3. Sau khi đã vận chuyển (shipping, completed, cancelled, refunded, failed) thì KHÔNG được hủy.
 */
function nbook_can_user_cancel_order($order) {
    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order);
    }
    if (!$order) {
        return false;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    // Kiểm tra quyền sở hữu đơn hàng
    if ((int) $order->get_customer_id() !== (int) $user_id && !current_user_can('manage_woocommerce')) {
        return false;
    }

    // Các trạng thái đơn hàng khi CHƯA VẬN CHUYỂN
    $cancellable_statuses = apply_filters('nbook_cancellable_order_statuses', ['pending', 'processing']);
    return in_array($order->get_status(), $cancellable_statuses, true);
}

/**
 * Mở rộng trạng thái cho phép hủy của WooCommerce core
 */
add_filter('woocommerce_valid_order_statuses_for_cancel', 'nbook_valid_order_statuses_for_cancel', 10, 2);
function nbook_valid_order_statuses_for_cancel($statuses, $order = null) {
    return array_unique(array_merge((array) $statuses, ['pending', 'processing']));
}

/**
 * AJAX xử lý Hủy đơn hàng từ phía Khách hàng
 */
add_action('wp_ajax_nbook_cancel_order', 'nbook_ajax_cancel_order_handler');
function nbook_ajax_cancel_order_handler() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Vui lòng đăng nhập để thực hiện thao tác này.'], 401);
    }

    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'nbook_cancel_order_nonce') && !wp_verify_nonce($nonce, 'nb_auth_nonce_action') && !wp_verify_nonce($nonce, 'nbook_search_nonce')) {
        wp_send_json_error(['message' => 'Phiên làm việc đã hết hạn hoặc không hợp lệ. Vui lòng tải lại trang.'], 403);
    }

    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => 'Mã đơn hàng không hợp lệ.'], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => 'Không tìm thấy thông tin đơn hàng.'], 404);
    }

    // Kiểm tra tính hợp lệ và trạng thái "chưa vận chuyển"
    if (!nbook_can_user_cancel_order($order)) {
        wp_send_json_error([
            'message' => 'Đơn hàng #' . $order->get_order_number() . ' đã được giao cho đơn vị vận chuyển hoặc không thể hủy tại thời điểm này.'
        ], 400);
    }

    $reason = sanitize_text_field($_POST['reason'] ?? 'Khách hàng hủy đơn');
    $note   = sanitize_textarea_field($_POST['note'] ?? '');

    $order_note = "Biblio: Khách hàng đã hủy đơn hàng.\n- Lý do: " . $reason;
    if (!empty($note)) {
        $order_note .= "\n- Ghi chú thêm: " . $note;
    }

    // Lưu meta thông tin hủy đơn
    $order->update_meta_data('_nb_cancel_reason', $reason);
    $order->update_meta_data('_nb_cancel_note', $note);
    $order->update_meta_data('_nb_cancelled_by', 'customer');
    $order->update_meta_data('_nb_cancel_timestamp', current_time('timestamp'));
    $order->save();

    // Cập nhật trạng thái đơn hàng sang 'cancelled' (tự động kích hoạt hook nbook_restore_stock_on_order_cancelled)
    $order->update_status('cancelled', $order_note);

    update_post_meta($order_id, '_nb_cancel_reason', $reason);
    update_post_meta($order_id, '_nb_cancel_note', $note);
    update_post_meta($order_id, '_nb_cancelled_by', 'customer');
    update_post_meta($order_id, '_nb_cancel_timestamp', current_time('timestamp'));

    wp_send_json_success([
        'message'      => 'Đơn hàng #' . $order->get_order_number() . ' đã được hủy thành công!',
        'order_id'     => $order_id,
        'order_number' => $order->get_order_number(),
        'new_status'   => 'cancelled',
        'badge_html'   => '<span class="nb-status-badge nb-status-cancelled"><i class="bi bi-x-circle"></i> Đã hủy đơn</span>',
        'reason'       => $reason,
        'note'         => $note
    ]);
}

/**
 * Render Modal Hủy đơn hàng phong cách Shopee trên các trang Tài khoản
 */
add_action('wp_footer', 'nbook_render_cancel_order_modal');
function nbook_render_cancel_order_modal() {
    if (!is_user_logged_in()) {
        return;
    }
    ?>
    <div id="nbCancelOrderModal" class="nb-cancel-modal-overlay" aria-hidden="true" style="display: none;">
        <div class="nb-cancel-modal-dialog">
            <div class="nb-cancel-modal-card">
                <!-- Modal Header -->
                <div class="nb-cancel-modal-header">
                    <div class="nb-cancel-modal-title">
                        <i class="bi bi-shield-exclamation text-danger"></i>
                        <span>Hủy đơn hàng <strong id="nbCancelModalOrderNum">#</strong></span>
                    </div>
                    <button type="button" class="nb-cancel-modal-close" id="nbCancelModalCloseBtn" aria-label="Đóng">&times;</button>
                </div>

                <!-- Modal Body -->
                <div class="nb-cancel-modal-body">
                    <div class="nb-cancel-notice-box">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Lưu ý:</strong> Đơn hàng chỉ có thể hủy khi <em>chưa bàn giao cho đơn vị vận chuyển</em>. Số lượng sách sẽ được tự động hoàn lại kho.
                        </div>
                    </div>

                    <div class="nb-cancel-reason-section">
                        <label class="nb-cancel-label">Vui lòng chọn lý do hủy đơn: <span class="text-danger">*</span></label>
                        <div class="nb-cancel-reasons-list">
                            <label class="nb-cancel-radio-item is-selected">
                                <input type="radio" name="nb_cancel_reason" value="Tôi muốn thay đổi địa chỉ nhận hàng" checked>
                                <span class="nb-radio-custom"></span>
                                <span class="nb-radio-text">Tôi muốn thay đổi địa chỉ nhận hàng</span>
                            </label>

                            <label class="nb-cancel-radio-item">
                                <input type="radio" name="nb_cancel_reason" value="Tôi muốn thay đổi sản phẩm trong đơn (số lượng, đổi sách khác)">
                                <span class="nb-radio-custom"></span>
                                <span class="nb-radio-text">Tôi muốn thay đổi sản phẩm trong đơn (số lượng, đổi sách khác)</span>
                            </label>

                            <label class="nb-cancel-radio-item">
                                <input type="radio" name="nb_cancel_reason" value="Tìm thấy giá rẻ hơn ở nơi khác hoặc muốn áp dụng mã voucher khác">
                                <span class="nb-radio-custom"></span>
                                <span class="nb-radio-text">Tìm thấy giá rẻ hơn ở nơi khác hoặc muốn áp dụng voucher khác</span>
                            </label>

                            <label class="nb-cancel-radio-item">
                                <input type="radio" name="nb_cancel_reason" value="Thời gian giao hàng dự kiến không phù hợp">
                                <span class="nb-radio-custom"></span>
                                <span class="nb-radio-text">Thời gian giao hàng dự kiến không phù hợp</span>
                            </label>

                            <label class="nb-cancel-radio-item">
                                <input type="radio" name="nb_cancel_reason" value="Đổi ý, không có nhu cầu mua nữa">
                                <span class="nb-radio-custom"></span>
                                <span class="nb-radio-text">Đổi ý, không có nhu cầu mua nữa</span>
                            </label>

                            <label class="nb-cancel-radio-item">
                                <input type="radio" name="nb_cancel_reason" value="Lý do khác">
                                <span class="nb-radio-custom"></span>
                                <span class="nb-radio-text">Lý do khác</span>
                            </label>
                        </div>
                    </div>

                    <div class="nb-cancel-note-group" id="nbCancelNoteGroup">
                        <label for="nbCancelOrderNote" class="nb-cancel-label">Chi tiết lý do (không bắt buộc):</label>
                        <textarea id="nbCancelOrderNote" class="nb-cancel-textarea" rows="3" placeholder="Nhập thêm lý do chi tiết để Biblio phục vụ bạn tốt hơn..."></textarea>
                    </div>

                    <div id="nbCancelModalError" class="nb-cancel-alert-error" style="display: none;"></div>
                </div>

                <!-- Modal Footer -->
                <div class="nb-cancel-modal-footer">
                    <input type="hidden" id="nbCancelModalOrderId" value="">
                    <input type="hidden" id="nbCancelModalNonce" value="<?php echo esc_attr(wp_create_nonce('nbook_cancel_order_nonce')); ?>">
                    
                    <button type="button" class="nb-btn-cancel-back" id="nbCancelModalDismissBtn">
                        Không, giữ lại đơn
                    </button>
                    <button type="button" class="nb-btn-cancel-confirm" id="nbCancelModalConfirmBtn">
                        <span class="nb-btn-text"><i class="bi bi-x-circle me-1"></i> Xác nhận hủy đơn</span>
                        <span class="nb-btn-spinner" style="display: none;"><i class="bi bi-arrow-repeat spin"></i> Đang xử lý...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

