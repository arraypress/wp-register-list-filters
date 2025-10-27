# WordPress Register List Filters

A powerful, elegant library for adding custom filters to WordPress admin list tables (posts, pages, custom post types, and users).

## Features

- 🎯 **Simple API** - Register filters with a single function call
- 📊 **Multiple Filter Types** - Taxonomy, meta field, and custom query filters
- 🔍 **Auto-Detection** - Automatically fetches taxonomy terms
- 🎨 **Smart UI** - Filters appear inline with WordPress's native interface
- 🔒 **Capability Support** - Restrict filters by user capabilities
- 🚀 **Performance** - Lazy-loads options and uses proper WordPress queries
- 🔗 **AND Logic** - Multiple filters drill down (Engineering + Inactive = both)
- ✨ **Clean Code** - Modern PHP 7.4+, fully typed, well-documented

## Installation

Install via Composer:

```bash
composer require arraypress/wp-register-list-filters
```

## Quick Start

### Post Filters

```php
use function ArrayPress\WP\RegisterListFilters\register_post_list_filters;

// Add filters to the Posts list table
register_post_list_filters( 'post', [
    // Taxonomy filter (auto-fetches terms)
    'category' => [
        'label'      => 'Category',
        'taxonomy'   => 'category',
        'show_count' => true,
        'hide_empty' => false
    ],
    
    // Meta field filter
    'status' => [
        'label'   => 'Status',
        'options' => [
            'active'   => 'Active',
            'inactive' => 'Inactive',
            'pending'  => 'Pending'
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
```

### User Filters

```php
use function ArrayPress\WP\RegisterListFilters\register_user_list_filters;

// Add filters to the Users list table
register_user_list_filters( [
    // Meta field filter
    'department' => [
        'label'   => 'Department',
        'options' => [
            'sales'       => 'Sales',
            'engineering' => 'Engineering',
            'marketing'   => 'Marketing'
        ]
    ],
    
    // Custom query callback
    'registered' => [
        'label'   => 'Registered',
        'options' => [
            'today'       => 'Today',
            'last_7_days' => 'Last 7 Days',
            'this_year'   => 'This Year'
        ],
        'query_callback' => function( $query, $value ) {
            $date_query = [];
            
            switch ( $value ) {
                case 'today':
                    $date_query = [
                        'after'  => date( 'Y-m-d' ) . ' 00:00:00',
                        'before' => date( 'Y-m-d' ) . ' 23:59:59'
                    ];
                    break;
                case 'last_7_days':
                    $date_query = [ 'after' => '7 days ago' ];
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
```

### Custom Post Type Filters

```php
// Register custom post type
register_post_type( 'product', [
    'labels' => [
        'name'          => 'Products',
        'singular_name' => 'Product'
    ],
    'public'      => true,
    'has_archive' => true
] );

// Add filters for the custom post type
register_post_list_filters( 'product', [
    // Taxonomy filter
    'product_brand' => [
        'label'      => 'Brand',
        'taxonomy'   => 'product_brand',
        'show_count' => true
    ],
    
    // Meta filter
    '_stock_status' => [
        'label'   => 'Stock',
        'options' => [
            'in_stock'     => 'In Stock',
            'out_of_stock' => 'Out of Stock'
        ]
    ],
    
    // Custom callback for price range
    'price_range' => [
        'label'   => 'Price Range',
        'options' => [
            'under_50'  => 'Under $50',
            '50_to_100' => '$50 - $100',
            'over_100'  => 'Over $100'
        ],
        'query_callback' => function( $query, $value ) {
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
```

## Filter Configuration

### Common Options

All filter types support these options:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `label` | `string` | *Required* | Label for the dropdown placeholder |
| `capability` | `string` | `manage_options` | Capability required to see filter |
| `options` | `array` | `[]` | Manual options (key => label) |

### Taxonomy Filters

For taxonomy-based filters, add these options:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `taxonomy` | `string` | `''` | Taxonomy slug to fetch terms from |
| `show_count` | `bool` | `false` | Show post count next to term name |
| `hide_empty` | `bool` | `true` | Hide terms with no posts |

