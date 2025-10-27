<?php
/**
 * Post List Filters Class
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

class Post extends ListFilters {

	/**
	 * Object type constant
	 */
	protected const OBJECT_TYPE = 'post';

	/**
	 * Load the necessary hooks for post list filters.
	 *
	 * @return void
	 */
	public function load_hooks(): void {
		add_action( 'restrict_manage_posts', [ $this, 'render_filters_for_post_type' ] );
		add_action( 'restrict_manage_posts', [ $this, 'render_filter_button' ], 11 );
		add_filter( 'parse_query', [ $this, 'modify_query' ] );
	}

	/**
	 * Render filters only for the current post type.
	 *
	 * @param string $post_type Current post type.
	 *
	 * @return void
	 */
	public function render_filters_for_post_type( string $post_type ): void {
		if ( $post_type !== $this->object_subtype ) {
			return;
		}

		$this->render_filters();
	}

	/**
	 * Render the filter button.
	 *
	 * @param string $post_type Current post type.
	 *
	 * @return void
	 */
	public function render_filter_button( string $post_type ): void {
		if ( $post_type !== $this->object_subtype ) {
			return;
		}

		$filters = self::get_filters( $this->object_type, $this->object_subtype );

		if ( empty( $filters ) ) {
			return;
		}

		submit_button( __( 'Filter' ), '', 'filter_action', false );
	}

	/**
	 * Modify the query based on selected filters.
	 *
	 * @param \WP_Query $query The query object.
	 *
	 * @return void
	 */
	public function modify_query( $query ): void {
		global $pagenow;

		// Only modify admin queries for the correct post type
		if ( ! is_admin() || $pagenow !== 'edit.php' ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		// Check if this is the correct post type
		$current_post_type = isset( $query->query['post_type'] ) ? $query->query['post_type'] : 'post';
		if ( $current_post_type !== $this->object_subtype ) {
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
			}
			// Priority 2: Taxonomy query
			elseif ( ! empty( $filter['taxonomy'] ) ) {
				$query->set( 'tax_query', [
					[
						'taxonomy' => $filter['taxonomy'],
						'field'    => 'slug',
						'terms'    => $value
					]
				] );
			}
			// Priority 3: Auto meta query (use filter key as meta_key)
			else {
				$query->set( 'meta_key', $key );
				$query->set( 'meta_value', $value );
			}
		}
	}

}