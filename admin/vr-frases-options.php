<?php
/**
 * VR-Frases Options Management and Configuration Interface
 *
 * This file provides comprehensive plugin configuration management with a modern
 * tabbed interface for settings and information display. It handles all aspects
 * of plugin options including validation, sanitization, GDPR compliance notices,
 * and multi-language support for optimal user experience.
 *
 * Key Features:
 * - Tabbed interface for settings and plugin information
 * - GDPR compliance notices with collapsible details
 * - Comprehensive options validation and sanitization
 * - Multi-language support with automatic locale detection
 * - WordPress coding standards compliance
 * - Nonce verification for all form submissions
 * - User capability checking for security
 *
 * Interface Components:
 * - Settings tab: Plugin configuration options
 * - Information tab: Admin procedures and shortcode documentation
 * - Language selector: Multi-language file detection
 * - Security notices: GDPR and privacy compliance information
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
 * Display GDPR compliance notice on settings page.
 *
 * Renders privacy notice with collapsible details and compliance
 * recommendations specifically for plugin settings page.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_show_gdpr_notice() {
	$screen = get_current_screen();

	// Only show the notice on the settings page.
	if ( ! isset( $screen->id ) || false === strpos( $screen->id, 'vrfr_managesettings' ) ) {
		return;
	}

	?>
	<div class="notice notice-info">
		<p style="margin-bottom: 10px;">
			<span class="dashicons dashicons-info" style="color: #0073aa; margin-right: 5px;"></span>
			<strong><?php echo esc_html__( 'Privacy & GDPR Compliance Notice', 'vr-frases' ); ?></strong>
			<button type="button" id="vr-gdpr-toggle" class="button" style="margin-left: 10px; display: inline-flex; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-arrow-down-alt2" id="vr-gdpr-arrow" style="font-size: 16px; width: 16px; height: 16px;"></span>
				<span id="vr-gdpr-text"><?php echo esc_html__( 'Show details', 'vr-frases' ); ?></span>
			</button>
		</p>
		<div id="vr-gdpr-details" class="notice-info" style="display: none; padding: 10px;">
			<?php echo wp_kses_post( __( 'This plugin manages famous quotes and author data.<br>While it does not collect personal data from users or visitors, content entered may include identifiable information about living individuals.<br><br><strong>Recommendations:</strong><br>• Enter factual and public information only.<br>• Avoid sensitive data or personal opinions.<br>• Confirm legal basis before adding personal details.<br><br>📌 The developer is not responsible for plugin usage or content published on third-party websites.', 'vr-frases' ) ); ?>
		</div>
	</div>
	<?php
}
add_action( 'admin_notices', 'vr_frases_show_gdpr_notice' );

/**
 * Render plugin options interface with tabbed navigation.
 *
 * Creates tabbed admin interface for configuration and documentation
 * with secure form processing and nonce verification.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_manage_settings() {
	// Nonce verification for options form using central helper.
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'vr_frases_options_group-options' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed: invalid nonce.', 'vr-frases' ) . '</p></div>';
			wp_safe_redirect( admin_url( 'admin.php?page=vrfr_managesettings' ) );
			exit;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to perform this action.', 'vr-frases' ) . '</p></div>';
			wp_safe_redirect( admin_url( 'admin.php?page=vrfr_managesettings' ) );
			exit;
		}
	}

	settings_errors();
	$options        = get_option( 'vr_frases_options' );
	$active_tab_raw = filter_input( INPUT_GET, 'tab', FILTER_UNSAFE_RAW );
	$active_tab     = null !== $active_tab_raw ? sanitize_text_field( wp_unslash( $active_tab_raw ) ) : 'settings';
	?>
	<div class="wrap vr-frases">
		<h1 style="display:flex;align-items:center;gap:12px;">
			<?php
				echo '<span class="dashicons dashicons-admin-generic" style="font-size: 30px; width: 30px; height: 30px;"></span>';
				echo esc_html( __( 'Manage Options', 'vr-frases' ) );
			?>
		</h1>
		<h2 class="nav-tab-wrapper">
		<a href="?page=vrfr_managesettings&tab=settings" class="nav-tab<?php echo 'settings' === $active_tab ? ' nav-tab-active' : ''; ?>">
				<?php echo esc_html( __( 'Settings', 'vr-frases' ) ); ?>
			</a>
		<a href="?page=vrfr_managesettings&tab=procedures" class="nav-tab<?php echo 'procedures' === $active_tab ? ' nav-tab-active' : ''; ?>">
				<?php echo esc_html( __( 'Procedures', 'vr-frases' ) ); ?>
			</a>
		<a href="?page=vrfr_managesettings&tab=system" class="nav-tab<?php echo 'system' === $active_tab ? ' nav-tab-active' : ''; ?>">
				<?php echo esc_html( __( 'System Info', 'vr-frases' ) ); ?>
			</a>
		</h2>
		<?php if ( 'settings' === $active_tab ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'vr_frases_options_group' ); ?>
				<table class="wp-list-table widefat striped">
					<!-- General settings. -->
					<tr valign="top">
						<th colspan="2" scope="col"><h3><?php echo esc_html( __( 'Settings for main and manage pages', 'vr-frases' ) ); ?></h3></th>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="num_inputs"><?php echo esc_html( __( 'Quotes per page', 'vr-frases' ) ); ?></label></th>
						<td><input type="number" name="vr_frases_options[num_inputs]" id="num_inputs" min="1" value="<?php echo esc_attr( $options['num_inputs'] ?? '' ); ?>" />
						<span class="description"><br /><?php echo esc_html( __( 'Limit to display results (quotes or authors) per page (must be > 0)', 'vr-frases' ) ); ?></span></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="page_slug"><?php echo esc_html( __( 'Page slug', 'vr-frases' ) ); ?></label></th>
						<td><input type="text" name="vr_frases_options[page_slug]" id="page_slug" value="<?php echo esc_attr( $options['page_slug'] ?? '' ); ?>" />
						<span class="description"><br /><?php echo esc_html( __( 'Caption (slug) of the page which contains the main shortcode.', 'vr-frases' ) ); ?></span></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="vr-frases_lang"><?php echo esc_html( __( 'Plugin Language', 'vr-frases' ) ); ?></label></th>
						<td><?php vr_frases_render_language_option(); ?>
						<span class="description"><br /><?php echo esc_html( __( 'Plugin Language. You can add more languages in the /languages/ directory.', 'vr-frases' ) ); ?></span></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="wiki_lang"><?php echo esc_html( __( 'Wikipedia Language', 'vr-frases' ) ); ?></label></th>
						<td><input type="text" name="vr_frases_options[wiki_lang]" id="wiki_lang" value="<?php echo esc_attr( $options['wiki_lang'] ?? 'en' ); ?>" placeholder="en" />
						<span class="description"><br /><?php echo esc_html( __( 'Language code for Wikipedia queries (e.g., es, en, fr). Default: en', 'vr-frases' ) ); ?></span></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="date_format"><?php echo esc_html( __( 'Date Format', 'vr-frases' ) ); ?></label></th>
						<td><input type="text" name="vr_frases_options[date_format]" id="date_format" value="<?php echo esc_attr( $options['date_format'] ?? 'd/m/Y' ); ?>" />
						<span class="description"><br /><?php echo esc_html( __( 'Specify the date format to use for authors (e.g., d/m/Y, m-d-Y).', 'vr-frases' ) ); ?></span></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="ac_bc_format"><?php echo esc_html( __( 'AC/BC Format', 'vr-frases' ) ); ?></label></th>
						<td>
							<select name="vr_frases_options[ac_bc_format]" id="ac_bc_format">
								<option value="AC" <?php selected( isset( $options['ac_bc_format'] ) ? $options['ac_bc_format'] : 'AC', 'AC' ); ?>><?php echo esc_html( __( 'AC', 'vr-frases' ) ); ?></option>
								<option value="BC" <?php selected( isset( $options['ac_bc_format'] ) ? $options['ac_bc_format'] : '', 'BC' ); ?>><?php echo esc_html( 'BC' ); // Temporarily without __ () for diagnosis. ?> </option>.
							</select>
							<span class="description"><br /><?php echo esc_html( __( 'Specify the format for dates before Christ (AC or BC).', 'vr-frases' ) ); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th colspan="2" scope="col"><h3><?php echo esc_html( __( 'Settings for widget and [randomfrase] short code', 'vr-frases' ) ); ?></h3></th> <!-- Escape translatable text. -->
					</tr>
					<tr valign="top">
						<th scope="row"><label for="link_autor"><?php echo esc_html( __( 'Link author', 'vr-frases' ) ); ?></label></th>
						<td><input type="checkbox" name="vr_frases_options[link_autor]" id="link_autor" value="1" <?php checked( 1, isset( $options['link_autor'] ) ? $options['link_autor'] : 0 ); ?> />
						<span class="description"><?php echo esc_html( __( 'Link the author name to main page in order to view more quotes from him.', 'vr-frases' ) ); ?></span></td>
					</tr>
						<tr valign="top">
							<th scope="row"><label for="side_autor"><?php echo esc_html( __( 'Side author', 'vr-frases' ) ); ?></label></th>
							<td><input type="checkbox" name="vr_frases_options[side_autor]" id="side_autor" value="1" <?php checked( 1, isset( $options['side_autor'] ) ? $options['side_autor'] : 0 ); ?> />
							<span class="description"><?php echo esc_html( __( 'Mark to display author name before the quote. Unmark to display after.', 'vr-frases' ) ); ?></span></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="hide_autor"><?php echo esc_html( __( 'Hide author', 'vr-frases' ) ); ?></label></th>
							<td>
								<input type="checkbox" name="vr_frases_options[hide_autor]" id="hide_autor" value="1" <?php checked( 1, isset( $options['hide_autor'] ) ? $options['hide_autor'] : 0 ); ?> />
								<span class="description"><?php echo esc_html( __( 'Mark to hide the author name in the quote display.', 'vr-frases' ) ); ?></span>
							</td>
						</tr>
					<tr valign="top">
						<th scope="row"><label for="sep_lines"><?php echo esc_html( __( 'Separate lines', 'vr-frases' ) ); ?></label></th>
						<td><input type="checkbox" name="vr_frases_options[sep_lines]" id="sep_lines" value="1" <?php checked( 1, isset( $options['sep_lines'] ) ? $options['sep_lines'] : 0 ); ?> />
						<span class="description"><?php echo esc_html( __( 'Mark to insert <br /> between author and quote. Otherwise inserts a blank space.', 'vr-frases' ) ); ?></span></td>
					</tr>
					<tr valign="top">
						<th colspan="2" scope="col"><h3><?php echo esc_html( __( 'Plugin Maintenance', 'vr-frases' ) ); ?></h3></th>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="allow_full_uninstall"><?php echo esc_html( __( 'Allow Full Uninstall', 'vr-frases' ) ); ?></label></th>
						<td>
							<input type="checkbox" name="vr_frases_options[allow_full_uninstall]" id="allow_full_uninstall" value="1" <?php checked( true, $options['allow_full_uninstall'] ?? false ); ?> />
							<span class="description" style="color: red;"><?php echo esc_html( __( 'WARNING: If checked, all plugin data (quotes, authors, settings) will be permanently deleted when the plugin is uninstalled. This action cannot be undone.', 'vr-frases' ) ); ?></span>
						</td>
					</tr>

				</table>
				<?php submit_button( esc_html__( 'Save changes', 'vr-frases' ), 'primary', 'submit' ); ?>
			</form>
		<?php elseif ( 'procedures' === $active_tab ) : ?>
		<div class="vr-frases-information" style="font-size: 1.2em;">
			<h2><?php echo esc_html__( 'Admin Panel Procedures', 'vr-frases' ); ?></h2>

			<?php
			$icons = array(
				'Manage Quotes'                    => 'dashicons-edit',
				'Add New Quote'                    => 'dashicons-plus-alt',
				'Manage Authors'                   => 'dashicons-admin-users',
				'Manage Classes and Manage Topics' => 'dashicons-tag',
				'Manage Import / Export'           => 'dashicons-upload',
				'Manage Options'                   => 'dashicons-admin-generic',
			);

			// translators: Section titles for admin accordion.
			__( 'Import / Export Quotes', 'vr-frases' );
			__( 'Manage Options', 'vr-frases' );
			__( 'Manage Classes and Manage Topics', 'vr-frases' );
			__( 'Manage Import / Export', 'vr-frases' );

			$sections = array(
				'Manage Quotes'                    => array(
					__( 'This is the main panel of the plugin where all quotes are displayed, as well as the results matching the search parameters, sorted by the criteria you choose. All criteria can be combined.', 'vr-frases' ),
					__( 'Quick Edit allows you to modify only the Class and Topics, while the Edit function lets you modify all fields.', 'vr-frases' ),
				),
				'Add New Quote'                    => array(
					__( 'This form is used to add quotes individually. All fields must be filled in.', 'vr-frases' ),
					__( 'If any element is duplicated (Quote and Author), an error message will be displayed and it will NOT be processed.', 'vr-frases' ),
				),
				'Manage Authors'                   => array(
					__( 'This panel shows a list of all authors registered in the database.', 'vr-frases' ),
					__( 'Each Author record is created automatically when adding a new Quote, modifying an existing Quote, or after processing an item from the imported quotes table.', 'vr-frases' ),
					__( 'Quick Edit allows you to add or modify the data associated with the Author: Place of birth and birth and death dates, as well as a brief biography.', 'vr-frases' ),
					__( 'For dates before Christ, you must check the "AC" option and enter the date as "01/01/YYYY", which will display the date as "YYYY AC".', 'vr-frases' ),
					__( 'When there is no known author (e.g. sayings, proverbs, etc.) in the dates of birth and death we will put 01/01/0001, this will make the field appear blank in the listing, but it will be assigned a value for filtering purposes.', 'vr-frases' ),
					__( 'There is a dropdown that allows you to filter Authors who have all their data and those who still need to complete the information.', 'vr-frases' ),
				),
				'Manage Classes and Manage Topics' => array(
					__( 'These panels allow you to add or modify Classes and Topics of your choice.', 'vr-frases' ),
					__( 'Classes act as categories and Topics as tags.', 'vr-frases' ),
					__( 'In the Add field, you can enter several values separated by commas (,).', 'vr-frases' ),
					__( 'If any element is duplicated, an error message will be displayed and it will NOT be processed.', 'vr-frases' ),
				),
				'Manage Import / Export'           => array(
					__( 'The Import tab of this panel, allows you to upload a .CSV or .TXT file with the fields "Quote" and "Author" in quotes and separated by commas (,).', 'vr-frases' ),
					__( 'When importing the file, the data is copied to an intermediate table from where you can process each record. You must add a Class and one or more Topics. When saving the data, the Quote goes to the main table and the Author goes to the authors table where you can add biographical data.', 'vr-frases' ),
					__( 'If any element is duplicated, an error message will be displayed during data import and it will NOT be processed.', 'vr-frases' ),
					__( 'In the Export tab, you have the option to export the Quotes and Authors from your database to a CSV or TXT file with fields in quotes and separated by commas.', 'vr-frases' ),
				),
				'Manage Options'                   => array(
					__( 'In this panel you can modify the plugin options. Each field has a descriptive legend of the option to configure, so it will be easy to adjust it to your preferences.', 'vr-frases' ),
					__( 'The most important option is the "slug" of the page where you will display your quotes to the user.', 'vr-frases' ),
					__( 'Create a blank page with the name you have chosen in the "slug" option and insert the shortcode [vrfrases] and it will be ready to use.', 'vr-frases' ),
				),
			);

			foreach ( $sections as $key => $items ) {
				$icon_class     = isset( $icons[ $key ] ) ? $icons[ $key ] : 'dashicons-admin-page';
				$translated_key = vr_frases_translate_section_key( $key );

				echo '<div class="vr-accordion">';
				echo '<button class="vr-accordion-toggle" type="button">';
				echo '<h4><span class="dashicons ' . esc_attr( $icon_class ) . '"></span>' . esc_html( $translated_key ) . '</h4>';
				echo '</button>';
				echo '<div class="vr-accordion-content"><ul>';
				foreach ( $items as $item ) {
					echo '<li class="vr-frase-item">' . esc_html( $item ) . '</li>';
				}
				echo '</ul></div></div>';
			}
			?>

		</div>
				<div class="vr-frases-information" style="font-size: 1.2em;">
			<h2><?php echo esc_html__( 'Widgets and Shortcodes included in this plugin', 'vr-frases' ); ?></h2>

			<?php
			$icons = array(
				'Widget: VR-frases'                 => 'dashicons-dashboard',
				'Widget: Take a look for VR-frases' => 'dashicons-dashboard',
				'Shortcode: [vrfrases]'             => 'dashicons-shortcode',
				'Shortcode: [randomfrase]'          => 'dashicons-randomize',
				'Shortcode: [frasescount]'          => 'dashicons-list-view',
				'Shortcode: [autorescount]'         => 'dashicons-id-alt',
			);

			__( 'Widget: VR-frases', 'vr-frases' );
			__( 'Widget: Take a look for VR-frases', 'vr-frases' );
			__( 'Shortcode: [vrfrases]', 'vr-frases' );
			__( 'Shortcode: [randomfrase]', 'vr-frases' );
			__( 'Shortcode: [frasescount]', 'vr-frases' );
			__( 'Shortcode: [autorescount]', 'vr-frases' );

			$sections = array(
				'Widget: VR-frases'                 => array(
					__( 'VR-Frases Widget (VR_Frases_Widget):', 'vr-frases' ),
					__( 'Displays a random quote on each page reload.', 'vr-frases' ),
				),
				'Widget: Take a look for VR-frases' => array(
					__( 'VR-Frases Dashboard Widget (vr_frases_dash_widget):', 'vr-frases' ),
					__( 'Displays in the WordPress admin dashboard a summary of the data (total quotes, authors, topics, classes), a random sample quote, and the plugin version.', 'vr-frases' ),
				),
				'Shortcode: [vrfrases]'             => array(
					__( 'Displays the main plugin page content and the search form.', 'vr-frases' ),
				),
				'Shortcode: [randomfrase]'          => array(
					__( 'Includes a random quote in the content of a post or page.', 'vr-frases' ),
					__( 'In your templates you can use the code: <code>&lt;?php echo vr_frases_random_frase (); ?&gt;</code>', 'vr-frases' ),
				),
				'Shortcode: [frasescount]'          => array(
					__( 'Returns an integer with the total number of stored quotes.', 'vr-frases' ),
				),
				'Shortcode: [autorescount]'         => array(
					__( 'Returns an integer with the total number of stored authors.', 'vr-frases' ),
				),
			);

			foreach ( $sections as $key => $items ) {
				$icon_class     = isset( $icons[ $key ] ) ? $icons[ $key ] : 'dashicons-admin-page';
				$translated_key = vr_frases_translate_section_key( $key );

				echo '<div class="vr-accordion">';
				echo '<button class="vr-accordion-toggle" type="button">';
				echo '<h4><span class="dashicons ' . esc_attr( $icon_class ) . '"></span>' . esc_html( $translated_key ) . '</h4>';
				echo '</button>';
				echo '<div class="vr-accordion-content"><ul>';
				foreach ( $items as $item ) {
					echo '<li class="vr-frase-item">' . esc_html( $item ) . '</li>';
				}
				echo '</ul></div></div>';
			}
			?>
		</div>

		<?php elseif ( 'system' === $active_tab ) : ?>
			<?php vr_frases_render_system_info_tab(); ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render system information tab.
 *
 * Displays system and plugin statistics including WordPress version,
 * PHP version, MySQL version, and plugin content statistics.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_render_system_info_tab() {
	global $wpdb;

	// Get system information.
	$wp_version  = get_bloginfo( 'version' );
	$php_version = PHP_VERSION;

	// Get MySQL version.
	$mysql_version = $wpdb->get_var( 'SELECT VERSION()' );

	// Get plugin statistics.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$frases_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->frases}" );
	$autores_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->autores}" );
	$clases_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->clases}" );
	$temas_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->temas}" );

	// Get authors with their quote counts.
	$autores_stats = $wpdb->get_results(
		"SELECT a.autor, COUNT(f.idfrase) as count
		 FROM {$wpdb->autores} a
		 LEFT JOIN {$wpdb->frases} f ON a.autor = f.autor
		 GROUP BY a.autor
		 ORDER BY count DESC, a.autor ASC
		 LIMIT 10"
	);

	// Get classes with their quote counts.
	$clases_stats = $wpdb->get_results(
		"SELECT c.clase, COUNT(f.idfrase) as count
		 FROM {$wpdb->clases} c
		 LEFT JOIN {$wpdb->frases} f ON c.idclase = f.idclase
		 GROUP BY c.idclase, c.clase
		 ORDER BY count DESC, c.clase ASC
		 LIMIT 10"
	);

	// Get themes with their quote counts through taxonomy table.
	$temas_stats = $wpdb->get_results(
		"SELECT t.tema, COUNT(tx.idfrase) as count
		 FROM {$wpdb->temas} t
		 LEFT JOIN {$wpdb->taxos} tx ON t.idtema = tx.idtema
		 GROUP BY t.idtema, t.tema
		 ORDER BY count DESC, t.tema ASC
		 LIMIT 10"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

	?>
	<div class="vr-frases-system-info" style="margin-top: 20px;">

		<div class="system-info-grid">
			<!-- System Information Card -->
			<div class="system-info-card">
				<h3><span class="dashicons dashicons-desktop" style="margin-right: 8px;"></span><?php esc_html_e( 'System Information', 'vr-frases' ); ?></h3>
				<table class="system-info-table">
					<tr>
						<td><?php esc_html_e( 'WordPress Version', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( $wp_version ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'PHP Version', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( $php_version ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'MySQL Version', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( $mysql_version ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Plugin Version', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( VR_FRASES_VERSION ); ?></td>
					</tr>
				</table>
			</div>

			<!-- Plugin Statistics Card -->
			<div class="system-info-card">
				<h3><span class="dashicons dashicons-chart-pie" style="margin-right: 8px;"></span><?php esc_html_e( 'Plugin Statistics', 'vr-frases' ); ?></h3>
				<table class="system-info-table">
					<tr>
						<td><?php esc_html_e( 'Total Quotes', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( number_format_i18n( $frases_count ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Total Authors', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( number_format_i18n( $autores_count ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Total Classes', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( number_format_i18n( $clases_count ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Total Themes', 'vr-frases' ); ?>:</td>
						<td><?php echo esc_html( number_format_i18n( $temas_count ) ); ?></td>
					</tr>
				</table>
				
				<div class="quick-links">
					<a href="?page=vrfr_managefrases"><?php esc_html_e( 'Manage Quotes', 'vr-frases' ); ?></a>
					<a href="?page=vrfr_manageautores"><?php esc_html_e( 'Manage Authors', 'vr-frases' ); ?></a>
					<a href="?page=vrfr_manageclases"><?php esc_html_e( 'Manage Classes', 'vr-frases' ); ?></a>
					<a href="?page=vrfr_managetemas"><?php esc_html_e( 'Manage Themes', 'vr-frases' ); ?></a>
				</div>
			</div>

		</div>

		<!-- Second Row: Top Statistics Cards -->
		<div class="system-info-grid">
			<!-- Top Authors Card -->
			<?php if ( ! empty( $autores_stats ) ) : ?>
			<div class="system-info-card">
				<h3><span class="dashicons dashicons-admin-users" style="margin-right: 8px;"></span><?php esc_html_e( 'Top Authors by Quote Count', 'vr-frases' ); ?></h3>
				<div class="authors-list">
					<?php foreach ( $autores_stats as $autor ) : ?>
					<div class="author-item">
						<span><?php echo esc_html( $autor->autor ); ?></span>
						<span><strong><?php echo esc_html( number_format_i18n( $autor->count ) ); ?></strong> <?php esc_html_e( 'quotes', 'vr-frases' ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $autores_stats ) >= 10 ) : ?>
				<p style="margin-top: 10px; font-style: italic; color: #666;">
					<?php esc_html_e( 'Showing top 10 authors. Visit Manage Authors to see all.', 'vr-frases' ); ?>
				</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Top Classes Card -->
			<?php if ( ! empty( $clases_stats ) ) : ?>
			<div class="system-info-card">
				<h3><span class="dashicons dashicons-tag" style="margin-right: 8px;"></span><?php esc_html_e( 'Top Classes by Quote Count', 'vr-frases' ); ?></h3>
				<div class="authors-list">
					<?php foreach ( $clases_stats as $clase ) : ?>
					<div class="author-item">
						<span><?php echo esc_html( $clase->clase ); ?></span>
						<span><strong><?php echo esc_html( number_format_i18n( $clase->count ) ); ?></strong> <?php esc_html_e( 'quotes', 'vr-frases' ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $clases_stats ) >= 10 ) : ?>
				<p style="margin-top: 10px; font-style: italic; color: #666;">
					<?php esc_html_e( 'Showing top 10 classes. Visit Manage Classes to see all.', 'vr-frases' ); ?>
				</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Top Themes Card -->
			<?php if ( ! empty( $temas_stats ) ) : ?>
			<div class="system-info-card">
				<h3><span class="dashicons dashicons-tag" style="margin-right: 8px;"></span><?php esc_html_e( 'Top Themes by Quote Count', 'vr-frases' ); ?></h3>
				<div class="authors-list">
					<?php foreach ( $temas_stats as $tema ) : ?>
					<div class="author-item">
						<span><?php echo esc_html( $tema->tema ); ?></span>
						<span><strong><?php echo esc_html( number_format_i18n( $tema->count ) ); ?></strong> <?php esc_html_e( 'quotes', 'vr-frases' ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $temas_stats ) >= 10 ) : ?>
				<p style="margin-top: 10px; font-style: italic; color: #666;">
					<?php esc_html_e( 'Showing top 10 themes. Visit Manage Themes to see all.', 'vr-frases' ); ?>
				</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Validation and sanitization engine for plugin options.
 *
 * Processes plugin configuration options with validation,
 * sanitization, and fallback values for data integrity.
 *
 * @since 4.1.0
 * @param array $input Raw input array from WordPress options form.
 * @return array Validated and sanitized options array.
 */
