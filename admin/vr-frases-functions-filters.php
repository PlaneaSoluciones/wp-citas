<?php
/**
 * VR-Frases Admin - Filters and Search Helpers
 *
 * Contains all functions related to pagination, search, filtering, ordering, and data
 * retrieval for the admin interface. Provides comprehensive search functionality,
 * secure filtering, and optimized data presentation with proper WordPress integration.
 *
 * File organization:
 * - Pagination functions with WordPress styling
 * - Search and filter processing with security validation
 * - Query building with prepared statements
 * - Form generation with proper escaping
 * - Data retrieval with optimized database queries
 * - Random quote selection with performance optimization
 *
 * Key features:
 * - Advanced pagination with customizable display
 * - Multi-field search (quote text, author, category)
 * - Secure SQL query building with prepared statements
 * - WordPress-styled form generation
 * - Optimized random quote selection
 * - Internationalization support throughout
 * - Proper input validation and sanitization
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

/**
 * Display pagination controls with WordPress native pagination styling.
 *
 * Uses WordPress paginate_links() for better UX with Previous/Next navigation,
 * proper parameter preservation, and enhanced accessibility features.
 *
 * @since 4.1.0
 * @param int    $pagina    Current page number.
 * @param int    $paginas   Total number of pages.
 * @param int    $registros Total number of records.
 * @param string $pos       Position identifier for unique element IDs.
 * @return void
 */
