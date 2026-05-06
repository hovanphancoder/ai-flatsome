<?php
/**
 * Helper / utility functions for AI Flatsome Shortcode Builder.
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate that a string is a proper HTTP/HTTPS URL.
 *
 * @param string $url URL to validate.
 * @return bool
 */
function afsb_validate_url( $url ) {
	$url = esc_url_raw( $url );
	return ( filter_var( $url, FILTER_VALIDATE_URL ) && preg_match( '#^https?://#i', $url ) );
}

/**
 * Validate uploaded image ($_FILES entry).
 *
 * @param array $file $_FILES array element.
 * @return true|WP_Error
 */
function afsb_validate_image( $file ) {
	if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'invalid_upload', __( 'No valid file uploaded.', AFSB_TEXT_DOMAIN ) );
	}

	// Check MIME via finfo.
	$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
	$finfo         = finfo_open( FILEINFO_MIME_TYPE );
	$mime          = finfo_file( $finfo, $file['tmp_name'] );
	finfo_close( $finfo );

	if ( ! in_array( $mime, $allowed_mimes, true ) ) {
		return new WP_Error( 'invalid_mime', __( 'Only JPEG, PNG, GIF, and WebP images are allowed.', AFSB_TEXT_DOMAIN ) );
	}

	// Max 8 MB.
	if ( $file['size'] > 8 * 1024 * 1024 ) {
		return new WP_Error( 'file_too_large', __( 'Image must be smaller than 8 MB.', AFSB_TEXT_DOMAIN ) );
	}

	return true;
}

/**
 * Validate the top-level JSON schema returned by AI.
 *
 * @param mixed $data Decoded JSON (should be array/object).
 * @return true|WP_Error
 */
function afsb_validate_json_schema( $data ) {
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'invalid_schema', __( 'AI response is not a valid JSON object.', AFSB_TEXT_DOMAIN ) );
	}

	if ( empty( $data['type'] ) || 'page' !== $data['type'] ) {
		return new WP_Error( 'invalid_schema', __( 'JSON schema missing "type: page".', AFSB_TEXT_DOMAIN ) );
	}

	if ( ! isset( $data['sections'] ) || ! is_array( $data['sections'] ) ) {
		return new WP_Error( 'invalid_schema', __( 'JSON schema missing "sections" array.', AFSB_TEXT_DOMAIN ) );
	}

	return true;
}

/**
 * Basic CSS minification: remove redundant whitespace/newlines.
 *
 * @param string $css Raw CSS.
 * @return string
 */
function afsb_minify_css( $css ) {
	// Remove comments.
	$css = preg_replace( '!/\*.*?\*/!s', '', $css );
	// Collapse whitespace.
	$css = preg_replace( '/\s+/', ' ', $css );
	// Remove spaces around specific characters.
	$css = preg_replace( '/\s*([:;{},])\s*/', '$1', $css );
	// Remove trailing semicolons before }.
	$css = str_replace( ';}', '}', $css );
	return trim( $css );
}

/**
 * Truncate HTML content to ~8000 characters to stay within token limits.
 *
 * @param string $html Full HTML string.
 * @return string Truncated, whitespace-collapsed HTML.
 */
function afsb_truncate_html( $html ) {
	// Remove scripts and styles to reduce noise.
	$html = preg_replace( '#<script[^>]*>.*?</script>#si', '', $html );
	$html = preg_replace( '#<style[^>]*>.*?</style>#si', '', $html );
	// Strip tags.
	$html = wp_strip_all_tags( $html );
	// Collapse whitespace.
	$html = preg_replace( '/\s+/', ' ', $html );
	// Limit length.
	return mb_substr( trim( $html ), 0, 8000 );
}
