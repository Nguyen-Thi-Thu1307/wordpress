<?php
define('WP_USE_THEMES', true);
require_once 'wp-load.php';

$_SERVER['REQUEST_URI'] = '/wordpress/tai-khoan/voucher/';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
wp_set_current_user(1);

global $wp, $wp_query;
$wp->parse_request();
$wp->query_posts();
$wp_query->the_post();

ob_start();
the_content();
$content = ob_get_clean();

echo "Content of /tai-khoan/voucher/:\n";
echo $content;
