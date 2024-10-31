<?php
$plugin_dir = plugins_url( '', dirname(__FILE__) );

wp_enqueue_script( 'outreach-email-templates-page', $plugin_dir . '/javascript/outreach-email-templates-page.js', array( 'jquery' ), ReachSEO_Plugin::VERSION, true );
wp_enqueue_style( 'outreach-email-templates-page', $plugin_dir . '/css/compiled/outreach-email-templates-page.css', array(), ReachSEO_Plugin::VERSION );

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	$actions = $_POST[ 'email_actions' ];
	$names = $_POST[ 'email_names' ];
	$ids = $_POST[ 'email_ids' ];
	$methods = $_POST[ 'email_methods' ];
	$subjects = $_POST[ 'email_subjects' ];
	/**
	 * NOTE: we clean the input within the model itself (see ReachSEO_Email_Template::sanitize) before
	 * storing in the db. This happens on both insert and update.
	 */
	foreach ($actions as $index => $action) {
		switch( $action ) {
			case 'insert':
				ReachSEO_Email_Template::insert( array(
					'name' => $names[ $index ],
					'body' => $_POST[ 'email-body' ],
					'subject' => $subjects[ $index ],
					'method' => $methods[ $index ]
				));
				break;
			case 'delete':
				ReachSEO_Email_Template::delete( $ids[ $index ] );
				break;
			case 'update':
				ReachSEO_Email_Template::update( array(
					'id' => $ids[ $index ],
					'name' => $names[ $index ],
					'body' => $_POST[ 'email-bodies-'.$ids[ $index ] ],
					'subject' => $subjects[ $index ],
					'method' => $methods[ $index ]
				));
				break;
		}
	}
}

$existing_templates = ReachSEO_Email_Template::find_all();
?>

<form class="email-templates-form container-fluid" action="" method="POST">
	<div class="row">
		<div class="col-sm-9">
			<h1>Outreach Email Templates</h1>
			<p>
				Outreach email templates give you the ability to quickly write emails for many outreach opportunities. 
				We highly recommend you to use this as a starting point for your outreach emails and then personalize accordingly.
			</p>
			<p>
				For maximum flexibility, we allow you to create <b><a href="http://handlebarsjs.com/" target="_blank">Handlebars</a></b> templates. Each outreach method has a different set of variables you can
				use to further customize your emails. These are found in the <i>Template Variables</i> dropdowns.
			</p>
			<hr>
			<div class="email-templates form-horizontal">
				<div class="form-group">
					<div class="col-sm-12">
						<div class="add-new-template">
							Add Template <span class="glyphicon glyphicon-plus"></span>
						</div>
					</div>
				</div>
					
				<div class="email-template new-email-template hidden">
					<input name="email_ids[]" type="hidden" value="-1">
					<input name="email_actions[]" type="hidden" value="">
					<div class="form-group">
						<label class="col-md-3 control-label">Name</label>
						<div class="col-md-4">
							<input name="email_names[]" class="email-template-title form-control" value="">
						</div>
						<label class="col-md-3 control-label">Outreach Campaign</label>
						<div class="col-md-2">
							<select name="email_methods[]" class="email-template-method form-control">
								<?php do_action( 'reachseo-outreach-method-option', '' ); ?>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">Email Subject</label>	
						<div class="col-sm-9">
							<input name="email_subjects[]" class="email-template-subject form-control" value="">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">Email Template</label>	
						<div class="col-sm-9">
							<?php
							wp_editor( $template['body'], 'email-body', array(
								'editor_height' => 300,
								'tinymce' => array(
									'resize' => false,
									'wpautop' => false,
									'wp_autoresize_on' => true,
									'add_unload_trigger' => false,
								)));
							?>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-12" style="text-align: right;">
							<button class="btn btn-xs btn-primary update-template">Submit</button>
						</div>
					</div>
				</div>

				<?php foreach ($existing_templates as $template): ?>
					<div class="email-template">
						<input name="email_ids[]" type="hidden" value="<?php echo esc_attr( $template[ 'id' ] ); ?>">
						<input name="email_actions[]" type="hidden" value="update">
						<div class="form-group">					
							<label class="col-sm-3 control-label">Name</label>
							<div class="col-sm-4">
								<input name="email_names[]" class="email-template-title form-control" value="<?php echo esc_attr( $template[ 'name' ] ); ?>">
							</div>
							<label class="col-sm-3 control-label">Outreach Campaign</label>
							<div class="col-sm-2">
								<select name="email_methods[]" class="email-template-method form-control">
									<?php do_action( 'reachseo-outreach-method-option', $template[ 'method' ] ); ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Email Subject</label>	
							<div class="col-sm-9">
								<input name="email_subjects[]" class="email-template-subject form-control" value="<?php echo esc_attr( $template[ 'subject' ] ); ?>">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Email Template</label>
							<div class="col-sm-9">
								<?php
								wp_editor( $template[ 'body' ], 'email-bodies-'.$template[ 'id' ], array(
									'editor_height' => 300,
									'tinymce' => array(
										'resize' => false,
										'wpautop' => false,
										'wp_autoresize_on' => true,
										'add_unload_trigger' => false,
									)));
								?>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-12" style="text-align: right;">
								<button class="btn btn-xs btn-primary update-template">Update</button>
								<button class="btn btn-xs btn-danger delete-template" type="button">Delete</button>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

		</div>
		<div class="col-sm-3">
			<h5>Template Variables</h5>
			<div class="panel-group" id="outreach-method-variables" role="tablist" aria-multiselectable="true">
				<?php do_action( 'reachseo-outreach-method-variables' ); ?>
			</div>
			<h5>Additional Variables <small>available to all outreach campaigns</small></h5>
			<ul class="list-unstyled">
				<li>
					<span class="label label-default">keyword</span>: The keyword used to find this opportunity
				</li>
				<li>
					<span class="label label-default">wp_user.display_name</span>: Logged in user's display name
				</li>
				<li>
					<span class="label label-default">wp_user.first_name</span>: Logged in user's first name
				</li>
				<li>
					<span class="label label-default">wp_user.last_name</span>: Logged in user's last name
				</li>
				<li>
					<span class="label label-default">wp_user.email</span>: Logged in user's email
				</li>
				<li>
					<span class="label label-default">wp_post.featured_image</span>: The url to the featured image of the post associated this opportunity
				</li>
				<li>
					<span class="label label-default">wp_post.title</span>: The title of the post associated this opportunity
				</li>
				<li>
					<span class="label label-default">wp_post.url</span>: The url of the post associated this opportunity
				</li>
			</ul>
			</div>
		</div>
	</div>
</form>