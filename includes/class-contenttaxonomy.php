<?php
/**
 * Content taxonomy class to store and use taxonomy data.
 *
 * @since 1.0.0
 * @package ubc-h5p-taxonomy
 */

namespace UBC\H5P\Taxonomy\ContentTaxonomy;

/**
 * Class to initiate Content Taxonomy functionalities
 */
class ContentTaxonomy {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'create_post_type_and_taxonomies' ) );
		add_action( 'admin_menu', array( $this, 'create_taxonomy_menus' ), 99 );

		add_action( 'show_user_profile', array( $this, 'additional_user_meta_field' ) );
		add_action( 'edit_user_profile', array( $this, 'additional_user_meta_field' ) );
		add_action( 'personal_options_update', array( $this, 'save_additional_user_meta_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_additional_user_meta_field' ) );
		add_action( 'show_user_profile', array( $this, 'enqueue_user_profile_script' ), 10 );
		add_action( 'edit_user_profile', array( $this, 'enqueue_user_profile_script' ), 10 );

		add_action( 'toplevel_page_h5p', array( $this, 'enqueue_listing_view_script' ), 99 );
		add_action( 'load-h5p-content_page_h5p_new', array( $this, 'enqueue_add_new_content_script' ), 10 );

		add_action( 'admin_init', array( $this, 'check_user_faculty' ) );
		add_action( 'wp_ajax_ubc_h5p_list_contents', array( $this, 'list_contents' ) );
		add_filter( 'wp_redirect', array( $this, 'save_h5p_content_taxonomy_on_creation' ) );
	}

	/**
	 * Register fake h5p post type so we can register taxonomies to create relations with real h5p contents.
	 *
	 * @return void
	 */
	public function create_post_type_and_taxonomies() {

		/**
		 * We're never going to create posts for h5p custom post type. It only exists because we want to create custom taxonomies.
		*/
		register_post_type(
			'ubc_h5p_content',
			array(
				'labels'      => array(
					'name'          => __( 'H5P Contents', 'ubc-h5p-taxonomy' ),
					'singular_name' => __( 'H5P Contents', 'ubc-h5p-taxonomy' ),
				),
				'public'      => false,
				'has_archive' => false,
			)
		);

		register_taxonomy(
			'ubc_h5p_content_faculty',
			array( 'ubc_h5p_content' ),
			array(
				'label'        => __( 'Faculty', 'ubc-h5p-taxonomy' ),
				'public'       => true,
				'rewrite'      => false,
				'hierarchical' => true,
			)
		);

		register_taxonomy(
			'ubc_h5p_content_discipline',
			array( 'ubc_h5p_content' ),
			array(
				'label'        => __( 'Discipline', 'ubc-h5p-taxonomy' ),
				'public'       => true,
				'rewrite'      => false,
				'hierarchical' => true,
			)
		);
	}//end create_post_type_and_taxonomies()

	/**
	 * Create submenus for custom taxonomies we created in create_post_type_and_taxonomies() and put them under H5P plugin main menu.
	 *
	 * @return void
	 */
	public function create_taxonomy_menus() {
		add_submenu_page(
			'h5p',
			__( 'Faculty', 'ubc-h5p-taxonomy' ),
			__( 'Faculty', 'ubc-h5p-taxonomy' ),
			'manage_options',
			'edit-tags.php?taxonomy=ubc_h5p_content_faculty'
		);

		add_submenu_page(
			'h5p',
			__( 'Discipline', 'ubc-h5p-taxonomy' ),
			__( 'Discipline', 'ubc-h5p-taxonomy' ),
			'manage_options',
			'edit-tags.php?taxonomy=ubc_h5p_content_discipline'
		);
	}//end create_taxonomy_menus()

	/**
	 * Metafield template for users profiles page.
	 * Allows user to select which faculty they are in.
	 *
	 * @param object $user current logged in user.
	 * @return void
	 */
	public function additional_user_meta_field( $user ) {
		$user_faculty           = get_user_meta( $user->ID, 'user_faculty', true );
		$user_does_have_faculty = ( false !== $user_faculty && is_array( $user_faculty ) && ! empty( $user_faculty ) );
		$faculties              = Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_faculty' );
		?>
			<hr style="margin-top: 40px;padding-bottom: 20px;">
			<h3 id="ubc-faculty"><?php echo esc_textarea( __( 'UBC Faculty', 'ubc-h5p-taxonomy' ) ); ?></h3>
			<?php if ( ! $user_does_have_faculty ) : ?>
			<p style="color: red;"><?php echo esc_textarea( __( 'Faculty information is manditory on the H5P platform.', 'ubc-h5p-taxonomy' ) ); ?></p>
			<?php endif; ?>
			<table class="form-table">
				<tr>
					<th><label for="twitter"><?php echo esc_textarea( __( 'Which faculty do you belong?', 'ubc-h5p-taxonomy' ) ); ?></label></th>
					<td>
						<select name="user_faculty[]" id="user_faculty" multiple style="width: 100%; height: 300px; padding: 10px;">
						<?php foreach ( $faculties as $key => $campus ) : ?>
							<optgroup label="<?php echo esc_textarea( $campus->name ); ?>">
								<?php foreach ( $campus->children as $key => $faculty ) : ?>
									<option value="<?php echo esc_attr( $faculty->term_id ); ?>"<?php echo ( $user_does_have_faculty ) && in_array( $faculty->term_id, $user_faculty ) ? ' selected' : ''; ?>><?php echo esc_textarea( $faculty->name ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_textarea( __( 'Please choose your faculties from the drop down. If your faculty does not in the list, please email lt.hub@ubc.ca.', 'ubc-h5p-taxonomy' ) ); ?></p>
						<p class="description"><?php echo esc_textarea( __( '**Multiselect** De-select in Windows, ctrl + click. De-select in MacOS, Command + click.', 'ubc-h5p-taxonomy' ) ); ?></p>
					</td>
				</tr>
			</table>
			<hr>
		<?php
	}//end additional_user_meta_field()

	/**
	 * Callback to save faculty information on users profile page.
	 *
	 * @param number $user_id the ID of current logged in user.
	 * @return void
	 */
	public function save_additional_user_meta_field( $user_id ) {
		// phpcs:ignore
		$faculty = isset( $_POST['user_faculty'] ) ? array_map(
			function( $fac ) {
				return sanitize_text_field( wp_unslash( $fac ) );
			},
			// phpcs:ignore
			$_POST['user_faculty']
		) : array();

		$result = update_user_meta( $user_id, 'user_faculty', $faculty );
	}//end save_additional_user_meta_field()

	/**
	 * Redirect user to their own profiles page if faculty is not selected.
	 *
	 * @return void
	 */
	public function check_user_faculty() {
		global $pagenow;

		$user_faculty = get_user_meta( get_current_user_id(), 'user_faculty', true );

		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ( false === $user_faculty || empty( $user_faculty ) ) && 'profile.php' !== $pagenow ) {
			wp_safe_redirect( admin_url( 'profile.php?action=faculty_redirect' ) );
			exit;
		}
	}//end check_user_faculty()

	/**
	 * Enqueue necessary Javascript to accomplish the scroll animation on users profile page.
	 *
	 * @return void
	 */
	public function enqueue_user_profile_script() {
		wp_enqueue_script(
			'ubc-h5p-taxonomy-user-profile-js',
			H5P_TAXONOMY_PLUGIN_URL . 'assets/dist/js/h5p-profile.js',
			array(),
			filemtime( H5P_TAXONOMY_PLUGIN_DIR . 'assets/dist/js/h5p-profile.js' ),
			true
		);
	}//end enqueue_user_profile_script()

	/**
	 * Enqueue necessary Javascript for listing view.
	 *
	 * @return void
	 */
	public function enqueue_listing_view_script() {
		if ( isset( $_GET['task'] ) && 'results' === $_GET['task'] ) {
			return;
		}

		if ( isset( $_GET['task'] ) && 'show' === $_GET['task'] ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Remove the original script from h5p.
		wp_deregister_script( 'h5p-data-views' );

		wp_enqueue_script(
			'ubc-h5p-taxonomy-listing-view-js',
			H5P_TAXONOMY_PLUGIN_URL . 'assets/dist/js/h5p-listing-view.js',
			array(),
			filemtime( H5P_TAXONOMY_PLUGIN_DIR . 'assets/dist/js/h5p-listing-view.js' ),
			true
		);

		$user_faculty = get_user_meta( get_current_user_id(), 'user_faculty', true );

		wp_localize_script(
			'ubc-h5p-taxonomy-listing-view-js',
			'ubc_h5p_admin',
			array(
				'user_faculty'           => $user_faculty ? $user_faculty : array(),
				'user_name'              => wp_get_current_user()->user_login,
				'admin_url'              => admin_url(),
				'can_user_editor_others' => current_user_can( 'edit_others_h5p_contents' ),
				'security_nonce'         => wp_create_nonce( 'security' ),
				'faculties_list'         => Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_faculty' ),
				'disciplines_list'       => Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_discipline' ),
			)
		);

		wp_register_style(
			'ubc-h5p-taxonomy-listing-view-css',
			H5P_TAXONOMY_PLUGIN_URL . 'assets/dist/css/h5p-listing-view.css',
			array(),
			filemtime( H5P_TAXONOMY_PLUGIN_DIR . 'assets/dist/css/h5p-listing-view.css' )
		);
		wp_enqueue_style( 'ubc-h5p-taxonomy-listing-view-css' );
	}//end enqueue_listing_view_script()

	/**
	 * Callback to save taxonomy information after H5P content is created.
	 * Not ideal to use wp_redirect filter since WordPress filter is suppose to change stuff not add stuff.
	 * However, due to cusotmization limitation from H5P plugin, this is currently the only way to make it work.
	 *
	 * @param string $location the URL to redirect user to.
	 * @return string $location the URL to redirect user to.
	 */
	public function save_h5p_content_taxonomy_on_creation( $location ) {
		$url_components = wp_parse_url( $location );
		parse_str( $url_components['query'], $params );

		if ( isset( $params['id'] ) && isset( $_REQUEST['ubc-h5p-content-taxonomy'] ) ) {
			// phpcs:ignore
			$this->save_taxonomy( intval( $params['id'] ), $_REQUEST['ubc-h5p-content-taxonomy'] );
		}

		return $location;
	}//end save_h5p_content_taxonomy_on_creation()

	/**
	 * Parse the JSON string of the taxonomy information and save them into the database.
	 *
	 * @param string $id ID of the current H5P content.
	 * @param string $taxonomy_json_string The JSON string which includes taxonomy information related to current H5P content.
	 * @return void
	 */
	private function save_taxonomy( $id, $taxonomy_json_string ) {
		$tax = json_decode( html_entity_decode( stripslashes( $taxonomy_json_string ) ) );

		if ( false === $tax || ! is_array( $tax->faculty ) || ! is_array( $tax->discipline ) ) {
			return;
		}

		// Remove all the rows attached to current H5P content before add new ones.
		ContentTaxonomyDB::clear_content_terms( $id );

		// Attach faculties to current H5P content.
		if ( is_array( $tax->faculty ) && count( $tax->faculty ) > 0 ) {
			foreach ( $tax->faculty as $key => $faculty ) {
				ContentTaxonomyDB::insert_content_term( $id, intval( $faculty ), 'faculty' );
			}
		}

		// Attach discipline to current H5P content.
		if ( is_array( $tax->discipline ) && count( $tax->discipline ) > 0 ) {
			foreach ( $tax->discipline as $key => $discipline ) {
				ContentTaxonomyDB::insert_content_term( $id, intval( $discipline ), 'discipline' );
			}
		}
	}//end save_taxonomy()

	/**
	 * Load assets for h5p new content page.
	 *
	 * @return void
	 */
	public function enqueue_add_new_content_script() {
		wp_enqueue_script(
			'ubc-h5p-taxonomy-js',
			H5P_TAXONOMY_PLUGIN_URL . 'assets/dist/js/h5p-new.js',
			array(),
			filemtime( H5P_TAXONOMY_PLUGIN_DIR . 'assets/dist/js/h5p-new.js' ),
			true
		);

		$user_faculty = get_user_meta( get_current_user_id(), 'user_faculty', true );

		wp_localize_script(
			'ubc-h5p-taxonomy-js',
			'ubc_h5p_admin',
			array(
				'faculties_list'     => Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_faculty' ),
				'disciplines_list'   => Helper::get_taxonomy_hierarchy( 'ubc_h5p_content_discipline' ),
				'user_faculty'       => $user_faculty,
				'id'                 => isset( $_GET['id'] ) ? intval( $_GET['id'] ) : null,
				'content_faculty'    => isset( $_GET['id'] ) ? ContentTaxonomyDB::get_content_terms_by_taxonomy( intval( $_GET['id'] ), 'faculty' ) : null,
				'content_discipline' => isset( $_GET['id'] ) ? ContentTaxonomyDB::get_content_terms_by_taxonomy( intval( $_GET['id'] ), 'discipline' ) : null,
			)
		);

		wp_register_style(
			'ubc-h5p-taxonomy-css',
			H5P_TAXONOMY_PLUGIN_URL . '/assets/dist/css/h5p-new.css',
			array(),
			filemtime( H5P_TAXONOMY_PLUGIN_DIR . 'assets/dist/css/h5p-new.css' )
		);
		wp_enqueue_style( 'ubc-h5p-taxonomy-css' );
	}//end enqueue_add_new_content_script()

	/**
	 * Ajax handler to list correct H5P contents for editors and authors.
	 *
	 * @return void
	 */
	public function list_contents() {
		check_ajax_referer( 'security', 'nonce' );

		$context = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : 'self';
		$sortby  = isset( $_POST['sortby'] ) ? intval( $_POST['sortby'] ) : 0;
		$revert  = isset( $_POST['revert'] ) && 'true' === $_POST['revert'];
		$limit   = isset( $_POST['limit'] ) ? sanitize_text_field( wp_unslash( $_POST['limit'] ) ) : null;
		$offset  = isset( $_POST['offset'] ) ? sanitize_text_field( wp_unslash( $_POST['offset'] ) ) : null;
		$search  = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : null;
		$terms   = isset( $_POST['terms'] ) ? json_decode($_POST['terms']) : array();

		// Nothing is changed for administrator or network administrator.
		if ( current_user_can( 'manage_options' ) ) {
			wp_send_json_success( $contents );
		}

		// If user has editor role. Then they should be able to see their own contents + contents within their assigned faculty.
		if ( current_user_can( 'edit_others_h5p_contents' ) ) {
			$contents = ContentTaxonomyDB::get_contents( $context, $sortby, $revert, $limit, $offset, $search, $terms );
			wp_send_json_success( $contents );
		}

		// If user has author role. Then they should only be able to see their own contents.
		if ( ( current_user_can( 'edit_h5p_contents' ) && 'faculty' === $context ) || ! current_user_can( 'edit_h5p_contents' ) ) {
			wp_send_json_success( array() );
		}

		$contents = ContentTaxonomyDB::get_contents( $context, $sortby, $revert, $limit, $offset, $search, $terms );
		wp_send_json_success( $contents );
	}//end list_contents()
}

new ContentTaxonomy();

