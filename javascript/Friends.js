var Friends = {
	data: false,
	onSelect: false,
	image_load_status: {},
	list: function(data, onInit, onClick, filter, host) {

		var that = this;
		if (data) {
			this.data = data;
		} else {
			var data = this.data;
		}

		var getAdditionalNames = [];

		if (!host) {
			var host = $('.friends');
		}

		var h = [];
		if (host.hasClass('friends')) {

			if (data.invitable_friends) {

				that.image_load_status = {}; //обнуляем статус загрузки изображений

				for (var i in data.invitable_friends) {
					var friend = data.invitable_friends[i];
					if (!filter || (filter && $.inArray(friend.id + '', filter) !== -1)) {
						var name = friend.name.split(' ').join('_').toUpperCase();
						that.image_load_status[friend.id] = {
							small: friend.picture.data.url_small,
							large: friend.picture.data.url,
							friend: friend
						};
						h.push('<div class="friend_square_host pr friend_square_host_' + friend.id + '">' +
								'<img src="' + A.baseURL() + 'images/question.png" alt="" class="pa friend_preloader_' + friend.id + '" style="z-index:1; width:50px; height:50px;"/>' +
								'<div class="large_photo_of_' + friend.id + '" style="display:none;">' + friend.picture.data.url + '</div>' +
								//'<img class="large_photo large_photo_of_' + friend.id + '" style="display:none;" src="' + A.baseURL() + 'images/question.png" alt/>' +
								'</div>');
						getAdditionalNames.push({
							name: friend.name,
							id: friend.id
						});
					}
				}
			}
		}

		if (data.invited_friends) {
			for (var i in data.invited_friends) {
				var friend = data.invited_friends[i];
				if (!filter || (filter && $.inArray(friend.id + '', filter) !== -1)) {
					var name = friend.name.split(' ').join('_').toUpperCase();
					//for (var k = 0; k < 10; k++) {
					h.push('<div class="friend_square_host pr"><img src="images/question.png" alt class="pa friend_preloader_' + friend.id + '" style="z-index:1; width:50px; height:50px;"/><img src="https://graph.facebook.com/' + friend.id + '/picture" class="friend_square pr cp friend_id_' + friend.id + '" alt="' + name + '" title="' + Base.convertName(friend.name) + '" style="visibility:hidden;"/>' + (host.hasClass('friends')
							? '<div class="already_invited_mark">✓</div>'
							: '') + '</div>');
					//}
					if (host.hasClass('friends')) {
						getAdditionalNames.push({
							name: friend.name,
							id: friend.id
						});
					}
				}
			}
		}

		if (onInit) {
			onInit(h.join(''), getAdditionalNames);
		}

		$('.friend_square', host).unbind('mousedown').mousedown(function() {
			Base.press($(this));
			var friend_id = $(this).attr('class').split('friend_id_')[1];
			//Send.select(friend_id, $(this));
			onClick(friend_id, $(this));
		});


		$('.friend_square').unbind('load').load(function() {
			var id = $(this).attr('class').split('friend_id_')[1];
			$('.friend_preloader_' + id).hide();
			$(this).css({
				visibility: 'visible'
			});
		});
	},
	fillImages: function(host) {
		var that = this;
		for (var key in that.image_load_status) {
			var record = that.image_load_status[key];
			var friend = that.image_load_status[key].friend;
			var name = friend.name.split(' ').join('_').toUpperCase();
			$('.friend_square_host_' + key, host).html(
					'<img src="' + record.small + '" alt="' + name + '" class="friend_square pr cp friend_id_' + friend.id + '" title="' + Base.convertName(friend.name) + '"/>' +
					'<div class="large_photo_of_' + friend.id + '" style="display:none;">' + record.large + '</div>'
					/*+
					 '<img class="large_photo large_photo_of_' + friend.id + '" style="display:none;" src="'+record.large+'" alt/>'*/);
			that.image_load_status[key] = null;
			that.image_load_status
		}
	},
	searchOnFacebook: function(additionalNames, finded) {	
		for (var i in additionalNames) {
			var friend = additionalNames[i];

			if (finded && finded[friend.name] && finded[friend.name].name) {//cashed names
				var id = friend.id;
				var name = finded[friend.name].name;
				var alt = $('.friend_id_' + id).attr('alt');
				var title = $('.friend_id_' + id).attr('title');
				$('.friend_id_' + id).attr('alt', alt + '(' + name.split(' ').join('_').toUpperCase() + ')');
				$('.friend_id_' + id).attr('title', title + ' (' + name + ') ');
			} else {
				Friends.getAdditionalName({
					name: friend.name,
					id: friend.id,
					limit: 5,
					callback: function(id, name) {		
						var alt = $('.friend_id_' + id).attr('alt');
						var title = $('.friend_id_' + id).attr('title');
						$('.friend_id_' + id).attr('alt', alt + '(' + name.split(' ').join('_').toUpperCase() + ')');
						$('.friend_id_' + id).attr('title', title + ' (' + name + ') ');
					}
				});
			}
		}
	},
	writeName: function(input) {
		//need to check if data are ok
        return true;
		Base.get({//get to prevent blocking on parallel requests
			script: 'index.php?r=user/names',
			//r: 'user/names',
			Names: 'write',
			uid: input.uid,
			name: input.name,
			eng_name: input.eng_name
		});
	},
	/**
	 * 
	 * @param {type} input
	 * @returns {undefined}
	 * 
	 * input = {
	 *	 name
	 *	 id
	 *	 limit
	 * }
	 */
	search_run: false,
	getAdditionalName: function(input) {
		
		var that = this;
		//check if selected user already has additional name

		//this is to prevent storm traffic if there are a lot of friends
		if (that.search_run) {//we don`t start script while previous has not returned data
			setTimeout(function() {
				that.getAdditionalName(input);
			}, 100);
			return;
		}

		//try to get additional information about friends
		that.search_run = true;
		FB.api('/search', {
			q: input.name,
			type: 'user',
			limit: input.limit,
			locale: User.data.locale
		}, function(response) {
			//that.search_run = false;
			//console.log(response);

			setTimeout(function() { //to prevent strom traffic
				that.search_run = false;
			}, 100);

			if (response.data && response.data.length > 0) {

				if (isNaN(input.id)) {
					var statistics = false;
				}

				for (var i in response.data) {
					if (isNaN(input.id)) {
						var name_norm = response.data[i].name.split(' ').join('_');

						if (input.name.search(/[А-яЁё]/) !== -1 && input.name != response.data[i].name) {
							continue;
						}

						if (!statistics) {
							statistics = {};
						}

						if (statistics[name_norm]) {
							statistics[name_norm].count += 1;
						} else {
							statistics[name_norm] = {
								name: response.data[i].name,
								count: response.data[i].name.search(/[А-яЁё]/) !== -1 && input.name == response.data[i].name
										? 10
										: (response.data[i].name.search(/[А-яЁё]/) === -1 //латинское имя
												? 1
												: (
														response.data[i].name != input.name //имена на русском должны совпадать
														? 1
														: 0))
							}
						}
						//input.callback(input.id, response.data[i].name);
					} else {
						if (response.data[i].id == input.id) {
							that.writeName({
								uid: response.data[i].id,
								eng_name: input.name,
								name: response.data[i].name
							});
							input.callback(input.id, response.data[i].name);
							return;
						}
					}
				}

				if (statistics) {
					//console.log(statistics);
					var max = 0;
					var name = false;
					for (var n in statistics) {
						if (statistics[n].count >= max) {
							max = statistics[n].count;
							name = statistics[n].name;
						}
					}
					that.writeName({
						uid: response.data[i].id,
						eng_name: input.name,
						name: name
					});
					input.callback(input.id, name);
					return false;
				}

				if (response.data.length < input.limit) { //limit exceed
					return false;
				} else {//call next chain
					var input2 = input;
					input2.limit = input.limit * 2;
					that.getAdditionalName(input2); //call once again
					return false;
				}

			} else {
				//console.log(input.name + ' not localized nothing');
				return false; //nothing has been founded
			}
		});
	},
	reset: function(host) {
		$('.friend_square_host', host).show();
	},
	filter: function(filter, host) { //host - DOM area where we use filter
		var that = this;
		if (filter) {

			if (filter.length <= 2) {
				var f = filter.toUpperCase();
				var f2 = '_' + f;
				var finded = $('[alt^=' + f + '],[alt*=' + f2 + ']', $('.friends', host));
			} else {
				var f = filter.toUpperCase().split(' ').join('_');
				var finded = $('[alt*=' + f + ']', $('.friends', host));
			}

			if (finded.length) {
				$('.friend_square_host', host).hide();
				finded.parent().show();
			} else {
				$('.friend_square_host', host).show(); //show all if not find
			}
		} else {
			$('.friend_square_host', host).show();
		}
	},
	getUsedPhones: function() {
		var that = this;
		B.get({
			script: 'index.php?r=user/saved-phones',
			//r: 'user/saved-phones',
			SavedPhones: 'list',
			ok: function(response) {
				if (response && response.phones) {
					var h = [];
					for (var i in response.phones) {
						h.push('<div class="select_phone_79">' + response.phones[i].phone + '</div>');
					}
					$('.saved_sms_host').html(h.join('')).show();

					$('.select_phone_79').unbind('mousedown').mousedown(function() {
						var email = $(this).html();
						$('.thankyousms').val(email);
						Send.hide_drop_sms_list();
					});
					$('.drop_list_host_sms').show();

					B.click($('.drop_list_sms'), function() {
						if ($('.send_form_sms_host').is(':visible')) {
							Send.hide_drop_sms_list();
						} else {
							Send.show_drop_sms_list();
						}
					});


				} else {
					$('.saved_sms_host').hide();
					$('.drop_list_host_sms').hide();
				}
			}
		});
	},
	getUsedEmails: function() {
		//get email addresses
		B.get({
			script: 'index.php?r=user/saved-mails',
			//r: 'user/SavedMails',
			SavedMails: 'list',
			ok: function(response) {
				if (response && response.emails) {
					var h = [];

					//исключаем из списка e-mail которые уже есть у друзей
					var reverse = {};
					for (var j in Friends.data.friends_emails) {
						reverse[Friends.data.friends_emails[j].email] = 1;
					}

					for (var i in response.emails) {
						if (!reverse[response.emails[i].email]) {
							h.push('<div class="select_email_79">' + response.emails[i].email + '</div>');
						}
					}
					$('.saved_email_host').html(h.join('')).show();

					$('.select_email_79').unbind('mousedown').mousedown(function() {
						var email = $(this).html();
						$('.thankyouemail').val(email);
						Send.hide_drop_emails_list();
					});

				} else {
					$('.saved_email_host').hide();
				}
			}
		});
	},
	addEmails: function(callback) {
		var that = this;
		if (that.data.invited_friends) {
			var ids = false;
			for (var i in that.data.invited_friends) {
				if (!that.data.invited_friends[i].email) {
					if (!ids) {
						var ids = [];
					}
					ids.push(that.data.invited_friends[i].id)
				}
			}
			if (ids) {
				B.get({
					script: 'index.php?r=user/getfriendsemails',
					//r: 'user/getfriendsemails',
					ids: ids.join(','),
					ok: function(response) {
						if (response.data) {
							that.data.friends_emails = response.data;
							callback();
						}
					}
				});
			}
		}
	}
};