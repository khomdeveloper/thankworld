var Menu = {
	//get template
	/*
	 * host :
	 * selector :
	 * once : true - do not upload it again from server if already
	 * callback - action when load or just get script
	 */
	getHTML: function(input) {

		if (!input.file) {
			console.error('input.file required');
			return;
		}

		var p = {
			file: input.file,
			once: input.once || 1,
			selector: input.selector,
			host: input.host || $(document.body),
			callback: input.callback || false,
			append: input.append
					? input.append
					: 0
		};

		if (p.once) {
			var obj = $(p.selector, p.host);

			if (obj.length) {
				if (p.callback) {
					p.callback(p.host);
				}
				return;
			}
		}

		Base.get({
			script: 'index.php',
			r: 'site/out',
			t: p.file.split('.php')[0],
			html: function(html) {
				if (!p.append) {
					p.host.html('');
				}
				p.host.append(html);
				if (p.callback) {
					var obj = $(p.selector);
					p.callback(p.host);
					//console.log('loaded');
				}
			}
		});
	},
	/*
	 *    {
	 *      pagename(php) : {
	 *          page : {
	 *              host,
	 *              selector
	 *          }
	 *          but : {
	 *              initial but detailes
	 *          }
	 *      },
	 *      active 
	 *    }
	 */
	init: function(input) {

		this.pages = input;

		for (var pagename in input) {
			if (pagename !== 'active' && pagename !== 'onInit') {
				var record = input[pagename];

				if (!record.but) {
					continue;
				}

				var cl = ['page_control'];

				if (record.but['class'] && record.but['class'].push) {
					cl = record.but['class'].push('page_control');
				} else {
					cl.push(record.but['class']);
				}

				if (record.but.$) {//preset but initiator

					//var actionclick = function

					record.but.$.css({
						cursor: 'pointer'
					}).unbind('click').click(function() {
						var obj = $(this);

						var id = B.getID(obj, 'open_page_');

						if (Menu.currentPage !== id) {

							Menu.getHTML({
								file: id,
								host: Menu.pages[id].page.host,
								selector: Menu.pages[id].page.selector,
								once: true,
								callback: function(page) {
									Menu.switchPage(id);
									if (Menu.pages[id].onSwitch) {
										Menu.pages[id].onSwitch();
									}
								}
							});

						} else if (id === 'send') {
							Menu.switchPage(id);
						}

						if (id === 'news') {
							if (id == 'news') {
								$('.header_center_host').animate({
									height: '40px'
								});
							}
						} else {
							$('.header_center_host').animate({
								height: '130px'
							});
						}

						$('.currentPage_marker').removeClass('currentPage').hide();
						$('.currentPage_marker_' + id).addClass('currentPage').show();

						$('.buttons_for').hide();
						$('.buttons_for_' + id).show();

						//Maps.hide();
						if (Switchers.list['statistics_filter']) {
							//Switchers.list['statistics_filter'].switch(-1, 'noaction');
						}

						if (Switchers.list['history_filter']) {
							//Switchers.list['history_filter'].switch(-1, 'noaction');
						}

					});

				} else {

					//place open button
					var but = Buttons.place({
						host: record.but.host,
						title: record.but.title,
						class: cl,
						id: record.but.id || pagename,
						action: function(id) {
							Menu.getHTML({
								file: id,
								host: Menu.pages[id].page.host,
								selector: Menu.pages[id].page.selector,
								callback: function(page) {
									Menu.switchPage(id);
									if (Menu.pages[id].onSwitch) {
										Menu.pages[id].onSwitch();
									}
								}
							});
						}
					});

					if (pagename === input['active']) {
						but.$.hide();
					}
				}
			}
		}

		if (input.onInit) {
			input.onInit();
		}


		//set active page
		if (input['active']) {
			var pagename = input['active'];
			Menu.getHTML({
				file: pagename,
				host: Menu.pages[pagename].page.host,
				selector: Menu.pages[pagename].page.selector,
				callback: function(page) {
					Menu.switchPage(pagename);
				}
			});
		}

	},
	currentPage: false,
	switchPage: function(page, action, redrawMenu) {

		if (page != 'statistics' && !Application.logged) {
			User.requestLogin(function(response) {
				User.logged(response);
			});
			return false;
		}

		this.currentPage = page;

		//console.log(page);
		$('.page_control').show();
		//$('.button_' + page).hide();
		$('.pages').slideUp();

		if (page != 'send') {
			$('.random_friends').slideUp();
			$('.send_spoiler_host').hide();
		} else {
			$('.send_spoiler_host').show();
			Base.wait(function() {
				return window.Send
						? true
						: false;
			}, function() {
				$('.random_friends').slideUp(function() {
					Send.randomFriends();
				});
				Send.showThankLink();
				Send.sendEmailSmsController();
			}, 300);

			if (redrawMenu) {
				$('.currentPage_marker').removeClass('currentPage').hide();
				$('.currentPage_marker_send').addClass('currentPage').show();
				$('.buttons_for').hide();
				$('.buttons_for_send').show();
				Switchers.list['type_of_send'].switch(0);
			}

		}

		Menu.pages[page].page.host.slideDown(function() {
			if (action) {
				action();
			}
		});
	},
	action: function(action, name, text) {

		if (action === 'terms') {
			Dialog.show({
				title: '',
				message: 'agreement.php',
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

		if (action === 'contacts') {
			Dialog.show({
				title: '',
				message: 'contacts.php',
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


		if (action === 'about') {
			Dialog.show({
				title: '',
				message: 'about.php',
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

		if (action.indexOf('switch_') !== -1) {

			var page = action.split('_')[1];

			if (!Menu.pages[page]) {
				console.log('uncknown page:' + page);
				return;
			}

			Menu.getHTML({//get page template and output it
				file: page,
				host: Menu.pages[page].page.host,
				selector: Menu.pages[page].page.selector,
				callback: function(pageObj) {
					Menu.switchPage(page);
				}
			});

		}

		if (action === 'thank') {
			Menu.switchPage('send');

			var json = false;
			try {
				json = $.parseJSON(name);
			} catch (e) {

			}

			if (json) {
				//приходим из email
				//TODO:
				Send.select_place({
					id: '',
					net: '',
					title: json.title,
					image: json.image,
					post_title: json.post_title
				});
				/*
				 json.place
				 }, json.title, json.image, json.post_title);*/
				Send.control();
				return;
			}

			if (name.indexOf('site_') !== -1) { //not used?
				/* console.log('not used?');
				 Base.wait(function() {
				 return window.Send && $('.find_what').length
				 ? true
				 : false;
				 }, function() {
				 Send.select_place('link', name.split('site_')[1]);
				 Send.control();
				 }); */
			} else {
				Base.wait(function() {
					return window.Send && window.Friends && $('.friends', $('.send_form_friends_host')).html()
							? true
							: false;
				}, function() {
					Send.selectByName(name);
					$('.send_title').val(text);
					Send.control();
				});
			}
		}

		if (action === 'unsubscribe') {
			Menu.switchPage('send');
			Base.post({
				script: 'index.php?r=user/unsubscribe',
				//r: 'user/unsubscribe',
				ok: function(response) {
					Dialog.show({
						title: '',
						message: response.message,
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
			});
		}


	}

};