<?php
/**
 * Admin Page – renders the plugin admin UI (Generate + Settings tabs).
 *
 * @package AI_Flatsome_Shortcode_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFSB_Admin_Page
 */
class AFSB_Admin_Page {

	/**
	 * Render the full admin page with tab navigation.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', AFSB_TEXT_DOMAIN ) );
		}

		// Handle settings save.
		if ( isset( $_POST['afsb_save_settings'] ) ) {
			check_admin_referer( 'afsb_save_settings_action', 'afsb_settings_nonce' );
			AFSB_Settings::save();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', AFSB_TEXT_DOMAIN ) . '</p></div>';
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'generate';
		$tabs       = array(
			'generate' => __( '⚡ Generate', AFSB_TEXT_DOMAIN ),
			'settings' => __( '⚙️ Settings', AFSB_TEXT_DOMAIN ),
		);
		?>
		<div class="wrap afsb-wrap">
			<h1 class="afsb-page-title">
				<span class="afsb-logo">🤖</span>
				<?php esc_html_e( 'AI Flatsome Shortcode Builder', AFSB_TEXT_DOMAIN ); ?>
			</h1>

			<nav class="afsb-tabs">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-flatsome&tab=' . $slug ) ); ?>"
					   class="afsb-tab<?php echo $active_tab === $slug ? ' afsb-tab--active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="afsb-tab-content">
				<?php if ( 'generate' === $active_tab ) : ?>
					<?php self::render_generate_tab(); ?>
				<?php else : ?>
					<?php self::render_settings_tab(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Generate Tab
	// -------------------------------------------------------------------------

	/**
	 * Render the Generate tab.
	 */
	private static function render_generate_tab() {
		$nonce = wp_create_nonce( 'afsb_generate_nonce' );
		$page_nonce = wp_create_nonce( 'afsb_create_page_nonce' );
		?>
		<div class="afsb-generate">

			<!-- === LEFT PANEL: Input === -->
			<div class="afsb-panel afsb-panel--input">
				<h2 class="afsb-section-title"><?php esc_html_e( 'Input', AFSB_TEXT_DOMAIN ); ?></h2>

				<!-- Mode selector -->
				<div class="afsb-field">
					<label class="afsb-label"><?php esc_html_e( 'Mode', AFSB_TEXT_DOMAIN ); ?></label>
					<div class="afsb-mode-toggle">
						<label class="afsb-mode-option">
							<input type="radio" name="afsb_mode" id="afsb-mode-image" value="image" checked>
							<span><?php esc_html_e( '🖼️ Image to Layout', AFSB_TEXT_DOMAIN ); ?></span>
						</label>
						<label class="afsb-mode-option">
							<input type="radio" name="afsb_mode" id="afsb-mode-url" value="url">
							<span><?php esc_html_e( '🌐 URL to Layout', AFSB_TEXT_DOMAIN ); ?></span>
						</label>
					</div>
				</div>

				<!-- Image upload area -->
				<div class="afsb-field" id="afsb-image-field">
					<label class="afsb-label" for="afsb-image-input"><?php esc_html_e( 'Upload Image', AFSB_TEXT_DOMAIN ); ?></label>
					<div class="afsb-dropzone" id="afsb-dropzone">
						<input type="file" id="afsb-image-input" accept="image/jpeg,image/png,image/gif,image/webp" class="afsb-file-input">
						<div class="afsb-dropzone-placeholder" id="afsb-dropzone-placeholder">
							<span class="afsb-dropzone-icon">📁</span>
							<p><?php esc_html_e( 'Drag & drop or click to upload (JPEG, PNG, GIF, WebP · max 8 MB)', AFSB_TEXT_DOMAIN ); ?></p>
						</div>
						<img id="afsb-image-preview" src="" alt="" style="display:none;">
					</div>
				</div>

				<!-- URL input area -->
				<div class="afsb-field" id="afsb-url-field" style="display:none;">
					<label class="afsb-label" for="afsb-url-input"><?php esc_html_e( 'Website URL', AFSB_TEXT_DOMAIN ); ?></label>
					<input type="url"
					       id="afsb-url-input"
					       class="afsb-input"
					       placeholder="https://example.com"
					       autocomplete="off">
				</div>

				<!-- Generate button -->
				<div class="afsb-field">
					<button id="afsb-btn-generate" class="afsb-btn afsb-btn--primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<span class="afsb-btn-icon">✨</span>
						<?php esc_html_e( 'Generate Layout', AFSB_TEXT_DOMAIN ); ?>
					</button>
				</div>

				<!-- Loading indicator -->
				<div id="afsb-loading" class="afsb-loading" style="display:none;">
					<div class="afsb-spinner"></div>
					<span><?php esc_html_e( 'Analyzing with AI… this may take up to 30s', AFSB_TEXT_DOMAIN ); ?></span>
				</div>

				<!-- Error message -->
				<div id="afsb-error" class="afsb-notice afsb-notice--error" style="display:none;"></div>
			</div>

			<!-- === RIGHT PANEL: Output === -->
			<div class="afsb-panel afsb-panel--output">
				<h2 class="afsb-section-title"><?php esc_html_e( 'Output', AFSB_TEXT_DOMAIN ); ?></h2>

				<!-- JSON Layout -->
				<div class="afsb-output-block">
					<div class="afsb-output-header">
						<label class="afsb-label"><?php esc_html_e( 'JSON Layout', AFSB_TEXT_DOMAIN ); ?></label>
					</div>
					<textarea id="afsb-output-json" class="afsb-textarea afsb-textarea--code" rows="8" readonly placeholder="<?php esc_attr_e( 'AI JSON response will appear here…', AFSB_TEXT_DOMAIN ); ?>"></textarea>
				</div>

				<!-- Flatsome Shortcode -->
				<div class="afsb-output-block">
					<div class="afsb-output-header">
						<label class="afsb-label"><?php esc_html_e( 'Flatsome Shortcode', AFSB_TEXT_DOMAIN ); ?></label>
						<button id="afsb-btn-copy-shortcode" class="afsb-btn afsb-btn--secondary afsb-btn--sm" disabled>
							📋 <?php esc_html_e( 'Copy', AFSB_TEXT_DOMAIN ); ?>
						</button>
					</div>
					<textarea id="afsb-output-shortcode" class="afsb-textarea afsb-textarea--code" rows="8" readonly placeholder="<?php esc_attr_e( 'Generated shortcode will appear here…', AFSB_TEXT_DOMAIN ); ?>"></textarea>
				</div>

				<!-- Generated CSS -->
				<div class="afsb-output-block">
					<div class="afsb-output-header">
						<label class="afsb-label"><?php esc_html_e( 'Generated CSS', AFSB_TEXT_DOMAIN ); ?></label>
						<button id="afsb-btn-copy-css" class="afsb-btn afsb-btn--secondary afsb-btn--sm" disabled>
							📋 <?php esc_html_e( 'Copy', AFSB_TEXT_DOMAIN ); ?>
						</button>
					</div>
					<textarea id="afsb-output-css" class="afsb-textarea afsb-textarea--code" rows="6" readonly placeholder="<?php esc_attr_e( 'Generated CSS will appear here…', AFSB_TEXT_DOMAIN ); ?>"></textarea>
				</div>

				<!-- Generated JS (optional) -->
				<div class="afsb-output-block" id="afsb-js-block" style="display:none;">
					<div class="afsb-output-header">
						<label class="afsb-label"><?php esc_html_e( 'Generated JS (optional)', AFSB_TEXT_DOMAIN ); ?></label>
					</div>
					<textarea id="afsb-output-js" class="afsb-textarea afsb-textarea--code" rows="4" readonly></textarea>
				</div>

				<!-- Create Draft Page -->
				<div class="afsb-output-block afsb-create-page">
					<h3 class="afsb-label"><?php esc_html_e( 'Create WordPress Page', AFSB_TEXT_DOMAIN ); ?></h3>
					<div class="afsb-row">
						<input type="text"
						       id="afsb-page-title"
						       class="afsb-input afsb-input--flex"
						       placeholder="<?php esc_attr_e( 'Page title…', AFSB_TEXT_DOMAIN ); ?>">
						<button id="afsb-btn-create-page"
						        class="afsb-btn afsb-btn--success"
						        data-nonce="<?php echo esc_attr( $page_nonce ); ?>"
						        disabled>
							🚀 <?php esc_html_e( 'Create Draft Page', AFSB_TEXT_DOMAIN ); ?>
						</button>
					</div>
					<div id="afsb-page-result" class="afsb-notice" style="display:none;"></div>
				</div>

			</div><!-- /.afsb-panel--output -->
		</div><!-- /.afsb-generate -->
		<?php
	}

