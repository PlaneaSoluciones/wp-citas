<?php
/**
 * Plugin Name: WP Citas
 * Plugin URI:  https://github.com/PlaneaSoluciones/wp-citas
 * Description: Crea y gestiona una lista de frases y autores con opciones de visualización en plantilla o widget. Fork de VR-Frases (Vicente Ruiz) mantenido por Planea Soluciones.
 * Author:      Planea Soluciones
 * Author URI:  https://github.com/PlaneaSoluciones
 * Version:     4.1.7
 * Requires at least: 5.5
 * Tested up to: 6.8.2
 * Requires PHP: 7.2
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vr-frases
 * Domain Path: /languages
 *
 * @package     VR_Frases
 * @author      Planea Soluciones
 * @version     4.1.7
 * @license     GPL-2.0+
 * @since       4.1.0
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'VR_FRASES_VERSION', '4.1.6' );
define( 'VR_FRASES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VR_FRASES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VR_FRASES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'VR_FRASES_MIN_WP_VERSION', '5.5' );
define( 'VR_FRASES_MIN_PHP_VERSION', '7.2' );

/**
 * Verifies minimum system requirements.
 *
 * @since 4.0.7
 * @return bool True if requirements are met, false otherwise.
 */
function vr_frases_check_requirements() {
	// Check WordPress version.
	global $wp_version;
	$wp_version_ok = version_compare( $wp_version, VR_FRASES_MIN_WP_VERSION, '>=' );

	// Check PHP version.
	$php_version_ok = version_compare( phpversion(), VR_FRASES_MIN_PHP_VERSION, '>=' );

	if ( ! $wp_version_ok || ! $php_version_ok ) {
		// Display error if requirements are not met.
		add_action( 'admin_notices', 'vr_frases_requirements_error' );
		return false;
	}

	return true;
}

/**
 * Displays error message if requirements are not met.
 *
 * @since 4.0.7
 * @return void
 */
function vr_frases_requirements_error() {
	$message = sprintf(
		/* translators: %1$s: Minimum WordPress version, %2$s: Minimum PHP version */
		esc_html__( 'VR-frases requires WordPress %1$s or higher and PHP %2$s or higher.', 'vr-frases' ),
		VR_FRASES_MIN_WP_VERSION,
		VR_FRASES_MIN_PHP_VERSION
	);

	echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
}

// Check requirements before starting the plugin.
if ( vr_frases_check_requirements() ) {
	// Load the main loader file.
	require_once VR_FRASES_PLUGIN_DIR . 'includes/vr-frases-loader.php';

	// Load and register activation/deactivation hooks.
	require_once VR_FRASES_PLUGIN_DIR . 'includes/vr-frases-activation.php';

	// Register activation/deactivation hooks.
	register_activation_hook( __FILE__, 'vr_frases_activar' );
	register_deactivation_hook( __FILE__, 'vr_frases_desactivar' );

	// Load obsolete files cleanup system.
	require_once VR_FRASES_PLUGIN_DIR . 'includes/vr-frases-obsoletes.php';

	/**
	 * Adds settings link to plugins list.
	 *
	 * @since 4.0.7
	 * @param array $links Current links.
	 * @return array Modified links.
	 */
	function vr_frases_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=vrfr_managesettings' ) ) . '">' . esc_html__( 'Settings', 'vr-frases' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	add_filter( 'plugin_action_links_' . VR_FRASES_PLUGIN_BASENAME, 'vr_frases_plugin_action_links' );

}

// END.
