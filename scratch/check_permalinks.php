<?php
require_once 'wp-load.php';

echo "Permalink structure: '" . get_option('permalink_structure') . "'\n";
echo "Site URL: " . site_url() . "\n";
echo "Home URL: " . home_url() . "\n";

$p_diem = get_page_by_path('diem-thuong');
if ($p_diem) {
    echo "Page diem-thuong: ID={$p_diem->ID}, status={$p_diem->post_status}, permalink=" . get_permalink($p_diem->ID) . "\n";
} else {
    echo "Page diem-thuong not found by path!\n";
}

$p_vouc = get_page_by_path('voucher-cua-toi');
if ($p_vouc) {
    echo "Page voucher-cua-toi: ID={$p_vouc->ID}, status={$p_vouc->post_status}, permalink=" . get_permalink($p_vouc->ID) . "\n";
} else {
    echo "Page voucher-cua-toi not found by path!\n";
}

$acc_id = get_option('woocommerce_myaccount_page_id');
echo "WC MyAccount page ID: $acc_id, permalink: " . get_permalink($acc_id) . "\n";
