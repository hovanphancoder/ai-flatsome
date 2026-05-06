<?php
/**
 * AI Client – communicates with OpenAI, Google Gemini, or Anthropic Claude API.
 *
 * Provider is selected via AFSB_Settings::get_provider().
 *  - 'openai' → OpenAI Chat Completions (vision via gpt-4o)
 *  - 'gemini' → Google Gemini generateContent (free tier, vision supported)
 *  - 'claude' → Anthropic Claude Messages API (best reasoning, vision supported)
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFSB_AI_Client
 */
class AFSB_AI_Client {

	// ── Endpoints ─────────────────────────────────────────────────────────────
	const OPENAI_ENDPOINT  = 'https://api.openai.com/v1/chat/completions';
	const GEMINI_ENDPOINT  = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';
	const CLAUDE_ENDPOINT  = 'https://api.anthropic.com/v1/messages';
	const CLAUDE_VERSION   = '2023-06-01';

	// ── System Prompt ─────────────────────────────────────────────────────────

	/**
	 * System prompt – identical for both providers.
	 *
	 * @return string
	 */
	private static function system_prompt() {
		return 'You are an expert UI layout analyzer and Flatsome UX Builder layout planner.

Your task:
Analyze the provided image or website HTML and return ONLY valid JSON following the required schema.

Rules:
- Do not return markdown.
- Do not return explanations.
- Do not generate Flatsome shortcode.
- Return JSON only.
- Detect page sections: hero, features, products, posts, banners, testimonials, CTA, FAQ, footer.
- Each section must have a unique section_class following the pattern: ai-section-{type}-{3digit_id} (e.g. ai-section-hero-001).
- All custom CSS must be scoped by that section_class.
- Reusable components must use reusable classes: ai-button, ai-product-card, ai-post-card, ai-banner-card, ai-icon-box, ai-testimonial-card, ai-category-card.
- Avoid global CSS that affects the whole site.
- Prefer Flatsome native layout attributes over custom CSS.
- Use custom CSS only when needed.
- Do not generate JavaScript unless absolutely necessary.
- Keep layout responsive; use span and span_sm for columns.
- Output clean, valid JSON matching this schema exactly:
{
  "type": "page",
  "page_name": "string",
  "reusable_components": {
    "ai-button": { "css": "string" },
    "ai-product-card": { "css": "string" },
    "ai-post-card": { "css": "string" },
    "ai-banner-card": { "css": "string" }
  },
  "sections": [
    {
      "type": "string",
      "section_id": "string",
      "section_class": "string",
      "background_color": "string",
      "background_image": "string",
      "padding": "string",
      "custom_css": "string",
      "rows": [
        {
          "type": "row",
          "columns": [
            {
              "span": 6,
              "span_sm": 12,
              "elements": []
            }
          ]
        }
      ]
    }
  ]
}';
	}

	// =========================================================================
	// Public API
	// =========================================================================

	/**
	 * Analyze an uploaded image and return the decoded JSON layout.
	 *
	 * @param string $file_path Absolute path to the uploaded image.
	 * @return array|WP_Error
	 */
	public static function analyze_image( $file_path ) {
		$provider = AFSB_Settings::get_provider();

		if ( 'gemini' === $provider ) {
			return self::gemini_analyze_image( $file_path );
		}

		if ( 'claude' === $provider ) {
			return self::claude_analyze_image( $file_path );
		}

		return self::openai_analyze_image( $file_path );
	}

	/**
	 * Analyze a URL and return the decoded JSON layout.
	 *
	 * @param string $url Target URL.
	 * @return array|WP_Error
	 */
	public static function analyze_url( $url ) {
		$provider = AFSB_Settings::get_provider();

		if ( 'gemini' === $provider ) {
			return self::gemini_analyze_url( $url );
		}

		if ( 'claude' === $provider ) {
			return self::claude_analyze_url( $url );
		}

		return self::openai_analyze_url( $url );
	}

	// =========================================================================
	// ── OpenAI ────────────────────────────────────────────────────────────────
	// =========================================================================

