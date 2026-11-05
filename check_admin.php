<?php
$lines = file('wp-content/plugins/nbook-core/includes/class-biblio-admin-hub.php');
echo implode("", array_slice($lines, 1230, 48));
