<?php
/**
 * VR-Frases Database Management System
 *
 * This file provides comprehensive database management for the VR-Frases plugin,
 * handling table creation, data initialization, version upgrades, and legacy
 * migrations. It ensures database integrity and smooth plugin operation across
 * different WordPress installations and plugin versions.
 *
 * Database architecture:
 * - Quotes table (frases) with author, class, and timestamp tracking
 * - Authors table (autores) with biographical information and dates
 * - Classes table (clases) for quote categorization
 * - Themes table (temas) with slug support for advanced filtering
 * - Taxonomies table (taxos) for many-to-many quote-theme relationships
 * - Import table for tracking bulk import operations
 *
 * Key functionalities:
 * - Safe table creation and updates using WordPress dbDelta
 * - Foreign key constraints for referential data integrity
 * - Version-specific upgrade procedures and data migrations
 * - Initial data insertion with default classes and sample content
 * - Legacy format support and automatic data migration
 * - Controlled upgrade execution with transient locks
 *
 * @package     VR_Frases
 * @author      Vicente Ruiz Gálvez
 * @version     4.1.0
 * @license     GPL-2.0+
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define global table references for easier access throughout the plugin.
global $wpdb;
$wpdb->frases  = $wpdb->prefix . 'vr_fr_frases';   // Quotes table.
$wpdb->autores = $wpdb->prefix . 'vr_fr_autores';  // Authors table.
$wpdb->clases  = $wpdb->prefix . 'vr_fr_clases';   // Classes table.
$wpdb->temas   = $wpdb->prefix . 'vr_fr_temas';    // Themes table.
$wpdb->taxos   = $wpdb->prefix . 'vr_fr_taxos';    // Taxonomies table.
$wpdb->import  = $wpdb->prefix . 'vr_fr_import';   // Import table.

/**
 * Creates or updates plugin database tables
 *
 * This function defines and creates all required database tables for the plugin.
 * It uses WordPress dbDelta function to safely create or modify tables
 * without data loss.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_new_first() {
	global $wpdb;
	// Include WordPress database upgrade functions.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Define table creation SQL statements with improved structure.
	$tables = array(
		'frases'  => "CREATE TABLE {$wpdb->frases} (
            idfrase int(11) NOT NULL AUTO_INCREMENT,
            autor text NOT NULL,
            frase text NOT NULL,
            idclase int(11) NOT NULL DEFAULT '1',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (idfrase),
            KEY idx_idclase (idclase)
        ) {$wpdb->get_charset_collate()};",

		'clases'  => "CREATE TABLE {$wpdb->clases} (
            idclase int(11) NOT NULL AUTO_INCREMENT,
            clase tinytext NOT NULL,
            descripcion text NULL,
            PRIMARY KEY (idclase)
        ) {$wpdb->get_charset_collate()};",

		'temas'   => "CREATE TABLE {$wpdb->temas} (
            idtema int(11) NOT NULL AUTO_INCREMENT,
            tema tinytext NOT NULL,
            slug varchar(255) NULL,
            PRIMARY KEY (idtema)
        ) {$wpdb->get_charset_collate()};",

		'taxos'   => "CREATE TABLE {$wpdb->taxos} (
            idtaxos int(11) NOT NULL AUTO_INCREMENT,
            idfrase int(11) NOT NULL,
            idtema int(11) NOT NULL,
            PRIMARY KEY (idtaxos),
            UNIQUE KEY unique_taxos (idfrase, idtema)
        ) {$wpdb->get_charset_collate()};", // Foreign keys added separately.

		'autores' => "CREATE TABLE {$wpdb->autores} (
            idautor int(11) NOT NULL AUTO_INCREMENT,
            autor text NOT NULL,
            pais text NULL,
            nacido date NULL,
            nacido_acdc enum('AC', 'DC') DEFAULT 'DC',
            muerto date NULL,
            muerto_acdc enum('AC', 'DC') DEFAULT 'DC',
            datos text NULL,
            frasescont int(11) NULL,
            PRIMARY KEY (idautor)
        ) {$wpdb->get_charset_collate()};",

		'import'  => "CREATE TABLE {$wpdb->import} (
            idimport int(11) NOT NULL AUTO_INCREMENT,
            frase text NOT NULL,
            autor text NOT NULL,
            processed tinyint(1) DEFAULT 0,
            import_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (idimport)
        ) {$wpdb->get_charset_collate()};",
	);

	// Create or update all tables using WordPress dbDelta function.
	foreach ( $tables as $key => $sql ) {
		dbDelta( $sql );
	}

	/**
	 * Add foreign key constraints to ensure referential integrity.
	 * These constraints help maintain data consistency when records are deleted.
	 */

	// Check if foreign keys already exist to avoid errors.
	// Use $wpdb->prepare() to safely pass the table name into the query.
	$foreign_keys = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = %s AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND TABLE_SCHEMA = DATABASE();",
			$wpdb->taxos
		)
	);

	// Extract existing constraint names from the DB result.
	// Use `wp_list_pluck` to avoid direct property naming style warnings.
	// (the column name is returned in upper case by INFORMATION_SCHEMA).
	$existing_keys = wp_list_pluck( $foreign_keys, 'CONSTRAINT_NAME' );

	// Add foreign key for quotes reference if it doesn't exist.
	if ( ! in_array( 'fr_taxos_idfrase', $existing_keys, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->taxos}
            ADD CONSTRAINT fr_taxos_idfrase
            FOREIGN KEY (idfrase) REFERENCES {$wpdb->frases}(idfrase) ON DELETE CASCADE"
		);

	}

	// Add foreign key for themes reference if it doesn't exist.
	if ( ! in_array( 'fr_taxos_idtema', $existing_keys, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$wpdb->taxos}
            ADD CONSTRAINT fr_taxos_idtema
            FOREIGN KEY (idtema) REFERENCES {$wpdb->temas}(idtema) ON DELETE CASCADE"
		);

	}
}