	/**
	 * OpenAI: analyze image.
	 *
	 * @param string $file_path
	 * @return array|WP_Error
	 */
	private static function openai_analyze_image( $file_path ) {
		$api_key = AFSB_Settings::get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'OpenAI API key is not configured.', AFSB_TEXT_DOMAIN ) );
		}

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'invalid_image', __( 'Uploaded image file not found.', AFSB_TEXT_DOMAIN ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$image_data = base64_encode( file_get_contents( $file_path ) );
		$mime       = mime_content_type( $file_path );
		$image_url  = 'data:' . $mime . ';base64,' . $image_data;

		$messages = array(
			array( 'role' => 'system', 'content' => self::system_prompt() ),
			array(
				'role'    => 'user',
				'content' => array(
					array( 'type' => 'image_url', 'image_url' => array( 'url' => $image_url ) ),
					array( 'type' => 'text', 'text' => 'Analyze this UI screenshot and return the JSON layout schema.' ),
				),
			),
		);

		return self::openai_call( $messages );
	}

	/**
	 * OpenAI: analyze URL.
	 *
	 * @param string $url
	 * @return array|WP_Error
	 */
	private static function openai_analyze_url( $url ) {
		$api_key = AFSB_Settings::get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'OpenAI API key is not configured.', AFSB_TEXT_DOMAIN ) );
		}

		if ( ! afsb_validate_url( $url ) ) {
			return new WP_Error( 'invalid_url', __( 'Please provide a valid HTTP/HTTPS URL.', AFSB_TEXT_DOMAIN ) );
		}

		$content = self::fetch_url_content( $url );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$messages = array(
			array( 'role' => 'system', 'content' => self::system_prompt() ),
			array( 'role' => 'user', 'content' => 'Analyze the following website text content and return the JSON layout schema:

' . $content ),
		);

		return self::openai_call( $messages );
	}

	/**
	 * Send messages to OpenAI and return decoded layout array.
	 *
	 * @param array $messages
	 * @return array|WP_Error
	 */
	private static function openai_call( $messages ) {
		$api_key     = AFSB_Settings::get_api_key();
		$model       = AFSB_Settings::get_model();
		$temperature = AFSB_Settings::get_temperature();

		$body = wp_json_encode( array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => 4096,
		) );

		$response = wp_remote_post( self::OPENAI_ENDPOINT, array(
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', sprintf(
				/* translators: %s: error message */
				__( 'OpenAI API request failed: %s', AFSB_TEXT_DOMAIN ),
				$response->get_error_message()
			) );
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );

		if ( 200 !== $http_code ) {
			$error_data = json_decode( $raw_body, true );
			$error_msg  = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : $raw_body;

			// Friendly message for quota errors.
			if ( 429 === $http_code ) {
				$error_msg = __( 'OpenAI quota exceeded (HTTP 429). You can switch to the free Google Gemini provider in Settings.', AFSB_TEXT_DOMAIN );
			}

			return new WP_Error( 'api_http_error', sprintf(
				/* translators: 1: HTTP code, 2: error message */
				__( 'OpenAI API returned HTTP %1$s: %2$s', AFSB_TEXT_DOMAIN ),
				$http_code,
				$error_msg
			) );
		}

		$api_data = json_decode( $raw_body, true );
		$content  = isset( $api_data['choices'][0]['message']['content'] ) ? $api_data['choices'][0]['message']['content'] : '';

		return self::parse_ai_response( $content );
	}

	// =========================================================================
	// ── Google Gemini ─────────────────────────────────────────────────────────
	// =========================================================================

	/**
	 * Gemini: analyze image.
	 *
	 * @param string $file_path
	 * @return array|WP_Error
	 */
	private static function gemini_analyze_image( $file_path ) {
		$api_key = AFSB_Settings::get_gemini_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'Google Gemini API key is not configured.', AFSB_TEXT_DOMAIN ) );
		}

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'invalid_image', __( 'Uploaded image file not found.', AFSB_TEXT_DOMAIN ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$image_data = base64_encode( file_get_contents( $file_path ) );
		$mime       = mime_content_type( $file_path );

		$parts = array(
			array(
				'inlineData' => array(
					'mimeType' => $mime,
					'data'     => $image_data,
				),
			),
			array( 'text' => 'Analyze this UI screenshot and return the JSON layout schema.' ),
		);

		return self::gemini_call( $parts );
	}

	/**
	 * Gemini: analyze URL.
	 *
	 * @param string $url
	 * @return array|WP_Error
	 */
	private static function gemini_analyze_url( $url ) {
		$api_key = AFSB_Settings::get_gemini_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'Google Gemini API key is not configured.', AFSB_TEXT_DOMAIN ) );
		}

		if ( ! afsb_validate_url( $url ) ) {
			return new WP_Error( 'invalid_url', __( 'Please provide a valid HTTP/HTTPS URL.', AFSB_TEXT_DOMAIN ) );
		}

		$content = self::fetch_url_content( $url );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$parts = array(
			array( 'text' => 'Analyze the following website text content and return the JSON layout schema:

' . $content ),
		);

		return self::gemini_call( $parts );
	}

	/**
	 * Send parts to Gemini generateContent and return decoded layout array.
	 * Auto-retries up to 3 times with exponential backoff on 429 / 503.
	 *
	 * @param array $parts Content parts array.
	 * @return array|WP_Error
	 */
	
	private static function gemini_call( $parts ) {
		$api_key     = AFSB_Settings::get_gemini_api_key();
		$model       = AFSB_Settings::get_gemini_model();
		$temperature = AFSB_Settings::get_temperature();

		// Build endpoint URL.
		$endpoint = str_replace( '{model}', rawurlencode( $model ), self::GEMINI_ENDPOINT );
		$endpoint = add_query_arg( 'key', $api_key, $endpoint );

		$body = wp_json_encode( array(
			'systemInstruction' => array(
				'parts' => array(
					array( 'text' => self::system_prompt() ),
				),
			),
			'contents'          => array(
				array( 'parts' => $parts ),
			),
			'generationConfig'  => array(
				'temperature'      => $temperature,
				'maxOutputTokens'  => 8192,
				'responseMimeType' => 'application/json',
			),
		) );

		// Retry delays in seconds for 429 / 503: 1st retry after 5s, 2nd after 15s.
		$retry_delays = array( 5, 15 );
		$max_attempts = 3;
		$response     = null;

		for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
			$response = wp_remote_post( $endpoint, array(
				'timeout' => 60,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $body,
			) );

			if ( is_wp_error( $response ) ) {
				// Network-level error – no point retrying.
				return new WP_Error( 'api_error', sprintf(
					/* translators: %s: error message */
					__( 'Gemini API request failed: %s', AFSB_TEXT_DOMAIN ),
					$response->get_error_message()
				) );
			}

			$http_code = (int) wp_remote_retrieve_response_code( $response );

			// Retry only on rate-limit / server-overload errors.
			if ( in_array( $http_code, array( 429, 503 ), true ) && $attempt < $max_attempts ) {
				$wait = $retry_delays[ $attempt - 1 ];
				sleep( $wait ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				continue;
			}

			// Any other status (200 or hard error) – stop retrying.
			break;
		}

		// $response is guaranteed non-null here.
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', sprintf(
				/* translators: %s: error message */
				__( 'Gemini API request failed: %s', AFSB_TEXT_DOMAIN ),
				$response->get_error_message()
			) );
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );

		if ( 200 !== $http_code ) {
			$error_data = json_decode( $raw_body, true );
			$error_msg  = '';

			// Gemini error format.
			if ( isset( $error_data['error']['message'] ) ) {
				$error_msg = $error_data['error']['message'];
			} elseif ( isset( $error_data['error']['status'] ) ) {
				$error_msg = $error_data['error']['status'];
			} else {
				$error_msg = $raw_body;
			}

			// Friendly error messages per HTTP status.
			if ( 429 === $http_code ) {
				$error_msg = __( 'Gemini API quota exceeded (HTTP 429). The plugin already retried 3 times automatically. Please wait 1–2 minutes before trying again, or switch to gemini-2.5-flash / Claude in Settings.', AFSB_TEXT_DOMAIN );
			} elseif ( 404 === $http_code ) {
				$current_model = AFSB_Settings::get_gemini_model();
				$error_msg = sprintf(
					/* translators: %s: current model name */
					__( 'Model "%s" is no longer available or has been deprecated by Google (HTTP 404). Please go to Settings and switch to gemini-2.0-flash or gemini-2.5-flash.', AFSB_TEXT_DOMAIN ),
					$current_model
				);
			} elseif ( 503 === $http_code ) {
				$current_model = AFSB_Settings::get_gemini_model();
				$error_msg = sprintf(
					/* translators: %s: current model name */
					__( 'The model "%s" is currently overloaded (HTTP 503). Google\'s servers are experiencing high demand. Please wait 30–60 seconds and try again, or switch to gemini-2.0-flash in Settings for better availability.', AFSB_TEXT_DOMAIN ),
					$current_model
				);
			} elseif ( 500 === $http_code ) {
				$error_msg = __( 'Gemini API internal server error (HTTP 500). This is a temporary issue on Google\'s side. Please try again in a moment.', AFSB_TEXT_DOMAIN );
			}

			return new WP_Error( 'api_http_error', sprintf(
				/* translators: 1: HTTP code, 2: error message */
				__( 'Gemini API returned HTTP %1$s: %2$s', AFSB_TEXT_DOMAIN ),
				$http_code,
				$error_msg
			) );
		}

		// Parse Gemini response structure.
		$api_data = json_decode( $raw_body, true );
		$content  = '';

		// Gemini response: candidates[0].content.parts[0].text
		if ( isset( $api_data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$content = $api_data['candidates'][0]['content']['parts'][0]['text'];
		}

		if ( empty( $content ) ) {
			// Check for safety blocks.
			$finish_reason = isset( $api_data['candidates'][0]['finishReason'] ) ? $api_data['candidates'][0]['finishReason'] : '';
			if ( 'SAFETY' === $finish_reason ) {
				return new WP_Error( 'safety_block', __( 'Gemini blocked the request due to safety filters. Try a different image or URL.', AFSB_TEXT_DOMAIN ) );
			}
			return new WP_Error( 'empty_ai_response', __( 'Gemini returned an empty response.', AFSB_TEXT_DOMAIN ) );
		}

		return self::parse_ai_response( $content );
	}

	// =========================================================================
	// ── Anthropic Claude ──────────────────────────────────────────────────────
	// =========================================================================

	/**
	 * Claude: analyze image.
	 *
	 * @param string $file_path
	 * @return array|WP_Error
	 */
	private static function claude_analyze_image( $file_path ) {
		$api_key = AFSB_Settings::get_claude_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'Anthropic Claude API key is not configured.', AFSB_TEXT_DOMAIN ) );
		}

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'invalid_image', __( 'Uploaded image file not found.', AFSB_TEXT_DOMAIN ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$image_data = base64_encode( file_get_contents( $file_path ) );
		$mime       = mime_content_type( $file_path );

		// Claude vision content format.
		$content = array(
			array(
				'type'   => 'image',
				'source' => array(
					'type'       => 'base64',
					'media_type' => $mime,
					'data'       => $image_data,
				),
			),
			array(
				'type' => 'text',
				'text' => 'Analyze this UI screenshot and return the JSON layout schema.',
			),
		);

		return self::claude_call( $content );
	}

	/**
	 * Claude: analyze URL.
	 *
	 * @param string $url
	 * @return array|WP_Error
	 */
	private static function claude_analyze_url( $url ) {
		$api_key = AFSB_Settings::get_claude_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'Anthropic Claude API key is not configured.', AFSB_TEXT_DOMAIN ) );
		}

		if ( ! afsb_validate_url( $url ) ) {
			return new WP_Error( 'invalid_url', __( 'Please provide a valid HTTP/HTTPS URL.', AFSB_TEXT_DOMAIN ) );
		}

		$text = self::fetch_url_content( $url );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$content = array(
			array(
				'type' => 'text',
				'text' => 'Analyze the following website text content and return the JSON layout schema:

' . $text,
			),
		);

		return self::claude_call( $content );
	}

	/**
	 * Send a message to Anthropic Claude and return decoded layout array.
	 *
	 * @param array $content User content parts.
	 * @return array|WP_Error
	 */
	private static function claude_call( $content ) {
		$api_key     = AFSB_Settings::get_claude_api_key();
		$model       = AFSB_Settings::get_claude_model();
		$temperature = AFSB_Settings::get_temperature(); // Capped at 1.0 for Claude.

		$body = wp_json_encode( array(
			'model'       => $model,
			'max_tokens'  => 4096,
			'temperature' => $temperature,
			'system'      => self::system_prompt(),
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $content,
				),
			),
		) );

		$response = wp_remote_post( self::CLAUDE_ENDPOINT, array(
			'timeout' => 60,
			'headers' => array(
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => self::CLAUDE_VERSION,
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', sprintf(
				/* translators: %s: error message */
				__( 'Claude API request failed: %s', AFSB_TEXT_DOMAIN ),
				$response->get_error_message()
			) );
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );

		if ( 200 !== $http_code ) {
			$error_data = json_decode( $raw_body, true );
			$error_msg  = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : $raw_body;

			// Friendly quota/credit messages.
			if ( 429 === $http_code ) {
				$error_msg = __( 'Claude API rate limit exceeded (HTTP 429). Please wait a moment and try again.', AFSB_TEXT_DOMAIN );
			} elseif ( 402 === $http_code ) {
				$error_msg = __( 'Claude API credit balance is low (HTTP 402). Add credits at console.anthropic.com, or switch to the free Gemini provider.', AFSB_TEXT_DOMAIN );
			}

			return new WP_Error( 'api_http_error', sprintf(
				/* translators: 1: HTTP code, 2: error message */
				__( 'Claude API returned HTTP %1$s: %2$s', AFSB_TEXT_DOMAIN ),
				$http_code,
				$error_msg
			) );
		}

		// Claude response: content[0].text
		$api_data = json_decode( $raw_body, true );
		$text     = isset( $api_data['content'][0]['text'] ) ? $api_data['content'][0]['text'] : '';

		if ( empty( $text ) ) {
			// Check stop reason.
			$stop_reason = isset( $api_data['stop_reason'] ) ? $api_data['stop_reason'] : '';
			if ( 'max_tokens' === $stop_reason ) {
				return new WP_Error( 'max_tokens', __( 'Claude response was cut off (max_tokens reached). Try a simpler layout or increase max_tokens.', AFSB_TEXT_DOMAIN ) );
			}
			return new WP_Error( 'empty_ai_response', __( 'Claude returned an empty response.', AFSB_TEXT_DOMAIN ) );
		}

		return self::parse_ai_response( $text );
	}

	// =========================================================================
	// ── Shared helpers ────────────────────────────────────────────────────────
	// =========================================================================

	/**
	 * Fetch and truncate HTML from a URL.
	 *
	 * @param string $url
	 * @return string|WP_Error
	 */
	private static function fetch_url_content( $url ) {
		$response = wp_remote_get( $url, array(
			'timeout'    => 20,
			'user-agent' => 'Mozilla/5.0 (compatible; AFSB/1.0)',
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'fetch_failed', sprintf(
				/* translators: %s: error message */
				__( 'Could not fetch URL: %s', AFSB_TEXT_DOMAIN ),
				$response->get_error_message()
			) );
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return new WP_Error( 'empty_response', __( 'The URL returned an empty response.', AFSB_TEXT_DOMAIN ) );
		}

		return afsb_truncate_html( $html );
	}

	/**
	 * Strip markdown fences, decode JSON, validate schema.
	 *
	 * @param string $raw Raw text from AI.
	 * @return array|WP_Error
	 */
	private static function parse_ai_response( $raw ) {
		if ( empty( $raw ) ) {
			return new WP_Error( 'empty_ai_response', __( 'AI returned an empty response.', AFSB_TEXT_DOMAIN ) );
		}

		// Strip ```json … ``` fences if AI disobeyed the rules.
		$raw = preg_replace( '/^```(?:json)?\s*/i', '', trim( $raw ) );
		$raw = preg_replace( '/\s*```$/', '', $raw );

		$decoded = json_decode( $raw, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'invalid_json', sprintf(
				/* translators: %s: JSON error */
				__( 'AI response is not valid JSON: %s', AFSB_TEXT_DOMAIN ),
				json_last_error_msg()
			) );
		}

		$valid = afsb_validate_json_schema( $decoded );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		return $decoded;
	}
}
