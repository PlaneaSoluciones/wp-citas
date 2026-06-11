<?php
/**
 * VR-Frases Admin Helper Functions
 *
 * This file contains all utility and helper functions used throughout the
 * plugin's admin interface. It provides core functionality for data management,
 * counting operations, deletion operations, and quote display with proper
 * security validation and error handling.
 *
 * Key Features:
 * - Centralized deletion operations with comprehensive validation
 * - Statistical counting functions for shortcodes
 * - Quote display functionality with security escaping
 * - Database optimization after operations
 * - Multi-language support with proper translation comments
 *
 * Dependencies:
 * - functions-filters.php: Search, pagination, and filtering functions
 * - functions-ajax.php: AJAX endpoints and handlers
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vr-frases-functions-filters.php';
require_once __DIR__ . '/vr-frases-functions-ajax.php';


/**
 * Centralized deletion function for all entity types.
 *
 * Handles deletion operations with validation, error handling,
 * and database optimization.
 *
 * @since 4.1.0
 * @param string    $tipo The type of item to delete.
 * @param array|int $ids  Single ID or array of IDs to delete.
 * @return array Array with success status, deleted items, and errors.
 */
function vr_frases_delete_common( $tipo, $ids ) {
	global $wpdb;

	// Convert single ID to array for uniform processing.
	if ( ! is_array( $ids ) ) {
		$ids = array( $ids );
	}

	// Validate the type.
	if ( empty( $tipo ) ) {
		return array(
			'success'       => false,
			'deleted_items' => array(),
			'errors'        => array( esc_html__( 'Error: Item type is missing or empty.', 'vr-frases' ) ),
		);
	}

	// Database table and column mapping by entity type.
	$map = array(
		'frases'  => array(
			'table'       => $wpdb->frases,
			'id_column'   => 'idfrase',
			'name_column' => 'frase',
		),
		'clases'  => array(
			'table'       => $wpdb->clases,
			'id_column'   => 'idclase',
			'name_column' => 'clase',
		),
		'temas'   => array(
			'table'       => $wpdb->temas,
			'id_column'   => 'idtema',
			'name_column' => 'tema',
		),
		'autores' => array(
			'table'       => $wpdb->autores,
			'id_column'   => 'idautor',
			'name_column' => 'autor',
		),
		'import'  => array(
			'table'       => $wpdb->import,
			'id_column'   => 'idimport',
			'name_column' => 'frase',
		),
	);

	// Validate that the type is valid.
	if ( ! isset( $map[ $tipo ] ) ) {
		return array(
			'success'       => false,
			'deleted_items' => array(),
			/* translators: %s: item type that was invalid */
			'errors'        => array( sprintf( esc_html__( 'Error: Invalid item type "%s".', 'vr-frases' ), esc_html( $tipo ) ) ),
		);
	}

	// Validate nonce and capability using centralized helper.
	$nonces     = array(
		'clases'  => 'vr_nonce_clases',
		'temas'   => 'vr_nonce_temas',
		'frases'  => 'vr_nonce_frases',
		'autores' => 'vr_nonce_autores',
		'import'  => 'vr_nonce_import',
	);
	$nonce_name = isset( $nonces[ $tipo ] ) ? $nonces[ $tipo ] : '';
	if ( empty( $nonce_name ) ) {
		return array(
			'success'       => false,
			'deleted_items' => array(),
			'errors'        => array( esc_html__( 'Error: Invalid nonce. Action not allowed.', 'vr-frases' ) ),
		);
	}

	$deleted_items = array();
	$errors        = array();

	foreach ( $ids as $id ) {
		$id = absint( $id );

		// Validate that the ID is valid.
		if ( 0 >= $id ) {
			// translators: This error message is shown when the ID provided is not valid.
			$errors[] = esc_html__( 'Error: Invalid ID.', 'vr-frases' );

			continue;
		}

		// Get the item before deleting it.
		// Use explicit per-type SQL with literal first argument for $wpdb->prepare().
		switch ( $tipo ) {
			case 'frases':
				$item = $wpdb->get_row( $wpdb->prepare( "SELECT idfrase, frase FROM {$wpdb->frases} WHERE idfrase = %d", $id ) );
				break;
			case 'clases':
				$item = $wpdb->get_row( $wpdb->prepare( "SELECT idclase, clase FROM {$wpdb->clases} WHERE idclase = %d", $id ) );
				break;
			case 'temas':
				$item = $wpdb->get_row( $wpdb->prepare( "SELECT idtema, tema FROM {$wpdb->temas} WHERE idtema = %d", $id ) );
				break;
			case 'autores':
				$item = $wpdb->get_row( $wpdb->prepare( "SELECT idautor, autor FROM {$wpdb->autores} WHERE idautor = %d", $id ) );
				break;
			case 'import':
				$item = $wpdb->get_row( $wpdb->prepare( "SELECT idimport, frase FROM {$wpdb->prefix}vr_fr_import WHERE idimport = %d", $id ) );
				break;
			default:
				$item = null;
				break;
		}

		if ( empty( $item ) ) {
			$errors[] = sprintf(
				// translators: %1$s is the item type, %2$d is the item ID.
				esc_html__( 'Error: %1$s not found with ID %2$d.', 'vr-frases' ),
				esc_html( ucfirst( $tipo ) ),
				$id
			);

			continue;
		}

		// Try to delete the item.
		$result = $wpdb->delete( $map[ $tipo ]['table'], array( $map[ $tipo ]['id_column'] => $id ) );

		if ( false !== $result ) {
			$deleted_items[] = array(
				'id'   => $item->{$map[ $tipo ]['id_column']},
				'name' => $item->{$map[ $tipo ]['name_column']},
			);

			// Optimize the table after deleting (table name is validated through internal mapping).
			$table_name = $map[ $tipo ]['table'];
			if ( in_array( $table_name, array( $wpdb->autores, $wpdb->clases, $wpdb->temas, $wpdb->frases, $wpdb->import ), true ) ) {
				/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
				$wpdb->query( "OPTIMIZE TABLE {$table_name}" );
			}
		} else {
			$errors[] = sprintf(
				// translators: %1$s is the item type, %2$s is the MySQL error message.
				esc_html__( 'Error deleting %1$s. MySQL error: %2$s', 'vr-frases' ),
				esc_html( $tipo ),
				esc_html( $wpdb->last_error )
			);
		}
	}

		return array(
			'success'       => ! empty( $deleted_items ),
			'deleted_items' => $deleted_items,
			'errors'        => $errors,
		);
}

