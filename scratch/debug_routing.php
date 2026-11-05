<?php
define('WP_USE_THEMES', true);

$_SERVER['REQUEST_URI'] = '/wordpress/tai-khoan/voucher/';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
require_once 'wp-load.php';
global $wp, $wp_query;

echo "=== ROUTING CHECK FOR /tai-khoan/voucher/ ===\n";
echo "Matched rule: " . $wp->matched_rule . "\n";
echo "Matched query: " . $wp->matched_query . "\n";
echo "Query vars:\n";
print_r($wp->query_vars);
echo "Is 404: " . (is_404() ? 'YES' : 'NO') . "\n";
echo "Is account page: " . (function_exists('is_account_page') && is_account_page() ? 'YES' : 'NO') . "\n";

global $wpdb;
wp_set_current_user(1);

// Test what WooCommerce template outputs for my-account
ob_start();
woocommerce_account_content();
$acc_content = ob_get_clean();
echo "woocommerce_account_content() length: " . strlen($acc_content) . " bytes\n";
echo "First 200 chars: " . substr(strip_tags($acc_content), 0, 200) . "\n";
