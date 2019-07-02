var Send = {
	ready: false,
	prepareMessage: function(status) {
		var preambula = Base.convertName(User.data.name) + ' ' + T.out('thank_for_070');
		var epilogue = T.out('invite_epilogue');
		var mess = preambula + ($('.send_title', $('.send_form')).val()
				? ' ' + T.out('for07') + ' ' + $('.send_title', $('.send_form')).val()
				: '') + ($('.send_message').val()
				? ' ' + T.out('and_message_12') + ' «' + $('.send_message').val() + '»'
				: '') + (status === ''
				? ''
				: ' ' + epilogue);
		return mess;
	},
	send: function(always_notify, no_post) {
		var that = this;
		if (that.control() && ($('.send_to').val() || (that.R && $('.find_what.input').val()))) {

			if (!always_notify) { //never triggered
				$('.random_friends').slideUp();
			}

			//if uid looks like integer - user present if not - 
			//we send his name as status and this means we try to invite him
			var status = that.getThankStatus();

			var name = $('.send_name').val();

			var post_timeline = CheckBoxes.list['send_request_checkbox'].status;

			if (status !== 'place' && (always_notify || (status !== '' && status !== 'place') || (status === '' && CheckBoxes.list['send_request_checkbox'].status))) {

				if ($('.send_to').val() && $('.send_to').val().indexOf('link_') === -1) {
					Facebook.sendRequest({
						message: that.prepareMessage(status),
						to: $('.send_to').val(),
						return_to: Application.appSourcePath + '/?thank_status=' + status +
								'&thank_title=' + $('.send_title', $('.send_form')).val() +
								'&thank_message=' + $('.send_message').val() +
								'&thank_name=' + $('.send_to').attr('title'),
						callback: function(response) {

							//to facebook user
							Send.record({
								status: status, //if empty $('.send_to').val() -> multiply
								receiver_uid: response.to[0],
								receiver_net: 'fb',
								sendAfterRequest: true
							});

							var post_text = User.data.first_name + ' ' + User.data.last_name + ' ' + T.out('you_just_thanked2') + ' ' + name;

							if (post_timeline && !no_post) {
								
								console.log('position3:', post_text);
								
								Facebook.post(post_text,
										//second parameter is redirect URI id Javascript SDK fails on iOS system
										Application.appSourcePath + '/?thank_post=' + encodeURIComponent(post_text));
							}

						}
					});

				}

			} else { //place e t.c.

				//always place? check it
				that.record({
					status: status
				});

				if (always_notify) {
					Dialog.hide(function() {
						Dialog.show({
							title: '<div style="margin-bottom:-10px;">' + T.out('your_thank_accepted19') + '</div>',
							message: ''
						});
					});
				}

				var post_text = User.data.first_name + ' ' + User.data.last_name + ' ' + T.out('you_just_thanked2') + ' ' + $('.find_what').attr('title');

				if (post_timeline && !no_post) {
					
					//console.log('position2:', post_text);
					
					Facebook.post(post_text,
							//second parameter is redirect URI id Javascript SDK fails on iOS system
							Application.appSourcePath + '/?thank_post=' + encodeURIComponent(post_text));
				}

			}
		} else {

			Dialog.show({
				title: '',
				message: T.out('write_the_reason2'),
				noexit: 1,
				buttons: [
					{
						title: 'OK',
						action: function() {
							Dialog.hide();
						},
						class: ['round', 'transparent_button'],
						css: {
							width: '40px',
							height: '33px',
							'padding-top': '7px'
						}
					}
				]
			});
		}
	},
	randomFriends: function() {
		Base.get({
			script: 'index.php?r=user/random-friends',
			//r: 'user/randomFriends',
			ok: function(d) {
				if (d.html) {
					Base.wait(function() {
						var a = $('.send').is(':visible')
								? true
								: false;
						//console.log('visible:',a);
						return a;
					}, function() {
						$('.random_friends').html(d.html).slideDown();
					});
				} else {
					$('.random_friends').hide();
				}
			}
		});

	},
	init: function() {

		var that = this;
		that.ready = 1;

		Buttons.place({
			host: $('.post_host'),
			title: '<img src="images/saythankyou_fill_' + T.locale + '.png" />',
			id: 'send_thank',
			class: '',
			outer: {
				css: {
					'margin-right': '0px',
					'margin-top': '0px'
				}
			},
			addPointIfNot: function(text) {
				//TODO
			},
			action: function() {
				that.send('with notify');
			}
		});



		Menu.getHTML({//try to get switcher code if it is not already loaded
			file: 'switcher.php',
			selector: '.switcher_flag',
			append: true,
			callback: function(page) {
				Switchers.place({
					host: $('.switch_to_others_host'),
					id: 'type_of_find',
					cls: 'switcher',
					unselected: 'switch_unselected',
					last: 'switch_unselected_last',
					selected: 'switch_selected',
					cur: 0,
					but: [
						{
							title: T.out('to_friends'),
							action: function(id) {
								$('.find_friends_send').slideDown();
								$('.find_communities_send').slideUp();
								that.reset('without_thanks');
							}
						}, {
							title: T.out('switch_to_others07'),
							action: function(id) {
								$('.find_friends_send').slideUp();
								$('.find_communities_send').slideDown();
								that.reset('without_thanks');
							}
						}
					]
				});

			}
		});


		CheckBoxes.place({
			host: $('.post_timeline_host'),
			id: 'send_request_checkbox',
			title: T.out('post_timeline'),
			css: {
				'margin-left': '-5px',
				'margin-top': '-5px'
			},
			action: function(status) {

			}
		});


		Switchers.place({
			host: $('.send_spoiler_host'),
			id: 'type_of_send',
			cls: 'switcher',
			unselected: 'switch_unselected',
			last: 'switch_unselected_last',
			selected: 'switch_selected',
			cur: 0,
			but: [
				{
					title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_social_' + T.locale + '.png" alt class="round_but_svg"/></div>',
					action: function(id) {
						$('.all_sendSpoilers').hide();
						$('.social_sendSpoiler').show();
						Menu.switchPage('send', function() {

						});
					}
				}, {
					title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_email_' + T.locale + '.png" alt class="round_but_svg"/></div>',
					action: function(id) {
						$('.all_sendSpoilers').hide();
						$('.email_sendSpoiler').show(function() {

						});

//TODO: Base.wait
						Friends.list(User.data.user, function(html, additionalNames) {
							$('.friends_emails').html(html);
							User.loadNames(additionalNames);
							Friends.addEmails(function() {

								/*
								 $('.friend_square', $('.friends_emails')).css({
								 opacity : 0.7,
								 cursor: 'default'
								 }).unbind('mousedown');
								 */

								var any_visible = false;

								$('.friend_square', $('.friends_emails')).each(function(obj) {
									var id = B.getID($(this), 'friend_id_');
									if (Friends.data && Friends.data.friends_emails && Friends.data.friends_emails[id]) {

									} else {
										$(this).parent().hide();
									}
								});

								Friends.getUsedEmails();

								//friend_id_865017106842153

								$('.drop_list_host_email').show();
							});
						}, function(id, obj) {
							//console.log(id, Friends.data.friends_emails[id]);
							$('.thankyouemail').val(Friends.data.friends_emails[id].email);
							that.hide_drop_emails_list();
						}, false, $('.friends_emails'));


						B.click($('.drop_list_email'), function() {
							if ($('.send_form_friends_emails_host').is(':visible')) {
								that.hide_drop_emails_list();
							} else {
								that.show_drop_emails_list();
							}
						});

					}
				},
				{
					title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_sms_' + T.locale + '.png" alt class="round_but_svg"/></div>',
					action: function(id) {
						$('.all_sendSpoilers').hide();
						$('.sms_sendSpoiler').show(function() {

						});
					}
				},
				{
					title: '<div class="round_but_svg_host"><img src="' + A.baseURL() + 'images/swt_link_' + T.locale + '.png" alt class="round_but_svg"/></div>',
					action: function(id) {
						$('.all_sendSpoilers').hide();
						$('.link_sendSpoiler').show(function() {
							$('#thank_link_code').focus().select();
						});
					}
				}
			]
		});

		//load random_friend data
		//this.randomFriends();

		//drop list handler TODO: remove drop list to additional class

		var that = this;

		$('.drop_list').unbind('mousedown').mousedown(function() {
			Base.press($(this));
			if ($('.send_form_friends_host').is(':visible')) {
				that.hide_drop_list();
				$('.drop_list').css({
					'padding-top': '2px',
					height: '18px'
				});
			} else {
				$('.drop_list').css({
					'padding-top': '0px',
					height: '20px'
				});
				that.show_drop_list();
				$('.send_to').val('');
				$('.receiver_uid').val('');
				$('.receiver_net').val('');
				$('.send_to').attr('title', '');
				$('.send_name').css({
					color: 'silver'
				});
				$('.send_name').val('');
				if (window.Friends) {
					Friends.reset($('.send_form_friends_host'));
				}
			}
		});

		$('.send_name').unbind('change').change(function() {
			that.filter();
		}).unbind('keyup').keyup(function() {
			that.filter();
		}).unbind('mouseup').mouseup(function() {
			that.filter();
		});

		$('.other_input').unbind('change').change(function() {
			that.control($(this).attr('name'));
		}).unbind('keyup').keyup(function(data) {
			that.control($(this).attr('name'));

			//close autocomplete on enter key
			if ($(this).attr('name') === 'send_title' && data.keyCode * 1 === 13 && $(this).val()) {
				$('.send_title').autocomplete('close');
			}

		}).unbind('mouseup').mouseup(function() {
			that.control($(this).attr('name'));
		});

		this.reset('without_friends'); //first entrance

		$('.send_title').unbind('keydown');

		//add autocompleter

		$('.send_title').autocomplete({
			minLength: 2,
			source: function(request, response) {
				Base.get({
					script: 'index.php?r=user/search',
					//r: 'user/search',
					term: request.term,
					ok: function(data) {
						if (data.finded && data.finded.length > 0) {
							response($.merge($.merge(data.finded, ['']), data.popular));
						} else {
							response(data.popular);
						}
					}
				});
			}
		});

		this.$ = $('.send_form_host');
	},
	getThankStatus: function() {

		if (this.R) {
			return 'place';
		}

		return $('.send_to').val() * 1
				? (window.Switchers && Switchers.list['type_of_find'] && Switchers.list['type_of_find'].cur * 1 === 1
						? 'place'
						: '')
				: $('.send_name').val();
	},
	selectByName: function(name) {
		var normalized = name.toUpperCase().split(' ').join('_'); //get the name in upecase without space symbols
		//var finded = $('img[alt=' + normalized + ']');

		//console.log(name, $('.friends',$('.send_form_friends_host')).html());

		//console.log(name);

		var finded = $('[alt*=' + normalized + ']', $('.friends', $('.send_form_friends_host')));
		if (finded.length) {
			var friend_id = finded.attr('class').split('friend_id_')[1];
			this.select(friend_id);
			return true;
		} else {
			return false;
		}
	},
	select: function(friend_id) {

		this.R = false;

		Send.reset('without_thanks');

		if (window.Switchers && Switchers.list['type_of_find']) {
			Switchers.list['type_of_find'].switch(0);
		}

		var obj = $('.friend_id_' + friend_id);

		if (!obj.length) { //object not found so need to show all friends
			Send.show_drop_list();
			return;
		}

		$('.send_to').val(friend_id);
		$('.send_name').val(obj.attr('title'));
		$('.send_to').attr('title', obj.attr('title'));
		$('.receiver_net').val('');
		$('.receiver_uid').val('');
		var a = obj.attr('src').split('/');
		var picture = a[a.length - 1];
		if (picture == 'picture') {
			picture = obj.attr('src') + '?width=80&height=80';
			var standart_picture = true;

			$('.userpic_vertical', $('.send')).css(standart_picture
					? {
						width: '80px',
						height: '80px',
						display: 'inline-block',
						'margin-top': '8px'
					}
			: {
				width: 'auto',
				height: '120px',
				display: 'inline-block'
			}).attr({
				src: picture
			}).unbind('error').error(function() {
				$(this).attr('src', obj.attr('src'));
			});

		} else {
			//picture = $('.large_photo_of_' + friend_id).attr('src');
			picture = $('.large_photo_of_' + friend_id).html();
			$('.userpic_vertical', $('.send')).parent().html('<img class="userpic_vertical receiver" src="' + picture + '" width="80" height="80" alt="">');
		}

		$('.receiver_host').show();
		$('.question_img').hide();
		Send.hide_drop_list();
		Send.control();
	},
	/*
	 * 
	 * @param {type} input
	 * 
	 * place_id, title, image, post_title
	 * 
	 * title
	 * post_title
	 * net
	 * id
	 * image
	 * 
	 * 
	 * @returns {undefined}
	 */
	select_place: function(input) {

		Send.reset('without_thanks');

		this.R = {
			net: input.net,
			uid: input.uid,
			name: input.title
		};

		console.log('R', this.R);
		
		console.log(input);

		if (window.Switchers && Switchers.list['type_of_find']) {
			Switchers.list['type_of_find'].switch(1);
		}

		$('.find_what').val(input.title).attr('title', input.title);
		$('.find_what').css({
			color: 'rgb(3, 55, 127);'
		});

		$('.send_to').val('');
		$('.send_name').val(input.title);
		$('.send_to').attr('title', input.title);

		$('.userpic_vertical', $('.send')).css({
			width: '80px',
			height: '80px',
			display: 'inline-block',
			'margin-top': '8px'
		}).attr({
			src: input.net === 'link'
					? A.baseURL() + 'images/question.png'
					: (input.net.indexOf('link_') !== -1
							? (input.image
									? input.image
									: A.baseURL() + 'images/question.png')
							: '//graph.facebook.com/' + input.uid + '/picture?width=80&height=80')
		});
		$('.receiver_host').show();
		$('.question_img').hide();

		Send.control();
	},
	show_drop_list: function() {
		$('.send_form_friends_host').slideDown(function() {
			$('.drop_list').html('▲');
		});

	},
	hide_drop_list: function() {
		$('.send_form_friends_host').slideUp(function() {
			$('.drop_list').html('▼');
		});
	},
	show_drop_emails_list: function() {
		$('.send_form_friends_emails_host').slideDown(function() {
			$('.drop_list_email').html('▲');
		});

	},
	hide_drop_emails_list: function() {
		$('.send_form_friends_emails_host').slideUp(function() {
			$('.drop_list_email').html('▼');
		});
	},
	show_drop_sms_list: function() {
		$('.send_form_sms_host').slideDown(function() {
			$('.drop_list_sms').html('▲');
		});

	},
	hide_drop_sms_list: function() {
		$('.send_form_sms_host').slideUp(function() {
			$('.drop_list_sms').html('▼');
		});
	},
	reset: function(without_thanks) {

		$('.input', $('.send')).val('');
		$('.send_to').val('');
		$('.send_to').attr('title', '');
		$('.send_name').css({
			color: 'silver'
		});
		$('.send_title').val('');
		$('.send_message').val('');
		$('.receiver_host').hide();
		$('.question_img').show();
		$('.find_what').val('');

		if (window.Search) {
			Search.clean($('.searchmain'));
		}

		if (CheckBoxes && CheckBoxes.list['send_request_checkbox']) {
			CheckBoxes.list['send_request_checkbox'].setStatus(1);
		}

		if (window.Thanks && !without_thanks) { //update main thanks list
			Thanks.get({
				host: $('.header_thanks_list'),
				name: 'header_thanks_list',
				filter: {
					get: 'new',
					start: 0,
					page: 3
				}
			});
		}

	},
	//filter friends function
	filter: function() {
		if (window.Friends) {
			if (!$('.send_form_friends_host').is(':visible')) {
				this.show_drop_list();
			}
			Friends.filter($('.send_name').val(), $('.send_form_friends_host'));
		}
		this.control();
	},
	control: function(name) {

		var that = this;

		var status = that.getThankStatus();

		if (name && name !== 'send_name') { //hide friends drop list on check any field
			this.hide_drop_list();
		}

		if ($('.send_to').val()) {
			if ($('.send_to').attr('title') != $('.send_name').val() && status != 'place') {//name has changed

				//cancel selected user
				$('.send_to').val('');
				$('.send_name').css({
					color: 'silver'
				});
				$('.receiver_host').hide();
				$('.question_img').show();
			} else {
				$('.send_name').css({
					color: 'rgb(3, 55, 127);'
				});
			}
		} else {

			if (status === 'place') {
				$('.send_name').css({
					color: 'rgb(3, 55, 127);'
				});
			} else {
				$('.send_name').css({
					color: 'silver'
				});
			}
		}

		if ($('.send_title').val()) {
			$('.send_title').css({
				color: 'rgb(3, 55, 127);'
			});
		} else {
			$('.send_title').css({
				color: 'silver'
			});
		}

		if ($('.send_message').val()) {
			$('.send_message').css({
				color: 'rgb(3, 55, 127);'
			});
		} else {
			$('.send_message').css({
				color: 'silver'
			});
		}

		if (status === '') {
			$('.post_timeline_host').show();
		}

		$('.button_host_send_thank').css({
			opacity: 1
		});
		return true;

	},
	actionAfterRecord: function(response) {
		//update header
		Thanks.get({
			host: $('.header_thanks_list'),
			name: 'header_thanks_list',
			filter: {
				get: 'new',
				start: 0,
				page: 3
			},
			readyResponse: response.all
		});
	},
	needToRecord: 0,
	/*
	 * net
	 * uid
	 * for
	 * name
	 */
	R: false, //record query for places
	record: function(input) {

		var that = this;
        console.log(input);

		if (input.status === 'place') {

			if (!that.R) {
				console.error('Nothing to send');
				return false;
			}

			Base.get({
				script: 'index.php',
				r: 'user/send',
				status: 'place',
				net: that.R.net,
				uid: that.R.uid,
				'for': $('.input.send_title').val(),
				name: that.R.name,
				ok: function(response) {
					that.needToRecord -= 1;
					if (that.needToRecord == 0) {
						that.actionAfterRecord(response);
					}

				}
			});

		} else {

			Base.get({//to prevent blocking
				script: 'index.php?r=user/send',
				//r: 'user/send',
				status: input.status,
				sender_uid: User.data.id, //no need this
				sender_net: 'fb', //no need this
				receiver_uid: input.receiver_uid,
				receiver_net: input.receiver_net,
				name: input.name
						? input.name
						: $('.send_to').attr('title'),
				title: input.title
						? input.title
						: $('.send_title', $('.send_form')).val(),
				place: input.status == 'place'
						? $('.send_to').attr('title')
						: false,
				message: input.message
						? input.message
						: ($('.send_message').val()
								? $('.send_message').val()
								: false),
				ok: function(response) {
					that.needToRecord -= 1;
					if (that.needToRecord == 0) {
						that.actionAfterRecord(response);
					}

				},
				no: function(response) {
					console.error('no', response);
				}
			});
		}

		that.reset('without_thanks');
	},
	showThankLink: function() {
		B.getHTML({
			file: 'backlink.php',
			selector: '#thank_link_code',
			once: 0,
			host: $('.thanklinkhost'),
			callback: function(html) {
				setTimeout(function() {
					$('#thank_link_code').focus().select();
				}, 500);
			}
		});
	},
	sendEmailSmsController: function() {
		var that = this;
		Buttons.place({
			host: $('.send_email_thankyoubuttonhost'),
			title: '<img src="images/saythankyou_fill_' + T.locale + '.png" />',
			id: 'send_thank_email',
			class: '',
			outer: {
				css: {
					'margin-right': '0px',
					'margin-top': '0px'
				}
			},
			action: function() {
				that.sendEmail();
			}
		});

		Buttons.place({
			host: $('.send_sms_thankyoubuttonhost'),
			title: '<img src="images/saythankyou_fill_' + T.locale + '.png" />',
			id: 'send_thank_sms',
			class: '',
			outer: {
				css: {
					'margin-right': '0px',
					'margin-top': '0px'
				}
			},
			action: function() {
				that.sendSMS();
			}
		});

		if (!$('.saved_sms_host').html()) {
			Friends.getUsedPhones();
		}

	},
	sendEmail: function() {
		var that = this;
		B.post({
			script: 'index.php?r=user/thankmail',
			//r: 'user/thankmail',
			email: $('.thankyouemail').val(),
			forwhat: $('.send_title', $('.email_sendSpoiler')).val(),
			ok: function(response) {
				Dialog.show({
					title: response.title,
					message: response.message
				});
				$('.thankyouemail').val('');
			},
			no: function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.sendEmail();
					});
				}
			}
		});
	},
	sendSMS: function() {
		var that = this;
		B.post({
			script: 'index.php?r=user/thanksms',
			//r: 'user/thanksms',
			phone: $('.thankyousms').val(),
			forwhat: $('.send_title', $('.sms_sendSpoiler')).val(),
			ok: function(response) {
				Dialog.show({
					title: response.title,
					message: response.message
				});
				$('.thankyousms').val('');
			},
			no: function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.sendSMS();
					});
				}
			}
		});
	}

};