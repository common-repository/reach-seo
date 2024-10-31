<div class="error">
	<p><?php echo REACHSEO_PLUGIN_NAME; ?> error: Your environment doesn't meet all of the system requirements listed below.</p>

	<ul class="ul-disc">
		<?php if ( version_compare( PHP_VERSION, REACHSEO_REQUIRED_PHP_VERSION, '<' ) ): ?>
		<li>
			<strong>PHP <?php echo REACHSEO_REQUIRED_PHP_VERSION; ?>+</strong>
			<em>(You're running version <?php echo PHP_VERSION; ?>)</em>
			<br>
			<p>If you need to upgrade your version of PHP you can ask your hosting company for assistance, and if you need help upgrading WordPress you can refer to <a href="http://codex.wordpress.org/Upgrading_WordPress">the Codex</a>.</p>
		</li>
		<?php endif; ?>

		<?php if ( version_compare( $wp_version, REACHSEO_REQUIRED_WP_VERSION, '<' ) ): ?>		
		<li>
			<strong>WordPress <?php echo REACHSEO_REQUIRED_WP_VERSION; ?>+</strong>
			<em>(You're running version <?php echo esc_html( $wp_version ); ?>)</em>
		</li>
		<?php endif; ?>		

		<?php if ( ! class_exists( 'WP_JSON_Server' ) ): ?>
		<li>
			<strong>WP REST API (WP API)</strong>
			<em>(Version ~1.2.5 REQUIRED)</em>
			<a href="/wp-admin/plugin-install.php?tab=plugin-information&plugin=json-rest-api">download now!</a>
		</li>
		<?php endif; ?>
	</ul>
</div>