function vr_frases_options_validate( $input ) {
	$validated_input                         = array();
	$validated_input['num_inputs']           = isset( $input['num_inputs'] ) ? intval( $input['num_inputs'] ) : 25;
	$validated_input['page_slug']            = isset( $input['page_slug'] ) ? sanitize_text_field( wp_unslash( $input['page_slug'] ) ) : 'frases';
	$validated_input['language']             = isset( $input['language'] ) ? sanitize_text_field( wp_unslash( $input['language'] ) ) : '';
	$validated_input['wiki_lang']            = isset( $input['wiki_lang'] ) ? sanitize_text_field( wp_unslash( $input['wiki_lang'] ) ) : 'en';
	$validated_input['hide_autor']           = isset( $input['hide_autor'] ) ? 1 : 0;
	$validated_input['link_autor']           = isset( $input['link_autor'] ) ? 1 : 0;
	$validated_input['side_autor']           = isset( $input['side_autor'] ) ? 1 : 0;
	$validated_input['sep_lines']            = isset( $input['sep_lines'] ) ? 1 : 0;
	$validated_input['date_format']          = isset( $input['date_format'] ) ? sanitize_text_field( wp_unslash( $input['date_format'] ) ) : 'd/m/Y';
	$ac_bc_format                            = isset( $input['ac_bc_format'] ) ? sanitize_text_field( wp_unslash( $input['ac_bc_format'] ) ) : 'AC';
	$validated_input['ac_bc_format']         = in_array( $ac_bc_format, array( 'AC', 'BC' ), true ) ? $ac_bc_format : 'AC';
	$validated_input['allow_full_uninstall'] = isset( $input['allow_full_uninstall'] ) ? (bool) $input['allow_full_uninstall'] : false;

	return $validated_input;
}

