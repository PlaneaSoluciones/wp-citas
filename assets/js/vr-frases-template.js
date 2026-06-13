/**
 * VR-Frases Frontend Template UI Management
 *
 * Manages interactive preferences panels and user settings persistence
 * for the public-facing template system.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

document.addEventListener(
	"DOMContentLoaded",
	function () {
		var btn  = document.getElementById( "toggle-preferences" );
		var bar  = document.querySelector( ".vr-frases-preferences-toggle" );
		var icon = document.getElementById( "toggle-icon" );
		/**
		 * Updates toggle button appearance with dynamic text and icon.
		 *
		 * @since 4.1.0
		 * @returns {void}
		 */
		function setButtonState() {
			if (bar.classList.contains( "hidden" )) {
				icon.innerHTML              = "&#9660;"; // Down arrow.
				btn.childNodes[0].nodeValue =
				(typeof vrFrasesPrefs !== "undefined" ? vrFrasesPrefs.show : "Show preferences") + " ";
			} else {
				icon.innerHTML              = "&#9650;"; // Up arrow.
				btn.childNodes[0].nodeValue =
				(typeof vrFrasesPrefs !== "undefined" ? vrFrasesPrefs.hide : "Hide preferences") + " ";
			}
		}
		/**
		 * Retrieves cookie value by name.
		 *
		 * @since 4.1.0
		 * @param {string} name Cookie name to retrieve.
		 * @returns {string|null} Cookie value or null if not found.
		 */
		function getCookie(name) {
			var match = document.cookie.match( new RegExp( "(^| )" + name + "=([^;]+)" ) );
			return match ? decodeURIComponent( match[2] ) : null;
		}
		if (btn && bar && icon) {
			// Restore state from cookie.
			var prefOpen = getCookie( "vr_frases_preferences_open" );
			if (prefOpen === "0") {
				bar.classList.add( "hidden" );
				btn.classList.add( "collapsed" );
			}
			setButtonState();
			btn.addEventListener(
				"click",
				function () {
					bar.classList.toggle( "hidden" );
					btn.classList.toggle( "collapsed" );
					setButtonState();
					// Guardar estado en cookie
					var isOpen      = ! bar.classList.contains( "hidden" ) ? "1" : "0";
					document.cookie = "vr_frases_preferences_open=" + isOpen + "; path=/; max-age=31536000";
				}
			);
		}

		// Persistence of preferences in cookies.
		var prefsForm = document.getElementById( "vr-frases-preferences" );
		if (prefsForm) {
			var fontSizeSel = prefsForm.querySelector( 'select[name="font_size"]' );
			var numInputs   = prefsForm.querySelector( 'input[name="num_inputs"]' );
			/**
			 * Creates browser cookies for preference persistence.
			 *
			 * @since 4.1.0
			 * @param {string} name Cookie identifier.
			 * @param {string} value Cookie data content.
			 * @param {number} days Expiration period in days.
			 * @returns {void}
			 */
			function setCookie(name, value, days) {
				var expires = "";
				if (days) {
						var date = new Date();
						date.setTime( date.getTime() + days * 24 * 60 * 60 * 1000 );
						expires = "; expires=" + date.toUTCString();
				}
				document.cookie = name + "=" + encodeURIComponent( value ) + expires + "; path=/";
			}

			if (fontSizeSel) {
				fontSizeSel.addEventListener(
					"change",
					function () {
						setCookie( "vr_frases_font_size", fontSizeSel.value, 7 );
						var wrap = document.querySelector( ".wrap" );
						if (wrap) {
							wrap.classList.remove(
								"font-size-default",
								"font-size-small",
								"font-size-medium",
								"font-size-large",
							);
							wrap.classList.add( "font-size-" + fontSizeSel.value );
						}
					}
				);
			}
			if (numInputs) {
				numInputs.addEventListener(
					"change",
					function () {
						setCookie( "vr_frases_num_inputs", numInputs.value, 7 );
					}
				);
			}

			// Frontend pagination functionality (using WordPress standard classes)
			const paginationInput = document.querySelector( ".current-page" );
			if (paginationInput) {
				// El comportamiento de Enter ya está manejado por el atributo onkeypress en el HTML
				// Solo agregamos validación adicional si es necesario

				// Validate input on change (blur is handled by onblur in HTML)
				paginationInput.addEventListener(
					"input",
					function () {
						const value = parseInt( this.value );
						if (isNaN( value ) || value < 1) {
							this.style.borderColor = "#dc3232";
						} else {
							this.style.borderColor = "#ddd";
						}
					}
				);
			}

			// Wikipedia search functionality (if not already present)
			const wikipediaLinks = document.querySelectorAll( ".search-wikipedia" );
			wikipediaLinks.forEach(
				function (link) {
					link.addEventListener(
						"click",
						function (e) {
							e.preventDefault();
							const author = this.getAttribute( "data-autor" );
							if (author) {
								const searchUrl =
									"https://en.wikipedia.org/wiki/Special:Search/" + encodeURIComponent( author );
								window.open( searchUrl, "_blank" );
							}
						}
					);
				}
			);
		}
	}
);
