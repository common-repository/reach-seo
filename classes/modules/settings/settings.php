<?php

if ( ! class_exists( 'ReachSEO_Settings' ) ) {

	/**
	 * Handles plugin settings and user profile meta fields
	 */
	class ReachSEO_Settings extends ReachSEO_Module {
		protected $settings;
		protected static $default_settings;

		const REQUIRED_CAPABILITY = 'administrator';


		/*
		 * General methods
		 */

		/**
		 * Constructor
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
		}

		/**
		 * Public setter for protected variables
		 *
		 * Updates settings outside of the Settings API or other subsystems
		 *
		 * @param string $variable
		 * @param array  $value This will be merged with ReachSEO_Settings->settings, so it should mimic the structure of the ReachSEO_Settings::$default_settings. It only needs the contain the values that will change, though. See ReachSEO_Plugin->upgrade() for an example.
		 */
		public function __set( $variable, $value ) {
			// Note: ReachSEO_Module::__set() is automatically called before this			
			if ( $variable != 'settings' ) {
				return;
			}

			$this->settings = self::validate_settings( $value );
			update_option( 'reachseo_settings', $this->settings );
		}

		/**
		 * Register callbacks for actions and filters
		 */
		public function register_hook_callbacks() {
			add_action( 'admin_menu',               __CLASS__ . '::register_settings_pages' );

			add_action( 'init',                     array( $this, 'init' ) );
			add_action( 'admin_init',               array( $this, 'register_settings' ) );

			add_filter(
				'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) ) . '/bootstrap.php',
				__CLASS__ . '::add_plugin_action_links'
			);
		}

		/**
		 * Activation code for settings
		 */
		public function activate() {
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
		}

		/**
		 * Cleanup after plugin uninstallation. Delete any settings that were set.
		 */
		public function uninstall() {
			delete_option( 'reachseo_settings' );
			delete_option( ReachSEO_Plugin::PREFIX.'display_tour' );
		}

		/**
		 * Initializes variables
		 */
		public function init() {
			self::$default_settings = self::get_default_settings();
			$this->settings         = self::get_settings();
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 *
		 * @mvc Model
		 *
		 * @param string $db_version
		 */
		public function upgrade( $db_version = 0 ) {
		}

		/**
		 * Checks that the object is in a correct state
		 *
		 * @mvc Model
		 *
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function is_valid( $property = 'all' ) {
			// Note: __set() calls validate_settings(), so settings are never invalid

			return true;
		}


		/*
		 * Plugin Settings
		 */

		/**
		 * Establishes initial values for all settings
		 *
		 * @mvc Model
		 *
		 * @return array
		 */
		protected static function get_default_settings() {
			$basic = array(
				'blb-api-key' => ''
			);

			$advanced = array(
				
			);

			return array(
				'db-version' => ReachSEO_Plugin::VERSION,
				'basic'      => $basic
			);
		}

		/**
		 * Retrieves all of the settings from the database
		 *
		 * @mvc Model
		 *
		 * @return array
		 */
		protected static function get_settings() {
			$settings = shortcode_atts(
				self::$default_settings,
				get_option( 'reachseo_settings', array() )
			);

			return $settings;
		}

		/**
		 * Adds links to the plugin's action link section on the Plugins page
		 *
		 * @mvc Model
		 *
		 * @param array $links The links currently mapped to the plugin
		 * @return array
		 */
		public static function add_plugin_action_links( $links ) {
			// array_unshift( $links, '<a href="http://wordpress.org/extend/plugins/wordpress-plugin-skeleton/faq/">Help</a>' );			
			array_unshift( $links, '<a href="options-general.php?page=' . 'reachseo_settings">Settings</a>' );

			return $links;
		}

		/**
		 * Adds pages to the Admin Panel menu
		 */
		public static function register_settings_pages() {
			add_submenu_page(
				'options-general.php',
				REACHSEO_PLUGIN_NAME . ' Settings',
				REACHSEO_PLUGIN_NAME,
				self::REQUIRED_CAPABILITY,
				'reachseo_settings',
				__CLASS__ . '::markup_settings_page'
			);
		}

		/**
		 * Creates the markup for the Settings page
		 */
		public static function markup_settings_page() {
			if ( current_user_can( self::REQUIRED_CAPABILITY ) ) {
				echo self::render_template( 'modules/settings/page-settings.php' );
			} else {
				wp_die( 'Access denied.' );
			}
		}

		/**
		 * Registers settings sections, fields and settings
		 */
		public function register_settings() {
			/*
			 * Basic Section
			 */
			add_settings_section(
				'reachseo_section-basic',
				'Basic Settings',
				__CLASS__ . '::markup_section_headers',
				'reachseo_settings'
			);

			add_settings_field(
				'blb-api-key',
				'Api Key',
				array( $this, 'markup_fields' ),
				'reachseo_settings',
				'reachseo_section-basic',
				array( 'label_for' => 'blb-api-key' )
			);

			/*
			 * Email Section
			 */
			add_settings_section(
				'reachseo_section-email',
				'Email Settings',
				__CLASS__ . '::markup_section_headers',
				'reachseo_settings'
			);

			add_settings_field(
				'reachseo-email-host',
				'SMTP Host',
				array( $this, 'markup_fields' ),
				'reachseo_settings',
				'reachseo_section-email',
				array( 'label_for' => 'reachseo-email-host' )
			);
			add_settings_field(
				'reachseo-email-port',
				'SMTP Port',
				array( $this, 'markup_fields' ),
				'reachseo_settings',
				'reachseo_section-email',
				array( 'label_for' => 'reachseo-email-port' )
			);
			add_settings_field(
				'reachseo-email-encryption',
				'SMTP Encryption',
				array( $this, 'markup_fields' ),
				'reachseo_settings',
				'reachseo_section-email',
				array( 'label_for' => 'reachseo-email-encryption' )
			);
			add_settings_field(
				'reachseo-email-username',
				'Username',
				array( $this, 'markup_fields' ),
				'reachseo_settings',
				'reachseo_section-email',
				array( 'label_for' => 'reachseo-email-username' )
			);
			add_settings_field(
				'reachseo-email-password',
				'Password',
				array( $this, 'markup_fields' ),
				'reachseo_settings',
				'reachseo_section-email',
				array( 'label_for' => 'reachseo-email-password' )
			);

			add_settings_field(
				'reachseo-email-from-address',
				'Email Address',
				array( $this, 'markup_fields' ),
				'reachseo_settings',
				'reachseo_section-email',
				array( 'label_for' => 'reachseo-email-from-address' )
			);

			// The settings container
			register_setting(
				'reachseo_settings',
				'reachseo_settings',
				array( $this, 'validate_settings' )
			);
		}

		/**
		 * Adds the section introduction text to the Settings page
		 *
		 * @param array $section
		 */
		public static function markup_section_headers( $section ) {
			echo self::render_template( 'modules/settings/page-settings-section-headers.php', array( 'section' => $section ), 'always' );
		}

		/**
		 * Delivers the markup for settings fields
		 *
		 * @param array $field
		 */
		public function markup_fields( $field ) {
			switch ( $field['label_for'] ) {
				case 'blb-api-key':
					// Do any extra processing here
					break;
			}

			echo self::render_template( 'modules/settings/page-settings-fields.php', array( 'settings' => $this->settings, 'field' => $field ), 'always' );			
		}

		/**
		 * Validates submitted setting values before they get saved to the database. Invalid data will be overwritten with defaults.
		 *
		 * @mvc Model
		 *
		 * @param array $new_settings
		 * @return array
		 */
		public function validate_settings( $new_settings ) {
			$new_settings = shortcode_atts( $this->settings, $new_settings );

			if ( ! is_string( $new_settings['db-version'] ) ) {
				$new_settings['db-version'] = ReachSEO_Plugin::VERSION;
			}
			// api key
			if ( isset( $new_settings['basic']['blb-api-key'] ) ) {
				$new_settings['basic']['blb-api-key'] = sanitize_text_field( $new_settings['basic']['blb-api-key'] );
			}
			// email host (not checking for valid host right now)
			if ( isset( $new_settings['basic']['reachseo-email-host'] ) ) {
				$new_settings['basic']['reachseo-email-host'] = sanitize_text_field( $new_settings['basic']['reachseo-email-host'] );
			}
			// encrytion (either ssl or tls)
			if ( isset( $new_settings['basic']['reachseo-email-encryption'] ) ) {
				$new_settings['basic']['reachseo-email-encryption'] = sanitize_text_field( $new_settings['basic']['reachseo-email-encryption'] );
				if ( !in_array( $new_settings['basic']['reachseo-email-encryption'], array( 'ssl', 'tls' ) ) )
					$new_settings['basic']['reachseo-email-encryption'] = '';
			}
			// username (email address or username)
			if ( isset( $new_settings['basic']['reachseo-email-username'] ) ) {
				$new_settings['basic']['reachseo-email-username'] = sanitize_text_field( $new_settings['basic']['reachseo-email-username'] );
			}
			// password (email password)
			if ( isset( $new_settings['basic']['reachseo-email-password'] ) ) {
				$new_settings['basic']['reachseo-email-password'] = sanitize_text_field( $new_settings['basic']['reachseo-email-password'] );
			}
			// from email address
			if ( isset( $new_settings['basic']['reachseo-email-from-address'] ) ) {
				$new_settings['basic']['reachseo-email-from-address'] = sanitize_text_field( $new_settings['basic']['reachseo-email-from-address'] );
			}

			return $new_settings;
		}
	} // end ReachSEO_Settings
}
