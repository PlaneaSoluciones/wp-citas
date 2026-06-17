<?php
/**
 * VR-Frases Admin Author Management
 *
 * This file contains all functions for managing authors in the admin panel.
 * It handles author listing, editing, creation, filtering, search functionality,
 * and provides comprehensive admin interface for author data management.
 *
 * File organization:
 * - Main controller function with route handling
 * - Author listing with pagination, search, and filtering
 * - Author detail display and frontend integration
 * - Add/Edit author forms and data processing
 * - AJAX endpoints for modern author management
 * - Helper functions for data formatting and validation
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
 * Main controller for author admin actions.
 *
 * Routes GET parameters for different UI views. Form processing
 * is handled via dedicated AJAX endpoints.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_manage_autores() {
	// Get action from GET parameters only (UI routing).
	$accion_raw  = filter_input( INPUT_GET, 'accion', FILTER_UNSAFE_RAW );
	$accion      = null !== $accion_raw ? sanitize_text_field( wp_unslash( $accion_raw ) ) : '';
	$idautor_raw = filter_input( INPUT_GET, 'idautor', FILTER_VALIDATE_INT );
	$idautor     = false === $idautor_raw || null === $idautor_raw ? 0 : absint( $idautor_raw );

	$accion = esc_html( $accion );

	// Route to appropriate UI function based on action.
	switch ( $accion ) {
		case 'addautor':
			if ( function_exists( 'vr_frases_add_autor_form' ) ) {
				vr_frases_add_autor_form();
			}
			break;
		default:
			if ( function_exists( 'vr_frases_listar_autores' ) ) {
				vr_frases_listar_autores();
			}
			break;
	}
}


/**
 * Display comprehensive author management interface.
 *
 * Renders author listing with pagination, search filters, bulk actions,
 * and quick-edit functionality for complete author management.
 *
 * @since 4.1.0
 * @param string $pagina Optional page number to display.
 * @return void
 */
