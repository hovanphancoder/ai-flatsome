<?php
/**
 * Plugin bootstrap – registers hooks, admin menu, AJAX handlers.
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFSB_Plugin
 * Singleton bootstrap class.
 */
class AFSB_Plugin {

	/** @var AFSB_Plugin|null Singleton instance. */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AFSB_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor – register all WordPress hooks.
	 */
	private function __construct() {
		// Admin menu.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers (logged-in users only).
		add_action( 'wp_ajax_afsb_generate',    array( $this, 'ajax_generate' ) );
		add_action( 'wp_ajax_afsb_create_page', array( $this, 'ajax_create_page' ) );

		// Frontend: enqueue inline CSS only for pages that have generated CSS.
		add_action( 'wp_enqueue_scripts', array( 'AFSB_Page_Creator', 'enqueue_frontend_css' ) );
	}

	// -------------------------------------------------------------------------
	// Admin Menu
	// -------------------------------------------------------------------------

	/**
	 * Register the admin menu page.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'AI Flatsome', AFSB_TEXT_DOMAIN ),
			__( 'AI Flatsome', AFSB_TEXT_DOMAIN ),
			'manage_options',
			'ai-flatsome',
			array( 'AFSB_Admin_Page', 'render' ),
			'dashicons-layout',
			80
		);
	}

	// -------------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------------

	/**
	 * Enqueue admin CSS and JS only on the plugin's admin page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_ai-flatsome' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'afsb-admin',
			AFSB_PLUGIN_URL . 'assets/admin.css',
			array(),
			AFSB_VERSION
		);

		wp_enqueue_script(
			'afsb-admin',
			AFSB_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			AFSB_VERSION,
			true
		);

		// Localise script with AJAX URL and nonces.
		wp_localize_script( 'afsb-admin', 'afsbData', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => array(
				'copied'       => __( 'Copied!', AFSB_TEXT_DOMAIN ),
				'copy'         => __( 'Copy', AFSB_TEXT_DOMAIN ),
				'generating'   => __( 'Generating…', AFSB_TEXT_DOMAIN ),
				'pageCreated'  => __( 'Draft page created! Edit it here:', AFSB_TEXT_DOMAIN ),
				'error'        => __( 'Error:', AFSB_TEXT_DOMAIN ),
			),
		) );
	}

	// -------------------------------------------------------------------------
	// AJAX: Generate
	// -------------------------------------------------------------------------

	/**
	 * AJAX handler: afsb_generate.
	 * Accepts mode (image|url), image file or URL, returns shortcode + css.
	 *
	 * @return void
	 */
	public function ajax_generate() {
		// Security checks.
		check_ajax_referer( 'afsb_generate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', AFSB_TEXT_DOMAIN ) ), 403 );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'image';

		if ( 'image' === $mode ) {
			$layout = $this->handle_image_mode();
		} else {
			$layout = $this->handle_url_mode();
		}

		if ( is_wp_error( $layout ) ) {
			wp_send_json_error( array( 'message' => $layout->get_error_message() ) );
		}

		// Convert JSON layout → shortcode.
		$shortcode = AFSB_Layout_Converter::convert( $layout );

		// Generate CSS.
		$css_result = AFSB_CSS_Generator::generate( $layout );

		wp_send_json_success( array(
			'json'      => wp_json_encode( $layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ),
			'shortcode' => $shortcode,
			'css'       => $css_result['combined'],
		) );
	}

	/**
	 * Handle image upload mode.
	 *
	 * @return array|WP_Error
	 */
	private function handle_image_mode() {
		if ( empty( $_FILES['image'] ) ) {
			return new WP_Error( 'no_image', __( 'Please upload an image.', AFSB_TEXT_DOMAIN ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$file  = $_FILES['image'];
		$valid = afsb_validate_image( $file );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		return AFSB_AI_Client::analyze_image( $file['tmp_name'] );
	}

	/**
	 * Handle URL mode.
	 *
	 * @return array|WP_Error
	 */
	private function handle_url_mode() {
		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( empty( $url ) ) {
			return new WP_Error( 'no_url', __( 'Please enter a URL.', AFSB_TEXT_DOMAIN ) );
		}

		return AFSB_AI_Client::analyze_url( $url );
	}

	// -------------------------------------------------------------------------
	// AJAX: Create Page
	// -------------------------------------------------------------------------

	/**
	 * AJAX handler: afsb_create_page.
	 *
	 * @return void
	 */
	public function ajax_create_page() {
		check_ajax_referer( 'afsb_create_page_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', AFSB_TEXT_DOMAIN ) ), 403 );
		}

		$title     = isset( $_POST['title'] )     ? sanitize_text_field( wp_unslash( $_POST['title'] ) )     : '';
		$shortcode = isset( $_POST['shortcode'] ) ? wp_kses_post( wp_unslash( $_POST['shortcode'] ) ) : '';
		$css       = isset( $_POST['css'] )       ? wp_strip_all_tags( wp_unslash( $_POST['css'] ) )         : '';

		$post_id = AFSB_Page_Creator::create( $title, $shortcode, $css );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		wp_send_json_success( array(
			'post_id'  => $post_id,
			'edit_url' => AFSB_Page_Creator::get_edit_url( $post_id ),
		) );
	}
}
