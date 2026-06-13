<?php
/**
 * VR-Frases Admin - AJAX Handlers
 *
 * AJAX endpoint functions for admin interface operations including
 * deletion, item creation, and external API integrations.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

/**
 * AJAX endpoint for single item deletion.
 *
 * Handles deletion with nonce validation and permission checking
 * for all entity types.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_ajax_delete_item() {
	$tipo  = isset( $_POST['tipo'] ) ? sanitize_text_field( wp_unslash( $_POST['tipo'] ) ) : '';
	$id    = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	$nonces_map = array(
		'frases'  => 'vr_nonce_frases',
		'autores' => 'vr_nonce_autores',
		'import'  => 'vr_nonce_import',
	);

	if ( empty( $tipo ) || empty( $id ) || empty( $nonce ) || ! isset( $nonces_map[ $tipo ] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Missing or invalid data.', 'vr-frases' ) ) );
	}

	if ( ! wp_verify_nonce( $nonce, $nonces_map[ $tipo ] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'vr-frases' ) ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'vr-frases' ) ) );
	}

	$result = vr_frases_delete_common( $tipo, $id );

	if ( $result['success'] && ! empty( $result['deleted_items'][0] ) ) {

		$item_names = array_map(
			function ( $item ) {
				return $item['name'];
			},
			$result['deleted_items']
		);

		$tipo_labels = array(
			'frases'  => esc_html__( 'Deleted Quote', 'vr-frases' ),
			'autores' => esc_html__( 'Deleted Author', 'vr-frases' ),
			'import'  => esc_html__( 'Deleted Import', 'vr-frases' ),
		);

		$label   = isset( $tipo_labels[ $tipo ] ) ? $tipo_labels[ $tipo ] : esc_html__( 'Item', 'vr-frases' );
		$message = sprintf(
			/* translators: %1$s is the item type, %2$s is the name. */
			esc_html__( '%1$s: %2$s', 'vr-frases' ),
			$label,
			implode( ', ', $item_names )
		);

		wp_send_json_success(
			array(
				'deleted' => $result['deleted_items'],
				'message' => $message,
			)
		);
	} elseif ( ! empty( $result['errors'] ) ) {
		wp_send_json_error( array( 'message' => implode( "\n", $result['errors'] ) ) );
	} else {
		wp_send_json_error( array( 'message' => esc_html__( 'Error while processing: No valid items found.', 'vr-frases' ) ) );
	}
}
add_action( 'wp_ajax_vr_frases_delete_item', 'vr_frases_ajax_delete_item' );

/**
 * AJAX endpoint for batch deletion of multiple items.
 *
 * Handles bulk deletion with validation and error handling
 * for all entity types.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_ajax_delete_multiple_items() {
	$tipo_raw  = isset( $_POST['tipo'] ) ? sanitize_text_field( wp_unslash( $_POST['tipo'] ) ) : '';
	$nonce_raw = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	$ids_raw   = isset( $_POST['ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['ids'] ) ) : array();

	$tipo  = $tipo_raw;
	$nonce = $nonce_raw;
	$ids   = array_map( 'absint', $ids_raw );
	$ids   = array_filter( $ids ); // Remove 0s.

	$nonces_map = array(
		'frases'  => 'vr_nonce_frases',
		'autores' => 'vr_nonce_autores',
	);

	if ( empty( $tipo ) || empty( $nonce ) || empty( $ids ) || ! isset( $nonces_map[ $tipo ] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Missing or invalid data.', 'vr-frases' ) ) );
	}

	if ( ! wp_verify_nonce( $nonce, $nonces_map[ $tipo ] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'vr-frases' ) ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You do not have sufficient permissions to perform this action.', 'vr-frases' ) ) );
	}

	$result = vr_frases_delete_common( $tipo, $ids );

	if ( $result['success'] ) {
		$item_names            = array_map(
			function ( $item ) {
				return $item['name'];
			},
			$result['deleted_items']
		);
			$tipo_labels_multi = array(
				'frases'  => esc_html__( 'Deleted Quotes', 'vr-frases' ),
				'autores' => esc_html__( 'Deleted Authors', 'vr-frases' ),
				'imports' => esc_html__( 'Deleted Imports', 'vr-frases' ),
			);
			$label_multi       = isset( $tipo_labels_multi[ $tipo ] ) ? $tipo_labels_multi[ $tipo ] : esc_html__( 'Items', 'vr-frases' );
			$message           = sprintf(
				/* translators: %1$s is the plural item type, %2$s is the list of names. */
				esc_html__( '%1$s: %2$s', 'vr-frases' ),
				$label_multi,
				implode( ', ', $item_names )
			);
			wp_send_json_success(
				array(
					'deleted' => $result['deleted_items'],
					'message' => $message,
				)
			);
	} elseif ( ! empty( $result['errors'] ) ) {
		wp_send_json_error( array( 'message' => implode( "\n", $result['errors'] ) ) );
	} else {
		wp_send_json_error( array( 'message' => esc_html__( 'Error while processing: No valid items found.', 'vr-frases' ) ) );
	}
}
add_action( 'wp_ajax_vr_frases_delete_multiple_items', 'vr_frases_ajax_delete_multiple_items' );

