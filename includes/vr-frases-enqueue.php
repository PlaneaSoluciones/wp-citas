<?php
/**
 * VR-Frases Asset Management and Enqueuing System
 *
 * This file manages the loading and enqueuing of CSS styles and JavaScript
 * files for both admin and frontend areas of the plugin. It provides smart
 * loading based on context, performance optimization through selective loading,
 * and comprehensive localization support for JavaScript functionality.
 *
 * Asset management features:
 * - Context-aware loading (admin vs frontend selective enqueuing)
 * - Performance optimization with shortcode detection
 * - File modification timestamps for cache busting
 * - CDN integration for external dependencies (jQuery UI)
 * - Comprehensive JavaScript localization and translations
 * - Security nonce generation for AJAX operations
 * - Transient caching for database-driven JavaScript data
 *
 * Loaded assets:
 * - Core plugin stylesheets with overlay support
 * - jQuery UI components and Select2 integration
 * - Main plugin scripts with AJAX functionality
 * - Frontend template scripts with shortcode detection
 * - Localized strings and configuration data
 *
 * @package     VR_Frases
 * @author      Vicente Ruiz Gálvez
 * @version     4.1.0
 * @license     GPL-2.0+
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enqueues the necessary CSS styles for the plugin in the admin area.
 *
 * This function registers and enqueues the necessary CSS files for the plugin,
 * including the plugin's main CSS, jQuery UI, and Select2.
 *
 * @since 4.1.0
 * @param string $hook The current admin page hook suffix.
 * @return void
 */
function vr_frases_enqueue_style( $hook ) {
	// Only load styles on plugin-specific admin pages to avoid conflicts with other themes/plugins.
	if ( strpos( $hook, 'vrfr_' ) === false ) {
		return;
	}

	wp_enqueue_style(
		'vr-frases',
		plugin_dir_url( __DIR__ ) . 'assets/css/vr-frases.css',
		array(),
		filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/vr-frases.css' ) // Use file modification date as version.
	);

	wp_enqueue_style(
		'jquery-ui-css',
		'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', // Load jQuery UI from CDN.
		array(),
		'1.12.1'
	);

	wp_enqueue_style(
		'vr-frases-overlay',
		plugin_dir_url( __DIR__ ) . 'assets/css/vr-frases-overlay.css',
		array(),
		'4.1.0'
	);
}
add_action( 'admin_enqueue_scripts', 'vr_frases_enqueue_style' );

/**
 * Enqueues the necessary JS scripts for the plugin in the admin area.
 *
 * This function registers and enqueues the necessary JavaScript scripts,
 * but only on specific plugin admin pages.
 *
 * @since 4.1.0
 * @param string $hook Identifier for the current admin page.
 * @return void
 */
