<?php
require_once 'wp-load.php';

$needed_coupons = [
    'CHAOBIBLIO' => ['type' => 'fixed_cart', 'amount' => 20000, 'min' => 120000, 'desc' => 'Quà tặng bạn đọc mới đăng ký tài khoản tại Biblio', 'custom_type' => 'new_user'],
    'BIBLIONEU' => ['type' => 'percent', 'amount' => 10, 'min' => 100000, 'desc' => 'Dành riêng cho sinh viên NEU & Giáo trình đại học', 'custom_type' => 'student'],
    'FREESHIP150' => ['type' => 'fixed_cart', 'amount' => 25000, 'min' => 150000, 'desc' => 'Miễn phí giao hàng nội thành từ 150.000đ', 'custom_type' => 'shipping'],
    'MEGA50K' => ['type' => 'fixed_cart', 'amount' => 50000, 'min' => 350000, 'desc' => 'Giảm 50K cho combo sách ngoại ngữ & kinh tế', 'custom_type' => 'category'],
    'BOOKWORM20K' => ['type' => 'fixed_cart', 'amount' => 20000, 'min' => 150000, 'desc' => 'Voucher đổi từ 200 Biblio Xu', 'custom_type' => 'fixed'],
    'FREESHIP' => ['type' => 'fixed_cart', 'amount' => 25000, 'min' => 150000, 'desc' => 'Voucher Freeship đổi từ 250 Biblio Xu', 'custom_type' => 'shipping'],
    'BIBLIO50K' => ['type' => 'fixed_cart', 'amount' => 50000, 'min' => 400000, 'desc' => 'Voucher giảm 50K đổi từ 500 Biblio Xu', 'custom_type' => 'fixed'],
];

foreach ($needed_coupons as $code => $data) {
    $cid = wc_get_coupon_id_by_code($code);
    if (!$cid) {
        $cid = wp_insert_post([
            'post_title' => $code,
            'post_status' => 'publish',
            'post_type' => 'shop_coupon',
            'post_excerpt' => $data['desc']
        ]);
        update_post_meta($cid, 'discount_type', $data['type']);
        update_post_meta($cid, 'coupon_amount', $data['amount']);
        update_post_meta($cid, 'minimum_amount', $data['min']);
        update_post_meta($cid, 'date_expires', strtotime('+365 days'));
        update_post_meta($cid, '_nbook_coupon_type', $data['custom_type']);
        if ($data['custom_type'] === 'shipping') {
            update_post_meta($cid, 'free_shipping', 'yes');
        }
        echo "Created coupon: $code (ID: $cid)\n";
    } else {
        echo "Coupon exists: $code (ID: $cid)\n";
    }
}
