# Changelog

Todos los cambios notables de WP Citas se documentan aquí.  
Formato: [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) · Versioning: [SemVer](https://semver.org/lang/es/).

## [Unreleased]

### Cambiado
- Requiere PHP 8.2 o superior (antes 8.0): PHP 8.0 y 8.1 ya no reciben actualizaciones
  de seguridad.
- Compatibilidad comprobada hasta WordPress 7.1.

## [4.7.1] - 2026-11-21

### Cambiado
- Compatibilidad comprobada hasta WordPress 7.1.5.
- Actualizado el componente de actualizaciones (Plugin Update Checker) a la versión 6.0.

## [4.7.0] - 2026-11-21

### Añadido
- El plugin se actualiza desde WordPress como cualquier otro: avisa de las versiones nuevas
  publicadas en GitHub, permite activar las actualizaciones automáticas e incluye el enlace
  «Comprobar actualizaciones» en el listado de plugins.

### Corregido
- La constante interna `VR_FRASES_VERSION` ahora está sincronizada con la versión del plugin,
  previniendo posibles inconsistencias en funciones que dependen de ese valor.
- `readme.txt` mostraba una versión estable y un PHP mínimo que no correspondían al plugin:
  ahora coinciden con los de la cabecera.
- La traducción al español se incluye compilada en el paquete del plugin.

## [4.6.2] - 2026-06-17

### Corregido
- La búsqueda de autores ya no se dispara con cada pulsación de tecla, reduciendo
  las consultas innecesarias al servidor.

## [4.6.1] - 2026-06-17

### Añadido
- Nueva opción para exportar las frases de un autor directamente desde la pantalla
  de gestión de frases.

## [4.6.0] - 2026-06-14

### Añadido
- Las columnas "Autor" y "Nº de citas" en la pantalla de gestión de autores ahora
  son ordenables con un clic.

## [4.5.1] - 2026-06-14

### Corregido
- Al ordenar por autor, las frases de cada autor se muestran ahora en orden alfabético.

## [4.5.0] - 2026-06-14

### Añadido
- La pantalla de gestión de frases ahora permite ordenar los resultados por columna
  haciendo clic en la cabecera.

## [4.4.2] - 2026-06-14

### Corregido
- Ajustes internos de código sin impacto en el comportamiento del plugin.

## [4.4.1] - 2026-06-14

### Corregido
- Corregido el botón de eliminar autor. Ahora muestra un diálogo de confirmación
  para evitar eliminaciones accidentales.

## [4.4.0] - 2026-06-13

### Añadido
- Nueva opción para guardar todas las frases importadas de una sola vez desde
  la pantalla de importación.

### Eliminado
- Se elimina el sistema de "temas" visuales del plugin; el diseño se hereda del
  tema activo de WordPress.

## [4.2.1] - 2026-06-13

### Eliminado
- Se eliminan los estilos visuales propios del frontend. El plugin ahora se adapta
  al diseño del tema activo de WordPress.

## [4.2.0] - 2026-06-13

### Eliminado
- Se elimina el sistema de "clases" (categorías de frases) para simplificar la
  gestión del plugin.

## [4.1.7] - 2026-06-13

### Corregido
- Los estilos del plugin ya no se cargan en páginas del panel ajenas al plugin,
  previniendo conflictos visuales con otros plugins instalados.

## [4.1.6] - 2026-06-12

### Corregido
- Importación de CSV/TXT mejorada: se procesan correctamente ficheros con marca BOM,
  línea de cabecera y codificación UTF-8.

## [4.1.5] - 2026-06-12

### Cambiado
- Al desinstalar el plugin ahora se requiere confirmación explícita antes de eliminar
  todos los datos, evitando pérdidas accidentales.

## [4.1.4] - 2026-06-12

### Corregido
- El ZIP del plugin incluye ahora la estructura de carpeta correcta (`wp-citas/`),
  asegurando que las actualizaciones automáticas de WordPress funcionen sin problemas.

## [4.1.3] - 2026-06-12

### Cambiado
- El menú del plugin en el panel de WordPress aparece ahora como "WP Citas"
  en lugar de "VR-Frases".

## [4.1.2] - 2026-06-12

### Corregido
- Eliminados includes redundantes y actualizados los metadatos del plugin.

## [4.1.1] - 2026-06-11

### Corregido
- Corregido un problema de estabilidad en la base de datos que podía impedir la
  instalación correcta en algunos servidores.
