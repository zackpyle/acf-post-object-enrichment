<?php
defined( 'ABSPATH' ) || exit;

class ACF_POE_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_acf_poe_get_fields', [ $this, 'handle' ] );
	}

	public function handle(): void {
		check_ajax_referer( 'acf_poe_get_fields', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$raw        = isset( $_POST['post_types'] ) ? (array) wp_unslash( $_POST['post_types'] ) : [];
		$post_types = array_values( array_filter( array_map( 'sanitize_key', $raw ) ) );

		wp_send_json_success( $this->get_fields( $post_types ) );
	}

	/**
	 * Returns a sorted list of ACF fields for the given post types.
	 * When $post_types is empty, returns fields from all field groups.
	 *
	 * @param string[] $post_types
	 * @return array<array{name:string,label:string,type:string}>
	 */
	private function get_fields( array $post_types ): array {
		$groups = empty( $post_types )
			? acf_get_field_groups()
			: $this->groups_for_post_types( $post_types );

		$seen   = [];
		$result = [];

		foreach ( $groups as $group ) {
			$fields = acf_get_fields( $group['key'] );
			if ( empty( $fields ) ) {
				continue;
			}
			foreach ( $fields as $field ) {
				if ( isset( $seen[ $field['name'] ] ) ) {
					continue;
				}
				$seen[ $field['name'] ] = true;
				$result[] = [
					'name'  => $field['name'],
					'label' => $field['label'] ?: $field['name'],
					'type'  => $field['type'],
				];
			}
		}

		usort( $result, static fn( $a, $b ) => strcmp( $a['label'], $b['label'] ) );

		return $result;
	}

	/**
	 * @param string[] $post_types
	 * @return array[]
	 */
	private function groups_for_post_types( array $post_types ): array {
		$groups = [];
		$seen   = [];

		foreach ( $post_types as $pt ) {
			foreach ( acf_get_field_groups( [ 'post_type' => $pt ] ) as $group ) {
				if ( ! isset( $seen[ $group['key'] ] ) ) {
					$groups[]              = $group;
					$seen[ $group['key'] ] = true;
				}
			}
		}

		return $groups;
	}
}