/**
 * Render dynamic language selector with automatic locale detection.
 *
 * Creates language selection dropdown by scanning plugin's language
 * directory for available translation files.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_render_language_option() {
	$options           = get_option( 'vr_frases_options' );
	$selected_language = $options['language'] ?? ''; // Default is empty string, not get_locale().

	// Get the .mo files in the /languages/ folder.
	$languages_path = plugin_dir_path( __DIR__ ) . '/languages/';
	$language_files = glob( $languages_path . 'vr-frases_*.mo' );
	$languages      = array();

	foreach ( $language_files as $file ) {
		$locale               = str_replace( array( 'vr-frases_', '.mo' ), '', basename( $file ) );
		$languages[ $locale ] = $locale;
	}

	// Render the select field.
	echo '<select name="vr_frases_options[language]" id="vr-frases_lang">';
	echo '<option value="" ' . selected( $selected_language, '', false ) . '>';
	echo esc_html( __( 'Default (use site locale)', 'vr-frases' ) );
	echo '</option>';
	foreach ( $languages as $locale ) {
		echo '<option value="' . esc_attr( $locale ) . '" ' . selected( $selected_language, $locale, false ) . '>';
		echo esc_html( $locale );
		echo '</option>';
	}
	echo '</select>';
}

/**
 * Translation mapper for admin interface section headers.
 *
 * Provides centralized translation mapping for section headers
 * in the plugin's information tab accordion interface.
 *
 * @since 4.1.0
 * @param string $key The section identifier that needs translation.
 * @return string Localized section header text or escaped original key.
 */