function vr_frases_form_paginar( $pagina, $paginas, $registros, $pos = '' ) {
	if ( $paginas > 1 ) {
		// Read and sanitize common query parameters using filter_input for improved safety.
		$frase_raw   = filter_input( INPUT_GET, 'frase', FILTER_UNSAFE_RAW );
		$autor_raw   = filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW );
		$orden_raw   = filter_input( INPUT_GET, 'orden', FILTER_UNSAFE_RAW );
		$orderby_raw = filter_input( INPUT_GET, 'orderby', FILTER_UNSAFE_RAW );
		$order_raw   = filter_input( INPUT_GET, 'order', FILTER_UNSAFE_RAW );
		$filter_raw  = filter_input( INPUT_GET, 'filter', FILTER_UNSAFE_RAW );
		$page_raw    = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
		$search_raw  = filter_input( INPUT_GET, 'search', FILTER_UNSAFE_RAW );

		// Build current URL for pagination base.
		$base_url   = is_admin() ? admin_url( 'admin.php' ) : home_url();
		$query_args = array(
			'frase'   => null !== $frase_raw ? sanitize_text_field( wp_unslash( $frase_raw ) ) : '',
			'autor'   => null !== $autor_raw ? sanitize_text_field( wp_unslash( $autor_raw ) ) : '',
			'orden'   => null !== $orden_raw ? sanitize_key( wp_unslash( $orden_raw ) ) : '',
			'orderby' => null !== $orderby_raw ? sanitize_key( wp_unslash( $orderby_raw ) ) : '',
			'order'   => null !== $order_raw ? sanitize_key( wp_unslash( $order_raw ) ) : '',
			'filter'  => null !== $filter_raw ? sanitize_text_field( wp_unslash( $filter_raw ) ) : '',
			'search'  => null !== $search_raw ? sanitize_text_field( wp_unslash( $search_raw ) ) : '',
			'page'    => null !== $page_raw ? sanitize_text_field( wp_unslash( $page_raw ) ) : 'vrfr_managefrases',
			'accion'  => 'buscar',
		);

		// Remove empty parameters to clean URLs.
		$query_args = array_filter(
			$query_args,
			function ( $value ) {
				return ! empty( $value );
			}
		);

		if ( $pagina < 1 ) {
			$pagina = 1;
		}

		// Build base URL with parameters (without page number).
		$base_with_args = add_query_arg( $query_args, $base_url );

		// Generate WordPress-style pagination within existing form context.
		if ( $paginas > 0 ) {

			// First page link.
			if ( $pagina > 1 ) {
				$first_url = add_query_arg( array_merge( $query_args, array( 'pagina' => 1 ) ), $base_url );
				echo '<a class="first-page button" href="' . esc_url( $first_url ) . '"><span class="screen-reader-text">' . esc_html__( 'First page', 'vr-frases' ) . '</span><span aria-hidden="true">«</span></a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
			}

			// Previous page link.
			if ( $pagina > 1 ) {
				$prev_url = add_query_arg( array_merge( $query_args, array( 'pagina' => $pagina - 1 ) ), $base_url );
				echo '<a class="prev-page button" href="' . esc_url( $prev_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Previous page', 'vr-frases' ) . '</span><span aria-hidden="true">‹</span></a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
			}
			?>
			<span class="paging-input">
				<label for="current-page-selector-<?php echo esc_attr( $pos ); ?>" class="screen-reader-text"><?php esc_html_e( 'Current Page', 'vr-frases' ); ?></label>
				<input class="current-page" id="current-page-selector-<?php echo esc_attr( $pos ); ?>" type="text" name="pagina" value="<?php echo esc_attr( $pagina ); ?>" size="<?php echo esc_attr( strlen( $paginas ) ); ?>" aria-describedby="table-paging" onkeypress="if(event.key==='Enter' && parseInt(this.value) >= 1 && parseInt(this.value) <= <?php echo esc_js( $paginas ); ?>) this.form.submit();" />
				<span class="tablenav-paging-text">
					<?php
					printf(
						/* translators: %s: total pages */
						esc_html__( ' of %s', 'vr-frases' ),
						'<span class="total-pages">' . esc_html( number_format_i18n( $paginas ) ) . '</span>'
					);
					?>
				</span>
			</span>
			<?php
			// Next page link.
			if ( $pagina < $paginas ) {
				$next_url = add_query_arg( array_merge( $query_args, array( 'pagina' => $pagina + 1 ) ), $base_url );
				echo '<a class="next-page button" href="' . esc_url( $next_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Next page', 'vr-frases' ) . '</span><span aria-hidden="true">›</span></a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
			}

			// Last page link.
			if ( $pagina < $paginas ) {
				$last_url = add_query_arg( array_merge( $query_args, array( 'pagina' => $paginas ) ), $base_url );
				echo '<a class="last-page button" href="' . esc_url( $last_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Last page', 'vr-frases' ) . '</span><span aria-hidden="true">»</span></a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
			}
			?>
			<?php
		}
	}
}

/**
 * Fallback pagination using dropdown selector.
 *
 * Provides original dropdown-style pagination when paginate_links() is not available.
 * Maintains backward compatibility and functionality in all environments.
 *
 * @since 4.1.0
 * @param int    $pagina     Current page number.
 * @param int    $paginas    Total number of pages.
 * @param int    $registros  Total number of records.
 * @param string $pos        Position identifier for unique element IDs.
 * @param array  $query_args Query arguments for URL building.
 * @return void
 */
function vr_frases_form_paginar_fallback( $pagina, $paginas, $registros, $pos, $query_args ) {
	?>
	<div class="tablenav">
		<div class="tablenav-pages">
		<form id="paginar<?php echo esc_attr( $pos ); ?>" action="" method="get" class="paging-input">
			<?php foreach ( $query_args as $name => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php endforeach; ?>
			
			<span class="displaying-num">
				<?php
				$registros_int = absint( $registros );
				printf(
					/* translators: %s: Number of items. */
					esc_html( _n( '%s item', '%s items', $registros_int, 'vr-frases' ) ),
					esc_html( number_format_i18n( $registros_int ) )
				);
				?>
			</span>
			<span class="pagination-links">
				<label for="pagina" class="tablenav-pages-navspan" style="margin-right:8px;">
					<?php // translators: %1$d is the current page number, %2$d is the total number of pages. ?>
					<?php printf( esc_html__( 'Page %1$d of %2$d', 'vr-frases' ), esc_html( $pagina ), esc_html( $paginas ) ); ?>
				</label>
				<select name="pagina" id="pagina" class="page-numbers pagination-selector" onchange="this.form.submit()">
					<?php for ( $i = 1; $i <= $paginas; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $pagina, $i ); ?>><?php echo esc_html( $i ); ?></option>
					<?php endfor; ?>
				</select>
			</span>
		</form>
		</div>
	</div>
	<?php
}

/**
 * Generate titles and messages for search results display.
 *
 * Creates appropriate titles and descriptive messages based on active
 * search parameters for user interface feedback.
 *
 * @since 4.1.0
 * @param array $params Array of search parameters.
 * @return array Array containing title and message strings.
 */
function vr_frases_define_titles( $params ) {
	$msg_parts = array();
	$title     = esc_html__( 'ALL quotes', 'vr-frases' );
	$msg       = esc_html__( 'You are viewing ALL quotes.', 'vr-frases' );

	if ( ! empty( $params ) ) { // Check that $params is not empty.
		$frase   = isset( $params['frase'] ) ? sanitize_text_field( $params['frase'] ) : '';
		$autor   = isset( $params['autor'] ) ? sanitize_text_field( $params['autor'] ) : '';
		$orden   = isset( $params['orden'] ) ? sanitize_key( $params['orden'] ) : '';
		$orderby = isset( $params['orderby'] ) ? sanitize_key( $params['orderby'] ) : '';
		$order   = isset( $params['order'] ) ? sanitize_key( $params['order'] ) : 'asc';

		if ( ! empty( $frase ) ) {
			$msg_parts[] = esc_html__( '[Quote: ', 'vr-frases' ) . esc_html( $frase ) . esc_html__( ']', 'vr-frases' );
		}
		if ( ! empty( $autor ) ) {
			$msg_parts[] = esc_html__( '[Author: ', 'vr-frases' ) . esc_html( $autor ) . esc_html__( ']', 'vr-frases' );
		}
		if ( 'aleatorio' === $orden ) {
			$msg_parts[] = esc_html__( '[Order: ', 'vr-frases' ) . esc_html( vr_frases_get_ordered_message( 'aleatorio' ) ) . esc_html__( ']', 'vr-frases' );
		} elseif ( ! empty( $orderby ) ) {
			$msg_parts[] = esc_html__( '[Order: ', 'vr-frases' ) . esc_html( vr_frases_get_ordered_message( $orderby, $order ) ) . esc_html__( ']', 'vr-frases' );
		}
		if ( empty( $msg_parts ) ) {
			$msg_parts[] = esc_html__( '[ALL Quotes]', 'vr-frases' );
		}
		$msg   = esc_html__( 'You are viewing Search Results for the next criteria: ', 'vr-frases' ) . implode( ' ', $msg_parts );
		$title = esc_html__( 'Search results', 'vr-frases' );
	}

	return array(
		'lista'  => '',
		'titulo' => $title,
		'msg'    => $msg,
	);
}

/**
 * Get human-readable message for current sorting order.
 *
 * Translates order parameter into user-friendly message
 * for display in admin interface.
 *
 * @since 4.1.0
 * @param string $orderby The column to order by.
 * @param string $order   The order direction (asc or desc).
 * @return string Translated sorting message.
 */
function vr_frases_get_ordered_message( $orderby, $order = 'asc' ) {
	if ( 'aleatorio' === $orderby ) {
		return esc_html__( 'in Random Order', 'vr-frases' );
	}
	$col_msgs = array(
		'id'    => esc_html__( 'by ID', 'vr-frases' ),
		'frase' => esc_html__( 'by Quote', 'vr-frases' ),
		'autor' => esc_html__( 'by Author', 'vr-frases' ),
	);
	$col_msg = isset( $col_msgs[ $orderby ] ) ? $col_msgs[ $orderby ] : $col_msgs['frase'];
	$dir_msg = 'desc' === $order ? esc_html__( 'descending', 'vr-frases' ) : esc_html__( 'ascending', 'vr-frases' );
	return esc_html__( 'sorted ', 'vr-frases' ) . $col_msg . ' (' . $dir_msg . ')';
}

/**
 * Generate SQL ORDER BY clause from ordering parameter.
 *
 * Maps user-friendly order parameter to secure SQL ORDER BY clause
 * with validation and safe defaults.
 *
 * @since 4.1.0
 * @param string $orderby The column to order by.
 * @param string $order   The order direction (asc or desc).
 * @return string Safe SQL ORDER BY clause.
 */
function vr_frases_get_order_by( $orderby = 'frase', $order = 'asc' ) {
	if ( 'aleatorio' === $orderby ) {
		return 'RAND()';
	}
	$col_map = array(
		'id'    => 'f.idfrase',
		'frase' => 'f.frase',
		'autor' => 'f.autor',
	);
	$col = isset( $col_map[ $orderby ] ) ? $col_map[ $orderby ] : 'f.frase';
	$dir = 'desc' === strtolower( $order ) ? 'DESC' : 'ASC';
	$clause = $col . ' ' . $dir;
	if ( 'autor' === $orderby ) {
		$clause .= ', f.frase ASC';
	}
	return $clause;
}

/**
 * Retrieve paginated quote data with filtering and ordering.
 *
 * Performs database queries with JOIN operations and proper security
 * to provide paginated quote data for admin display.
 *
 * @since 4.1.0
 *
 * @param int    $pagina     Current page number.
 * @param int    $num_inputs Items per page.
 * @param string $orderby    Column to sort by.
 * @param string $order      Sort direction (asc or desc).
 *
 * @return array Complete paginated data with frases, registros, and paginas.
 */
function vr_frases_get_list_data( $pagina = 1, $num_inputs = 20, $orderby = 'frase', $order = 'asc' ) {
	global $wpdb;

	$filters         = vr_frases_search_filters();
	$where_clause    = $filters['sql'];
	$where_params    = $filters['params'];
	$order_by_clause = vr_frases_get_order_by( $orderby, $order );
	$inicio          = ( 0 < $pagina ) ? ( ( $pagina - 1 ) * $num_inputs ) : 0;

	// Total count.
	$total_sql = "SELECT COUNT(*) FROM {$wpdb->frases} f";
	if ( ! empty( $where_clause ) ) {
		$total_sql .= " WHERE {$where_clause}";
		/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared */
		$registros = $wpdb->get_var( $wpdb->prepare( $total_sql, ...$where_params ) );
	} else {
		/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared */
		$registros = $wpdb->get_var( $total_sql );
	}
	$registros = absint( $registros );
	$paginas   = ceil( $registros / $num_inputs );

	// Main query.
	$sql = "SELECT f.idfrase, f.frase, f.autor FROM {$wpdb->frases} f";
	if ( ! empty( $where_clause ) ) {
		$sql .= " WHERE {$where_clause}";
	}
	if ( ! empty( $order_by_clause ) ) {
		$sql .= " ORDER BY {$order_by_clause}";
	}
	$sql .= ' LIMIT %d OFFSET %d';

	$params = array_merge( $where_params, array( $num_inputs, $inicio ) );

	/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared */
	$frases = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );

	return array(
		'frases'    => $frases,
		'registros' => $registros,
		'paginas'   => $paginas,
	);
}

/**
 * Creates a search query from user inputs in $_GET.
 *
 * Builds a secure SQL WHERE clause and parameters for search filters.
 *
 * @since 4.1.0
 * @return array {
 *     @type string $sql    SQL WHERE clause.
 *     @type array  $params Parameters for prepared statement.
 * }
 */
function vr_frases_search_filters() {
	global $wpdb;

	$where_parts = array();
	$params      = array();

	$frase_raw = filter_input( INPUT_GET, 'frase', FILTER_UNSAFE_RAW );
	$autor_raw = filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW );

	$frase = null !== $frase_raw ? sanitize_text_field( wp_unslash( $frase_raw ) ) : '';
	$autor = null !== $autor_raw ? sanitize_text_field( wp_unslash( $autor_raw ) ) : '';

	if ( ! empty( $frase ) ) {
		$where_parts[] = 'f.frase LIKE %s';
		$params[]      = '%' . $wpdb->esc_like( $frase ) . '%';
	}

	if ( ! empty( $autor ) ) {
		$where_parts[] = 'f.autor LIKE %s';
		$params[]      = '%' . $wpdb->esc_like( $autor ) . '%';
	}

	$where_sql = implode( ' AND ', $where_parts );

	return array(
		'sql'    => $where_sql,
		'params' => $params,
	);
}

/**
 * Generates a search form for the quotes admin page with modern WordPress styling.
 *
 * @since 4.1.0
 * @param string $orden      Optional. Current order parameter.
 * @param string $num_inputs Optional. Number of inputs parameter.
 * @return string HTML output of the search form.
 */
function vr_frases_search_form( $orden = '', $num_inputs = '' ) {
	// Read incoming GET parameters safely.
	$frase_raw   = filter_input( INPUT_GET, 'frase', FILTER_UNSAFE_RAW );
	$autor_raw   = filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW );
	$orden_raw   = filter_input( INPUT_GET, 'orden', FILTER_UNSAFE_RAW );
	$orderby_raw = filter_input( INPUT_GET, 'orderby', FILTER_UNSAFE_RAW );
	$order_raw   = filter_input( INPUT_GET, 'order', FILTER_UNSAFE_RAW );

	$frase       = null !== $frase_raw ? sanitize_text_field( wp_unslash( $frase_raw ) ) : '';
	$autor       = null !== $autor_raw ? sanitize_text_field( wp_unslash( $autor_raw ) ) : '';
	$orderby_val = null !== $orderby_raw ? sanitize_key( wp_unslash( $orderby_raw ) ) : '';
	$order_val   = null !== $order_raw ? sanitize_key( wp_unslash( $order_raw ) ) : 'asc';

	// Prefer the function parameter $orden when provided (for aleatorio), otherwise use GET value.
	$orden_value = '' !== $orden ? sanitize_key( $orden ) : ( null !== $orden_raw ? sanitize_key( wp_unslash( $orden_raw ) ) : '' );

	$query_params = array(
		'frase'   => $frase,
		'autor'   => $autor,
		'orden'   => $orden_value,
		'orderby' => $orderby_val,
		'order'   => $order_val,
	);

	$is_admin   = is_admin();
	$options    = get_option( 'vr_frases_options' );
	$page_value = $is_admin ? 'vrfr_managefrases' : ( isset( $options['page_slug'] ) ? $options['page_slug'] : '' );

	ob_start();
	?>
	<!-- Search container with specific ID and class. -->
	<div id="vr-search-container" class="search-box vr-search-container">
		<form id="frases-searchform" method="get" action="" class="<?php echo $is_admin ? 'search-form' : 'vr-frases-search-form'; ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_value ); ?>">
			<input type="hidden" name="accion" value="buscar">
			<label for="frase" class="screen-reader-text"><?php esc_html_e( 'Quote text:', 'vr-frases' ); ?></label>
			<input type="text" name="frase" id="frase" placeholder="<?php esc_attr_e( 'Quote text', 'vr-frases' ); ?>" value="<?php echo esc_attr( $query_params['frase'] ); ?>" class="search-input" />
			<label for="autor" class="screen-reader-text"><?php esc_html_e( 'Author Name:', 'vr-frases' ); ?></label>
			<input type="text" name="autor" id="autor" placeholder="<?php esc_attr_e( 'Author Name', 'vr-frases' ); ?>" value="<?php echo esc_attr( $query_params['autor'] ); ?>" class="search-input" />
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $query_params['orderby'] ); ?>" />
			<input type="hidden" name="order" value="<?php echo esc_attr( $query_params['order'] ); ?>" />
			<label for="orden" class="screen-reader-text"><?php esc_html_e( 'Order by:', 'vr-frases' ); ?></label>
			<select name="orden" id="orden" class="search-input" onchange="document.getElementById('frases-searchform').submit();">
				<option value="" <?php selected( $query_params['orden'], '' ); ?>><?php esc_html_e( 'Normal Order', 'vr-frases' ); ?></option>
				<option value="aleatorio" <?php selected( $query_params['orden'], 'aleatorio' ); ?>><?php esc_html_e( 'Random Order', 'vr-frases' ); ?></option>
			</select>
			<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'vr-frases' ); ?>" />
			<?php if ( $is_admin ) : ?>
				<button type="button" class="button reset-search" onclick="window.location.href='<?php echo esc_url( admin_url( 'admin.php?page=' . $page_value ) ); ?>'">
					<?php esc_html_e( 'Reset', 'vr-frases' ); ?>
				</button>
			<?php else : ?>
				<button type="button" class="button reset-search" onclick="window.location.href=window.location.pathname;">
					<?php esc_html_e( 'Reset', 'vr-frases' ); ?>
				</button>
			<?php endif; ?>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Selects and formats a random quote with performance optimization.
 *
 * Implements efficient random selection algorithm that avoids the performance
 * penalty of ORDER BY RAND() on large datasets. Uses COUNT() and LIMIT with
 * calculated offset for optimal performance regardless of database size.
 *
 * Features:
 * - Performance-optimized random selection algorithm
 * - Configurable author display options from plugin settings
 * - Author linking with proper URL encoding
 * - Flexible formatting with line separators
 * - Graceful handling of empty databases
 * - Full integration with plugin configuration options
 *
 * Display options (from plugin settings):
 * - Author link toggle (link_autor)
 * - Author position (side_autor: before/after quote)
 * - Author visibility (hide_autor)
 * - Line separator configuration (sep_lines)
 *
 * Used by:
 * - Shortcode [randomfrase] for frontend display
 * - Widget random quote display
 * - Theme integration functions
 *
 * @since 4.1.0
 * @return string Formatted quote with author according to plugin settings, or empty string if no quotes exist.
 */
