<?php
require_once 'wp-load.php';

echo "WC query vars:\n";
print_r(WC()->query->get_query_vars());
