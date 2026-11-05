<?php
require_once 'wp-load.php';

global $wp_rewrite;
$rules = $wp_rewrite->wp_rewrite_rules();
echo "Rules matching voucher:\n";
foreach ($rules as $k => $v) {
    if (strpos($k, 'voucher') !== false || strpos($v, 'voucher') !== false) {
        echo "  $k => $v\n";
    }
}
