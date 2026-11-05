<?php
require_once 'wp-load.php';
global $wpdb;

echo "=== wp_nbook_rewards ===\n";
$rewards = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nbook_rewards");
print_r($rewards);

echo "\n=== wp_nbook_points_ledger count ===\n";
$ledger_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nbook_points_ledger");
echo "Ledger rows: $ledger_count\n";