function vr_frases_listar_autores( $pagina = '' ) {
	global $wpdb;

		$nonce_raw = filter_input( INPUT_GET, 'nonce', FILTER_UNSAFE_RAW );
		$nonce     = null !== $nonce_raw ? sanitize_text_field( wp_unslash( $nonce_raw ) ) : '';
	if ( $nonce ) {
		if ( ! wp_verify_nonce( $nonce, 'vr_nonce_autores' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed: invalid nonce.', 'vr-frases' ) . '</p></div>';
			wp_safe_redirect( admin_url( 'admin.php?page=vrfr_manageautores' ) );
			exit;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to perform this action.', 'vr-frases' ) . '</p></div>';
			wp_safe_redirect( admin_url( 'admin.php?page=vrfr_manageautores' ) );
			exit;
		}
	}

	$options    = get_option( 'vr_frases_options' );
	$num_inputs = isset( $options['num_inputs'] ) && $options['num_inputs'] > 0 ? absint( $options['num_inputs'] ) : 20; // Default to 20 if not set or invalid.

	// Read GET parameters using filter_input and validate nonce early.
	$pagina_raw = filter_input( INPUT_GET, 'pagina', FILTER_VALIDATE_INT );
	$pagina     = false === $pagina_raw || null === $pagina_raw ? 1 : max( 1, absint( $pagina_raw ) );

	$filter_raw = filter_input( INPUT_GET, 'filter', FILTER_UNSAFE_RAW );
	$filter     = null !== $filter_raw ? sanitize_text_field( wp_unslash( $filter_raw ) ) : 'all';
	$search_raw = filter_input( INPUT_GET, 'search', FILTER_UNSAFE_RAW );
	$search     = null !== $search_raw ? sanitize_text_field( wp_unslash( $search_raw ) ) : '';

	$orderby_raw   = filter_input( INPUT_GET, 'orderby', FILTER_UNSAFE_RAW );
	$orderby_clean = null !== $orderby_raw ? sanitize_key( wp_unslash( $orderby_raw ) ) : '';
	$orderby       = in_array( $orderby_clean, array( 'autor', 'quotes' ), true ) ? $orderby_clean : 'autor';
	$order_raw     = filter_input( INPUT_GET, 'order', FILTER_UNSAFE_RAW );
	$order_clean   = null !== $order_raw ? strtoupper( sanitize_key( wp_unslash( $order_raw ) ) ) : '';
	$order         = in_array( $order_clean, array( 'ASC', 'DESC' ), true ) ? $order_clean : 'ASC';

	$where = array( '1=1' );

	if ( 'all' === $filter ) {
		$where[] = '1=1';
	} elseif ( 'complete' === $filter ) {
		$where[] = '(pais IS NOT NULL AND pais != "")';
		$where[] = '(nacido IS NOT NULL AND nacido != "0000-00-00")';
		$where[] = '(muerto IS NOT NULL AND muerto != "0000-00-00")';
		$where[] = '(datos IS NOT NULL AND datos != "")';
	} elseif ( 'incomplete' === $filter ) {
		$where[] = '(pais IS NULL OR pais = ""'
			. ' OR nacido IS NULL OR nacido = "0000-00-00"'
			. ' OR muerto IS NULL OR muerto = "0000-00-00"'
			. ' OR datos IS NULL OR datos = "")';
	}

	if ( ! empty( $search ) ) {
		$where[] = $wpdb->prepare( 'autor LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
	}

	$where_clause = implode( ' AND ', $where );

	// Query to count authors, maintaining filters and arguments.
	$sql_count  = "SELECT COUNT(*) FROM {$wpdb->autores} WHERE 1=1";
	$args_count = array();
	if ( 'complete' === $filter ) {
		$sql_count .= " AND (pais IS NOT NULL AND pais != '')";
		$sql_count .= " AND (nacido IS NOT NULL AND nacido != '0000-00-00')";
		$sql_count .= " AND (muerto IS NOT NULL AND muerto != '0000-00-00')";
		$sql_count .= " AND (datos IS NOT NULL AND datos != '')";
	} elseif ( 'incomplete' === $filter ) {
		$sql_count .= " AND (pais IS NULL OR pais = ''"
			. " OR nacido IS NULL OR nacido = '0000-00-00'"
			. " OR muerto IS NULL OR muerto = '0000-00-00'"
			. " OR datos IS NULL OR datos = '')";
	}
	if ( ! empty( $search ) ) {
		$sql_count   .= ' AND autor LIKE %s';
		$args_count[] = '%' . $wpdb->esc_like( $search ) . '%';
	}
	if ( ! empty( $args_count ) ) {
		$registros = (int) $wpdb->get_var( $wpdb->prepare( $sql_count, ...$args_count ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	} else {
		$registros = (int) $wpdb->get_var( $sql_count ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	$inicio = ( $pagina - 1 ) * $num_inputs;
	$sql    = "SELECT * FROM {$wpdb->autores} WHERE 1=1";
	$args   = array();
	if ( 'complete' === $filter ) {
		$sql .= " AND (pais IS NOT NULL AND pais != '')";
		$sql .= " AND (nacido IS NOT NULL AND nacido != '0000-00-00')";
		$sql .= " AND (muerto IS NOT NULL AND muerto != '0000-00-00')";
		$sql .= " AND (datos IS NOT NULL AND datos != '')";
	} elseif ( 'incomplete' === $filter ) {
		$sql .= " AND (pais IS NULL OR pais = ''"
			. " OR nacido IS NULL OR nacido = '0000-00-00'"
			. " OR muerto IS NULL OR muerto = '0000-00-00'"
			. " OR datos IS NULL OR datos = '')";
	}
	if ( ! empty( $search ) ) {
		$sql   .= ' AND autor LIKE %s';
		$args[] = '%' . $wpdb->esc_like( $search ) . '%';
	}
	$sql    .= ' ORDER BY autor ASC LIMIT %d, %d';
	$args[]  = (int) $inicio;
	$args[]  = (int) $num_inputs;
	$autores = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$inicio  = ( $pagina - 1 ) * $num_inputs;
	$paginas = $num_inputs > 0 ? max( 1, ceil( $registros / $num_inputs ) ) : 1; // Prevent division by zero.

	?>
	<div class="wrap vr-frases">
		<h1 style="display:flex;align-items:center;gap:12px;">
			<span class="dashicons dashicons-admin-users" style="font-size:30px;width:30px;height:30px;"></span>
			<?php esc_html_e( 'Manage Authors', 'vr-frases' ); ?>
		</h1>
		<div class="vr-flexbar" style="margin: 20px 0;">
			<div class="vr-flexbar-search">
				<div style="display: flex; align-items: center; gap: 20px;">
					<a href="admin.php?page=vrfr_manageautores&accion=addautor" class="button button-primary" style="display:flex;align-items:center;gap:6px;text-decoration:none;">
						<span class="dashicons dashicons-plus-alt" style="font-size:18px;width:18px;height:18px;"></span>
						<?php esc_html_e( 'Add Author', 'vr-frases' ); ?>
					</a>
					<div style="display: inline-block;">
						<form id="filter-form" method="get" action="">
							<input type="hidden" name="page" value="vrfr_manageautores" />
							<input type="hidden" name="pagina" value="<?php echo esc_attr( $pagina ); ?>" />
							<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_autores' ) ); ?>" />
							<label for="filter"><?php esc_html_e( 'Filter:', 'vr-frases' ); ?></label>
							<select name="filter" id="filter" onchange="this.form.submit();">
								<option value="all" <?php selected( $filter, 'all' ); ?>>
									<?php esc_html_e( 'Show all records', 'vr-frases' ); ?>
								</option>
								<option value="complete" <?php selected( $filter, 'complete' ); ?>>
									<?php esc_html_e( 'Show complete records', 'vr-frases' ); ?>
								</option>
								<option value="incomplete" <?php selected( $filter, 'incomplete' ); ?>>
									<?php esc_html_e( 'Show pending records', 'vr-frases' ); ?>
								</option>
							</select>
						</form>
					</div>
					<div style="display: inline-block;">
						<form method="get" action="">
							<input type="hidden" name="page" value="vrfr_manageautores" />
							<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_autores' ) ); ?>" />
							<label for="search"><?php esc_html_e( 'Search Authors:', 'vr-frases' ); ?></label>
							<input type="text" id="search" name="search" placeholder="<?php esc_attr_e( 'Search authors...', 'vr-frases' ); ?>" value="<?php echo esc_attr( $search ); ?>" oninput="this.form.submit();" />
						</form>
					</div>

				</div>
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
						<input type="hidden" name="page" value="vrfr_manageautores" />
						<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>" />
						<input type="hidden" name="search" value="<?php echo esc_attr( $search ); ?>" />
						<div class="tablenav-pages">
							<?php vr_frases_form_paginar( $pagina, $paginas, $registros, 'top' ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
		$args  = array();
		$where = 'WHERE 1=1';

		if ( 'complete' === $filter ) {
			$where .= " AND (pais IS NOT NULL AND pais != '')";
			$where .= " AND (nacido IS NOT NULL AND nacido != '0000-00-00')";
			$where .= " AND (muerto IS NOT NULL AND muerto != '0000-00-00')";
			$where .= " AND (datos IS NOT NULL AND datos != '')";
		} elseif ( 'incomplete' === $filter ) {
			$where .= " AND (pais IS NULL OR pais = ''"
				. " OR nacido IS NULL OR nacido = '0000-00-00'"
				. " OR muerto IS NULL OR muerto = '0000-00-00'"
				. " OR datos IS NULL OR datos = '')";
		}

		if ( 'quotes' === $orderby ) {
			$sql = "SELECT a.*, (SELECT COUNT(*) FROM {$wpdb->frases} WHERE autor = a.autor) as quote_count FROM {$wpdb->autores} a {$where}";
		} else {
			$sql = "SELECT * FROM {$wpdb->autores} {$where}";
		}

		if ( ! empty( $search ) ) {
			$sql   .= ' AND autor LIKE %s';
			$args[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( 'quotes' === $orderby ) {
			$sql .= " ORDER BY quote_count {$order} LIMIT %d, %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$sql .= " ORDER BY autor {$order} LIMIT %d, %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		$args[] = (int) $inicio;
		$args[] = (int) $num_inputs;

		$autores = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$base_sort_args = array(
			'page'   => 'vrfr_manageautores',
			'filter' => $filter,
			'search' => $search,
			'pagina' => 1,
		);

		if ( 'autor' === $orderby ) {
			$autor_next_order = 'ASC' === $order ? 'desc' : 'asc';
			$autor_th_class   = 'sorted ' . strtolower( $order );
		} else {
			$autor_next_order = 'asc';
			$autor_th_class   = 'sortable asc';
		}
		$autor_sort_url = add_query_arg(
			array_merge(
				$base_sort_args,
				array(
					'orderby' => 'autor',
					'order'   => $autor_next_order,
				)
			),
			admin_url( 'admin.php' )
		);

		if ( 'quotes' === $orderby ) {
			$quotes_next_order = 'ASC' === $order ? 'desc' : 'asc';
			$quotes_th_class   = 'sorted ' . strtolower( $order );
		} else {
			$quotes_next_order = 'desc';
			$quotes_th_class   = 'sortable desc';
		}
		$quotes_sort_url = add_query_arg(
			array_merge(
				$base_sort_args,
				array(
					'orderby' => 'quotes',
					'order'   => $quotes_next_order,
				)
			),
			admin_url( 'admin.php' )
		);

		if ( ! empty( $autores ) ) {
			?>
			<form name="listform" id="listform" class="vr-frases-form autores-list-form" action="" method="post">
				<input name="tipo" type="hidden" value="autores" />
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_autores' ) ); ?>" />
				<table class="wp-list-table vr-autores widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column check-column vr-column-center" style="width: 02%;">
								<input id="cb-select-all-1" title="<?php esc_attr_e( 'Select/Deselect all', 'vr-frases' ); ?>" type="checkbox" onclick="SetAllCheckBoxes('listform', 'ids[]', this.checked);" />
								<label></label>
							</th>
							<th scope="col" class="vr-column-center" style="width: 02%;"><?php esc_html_e( 'ID', 'vr-frases' ); ?></th>
							<th scope="col" class="<?php echo esc_attr( $autor_th_class ); ?>" style="width: 10%;">
								<a href="<?php echo esc_url( $autor_sort_url ); ?>">
									<span><?php esc_html_e( 'Author', 'vr-frases' ); ?></span>
									<span class="sorting-indicators">
										<span class="sorting-indicator asc" aria-hidden="true"></span>
										<span class="sorting-indicator desc" aria-hidden="true"></span>
									</span>
								</a>
							</th>
							<th scope="col" style="width: 10%;"><?php esc_html_e( 'Country', 'vr-frases' ); ?></th>
							<th scope="col" style="width: 10%;"><?php esc_html_e( 'Birth Date', 'vr-frases' ); ?></th>
							<th scope="col" style="width: 10%;"><?php esc_html_e( 'Death Date', 'vr-frases' ); ?></th>
							<th scope="col" style="width: 32%;"><?php esc_html_e( 'Details', 'vr-frases' ); ?></th>
							<th scope="col" class="vr-column-center <?php echo esc_attr( $quotes_th_class ); ?>" style="width: 06%;">
								<a href="<?php echo esc_url( $quotes_sort_url ); ?>">
									<span><?php esc_html_e( 'Quotes', 'vr-frases' ); ?></span>
									<span class="sorting-indicators">
										<span class="sorting-indicator asc" aria-hidden="true"></span>
										<span class="sorting-indicator desc" aria-hidden="true"></span>
									</span>
								</a>
							</th>
							<th scope="col" class="vr-column-center" style="width: 11%;"><?php esc_html_e( 'Edit', 'vr-frases' ); ?></th>
							<th scope="col" class="vr-column-center" style="width: 07%;"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $autores as $autor ) {
							if ( isset( $autor->quote_count ) ) {
								$contador = (int) $autor->quote_count;
							} else {
								$contador = (int) $wpdb->get_var(
									$wpdb->prepare(
										"SELECT COUNT(*) FROM {$wpdb->frases} WHERE autor = %s",
										$autor->autor
									)
								);
							}
							?>
							<tr id="autor-<?php echo esc_attr( $autor->idautor ); ?>">
								<th scope="row" class="check-column">
									<?php if ( 0 === $contador ) { ?>
										<input type="checkbox" name="ids[]" class="vr-checkbox" data-id="<?php echo esc_attr( $autor->idautor ); ?>" data-tipo="autores" value="<?php echo esc_attr( $autor->idautor ); ?>">
									<?php } ?>
								</th>
								<td class="vr-column-center"><?php echo esc_html( $autor->idautor ); ?></td>
								<td>
									<a title="<?php echo esc_attr__( 'View more quotes from this Author...', 'vr-frases' ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=vrfr_managefrases&accion=buscar&autor=' . rawurlencode( $autor->autor ) ) ); ?>">
										<?php echo esc_html( $autor->autor ); ?>
									</a>
									<a href="javascript:void(0);" class="search-wikipedia" data-autor="<?php echo esc_attr( $autor->autor ); ?>" title="<?php esc_attr_e( 'Search on Wikipedia', 'vr-frases' ); ?>">
										<span class="dashicons dashicons-external"></span>
									</a>
								</td>
								<td><?php echo esc_html( $autor->pais ); ?></td>
								<td>
									<?php
									$options      = get_option( 'vr_frases_options' );
									$date_format  = $options['date_format'] ?? 'd/m/Y';
									$ac_bc_format = $options['ac_bc_format'] ?? 'AC';
									if ( '0001-01-01' === $autor->nacido || '' === $autor->nacido || null === $autor->nacido ) {
												echo ''; // Do not show anything.
									} elseif ( 'AC' === $autor->nacido_acdc && '01-01' === substr( $autor->nacido, 5 ) ) {
											echo esc_html( substr( $autor->nacido, 0, 4 ) ) . ' ' . esc_html( $ac_bc_format ); // Show only the year and the suffix.
									} else {
											echo esc_html( gmdate( $date_format, strtotime( $autor->nacido ) ) ); // Show the date in the configured format.
									}
									?>
								</td>
								<td>
									<?php
									if ( '0001-01-01' === $autor->muerto || '' === $autor->muerto || null === $autor->muerto ) {
												echo ''; // Do not show anything.
									} elseif ( 'AC' === $autor->muerto_acdc && '01-01' === substr( $autor->muerto, 5 ) ) {
											echo esc_html( substr( $autor->muerto, 0, 4 ) ) . ' ' . esc_html( $ac_bc_format ); // Show only the year and the suffix.
									} else {
											echo esc_html( gmdate( $date_format, strtotime( $autor->muerto ) ) ); // Show the date in the configured format.
									}
									?>
								</td>
								<td><?php echo esc_html( $autor->datos ); ?></td>
								<td class="vr-column-center"><?php echo esc_html( $contador ); ?></td>
								<td class="vr-column-center">
									<button type="button" class="quick-edit button" 
										data-context="autores" 
										data-id="<?php echo esc_attr( $autor->idautor ); ?>" 
										data-name="<?php echo esc_attr( $autor->autor ?? '' ); ?>" 
										data-pais="<?php echo esc_attr( $autor->pais ?? '' ); ?>" 
										data-nacido="<?php echo esc_attr( $autor->nacido ?? '' ); ?>" 
										data-muerto="<?php echo esc_attr( $autor->muerto ?? '' ); ?>" 
										data-datos="<?php echo esc_attr( $autor->datos ?? '' ); ?>"
										data-contador="<?php echo esc_attr( $contador ); ?>"> 
										<span class="dashicons dashicons-edit" style="vertical-align: text-bottom;"></span>
										<?php esc_html_e( 'Edit Data', 'vr-frases' ); ?>
									</button>
								</td>
								<td class="vr-column-center">
									<?php if ( 0 === $contador ) { ?>
										<button
											type="button"
											class="button vr-delete-button"
											title="<?php esc_attr_e( 'Delete this Author', 'vr-frases' ); ?>"
											data-id="<?php echo esc_attr( $autor->idautor ); ?>"
											data-tipo="autores"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_autores' ) ); ?>"
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
							<th scope="col" class="manage-column check-column">
								<input id="cb-select-all-2" title="<?php esc_attr_e( 'Select/Deselect all', 'vr-frases' ); ?>" type="checkbox" onclick="SetAllCheckBoxes('listform', 'ids[]', this.checked);" />
								<label></label>
							</th>
							<th scope="col" class="vr-column-center" style="width: 01%;"><?php esc_html_e( 'ID', 'vr-frases' ); ?></th>
							<th scope="col" class="<?php echo esc_attr( $autor_th_class ); ?>" width="15%">
								<a href="<?php echo esc_url( $autor_sort_url ); ?>">
									<span><?php esc_html_e( 'Author', 'vr-frases' ); ?></span>
									<span class="sorting-indicators">
										<span class="sorting-indicator asc" aria-hidden="true"></span>
										<span class="sorting-indicator desc" aria-hidden="true"></span>
									</span>
								</a>
							</th>
							<th scope="col" width="15%"><?php esc_html_e( 'Country', 'vr-frases' ); ?></th>
							<th scope="col" width="15%"><?php esc_html_e( 'Birth Date', 'vr-frases' ); ?></th>
							<th scope="col" width="15%"><?php esc_html_e( 'Death Date', 'vr-frases' ); ?></th>
							<th scope="col" width="27%"><?php esc_html_e( 'Details', 'vr-frases' ); ?></th>
							<th scope="col" class="vr-column-center <?php echo esc_attr( $quotes_th_class ); ?>" style="width: 01%;">
								<a href="<?php echo esc_url( $quotes_sort_url ); ?>">
									<span><?php esc_html_e( 'Quotes', 'vr-frases' ); ?></span>
									<span class="sorting-indicators">
										<span class="sorting-indicator asc" aria-hidden="true"></span>
										<span class="sorting-indicator desc" aria-hidden="true"></span>
									</span>
								</a>
							</th>
							<th scope="col" class="vr-column-center" style="width: 07%;"><?php esc_html_e( 'Edit', 'vr-frases' ); ?></th>
							<th scope="col" class="vr-column-center" style="width: 05%;"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
						</tr>
					</tfoot>

				</table>
				<div class="tablenav bottom submit alignleft" style="margin-top: 12px;">
							<button
								id="vr-delitems-button"
								class="button"
								data-tipo="autores"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_autores' ) ); ?>"
								data-confirm="<?php echo esc_attr( __( 'Are you sure you want to delete these Authors?', 'vr-frases' ) ); ?>"
							>
								<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; color: #a00;"></span>
								<?php esc_html_e( 'Delete selected', 'vr-frases' ); ?>
							</button>
					<small>
						<span class="dashicons dashicons-info" style="color: #0073aa;"></span>
						<?php esc_html_e( 'NOTICE: You only can delete authors that do not have related quotes. You can modify them, or go to delete the related quotes before proceed.', 'vr-frases' ); ?>
					</small>
				</div>
			</form>
				<?php } else { ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No authors to list.', 'vr-frases' ); ?></p>
				</div>
			<?php } ?>
		</div>
	<?php
}

/**
 * Display author details in a structured table format.
 *
 * Shows author information with formatted dates, country data,
 * and biographical information.
 *
 * @since 4.1.0
 * @param int $idautor Author ID to display.
 * @return void
 */
function vr_frases_mostrar_autor( $idautor ) {
	global $wpdb;
	$autor = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->autores} WHERE idautor = %d",
			$idautor
		)
	);
	?>
	<div id="vr-author-details" class="autor-info-container vr-author-details-container">
		<h2><?php esc_html_e( 'Author Details', 'vr-frases' ); ?></h2>
		<table class="wp-list-table widefat striped author-details-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'ID', 'vr-frases' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Author', 'vr-frases' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Country', 'vr-frases' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Birth Date', 'vr-frases' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Death Date', 'vr-frases' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Details', 'vr-frases' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Quotes', 'vr-frases' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo esc_html( $autor->idautor ); ?></td>
					<td><?php echo esc_html( $autor->autor ); ?></td>
					<td><?php echo esc_html( $autor->pais ); ?></td>
					<td>
						<?php
						$options      = get_option( 'vr_frases_options' );
						$date_format  = $options['date_format'] ?? 'd/m/Y';
						$ac_bc_format = $options['ac_bc_format'] ?? 'AC';
						if ( '0001-01-01' === $autor->nacido || '' === $autor->nacido || null === $autor->nacido ) {
							echo '';
						} elseif ( 'AC' === $autor->nacido_acdc && '01-01' === substr( $autor->nacido, 5 ) ) {
							echo esc_html( substr( $autor->nacido, 0, 4 ) ) . ' ' . esc_html( $ac_bc_format );
						} else {
							echo esc_html( gmdate( $date_format, strtotime( $autor->nacido ) ) );
						}
						?>
					</td>
					<td>
						<?php
						if ( '0001-01-01' === $autor->muerto || '' === $autor->muerto || null === $autor->muerto ) {
							echo '';
						} elseif ( 'AC' === $autor->muerto_acdc && '01-01' === substr( $autor->muerto, 5 ) ) {
							echo esc_html( substr( $autor->muerto, 0, 4 ) ) . ' ' . esc_html( $ac_bc_format );
						} else {
							echo esc_html( gmdate( $date_format, strtotime( $autor->muerto ) ) );
						}
						?>
					</td>
					<td><?php echo esc_html( $autor->datos ); ?></td>
					<td><?php echo esc_html( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->frases} WHERE autor = %s", $autor->autor ) ) ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Display AJAX-based author creation form.
 *
 * Renders author creation form with AJAX submission,
 * validation, and error handling.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_add_autor_form() {
	?>
	<div class="wrap vr-frases">
		<h2 style="display:flex;align-items:center;gap:12px;">
			<span class="dashicons dashicons-admin-users" style="font-size:24px;width:24px;height:24px;"></span>
			<?php esc_html_e( 'Add New Author', 'vr-frases' ); ?>
		</h2>
		<form id="add-author-form" autocomplete="off">
			<input type="hidden" name="nonce" id="vr_nonce_autores" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_autores' ) ); ?>" />
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="autor-name"><?php esc_html_e( 'Author Name', 'vr-frases' ); ?>:</label>
					</th>
					<td>
						<input type="text" id="autor-name" name="autor" value="" class="regular-text" required />
					</td>
				</tr>
			</table>
			<p class="submit">
				<button type="submit" id="add-author-submit" class="button button-primary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Save', 'vr-frases' ); ?>
				</button>
				<a href="admin.php?page=vrfr_manageautores" class="button" style="margin-left:10px;">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Return', 'vr-frases' ); ?>
				</a>
			</p>
		</form>
		<div id="add-author-message"></div>
	</div>
	<?php
}

/**
 * AJAX endpoint to retrieve author data for editing.
 *
 * Fetches complete author information for AJAX editing forms
 * with security validation.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_ajax_get_autor_data() {
	global $wpdb;
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'vr_nonce_autores' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed: invalid nonce.', 'vr-frases' ) ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'vr-frases' ) ) );
	}
	$idautor_raw = filter_input( INPUT_POST, 'idautor', FILTER_VALIDATE_INT );
	$idautor     = false === $idautor_raw || null === $idautor_raw ? 0 : absint( $idautor_raw );
	if ( 0 === (int) $idautor ) {
		wp_send_json_error( array( 'message' => __( 'Invalid author ID.', 'vr-frases' ) ) );
	}
	$autor = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT idautor, autor, pais, nacido, nacido_acdc, muerto, muerto_acdc, datos FROM {$wpdb->autores} WHERE idautor = %d",
			$idautor
		)
	);
	if ( ! $autor ) {
		wp_send_json_error( array( 'message' => __( 'Author not found.', 'vr-frases' ) ) );
	}
	wp_send_json_success( $autor );
}
add_action( 'wp_ajax_get_autor_data', 'vr_frases_ajax_get_autor_data' );


/**
 * Display author information in an attractive card format for frontend use.
 *
 * Renders a comprehensive author information card with formatted biographical
 * data, dates, country information, and quote statistics. Designed for frontend
 * display with responsive layout and proper date formatting.
 *
 * Card features:
 * - Responsive two-column layout
 * - Formatted birth/death dates with AC/BC support
 * - Country and lifespan information
 * - Quote count with collection statistics
 * - Biographical information display
 * - Conditional field display (only shows available data)
 *
 * @since 4.1.0
 * @param int $idautor Author ID to display. Must be a valid author ID.
 * @return void
 */
function vr_frases_mostrar_autor_frontend( $idautor ) {
	global $wpdb;
	$autor = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->autores} WHERE idautor = %d",
			$idautor
		)
	);

	if ( ! $autor ) {
		return;
	}

	// Get options for date formatting.
	$options      = get_option( 'vr_frases_options' );
	$date_format  = $options['date_format'] ?? 'd/m/Y';
	$ac_bc_format = $options['ac_bc_format'] ?? 'AC';

	// Format birth date.
	$birth_date = '';
	if ( '0001-01-01' !== $autor->nacido && '' !== $autor->nacido && null !== $autor->nacido ) {
		if ( 'AC' === $autor->nacido_acdc && '01-01' === substr( $autor->nacido, 5 ) ) {
			$birth_date = substr( $autor->nacido, 0, 4 ) . ' ' . $ac_bc_format;
		} else {
			$birth_date = gmdate( $date_format, strtotime( $autor->nacido ) );
		}
	}

	// Format death date.
	$death_date = '';
	if ( '0001-01-01' !== $autor->muerto && '' !== $autor->muerto && null !== $autor->muerto ) {
		if ( 'AC' === $autor->muerto_acdc && '01-01' === substr( $autor->muerto, 5 ) ) {
			$death_date = substr( $autor->muerto, 0, 4 ) . ' ' . $ac_bc_format;
		} else {
			$death_date = gmdate( $date_format, strtotime( $autor->muerto ) );
		}
	}

	// Format lifespan.
	$lifespan = '';
	if ( ! empty( $birth_date ) || ! empty( $death_date ) ) {
		$lifespan = $birth_date . ( ! empty( $birth_date ) && ! empty( $death_date ) ? ' - ' : '' ) . $death_date;
	}

	// Get quote count.
	$quote_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->frases} WHERE autor = %s", $autor->autor ) );
	?>
	<div class="vr-author-card">
		<!-- Header row with author name -->
		<div class="vr-author-header">
			<h3><?php esc_html_e( 'Author Information', 'vr-frases' ); ?>: <span class="author-name"><?php echo esc_html( $autor->autor ); ?></span></h3>
		</div>
		
		<!-- Content row with two columns -->
		<div class="vr-author-content">
			<div class="vr-author-left-column">
				<?php if ( ! empty( $autor->pais ) ) : ?>
					<p><strong><?php esc_html_e( 'Country', 'vr-frases' ); ?>:</strong> <?php echo esc_html( $autor->pais ); ?></p>
				<?php endif; ?>
				
				<?php if ( ! empty( $lifespan ) ) : ?>
					<p><strong><?php esc_html_e( 'Lifespan', 'vr-frases' ); ?>:</strong> <?php echo esc_html( $lifespan ); ?></p>
				<?php endif; ?>
				
				<p><strong><?php esc_html_e( 'Quotes in collection', 'vr-frases' ); ?>:</strong> <?php echo esc_html( $quote_count ); ?></p>
			</div>
			
			<div class="vr-author-right-column">
				<?php if ( ! empty( $autor->datos ) ) : ?>
					<p><strong><?php esc_html_e( 'Biography', 'vr-frases' ); ?>:</strong></p>
					<p class="author-biography"><?php echo esc_html( $autor->datos ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * AJAX endpoint for creating new authors.
 *
 * Processes author creation via AJAX with validation and duplicate checking.
 * Supports comma-separated input for bulk creation.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_addnew_autor_ajax() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'vr-frases' ) ) );
		wp_die();
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'vr_nonce_autores' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce.', 'vr-frases' ) ) );
		wp_die();
	}

	$autores_raw = isset( $_POST['autor'] ) ? sanitize_text_field( wp_unslash( $_POST['autor'] ) ) : '';
	if ( empty( $autores_raw ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Error processing Author. Fields cannot be void.', 'vr-frases' ) ) );
		wp_die();
	}

	$autores_array = array_filter( array_map( 'trim', explode( ',', $autores_raw ) ) );

	if ( ! function_exists( 'vr_frases_add_items_common_ajax' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Internal error: common add function not found.', 'vr-frases' ) ) );
		wp_die();
	}

	$result = vr_frases_add_items_common_ajax( 'autores', $autores_array, $GLOBALS['wpdb'] );

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
add_action( 'wp_ajax_vrfr_add_autor', 'vr_frases_addnew_autor_ajax' );

/**
 * AJAX endpoint for quick-edit functionality on authors.
 *
 * Handles inline editing of author fields including biographical data,
 * dates with AC/BC notation, and validation.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_quick_edit_ajax_autores() {
	global $wpdb;
	if ( ! isset( $_POST['idautor'], $_POST['nonce'] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Missing required data.', 'vr-frases' ) ) );
	}
	$idautor     = absint( wp_unslash( $_POST['idautor'] ) );
	$nonce       = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
	$autor       = isset( $_POST['autor'] ) ? sanitize_text_field( wp_unslash( $_POST['autor'] ) ) : '';
	$pais        = isset( $_POST['pais'] ) ? sanitize_text_field( wp_unslash( $_POST['pais'] ) ) : '';
	$nacido      = isset( $_POST['nacido'] ) ? sanitize_text_field( wp_unslash( $_POST['nacido'] ) ) : '';
	$nacido_acdc = isset( $_POST['nacido_acdc'] ) ? sanitize_text_field( wp_unslash( $_POST['nacido_acdc'] ) ) : 'DC';
	$muerto      = isset( $_POST['muerto'] ) ? sanitize_text_field( wp_unslash( $_POST['muerto'] ) ) : '';
	$muerto_acdc = isset( $_POST['muerto_acdc'] ) ? sanitize_text_field( wp_unslash( $_POST['muerto_acdc'] ) ) : 'DC';
	$datos       = isset( $_POST['datos'] ) ? sanitize_text_field( wp_unslash( $_POST['datos'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'vr_nonce_autores' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'vr-frases' ) ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'vr-frases' ) ) );
	}
	if ( empty( $idautor ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'All fields are required.', 'vr-frases' ) ) );
	}
	if ( empty( $autor ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Author name cannot be empty.', 'vr-frases' ) ) );
	}

	// Handle incomplete dates (year only).
	if ( ! empty( $nacido ) && preg_match( '/^\d{4}$/', $nacido ) ) {
		$nacido .= '-01-01';
	}
	if ( ! empty( $muerto ) && preg_match( '/^\d{4}$/', $muerto ) ) {
		$muerto .= '-01-01';
	}

	$updated = $wpdb->update(
		$wpdb->autores,
		array(
			'autor'       => $autor,
			'pais'        => $pais,
			'nacido'      => $nacido,
			'nacido_acdc' => $nacido_acdc,
			'muerto'      => $muerto,
			'muerto_acdc' => $muerto_acdc,
			'datos'       => $datos,
		),
		array( 'idautor' => $idautor ),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Error updating the author. Please check the data.', 'vr-frases' ) ) );
	}

	// Get updated data for the row.
	$autor_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->autores} WHERE idautor = %d", $idautor ) );
	$contador  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->frases} WHERE autor = %s", $autor_obj->autor ) );

	wp_send_json_success(
		array(
			'message'     => esc_html__( 'Author updated successfully.', 'vr-frases' ),
			'idautor'     => $autor_obj->idautor,
			'autor'       => $autor_obj->autor,
			'pais'        => $autor_obj->pais,
			'nacido'      => $autor_obj->nacido,
			'nacido_acdc' => $autor_obj->nacido_acdc,
			'muerto'      => $autor_obj->muerto,
			'muerto_acdc' => $autor_obj->muerto_acdc,
			'datos'       => $autor_obj->datos,
			'contador'    => $contador,
		)
	);
}
add_action( 'wp_ajax_vr_frases_quick_edit_autores', 'vr_frases_quick_edit_ajax_autores' );