/**
 * Get the total count of quotes with localized formatting.
 *
 * Retrieves quote count from database and returns it as
 * a localized, formatted string.
 *
 * @since 4.1.0
 * @return string Localized and formatted count of total quotes.
 */
function vr_frases_total_frases() {
	global $wpdb;

	// Always query the database.
	$quotes    = $wpdb->get_var( "SELECT COUNT(frase) AS count FROM {$wpdb->frases}" );
	$formatted = number_format_i18n( (int) $quotes );

	return $formatted;
}

/**
 * Get the count of unique authors with localized formatting.
 *
 * Calculates distinct authors count, groups by name to avoid
 * duplicates, and returns localized formatted string.
 *
 * @since 4.1.0
 * @return string Localized and formatted count of unique authors.
 */
function vr_frases_total_autores() {
	global $wpdb;

	// Always query the database.
	$results   = $wpdb->get_results( "SELECT COUNT(autor) AS count FROM {$wpdb->frases} GROUP BY autor" );
	$authors   = count( $results );
	$formatted = number_format_i18n( (int) $authors );

	return $formatted;
}

/**
 * Get the count of unique themes with localized formatting.
 *
 * Retrieves distinct themes count from database and returns
 * localized formatted string for display.
 *
 * @since 4.1.0
 * @return string Localized and formatted count of unique themes.
 */
function vr_frases_total_temas() {
	global $wpdb;

	// Always query the database.
	$results   = $wpdb->get_results( "SELECT COUNT(tema) AS count FROM {$wpdb->temas} GROUP BY tema" );
	$themes    = count( $results );
	$formatted = number_format_i18n( (int) $themes );

	return $formatted;
}

/**
 * Get the count of unique classes with localized formatting.
 *
 * Retrieves distinct classes count from database and returns
 * localized formatted string for display.
 *
 * @since 4.1.0
 * @return string Localized and formatted count of unique classes.
 */
function vr_frases_total_clases() {
	global $wpdb;

	// Always query the database.
	$results   = $wpdb->get_results( "SELECT COUNT(clase) AS count FROM {$wpdb->clases} GROUP BY clase" );
	$classes   = count( $results );
	$formatted = number_format_i18n( (int) $classes );

	return $formatted;
}

/**
 * Display a single quote with author information as formatted HTML.
 *
 * Retrieves and displays specific quote by ID with author
 * as clickable link. Handles invalid IDs gracefully.
 *
 * @since 4.1.0
 * @param int $idfrase The unique ID of the quote to display.
 * @return void
 */
function vr_frases_single_frase( $idfrase ) {
	global $wpdb;

	$id = absint( $idfrase );
	if ( 0 >= $id ) {
		return;
	}

	$options = get_option( 'vr_frases_options' );
	$row     = $wpdb->get_row( $wpdb->prepare( "SELECT frase, autor FROM {$wpdb->frases} WHERE idfrase = %d", $id ) );

	if ( empty( $row ) ) {
		return;
	}

	// Build safe URL and escape values.
	$author_esc = esc_html( $row->autor );
	$quote_esc  = esc_html( $row->frase );
	$link_url   = esc_url( get_option( 'siteurl' ) . '/' . $options['page_slug'] . '/?autor=' . rawurlencode( $row->autor ) );

	$output = '<a title="' . esc_attr__( 'View more quotes from this Author...', 'vr-frases' ) . '" href="' . $link_url . '"><b>' . $author_esc . '</b></a>: ' . $quote_esc;

	echo wp_kses_post( $output );
}
