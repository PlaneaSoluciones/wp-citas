<?php
/**
 * VR-Frases Shortcode Management and Registration System
 *
 * Provides shortcode functionality for displaying quotes, statistics,
 * and interactive content in WordPress posts, pages, and widgets.
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

/**
 * Primary quotes interface shortcode.
 *
 * Renders complete quotes interface with search, pagination,
 * and filtering capabilities.
 *
 * @since 4.1.0
 * @return string Complete HTML output of the quotes interface.
 */
function vr_frases_show_shortcode() {
	return vr_frases_show_main(); // HTML is escaped in the show_main function.
}
add_shortcode( 'vrfrases', 'vr_frases_show_shortcode' );

/**
 * Random quote display shortcode.
 *
 * Provides dynamic random quote functionality with author
 * attribution and professional formatting.
 *
 * @since 4.1.0
 * @return string Formatted HTML output with random quote and author.
 */
function vr_frases_randomfrase_shortcode() {
	return vr_frases_random_frase(); // HTML is escaped in the random_frase function.
}
add_shortcode( 'randomfrase', 'vr_frases_randomfrase_shortcode' );

/**
 * Quote statistics shortcode with localized formatting.
 *
 * Displays total quote count with proper localization
 * and formatting according to site locale.
 *
 * @since 4.1.0
 * @return string Localized and formatted total quote count.
 */
function vr_frases_frasescount_shortcode() {
	return vr_frases_total_frases(); // Returns a simple integer.
}
add_shortcode( 'frasescount', 'vr_frases_frasescount_shortcode' );

/**
 * Author statistics shortcode with unique contributor counting.
 *
 * Displays unique authors count using intelligent grouping
 * to avoid duplicates with localized formatting.
 *
 * @since 4.1.0
 * @return string Localized and formatted unique author count.
 */
function vr_frases_autorescount_shortcode() {
	return vr_frases_total_autores(); // Returns a simple integer.
}
add_shortcode( 'autorescount', 'vr_frases_autorescount_shortcode' );
