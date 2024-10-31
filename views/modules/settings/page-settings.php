<?php
	// 568c4f502602072010d49dba
	// Update intro if needed
	$settings = get_option( 'reachseo_settings', array() );
	$api_key = $settings['basic']['blb-api-key'];
	$email_settings = $settings['basic']['reachseo-email-host'];
	$tour = get_option( ReachSEO_Plugin::PREFIX . 'display_tour' );
	if ( $tour < 2 && !$api_key ) {
		// We have completed step 1 by making it to the settings page (but still no API key)
		update_option( ReachSEO_Plugin::PREFIX . 'display_tour', 2 );
		$tour = 2;
	}
	if ( $tour < 3 && $api_key ) {
		// We have completed step 2 since there is already an API key stored
		update_option( ReachSEO_Plugin::PREFIX . 'display_tour', 3 );
		$tour = 3;
	}
	if ( $tour < 4 && $email_settings ) {
		update_option( ReachSEO_Plugin::PREFIX . 'display_tour', 4 );
		$tour = 4;
	}

	wp_localize_script( 'reachseo_admin_intro', 'ReachSEO_Admin', array(
		'display_tour' => $tour,
		'wp_admin_url' => get_admin_url()
	));
?>

<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>	

	<h2><?php esc_html_e( REACHSEO_PLUGIN_NAME ); ?> Settings</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'reachseo_settings' ); ?>
		<?php do_settings_sections( 'reachseo_settings' ); ?>
		<div style="padding-left: 20px;">
			<p>Popular SMTP configurations:</p>
			<table class="table" style="max-width: 50%;">
				<thead>
					<tr>
						<th>Service</th>
						<th>Host</th>
						<th>Port</th>
						<th>Encryption</th>
						<th>Username</th>
						<th>Password</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Gmail</td>
						<td>smtp.gmail.com</td>
						<td>465</td>
						<td>ssl</td>
						<td>(Gmail email address)</td>
						<td>(Gmail account password)</td>
					</tr>
					<tr>
						<td>Outlook</td>
						<td>smtp-mail.outlook.com</td>
						<td>587</td>
						<td>tls</td>
						<td>(Full outlook email address)</td>
						<td>(outlook password)</td>
					</tr>
				</tbody>
			</table>
			<p>Note: If you are using Gmail, you need to enable a setting within your gmail account <a href="https://www.google.com/settings/security/lesssecureapps" target="_blank">here</a>.</p>
		</div>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
		</p>
	</form>
</div> <!-- .wrap -->
