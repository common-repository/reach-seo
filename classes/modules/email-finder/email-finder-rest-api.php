<?php

if ( ! class_exists( 'ReachSEO_Email_Finder_Rest_API' ) ) {

	/**
	 * 
	 * Implementation for broken links REST API.
	 * 
	 */
	class ReachSEO_Email_Finder_Rest_API extends ReachSEO_Rest_API_Interface {

		public function register_routes( $routes ) {
			$routes[ self::RESOURCE_URI . '/ef' ] = array(
				array( array( $this, 'get_emails_by_domain' ), WP_JSON_Server::READABLE )
			);
			return $routes;
		}

		public function get_emails_by_domain( $domain, $nonce ) {
			check_ajax_referer( 'wp_json', 'nonce', true );

			$response = self::reach_remote_get( '/email-finder?domain='.urlencode($domain) );

			if ( is_array( $response ) ) {
				$res = json_decode( $response[ 'body' ] );
				if ( $res ) {
					return new WP_JSON_Response( $res );
				} else {
					return new WP_JSON_Response( array( 'errors' => $response[ 'response' ] ) );
				}
			} else {
				return new WP_JSON_Response( $response );
			}
		}
	}
}