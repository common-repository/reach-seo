(function( $, ReachSEO ) {	

	/**
	 *
	 * Handlebars Helpers
	 * 
	 */
	Handlebars.registerHelper( 'date_formatted', function(a, opts) {
		if ( a && a > 0 ) {
			return moment.unix( a ).fromNow();
		}
		return '';
	});
	Handlebars.registerHelper( 'if_report', function(status, report, opts) {
		if ( report.status == status && report.report_id ) {
			return opts.fn( this );
		}
	});

	/**
	 * 
	 * State Controller
	 * 
	 */
	function StateController() {
		this.reports = [];
		this.$outreach_method_options = $( '.stage.stage-outreach-method .outreach-method' );
	}

	StateController.prototype.codes = {
		INVALID_API_KEY: 1,
		REPORT_PENDING: 4,
		REPORT_COMPLETED: 7,
		HIT_RATE_LIMIT: 11,
		SERVER_BUSY: 12
	};

	StateController.prototype.renderExistingReports = function( existing_reports ) {
		if ( existing_reports.length ) {
			for (var i = 0; i < existing_reports.length; i++) {
				var endpoint = $( '.outreach-method-' + existing_reports[i].type ).attr( 'data-outreach-method-endpoint' );
				// 1. Create the report
				var report = new ReportController( existing_reports[i], endpoint );
				// 2. If the report is still pending, start the pinging process again
				if ( report.status == 'pending' ) {
					report.startPing();
				}
				// 3. Render the report (add to list)
				report.render();
				// 4. Last but not least, add to our stack of reports
				this.reports.push( report );
			};
		}
	};

	StateController.prototype.requestReport = function( query, method, endpoint, cb ) {
		var self = this;
		$.ajax({
			type: 'GET',
			url: endpoint + '/report/new?keyword=' + encodeURI( query ) + '&post_id=' + ReachSEO.post_id + '&nonce=' + ReachSEO.nonce,
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ReachSEO.nonce );
	       	},
			success: function( response ) {
				var new_report = null;
				switch( response.code ) {
					case StateController.prototype.codes.REPORT_PENDING:
						new_report = new ReportController( response.report, endpoint );
						new_report.render();
						new_report.startPing();
						self.reports.unshift( new_report );
						break;
					case StateController.prototype.codes.HIT_RATE_LIMIT:
						self.updateNotificationMessage( 'rate-limit' );
						break;
					case StateController.prototype.codes.SERVER_BUSY:
						self.updateNotificationMessage( 'busy' );
						break;
					case StateController.prototype.codes.INVALID_API_KEY:
						self.updateNotificationMessage( 'invalid-api-key' );
						break;
					default:
						self.updateNotificationMessage( 'error' );  // Could be network error or api server down
						break;
				}
				console.log( response );

				if ( response.errors ) {
					self.updateNotificationMessage( 'error' );  // Could be network error or api server down
				}
				cb( new_report );
			}
		});
	};

	StateController.prototype.updateNotificationMessage = function( state ) {
		var $search_input_container = $( '.search-input-container' );
		var $error = $( '.reachseo-meta-box .stage-search .error' );
		switch( state ) {
			case 'rate-limit':
				$search_input_container.hide();
				$error.html( 'Sorry, but it looks like you have hit the API limit. To increase this limit, upgrade your account at <a target="_blank" href="http://app.reachseo.io">reachseo.io</a>' ).show();
				break;
			case 'busy':
				$error.html( 'Our server is experiencing unusual amounts of traffic, and appears to be busy! Try again soon.' ).show();
				break;
			case 'error':
				$search_input_container.hide();
				$error.html( 'Internal error. Please contact <a href="mailto:info@reachseo.io">info@reachseo.io</a>.' ).show();
				break;
			case 'invalid-api-key':
				$search_input_container.hide();
				$error.html( 'Invalid API key. Please enter a valid API key from the Reach SEO settings page. If you are still having problems, contact us asap! <a href="mailto:info@reachseo.io">info@reachseo.io</a>' ).show();
				break;
			case 'success':
				$error.html('').hide();
				$search_input_container.show();
				break;
			default:
				break;
		}
	};

	StateController.prototype.setActiveOutreachMethod = function( method ) {
		this.$outreach_method_options.removeClass( 'active' );
		this.$outreach_method_options
			.filter( '.outreach-method-' + method )
			.addClass( 'active' )
			.find( '.loading' ).addClass( 'active' );
	};

	StateController.prototype.clearActiveOutreachMethod = function() {
		this.$outreach_method_options.addClass( 'active' ).find( '.loading' ).removeClass( 'active' );
	};


	/**
	 * 
	 * Report Controller.
	 *
	 * Handles requesting of an actual report, pinging the server
	 * for report status, report list item state, etc..
	 * 
	 */
	function ReportController( report, endpoint ) {
		for ( var property in report ) {
		    if ( report.hasOwnProperty( property ) ) {
		        this[ property ] = report[ property ];
		    }
		}
		this.endpoint = endpoint;
	}	

	ReportController.prototype.template = Handlebars.compile( $( 'script#outreach-report-li-template' ).html() );	
	
	/**
	 * Render a report li. This method renders the template AND adds it to the existing list of reports.
	 */
	ReportController.prototype.render = function() {
		// We need to pass the outreach campaign icon and whether or not this report is pending to the handlebars template
		this.pending = this.status == 'pending' ? true : undefined;
		this.failed = this.status == 'failed' ? true : undefined;
		this.icon = ReachSEO.modules[ this.type ].icon;
		var template = ReportController.prototype.template( this );
		this.$li = $( template );
		$( 'ul.outreach-reports-list' ).prepend( this.$li );
	};

	ReportController.prototype.setState = function( state ) {
		switch( state ) {
			case 'complete':
				this.$li.find( '.loading' ).removeClass( 'active' );
				break;
			case 'failed':
				this.$li.find( '.loading' ).removeClass( 'active' );
				this.$li.find( '.date' ).html( '<span style="font-style:italic;color:red;">failed</span>' );
				break;
			case 'pending':
				this.$li.find( '.loading' ).addClass( 'active' );
				break;
		}
	};

	ReportController.prototype.startPing = function() {
		var self = this;
		this.ping_interval = setInterval( function() {
			self.ping();
		}, 8000 );
	};

	ReportController.prototype.ping = function() {
		var self = this;
		$.ajax({
			type: 'GET',
			url: this.endpoint + '/report?report_id=' + this.report_id + '&post_id=' + this.post_id + '&nonce=' + ReachSEO.nonce,
			beforeSend : function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', ReachSEO.nonce );
	       	},
			success: function( response ) {
				switch( response.status ) {
					case 'failed':
						self.setState( 'failed' );
						clearInterval( self.ping_interval );
						break;
					case 'complete':
						// Update this reports's data
						for ( var property in response ) {
						    if ( response.hasOwnProperty( property ) ) {
						        self[ property ] = response[ property ];
						    }
						}
						// Update the date_completed field!
						self.$li.find( '.date' ).html( 'just finished' );
						self.$li.wrapInner( '<a href="'+ReachSEO.outreach_page_url+'&report_id='+self.report_id+'"></a>' );
						clearInterval( self.ping_interval );
						self.setState( 'complete' );
						break;
					default:
						self.setState( 'pending' );
						break;  // pending
				}
			},
			error: function() {
				self.setState( 'failed' );
				clearInterval( self.ping_interval );
			}
		});
	};


	/**
	 * 
	 * Local vars
	 * 
	 */	
	var $query_input = $( '.stage.stage-search input.query' );
	var state_controller = new StateController();

	/**
	 * 
	 * Setup/Initialization
	 * 
	 */
	state_controller.renderExistingReports( ReachSEO.reports );

	if ( ReachSEO.reports.length === 0 ) {
		// Try to auto-fill the input text based on post title OR yoast focus keyword!
		var yoast = $( '#yoast_wpseo_focuskw_text_input' ).val();
		var post_title = $( '#title' ).val();
		if ( yoast ) {
			$query_input.val( yoast );
		} else {
			$query_input.val( post_title );
		}
	}

	/**
	 * 
	 * Events
	 * 
	 */
	$( window ).keydown( function( event ) {
		if( event.keyCode == 13 ) {
			if ( $query_input.is( ':focus' ) ) {
				event.preventDefault();
				$( '.stage' ).toggleClass( 'active' );
				return false;
			}
		}
	});

	$( '.next-stage' ).click( function() {
		$( '.stage' ).toggleClass( 'active' );
	});

	$( '.back-to-default-stage' ).click( function( event ) {
		event.preventDefault();
		$( '.stage.stage-search' ).addClass( 'active' );
		$( '.stage.stage-outreach-method' ).removeClass( 'active' );
	});

	state_controller.$outreach_method_options.click( function() {			
		// 1. Gather data
		var query = $query_input.val();
		var method = $( this ).attr( 'data-outreach-method' );
		var endpoint = $( this ).attr( 'data-outreach-method-endpoint' );
		// 2. Update UI
		state_controller.setActiveOutreachMethod( method );
		setTimeout( function() {  // TODO: remove
		// 3. Request Report
		state_controller.requestReport( query, method, endpoint, function( new_report ) {
			// 4. Transition back to default stage (everything else is handled in requestReport)
			$( '.stage.stage-search' ).addClass( 'active' );
			$( '.stage.stage-outreach-method' ).removeClass( 'active' );
			
			// 5. create the clone that will 'add to list'
			if ( new_report != null ) {
				var $active_method = state_controller.$outreach_method_options.filter( '.active' );
				var $clone = $active_method.find( 'img' ).clone();
				$( '.reachseo-meta-box' ).append( $clone );
				var current_position = $active_method.position();
				var target_position = new_report.$li.position();
				state_controller.$outreach_method_options.hide();  // hide them until animation complete
				new_report.$li.css( 'opacity', 0 );  // hide until animation complete
				$clone.css({
					'position': 'absolute',
					'top': current_position.top + 'px',
					'left': current_position.left + 'px'
				});
				$clone.animate({
					'top': target_position.top + 'px',
					'left': target_position.left + 'px'
				}, 500, function() {
					new_report.$li.css( 'opacity', 1 );  // hide until animation complete
					state_controller.$outreach_method_options.show();
					state_controller.clearActiveOutreachMethod();
					$clone.remove();
				});
			} else {
				state_controller.clearActiveOutreachMethod();	
			}
		});
		}, 2000);
	});


})( jQuery, ReachSEO );