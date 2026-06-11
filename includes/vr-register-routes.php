<?php
/**
 * VR-Frases REST API Routes and Endpoint Registration
 *
 * This file handles the registration and configuration of REST API endpoints
 * for the VR-Frases plugin, providing programmatic access to quotes, authors,
 * classes, and themes data. It enables integration with external applications
 * and supports modern web development patterns.
 *
 * API endpoint structure:
 * - Quote endpoints for CRUD operations and filtering
 * - Author endpoints with biographical data access
 * - Class endpoints for categorization management
 * - Theme endpoints with taxonomic relationship support
 * - Search and filtering capabilities across all data types
 * - Pagination and sorting options for large result sets
 *
 * Security and validation features:
 * - Authentication and permission checks for write operations
 * - Input validation and sanitization for all endpoints
 * - Rate limiting and abuse protection mechanisms
 * - CORS support for cross-origin requests
 * - Nonce validation for WordPress integration
 *
 * @package     VR_Frases
 * @author      Vicente Ruiz Gálvez
 * @version     4.1.0
 * @license     GPL-2.0+
 */

// Prevent direct access to file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
