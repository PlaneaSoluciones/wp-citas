<?php
/**
 * VR-Frases Widget Management and Dashboard Integration
 *
 * Provides sidebar widgets and dashboard widgets for displaying quotes
 * and plugin statistics in the WordPress admin.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Verify WP_Widget class availability.
// Include the Widget OO class.
require_once __DIR__ . '/class-vr-frases-widget.php';

/**
 * Dashboard widget with statistics and management links.
 *
 * Displays plugin statistics, management page links, and a sample quote
 * output for administrators in the WordPress dashboard.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_dash_widget() {
	$frases  = vr_frases_total_frases();
	$autores = vr_frases_total_autores();
	$temas   = vr_frases_total_temas();
	$random  = vr_frases_random_frase(); // Allow HTML in the random phrase.
	$version = get_option( 'vr_frases_version' );

	echo '<p><b>' . esc_html__( 'Registered data totals:', 'vr-frases' ) . '</b></p>'; // Escape.
	echo '<ul><li>' . esc_html__( 'At this moment your database contains ', 'vr-frases' ) . '<a href="' . esc_url( admin_url( 'admin.php?page=vrfr_managefrases' ) ) . '">' . esc_html( $frases ) . ' ' . esc_html__( 'Quotes', 'vr-frases' ) . '</a>' . esc_html__( ' from ', 'vr-frases' ) . '<a href="' . esc_url( admin_url( 'admin.php?page=vrfr_manageautores' ) ) . '">' . esc_html( $autores ) . esc_html__( ' Authors.', 'vr-frases' ) . '</a></li></ul>'; // Escape.
	echo '<ul><li>' . esc_html__( 'Your classification handlers: ', 'vr-frases' ) . '<a href="' . esc_url( admin_url( 'admin.php?page=vrfr_managetemas' ) ) . '">' . esc_html( $temas ) . ' ' . esc_html__( 'Themes', 'vr-frases' ) . '</a>.</li></ul>'; // Escape.
	echo '<fieldset id="box" style="height: auto; border: solid 1px; padding: 10px;">';
	echo '<legend style="padding: 5px;"><em>' . esc_html__( 'Sample of output style for [randomfrase] and sidebar widgets:', 'vr-frases' ) . '</em></legend>'; // Escape.
	echo '<ul><li>' . wp_kses_post( $random ) . '</li></ul>'; // Allow HTML in the phrase.
	echo '</fieldset>';
	echo '<p>' . esc_html__( 'You are using VR-frases version: ', 'vr-frases' ) . '<b>' . esc_html( $version ) . '</b></p>'; // Escape.
}

// Register widget.
add_action(
	'widgets_init',
	function () {
		register_widget( 'VR_Frases_Widget' );
	}
);

/**
 * Register the VR-Frases dashboard widget.
 *
 * Adds the plugin dashboard widget to WordPress admin using
 * wp_add_dashboard_widget() with statistics and management links.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_add_dashboard_widget() {
	wp_add_dashboard_widget( 'vr_frases_dashboard', __( 'Take a look for VR-frases', 'vr-frases' ), 'vr_frases_dash_widget' );
}

// Insert dashboard widget only for admin pages.
add_action( 'wp_dashboard_setup', 'vr_frases_add_dashboard_widget' );
