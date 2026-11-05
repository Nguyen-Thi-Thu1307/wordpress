<?php
require_once 'wp-load.php';

$url = 'http://localhost/wordpress/diem-thuong/';
$pid = url_to_postid($url);
echo "url_to_postid('$url') = " . var_export($pid, true) . "\n";

$p = get_post(445);
echo "Post 445: title='{$p->post_title}', name='{$p->post_name}', type='{$p->post_type}', status='{$p->post_status}'\n";

global $wp_rewrite;
$rules = $wp_rewrite->wp_rewrite_rules();
echo "Rules matching diem-thuong:\n";
foreach ($rules as $k => $v) {
    if (strpos($k, 'diem-thuong') !== false || strpos($v, 'diem-thuong') !== false) {
        echo "  $k => $v\n";
    }
}
