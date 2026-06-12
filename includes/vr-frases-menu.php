<?php
/**
 * VR-Frases WordPress Admin Menu System and Navigation
 *
 * This file manages the complete WordPress admin menu structure for the
 * VR-Frases plugin, creating an organized navigation system that provides
 * easy access to all plugin functionality. It handles menu creation,
 * submenu organization, and plugin action links integration.
 *
 * Menu structure and organization:
 * - Main menu entry with quote management icon and primary access
 * - Submenu for quote management (listing, editing, bulk operations)
 * - Add new quote interface with form validation and AJAX support
 * - Author management for biographical data and Wikipedia integration
 * - Class management for quote categorization and organization
 * - Theme management with slug support and taxonomic relationships
 * - Import/export functionality for bulk data operations
 * - Settings management for plugin configuration and customization
 *
 * Additional features:
 * - Plugin action links on WordPress plugins page
 * - Direct settings access from plugin list
 * - Proper capability checks and security validation
 * - Localized menu titles and descriptions
 *
 * @package     VR_Frases
 * @author      Vicente Ruiz Gálvez
 * @version     4.1.0
 * @license     GPL-2.0+
 */

// Prevent direct access to file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Creates the menus and submenus in the admin panel for the VR Frases plugin.
 *
 * Generates the complete navigation structure for the plugin in WordPress.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_add_menu() {
	// Main menu for quote management.
	add_menu_page(
		esc_html__( 'WP Citas', 'vr-frases' ), // Main menu name.
		esc_html__( 'WP Citas', 'vr-frases' ),
		'manage_options',
		'vrfr_managefrases',
		'vr_frases_manage_frases',
		'dashicons-format-quote'
	);

	// First submenu: Manage quotes (to prevent duplicate menu name).
	add_submenu_page(
		'vrfr_managefrases',
		esc_html__( 'Manage Quotes', 'vr-frases' ),
		esc_html__( 'Manage Quotes', 'vr-frases' ),
		'manage_options',
		'vrfr_managefrases',
		'vr_frases_manage_frases'
	);

	// Submenus for adding quotes, managing classes, themes, and settings.
	add_submenu_page(
		'vrfr_managefrases',
		esc_html__( 'Add New Quote', 'vr-frases' ),
		esc_html__( 'Add New Quote', 'vr-frases' ),
		'manage_options',
		'vrfr_addnewquote',
		'vr_frases_addnew_frase_form'
	);

	add_submenu_page(
		'vrfr_managefrases',
		esc_html__( 'Manage Authors', 'vr-frases' ),
		esc_html__( 'Manage Authors', 'vr-frases' ),
		'manage_options',
		'vrfr_manageautores',
		'vr_frases_manage_autores'
	);

	add_submenu_page(
		'vrfr_managefrases',
		esc_html__( 'Manage Classes', 'vr-frases' ),
		esc_html__( 'Manage Classes', 'vr-frases' ),
		'manage_options',
		'vrfr_manageclases',
		'vr_frases_manage_clases'
	);

	add_submenu_page(
		'vrfr_managefrases',
		esc_html__( 'Manage Themes', 'vr-frases' ),
		esc_html__( 'Manage Themes', 'vr-frases' ),
		'manage_options',
		'vrfr_managetemas',
		'vr_frases_manage_temas'
	);

	add_submenu_page(
		'vrfr_managefrases',
		esc_html__( 'Manage Import/Export', 'vr-frases' ),
		esc_html__( 'Manage Import/Export', 'vr-frases' ),
		'manage_options',
		'vrfr_manageimport',
		'vr_frases_manage_import'
	);

	add_submenu_page(
		'vrfr_managefrases',
		esc_html__( 'Manage Settings', 'vr-frases' ), // Page title.
		esc_html__( 'Manage Settings', 'vr-frases' ), // Menu title.
		'manage_options',
		'vrfr_managesettings', // Slug.
		'vr_frases_manage_settings' // Callback.
	);
}
add_action( 'admin_menu', 'vr_frases_add_menu' );

/**
 * Adds configuration links on the plugins page.
 *
 * Adds a direct link to the plugin settings in the plugins list.
 *
 * @param array  $links Current plugin links.
 * @param string $file  Current plugin file name.
 * @return array Modified links.
 */
function vr_frases_action_links( $links, $file ) {
	static $vr_frases;

	if ( ! $vr_frases ) {
		$vr_frases = plugin_basename( dirname( __DIR__ ) . '/vr-frases.php' );
	}
	if ( $file === $vr_frases ) {
		// Remove any link that contains 'configurar' or 'settings' (case-insensitive).
		$links = array_filter(
			$links,
			function ( $link ) {
				return stripos( $link, 'configurar' ) === false && stripos( $link, 'settings' ) === false;
			}
		);
		// Add custom link.
		$settings_link = '<a href="admin.php?page=vrfr_managesettings">' . esc_html__( 'Manage Settings', 'vr-frases' ) . '</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;
}
add_filter( 'plugin_action_links', 'vr_frases_action_links', 10, 2 );
