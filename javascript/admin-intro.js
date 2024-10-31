jQuery( function( $ ) {
	var tour = parseInt( ReachSEO_Admin.display_tour );
	var $menu_item = $( '#toplevel_page_outreach' );
	if ( !ReachSEO_Admin.wp_admin_url )
		ReachSEO_Admin.wp_admin_url = '/wp-admin/';
	
	if ( window.innerWidth > 960 ) {  // Breaking point for WordPress sidebar
		var template = function( title, inner_html, top, left, position, carrot, url, url_text ) {
			var t = '<div class="reachseo-intro-container" style="top: '+top+'px; left: '+left+'px; position: '+position+';">';
			t += '<div class="carrot '+carrot+'"></div>';
			t += '<div class="intro-header"><span class="icon dashicons-before dashicons-megaphone"></span>' + title + '<div class="intro-close">&#10005;</div></div>';
			t += '<div class="intro-body">' + inner_html + '</div>';
			t += '<div class="intro-footer"><a class="cancel-tour" href="' + ReachSEO_Admin.wp_admin_url + 'admin.php?page=outreach&reachseo-tour=end">end tutorial</a>'
			if ( url ) {
				t += '<a href="'+ url +'" class="reachseo-tour-nav">'+url_text+'</a>'
			}
			t += '</div></div>';
			return t;
		};

		switch( tour ) {
			case 1:
				// 1. Plugin just activated, show intro step to options page!
				var top = $menu_item.offset().top;  // 5px account for border
				var left = $menu_item.width();
				if ( $menu_item.hasClass('wp-menu-open') ) {
					var $menu_item_settings = $menu_item.find( 'ul > li:last' );
					top = $menu_item_settings.offset().top;
					left = $menu_item_settings.width();
				}
				var intro_title = 'Let\'s Get Started!';
				var intro_body = 'To start, we need to configure the plugin.';				
				var url =  ReachSEO_Admin.wp_admin_url + 'admin.php?page=outreach-settings';
				var intro_1_html = template( intro_title, intro_body, top, left, 'fixed', 'left', url, 'Next' );
				$( 'body' ).append( intro_1_html );
				break;
			case 2:
				// 2. We are on settings page, need to add an API key
				var $target = $( '#reachseo_settings\\[basic\\]\\[blb-api-key\\]' );
				if ( $target.offset() ) {
					var top = $target.offset().top + 45;
					var left = $target.offset().left + 15;
					var intro_title = 'Update Your API Key';
					var intro_body = 'Create an account or use an existing one to get your API key. Then, save it in your plugin settings. This is required for generating leads and outreach opportunities!';
					var intro_1_html = template( intro_title, intro_body, top, left, 'absolute', 'top' );
					$( 'body' ).append( intro_1_html );
					var scroll_top = top - window.innerHeight / 2;
					$( window ).scrollTop( scroll_top );
				}
				break;
			case 3:
				// 3. We have added an API key, prompt to add email settings
				var $target = $( '#reachseo_settings\\[basic\\]\\[reachseo-email-host\\]' );
				if ( $target.offset() ) {
					var top = $target.offset().top + 45;
					var left = $target.offset().left + 15;
					var intro_title = 'Email settings';
					var intro_body = 'By default, Reach SEO will try to send outreach emails from your server and email address. Update these settings for more control.';
					var intro_1_html = template( intro_title, intro_body, top, left, 'absolute', 'top', '#', 'Skip' );
					$( 'body' ).append( intro_1_html );
					var scroll_top = top - window.innerHeight / 2;
					$( window ).scrollTop( scroll_top );
				}
			case 4:
				// 4. We have added email settings
				// 5. We are on post edit page and need to generate our first report!
				var $target = $( '.reachseo-meta-box input.query' );
				if ( $target.offset() ) {
					var top = $target.offset().top;
					var left = $target.offset().left - 365;
					var intro_title = 'Begin Outreach!';
					var intro_body = 'This is where you generate outreach opportunities for a given post. Enter keywords related to your content, then select an outreach campaign.';
					var url = ReachSEO_Admin.wp_admin_url + 'admin.php?page=outreach';
					var intro_1_html = template( intro_title, intro_body, top, left, 'absolute', 'right', url, 'View Outreach Page!' );
					$( 'body' ).append( intro_1_html );
					var scroll_top = top - window.innerHeight / 2;
					$( window ).scrollTop( scroll_top );
					// Lets also listen for a report being generated!
					$( 'ul.outreach-reports-list' ).bind( 'DOMSubtreeModified', function() {
						// Update the tutorial box!
						console.log( 'change!' );
						var $report_li = $( 'ul.outreach-reports-list li:first' );
						$( '.reachseo-intro-container' ).animate({
							top: $report_li.offset().top + 14
						}, 350);
						$( '.reachseo-intro-container' ).fadeIn();
						$( '.reachseo-intro-container .intro-header' ).html( $( '.reachseo-intro-container .intro-header' ).html().replace( 'Begin Outreach!', 'Awesome!' ) );
						$( '.reachseo-intro-container .intro-body' ).html( 'Your report is being generated as we speak. Response times may vary between each report, but typically they are ready in under 5 minutes.<br><br>You may generate additional reports while you are waiting.' );
					});
				}
				break;
			default:
				console.log( tour );
		}

		$( 'body' ).on( 'click', '.intro-close', function() {
			$( '.reachseo-intro-container' ).fadeOut();
		});

		$( 'body' ).on( 'click', 'a.reachseo-tour-nav', function( event ) {
			var href = $( this ).attr( 'href' );
			if ( href == '#' ) {
				event.preventDefault();
				var $container = $( '.reachseo-intro-container' );
				var new_container = template( 'What next?',
					'In order to continue the tutorial, you need to edit a Post of your choice.<br><br>This should be a post that you would like to promote, improve outreach, and increase SEO rankings for.',
					$container.offset().top,
					$container.offset().left,
					'absolute',
					'hidden',
					ReachSEO_Admin.wp_admin_url + 'edit.php',
					'Find Post'
				);
				$( '.reachseo-intro-container' ).fadeOut( function() {
					$( 'body' ).append( new_container );
				});
			}
		});
	}


	// This is for hiding the header/footer within iframe on settings page!
	var iframe = document.getElementById( 'settings-iframe' );
	if ( iframe ) {
		var iframe_origin = $( iframe ).attr( 'src' );
		console.log( iframe_origin );
		$( iframe ).load( function(){			
			iframe.contentWindow.postMessage( { href: location.href, action: 'hide' }, iframe_origin );
		});
	}
});