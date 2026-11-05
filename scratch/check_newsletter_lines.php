<?php
$files = [
    'wp-content/themes/nbook-child/front-page.php',
    'wp-content/themes/nbook-child/functions.php',
    'wp-content/themes/nbook-child/page-tin-tuc-su-kien.php'
];

foreach ($files as $file) {
    echo "=== $file ===\n";
    $lines = file($file);
    foreach ($lines as $i => $line) {
        if (mb_stripos($line, 'bản tin') !== false || mb_stripos($line, 'newsletter') !== false || mb_stripos($line, 'voucher 50') !== false) {
            echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
