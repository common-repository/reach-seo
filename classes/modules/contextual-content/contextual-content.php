<?php

if ( ! class_exists( 'ReachSEO_Contextual_Content' ) ) {

	/**
	 * 
	 * Contextual Content Controller. Mostly handles the generation of Contextual Content reports and the
	 * section within the meta box.
	 * 
	 */
	class ReachSEO_Contextual_Content extends ReachSEO_Module {

		const OUTREACH_METHOD_TITLE = 'Backlinks';

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
			$rest_api = new ReachSEO_Contextual_Content_Rest_API();
			add_filter( 'json_endpoints', array( $rest_api, 'register_routes' ) );
		}

		public function get_outreach_method_meta_box_params() {
			return array(
				'method' => 'contextual-content',
				'icon' => 'contextual-content-icon.png',
				'endpoint' => '/wp-json/reachseo/cc',
				'title' => self::OUTREACH_METHOD_TITLE
			);
		}

		public function render_outreach_method_variables() {
			echo self::render_template( 'modules/contextual-content/outreach-method-variables.php' );
		}

		public function render_outreach_method_select_option( $active_method ) {
			$selected = $active_method == 'contextual-content' ? 'selected' : '';
			?><option value="contextual-content" <?php echo $selected; ?>>Contextual Content</option><?php
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

	} // end ReachSEO_Contextual_Content
}
