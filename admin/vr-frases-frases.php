<?php
/**
 * VR-Frases Admin Quote Management
 *
 * This file contains all functions for managing quotes in the admin panel.
 * It handles quote listing, editing, creation, duplicate checking, AJAX operations,
 * and provides comprehensive admin interface functionality.
 *
 * File organization:
 * - Main controller function with route handling
 * - Quote listing with pagination, search, and filtering
 * - Add/Edit quote forms and data processing
 * - AJAX endpoints for modern quote management
 * - Helper functions for duplicate checking and validation
 * - Quick-edit functionality with inline updates
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
 * Main controller for quote admin actions.
 *
 * Routes GET parameters to appropriate UI views. Form processing
 * is handled via dedicated AJAX endpoints.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_manage_frases() {
	// Get action from GET parameters only (UI routing).
	$accion_raw  = filter_input( INPUT_GET, 'accion', FILTER_UNSAFE_RAW );
	$accion      = null !== $accion_raw ? sanitize_text_field( wp_unslash( $accion_raw ) ) : '';
	$idfrase_raw = filter_input( INPUT_GET, 'idfrase', FILTER_VALIDATE_INT );
	$idfrase     = false === $idfrase_raw || null === $idfrase_raw ? 0 : absint( $idfrase_raw );

	$accion = esc_html( $accion );

	// Route to appropriate UI function.
	switch ( $accion ) {
		case 'editar':
			if ( function_exists( 'vr_frases_editar_frase' ) ) {
				vr_frases_editar_frase( $idfrase );
			}
			break;
		default:
			if ( function_exists( 'vr_frases_listar_frases' ) ) {
				vr_frases_listar_frases();
			}
			break;
	}
}

/**
 * Display main list of quotes with management features.
 *
 * Renders comprehensive quote interface with pagination, search filters,
 * sorting options, bulk actions, and inline editing capabilities.
 *
 * @since 4.1.0
 * @param string $pagina Optional page number to display.
 * @return void
 */
