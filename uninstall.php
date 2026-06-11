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

	// Common options to delete (even on temporary uninstall).
	$options_to_delete = array(
		'vr_frases_version',          // Plugin version.
		'widget_vr_frases_widget',    // Widget options.
	);

	// Delete common options.
	foreach ( $options_to_delete as $option_name ) {
		delete_option( $option_name );
	}

	// Process confirmation if in admin interface.
	if ( is_admin() ) {
		// Create a nonce for security.
		$nonce = wp_create_nonce( 'vr_frases_uninstall_nonce' );

		// Show confirmation dialog if not already confirmed.
		if ( ! isset( $_POST['vr_confirm_uninstall'] ) ) {
			echo '<div class="wrap">';
			echo '<h2>' . esc_html__( 'Confirm Full Uninstall', 'vr-frases' ) . '</h2>';
			echo '<p>' . esc_html__( 'Warning: You are about to perform a FULL UNINSTALL. All plugin data will be permanently deleted. This includes all quotes, authors, classes, and themes.', 'vr-frases' ) . '</p>';
			echo '<p><strong>' . esc_html__( 'This action cannot be undone.', 'vr-frases' ) . '</strong></p>';
			echo '<p>' . esc_html__( 'If you want to preserve your data for future use, click "Keep Data" to perform a temporary uninstall (which preserves database tables).', 'vr-frases' ) . '</p>';
			echo '<form method="post">';
			echo '<input type="hidden" name="vr_uninstall_nonce" value="' . esc_attr( $nonce ) . '" />';
			echo '<button type="submit" name="vr_confirm_uninstall" value="yes" class="button button-primary">' . esc_html__( 'Yes, Delete Everything', 'vr-frases' ) . '</button> ';
			echo '<button type="submit" name="vr_confirm_uninstall" value="no" class="button">' . esc_html__( 'Keep Data', 'vr-frases' ) . '</button>';
			echo '</form></div>';
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['vr_uninstall_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vr_uninstall_nonce'] ) ), 'vr_frases_uninstall_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'vr-frases' ) );
		}

		// If user cancels, disable the option and exit.
		if ( isset( $_POST['vr_confirm_uninstall'] ) && 'no' === $_POST['vr_confirm_uninstall'] ) {
			$plugin_options['allow_full_uninstall'] = false;
			update_option( 'vr_frases_options', $plugin_options );
			return;
		}
	}

	// Clean any transients that might have been created.
	delete_transient( 'vr_frases_cache' );

	// If full uninstall is not allowed, we're done (temporary uninstall).
	if ( ! $proceed_with_full_uninstall ) {
		// For temporary uninstall, we exit here.
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
