var User = {
	data: false,
	showWelcomeDialog: function(data) {
		Dialog.show({
			title: '',
			message: 'first_entrance.php',
			noexit: 1,
			buttons: [
				{
					title: 'OK',
					action: function() {
						Dialog.hide();
				p	},
					class: ['round', 'transparent_button'],
					css: {
						width: '40px',
						height: '33px',
						'padding-top': '7px'
					}
				}
			],
			onShow: function() {
				$('.your_karma173').html(data.karma);
				$('.friends_karma173').html(data.average_friends_karma);
			}
		});
	},
	loadNames: function(additionalNames) {
		if (User.karmaLoaded) {
			Base.get({
				script: 'index.php?r=user/names',
				//r: 'user/names',
				Names: 'read',
				ok: function(finded) {
					Friends.searchOnFacebook(additionalNames, finded);
				}
			});
		} else {
			setTimeout(function() {
				User.loadNames(additionalNames);
			}, 500);
		}
	},
	updateFriends: function() {
		if (User.data && User.karmaLoaded) {
			Base.get({
				script: 'index.php?r=user/updatefriends',
				//r: 'user/updatefriends',
				uid: User.data.id,
				net: 'fb',
				ok: function(response) {
					console.log(response);
					if (response.success && response.first_entrance * 1 === 0) {
						if ($('.dialog').is(':visible')) {
							setTimeout(function() {
								User.showWelcomeDialog(response.data);
							}, 1000);
						} else {
							User.showWelcomeDialog(response.data);
						}
					}

				}
			});
		} else {
			//console.log(User.data, User.karmaLoaded);
			setTimeout(function() {
				User.updateFriends();
			}, 15000);

			setTimeout(function() {
				Geolocation.get();
			}, 1);

		}
	},
	requestLogin: function(ok) {

		if (Application.alreadyStart) {
			Dialog.show({
				title: T.out('press_to_login'),
				message: '',
				buttons: [{
						title: 'FB',
						action: function() {
							/*if (B.isFirefox() && false) { //deprecated
							 Facebook.redirectLogin({
							 return_to: B.addHTTPS(Application.appSourcePath),
							 scope: 'user_friends,email' //access to user data which we need on start
							 });
							 } else {
							 FB.login(function(response) {
							 Facebook.onLogin(response, ok);
							 }, {scope: 'user_friends, email'});
							 }*/
						},
						outer: {
							css: {
								left: '50%'
							}
						}
					}
				],
				onShow: function() {
					$('.pr', $('.dialog_footer')).css({
						left: '40px',
						'margin-left': '-60px'
					}).unbind('click').click(function() {
						FB.login(function(response) {
							Facebook.onLogin(response, ok);
						}, {scope: 'user_friends, email'});
					});

				}/*,
				 noexit: true*/
			});

		} else { //ask for login on first start

			//TODO:

			//hide gear

			//show button at right

			//show external_statistics instead normal statistics

			this.initMenu('statistics'); //need to hide some buttons
			Application.alreadyStart = true;

			/*
			 $('.saythank_but_host').hide();
			 $('.statistic_but_host').hide();
			 $('.history_but_host').hide();
			 */
		}

	},
	logged: function() {
		console.log('need to lazyload User.logged function');
	},
	logout: function() {
		FB.logout(function(response) {
			B.post({
				script: 'index.php?r=user/logout',
				ok: function(response) {
					if (response.ok) {
						var cookies = document.cookie.split(";");
						for (var i = 0; i < cookies.length; i++) {
							var equals = cookies[i].indexOf("=");
							var name = equals > -1
									? cookies[i].substr(0, equals)
									: cookies[i];
							document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
						}
						window.location.reload(true);
					}
				}
			});
		});
	},
	notLogged: function(data) {
		console.log('need to lazyload User.notLogged function');
	},
	notConnected: function(data) {
		console.log('need to lazyload User.notConnected function');
	},
	output: function() {

		Application.alreadyStart = 1;

		/*
		 $('.saythank_but_host').show();
		 $('.statistic_but_host').show();
		 $('.history_but_host').show();
		 */

		$('.user_name').html(this.data.first_name + '&nbsp;' /*+ this.data.middle_name + '&nbsp;'*/ + this.data.last_name);

		//$('.user_name').html('');

		$('.header_center.user_name').css({
			'text-transform': 'uppercase'
		});

		$('.userpic_host').html('<img src="https://graph.facebook.com/' + this.data.id + '/picture?width=80&height=80" class="userpic"/>');

		//menu and pages initialisation
		this.initMenu();

	},
	initMenu: function(active) {

		if (Base.inIframe()) {
			$('.redirect_to_site').hide();
			$('.special_menu_host').show().html(
					/*'<div class="pr" style="width:8px; height:17px; display:inline-block; margin:2px;">' +
					 '<img src="' + A.baseURL() + 'images/help.png" class="cp pa open_help_menu" style="left:0px; top:1px; width:100%; height:100%;" alt />' +
					 '</div>' +*/
					'<div class="pr" style="width:13px; height:13px; display:inline-block; margin:5px;">' +
					'<img src="' + A.baseURL() + 'images/call_button.png" class="cp pa redirect_to_www_button" style="left:0px; top:-1px; width:100%; height:100%;" alt />' +
					'</div>').show();
		} else {
			$('.redirect_to_site').hide();
			$('.special_menu_host').html(
					'<div class="pr open_special_menu_host" style="width:13px; height:13px; display:none; margin:5px;">' +
					'<img src="' + A.baseURL() + 'images/call_button.png" class="cp pa open_special_menu" style="left:0px; top:-1px; width:100%; height:100%;" alt />' +
					'</div>' +
					'<div class="pr login_to_facebook_77_host" style="width:15px; height:15px; display:inline-block; margin:2px;">' +
					'<img src="' + A.baseURL() + 'images/facebook.png" class="cp pa login_to_facebook_77" style="left:0px; top:1px; height:13px;" alt />' +
					'</div>' /*+
					 '<div class="pr" style="width:8px; height:17px; display:inline-block; margin:2px;">' +
					 '<img src="' + A.baseURL() + 'images/help.png" class="cp pa open_help_menu" style="left:0px; top:1px; width:100%; height:100%;" alt />' +
					 '</div>'*/
					).show();
		}

		B.click($('.redirect_to_www_button'), function() {
			top.location = Application.appSourcePath;
		});

		$('.login_to_facebook_77').unbind('mousedown').mousedown(function() {
			Base.press($(this));
			User.requestLogin(function(response) {
				User.logged(response);
			});
		});

		$('.open_special_menu').unbind('mousedown').mousedown(function() {
			Base.press($(this));
			if (!window.Thankbuttons) {
				//alert('stop');
			}
			Thankbuttons.choose();
		});

		B.click($('.open_help_menu'), function() {
			Help.show();
		});

		B.click($('.logout'), function() {
			User.logout();
		});


		Menu.init({
			send: {
				page: {
					host: $('.send_form_host'),
					selector: '.send'
				},
				but: {
					/*host: $('.saythank_but_host'),
					 title: '<span style="line-height:23px;">' + T.out('say_thank_caps3') + '</span>',
					 class: 'gold_button green_transparent'*/
					$: $('.run_thank')
				},
                onSwitch: function() {
                    Maps.hide();
                }
			},
			statistics: {
				page: {
					host: $('.statisticsPage'),
					selector: '.statistics'
				},
				but: {
					/*host: $('.statistic_but_host'),
					 title: '<div class="statistics_but_host_title"></div>',
					 class: 'gold_button'*/
					$: $('.run_karma')
				},
				onSwitch : function(){
					if ($('#switch_statistics_filter_item_0').hasClass('switch_selected')){
						$('#maps').slideDown();
					}
				}
			},
			history: {
				page: {
					host: $('.historyPage'),
					selector: '.history'
				},
				but: {
					$: $('.run_thankyou')
							/*
							 host: $('.statistic_but_host'),
							 title: '<div class="history_but_host_title"></div>',
							 class: 'gold_button green_transparent thanks0147'
							 */
				},
				onSwitch: function() {

					if ($('#switch_history_filter_item_0').hasClass('switch_selected')){
						$('#maps').slideDown();
					} else {
						$('#maps').slideUp();
					}
					

					Buttons.place({
						host: $('.send_donation_host'),
						title: T.out('send_donation'),
						id: 'send_donation',
						class: 'red_button',
						outer: {
							css: {
								'margin-right': '0px',
								'margin-top': '0px',
								width: '100px'
							}
						},
						css: {
							width: '100px',
							'margin-right': '-20px'
						},
						action: function() {
							B.wait(
									function() {
										return window.History
												? true
												: false;
									},
									function() {
										History.sendDonation();
									});
						}
					});

					
					
					//!!! place to add buttons
					Switchers.place({
						host: $('.history_switcher_host'),
						id: 'history_filter',
						cls: 'switcher',
						unselected: 'switch_unselected',
						last: 'switch_unselected_last',
						selected: 'switch_selected',
						once: true,
						cur: 1,
						but: [
							{//map
								title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_map_thanks_' + T.locale + '.png" alt class="round_but_svg"/></div>',
								action: function(id) {
									$('.my_thanks_in_history').slideUp();
									$('.my_thanks_meter_host').slideUp();
									$('.around_rate').slideUp();
									$('.shop_host').slideUp();
									//$('.thanks_map_host').slideDown(400, History.getGeoThankPlaces);
                                    Maps.clickToThanksBtn();
								}
							},
							{//THANKS meter
								title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_my_thanks_' + T.locale + '.png" alt class="round_but_svg"/></div>',
								action: function(id) {
									$('.my_thanks_in_history').slideUp();
									$('.around_rate').slideUp();
									$('.shop_host').slideUp();
									$('.my_thanks_meter_host').slideDown();
									$('.thanks_map_host').slideUp();
                                    Maps.hide();
								}
							},/*
							{//KARMA rate
								title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_history_' + T.locale + '.png" alt class="round_but_svg"/></div>',
								action: function(id) {
									$('.my_thanks_in_history').slideDown();
									$('.my_thanks_meter_host').slideUp();
									$('.around_rate').slideUp();
									$('.shop_host').slideUp();
									//$('.thanks_map_host').slideUp();
                                    Maps.hide();
								}
							},*/
							{//shop
								title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_shop_' + T.locale + '.png" alt class="round_but_svg"/></div>',
								action: function(id) {
									/*Dialog.show({
									 title: '<div style="margin-bottom:-10px;">Coming soon!</div>',
									 message: ''
									 });*/
									$('.my_thanks_in_history').slideUp();
									$('.my_thanks_meter_host').slideUp();
									$('.around_rate').slideUp();
									$('.shop_host').slideDown();
									$('#maps').slideUp();
									$('.thanks_map_host').slideUp();
                                    Maps.hide();
								}
							},
							{//around
								title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_around_' + T.locale + '.png" alt class="round_but_svg"/></div>',
								action: function(id) {
									Statistics.outputAround();
								}
							}

						]
					});
				}
			},
			/*
			 history: {
			 page: {
			 host: $('.historyPage'),
			 selector: '.history'
			 },
			 but: {
			 host: $('.history_but_host'),
			 title: '<table style="margin:0px; margin-top:-5px;"><tr><td><div class="ac my_thanks round">0</div></td><td style="padding-top:10px;">' + T.out('history_caps') + '</td></tr></table>',
			 //'<span class="my_thanks" style="vertical-align:2px; margin-right:5px; padding:0px;"></span> <img class="thank_in_rating" style="position:relative; margin:0px; top:-3px; margin-bottom:0px; vertical-align:0px;" src="' + Application.baseUrl + '/images/left.png" alt/>',
			 class: 'gold_button'
			 },
			 onSwitch: function() {
			 History.update();
			 }
			 },*/
			messages: {
				page: {
					host: $('.messagesPage'),
					selector: '.messages'
				}
			},
			news: {
				page: {
					host: $('.newsPage'),
					selector: '.news'
				},
				but: {
					$: $('.logo_header_09')
				},
				onSwitch: function() {
					Thanks.get({
						host: $('.news'),
						name: 'news_thanks_list',
						filter: {
							get: 'new',
							start: 0,
							page: 20
						}
					});
				}
			},
			preloader: {
				page: {
					host: $('.preloader_host')
				}
			},
			active: active || 0,
			onInit: function() {
				var title_host = $('.history_but_host_title');
				title_host.html('<table style="margin:0px; margin-top:-5px;"><tr><td style="padding-top:10px;" class="ar"><div class="history_caps_visible" style="display:none; color:white;">' + T.out('history_caps') + '</div><img src="images/thk_green.png" class="statistics_green_hand_visible"/></td><td class="ar" style="padding-right:5px;"><div class="ac available_thanks round" style="float:right;">0</div></td></tr></table>');
				title_host.unbind('mouseenter').mouseenter(function() {
					$('.statistics_green_hand_visible').hide();
					$('.history_caps_visible').show();
					$('.history_but_host_title').css({
						background: 'rgb(141,196,73)'
					});
				}).unbind('mouseleave').mouseleave(function() {
					$('.statistics_green_hand_visible').show();
					$('.history_caps_visible').hide();
					$('.history_but_host_title').css({
						background: 'none'
					});
				});
				var title_host2 = $('.statistics_but_host_title');
				title_host2.html('<table style="margin:0px; margin-top:-5px;"><tr><td><div class="ac carma_rate_main round">0</div></td><td style="padding-top:10px;"><div class="statistics_caps2_visible" style="display:none; color:white;">' + T.out('statistics_caps2') + '</div><img src="images/thk_hands_blue.png" class="statistics_blue_hands_visible"/></td></tr></table>');
				title_host2.unbind('mouseenter').mouseenter(function() {
					$('.statistics_blue_hands_visible').hide();
					$('.statistics_caps2_visible').show();
					$('.statistics_but_host_title').css({
						background: 'rgb(3, 55, 127)'
					});
					$('.button_statistics').css({
						border: '1px solid white'
					});
				}).unbind('mouseleave').mouseleave(function() {
					$('.statistics_blue_hands_visible').show();
					$('.statistics_caps2_visible').hide();
					$('.statistics_but_host_title').css({
						background: 'none'
					});
					$('.button_statistics').css({
						border: '1px solid rgb(3, 55, 127)'
					});
				});
			}
		});

	}
};