function vr_frases_listar_frases( $pagina = '' ) {
	global $wpdb;

	// Get options and initial parameters.
	$options      = get_option( 'vr_frases_options' );
	$num_inputs   = isset( $options['num_inputs'] ) ? absint( $options['num_inputs'] ) : 20; // Default value if not exists.
	$pagina_raw   = filter_input( INPUT_GET, 'pagina', FILTER_VALIDATE_INT );
	$pagina       = false === $pagina_raw || null === $pagina_raw ? 1 : absint( $pagina_raw ); // Sanitize page, default to 1.
	$filters      = vr_frases_search_filters();
	$where_clause = $filters['sql'];
	$where_params = $filters['params'];

	// Get the order of results.
	// Nonce verification not required here as we only read parameters to display information, not to modify data.
	$orderby_raw = filter_input( INPUT_GET, 'orderby', FILTER_UNSAFE_RAW );
	$order_raw   = filter_input( INPUT_GET, 'order', FILTER_UNSAFE_RAW );
	$orden_raw   = filter_input( INPUT_GET, 'orden', FILTER_UNSAFE_RAW );

	$valid_orderby = array( 'id', 'frase', 'autor' );
	$orderby       = ! empty( $orderby_raw ) ? sanitize_key( wp_unslash( $orderby_raw ) ) : 'frase';
	if ( ! in_array( $orderby, $valid_orderby, true ) ) {
		$orderby = 'frase';
	}
	$order_val = ! empty( $order_raw ) ? sanitize_key( wp_unslash( $order_raw ) ) : 'asc';
	$order     = in_array( $order_val, array( 'asc', 'desc' ), true ) ? $order_val : 'asc';
	$aleatorio = ! empty( $orden_raw ) && 'aleatorio' === sanitize_key( wp_unslash( $orden_raw ) );
	if ( $aleatorio ) {
		$orderby = 'aleatorio';
		$order   = 'asc';
	}

	$data      = vr_frases_get_list_data( $pagina, $num_inputs, $orderby, $order );
	$frases    = $data['frases'];
	$registros = $data['registros'];
	$paginas   = $data['paginas'];

	// Display the user interface.
	?>
	<div class="wrap vr-frases">
		<h1>
			<span class="dashicons dashicons-format-quote"></span>
			<?php esc_html_e( 'Manage Quotes', 'vr-frases' ); ?>
		</h1>		<?php // Display the titles message. ?>
			<?php
			echo '<span class="search-item">' . esc_html( vr_frases_define_titles( filter_input_array( INPUT_GET, FILTER_UNSAFE_RAW ) )['msg'] ) . '</span>'
			?>
		</h3>


		<div class="vr-flexbar">
			<div class="vr-flexbar-search">
				<?php
				// Marked safe: vr_frases_search_form() returns safe HTML for this context.
				/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
				echo vr_frases_search_form();
				?>
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
				<?php
				$autor_filtro = null !== filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW ) ? sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW ) ) ) : '';
				if ( ! empty( $autor_filtro ) ) :
					?>
				<div class="vr-flexbar-export">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="vr_frases_exportar_por_autores" />
						<input type="hidden" name="vr_nonce_export_autores" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_export_autores' ) ); ?>" />
						<input type="hidden" name="autor" value="<?php echo esc_attr( $autor_filtro ); ?>" />
						<button type="submit" class="button">
							<span class="dashicons dashicons-download" style="vertical-align: text-bottom;"></span>
							<?php
							printf(
								/* translators: %s: author name */
								esc_html__( 'Export quotes from "%s"', 'vr-frases' ),
								esc_html( $autor_filtro )
							);
							?>
						</button>
					</form>
				</div>
				<?php endif; ?>
				<div class="vr-flexbar-paging">
					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="paging-input">
						<input type="hidden" name="page" value="vrfr_managefrases" />
						<input type="hidden" name="orderby" value="<?php echo esc_attr( $aleatorio ? '' : $orderby ); ?>" />
						<input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>" />
						<input type="hidden" name="orden" value="<?php echo esc_attr( $aleatorio ? 'aleatorio' : '' ); ?>" />
						<input type="hidden" name="frase" value="<?php echo esc_attr( null !== filter_input( INPUT_GET, 'frase', FILTER_UNSAFE_RAW ) ? sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'frase', FILTER_UNSAFE_RAW ) ) ) : '' ); ?>" />
						<input type="hidden" name="autor" value="<?php echo esc_attr( null !== filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW ) ? sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW ) ) ) : '' ); ?>" />
						<div class="tablenav-pages">
							<?php vr_frases_form_paginar( $pagina, $paginas, $registros, 'top' ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		
			<!-- Make sure everything above has cleared properly -->
		<?php
		if ( null !== filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW ) ) {
			$autor   = null !== filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW ) ? sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW ) ) ) : '';
			$idautor = $wpdb->get_var( $wpdb->prepare( "SELECT idautor FROM {$wpdb->autores} WHERE autor = %s", $autor ) );
			if ( $idautor ) {
				echo '<div id="vr-author-section" class="author-details-section vr-author-section">';
				vr_frases_mostrar_autor( $idautor ); // Display author information.
				echo '</div>';
				// Separation div with class instead of inline style.
				echo '<div class="vr-section-separator"></div>';
			}
		}
		?>
		
		<?php if ( ! empty( $frases ) ) : ?>
			<div id="vr-main-list-container" class="vr-main-list-container">
			<form name="listform" id="listform" class="vr-frases-form frases-list-form" action="" method="post">
				<input name="tipo" type="hidden" value="frases" />
				<input type="hidden" id="vr_nonce_frases" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_frases' ) ); ?>" />
				<table class="wp-list-table vr-frases widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column check-column vr-column-center" style="width: 03%;">
								<input id="cb-select-all-1" title="<?php echo esc_attr__( 'Seleccionar/Deseleccionar todo', 'vr-frases' ); ?>" type="checkbox" onclick="SetAllCheckBoxes('listform', 'ids[]', this.checked);" />
							</th>
							<?php
							$sort_col = $aleatorio ? '' : $orderby;
							// Marked safe: vr_frases_sortable_column_header() returns properly escaped HTML.
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo vr_frases_sortable_column_header( __( 'ID', 'vr-frases' ), 'id', $sort_col, $order, 'width: 03%;', 'vr-column-center' );
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo vr_frases_sortable_column_header( __( 'Quote', 'vr-frases' ), 'frase', $sort_col, $order, 'width: 58%;', 'column-primary' );
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo vr_frases_sortable_column_header( __( 'Author', 'vr-frases' ), 'autor', $sort_col, $order, 'width: 15%;' );
							?>
							<th scope="col" class="manage-column vr-column-center" style="width: 06%;"><?php esc_html_e( 'Edit', 'vr-frases' ); ?></th>
							<th scope="col" class="manage-column vr-column-center" style="width: 04%;"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $frases as $frase ) :
							if ( is_object( $frase ) && isset( $frase->idfrase ) ) :
								?>
								<tr data-id="<?php echo esc_attr( $frase->idfrase ); ?>">
									<th scope="row" class="check-column vr-column-center">
										<input type="checkbox" class="vr-checkbox" data-id="<?php echo esc_attr( $frase->idfrase ); ?>" data-tipo="frases">
									</th>
									<td class="vr-column-center"><?php echo esc_html( $frase->idfrase ); ?></td>
									<td class="column-quote">
										<?php echo esc_html( $frase->frase ); ?>
										<div class="vr-column-center">

										</div>
									</td>
									<td>
										<a class="author-link" title="<?php echo esc_attr__( 'Ver más frases de este autor...', 'vr-frases' ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=vrfr_managefrases&accion=buscar&autor=' . rawurlencode( $frase->autor ) ) ); ?>">
											<?php echo esc_html( $frase->autor ); ?>
										</a>
										<a href="javascript:void(0);" class="search-wikipedia"  
											data-autor="<?php echo esc_html( rawurlencode( str_replace( ' ', '_', $frase->autor ) ) ); ?>" 
											title="<?php esc_attr_e( 'Buscar en Wikipedia', 'vr-frases' ); ?>">
											<span class="dashicons dashicons-external"></span>
										</a>

									</td>
									<td class="vr-column-center">
										<div class="button-group">
											<a href="
											<?php
											echo esc_url(
												add_query_arg(
													array(
														'page' => 'vrfr_managefrases',
														'accion' => 'editar',
														'idfrase' => $frase->idfrase,
														'_wpnonce' => wp_create_nonce( 'vr_nonce_frases' ),
													),
													admin_url( 'admin.php' )
												)
											);
											?>
											"
											class="button"
											title="<?php esc_attr_e( 'Editar esta frase', 'vr-frases' ); ?>">
												<span class="dashicons dashicons-edit"></span>
											</a>
										</div>
									</td>
									<td class="vr-column-center">
										<button
											type="button"
											class="button enabled vr-delete-button"
											title="<?php esc_attr_e( 'Delete this Quote', 'vr-frases' ); ?>"
											data-id="<?php echo esc_attr( $frase->idfrase ); ?>"
											data-tipo="frases"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_frases' ) ); ?>"
										>
											<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; color: #a00;"></span>
										</button>
									</td>
								</tr>
									<?php
							endif;
					endforeach;
						?>
					</tbody>
					<tfoot>
						<tr>
							<th scope="col" id="cb" class="manage-column check-column vr-column-center">
								<input id="cb-select-all-2" title="<?php echo esc_attr__( 'Seleccionar/Deseleccionar todo', 'vr-frases' ); ?>" type="checkbox" onclick="SetAllCheckBoxes('listform', 'ids[]', this.checked);" />
							</th>
							<?php
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo vr_frases_sortable_column_header( __( 'ID', 'vr-frases' ), 'id', $sort_col, $order, 'width: 03%;', 'vr-column-center' );
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo vr_frases_sortable_column_header( __( 'Quote', 'vr-frases' ), 'frase', $sort_col, $order, 'width: 58%;', 'column-primary' );
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo vr_frases_sortable_column_header( __( 'Author', 'vr-frases' ), 'autor', $sort_col, $order, 'width: 15%;' );
							?>
							<th scope="col" class="manage-column vr-column-center"><?php esc_html_e( 'Edit', 'vr-frases' ); ?></th>
							<th scope="col" class="manage-column vr-column-center"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
						</tr>
					</tfoot>
				</table>
				<div class="tablenav bottom">
					<div class="alignleft actions bulkactions">
							<button
								id="vr-delitems-button"
								class="button"
								data-tipo="frases"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_frases' ) ); ?>"
								data-confirm="<?php echo esc_attr( __( 'Are you sure you want to delete these Quotes?', 'vr-frases' ) ); ?>"
							>
								<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; color: #a00;"></span>
								<?php esc_html_e( 'Delete selected', 'vr-frases' ); ?>
							</button>
					</div>
				</div>
			</form>
			</div><!-- end of vr-main-list-container -->
		<?php else : ?>
			<!-- Apply consistent spacing even when there are not quotes. -->
			<div style="margin-top: 20px; clear:both;">
				<div id="message" class="error"><p><?php esc_html_e( 'No quotes to list.', 'vr-frases' ); ?></p></div>
			</div>
		<?php endif; ?>
	</div>
		<?php
}

