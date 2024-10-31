<?php
	// post_id, api_key, and modules are available from controller

	$plugin_dir = plugins_url( '', dirname(__FILE__) );
	wp_enqueue_script( 'moment', $plugin_dir . '/javascript/vendor/moment.min.js', array( 'jquery' ), '2.10.6', true );
	wp_enqueue_script( 'handlebars', $plugin_dir . '/javascript/vendor/handlebars.js', array( 'jquery' ), '4.0.2', true );
	wp_enqueue_script( 'outreach-meta-box', $plugin_dir . '/javascript/outreach-meta-box.js', array( 'jquery' ), '0.0.1', true );
	wp_enqueue_style( 'outreach-meta-box', $plugin_dir . '/css/compiled/outreach-meta-box.css', array(), '0.0.1' );

	$campaign = ReachSEO_Campaign::get( $post_id );
	$existing_reports = ReachSEO_Report::find_all( $post_id, null, null );

	$nonce = wp_create_nonce( 'wp_json' );
	$outreach_page_url = get_admin_url() . '?page=outreach&campaign_id=' . $post_id;

	wp_localize_script( 'outreach-meta-box', 'ReachSEO', array(
		'campaign' => $campaign,
		'reports' => $existing_reports,
		'modules' => $modules,
		'nonce' => $nonce,
		'post_id' => $post_id,
		'outreach_page_url' => $outreach_page_url
	));

	$tour = isset( $_GET[ 'reachseo_tour' ] ) ? (int) $_GET[ 'reachseo_tour' ] : get_option( ReachSEO_Plugin::PREFIX . 'display_tour' );
	update_option( ReachSEO_Plugin::PREFIX . 'display_tour', $tour );
	wp_localize_script( 'reachseo_admin_intro', 'ReachSEO_Admin', array(
		'display_tour' => $tour,
		'wp_admin_url' => get_admin_url()
	));
?>
<div class="reachseo-meta-box">
	<div class="stage stage-search active">
		<div class="search-input-container">
			<p class="description">search term related to your post</p>
			<div class="input-group">
				<input class="query form-control" type="text">
				<span class="input-group-btn">
					<button class="next-stage toggle btn btn-default" type="button">Next</button>
				</span>
			</div>
		</div>
		<p class="error"></p>
		<div class="outreach-reports-list-container">
			<p>Reports</p>
			<ul class="outreach-reports-list"></ul>
		</div>
		<a href="<?php echo esc_attr( $outreach_page_url ); ?>" class="view-campaign">view all</a>
	</div>
	<div class="stage stage-outreach-method" style="display:none;">
		<p class="description">select an outreach campaign</p>
		<div class="outreach-methods">
			<?php foreach ($modules as $method => $module): ?>
				<div class="outreach-method outreach-method-<?php echo esc_attr( $method ); ?> active" data-outreach-method="<?php echo esc_attr( $method ); ?>" data-outreach-method-endpoint="<?php echo esc_attr( $module[ 'endpoint' ] ); ?>" title="<?php echo esc_attr( $module[ 'title' ] ); ?>">
					<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $module[ 'icon' ] ); ?>">
					<div class="loading preloader-wrapper big">
						<div class="spinner-layer">
							<div class="circle-clipper left">
								<div class="circle"></div>
							</div><div class="gap-patch">
								<div class="circle"></div>
							</div><div class="circle-clipper right">
								<div class="circle"></div>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<p><a class="back-to-default-stage" href="#">cancel</a></p>
	</div>

	<script type="x-handlebars-template" id="outreach-report-li-template">
		<li class="outreach-report-li">
			{{#if_report 'complete' this}}
			<a href="<?php echo esc_attr( $outreach_page_url ); ?>&report_id={{ report_id }}">
			{{/if_report}}
				<div class="icon-container">
					<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/{{ icon }}">
					<div class="loading preloader-wrapper big {{#if pending}}active{{/if}}">
						<div class="spinner-layer">
							<div class="circle-clipper left">
								<div class="circle"></div>
							</div><div class="gap-patch">
								<div class="circle"></div>
							</div><div class="circle-clipper right">
								<div class="circle"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="detail-container">
					<div class="keyword">{{ keyword }}</div>
					<div class="date">
						{{#if_report 'failed' this}}<span style="font-style:italic;color:red;">failed</span>{{/if_report}}
						{{#date_formatted date_completed }}{{/date_formatted}}
					</div>
				</div>
			{{#if_report 'complete' this}}
			</a>
			{{/if_report}}
		</li>
	</script>

</div>
