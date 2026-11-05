<?php
require_once 'wp-load.php';
global $wpdb;

echo "=== Searching wp_posts ===\n";
$posts = $wpdb->get_results("SELECT ID, post_title, post_type, post_status FROM {$wpdb->prefix}posts WHERE post_content LIKE '%BẢN TIN%' OR post_content LIKE '%bản tin%' OR post_content LIKE '%VOUCHER 50%' OR post_content LIKE '%nhận bản tin%'");
foreach ($posts as $p) {
    echo "- ID: {$p->ID}, title: '{$p->post_title}', type: {$p->post_type}, status: {$p->post_status}\n";
}

echo "\n=== Searching wp_options ===\n";
$opts = $wpdb->get_results("SELECT option_name FROM {$wpdb->prefix}options WHERE option_value LIKE '%BẢN TIN%' OR option_value LIKE '%bản tin%' OR option_value LIKE '%VOUCHER 50%'");
foreach ($opts as $o) {
    echo "- Option: {$o->option_name}\n";
}

echo "\n=== Searching files in nbook-child ===\n";
$dir = get_stylesheet_directory();
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($files as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['php', 'css', 'js'])) {
        $content = file_get_contents($file->getPathname());
        if (mb_stripos($content, 'bản tin') !== false || mb_stripos($content, 'nhận voucher 50') !== false || mb_stripos($content, 'newsletter') !== false) {
            echo "- File: " . $file->getPathname() . "\n";
        }
    }
}
