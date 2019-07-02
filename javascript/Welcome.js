var Welcome = {
	place: function(records) {
		var that = this;

		//console.log(records);

		//TODO: здесь вместо всего этого вывести line pack

		B.getHTML({
			file: 'welcome_line.php',
			selector: '.welcome_line_raw',
			host: $('.welcome_message'),
			callback: function(html) {

				if ($('.welcome_data', $('.welcome_message')).length == 0) {
					$('.welcome_message').append('<div class="welcome_data"></div>');
				}

				var host = $('.welcome_data', $('.welcome_message'));

				var template = $('.welcome_line_host').html();

				for (var i in records) {

					host.append(template);
					var record = records[i];
					$('.welcome_line_raw', host).removeClass('welcome_line_raw').addClass('welcome_line').addClass('welcome_line_' + i);
					var host0 = $('.welcome_line_' + i);

					$('.sender_image', host0).attr({
						src: '//graph.facebook.com/' + record.sender_uid + '/picture'
					});

					$('.welcome_thankback', host0).addClass('welcome_thankback_' + record.sender_uid).attr({
						alt: record.sender_name
					});

					B.wait(function() {
						return window.Thanks
								? true
								: false;
					}, function() {
						var text = Thanks.getCaption(record, false, 'all', true);
						$('.message_text17', host0).html(text);
					});

				}

				B.click($('.welcome_thankback'), function(obj) {
					var thanker_uid = B.getId(obj, 'welcome_thankback_');
					if (window.Menu && window.Send) {
						Menu.switchPage('send', function() {
							Send.select(thanker_uid);
						});
					}
					that.read(records);
				});

				$('.self_userpic_in_welcome').attr({
					src: '//graph.facebook.com/' + User.data.uid + '/picture'
				});

				CheckBoxes.place({
					id: 'postit_in_timeline',
					host: $('.dialog_footer'),
					title: T.out('post_to_timeline'),
					prepend: true,
					css: {
						'margin-top': '-20px',
						width: 'auto'
					},
					action: function(status) {
						//action   if changed
					}
				});

			}
		});

		/*
		 $('.say_thank', $('.welcome_message')).unbind('mousedown').mousedown(function() {
		 that.read(record);
		 if (window.Menu && window.Send) {
		 Menu.switchPage('send', function() {
		 Send.show_drop_list();
		 });
		 }
		 });
		 
		 $('.thank_specified', $('.welcome_message')).unbind('mousedown').mousedown(function() {
		 that.read(record);
		 if (window.Menu && window.Send) {
		 Menu.switchPage('send', function() {
		 Send.select($('.thanker_uid').html());
		 
		 });
		 }
		 });
		 
		 
		 */
	},
	read: function(records) {
		if (CheckBoxes.list['postit_in_timeline'].status) {

			var post_text = [];

			for (var i in records) {
				var record = records[i];
				post_text.push(record.sender_name + ' ' + T.out('thank_for_075') + (record.title
						? ' ' + T.out('for07') + ' ' + record.title
						: '') + (record.message && record.message != '0' && record.message != 'undefined'
						? ' ' + T.out('and_message_12') + ' ' + record.message + T.out('close_quote')
						: '') + ' ' + record.changed.split('-').join('.'));
			}
			//console.log(record);    

			Facebook.post(post_text.join("\n\r"),
					//second parameter is redirect URI id Javascript SDK fails on iOS system		
					Application.appSourcePath + '/?thank_post=' + encodeURIComponent(post_text.join(",\n\r")));
		}

		var ids = [];
		for (var i in records) {
			ids.push(records[i].id);
		}
		Thanks.read(ids);  //mark message as it is read (temporary blocked)
		Dialog.hide();
	}
};