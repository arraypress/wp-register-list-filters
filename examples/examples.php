<?php
/**
 * Row Actions Examples
 *
 * Practical examples of using the WP Register Row Actions library.
 * Note: No need to wrap in admin_init - the library handles hook timing automatically!
 *
 * @package ArrayPress\WP\RegisterListFilters
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add test data on plugin activation
 */
register_activation_hook( __FILE__, function () {
	// Add post meta to existing posts
	$posts = get_posts( [ 'numberposts' => 20, 'post_type' => 'post' ] );

	$statuses   = [ 'active', 'inactive', 'pending' ];
	$priorities = [ 'high', 'medium', 'low' ];

	foreach ( $posts as $index => $post ) {
		update_post_meta( $post->ID, 'status', $statuses[ $index % 3 ] );
		update_post_meta( $post->ID, '_priority', $priorities[ $index % 3 ] );
		update_post_meta( $post->ID, '_featured', $index % 2 );
	}

	// Add user meta to existing users
	$users = get_users( [ 'number' => 10 ] );

	$departments = [ 'sales', 'engineering', 'marketing', 'support' ];
	$statuses    = [ 'active', 'inactive' ];

	foreach ( $users as $index => $user ) {
		update_user_meta( $user->ID, 'department', $departments[ $index % 4 ] );
		update_user_meta( $user->ID, 'user_status', $statuses[ $index % 2 ] );
	}
} );

/**
 * Test 1: Post filters with taxonomy
 */
register_post_list_filters( 'post', [
	// Taxonomy filter (auto-fetches terms)
	'category'  => [
		'label'      => 'Category',
		'taxonomy'   => 'category',
		'show_count' => true,
		'hide_empty' => false  // ← Change this!
	],

	// Meta filter (auto-uses 'status' as meta_key)
	'status'    => [
		'label'   => 'Status',
		'options' => [
			'active'   => 'Active',
			'inactive' => 'Inactive',
			'pending'  => 'Pending'
		]
	],

	// Meta filter with underscore prefix
	'_priority' => [
		'label'   => 'Priority',
		'options' => [
			'high'   => 'High Priority',
			'medium' => 'Medium Priority',
			'low'    => 'Low Priority'
		]
	],

	// Boolean meta filter
	'_featured' => [
		'label'   => 'Featured Status',
		'options' => [
			'1' => 'Featured',
			'0' => 'Not Featured'
		]
	]
] );

/**
 * Test 2: User filters with taxonomy and meta
 */
register_user_list_filters( [
	// Meta filter (auto-uses 'department' as meta_key)
	'department'  => [
		'label'   => 'Department',
		'options' => [
			'sales'       => 'Sales',
			'engineering' => 'Engineering',
			'marketing'   => 'Marketing',
			'support'     => 'Support'
		]
	],

	// Meta filter
	'user_status' => [
		'label'   => 'Account Status',
		'options' => [
			'active'   => 'Active',
			'inactive' => 'Inactive'
		]
	],

	// Custom callback for registration date
	'registered'  => [
		'label'          => 'Registered',
		'options'        => [
			'today'        => 'Today',
			'last_7_days'  => 'Last 7 Days',
			'last_30_days' => 'Last 30 Days',
			'this_year'    => 'This Year'
		],
		'query_callback' => function ( $query, $value ) {
			$date_query = [];

			switch ( $value ) {
				case 'today':
					$date_query = [
						'after'     => date( 'Y-m-d' ) . ' 00:00:00',
						'before'    => date( 'Y-m-d' ) . ' 23:59:59',
						'inclusive' => true
					];
					break;
				case 'last_7_days':
					$date_query = [ 'after' => '7 days ago' ];
					break;
				case 'last_30_days':
					$date_query = [ 'after' => '30 days ago' ];
					break;
				case 'this_year':
					$date_query = [ 'after' => date( 'Y' ) . '-01-01' ];
					break;
			}

			if ( ! empty( $date_query ) ) {
				$query->set( 'date_query', [ $date_query ] );
			}
		}
	]
] );

/**
 * Test 3: Register a custom post type with filters
 */
add_action( 'init', function () {
	// Register custom post type
	register_post_type( 'product', [
		'labels'       => [
			'name'          => 'Products',
			'singular_name' => 'Product'
		],
		'public'       => true,
		'has_archive'  => true,
		'show_in_menu' => true,
		'supports'     => [ 'title', 'editor' ]
	] );

	// Register custom taxonomy
	register_taxonomy( 'product_brand', 'product', [
		'labels'            => [
			'name'          => 'Brands',
			'singular_name' => 'Brand'
		],
		'hierarchical'      => true,
		'show_admin_column' => true
	] );
} );

