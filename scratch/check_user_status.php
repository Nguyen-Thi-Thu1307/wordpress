<?php
require_once 'wp-load.php';
global $wpdb;

foreach ([1, 2, 3] as $uid) {
    $u = get_userdata($uid);
    if (!$u) continue;
    $balance = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}nbook_points_ledger WHERE user_id=%d", $uid));
    $saved = get_user_meta($uid, '_nbook_saved_coupons', true);
    echo "User $uid ({$u->user_login}): points = $balance, saved coupons = " . json_encode($saved) . "\n";
}
