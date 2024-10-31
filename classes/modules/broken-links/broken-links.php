<?php

if ( ! class_exists( 'ReachSEO_Broken_Links' ) ) {

	/**
	 * 
	 * The Broken Link Controller. For the most part, this handles the meta box for
	 * generating broken link reports on a given post page.
	 * 
	 */
	class ReachSEO_Broken_Links extends ReachSEO_Module {

		const OUTREACH_METHOD_TITLE = 'Broken Links';

		/**
		 * Constructor
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
		}

		/**
		 * Register callbacks for actions and filters
		 */
		public function register_hook_callbacks() {
			add_action( 'wp_json_server_before_serve', array( $this, 'rest_api_init' ) );
			add_action( 'reachseo-outreach-method-variables', array( $this, 'render_outreach_method_variables' ) );
			add_action( 'reachseo-outreach-method-option', array( $this, 'render_outreach_method_select_option' ), 10, 1 );
		}

		public function rest_api_init() {
			$rest_api = new ReachSEO_Broken_Links_Rest_API();
			add_filter( 'json_endpoints', array( $rest_api, 'register_routes' ) );
		}

		public function get_outreach_method_meta_box_params() {
			return array(
				'method' => 'broken-links',
				'icon' => 'broken-links-icon.png',
				'endpoint' => '/wp-json/reachseo/bl',
				'title' => self::OUTREACH_METHOD_TITLE
			);
		}

		public function render_outreach_method_variables() {
			echo self::render_template( 'modules/broken-links/outreach-method-variables.php' );
		}

		public function render_outreach_method_select_option( $active_method ) {
			$selected = $active_method == 'broken-links' ? 'selected' : '';
			?><option value="broken-links" <?php echo $selected; ?>>Broken Links</option><?php
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
		 * Cleanup after plugin uninstallation.
		 */
		public function uninstall() {
		}

		/**
		 * Initializes variables
		 */
		public function init() {
			
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 *
		 * @param string $db_version
		 */
		public function upgrade( $db_version = 0 ) {
			
		}

		/**
		 * Checks that the object is in a correct state
		 *
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function is_valid( $property = 'all' ) {
			
		}

	} // end ReachSEO_Broken_Links
}
