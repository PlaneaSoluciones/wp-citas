<?php
/**
 * VR-Frases Import/Export Management
 *
 * This file contains all functions for importing and exporting quotes and authors.
 * It handles file uploads, AJAX processing, duplicate detection, data validation,
 * and CSV/TXT export functionality.
 *
 * File organization:
 * - Main controller function with tab navigation
 * - Import form rendering and display functions
 * - AJAX endpoints for file processing and data saving
 * - Export functionality with multiple format support
 * - Helper functions for pagination and row rendering
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
 * Main controller for import/export page with tab navigation.
 *
 * Displays the main import/export page with tabbed interface for importing
 * and exporting quotes. Handles nonce verification and form processing.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_manage_import() {
	$active_tab      = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'import';
	$vr_nonce_import = wp_create_nonce( 'vr_nonce_import' );

	?>

	<div class="wrap vr-frases">
		<h1 style="display:flex;align-items:center;gap:12px;">
			<span class="dashicons dashicons-upload" style="font-size: 30px; width: 30px; height: 30px;"></span>
			<?php esc_html_e( 'Manage Import / Export', 'vr-frases' ); ?>
		</h1>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vrfr_manageimport&tab=import' ) ); ?>" class="nav-tab <?php echo ( 'import' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Import Quotes', 'vr-frases' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vrfr_manageimport&tab=export' ) ); ?>" class="nav-tab <?php echo ( 'export' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Export Quotes', 'vr-frases' ); ?>
			</a>
		</h2>

		<?php if ( 'import' === $active_tab ) : ?>
			<?php vr_frases_import_form( $vr_nonce_import ); ?>

			<?php vr_frases_display_imported_data(); ?>

		<?php elseif ( 'export' === $active_tab ) : ?>
			<?php vr_frases_export_form(); ?>
		<?php endif; ?>
	</div>

	<?php
}

/**
 * Display the form for importing CSV or TXT files with quotes and authors.
 *
 * Renders the file upload form with drag-and-drop functionality and validation.
 * Includes support for multiple file selection and file type restrictions.
 *
 * @since 4.1.0
 * @param string $vr_nonce_import Security nonce for the import form.
 * @return void
 */
function vr_frases_import_form( $vr_nonce_import ) {
	?>

	<form method="post" enctype="multipart/form-data" id="import-form">
		<input type="hidden" id="vr_nonce_import" name="vr_nonce_import" value="<?php echo esc_attr( $vr_nonce_import ); ?>" />

		<p>
			<label for="import_files"><?php esc_html_e( 'Upload CSV or TXT files:', 'vr-frases' ); ?></label>
		</p>

		<div id="drop-zone" style="background-color: #FAFAFA; border: 2px dashed #CCC; padding: 40px; text-align: center; cursor: pointer;">
			<?php esc_html_e( 'Drag and drop your CSV or TXT files here, or click to select.', 'vr-frases' ); ?>
			<input type="file" name="import_files[]" id="import_files" accept=".csv,.txt" multiple hidden />
		</div>

		<p id="file-name" style="margin-top: 10px; font-weight: bold;"></p>

		<div>
			<button type="submit" class="button">
				<?php esc_html_e( 'Import', 'vr-frases' ); ?>
			</button>

			<span style="display: inline; margin-left: 20px;">
				<?php esc_html_e( 'Note: The files should have two columns: "Quote" and "Author" separated by commas.', 'vr-frases' ); ?>
			</span>
		</div>
	</form>

	<?php
}

/**
 * AJAX endpoint to process uploaded files and save quotes to the import table.
 *
 * This function handles file uploads via AJAX, providing better UX with progress
 * feedback and error handling. It processes CSV/TXT files, validates content,
 * detects duplicates, and stores valid quotes in the import table for further
 * processing by users.
 *
 * @since 4.1.0
 * @return void Sends JSON response and exits.
 */
