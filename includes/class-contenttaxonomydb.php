<?php
/**
 * Content TaxonomyDB class for underling database process.
 *
 * @since 1.0.0
 * @package ubc-h5p-taxonomy
 */

namespace UBC\H5P\Taxonomy\ContentTaxonomy;

/**
 * Class to initiate ContentTaxonomyDB functionalities
 */
class ContentTaxonomyDB {

	/**
	 * Version of the database.
	 *
	 * @since    1.0.0
	 * @var      $db_version
	 */
	private static $db_version = '0.2.2';

	/**
	 * Create database table when plugin activates.
	 * It won't run the database table creation again if the db_version is match the one saved in options table.
	 *
	 * @return void
	 */
	public static function create_database() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( get_option( 'h5p_taxonomy_db_version', '' ) === self::$db_version ) {
			return;
		}

		// Get charset to use.
		$charset = self::determine_charset();

		$test = dbDelta(
			"CREATE TABLE {$wpdb->prefix}h5p_contents_taxonomy (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			content_id INT UNSIGNED NOT NULL,
			term_id INT UNSIGNED NOT NULL,
			taxonomy VARCHAR(255) NOT NULL,
			PRIMARY KEY  (id)
		  ) {$charset};"
		);

		update_option( 'h5p_taxonomy_db_version', self::$db_version );
	}//end create_database()

	/**
	 * Determine charset to use for database tables
	 *
	 * @since 1.2.0
	 * @global \wpdb $wpdb
	 */
	public static function determine_charset() {
		global $wpdb;
		$charset = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset = "DEFAULT CHARACTER SET {$wpdb->charset}";

			if ( ! empty( $wpdb->collate ) ) {
				$charset .= " COLLATE {$wpdb->collate}";
			}
		}
		return $charset;
	}//end determine_charset()

	/**
	 * Remove all the rows in content taxonomy relation table based on content ID.
	 *
	 * @param int $content_id ID of the content to remove.
	 * @return void
	 */
	public static function clear_content_terms( $content_id ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}h5p_contents_taxonomy WHERE content_id = %d", $content_id ) );
	}//end clear_content_terms()

	/**
	 * Remove all the rows in content taxonomy relation table based on content ID and term type.
	 *
	 * @param int $content_id ID of the content to remove.
	 * @param int $term_type type of the term, faculty, discpline, group or etc.
	 * @return void
	 */
	public static function clear_content_terms_by_type( $content_id, $term_type ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}h5p_contents_taxonomy WHERE content_id = %d and taxonomy = %s", $content_id, $term_type ) );
	}//end clear_content_terms_by_type()

	/**
	 * List all the H5P contents based on query.
	 *
	 * @param string $context either the query is for user themselves or their faculties.
	 * @param string $sortby the field to sort the content by.
	 * @param string $reverse_order whether sort by reverse order.
	 * @param int    $limit Max number of rows to return.
	 * @param int    $offset skip this many rows.
	 * @param string $search terms to search.
	 * @param bool   $term_ids term ids to filter the result by. Only expect at most two terms to be searched.
	 * @param array  $tags array of tags attached to current h5p content.
	 * @return array query results.
	 */
	public static function get_contents( $context = 'self', $sortby = 0, $reverse_order = false, $limit = null, $offset = null, $search = null, $term_ids = array(), $tags = array() ) {
		if ( ! class_exists( 'H5PContentQuery' ) ) {
			return array();
		}

		$order_by_array = array(
			'hc.updated_at',
			'hc.title',
			'hl.title',
			'u.display_name',
			'hc.id',
		);

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$user_faculty_ids = get_user_meta( get_current_user_id(), 'user_faculty', true );

		$base_select = "SELECT hc.title AS title, hl.title AS content_type, u.display_name AS user_name, GROUP_CONCAT(DISTINCT t.name SEPARATOR ';') AS tags, hc.updated_at AS updated_at, hc.id AS id, u.ID AS user_id, hl.name AS content_type_id";
		$base_select .= ', ' . implode( ', ', apply_filters( 'h5p_add_field_to_query_response', array() ) );

		$base_count  = 'SELECT COUNT(*)';

		$base_query = ' FROM ' . $wpdb->prefix . 'h5p_contents hc
			LEFT JOIN ' . $wpdb->prefix . 'h5p_libraries hl ON hl.id = hc.library_id 
			LEFT JOIN ' . $wpdb->prefix . 'users u ON hc.user_id = u.ID 
			LEFT JOIN ' . $wpdb->prefix . 'h5p_contents_tags ct ON ct.content_id = hc.id
			';

		$groupby_query = ' GROUP BY hc.id';

		/** Tag Query */

		// Filter content based on tags selected.
		$tag_query = '';

		if ( ! empty( $tags ) ) {
			$tag_ids = array_map(
				function( $tag ) {
					return $tag->value;
				},
				$tags,
			);

			$tag_query = ' RIGHT JOIN (SELECT *  FROM ' . $wpdb->prefix . 'h5p_tags WHERE id in (' . implode( ',', $tag_ids ) . ') ) t ON ct.tag_id = t.id';
		} else {
			$tag_query = ' LEFT JOIN ' . $wpdb->prefix . 'h5p_tags t ON ct.tag_id = t.id';
		}
		/** End Tag Query */

		/** Term Query */
		$term_ids = apply_filters( 'h5p_content_taxonomy_terms', $term_ids, $context );

		if ( 0 < count( $term_ids ) ) {
			$term_query = ' RIGHT JOIN (SELECT content_id FROM ' . $wpdb->prefix . 'h5p_contents_taxonomy WHERE term_id IN (' . implode( ',', $term_ids ) . ') group by content_id having count(*)>=' . count( $term_ids ) . ') ctt on ctt.content_id = hc.id';
		} else {
			$term_query = '';
		}
		/** End Term Query */

		// Based on the context whether the query is for user only, or for faculty.
		switch ( $context ) {
			case 'self':
				$context_query = ' WHERE u.ID = ' . get_current_user_id();
				break;
			case 'faculty':
				$context_query = ' WHERE hc.id IN (SELECT DISTINCT content_id from ' . $wpdb->prefix . 'h5p_contents_taxonomy WHERE term_id IN (' . implode( ',', $user_faculty_ids ) . ')) AND u.ID != ' . get_current_user_id();
				break;
			case 'admin':
				$context_query = ' WHERE u.ID != ' . get_current_user_id();
				break;
		}
		$context_query    = apply_filters( 'h5p_content_taxonomy_context_query', $context_query, $context );
		$search_query     = empty( $search ) ? '' : " AND ( hc.title LIKE '%" . $search . "%' OR u.display_name LIKE '%" . $search . "%' )";
		$sortby_query     = ' ORDER BY ' . $order_by_array[ $sortby ] . ( $reverse_order ? ' ASC' : ' DESC' );
		$pagination_query = ' LIMIT ' . ( $offset ? $offset : '0' ) . ' ,' . ( $limit ? $limit : '20' );

		$content_query        = $base_select . $base_query . $tag_query . $term_query . $context_query . $search_query . $groupby_query . $sortby_query . $pagination_query;
		// phpcs:ignore
		$content_query_result = $wpdb->get_results( $content_query );

		$count_query = $base_count . $base_query . $tag_query . $term_query . $context_query . $search_query . $groupby_query . $sortby_query;
		// phpcs:ignore		
		$count_query_result = $wpdb->get_results( $count_query );

		// Retrieve faculty information for the contents.
		$data = array_map(
			function( $content ) {
				$content->faculty = self::get_content_terms_by_taxonomy( $content->id, 'faculty' );
				$content->faculty = array_map(
					function( $term_id ) {
						return get_term_by( 'id', $term_id, 'ubc_h5p_content_faculty' );
					},
					$content->faculty
				);
				$content->faculty = array_filter(
					$content->faculty,
					function( $term ) {
						return false !== $term;
					}
				);

				return $content;
			},
			array_values( $content_query_result )
		);

		return array(
			'data' => $data,
			'num'  => count( $count_query_result ),
		);
	}//end get_contents()

	/**
	 * Get tags attached to H5P content.
	 *
	 * @return array Array of tags.
	 */
	public static function get_content_tags() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$query = 'SELECT * FROM ' . $wpdb->prefix . 'h5p_tags';
		// phpcs:ignore
		$result = $wpdb->get_results( $query );

		return $result;
	}

	/**
	 * Based on faculty ids, get associated content IDs.
	 *
	 * @param array $faculty_ids an array of faculty IDs.
	 * @return array Array of content IDs.
	 */
	public static function get_content_ids_by_faculty( $faculty_ids ) {
		if ( ! is_array( $faculty_ids ) ) {
			return array();
		}

		if ( count( $faculty_ids ) === 0 ) {
			return array();
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$query = 'SELECT DISTINCT content_id FROM wp_h5p_contents_taxonomy
			WHERE term_id IN (' . implode( ',', $faculty_ids ) . ')';

		return array_map(
			function( $content ) {
				return $content->content_id;
			},
			// phpcs:ignore
			$wpdb->get_results( $query )
		);
	}//end get_content_ids_by_faculty()

	/**
	 * Insert a new content term relationship to the H5P content taxonomy table.
	 *
	 * @param int    $content_id ID of the current content.
	 * @param int    $term_id ID of the term attached.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	public static function insert_content_term( $content_id, $term_id, $taxonomy ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Make sure content_id + term_id is unique combination in database. Since dbdelta lack of multi-index support. Have to do this manually.
		$results = $wpdb->query(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}h5p_contents_taxonomy
				WHERE content_id = %d AND term_id = %d",
				$content_id,
				$term_id
			)
		);

		// If combination found, update it.
		if ( 0 !== $results ) {
			$results = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}h5p_contents_taxonomy
					SET taxonomy = %s
					WHERE content_id = %d AND term_id = %d",
					$taxonomy,
					$content_id,
					$term_id
				)
			);
		} else {
			// if not found, create it.
			$results = $wpdb->query(
				$wpdb->prepare(
					"INSERT into {$wpdb->prefix}h5p_contents_taxonomy (content_id, term_id, taxonomy)
					VALUES (%d, %d, %s)
					ON DUPLICATE KEY UPDATE taxonomy = %s",
					$content_id,
					$term_id,
					$taxonomy,
					$taxonomy
				)
			);
		}
	}//end insert_content_term()

	/**
	 * Get a list of terms attached to current H5P content.
	 *
	 * @param int $id ID of the current content.
	 * @return array array of term IDs.
	 */
	public static function get_content_terms( $id ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id FROM {$wpdb->prefix}h5p_contents_taxonomy 
				WHERE `content_id` = %d",
				$id
			)
		);

		return empty( $result ) ? array() : $result;
	}//end get_content_terms()

	/**
	 * Get a list of terms attached to current H5P content with specific taxonomy.
	 *
	 * @param int    $id ID of the current content.
	 * @param string $taxonomy Taxonomy where the terms are belong.
	 * @return array array of term IDs.
	 */
	public static function get_content_terms_by_taxonomy( $id, $taxonomy ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id FROM {$wpdb->prefix}h5p_contents_taxonomy 
				WHERE `content_id` = %d AND `taxonomy` = %s",
				$id,
				$taxonomy
			)
		);

		return empty( $result ) ? array() : array_map(
			function( $content ) {
				return intval( $content->term_id );
			},
			$result
		);
	}//end get_content_terms_by_taxonomy()

}
