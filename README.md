# WP Register List Filters

Lightweight library for registering custom dropdown filters in WordPress admin list tables.

## Installation

```bash
composer require arraypress/wp-register-list-filters
```

## Quick Start

### Three Ways to Filter

```php
// 1. TAXONOMY (Auto-fetches terms)
register_post_list_filters( 'product', [
    'category' => [
        'label'    => 'Category',
        'taxonomy' => 'product_cat'
    ]
] );

// 2. META (Auto-uses key as meta_key)
register_post_list_filters( 'product', [
    'status' => [
        'label'   => 'Status',
        'options' => [
            'active'   => 'Active',
            'inactive' => 'Inactive'
        ]
    ]
] );

// 3. CUSTOM (Full control)
register_post_list_filters( 'product', [
    'price_range' => [
        'label'   => 'Price',
        'options' => [
            'under_50'  => 'Under $50',
            'over_100'  => 'Over $100'
        ],
        'query_callback' => function( $query, $value ) {
            // Your custom query logic
        }
    ]
] );
```

## Posts

```php
register_post_list_filters( 'post', [
    'category' => [
        'label'    => 'Category',
        'taxonomy' => 'category'
    ],
    '_featured' => [
        'label'   => 'Featured',
        'options' => [
            '1' => 'Yes',
            '0' => 'No'
        ]
    ]
] );
```

## Users

```php
register_user_list_filters( [
    'department' => [
        'label'    => 'Department',
        'taxonomy' => 'user_department'  // Works with user taxonomies
    ],
    'status' => [
        'label'   => 'Status',
        'options' => [
            'active'   => 'Active',
            'inactive' => 'Inactive'
        ]
    ]
] );
```

## Multiple Post Types

```php
register_post_list_filters( [ 'post', 'page', 'product' ], [
    '_priority' => [
        'label'   => 'Priority',
        'options' => [
            'high'   => 'High',
            'medium' => 'Medium',
            'low'    => 'Low'
        ]
    ]
] );
```

## Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `label` | string | Required | Dropdown label |
| `taxonomy` | string | '' | Auto-fetch taxonomy terms |
| `options` | array | [] | Manual options (value => label) |
| `query_callback` | callable | null | Custom query modification |
| `capability` | string | 'manage_options' | Required capability |
| `show_count` | bool | false | Show term counts |
| `hide_empty` | bool | true | Hide empty taxonomy terms |

## Smart Query Logic

**Priority order:**
1. If `query_callback` exists → Use it
2. If `taxonomy` exists → Auto tax_query
3. Else → Auto meta_query using key as meta_key

## Advanced Examples

### Date Range Filter

```php
register_post_list_filters( 'event', [
    'timeframe' => [
        'label'   => 'When',
        'options' => [
            'upcoming' => 'Upcoming',
            'past'     => 'Past'
        ],
        'query_callback' => function( $query, $value ) {
            $today = date( 'Y-m-d' );
            
            $meta_query = [
                'key'     => '_event_date',
                'value'   => $today,
                'compare' => $value === 'upcoming' ? '>=' : '<',
                'type'    => 'DATE'
            ];
            
            $query->set( 'meta_query', [ $meta_query ] );
        }
    ]
] );
```

### Taxonomy with Counts

```php
register_post_list_filters( 'product', [
    'brand' => [
        'label'      => 'Brand',
        'taxonomy'   => 'product_brand',
        'show_count' => true,
        'hide_empty' => true
    ]
] );
```

### Complex Meta Query

```php
register_post_list_filters( 'product', [
    'stock' => [
        'label'   => 'Stock',
        'options' => [
            'in_stock'  => 'In Stock',
            'low_stock' => 'Low Stock'
        ],
        'query_callback' => function( $query, $value ) {
            if ( $value === 'in_stock' ) {
                $meta_query = [
                    'key'     => '_stock',
                    'value'   => 10,
                    'compare' => '>',
                    'type'    => 'NUMERIC'
                ];
            } else {
                $meta_query = [
                    'key'     => '_stock',
                    'value'   => [ 1, 10 ],
                    'compare' => 'BETWEEN',
                    'type'    => 'NUMERIC'
                ];
            }
            
            $query->set( 'meta_query', [ $meta_query ] );
        }
    ]
] );
```

### User Registration Filter

```php
register_user_list_filters( [
    'registered' => [
        'label'   => 'Registered',
        'options' => [
            'last_7_days'  => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days'
        ],
        'query_callback' => function( $query, $value ) {
            $days = $value === 'last_7_days' ? 7 : 30;
            
            $query->set( 'date_query', [
                [ 'after' => $days . ' days ago' ]
            ] );
        }
    ]
] );
```

## What's Supported

- ✅ Posts (all post types)
- ✅ Users
- ❌ Taxonomies (no WordPress hook)
- ❌ Comments (no WordPress hook)

## License

GPL-2.0-or-later