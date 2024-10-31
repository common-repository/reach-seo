<?php

/**
 * Abstract class to define/implement base methods for all rest api classes
 */
abstract class ReachSEO_Rest_API_Interface {

	const RESOURCE_URI = '/reachseo';
	const BLB_ENDPOINT = 'https://app.reachseo.io/api/v1';

	const NEW_REPORT_SUCCESS = 4;
	const REPORT_COMPLETED = 7;
	const REPORT_PREVIEW = 9;
	const REPORT_FAILED = 10;
	const RATE_LIMIT_HIT = 11;

	/**
	 * Register all routes associated with this rest module
	 */
	public abstract function register_routes( $routes );

	/**
	 * [reach_remote_get description]
	 * @param  [type] $resource [description]
	 * @return [type]      [description]
	 */
	public function reach_remote_get( $resource, $timeout = 10 ) {
		$settings = get_option( 'reachseo_settings' );
		$api_key = $settings[ 'basic' ][ 'blb-api-key' ];
		$response = wp_remote_get( self::BLB_ENDPOINT.$resource, array(
			'headers' => array( 'x-api-key' => $api_key ),
			'timeout' => $timeout
		));
		return $response;
	}

}
