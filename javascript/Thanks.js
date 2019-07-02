var Thanks = {
	/*
	 filter
	 host
	 name
	 callback
	 */
	disabled: false,
	//TODO: redo this
	disable: function() {
		this.disabled = true;
		$('.thanks_hidable_host').css({
			opacity: 0.7
		});
		$('.cp', $('.thanks_hidable_host')).css({
			cursor: 'default'
		});
	},
	enable: function() {
		this.disabled = false;
		$('.thanks_hidable_host').css({
			opacity: 1
		});
		$('.cp', $('.thanks_hidable_host')).css({
			cursor: 'pointer'
		});
	},
	select: function(id) {
		var that = this;
		if (window.Menu) {
			var page = 'messages';
			if ($('.messages').is(':visible')) {
				if (window.Messages) {
					$('.send_message_in_messages_host').hide(); //slideUp send message form
					if (!that.disabled) {
						that.disable();
						$('.messages_list').slideUp(function() {
							Messages.startChain(id, function() {
								$('.messages_list').show();
							}, function() {
								that.enable();
							});
						});
					}
				}
			} else {
				Menu.getHTML({
					file: page,
					host: Menu.pages[page].page.host,
					selector: Menu.pages[page].page.selector,
					callback: function(page) {
						Menu.switchPage('messages', function() {
							if (window.Messages) {
								if (!that.disabled) {
									that.disable();
									Messages.startChain(id, function() {
										$('.messages_list').show();
									}, function() {
										that.enable();
									});
								}
							}
						});
					}
				});
			}
		} else {
			setTimeout(function() {
				this.select(id);
			}, 500);
		}
	},
	get: function(input) {
		var that = this;
		that.getData(function(data) {
			if (input.callback) {
				input.callback(data);
			}

			that.output(data, input.host, input.filter.page === 3
					? false
					: true);

			if (input.afterOutput) {
				input.afterOutput(data);
			}

			Pagers.place({
				id: input.name || false,
				host: input.host,
				totalRecords: data.total,
				recordsAtPage: input.filter.page,
				currentPage: 0,
				maxPage: input.host.hasClass('header_thanks_list')
						? 5
						: 7,
				noTriangle: input.host.hasClass('header_thanks_list')
						? 0
						: 1,
				maxPageAction: input.host.hasClass('header_thanks_list')
						? function() {
							Menu.getHTML({
								file: 'news',
								host: Menu.pages['news'].page.host,
								selector: Menu.pages['news'].page.selector,
								callback: function(page) {
									Menu.switchPage('news', function() {
										Thanks.get({
											host: $('.news'),
											name: 'news_thanks_list',
											filter: {
												get: 'new',
												start: 0,
												page: 20
											}
										});
									});
								}
							});
						}
				: false,
				callback: function(page, slide) {
					$('.thanks_hidable_host', input.host).animate({
						left: slide == 'left'
								? '-100%'
								: '100%'
					}, 500, function() {
						$('.thanks_hidable_host', input.host).css({
							left: slide == 'left'
									? '100%'
									: '-100%'
						});
						that.getData(function(response) {
							that.output(response, input.host, input.filter.page === 3
									? false
									: true);

							if (input.afterOutput) {
								input.afterOutput(response);
							}

							$('.thanks_hidable_host', input.host).animate({
								left: '0px'
							}, 500, function() {
							});
						}, {
							get: input.filter.get,
							start: page * input.filter.page,
							page: input.filter.page
						});
					});
				}
			});
		}, input.filter, input.readyResponse);
	},
	//TODO: think about increasing the pages on observe
	//TODO: cash already loaded data
	//response -> we has already loaded data

	getData: function(callback, filter, response) {

		var that = this;
		if (!filter) { //default
			var filter = {
				get: 'all',
				start: 0,
				page: 3
			};
		}


		if (response) {
			$('.all_thanks').html(response.my + response.i);
			$('.my_thanks').html(response.my);
			$('.my_thanks_indicator').html(response.my);
			$('.i_thanks').html(response.i);
			if (callback) {
				callback(response);
			}
			return;
		}

		//call history
		Base.get({
			script: 'index.php?r=user/history',
			//r: 'user/history',
			get: filter.get,
			start: filter.start,
			page: filter.page,
			only_favourite: $('.favourite_selector').attr('alt'),
			ok: function(response) {
				//console.log('user/history', response);
				$('.all_thanks').html(response.my + response.i);
				$('.my_thanks').html(response.my);
				$('.my_thanks_indicator').html(response.my);
				$('.i_thanks').html(response.i);
				if (callback) {
					callback(response);
				}
			},
			no: function(response) {
				
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.getData((callback, filter, response));
					});
				}
			}
		});
	},
	//prepare message caption in the list
	convertName: function(name) {
		return Base.convertName(name);
	},
	getSenderName: function(line) {
		return line.sender_name;
	},
	getReceiverName: function(line) {
		return line.receiver_name
				? line.receiver_name
				:
				((line.status === '' || line.status === 'message' || line.status === 'place')
						? ''
						: Base.convertName(line.status));
	},
	/*
	 * add "for" / "за"
	 *  
	 * @param {type} text
	 * @returns {String}
	 */
	getPrefix: function(text) {
		if (T.locale == 'ru') {
			if (text.toUpperCase().indexOf('ЗА ') == 0) {
				return '';
			} else {
				return 'за ';
			}
		} else {
			if (text.toUpperCase().indexOf('FOR ') == 0) {
				return '';
			} else {
				return 'for ';
			}

		}
	},
	getCaptionJH: function(line) {

		var you_receiver = (line.receiver_uid * 1 === User.data.uid * 1 && line.receiver_net === User.data.net)
				? true
				: false;
		var you_sender = (line.sender_uid * 1 === User.data.uid * 1 && line.sender_net === User.data.net)
				? true
				: false;

		var receiver_name = you_receiver
				? T.out('to_you')
				: (line.receiver_name
						? (line.replace_title
								? line.replace_title
								: line.receiver_name)
						:
						((line.status === '' || line.status === 'message' || line.status === 'place')
								? ''
								: Base.convertName(line.status))
						);

		return receiver_name + T.out('on_date') + Base.reformat(line.changed);

	},
	getCaption: function(line, withoutdate, onlythanks, af, nosend) {

		var you_receiver = (line.receiver_uid * 1 === User.data.uid * 1 && line.receiver_net === User.data.net)
				? true
				: false;
		var you_sender = (line.sender_uid * 1 === User.data.uid * 1 && line.sender_net === User.data.net)
				? true
				: false;

		var receiver_name = you_receiver
				? (line.ref * 1
						? T.out('to_you')
						: T.out('to_you_inv')
						)
				: '<span ' + (line.place*1 !== 0 && !nosend
						? 'class="dot cp goto_place_thank placeNo_' + line.receiver_uid + '"'
						: (af && af[line.receiver_uid]
								? 'class="dot cp goto_send_thank"'
								: '')) + '>' + (line.receiver_name
				? (line.replace_title
						? line.replace_title
						: line.receiver_name)
				:
				((line.status === '' || line.status === 'message' || line.status === 'place')
						? ''
						: Base.convertName(line.status))
				) + '</span>';
		var sender_name = you_sender
				? T.out('you_to')
				: '<span ' + (af && af[line.sender_uid]
						? 'class="dot cp goto_send_thank"'
						: '') + '>' + (line.sender_name ? line.sender_name : line.place) + '</span>';
		var action = line.ref * 1 //it is a message
				? (
						you_sender
						? T.out('you_sent_a_message3')
						: T.out('somebody_sent_message3')
						)
				: (//not message
						line.status === '' || line.status === 'place' || line.status === 'read' || onlythanks //thank
						? (
								you_sender
								? T.out('you_thanked3')
								: T.out('thanked_low3')
								)
						: (//invite
								you_sender
								? T.out('you_invited3')
								: T.out('invited_low2')
								)
						);

		return sender_name + ' ' + action + ' ' + receiver_name + (withoutdate
				? ''
				: T.out('on_date') + Base.reformat(line.changed)) +
				(line.title
						? '<div>' + this.getPrefix(line.title) + line.title + '</div>'
						: '');
	},
	/*
	 this function just output data into host
	 it will automatically create .thanks_hidable_host container
	 */
	output: function(data, host, nodate) {

		var host = host
				? host
				: this.host;
		var that = this;

		that.data2 = data;

		if (data && data.total) {
			var h = [];
			var blockdate = false;


			var af = {};
			for (var i in data.affairs) {
				af[data.affairs[i]] = 1;
			}

			var outputNames = host.hasClass('header_thanks_list') || host.hasClass('news') || host.hasClass('thanks_data_history_host')
					? true
					: false;

			for (var i in data) {
				if (!isNaN(i)) {
					var line = data[i];

					//console.log(line);

					var received = (line.receiver_uid * 1 === User.data.uid * 1 && line.receiver_net === User.data.net)
							? true
							: false;
					var participate_in_correspondence =
							((line.receiver_uid * 1 === User.data.uid * 1 && line.receiver_net === User.data.net) ||
									(line.sender_uid * 1 === User.data.uid * 1 && line.sender_net === User.data.net))
							? true
							: false;
					var left_picture = received
							? '//graph.facebook.com/' + User.data.uid + '/picture'
							: '//graph.facebook.com/' + line.sender_uid + '/picture';
					var right_picture = !received && line.receiver_net.indexOf('link_') !== -1
							? (
									line.logo
									? line.logo
									: A.baseURL() + 'images/question.png'
									)
							: ('//graph.facebook.com/' + (received
									? line.sender_uid
									: line.receiver_uid) + '/picture');

					var host_attributes = !received && line.receiver_net.indexOf('link_') !== -1 && line.logo
							? 'host_of_' + line.receiver_uid
							: '';

					var central_picture = (line.ref * 1)
							? 'arrow.png'
							: (
									received
									? 'left.png'
									: 'right.png'
									);
					var left_image_attributes = outputNames
							? (received
									? 'alt="' + line.receiver_uid + '" title="' + that.getReceiverName(line) + '"'
									: 'alt="' + line.sender_uid + '" title="' + that.getSenderName(line) + '"')
							: 'alt';
					var right_image_attributes = outputNames
							? (
									received
									? 'alt="' + line.sender_uid + '" title="' + that.getSenderName(line) + '"'
									: 'alt="' + line.receiver_uid + '" title="' + that.getReceiverName(line) + '"'
									)
							: 'alt';


					var left_class = outputNames
							? 'userpic self_userpic_in_history ' + (
									received
									? (
											af[line.receiver_uid]
											? 'goto_send_image cp'
											: ''
											)
									: (
											af[line.sender_uid]
											? 'goto_send_image cp'
											: ''
											)
									)
							: 'userpic self_userpic_in_history';

					var right_class = 'userpic self_userpic_in_history' + (
							outputNames
							? (
									received
									? (
											af[line.sender_uid]
											? ' goto_send_image cp'
											: ''
											)
									: (
											line.status === 'place'
											? ' goto_place_image cp'
											: (af[line.receiver_uid]
													? ' goto_send_image cp'
													: '')
											)
									)
							: '');

					if (nodate) {
						var date = Base.reformat(line.changed);
						if (date != blockdate) {
							blockdate = date;
							h.push('<div class="ac fb date_p date_p_' + date.split('.').join('-') + '">' + date + '</div>');
						}
					}

					h.push('<table class="thank_history_' + line.id + ' thank_history_line ' + (!that.disabled && participate_in_correspondence
							? 'goto_messages'
							: '') + ' ' + host_attributes + '" style="width:100%; ' + (host.hasClass('thanks_data_history_host')
							? ''
							: '') + '"><tr>' +
							(host.hasClass('thanks_data_history_host')
									? '<td><img src="images/' +
									(line.favourite == 1
											? ''
											: 'empty_') +
									'star.png" class="cp favourite favourite_' + line.id + ' data_op_' + date.split('.').join('-') + '" title="' + line.id + '" alt="' + line.favourite + '"/></td>'
									: '') +
							'<td class="p5 w30"><div class="userpic_in_history_list_host">' +
							'<img class="' + left_class + '" src="' + left_picture + '" ' + left_image_attributes + ' style="width:30px; height:30px;"/>' +
							'</div></td><td class="p5 w30 ac">' +
							'<img src="' + A.baseURL() + 'images/' + central_picture + '" class="thank_type_raw" alt/>' +
							'</td><td class="p5 w30"><div class="userpic_in_history_list_host">' +
							'<img class="' + right_class + '" src="' + right_picture + '" ' + right_image_attributes + ' style="width:30px; height:30px;"/>' +
							'</div></td><td class="p5">' + that.getCaption(line, nodate, data.filter === 'new' || data.filter === 'all'
									? true
									: false, outputNames
									? af
									: false, outputNames
									? false
									: true) +
							'</td>' + (host.hasClass('header_thanks_list') || host.hasClass('news') /*|| host.hasClass('thanks_data_history_host')*/
									? (
											'<td style="width:100px;"><div style="float:right; width:100px; height:40px; top:0px;" class="pr">' +
											'<div class="ac pr ' + (data.pressed && data.pressed['thank_78_back_' + line.sender_uid + '_' + line.id]
													? 'thank_back_pressed2'
													: 'cp thank_back_but2') + ' thank_78_back_' + line.sender_uid + '_' + line.id + '" title="' +
											T.out('thank_for_jh') + ' ' + that.getCaptionJH(line) + '"><img src="images/saythankyou_' +
											(data.pressed && data.pressed['thank_78_back_' + line.sender_uid + '_' + line.id]
													? 'fill'
													: 'transparent') +
											'_' + T.locale + '.png" style="width:100px;"/></div>' +
											'</div></td>')
									: (host.hasClass('thanks_data_history_host')
											? (data.pressed && data.pressed['thank_77_back_' + line.id]
													? '<td><div style="width:100px; height:20px; top:0px; float:right;" class="pr">' +
													'<div class="ac pr thank_back_but_pressed thank_77_back_' + line.id + '"><img src="images/saythankyou_fill_' + T.locale + '.png" style="width:100px;"/></div>' +
													'</div></td>'
													: '<td><div style="width:100px; height:20px; top:0px; float:right;" class="pr">' +
													'<div class="ac pr cp thank_back_but thank_77_back_' + line.id + '"><img src="images/saythankyou_transparent_' + T.locale + '.png" style="width:100px;"/></div>' +
													'</div></td>')
											: '')) +
							'</tr></table>');
				}
			} //of for


			if ($('.thanks_hidable_host', host).length === 0) {
				host.html('<div class="thanks_hidable_host pr">' + h.join('') + '</div>');

				$('.userpic', $('.userpic_in_history_list_host')).unbind('error').error(function() {
					$(this).attr('src', A.baseURL() + 'images/question.png');
				});

			} else {
				$('.thanks_hidable_host', host).html(h.join(''));
			}

			if (host.hasClass('thanks_data_history_host')) {
				that.favourites();

				B.click($('.favourite'), function(obj) {
					var id = B.getId(obj, 'favourite_');
					that.favourite(id);
				});

				$('.favourite').unbind('mouseover').mouseover(function() {
					$(this).attr({
						src: 'images/star.png'
					});
				}).unbind('mouseout').mouseout(function() {
					var id = B.getId($(this), 'favourite_');
					if ($(this).attr('alt') != 1) {
						$(this).attr({
							src: 'images/empty_star.png'
						});
					}
				});

			}

			B.click($('.thank_back_but'), function(obj) {
				var id0 = B.getId(obj, 'thank_77_back_');
				for (var i in data) {
					var record = data[i];
					if (record.id && record.id == id0) {

						B.post({
							script: 'index.php?r=user/press',
							//r: 'user/press',
							Pressed: 'add',
							selector: 'thank_77_back_' + id0
						});
						
						obj.removeClass('thank_back_but').addClass('thank_back_but_pressed').unbind('click');
						$('img', obj).attr({
							src : 'images/saythankyou_fill_' + T.locale +'.png'
						});

						Dialog.hide();
						Menu.switchPage('send', function() {
							if (record.place) {
								Send.select_place({
									uid : record.sender_uid,
									net : record.sender_uid,
									title: record.place
								});
							} else {
								Send.select(record.sender_uid);
							}
						}, 'redrawMenu');
						break;
					}
				}
			});

			$('.thank_back_but2').each(function() {
				B.click($(this), function(obj) {
					var id0 = B.getID(obj, 'thank_78_back_');

					var id = id0.split('_')[0];

					var message_id = id0.split('_')[1];

					B.post({
						script: 'index.php?r=user/press',
						//r: 'user/press',
						Pressed: 'add',
						selector: 'thank_78_back_' + id0
					});

					obj.removeClass('thank_back_but2').removeClass('cp').addClass('thank_back_pressed2').unbind('mousedown');
					$('img', obj).attr('src', 'images/saythankyou_fill_' + T.locale + '.png');

					//TODO: for places
					Send.record({
						status: '', //if empty $('.send_to').val() -> multiply
						receiver_uid: id,
						receiver_net: 'fb',
						place: false,
						sendAfterRequest: false,
						title: obj.attr('title')
					});

					var sender_name = $('.goto_send_thank', $('.thank_history_' + message_id)).html();

					Dialog.show({
						title: T.out('you_thanks_jh') + ' ' + sender_name + ' ' + obj.attr('title')
					});

				});
			});

			//TODO: check if we are sender or receiver to prevent sending

			/* controller
			 $('.goto_messages', host).unbind('mousedown').mousedown(function() {
			 var id = $(this).attr('class').split(' ')[0].split('thank_history_')[1];
			 that.select(id);
			 });
			 */

			if (outputNames) {

				//select friend
				$('.goto_send_thank', host).unbind('mousedown').mousedown(function() {
					Send.selectByName($(this).html());
					if (Menu.currentPage != 'send') {
						Menu.switchPage('send', false, 'redrawMenu');
					}
				});

				$('.goto_send_image', host).unbind('mousedown').mousedown(function() {
					Send.selectByName($(this).attr('title'));
					if (Menu.currentPage != 'send') {
						Menu.switchPage('send', false, 'redrawMenu');
					}
				});

		
			}

			var parent = host.parent();

			var remain_for_timer = (new Date()).getTime() - Header.start;

			if (remain_for_timer <= 0) {
				$('.header_thanks_list', parent).slideDown();
				$('.info_title', parent).slideUp();
			} else {
				setTimeout(function() {
					$('.header_thanks_list', parent).slideDown();
					$('.info_title', parent).slideUp();
				}, 5000 - remain_for_timer);
			}

		} else { //if there are no any thanks
			var parent = host.parent();
			host.html('');

			if (host.hasClass('thanks_data_history_host')) {
				that.favourites();
			}

			$('.header_thanks_list', parent).slideUp();
			$('.info_title', parent).slideDown();

		}

	},
	favouriteFilter: 0,
	read: function(id) { //mark thank as it is already read
		if ($.isArray(id)) {
			B.post({
				script: 'index.php?r=user/read',
				//r: 'user/read',
				ids: id,
				ok: function(response) {

				},
				no: function(response) {
					console.error('read', response);
				}
			});
		} else {
			B.post({
				script: 'index.php?r=user/read',
				//r: 'user/read',
				id: id,
				ok: function(response) {

				},
				no: function(response) {
					console.error('read', response);
				}
			});
		}
	},
	favourite: function(id) { //toggle favourite
		var that = this;
		$('.favourite_' + id).css({
			opacity: 0.5,
			cursor: 'default'
		});
		B.post({
			script: 'index.php?r=user/favourite',
			//r: 'user/favourite',
			thank_id: id,
			Favourite: 'set',
			ok: function(response) {

				if (response.favourite) {
					$('.favourite_' + id).attr({
						src: 'images/star.png',
						alt: 1
					}).css({
						opacity: 1,
						cursor: 'pointer'
					});
				} else {

					if ($('.favourite_selector').attr('alt') * 1 === 1) {
						$('.thank_history_' + id).hide();

						var data = B.getId($('.favourite_' + id), 'data_op_');

						var has_visible = false;
						$('.data_op_' + data).each(function() {
							if ($(this).is(':visible')) {
								has_visible = true;
							}
						});

						if (has_visible) {
							$('.date_p_' + data).show();
						} else {
							$('.date_p_' + data).hide();
						}

					}

					$('.favourite_' + id).attr({
						src: 'images/empty_star.png',
						alt: 0
					}).css({
						opacity: 1,
						cursor: 'pointer'
					});
				}
			}
		});
	},
	favourites: function() {
		var that = this;

		if ($('.favourite_selector').length == 0) {
			$('.thanks_data_history_host').prepend('<div><img src="images/' + (that.favouriteFilter
					? ''
					: 'empty_') + 'star.png" style="margin-left:5px; width:20px;" class="cp favourite_selector" alt="' + that.favouriteFilter + '"/></div>');
		}

		B.click($('.favourite_selector'), function(obj) {
			if (obj.attr('alt') == 0) {
				that.favouriteFilter = 1;
				obj.attr({
					alt: 1,
					src: 'images/star.png'
				});
			} else {
				that.favouriteFilter = 0;
				obj.attr({
					alt: 0,
					src: 'images/empty_star.png'
				});
			}

			Thanks.get({
				host: $('.thanks_data_history_host'),
				name: 'thanks_data_history_host',
				filter: {
					get: 'me',
					start: 0,
					page: 7
				},
				callback: function(data) {
					var filter = data.affairs;
					$('.amount_indicator').html(data.my * 1 + data.i * 1);
				}
			});

		});

	}/*,
	 showOnlyFavourites: function() {
	 var that = this;
	 if (!that.favData) {
	 that.showFavouritesAll();
	 } else {
	 $('.date_p').hide();
	 for (var key in that.favData) {
	 if (that.favData[key].status * 1) {
	 $('.thank_history_' + key).show();
	 var data = B.getId($('.favourite_' + key), 'data_op_');
	 $('.date_p_' + data).show();
	 } else {
	 $('.thank_history_' + key).hide();
	 }
	 }
	 }
	 },
	 showFavouritesAll: function() {
	 $('.date_p').show();
	 $('.thank_history_line').show();
	 }
	 */
};