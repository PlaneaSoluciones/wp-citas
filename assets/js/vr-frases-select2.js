/**
 * VR-Frases Select2 integration file.
 *
 * Manages Select2 initialization and configuration for the VR-Frases plugin.
 *
 * @package    VR-Frases
 * @author     Vicente Ruiz Gálvez
 * @version    4.1.0
 * @license    GPL-2.0+
 * @since 4.1.0
 */

/**
 * Select2 initialization.
 *
 * Initializes Select2 controls when the DOM content is loaded and configures
 * Select2 options for themes and class fields.
 *
 * @since 4.1.0
 * @returns {void}
 */
document.addEventListener(
	"DOMContentLoaded",
	function () {
		/**
		 * Initialize Select2 for form fields.
		 *
		 * Destroys previous instances to prevent duplicates and sets options.
		 *
		 * @since 4.1.0
		 * @returns {void}
		 */
		function initializeSelect2() {
			// Destroy previous select2 instances if they exist (prevents double initialization).
			if (jQuery( ".select2-temas" ).data( "select2" )) {
				jQuery( ".select2-temas" ).select2( "destroy" );
			}
			// Initialize Select2 for themes fields.
			if (jQuery( ".select2-temas" ).length) {
				jQuery( ".select2-temas" ).select2(
					{
						placeholder: vrSelectTranslations.SelTemas,
						allowClear: true,
						multiple: true,
						tags: true,
						// Ensure new tags use the term as id so server-side code can detect non-numeric values.
						createTag: function (params) {
							return {
								id: params.term,
								text: params.term,
								newOption: true,
							};
						},
						width: "resolve",
						language: "es",
					}
				);
			}

		}

		/**
		 * Initialize Select2 on page load and after AJAX requests.
		 *
		 * @since 4.1.0
		 * @returns {void}
		 */
		// Initialize Select2 when the page loads.
		initializeSelect2();

		// Re-initialize Select2 after AJAX requests (WordPress uses jQuery.ajax).
		if (typeof jQuery !== "undefined") {
			jQuery( document ).ajaxComplete(
				function () {
					initializeSelect2();
				}
			);
		}
	}
);
