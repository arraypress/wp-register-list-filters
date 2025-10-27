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
		// Render filters
		add_action( 'restrict_manage_users', [ $this, 'render_filters_and_button' ] );
		// Use the correct filter for user list table queries
		add_filter( 'users_list_table_query_args', [ $this, 'modify_list_table_query' ] );
	}

	/**
	 * Render filters and button for users.
	 *
	 * @return void
	 */
	public function render_filters_and_button(): void {
		$this->render_filters();

		// Add filter button
		$filters = self::get_filters( $this->object_type, $this->object_subtype );
		if ( ! empty( $filters ) ) {
			submit_button( __( 'Filter' ), '', 'filter_action', false );
		}
	}

	/**
	 * Modify the user list table query arguments.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array Modified query arguments.
	 */
	public function modify_list_table_query( $args ): array {
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
				// Create a mock query object that can set args
				$mock_query = new class( $args ) {
					private $args;
					public function __construct( $args ) { $this->args = $args; }
					public function set( $key, $value ) { $this->args[ $key ] = $value; }
					public function get_args() { return $this->args; }
				};

				call_user_func( $filter['query_callback'], $mock_query, $value );
				$args = $mock_query->get_args();
			}
			// Priority 2: Taxonomy query
			elseif ( ! empty( $filter['taxonomy'] ) ) {
				$args['tax_query'] = [
					[
						'taxonomy' => $filter['taxonomy'],
						'field'    => 'slug',
						'terms'    => $value
					]
				];
			}
			// Priority 3: Auto meta query (use filter key as meta_key)
			else {
				$args['meta_key']   = $key;
				$args['meta_value'] = $value;
			}
		}

		return $args;
	}

	/**
	 * Modify the query based on selected filters.
	 * Kept for backwards compatibility but not used for list table.
	 *
	 * @param \WP_User_Query $query The query object.
	 *
	 * @return void
	 */
	public function modify_query( $query ): void {
		// This method is not used for the users list table
		// The list table uses users_list_table_query_args filter instead
	}

}