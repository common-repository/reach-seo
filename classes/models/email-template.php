<?php

if ( ! class_exists( 'ReachSEO_Email_Template' ) ) {	

	/**
	 * 
	 * Email Template
	 *
	 */
	class ReachSEO_Email_Template implements ReachSEO_Model_Interface {

		const TABLE_NAME = 'wp_reachseo_email_templates';

		/**
		 *
		 *	Static Methods
		 * 
		 */

		/**
		 * Get all email templates
		 */
		public static function find_all() {
			global $wpdb;
			// Grab the Campaign
			$sql = "SELECT * FROM `" . self::TABLE_NAME . "` ORDER BY id DESC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			foreach ($rows as $i => $row) {
				$rows[ $i ][ 'body' ] = stripslashes( htmlspecialchars_decode( $row[ 'body' ] ) );
			}
			return $rows;
		}

		public static function insert( $data ) {
			global $wpdb;
			self::sanitize( $data );
			$wpdb->insert( self::TABLE_NAME, $data );
		}

		public static function update( $data ) {
			global $wpdb;
			self::sanitize( $data );
			$wpdb->update( self::TABLE_NAME, array(
				'name' => $data[ 'name' ],
				'subject' => $data[ 'subject' ],
				'body' => $data[ 'body' ],
				'method' => $data[ 'method' ]
			), array( 'id' => $data[ 'id' ] ));
		}

		public static function delete( $id ) {
			global $wpdb;
			$wpdb->delete( self::TABLE_NAME, array( 'id' => $id ) );
		}

		private static function sanitize( &$data ) {
			$data[ 'name' ] = isset( $data[ 'name' ] ) ? sanitize_text_field( $data[ 'name' ] ) : '';
			$data[ 'name' ] = substr( $data[ 'name' ], 0, 200 );  // varchar(200)
			$data[ 'subject' ] = isset( $data[ 'subject' ] ) ? sanitize_text_field( $data[ 'subject' ] ) : '';
			$data[ 'subject' ] = substr( $data[ 'subject' ], 0, 200 );  // varchar(200)
			$data[ 'method' ] = isset( $data[ 'method' ] ) ? sanitize_text_field( $data[ 'method' ] ) : '';
			$data[ 'method' ] = substr( $data[ 'method' ], 0, 200 );  // varchar(200)
			$data[ 'body' ] = isset( $data[ 'body' ] ) ? htmlspecialchars( $data[ 'body' ] ) : '';
		}

		public static function create_table() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$table_name = self::TABLE_NAME;
			$sql = "CREATE TABLE $table_name (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  name varchar(200) NOT NULL,
			  subject varchar(200) NOT NULL,
			  body BLOB,
			  method varchar(200) NOT NULL,
			  UNIQUE KEY id (id)
			) $charset_collate;";
			dbDelta( $sql );

			$templates = ReachSEO_Email_Template::find_all();
			if ( count($templates) == 0 ) {
				ReachSEO_Email_Template::seed();
			}
		}

		public static function drop_table() {
			global $wpdb;
			$table_name = self::TABLE_NAME;
			$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		}

		public static function seed() {
			$broken_links_email = '<p>Hi there!</p>';
			$broken_links_email.= '<p>I was browsing your site and noticed that there\'s a broken link here: {{source.url}}</p>';
			$broken_links_email.= '<p>It links to: {{destination.domain}}</p>';
			$broken_links_email.= '<p>I recently wrote an article similar to the subject and I figured you might find it useful.</p>';
			$broken_links_email.= '<p>It is here: <a href="{{wp_post.url}}">{{{wp_post.title}}}</a></p>';
			$broken_links_email.= '<p>If you like the article, please replace the outdated article with mine.</p>';
			$broken_links_email.= '<p>Thanks!<br>{{wp_user.first_name}}</p>';
			$default_broken_links = array(
				'name' => 'Default Broken Link Template',
				'subject' => 'A Broken Link on your Site',
				'method' => 'broken-links',
				'body' => $broken_links_email
			);
			ReachSEO_Email_Template::insert( $default_broken_links );

			$contextual_content_email = '<p>Hi there!</p>';
			$contextual_content_email.= '<p>I noticed you have an article referencing <b>{{keyword}}</b> on your website here {{backlink.url}}</p>';
			$contextual_content_email.= '<p>Nice piece!  I took some inspiration from it in my new article here: <a href="{{wp_post.url}}">{{{wp_post.title}}}</a>. It would be great to get your opinion on my take.</p>';
			$contextual_content_email.= '<p>Anyway, let me know what you think!</p>';
			$contextual_content_email.= '<p>Best!<br>{{wp_user.first_name}}</p>';
			$default_contextual_content = array(
				'name' => 'Default Contextual Content Template',
				'subject' => 'Nice Entry!',
				'method' => 'contextual-content',
				'body' => $contextual_content_email
			);
			ReachSEO_Email_Template::insert( $default_contextual_content );

			$relevant_contacts_email = '<p>Hi {{user.name}},</p>';
			$relevant_contacts_email.= '<p>I noticed that you were talking about <b>{{keyword}}</b> on twitter. I thought you might be interested in an entry I wrote here: <a href="{{wp_post.url}}">{{{wp_post.title}}}</a>.</p>';
			$relevant_contacts_email.= '<p>Let me know what you think!';
			$relevant_contacts_email.= '<p>Thanks,<br>{{wp_user.first_name}}</p>';
			$default_relevant_contacts = array(
				'name' => 'Default Relevant Contacts Template',
				'subject' => 'Found you through Twitter',
				'method' => 'relevant-contacts',
				'body' => $relevant_contacts_email
			);
			ReachSEO_Email_Template::insert( $default_relevant_contacts );
		}		
	}

}