<?php
/**
 * VR-frases Uninstall
 *
 * Uninstalling VR-frases deletes tables and options if enabled in plugin settings.
 *
 * @package     VR_Frases
 * @author      Vicente Ruiz Gálvez
 * @version     4.1.0
 * @license     GPL-2.0+
 * @since       4.1.0
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Performs the uninstallation process for VR-frases plugin.
 *
 * Handles complete removal of plugin data including tables and options.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_perform_uninstall() {
	global $wpdb;

	// Get plugin options.
	$plugin_options = get_option( 'vr_frases_options', array() );

	// Determine if full uninstall is allowed.
	// Default is false - we only delete database tables if explicitly enabled.
	$proceed_with_full_uninstall = false;

	if ( is_array( $plugin_options ) && isset( $plugin_options['allow_full_uninstall'] ) ) {
		// Explicitly check for true (boolean).
		if ( true === $plugin_options['allow_full_uninstall'] ) {
			$proceed_with_full_uninstall = true;
		}
	}

	// Clean any transients that might have been created.
	delete_transient( 'vr_frases_cache' );

	// Only continue if the user explicitly enabled full uninstall in plugin settings.
	if ( ! $proceed_with_full_uninstall ) {
		return;
	}

	// FULL UNINSTALL ONLY BEYOND THIS POINT.

	// Define table names with WordPress prefix.
	$tables_to_drop = array(
		$wpdb->prefix . 'vr_fr_frases',   // Quotes table.
		$wpdb->prefix . 'vr_fr_clases',    // Classes table.
		$wpdb->prefix . 'vr_fr_temas',     // Themes table.
		$wpdb->prefix . 'vr_fr_taxos',     // Taxonomies table.
		$wpdb->prefix . 'vr_fr_import',    // Import table.
		$wpdb->prefix . 'vr_fr_autores',   // Authors table.
	);

	// Delete the main plugin options (only on full uninstall).
	delete_option( 'vr_frases_options' );

	// Drop database tables (only on full uninstall).
	foreach ( $tables_to_drop as $table_name ) {
		// Use esc_sql for table name since it's already prefixed and safe.
		$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $table_name ) );
	}

	// Full uninstall completed.
}

// Execute the uninstallation function.
vr_frases_perform_uninstall();
