<?php

if ( ! class_exists( 'ReachSEO_Report' ) ) {	

	/**
	 * Campaign
	 *
	 */
	class ReachSEO_Report implements ReachSEO_Model_Interface {

		const TABLE_NAME = 'wp_reachseo_reports';

		public $post_id;

		/**
		 *
		 *	Instance Methods
		 * 
		 */
		public function __construct( $data ) {
		    $this->post_id = isset( $data["post_id"] ) ? $data[ 'post_id' ] : 0;
		    $this->report_id = isset( $data["id"] ) ? $data[ 'id' ] : 0;
		    $this->status = isset( $data["status"] ) ? $data[ 'status' ] : 'pending';
		    $this->keyword = isset( $data["keyword"] ) ? $data[ 'keyword' ] : '';
		    $this->type = isset( $data["type"] ) ? $data[ 'type' ] : '';
		    $this->date_completed = isset( $data["date_completed"] ) ? $data[ 'date_completed' ] : '';
		}

		public function save() {
			global $wpdb;
			$this->sanitize();
			$sql = "INSERT INTO `". self::TABLE_NAME ."`".
					" (`id`, `post_id`, `status`, `keyword`, `type`, `date_completed`)".
			 		" VALUES (%s, %d, %s, %s, %s, %d) ON DUPLICATE KEY UPDATE".
			 		" `status` = VALUES(`status`), `keyword` = VALUES(`keyword`), `type` = VALUES(`type`), `last_updated` = NOW(), `date_completed` = VALUES(`date_completed`);";
			$sql = $wpdb->prepare( $sql, $this->report_id, $this->post_id, $this->status, $this->keyword, $this->type, $this->date_completed);
			return $wpdb->query( $sql );
		}

		public function sanitize() {
			// `post_id`: int
		    $this->post_id = (int) $this->post_id;
		    // `report_id`: refering to a mongodb id, we can validate with regex
		    $this->report_id = preg_match( '/^[0-9a-fA-F]{24}$/', $this->report_id ) ? $this->report_id : '';
		    // `status`: enum
		    if ( !in_array( $this->status, array( 'pending', 'complete', 'failed' ) ) ) {
		    	$this->status = 'pending';
		    }
		    // `keyword`: text only input, varchar(200)
		    $this->keyword = sanitize_text_field( $this->keyword );
		    $this->keyword = substr( $this->keyword, 0, 200 );
		    // `type`: text only input, varchar(200)
		    $this->type = sanitize_text_field( $this->type );
		    $this->type = substr( $this->type, 0, 200 );
		    // `date_complete`: timestamp, bigint (cant cast to int)
		    $this->date_completed = is_numeric( $this->date_completed ) ? $this->date_completed : '';
		}

		/**
		 *
		 *	Static Methods
		 * 
		 */
		public static function get( $report_id ) {
			global $wpdb;
			$wpdb->show_errors();
			$sql = "SELECT * FROM `" . self::TABLE_NAME . "` WHERE `id` = \"$report_id\"";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			if ( $rows[0] == null )
				return null;
			$report = $rows[0];
			return new ReachSEO_Report( $report );
		}

		/**
		 * Find all reports associated with the given post (campaign).
		 */
		public static function find_all( $post_id, $include_opportunities = false, $type = null ) {
			global $wpdb;
			$sql = "SELECT * FROM `" . self::TABLE_NAME . "` WHERE `post_id` = \"$post_id\"";
			if ( $type != null )
				$sql.= " AND `type` = \"$type\";";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			$reports = array();
			foreach ( $rows as $row ) {
				$report = new ReachSEO_Report( $row );
				if ( $include_opportunities ) {
					$report->opportunities = ReachSEO_Opportunity::find_by_report_id( $report->report_id );
				}
				array_push( $reports, $report );
			}
			return $reports;
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
			  id varchar(24) NOT NULL,
			  post_id bigint(20) NOT NULL,
			  status enum('pending','complete','failed') NOT NULL,
			  keyword varchar(200),
			  type varchar(200),
			  date_completed BIGINT,
			  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  UNIQUE KEY id (id)
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