<?php
/**
 * VR-Frases Plugin Activation and Deactivation Handler
 *
 * This file manages all aspects of plugin lifecycle including activation,
 * deactivation, updates, and language loading. It ensures proper database
 * initialization, version control, and clean plugin state management.
 *
 * Key functionalities:
 * - Plugin activation with database creation and initial data insertion
 * - Deactivation cleanup and temporary data removal
 * - Version-specific upgrade procedures and migrations
 * - Multilingual support with dynamic textdomain loading
 * - Legacy directory cleanup for plugin updates
 * - Update detection and automatic upgrade execution
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
 * Plugin activation handler
 *
 * Creates database tables, inserts initial data, and performs upgrades if needed.
 * This function is called when the plugin is activated.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_activar() {
	global $wpdb;

	// Create or update database tables.
	if ( function_exists( 'vr_frases_new_first' ) ) {
		vr_frases_new_first();
	}

	// Insert initial data if this is a new installation.
	if ( function_exists( 'vr_frases_insert_initial_data' ) ) {
		vr_frases_insert_initial_data();
	}

	// Verify and execute updates if needed.
	vr_frases_check_for_updates();

	// Add activation timestamp.
	update_option( 'vr_frases_activation_time', time() );
}
// Activation hook is registered from the main plugin file.

/**
 * Plugin deactivation handler
 *
 * Cleans up temporary data when the plugin is deactivated.
 * Does NOT remove permanent data like database tables or settings.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_desactivar() {

	// Log deactivation time.
	update_option( 'vr_frases_deactivation_time', time() );

	// Unschedule any cron jobs if they exist.
	if ( function_exists( 'wp_next_scheduled' ) ) {
		$timestamp = wp_next_scheduled( 'vr_frases_scheduled_event' );
		if ( $timestamp && function_exists( 'wp_unschedule_event' ) ) {
			wp_unschedule_event( $timestamp, 'vr_frases_scheduled_event' );
		}
	}
}
// Deactivation hook is registered from the main plugin file.

/**
 * Loads the appropriate language files for the plugin.
 *
 * This function handles loading translation files based on the plugin's language setting
 * or WordPress's current locale. It checks the plugin options first, and if no specific
 * language is set (empty string = default), it uses WordPress's built-in textdomain loading.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_load_textdomain() {
	// Prevent multiple loads.
	static $textdomain_loaded = false;
	if ( $textdomain_loaded ) {
		return;
	}

	// Get the selected language from plugin options.
	$options           = get_option( 'vr_frases_options' );
	$selected_language = isset( $options['language'] ) ? $options['language'] : '';

	// Get WordPress locale for comparison.
	$wp_locale = get_locale();

	// Build the correct plugin languages directory path.
	$plugin_languages_path = VR_FRASES_PLUGIN_DIR . 'languages/';
	$languages_dir         = dirname( VR_FRASES_PLUGIN_BASENAME ) . '/languages/';

	// Determine which locale to load.
	$target_locale = empty( $selected_language ) ? $wp_locale : $selected_language;
	$mo_file       = $plugin_languages_path . 'vr-frases_' . $target_locale . '.mo';

	// Force clear any cached translations first.
	global $l10n;
	if ( isset( $l10n['vr-frases'] ) ) {
		unset( $l10n['vr-frases'] );
	}

	// Try to load the specific .mo file first.
	if ( file_exists( $mo_file ) ) {
		$result = load_textdomain( 'vr-frases', $mo_file );

		if ( $result ) {
			$textdomain_loaded = true;
		}
	} else {
		// Fallback to standard WordPress textdomain loading.
		$result = load_plugin_textdomain(
			'vr-frases',
			false,
			$languages_dir
		);

		if ( $result ) {
			$textdomain_loaded = true;
		}
	}
}
add_action( 'plugins_loaded', 'vr_frases_load_textdomain', 1 );

/**
 * Checks for plugin updates and executes necessary upgrade procedures.
 *
 * This function compares the current installed version with the latest version
 * and runs the upgrade processes if needed.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_check_for_updates() {
	// Get current installed version.
	$current_version = get_option( 'vr_frases_version', '1.0' );

	// Get new plugin version from constants or default.
	$plugin_version = defined( 'VR_FRASES_VERSION' ) ? VR_FRASES_VERSION : '4.1.0';

	// Only run updates if version has changed.
	if ( version_compare( $current_version, $plugin_version, '<' ) ) {
		// Set upgrade flag but don't update version yet - let vr_frases_maybe_run_upgrades() handle it.
		$upgrade_data = array(
			'from_version' => $current_version,
			'to_version'   => $plugin_version,
			'timestamp'    => time(),
		);
		update_option( 'vr_frases_needs_upgrade', $upgrade_data );

		// Log upgrade initiation.
		update_option( 'vr_frases_last_upgrade_attempt', time() );

		// Run version-specific upgrades if needed (non-DB changes only).
		vr_frases_run_specific_updates( $current_version, $plugin_version );
	}
}
add_action( 'init', 'vr_frases_check_for_updates' );

/**
 * Runs version-specific upgrade procedures.
 *
 * This function can handle specific upgrade tasks depending on
 * which version the user is upgrading from.
 *
 * @since 4.1.0
 * @param string $old_version The previously installed version.
 * @param string $new_version The new version being installed.
 * @return void
 */
