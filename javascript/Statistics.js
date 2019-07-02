var Statistics = {
	/*
	 p.paging = {
	 recordsAtPage
	 currentPageFriends
	 currentPageCountries
	 }
	 */
	init: function(p) {
		var that = this;
		this.paging = p && p.paging
				? p.paging
				: {
					recordsAtPage: that.onstart.recordsAtPage,
					currentPageFriends: 0,
					currentPageCountries: 0,
					currentPageOther: 0
				};

		var that = this;
		//TODO: cash statistic output data

		var but = Base.inIframe()
				? [
					{//map
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_map_karma_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							$('.rating_output').slideUp();
							Maps.clickToKarmaBtn();
						}
					},
					{//KARMA rate
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_my_karma_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							that.getKarma();
						}
					},
					{
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_friends_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							that.onstart.actionOnSwitch(id);
						}
					},
					{//другие
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_org_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							that.getData('other', function(response) {
								$('.rating_output').slideUp();
								that.outputOther(response.data);
								$('.other_line').slideDown();
								that.initPagers(response);
							});
						}
					}
				]
				: //if in single mode
				[
					{//map
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_map_karma_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							$('.rating_output').slideUp();
							Maps.clickToKarmaBtn();
						}
					},
					{//KARMA rate
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_my_karma_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							that.getKarma();
						}
					},
					/*{
					 title: T.out('countries'),
					 action: function(id) {
					 that.getData('countries', function(response) {
					 that.outputCountries(response);
					 });
					 }
					 }, {//all
					 title: T.out('everybody2'),
					 action: function(id) {
					 that.getData('all', function(response) {
					 that.outputAll(response);
					 });
					 }
					 },*/
					{
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_friends_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							that.onstart.actionOnSwitch(id);
						}
					},
					{//другие
						title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_org_' + T.locale + '.png" alt class="round_but_svg"/></div>',
						action: function(id) {
							that.getData('other', function(response) {
								$('.rating_output').slideUp();
								that.outputOther(response.data);
								$('.other_line').slideDown();
								that.initPagers(response);
							});
						}
					}
				];


		Menu.getHTML({//try to get switcher code if it is not already loaded
			file: 'switcher.php',
			selector: '.switcher_flag',
			append: true,
			callback: function(page) {
				Switchers.place({
					host: $('.statistics_switcher_host'),
					id: 'statistics_filter',
					cls: 'switcher',
					unselected: 'switch_unselected',
					last: 'switch_unselected_last',
					selected: 'switch_selected',
					cur: -1, //that.onstart.cur,
					but: but
				});

				//TODO: change start page (on start)
				if (p && p.startPage) {
					Switchers.list['statistics_filter'].switch(that.convertSwitchNametoNum(p.startPage));
				} else {//by default other
					Switchers.list['statistics_filter'].switch(that.convertSwitchNametoNum(User.data
							? 'karma'
							: 'other'));
				}
			}
		});

		//place share button
		Buttons.place({
			host: $('.share_karma_button_host'),
			title: T.out('share_karma2'),
			id: 'share_karma',
			class: 'red_button',
			outer: {
				css: {
					'margin-right': '-20px',
					'margin-top': '0px',
					width: '200px'
				}
			},
			css: {
				width: '200px'
			},
			action: function() {
				var post_text = T.out('current_karma_title') + ' ' + $('.karma_indicator').html() + "\r\n" +
						T.out('friends_karma_title') + ' ' + $('.friends_karma_indicator').html() + "\r\n\r\n" +
						T.out('get_carma2') + ' ' + Application.addHTTPS();

				console.log('position1:', post_text);

				Facebook.post(post_text,
						//second parameter is redirect URI id Javascript SDK fails on iOS system		
						Application.baseURL() + '?thank_post=' + encodeURIComponent(post_text));
				$('.share_karma_button_host').hide();
			}
		});
		that.observeKarma(); //start karma observer

		that.ready = 1;
	},
	outputAround: function() {
		//if not present output folder

		$('.my_thanks_meter_host').slideUp();
		//$('.rating_output').slideUp();
		$('.around_rate').slideDown();
		$('.my_thanks_in_history').slideUp();
		$('.shop_host').slideUp();
		Maps.hide();

		//output data into folder
		B.get({
			r: 'user/get-popular',
			ok: function(response) {

				if (response.self) {
					var h = ['<table style="width:100%;">'];
					var odd = 0;
					for (var i in response.self) {
						h.push('<tr class="' + (odd % 2 == 0
								? 'inline_red'
								: 'inline_gold') + '"><td>' + response.self[i].for + '</td><td class="ar">' + response.self[i].count + '</td></tr>');
						odd++;
					}
					h.push('</table>');
					$('.self_around_host').html(h.join(''));
				}

				if (response.friends) {
					var h = ['<table style="width:100%:">'];
					var odd = 0;
					for (var i in response.friends) {
						h.push('<tr class="' + (odd % 2 == 0
								? 'inline_red'
								: 'inline_gold') + '"><td>' + response.friends[i].for + '</td><td class="ar">' + response.friends[i].count + '</td></tr>');
						odd++;
					}
					h.push('</table>');
					$('.friends_around_host').html(h.join(''));
				}

				if (response.others) {
					var h = ['<table style="width:100%:">'];
					var odd = 0;
					for (var i in response.others) {
						h.push('<tr class="' + (odd % 2 == 0
								? 'inline_red'
								: 'inline_gold') + '"><td>' + response.others[i].for + '</td><td class="ar">' + response.others[i].count + '</td></tr>');
						odd++;
					}
					h.push('</table>');
					$('.other_around_host').html(h.join(''));
				}

				$('.around_preloader_host').hide();
				$('.around_data_host').show();

			}
		});

	},
	convertSwitchNametoNum: function(name) { //CHECK nums in application mode

		//when adding countries - remove true

		if (true || Base.inIframe()) {
			if (name === 'friends') {
				return 1;
			}
			if (name === 'other') {
				return 2;
			}
			if (name === 'karma') {
				return 0;
			}

		} else {
			if (name === 'other') {
				return 2;
			}
			if (name == 'friends') {
				return 0;
			}
			/*
			 if (name == 'countries') {
			 return 1;
			 }
			 if (name == 'all') {
			 return 2;
			 }
			 if (name == 'friends'){
			 return 2;
			 }*/
		}
	},
	initPagers: function(response) { //on other

		var that = this;
		Pagers.place({
			id: 'statistics_other_pager',
			host: $('.other_line'),
			totalRecords: response.count,
			recordsAtPage: that.paging.recordsAtPage,
			currentPage: that.paging.currentPageOther,
			maxPage: Base.inIframe
					? 1
					: 0,
			maxPageAction: function() {
				//top.location = 'https://thankworld.com/?switch_to=other';
				top.location = A.baseURL() + '?run_action=switch_statistics&switch_to=other';
			},
			callback: function(page, slide) {
				$('.statistics_other_host', $('.other_line')).animate({
					left: slide === 'left'
							? '-100%'
							: '100%'
				}, 500, function() {
					$('.statistics_other_host', $('.other_line')).css({
						left: slide === 'left'
								? '100%'
								: '-100%'
					});
					that.paging.currentPageOther = page;
					that.getData('other', function(response) {
						that.outputOther(response.data);
						$('.statistics_other_host', $('.other_line')).animate({
							left: '0px'
						}, 500, function() {
						});
					});
				});
			}
		}); //of pagers_place
	},
	getKarmaStatus: function(k) {
		if (k < 1)
			return T.out('New karma');
		if (k < 5)
			return T.out('Rising karma');
		if (k < 10)
			return T.out('Good karma');
		if (k < 20)
			return T.out('Karma knight');
		if (k < 40)
			return T.out('Karma hero');
		if (k < 70)
			return T.out('Super karma');
		if (k < 110)
			return T.out('Karma guru');

		return T.out('Clean soul');

	},
	getKarmaImage: function(k) {
		if (k < 1)
			return 'images/status/1.png';
		if (k < 5)
			return 'images/status/5.png';
		if (k < 10)
			return 'images/status/10.png';
		if (k < 20)
			return 'images/status/20.png';
		if (k < 40)
			return 'images/status/40.png';
		if (k < 70)
			return 'images/status/70.png';
		if (k < 110)
			return 'images/status/100.png';

		return 'images/status/110.png';
	},
	getKarma: function(uid, data, host) {

		var that = this;
		$('.share_karma_button_host').show();
		$('.partner_statistics').hide();

		var place = data && data.place
				? data.place
				: false;

		if (place) {

			var net = data.net;

			$('.rating_output').slideUp();
			$('.karma_rate').slideDown();

			$('.karma_left_part').hide();
			$('.chart_control_host').hide();
			$('.friend_karma_header').show();

			$('.friends_karma_userpic').attr({
				src: data.image
			});

			for (var i in data) {
				$('.' + i).html(data[i]);
			}

			$('.share_karma_button_host').hide();

			$('.partner_statistics').hide();

			Thanks.get({
				host: $('.partner_thanks'),
				name: 'partner_thanks_list',
				filter: {
					get: {
						partner: {
							uid: uid,
							net: net
						}
					},
					start: 0,
					page: 20
				},
				afterOutput: function(data) {
					if (data.total == 0) {
						$('.partner_statistics').hide();
						$('.partner_thanks').slideDown();
					} else {
						$('.partner_statistics').show();
						$('.partner_thanks').slideDown();
					}
				}
			});

			$('.karma_chart', $('.karma_rate')).show();
			ChartThank.init('karma_chart', uid, net);

			//установка select place
			$('.send_thank_476').unbind('mousedown').mousedown(function() {

				Send.select_place({
					uid: uid,
					net: net,
					title: data.name_inline,
					image: data.image,
					post_title: net.indexOf('link_') !== -1 //link
							? data.friends_karma_name + ' ' + data.name_inline + ''
							: data.friends_karma_name + ' https://facebook.com/' + uid + ''
				});

				Menu.switchPage('send', false, 'redrawMenu');
			}).unbind('mouseover').mouseover(function() {
				$('.text_to_underline').css({
					'text-decoration': 'underline'
				});
			}).unbind('mouseout').mouseout(function() {
				$('.text_to_underline').css({
					'text-decoration': 'none'
				});
			});

			return;
		} else {

			$('.send_thank_476').unbind('mousedown').mousedown(function() {
				Send.select(uid);
				Menu.switchPage('send', false, 'redrawMenu');
			}).unbind('mouseover').mouseover(function() {
				$('.text_to_underline').css({
					'text-decoration': 'underline'
				});
			}).unbind('mouseout').mouseout(function() {
				$('.text_to_underline').css({
					'text-decoration': 'none'
				});
			});

		}

		Base.get({//return karma value
			script: 'index.php?r=user/karma',
			//r: 'user/karma',
			ok: function(response) {
				//console.log('user/karma', response);
				$('.rating_output').slideUp();
				$('.karma_rate').slideDown();
				$('.karma_indicator').html(response.karma);
				$('.carma_rate_main').html(response.karma);
				$('.self_user_karma').css({
					'font-size': T.locale === 'en'
							? '0.85rem'
							: '0.65rem'
				}).html(Statistics.getKarmaStatus(response.karma));
				$('.self_user_karma_picture').attr({
					src: Statistics.getKarmaImage(response.karma)
				});

				$('.friends_karma_indicator').html(response.average_friends_karma);

				if (uid) {
					$('.karma_left_part').hide();
					$('.chart_control_host').hide();
					$('.friend_karma_header').show();

					$('.friends_karma_userpic').attr({
						src: '//graph.facebook.com/' + uid + '/picture?width=50&amp;height=50'
					});

					if (data) {
						for (var i in data) {
							$('.' + i).html(data[i]);
						}
					}

					$('.share_karma_button_host').hide();
					$('.partner_statistics').hide();

				} else {
					//(Math.min(response.karma,160)

					//var deg = Math.min(response.karma, 160) * 2;

					var deg = -14400 / (response.karma + 14400 / 180) + 180;

					Base.loadRemote(Application.baseUrl + '/js_minified/jquery.transform2d.js', function() {

						$('.round_karma_indicator_arrow').animate({
							'transform': 'rotate(' + deg + 'deg)'
						}, 1000);

					});

					$('.karma_left_part').show();
					$('.chart_control_host').show();
					$('.friend_karma_header').hide();

					$('.friends_karma_userpic').attr({
						src: A.baseURL() + 'images/question.png'
					});
					$('.share_karma_button_host').show();
					$('.partner_statistics').hide();
				}


				if (uid) {
					$('.karma_chart', $('.karma_rate')).show();
				} else {
					$('.karma_chart', $('.karma_rate')).hide();
				}
				ChartThank.init(host
						? host
						: 'karma_chart', uid);
			},
			no: function(response) { //action on login
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.getKarma(uid, data, host);
					});
				}
			}
		});

	},
	getData: function(filter, callback) {
		var that = this;
		Base.get({
			script: 'index.php?r=user/statistics',
			//r: 'user/statistics',
			filter: filter || 'all',
			start: that.paging
					? (filter === 'friends'
							? that.paging.currentPageFriends
							: that.paging.currentPageOther) * that.paging.recordsAtPage
					: 0,
			page: that.paging
					? that.paging.recordsAtPage
					: 3,
			_ignorelimit: Statistics.onstart.ignoreLimit,
			ok: function(response) {
				if (callback) {
					callback(response);
				}
			},
			no: function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.getData(filter, callback);
					});
				}
			}
		});
	},
	/**
	 * type = 'all', 'other', 'friend' , 'countries'
	 */
	prepareOutput: function(type, response, host, paging) {
		var that = this;
		if (type !== 'friends' && type !== 'other') {
			$('.rating_output').slideUp();
		}
		var h = [];

		for (var i in response) {
			var place = i * 1 + 1 + (paging
					? paging * 1
					: 0);
			var record = response[i];
			var name_inline = (type === 'other'
					? record.name
					: (type === 'countries'
							? record.country
							: Base.convertName(record.name)));

			var visible_name = (type === 'other' && record.net.indexOf('link_') !== -1 && record.title
					? record.title
					: name_inline);

			var image = type === 'other' && record.net.indexOf('link_') !== -1
					? (
							record.logo
							? A.baseURL() + record.logo
							: A.baseURL() + 'images/question.png'
							)
					: '//graph.facebook.com/' + record.receiver_uid + '/picture?width=50&height=50';

			h.push('<tr class="' + (i % 2 === 0
					? 'inline_red'
					: 'inline_gold') + (type === 'countries'
					? (
							record.code
							? ''
							: ' other_countries cp '
							)
					: (type === 'friends' || type === 'other' || (type === 'all' && $('.friend_id_' + record.receiver_uid).length)
							? ' cp statistics_line statistics_line_' + record.receiver_uid + ' statistics_net_' + record.net
							: ''
							)
					) + '" ' + (
					type === 'friends'
					? 'title="' + that.getKarmaStatus(record.count) + '"'
					: ''
					) + '>' +
					'<td class="place_inline">' + place + '</td>' +
					//userpic
					'<td class="userpic_inline">' +
					(type === 'countries'
							? //for countries
							'<div class="countrypic_host">' +
							'<img src="' + A.baseUrl() + 'images/flags/' + (record.code
							? record.code
							: '_united-nations') + '.png" alt class="countrypic" ' +
							(record.code
									? ''
									: 'style="width:100px; height:100px; margin-left:-25px; margin-top:-24px;"') + '/>' +
							'</div>'
							: //for all other
							'<div class="pr" style="width:50px; height:50px;">' +
							'<img class="userpic pr" src="' + image + '" alt="' + record.receiver_uid + '" title="' + visible_name + '" style="width:50px; height:50px;"/>' +
							(type === 'friends'
									? '<img class="pa karma_status_images karma_status_image_' + record.receiver_uid + '" src="' + that.getKarmaImage(record.count) + '" />' + '</div>'
									: '')) +
					'</td>' +
					//name inline
					'<td><span class="visible_name">' + visible_name + '</span>' + (type === 'friends'
							? '<span class="visible_rank"> — ' + Statistics.getKarmaStatus(record.count) + '</span>'
							: '') + '<span style="display:none;" class="name_inline">' + name_inline + '</span></td>' +
					//thanks
					'<td class="thanks_inline">' + record.count + ' ' + (
							record.raise > 0
							? '<span style="font-size:1rem; color:green;">⬆</span>'
							: (record.raise < 0
									? '<span style="font-size:1rem; color:red;">⬇</span>'
									: '<span style="font-size:0.9rem;" class="hiragino">➡</span>')
							) +
					//'<img src="' + Application.baseUrl + '/images/logo1.png" alt/>' +
					'</td>' +
					'</tr>'
					);
		}
		if (h.length > 0) {
			host.html('<table class="stat_output_table">' + h.join('') + '</table>');

			$('.userpic', host).unbind('error').error(function() {
				$(this).attr({
					src: A.baseURL() + 'images/question.png'
				});
			});

		}

		$('.userpic', host).css({
			opacity: 0.8
		});

		if (type === 'countries') {
			$('.other_countries', host).unbind('mousedown').mousedown(function() {
				//top.location = 'https://thankworld.com/?switch_to=countries';
				top.location = Application.appSourcePath + '/?run_action=switch_statistics&switch_to=countries';
			});
		}

		if (type === 'other' || type === 'friends' || type === 'all') {
			$('.statistics_line', host).unbind('click').click(function() {

				var id = B.getID($(this), 'statistics_line statistics_line_');
				var net = B.getId($(this), 'statistics_net_');

				Switchers.list['statistics_filter'].switch(-1, 'noaction');

				/*D.show({
				 title: 'Karma graph',
				 message: '<div class="karma_graph_host_map"></div>',
				 onShow: function() {*/
				Statistics.getKarma(id, {
					place: type === 'other'
							? true
							: false,
					name_inline: $('.name_inline', $(this)).html(),
					type: type,
					net: net,
					friends_karma_name: $('.visible_name', $(this)).html(),
					friends_karma_value: $('.thanks_inline', $(this)).html(),
					friends_karma_rank: $('.visible_rank', $(this)).html(),
					image: $('.userpic', $(this)).attr('src')
				}, 'karma_graph_host_map');
				/*}
				 });*/

			}).unbind('mouseover').mouseover(function() {
				$('.userpic', $(this)).css({
					opacity: 1
				});
				$('.visible_name', $(this)).css({
					'text-decoration': 'underline'
				});
				$('.visible_rank', $(this)).css({
					'text-decoration': 'underline'
				});
			}).unbind('mouseout').mouseout(function() {
				$('.userpic', $(this)).css({
					opacity: 0.8
				});
				$('.visible_name', $(this)).css({
					'text-decoration': 'none'
				});
				$('.visible_rank', $(this)).css({
					'text-decoration': 'none'
				});
			});
		}

		Statistics.onstart.appendTriangle(type, host);

		host.slideDown();
	},
	outputFriends: function(response) {
		var that = this;
		if (!$('.statistics_friends_host').length) {
			$('.friends_line').html('<div class="statistics_friends_host pr" style="width:70%; margin:auto; height:auto; min-height:300px;">');
		}
		that.prepareOutput('friends', response, $('.statistics_friends_host'), that.paging.currentPageFriends * that.paging.recordsAtPage);
	},
	outputCountries: function(response) {
		this.prepareOutput('countries', response, $('.countries_line'));
	},
	outputOther: function(response) {
		if (!$('.statistics_other_host').length) {
			$('.other_line').html('<div class="statistics_other_host pr" style="width:70%; margin:auto; height:auto; min-height:300px;">');
		}
		this.prepareOutput('other', response, $('.statistics_other_host'), this.paging.currentPageOther * this.paging.recordsAtPage);
	},
	outputAll: function(response) {
		this.prepareOutput('all', response, $('.top10_line'));
		return;
	},
	//Observer karma and thanks status
	observeKarmaTimer: false,
	observeKarma: function() {

		Base.get({//return karma value
			script: 'index.php?r=user/karma-history',
			//r: 'user/karmaHistory',
			ok: function(response) {
				//console.log('karmaHistory', response);
				$('.friends_thanks_indicator').html(Math.round(response.friends_thanks));
				var karma = response.karma;
				$('.karma_indicator').html(karma.karma);
				$('.carma_rate_main').html(karma.karma);
				$('.self_user_karma').html(Statistics.getKarmaStatus(karma.karma));
				$('.self_user_karma_picture').attr({
					src: Statistics.getKarmaImage(karma.karma)
				});
				$('.friends_karma_indicator').html(karma.average_friends_karma);

				var deg = -14400 / (karma.karma + 14400 / 180) + 180;

				var deg2 = -14400 / (response.notused + 14400 / 180) + 180;

				Base.loadRemote(Application.baseUrl + '/js_minified/jquery.transform2d.js', function() {

					$('.round_karma_indicator_arrow').animate({
						'transform': 'rotate(' + deg + 'deg)'
					}, 1000);

					$('.round_thanks_indicator_arrow').animate({
						'transform': 'rotate(' + deg2 + 'deg)'
					}, 1000);

				});

				if (karma.karma != User.data.myKarma || karma.average_friends_karma != User.data.average_friends_karma) {
					//redraw graphic
					ChartThank.init('karma_chart', User.data.uid);
					User.data.myKarma = karma.karma;
					User.data.average_friends_karma = karma.average_friends_karma;
				}

				//redraw thanks data
				$('.my_thanks').html(response.notused/*response.history.my*/);
				$('.my_thanks_indicator').html(response.notused/*response.history.my*/);
				$('.available_thanks').html(response.notused);

			},
			no: function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.observeKarma();
					});
				}
			}
		});

		var that = this;
		if (that.observerKarmaTimer) {
			clearTimeout(that.observerKarmaTimer);
			that.observerKarmaTimer = false;
		}
		that.observerKarmaTimer = setTimeout(function() {
			that.observeKarma();
		}, 5000);
	},
	getGeoKarmaPlaces: function() {
		Base.get({
			script: 'index.php?r=user/get-geo-karma-places',
			ok: function(response) {
				var myLatLng = {lat: 55.751244, lng: 37.618423};

				// Create a map object and specify the DOM element for display.
				var map = new google.maps.Map(document.getElementById('map'), {
					center: myLatLng,
					scrollwheel: false,
					zoom: 12
				});

				// Create a marker and set its position.
				var place;
				for (var i = 0; i < response.places.length; i++) {
					place = response.places[i];
					var marker = new google.maps.Marker({
						map: map,
						position: place.coordinates,
						title: place.title + ' (' + place.karma + ')',
						//icon: 'https://graph.facebook.com/' + place.place_uid + '/picture?width=25&height=25',
						//icon: 'images/thk_hands_blue_25x25.png'
						icon: 'images/pin_karma.png'
					});

					google.maps.event.addListener(marker, "click", (function(marker, i) {
						return function() {
							$('#map').hide();
							Statistics.getKarma(response.places[i].place_uid, {
								friends_karma_name: response.places[i].title,
								friends_karma_rank: undefined,
								friends_karma_value: response.places[i].karma,
								image: "//graph.facebook.com/" + response.places[i].place_uid + "/picture?width=50&height=50",
								//image: 'images/thk_hands_blue.png',
								name_inline: response.places[i].title,
								net: "fb",
								place: true,
								type: "map"
							});
						}
					})(marker, i));
				}
			},
			no: function(response) {
				console.log(response);
			}
		});
	}
};