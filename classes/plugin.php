<?php

if ( ! class_exists( 'ReachSEO_Plugin' ) ) {

	/**
	 * Main
	 *
	 * ReachSEO_Plugin is the overall plugin controller. Each individual component of this plugin
	 * can (and should) be factored out into it's own module.
	 *
	 * Order of instantiation
	 * 
	 * 	bootstrap ->
	 * 		ReachSEO_Plugin ->
	 * 			all modules defined in __construct
	 *
	 * HOW TO ADD A MODULE:
	 * 	1. extend the ReachSEO_Module class
	 * 	2. implement abstract methods
	 * 	3. add a `require_once` clause to bootstrap.php
	 * 	4. add the module to the $modules array in ReachSEO_Plugin __construct 
	 */
	class ReachSEO_Plugin extends ReachSEO_Module {

		protected $modules;
		protected $models;

		const VERSION    = '1.0.5';
		const PREFIX     = 'reachseo_';
		const CAMPAIGNS_TABLE = 'reachseo_campaigns';
		const OPPORTUNITIES_TABLE = 'reachseo_opportunities';

		/**
		 * Constructor
		 */
		protected function __construct() {
			$this->register_hook_callbacks();

			$this->modules = array(
				'ReachSEO_Settings'     => ReachSEO_Settings::get_instance(),
				'ReachSEO_Broken_Links' => ReachSEO_Broken_Links::get_instance(),
				'ReachSEO_Contextual_Content' => ReachSEO_Contextual_Content::get_instance(),
				'ReachSEO_Relevant_Contacts' => ReachSEO_Relevant_Contacts::get_instance(),
				'ReachSEO_Email_Finder' => ReachSEO_Email_Finder::get_instance(),
			);

			$this->models = array(
				'ReachSEO_Campaign'			=> ReachSEO_Campaign,
				'ReachSEO_Opportunity'		=> ReachSEO_Opportunity,
				'ReachSEO_Report'			=> ReachSEO_Report,
				'ReachSEO_Email_Template'	=> ReachSEO_Email_Template,
			);
		}

		/**
		 * Register callbacks for actions and filters
		 */
		public function register_hook_callbacks() {
			// add_action( 'wp_enqueue_scripts',    __CLASS__ . '::load_resources' );
			add_action( 'admin_enqueue_scripts', __CLASS__ . '::load_admin_resources' );
			add_action( 'admin_init',            array( $this, 'admin_init' ) );
			add_action( 'init',                  array( $this, 'upgrade' ), 11 );
			add_action( 'admin_menu', array( $this, 'add_outreach_menu_item' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_campaign_meta_box' ) );
			add_action( 'wp_json_server_before_serve', array( $this, 'rest_api_init' ) );
		}

		public function rest_api_init() {
			$rest_api = new ReachSEO_Rest_API();
			add_filter( 'json_endpoints', array( $rest_api, 'register_routes' ) );
		}

		/**
		 * Adds a box to the main column on the Post edit screens.
		 */
		public function add_campaign_meta_box() {
			$screens = array( 'post' );
			foreach ( $screens as $screen ) {
				add_meta_box(
					'reachseo_campaign_meta_box',
					'ReachSEO',
					array( $this, 'render_campaign_meta_box' ),
					$screen,
					'side'
				);
			}
		}
		public function render_campaign_meta_box( $post ) {
			// First things first, check for API key
			$settings = get_option( 'reachseo_settings' );
			$api_key = false;
			if ( $settings && isset($settings['basic']) && isset($settings['basic']['blb-api-key']) ) {
				$api_key = $settings[ 'basic' ][ 'blb-api-key' ];
			}
			if ( !$api_key ):
				?>
				<div class="blb-meta-box">
					<p>No API Key =(</p>
					<a href="<?php echo admin_url('options-general.php?page=reachseo_settings') ?>">Setup your API key here!</a>
				</div>
				<?php
			else:
				// Fetch any modules that need to be added to the meta box.
				$modules = array();
				foreach ( $this->modules as $module ) {
					if ( method_exists( $module, 'get_outreach_method_meta_box_params' ) ) {
						$params = $module->get_outreach_method_meta_box_params();
						$modules[ $params[ 'method' ] ] = $params;
					}
				}
				// Head
				echo self::render_template( 'outreach-meta-box.php', array( 
					'api_key' => $api_key,
					'post_id' => $post->ID,
					'modules' => $modules
				));
			endif;
		}

		/**
		 * Add Menu Item to the admin sidebar for the Outreach Page.
		 */
		public function add_outreach_menu_item() {
			add_menu_page( 'Reach SEO Outreach', 'Outreach', 'manage_options', 'outreach', array( $this, 'render_outreach_page' ), 'dashicons-megaphone', 6 );		
			add_submenu_page( 'outreach', 'Reach SEO Email Templates', 'Email Templates', 'manage_options', 'outreach-email-templates', array( $this, 'render_outreach_email_templates_page') );
			add_submenu_page( 'outreach', 'Reach SEO Settings', 'Settings', 'administrator', 'outreach-settings', 'ReachSEO_Settings::markup_settings_page' );
		}
		public function render_outreach_page() {
			// Fetch any modules that need to be added to the meta box.
			$modules = array();
			foreach ( $this->modules as $module ) {
				if ( method_exists( $module, 'get_outreach_method_meta_box_params' ) ) {
					$params = $module->get_outreach_method_meta_box_params();
					$modules[ $params[ 'method' ] ] = $params;
				}
			}
			echo self::render_template( 'outreach-page.php', array (
				'modules' => $modules
			));
		}
		public function render_outreach_email_templates_page() {
			echo self::render_template( 'outreach-email-templates-page.php' );
		}

		/**
		 * Enqueues CSS, JavaScript, etc for admin
		 */
		public static function load_admin_resources( $hook ) {
			//Prevents Boostrap from loading on other plugin's pages.
			global $pagenow;
			$plugin_dir = plugins_url( '', dirname(__FILE__) );
			if( (isset($_GET['page']) && ( $_GET['page'] == 'outreach') || $_GET['page'] == 'outreach-email-templates' ) ) {				
				wp_enqueue_script( 'bootstrap', $plugin_dir . '/javascript/vendor/bootstrap.min.js', array( 'jquery' ), '0.0.1', true );
				wp_enqueue_style( 'bootstrap', $plugin_dir . '/css/vendor/bootstrap.min.css', array(), '0.0.1' );
			}

			if ( current_user_can( 'manage_options' ) ) {
				$tour = get_option( ReachSEO_Plugin::PREFIX . 'display_tour' );
				if ( !$tour )
					$tour = 1;
				wp_enqueue_script( 'reachseo_admin_intro', $plugin_dir . '/javascript/admin-intro.js', array( 'jquery' ), ReachSEO_Plugin::VERSION, true );
				wp_enqueue_style( 'reachseo-admin-global', $plugin_dir . '/css/compiled/reachseo-admin-global.css', array(), ReachSEO_Plugin::VERSION );
				wp_localize_script( 'reachseo_admin_intro', 'ReachSEO_Admin', array(
					'display_tour' => $tour,
					'wp_admin_url' => get_admin_url()
				));
			}
		}


		/*
		 * Instance methods
		 */

		/**
		 * Prepares site to use the plugin (activate all modules). Note that
		 * the models are handled in upgrade instead.
		 */
		public function activate() {			
			// Module activations
			foreach ( $this->modules as $module ) {
				$module->activate();
			}
			if ( $activation ) {  // SHOULD only be used on plugin activation!
				flush_rewrite_rules();
			}
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
			foreach ( $this->modules as $module ) {
				$module->deactivate();
			}
			flush_rewrite_rules();
		}

		/**
		 * Uninstallation of this plugin. Cleanup time.
		 */
		public function uninstall() {
			delete_option( self::PREFIX . 'version' );
			// Main plugin uninstall
			
			// Model table uninstalls
			foreach ( $this->models as $model ) {
				$refl = new ReflectionClass( $model );
				if ( $refl->hasMethod( 'drop_table' ) ) {
					$drop_table = $refl->getMethod( 'drop_table' );
					$drop_table->invoke( null );  // null == static method
				}
			}

			// Module uninstalls
			foreach ( $this->modules as $module ) {
				$module->uninstall();
			}
		}

		/**
		 * Admin hooks
		 */
		public function init() {

		}

		public function admin_init() {
			// Each model has the opportunity to clean up resources when deleting a post.
			foreach ( $this->models as $model ) {
				$refl = new ReflectionClass( $model );
				if ( $refl->hasMethod( 'on_delete_post' ) ) {
					add_action( 'delete_post', array( $model, 'on_delete_post' ), 10 );
				}
			}
		}

		/**
		 * Checks if the plugin was recently updated and upgrades if necessary
		 *
		 * @param string $db_version
		 */
		public function upgrade( $db_version = 0 ) {
			$previous_version = get_option( ReachSEO_Plugin::PREFIX.'version' );
			if ( $previous_version == self::VERSION ) {
				return;
			}

			foreach ( $this->modules as $module ) {
				$module->upgrade( self::VERSION );
			}

			foreach ( $this->models as $model ) {
				$refl = new ReflectionClass( $model );
				if ( $refl->hasMethod( 'create_table' ) ) {
					$create_table = $refl->getMethod( 'create_table' );
					$create_table->invoke( null );  // null == static method
				}
			}
			
			$settings = get_option( 'reachseo_settings', array() );
			$api_key = $settings['basic']['blb-api-key'];	
			$auto_generated = false;

			// If the user does not have an api key, sign them up! Use their email address associated with
			// WordPress account.
			if ( !isset($api_key) || !is_string($api_key) || $api_key == '' ) {
				global $current_user;
				get_currentuserinfo();
				$email_address = isset( $settings[ 'basic' ][ 'reachseo-email-from-address' ] ) 
									&& $settings[ 'basic' ][ 'reachseo-email-from-address' ] != '' 
									? $settings[ 'basic' ][ 'reachseo-email-from-address' ] 
									: $current_user->user_email;
				$settings[ 'basic' ][ 'reachseo-email-from-address' ] = $email_address;
				$api_key_response = ReachSEO_Rest_API::request_api_key( $email_address );
				if ( $api_key_response != null ) {
					$settings[ 'basic' ][ 'blb-api-key' ] = $api_key_response->_id;
					$auto_generated = true;
				}
				$this->modules[ 'ReachSEO_Settings' ]->settings = $settings;
			}

			// Lets assume if they already have an API key they do not need to see the intro/tutorial
			if ( $api_key && !$auto_generated ) {
				update_option( ReachSEO_Plugin::PREFIX.'display_tour', 99 );
			} else {
				update_option( ReachSEO_Plugin::PREFIX.'display_tour', 1 );
			}

			// Finally, update with new version number
			update_option( ReachSEO_Plugin::PREFIX.'version', self::VERSION );
		}

		/**
		 * Checks that the object is in a correct state
		 *
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function is_valid( $property = 'all' ) {
			return true;
		}
	} // end ReachSEO_Plugin
}
