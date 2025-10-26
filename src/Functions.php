<?php
/**
 * List Filters Helper Functions
 *
 * Global helper functions for registering list filters.
 *
 * @package     ArrayPress\WP\RegisterListFilters
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

use ArrayPress\WP\RegisterListFilters\Tables\Post;
use ArrayPress\WP\RegisterListFilters\Tables\User;

if ( ! function_exists( 'register_post_list_filters' ) ):
	/**
	 * Register list filters for posts or custom post types
	 *
	 * @param string|array $post_types Post type(s) to register filters for
	 * @param array        $filters    Filter configurations
	 *
	 * @return void
	 */
	function register_post_list_filters( $post_types, array $filters ): void {
		$post_types = (array) $post_types;

		foreach ( $post_types as $post_type ) {
			new Post( $filters, $post_type );
		}
	}
endif;

if ( ! function_exists( 'register_user_list_filters' ) ):
	/**
	 * Register list filters for users
	 *
	 * @param array $filters Filter configurations
	 *
	 * @return void
	 */
	function register_user_list_filters( array $filters ): void {
		new User( $filters, 'user' );
	}
endif;