<?php
defined( 'ABSPATH' ) || exit;

class ACF_POE_Value_Filter {

	public function __construct() {
		// Priority 20 so ACF has already formatted the value into WP_Post objects.
		add_filter( 'acf/format_value', [ $this, 'maybe_enrich' ], 20, 3 );
	}

	/**
	 * @param mixed  $value
	 * @param int    $post_id
	 * @param array  $field
	 * @return mixed
	 */
	public function maybe_enrich( $value, $post_id, array $field ) {
		if ( ! in_array( $field['type'], [ 'post_object', 'relationship' ], true ) ) {
			return $value;
		}

		$setting = trim( $field['acf_poe_fields'] ?? '' );
		if ( $setting === '' ) {
			return $value;
		}

		$names = array_filter( array_map( 'trim', explode( ',', $setting ) ) );
		if ( empty( $names ) ) {
			return $value;
		}

		// post_object can return a single WP_Post or an array; relationship always returns an array.
		$single = ! is_array( $value );
		$posts  = $single ? [ $value ] : $value;

		foreach ( $posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}
			foreach ( $names as $name ) {
				$post->$name = get_field( $name, $post->ID );
			}
		}

		return $single ? $posts[0] : $posts;
	}
}
