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
	private static $db_version = '0.1.3';

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

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}h5p_contents_taxonomy (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			content_id INT UNSIGNED NOT NULL,
			term_id INT UNSIGNED NOT NULL,
			taxonomy VARCHAR(255) NOT NULL,
			PRIMARY KEY  (id),
			CONSTRAINT unique_content_term UNIQUE (content_id, term_id)
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

		$results = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}h5p_contents_taxonomy WHERE content_id = %d", $content_id ) );
	}//end clear_content_terms()

	/**
	 * List all the H5P contents based on query.
	 *
	 * @param array  $sortby the field to sort the content by.
	 * @param int    $offset Skip this many rows.
	 * @param int    $limit Max number of rows to return.
	 * @param string $order_by Field to order content by.
	 * @param bool   $reverse_order Reverses the ordering.
	 * @param array  $filters Must be defined like so: array(array('field', 'Cool Content', 'LIKE')).
	 * @return array query results.
	 */
	public static function get_contents( $context = 'self', $sortby = 'updated_at', $limit = null, $offset = null, $order_by = null, $reverse_order = null, $filters = null ) {
		if ( ! class_exists( 'H5PContentQuery' ) ) {
			return array();
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$user_faculty = get_user_meta( get_current_user_id(), 'user_faculty', true );
		$user_faculty_content_ids = self::get_content_ids_by_faculty( $user_faculty );

		$base_select = "SELECT hc.title AS title, hl.title AS content_type, u.display_name AS user_name, GROUP_CONCAT(DISTINCT CONCAT(t.id,',',t.name) ORDER BY t.id SEPARATOR ';') AS tags, hc.updated_at AS updated_at, hc.id AS id, u.ID AS user_id, hl.name AS content_type_id";
		$base_count  = 'SELECT COUNT(*)';

		$base_query = ' FROM wp_h5p_contents hc
			LEFT JOIN wp_h5p_libraries hl ON hl.id = hc.library_id 
			LEFT JOIN wp_users u ON hc.user_id = u.ID 
			LEFT JOIN wp_h5p_contents_tags ct ON ct.content_id = hc.id
			LEFT JOIN wp_h5p_tags t ON ct.tag_id = t.id
			LEFT JOIN wp_h5p_contents_tags ct2 ON ct2.content_id = hc.id';

		$groupby_query    = ' GROUP BY hc.id';
		$context_query    = 'self' === $context ? " WHERE u.ID = '" . get_current_user_id() . "'" : ' WHERE hc.id IN (' . implode( ',', $user_faculty_content_ids ) . ') AND u.ID != ' . get_current_user_id();
		$sortby_query     = ' ORDER BY hc.' . $sortby . ' DESC';
		$pagination_query = ' LIMIT ' . ( $offset ? $offset : '0' ) . ' ,' . ( $limit ? $limit : '20' );

		$content_query        = $base_select . $base_query . $context_query . $groupby_query . $sortby_query . $pagination_query;
		$content_query_result = $wpdb->get_results( $content_query );

		$count_query        = $base_count . $base_query . $context_query . $groupby_query . $sortby_query;
		$count_query_result = $wpdb->get_results( $count_query );

		return array(
			'data' => array_values( $content_query_result ),
			'num'  => count( $count_query_result ),
		);
	}//end get_contents()

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
