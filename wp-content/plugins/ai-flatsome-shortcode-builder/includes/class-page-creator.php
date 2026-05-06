<?php
/**
 * Page Creator – creates a WordPress draft page from shortcode + CSS.
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFSB_Page_Creator
 */
class AFSB_Page_Creator {

	/** Post meta key for generated CSS. */
	const META_KEY = '_ai_flatsome_generated_css';

	/**
	 * Create a draft page.
	 *
	 * @param string $title     Page title.
	 * @param string $shortcode Flatsome shortcode content.
	 * @param string $css       Generated CSS.
	 * @return int|WP_Error New post ID or WP_Error.
	 */
	public static function create( $title, $shortcode, $css ) {
		$title     = sanitize_text_field( $title );
		$shortcode = wp_kses_post( $shortcode );

		if ( empty( $title ) ) {
			$title = __( 'AI Generated Page', AFSB_TEXT_DOMAIN );
		}

		if ( empty( $shortcode ) ) {
			return new WP_Error( 'empty_shortcode', __( 'Cannot create page: shortcode is empty.', AFSB_TEXT_DOMAIN ) );
		}

		$post_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_content' => $shortcode,
			'post_status'  => 'draft',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id(),
		), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Save generated CSS as post meta.
		if ( ! empty( $css ) ) {
			update_post_meta( $post_id, self::META_KEY, $css );
		}

		return $post_id;
	}

	/**
	 * Get the edit URL for a page.
	 *
	 * @param int $post_id Page ID.
	 * @return string
	 */
	public static function get_edit_url( $post_id ) {
		return get_edit_post_link( $post_id, 'raw' );
	}

	/**
	 * Enqueue inline CSS for pages that have generated CSS stored in meta.
	 * Called on wp_enqueue_scripts hook.
	 *
	 * @return void
	 */
	public static function enqueue_frontend_css() {
		if ( ! is_singular( 'page' ) ) {
			return;
		}

		$post_id = get_the_ID();
		$css     = get_post_meta( $post_id, self::META_KEY, true );

		if ( empty( $css ) ) {
			return;
		}

		// Enqueue a dummy handle to attach inline style.
		wp_register_style( 'afsb-page-css-' . $post_id, false ); // phpcs:ignore
		wp_enqueue_style( 'afsb-page-css-' . $post_id );
		wp_add_inline_style( 'afsb-page-css-' . $post_id, wp_strip_all_tags( $css ) );
	}
}