/**
 * Initializes database with default data
 *
 * Populates the plugin database tables with initial data if they're empty.
 * Also sets up default plugin options in the WordPress options table.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_insert_initial_data() {
	global $wpdb;

	// Only add initial data if quotes table is empty.
	if ( ! $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->frases}" ) ) {
		// Insert default classes if they don't exist.
		if ( 0 === $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->clases}" ) ) {
			$initial_classes = array(
				array(
					'clase'       => __( 'General', 'vr-frases' ),
					'descripcion' => __( 'Default category for all quotes', 'vr-frases' ),
				),
				array(
					'clase'       => __( 'Wisdom', 'vr-frases' ),
					'descripcion' => __( 'Quotes that contain deep wisdom and philosophical insights', 'vr-frases' ),
				),
				array(
					'clase'       => __( 'Humor', 'vr-frases' ),
					'descripcion' => __( 'Funny and humorous quotes', 'vr-frases' ),
				),
			);

			foreach ( $initial_classes as $class ) {
				$wpdb->insert(
					$wpdb->clases,
					array(
						'clase'       => $class['clase'],
						'descripcion' => $class['descripcion'],
					),
					array( '%s', '%s' )
				);
			}
		}

		// Insert default theme if none exists.
		if ( 0 === $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->temas}" ) ) {
			$wpdb->insert(
				$wpdb->temas,
				array(
					'tema' => __( 'General', 'vr-frases' ),
					'slug' => 'general',
				),
				array( '%s', '%s' )
			);
		}

		// Insert sample quote for new users.
		$wpdb->insert(
			$wpdb->frases,
			array(
				'autor'   => __( 'Anonymous', 'vr-frases' ),
				'frase'   => __( 'Welcome to VR Frases! This is a sample quote.', 'vr-frases' ),
				'idclase' => 1,
			),
			array( '%s', '%s', '%d' )
		);

		// Get the new quote ID.
		$quote_id = $wpdb->insert_id;

		// Add the author if not exists.
		if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->autores} WHERE autor = %s", __( 'Anonymous', 'vr-frases' ) ) ) ) {
			$wpdb->insert(
				$wpdb->autores,
				array(
					'autor' => __( 'Anonymous', 'vr-frases' ),
					'datos' => __( 'Used when the author of a quote is unknown', 'vr-frases' ),
				),
				array( '%s', '%s' )
			);
		}

		// Connect the quote with the first theme.
		if ( $quote_id ) {
			$wpdb->insert(
				$wpdb->taxos,
				array(
					'idfrase' => $quote_id,
					'idtema'  => 1,
				),
				array( '%d', '%d' )
			);
		}
	}

	// Set up default plugin options.
	$current_options = get_option( 'vr_frases_options' );
	$initial_options = array(
		'num_inputs'           => 25,
		'page_slug'            => 'frases',
		'language'             => '', // Empty string = use WordPress locale automatically.
		'wiki_lang'            => 'en', // Default to English for Wikipedia.
		'hide_autor'           => 0,
		'link_autor'           => 1,
		'side_autor'           => 1,
		'sep_lines'            => 0,
		'date_format'          => 'd/m/Y',
		'ac_bc_format'         => 'AC',
		'allow_full_uninstall' => false,
		'version'              => VR_FRASES_VERSION, // Store current version for upgrades.
	);

	if ( false === $current_options ) {
		// If options don't exist, create them.
		add_option( 'vr_frases_options', $initial_options );
	} else {
		// If options exist, merge to preserve existing values and add any new ones.
		$updated_options = array_merge( $initial_options, $current_options );

		// Always update version number.
		$updated_options['version'] = VR_FRASES_VERSION;

		// Ensure uninstall option exists.
		if ( ! isset( $current_options['allow_full_uninstall'] ) ) {
			$updated_options['allow_full_uninstall'] = false;
		}

		update_option( 'vr_frases_options', $updated_options );
	}
}

/**
 * Handles database upgrades between plugin versions
 *
 * This function performs necessary database structure changes when upgrading
 * from older versions of the plugin. It creates missing tables and migrates data
 * as needed to maintain compatibility.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_upgrade() {
	global $wpdb;
	$options            = get_option( 'vr_frases_options', array() );
	$current_db_version = isset( $options['db_version'] ) ? $options['db_version'] : '0';

	// Basic Tables Creation (for older versions that might not have them).
	// Ensure taxonomy table exists (for plugins before v3.0).
	$wpdb->query(
		"CREATE TABLE IF NOT EXISTS {$wpdb->taxos} (
			idtaxos int(11) NOT NULL AUTO_INCREMENT,
			idfrase int(11) NOT NULL,
			idtema int(11) NOT NULL,
			PRIMARY KEY (idtaxos),
			UNIQUE KEY unique_taxos (idfrase, idtema),
			CONSTRAINT fr_taxos_idfrase FOREIGN KEY (idfrase) REFERENCES {$wpdb->frases}(idfrase) ON DELETE CASCADE,
			CONSTRAINT fr_taxos_idtema FOREIGN KEY (idtema) REFERENCES {$wpdb->temas}(idtema) ON DELETE CASCADE
		) {$wpdb->get_charset_collate()};"
	);

	// Ensure import table exists (for plugins before v3.5).
	$wpdb->query(
		"CREATE TABLE IF NOT EXISTS {$wpdb->import} (
			idimport int(11) NOT NULL AUTO_INCREMENT,
			frase text NOT NULL,
			autor text NOT NULL,
			processed tinyint(1) DEFAULT 0,
			import_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (idimport)
		) {$wpdb->get_charset_collate()};"
	);

	// Ensure authors table exists (for plugins before v3.0).
	$wpdb->query(
		"CREATE TABLE IF NOT EXISTS {$wpdb->autores} (
			idautor int(11) NOT NULL AUTO_INCREMENT,
			autor text NOT NULL,
			pais text NULL,
			nacido date NULL,
			nacido_acdc enum('AC', 'DC') DEFAULT 'DC',
			muerto date NULL,
			muerto_acdc enum('AC', 'DC') DEFAULT 'DC',
			datos text NULL,
			frasescont int(11) NULL,
			PRIMARY KEY (idautor)
		) {$wpdb->get_charset_collate()};"
	);

	// Data Migration for Version-Specific Upgrades.
	// Migrate data from version 2.x to 3.x format.
	// Check if the old theme column exists in frases table.
	$column_exists = $wpdb->get_results(
		$wpdb->prepare(
			"SHOW COLUMNS FROM {$wpdb->frases} LIKE %s",
			'idtema'
		)
	);

	if ( ! empty( $column_exists ) ) {
		// Get quotes with theme data.
		$existing_taxos = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT idfrase, idtema FROM {$wpdb->frases} WHERE idtema IS NOT NULL AND idtema != %d",
				0
			)
		);

		// Migrate theme data to the taxonomy table.
		if ( ! empty( $existing_taxos ) ) {
			foreach ( $existing_taxos as $taxo ) {
				// Check if this relationship already exists.
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->taxos} WHERE idfrase = %d AND idtema = %d",
						$taxo->idfrase,
						$taxo->idtema
					)
				);

				// Only insert if not already exists.
				if ( 0 === $existing ) {
					$wpdb->insert(
						$wpdb->taxos,
						array(
							'idfrase' => $taxo->idfrase,
							'idtema'  => $taxo->idtema,
						),
						array( '%d', '%d' )
					);
				}
			}

			// Remove the old column once data is migrated.
			$wpdb->query( "ALTER TABLE {$wpdb->frases} DROP COLUMN IF EXISTS idtema" );
		}
	}

	// Add new slug field to themes table if upgrading from before 4.0.
	$slug_exists = $wpdb->get_results(
		$wpdb->prepare(
			"SHOW COLUMNS FROM {$wpdb->temas} LIKE %s",
			'slug'
		)
	);

	if ( empty( $slug_exists ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->temas} ADD COLUMN slug VARCHAR(255) NULL" );

		// Generate slugs for existing themes.
		$themes = $wpdb->get_results( "SELECT idtema, tema FROM {$wpdb->temas}" );
		if ( ! empty( $themes ) ) {
			foreach ( $themes as $theme ) {
				$slug = sanitize_title( $theme->tema );
				$wpdb->update(
					$wpdb->temas,
					array( 'slug' => $slug ),
					array( 'idtema' => $theme->idtema ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	// Update the stored database version.
	$options['db_version'] = VR_FRASES_VERSION;
	update_option( 'vr_frases_options', $options );
}

/**
 * Update legacy tables and migrate data from very old plugin versions.
 *
 * This routine is intended to be executed as part of a controlled upgrade
 * sequence (see `vr_frases_maybe_run_upgrades()`). It performs schema
 * adjustments and data migrations that belong to very old versions.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_update_legacy() {
	global $wpdb;

	// Legacy tables.
	$old_frases = $wpdb->prefix . 'fr_frases';
	$old_clases = $wpdb->prefix . 'fr_clases';
	$old_temas  = $wpdb->prefix . 'fr_temas';

	// Check if legacy tables exist (use prepared statements for the LIKE pattern).
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_frases ) ) ) {
		// Add necessary columns if they don't exist. Escape identifiers.
		$old_frases_esc = '`' . esc_sql( $old_frases ) . '`';
		$wpdb->query( "ALTER TABLE {$old_frases_esc} ADD COLUMN IF NOT EXISTS idclase INT(11) NOT NULL" );
		$wpdb->query( "ALTER TABLE {$old_frases_esc} ADD COLUMN IF NOT EXISTS idtema INT(11) NOT NULL" );
	}

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_clases ) ) ) {
		$old_clases_esc = '`' . esc_sql( $old_clases ) . '`';
		$wpdb->query( "ALTER TABLE {$old_clases_esc} ADD COLUMN IF NOT EXISTS descripcion TEXT" );
	}

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_temas ) ) ) {
		$old_temas_esc = '`' . esc_sql( $old_temas ) . '`';
		$wpdb->query( "ALTER TABLE {$old_temas_esc} ADD COLUMN IF NOT EXISTS slug VARCHAR(255)" );
	}

	// Migrate data if necessary. Use prepared statements for value placeholders.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_frases ) ) ) {
		$old_frases_esc = '`' . esc_sql( $old_frases ) . '`';
		$query          = "UPDATE {$old_frases_esc} SET idclase = %d WHERE idclase IS NULL";
		$wpdb->query( $wpdb->prepare( $query, 1 ) );
		$query = "UPDATE {$old_frases_esc} SET idtema = %d WHERE idtema IS NULL";
		$wpdb->query( $wpdb->prepare( $query, 1 ) );
	}
}

/**
 * Maybe run all upgrade steps in a controlled way.
 *
 * This function runs table creation, initial data insertion, general upgrades
 * and legacy migrations. It is safe to call repeatedly because it uses a
 * transient lock and updates the stored plugin version only on success.
 *
 * Executed on `admin_init` to avoid running during front-end requests.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_maybe_run_upgrades() {
	if ( ! is_admin() ) {
		return;
	}

	// Prevent concurrent runs.
	if ( get_transient( 'vr_frases_upgrade_lock' ) ) {
		return;
	}
	set_transient( 'vr_frases_upgrade_lock', 1, 300 );

	global $wpdb;

	$stored_version = get_option( 'vr_frases_version', '1.0' );
	$upgrade_data   = get_option( 'vr_frases_needs_upgrade', false );

	// If already up-to-date and no pending upgrade, nothing to do.
	if ( version_compare( $stored_version, VR_FRASES_VERSION, '>=' ) && ! $upgrade_data ) {
		delete_transient( 'vr_frases_upgrade_lock' );
		return;
	}

	// Log upgrade start.
	$upgrade_log = array(
		'start_time'      => time(),
		'from_version'    => $stored_version,
		'to_version'      => VR_FRASES_VERSION,
		'steps_completed' => array(),
		'errors'          => array(),
	);

	// Log del inicio del upgrade usando el sistema de logging del plugin.
	if ( function_exists( 'vr_frases_log' ) ) {
		vr_frases_log(
			'Iniciando proceso de upgrade de base de datos',
			'info',
			array(
				'from_version' => $stored_version,
				'to_version'   => VR_FRASES_VERSION,
				'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'unknown',
			)
		);
	}

	$ok = true;

	// Step 1: Core table creation and updates.
	try {
		vr_frases_new_first();
		if ( $wpdb->last_error ) {
			throw new Exception( 'Database table creation failed: ' . $wpdb->last_error );
		}
		$upgrade_log['steps_completed'][] = 'tables_created';
	} catch ( Exception $e ) {
		$upgrade_log['errors'][] = $e->getMessage();
		error_log( 'VR Frases upgrade error (tables): ' . $e->getMessage() );
		$ok = false;
	}

	// Step 2: Insert initial data if needed.
	if ( $ok ) {
		try {
			vr_frases_insert_initial_data();
			if ( $wpdb->last_error ) {
				throw new Exception( 'Initial data insertion failed: ' . $wpdb->last_error );
			}
			$upgrade_log['steps_completed'][] = 'initial_data_inserted';
		} catch ( Exception $e ) {
			$upgrade_log['errors'][] = $e->getMessage();
			error_log( 'VR Frases upgrade error (initial_data): ' . $e->getMessage() );
			$ok = false;
		}
	}

	// Step 3: Run generic version upgrades.
	if ( $ok ) {
		try {
			vr_frases_upgrade();
			if ( $wpdb->last_error ) {
				throw new Exception( 'Version upgrade failed: ' . $wpdb->last_error );
			}
			$upgrade_log['steps_completed'][] = 'version_upgrade_completed';
		} catch ( Exception $e ) {
			$upgrade_log['errors'][] = $e->getMessage();
			error_log( 'VR Frases upgrade error (upgrade): ' . $e->getMessage() );
			$ok = false;
		}
	}

	// Step 4: Run legacy migrations (very old formats).
	if ( $ok ) {
		try {
			vr_frases_update_legacy();
			if ( $wpdb->last_error ) {
				throw new Exception( 'Legacy migration failed: ' . $wpdb->last_error );
			}
			$upgrade_log['steps_completed'][] = 'legacy_migration_completed';
		} catch ( Exception $e ) {
			$upgrade_log['errors'][] = $e->getMessage();
			error_log( 'VR Frases upgrade error (legacy): ' . $e->getMessage() );
			$ok = false;
		}
	}

	// Finalize upgrade.
	$upgrade_log['end_time'] = time();
	$upgrade_log['success']  = $ok;

	if ( $ok ) {
		// Success: Update version and clean up.
		update_option( 'vr_frases_version', VR_FRASES_VERSION );
		delete_option( 'vr_frases_needs_upgrade' );
		update_option( 'vr_frases_last_successful_upgrade', time() );

		// Store successful upgrade log.
		update_option( 'vr_frases_last_upgrade_log', $upgrade_log );

		// Clean up old failed upgrade logs.
		delete_option( 'vr_frases_failed_upgrade_log' );

		// Log del éxito del upgrade.
		if ( function_exists( 'vr_frases_log' ) ) {
			vr_frases_log(
				'Upgrade de base de datos completado exitosamente',
				'info',
				array(
					'duration_seconds' => time() - $upgrade_log['start_time'],
					'steps_completed'  => $upgrade_log['steps_completed'],
					'final_version'    => VR_FRASES_VERSION,
				)
			);
		}
	} else {
		// Failure: Store detailed error log for debugging.
		update_option( 'vr_frases_failed_upgrade_log', $upgrade_log );

		// Set flag for admin notices.
		update_option( 'vr_frases_upgrade_failed', true );

		// Log del fallo del upgrade.
		if ( function_exists( 'vr_frases_log' ) ) {
			vr_frases_log(
				'Upgrade de base de datos falló',
				'error',
				array(
					'duration_seconds'  => time() - $upgrade_log['start_time'],
					'steps_completed'   => $upgrade_log['steps_completed'],
					'errors'            => $upgrade_log['errors'],
					'attempted_version' => VR_FRASES_VERSION,
				)
			);
		}
	}

	delete_transient( 'vr_frases_upgrade_lock' );
}

add_action( 'admin_init', 'vr_frases_maybe_run_upgrades' );

/**
 * Display admin notices for upgrade status.
 *
 * Shows success or failure messages after plugin upgrades.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_upgrade_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check for failed upgrade.
	if ( get_option( 'vr_frases_upgrade_failed', false ) ) {
		$failed_log = get_option( 'vr_frases_failed_upgrade_log', array() );
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_html_e( 'VR-Frases Plugin Upgrade Failed', 'vr-frases' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'The plugin upgrade encountered errors. Please check your error logs or contact support.', 'vr-frases' ); ?>
				<?php if ( ! empty( $failed_log['errors'] ) ) : ?>
					<br><strong><?php esc_html_e( 'Last error:', 'vr-frases' ); ?></strong> 
					<?php echo esc_html( end( $failed_log['errors'] ) ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php
		// Clear the failed flag after showing the notice.
		delete_option( 'vr_frases_upgrade_failed' );
	}

	// Check for successful upgrade.
	$last_upgrade = get_option( 'vr_frases_last_successful_upgrade', 0 );
	$notice_shown = get_option( 'vr_frases_upgrade_notice_shown', 0 );

	if ( $last_upgrade > $notice_shown && $last_upgrade > ( time() - 3600 ) ) { // Show within 1 hour.
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'VR-Frases Plugin Updated Successfully', 'vr-frases' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: plugin version */
					esc_html__( 'VR-Frases has been updated to version %s. All data has been preserved.', 'vr-frases' ),
					esc_html( VR_FRASES_VERSION )
				);
				?>
			</p>
		</div>
		<?php
		// Mark notice as shown.
		update_option( 'vr_frases_upgrade_notice_shown', time() );
	}
}
add_action( 'admin_notices', 'vr_frases_upgrade_admin_notices' );

