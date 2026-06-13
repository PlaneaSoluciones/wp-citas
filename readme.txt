=== WP Citas ===
Contributors:       vruizg, planeasoluciones
Tags:               quotes, random, frases, citas, citations, sayings, wisdom, inspirational, authors
Requires at least:  5.5
Tested up to:       6.8.2
Requires PHP:       7.2
Stable tag:         4.2.0
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Organize and display quotes with author management, themes, and search functionality. Includes widgets, shortcodes, and import/export features.

== Description ==

WP Citas is a comprehensive quote management plugin that allows you to organize, categorize, and display collections of famous quotes and phrases. Perfect for websites that want to share inspirational content, literary quotes, or wisdom from notable figures.

This is a maintained fork of VR-Frases by Vicente Ruiz, continuing under the same GPLv2 license.

**Key Features:**

* **Quote Management**: Add, edit, and organize quotes with full CRUD functionality
* **Author Profiles**: Manage author information including biographical data and Wikipedia links
* **Theme System**: Organize quotes using themes (tags)
* **Search & Filter**: Advanced search functionality by author or theme
* **Import/Export**: Bulk import quotes from CSV/TXT files and export your collection
* **Display Options**: Multiple ways to display quotes on your site
* **Responsive Design**: Mobile-friendly interface and displays

**Display Methods:**

* **Random Quote**: Show a random quote anywhere using `[randomfrase]` shortcode
* **Quote Collection**: Display all quotes with search functionality using `[vrfrases]` shortcode
* **Widget**: Add random quotes to sidebars and widget areas

**Admin Features:**

* Intuitive dashboard for managing quotes, authors and themes
* Quick edit functionality for fast updates
* Bulk operations for efficient management
* Import wizard for adding multiple quotes at once
* Export functionality to backup or share your quote collection
* Author management with biographical information and Wikipedia integration

**For Developers:**

* Clean, well-documented code following WordPress standards
* Template functions for custom implementations
* Hooks and filters for extensibility
* Database optimization with proper indexing

== Installation ==

**Manual Installation:**

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the files
4. Activate the plugin through the 'Plugins' menu of WordPress
5. Go to WP Citas → Settings to configure the plugin

**Setup:**

1. After activation, go to WP Citas → Settings
2. Configure your preferences (page slug, display options, etc.)
3. Create a new page for displaying quotes
4. Add the `[vrfrases]` shortcode to that page
5. Start adding quotes through WP Citas → Manage Quotes

**Upgrading:**

The plugin includes automatic upgrade routines. Your data will be preserved during updates. Always backup your database before major updates.

== Frequently Asked Questions ==

= How do I display quotes on my website? =

You have several options:
* Use the `[vrfrases]` shortcode on any page or post to display the full quote collection with search
* Use `[randomfrase]` to display a random quote anywhere
* Add the WP Citas widget to your sidebar
* Use template functions in your theme files

= Can I import quotes from other sources? =

Yes! The plugin includes an import feature that accepts CSV and TXT files. Format your file with "Quote" and "Author" columns, separated by commas and enclosed in quotes.

= How do I organize my quotes? =

Use the classification system:
* **Themes**: Act like tags (e.g., "love", "success", "wisdom")
* **Authors**: Manage author information and link to Wikipedia

= Is the plugin translation ready? =

Yes! The plugin is fully internationalized and ready for translation. Language files are included, and you can create custom translations for your language.

= Can I customize the appearance? =

Yes! The plugin includes CSS classes for styling. You can:
* Override the default styles in your theme
* Use the built-in style options in settings
* Customize templates if you're a developer

= What happens to my data if I deactivate the plugin? =

Your data remains in the database when you deactivate the plugin. Only when you delete the plugin (and confirm in settings) will the data be removed.

= Can I backup my quotes? =

Yes! Use the Export feature to download your quotes as CSV or TXT files. This creates a backup you can re-import later or use elsewhere.

= How to use the plugin? =

**Managing Quotes**
* This is the main dashboard where all quotes are displayed with results based on selected criteria.
* "Quick Edit" allows changing Themes; "Edit" modifies all fields.

**Adding New Quotes**
* Form for adding quotes individually.
* All fields are required.
* If the quote and author already exist, an error will be shown and it won't be processed.

**Managing Authors**
* List of all registered authors.
* Each author is created automatically when adding or importing a quote.
* Quick edit allows completing birthplace, birth/death dates, and biographical notes.
* For dates before Christ, check "BC" and use dd/mm/yyyy format.
* Filter complete authors or those pending data.

**Managing Themes**
* Allows adding or modifying tags (Themes).
* In the add field, separate multiple values with commas.
* Duplicate values will not be processed.

**Import/Export Quotes**
* Import CSV or TXT with "Quote" and "Author" fields in quotes and separated by commas.
* Data is copied to an intermediate table for processing: assign Themes before saving.
* When saving, the quote goes to the main table and the author to the authors table.
* Export quotes and authors to CSV or TXT with similar format.

**Managing Options**
* Modify plugin options from a clear and descriptive panel.
* The most important option is the "slug" of the page where you'll display your quotes.
* Create a blank page with that slug and insert the [vrfrases] shortcode.

**Included Shortcodes**
* [vrfrases]
  Displays the plugin's main page and search form.
* [randomfrase]
  Inserts a random quote in the content of a post or page.
  In your templates: `<?php echo vr_frases_random_frase(); ?>`
* [frasescount]
  Returns an integer with the total number of stored quotes.
* [authorscount]
  Returns an integer with the total number of stored authors.

== Screenshots ==