function vr_frases_enqueue_scripts( $hook ) {
	// Allowed hooks to load plugin scripts and styles in admin (unique and sorted).
	$allowed_hooks = array_unique(
		array(
			'gestionar-frases_page_vrfr_addnewquote',
			'gestionar-frases_page_vrfr_manageautores',
			'gestionar-frases_page_vrfr_manageimport',
			'toplevel_page_vrfr_managefrases',
			'toplevel_page_vrfr_addnewquote',
			'toplevel_page_vrfr_manageimport',
			'toplevel_page_vrfr_managesettings',
			'vr-frases_page_vrfr_manageautores',
			'vr-frases_page_vrfr_manageimport',
		)
	);

	// Allow any page that contains vrfr_ in its hook or listed in allowed hooks.
	if ( strpos( $hook, 'vrfr_' ) === false && ! in_array( $hook, $allowed_hooks, true ) ) {
		return;
	}

	global $wpdb;

	// Enqueue jQuery UI Accordion (if used in admin).
	wp_enqueue_script( 'jquery-ui-accordion' );

	// Main plugin script.
	wp_enqueue_script(
		'vr-frases-scripts',
		plugin_dir_url( __DIR__ ) . 'assets/js/vr-frases-scripts.js',
		array( 'jquery', 'jquery-ui-accordion' ),
		filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/vr-frases-scripts.js' ),
		true
	);

	wp_enqueue_script(
		'vr-frases-overlay',
		plugin_dir_url( __DIR__ ) . 'assets/js/vr-frases-overlay.js',
		array( 'jquery' ),
		filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/vr-frases-overlay.js' ),
		true
	);

	wp_enqueue_script(
		'vr-frases-ajax',
		plugin_dir_url( __DIR__ ) . 'assets/js/vr-frases-ajax.js',
		array( 'jquery', 'vr-frases-overlay' ),
		filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/vr-frases-ajax.js' ),
		true
	);

		$vr_frases_translations = array(
			'ajaxError'             => __( 'AJAX error.', 'vr-frases' ),
			'author'                => __( 'Author', 'vr-frases' ),
			'birthDate'             => __( 'Birth Date', 'vr-frases' ),
			'cancel'                => __( 'Cancel', 'vr-frases' ),
			'confirm'               => __( 'Confirm', 'vr-frases' ),
			'confirmDeleteMultiple' => __( 'Are you sure you want to delete these items?', 'vr-frases' ),
			'confirmDeleteSingle'   => __( 'Are you sure you want to delete this item?', 'vr-frases' ),
			'country'               => __( 'Country', 'vr-frases' ),
			'deathDate'             => __( 'Death Date', 'vr-frases' ),
			'defaultSuccessMessage' => __( 'Item deleted successfully.', 'vr-frases' ),
			'details'               => __( 'Details', 'vr-frases' ),
			'emptyField'            => __( 'Field cannot be empty.', 'vr-frases' ),
			'error'                 => __( 'An error occurred.', 'vr-frases' ),
			'errorLoadingData'      => __( 'Error loading quote data.', 'vr-frases' ),
			'errorSavingData'       => __( 'Error saving quote data.', 'vr-frases' ),
			'invalidData'           => __( 'Missing or invalid data.', 'vr-frases' ),
			'invalidId'             => __( 'The item does not have a valid ID.', 'vr-frases' ),
			'noItemsSelected'       => __( 'No items selected.', 'vr-frases' ),
			'quickEdit'             => __( 'Quick Edit', 'vr-frases' ),
			'save'                  => __( 'Save', 'vr-frases' ),
			'searchWikipedia'       => __( 'Search on Wikipedia', 'vr-frases' ),
			'saving'                => __( 'Saving...', 'vr-frases' ),
			'settingsErrorMessage'  => __( 'Error saving settings.', 'vr-frases' ),
			'settingsSavedMessage'  => __( 'Settings saved.', 'vr-frases' ),
			'viewMoreQuotes'        => __( 'View more quotes from this author...', 'vr-frases' ),
			'noFilesSelected'       => __( 'Please select files to import.', 'vr-frases' ),
			'invalidFileType'       => __( 'Invalid file type for "{filename}". Please upload CSV or TXT files only.', 'vr-frases' ),
			'duplicatesFound'       => __( '{count} duplicates were skipped.', 'vr-frases' ),
			'errorImportingFiles'   => __( 'Error importing files.', 'vr-frases' ),
			'importTimeout'         => __( 'Import timeout. Please try with smaller files.', 'vr-frases' ),
			'hide'                  => __( 'Hide', 'vr-frases' ),
			'duplicatesFoundTitle'  => __( 'Duplicate records found:', 'vr-frases' ),
			'file'                  => __( 'File', 'vr-frases' ),
			'line'                  => __( 'Line', 'vr-frases' ),
			'alreadyInDatabase'     => __( 'Already in database', 'vr-frases' ),
			'alreadyInImport'       => __( 'Already in import list', 'vr-frases' ),
			'saveAll'               => __( 'Save all', 'vr-frases' ),
			'savingAll'             => __( 'Saving all...', 'vr-frases' ),
			// translators: %d: number of quotes saved successfully.
			'saveAllSuccess'        => __( '%d quotes saved successfully.', 'vr-frases' ),
			// translators: %d: number of quotes that could not be saved.
			'saveAllErrors'         => __( '%d quotes could not be saved (duplicates or errors).', 'vr-frases' ),
			'duplicateNotice'       => __( 'These quotes were not imported because they already exist.', 'vr-frases' ),
			'pageWillReload'        => __( 'Page will reload automatically when you close the duplicates panel.', 'vr-frases' ),
			'gdpr_show'             => __( 'Show details', 'vr-frases' ),
			'gdpr_hide'             => __( 'Hide details', 'vr-frases' ),
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'nonceAutores'          => wp_create_nonce( 'vr_nonce_autores' ),
			'nonceFrases'           => wp_create_nonce( 'vr_nonce_frases' ),
			'nonceImport'           => wp_create_nonce( 'vr_nonce_import' ),
			'wikilang'              => get_option( 'wiki_lang', 'es' ),
		);

		// Translations and data for JS.
		wp_localize_script(
			'vr-frases-scripts',
			'vrFrasesTranslations',
			$vr_frases_translations
		);

	wp_localize_script(
		'vr-frases-ajax',
		'vrFrasesAjax',
		$vr_frases_translations
	);

	wp_localize_script(
		'vr-frases-overlay',
		'vrFrasesOverlay',
		array(
			'updatingText' => esc_html__( 'Updating results...', 'vr-frases' ),
			'deleting'     => esc_html__( 'Deleting...', 'vr-frases' ),
			'saving'       => esc_html__( 'Saving...', 'vr-frases' ),
			'loading'      => esc_html__( 'Loading data...', 'vr-frases' ),
		)
	);
}