function vr_frases_random_frase() {
	global $wpdb; // Access WordPress database.
	$options = get_option( 'vr_frases_options' ); // Get configured options.

	// Get the total count first.
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->frases}" );

	// If there are no quotes, return an empty string.
	if ( 0 >= $count ) {
		return '';
	}

	// Get a random offset.
	$random_offset = wp_rand( 0, $count - 1 );

	// Get the quote at that offset. More efficient than ORDER BY RAND().
	$fila = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT frase, autor FROM {$wpdb->frases} LIMIT %d, 1",
			$random_offset
		)
	);

	$sep        = isset( $options['sep_lines'] ) && '1' === $options['sep_lines'] ? '<br />' : ''; // Define line separator based on option.
	$link_autor = isset( $options['link_autor'] ) ? $options['link_autor'] : '0';

	// Format the author part with a link if the option is enabled.
	$autorpart = ( '1' === $link_autor ) ?
		'<a title="' . esc_attr__( 'View more quotes from this Author...', 'vr-frases' ) . '" href="' . esc_url( get_option( 'siteurl' ) . '/' . $options['page_slug'] . '/?autor=' . rawurlencode( $fila->autor ) ) . '"><b><em>' . esc_html( $fila->autor ) . '</em></b></a>' :
		'<b><em>' . esc_html( $fila->autor ) . '</em></b>';

	$side_autor = isset( $options['side_autor'] ) ? $options['side_autor'] : '0';
	$frase      = ( '1' === $side_autor ) ?
		$autorpart . ': ' . $sep . esc_html( $fila->frase ) :
		esc_html( $fila->frase ) . ' ' . $sep . $autorpart;

	if ( isset( $options['hide_autor'] ) && '1' === $options['hide_autor'] ) {
		$frase = esc_html( $fila->frase ); // Escape $frase.
	}

	return $frase; // Return the formatted quote.
}

