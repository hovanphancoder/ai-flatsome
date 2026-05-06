<?php
/**
 * CSS Generator – converts JSON layout into scoped CSS.
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFSB_CSS_Generator
 */
class AFSB_CSS_Generator {

	/**
	 * Generate full CSS from the decoded JSON layout.
	 * Returns an array with 'reusable' CSS and 'scoped' CSS.
	 *
	 * @param array $layout Decoded JSON layout array.
	 * @return array { reusable: string, scoped: string, combined: string }
	 */
	public static function generate( $layout ) {
		$reusable_css = self::build_reusable_css( $layout );
		$scoped_css   = self::build_scoped_css( $layout );

		$combined = '';
		if ( ! empty( $reusable_css ) ) {
			$combined .= "/* === Reusable Component CSS === */\n" . $reusable_css . "\n\n";
		}
		if ( ! empty( $scoped_css ) ) {
			$combined .= "/* === Section Scoped CSS === */\n" . $scoped_css;
		}

		return array(
			'reusable' => $reusable_css,
			'scoped'   => $scoped_css,
			'combined' => trim( $combined ),
		);
	}

	/**
	 * Build reusable component CSS from reusable_components key.
	 *
	 * @param array $layout Layout array.
	 * @return string
	 */
	private static function build_reusable_css( $layout ) {
		if ( empty( $layout['reusable_components'] ) || ! is_array( $layout['reusable_components'] ) ) {
			return '';
		}

		$css = '';
		foreach ( $layout['reusable_components'] as $component_name => $component ) {
			if ( ! empty( $component['css'] ) ) {
				$css .= '/* ' . esc_attr( $component_name ) . " */\n";
				$css .= trim( $component['css'] ) . "\n";
			}
		}

		return $css;
	}

	/**
	 * Build scoped CSS for each section from their custom_css field.
	 *
	 * @param array $layout Layout array.
	 * @return string
	 */
	private static function build_scoped_css( $layout ) {
		if ( empty( $layout['sections'] ) || ! is_array( $layout['sections'] ) ) {
			return '';
		}

		$css = '';
		foreach ( $layout['sections'] as $section ) {
			$section_class = isset( $section['section_class'] ) ? sanitize_html_class( $section['section_class'] ) : '';
			$custom_css    = isset( $section['custom_css'] ) ? $section['custom_css'] : '';

			if ( empty( $section_class ) ) {
				continue;
			}

			// Always generate the section wrapper CSS (background, padding) if provided.
			$bg_color = isset( $section['background_color'] ) ? sanitize_hex_color( $section['background_color'] ) : '';
			$padding  = isset( $section['padding'] ) ? sanitize_text_field( $section['padding'] ) : '';

			$wrapper_rules = array();
			if ( $bg_color ) {
				$wrapper_rules[] = 'background-color:' . $bg_color . ';';
			}
			if ( $padding ) {
				$wrapper_rules[] = 'padding:' . $padding . ';';
			}

			if ( ! empty( $wrapper_rules ) ) {
				$css .= '/* Section: ' . esc_attr( $section_class ) . " */\n";
				$css .= '.' . $section_class . '{' . implode( '', $wrapper_rules ) . "}\n";
			}

			// Append the AI-generated scoped CSS.
			if ( ! empty( $custom_css ) ) {
				// Safety: ensure CSS is scoped (contains section class).
				// We do not blindly include CSS that doesn't reference the section class.
				$css .= trim( $custom_css ) . "\n";
			}

			$css .= "\n";
		}

		return trim( $css );
	}
}
