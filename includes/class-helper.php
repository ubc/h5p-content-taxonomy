<?php
/**
 * Including global helper function. Most of the functions are used by the plugin itself.
 *
 * @since 1.0.0
 * @package ubc-h5p-taxonomy
 */

namespace UBC\H5P\Taxonomy\ContentTaxonomy;

/**
 * Helper utility class.
 */
class Helper {
	/**
	 * Recursively get taxonomy and its children
	 *
	 * @param string $taxonomy taxonomy to retrieve terms from.
	 * @param int    $parent parent term id.
	 * @return array
	 */
	public static function get_taxonomy_hierarchy( $taxonomy, $parent = 0 ) {
		// get all direct descendants of the $parent.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'parent'     => $parent,
			)
		);
		// prepare a new array.  these are the children of $parent
		// we'll ultimately copy all the $terms into this new array, but only after they
		// find their own children.
		$children = array();
		// go through all the direct descendants of $parent, and gather their children.
		foreach ( $terms as $term ) {
			// recurse to get the direct descendants of "this" term.
			$term->children = self::get_taxonomy_hierarchy( $taxonomy, $term->term_id );
			// add the term to our new array.
			array_push( $children, $term );
		}
		// send the results back to the caller.
		return $children;
	}//end get_taxonomy_hierarchy()

	/**
	 * If current page is h5p content listing page.
	 *
	 * @return boolean
	 */
	public static function is_h5p_list_view_page() {
		// phpcs:ignore
		return isset( $_GET['page'] ) && 'h5p' === $_GET['page'] && ! isset( $_GET['task'] ) && ! isset( $_GET['id'] );
	}//end is_h5p_list_view_page()

	/**
	 * Detect if current user is an administrator.
	 *
	 * @return boolean
	 */
	public static function is_role_administrator() {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}//end is_role_administrator()

	/**
	 * Detect if current user is an editor.
	 *
	 * @return boolean
	 */
	public static function is_role_editor() {
		return is_user_logged_in() && ! current_user_can( 'manage_options' ) && current_user_can( 'edit_others_h5p_contents' );
	}//end is_role_editor()

	/**
	 * Detect if current user is an author.
	 *
	 * @return boolean
	 */
	public static function is_role_author() {
		return is_user_logged_in() && ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_others_h5p_contents' ) && current_user_can( 'edit_h5p_contents' );
	}//end is_role_author()

	/**
	 * Detect if current user is a subscriber.
	 *
	 * @return boolean
	 */
	public static function is_role_subscriber() {
		return is_user_logged_in() && ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_others_h5p_contents' ) && ! current_user_can( 'edit_h5p_contents' );
	}//end is_role_subscriber()

	/**
	 * Write stuff to error log for debugging.
	 *
	 * @param object/array/string $log stuff to log.
	 * @return void
	 */
	public static function write_log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}//end write_log()
}