/**
 * Generate HTML for a sortable column header following WordPress conventions.
 *
 * Produces a <th> with a sort link that toggles ASC/DESC on the active column
 * and defaults to ASC on inactive columns. Uses standard WordPress CSS classes
 * (sortable/sorted + asc/desc) and the .sorting-indicator span.
 *
 * @since 4.2.0
 * @param string $label           Column display text.
 * @param string $column_key      Column key: id, frase, or autor.
 * @param string $current_orderby Active orderby value.
 * @param string $current_order   Active order value (asc|desc).
 * @param string $style           Optional inline style for the <th>.
 * @param string $extra_classes   Optional additional CSS classes.
 * @return string Safe HTML for the <th> element.
 */
function vr_frases_sortable_column_header( $label, $column_key, $current_orderby, $current_order, $style = '', $extra_classes = '' ) {
	$is_sorted  = ( $column_key === $current_orderby );
	$link_order = $is_sorted ? ( 'asc' === $current_order ? 'desc' : 'asc' ) : 'asc';
	$th_class   = 'manage-column ' . ( $is_sorted ? 'sorted ' . $current_order : 'sortable asc' );
	if ( $extra_classes ) {
		$th_class .= ' ' . $extra_classes;
	}

	$url = add_query_arg(
		array(
			'orderby' => $column_key,
			'order'   => $link_order,
			'orden'   => false,
			'pagina'  => false,
		)
	);

	$style_attr = $style ? ' style="' . esc_attr( $style ) . '"' : '';

	return sprintf(
		'<th scope="col" class="%1$s"%2$s><a href="%3$s"><span>%4$s</span><span class="sorting-indicator"></span></a></th>',
		esc_attr( $th_class ),
		$style_attr,
		esc_url( $url ),
		esc_html( $label )
	);
}
