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
			$children[ $term->term_id ] = $term;
		}
		// send the results back to the caller.
		return $children;
	}

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
	}
}