/**
 * Display add new quote form with enhanced UI.
 *
 * Renders quote creation form with Select2 integration, validation,
 * and AJAX-based submission.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_addnew_frase_form() {
	global $wpdb;
	$form_key   = 'vr_frases_form_' . ( get_current_user_id() > 0 ? get_current_user_id() : 'guest' );
	$pres_frase = '';
	$pres_autor = '';
	?>
	<div class="wrap vr-frases">
			<h1 style="display:flex;align-items:center;gap:12px;">
				<span class="dashicons dashicons-plus-alt" style="font-size:30px;width:30px;height:30px;color:#0073aa;"></span>
				<?php esc_html_e( 'Add New Quote', 'vr-frases' ); ?>
			</h1>

		<form id="addnew_frase" name="addnew_frase" method="post" action="">
			<input name="accion" type="hidden" value="addfrase" />
			<input type="hidden" id="vr_nonce_frases" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_frases' ) ); ?>" />
			<table class="form-table" role="presentation">
				<tr valign="top">
					<th scope="row"><label for="frase"><?php esc_html_e( 'Quote', 'vr-frases' ); ?></label></th>
					<td>
						<textarea name='frase' id='frase' class="large-text" rows="3" required><?php echo esc_textarea( $pres_frase ); ?></textarea>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="autor"><?php esc_html_e( 'Author', 'vr-frases' ); ?></label></th>
					<td>
						<input type='text' name='autor' id='autor' class="regular-text" value="<?php echo esc_attr( $pres_autor ); ?>" required />
					</td>
				</tr>
		</table>
			<p class="submit">
				<button type="submit" class="button button-primary" name="save">
					<span class="dashicons dashicons-plus-alt" style="vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Save', 'vr-frases' ); ?>
				</button>
				<a href="admin.php?page=vrfr_managefrases" class="button" style="margin-left:10px;">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Return', 'vr-frases' ); ?>
				</a>
			</p>

		</form>
	</div>
	<?php
}

/**
 * Display AJAX-based quote editing interface.
 *
 * Renders quote editing form with dynamic data loading,
 * validation, and error handling.
 *
 * @since 4.1.0
 * @param int|string $id The ID of the quote to edit.
 * @return void
 */
