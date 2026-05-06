<?php
/**
 * Settings management for AI Flatsome Shortcode Builder.
 *
 * Supports multiple AI providers:
 *  - openai  : OpenAI (paid)
 *  - gemini  : Google Gemini (generous free tier)
 *  - claude  : Anthropic Claude Sonnet (paid, best reasoning)
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFSB_Settings
 */
class AFSB_Settings {

	/** Option key stored in wp_options. */
	const OPTION_KEY = 'afsb_settings';

	/** Supported AI providers. */
	const PROVIDERS = array( 'openai', 'gemini', 'claude' );

	/** Allowed OpenAI models. */
	const OPENAI_MODELS = array( 'gpt-4o', 'gpt-4o-mini', 'custom' );

	/** Allowed Gemini models. */
	const GEMINI_MODELS = array(
		'gemini-2.5-pro',
		'gemini-2.5-flash',
		'gemini-2.0-flash',
		'gemini-2.0-flash-lite',
		'custom',
	);

	/** Allowed Claude models. */
	const CLAUDE_MODELS = array(
		'claude-sonnet-4-5',
		'claude-3-7-sonnet-20250219',
		'claude-3-5-sonnet-20241022',
		'claude-3-5-haiku-20241022',
		'custom',
	);

	/**
	 * Save settings from POST data.
	 * Must be called after nonce/capability verification.
	 *
	 * @return void
	 */
	public static function save() {
		$current = self::get_all();

		// ── Provider ──────────────────────────────────────────────────────────
		if ( isset( $_POST['afsb_provider'] ) ) {
			$provider = sanitize_text_field( wp_unslash( $_POST['afsb_provider'] ) );
			$current['provider'] = in_array( $provider, self::PROVIDERS, true ) ? $provider : 'openai';
		}

		// ── OpenAI ────────────────────────────────────────────────────────────
		if ( isset( $_POST['afsb_api_key'] ) ) {
			$current['api_key'] = sanitize_text_field( wp_unslash( $_POST['afsb_api_key'] ) );
		}

		if ( isset( $_POST['afsb_model'] ) ) {
			$model = sanitize_text_field( wp_unslash( $_POST['afsb_model'] ) );
			$current['model'] = in_array( $model, self::OPENAI_MODELS, true ) ? $model : 'gpt-4o';
		}

		if ( isset( $_POST['afsb_custom_model'] ) ) {
			$current['custom_model'] = sanitize_text_field( wp_unslash( $_POST['afsb_custom_model'] ) );
		}

		// ── Gemini ────────────────────────────────────────────────────────────
		if ( isset( $_POST['afsb_gemini_api_key'] ) ) {
			$current['gemini_api_key'] = sanitize_text_field( wp_unslash( $_POST['afsb_gemini_api_key'] ) );
		}

		if ( isset( $_POST['afsb_gemini_model'] ) ) {
			$gmodel = sanitize_text_field( wp_unslash( $_POST['afsb_gemini_model'] ) );
			$current['gemini_model'] = in_array( $gmodel, self::GEMINI_MODELS, true ) ? $gmodel : 'gemini-2.0-flash';
		}

		if ( isset( $_POST['afsb_gemini_custom_model'] ) ) {
			$current['gemini_custom_model'] = sanitize_text_field( wp_unslash( $_POST['afsb_gemini_custom_model'] ) );
		}

		// ── Claude ────────────────────────────────────────────────────────────
		if ( isset( $_POST['afsb_claude_api_key'] ) ) {
			$current['claude_api_key'] = sanitize_text_field( wp_unslash( $_POST['afsb_claude_api_key'] ) );
		}

		if ( isset( $_POST['afsb_claude_model'] ) ) {
			$cmodel = sanitize_text_field( wp_unslash( $_POST['afsb_claude_model'] ) );
			$current['claude_model'] = in_array( $cmodel, self::CLAUDE_MODELS, true ) ? $cmodel : 'claude-sonnet-4-5';
		}

		if ( isset( $_POST['afsb_claude_custom_model'] ) ) {
			$current['claude_custom_model'] = sanitize_text_field( wp_unslash( $_POST['afsb_claude_custom_model'] ) );
		}

		// ── Shared ────────────────────────────────────────────────────────────
		if ( isset( $_POST['afsb_temperature'] ) ) {
			$temp = (float) $_POST['afsb_temperature'];
			$current['temperature'] = max( 0.0, min( 1.0, $temp ) ); // Claude max is 1.0
		}

		update_option( self::OPTION_KEY, $current );
	}

	/**
	 * Get all settings with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		$defaults = array(
			// Shared
			'provider'            => 'openai',
			'temperature'         => 0.2,
			// OpenAI
			'api_key'             => '',
			'model'               => 'gpt-4o',
			'custom_model'        => '',
			// Gemini
			'gemini_api_key'      => '',
			'gemini_model'        => 'gemini-2.0-flash',
			'gemini_custom_model' => '',
			// Claude
			'claude_api_key'      => '',
			'claude_model'        => 'claude-sonnet-4-5',
			'claude_custom_model' => '',
		);
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Get the currently selected provider.
	 *
	 * @return string 'openai' | 'gemini' | 'claude'
	 */
	public static function get_provider() {
		$settings = self::get_all();
		return in_array( $settings['provider'], self::PROVIDERS, true ) ? $settings['provider'] : 'openai';
	}

	// ── OpenAI getters ────────────────────────────────────────────────────────

	/** @return string */
	public static function get_api_key() {
		return self::get_all()['api_key'];
	}

	/** @return string */
	public static function get_model() {
		$s = self::get_all();
		if ( 'custom' === $s['model'] && ! empty( $s['custom_model'] ) ) {
			return $s['custom_model'];
		}
		return ! empty( $s['model'] ) ? $s['model'] : 'gpt-4o';
	}

	// ── Gemini getters ────────────────────────────────────────────────────────

	/** @return string */
	public static function get_gemini_api_key() {
		return self::get_all()['gemini_api_key'];
	}

	/** @return string */
	public static function get_gemini_model() {
		$s = self::get_all();
		if ( 'custom' === $s['gemini_model'] && ! empty( $s['gemini_custom_model'] ) ) {
			return $s['gemini_custom_model'];
		}
		return ! empty( $s['gemini_model'] ) ? $s['gemini_model'] : 'gemini-2.0-flash';
	}

	// ── Claude getters ────────────────────────────────────────────────────────

	/** @return string */
	public static function get_claude_api_key() {
		return self::get_all()['claude_api_key'];
	}

	/** @return string */
	public static function get_claude_model() {
		$s = self::get_all();
		if ( 'custom' === $s['claude_model'] && ! empty( $s['claude_custom_model'] ) ) {
			return $s['claude_custom_model'];
		}
		return ! empty( $s['claude_model'] ) ? $s['claude_model'] : 'claude-sonnet-4-5';
	}

	// ── Shared getters ────────────────────────────────────────────────────────

	/** @return float */
	public static function get_temperature() {
		$s = self::get_all();
		// Claude max temperature is 1.0; clamp here.
		$temp = isset( $s['temperature'] ) ? (float) $s['temperature'] : 0.2;
		if ( 'claude' === self::get_provider() ) {
			return min( 1.0, $temp );
		}
		return $temp;
	}
}
