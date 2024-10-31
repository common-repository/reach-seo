<?php

if ( ! class_exists( 'ReachSEO_Rest_API' ) ) {

	class InvalidEmailException extends Exception {};

	/**
	 * 
	 * Overall Plugin REST Api.
	 * 
	 */
	class ReachSEO_Rest_API extends ReachSEO_Rest_API_Interface {

		public function register_routes( $routes ) {
			$routes[ self::RESOURCE_URI . '/campaign' ] = array(
				array( array( $this, 'get_campaign' ), WP_JSON_Server::READABLE )
			);

			$routes[ self::RESOURCE_URI . '/campaign/opportunities' ] = array(
				array( array( $this, 'get_opportunites_for_campaign' ), WP_JSON_Server::READABLE )
			);

			$routes[ self::RESOURCE_URI . '/opportunity' ] = array(
				array( array( $this, 'update_opportunity' ), WP_JSON_Server::CREATABLE )
			);

			$routes[ self::RESOURCE_URI . '/opportunity/delete/(?P<id>\d+)' ] = array(
				array( array( $this, 'delete_opportunity' ), WP_JSON_Server::CREATABLE )
			);

			$routes[ self::RESOURCE_URI . '/opportunity/emails' ] = array(
				array( array( $this, 'get_opportunity_emails' ), WP_JSON_Server::READABLE )
			);

			$routes[ self::RESOURCE_URI . '/opportunity/outreach' ] = array(
				array( array( $this, 'send_outreach_email' ), WP_JSON_Server::CREATABLE )
			);
			
			return $routes;
		}

		/**
		 * GET @ /wp-json/reachseo/campaign
		 *
		 * Get the campaign for the given post_id. If a campaign does not yet exist, one will be created.
		 */
		public function get_campaign( $post_id, $nonce ) {
			check_ajax_referer( 'wp_json', 'nonce', true );

			$campaign = ReachSEO_Campaign::get( $post_id );
			if ( $campaign != null ) {
				return new WP_JSON_Response( array( 'campaign' => $campaign ) );
			}

			$campaign = new ReachSEO_Campaign( array(
				'post_id' => $post_id 
			));
			$campaign->save();

			return new WP_JSON_Response( array( 'campaign' => $campaign ) );
		}

		/**
		 * POST @ /wp-json/reachseo/opportunity
		 *
		 * Update an opportunity
		 */
		public function update_opportunity() {
			check_ajax_referer( 'wp_json', 'nonce', true );
			unset( $_POST[ 'nonce' ] );
			$_POST[ 'email_body' ] = stripslashes( $_POST[ 'email_body' ] );  // thanks to php magic_quotes of json
			/**
			 * NOTE: We clean the input within the model (see ReachSEO_Opportunity::sanitize).
			 */
			ReachSEO_Opportunity::update( $_POST );

			return new WP_JSON_Response( array( 'success' => true ) );
		}

		/**
		 * POST @ /wp-json/reachseo/opportunity/delete/{id}
		 *
		 * Delete an opportunity
		 */
		public function delete_opportunity( $id ) {
			check_ajax_referer( 'wp_json', 'nonce', true );

			if ( $id ) {
				ReachSEO_Opportunity::delete( $id );
			}

			return new WP_JSON_Response( array( 'deleted' => $id ) );
		}

		public function get_opportunity_emails( $id ) {
			check_ajax_referer( 'wp_json', 'nonce', true );
			$opportunity = ReachSEO_Opportunity::find( $id );
			if ( $opportunity != null ) {
				$domain = '';
				switch( $opportunity[ 'type' ] ) {
					case 'broken-links':
						$domain = $opportunity[ 'data' ]->source->domain;
						break;
					case 'contextual-content':
						$domain = $opportunity[ 'data' ]->backlink->domain;
						break;
				}
				$response = self::reach_remote_get( '/email-finder?domain=' . $domain, 15 );
				if ( is_array( $response ) ) {
					$res = json_decode( $response[ 'body' ] );
					$emails = $res->results;
					$opportunity[ 'data' ]->potential_emails = $emails;
					/**
					 * NOTE: We clean the input within the model (see ReachSEO_Opportunity::sanitize).
					 */
					ReachSEO_Opportunity::update( $opportunity );
					return new WP_JSON_Response( array( 'emails' => $emails ) );
				} else {
					return new WP_JSON_Response( $response );
				}
			} else {
				// No opportunity with the given id;
				return new WP_JSON_Response( array( 'error' => 'Invalid opportunity id.' ) );
			}
		}

		/**
		 * POST @ /wp-json/reachseo/outreach
		 *
		 * Send an outreach email for the given opportunity
		 */
		public function send_outreach_email() {
			check_ajax_referer( 'wp_json', 'nonce', true );
			unset( $_POST[ 'nonce' ] );

			$opportunity = $_POST;
			$settings = get_option( 'reachseo_settings', array() );
			$settings = $settings[ 'basic' ];

			try {
				// 0. Validate email addresses before we get started				
				$to = explode( ',', $opportunity[ 'email_to' ] );
				for ($i = 0; $i < count( $to ); $i++) { 
					if ( !filter_var( $to[ $i ], FILTER_VALIDATE_EMAIL) ) {
						throw new InvalidEmailException( 'Invalid email address given: ' . $to[ $i ] );
					}
				}
				if ( !filter_var( $settings[ 'reachseo-email-from-address' ], FILTER_VALIDATE_EMAIL) ) {
					$settings_url = get_admin_url() . 'options-general.php?page=reachseo_settings';
					throw new InvalidEmailException( 'Your email address ['.$settings[ 'reachseo-email-from-address' ].'] is invalid, please update your settings <a href="'.esc_attr( $settings_url ).'">here</a>: ' );
				}

				// 1. Setup the Transport
				$transport = Swift_SmtpTransport::newInstance();  // defaults to local smtp php mail
				if ( isset( $settings[ 'reachseo-email-host' ] ) && strlen($settings[ 'reachseo-email-host' ]) > 0 ) {
					$transport->setEncryption( $settings[ 'reachseo-email-encryption' ] )
							  ->setHost( $settings[ 'reachseo-email-host' ] )
							  ->setPort( $settings[ 'reachseo-email-port' ] )
							  ->setUsername( $settings[ 'reachseo-email-username' ] )
							  ->setPassword( $settings[ 'reachseo-email-password' ] );
				}
				// 2a. Create the mailer
				$mailer = Swift_Mailer::newInstance( $transport );				

				// 2b. Create the message
				$message = Swift_Message::newInstance( $opportunity[ 'email_subject' ] )
				  ->setFrom( array( $settings[ 'reachseo-email-from-address' ] ) )
				  ->setTo( $to )
				  ->setBody( stripslashes( $opportunity[ 'email_body' ] ), 'text/html' );
				// 3. Send the message
				$result = $mailer->send( $message );
				// 4. Update the opportunity
				$opportunity[ 'email_sent' ] = true;
				/**
				 * NOTE: We clean the input within the model (see ReachSEO_Opportunity::sanitize).
				 */
				ReachSEO_Opportunity::update( $opportunity );

				return new WP_JSON_Response( array(
					'success' => true,
					'recipients' => $result
				));
				// 3. update the opportunity in db
			} catch ( Exception $e ) {

				if ( $e instanceof Swift_TransportException || $e instanceof InvalidEmailException ) {
					$message = $e->getMessage();
				} else {
					$message = 'We could not send your email. Check your email account settings, and then check you are sending to a valid email address.';
				}

				return new WP_JSON_Response( array( 
					'error' => $message,
					'code' => $e->getCode()
				));
			}
		}

		public static function request_api_key( $email ) {
			$random_password = substr( str_shuffle( MD5( microtime() ) ), 0, 10);
			$response = wp_remote_post( 'https://app.reachseo.io/signup/free', array(
				'body' => array(
					'email' => $email,
					'password' => $random_password,
					'confirmPassword' => $random_password,
					'subscription' => 'tier-0',
					'rest' => true
				)
			));
			if ( is_wp_error( $response ) || $response[ 'response' ][ 'code' ] != 201 ) {  // 201 == created				
				return null;
			} else {
				return json_decode( $response[ 'body' ] );
			}
		}
	}
}