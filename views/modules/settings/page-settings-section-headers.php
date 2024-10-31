
<?php if ( $section[ 'id' ] == 'reachseo_section-basic' ): ?>
	<?php
		$settings = get_option( 'reachseo_settings', array() );
		if ( !$settings['basic']['blb-api-key'] ): ?>
	<p>To Begin using Reach SEO you need an API key. Signup (or sign-in), then copy your API key into the field below!</p>
	<iframe id="settings-iframe" src="https://app.reachseo.io/signup/free" width="100%" style="width: 100%; max-width: 800px; min-height: 400px;"></iframe>
	<?php endif; ?>
<?php endif; ?>

<?php if ( $section[ 'id' ] == 'reachseo_section-email' ): ?> 
	<p>Email settings are used for sending outreach emails. See below for popular email configurations including gmail and outlook.</p>
<?php endif; ?>