function vr_frases_editar_frase( $id = 0 ) {
	$id = absint( $id );
	if ( empty( $id ) || ! is_numeric( $id ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid quote ID.', 'vr-frases' ) . '</p></div>';
		wp_safe_redirect( admin_url( 'admin.php?page=vrfr_managefrases' ) );
		exit;
	}

	// Verify nonce for access (edit link).
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'vr_nonce_frases' ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Error: Invalid access nonce.', 'vr-frases' ) . '</p></div>';
		wp_safe_redirect( admin_url( 'admin.php?page=vrfr_managefrases' ) );
		exit;
	}

	?>
	<div class="wrap vr-frases">
		<h2><?php esc_html_e( 'Edit quote: ', 'vr-frases' ); ?><b><?php echo esc_html( $id ); ?></b></h2>
		
		<div id="vr-edit-quote-loading" class="vr-loading">
			<p><?php esc_html_e( 'Loading quote data...', 'vr-frases' ); ?></p>
		</div>

		<div id="vr-edit-quote-error" class="notice notice-error" style="display: none;">
			<p id="vr-edit-quote-error-message"><?php esc_html_e( 'Error loading quote data.', 'vr-frases' ); ?></p>
		</div>

		<form id="vr-edit-quote-form" style="display: none;">
			<input type="hidden" id="edit-quote-nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_frases' ) ); ?>" />
			<input type="hidden" id="edit-quote-id" value="<?php echo esc_attr( $id ); ?>" />

			<table class="form-table">
				<tr>
					<th><label for="edit-quote-text"><?php esc_html_e( 'Quote', 'vr-frases' ); ?></label></th>
					<td><textarea id="edit-quote-text" rows="3" class="large-text"></textarea></td>
				</tr>
				<tr>
					<th><label for="edit-quote-author"><?php esc_html_e( 'Author', 'vr-frases' ); ?></label></th>
					<td><input type="text" id="edit-quote-author" class="regular-text" /></td>
				</tr>
		</table>

			<p class="submit">
				<button type="button" id="vr-save-quote-btn" class="button button-primary">
					<span class="dashicons dashicons-yes" style="vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Save Changes', 'vr-frases' ); ?>
				</button>
				<button type="button" id="vr-save-quote-loading" class="button button-primary" style="display: none;" disabled>
					<span class="dashicons dashicons-update-alt" style="vertical-align: text-bottom; animation: spin 1s linear infinite;"></span>
					<?php esc_html_e( 'Saving...', 'vr-frases' ); ?>
				</button>
				<a href="admin.php?page=vrfr_managefrases" class="button" style="margin-left:10px;">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Return', 'vr-frases' ); ?>
				</a>
			</p>
		</form>

		<div id="vr-edit-quote-success" class="notice notice-success" style="display: none;">
			<p><?php esc_html_e( 'Quote updated successfully!', 'vr-frases' ); ?></p>
		</div>
	</div>

	<style>
	.vr-loading {
		text-align: center;
		padding: 20px;
		font-style: italic;
	}
	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
	</style>
	<?php
}

