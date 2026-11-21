# AGENTS.md

## Qué es esto

Plugin de WordPress **WP Citas** para gestionar y mostrar frases/citas con autores. Fork de [VR-Frases](https://github.com/vicenteR/vr-frases) mantenido por Planea Soluciones, en producción.

> El fichero principal sigue llamándose `vr-frases.php` y los prefijos internos son `vr_frases_` / `vr_fr_` por compatibilidad con la BD existente. El nombre público del plugin es **WP Citas**.

## Comandos

```bash
composer install          # dependencias de desarrollo y hook (core.hooksPath .githooks)
composer lint             # PHPCS (WordPress Coding Standards, sin warnings)
composer fix              # phpcbf
vendor/bin/phpstan analyse --memory-limit=768M   # level 5, phpVersion 80000
```

PHP puro sin compilación. Los assets JS/CSS están en `assets/js/` y `assets/css/` (no en `scripts/` ni `css/`).

## Despliegue

1. Cambios → SFTP manual desde VS Code (verificación con el cliente).
2. Aprobado → PR → merge → tag `vX.Y.Z` (ver Releases): `release-php.yml` despliega el ZIP limpio a producción y crea el GitHub Release.

Producción: `isp03.planea.com.es` (SFTP, puerto 22), usuario `andresherrero_com_planea`, ruta `web/wp-content/plugins/wp-citas`. El deploy usa `mirror --delete`: lo que no esté en el ZIP desaparece del servidor.

## Arquitectura

### Actualizaciones

`vr-frases.php` carga Plugin Update Checker (`yahnis-elsts/plugin-update-checker`, dependencia de producción: `vendor/` sí va en el ZIP, como en `timp-events-viewer`). Consulta la última Release de GitHub y descarga su asset `wp-citas.zip`; el resto (aviso de versión, toggle de actualizaciones automáticas, enlace «Comprobar actualizaciones») es el de WordPress. `Update URI` evita las comprobaciones contra wordpress.org.

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

## Workflows

- `pr-title-lint.yml` — valida Conventional Commits en PRs
- `secret-scan.yml` — gitleaks en cada push y PR
- `lint-php.yml` — `php -l` en matriz PHP 8.0–8.5, PHPCS y PHPStan (8.4)
- `release-php.yml` — con tag `v*`: comprueba que cabecera, `VR_FRASES_VERSION` y `Stable tag` coinciden con el tag → `composer install --no-dev` → compila los `.mo` → `wp-citas.zip` (carpeta raíz `wp-citas/`) → deploy SFTP (transitorio) → GitHub Release con las notas de `CHANGELOG.md` y el ZIP como asset. Secrets: `SFTP_HOST`, `SFTP_USERNAME`, `SFTP_PASSWORD`
- `.github/dependabot.yml` — Composer y GitHub Actions, semanal
- Hook `.githooks/pre-commit`: gitleaks + PHPCS sobre lo staged

## Releases

Versión en la cabecera `* Version:` de `vr-frases.php`, `VR_FRASES_VERSION` y `Stable tag:` de `readme.txt` (las tres iguales). `CHANGELOG.md` es la fuente de las notas: mostrar el borrador para aprobación y pasar `composer lint` y PHPStan antes del bump. PR `chore: bump version to X.Y.Z` → merge → `git tag vX.Y.Z` en `main` → `git push origin vX.Y.Z`.

## Pendientes

- PHPStan: 60 errores heredados en `phpstan-baseline.neon` (tipos en `esc_*`, código inalcanzable, `DOING_AJAX`, constantes no detectadas, `register_setting` con args en formato antiguo). Ir eliminándolos del baseline al tocar cada fichero.
- Sin página en BookStack.
- **Quitar el deploy SFTP** cuando esté verificado el actualizador (Plugin Update Checker, desde la release que lo incluya):
  1. Release con el actualizador, desplegada aún por SFTP.
  2. Siguiente release sin el step SFTP: comprobar en producción que llega por WordPress (o MainWP) y se instala bien.
  3. Borrar el step SFTP de `release-php.yml`, los secrets `SFTP_*` y la sección de producción SFTP de este fichero.