1. Manage Quotes - Main dashboard showing all quotes with search and filter options
2. Add New Quotes - Form to add new quotes into database
3. Manage Themes - Tag management for quote categorization
4. Manage Authors - Author management panel with biographical information
5. Author Details - Displays the author information details on single search
6. Manage Import - Bulk import interface for CSV/TXT files with preview and assignment options
7. Manage Export - Form to export your main Quotes database, included Author name
8. Manage Options - Configuration options for customizing plugin behavior
9. System Info - Displays information about system and simple plugin stats
10. Main Page (user view) - Front-end display showing quotes with search functionality
11. Author details (user view) - Filtered results showing quotes by author and author details
12. Widget (detail) - Sidebar widget showing random quotes with customization options

== Changelog ==

= 4.2.0 =
*Release Date: June 13, 2026*

* **Removed**: Classes (categories) feature completely eliminated — quotes are now organized only by Themes

= 4.1.7 =
*Release Date: 2026*

* **Fixed**: Restrict admin styles to plugin pages to avoid conflicts with other plugins

= 4.1.0 =
*Release Date: October 15, 2025*

* **Improved**: Main plugin structure with better organization of constants and initialization code
* **Improved**: Error handling and user notifications with enhanced success/error/warning message system
* **Improved**: New front-end user page design (user view)
* **Improved**: Overlay and spinner updated to show waiting time while results are being refreshed
* **Improved**: Pagination system completely optimized and redesigned for better performance
* **Improved**: Duplicate detection system enhanced when importing files
* **Added**: Complete AJAX migration for all admin functionality including add, edit, quick-edit, and delete operations
* **Added**: Loading overlays for all data update actions in both admin and frontend interfaces
* **Added**: Options bar in the user view to select layout, font size and number of records per page
* **Added**: Use of cookies to preserve user preferences
* **Added**: New random ordering type available in both front-end and back-end
* **Added**: System requirements verification to ensure compatibility
* **Added**: Automatic cleanup system for obsolete files and directories after plugin updates
* **Added**: Tab in Options page with System Info and simple stats of the plugin, including Top 10 lists
* **Enhanced**: Code documentation and standardization throughout the plugin
* **Enhanced**: AJAX implementation for seamless user experience without page reloads
* **Fixed**: Various minor bugs and compatibility issues
* **Removed**: Cache/transient system eliminated for improved performance and reliability
* **Security**: Enhanced nonce verification and input sanitization
* **Restructured**: Assets organization - moved CSS, screenshots, JS and images to /assets/ folder

= 4.0.7 =
*Release Date: May 26, 2025*

* **Fixed**: Wikipedia author links functionality restored
* **Added**: Export functionality - new tab for exporting quotes to CSV or TXT files
* **Improved**: Import/Export interface organization

= 4.0.6 =
*Release Date: May 26, 2025*

* **Fixed**: Nonce validation error when saving changes to Themes in imported quotes
* **Fixed**: Translation issues - textdomain loading corrected
* **Added**: Language selection option with support for custom .mo and .po files
* **Added**: Display of record count in Authors list for better navigation

= 4.0.5 =
*Release Date: May 22, 2025*

* **Added**: Uninstall verification option to prevent accidental data loss
* **Added**: New help tab in Options page with comprehensive function descriptions
* **Added**: Documentation for all widgets and shortcodes
* **Fixed**: Pagination error in Authors list management

= 4.0.4 =
*Release Date: May 18, 2025*

* **Verified**: Compatibility with WordPress 6.8.1
* **Added**: Authors table link in dashboard widget for quick access
* **Improved**: Enhanced readme.txt with detailed information and screenshot descriptions

= 4.0.3 =
*Release Date: May 5, 2025*

* **Added**: Author biographical data display in search results (both admin and user pages)
* **Fixed**: Minor syntax errors and compatibility issues

= 4.0.2 =
*Release Date: May 3, 2025*

* **Fixed**: Minor syntax bugs and code optimization
* **Added**: Wikipedia author links on front-end display
* **Restructured**: Modularized codebase - moved admin files to /admin/ folder and includes to /includes/ folder

= 4.0.0 =
*Release Date: February 28, 2024*

* **Verified**: Compatibility with WordPress 6.8
* **Fixed**: Page loading issues in older WordPress versions resolved
* **Added**: AJAX functionality for improved admin interface management
* **Added**: Bulk import feature for quotes and authors from CSV/TXT files
* **Added**: Select2 controls for enhanced user experience managing quotes, authors and themes
* **Added**: Dedicated Authors table with Wikipedia integration
* **Added**: New taxonomies table for improved quote and theme relationships
* **Major**: Complete codebase restructure for better performance and maintainability

= 3.0.1 =
*Release Date: March 11, 2017*

* **Fixed**: Error in search functions corrected

= 3.0.0 =
*Release Date: February 23, 2017*

* **Verified**: Compatibility with WordPress 4.7.2
* **Changed**: Relocated settings page to main menu
* **Improved**: Updated CSS for main page styling
* **Fixed**: Various minor bug fixes

= 2.0.2 =
*Release Date: September 2011*

* **Added**: Option to hide author name in Widget and [randomfrase] shortcode
* **Added**: Warning notice when attempting to upgrade from very old versions

= 2.0.1 =
*Release Date: September 1, 2011*

* **Fixed**: Minor bugs in dashboard widget
* **Added**: Automatic detection of previous installations during plugin activation

= 2.0.0 =
*Release Date: August 31, 2011*

* **Major Release**: Code completely rewritten to optimize functions and database queries
* **Added**: New functions in admin area to edit and delete items
* **Added**: Search form for admin area
* **Added**: Bulk delete functionality via checkbox selection
* **Added**: New dashboard widget for quick overview
* **Added**: CSS Style Sheets for main page customization
* **Added**: Automatic uninstall hook to remove options and database tables when deleting plugin

= 1.0 =
*Release Date: March 15, 2006*

* **Initial Release**: First stable version of VR-Frases plugin
