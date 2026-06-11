<?php
/**
 * VR-Frases Obsolete Files Management
 *
 * This file handles the cleanup of obsolete files and directories after plugin updates.
 * It provides functionality to remove old assets that are no longer needed in newer
 * versions, ensuring clean installations and avoiding conflicts.
 *
 * Key functionalities:
 * - Version-aware file cleanup after plugin updates
 * - Safe removal of obsolete directories and files
 * - Integration with WordPress upgrade system
 * - Logging for debugging and troubleshooting
 *
 * @package     VR_Frases
 * @author      Vicente Ruiz Gálvez
 * @version     4.1.0
 * @since       4.1.0
 * @license     GPL-2.0+
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elimina archivos y carpetas obsoletos tras actualizar el plugin.
 *
 * Esta función se ejecuta después de que WordPress complete la actualización
 * del plugin y verifica que la versión instalada sea 4.1.0 o superior antes
 * de proceder con la limpieza.
 *
 * @since 4.1.0
 * @param object $upgrader_object The upgrader object.
 * @param array  $options Array of update options.
 * @return void
 */
function vr_frases_eliminar_archivos_obsoletos( $upgrader_object, $options ) {
	// Verificar que es una actualización de plugin.
	if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) {
		return;
	}

	// Verificar que el plugin actualizado es vr-frases.
	if ( ! isset( $options['plugins'] ) || ! in_array( VR_FRASES_PLUGIN_BASENAME, $options['plugins'], true ) ) {
		return;
	}

	// Verificar que la versión actual es 4.1.0 o superior antes de limpiar.
	$current_version = get_option( 'vr_frases_version', '1.0' );
	if ( version_compare( $current_version, '4.1.0', '<' ) ) {
		return;
	}

	// Verificar si ya se ejecutó la limpieza para esta versión.
	$cleanup_done = get_option( 'vr_frases_cleanup_done', array() );
	if ( in_array( VR_FRASES_VERSION, $cleanup_done, true ) ) {
		return;
	}

	// Log del inicio de la limpieza.
	vr_frases_log(
		'Iniciando limpieza de archivos obsoletos',
		'info',
		array(
			'version'        => VR_FRASES_VERSION,
			'stored_version' => $current_version,
			'trigger'        => 'upgrader_process_complete',
		)
	);

	// Lista de archivos y carpetas obsoletos a eliminar.
	$rutas_obsoletas = array(
		// Archivos CSS obsoletos.
		'css/select2.min.css',
		'css/vr-frases.css',

		// Archivos de imágenes obsoletos.
		'images/Diseña un logotipo p.png',
		'images/menu.png',

		// Archivos JavaScript obsoletos.
		'scripts/select2.min.js',
		'scripts/vr-frases-scripts.js',
		'scripts/vr-frases-select2.js',
		'scripts/wikipediaSearch.js',

		// Directorios obsoletos (deben ir al final).
		'css/',
		'images/',
		'scripts/',
	);

	$archivos_eliminados = 0;
	$carpetas_eliminadas = 0;
	$errores             = array();

	foreach ( $rutas_obsoletas as $rel_path ) {
		$abs_path = VR_FRASES_PLUGIN_DIR . $rel_path;

		try {
			if ( is_file( $abs_path ) ) {
				if ( vr_frases_eliminar_archivo( $abs_path ) ) {
					++$archivos_eliminados;
				}
			} elseif ( is_dir( $abs_path ) ) {
				if ( vr_frases_eliminar_carpeta_recursiva( $abs_path ) ) {
					++$carpetas_eliminadas;
				}
			}
		} catch ( Exception $e ) {
			$error_msg = sprintf( 'Error eliminando %s: %s', $rel_path, $e->getMessage() );
			$errores[] = $error_msg;
			vr_frases_log( $error_msg, 'error', array( 'file' => $rel_path ) );
		}
	}

	// Log del resultado de la limpieza.
	vr_frases_log(
		'Limpieza de archivos completada',
		'info',
		array(
			'files_removed'       => $archivos_eliminados,
			'directories_removed' => $carpetas_eliminadas,
			'errors_count'        => count( $errores ),
			'errors'              => $errores,
		)
	);

	// Marcar la limpieza como completada para esta versión.
	$cleanup_done[] = VR_FRASES_VERSION;
	update_option( 'vr_frases_cleanup_done', $cleanup_done );

	// Guardar log de la limpieza.
	$cleanup_log = array(
		'version'             => VR_FRASES_VERSION,
		'timestamp'           => time(),
		'files_removed'       => $archivos_eliminados,
		'directories_removed' => $carpetas_eliminadas,
		'errors'              => $errores,
	);
	update_option( 'vr_frases_last_cleanup_log', $cleanup_log );
}

/**
 * Elimina un archivo de forma segura.
 *
 * @since 4.1.0
 * @param string $file_path Ruta absoluta del archivo a eliminar.
 * @return bool True si se eliminó correctamente, false en caso contrario.
 */
