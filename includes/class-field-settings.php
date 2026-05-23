<?php
defined( 'ABSPATH' ) || exit;

class ACF_POE_Field_Settings {

	public function __construct() {
		add_action( 'acf/render_field_settings/type=post_object',  [ $this, 'render_setting' ] );
		add_action( 'acf/render_field_settings/type=relationship', [ $this, 'render_setting' ] );
		add_action( 'acf/field_group/admin_enqueue_scripts',       [ $this, 'enqueue_assets' ] );
	}

	public function render_setting( array $field ): void {
		acf_render_field_setting( $field, [
			'label'        => __( 'Attach additional fields', 'acf-post-object-enrichment' ),
			'instructions' => __( 'Attach additional ACF field values as properties on each returned WP_Post object.', 'acf-post-object-enrichment' ),
			'name'         => 'acf_poe_fields',
			'type'         => 'text',
			'wrapper'      => [ 'class' => 'acf-poe-setting' ],
		] );
	}

	public function enqueue_assets(): void {
		wp_enqueue_style(
			'acf-poe-field-settings',
			ACF_POE_PLUGIN_URL . 'assets/css/field-settings.css',
			[],
			ACF_POE_VERSION
		);
		wp_enqueue_script(
			'acf-poe-field-settings',
			ACF_POE_PLUGIN_URL . 'assets/js/field-settings.js',
			[ 'jquery' ],
			ACF_POE_VERSION,
			true
		);
		wp_localize_script( 'acf-poe-field-settings', 'acfPoeData', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'acf_poe_get_fields' ),
			'i18n'    => [
				'addField' => __( '+ Add field', 'acf-post-object-enrichment' ),
				'search'   => __( 'Search fields…', 'acf-post-object-enrichment' ),
				'loading'  => __( 'Loading…', 'acf-post-object-enrichment' ),
				'allAdded' => __( 'All available fields have been added.', 'acf-post-object-enrichment' ),
				'noFields' => __( 'No fields found for the selected post type(s).', 'acf-post-object-enrichment' ),
				'error'    => __( 'Failed to load fields.', 'acf-post-object-enrichment' ),
			],
		] );
	}
}
