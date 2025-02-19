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
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',          'p:7y<4Jj1(HyjKTOliOjS,?d.YY=$`$^~%w,$9zm#<)-^P_1E5$4by4^C3s(^(;Z' );
define( 'SECURE_AUTH_KEY',   '?sC[qXH5vtN5KB2*k:;}wA>L{<*QKQdr)(GuB!V-_#xHu<F&31D4<+tX9}g]B7#M' );
define( 'LOGGED_IN_KEY',     't%G`24M/3#lw%fb(^Z}wBy-l2`z#z+X3u:(ds+Dd$akZGMzshm{G|Z8V^D)B>^#g' );
define( 'NONCE_KEY',         '=(DpieSdgCD:wj,kZk(2W+h@6mEmte`N}P0H#]ELnQ_(]N4*ItHz&&PM|ZfffJ5i' );
define( 'AUTH_SALT',         'U`|cI3:bp;h]~m0ynCI62Ou2JPu3ecGfb]KgHmMrJHPzr[VE(DESpi{G= Vph`~M' );
define( 'SECURE_AUTH_SALT',  '#q!yJsUYS$G~2L!aO_mW]o=$L2MJ*_`C:PE>jJ2D5z^|pO6A*aQQHyD3&U5[H0e6' );
define( 'LOGGED_IN_SALT',    'z$T[,]`GMmdoIw?!B|>!MrV.QJt5jpw?$9>AWN{]YF^N%/&!M%Mnhrj&qTy~D-dz' );
define( 'NONCE_SALT',        'JD*Fk 5t[,%k).Jc3>+YaTLoJ0<`j:%mr63rhdjWqREL<R#-;1lqQ%s0qhd(5Yo,' );
define( 'WP_CACHE_KEY_SALT', '%?o ~p`5s):@;{d`OHPCL88d6/)S3UfTI0FetnWfabG$sPUf[_;EnTqzG<G!@e{X' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