function vr_frases_eliminar_archivo( $file_path ) {
	if ( ! file_exists( $file_path ) ) {
		return false;
	}

	if ( ! is_writable( $file_path ) ) {
		return false;
	}

	return unlink( $file_path );
}

/**
 * Elimina una carpeta y todo su contenido de forma recursiva.
 *
 * @since 4.1.0
 * @param string $dir Ruta absoluta del directorio a eliminar.
 * @return bool True si se eliminó correctamente, false en caso contrario.
 */
function vr_frases_eliminar_carpeta_recursiva( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return false;
	}

	$items = scandir( $dir );
	if ( false === $items ) {
		return false;
	}

	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}

		$path = $dir . DIRECTORY_SEPARATOR . $item;
		if ( is_dir( $path ) ) {
			vr_frases_eliminar_carpeta_recursiva( $path );
		} elseif ( file_exists( $path ) && is_writable( $path ) ) {
			unlink( $path );
		}
	}

	// Eliminar el directorio vacío si es escribible.
	if ( is_writable( $dir ) ) {
		return rmdir( $dir );
	}

	return false;
}

/**
 * Verifica si es necesario ejecutar la limpieza de archivos obsoletos.
 *
 * Esta función se integra con el sistema de upgrades existente y se ejecuta
 * solo cuando hay una actualización pendiente.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_maybe_cleanup_obsolete_files() {
	if ( ! is_admin() ) {
		return;
	}

	// Verificar si hay una actualización pendiente o si es una nueva instalación.
	$stored_version = get_option( 'vr_frases_version', '1.0' );
	$cleanup_done   = get_option( 'vr_frases_cleanup_done', array() );

	// Solo ejecutar si:
	// 1. La versión almacenada es anterior a 4.1.0
	// 2. La versión actual es 4.1.0 o superior
	// 3. No se ha ejecutado la limpieza para esta versión
	if ( version_compare( $stored_version, '4.1.0', '<' ) &&
		version_compare( VR_FRASES_VERSION, '4.1.0', '>=' ) &&
		! in_array( VR_FRASES_VERSION, $cleanup_done, true ) ) {

		// Simular el array de opciones para la función de limpieza.
		$mock_options = array(
			'action'  => 'update',
			'type'    => 'plugin',
			'plugins' => array( VR_FRASES_PLUGIN_BASENAME ),
		);

		vr_frases_eliminar_archivos_obsoletos( null, $mock_options );
	}
}

/**
 * Muestra información sobre la última limpieza en el log de upgrades.
 *
 * @since 4.1.0
 * @return void
 */
function vr_frases_get_cleanup_status() {
	$cleanup_log = get_option( 'vr_frases_last_cleanup_log', false );

	if ( ! $cleanup_log ) {
		return __( 'No cleanup performed yet.', 'vr-frases' );
	}

	return sprintf(
		/* translators: 1: version, 2: date, 3: files removed, 4: directories removed */
		__( 'Last cleanup: v%1$s on %2$s. Removed %3$d files and %4$d directories.', 'vr-frases' ),
		$cleanup_log['version'],
		date_i18n( get_option( 'date_format' ), $cleanup_log['timestamp'] ),
		$cleanup_log['files_removed'],
		$cleanup_log['directories_removed']
	);
}

/**
 * Sistema de logging avanzado para debugging y testing.
 *
 * @since 4.1.0
 * @param string $message Mensaje a registrar.
 * @param string $level Nivel del log (info, warning, error, debug).
 * @param array  $context Datos adicionales de contexto.
 * @return void
 */
function vr_frases_log( $message, $level = 'info', $context = array() ) {
	// Solo loggar si WP_DEBUG está activado o si está habilitado el logging del plugin.
	$debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;
	$plugin_debug  = get_option( 'vr_frases_debug_mode', false );

	if ( ! $debug_enabled && ! $plugin_debug ) {
		return;
	}

	$timestamp = current_time( 'Y-m-d H:i:s' );
	$user_info = is_user_logged_in() ? wp_get_current_user()->user_login : 'guest';
	$version   = VR_FRASES_VERSION;

	// Información del contexto.
	$context_str = '';
	if ( ! empty( $context ) ) {
		$context_str = ' | Context: ' . wp_json_encode( $context );
	}

	$log_message = sprintf(
		'[%s] VR-Frases v%s (%s) [%s]: %s%s',
		$timestamp,
		$version,
		$user_info,
		strtoupper( $level ),
		$message,
		$context_str
	);

	// Escribir al error log de WordPress.
	error_log( $log_message );

	// También guardar en un log interno del plugin.
	$internal_logs   = get_option( 'vr_frases_debug_logs', array() );
	$internal_logs[] = array(
		'timestamp' => time(),
		'level'     => $level,
		'message'   => $message,
		'context'   => $context,
		'version'   => $version,
		'user'      => $user_info,
	);

	// Mantener solo los últimos 100 logs.
	if ( count( $internal_logs ) > 100 ) {
		$internal_logs = array_slice( $internal_logs, -100 );
	}

	update_option( 'vr_frases_debug_logs', $internal_logs );
}