function vr_frases_handle_import_ajax() {
	// Verify AJAX request.
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		wp_die( esc_html__( 'Invalid request.', 'vr-frases' ) );
	}

	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vr_nonce_import' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid security token.', 'vr-frases' ) ) );
	}

	// Check user permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'vr-frases' ) ) );
	}

	// Check if files were uploaded.
	if ( ! isset( $_FILES['import_files'] ) || empty( $_FILES['import_files']['name'][0] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'No files uploaded.', 'vr-frases' ) ) );
	}

	global $wpdb;

	// Initialize wp_filesystem.
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();
	global $wp_filesystem;

	// Sanitize uploaded files array.
	$files = wp_unslash( $_FILES['import_files'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	// Sanitize file array fields.
	if ( isset( $files['name'] ) && is_array( $files['name'] ) ) {
		$files['name'] = array_map( 'sanitize_text_field', $files['name'] );
	}
	if ( isset( $files['type'] ) && is_array( $files['type'] ) ) {
		$files['type'] = array_map( 'sanitize_text_field', $files['type'] );
	}
	if ( isset( $files['tmp_name'] ) && is_array( $files['tmp_name'] ) ) {
		$files['tmp_name'] = array_map( 'sanitize_text_field', $files['tmp_name'] );
	}
	if ( isset( $files['error'] ) && is_array( $files['error'] ) ) {
		$files['error'] = array_map( 'absint', $files['error'] );
	}
	if ( isset( $files['size'] ) && is_array( $files['size'] ) ) {
		$files['size'] = array_map( 'absint', $files['size'] );
	}

	$files_count      = ( isset( $files['name'] ) && is_array( $files['name'] ) ) ? count( $files['name'] ) : 0;
	$imported_count   = 0;
	$duplicates_found = array();
	$processed_files  = array();

	// Process each uploaded file.
	for ( $i = 0; $i < $files_count; $i++ ) {
		$filename  = $files['name'][ $i ];
		$file_type = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		// Validate file type.
		if ( ! in_array( $file_type, array( 'csv', 'txt' ), true ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
					/* translators: %s is the filename */
						esc_html__( 'Invalid file type for "%s". Please upload CSV or TXT files only.', 'vr-frases' ),
						esc_html( $filename )
					),
				)
			);
		}

		// Check for upload errors.
		if ( UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
					/* translators: %s is the filename */
						esc_html__( 'Error uploading file "%s".', 'vr-frases' ),
						esc_html( $filename )
					),
				)
			);
		}

		// Read file content.
		$file_content = $wp_filesystem->get_contents( $files['tmp_name'][ $i ] );
		if ( false === $file_content ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
					/* translators: %s is the filename */
						esc_html__( 'Could not read file "%s".', 'vr-frases' ),
						esc_html( $filename )
					),
				)
			);
		}

		// Strip UTF-8 BOM if present.
		$file_content = ltrim( $file_content, "\xEF\xBB\xBF" );

		// Detect separator: TXT exports use tab, CSV exports use comma.
		$separator = ( 'txt' === $file_type ) ? "\t" : ',';

		// Process file lines.
		$lines           = explode( "\n", $file_content );
		$file_imported   = 0;
		$file_duplicates = 0;
		$first_data_line = true;

		foreach ( $lines as $line_number => $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Skip header row produced by the plugin's own export.
			if ( $first_data_line ) {
				$first_data_line = false;
				$lower           = strtolower( $line );
				if ( 'frase,autor' === $lower || '"frase","autor"' === $lower || "frase\tautor" === $lower ) {
					continue;
				}
			}

			$data = str_getcsv( $line, $separator, '"', '\\' );
			if ( ! $data || count( $data ) < 2 ) {
				continue;
			}

			$frase_original = trim( $data[0] );
			$autor_original = trim( $data[1] );

			if ( empty( $frase_original ) || empty( $autor_original ) ) {
				continue;
			}

			// Normalize text for better comparison.
			$frase_normalizada = trim( preg_replace( '/\s+/', ' ', $frase_original ) );
			$autor_normalizado = trim( preg_replace( '/\s+/', ' ', $autor_original ) );

			// Check for duplicates in main frases table (case-insensitive).
			$duplicado_frases = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->frases} WHERE LOWER(TRIM(frase)) = LOWER(%s) AND LOWER(TRIM(autor)) = LOWER(%s)",
					$frase_normalizada,
					$autor_normalizado
				)
			);

			// Check for duplicates in import table to avoid re-importing (case-insensitive).
			$duplicado_import = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}vr_fr_import WHERE LOWER(TRIM(frase)) = LOWER(%s) AND LOWER(TRIM(autor)) = LOWER(%s)",
					$frase_normalizada,
					$autor_normalizado
				)
			);

			if ( $duplicado_frases > 0 || $duplicado_import > 0 ) {
				$duplicates_found[] = array(
					'frase' => $frase_original,
					'autor' => $autor_original,
					'file'  => $filename,
					'line'  => $line_number + 1,
					'type'  => $duplicado_frases > 0 ? 'database' : 'import',
				);
				++$file_duplicates;
				continue;
			}

			// Insert into import table.
			$result = $wpdb->insert(
				$wpdb->prefix . 'vr_fr_import',
				array(
					'frase' => $frase_original,
					'autor' => $autor_original,
				),
				array( '%s', '%s' )
			);

			if ( $result ) {
				++$file_imported;
				++$imported_count;
			}
		}

		$processed_files[] = array(
			'name'        => $filename,
			'imported'    => $file_imported,
			'duplicates'  => $file_duplicates,
			'total_lines' => count( $lines ),
		);
	}

	// Send success response with detailed results.
	wp_send_json_success(
		array(
			'message'          => sprintf(
			/* translators: %d is the number of imported quotes */
				esc_html__( '%d quotes imported successfully.', 'vr-frases' ),
				$imported_count
			),
			'imported_count'   => $imported_count,
			'duplicates_count' => count( $duplicates_found ),
			'duplicates'       => $duplicates_found,
			'processed_files'  => $processed_files,
			'files_count'      => $files_count,
		)
	);
}
add_action( 'wp_ajax_vr_frases_import_files', 'vr_frases_handle_import_ajax' );

