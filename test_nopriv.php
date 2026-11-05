<?php
require_once 'wp-load.php';

// Check what check_ajax_referer does:
add_action('wp_ajax_nopriv_nbook_notifications', function() {
    echo "NOPRIV CALLED";
    exit;
});
