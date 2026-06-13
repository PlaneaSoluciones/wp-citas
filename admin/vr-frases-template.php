<?php
/**
 * VR-Frases Frontend Template Engine and Display System
 *
 * This file provides comprehensive frontend template functionality for displaying
 * quotes with advanced user interface features. It handles responsive design,
 * user preferences, search capabilities, pagination, and interactive quote cards
 * with professional styling and WordPress integration standards.
 *
 * Key Features:
 * - Responsive quote grid layout with card-based design
 * - Advanced user preferences (style themes, font sizes, pagination)
 * - Real-time search with author, quote, and category filtering
 * - Intelligent pagination with configurable items per page
 * - Interactive author information cards with Wikipedia integration
 * - Cookie-based preference persistence for enhanced UX
 * - Multiple visual themes (standard, dark, elegant, classic, minimalist)
 * - Accessibility features with proper ARIA labels and semantic HTML
 *
 * Template Components:
 * - Main template orchestrator with preference management
 * - Preferences bar with style and display customization
 * - Search interface with advanced filtering capabilities
 * - Pagination controls with responsive design
 * - Quote card grid with author attribution and categorization
 * - Author information display with biographical integration
 *
 * Technical Implementation:
 * - WordPress security standards with nonce verification
 * - Responsive CSS Grid layout for optimal display
 * - JavaScript integration for interactive preferences
 * - Cookie management for user preference persistence
 * - WordPress i18n support for multi-language compatibility
 * - SEO-friendly markup with proper heading structure
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
 * Render main view for VR-Frases frontend display.
 *
 * Constructs the complete frontend interface with preferences, search,
 * pagination, and quote grid. Handles user preferences through cookies
 * and GET parameters with proper sanitization.
 *
 * @since 4.1.0
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return string Complete rendered HTML for the quotes interface.
 */