/**
 * Check if a quote and author combination is duplicated in the database.
 *
 * This function checks for duplicates in both the main frases table and the
 * import table to prevent duplicate entries during the import process.
 *
 * @since 4.1.0
 * @param string $frase     The quote text to check for duplicates.
 * @param string $autor     The author name to check for duplicates.
 * @param bool   $solo_bool Optional. If true, only returns boolean without message output. Default false.
 * @return bool True if duplicate found, false otherwise.
 */
function vr_frases_check_duplicates( $frase, $autor, $solo_bool = false ) {
	global $wpdb;

	$duplicado_frases = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->frases} WHERE LOWER(frase) = %s AND LOWER(autor) = %s",
			$frase,
			$autor
		)
	);

	$duplicado_import = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}vr_fr_import WHERE LOWER(frase) = %s AND LOWER(autor) = %s",
			$frase,
			$autor
		)
	);

	if ( 0 < $duplicado_frases || 0 < $duplicado_import ) {
		if ( $solo_bool ) {
			return true;
		}
		// Duplicate found, skip this entry (no need to show message for each duplicate during bulk import).
		return true;
	}

	return false;
}

/**
 * Display the imported quotes in a paginated table with editing controls.
 *
 * This function renders the imported quotes table with pagination, allowing users
 * to assign classes and themes to each quote before saving it to the main database.
 * Includes nonce verification for security.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_display_imported_data() {
	$accion_post = isset( $_POST['save_import'] ) || isset( $_POST['delete_import'] );
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && $accion_post ) {
		if ( ! isset( $_POST['vr_nonce_import'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vr_nonce_import'] ) ), 'vr_nonce_import' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Error: Action not allowed. Invalid nonce.', 'vr-frases' ) . '</p></div>';
			$redirect_url = esc_url( admin_url( 'admin.php?page=vrfr_manageimport&tab=import' ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	global $wpdb;

	// Pagination configuration.
	$options        = get_option( 'vr_frases_options' );
	$items_per_page = isset( $options['num_inputs'] ) && $options['num_inputs'] > 0 ? absint( $options['num_inputs'] ) : 20; // Default to 20 if not set or invalid.
	$pagina         = isset( $_GET['pagina'] ) ? absint( $_GET['pagina'] ) : 1;

	// Get total records count from database.
	$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vr_fr_import" );
	$inicio      = ( $pagina > 0 ) ? ( ( $pagina - 1 ) * $items_per_page ) : 0;
	$paginas     = ceil( $total_items / $items_per_page );

	// Get records for current page from database.
	$importados = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}vr_fr_import LIMIT %d OFFSET %d",
			$items_per_page,
			$inicio
		)
	);

	if ( ! empty( $importados ) ) {
		?>

	<div class="vr-flexbar-info" style="margin: 20px 0; justify-content: flex-end;">
		<div class="vr-flexbar-count">
			<span class="frases-num-items search-item">
				<?php
				/* translators: %1$s is the number of items, %2$s is the plural suffix */
				printf(
					/* translators: %1$s is the number of items, %2$s is the plural suffix */
					esc_html__( '%1$s item%2$s found', 'vr-frases' ),
					esc_html( number_format_i18n( $total_items ) ),
					( 1 === (int) $total_items ) ? '' : 's'
				);
				?>
			</span>
		</div>
		<div class="vr-flexbar-actions" style="margin-left: auto; margin-right: 16px;">
			<button
				type="button"
				id="vr-save-all-import-button"
				class="button button-primary"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_import' ) ); ?>"
			>
				<span class="dashicons dashicons-saved" style="vertical-align: text-bottom;"></span>
				<?php esc_html_e( 'Save all', 'vr-frases' ); ?>
			</button>
		</div>
		<div class="vr-flexbar-paging">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="paging-input">
				<input type="hidden" name="page" value="vrfr_manageimport" />
				<input type="hidden" name="tab" value="import" />
				<div class="tablenav-pages">
					<?php vr_frases_form_paginar( $pagina, $paginas, $total_items, 'top' ); ?>
				</div>
			</form>
		</div>
	</div>

	<form id="import-staging-form" method="post" action="">
		<?php wp_nonce_field( 'vr_nonce_import', 'vr_nonce_import' ); ?>

		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th width="50%"><?php esc_html_e( 'Quote', 'vr-frases' ); ?></th>
					<th width="30%"><?php esc_html_e( 'Author', 'vr-frases' ); ?></th>
					<th width="10%"><?php esc_html_e( 'Save', 'vr-frases' ); ?></th>
					<th width="10%"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php
				foreach ( $importados as $item ) {
					vr_frases_render_import_row( $item );
				}
				?>
			</tbody>

			<tfoot>
				<tr>
					<th width="50%"><?php esc_html_e( 'Quote', 'vr-frases' ); ?></th>
					<th width="30%"><?php esc_html_e( 'Author', 'vr-frases' ); ?></th>
					<th width="10%"><?php esc_html_e( 'Save', 'vr-frases' ); ?></th>
					<th width="10%"><?php esc_html_e( 'Delete', 'vr-frases' ); ?></th>
				</tr>
			</tfoot>
		</table>
	</form>

		<?php
	} else {
		echo '<div class="notice notice-info"><p>' . esc_html__( 'No imported quotes found.', 'vr-frases' ) . '</p></div>';
	}
}