	// -------------------------------------------------------------------------
	// Settings Tab
	// -------------------------------------------------------------------------

	/**
	 * Render the Settings tab.
	 */
	private static function render_settings_tab() {
		$settings = AFSB_Settings::get_all();
		$provider = $settings['provider'];
		?>
		<form method="post" action="" class="afsb-settings-form">
			<?php wp_nonce_field( 'afsb_save_settings_action', 'afsb_settings_nonce' ); ?>
			<input type="hidden" name="afsb_save_settings" value="1">

			<table class="form-table afsb-form-table" role="presentation">
				<tbody>

					<!-- ══ AI Provider ══════════════════════════════════════════ -->
					<tr>
						<th scope="row"><?php esc_html_e( 'AI Provider', AFSB_TEXT_DOMAIN ); ?></th>
						<td>
							<div class="afsb-provider-cards">

								<!-- OpenAI -->
								<label class="afsb-provider-card <?php echo 'openai' === $provider ? 'afsb-provider-card--active' : ''; ?>" id="afsb-provider-card-openai">
									<input type="radio" name="afsb_provider" value="openai" <?php checked( $provider, 'openai' ); ?>>
									<span class="afsb-provider-logo">🤖</span>
									<span class="afsb-provider-name">OpenAI</span>
									<span class="afsb-provider-badge afsb-badge--paid"><?php esc_html_e( 'Paid', AFSB_TEXT_DOMAIN ); ?></span>
									<span class="afsb-provider-models">gpt-4o · gpt-4o-mini</span>
								</label>

								<!-- Gemini -->
								<label class="afsb-provider-card <?php echo 'gemini' === $provider ? 'afsb-provider-card--active' : ''; ?>" id="afsb-provider-card-gemini">
									<input type="radio" name="afsb_provider" value="gemini" <?php checked( $provider, 'gemini' ); ?>>
									<span class="afsb-provider-logo">✨</span>
									<span class="afsb-provider-name">Google Gemini</span>
									<span class="afsb-provider-badge afsb-badge--paid"><?php esc_html_e( 'Paid / Free', AFSB_TEXT_DOMAIN ); ?></span>
									<span class="afsb-provider-models">gemini-2.5-pro · gemini-2.5-flash · gemini-2.0-flash</span>
								</label>

								<!-- Claude -->
								<label class="afsb-provider-card <?php echo 'claude' === $provider ? 'afsb-provider-card--active' : ''; ?>" id="afsb-provider-card-claude">
									<input type="radio" name="afsb_provider" value="claude" <?php checked( $provider, 'claude' ); ?>>
									<span class="afsb-provider-logo">🧠</span>
									<span class="afsb-provider-name">Claude Sonnet</span>
									<span class="afsb-provider-badge afsb-badge--paid"><?php esc_html_e( 'Paid', AFSB_TEXT_DOMAIN ); ?></span>
									<span class="afsb-provider-models">claude-sonnet-4-5 · claude-3-5-haiku</span>
								</label>

							</div>
							<p class="description" style="margin-top:10px;">
								<?php esc_html_e( 'Gemini 2.0 Flash is completely FREE. Claude Sonnet offers best reasoning. OpenAI gpt-4o gives the best image analysis.', AFSB_TEXT_DOMAIN ); ?>
							</p>
						</td>
					</tr>

					<!-- ══ OpenAI Settings ══════════════════════════════════════ -->
					<tr class="afsb-provider-section afsb-openai-section" style="<?php echo 'openai' !== $provider ? 'display:none;' : ''; ?>">
						<th scope="row" colspan="2">
							<div class="afsb-section-divider">
								<span>🤖 <?php esc_html_e( 'OpenAI Settings', AFSB_TEXT_DOMAIN ); ?></span>
							</div>
						</th>
					</tr>

					<tr class="afsb-provider-section afsb-openai-section" style="<?php echo 'openai' !== $provider ? 'display:none;' : ''; ?>">
						<th scope="row">
							<label for="afsb-api-key"><?php esc_html_e( 'OpenAI API Key', AFSB_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="password"
							       id="afsb-api-key"
							       name="afsb_api_key"
							       class="afsb-input afsb-input--wide"
							       value="<?php echo esc_attr( $settings['api_key'] ); ?>"
							       autocomplete="new-password">
							<p class="description">
								<?php esc_html_e( 'Stored securely, never exposed to the frontend.', AFSB_TEXT_DOMAIN ); ?>
								<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Get your API key →', AFSB_TEXT_DOMAIN ); ?>
								</a>
							</p>
						</td>
					</tr>

					<tr class="afsb-provider-section afsb-openai-section" style="<?php echo 'openai' !== $provider ? 'display:none;' : ''; ?>">
						<th scope="row">
							<label for="afsb-model"><?php esc_html_e( 'OpenAI Model', AFSB_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<select id="afsb-model" name="afsb_model" class="afsb-select">
								<option value="gpt-4o"      <?php selected( $settings['model'], 'gpt-4o' ); ?>>gpt-4o <?php esc_html_e( '(best for images)', AFSB_TEXT_DOMAIN ); ?></option>
								<option value="gpt-4o-mini" <?php selected( $settings['model'], 'gpt-4o-mini' ); ?>>gpt-4o-mini <?php esc_html_e( '(faster, cheaper)', AFSB_TEXT_DOMAIN ); ?></option>
								<option value="custom"      <?php selected( $settings['model'], 'custom' ); ?>><?php esc_html_e( 'Custom…', AFSB_TEXT_DOMAIN ); ?></option>
							</select>
							<input type="text"
							       id="afsb-custom-model"
							       name="afsb_custom_model"
							       class="afsb-input afsb-input--wide"
							       value="<?php echo esc_attr( $settings['custom_model'] ); ?>"
							       placeholder="<?php esc_attr_e( 'e.g. gpt-4-turbo', AFSB_TEXT_DOMAIN ); ?>"
							       style="<?php echo 'custom' !== $settings['model'] ? 'display:none;' : ''; ?>margin-top:8px;">
						</td>
					</tr>

					<!-- ══ Gemini Settings ═══════════════════════════════════════ -->
					<tr class="afsb-provider-section afsb-gemini-section" style="<?php echo 'gemini' !== $provider ? 'display:none;' : ''; ?>">
						<th scope="row" colspan="2">
							<div class="afsb-section-divider">
								<span>✨ <?php esc_html_e( 'Google Gemini Settings', AFSB_TEXT_DOMAIN ); ?></span>
							</div>
						</th>
					</tr>

					<tr class="afsb-provider-section afsb-gemini-section" style="<?php echo 'gemini' !== $provider ? 'display:none;' : ''; ?>">
						<th scope="row">
							<label for="afsb-gemini-api-key"><?php esc_html_e( 'Gemini API Key', AFSB_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="password"
							       id="afsb-gemini-api-key"
							       name="afsb_gemini_api_key"
							       class="afsb-input afsb-input--wide"
							       value="<?php echo esc_attr( $settings['gemini_api_key'] ); ?>"
							       autocomplete="new-password">
							<p class="description">
								<?php esc_html_e( 'Free API key from Google AI Studio (no credit card required).', AFSB_TEXT_DOMAIN ); ?>
								<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Get free Gemini API key →', AFSB_TEXT_DOMAIN ); ?>
								</a>
							</p>
						</td>
					</tr>

					<tr class="afsb-provider-section afsb-gemini-section" style="<?php echo 'gemini' !== $provider ? 'display:none;' : ''; ?>">
						<th scope="row">
							<label for="afsb-gemini-model"><?php esc_html_e( 'Gemini Model', AFSB_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<select id="afsb-gemini-model" name="afsb_gemini_model" class="afsb-select">
								<option value="gemini-2.5-pro"       <?php selected( $settings['gemini_model'], 'gemini-2.5-pro' ); ?>>gemini-2.5-pro <?php esc_html_e( '(Paid · best quality)', AFSB_TEXT_DOMAIN ); ?></option>
								<option value="gemini-2.5-flash"     <?php selected( $settings['gemini_model'], 'gemini-2.5-flash' ); ?>>gemini-2.5-flash <?php esc_html_e( '(Paid · fast & smart)', AFSB_TEXT_DOMAIN ); ?></option>
								<option value="gemini-2.0-flash"     <?php selected( $settings['gemini_model'], 'gemini-2.0-flash' ); ?>>gemini-2.0-flash <?php esc_html_e( '(FREE · recommended)', AFSB_TEXT_DOMAIN ); ?></option>
								<option value="gemini-2.0-flash-lite" <?php selected( $settings['gemini_model'], 'gemini-2.0-flash-lite' ); ?>>gemini-2.0-flash-lite <?php esc_html_e( '(Paid · cheapest)', AFSB_TEXT_DOMAIN ); ?></option>
								<option value="custom"               <?php selected( $settings['gemini_model'], 'custom' ); ?>><?php esc_html_e( 'Custom…', AFSB_TEXT_DOMAIN ); ?></option>
							</select>
							<input type="text"
							       id="afsb-gemini-custom-model"
							       name="afsb_gemini_custom_model"
							       class="afsb-input afsb-input--wide"
							       value="<?php echo esc_attr( $settings['gemini_custom_model'] ); ?>"
							       placeholder="<?php esc_attr_e( 'e.g. gemini-2.5-pro-preview', AFSB_TEXT_DOMAIN ); ?>"
							       style="<?php echo 'custom' !== $settings['gemini_model'] ? 'display:none;' : ''; ?>margin-top:8px;">
							<p class="description">
								<?php esc_html_e( 'With a paid API key: gemini-2.5-pro gives the best layout analysis quality. gemini-2.5-flash is fast and cost-efficient.', AFSB_TEXT_DOMAIN ); ?>
							</p>
						</td>
					</tr>

				<!-- ══ Claude Settings ═══════════════════════════════════════ -->
				<tr class="afsb-provider-section afsb-claude-section" style="<?php echo 'claude' !== $provider ? 'display:none;' : ''; ?>">
					<th scope="row" colspan="2">
						<div class="afsb-section-divider">
							<span>🧠 <?php esc_html_e( 'Claude Settings', AFSB_TEXT_DOMAIN ); ?></span>
						</div>
					</th>
				</tr>

				<tr class="afsb-provider-section afsb-claude-section" style="<?php echo 'claude' !== $provider ? 'display:none;' : ''; ?>">
					<th scope="row">
						<label for="afsb-claude-api-key"><?php esc_html_e( 'Claude API Key', AFSB_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input type="password"
						       id="afsb-claude-api-key"
						       name="afsb_claude_api_key"
						       class="afsb-input afsb-input--wide"
						       value="<?php echo esc_attr( $settings['claude_api_key'] ); ?>"
						       autocomplete="new-password">
						<p class="description">
							<?php esc_html_e( 'Your Anthropic API key. Requires a funded account.', AFSB_TEXT_DOMAIN ); ?>
							<a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Get your API key →', AFSB_TEXT_DOMAIN ); ?>
							</a>
						</p>
					</td>
				</tr>

				<tr class="afsb-provider-section afsb-claude-section" style="<?php echo 'claude' !== $provider ? 'display:none;' : ''; ?>">
					<th scope="row">
						<label for="afsb-claude-model"><?php esc_html_e( 'Claude Model', AFSB_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="afsb-claude-model" name="afsb_claude_model" class="afsb-select">
							<option value="claude-sonnet-4-5"           <?php selected( $settings['claude_model'], 'claude-sonnet-4-5' ); ?>>claude-sonnet-4-5 <?php esc_html_e( '(latest · recommended)', AFSB_TEXT_DOMAIN ); ?></option>
							<option value="claude-3-7-sonnet-20250219"  <?php selected( $settings['claude_model'], 'claude-3-7-sonnet-20250219' ); ?>>claude-3-7-sonnet <?php esc_html_e( '(extended thinking)', AFSB_TEXT_DOMAIN ); ?></option>
							<option value="claude-3-5-sonnet-20241022"  <?php selected( $settings['claude_model'], 'claude-3-5-sonnet-20241022' ); ?>>claude-3-5-sonnet <?php esc_html_e( '(proven stable)', AFSB_TEXT_DOMAIN ); ?></option>
							<option value="claude-3-5-haiku-20241022"   <?php selected( $settings['claude_model'], 'claude-3-5-haiku-20241022' ); ?>>claude-3-5-haiku <?php esc_html_e( '(faster, cheaper)', AFSB_TEXT_DOMAIN ); ?></option>
							<option value="custom"                       <?php selected( $settings['claude_model'], 'custom' ); ?>><?php esc_html_e( 'Custom…', AFSB_TEXT_DOMAIN ); ?></option>
						</select>
						<input type="text"
						       id="afsb-claude-custom-model"
						       name="afsb_claude_custom_model"
						       class="afsb-input afsb-input--wide"
						       value="<?php echo esc_attr( $settings['claude_custom_model'] ); ?>"
						       placeholder="<?php esc_attr_e( 'e.g. claude-opus-4-5', AFSB_TEXT_DOMAIN ); ?>"
						       style="<?php echo 'custom' !== $settings['claude_model'] ? 'display:none;' : ''; ?>margin-top:8px;">
						<p class="description">
							<?php esc_html_e( 'claude-sonnet-4-5 offers the best balance of speed and layout reasoning quality.', AFSB_TEXT_DOMAIN ); ?>
						</p>
					</td>
				</tr>

					<!-- ══ Shared Settings ═══════════════════════════════════════ -->
					<tr>
						<th scope="row" colspan="2">
							<div class="afsb-section-divider">
								<span>⚙️ <?php esc_html_e( 'Shared Settings', AFSB_TEXT_DOMAIN ); ?></span>
							</div>
						</th>
					</tr>

					<tr>
						<th scope="row">
							<label for="afsb-temperature"><?php esc_html_e( 'Temperature', AFSB_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="number"
							       id="afsb-temperature"
							       name="afsb_temperature"
							       class="afsb-input afsb-input--sm"
							       value="<?php echo esc_attr( $settings['temperature'] ); ?>"
							       min="0" max="2" step="0.1">
							<p class="description"><?php esc_html_e( 'Lower = more deterministic output. Default: 0.2. Range: 0–2.', AFSB_TEXT_DOMAIN ); ?></p>
						</td>
					</tr>

				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="afsb-btn afsb-btn--primary">
					💾 <?php esc_html_e( 'Save Settings', AFSB_TEXT_DOMAIN ); ?>
				</button>
			</p>
		</form>
		<?php
	}
}
