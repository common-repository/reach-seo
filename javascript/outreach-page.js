(function( $, ReachSEO ) {	
	/**
	 *
	 * Handlebars Helpers
	 * 
	 */
	Handlebars.registerHelper( 'if_eq', function(a, b, opts) {
	    if( a == b )
	        return opts.fn(this);
	    else
	        return opts.inverse(this);
	});
	Handlebars.registerHelper( 'sum_shares', function(a, opts) {
	    var sum = 0;
	    for (var property in a) {
		    if (a.hasOwnProperty(property)) {
		        sum+= a[ property ];
		    }
		}
		return sum;
	});	

	/**
	 *
	 * App State/Logic
	 * 
	 */
	function App() {
		this.campaigns = ReachSEO.campaigns;
		this.active_campaign = ReachSEO.active_campaign;

		this.$nav_container = $( '.nav-inner' );
		this.$content_container = $( '.content-container' );

		this.$opportunities_list = $( 'ul#outreach-opportunity-list' );		
		this.$opportunity_content = $( '.outreach-details-container' );
		this.$opportunity_email_container = $( 'section.opportunity-email-container' );
		this.$opportunity_notes_container = $( 'section.opportunity-notes-container' );		
		this.$active_campaign_title = $( '#active-campaign' );
		this.$email_error = $( 'section.opportunity-email-container .error' );
	}

	App.prototype.opportunities_list_item_template = Handlebars.compile( $( 'script#opportunities-list-item-template' ).html() );
	App.prototype.opportunity_content_template = Handlebars.compile( $( 'script#opportunity-content-template' ).html() );

	App.prototype.setActiveCampaign = function( campaign_id ) {		
		// 1. Find the campaign
		for (var i = this.campaigns.length - 1; i >= 0; i--) {
			if ( this.campaigns[i].post_id == campaign_id ) {
				// 2. Set the active campaign
				this.active_campaign = this.campaigns[i];
				// 3. update the title in heading
				var title = $( '[data-campaign-id='+ campaign_id +']' ).attr( 'data-campaign-title' );
				this.$active_campaign_title.html( "for post: "+title );
				// 4. update the opportunity counts on the outreach campaign selection page
				var counts = {
					'broken-links': 0,
					'relevant-contacts': 0,
					'contextual-content': 0
				};
				for (var i = 0; i < this.active_campaign.reports.length; i++) {
					var type = this.active_campaign.reports[i].type;
					var count = this.active_campaign.reports[i].opportunities.length;
					var previous_count = counts[ type ] ? counts[ type ] : 0;
					counts[ type ] = previous_count + count;
				};
				for (method in counts) {
					$( 'ul.outreach-methods li[data-outreach-method="'+ method +'"]' ).find( '.count' ).html( counts[ method ] );
				}
				break;
			}
		};
	};

	App.prototype.setActiveOutreachMethod = function( method, title, $img ) {
		this.active_outreach_method = method;
		// 1. Clear the existing opportunity list
		this.$opportunities_list.html( '' );
		// 2. Update with opportunities from this outreach campaign
		for (var j = 0; j < this.active_campaign.reports.length; j++) {
			var report = this.active_campaign.reports[j];
			if ( report.type == method ) {
				var $li = $( this.opportunities_list_item_template( report ) );
				this.$opportunities_list.append( $li );
			}
		}
		this.$opportunities_list.scrollTop( 0 );
		// 3. activate the sticky headers ;)
		$('#outreach-opportunity-list-container').stickySectionHeaders({
			stickyClass     : 'sticky',
			headlineSelector: '.report-header'
		});
		// 4. Update the navigation header info (to show active outreach campaign)
		$( '.nav-outreach .outreach-method-title' ).html( title );
		$( '.nav-outreach .outreach-method-icon' ).html( $img );
		// 5. Update which content pane is visible
		$( '.content-opportunity-list .content-outreach-method' ).addClass( 'hidden' );
		$( '.content-opportunity-list .content-outreach-method[data-method="'+method+'"]' ).removeClass( 'hidden' );
	};

	App.prototype.setActiveOpportunity = function( opportunity_id ) {
		this.clearActiveOpportunityContent();
		// 1. Find the opportunity within the active campaign
		for (var i = 0; i < this.active_campaign.reports.length; i++) {
			var report = this.active_campaign.reports[i];
			for (var j = 0; j < report.opportunities.length; j++) {
				var opportunity = report.opportunities[j];
				if ( opportunity.id == opportunity_id ) {
					this.active_opportunity = opportunity;
					this.active_opportunity.data.keyword = report.keyword;
					// 1. Fill the opportunity content pane
					var $content = this.opportunity_content_template( opportunity );
					this.$opportunity_content.html( $content );
					// 2. Populate the email outreach section
					$( 'select#active-email-template option' ).hide();
					var $options = $( 'select#active-email-template option[data-template-method="'+this.active_opportunity.type+'"]').show();
					if ( opportunity.email_body ) {
						// 2a. There is already an email body for this opportunity
						this.setEmailEditorContent( opportunity.email_body );
					} else {
						// 2b. No email body, so use the first template of this outreach campaign (hide other templates)						
						if ( $options.length ) {
							var template_id = $options.first().val();
							$options.first().prop( 'selected', 'selected' );
							this.renderEmailTemplate( template_id );
						}
					}
					var subject = $options.first().attr( 'data-template-subject' );
					if ( opportunity.email_subject ) {
						subject = opportunity.email_subject;
					}
					this.$opportunity_email_container.find( 'input[name="email-subject"]' ).val( subject );
					this.$opportunity_email_container.find( 'input[name="email-to"]' ).val( opportunity.email_to );
					
					// 3. Fill the notes
					this.$opportunity_notes_container.find( 'textarea[name="notes"]' ).val( opportunity.notes );
					// 4. Show email sent if previously sent
					if ( parseInt(opportunity.email_sent) > 0 )
						this.showEmailSuccess();
					// 5. Finally, lets show the opportunity view and mark li as active (hide li from list and show in opp view)
					this.setContentState( 'outreach' );
					var $li = this.$opportunities_list.find( '.outreach-opportunity[data-id='+opportunity_id+']' );
					// 5a. animate li to the right
					$li.addClass( 'active' );
					var $li_clone = $li.clone();
					// 5b. animate outreach details in
					this.$opportunity_content.find( '.outreach-li-header' ).html( $li_clone );
					var self = this;
					setTimeout( function() {
						// Had to wait for dom to render
						self.$opportunity_content.find( '.outreach-details' ).addClass( 'slide-in' );
					}, 50 );
					// 6. Now we auto fetch potential emails
					if ( opportunity.data.potential_emails === undefined ) {
						self.fetchPotentialEmailsForActiveOpportunity();
					}
					break;
				}
			};
		};
	};

	App.prototype.fetchPotentialEmailsForActiveOpportunity = function() {
		// We are making sure that when we get the results, the user is still on the same opportunity.
		var active_opportunity_id = this.active_opportunity.id;
		var self = this;
		$.ajax({
			type: 'GET',
			url: '/wp-json/reachseo/opportunity/emails?id=' + this.active_opportunity.id + '&nonce=' + ReachSEO.nonce,
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ReachSEO.nonce );
	       	},
			success: function( response ) {
				if ( self.active_opportunity.id == active_opportunity_id ) {
					var $results = self.$opportunity_content.find( '.email-results' );
					if ( response && response.emails != undefined ) {
						self.active_opportunity.data.potential_emails = response.emails;
						if ( response.emails.length == 0 ) {
							$results.html( '<p>No emails found</p>' );
						} else {
							var results = '<ul>';
							for (var i = 0; i < response.emails.length; i++) {
								results += '<li>'+response.emails[i].email+'<button class="use-email btn btn-xs" data-email="'+response.emails[i].email+'">use</button></li>';
							}
							results += '</ul>';
							$results.html( results );
						}
					} else {
						$results.html( '<p>Error</p>' );
					}
				}
			}
		});
	};

	App.prototype.clearActiveOpportunityContent = function() {
		this.$opportunity_content.find( '.outreach-details' ).removeClass( 'slide-in' );
		this.hideEmailError();
		this.hideEmailSuccess();
		this.setEmailEditorContent('');
		this.$opportunities_list.find( '.outreach-opportunity.active' ).removeClass( 'active' );
	};

	App.prototype.deleteActiveOpportunity = function( cb ) {
		if ( this.active_opportunity ) {
			this.$opportunity_content.find( '.delete .progress' ).removeClass( 'hidden' );
			var self = this;
			$.ajax({
				type: 'POST',
				url: '/wp-json/reachseo/opportunity/delete/' + this.active_opportunity.id,
				data: {
					nonce: ReachSEO.nonce,
					id: this.active_opportunity.id
				},
				beforeSend : function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', ReachSEO.nonce );
		       	},
				success: function( response ) {
					// Kind of tedious, but we need to remove the active_opportunity from
					// our local storage.
					for (var i = 0; i < self.active_campaign.reports.length; i++) {
						var report = self.active_campaign.reports[i];
						if ( report.report_id == self.active_opportunity.report_id ) {
							for (var j = 0; j < report.opportunities.length; j++) {
								var opportunity = report.opportunities[j];
								if ( opportunity.id == self.active_opportunity.id ) {
									// Remove opportunity from the array
									report.opportunities.splice( j, 1 );
									cb();
									break;
								}
							}
							break;
						}
					}
				}
			});
		} else {
			cb();
		}
	};

	App.prototype.sendActiveOpportunityOutreachEmail = function() {
		this.updateActiveOpportunity();  // Make sure this.active_opportunity has the latest info
		$( '.send-email' ).prop( 'disabled', true ).find( '.progress' ).removeClass( 'hidden' );
		var self = this;
		$.ajax({
			type: 'POST',
			url: '/wp-json/reachseo/opportunity/outreach',
			data: this.active_opportunity,
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ReachSEO.nonce );
	       	},
			success: function( response ) {
				if ( response.error ) {
					self.showEmailError( response.error );
				} else {
					console.log( 'Email sent to %s people.', response.recipients );
					self.showEmailSuccess();
					self.active_opportunity.email_sent = 1;
					$( '.outreach-opportunity.active' ).addClass( 'complete' );
					self.advanceToNextOpportunity();
				}
			},
			complete: function() {
				$( '.send-email' ).prop( 'disabled', false ).find( '.progress' ).addClass( 'hidden' );
			}
		});
	};

	App.prototype.updateActiveOpportunity = function( cb ) {
		// 1. Grab any email info that has changed
		this.active_opportunity.email_to = this.$opportunity_email_container.find( 'input[name="email-to"]' ).val();
		this.active_opportunity.email_subject = this.$opportunity_email_container.find( 'input[name="email-subject"]' ).val();
		// 1b. If the editor is active we want to grab the content from the editor, otherwise just grab from the textarea.
		var email_body = $( '#email-body-editor' ).val();
		var editor = tinymce.get( 'email-body-editor' );
		if ( editor != null )
			email_body = editor.getContent();
		this.active_opportunity.email_body = email_body;
		// 2. Update the notes field
		this.active_opportunity.notes = this.$opportunity_notes_container.find( 'textarea[name="notes"]' ).val();
		this.active_opportunity.nonce = ReachSEO.nonce;

		if ( cb !== undefined ) {
			$.ajax({
				type: 'POST',
				url: '/wp-json/reachseo/opportunity',
				data: this.active_opportunity,
				beforeSend : function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', ReachSEO.nonce );
		       	},
				complete: function() {
					cb();
				}
			});
		}
	};

	App.prototype.advanceToNextOpportunity = function() {
		var current_id = this.active_opportunity.id;
		// 1. Clear active (takes ~250ms for animations to complete)
		this.clearActiveOpportunityContent();
		// 2. Find the next li. This is slightly trickier than it seems because the opportunities
		// are spread out through multiple lists
		var $opportunities = this.$opportunities_list.find( '.outreach-opportunity' );
		var $current = $opportunities.filter( '[data-id="'+current_id+'"]' );
		var index = $opportunities.index( $current );
		console.log( 'index of current opp: ' + index );
		var next = $opportunities.get( index + 1 );
		if ( next ) {
			// 3. Set this as the active opportunity
			var next_id = $( next ).attr( 'data-id' );
			var self = this;
			setTimeout( function() {
				self.setActiveOpportunity( next_id );
			}, 250 );
		} else {
			this.navigateTo( 'opportunity-list' );
		}
	};

	/**
	 * Update the content pane based on the navigation state.
	 */
	App.prototype.setContentState = function( state ) {		
		// 1. Remove active from previous content container
		this.$content_container.find( '.content-container-state.active' ).removeClass( 'active' );
		// 2. Add active to the new content container
		this.$content_container.find( '.content-' + state ).addClass( 'active' );
	};

	/**
	 * Update the navigation pane to reflect given state
	 */
	App.prototype.navigateTo = function( state ) {
		// 1. Update the content pane
		this.setContentState( state );
		// 2. Update the navigation pane
		this.$nav_container[0].className = this.$nav_container[0].className.replace(/\bposition-.*?\b/g, '');
		switch( state ) {
			case 'campaigns':
				this.$nav_container.addClass( 'position-campaigns' );
				this.$active_campaign_title.html( 'ReachSEO' );
				break;
			case 'outreach-method':
				this.$nav_container.addClass( 'position-outreach-method' );
				break;
			case 'opportunity-list':
			case 'outreach':
				this.$nav_container.addClass( 'position-outreach' );
				break;
		}
	};

	App.prototype.renderEmailTemplate = function( template_id ) {
		var $template = $( 'script#email-' + template_id + '-template' );
		if ( $template.length > 0 ) {
			var template = Handlebars.compile( $template.html() );
			var template_data = this.active_opportunity.data;
			template_data.wp_post = this.active_campaign.post;
			template_data.wp_user = ReachSEO.wp_user;
			console.log( template_data );
			var rendered = template( template_data );
			this.setEmailEditorContent( rendered );
		}
	};

	App.prototype.setEmailEditorContent = function( content ) {
		if ( typeof tinymce !== 'undefined' ) {
			var editor = tinymce.get( 'email-body-editor' );
			if ( editor != null )
				editor.setContent( content );
			$( '#email-body-editor' ).html( content ).val( content );
		}
	};

	App.prototype.hideEmailError = function() {
		this.$email_error.hide();
	};
	App.prototype.showEmailError = function( error ) {
		this.hideEmailSuccess();
		this.$email_error.show().find( '.error-message' ).html( error );
	};
	App.prototype.showEmailSuccess = function() {
		this.hideEmailError();
		this.$opportunity_email_container.find( '.success' ).show();
	};
	App.prototype.hideEmailSuccess = function() {
		this.$opportunity_email_container.find( '.success' ).hide();
	};

	var app = new App();
	window.app = app;	

	/**
	 *
	 * Event Handlers
	 * 
	 */
	$( 'ul#campaigns-list li' ).click( function() {
		var target_campaign = $( this ).attr( 'data-campaign-id' );
		app.setActiveCampaign( target_campaign );
		app.navigateTo( 'outreach-method' );
	});

	$( '.nav-back' ).click( function() {
		var target = $( this ).attr( 'data-back-to' );
		app.navigateTo( target );
	});

	$( 'ul.outreach-methods li' ).click( function() {
		var method = $( this ).attr( 'data-outreach-method' );
		var title = $( this ).find( '.title' ).html();
		var $img = $( this ).find( 'img.icon' ).clone();
		app.setActiveOutreachMethod( method, title, $img );
		app.navigateTo( 'opportunity-list' );
	});

	$( 'ul#outreach-opportunity-list' ).on( 'click', '.outreach-opportunity', function() {
		var opportunity_id = $( this ).attr( 'data-id' );
		app.setActiveOpportunity( opportunity_id );
	});

	$( '.outreach-details-container' ).on( 'click', '.close-opportunity', function() {
		app.clearActiveOpportunityContent();
		app.navigateTo( 'opportunity-list' );
	});

	$( '.outreach-details-container' ).on( 'click', '.delete', function() {
		var id = app.active_opportunity.id;
		// TODO: show processing?
		app.deleteActiveOpportunity( function() {
			// 1. Lets reset all the opportunity content fields
			app.clearActiveOpportunityContent();
			// 2. Show the opportunity list again
			app.navigateTo( 'opportunity-list' );
			// 3. Remove the li from opportunity list
			setTimeout( function() {
				$( '.outreach-opportunity[data-id="'+id+'"]' ).slideUp( 250, function() {
					$( this ).remove();
				});
			}, 250 );
		});
	});

	$( '.outreach-details-container' ).on( 'click', '.use-email', function() {
		var email = $( this ).attr( 'data-email' );
		$( 'form#opportunity-email input[name="email-to"]' ).val( email );
	});

	$( '#active-email-template' ).change(function() {
		app.renderEmailTemplate( $(this).val() );
	});

	$( '.content-outreach .update-opportunity' ).click( function() {
		$( '.update-opportunity' ).prop( 'disabled', true ).find( '.progress' ).removeClass( 'hidden' );
		app.updateActiveOpportunity( function() {
			$( '.update-opportunity' ).prop( 'disabled', false ).find( '.progress' ).addClass( 'hidden' );
		});
	});

	$( '.content-outreach .send-email' ).click( function() {
		app.sendActiveOpportunityOutreachEmail();
	});

	$( '.content-outreach .dismiss-error' ).click( function() {
		app.hideEmailError();
	});

	$( '#email-finder-modal #email-finder-submit' ).click( function() {
		$( '#email-finder-modal #email-finder-results' ).html( '' );  // clear any previous results
		$( this ).addClass( 'disabled' ).prop( 'disabled', true );
		$( this ).find( '.progress' ).removeClass( 'hidden' );
		var domain = $( '#email-finder-modal input[name="email-finder-input"]' ).val();
		var self = this;
		$.ajax({
			type: 'GET',
			url: '/wp-json/reachseo/ef?domain=' + domain + '&nonce=' + ReachSEO.nonce,
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ReachSEO.nonce );
	       	},
	       	success: function( response ) {
	       		var results = '(No emails found for the following domain: ' + domain + ')';
	       		if ( response.results && response.results.length ) {
	       			results = '<h5>Emails found:</h5><ul class="list-unstyled">';
	       			for (var i = 0; i < response.results.length; i++) {
	       				results += '<li>' + response.results[i].email + '</li>';
	       			}
	       			results+= '</ul>';
	       		}
	       		$( '#email-finder-modal #email-finder-results' ).html( results );
	       	},
			complete: function() {
				$( self ).find( '.progress' ).addClass( 'hidden' );
				$( self ).removeClass( 'disabled' ).prop( 'disabled', false );
			}
		});
	});

	/**
	 * 
	 * Setup initial state
	 * 
	 */
	if ( ReachSEO.active_campaign ) {
		app.setActiveCampaign( ReachSEO.active_campaign );  // TODO: update deep linking
		app.navigateTo( 'outreach-method' );
	}
	if ( ReachSEO.active_outreach_method ) {
		console.log( $( 'ul.outreach-methods li[data-outreach-method="'+ReachSEO.active_outreach_method+'"]' ).first() );
		$( 'ul.outreach-methods li[data-outreach-method="'+ReachSEO.active_outreach_method+'"]' ).first().click();		
	}
	if ( ReachSEO.active_report ) {
		// scroll to target element
		var $target = $( '#outreach-opportunity-list .report-header[data-id="'+ReachSEO.active_report+'"]');
		var $container = $( '#outreach-opportunity-list' );
		if ( $target.length && $container.length ) {
			setTimeout( function() {
				var scroll_to = $target.offset().top - 240;
				$container.scrollTop( scroll_to ); // had to wait for things to render
			}, 250 );
		}
	}

	function updateHeights() {
		var target_height = $( window ).height() - $( '.row-content' ).offset().top;
		$( '.row-content > *' ).height( target_height );
		var target_height_lists = target_height - 56;
		$( '.row-content .list-container' ).height( target_height_lists );
	}
	updateHeights();
	function updateContainerPosition() {
		var left = $( '#wpcontent' ).css('margin-left');
		$( '#wpbody-content' ).css( 'left', left );
	}
	updateContainerPosition();
	$( window ).resize( function() {
		updateHeights();
		updateContainerPosition();
	});

})( jQuery, ReachSEO );