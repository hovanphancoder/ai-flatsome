<?php
/**
 * Plugin Name: AI Flatsome Shortcode Builder
 * Plugin URI:  https://github.com/your-repo/ai-flatsome-shortcode-builder
 * Description: Upload an image or enter a URL, let AI analyze the layout, and automatically generate Flatsome shortcode + scoped CSS.
 * Version:     1.0.0
 * Author:      Your Name
 * Text Domain: ai-flatsome-shortcode-builder
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

// Plugin constants.
define( 'AFSB_VERSION',     '1.0.0' );
define( 'AFSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFSB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFSB_TEXT_DOMAIN', 'ai-flatsome-shortcode-builder' );

// Load helpers first (utility functions).
require_once AFSB_PLUGIN_DIR . 'includes/helpers.php';

// Load all classes.
require_once AFSB_PLUGIN_DIR . 'includes/class-settings.php';
require_once AFSB_PLUGIN_DIR . 'includes/class-ai-client.php';
require_once AFSB_PLUGIN_DIR . 'includes/class-layout-converter.php';
require_once AFSB_PLUGIN_DIR . 'includes/class-css-generator.php';
require_once AFSB_PLUGIN_DIR . 'includes/class-page-creator.php';
require_once AFSB_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once AFSB_PLUGIN_DIR . 'includes/class-plugin.php';

// Kick off.
AFSB_Plugin::get_instance();
