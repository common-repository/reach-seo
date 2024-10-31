<div class="panel">
	<div class="panel-heading" id="relevant-contacts-variables-heading" role="tab" data-toggle="collapse" data-parent="#outreach-method-variables" href="#relevant-contacts-variables" aria-expanded="true" aria-controls="relevant-contacts-variables">
		<h5 class="panel-title">
			Relevant Contacts
		</h5>
	</div>
	<div id="relevant-contacts-variables" class="panel-collapse collapse" role="tabpanel" arialabelby="relevant-contacts-variables-heading">
		<div class="panel-body">
			<ul class="list-unstyled">
				<li>
					<span class="label label-default">user.name</span>: Name of the user (according to twitter).
				</li>
				<li>
					<span class="label label-default">user.screen_name</span>: Twitter user's screen name.
				</li>
				<li>
					<span class="label label-default">user.image_url</span>: Twitter user's profile picture.
				</li>
				<li>
					<span class="label label-default">user.followers</span>: Number of followers the twitter user has.
				</li>
				<li>
					<span class="label label-default">user.url</span>: The twitter user's website (if specified in their twitter account).
				</li>
				<li>
					<span class="label label-default">user.domain</span>: The twitter user's website domain (if specified in their twitter account).
				</li>
				<li>
					<span class="label label-default">potential_emails</span>: An array of potential emails gathered for this user.
				</li>
				<li>
					<span class="label label-default">potential_emails[i].email</span>: The potential email address.
				</li>
				<li>
					<span class="label label-default">potential_emails[i].method</span>: Method in which the email was found (currently `whois` and `contact-page` are only available options).
				</li>
				<li>
					<span class="label label-default">potential_emails[i].url</span>: Url in which the email was found.
				</li>
			</ul>
		</div>
	</div>
</div>
