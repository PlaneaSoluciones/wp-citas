<?php
/**
 * VR-Frases Plugin Component Loader and Initialization System
 *
 * This file serves as the central loading mechanism for all plugin components,
 * managing the initialization order and conditional loading based on context
 * (admin vs frontend). It ensures proper dependency management and optimal
 * resource loading for different plugin areas.
 *
 * Loading architecture:
 * - Core configuration and utility files (always loaded)
 * - Database management and asset enqueuing systems
 * - Main plugin functions and widget support
 * - Route registration for API endpoints
 * - Conditional admin module loading (options, management interfaces)
 * - Conditional frontend module loading (templates, shortcodes)
 * - Settings registration and validation setup
 *
 * Module organization:
 * - Database operations and table management
 * - Asset enqueuing for styles and scripts
 * - Admin interfaces for quotes, authors, classes, themes
 * - Import/export functionality and data management
 * - Frontend template rendering and shortcode support
 * - Menu system and navigation structure
 *
 * @package     VR_Frases
 * @author      Vicente Ruiz Gálvez
 * @version     4.1.0
 * @license     GPL-2.0+
 */

// Prevent direct access to file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Load configuration and utility files ---.
// require_once plugin_dir_path(__FILE__) . 'vr-frases-settings.php'; // Plugin configuration.
require_once plugin_dir_path( __FILE__ ) . 'vr-frases-database.php'; // Database related functions.
require_once plugin_dir_path( __FILE__ ) . 'vr-frases-enqueue.php'; // Script and style enqueuing.
require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-functions.php'; // Main plugin functions.
require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-widgets.php'; // Widgets.
require_once plugin_dir_path( __DIR__ ) . '/includes/vr-register-routes.php'; // Routes (if applicable).

// --- Load conditional modules ---.
if ( is_admin() ) {
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-options.php'; // Plugin options.
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-frases.php'; // Quotes management.
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-autores.php'; // Authors management.
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-temas.php'; // Themes management.
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-import.php'; // Data import.
	require_once plugin_dir_path( __FILE__ ) . 'vr-frases-menu.php'; // Load menu file to hook in.
} else {
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-template.php'; // Template for users.
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-shortcodes.php'; // Shortcodes.
	require_once plugin_dir_path( __DIR__ ) . '/admin/vr-frases-autores.php'; // Authors management.
}

/**
 * Register plugin settings and validation callback.
 *
 * Registers the `vr_frases_options` option and its validation handler.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_admin_init_setup() {
	register_setting( 'vr_frases_options_group', 'vr_frases_options', 'vr_frases_options_validate' ); // Direct registration of settings.
}
add_action( 'admin_init', 'vr_frases_admin_init_setup' );