/**
 * Clean up old upgrade logs and temporary data.
 *
 * Removes upgrade logs older than 30 days to prevent database bloat.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_cleanup_old_upgrade_logs() {
	$logs        = get_option( 'vr_frases_upgrade_log', array() );
	$cutoff_time = time() - ( 30 * 24 * 60 * 60 ); // 30 days ago.

	// Filter out old logs.
	$cleaned_logs = array_filter(
		$logs,
		function ( $log ) use ( $cutoff_time ) {
			return isset( $log['timestamp'] ) && $log['timestamp'] > $cutoff_time;
		}
	);

	// Update if logs were cleaned.
	if ( count( $cleaned_logs ) !== count( $logs ) ) {
		update_option( 'vr_frases_upgrade_log', $cleaned_logs );
		error_log(
			sprintf(
				'VR-Frases: Cleaned %d old upgrade logs',
				count( $logs ) - count( $cleaned_logs )
			)
		);
	}

	// Clean up old failed upgrade logs.
	$failed_logs = get_option( 'vr_frases_failed_upgrade_log', array() );
	if ( ! empty( $failed_logs['timestamp'] ) && $failed_logs['timestamp'] < $cutoff_time ) {
		delete_option( 'vr_frases_failed_upgrade_log' );
	}
}

// Schedule cleanup during upgrade process.
add_action( 'vr_frases_after_upgrade', 'vr_frases_cleanup_old_upgrade_logs' );
