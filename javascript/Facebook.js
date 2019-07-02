var Facebook = {
	appId: false,
	init: function(appId, ok, no) {

		var that = this;
		//1) get javascript sdk
		$.getScript('//connect.facebook.net/en_UK/all.js', function() {

			that.appId = appId;
			//2) init javascript sdk
			FB.init({
				appId: appId,
				xfbml: true,
				version: 'v2.1',
				status: true,
				cookie: true
			});
			FB.Canvas.setAutoGrow(500);
			//3) get login status
			FB.getLoginStatus(function(response) {
				// Check login status on load, and if the user is
				// already logged in, go directly to the welcome message.
				if (response.status == 'connected') {
					that.onLogin(response, ok);
				} else {
					if (Base.inIframe() && !Base.inIpad()) { //just call facebook login dialog
						FB.login(function(response) {
							that.onLogin(response, ok);
						}, {scope: 'user_friends, email'});
					} else { //separate site -> call external method, because immediatelly call of fb dialog (in popup window) will be blocked by browser
						if (no) {
							no(); //this is reaction on popup window
						} else {
							FB.login(function(response) {
								that.onLogin(response, ok);
							}, {scope: 'user_friends, email'});
						}
					}
				}
			}, true);
		});
	},
	onLogin: function(response, callback) { //action when login
		if (response.status == 'connected') {
			FB.api('/me', function(data) {
				if (callback) {
					data.token = response.authResponse.accessToken;
					callback(data);
				}
			});
		} else {
			if (window.User) {
				User.notConnected();
			} else {
				top.location = Application.appSourcePath;
			}
		}
	},
	/**
	 required = 'publish_action' //TODO: add multiply permissions
	 ok,no
	 */

	checkPermissions: function(required, ok, no) {
		FB.api('/me/permissions', function(response) {
			//console.log('permissions:', response);
			if (!response || !response.data) {
				if (no) {
					no(response);
				} else {
					console.error('no response from /me/permissions', response);
				}
				return false;
			}

			if (!required) {
				return false;
			}

			for (var i in response.data) {
				if ((response.data[i].permission === required && response.data[i].status === 'granted') ||
						(response.data[i][required])) {
					ok(response);
					return true;
				}
			}

			if (no) {
				no(response);
			} else {
				console.error('permissions:', $response);
			}

		});
	},
	/**
	 * call login dialog by redirection (if javascriptSDK) failes
	 * 
	 * @input = { 
	 *	    return_to - url where we return if login correct
	 * }
	 * 
	 */
	redirectLogin: function(input) {
		var url = 'https://www.facebook.com/dialog/oauth?client_id=' + this.appId +
				'&redirect_uri=' + encodeURIComponent(B.addHTTPS(input.return_to)) +
				'&scope=' + input.scope;
		
		if (Base.inIframe()) {
			top.location.href = B.addHTTPS(url);
		} else {
			//alert(url);
			location.href = B.addHTTPS(url);
		}
	},
	/**
	 * send request by using redirection
	 * 
	 * @input {
	 *		'message',
	 *		'action_type',
	 *		'return_to' - redirect back
	 *		'to'
	 * }    
	 *	
	 * 
	 */
	sendRequest: function(input) {
		//here we need to check if it is iOS and chrome in IOS

		//alert(navigator.userAgent);

		if (Base.inIpad()) {
			var that = this;
			var url = 'https://www.facebook.com/dialog/apprequests?app_id=' + that.appId +
					'&to=' + input.to +
					'&redirect_uri=' + encodeURIComponent(B.addHTTPS(input.return_to)) +
					(input.action_type
							? '&action_type=' + input.action_type
							: '') +
					'&message=' + encodeURIComponent(input.message);
			top.location.href = B.addHTTPS(url);
		} else {
			FB.ui({
				method: 'apprequests',
				/*action_type: 'send',
				 object_id: 191181717736427, //191181717736427*/
				message: input.message,
				to: input.to
			}, function(response) {
				if (response && response.request) { //successfully sending
					//console.log('facebook return:', response);
					input.callback(response);
				} else {
					//TODO: something if error
				}
			});
		}
	},
	ask_times_remain: 1, //this is necessary to prevent nag permissions asking
	post: function(what, redirect_path, properties) {
		//console.log(properties);
		
		var that = this;
		this.checkPermissions(
				'publish_actions',
				function(response) {
					FB.api('/me/feed', 'post', {
						message: what,
						picture: B.addHTTPS(Application.appSourcePath) + '/images/share.png',
						caption: T.out('image caption in facebook post'),
						link: B.addHTTPS(Application.appSourcePath) + '/?s=' + Math.random(),
						name: T.out('title when post on facebook wall'),
						description: T.out('description when post on facebook wall'),
						properties: properties || false
					}, function(response) {
						if (!response || response.error) {
							console.error('error in me/feed', JSON.stringify(response.error));
						} else {
							//console.log(response); //ok point ->
						}
					}
					);
				}, function(response) {
			//no permissions has founded -> ask for them
			console.log('no permission');
			if (Base.inIpad()) {
				if (redirect_path) {
					that.redirectLogin({
						return_to: redirect_path,
						scope: 'publish_actions'
					});
				}
			} else {
				if (that.ask_times_remain > 0) {
					that.ask_times_remain -= 1;
					FB.login(function(response) {
						if (response.status === 'connected') {
							that.post(what); //retry to post	
						} else {
							//TODO: if we suddenly disconnected from facebook
						}
					}, {scope: 'publish_actions'});
				} else {
					//TODO: if no ask times remained -> nothing to do
				}
			}
		});
	}
};

/*
 * var params = {};
 params['message'] = 'Message';
 params['name'] = 'Name';
 params['description'] = 'Description';
 params['link'] = 'http://apps.facebook.com/summer-mourning/';
 params['picture'] = 'http://summer-mourning.zoocha.com/uploads/thumb.png';
 params['caption'] = 'Caption';
 
 FB.api('/me/feed', 'post', params, function(response) {
 if (!response || response.error) {
 alert('Error occured');
 } else {
 alert('Published to stream - you might want to delete it now!');
 }
 });
 */