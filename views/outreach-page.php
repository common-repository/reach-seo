<?php
/**
 * This is the main outreach page.
 *
 * Note: $modules is passed from render_outreach_page() in plugin.php
 */

$plugin_dir = plugins_url( '', dirname(__FILE__) );

wp_enqueue_script( 'moment', $plugin_dir . '/javascript/vendor/moment.min.js', array( 'jquery' ), '2.10.6', true );
wp_enqueue_script( 'handlebars', $plugin_dir . '/javascript/vendor/handlebars.js', array( 'jquery' ), '4.0.2', true );
wp_enqueue_script( 'stickysectionheaders', $plugin_dir . '/javascript/vendor/jquery.stickysectionheaders.js', array( 'jquery' ), '1.0', true );
wp_enqueue_script( 'outreach-page', $plugin_dir . '/javascript/outreach-page.js', array( 'jquery', 'moment', 'handlebars', 'stickysectionheaders' ), ReachSEO_Plugin::VERSION, true );

wp_enqueue_style( 'outreach-page', $plugin_dir . '/css/compiled/outreach-page.css', array(), ReachSEO_Plugin::VERSION );
require_once __DIR__.'/outreach-page-templates.hbs';

$campaign_id = $_GET[ 'campaign_id' ];
$report_id = $_GET[ 'report_id' ];

$email_templates_url = get_admin_url() . 'admin.php?page=outreach-email-templates';
$nonce = wp_create_nonce( 'wp_json' );
$campaigns = ReachSEO_Campaign::find_all( true );

$active_campaign = null;
$active_report = null;
$active_outreach_method = null;

foreach ( $campaigns as $campaign ) {	
	if ( $campaign->post_id == $campaign_id ) {
		$active_campaign = $campaign_id;
		foreach ( $campaign->reports as $report ) {
			if ( $report->report_id == $report_id ) {
				$active_report = $report_id;
				$active_outreach_method = $report->type;
				break;
			}
		}
		break;
	}
}

$email_templates = ReachSEO_Email_Template::find_all();

global $current_user;
get_currentuserinfo();
$user_data = array(
	'display_name' => $current_user->display_name,
	'first_name' => $current_user->user_firstname,
	'last_name' => $current_user->user_lastname,
	'email' => $current_user->user_email,
);

// We basically pass all the info to the frontend and let the javascript handle all our logic (since the frontend is dynamic anyway, we dont want to repeat the logic).
wp_localize_script( 'outreach-page', 'ReachSEO', array(
	'campaigns' => $campaigns,
	'active_campaign' => $active_campaign,
	'active_outreach_method' => $active_outreach_method,
	'active_report' => $active_report,
	'wp_user' => $user_data,
	'nonce' => $nonce
));

// Update intro if needed
$tour = $_GET[ 'reachseo-tour' ];
if ( isset( $_GET[ 'reachseo-tour' ] ) || count( $campaigns ) > 0 ) {
	if ( $tour == 'end' || count( $campaigns ) > 0 ) {
		// If the user canceled tour OR the user already has campaigns...
		update_option( ReachSEO_Plugin::PREFIX . 'display_tour', 99 );
	}
}
$tour = get_option( ReachSEO_Plugin::PREFIX . 'display_tour' );
if ( $tour < 1 ) {
	update_option( ReachSEO_Plugin::PREFIX . 'display_tour', 1 );
	wp_localize_script( 'reachseo_admin_intro', 'ReachSEO_Admin', array(
		'display_tour' => 1
	));
}

?>

