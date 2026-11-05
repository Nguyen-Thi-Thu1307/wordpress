<?php
require_once 'wp-load.php';

echo "=== Page 9 (tai-khoan) ===\n";
$p9 = get_post(9);
echo "Title: " . $p9->post_title . "\n";
echo "Content: " . $p9->post_content . "\n";
echo "Page Template: " . get_post_meta(9, '_wp_page_template', true) . "\n";

echo "\n=== Page 445 (diem-thuong) ===\n";
$p445 = get_post(445);
echo "Title: " . $p445->post_title . "\n";
echo "Content: " . $p445->post_content . "\n";

echo "\n=== WC My Account Endpoints ===\n";
if (function_exists('wc_get_account_menu_items')) {
    print_r(wc_get_account_menu_items());
}

echo "\n=== Users ===\n";
$users = get_users(['number' => 5]);
foreach ($users as $u) {
    echo "- ID: {$u->ID}, login: {$u->user_login}, roles: " . implode(',', $u->roles) . "\n";
}