/**
 * Render a single row for an imported quote in the import table.
 *
 * This function renders a table row with dropdowns for classes and themes,
 * along with save and delete buttons for each imported quote.
 *
 * @since 4.1.0
 * @param object $item Imported quote object from the database.
 * @return void
 */
function vr_frases_render_import_row( $item ) {
	?>

	<tr>
		<td><?php echo esc_html( $item->frase ); ?></td>
		<td><?php echo esc_html( $item->autor ); ?></td>

		<td>
			<button 
				type="button" 
				class="button vr-save-import-button"
				data-id="<?php echo esc_attr( $item->idimport ); ?>"
				title="<?php esc_attr_e( 'Save this imported quote', 'vr-frases' ); ?>"
			>
				<span class="dashicons dashicons-saved" style="vertical-align: text-bottom; color: #046;"></span>
				<?php esc_html_e( 'Save', 'vr-frases' ); ?>
			</button>
		</td>

		<td>
			<button
				type="button"
				class="button enabled"
				title="<?php esc_attr_e( 'Delete this Import', 'vr-frases' ); ?>"
				data-id="<?php echo esc_attr( $item->idimport ); ?>"
				data-tipo="import"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'vr_nonce_import' ) ); ?>"
			>
				<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; color: #a00;"></span>
				<?php esc_html_e( 'Delete', 'vr-frases' ); ?>
			</button>
		</td>
	</tr>

	<?php
}

/**
 * AJAX endpoint to save imported data to the main quotes database.
 *
 * This function processes individual imported quotes, validates the data,
 * creates new classes/themes if needed, checks for duplicates, and saves
 * the quote to the main database with proper relationships.
 *
 * @since 4.1.0
 * @return void Sends JSON response and exits.
 */
