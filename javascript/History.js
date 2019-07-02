var History = {
	init: function() {

		var that = this;

		that.ready = 1;

		if (!window.Friends) { //wait for script loading
			setTimeout(function() {
				that.init();
			}, 200);
			return false;
		}

		//load/init switcher
		Menu.getHTML({
			file: 'switcher.php',
			selector: '.switcher_flag',
			append: true,
			callback: function(page) {
                //Maps.hide();
			}
		});

		//that.thanks(); //->точка вызова

	},
	update: function() {
		Thanks.get({
			host: $('.thanks_data_history_host'),
			name: 'thanks_data_history_host',
			filter: {
				get: 'all',
				start: 0,
				page: 7
			},
			callback: function(data) {
				$('.amount_indicator').html(data.my * 1 + data.i * 1);
				$('.thanks_data_history_host').slideDown();
			}
		});
	},
	sendDonation: function() {
		B.post({
			script: 'index.php?r=user/donate',
			//r: 'user/donate',
			ok: function(response) {
				Dialog.show({
					title: '<div style="margin-bottom:-10px;">' + response.message + '</div>',
					message: ''
				});
				
				$('.available_thaks').html(response.available_thanks);
				
			},
			no: function(error) {
				Dialog.show({
					title: '<div style="margin-bottom:-10px;">' + error.error + '</div>',
					message: ''
				});
			}
		});
	},
	thanks: function() {
		var that = this;
		if (!window.Thanks) {
			setTimeout(function() {
				that.thanks();
			}, 200);
			return false;
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
				//ChartThank.init('thanks_chart');
			}
		});
	},
	switcherControl: function() {
		if (ChartThank.show_my && ChartThank.show_me) {
			//all selected    
			$('.thanks_data_history_host').slideUp(function() {
				Thanks.get({
					host: $('.thanks_data_history_host'),
					name: 'thanks_data_history_host',
					filter: {
						get: 'all',
						start: 0,
						page: 7
					},
					callback: function(data) {
						$('.amount_indicator').html(data.i * 1 + data.my * 1);
						$('.thanks_data_history_host').slideDown();
					}
				});
			});
		} else {
			if (ChartThank.show_my) {
				//I thank
				$('.thanks_data_history_host').slideUp(function() {
					Thanks.get({
						host: $('.thanks_data_history_host'),
						name: 'thanks_data_history_host',
						filter: {
							get: 'me',
							start: 0,
							page: 7
						},
						callback: function(data) {
							$('.amount_indicator').html(data.my);
							$('.thanks_data_history_host').slideDown();
						}
					});
				});

			} else {
				if (ChartThank.show_me) {
					//thank me
					$('.thanks_data_history_host').slideUp(function() {
						Thanks.get({
							host: $('.thanks_data_history_host'),
							name: 'thanks_data_history_host',
							filter: {
								get: 'i',
								start: 0,
								page: 7
							},
							callback: function(data) {
								$('.amount_indicator').html(data.i);
								$('.thanks_data_history_host').slideDown();
							}
						});
					});

				} else {
				
					$('.thanks_data_history_host').slideUp();
					$('.amount_indicator').html(0);
				}
			}
		}

	},
    getGeoThankPlaces: function() {
        Base.get({
            script: 'index.php?r=user/get-geo-thank-places',
            ok: function(response) {
                var myLatLng = {lat: 55.751244, lng: 37.618423};

                // Create a map object and specify the DOM element for display.
                var map = new google.maps.Map(document.getElementById('thanks_map'), {
                    center: myLatLng,
                    scrollwheel: false,
                    zoom: 12
                });

                // Create a marker and set its position.
                var place, contentString, infoWindow = new google.maps.InfoWindow();
                for (var i = 0; i < response.places.length; i++) {
                    place = response.places[i];
                    var marker = new google.maps.Marker({
                        map: map,
                        position: place.coordinates,
                        title: place.title + ' (' + place.thank_count + ')',
                        //icon: 'https://graph.facebook.com/' + place.place_uid + '/picture?width=25&height=25',
                        //icon: 'images/thk_blue_25x25.png'
						icon: 'images/pin_thank.png'
                    });

                    google.maps.event.addListener(marker, "click", (function(marker, i) {
                        return function() {
                            contentString = "<table style='margin-bottom:0px;'><tr><td rowspan='2'><img src='//graph.facebook.com/" +
                                response.places[i].place_uid + "/picture?width=50&height=50'></td><td>" +
                                response.places[i].title + "</td>" +
                                "<tr><td><img src='images/pin_thank.png'> " + response.places[i].thank_count + '</td></tr></table>';
                            infoWindow.setContent(contentString);
                            infoWindow.open(map, marker);
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