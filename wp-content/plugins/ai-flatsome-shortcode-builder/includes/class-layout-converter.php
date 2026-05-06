<?php
/**
 * Layout Converter – converts decoded JSON layout into Flatsome shortcode.
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFSB_Layout_Converter
 */
class AFSB_Layout_Converter {

	/**
	 * Convert a full JSON layout array to Flatsome shortcode string.
	 *
	 * @param array $layout Decoded JSON layout.
	 * @return string Flatsome shortcode.
	 */
	public static function convert( $layout ) {
		if ( empty( $layout['sections'] ) || ! is_array( $layout['sections'] ) ) {
			return '';
		}

		$output = '';
		foreach ( $layout['sections'] as $section ) {
			$output .= self::convert_section( $section );
		}

		return trim( $output );
	}

	// -------------------------------------------------------------------------
	// Section
	// -------------------------------------------------------------------------

	/**
	 * Convert a section block.
	 *
	 * @param array $section Section data.
	 * @return string
	 */
	private static function convert_section( $section ) {
		$atts = array();

		// Section class.
		if ( ! empty( $section['section_class'] ) ) {
			$atts['class'] = sanitize_html_class( $section['section_class'] );
		}

		// Background color.
		if ( ! empty( $section['background_color'] ) ) {
			$atts['bg_color'] = sanitize_hex_color( $section['background_color'] );
		}

		// Background image.
		if ( ! empty( $section['background_image'] ) ) {
			$atts['bg'] = esc_url( $section['background_image'] );
		}

		// Padding.
		if ( ! empty( $section['padding'] ) ) {
			$atts['padding'] = sanitize_text_field( $section['padding'] );
		}

		$inner = '';
		if ( ! empty( $section['rows'] ) && is_array( $section['rows'] ) ) {
			foreach ( $section['rows'] as $row ) {
				$inner .= self::convert_row( $row );
			}
		}

		return self::wrap_shortcode( 'section', $atts, $inner ) . "\n";
	}

	// -------------------------------------------------------------------------
	// Row
	// -------------------------------------------------------------------------

	/**
	 * Convert a row block.
	 *
	 * @param array $row Row data.
	 * @return string
	 */
	private static function convert_row( $row ) {
		$atts  = array();
		$inner = '';

		if ( ! empty( $row['columns'] ) && is_array( $row['columns'] ) ) {
			foreach ( $row['columns'] as $column ) {
				$inner .= self::convert_column( $column );
			}
		}

		return self::wrap_shortcode( 'row', $atts, $inner ) . "\n";
	}

	// -------------------------------------------------------------------------
	// Column
	// -------------------------------------------------------------------------

	/**
	 * Convert a column block.
	 *
	 * @param array $column Column data.
	 * @return string
	 */
	private static function convert_column( $column ) {
		$atts = array();

		if ( isset( $column['span'] ) ) {
			$atts['span'] = (int) $column['span'];
		}
		if ( isset( $column['span_sm'] ) ) {
			$atts['span__sm'] = (int) $column['span_sm'];
		}

		$inner = '';
		if ( ! empty( $column['elements'] ) && is_array( $column['elements'] ) ) {
			foreach ( $column['elements'] as $element ) {
				$inner .= self::convert_element( $element );
			}
		}

		return self::wrap_shortcode( 'col', $atts, $inner ) . "\n";
	}

	// -------------------------------------------------------------------------
	// Elements dispatcher
	// -------------------------------------------------------------------------

	/**
	 * Dispatch element conversion by type.
	 *
	 * @param array $el Element data.
	 * @return string
	 */
	private static function convert_element( $el ) {
		$type = isset( $el['type'] ) ? $el['type'] : '';

		switch ( $type ) {
			case 'heading':
				return self::convert_heading( $el );
			case 'paragraph':
				return self::convert_paragraph( $el );
			case 'button':
				return self::convert_button( $el );
			case 'image':
				return self::convert_image( $el );
			case 'banner':
				return self::convert_banner( $el );
			case 'gap':
				return self::convert_gap( $el );
			case 'divider':
				return self::convert_divider( $el );
			case 'text_box':
				return self::convert_text_box( $el );
			case 'products':
				return self::convert_products( $el );
			case 'posts':
				return self::convert_posts( $el );
			default:
				// Fallback: wrap as paragraph.
				return self::convert_paragraph( $el );
		}
	}

	// -------------------------------------------------------------------------
	// Individual element converters
	// -------------------------------------------------------------------------

	/**
	 * Heading element → raw HTML tag.
	 */
	private static function convert_heading( $el ) {
		$tag   = ! empty( $el['tag'] ) ? sanitize_text_field( $el['tag'] ) : 'h2';
		// Allow only h1–h6.
		if ( ! in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
			$tag = 'h2';
		}
		$text  = ! empty( $el['text'] ) ? esc_html( $el['text'] ) : '';
		$class = ! empty( $el['class'] ) ? ' class="' . esc_attr( $el['class'] ) . '"' : '';
		return "<{$tag}{$class}>{$text}</{$tag}>\n";
	}

	/**
	 * Paragraph element → raw HTML.
	 */
	private static function convert_paragraph( $el ) {
		$text  = ! empty( $el['text'] ) ? esc_html( $el['text'] ) : '';
		$class = ! empty( $el['class'] ) ? ' class="' . esc_attr( $el['class'] ) . '"' : '';
		return "<p{$class}>{$text}</p>\n";
	}

