<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ai-flatsome' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'MYb}e>#l^gd=-_R_L*/3:*pk E7.aCynMJoD7z(0y&^fOS9)!:%J;klY~t8:rM.9' );
define( 'SECURE_AUTH_KEY',  'NLyAZL}8Vv<!ATslWHcX16Z/C#Sn/Ya9}`Oio*Jw,W(]&F{{B!:.9m!Q6.EL{e)j' );
define( 'LOGGED_IN_KEY',    '8ye,:XWL|r6KsJbQQU6f%4coI5<-]z?d>sL*1BJTCp*I=5TP6)Vwxf/QCS?1$Wwo' );
define( 'NONCE_KEY',        '(?;_qT)%0a$hAj#Awa#UqB-(K9iKsQpK :]}2 `8G~(5)W@# 2$<7ZNXFOmX|1kS' );
define( 'AUTH_SALT',        '#O)eC5/)y=E&}d$;i@M:Skan?rnQ_^$-}mxS5U`l#*KpWon@RgSG}{}e.J?u_,hU' );
define( 'SECURE_AUTH_SALT', ':aA,dSA9%}=f3X{hI &m*V1GBE^nRXKr_urvN;+VT-J;B#YV+aRboT/dyT#_dd02' );
define( 'LOGGED_IN_SALT',   'ItHW)_7Qn3#rtqRD tw89rq]`Z)y(JV/|c@eWk2*J!(QLOY/|qZ~VXd V|EEYmu5' );
define( 'NONCE_SALT',       '@Sw:(:rQI&pR-k9}=%(|?x_Dt@A5S<z0p:Uf7k3X5a.`2$><+{6t8#DQ9Sc]Eo$p' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