function vr_frases_save_imported_data_ajax() {
	// Verify AJAX request.
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		wp_die( esc_html__( 'Invalid request.', 'vr-frases' ) );
	}

	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vr_nonce_import' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid security token.', 'vr-frases' ) ) );
	}

	// Check required parameters.
	if ( ! isset( $_POST['idimport'] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Missing required data.', 'vr-frases' ) ) );
	}

	global $wpdb;

	$idimport = absint( $_POST['idimport'] );

	// Validate that import record exists.
	$importado = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vr_fr_import WHERE idimport = %d", $idimport ) );
	if ( ! $importado ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Import record not found.', 'vr-frases' ) ) );
	}

	// Check for duplicates in main frases table only (not in import table).
	$frase_normalizada = trim( preg_replace( '/\s+/', ' ', $importado->frase ) );
	$autor_normalizado = trim( preg_replace( '/\s+/', ' ', $importado->autor ) );

	$duplicado_frases = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->frases} WHERE LOWER(TRIM(frase)) = LOWER(%s) AND LOWER(TRIM(autor)) = LOWER(%s)",
			$frase_normalizada,
			$autor_normalizado
		)
	);
	if ( $duplicado_frases > 0 ) {
		wp_send_json_error( array( 'message' => esc_html__( 'This quote already exists in the database.', 'vr-frases' ) ) );
	}

	// Save the quote.
	$result = $wpdb->insert(
		"{$wpdb->frases}",
		array(
			'frase' => $importado->frase,
			'autor' => $importado->autor,
		),
		array( '%s', '%s' )
	);

	if ( false === $result ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Error saving quote to database.', 'vr-frases' ) ) );
	}

	$idfrase = $wpdb->insert_id;

	// Add author if it doesn't exist.
	$autor_existente = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT idautor FROM {$wpdb->autores} WHERE autor = %s",
			$importado->autor
		)
	);
	if ( null === $autor_existente ) {
		if ( function_exists( 'vr_frases_add_items_common_ajax' ) ) {
			vr_frases_add_items_common_ajax( 'autores', array( $importado->autor ), $wpdb );
		}
	}

	// Delete the import record.
	if ( function_exists( 'vr_frases_delete_common' ) ) {
		vr_frases_delete_common( 'import', $idimport );
	}

	wp_send_json_success(
		array(
			'message' => esc_html__( 'Quote saved successfully.', 'vr-frases' ),
			'idfrase' => $idfrase,
		)
	);
}
add_action( 'wp_ajax_vr_frases_save_import', 'vr_frases_save_imported_data_ajax' );

/**
 * AJAX endpoint to save all pending imported quotes to the main database.
 *
 * @since 4.4.0
 * @return void Sends JSON response and exits.
 */
function vr_frases_save_all_imported_ajax() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		wp_die( esc_html__( 'Invalid request.', 'vr-frases' ) );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vr_nonce_import' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid security token.', 'vr-frases' ) ) );
	}

	global $wpdb;

	$importados = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vr_fr_import" );
	if ( empty( $importados ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'No imported quotes found.', 'vr-frases' ) ) );
	}

	$saved  = 0;
	$errors = 0;

	foreach ( $importados as $importado ) {
		$frase_normalizada = trim( preg_replace( '/\s+/', ' ', $importado->frase ) );
		$autor_normalizado = trim( preg_replace( '/\s+/', ' ', $importado->autor ) );

		$duplicado = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->frases} WHERE LOWER(TRIM(frase)) = LOWER(%s) AND LOWER(TRIM(autor)) = LOWER(%s)",
				$frase_normalizada,
				$autor_normalizado
			)
		);

		if ( $duplicado > 0 ) {
			++$errors;
			if ( function_exists( 'vr_frases_delete_common' ) ) {
				vr_frases_delete_common( 'import', $importado->idimport );
			}
			continue;
		}

		$result = $wpdb->insert(
			"{$wpdb->frases}",
			array(
				'frase' => $importado->frase,
				'autor' => $importado->autor,
			),
			array( '%s', '%s' )
		);

		if ( false === $result ) {
			++$errors;
			continue;
		}

		$autor_existente = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT idautor FROM {$wpdb->autores} WHERE autor = %s",
				$importado->autor
			)
		);
		if ( null === $autor_existente && function_exists( 'vr_frases_add_items_common_ajax' ) ) {
			vr_frases_add_items_common_ajax( 'autores', array( $importado->autor ), $wpdb );
		}

		if ( function_exists( 'vr_frases_delete_common' ) ) {
			vr_frases_delete_common( 'import', $importado->idimport );
		}

		++$saved;
	}

	wp_send_json_success(
		array(
			'saved'  => $saved,
			'errors' => $errors,
		)
	);
}
add_action( 'wp_ajax_vr_frases_save_all_import', 'vr_frases_save_all_imported_ajax' );

/**
 * Export all quotes to a CSV or TXT file and send to browser for download.
 *
 * This function handles the export functionality, generating CSV or TXT files
 * with all quotes and authors from the database. Includes UTF-8 BOM for proper
 * encoding and supports optional headers.
 *
 * @since 4.1.0
 * @return void Sends file to browser and exits.
 */
