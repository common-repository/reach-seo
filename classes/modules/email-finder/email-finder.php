<?php

if ( ! class_exists( 'ReachSEO_Email_Finder' ) ) {

	/**
	 * 
	 * Email Finder Controller. For now simply registers the email finder rest api.
	 * 
	 */
	class ReachSEO_Email_Finder extends ReachSEO_Module {

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
		}

		public function rest_api_init() {
			$rest_api = new ReachSEO_Email_Finder_Rest_API();
			add_filter( 'json_endpoints', array( $rest_api, 'register_routes' ) );
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

	} // end ReachSEO_Email_Finder
}
