jQuery( document ).ready(
	function ($) {

		var checkIndexingIntervalID;

		function expertrec_is_indexing() {
			jQuery( "#indexing-message" ).text(
				"Indexing is in progress. Check your website now." +
				" Partial search results are being displayed."
			)
			jQuery( "#exp-reindex-loading" ).css( 'display', 'block' )
			jQuery( "#expertrec-reindex-btn" ).hide()
			jQuery( "#expertrec-stop-indexing-btn" ).show()
		}

		function expertrec_done_indexing() {
			jQuery( "#exp-reindex-loading" ).css( 'display', 'none' )
			jQuery( "#expertrec-stop-indexing-btn" ).hide()
			jQuery( "#expertrec-reindex-btn" ).show()
			jQuery( "#indexing-message" ).text( "" )
		}

		if (window.location.href.indexOf( "?page=Expertrec" ) > -1) {
			// Login/Home Page loaded
			jQuery( "#existing_ECOM_id_continue" ).click(
				function (event) {
					var secret_key = $( "#existing_secret_id" ).val()
					console.debug( "secret key ---->", secret_key )
					var org_id     = $( "#existing_org_id" ).val()
					var post_data  = {}
					post_data      = {
						action: 'expertrec_login_response',
						site_id: org_id,
						ecom_id: org_id,
						cse_id: null,
						write_api_key: secret_key,
						expertrec_engine: "db"
					};
						// valid data to send to plugin.
						console.log( "Sending to plugin backend ", post_data )
						$.post(
							the_ajax_script.ajaxurl,
							post_data,
							function (response) {
								// our plugin response.
								console.debug( response )
								location.reload();

							}
						);
				}
			)
			// jQuery("#exp-settings-loading").css('display', 'block')
			// jQuery('#existing_ecom_id_continue').hide()
			// var org_id = $("#existing_org_id1").val()
			// console.debug("org id ---->", org_id)
			// var base_url = "https://cseb.expertrec.com/api/7e70731cfb3a6fc453847f952906c82c";
			// var url = base_url + "/organisation/"+ org_id +"/mid_status_type"
			// console.log("Taking status from ", url)
			// $.get(url,
			// function(data, status){
			// console.log(data)
			// var post_data = {}
			// if(data.service.includes("ECOM")) {

			// }
			// if (post_data) {
			// valid data to send to plugin.
			// console.log("Sending to plugin backend ", post_data)
			// $.post(the_ajax_script.ajaxurl, post_data, function (response) {
			// jQuery("#exp-settings-loading").css('display', 'none')
			// jQuery('#existing_ecom_id_continue').show()
			// our plugin response.
			// console.debug(response)
			// location.reload();

			// });
			// }
			// }
			// }

			jQuery( "#existing_org_id_continue" ).click(
				function (event) {
					// jQuery("#exp-settings-loading").css('display', 'block')
					// jQuery('#existing_org_id_continue').hide()
					var org_id   = $( "#existing_org_id" ).val()
					console.debug( "org id ---->", org_id )
					var base_url = "https://cseb.expertrec.com/api/7e70731cfb3a6fc453847f952906c82c";
					var url      = base_url + "/organisation/" + org_id + "/mid_status_type"
					console.log( "Taking status from ", url )
					$.get(
						url,
						function (data, status) {
							console.log( data )
							var post_data = {}
							// decide service
							if (data.service.includes( "CSE" )) {
								// call backend
								post_data = {
									action: 'expertrec_login_response',
									site_id: org_id,
									ecom_id: null,
									cse_id: org_id,
									write_api_key: null,
									expertrec_engine: "crawl" // 1. check this value.
								};
								if (post_data) {
									// valid data to send to plugin.
									console.log( "Sending to plugin backend ", post_data )
									$.post(
										the_ajax_script.ajaxurl,
										post_data,
										function (response) {
											jQuery( "#exp-settings-loading" ).css( 'display', 'none' )
											jQuery( '#existing_org_id_continue' ).show()
											jQuery( '#existing_ECOM_id_continue' ).hide()
											// our plugin response.
											console.debug( response )
											location.reload();
										}
									);
								}
							} else if (data.service.includes( "ECOM" )) {
								// we should display a new input box to get writekey from client.
								jQuery( '#existing_org_id_continue' ).hide()
								jQuery( '#ecomblock' ).show();
								jQuery( '#apiKeyHelp' ).hide();
								jQuery( '#existing_ECOM_id_continue' ).show()
								// link will be kept next to inputbox for helping client.
								// currently this is not implemented.
								console.log( "Add support for this service" )
								// show the next box and a new submit button.
								return;
							} else {

							}

						}
					)
				}
			);

			if ($( '#open-child-window' ).length) {
				// Login Page
				console.debug( 'Login Page Loaded.' )
				// variable that holds the handle of the child
				let child_window_handle = null;

				// on opening child window
				document.querySelector( "#open-child-window" ).addEventListener(
					'click',
					function () {
						child_window_handle = window.open( 'https://wordpress.expertrec.com/createorg.html', '_blank' );
						var data            = {
							action: 'expertrec_signup_clicked',
							site_url: jQuery( "input[name='crawl_site_url']:input" ).val(),
							expertrec_engine : jQuery( "input[name='indexingWay']:checked" ).val(),
						};
						$.post( the_ajax_script.ajaxurl, data );
					}
				);

				// event handler will listen for messages from the child
				window.addEventListener(
					'message',
					function (e) {
						// e.data hold the message from child
						const message = e.data
						console.debug( "Parent received this message: ", message );
						if (message === 'send_data') {
							sendDataToChild();
						} else if (message && message instanceof Object && 'final_data' in message) {
							console.debug( 'final data from the child is ', message )
							console.debug( 'type is ', typeof (message) )
							// construct data to send back to plugin backend
							var data = {
								action: 'expertrec_login_response',
								site_id: message.final_data.site_id,
								ecom_id: message.final_data.ecom_id,
								cse_id: message.final_data.cse_id,
								write_api_key: message.final_data.write_api_key,
								expertrec_engine: message.final_data.expertrec_engine
							};
							// the_ajax_script.ajaxurl is a variable that will contain the url to the ajax processing file
							$.post(
								the_ajax_script.ajaxurl,
								data,
								function (response) {
									console.debug( response );
									child_window_handle.close()
									location.reload();
								}
							);
						}
					},
					false
				);

				function sendDataToChild() {
						var d = {
							action: 'expertrec_get_site_info'
					}
						$.post(
							the_ajax_script.ajaxurl,
							d,
							function (response) {
								console.debug( "last sync: ", response )
								var resp                 = response
								console.debug( "Site info for login: ", resp )
								resp["site_url"]         = jQuery( "input[name='crawl_site_url']:input" ).val()
								resp["expertrec_engine"] = jQuery( "input[name='indexingWay']:checked" ).val()

								console.debug( "Sending Data: ", resp )
								// this will post a message to the child
								child_window_handle.postMessage( resp, "*" );
							}
						);
				}
			} else {
				// Home Page
				console.debug( 'Home Page Loaded.' )

				var data = {
					action: 'expertrec_engine'
				}
				$.post(
					the_ajax_script.ajaxurl,
					data,
					function (response) {
						console.debug( "Expertrec engine: ", response )
						var resp = response
						if (resp == "db") {
							// If Engine = db, Load this JS

							console.debug( "Update index stats" )
							// Updating indexing progress stats on loading home page
							update_index_stats()
							jQuery( "#expertrec-reindex-btn" ).hide()
							// Update indexing progress every 2 sec, if indexing is not completed
							checkIndexingIntervalID = setInterval( check_indexing_status, 2000 );

							// When first time home page loaded, then starting re-indexing
							var data = {
								action: 'expertrec_account_created'
							}
							$.post(
								the_ajax_script.ajaxurl,
								data,
								function (response) {
									var json_data = response
									console.debug( "On loading home page: ", json_data )
									if (json_data.account_created && ! json_data.first_sync_done) {
										start_indexing();
									}
								}
							)

							// Getting last successful sync time
							var data = {
								action: 'expertrec_last_sync'
							}
							$.post(
								the_ajax_script.ajaxurl,
								data,
								function (response) {
									var response = response
									console.debug( "last sync: ", response )
									if (response == 'NA') {
										jQuery( ".expertrec-last-sync" ).hide()
									} else {
										var date = new Date( response * 1000 )
										jQuery( "#exp-sync-time" ).text( date.toLocaleString() )
									}
								}
							)

							// Re-Index Button functionality
							jQuery( "#expertrec-reindex-btn" ).click(
								function (event) {
									console.debug( "Clicked reindex button" )
									jQuery( "#exp-reindex-loading" ).css( 'display', 'block' )
									jQuery( "#expertrec-reindex-btn" ).hide()
									// First set the indexing progress to 0, then start indexing
									// reset_index_stats()
									jQuery( '.expertrec-svg-circle-product' ).animate( {'stroke-dashoffset': 386}, 1000 )
									jQuery( '.expertrec-svg-circle-post' ).animate( {'stroke-dashoffset': 386}, 1000 )
									jQuery( '.expertrec-svg-circle-page' ).animate( {'stroke-dashoffset': 386}, 1000 )
									jQuery( '.expertrec-svg-circle-other' ).animate( {'stroke-dashoffset': 386}, 1000 )
									start_indexing()
								}
							);

							// Stop-Indexing Button functionality
							jQuery( "#expertrec-stop-indexing-btn" ).click(
								function (event) {
									console.debug( "Clicked stop indexing button." )
									const StopIndexingConfirmMessage = "Are you sure you want to stop the Indexing? Reindexing will start the processing from the beginning. Click OK to Confirm."
									if (confirm( StopIndexingConfirmMessage )) {
										jQuery( "#expertrec-stop-indexing-btn" ).hide()
										var data = {
											action: 'expertrec_stop_indexing'
										}
										$.post(
											the_ajax_script.ajaxurl,
											data,
											function (response) {
												console.debug( "Stop indexing response", response )
											}
										)
									}
								}
							)
						} else {
							// If Engine = crawl, then load this JS

							// Getting page crawl status
							var data = {
								action: 'expertrec_crawl',
								func_to_call: 'crawl_status'
							}
							$.post(
								the_ajax_script.ajaxurl,
								data,
								function (response) {
									console.debug( "Crawl Status: ", response )
									var crawl_input = response
									if (typeof crawl_input === 'string' || crawl_input instanceof String) {
										crawl_input = JSON.parse( crawl_input );
									}
									var status = crawl_input.crawl_status
									if (status == "") {
										status = "NA"
									}
									var pages_crawled = crawl_input.pages_crawled
									jQuery( '#exp-pages-crawled' ).text( pages_crawled )
									jQuery( '#exp-crawl-status' ).text( status )
								}
							)

							// Re-crawl button
							jQuery( "#expertrec-recrawl-btn" ).click(
								function (event) {
									console.debug( "Re-Crawl btn clicked" )
									jQuery( "#expertrec-recrawl-btn" ).hide()
									jQuery( "#expertrec-stop-crawl-btn" ).show()
									var data = {
										action: 'expertrec_crawl',
										func_to_call: 'start_crawl'
									}
									$.post(
										the_ajax_script.ajaxurl,
										data,
										function (response) {
											console.debug( 'Re-Crawl response: ', response )
											jQuery( "#expertrec-stop-crawl-btn" ).hide()
											jQuery( "#expertrec-recrawl-btn" ).show()
										}
									)
								}
							)

							// Stop crawl button
							jQuery( "#expertrec-stop-crawl-btn" ).click(
								function (event) {
									console.debug( "Stop Crawl btn clicked" )
									jQuery( "#expertrec-stop-crawl-btn" ).hide()
									jQuery( "#expertrec-recrawl-btn" ).show()
									var data = {
										action: 'expertrec_crawl',
										func_to_call: 'stop_crawl'
									}
									$.post(
										the_ajax_script.ajaxurl,
										data,
										function (response) {
											console.debug( 'Re-Crawl response: ', response )
										}
									)
								}
							)
						}
					}
				)

				// Install mode settings update
				window.search_bar_update = function () {
					console.debug( "Install mode upadte btn clicked." )
					jQuery( "#exp-install-loading" ).css( 'display', 'block' )
					var hook     = jQuery( "input[name='searchHook']:checked" ).val()
					var hook_val = true
					if (hook == 'expertrec') {
						hook_val = false
					}
					data = {
						action: 'expertrec_update_config',
						data: {
							hook_on_existing_input_box: hook_val,
							org_status: 'NA'
						},
						update_type: 'install_mode'
					}
					$.post(
						the_ajax_script.ajaxurl,
						data,
						function (response) {
							console.debug( response )
							jQuery( "#exp-install-loading" ).css( 'display', 'none' )
							// function expertrec_update_config returns true or false
						}
					)
				}

				// Trial days
				var data = {
					action: 'expertrec_is_expired'
				}
				$.post(
					the_ajax_script.ajaxurl,
					data,
					function (response) {
						var json_data = response
						console.debug( "Trial days response: ", json_data.days, json_data.is_subscribed )
						var isExpire  = 15;
						if (json_data.is_subscribed) {
							jQuery( ".search-progress-cu-outer" ).hide()
						} else {
							var original_days = json_data.days + " days left in trial";
							var search_class  = ''
							if (json_data.days < 16 && json_data.days > 6) {
								search_class = 'green_section_color'
							} else if (json_data.days < 7 && json_data.days >= 1) {
								search_class = 'orange_section_color'
							} else {
								search_class  = 'red_section_color'
								original_days = 'Your trial has expired.'
								isExpire      = 0;
							}
							jQuery( ".expertrec_search_wrap" ).addClass( 'expertrec-trial' )
							jQuery( ".search-progress-count" ).addClass( search_class )
							jQuery( ".search-progress-count" ).text( original_days )
						}
					}
				)
			}

			function reset_index_stats() {
				// This will reset the indexing progress to 0
				var data = {
					action: 'expertrec_reset_indexing_progress'
				}
				$.post(
					the_ajax_script.ajaxurl,
					data,
					function (response) {
						console.debug( "Reset indexing progress response: ", response )
					}
				)
			}

			function check_indexing_status() {
				var data = {
					action: 'expertrec_indexing_status'
				}
				$.post(
					the_ajax_script.ajaxurl,
					data,
					function (response) {
						var response = response
						console.debug( "Indexing Status: ", response )
						if (response == 'indexing') {
							expertrec_is_indexing();
							update_index_stats()
						} else {
							expertrec_done_indexing();
							clearInterval( checkIndexingIntervalID );
							update_index_stats()
						}
					}
				);
			}

			function update_index_stats() {
				console.debug( "update_index_stats" )
				var data = {
					action: 'expertrec_index_stats'
				}
				$.post(
					the_ajax_script.ajaxurl,
					data,
					function (response) {
						var json_data             = response
						console.debug( "Index stats: ", json_data )
						var prod_indexed_percent  = (386 / json_data.product.indexable) * json_data.product.indexed
						var page_indexed_percent  = (386 / json_data.page.indexable) * json_data.page.indexed
						var post_indexed_percent  = (386 / json_data.post.indexable) * json_data.post.indexed
						var other_indexed_percent = (386 / json_data.other.indexable) * json_data.other.indexed
						console.debug( "products indexed: ", json_data.product.indexable )
						console.debug( "percent_completed product: ", prod_indexed_percent )
						console.debug( "percent_completed page: ", page_indexed_percent )
						console.debug( "percent_completed post: ", post_indexed_percent )
						console.debug( "percent_completed other: ", other_indexed_percent )
						jQuery( '.expertrec-svg-circle-product' ).animate( {'stroke-dashoffset': 386 - prod_indexed_percent}, 1000 )
						jQuery( '#exp-indexed-prod' ).text( json_data.product.label )
						jQuery( '.expertrec-svg-circle-post' ).animate( {'stroke-dashoffset': 386 - post_indexed_percent}, 1000 )
						jQuery( '#exp-indexed-post' ).text( json_data.post.label )
						jQuery( '.expertrec-svg-circle-page' ).animate( {'stroke-dashoffset': 386 - page_indexed_percent}, 1000 )
						jQuery( '#exp-indexed-page' ).text( json_data.page.label )
						jQuery( '.expertrec-svg-circle-other' ).animate( {'stroke-dashoffset': 386 - other_indexed_percent}, 1000 )
						jQuery( '#exp-indexed-other' ).text( json_data.other.label )
					}
				)
			}

			function start_indexing() {
				console.debug( "start_indexing called" )
				jQuery( "#exp-reindex-loading" ).css( 'display', 'block' )
				jQuery( "#expertrec-reindex-btn" ).hide()
				jQuery( "#expertrec-stop-indexing-btn" ).show()
				checkIndexingIntervalID = setInterval( check_indexing_status, 2000 );
				var data                = {
					action: 'expertrec_reindex_data'
				}
				// prefer to use the ajax call with both success and failure call back,
				// as we cannot endlessly spin.
				$.ajax(
					{
						type: "POST",
						url : the_ajax_script.ajaxurl,
						data: data,
						dataType: "json",
						complete : function (response) {
							console.debug( response )
							console.debug( "Indexing call Completed" )
							jQuery( "#expertrec-stop-indexing-btn" ).hide()
							jQuery( "#expertrec-reindex-btn" ).show()
							clearInterval( checkIndexingIntervalID );
						},
						error: function (response) {
							jQuery( "#expertrec-reindex-error" ).show();
							jQuery( "#expertrec-reindex-error" ).text( "Indexing request failed.  Already in progress." );
							expertrec_done_indexing();
						},
						success: function (response) {
							jQuery( "#expertrec-reindex-error" ).show();
							jQuery( "#expertrec-reindex-error" ).text( "Indexing request succeeded" );
							expertrec_done_indexing();
							// Ideally this is not needed.
							// TODO: remove this and make the page work.
							location.reload();
							console.debug( "indexing request completed successfully" )
							console.debug( response )
						}
					}
				)
			}

		} else if (window.location.href.indexOf( "?page=expertrecsearch-layout" ) > -1) {
			// Layout Page

			jQuery( '#layout-update-btn' ).click(
				function (event) {
					// To prevent default form submission
					event.preventDefault()
					console.debug( "Clicked layout update btn." )
					jQuery( '#exp-layout-loading' ).css( 'display', 'block' )
					jQuery( '#layout-update-btn' ).hide()
					var temp = jQuery( "input[name='template']:checked" ).val()
					var data = {
						action: 'expertrec_layout_submit',
						template: temp
					}
					if (temp == 'separate') {
						jQuery.extend(
							data,
							{
								search_path: jQuery( "input[name='search_path']" ).val(),
								query_parameter: jQuery( "input[name='query_parameter']" ).val()
							}
						)
					}
					$.post(
						the_ajax_script.ajaxurl,
						data,
						function (response) {
							console.debug( "layout update response: ", response )
							jQuery( '#layout-update-btn' ).show()
							jQuery( '#exp-layout-loading' ).css( 'display', 'none' )
							// response from function update_expertrec_layout is True or False
						}
					)
				}
			)
		} else if (window.location.href.indexOf( "?page=expertrecsearch-settings" )) {
			// Settings Page
			// input batch size should be under 100 only
			$( "#er_batch_size" ).keyup(
				function () {
					// Check correct, else revert back to old value.
					var batch_input = $( this ).val();
					if (batch_input && ! (parseInt( batch_input ) <= 100 && parseInt( batch_input ) >= 1)) {
						$( this ).val( $( this ).data( "old" ) );
					}
				}
			);

			jQuery( '#settings-update-btn' ).click(
				function (event) {

					// To prevent default form submission
					event.preventDefault()
					console.debug( "Clicked settings update btn." )
					jQuery( "#exp-settings-loading" ).css( 'display', 'block' )
					jQuery( '#settings-update-btn' ).hide()
					if (jQuery( "input[name='indexingWay']:checked" ).val()) {
						var exp_eng = jQuery( "input[name='indexingWay']:checked" ).val()
					} else {
						var exp_eng = 'db'
					}
					// assigning input batch size
					var exp_batch_size = $( "#er_batch_size" ).val()
					var data           = {
						action: 'expertrec_settings_update',
						engine: exp_eng,
						er_batch_size: exp_batch_size,
					}
					$.post(
						the_ajax_script.ajaxurl,
						data,
						function (response) {
							response = response
							console.debug( "Settings update response: ", response )
							jQuery( "input[name=api_key]" ).val( response )
							jQuery( '#settings-update-btn' ).show()
							jQuery( "#exp-settings-loading" ).css( 'display', 'none' )
							// function update_expertrec_settings returns false or $site_id
						}
					)
				}
			)
		}
		window.db_ui_show = function () {
			jQuery( '#er_batch_input' ).show();
			jQuery( '#er_write_key_title' ).show();
			jQuery( '#er_write_key' ).show();
		}
		window.db_ui_hide = function () {
			jQuery( '#er_batch_input' ).hide();
			jQuery( '#er_write_key_title' ).hide();
			jQuery( '#er_write_key' ).hide();
		}
	}
)
