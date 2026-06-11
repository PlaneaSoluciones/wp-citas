<?php
/**
 * VR-Frases Admin Class Management
 *
 * This file contains all functions for managing quote classes in the admin panel.
 * It handles class listing, creation, editing, deletion, search functionality,
 * and provides comprehensive admin interface for class management.
 *
 * File organization:
 * - Main controller function with GET routing
 * - Class listing with pagination, search, and filtering
 * - Add/Edit class forms and bulk operations
 * - AJAX endpoints for modern class management
 * - Quick-edit functionality with inline updates
 * - Validation and duplicate checking
 *
 * Key features:
 * - Pagination support for large class lists
 * - Real-time search with instant filtering
 * - Bulk class creation (comma-separated input)
 * - Quick-edit functionality for rapid updates
 * - Cascade protection (prevent deletion of classes with quotes)
 * - AJAX-based operations for improved user experience
 * - Duplicate prevention with intelligent validation
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
 * Main controller for class admin actions.
 *
 * Routes GET parameters for different UI views. Form processing
 * is handled via dedicated AJAX endpoints.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_manage_clases() {
	// Get action from GET parameters only (UI routing).
	$accion_raw  = filter_input( INPUT_GET, 'accion', FILTER_UNSAFE_RAW );
	$accion      = null !== $accion_raw ? sanitize_text_field( wp_unslash( $accion_raw ) ) : '';
	$idclase_raw = filter_input( INPUT_GET, 'idclase', FILTER_VALIDATE_INT );
	$idclase     = false === $idclase_raw || null === $idclase_raw ? 0 : absint( $idclase_raw );

	$accion = esc_html( $accion );

	// Route to appropriate UI function - currently only default (list classes).
	if ( function_exists( 'vr_frases_listar_clases' ) ) {
		vr_frases_listar_clases();
	}
}

/**
 * Display comprehensive class management interface.
 *
 * Renders class management with dual-panel layout, creation forms,
 * search functionality, pagination, and quick-edit capabilities.
 *
 * @since 4.1.0
 * @param string $pagina Optional current page number for pagination.
 * @return void
 */
