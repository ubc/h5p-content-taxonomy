<?php
/**
 * Import/export taxonomies from site to site.
 *
 * @since 1.0.0
 * @package ubc-h5p-taxonomy
 */

namespace UBC\H5P\Taxonomy\ContentTaxonomy;

/**
 * Class to initiate Content Taxonomy export functionalities
 */
class ContentTaxonomyExport {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'initial_output_buffer' ) );
		add_action( 'admin_menu', array( $this, 'create_taxonomy_menus' ), 60 );
		add_action( 'h5p_export_actions', array( $this, 'enqueue_scripts' ) );

		add_action( 'h5p_export_actions', array( $this, 'import_export_taxonomy_actions' ) );
	}

	/**
	 * Fix buffer issue for file download.
	 */
	public function initial_output_buffer() {
		global $pagenow;
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'h5p-export' === $_GET['page'] ) {
			ob_start();
		}
	}

	/**
	 * Create submenus for custom taxonomies we created in create_post_type_and_taxonomies() and put them under H5P plugin main menu.
	 *
	 * @return void
	 */
	public function create_taxonomy_menus() {
		add_submenu_page(
			'h5p',
			__( 'Import/Export', 'ubc-h5p-taxonomy' ),
			__( 'Import/Export', 'ubc-h5p-taxonomy' ),
			'manage_options',
			'h5p-export',
			array( $this, 'import_export_template' )
		);
	}//end create_taxonomy_menus()

	public function import_export_template() {
		do_action( 'h5p_export_actions' );
		?>
			<div id="h5p-export"></div>
		<?php
	}

	/**
	 * Enqueue scripts and styles for import/export page.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'ubc-h5p-export-js',
			H5P_TAXONOMY_PLUGIN_URL . 'assets/dist/js/h5p-export.js',
			array(),
			filemtime( H5P_TAXONOMY_PLUGIN_DIR . 'assets/dist/js/h5p-export.js' ),
			true
		);

		wp_localize_script(
			'ubc-h5p-export-js',
			'ubc_h5p_admin',
			array(
				'export_admin_url' => admin_url( 'admin.php?page=h5p-export' ),
			)
		);

		wp_register_style(
			'ubc-h5p-export-css',
			H5P_TAXONOMY_PLUGIN_URL . '/assets/dist/css/h5p-export.css',
			array(),
			filemtime( H5P_TAXONOMY_PLUGIN_DIR . 'assets/dist/css/h5p-export.css' )
		);
		wp_enqueue_style( 'ubc-h5p-export-css' );
	}

	/**
	 * Accept form actions and respond.
	 *
	 * @return void
	 */
	public function import_export_taxonomy_actions() {
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		if ( 'export_terms' === $_POST['action'] && isset( $_POST['taxonomies'] ) ) {
			ob_end_clean();
			$this->export_terms( sanitize_text_field( wp_unslash( $_POST['taxonomies'] ) ) );
		}

		if ( 'import_terms' === $_POST['action'] && isset( $_FILES['import_terms_file'] ) ) {
			$this->import_terms( $_FILES['import_terms_file'] );
		}
	}

	/**
	 * Handle actions that exports taxonomy terms.
	 *
	 * @param string $taxonomies choose taxonomies to export.
	 * @return void
	 */
	private function export_terms( $taxonomies ) {
		// filename for download.
		$filename = 'export_terms' . gmdate( 'Ymd' ) . '.json';

		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );

		switch ( $taxonomies ) {
			case 'all':
				echo wp_json_encode( array_merge( Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_faculty' ), Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_discipline' ) ) );
				break;
			case 'faculty':
				echo wp_json_encode( Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_faculty' ) );
				break;
			case 'discipline':
				echo wp_json_encode( Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_discipline' ) );
				break;
		}

		die();
	}

	private function import_terms( $file ) {
		if ( isset( $file['error'] ) && 0 !== $file['error'] ) {
			import_failure();
		}

		$content = file_get_contents( $file['tmp_name'] );
		if ( false === $content ) {
			import_failure();
		}

		$content = json_decode( $content );
		if ( false === $content || null === $content ) {
			import_failure();
		}

		foreach ( $content as $key => $term ) {
			// Import first level.
			$parent_id = $this->create_or_update_term( $term );
			$children  = (array) $term->children;
			if ( isset( $children ) && is_array( $children ) && ! empty( $children ) ) {
				foreach ( $children as $key => $child_term ) {
					// Import second level.
					$this->create_or_update_term( $child_term, $parent_id );
				}
			}
		}
	}

	private function create_or_update_term( $given_term, $parent = 0 ) {
		$term = get_term_by( 'slug', $given_term->slug, $given_term->taxonomy );
		if ( false !== $term ) {
			wp_update_term(
				$term->term_id,
				$term->taxonomy,
				array(
					'parent'      => $parent,
					'name'        => $given_term->name,
					'description' => $given_term->description,
				)
			);

			return $term->term_id;
		} else {
			$new_term = wp_insert_term(
				$given_term->name,
				$given_term->taxonomy,
				array(
					'description' => $given_term->description,
					'parent'      => $parent,
					'slug'        => $given_term->slug,
					'name'        => $given_term->name,
				)
			);

			return is_array( $new_term ) ? $new_term['term_id'] : false;
		}
	}

	private function import_failure() {
		$query = $_GET;
		$query['submited'] = 'true';
		$query['success']  = 'false';

		// rebuild url.
		$query_result = http_build_query( $query );
		header( 'location: ' . $_SERVER['PHP_SELF'] . '?' . $query_result );
	}
}

new ContentTaxonomyExport();