function vr_frases_show_main() {
	ob_start();
	global $wpdb;
	$nonce_valid = false;
	$nonce       = isset( $_GET['vr_frases_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['vr_frases_nonce'] ) ) : '';
	if ( $nonce && wp_verify_nonce( $nonce, 'vr_frases_preferences' ) ) {
		$nonce_valid = true;
	}
	$options            = get_option( 'vr_frases_options' );
	$default_num_inputs = isset( $options['num_inputs'] ) && $options['num_inputs'] > 0 ? absint( $options['num_inputs'] ) : 20; // Default to 20 if not set or invalid.

	// Read GET parameters safely using filter_input and sanitize.
	$get_font_size = filter_input( INPUT_GET, 'font_size', FILTER_UNSAFE_RAW );
	$get_num       = filter_input( INPUT_GET, 'num_inputs', FILTER_UNSAFE_RAW );
	$get_pagina    = filter_input( INPUT_GET, 'pagina', FILTER_UNSAFE_RAW );
	$get_frase  = filter_input( INPUT_GET, 'frase', FILTER_UNSAFE_RAW );
	$get_autor  = filter_input( INPUT_GET, 'autor', FILTER_UNSAFE_RAW );
	$get_orden  = filter_input( INPUT_GET, 'orden', FILTER_UNSAFE_RAW );

	$font_size = 'default';
	if ( $nonce_valid && null !== $get_font_size && '' !== $get_font_size ) {
		$font_size = sanitize_text_field( wp_unslash( $get_font_size ) );
	} elseif ( isset( $_COOKIE['vr_frases_font_size'] ) ) {
		$font_size = sanitize_text_field( wp_unslash( $_COOKIE['vr_frases_font_size'] ) );
	}

	$num_inputs = $default_num_inputs;
	if ( $nonce_valid && null !== $get_num && is_numeric( $get_num ) ) {
		$num_inputs = absint( wp_unslash( $get_num ) );
	} elseif ( isset( $_COOKIE['vr_frases_num_inputs'] ) && is_numeric( $_COOKIE['vr_frases_num_inputs'] ) ) {
		$num_inputs = absint( wp_unslash( $_COOKIE['vr_frases_num_inputs'] ) );
	}

	$pagina = 1;
	if ( null !== $get_pagina && is_numeric( $get_pagina ) && absint( $get_pagina ) > 0 ) {
		$pagina = absint( wp_unslash( $get_pagina ) );
	}

	// Determine order: random by default if not active search.
	$has_search_params = ( null !== $get_frase && '' !== $get_frase ) || ( null !== $get_autor && '' !== $get_autor );
	$orden             = 'aleatorio';
	if ( null !== $get_orden && '' !== $get_orden ) {
		$orden = sanitize_text_field( wp_unslash( $get_orden ) );
	} elseif ( $has_search_params ) {
		$orden = 'porfrase';
	}

	$frase = null !== $get_frase ? sanitize_text_field( wp_unslash( $get_frase ) ) : '';
	$autor = null !== $get_autor ? sanitize_text_field( wp_unslash( $get_autor ) ) : '';

	$data      = vr_frases_get_list_data( $pagina, $num_inputs, $orden );
	$frases    = $data['frases'];
	$registros = $data['registros'];
	$paginas   = $data['paginas'];

	// Display the user interface.
	?>
	<div class="wrap <?php echo 'font-size-' . esc_attr( $font_size ); ?>">
		<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
			<h1 style="margin: 0;"><?php esc_html_e( 'List of Quotes', 'vr-frases' ); ?></h1>
			<button type="button" id="toggle-preferences" class="toggle-btn" style="margin-left: auto;">
				<?php esc_html_e( 'Show preferences', 'vr-frases' ); ?> <span id="toggle-icon">&#9650;</span>
			</button>
		</div>
		<div class="vr-frases-preferences-toggle">
			<?php vr_frases_render_preferences_bar( $font_size, $num_inputs ); ?>
		</div>
	<h3><span class="search-item">
	<?php
	echo esc_html(
		vr_frases_define_titles(
			array(
				'frase' => $frase,
				'autor' => $autor,
				'orden' => $orden,
			)
		)['msg']
	);
	?>
									</span></h3>
	<?php vr_frases_render_pagination_bar( $pagina, $paginas, $registros ); ?>
	<?php vr_frases_render_search_bar( $orden, $num_inputs ); ?>

		<?php
		if ( '' !== $autor ) {
			$idautor = $wpdb->get_var( $wpdb->prepare( "SELECT idautor FROM {$wpdb->autores} WHERE autor = %s", $autor ) );
			if ( $idautor && function_exists( 'vr_frases_mostrar_autor_frontend' ) ) {
				vr_frases_mostrar_autor_frontend( $idautor ); // Display author information in card format.
			}
		}
		?>

		<?php
		if ( ! empty( $frases ) ) {
			vr_frases_block_listado_frases( $frases, $options, $pagina, $paginas, $registros );
		} else {
			echo '<div id="message" class="error"><p>' . esc_html__( 'No quotes to list.', 'vr-frases' ) . '</p></div>';
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render user preferences control panel.
 *
 * Creates a preferences interface for customizing quote display with
 * visual themes, font sizes, and pagination settings. Uses responsive
 * flexbox design with automatic form submission.
 *
 * @since 4.1.0
 * @param string $style      Currently selected visual theme.
 * @param string $font_size  Currently selected font size.
 * @param int    $num_inputs Currently selected records per page.
 * @return void Outputs HTML directly to browser.
 */
function vr_frases_render_preferences_bar( $font_size, $num_inputs ) {
	?>
	<form method="get" id="vr-frases-preferences" style="margin-bottom:30px;width:100%;">
		<div class="vr-frases-preferences-bar">
				<h2><?php esc_html_e( 'Preferences:', 'vr-frases' ); ?></h2>
				<label for="font_size"><?php esc_html_e( 'Font size:', 'vr-frases' ); ?></label>
			<select name="font_size" id="font_size" onchange="this.form.submit()">
						<option value="default" <?php selected( $font_size, 'default' ); ?>><?php esc_html_e( 'Default', 'vr-frases' ); ?></option>
						<option value="small" <?php selected( $font_size, 'small' ); ?>><?php esc_html_e( 'Small', 'vr-frases' ); ?></option>
						<option value="medium" <?php selected( $font_size, 'medium' ); ?>><?php esc_html_e( 'Medium', 'vr-frases' ); ?></option>
						<option value="large" <?php selected( $font_size, 'large' ); ?>><?php esc_html_e( 'Large', 'vr-frases' ); ?></option>
			</select>
				<label for="num_inputs"><?php esc_html_e( 'Records per page:', 'vr-frases' ); ?></label>
			<input type="number" name="num_inputs" id="num_inputs" value="<?php echo esc_attr( $num_inputs ); ?>" min="1" max="999" onchange="this.form.submit()" />
		</div>
	</form>
	<?php
}

/**
 * Render pagination interface with record count display.
 *
 * Creates pagination controls with record count statistics and
 * navigation elements in a responsive design.
 *
 * @since 4.1.0
 * @param int $pagina    Current page number.
 * @param int $paginas   Total number of pages.
 * @param int $registros Total number of records found.
 * @return void Outputs HTML directly to browser.
 */
function vr_frases_render_pagination_bar( $pagina, $paginas, $registros ) {
	?>
	<div class="vr-frases-pagination-bar">
		<h3><span class="search-item">
			<?php
			printf(
				/* translators: %1$s: number of items found, %2$s: plural suffix ('s' or empty string) */
				esc_html__( '%1$s item%2$s found', 'vr-frases' ),
				esc_html( number_format_i18n( $registros ) ),
				( 1 === (int) $registros ) ? '' : 's'
			);
			?>
		</span></h3>
		<?php if ( $paginas > 1 ) : ?>
		<div class="vr-frases-pagination-controls">
			<?php vr_frases_frontend_pagination( $pagina, $paginas ); ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render search interface for quote filtering.
 *
 * Creates a search control panel for filtering quotes by text, author,
 * category, and sort order with responsive design.
 *
 * @since 4.1.0
 * @param string $orden      Current sort order preference.
 * @param int    $num_inputs Number of records per page.
 * @return void Outputs HTML search interface.
 */
function vr_frases_render_search_bar( $orden, $num_inputs ) {
	?>
	<div class="vr-frases-search-bar">
		<?php
		/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		echo vr_frases_search_form( $orden, $num_inputs );
		?>
	</div>
	<?php
}

/**
 * Render frontend-specific pagination controls for quote navigation.
 *
 * Creates pagination controls optimized for the frontend template system,
 * handling URL generation, parameter preservation, and responsive design.
 * This function is specifically designed for the public-facing quote display
 * and differs from the admin pagination system.
 *
 * @since 4.1.0
 * @param int $pagina    Current page number (1-based indexing).
 * @param int $paginas   Total number of pages available.
 * @return void Outputs HTML pagination controls directly to browser.
 */
function vr_frases_frontend_pagination( $pagina, $paginas ) {
	if ( $paginas <= 1 ) {
		return;
	}

	// Get current URL and preserve parameters.
	$current_url = home_url( add_query_arg( null, null ) );
	$base_url    = strtok( $current_url, '?' );

	// Get current parameters safely.
	$current_params  = array();
	$preserve_params = array( 'font_size', 'num_inputs', 'frase', 'autor', 'orden' );

	foreach ( $preserve_params as $param ) {
		$param_value = filter_input( INPUT_GET, $param, FILTER_UNSAFE_RAW );
		if ( null !== $param_value && '' !== $param_value ) {
			$current_params[ $param ] = sanitize_text_field( wp_unslash( $param_value ) );
		}
	}

	// Add nonce for preferences.
	if ( ! empty( $current_params ) ) {
		$current_params['vr_frases_nonce'] = wp_create_nonce( 'vr_frases_preferences' );
	}

	?>
	<div class="vr-frases-pagination-nav">
		<?php
		// First page link.
		if ( $pagina > 1 ) {
			$first_url = add_query_arg( array_merge( $current_params, array( 'pagina' => 1 ) ), $base_url );
			echo '<a class="vr-pagination-button first-page" href="' . esc_url( $first_url ) . '" title="' . esc_attr__( 'First page', 'vr-frases' ) . '">«</a>';
		} else {
			echo '<span class="vr-pagination-button disabled">«</span>';
		}

		// Previous page link.
		if ( $pagina > 1 ) {
			$prev_url = add_query_arg( array_merge( $current_params, array( 'pagina' => $pagina - 1 ) ), $base_url );
			echo '<a class="vr-pagination-button prev-page" href="' . esc_url( $prev_url ) . '" title="' . esc_attr__( 'Previous page', 'vr-frases' ) . '">‹</a>';
		} else {
			echo '<span class="vr-pagination-button disabled">‹</span>';
		}
		?>

		<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="vr-pagination-form" style="display: inline-block;">
			<?php
			// Add hidden fields for current parameters.
			foreach ( $current_params as $param => $value ) {
				printf(
					'<input type="hidden" name="%s" value="%s" />',
					esc_attr( $param ),
					esc_attr( $value )
				);
			}
			?>
			<span class="paging-input">
				<label for="current-page-selector-frontend" class="screen-reader-text"><?php esc_html_e( 'Current Page', 'vr-frases' ); ?></label>
				<input class="current-page" id="current-page-selector-frontend" type="text" name="pagina" value="<?php echo esc_attr( $pagina ); ?>" size="<?php echo esc_attr( strlen( $paginas ) ); ?>" aria-describedby="table-paging" onkeypress="if(event.key==='Enter' && parseInt(this.value) >= 1 && parseInt(this.value) <= <?php echo esc_js( $paginas ); ?>) this.form.submit();" onblur="if(parseInt(this.value) >= 1 && parseInt(this.value) <= <?php echo esc_js( $paginas ); ?>) this.form.submit();" />
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
		</form>

		<?php
		// Next page link.
		if ( $pagina < $paginas ) {
			$next_url = add_query_arg( array_merge( $current_params, array( 'pagina' => $pagina + 1 ) ), $base_url );
			echo '<a class="vr-pagination-button next-page" href="' . esc_url( $next_url ) . '" title="' . esc_attr__( 'Next page', 'vr-frases' ) . '">›</a>';
		} else {
			echo '<span class="vr-pagination-button disabled">›</span>';
		}

		// Last page link.
		if ( $pagina < $paginas ) {
			$last_url = add_query_arg( array_merge( $current_params, array( 'pagina' => $paginas ) ), $base_url );
			echo '<a class="vr-pagination-button last-page" href="' . esc_url( $last_url ) . '" title="' . esc_attr__( 'Last page', 'vr-frases' ) . '">»</a>';
		} else {
			echo '<span class="vr-pagination-button disabled">»</span>';
		}
		?>
	</div>
	<?php
}

/**
 * Render quotes display grid with interactive card layout.
 *
 * Creates a responsive CSS Grid layout displaying quotes as cards with
 * author attribution, category metadata, and interactive elements including
 * Wikipedia search links and author filtering.
 *
 * @since 4.1.0
 * @param array $frases  Array of quote objects with metadata.
 * @param array $options Plugin configuration options.
 * @return void Outputs complete HTML grid interface.
 */
function vr_frases_block_listado_frases( $frases, $options ) {
	?>
	<form name="listform" id="listform" class="vr-frases-form template-list-form" action="" method="post">
		<input name="tipo" type="hidden" value="frases" />
		<input type="hidden" id="vr_nonce_frases" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_frases' ) ); ?>" />
		<div class="vr-frases-grid">
			<?php
			foreach ( $frases as $frase ) :
				if ( is_object( $frase ) && isset( $frase->idfrase ) ) :
					?>
					<div class="frase-card">
						<div class="frase-quote"><?php echo esc_html( $frase->frase ); ?></div>
						<div class="frase-meta">
							<span class="frase-author">
								<a title="<?php echo esc_attr__( 'View more quotes from this Author...', 'vr-frases' ); ?>" href="<?php echo esc_url( home_url( '/' . $options['page_slug'] . '/?autor=' . rawurlencode( $frase->autor ) ) ); ?>">
									<?php echo esc_html( $frase->autor ); ?>
								</a>
								<a href="javascript:void(0);" class="search-wikipedia" data-autor="<?php echo esc_html( $frase->autor ); ?>" title="<?php esc_attr_e( 'Search on Wikipedia', 'vr-frases' ); ?>">
									<span class="dashicons dashicons-external"></span>
								</a>
							</span>
								</div>
					</div>
					<?php
				endif;
			endforeach;
			?>
		</div>
	</form>
	<?php
}

// Global Plugin options.
$vr_frases_options = get_option( 'vr_frases_options' );

/**
 * Enqueue JavaScript for interactive preferences and enhanced UX.
 *
 * Loads JavaScript for preferences panel toggling, Wikipedia search
 * integration, and pagination functionality with localization support.
 *
 * @since 4.1.0
 * @return void Enqueues JavaScript assets for frontend functionality.
 */
function vr_frases_enqueue_template_js() {
	wp_enqueue_script(
		'vr-frases-template-js',
		plugin_dir_url( __DIR__ ) . 'assets/js/vr-frases-template.js',
		array(),
		gmdate( 'YmdHis' ),
		true
	);
	// Locate chains for JS.
	wp_localize_script(
		'vr-frases-template-js',
		'vrFrasesPrefs',
		array(
			'show' => esc_html__( 'Show preferences', 'vr-frases' ),
			'hide' => esc_html__( 'Hide preferences', 'vr-frases' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'vr_frases_enqueue_template_js' );

/**
 * Enqueue frontend styles for quote display.
 *
 * Loads the standard CSS stylesheet for frontend quote display,
 * handling style conflicts and providing responsive design.
 *
 * @since 4.1.0
 * @return void Enqueues CSS assets for frontend display.
 */
function vr_frases_enqueue_frontend_assets() {
	wp_dequeue_style( 'vr-frases' );
	wp_deregister_style( 'vr-frases' );
	wp_enqueue_style(
		'vr-frases-standard',
		plugin_dir_url( __DIR__ ) . 'assets/css/vr-frases-standard.css',
		array(),
		gmdate( 'YmdHis' )
	);
}
add_action( 'wp_enqueue_scripts', 'vr_frases_enqueue_frontend_assets' );