<div class="container-fluid outreach-container">
	<div class="row row-header">
		<div class="page-header">
			<h1>Outreach <small id="active-campaign">Reach SEO</small></h1>
		</div>
	</div>
	<div class="row row-content">

		<div class="col-sm-3 nav-outer container">
			<div class="nav-inner">
				<?php
				/**
				 * Navigation container:
				 *
				 * A single .nav-container is active at a given time. We change the active nav by moving
				 * .nav-inner absolutely within .nav-outer. All .nav-containers are inline horizontally
				 * so we get a slide left/right navigation effect.
				 *
				 * Note: the class names [nav-*] correspond to the class names [content-*] of the content
				 * containers below. For example, when [nav-xyz] becomes active, so does the [content-xyz]
				 * container.
				 */
				?>
				<div class="nav-container nav-campaigns" style="display:none;">
					<h3><span>Posts</span></h3>
					<div id="outreach-campaigns-list-container" class="list-container">
						<?php if ( count( $campaigns ) == 0 ): ?>
						<p style="white-space: normal; padding: 10px;">It looks like you have not started any outreach campaigns. Find out how <b>→</b></p>
						<?php else: ?>
						<ul id="campaigns-list" class="list-unstyled">
							<?php foreach ( $campaigns as $campaign ): ?>
								<?php $campaign_title = get_the_title( $campaign->post_id );  // get_the_title already escapes ?>
								<li class="campaign" data-campaign-id="<?php echo esc_attr( $campaign->post_id ); ?>" data-campaign-title="<?php echo esc_attr( $campaign_title ); ?>"><?php echo esc_html( $campaign_title ); ?></li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</div>
				</div>

				<div class="nav-container nav-outreach-method" style="display:none;">
					<h3><span class="nav-back" data-back-to="campaigns">←</span><span class="title">Outreach Campaign</span></h3>
					<ul class="outreach-methods">
						<?php foreach( $modules as $method => $module ): ?>
							<li data-outreach-method="<?php echo esc_attr( $method ); ?>">
								<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $module[ 'icon' ] ); ?>">
								<div class="details">
									<div class="title"><?php echo esc_html( $module[ 'title' ] ); ?></div>
									<div class="count">0</div>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="nav-container nav-outreach" style="display:none;">
					<h3><span class="nav-back" data-back-to="outreach-method">←</span>
						<span class="outreach-method-icon"></span>
						<span class="outreach-method-title"></span>
					</h3>
					<div id="outreach-opportunity-list-container" class="list-container">
						<ul id="outreach-opportunity-list" class="list-unstyled"></ul>
					</div>
				</div>
				<?php
				/**
				 * End of Navigation
				 */
				?>
			</div>
		</div>
		
		<main class="col-sm-9 col-content-pane">
			<div class="content-container">
				<?php
				/**
				 * Content Container:
				 *
				 * A single .content-container-state will be active at any given time based on 
				 * the current navigation level.
				 *
				 * Note: read the Navigation Container note about matching class names.
				 */
				?>
				<div class="content-container-state content-campaigns active" style="display:none;">
					<h2>Welcome to Reach SEO! <small>Automate your outreach workflow</small></h2>
					<p>This plugin allows you to promote your content through different outreach campaigns. If you have more questions about how to use this plugin, learn how it works: <a href="https://reachseo.io/how-it-works/" target="_blank">https://reachseo.io/how-it-works/</a></p>

					<div class="container-fluid">
						<div class="row">
							<div class="col-sm-4">
								<h3>① Select a Post</h3>
								<p>Choose one of your posts that you would like to promote! Here are a few of your most recent:</p>
								<ul class="list-unstyled">
									<?php $recent_posts = wp_get_recent_posts( array( 'numberposts' => 3, 'post_status' => 'publish' ) ); ?>
									<?php foreach ( $recent_posts as $recent ): ?>
										<li>- <a href="<?php echo get_edit_post_link( $recent['ID'] ); ?>"><?php echo $recent[ 'post_title' ]; ?></a></li>
									<?php endforeach; ?>
								</ul>
								<h3 style="margin-top: 40px;">② Fetch Leads</h3>
								<p>Find the Reach SEO meta box, type in keywords related to your post, and then select your outreach campaign. It may take up to 5 minutes at a time to generate leads.</p>
							</div>
							<div class="col-sm-8" style="text-align: center;">
								<h3>&nbsp;</h3>
								<img src="<?php echo $plugin_dir ?>/images/screenshot-1.gif" width="100%" style="max-width: 480px;">
							</div>
						</div>
						<div class="row">							
							<div class="col-sm-12">
								<h3 style="margin-top: 40px;">③ Send Outreach Emails</h3>
								<p>Once your leads are generated, come back here to begin sending outreach emails.
								   Select your post on the left, select an outreach campaign, and begin processing your leads!
								   We recommend personalizing your outreach emails for the best results.
								</p>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-12" style="text-align: left;">
								<img src="<?php echo $plugin_dir ?>/images/screenshot-2.cropped.gif" width="100%" style="max-width: 480px; margin-top: 30px;">
							</div>
						</div>
					</div>
				</div>

				<div class="content-container-state content-outreach-method" style="display:none;">
					<h2>Outreach Campaigns</h2>
					<div class="container-fluid">
						<div class="col-sm-4">
							<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $modules[ 'broken-links' ][ 'icon' ] ); ?>">
							<h4>Broken Link Building</h4>
							<p> We find broken links related to your content/keywords. 
								The goal is to replace these <i>broken links</i> with a link to <i>your</i> content.
								This is a win-win for both parties, and a great way to boost SEO for your post/site.
							</p>
						</div>
						<div class="col-sm-4">
							<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $modules[ 'contextual-content' ][ 'icon' ] ); ?>">
							<h4>Contextual Backlinks</h4>
							<p> These are links pointing to content similar to yours.
								The goal here is to convince the owner of the backlink to update/change their link to your content.
								Often times if your content is updated or better, people are willing to change their links.
							</p>
						</div>
						<div class="col-sm-4">
							<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $modules[ 'relevant-contacts' ][ 'icon' ] ); ?>">
							<h4>Relevant Contacts</h4>
							<p> Make sure your content reaches the right people.
								People who have similar interests to your content are much more likely to consume and share it.
							</p>
						</div>
					</div>
				</div>

				<div class="content-container-state content-opportunity-list" style="display:none;">
					<h2>Opportunity Selection</h2>
					<p>Select an opportunity to start sending out emails. Be sure to personalize the email you’re sending. You have a much higher chance of reply if you personalize. Create and modify email templates <a href="<?php echo esc_attr( $email_templates_url ); ?>">here</a> to streamline the process.</p>
					<div class="content-outreach-method hidden" data-method="broken-links">
						<h2>Broken Link Building</h2>
						<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $modules[ 'broken-links' ][ 'icon' ] ); ?>">						
						<p> We find websites with broken links related to your keyword. 
							If you provide similar and good content related to the keyword, webmasters are usually happy to add your link to the mix. 
							Purpose of this tool is to create a win-win situation for you and the webmaster you’re helping.  
							Having a good context to reach someone raises the chance of you getting linked.
						</p>
					</div>
					<div class="content-outreach-method hidden" data-method="contextual-content">
						<h2>Contextual Backlinks</h2>
						<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $modules[ 'contextual-content' ][ 'icon' ] ); ?>">						
						<p> We find websites that link to your competitors. 
							Ask those websites to checkout your content.  
							If they like it, there’s a chance they’ll link to it from the same page or in a new blog entry.  
							If the site is membership based, you can also sign up for it and create your own link too. 
							The purpose of this tool is to cover your link building base with your competitor’s link profile.
						</p>
					</div>
					<div class="content-outreach-method hidden" data-method="relevant-contacts">
						<h2>Relevant Contact Outreach</h2>
						<img class="icon" height="64px" width="64px" src="<?php echo esc_attr( $plugin_dir ); ?>/images/<?php echo esc_attr( $modules[ 'relevant-contacts' ][ 'icon' ] ); ?>">						
						<p> We find contact who have talked about your topic so you can reach out to them.  
							This allows you to start building relationships with people.  
							This can be a way to start creating a buzz for your product or your service.  
							The purpose of this tool is to help build a social following.
						</p>
					</div>
				</div>

				<div class="content-container-state content-outreach" style="display:none;">

					<div class="col-sm-3 col-opportunity-details">
						<h4><span>Outreach Details</span></h4>						
						<div class="outreach-details-container"></div>
					</div>

					<div class="col-sm-9 col-outreach">

						<section class="opportunity-email-container">
							<header class="email-header">
								<div class="title">
									<h3><span>Outreach Email</span></h3>
								</div>
								<div class="email-templates-container">
									<span>
										<span>Template</span>
										<select id="active-email-template">
											<option>Select a Template</option>
											<?php foreach ($email_templates as $template): ?>
												<option value="<?php echo esc_attr( $template['id'] ); ?>" data-template-method="<?php echo esc_attr( $template['method'] ); ?>" data-template-subject="<?php echo esc_attr( $template[ 'subject' ] ); ?>"><?php echo esc_html( $template['name'] ); ?></option>
											<?php endforeach; ?>
										</select>
									</span>
								</div>
							</header>

							<div class="email-templates hidden">
								<?php foreach ($email_templates as $template): ?>
									<script type="x-handlebars-template" id="email-<?php echo esc_attr( $template['id'] ); ?>-template">
										<?php echo $template['body']; ?>
									</script>
								<?php endforeach; ?>
							</div>

							<form action="" method="" id="opportunity-email">
								<div class="form-group">
									<div class="input-group">
										<input type="text" class="form-control" class="email-to" name="email-to" placeholder="To (comma seperated)">
										<span class="input-group-btn">
											<button class="btn btn-default" type="button" data-toggle="modal" data-target="#email-finder-modal">Email Finder</button>
										</span>
									</div>
								</div>
								<div class="form-group">
									<input type="text" class="form-control" class="email-subject" name="email-subject" placeholder="Subject">
								</div>
								<div class="form-group">
									<?php
									wp_editor( '', 'email-body-editor', array(
										'editor_height' => 300,
										'tinymce' => array(
											'resize' => false,
											'wpautop' => false,
											'wp_autoresize_on' => true,
											'add_unload_trigger' => false,
										)));
									?>
								</div>
								<p class="error bg-danger"><span class="error-message"></span><span class="dismiss-error glyphicon glyphicon-remove-circle"></span></p>
								<p class="success bg-success">Your email has been sent!</p>
								<div class="form-group" style="text-align: right;">
									<button class="btn btn-default draft-email update-opportunity" type="button">Draft
										<div class="progress hidden">
							                <div class="indeterminate"></div>
							            </div>
									</button>
									<button class="btn btn-primary send-email" type="button">Send
										<div class="progress hidden">
							                <div class="indeterminate"></div>
							            </div>
									</button>
								</div>
							</form>
						</section>

						<section class="opportunity-notes-container">
							<h3>Notes</h3>
							<form action="" method="" id="opportunity-notes">
								<div class="form-group">
									<textarea type="text" class="form-control" class="opportunity-notes" name="notes" placeholder="Notes"></textarea>
								</div>
								<div class="form-group" style="text-align: right;">
									<button class="btn btn-primary update-opportunity" type="button">Save
										<div class="progress hidden">
							                <div class="indeterminate"></div>
							            </div>
									</button>
								</div>
							</form>
						</section>

					<div>
				
				</div>
				<?php
				/**
				 * End of Content Container
				 */
				?>
			</div>
		</main>
	</div>
</div>

<div class="modal" id="email-finder-modal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Email Finder</h4>
			</div>
			<div class="modal-body">
				<p>Currently, you can only find email addresses for a given domain. This is useful if you know the person has a website and would like a quick method of grabbing an email. We run a `whois` query and also try to find an email within their site.</p>
				<div class="form-group">
					<div class="input-group">
						<input class="form-control" type="text" name="email-finder-input" placeholder="Domain name">
						<span class="input-group-btn">
							<button class="btn btn-primary" id="email-finder-submit">Find
								<div class="progress hidden">
					                <div class="indeterminate"></div>
					            </div>
							</button>
						</span>
					</div>
				</div>
				<div id="email-finder-results"></div>
			</div>
		</div>
	</div>
</div>