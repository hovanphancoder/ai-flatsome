/**
 * AI Flatsome Shortcode Builder – Admin JavaScript
 *
 * Handles:
 *  - Mode toggle (image / URL)
 *  - Image drag & drop + preview
 *  - Generate AJAX
 *  - Copy shortcode / CSS to clipboard
 *  - Create draft page AJAX
 *  - Settings: custom model field toggle
 */
/* global afsbData, jQuery */
( function ( $ ) {
	'use strict';

	// -----------------------------------------------------------------------
	// Cache DOM
	// -----------------------------------------------------------------------
	var $modeImage     = $( '#afsb-mode-image' );
	var $modeUrl       = $( '#afsb-mode-url' );
	var $imageField    = $( '#afsb-image-field' );
	var $urlField      = $( '#afsb-url-field' );
	var $urlInput      = $( '#afsb-url-input' );
	var $imageInput    = $( '#afsb-image-input' );
	var $imagePreview  = $( '#afsb-image-preview' );
	var $dropzone      = $( '#afsb-dropzone' );
	var $dropHolder    = $( '#afsb-dropzone-placeholder' );
	var $btnGenerate   = $( '#afsb-btn-generate' );
	var $loading       = $( '#afsb-loading' );
	var $error         = $( '#afsb-error' );
	var $outJson       = $( '#afsb-output-json' );
	var $outShortcode  = $( '#afsb-output-shortcode' );
	var $outCss        = $( '#afsb-output-css' );
	var $outJs         = $( '#afsb-output-js' );
	var $jsBlock       = $( '#afsb-js-block' );
	var $btnCopySC     = $( '#afsb-btn-copy-shortcode' );
	var $btnCopyCSS    = $( '#afsb-btn-copy-css' );
	var $pageTitle     = $( '#afsb-page-title' );
	var $btnCreatePage = $( '#afsb-btn-create-page' );
	var $pageResult    = $( '#afsb-page-result' );
	// Settings tab.
	var $modelSelect   = $( '#afsb-model' );
	var $customModel   = $( '#afsb-custom-model' );

	// Store the current image File object.
	var currentImageFile = null;

	// -----------------------------------------------------------------------
	// Mode toggle
	// -----------------------------------------------------------------------
	function updateMode() {
		var isImage = $modeImage.is( ':checked' );
		$imageField.toggle( isImage );
		$urlField.toggle( ! isImage );
	}

	$modeImage.on( 'change', updateMode );
	$modeUrl.on( 'change', updateMode );
	updateMode(); // initial state

	// -----------------------------------------------------------------------
	// Image dropzone / file picker
	// -----------------------------------------------------------------------
	$imageInput.on( 'change', function () {
		handleFileSelect( this.files[0] );
	} );

	$dropzone.on( 'dragover dragenter', function ( e ) {
		e.preventDefault();
		$dropzone.addClass( 'afsb-dragover' );
	} );

	$dropzone.on( 'dragleave drop', function () {
		$dropzone.removeClass( 'afsb-dragover' );
	} );

	$dropzone.on( 'drop', function ( e ) {
		e.preventDefault();
		var file = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files[0];
		if ( file ) {
			handleFileSelect( file );
		}
	} );

	/**
	 * Preview the selected image file.
	 *
	 * @param {File} file
	 */
	function handleFileSelect( file ) {
		if ( ! file || ! file.type.match( /^image\// ) ) {
			showError( 'Please select a valid image file (JPEG, PNG, GIF, WebP).' );
			return;
		}
		if ( file.size > 8 * 1024 * 1024 ) {
			showError( 'Image must be smaller than 8 MB.' );
			return;
		}

		currentImageFile = file;
		var reader = new FileReader();
		reader.onload = function ( ev ) {
			$imagePreview.attr( 'src', ev.target.result ).show();
			$dropHolder.hide();
		};
		reader.readAsDataURL( file );
		hideError();
	}

	// -----------------------------------------------------------------------
	// Generate
	// -----------------------------------------------------------------------
	$btnGenerate.on( 'click', function () {
		var nonce = $btnGenerate.data( 'nonce' );
		var mode  = $modeImage.is( ':checked' ) ? 'image' : 'url';

		hideError();
		clearOutputs();

		// Validate inputs.
		if ( 'image' === mode && ! currentImageFile ) {
			showError( 'Please upload an image first.' );
			return;
		}
		if ( 'url' === mode && ! $.trim( $urlInput.val() ) ) {
			showError( 'Please enter a URL.' );
			return;
		}

		// Build FormData.
		var formData = new FormData();
		formData.append( 'action', 'afsb_generate' );
		formData.append( 'nonce', nonce );
		formData.append( 'mode', mode );

		if ( 'image' === mode ) {
			formData.append( 'image', currentImageFile );
		} else {
			formData.append( 'url', $.trim( $urlInput.val() ) );
		}

		setLoading( true );

		$.ajax( {
			url:         afsbData.ajaxUrl,
			type:        'POST',
			data:        formData,
			processData: false,
			contentType: false,
			timeout:     90000,
			success: function ( response ) {
				setLoading( false );
				if ( response.success ) {
					populateOutputs( response.data );
				} else {
					showError( ( response.data && response.data.message ) ? response.data.message : 'Unknown error.' );
				}
			},
			error: function ( xhr ) {
				setLoading( false );
				var msg = 'AJAX request failed.';
				if ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
					msg = xhr.responseJSON.data.message;
				}
				showError( msg );
			}
		} );
	} );

	/**
	 * Populate output textareas with generated data.
	 *
	 * @param {Object} data
	 */
	function populateOutputs( data ) {
		$outJson.val( data.json || '' );
		$outShortcode.val( data.shortcode || '' );
		$outCss.val( data.css || '' );

		// Optional JS.
		if ( data.js ) {
			$outJs.val( data.js );
			$jsBlock.show();
		} else {
			$jsBlock.hide();
		}

		// Enable action buttons.
		if ( data.shortcode ) {
			$btnCopySC.prop( 'disabled', false );
			$btnCreatePage.prop( 'disabled', false );
		}
		if ( data.css ) {
			$btnCopyCSS.prop( 'disabled', false );
		}
	}

	/**
	 * Clear all output textareas and disable buttons.
	 */
	function clearOutputs() {
		$outJson.val( '' );
		$outShortcode.val( '' );
		$outCss.val( '' );
		$outJs.val( '' );
		$jsBlock.hide();
		$btnCopySC.prop( 'disabled', true );
		$btnCopyCSS.prop( 'disabled', true );
		$btnCreatePage.prop( 'disabled', true );
		$pageResult.hide().text( '' ).removeClass( 'afsb-notice--success afsb-notice--error' );
	}

	// -----------------------------------------------------------------------
	// Copy to clipboard
	// -----------------------------------------------------------------------
	$btnCopySC.on( 'click', function () {
		copyToClipboard( $outShortcode.val(), $btnCopySC );
	} );

	$btnCopyCSS.on( 'click', function () {
		copyToClipboard( $outCss.val(), $btnCopyCSS );
	} );

	/**
	 * Copy text to clipboard and show feedback on the button.
	 *
	 * @param {string} text
	 * @param {jQuery} $btn
	 */
	function copyToClipboard( text, $btn ) {
		if ( ! text ) return;

		// Modern API.
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function () {
				flashCopied( $btn );
			} );
		} else {
			// Fallback.
			var $tmp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
			document.execCommand( 'copy' );
			$tmp.remove();
			flashCopied( $btn );
		}
	}

	/**
	 * Show "Copied!" text on a button then revert.
	 *
	 * @param {jQuery} $btn
	 */
	function flashCopied( $btn ) {
		var original = $btn.html();
		$btn.html( '✅ ' + afsbData.i18n.copied );
		setTimeout( function () {
			$btn.html( original );
		}, 1800 );
	}

	// -----------------------------------------------------------------------
	// Create Draft Page
	// -----------------------------------------------------------------------
	$btnCreatePage.on( 'click', function () {
		var nonce     = $btnCreatePage.data( 'nonce' );
		var title     = $.trim( $pageTitle.val() );
		var shortcode = $outShortcode.val();
		var css       = $outCss.val();

		if ( ! shortcode ) {
			showPageResult( 'error', 'Shortcode is empty. Please generate a layout first.' );
			return;
		}

		$btnCreatePage.prop( 'disabled', true ).text( '⏳ Creating…' );

		$.post( afsbData.ajaxUrl, {
			action:    'afsb_create_page',
			nonce:     nonce,
			title:     title,
			shortcode: shortcode,
			css:       css
		}, function ( response ) {
			$btnCreatePage.prop( 'disabled', false ).html( '🚀 Create Draft Page' );
			if ( response.success ) {
				var editUrl = response.data.edit_url;
				showPageResult(
					'success',
					afsbData.i18n.pageCreated + ' <a href="' + editUrl + '" target="_blank">' + editUrl + '</a>'
				);
			} else {
				var msg = ( response.data && response.data.message ) ? response.data.message : 'Unknown error.';
				showPageResult( 'error', msg );
			}
		} ).fail( function () {
			$btnCreatePage.prop( 'disabled', false ).html( '🚀 Create Draft Page' );
			showPageResult( 'error', 'Request failed. Please try again.' );
		} );
	} );

	/**
	 * Show result message in the page creation area.
	 *
	 * @param {string} type    'success' or 'error'
	 * @param {string} message HTML message.
	 */
	function showPageResult( type, message ) {
		$pageResult
			.removeClass( 'afsb-notice--success afsb-notice--error' )
			.addClass( 'afsb-notice--' + type )
			.html( message )
			.show();
	}

	// -----------------------------------------------------------------------
	// Settings: toggle custom model inputs
	// -----------------------------------------------------------------------

	// OpenAI custom model.
	if ( $modelSelect.length ) {
		$modelSelect.on( 'change', function () {
			$customModel.toggle( 'custom' === $( this ).val() );
		} );
	}

	// Gemini custom model.
	var $geminiModelSelect = $( '#afsb-gemini-model' );
	var $geminiCustomModel = $( '#afsb-gemini-custom-model' );

	if ( $geminiModelSelect.length ) {
		$geminiModelSelect.on( 'change', function () {
			$geminiCustomModel.toggle( 'custom' === $( this ).val() );
		} );
	}

	// Claude custom model.
	var $claudeModelSelect = $( '#afsb-claude-model' );
	var $claudeCustomModel = $( '#afsb-claude-custom-model' );

	if ( $claudeModelSelect.length ) {
		$claudeModelSelect.on( 'change', function () {
			$claudeCustomModel.toggle( 'custom' === $( this ).val() );
		} );
	}

	// -----------------------------------------------------------------------
	// Settings: provider card switcher (openai | gemini | claude)
	// -----------------------------------------------------------------------
	var $providerCards   = $( '.afsb-provider-card' );
	var $openaiSections  = $( '.afsb-openai-section' );
	var $geminiSections  = $( '.afsb-gemini-section' );
	var $claudeSections  = $( '.afsb-claude-section' );

	$providerCards.on( 'click', function () {
		var $card    = $( this );
		var provider = $card.find( 'input[type="radio"]' ).val();

		// Toggle active styling.
		$providerCards.removeClass( 'afsb-provider-card--active' );
		$card.addClass( 'afsb-provider-card--active' );

		// Hide all provider sections first.
		$openaiSections.hide();
		$geminiSections.hide();
		$claudeSections.hide();

		// Show only selected provider's rows.
		if ( 'gemini' === provider ) {
			$geminiSections.show();
		} else if ( 'claude' === provider ) {
			$claudeSections.show();
		} else {
			$openaiSections.show();
		}
	} );

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------
	function setLoading( state ) {
		$loading.toggle( state );
		$btnGenerate.prop( 'disabled', state );
	}

	function showError( msg ) {
		$error.text( msg ).show();
	}

	function hideError() {
		$error.hide().text( '' );
	}

} )( jQuery );