add_action( 'admin_enqueue_scripts', 'vr_frases_enqueue_scripts' );

/**
 * Enqueues scripts and styles for the frontend when the [vrfrases] shortcode is used.
 *
 * This function detects the presence of the shortcode in the page content
 * and loads the necessary resources only when they are required.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_enqueue_template_scripts() {
	global $post;

	// Check if the page content includes the [vrfrases] shortcode.
	if ( isset( $post ) && has_shortcode( $post->post_content, 'vrfrases' ) ) {
		// Get the configured language.
		$lang = get_option( 'vr_frases_options' )['wiki_lang'] ?? 'en'; // Use 'en' as default if not configured.

		// Enqueue styles.
		wp_enqueue_style(
			'vr-frases-template',
			plugin_dir_url( __DIR__ ) . 'assets/css/vr-frases.css',
			array(),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/vr-frases.css' )
		);

		wp_enqueue_style(
			'vr-frases-overlay-frontend',
			plugin_dir_url( __DIR__ ) . 'assets/css/vr-frases-overlay.css',
			array(),
			'4.1.0'
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'vr-frases-overlay-frontend',
			plugin_dir_url( __DIR__ ) . 'assets/js/vr-frases-overlay.js',
			array( 'jquery' ),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/vr-frases-overlay.js' ),
			true
		);

		wp_enqueue_script(
			'vr-frases-template-scripts',
			plugin_dir_url( __DIR__ ) . 'assets/js/vr-frases-scripts.js',
			array( 'jquery', 'vr-frases-overlay-frontend' ), // jQuery dependency.
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/vr-frases-scripts.js' ),
			true
		);

		// Localize data for vr-frases-template-scripts.js.
		wp_localize_script(
			'vr-frases-template-scripts',
			'vrFrasesTranslations',
			array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonceAutores' => wp_create_nonce( 'vr_nonce_autores' ), // Nonce for security.
			)
		);

		wp_localize_script(
			'vr-frases-overlay-frontend',
			'vrFrasesOverlay',
			array(
				'updatingText' => esc_html__( 'Updating results...', 'vr-frases' ),
				'deleting'     => esc_html__( 'Deleting...', 'vr-frases' ),
				'saving'       => esc_html__( 'Saving...', 'vr-frases' ),
				'loading'      => esc_html__( 'Loading data...', 'vr-frases' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'vr_frases_enqueue_template_scripts' );

/**
 * Note about nonce usage:
 *
 * The wp_create_nonce calls are made within hooks (admin_enqueue_scripts, wp_enqueue_scripts)
 * that normally run after pluggable.php is loaded, which makes the
 * nonce functions available and safe to use in this context.
 */
