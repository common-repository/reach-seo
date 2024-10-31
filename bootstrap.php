<?php
/*
Plugin Name: Reach SEO
Plugin URI:  https://reachseo.io
Description: Reach SEO provides a set of outreach and research tools that will help you get your content into the right hands.
Version:     1.0.5
Author:      Y-Designs
Author URI:  http://www.y-designs.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

define( 'REACHSEO_PLUGIN_NAME', 'Reach SEO' );

define( 'REACHSEO_REQUIRED_PHP_VERSION', '5.3' );                          // because of get_called_class()
define( 'REACHSEO_REQUIRED_WP_VERSION',  '3.5' );                          // because of get_charset_collate()

require_once( __DIR__ . '/classes/vendor/tgmpa/tgm-plugin-activation/class-tgm-plugin-activation.php');

require_once( __DIR__ . '/classes/vendor/autoload.php' );
require_once( __DIR__ . '/classes/module.php' );
require_once( __DIR__ . '/classes/model.php' );
require_once( __DIR__ . '/classes/rest-api-interface.php' );
require_once( __DIR__ . '/classes/rest-api.php' );
require_once( __DIR__ . '/classes/plugin.php' );

// Models
require_once( __DIR__ . '/classes/models/campaign.php' );
require_once( __DIR__ . '/classes/models/opportunity.php' );
require_once( __DIR__ . '/classes/models/report.php' );
require_once( __DIR__ . '/classes/models/email-template.php' );

// Settings Module
require_once( __DIR__ . '/classes/modules/settings/settings.php' );
// Broken Links Module
require_once( __DIR__ . '/classes/modules/broken-links/broken-links.php' );
require_once( __DIR__ . '/classes/modules/broken-links/broken-links-rest-api.php' );
// Contextual Content Module
require_once( __DIR__ . '/classes/modules/contextual-content/contextual-content.php' );
require_once( __DIR__ . '/classes/modules/contextual-content/contextual-content-rest-api.php' );
// Relevant Contacts Finder
require_once( __DIR__ . '/classes/modules/relevant-contacts/relevant-contacts.php' );
require_once( __DIR__ . '/classes/modules/relevant-contacts/relevant-contacts-rest-api.php' );
// Email Finder
require_once( __DIR__ . '/classes/modules/email-finder/email-finder.php' );
require_once( __DIR__ . '/classes/modules/email-finder/email-finder-rest-api.php' );


function reach_seo_register_required_plugins() {
	$plugins = array(
			array(
				'name' => 'WP REST API (WP API)',
				'slug' => 'json-rest-api',
				'required' => true,
				'force_activation' => true
				)
		);
	$config = array(
			'dismissable' => FALSE,
			'strings' => array(
					'notice_can_install_required'     => _n_noop(
						'Reach SEO requires the following plugin: %1$s.',
						'Reach SEO requires the following plugins: %1$s.',
						'theme-slug'
					), // %1$s = plugin name(s).
					'notice_can_install_recommended'  => _n_noop(
						'Reach SEO recommends the following plugin: %1$s.',
						'Reach SEO recommends the following plugins: %1$s.',
						'theme-slug'
					)
				)
		);//completely optional
	tgmpa($plugins, $config);
}

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function reachseo_requirements_met() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, REACHSEO_REQUIRED_PHP_VERSION, '<' ) ) {
		return false;
	}

	if ( version_compare( $wp_version, REACHSEO_REQUIRED_WP_VERSION, '<' ) ) {
		return false;
	}

	// Dependency https://github.com/WP-API/WP-API/tree/1.2.2
	if ( ! class_exists( 'WP_JSON_Server' ) ) {
		add_action( 'tgmpa_register', 'reach_seo_register_required_plugins' );
		//return false;
	}

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 */
function reachseo_requirements_error() {
	global $wp_version;

	require_once( dirname( __FILE__ ) . '/views/requirements-error.php' );
}

/**
 * In case the plugin requirements are not met...
 */
function reachseo_deactivate_self() {
	deactivate_plugins( plugin_basename( __FILE__ ), false );
}

/*
 * Check requirements and load main class
 */
function reachseo_init() {
	if ( reachseo_requirements_met() ) {
		// 0. Setup/Activation
		$reachseo_plugin = ReachSEO_Plugin::get_instance();
		register_activation_hook(   __FILE__, array( $reachseo_plugin, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $reachseo_plugin, 'deactivate' ) );
		// register_uninstall_hook( __FILE__, array( $reachseo_plugin, 'uninstall' ) );

		// 1. Check that we have an api key, if not, show nag
		$settings = get_option( 'reachseo_settings' );
		$api_key = $settings[ 'basic' ][ 'blb-api-key' ];
		if ( !$api_key && $_GET['page'] != 'reachseo_settings' && $_GET['page'] != 'outreach-settings' ) {
			function my_admin_notice() {
				$settings_url = get_admin_url() . 'options-general.php?page=reachseo_settings';
				?>
			    <div class="notice update-nag">
			        To begin using <?php echo REACHSEO_PLUGIN_NAME; ?>, add your API key <a href="<?php esc_attr_e( $settings_url ); ?>">here</a>.
			    </div>
			    <?php
			}
			add_action( 'admin_notices', 'my_admin_notice' );
		}

	} else {
		add_action( 'admin_notices', 'reachseo_requirements_error' );
		add_action( 'admin_init', 'reachseo_deactivate_self' );
	}
}

add_action( 'plugins_loaded', 'reachseo_init' );