/**
 * Check for duplicate quotes in the database.
 *
 * Compares quote text and author combinations. When editing,
 * excludes the current quote to prevent false positives.
 *
 * @since 4.1.0
 * @param string   $frase   The quote text to check.
 * @param string   $autor   The author name to check.
 * @param int|null $idfrase Optional quote ID to exclude when editing.
 * @return bool True if duplicate found, false otherwise.
 */
function vr_frases_comprobar_duplicados( $frase, $autor, $idfrase = null ) {
			global $wpdb;

			// Build query to check for duplicates.
	if ( ! is_null( $idfrase ) ) {
		// Direct query without cache: specific duplicate verification, not critical for performance.
			$frase_existente = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->frases} WHERE frase = %s AND autor = %s AND idfrase != %d",
					$frase,
					$autor,
					$idfrase
				)
			);
	} else {
			$frase_existente = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->frases} WHERE frase = %s AND autor = %s",
					$frase,
					$autor
				)
			);
	}

			// Return true if a duplicate exists, false otherwise.
			return ! empty( $frase_existente );
}

/**
 * AJAX endpoint for adding new quotes.
 *
 * Processes quote creation with validation, duplicate checking,
 * and automatic creation of related entities.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_ajax_add_frase() {
	global $wpdb;

	$frase = isset( $_POST['frase'] ) ? sanitize_text_field( wp_unslash( $_POST['frase'] ) ) : '';
	$autor = isset( $_POST['autor'] ) ? sanitize_text_field( wp_unslash( $_POST['autor'] ) ) : '';
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( empty( $frase ) || empty( $autor ) ) {
		wp_send_json_error( array( 'message' => __( 'Fields cannot be void.', 'vr-frases' ) ) );
	}
	if ( ! wp_verify_nonce( $nonce, 'vr_nonce_frases' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'vr-frases' ) ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'vr-frases' ) ) );
	}
	// Check for duplicates.
	if ( function_exists( 'vr_frases_comprobar_duplicados' ) && vr_frases_comprobar_duplicados( $frase, $autor ) ) {
		wp_send_json_error( array( 'message' => __( 'Error: The Quote already exists.', 'vr-frases' ) ) );
	}
	// Insert quote.
	$resultado = $wpdb->insert(
		$wpdb->frases,
		array(
			'autor' => $autor,
			'frase' => $frase,
		),
		array( '%s', '%s' )
	);
	if ( false !== $resultado ) {
		$idfrase = $wpdb->insert_id;
		// Add author if it doesn't exist.
		$autor_existente = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT idautor FROM {$wpdb->autores} WHERE autor = %s",
				$autor
			)
		);
		if ( null === $autor_existente ) {
			// Use modern AJAX flow to add author.
			if ( function_exists( 'vr_frases_add_items_common_ajax' ) ) {
				$result = vr_frases_add_items_common_ajax( 'autores', array( $autor ), $wpdb );
				// Opcional: manejar $result['success'], $result['messages'] si se requiere feedback.
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
			}
		}
			wp_send_json_success(
				array(
					'message' => __( 'Quote added successfully.', 'vr-frases' ),
					'id'      => $idfrase,
				)
			);
	} else {
		wp_send_json_error(
			array(
				'message' => __( 'Error saving the quote. Please try again.', 'vr-frases' ),
			)
		);
	}
}
add_action( 'wp_ajax_vrfr_add_frase', 'vr_frases_ajax_add_frase' );

/**
 * AJAX endpoint for quick-edit functionality on quotes.
 *
 * Handles inline editing of quote classes and themes
 * with validation and relationship management.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_quick_edit_ajax_frases() {
	if ( ! isset( $_POST['idfrase'], $_POST['nonce'] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Missing required data.', 'vr-frases' ) ) );
	}

	$idfrase = absint( wp_unslash( $_POST['idfrase'] ) );
	$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'vr_nonce_frases' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'vr-frases' ) ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'vr-frases' ) ) );
	}
	if ( empty( $idfrase ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Quote ID is required.', 'vr-frases' ) ) );
	}

	global $wpdb;

	// Get data for the row (data only, no HTML).
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT idfrase, frase, autor FROM {$wpdb->frases} WHERE idfrase = %d", $idfrase ), ARRAY_A );

	wp_send_json_success(
		array(
			'message' => esc_html__( 'Quote updated successfully.', 'vr-frases' ),
			'idfrase' => $row['idfrase'],
			'frase'   => $row['frase'],
			'autor'   => $row['autor'],
		)
	);
}
add_action( 'wp_ajax_vr_frases_quick_edit_frases', 'vr_frases_quick_edit_ajax_frases' );

/**
 * AJAX endpoint to retrieve quote data for editing forms.
 *
 * Fetches quote information, associated themes, and available
 * classes/themes for form population.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_ajax_get_frase_data() {
	// Verify nonce for security.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vr_nonce_frases' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed: invalid nonce.', 'vr-frases' ) ) );
	}

	// Check user permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'vr-frases' ) ) );
	}

	// Get and validate the quote ID.
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	if ( empty( $id ) || ! is_numeric( $id ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid quote ID.', 'vr-frases' ) ) );
	}

	global $wpdb;

	// Get the quote data.
	$frase = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$wpdb->frases} WHERE idfrase = %d", $id ),
		ARRAY_A
	);

	if ( empty( $frase ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Quote not found.', 'vr-frases' ) ) );
	}

	wp_send_json_success(
		array(
			'frase' => $frase,
		)
	);
}
add_action( 'wp_ajax_vr_frases_get_frase_data', 'vr_frases_ajax_get_frase_data' );

/**
 * AJAX endpoint to save edited quote data with comprehensive validation.
 *
 * Processes complete quote updates from AJAX editing forms with full validation
 * pipeline including duplicate checking, security verification, and relationship
 * management. Handles dynamic creation of new classes/themes and maintains
 * data integrity throughout the update process.
 *
 * Update process:
 * - Security nonce and permission validation
 * - Required field validation and sanitization
 * - Duplicate detection (excluding current quote)
 * - Dynamic class creation if needed
 * - Quote data update in database
 * - Author creation if not exists
 * - Theme relationship management
 * - JSON response with success/error status
 *
 * @since 4.1.0
 * @return void Sends JSON response with operation result.
 */