// Add filters for the custom post type (no init wrapper needed)
register_post_list_filters( 'product', [
	// Taxonomy filter
	'product_brand' => [
		'label'      => 'Brand',
		'taxonomy'   => 'product_brand',
		'show_count' => true,
		'hide_empty' => false
	],

	// Meta filter
	'_stock_status' => [
		'label'   => 'Stock',
		'options' => [
			'in_stock'     => 'In Stock',
			'out_of_stock' => 'Out of Stock',
			'low_stock'    => 'Low Stock'
		]
	],

	// Custom callback for price range
	'price_range'   => [
		'label'          => 'Price Range',
		'options'        => [
			'under_50'  => 'Under $50',
			'50_to_100' => '$50 - $100',
			'over_100'  => 'Over $100'
		],
		'query_callback' => function ( $query, $value ) {
			$meta_query = [];

			switch ( $value ) {
				case 'under_50':
					$meta_query = [
						'key'     => '_price',
						'value'   => 50,
						'compare' => '<',
						'type'    => 'NUMERIC'
					];
					break;
				case '50_to_100':
					$meta_query = [
						'key'     => '_price',
						'value'   => [ 50, 100 ],
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC'
					];
					break;
				case 'over_100':
					$meta_query = [
						'key'     => '_price',
						'value'   => 100,
						'compare' => '>',
						'type'    => 'NUMERIC'
					];
					break;
			}

			if ( ! empty( $meta_query ) ) {
				$query->set( 'meta_query', [ $meta_query ] );
			}
		}
	]
] );

/**
 * Test 4: Add test products on init (only once)
 */
add_action( 'init', function () {
	if ( get_option( 'wplf_test_products_added' ) ) {
		return;
	}

	// Check if product post type exists
	if ( ! post_type_exists( 'product' ) ) {
		return;
	}

	// Create some test brands
	$brands    = [ 'Nike', 'Adidas', 'Puma', 'Reebok' ];
	$brand_ids = [];

	foreach ( $brands as $brand ) {
		$term = wp_insert_term( $brand, 'product_brand' );
		if ( ! is_wp_error( $term ) ) {
			$brand_ids[] = $term['term_id'];
		}
	}

	// Create test products
	$products = [
		[ 'name' => 'Running Shoes', 'price' => 89.99, 'stock' => 'in_stock' ],
		[ 'name' => 'Basketball Sneakers', 'price' => 129.99, 'stock' => 'in_stock' ],
		[ 'name' => 'Training Shoes', 'price' => 45.99, 'stock' => 'low_stock' ],
		[ 'name' => 'Classic Sneakers', 'price' => 79.99, 'stock' => 'in_stock' ],
		[ 'name' => 'Limited Edition', 'price' => 199.99, 'stock' => 'out_of_stock' ],
	];

	foreach ( $products as $index => $product ) {
		$post_id = wp_insert_post( [
			'post_title'  => $product['name'],
			'post_type'   => 'product',
			'post_status' => 'publish'
		] );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			// Add meta
			update_post_meta( $post_id, '_price', $product['price'] );
			update_post_meta( $post_id, '_stock_status', $product['stock'] );

			// Add brand taxonomy
			if ( ! empty( $brand_ids ) ) {
				wp_set_object_terms( $post_id, $brand_ids[ $index % count( $brand_ids ) ], 'product_brand' );
			}
		}
	}

	update_option( 'wplf_test_products_added', true );
}, 999 );

/**
 * Test 5: Multiple post types with same filters
 */
register_post_list_filters( [ 'post', 'page' ], [
	'_visibility' => [
		'label'   => 'Visibility',
		'options' => [
			'public'  => 'Public',
			'private' => 'Private'
		]
	]
] );

/**
 * Test 6: Capability-restricted filter
 */
register_post_list_filters( 'post', [
	'_admin_notes' => [
		'label'      => 'Admin Notes',
		'capability' => 'manage_options',
		'options'    => [
			'flagged'  => 'Flagged',
			'reviewed' => 'Reviewed',
			'approved' => 'Approved'
		]
	]
] );

/**
 * Admin notice showing library is active
 */
add_action( 'admin_notices', function () {
	if ( ! get_option( 'wplf_test_notice_dismissed' ) ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong>WP List Filters Test Plugin Active!</strong>
				Check your Posts, Users, and Products admin pages to see the filters in action.
			</p>
		</div>
		<?php
	}
} );