/**
 * Register AJAX handlers for plugin admin functionality.
 *
 * Initializes all AJAX endpoints with proper capability checking
 * and security validation for logged-in users.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_register_ajax_handlers() {
	add_action( 'wp_ajax_vr_frases_delete_item', 'vr_frases_ajax_delete_item' );
	add_action( 'wp_ajax_vr_frases_delete_multiple_items', 'vr_frases_ajax_delete_multiple_items' );
}
add_action( 'init', 'vr_frases_register_ajax_handlers' );

/**
 * Centralized handler for adding multiple items.
 *
 * Handles bulk creation with validation and duplicate checking.
 *
 * @since 4.1.0
 * @param string       $tipo  Entity type (clases, temas, autores).
 * @param array|string $items Items to create.
 * @param wpdb         $wpdb  WordPress database object.
 * @return array Creation results with success/failure details.
 */
function vr_frases_add_items_common_ajax( $tipo, $items, $wpdb ) {
	$map = array(
		'autores' => array(
			'table'  => $wpdb->autores,
			'column' => 'autor',
		),
	);
	if ( ! isset( $map[ $tipo ] ) ) {
		return array(
			'added'      => array(),
			'duplicates' => array(),
			'messages'   => array( __( 'Invalid type.', 'vr-frases' ) ),
			'success'    => false,
		);
	}
	$table      = $map[ $tipo ]['table'];
	$column     = $map[ $tipo ]['column'];
	$added      = array();
	$duplicates = array();
	$messages   = array();

	// Labels for entity types.
	$tipo_labels_add = array(
		'autores' => array(
			'singular' => esc_html__( 'Author added', 'vr-frases' ),
			'plural'   => esc_html__( 'Authors added', 'vr-frases' ),
		),
	);

	$tipo_labels_dup = array(
		'autores' => array(
			'singular' => esc_html__( 'Author already exists', 'vr-frases' ),
			'plural'   => esc_html__( 'Authors already exist', 'vr-frases' ),
		),
	);

	$names = array();

	foreach ( $items as $item ) {
		$item_clean = sanitize_text_field( $item );
		if ( '' === $item_clean ) {
			continue;
		}
		// Query construction to satisfy PHPCS security checks.
		$row = null;
		if ( 'autores' === $tipo ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->autores} WHERE autor = %s", $item_clean ) );
		}
		if ( $row ) {
			$duplicates[] = $item_clean;
		} else {
			$wpdb->insert( $table, array( $column => $item_clean ), array( '%s' ) );
			$id      = $wpdb->insert_id;
			$added[] = array(
				'id'   => $id,
				'name' => $item_clean,
			);
		}
	}

	if ( ! empty( $added ) ) {
		$names      = array_map(
			function ( $item ) {
				return is_array( $item ) ? $item['name'] : $item;
			},
			$added
		);
		$label      = ( count( $names ) === 1 )
			? $tipo_labels_add[ $tipo ]['singular']
			: $tipo_labels_add[ $tipo ]['plural'];
		$messages[] = sprintf( '%s: %s', $label, implode( ', ', $names ) );
	}
	if ( ! empty( $duplicates ) ) {
		$label_dup  = ( count( $duplicates ) === 1 )
			? $tipo_labels_dup[ $tipo ]['singular']
			: $tipo_labels_dup[ $tipo ]['plural'];
		$messages[] = sprintf( '%s: %s', $label_dup, implode( ', ', $duplicates ) );
	}
	$success  = ! empty( $added );
	$messages = implode( "\n", $messages );
	return array(
		'added'      => $added,
		'duplicates' => $duplicates,
		'messages'   => $messages,
		'success'    => $success,
	);
}

/**
 * AJAX endpoint for Wikipedia search integration.
 *
 * Provides Wikipedia API integration for author research with
 * multilingual support and proper error handling.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_search_wikipedia() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vr_nonce_autores' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed: invalid nonce.', 'vr-frases' ) ) );
	}
	$autor_raw = filter_input( INPUT_POST, 'autor', FILTER_UNSAFE_RAW );
	$autor     = null !== $autor_raw ? sanitize_text_field( wp_unslash( $autor_raw ) ) : '';
	if ( empty( $autor ) ) {
		wp_send_json_error( array( 'message' => __( 'Author name is required.', 'vr-frases' ) ) );
	}
	$options  = get_option( 'vr_frases_options' );
	$lang     = $options['wiki_lang'] ?? 'en';
	$api_url  = "https://{$lang}.wikipedia.org/w/api.php?action=query&list=search&srsearch=" . rawurlencode( $autor ) . '&format=json';
	$response = wp_remote_get( $api_url );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => __( 'Error connecting to Wikipedia API.', 'vr-frases' ) ) );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( isset( $data['query']['search'][0]['title'] ) ) {
		$page_title = $data['query']['search'][0]['title'];
		$url        = "https://{$lang}.wikipedia.org/wiki/" . rawurlencode( $page_title );
		wp_send_json_success( array( 'url' => $url ) );
	} else {
		wp_send_json_error( array( 'message' => __( 'No Wikipedia page found.', 'vr-frases' ) ) );
	}
}
add_action( 'wp_ajax_search_wikipedia', 'vr_frases_search_wikipedia' );
