/*
 * 
 * requred Base.js, buttons.php
 * 
 */

var Dialog = {
	/*
	 * title
	 * message
	 * css
	 * butons : [
	 *   {
	 *      title
	 *      action
	 *      css
	 *   },
	 *   {
	 *   }
	 * ]
	 */
	show: function(p) {
		var that = this;

		if (p.waitIfBusy) {
			if (that.starting || $('.dialog').is(':visible')) {
				setTimeout(function() {
					that.show(p);
				}, 1000)
				return;
			}
		}

		that.starting = true;

		var host = $('.dialog');
		$('.title', host).html(p.title).show();

		if (p.header) {
			$('.dialog_header', host).html(p.header).show();
		} else {
			$('.dialog_header', host).hide();
		}

		//console.log(p);


		//add buttons

		if (p.cls) {
			$('.dialog').addClass(p.cls);
		} else {
			$('.dialog').attr('class', 'dialog cell0');
		}

		if (p.noexit) {
			$('.message').css({
				'margin-top': '-40px',
				'margin-bottom': '-30px'
			});
		} else {
			$('.message').css({
				'margin-top': '0px'
			});
		}

		$('.dialog_footer').html('');

		if (p.buttons) {
			for (var i in p.buttons) {
				var but = p.buttons[i];

				Buttons.place({
					title: but.title,
					host: $('.dialog_footer'),
					action: but.action,
					css: but.css || false,
					class: but.class || 'transparent_button',
					outer: but.outer || false
				});
			}
		}

		if (p.noexit) {
			$('.button.exit').hide();
			$('.pr', $('.dialog_footer')).css({
				display: 'block',
				left: '100%',
				'margin-left': '-50px'
			});
		} else {
			$('.button.exit').show().unbind('mousedown').mousedown(function() {
				Base.press($(this));
				$('.dialog').fadeOut(function() {
					if (p.no) {
						p.no();
					}
				});
			});
		}

		if (p.message && p.message.indexOf('.php') !== -1) {
			//upload file at first and then show

			$('.message_host', host).show();
			$('.message', $('.dialog_message')).html('');
			B.getHTML({
				file: p.message,
				selector: '.welcome_message',
				host: $('.message', $('.dialog_message')),
				callback: function(html) {
					if (p.onShow) {
						p.onShow();
					}
					$('.message', host).show();
					$('.dialog').fadeIn(function() {
						that.starting = false; //dialog shown
					});
				}
			});

		} else {
			if (p.message) {
				$('.message_host', host).show();
				$('.message', host).html(p.message).show();
			} else {
				$('.message_host', host).hide();
			}
			$('.dialog').fadeIn(function() {
				that.starting = false; //dialog shown
			});
			if (p.onShow) {
				p.onShow();
			}
		}


	},
	hide: function(callback) {
		$('.dialog').fadeOut(function() {
			if (callback) {
				callback();
			}
		});
	}

};