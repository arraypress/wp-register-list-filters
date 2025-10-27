<?php
/**
 * List Filters Abstract Class
 *
 * @package     ArrayPress\WP\RegisterListFilters
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WP\RegisterListFilters\Abstracts;

use Exception;

abstract class ListFilters {

	/**
	 * Object type constant (e.g., 'post', 'user')
	 */
	protected const OBJECT_TYPE = '';

	/**
	 * Object type
	 *
	 * @var string
	 */
	protected string $object_type;

	/**
	 * Object subtype (e.g., post type for posts)
	 *
	 * @var string
	 */
	protected string $object_subtype;

	/**
	 * Registered filters storage
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	protected static array $filters = [];

	/**
	 * Registered instances to prevent duplicates
	 *
	 * @var array<string, array<string, bool>>
	 */
	protected static array $instances = [];

	/**
	 * ListFilters constructor.
	 *
	 * @param array  $filters        Filter configurations.
	 * @param string $object_subtype Object subtype (e.g., 'post', 'page').
	 *
	 * @throws Exception If OBJECT_TYPE is not defined.
	 */
	public function __construct( array $filters, string $object_subtype ) {
		if ( empty( static::OBJECT_TYPE ) ) {
			throw new Exception( 'Child class must define OBJECT_TYPE constant.' );
		}

		$this->object_type    = static::OBJECT_TYPE;
		$this->object_subtype = $object_subtype;

		// Prevent duplicate registration
		$instance_key = $this->object_type . '_' . $this->object_subtype;
		if ( isset( self::$instances[ $instance_key ] ) ) {
			return;
		}
		self::$instances[ $instance_key ] = true;

		$this->add_filters( $filters );

		// Load hooks immediately if already in admin, otherwise wait
		if ( did_action( 'admin_init' ) ) {
			$this->load_hooks();
		} else {
			add_action( 'admin_init', [ $this, 'load_hooks' ] );
		}
	}

	/**
	 * Add filters to the registry.
	 *
	 * @param array $filters Filter configurations.
	 *
	 * @return void
	 * @throws Exception If a filter key is invalid.
	 */
	public function add_filters( array $filters ): void {
		$default_filter = [
			'label'          => '',
			'options'        => [],
			'taxonomy'       => '',
			'query_callback' => null,
			'capability'     => 'manage_options',
			'show_count'     => false,
			'hide_empty'     => true,
		];

		foreach ( $filters as $key => $filter ) {
			if ( ! is_string( $key ) || empty( $key ) ) {
				throw new Exception( 'Invalid filter key. It must be a non-empty string.' );
			}

			$filter = wp_parse_args( $filter, $default_filter );

			// Store the filter config - taxonomy options will be fetched at render time
			self::$filters[ $this->object_type ][ $this->object_subtype ][ $key ] = $filter;
		}
	}

	/**
	 * Get options from a taxonomy.
	 *
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $hide_empty Hide empty terms.
	 * @param bool   $show_count Include term counts.
	 *
	 * @return array Options array.
	 */
	protected function get_taxonomy_options( string $taxonomy, bool $hide_empty = true, bool $show_count = false ): array {
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $hide_empty,
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		$options = [];

		foreach ( $terms as $term ) {
			if ( $show_count ) {
				$options[ $term->slug ] = [
					'label' => $term->name,
					'count' => $term->count
				];
			} else {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Get all registered filters for a specific object type and subtype.
	 *
	 * @param string $object_type    Object type.
	 * @param string $object_subtype Object subtype.
	 *
	 * @return array<string, mixed> Registered filters.
	 */
	public static function get_filters( string $object_type, string $object_subtype ): array {
		return self::$filters[ $object_type ][ $object_subtype ] ?? [];
	}

	/**
	 * Load the necessary hooks for list filters.
	 *
	 * @return void
	 */
	abstract public function load_hooks(): void;

	/**
	 * Render filter dropdowns.
	 *
	 * @return void
	 */
	public function render_filters(): void {
		$filters = self::get_filters( $this->object_type, $this->object_subtype );

		foreach ( $filters as $key => $filter ) {
			// Check capability
			if ( ! empty( $filter['capability'] ) && ! current_user_can( $filter['capability'] ) ) {
				continue;
			}

			// Lazy load taxonomy options if not already loaded
			if ( ! empty( $filter['taxonomy'] ) && empty( $filter['options'] ) ) {
				$filter['options'] = $this->get_taxonomy_options( $filter['taxonomy'], $filter['hide_empty'], $filter['show_count'] );
			}

			// Check if options are provided
			if ( empty( $filter['options'] ) || ! is_array( $filter['options'] ) ) {
				continue;
			}

			// Get selected value
			$selected = isset( $_GET[ $key ] ) ? sanitize_text_field( $_GET[ $key ] ) : '';

			// Render dropdown
			$this->render_dropdown( $key, $filter, $selected );
		}
	}

	/**
	 * Render a single dropdown filter.
	 *
	 * @param string $key      Filter key.
	 * @param array  $filter   Filter configuration.
	 * @param string $selected Selected value.
	 *
	 * @return void
	 */
	protected function render_dropdown( string $key, array $filter, string $selected ): void {
		echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
		echo '<option value="">' . esc_html( $filter['label'] ) . '</option>';

		foreach ( $filter['options'] as $value => $label ) {
			// Handle options with count
			if ( is_array( $label ) && isset( $label['label'] ) ) {
				$option_label = $label['label'];
				if ( ! empty( $filter['show_count'] ) && isset( $label['count'] ) ) {
					$option_label .= ' (' . absint( $label['count'] ) . ')';
				}
			} else {
				$option_label = $label;
			}

			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $selected, $value, false ),
				esc_html( $option_label )
			);
		}

		echo '</select>';
	}

	/**
	 * Modify the query based on selected filters.
	 *
	 * @param mixed $query The query object.
	 *
	 * @return void
	 */
	abstract public function modify_query( $query ): void;

}