function vr_frases_listar_clases( $pagina = '' ) {
	global $wpdb;
	$options    = get_option( 'vr_frases_options' );
	$options    = is_array( $options ) ? $options : array(); // Ensure $options is an array.
	$num_inputs = isset( $options['num_inputs'] ) && $options['num_inputs'] > 0 ? absint( $options['num_inputs'] ) : 20; // Default to 20 if not set or invalid.

	// Get search term using filter_input to avoid direct superglobal access.
	// Get search term using filter_input to avoid direct superglobal access.
	$search_raw = filter_input( INPUT_GET, 'search', FILTER_UNSAFE_RAW );
	$search     = null !== $search_raw ? sanitize_text_field( wp_unslash( $search_raw ) ) : '';

		// Query the database directly for class list, search and pagination.
		$search_like = '';
	if ( ! empty( $search ) ) {
		$search_like = '%' . $wpdb->esc_like( $search ) . '%';
		$registros   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->clases} WHERE clase LIKE %s", $search_like ) );
	} else {
		$registros = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->clases}" );
	}
		$pagina_raw = filter_input( INPUT_GET, 'pagina', FILTER_VALIDATE_INT );
		$pagina     = null === $pagina_raw || false === $pagina_raw ? 1 : absint( $pagina_raw ); // Current Page, Default 1.
		$paginas    = $num_inputs > 0 ? ceil( $registros / $num_inputs ) : 1;
		$inicio     = ( $pagina - 1 ) * $num_inputs;

	if ( ! empty( $search ) ) {
		$clases = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->clases} WHERE clase LIKE %s ORDER BY clase ASC LIMIT %d, %d", $search_like, $inicio, $num_inputs ) );
	} else {
		$clases = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->clases} ORDER BY clase ASC LIMIT %d, %d", $inicio, $num_inputs ) );
	}

	?>
	<div class="wrap vr-frases">
		<h1 style="display:flex;align-items:center;gap:12px;">
			<span class="dashicons dashicons-tag" style="font-size: 30px; width: 30px; height: 30px;"></span>
			<?php esc_html_e( 'Manage Classes', 'vr-frases' ); ?>
		</h1>
		<div id="col-left" style="width: 30%;">
			<div class="form-wrap">
				<h3>
					<span class="dashicons dashicons-plus-alt" style="color: #0073aa;"></span>
					<?php esc_html_e( 'Add new classes', 'vr-frases' ); ?>
				</h3>
				<form id="add-class-form" name="addnew" autocomplete="off">
					<input type="hidden" id="vr_nonce_clases" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_clases' ) ); ?>" />
					<div class="form-field">
						<label for="clase"><?php esc_html_e( 'Class', 'vr-frases' ); ?></label>
						<input type='text' name='clase' id='clase' size='40' value="" required />
					</div>
					<p class="submit">
						<button id="addnew" class="button" type="submit"><?php esc_html_e( 'Save', 'vr-frases' ); ?></button>
						<small><?php esc_html_e( 'NOTICE: You can input multiple values, separated by commas.', 'vr-frases' ); ?></small>
					</p>
				</form>
			</div>
		</div>
		<div class="vr-flexbar" style="width: 70%;">
			<div class="vr-flexbar-search">
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" id="search-form">
					<input type="hidden" name="page" value="vrfr_manageclases" />
					<label for="search"><?php esc_html_e( 'Search Classes:', 'vr-frases' ); ?></label>
					<input type="text" id="search" name="search" placeholder="<?php esc_attr_e( 'Search classes...', 'vr-frases' ); ?>" value="<?php echo esc_attr( $search ); ?>" oninput="document.getElementById('search-form').submit();" />
				</form>
			</div>
			<div class="vr-flexbar-info" style="justify-content: flex-end;">
				<div class="vr-flexbar-count">
					<span class="frases-num-items search-item">
						<?php
						/* translators: %1$s is the number of items, %2$s is the plural suffix */
						printf(
							/* translators: %1$s is the number of items, %2$s is the plural suffix */
							esc_html__( '%1$s item%2$s found', 'vr-frases' ),
							esc_html( number_format_i18n( $registros ) ),
							( 1 === (int) $registros ) ? '' : 's'
						);
						?>
					</span>
				</div>
				<div class="vr-flexbar-paging">
					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="paging-input">
						<input type="hidden" name="page" value="vrfr_manageclases" />
						<input type="hidden" name="search" value="<?php echo esc_attr( $search ); ?>" />
						<div class="tablenav-pages">
							<?php vr_frases_form_paginar( $pagina, $paginas, $registros, 'top' ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div id="col-right" style="width: 70%;">
		<div class="col-wrap">
				<?php
				// Prepare SELECT with or without search condition to avoid interpolated variables.
				if ( ! empty( $search ) ) {
					$clases = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->clases} WHERE clase LIKE %s ORDER BY clase ASC LIMIT %d, %d", $search_like, $inicio, $num_inputs ) );
				} else {
					$clases = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->clases} ORDER BY clase ASC LIMIT %d, %d", $inicio, $num_inputs ) );
				}
				if ( ! empty( $clases ) ) {
					?>
					<form name="listform" id="listform" class="vr-frases-form clases-list-form" autocomplete="off">
						<table class="wp-list-table vr-clases widefat fixed striped">
							<thead>
								<tr>
									<th scope="col" class="manage-column check-column vr-column-center" style="width: 03%;">
										<input id="cb-select-all-1" title="<?php esc_attr_e( 'Seleccionar/Deseleccionar todo', 'vr-frases' ); ?>" type="checkbox" onclick="SetAllCheckBoxes('listform', 'ids[]', this.checked);" />
									</th>
									<th scope="col" class="vr-column-center" style="width: 3%;"><?php esc_html_e( 'ID', 'vr-frases' ); ?></th>
									<th scope="col" style="width: 65%;"><?php esc_html_e( 'Class', 'vr-frases' ); ?></th>
									<th scope="col" class="vr-column-center" style="width: 07%;"><?php esc_html_e( 'Quotes', 'vr-frases' ); ?></th>
									<th scope="col" class="vr-column-center" style="width: 15%;"><?php esc_html_e( 'Edit', 'vr-frases' ); ?></th>
									<th scope="col" class="vr-column-center" style="width: 10%;"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $clases as $clase ) {
									$contador = (int) $wpdb->get_var(
										$wpdb->prepare(
											"SELECT COUNT(*) FROM {$wpdb->frases} WHERE idclase = %d",
											$clase->idclase
										)
									);
									?>
									<tr id="clase-<?php echo esc_attr( $clase->idclase ); ?>">
										<th scope="row" class="check-column vr-column-center">
											<?php if ( 0 === $contador ) { ?>
											<input type="checkbox" class="vr-checkbox" data-id="<?php echo esc_attr( $clase->idclase ); ?>" data-tipo="clases">
											<?php } ?>
										</th>
										<td class="vr-column-center"><?php echo esc_html( $clase->idclase ); ?></td>
										<td><?php echo esc_html( $clase->clase ); ?></td>
										<td class="vr-column-center"><?php echo esc_html( $contador ); ?></td>
										<td class="vr-column-center">
											<button type="button" class="quick-edit button" 
												data-context="clases" 
												data-id="<?php echo esc_attr( $clase->idclase ); ?>" 
												data-name="<?php echo esc_attr( $clase->clase ); ?>">
												<span class="dashicons dashicons-edit" style="vertical-align: text-bottom;"></span>
												<?php esc_html_e( 'Quick Edit', 'vr-frases' ); ?>
											</button>
										</td>
										<td class="vr-column-center">
											<?php if ( 0 === $contador ) { ?>
												<button
													type="button"
													class="button enabled"
													title="<?php esc_attr_e( 'Delete this Class', 'vr-frases' ); ?>"
													data-id="<?php echo esc_attr( $clase->idclase ); ?>"
													data-tipo="clases"
													data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_clases' ) ); ?>"
												>
											<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; color: #a00;"></span>
																				<?php esc_html_e( 'Delete', 'vr-frases' ); ?>
												</button>
											<?php } else { ?>
												<span class="button disabled">
													<span class="dashicons dashicons-no" style="vertical-align: text-bottom;"></span>
													<?php esc_html_e( 'Delete', 'vr-frases' ); ?>
												</span>
											<?php } ?>
										</td>
									</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr>
									<th scope="col" class="manage-column check-column" style="width: 5%;">
										<input id="cb-select-all-2" title="<?php esc_attr_e( 'Seleccionar/Deseleccionar todo', 'vr-frases' ); ?>" type="checkbox" onclick="SetAllCheckBoxes('listform', 'ids[]', this.checked);" />
									</th>
									<th scope="col" class="vr-column-center"><?php esc_html_e( 'ID', 'vr-frases' ); ?></th>
									<th scope="col" ><?php esc_html_e( 'Class', 'vr-frases' ); ?></th>
									<th scope="col" class="vr-column-center"><?php esc_html_e( 'Quotes', 'vr-frases' ); ?></th>
									<th scope="col" class="vr-column-center"><?php esc_html_e( 'Edit', 'vr-frases' ); ?></th>
									<th scope="col" class="vr-column-center"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
								</tr>
							</tfoot>
						</table>
						<div class="tablenav bottom submit alignleft">
							<button
								id="vr-delitems-button"
								class="button"
								type="button"
								data-tipo="clases"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_clases' ) ); ?>"
								data-confirm="<?php echo esc_attr( __( 'Are you sure you want to delete these Classes?', 'vr-frases' ) ); ?>"
							>
								<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; color: #a00;"></span>
								<?php esc_html_e( 'Delete selected', 'vr-frases' ); ?>
							</button>
							<small>
								<span class="dashicons dashicons-info" style="color: #0073aa;"></span>
								<?php esc_html_e( 'NOTICE: You only can delete items that do not have related quotes. You can modify them, or go to delete the related quotes before proceed.', 'vr-frases' ); ?>
							</small>
						</div>
					</form>
					<?php
				} else {
					echo '<div class="notice notice-info"><p>' . esc_html__( 'No classes to list.', 'vr-frases' ) . '</p></div>';
				}
				?>
			</div>	
		</div>
	</div>
	<?php
}

