<?php

if ( ! class_exists( 'ReachSEO_Campaign' ) ) {	

	/**
	 * Campaign
	 *
	 */
	class ReachSEO_Campaign implements ReachSEO_Model_Interface {

		const TABLE_NAME = 'wp_reachseo_campaigns';

		public $post_id;
		public $reports;

		/**
		 *
		 *	Instance Methods
		 * 
		 */
		public function __construct( $data ) {
			// NOTE: the only model we actually use contructor for. Important thing here is that we are attaching additional post
			// data to the campaign, but is not stored in the campaign itself...
		    $this->post_id = intval( $data[ 'post_id' ] );
		    $this->reports = array();
		    $this->post = array(
		    	'title' => get_the_title( $this->post_id ),
		    	'url' => get_post_permalink( $this->post_id ),
		    	'id' => $this->post_id,
		    	'featured_image' => wp_get_attachment_url( get_post_thumbnail_id( $this->post_id ) )
		    );
		}

		public function save() {
			global $wpdb;
			$this->post_id = intval( $this->post_id );  // sanitize post_id
			$sql = "INSERT INTO `". self::TABLE_NAME ."` (`post_id`) VALUES (%d) ON DUPLICATE KEY UPDATE `last_updated` = NOW();";
			$sql = $wpdb->prepare( $sql, $this->post_id );
			return $wpdb->query( $sql );
		}

		/**
		 *
		 *	Static Methods
		 * 
		 */
		
		/**
		 * Get a single campaign by id (NOTE: campaign id corresponds to post_id)
		 * 
		 * @param int $post_id
		 * @param boolean $include_reports Whether or not to include the reports associated with this campaign.
		 * @return ReachSEO_Campaign
		 */
		public static function get( $post_id, $include_reports = true ) {
			global $wpdb;
			// Grab the Campaign
			$sql = "SELECT * FROM `" . self::TABLE_NAME . "` WHERE `post_id` = $post_id";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			$campaign = null;
			if ( $rows[0] != null ) {
				$campaign = new ReachSEO_Campaign( $rows[0] );
			} else {
				$campaign = new ReachSEO_Campaign(array(
					'post_id' => $post_id
				));
				$campaign->save();
			}
			if ( $include_reports ) {
				// Fetch all reports associated with this campaign
				$campaign->reports = ReachSEO_Report::find_all( $post_id );
			}
			return $campaign;
		}

		/**
		 * Fetch all campaigns, with the option to include each campaigns individual reports.
		 * 
		 * @param $include_reports Whether or not to include the reports for each campaign.
		 * @return [ReachSEO_Campaign] array of campaigns
		 */
		public static function find_all( $include_reports = false ) {
			global $wpdb;
			// Grab the Campaign
			$sql = "SELECT * FROM `" . self::TABLE_NAME . "`";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			$campaigns = array();
			foreach ( $rows as $row ) {
				$campaign = new ReachSEO_Campaign( $row );
				if ( $include_reports ) {
					// Fetch all reports associated with this campaign
					$campaign->reports = ReachSEO_Report::find_all( $campaign->post_id, true );
				}
				array_push( $campaigns, $campaign );
			}
			return $campaigns;
		}

		/**
		 * Delete all reports associated with the given post id.
		 */
		public static function on_delete_post( $post_id ) {
			global $wpdb;
			$wpdb->delete( self::TABLE_NAME, array( 'post_id' => $post_id ) );
		}

		public static function create_table() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$table_name = self::TABLE_NAME;
			$sql = "CREATE TABLE $table_name (
			  post_id bigint(20) NOT NULL,
			  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  UNIQUE KEY post_id (post_id)
			) $charset_collate;";
			dbDelta( $sql );
		}

		public static function drop_table() {
			global $wpdb;
			$table_name = self::TABLE_NAME;
			$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		}
	}

}