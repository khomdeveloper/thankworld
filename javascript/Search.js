var Search = {
	/**
	 * 
	 * @param {type} what - what to find (string)
	 * @param {type} host - $ object
	 * @param {type} popularnotneed (if true do not find popular places)
	 * @returns {undefined}
	 */
	get: function(what, host, popularnotneed) {
		var that = this;
		this.notactual = false;
		if (what && what.length > 2) {

			that.clean(host);

			FB.api('/search', {
				q: what,
				type: 'page',
				limit: 10,
				locale: User.data.locale
			}, function(response) {
				if (response.data && response.data.length > 0) {
					that.output(response.data, false, host);
				}
			});

			FB.api('/search', {
				q: what,
				type: 'place',
				limit: 10,
				locale: User.data.locale
			}, function(response) {
				if (response.data && response.data.length > 0) {
					that.output(false, response.data, host);
				}
			});

			if (!popularnotneed) {
				Base.get({
					script: 'index.php?r=user/statistics',
					//r: 'user/statistics',
					filter: 'other',
					start: 0,
					page: 30,
					ok: function(response) {
						that.outputPopular(response.data, host);
					}
				});
			}

			that.action(host, 'slide_down', false);

			$('.search_results', host).slideDown();

		} else {
			that.clean(host);
		}
	},
	notactual: false,
	clean: function(host) {
		$('.search_results', host).slideUp();
		$('.search_results_pages', host).html('');
		$('.search_results_places', host).html('');
		$('.search_results_popular', host).html('');
		$('.select_anyway_host', host).hide();
		this.action(host, 'slide_up', false);
		$('.find_what', host).css({
			color: 'rgb(192, 192, 192)'
		});
	},
	outputPopular: function(data, host0) {

		var that = this;

		var host = $('.search_results_popular', host0);

		if (data && data.length > 0) {

			var header = '<tr><td colspan="2" class="search_header">' + T.out('search_header_popular') + '</td></tr>';
			var h = [];
			for (var i in data) {
				var record = data[i];
				h.push(
						'<tr style="border-bottom:1px solid gray;" class="cp search_line search_line_' + record.receiver_uid +
						'-' + record.receiver_net + '"><td>' +
						'<img src="' + (record.receiver_net.indexOf('link_') !== -1
								? (record.logo
										? record.logo
										: A.baseURL() + 'images/question.png')
								: '//graph.facebook.com/' + record.receiver_uid + '/picture?width=50&height=50') + '" width="50" height="50" alt="' + (record.logo
						? record.logo
						: '') + '"/></td>' +
						'<td><span class="place_name">' + record.name + '</span>' +
						'<div style="font-size:0.8em; color:gray;">' + T.out('thank_amount_2') + ': ' + record.count + '</div>' +
						'</td></tr>'
						);
			}

			if (h.length) {
				host.html('<table style="width:100%;">' + header + h.join('') + '</table>');
				B.click($('.search_line', host), function(obj) { //select place or page
					var uid_net = B.getId(obj, 'search_line_').split('-');
					var title = $('.place_name', obj).html();
					that.clean(host0);
					that.notactual = true;
					that.action(host0, 'select_place', {
						uid: uid_net[0],
						net: uid_net[1],
						image: $('img', obj).attr('alt'),
						title: title
					});
				});
			}

		}
	},
	output: function(page_data, place_data, host0) {

		var that = this;

		var h = [];

		var data = page_data
				? page_data
				: place_data;


		var host = page_data
				? $('.search_results_pages', host0)
				: $('.search_results_places', host0);

		var header = page_data
				? '<tr><td colspan="2" class="search_header">' + T.out('search_header_pages') + '</td></tr>'
				: '<tr><td colspan="2" class="search_header">' + T.out('search_header_places') + '</td></tr>';


		for (var i in data) {
			var record = data[i];

			var category_list = [];
			if (record.category_list && record.category_list.length) {
				for (var j in record.category_list) {
					category_list.push(record.category_list[j].name);
				}
			}

			var location = [];
			if (record.location) {
				for (var j in record.location) {
					if ((j === 'country' || j === 'city' || j === 'state' || j === 'street') && record.location[j]) {
						location.push(record.location[j]);
					}
				}
			}

			h.push(
					'<tr style="border-bottom:1px solid gray;" class="cp search_line search_line_' + record.id + '-fb"><td><img src="//graph.facebook.com/' + record.id + '/picture?width=50&height=50" alt=""/></td>' +
					'<td><span class="place_name">' + record.name + '</span>' +
					(category_list.length > 0
							? '<span style="font-size:0.8em;"> (' + category_list.join(', ') + ')</span>'
							: '') +
					'<div style="font-size:0.8em; color:gray;">' + location.join(', ') + '</div>' +
					'</td></tr>'
					);
		}

		if (h.length) {
			host.html('<table style="width:100%;">' + header + h.join('') + '</table>');

			B.click($('.search_line', host), function(obj) {
				var uid_net = B.getId(obj, 'search_line_').split('-');
				var title = $('.place_name', obj).html();				
				that.clean(host0);
				$('.select_anyway_host', host0).hide();
				this.notactual = true;				
				that.action(host0, 'select_place', {
					uid: uid_net[0],
					net: uid_net[1],
					title: title
				});
                if (place_data) {
                    Base.get({
                        script: 'index.php?r=user/get-geo-of-current-user',
                        defaultMoscow: true,
                        ok: function(response) {
                            var selector = host0.selector, map_id;
                            if (selector === '.searchQR') {
                                map_id = 'place_map';
                            } else {
                                map_id = 'place_map_reverted';
                            }

                            var description = (selector === '.searchQR') ? 'QRCODE' : 'QRCODE_REVERTED';

                            Thankbuttons.showMap(map_id, response, description, uid_net[0], null);

                        },
                        no: function(response) {
                            console.log('Error while pending request "index.php?r=user/get-geo-of-current-user"');
                            console.log(response);
                        }
                    });
                }
			});

		}
	},
	placed: {},
	actions: {},
	setActions: function(host, actions) {
		var that = this;
		that.actions[that.getHostName(host)] = actions;
	},
	place: function(host) {

		var that = this;
		$('.find_what', host).unbind('keyup').keyup(function() {
			that.control(host);
		});

		//special action on select url
		$('.select_anyway', host).unbind('mousedown').mousedown(function() {
			Base.press($(this));
			that.action(host, 'select_anyway', {
				title: $('.find_what', host).val()
			});
		});

	},
	delay: false,
	actions: {},
			action: function(host, action, input) {
				var that = this;
				if (that.actions[that.getHostName(host)] && that.actions[that.getHostName(host)][action]) {
					that.actions[that.getHostName(host)][action](input);
				}
			},
	control: function(host) {
		var that = this;

		var text = $('.find_what', host).val();

		if (text && Base.isURL(text)) {
			$('.select_anyway_host', host).show();
			$('.find_what', host).css({
				color: 'rgb(196, 43, 44)'
			});
			$('.search_results', host).slideUp();
			that.action(host, 'slide_up', false);
			if (that.delay) {
				clearTimeout(that.delay);
			}
			return;
		} else {
			$('.select_anyway_host', host).hide();
			$('.find_what', host).css({
				color: 'rgb(192, 192, 192)'
			});
		}

		if (that.delay) {
			clearTimeout(that.delay);
			that.delay = setTimeout(function() {
				that.call(host);
			}, 500);
		} else {
			that.delay = setTimeout(function() {
				that.call(host);
			}, 500);
		}
	},
	getHostName: function(host) {
		if (host.hasClass('searchQR')) {
			return 'searchQR';
		} 
		if (host.hasClass('searchQRreverted')){
			return 'searchQRreverted';
		}
		return 'searchmain';
	},
	isPopularNeed: function(host) {
		if (host.hasClass('searchQR')) {
			return true;
		} else {
			return false;
		}
	},
	call: function(host) {
		var val = $('.find_what', host).css({
			color: 'silver'
		}).val();
		this.get(val, host, this.isPopularNeed(host));
	}
};