	/**
	 * Button element → [button] shortcode.
	 */
	private static function convert_button( $el ) {
		$atts = array();
		if ( ! empty( $el['text'] ) ) {
			$atts['text'] = sanitize_text_field( $el['text'] );
		}
		if ( ! empty( $el['link'] ) ) {
			$atts['link'] = esc_url( $el['link'] );
		}
		if ( ! empty( $el['class'] ) ) {
			$atts['class'] = sanitize_html_class( $el['class'] );
		}
		if ( ! empty( $el['color'] ) ) {
			$atts['color'] = sanitize_hex_color( $el['color'] );
		}
		if ( ! empty( $el['size'] ) ) {
			$atts['size'] = sanitize_text_field( $el['size'] );
		}
		return self::self_closing_shortcode( 'button', $atts ) . "\n";
	}

	/**
	 * Image element → [ux_image] or <img> tag.
	 */
	private static function convert_image( $el ) {
		$class = ! empty( $el['class'] ) ? $el['class'] : '';
		$alt   = ! empty( $el['alt'] ) ? esc_attr( $el['alt'] ) : '';

		// Prefer WordPress attachment ID.
		if ( ! empty( $el['attachment_id'] ) ) {
			$atts = array( 'id' => (int) $el['attachment_id'] );
			if ( $class ) {
				$atts['class'] = sanitize_html_class( $class );
			}
			return self::self_closing_shortcode( 'ux_image', $atts ) . "\n";
		}

		// Fallback to plain img tag.
		$url = ! empty( $el['url'] ) ? esc_url( $el['url'] ) : '';
		if ( $url ) {
			$class_attr = $class ? ' class="' . esc_attr( $class ) . '"' : '';
			return "<img src=\"{$url}\" alt=\"{$alt}\"{$class_attr}>\n";
		}

		return '';
	}

	/**
	 * Banner element → [ux_banner].
	 */
	private static function convert_banner( $el ) {
		$atts = array();
		if ( ! empty( $el['class'] ) ) {
			$atts['class'] = sanitize_html_class( $el['class'] );
		}
		if ( ! empty( $el['bg'] ) ) {
			$atts['bg'] = esc_url( $el['bg'] );
		}
		if ( ! empty( $el['height'] ) ) {
			$atts['height'] = sanitize_text_field( $el['height'] );
		}

		$inner = '';
		if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
			foreach ( $el['elements'] as $child ) {
				$inner .= self::convert_element( $child );
			}
		}

		return self::wrap_shortcode( 'ux_banner', $atts, $inner ) . "\n";
	}

	/**
	 * Gap element → [gap].
	 */
	private static function convert_gap( $el ) {
		$atts = array();
		if ( ! empty( $el['height'] ) ) {
			$atts['height'] = sanitize_text_field( $el['height'] );
		}
		return self::self_closing_shortcode( 'gap', $atts ) . "\n";
	}

	/**
	 * Divider element → [divider].
	 */
	private static function convert_divider( $el ) {
		$atts = array();
		if ( ! empty( $el['style'] ) ) {
			$atts['style'] = sanitize_text_field( $el['style'] );
		}
		return self::self_closing_shortcode( 'divider', $atts ) . "\n";
	}

	/**
	 * Text box → [text_box].
	 */
	private static function convert_text_box( $el ) {
		$text = ! empty( $el['text'] ) ? wp_kses_post( $el['text'] ) : '';
		return self::wrap_shortcode( 'text_box', array(), $text ) . "\n";
	}

	/**
	 * Products → [ux_products].
	 */
	private static function convert_products( $el ) {
		$atts = array(
			'products' => isset( $el['count'] ) ? (int) $el['count'] : 8,
			'columns'  => isset( $el['columns'] ) ? (int) $el['columns'] : 4,
		);
		if ( ! empty( $el['class'] ) ) {
			$atts['class'] = sanitize_html_class( $el['class'] );
		}
		if ( ! empty( $el['orderby'] ) ) {
			$atts['orderby'] = sanitize_text_field( $el['orderby'] );
		}
		return self::self_closing_shortcode( 'ux_products', $atts ) . "\n";
	}

	/**
	 * Posts → [blog_posts].
	 */
	private static function convert_posts( $el ) {
		$atts = array(
			'style'   => isset( $el['style'] ) ? sanitize_text_field( $el['style'] ) : 'normal',
			'columns' => isset( $el['columns'] ) ? (int) $el['columns'] : 3,
		);
		if ( ! empty( $el['count'] ) ) {
			$atts['count'] = (int) $el['count'];
		}
		return self::self_closing_shortcode( 'blog_posts', $atts ) . "\n";
	}

	// -------------------------------------------------------------------------
	// Shortcode builder helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a wrapping shortcode: [tag atts]inner[/tag].
	 *
	 * @param string $tag   Shortcode tag.
	 * @param array  $atts  Attributes key => value.
	 * @param string $inner Inner content.
	 * @return string
	 */
	private static function wrap_shortcode( $tag, $atts, $inner ) {
		return '[' . $tag . self::build_atts( $atts ) . ']' . $inner . '[/' . $tag . ']';
	}

	/**
	 * Build a self-closing shortcode: [tag atts].
	 *
	 * @param string $tag  Shortcode tag.
	 * @param array  $atts Attributes.
	 * @return string
	 */
	private static function self_closing_shortcode( $tag, $atts ) {
		return '[' . $tag . self::build_atts( $atts ) . ']';
	}

	/**
	 * Convert atts array to shortcode attribute string.
	 *
	 * @param array $atts Key => value pairs.
	 * @return string
	 */
	private static function build_atts( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}
		$parts = array();
		foreach ( $atts as $key => $value ) {
			$parts[] = sanitize_key( $key ) . '="' . esc_attr( $value ) . '"';
		}
		return ' ' . implode( ' ', $parts );
	}
}
