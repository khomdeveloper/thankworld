var Messages = {
    data_snapshot: false,
    createDataSnapshot: function(data) {
	this.data_snapshot = {};
	if (data) {

	}
    },
    startChain: function(id, onStart, onComplete) {
	$('.messages_list').html('');
	Buttons.place({
	    host: $('.message_send_message_button_host'),
	    title: T.out('send_mess'),
	    id: 'send_message',
	    class: 'red_button',
	    outer: {
		css: {
		    float: 'right',
		    'margin-right': '-10px'
		}
	    },
	    action: function() {
		if ($('.message_send_message').val()) {
		    Base.post({
			script: 'index.php?r=user/answer',
			//r: 'user/answer',
			id: $('.message_id').val(),
			message: $('.message_send_message').val(),
			ok: function(response) {
			    //TODO: use response in request
			    Thanks.disable();
			    $('.messages_list').slideUp(function() {
				Messages.startChain($('.message_id').val(), function() {
				    $('.messages_list').show();
				    Thanks.enable();
				});
			    });

			    /* no need to reset on messages because we do not see them
			     if (window.Thanks) { //update main thanks list
			     Thanks.get({
			     host: $('.header_thanks_list'),
			     name: 'header_thanks_list',
			     filter: {
			     get: 'new',
			     start: 0,
			     page: 3
			     },
			     callback: function(){
			     Thanks.enable();
			     }
			     });
			     }
			     */
			    $('.message_send_message').val('');
			},
			no: function(response) {
			    console.error(response);
			}
		    });
		} else {
		    Dialog.show({
			title : '',
			message: T.out('should_write_smth'),
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
	    }
	});

	this.first = false;
	var that = this;
	this.getChain(id,
		function(data) {
		    if (!that.first) {
			if (onStart) {
			    $('.message_id').val(data.id);
			    onStart(data);
			}
			that.first = true;
		    }
		},
		function(data) {
		    if (data.status != '') {//available to send messages only for thanks
			$('.send_message_in_messages_host').hide()
		    } else {
			$('.send_message_in_messages_host').slideDown();
		    }
		    if (onComplete) {
			onComplete();
		    }
		}
	);
    },
    //return all messages and thanks in chain under main id
    getChain: function(id, onLoad, onComplete) {
	var that = this;
	Base.get({
	    script: 'index.php?r=user/message',
	    //r: 'user/message',
	    id: id,
	    ok: function(data) {

		/*
		 flat one level message system
		 it is possible to answer only on initial message
		 unable to answer on answer
		 
		 */

		if (data) {

		    //console.log(data);
		    that.output(data, $('.messages_list'), data.children
			    ? false
			    : function() {
				if (onComplete) {
				    onComplete(data);
				}
			    });

		    if (data.children) {
			for (var i in data.children) {
			    var child = data.children[i];
			    that.output(child, $('.messages_list'), (i < (data.children.length - 1))
				    ? false
				    : function() {
					if (onComplete) {
					    onComplete(data);
					}
				    });
			}
		    }

		    if (onLoad) {
			onLoad(data);
		    }

		} else {
		    if (onComplete) {
			onComplete(data);
		    }
		}
	    },
	    no: function(error) {

		//TODO: add handler here

		console.error(error);
	    }
	});
    },
    output: function(data, appendTo, onSlideDown) {

	T.out('send_rethank2');

	var obj = $('.message_raw').clone();
	obj.removeClass('message_raw').addClass('message_line_host').addClass('message_line_host_' + data.id);
	obj.appendTo(appendTo);

	var host = $('.message_line_host_' + data.id);

	$('.message_ankor', host).attr({
	    'name': 'message_' + data.id
	});

	$('.userpic', $('.self_user_pic_in_messages', host)).attr({
	    'src': '//graph.facebook.com/' + User.data.uid + '/picture?width=80&height=80'
	});

	$('.message_pretext', host).html(Thanks.getCaption(data, 'no date') + '<br/>' + data.changed);

	$('.message_detales', host).html(
		(data.receiver_uid == User.data.uid && data.receiver_net == User.data.net && data.ref * 1 === 0
			? '<div style="float:right; width:140px; height:30px;" class="pr rethank_host"></div>'
			: '') + '<p><b>' + data.title + '</b></p>' + data.message
		).show();

	if (data.receiver_uid == User.data.uid && data.receiver_net == User.data.net) {//I am receiver

	    if (data.ref * 1 === 0) {

		var uid = data.sender_uid;

		//send thank back
		$('table', $('.message_line_host_' + data.id)).addClass('cp').unbind('mousedown').mousedown(function() {
		    Base.press($(this));
		    Send.select(uid);
		    Menu.switchPage('send');
		});

		Buttons.place({
		    host: $('.rethank_host', $('.message_line_host_' + data.id)),
		    refresh: true,
		    title: T.out('send_rethank2'),
		    id: 'rethank_' + uid,
		    class: 'red_button',
		    outer: {
			css: {
			    float: 'right',
			    'margin-right': '0px'
			}
		    },
		    action: function() {
			Send.select(uid);
			Menu.switchPage('send');
		    }
		});


	    }

	    $('.direction_mark', host).attr({
		src: Application.baseUrl + '/images/left_big.png'
	    });

	    if (data.status != 'message') {
		$('.thank_mark', host).show();
		$('.userpic', $('.self_user_pic_in_messages', host)).show();
	    } else {
		$('.thank_mark', host).hide();
		$('.userpic', $('.self_user_pic_in_messages', host)).hide();
	    }

	    $('.userpic', $('.friend_user_pic_in_messages', host)).attr({
		'src': '//graph.facebook.com/' + data.sender_uid + '/picture?width=80&height=80'
	    });


	} else { //i am sender

	    $('table', $('.message_line_host_' + data.id)).removeClass('cp').unbind('mousedown');

	    $('.direction_mark', host).attr({
		src: Application.baseUrl + '/images/right_big.png'
	    });
	    $('.userpic', $('.friend_user_pic_in_messages', host)).attr({
		'src': '//graph.facebook.com/' + data.receiver_uid + '/picture?width=80&height=80'
	    });
	    $('.userpic', $('.self_user_pic_in_messages', host)).show();
	    $('.thank_mark', host).hide();

	    if (data.status != 'message') {
		$('.userpic', $('.friend_user_pic_in_messages', host)).show();
	    } else {
		$('.userpic', $('.friend_user_pic_in_messages', host)).hide();
	    }

	}

	host.slideDown(function() {
	    if (onSlideDown) {
		onSlideDown();
	    }
	});
    }
};