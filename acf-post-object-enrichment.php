<?php
/**
 * Plugin Name: 	  ACF Post Object Enrichment
 * Description: 	  Adds a UI to ACF Post Object and Relationship fields to automatically attach additional ACF field values to each returned WP_Post object.
 * Version:     	  1.0.1
 * Author:      	  SnippetNest
 * Author URI:  	  https://snippetnest.com
 * Plugin URI:  	  https://snippetnest.com/snippet/extend-acf-post-object-relationship-fields-custom-data/
 * Requires at least: 5.9
 * Requires PHP:      8.1
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       acf-post-object-enrichment
 */

defined( 'ABSPATH' ) || exit;

define( 'ACF_POE_VERSION',    '1.0.1' );
define( 'ACF_POE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACF_POE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'ACF' ) ) {
		return;
	}

	load_plugin_textdomain( 'acf-post-object-enrichment', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	require_once ACF_POE_PLUGIN_DIR . 'includes/class-field-settings.php';
	require_once ACF_POE_PLUGIN_DIR . 'includes/class-value-filter.php';
	require_once ACF_POE_PLUGIN_DIR . 'includes/class-ajax.php';

	new ACF_POE_Field_Settings();
	new ACF_POE_Value_Filter();
	new ACF_POE_Ajax();
} );

// GitHub Updater integration
require_once __DIR__ . '/includes/GithubUpdater.php';
if ( class_exists( 'ACF_POE_GithubUpdater' ) ) {
	$acf_poe_updater = new ACF_POE_GithubUpdater( __FILE__ );
	$acf_poe_updater->set_username( 'zackpyle' );
	$acf_poe_updater->set_repository( 'acf-post-object-enrichment' );
	$acf_poe_updater->set_settings( array(
		'requires'      => '5.9',
		'tested'        => '6.9.4',
		'requires_php'  => '8.1',
		'rating'        => '100.0',
		'num_ratings'   => '10',
		'downloaded'    => '10',
		'added'         => '2026-05-23',
		'icons'         => false,
		'banners'       => false,
	) );
	$acf_poe_updater->initialize();
}

// SN Analytics
if ( ! defined( 'SN_ANALYTICS_ENDPOINT' ) ) {
	define( 'SN_ANALYTICS_ENDPOINT', 'https://snippetnest.com/wp-json/sn-analytics/v1/ping' );
}
if ( ! defined( 'SN_ANALYTICS_SECRET' ) ) {
	define( 'SN_ANALYTICS_SECRET', 'b64:O84AMMGANsFvXQFWgVW5OrbzghcxWZ7ygb3vUhWGZiA=' );
}
require_once __DIR__ . '/includes/class-sn-analytics.php';
if ( class_exists( 'SN_Analytics' ) ) {
	$acf_poe_analytics = new SN_Analytics( __FILE__, 'acf-post-object-enrichment' );
	register_activation_hook( __FILE__, function() use ( $acf_poe_analytics ) {
		$acf_poe_analytics->activate();
	} );
	register_deactivation_hook( __FILE__, function() use ( $acf_poe_analytics ) {
		$acf_poe_analytics->deactivate();
	} );
	$acf_poe_analytics->initialize();
}
function sn_acf_poe_analytics_uninstall() {
	require_once __DIR__ . '/includes/class-sn-analytics.php';
	if ( class_exists( 'SN_Analytics' ) ) {
		SN_Analytics::uninstall( 'acf-post-object-enrichment' );
	}
}
register_uninstall_hook( __FILE__, 'sn_acf_poe_analytics_uninstall' );