**Example:**
```php
'category' => [
    'label'      => 'Category',
    'taxonomy'   => 'category',
    'show_count' => true,
    'hide_empty' => false  // Show all categories, even empty ones
]
```

### Meta Field Filters

For meta field filters, the filter key is used as the meta_key:

```php
// Automatically uses 'department' as meta_key
'department' => [
    'label'   => 'Department',
    'options' => [
        'sales' => 'Sales',
        'engineering' => 'Engineering'
    ]
]
```

For meta keys with underscores, include them in the key:

```php
'_featured' => [
    'label'   => 'Featured',
    'options' => [
        '1' => 'Yes',
        '0' => 'No'
    ]
]
```

### Custom Query Callbacks

For complex filtering logic, use a custom callback:

```php
'custom_filter' => [
    'label'   => 'Custom Filter',
    'options' => [
        'option1' => 'Option 1',
        'option2' => 'Option 2'
    ],
    'query_callback' => function( $query, $value ) {
        // For posts: $query is WP_Query object
        // For users: $query is QueryArgsAdapter object
        
        // Modify the query based on selected value
        if ( $value === 'option1' ) {
            $query->set( 'meta_query', [
                [
                    'key'     => '_custom_field',
                    'value'   => 'custom_value',
                    'compare' => '='
                ]
            ] );
        }
    }
]
```

## Multiple Post Types

Apply the same filters to multiple post types:

```php
register_post_list_filters( [ 'post', 'page' ], [
    '_visibility' => [
        'label'   => 'Visibility',
        'options' => [
            'public'  => 'Public',
            'private' => 'Private'
        ]
    ]
] );
```

## Capability Restrictions

Restrict filters to specific user capabilities:

```php
register_post_list_filters( 'post', [
    '_admin_notes' => [
        'label'      => 'Admin Notes',
        'capability' => 'manage_options',  // Only admins can see this
        'options'    => [
            'flagged'  => 'Flagged',
            'reviewed' => 'Reviewed'
        ]
    ]
] );
```

## AND Logic (Drill-Down Filtering)

Multiple filters use AND logic for drill-down filtering:

1. Select "Engineering" from Department → Shows 25 users
2. Add "Inactive" filter → Shows only inactive engineers (fewer results)
3. Add "This Year" registration filter → Shows inactive engineers who registered this year

This is standard WordPress behavior and matches user expectations.

## How It Works

### Posts
- Uses `restrict_manage_posts` action to render filters
- Uses `parse_query` action to modify WP_Query
- Builds proper `meta_query` and `tax_query` arrays with AND relations

### Users
- Uses `restrict_manage_users` action to render filters
- Uses `users_list_table_query_args` filter to modify user query
- Prevents duplicate rendering (action fires twice per page)

## Technical Details

### Timing

The library automatically handles WordPress hook timing:
- Checks if `init` has fired (taxonomies are registered)
- Loads hooks on `admin_init` (proper admin context)
- No manual hook management needed

### Meta Queries

For multiple meta filters, the library builds proper meta_query arrays:

```php
// Single filter
[
    'meta_key'   => 'department',
    'meta_value' => 'engineering'
]

// Multiple filters (AND logic)
[
    'meta_query' => [
        'relation' => 'AND',
        [
            'key'   => 'department',
            'value' => 'engineering'
        ],
        [
            'key'   => 'user_status',
            'value' => 'active'
        ]
    ]
]
```

### Taxonomy Queries

Similar approach for taxonomy filters:

```php
[
    'tax_query' => [
        'relation' => 'AND',
        [
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => 'technology'
        ],
        [
            'taxonomy' => 'post_tag',
            'field'    => 'slug',
            'terms'    => 'featured'
        ]
    ]
]
```

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

GPL-2.0-or-later

## Credits

Developed by [ArrayPress](https://arraypress.com/)

## Support

- [Documentation](https://github.com/arraypress/wp-register-list-filters)
- [Issue Tracker](https://github.com/arraypress/wp-register-list-filters/issues)