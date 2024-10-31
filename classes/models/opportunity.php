<?php

if ( ! class_exists( 'ReachSEO_Opportunity' ) ) {

	/**
	 * Campaign
	 *
	 */
	class ReachSEO_Opportunity implements ReachSEO_Model_Interface {

		const TABLE_NAME = 'wp_reachseo_opportunities';

		/**
		 *
		 *	Static Methods
		 * 
		 */
		public static function insert( $data ) {
			$data = self::sanitize( $data );
			global $wpdb;
			$wpdb->insert( self::TABLE_NAME, $data );
		}

		public static function update( $data ) {
			global $wpdb;
			$data = self::sanitize( $data );
			$wpdb->update( self::TABLE_NAME, $data, array( 'id' => $data[ 'id' ] ));
		}

		public static function delete( $id ) {
			global $wpdb;
			$wpdb->delete( self::TABLE_NAME, array( 'id' => $id ) );
		}

		public static function find( $id ) {
			global $wpdb;
			$row = $wpdb->get_row( "SELECT * FROM `" . self::TABLE_NAME . "` WHERE `id` = \"$id\"", ARRAY_A );
			if ( $row != null ) {
				$row[ 'data' ] = json_decode( $row[ 'data' ] );
				$row[ 'email_body' ] = htmlspecialchars_decode( $row[ 'email_body' ] );
			}
			return $row;
		}

		public static function find_by_report_id( $report_id ) {
			global $wpdb;
			$sql = "SELECT * FROM `" . self::TABLE_NAME . "` WHERE `report_id` = \"$report_id\"";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			$opportunities = array();
			foreach ( $rows as $row ) {
				$row[ 'data' ] = json_decode( $row[ 'data' ] );
				$row[ 'email_body' ] = htmlspecialchars_decode( $row[ 'email_body' ] );
				array_push( $opportunities, $row );
			}
			return $opportunities;
		}

		/**
		 * Delete all reports associated with the given post id.
		 */
		public static function on_delete_post( $post_id ) {
			global $wpdb;
			$wpdb->delete( self::TABLE_NAME, array( 'post_id' => $post_id ) );
		}

		public static function sanitize( $data ) {
			$sanitized = array();
			// `id`: int
			$sanitized[ 'id' ] = isset( $data[ 'id' ] ) ? (int) $data[ 'id' ] : 0;
			// `post_id`: int
			$sanitized[ 'post_id' ] = isset( $data[ 'post_id' ] ) ? (int) $data[ 'post_id' ] : 0;
			// `report_id`: corresponds to a mongodb id, we can verify via regex			
			$sanitized[ 'report_id' ] = isset( $data[ 'report_id' ] ) && preg_match( '/^[0-9a-fA-F]{24}$/', $data[ 'report_id' ] ) ? $data[ 'report_id' ] : '';
			// `type`: string, no special chars, limited to 200 chars
			$sanitized[ 'type' ] = isset( $data[ 'type' ] ) ? sanitize_text_field( $data[ 'type' ] ) : '';
			$sanitized[ 'type' ] = substr( $sanitized[ 'type' ], 0, 200 );  // varchar( 200 )
			// `data`: expecting json (may be encoded already)
			if ( is_string( $data[ 'data' ] ) ) {
				$sanitized[ 'data' ] = sanitize_text_field( $data[ 'data' ] );
			} else {
				$sanitized[ 'data' ] = json_encode( $data[ 'data' ] );
			}
			// `email_sent`: bool
			$sanitized[ 'email_sent' ] = isset( $data[ 'email_sent' ] ) ? filter_var( $data[ 'email_sent' ], FILTER_VALIDATE_BOOLEAN ) : false;
			// `email_to`: list of emails (not going to validate emails here though)
			$sanitized[ 'email_to' ] = isset( $data[ 'email_to' ] ) ? sanitize_text_field( $data[ 'email_to' ] ) : '';
			// `email_subject`: text field, limit 255 chars
			$sanitized[ 'email_subject' ] = isset( $data[ 'email_subject' ] ) ? sanitize_text_field( $data[ 'email_subject' ] ) : '';
			$sanitized[ 'email_subject' ] = substr( $sanitized[ 'email_subject' ], 0, 255 );  // varchar(255)
			// `email_body`: stored as blob. this can contain html entities.
			$sanitized[ 'email_body' ] = isset( $data[ 'email_body' ] ) ? htmlspecialchars( $data[ 'email_body' ] ) : '';
			// `notes`: text field
			$sanitized[ 'notes' ] = isset( $data[ 'notes' ] ) ? sanitize_text_field( $data[ 'notes' ] ) : '';
			// `last_updated`: timestamp, not user inputed
			$sanitized[ 'last_updated' ] = time();
			return $sanitized;
		}

		public static function create_table() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = self::TABLE_NAME;
			$sql = "CREATE TABLE $table_name (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  post_id bigint(20) NOT NULL,
			  report_id varchar(24) NOT NULL,
			  type varchar(200) NOT NULL,
			  data LONGTEXT,
			  email_sent boolean DEFAULT 0 NOT NULL,
			  email_to TEXT,
			  email_subject varchar(255),
			  email_body BLOB,
			  notes TEXT,
			  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  UNIQUE KEY id (id)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		public static function drop_table() {
			global $wpdb;
			$table_name = self::TABLE_NAME;
			$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		}
	}

}