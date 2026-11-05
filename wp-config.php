<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('WP_CACHE', false);
//define( 'WPCACHEHOME', 'C:\xampp\htdocs\wordpress\wp-content\plugins\wp-super-cache/' );
define( 'DB_NAME', 'biblio' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '5cCTjQ@@=<_-BDA&6aw$+n4OAED5)~3)XGp5h<o%+5gQ`WZIA_,DY@d-{3m*9@ +' );
define( 'SECURE_AUTH_KEY',   '0zs5KPo@#r4lf%_R&#$di1Yc/1dRD;D7o2O S]}=hsl5Wx EIhTwfl`ss(*3}E|p' );
define( 'LOGGED_IN_KEY',     '-$V^tKkS|hioFrrjV5FOIveNh4 pOdo.H{kfcE}]^lagB]4 1)bn:`oayM4ZIr5H' );
define( 'NONCE_KEY',         'z ?UO;tI8mPosCFXEZ;;*Cc<Q9REr`)nC)ODv2Ad$Qj::j+Pp!b1bKO|!S]*sch;' );
define( 'AUTH_SALT',         'y#0]|v E=l?fhn/]yc<Zvp~.WlL](x^:;G;Q`$P7WZ>(?w}~S9MYC(}q>=p+EtGj' );
define( 'SECURE_AUTH_SALT',  'hUg7p2Y#hOix@6#t[)1yC?/H|:8Ci)`oywFP.9[5RG*1!q(S?W6|}N%+)#0,1_W-' );
define( 'LOGGED_IN_SALT',    '7b%1RND<kDxQa~*A4+?Hb%|wDI4r$U-Av1Rin:P|z4X@k6D?*R|bzwdNJh*8+fF`' );
define( 'NONCE_SALT',        '?#)O(|qtvS~P/!x:{W*(:GddYdwnwnCm?IABJb#XrZkO<%d$db934rG. f:%Q9N&' );
define( 'WP_CACHE_KEY_SALT', 'Ouny9g# <Z>v},}0A3t u{}SHEkY-hu:6!!iObg+ckf2n?UkrEdEfE+lQ-(AVMlX' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */
define('DISABLE_WP_CRON', true);



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );

define( 'WP_MEMORY_LIMIT', '512M' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