function vr_frases_translate_section_key( $key ) {
	$translations = array(
		// Plugin information sections.
		'Manage Quotes'                     => __( 'Manage Quotes', 'vr-frases' ),
		'Add New Quote'                     => __( 'Add New Quote', 'vr-frases' ),
		'Manage Authors'                    => __( 'Manage Authors', 'vr-frases' ),
		'Manage Classes and Manage Topics'  => __( 'Manage Classes and Manage Topics', 'vr-frases' ),
		'Manage Import / Export'            => __( 'Manage Import / Export', 'vr-frases' ),
		'Manage Options'                    => __( 'Manage Options', 'vr-frases' ),
		// Widget and shortcode sections.
		'Widget: VR-frases'                 => __( 'Widget: VR-frases', 'vr-frases' ),
		'Widget: Take a look for VR-frases' => __( 'Widget: Take a look for VR-frases', 'vr-frases' ),
		'Shortcode: [vrfrases]'             => __( 'Shortcode: [vrfrases]', 'vr-frases' ),
		'Shortcode: [randomfrase]'          => __( 'Shortcode: [randomfrase]', 'vr-frases' ),
		'Shortcode: [frasescount]'          => __( 'Shortcode: [frasescount]', 'vr-frases' ),
		'Shortcode: [autorescount]'         => __( 'Shortcode: [autorescount]', 'vr-frases' ),
	);

	return isset( $translations[ $key ] ) ? $translations[ $key ] : esc_html( $key );
}
