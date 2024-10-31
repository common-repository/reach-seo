<?php
/*
 * Basic Section
 */
?>

<?php if ( 'blb-api-key' == $field['label_for'] ) : ?>

	<input id="<?php esc_attr_e( 'reachseo_settings[basic][blb-api-key]' ); ?>" name="<?php esc_attr_e( 'reachseo_settings[basic][blb-api-key]' ); ?>" class="regular-text" value="<?php esc_attr_e( $settings['basic']['blb-api-key'] ); ?>" />
	<input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	
<?php endif; ?>

<?php if ( 'reachseo-email-host' == $field['label_for'] ) : ?>
	
	<input id="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-host]' ); ?>" name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-host]' ); ?>" class="regular-text" value="<?php esc_attr_e( $settings['basic']['reachseo-email-host'] ); ?>" />

<?php endif; ?>

<?php if ( 'reachseo-email-port' == $field['label_for'] ) : ?>

	<input id="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-port]' ); ?>" name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-port]' ); ?>" class="regular-text" value="<?php esc_attr_e( $settings['basic']['reachseo-email-port'] ); ?>" />

<?php endif; ?>

<?php if ( 'reachseo-email-encryption' == $field['label_for'] ) : ?>

	<?php $encryption = $settings['basic']['reachseo-email-encryption']; ?>
	<select id="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-encryption]' ); ?>" name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-encryption]' ); ?>" class="regular-text">
		<option value="" <?php if( $encryption == '' ){ echo 'selected'; }?>></option>
		<option value="tls" <?php if( $encryption == 'tls' ){ echo 'selected'; }?>>TLS</option>
		<option value="ssl" <?php if( $encryption == 'ssl' ){ echo 'selected'; }?>>SSL</option>
	</select>

<?php endif; ?>

<?php if ( 'reachseo-email-username' == $field['label_for'] ) : ?>

	<input id="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-username]' ); ?>" name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-username]' ); ?>" class="regular-text" value="<?php esc_attr_e( $settings['basic']['reachseo-email-username'] ); ?>" />

<?php endif; ?>

<?php if ( 'reachseo-email-password' == $field['label_for'] ) : ?>

	<input id="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-password]' ); ?>" name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-password]' ); ?>" class="regular-text" value="<?php esc_attr_e( $settings['basic']['reachseo-email-password'] ); ?>" type="password"/>

<?php endif; ?>

<?php if ( 'reachseo-email-from-address' == $field['label_for'] ) : ?>

	<input id="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-from-address]' ); ?>" name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-from-address]' ); ?>" class="regular-text" value="<?php esc_attr_e( $settings['basic']['reachseo-email-from-address'] ); ?>" />
	<p class="description">Note: this is the email address you are sending emails <em>from</em>.</p>

<?php endif; ?>


<?php if ( 'reachseo-email-transport' == $field['label_for'] ) : ?>
	<?php
	/**
	 * NOTE: this is not currently implemented, but we may want to add the option for different email transports (such as mail/sendmail)
	 */
	?>
	<?php $is_checked = $selected_transport == 'remote' ? 'checked' : ''; ?>
	<label>
		<input type="radio" value="remote" <?php echo $is_checked; ?> name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-transport]' ); ?>">
		Remote		
	</label>
	<?php if ( shell_exec('which sendmail') ): ?>
		<?php $is_checked = $selected_transport == 'sendmail' ? 'checked' : ''; ?>
		<label>
			<input type="radio" value="sendmail" <?php echo $is_checked; ?> name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-transport]' ); ?>">
			SendMail
		</label>
	<?php endif; ?>
	<?php if ( shell_exec('which mail') ): ?>
		<?php $is_checked = $selected_transport == 'mail' ? 'checked' : ''; ?>
		<label>
			<input type="radio" value="mail" <?php echo $is_checked; ?> name="<?php esc_attr_e( 'reachseo_settings[basic][reachseo-email-transport]' ); ?>">
			Mail
		</label>
	<?php endif; ?>
<?php endif; ?>


