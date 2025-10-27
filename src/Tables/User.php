<?php
/**
 * User List Filters Class
 *
 * @package     ArrayPress\WP\RegisterListFilters
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WP\RegisterListFilters\Tables;

use ArrayPress\WP\RegisterListFilters\Abstracts\ListFilters;

class User extends ListFilters {

	/**
	 * Object type constant
	 */
	protected const OBJECT_TYPE = 'user';

	/**
	 * Load the necessary hooks for user list filters.
	 *
	 * @return void
	 */
	public function load_hooks(): void {
		// Use high priority to come after role dropdown, then manually add button
		add_action( 'restrict_manage_users', [ $this, 'start_output_buffer' ], 1 );
		add_action( 'restrict_manage_users', [ $this, 'render_filters_after_change_button' ], 100 );
		add_filter( 'pre_get_users', [ $this, 'modify_query' ] );
	}

	/**
	 * Start output buffering to capture role dropdown.
	 *
	 * @return void
	 */
	public function start_output_buffer(): void {
		ob_start();
	}

	/**
	 * Render filters after the change button by manipulating the output.
	 *
	 * @return void
	 */
	public function render_filters_after_change_button(): void {
		$content = ob_get_clean();

		// Output the original content (role dropdown + change button)
		echo $content;

		// Now add our filters
		$this->render_filters();

		// Add filter button
		$filters = self::get_filters( $this->object_type, $this->object_subtype );
		if ( ! empty( $filters ) ) {
			submit_button( __( 'Filter' ), '', 'filter_action', false );
		}
	}

	/**
	 * Modify the query based on selected filters.
	 *
	 * @param \WP_User_Query $query The query object.
	 *
	 * @return void
	 */
	public function modify_query( $query ): void {
		global $pagenow;

		// Only modify admin queries
		if ( ! is_admin() || $pagenow !== 'users.php' ) {
			return;
		}

		$filters = self::get_filters( $this->object_type, $this->object_subtype );

		foreach ( $filters as $key => $filter ) {
			// Skip if filter not set or empty
			if ( empty( $_GET[ $key ] ) ) {
				continue;
			}

			// Check capability
			if ( ! empty( $filter['capability'] ) && ! current_user_can( $filter['capability'] ) ) {
				continue;
			}

			$value = sanitize_text_field( $_GET[ $key ] );

			// Priority 1: Custom query callback
			if ( ! empty( $filter['query_callback'] ) && is_callable( $filter['query_callback'] ) ) {
				call_user_func( $filter['query_callback'], $query, $value );
			} // Priority 2: Taxonomy query
			elseif ( ! empty( $filter['taxonomy'] ) ) {
				$query->set( 'tax_query', [
					[
						'taxonomy' => $filter['taxonomy'],
						'field'    => 'slug',
						'terms'    => $value
					]
				] );
			} // Priority 3: Auto meta query (use filter key as meta_key)
			else {
				$query->set( 'meta_key', $key );
				$query->set( 'meta_value', $value );
			}
		}
	}

}