function vr_frases_ajax_save_frase_data() {
	// Verify nonce for security.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vr_nonce_frases' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed: invalid nonce.', 'vr-frases' ) ) );
	}

	// Check user permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'vr-frases' ) ) );
	}

	// Get and validate data.
	$idfrase = isset( $_POST['idfrase'] ) ? absint( $_POST['idfrase'] ) : 0;
	$autor   = isset( $_POST['autor'] ) ? sanitize_text_field( wp_unslash( $_POST['autor'] ) ) : '';
	$frase   = isset( $_POST['frase'] ) ? sanitize_text_field( wp_unslash( $_POST['frase'] ) ) : '';

	// Validate required fields.
	if ( empty( $idfrase ) || empty( $autor ) || empty( $frase ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'All fields are required.', 'vr-frases' ) ) );
	}

	global $wpdb;

	// Check for duplicates (excluding the current ID).
	if ( vr_frases_comprobar_duplicados( $frase, $autor, $idfrase ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Error: A quote with the same text and author already exists.', 'vr-frases' ) ) );
	}

	// Update the quote and author.
	$resultado_frase = $wpdb->update(
		$wpdb->frases,
		array(
			'autor' => $autor,
			'frase' => $frase,
		),
		array( 'idfrase' => $idfrase ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	// Add author to the authors table if it doesn't exist.
	$autor_existente = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT idautor FROM {$wpdb->autores} WHERE autor = %s",
			$autor
		)
	);
	if ( null === $autor_existente ) {
		if ( function_exists( 'vr_frases_add_items_common_ajax' ) ) {
			$result = vr_frases_add_items_common_ajax( 'autores', array( $autor ), $wpdb );
			// Optional: handle $result['success'], $result['messages'] if feedback is required.
		}
	}

	// Return success or error response.
	if ( false !== $resultado_frase ) {
		wp_send_json_success(
			array(
				'message' => esc_html__( 'Quote updated successfully.', 'vr-frases' ),
				'id'      => $idfrase,
			)
		);
	} else {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Error updating the quote. Please try again.', 'vr-frases' ),
			)
		);
	}
}
add_action( 'wp_ajax_vr_frases_save_frase_data', 'vr_frases_ajax_save_frase_data' );