function vr_frases_exportar_csv() {
	global $wpdb;

	// Verify nonce before processing export.
	if ( ! isset( $_POST['vr_nonce_export'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vr_nonce_export'] ) ), 'vr_nonce_export' ) ) {
		wp_die( esc_html__( 'Error: Action not allowed. Invalid nonce.', 'vr-frases' ) );
	}

	// Get filename, extension and header inclusion preference.
	$filename        = isset( $_POST['filename'] ) && ! empty( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : 'export';
	$extension       = isset( $_POST['filetype'] ) && 'txt' === $_POST['filetype'] ? 'txt' : 'csv';
	$include_headers = isset( $_POST['include_headers'] ) && '1' === $_POST['include_headers'];
	$filename       .= '.' . $extension;

	// Set headers for file download with UTF-8 encoding.
	header( 'Content-Type: text/' . ( 'txt' === $extension ? 'plain' : 'csv' ) . '; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	// Add BOM to UTF-8 output.
	echo "\xEF\xBB\xBF";

	// Open standard output as file handle.
	$output = fopen( 'php://output', 'w' );

	// Write file headers if requested.
	if ( $include_headers ) {
		if ( 'csv' === $extension ) {
			fputcsv( $output, array( 'Frase', 'Autor' ), ',', '"' );
		} elseif ( 'txt' === $extension ) {
			fwrite( $output, "\"Frase\"\t\"Autor\"\n" );
		}
	}

	// Get all quotes from database.
	$frases = $wpdb->get_results( "SELECT frase, autor FROM {$wpdb->frases}", ARRAY_A );

	// Write data to file.
	foreach ( $frases as $frase ) {
		$frase_texto = $frase['frase']; // Remove manual quotes.
		$autor_texto = $frase['autor']; // Remove manual quotes.
		if ( 'csv' === $extension ) {
			fputcsv( $output, array( $frase_texto, $autor_texto ), ',', '"' );
		} elseif ( 'txt' === $extension ) {
			fwrite( $output, '"' . $frase_texto . '"' . "\t" . '"' . $autor_texto . '"' . "\n" );
		}
	}

	// Close output stream.
	fclose( $output );

	// End execution to avoid additional content.
	exit;
}
add_action( 'admin_post_vr_frases_exportar_csv', 'vr_frases_exportar_csv' );

/**
 * Render the export form for quotes with format and options selection.
 *
 * This function displays the export form allowing users to choose filename,
 * file format (CSV/TXT), and whether to include column headers.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_export_form() {
	$frases = vr_frases_total_frases();
	$nonce  = wp_create_nonce( 'vr_nonce_export' );
	?>

	<h3><?php esc_html_e( 'Export Quotes', 'vr-frases' ); ?></h3>

	<div class="alignleft" style="margin-top: 20px;">
		<h2>
			<?php esc_html_e( 'Total number of quotes in your database: ', 'vr-frases' ); ?>
			<?php echo esc_html( $frases ); ?>
		</h2>
	</div>

	<br /><br />

	<p><?php esc_html_e( 'Enter a filename or leave it blank to use the default (export):', 'vr-frases' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="vr_frases_exportar_csv" />
		<input type="hidden" name="vr_nonce_export" value="<?php echo esc_attr( $nonce ); ?>" />

		<input type="text" name="filename" placeholder="export" style="width: 300px;" />

		<br /><br />

		<p><?php esc_html_e( 'Select file type:', 'vr-frases' ); ?></p>

		<label>
			<input type="radio" name="filetype" value="csv" checked />
			<?php esc_html_e( 'CSV', 'vr-frases' ); ?>
		</label>

		<label style="margin-left: 20px;">
			<input type="radio" name="filetype" value="txt" />
			<?php esc_html_e( 'TXT', 'vr-frases' ); ?>
		</label>

		<br /><br />

		<label>
			<input type="checkbox" name="include_headers" value="1" />
			<?php esc_html_e( 'Include headers', 'vr-frases' ); ?>
		</label>

		<br /><br />

		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Export Quotes', 'vr-frases' ); ?>
		</button>

		<span style="display: inline; margin-left: 20px;">
			<?php esc_html_e( 'Note: The created file will have two columns "Frase" and "Autor" separated by commas.', 'vr-frases' ); ?>
		</span>
	</form>

	<?php
}
