<?php
/**
 * UBC H5P Addon - Taxonomy
 *
 * @package     UBC H5P
 * @author      Kelvin Xu
 * @copyright   2021 University of British Columbia
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: UBC H5P Addon - Taxonomy
 * Plugin URI:  https://ubc.ca/
 * Description: Add pre-defined taxonomies as dropdown for h5p content.
 * Version:     1.0.0
 * Author:      Kelvin Xu
 * Text Domain: ubc-h5p-taxonomy
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace UBC\H5P\Taxonomy;

define( 'H5P_TAXONOMY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'H5P_TAXONOMY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once 'includes/class-contenttaxonomydb.php';

/**
 * Plugin initialization
 *
 * @return void
 */
function init() {
	if ( ! is_admin() ) {
		return;
	}

	require_once 'includes/class-helper.php';
	require_once 'includes/class-contenttaxonomy.php';
}

add_action( 'plugin_loaded', __NAMESPACE__ . '\\init' );
register_activation_hook( __FILE__, 'UBC\H5P\Taxonomy\ContentTaxonomy\ContentTaxonomyDB::create_database' );
