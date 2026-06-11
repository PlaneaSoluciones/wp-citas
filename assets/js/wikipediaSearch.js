/**
 * VR-Frases Wikipedia Integration Module
 *
 * Provides author research capabilities through Wikipedia's API
 * with multi-language support and error handling.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

// Browser-compatible configuration for WordPress frontend integration.
// Note: This module is designed for browser environments, not Node.js
// For Node.js environments, uncomment the following line:
// const fetch = require("node-fetch");

// Default language configuration from global translations.
// In browser environments, this will be provided by WordPress localization
const wikilang =
	typeof vrFrasesTranslations !== "undefined" && vrFrasesTranslations.wikilang
	? vrFrasesTranslations.wikilang
	: "es"; // Use 'es' as default if not defined.

/**
 * Searches Wikipedia for author information.
 *
 * @since 4.1.0
 * @param {string} authorName Author name to search for.
 * @param {string} [lang=wikilang] Wikipedia language subdomain.
 * @returns {Promise<string|null>} Wikipedia article URL or null.
 */
async function searchWikipedia(authorName, lang = wikilang) {
	// Input validation.
	if ( ! authorName || typeof authorName !== "string") {
		console.error( "Invalid author name." );
		return null;
	}

	// URL-encode the author name for the API request.
	const encodedAuthorName = encodeURIComponent( authorName.trim() );
	const apiUrl            = `https:// ${lang}.wikipedia.org/w/api.php?action=query&list=search&srsearch=${encodedAuthorName}&format=json`;

	try {
		// Make request to Wikipedia API.
		const response = await fetch( apiUrl );

		// Check for HTTP errors.
		if ( ! response.ok) {
			console.error( `Wikipedia API request error: ${response.statusText}` );
			return null;
		}

		// Parse response JSON.
		const data = await response.json();

		// Validate response structure and extract page title.
		if (data.query && data.query.search && data.query.search.length > 0) {
			const pageTitle = data.query.search[0].title;
			return `https:// ${lang}.wikipedia.org/wiki/${encodeURIComponent(pageTitle)}`;
		} else {
			console.warn( "No Wikipedia results found for:", authorName );
			return null; // No relevant page found
		}
	} catch (error) {
		console.error( "Error making Wikipedia API request:", error );
		return null;
	}
}

// Universal module exports for browser and Node.js compatibility.
if (typeof module !== "undefined" && module.exports) {
	// Node.js environment
	module.exports = { searchWikipedia };
} else if (typeof window !== "undefined") {
	// Browser environment - attach to global window object
	window.wikipediaSearch = { searchWikipedia };
}
