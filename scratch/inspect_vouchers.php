<?php
require_once 'wp-load.php';

echo "=== Page 388 (khuyen-mai) ===\n";
$p388 = get_post(388);
if ($p388) {
    echo "Title: " . $p388->post_title . "\n";
    echo "Content: " . $p388->post_content . "\n";
}

echo "\n=== Saved coupons of User 1, 2, 3 ===\n";
foreach ([1, 2, 3] as $uid) {
    $saved = get_user_meta($uid, '_nbook_saved_coupons', true);
    $redeemed = get_user_meta($uid, '_nbook_vouchers', true);
    echo "User $uid: saved = " . json_encode($saved) . ", redeemed = " . json_encode($redeemed) . "\n";
}

echo "\n=== All Coupons in DB ===\n";
$coupons = get_posts(['post_type' => 'shop_coupon', 'posts_per_page' => 20]);
foreach ($coupons as $c) {
    $wc_c = new WC_Coupon($c->ID);
    $type = get_post_meta($c->ID, '_nbook_coupon_type', true);
    echo "- Code: {$c->post_title}, Type: {$wc_c->get_discount_type()} (custom: $type), Amount: {$wc_c->get_amount()}, Min: {$wc_c->get_minimum_amount()}\n";
}
