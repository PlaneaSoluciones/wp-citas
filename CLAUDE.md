# CLAUDE.md

## Qué es esto

Plugin de WordPress **WP Citas** (v4.6.2) para gestionar y mostrar frases/citas con autores. Fork de [VR-Frases](https://github.com/vicenteR/vr-frases) mantenido por Planea Soluciones. Plugin completamente implementado y en producción.

**Repositorio git:** GitHub: [PlaneaSoluciones/wp-citas](https://github.com/PlaneaSoluciones/wp-citas)

> El fichero principal sigue llamándose `vr-frases.php` y los prefijos internos son `vr_frases_` / `vr_fr_` por compatibilidad con la BD existente. El nombre público del plugin es **WP Citas**.

## Despliegue

**Flujo de trabajo:**
1. Cambios → SFTP manual desde VS Code (verificación con cliente)
2. Aprobado → `commit` → `push` → `git tag vX.Y.Z` → `git push --tags`
3. GitHub Actions despliega automáticamente el ZIP limpio a producción y crea el GitHub Release

**Servidor de producción:**
- Host: `isp03.planea.com.es` (puerto 22, SFTP)
- Usuario: `andresherrero_com_planea`
- Ruta del plugin: `web/wp-content/plugins/wp-citas`
- Secrets en GitHub: `SFTP_HOST`, `SFTP_USERNAME`, `SFTP_PASSWORD`

**Pipeline CI/CD (GitHub Actions):**
- `lint.yml` — PHPCS en matrix PHP 8.1/8.2/8.3, se ejecuta en cada push a cualquier rama
- `release.yml` — se activa con tags `v*`: construye ZIP limpio (sin vendor, .github, .githooks, .claude, etc.) → despliega por SFTP con `mirror --delete` → crea GitHub Release

PHP puro sin compilación. Los assets JS/CSS están en `assets/js/` y `assets/css/` (no en `scripts/` ni `css/`).

## Arquitectura

### Flujo de carga

`vr-frases.php` → `vr-frases-loader.php` → carga condicional de módulos según contexto (admin vs frontend). El loader registra también los settings de WP con callback de validación.

### Base de datos

Prefijo de tablas: `{$wpdb->prefix}vr_fr_` (3 tablas activas):

| Tabla | Descripción |
|---|---|
| `frases` | Citas: `idfrase`, `autor`, `frase`, `idclase`, `created_at`, `updated_at` |
| `autores` | Autores: `idautor`, `autor`, `pais`, `nacido`/`muerto` (con enum AC/DC), `datos`, `frasescont` |
| `import` | Staging para importación masiva: `frase`, `autor`, `processed`, `import_date` |

Las migraciones de esquema están en `vr-frases-database.php` → `vr_frases_upgrade()`. La orquestación usa transient locks para evitar ejecuciones concurrentes.

### Shortcodes disponibles

- `[vrfrases]` — Interfaz completa con búsqueda, filtros y paginación
- `[randomfrase]` — Frase aleatoria
- `[frasescount]` — Contador total de frases
- `[autorescount]` — Contador de autores únicos

### JavaScript

- `assets/js/vr-frases-ajax.js` (~1838 líneas): toda la lógica AJAX del admin — CRUD inline con quick-edit, borrado múltiple, importación con detección de duplicados, integración con Select2
- `assets/js/vr-frases-scripts.js`: inicialización de UI (acordeones, drag-drop para import, checkbox masivo)
- `assets/js/wikipediaSearch.js`: búsqueda de autores en Wikipedia desde el admin
- Select2 vendored en `assets/js/select2.min.js`

El cache-busting de assets usa `filemtime()` sobre el fichero.

### Frontend

La plantilla frontend (`admin/vr-frases-template.php`) soporta el tema visual `standard` y persiste preferencias de usuario (estilo, tamaño de fuente, paginación) via cookies y parámetros GET.

### REST API

`includes/vr-register-routes.php` está vacío — los endpoints REST **no están implementados**. Toda la comunicación AJAX del admin usa `wp-admin/admin-ajax.php` con nonces.

## Convenciones

- Prefijo de funciones y hooks: `vr_frases_`
- Prefijo de tablas BD: `$wpdb->prefix . 'vr_fr_'` (ojo: `vr_fr_`, no `vr_frases_`)
- Sanitización: `sanitize_text_field()`, `absint()`, `wp_kses_post()` según el tipo
- Nonces en formularios admin: `wp_nonce_field()` / `check_admin_referer()`
- Assets cargados condicionalmente via `vr-frases-enqueue.php` — solo en páginas del plugin