function vr_frases_run_specific_updates( $old_version, $new_version ) {
	// From versions before 4.0 to 4.x.
	// No specific steps required for upgrades from pre-4.0 in this release.

	// From 4.0.x to 4.1.0.
	if ( version_compare( $old_version, '4.1.0', '<' ) && version_compare( $new_version, '4.1.0', '>=' ) ) {
		// Update any settings or structure for 4.1.0.
		$options = get_option( 'vr_frases_options', array() );

		// Ensure the allow_full_uninstall option exists.
		if ( ! isset( $options['allow_full_uninstall'] ) ) {
			$options['allow_full_uninstall'] = false;
			update_option( 'vr_frases_options', $options );
		}

		// Remove obsolete folders: css, scripts, images.
		vr_frases_remove_obsolete_dirs();
	}
}

/**
 * Recursively delete a directory and its contents.
 *
 * Removes all files and subdirectories inside the given directory and
 * then removes the directory itself.
 *
 * @since 4.1.0
 * @param string $dir Absolute path to the directory to delete.
 * @return void
 */
function vr_frases_delete_directory( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	// Prefer WP_Filesystem when available to handle file operations.
	if ( defined( 'ABSPATH' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( function_exists( 'WP_Filesystem' ) ) {
			WP_Filesystem();
			global $wp_filesystem;
			if ( isset( $wp_filesystem ) && method_exists( $wp_filesystem, 'delete' ) ) {
				// Delete directory recursively.
				$wp_filesystem->delete( $dir, true );
				return;
			}
		}
	}

	// Fallback: manual recursive deletion when WP_Filesystem is not available.
	$files = array_diff( scandir( $dir ), array( '.', '..' ) );
	foreach ( $files as $file ) {
		$path = $dir . '/' . $file;
		if ( is_dir( $path ) ) {
			vr_frases_delete_directory( $path );
		} elseif ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $path );
		} elseif ( file_exists( $path ) ) {
			if ( function_exists( 'WP_Filesystem' ) && isset( $wp_filesystem ) && method_exists( $wp_filesystem, 'delete' ) ) {
				$deleted = $wp_filesystem->delete( $path, false );
			} elseif ( function_exists( 'wp_delete_file' ) ) {
				$deleted = wp_delete_file( $path );
			} else {
				$deleted = call_user_func( 'unlink', $path );
			}
			// If deletion failed, proceed silently (no debug output in production).
		}
	}
	if ( is_dir( $dir ) ) {
		if ( defined( 'ABSPATH' ) && isset( $wp_filesystem ) && method_exists( $wp_filesystem, 'delete' ) ) {
			$removed = $wp_filesystem->delete( $dir, true );
		} else {
			$removed = call_user_func( 'rmdir', $dir );
		}
		// If directory removal failed, proceed silently (no debug output in production).
	}
}

/**
 * Remove obsolete plugin folders after update to 4.1.0.
 *
 * This will delete folders such as `css`, `scripts` and `images` if
 * they exist in the plugin's base directory.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_remove_obsolete_dirs() {
	$base          = plugin_dir_path( dirname( __DIR__ ) );
	$obsolete_dirs = array(
		$base . 'css',
		$base . 'scripts',
		$base . 'images',
	);
	foreach ( $obsolete_dirs as $dir ) {
		if ( is_dir( $dir ) ) {
			vr_frases_delete_directory( $dir );
		}
	}
}