/**
 * AJAX endpoint for adding new classes with bulk creation support.
 *
 * Processes class creation via AJAX with validation and duplicate checking.
 * Supports comma-separated input for bulk creation.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_addnew_clase_ajax() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'vr-frases' ) ) );
		wp_die();
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'vr_nonce_clases' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce.', 'vr-frases' ) ) );
		wp_die();
	}

	$clases_raw = isset( $_POST['clase'] ) ? sanitize_text_field( wp_unslash( $_POST['clase'] ) ) : '';
	if ( empty( $clases_raw ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Error processing Class. Fields cannot be void.', 'vr-frases' ) ) );
		wp_die();
	}

	// Convert string to array and clean spaces.
	$clases_array = array_filter( array_map( 'trim', explode( ',', $clases_raw ) ) );

	if ( ! function_exists( 'vr_frases_add_items_common_ajax' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Internal error: common add function not found.', 'vr-frases' ) ) );
		wp_die();
	}

	$result = vr_frases_add_items_common_ajax( 'clases', $clases_array, $GLOBALS['wpdb'] );

	if ( isset( $result['success'] ) && $result['success'] ) {
		wp_send_json_success(
			array(
				'message'    => $result['messages'],
				'added'      => $result['added'],
				'duplicates' => $result['duplicates'],
			)
		);
	} else {
		$msg = isset( $result['messages'] ) ? $result['messages'] : esc_html__( 'Unknown error.', 'vr-frases' );
		wp_send_json_error(
			array(
				'message'    => $msg,
				'added'      => $result['added'],
				'duplicates' => $result['duplicates'],
			)
		);
	}

	wp_die();
}
add_action( 'wp_ajax_vrfr_add_clase', 'vr_frases_addnew_clase_ajax' );

/**
 * AJAX endpoint for quick-edit functionality on classes.
 *
 * Handles inline editing of class names with validation
 * and duplicate checking.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_quick_edit_ajax_clases() {
	if ( ! isset( $_POST['id'], $_POST['name'], $_POST['nonce'] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Missing required data.', 'vr-frases' ) ) );
	}

	$id    = absint( wp_unslash( $_POST['id'] ) );
	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ) );
	$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'vr_nonce_clases' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'vr-frases' ) ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'vr-frases' ) ) );
	}
	if ( empty( $id ) || empty( $name ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'ID and name are required.', 'vr-frases' ) ) );
	}

	global $wpdb;
	// Check for duplicate name (excluding current ID).
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->clases} WHERE clase = %s AND idclase != %d", $name, $id ) );
	if ( $exists ) {
		wp_send_json_error( array( 'message' => esc_html__( 'A class with that name already exists.', 'vr-frases' ) ) );
	}

	$updated = $wpdb->update(
		$wpdb->clases,
		array( 'clase' => $name ),
		array( 'idclase' => $id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Database error updating class.', 'vr-frases' ) ) );
	}

	// Return updated row data for UI update.
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT idclase, clase FROM {$wpdb->clases} WHERE idclase = %d", $id ), ARRAY_A );
	wp_send_json_success(
		array(
			'message' => esc_html__( 'Class updated successfully.', 'vr-frases' ),
			'row'     => $row,
		)
	);
}
add_action( 'wp_ajax_vr_frases_quick_edit_clases', 'vr_frases_quick_edit_ajax_clases' );
