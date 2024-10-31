<?php

if ( ! class_exists( 'ReachSEO_Contextual_Content_Rest_API' ) ) {

	/**
	 * 
	 * Implementation for broken links REST API.
	 * 
	 */
	class ReachSEO_Contextual_Content_Rest_API extends ReachSEO_Rest_API_Interface {

		public function register_routes( $routes ) {
			$routes[ self::RESOURCE_URI . '/cc/report' ] = array(
				array( array( $this, 'get_report' ), WP_JSON_Server::READABLE )
			);
			$routes[ self::RESOURCE_URI . '/cc/report/new' ] = array(
				array( array( $this, 'request_report' ), WP_JSON_Server::READABLE )
			);
			return $routes;
		}

		public function get_report( $report_id, $post_id, $nonce ) {
			check_ajax_referer( 'wp_json', 'nonce', true );

			// 1. Grab the Report
			$report = ReachSEO_Report::get( $report_id );
			if ( $report != null ) {
				switch( $report->status ) {
					case 'pending':
						// 2a. The report is still pending, check the server for any updates
						$response = self::reach_remote_get( '/contextual-content/'.$report_id );
						if ( is_array( $response ) ) {
							$res = json_decode( $response[ 'body' ] );
							switch( $res->code ) {
								case self::REPORT_COMPLETED:
									// Save the report in db.
									$report->status = 'complete';
									$report->date_completed = time();
									$report->save();
									// Save the results of the report as `opportunites`
									foreach ( $res->report->contextual_links as $content ) {
										// Each link may have up to 25 backlinks. We need to treat the backlinks as individual opportunities.
										foreach ( $content->backlinks as $backlink ) {
											/**
											 * NOTE: We clean the input within the model (see ReachSEO_Opportunity::sanitize).
											 */
											ReachSEO_Opportunity::insert( array(
												'post_id' => $post_id,
												'report_id' => $report_id,
												'type' => 'contextual-content',
												'data' => array(
													'source' => array(
														'url' => $content->source->url,
														'domain' => $content->source->domain,
														'title' => $content->source->title,
														'shares' => $content->shares,
													),
													'backlink' => $backlink
												)
											));
										}
									}
									break;
								case self::REPORT_FAILED:
									$report->status = 'failed';
									$report->save();
									break;
							}
							return new WP_JSON_Response( $report );
						} else {
							return new WP_JSON_Response( $response );
						}
					case 'complete':
					case 'failed':
						// 2b. The report is complete, simply send it back!
						// 2c. The report failed to complete
						return new WP_JSON_Response( $report );
				}
			} else {
				return new WP_JSON_Response( array( 'status' => 'failed' ) );
			}
		}

		/**
		 * Request a new report for the given keyword.
		 * 
		 */
		public function request_report( $keyword, $post_id, $nonce ) {
			check_ajax_referer( 'wp_json', 'nonce', true );

			// We need keyword AND api key
			$response = self::reach_remote_get( '/contextual-content?keyword='.urlencode($keyword) );

			if ( is_array( $response ) ) {
				$res = json_decode( $response[ 'body' ] );
				if ( $res ) {
					switch( $res->code ) {
						case self::NEW_REPORT_SUCCESS:
							// Create the new report
							$report = new ReachSEO_Report( array(
								'id' => $res->report_id,
								'post_id' => $post_id,
								'keyword' => $keyword,
								'type' => 'contextual-content',
								'status' => 'pending'
							));
							$report->save();
							$res->report = $report;
							break;
						default:
							break;
					}
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