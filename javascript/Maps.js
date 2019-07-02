/**
 * Created by indikator on 2/1/16.
 */

var Maps = {
	mapsButtons: {},
	mapsId: 'maps',
	markers: [],
	markerCluster: {},
	checkNear: function(base) {
		return base + ($('#map_near').hasClass('active')
				? '_near'
				: '');
	},
	checkMode: function() {
		var that = this;
		if ($('#map_friends').hasClass('active')) {
			that.clickToAllThanksFriends(that.checkNear('friends'));
		}
		if ($('#map_thanks').hasClass('active')) {
			that.clickToThanksBtn(that.checkNear('thanks'));
		}
	},
	init: function() {
		var that = this;
		$(document).ready(function() {
			B.loadRemote(A.baseURL() + 'js_minified/markerclusterer.js', function() {
				var mbs;
				var lru = T.locale === 'ru';
				mbs = {
					r: $('<div id="map_near"></div>').html('<div class="map_but_hover" style="display:none; margin-left:-95px; top:15px; ">' + (lru
							? 'РЯДОМ'
							: 'NEAR ME') + '</div>' + (lru
							? '<img src="images/btn_map_near_ru.png" />'
							: '<img src="images/btn_map_near_en.png" />')).addClass('btn'),
					y: $('<div id="map_year"></div>').html(
							'<div class="map_but_hover" style="display:none;">' + (lru
									? 'ЗА ГОД'
									: 'THIS YEAR') + '</div>' + (lru
							? '<img src="images/btn_map_y_ru.png" />'
							: '<img src="images/btn_map_y_en.png" />')).addClass('btn'),
					m: $('<div id="map_month"></div>').html(
							'<div class="map_but_hover" style="display:none;">' + (lru
									? 'ЗА МЕСЯЦ'
									: 'THIS MONTH') + '</div>' + (lru
							? '<img src="images/btn_map_m_ru.png" />'
							: '<img src="images/btn_map_m_en.png" />')).addClass('btn'),
					friends: $('<div id="map_friends"></div>').html('<div class="map_but_hover" style="display:none;">' + (lru
							? 'ДРУЗЬЯ'
							: 'FRIENDS') + '</div>' + (lru
							? '<img src="images/btn_map_friends_ru.png" />'
							: '<img src="images/btn_map_friends_en.png" />')).addClass('btn'),
					place_karma: $('<div id="map_karma"></div>').html(
							'<div class="map_but_hover" style="display:none;">' + (lru
									? 'МЕСТА КАРМЫ'
									: 'KARMA PLACES') + '</div>' + (lru
							? '<img src="images/btn_map_karma_ru.png" />'
							: '<img src="images/btn_map_karma_en.png" />')).addClass('btn').hide(),
					place_thanks: $('<div id="map_thanks" class="map_but_host"></div>').html(
							'<div class="map_but_hover" style="display:none;">' + (lru
									? 'ОРГАНИЗАЦИИ'
									: 'ENTERPRISES') + '</div>' +
							(lru
									? '<img src="images/btn_map_thanks_ru.png" />'
									: '<img src="images/btn_map_thanks_en.png" />')).addClass('btn'),
					place_all: $('<div id="map_all2" style="display:none;"></div>').html('<div class="map_but_hover" style="display:none;">' + (lru
							? 'ВСЕ СПАСИБО'
							: 'ALL THANKS') + '</div>' + (lru
							? '<img src="images/btn_map_all_ru.png" />'
							: '<img src="images/btn_map_all_en.png" />')).addClass('btn')
				};

				mbs.near_host = $('<div></div>').append(mbs.r);

				mbs.y_m_friends = $('<div></div>').
						//append(mbs.y).
						//append(mbs.m).
						//append(mbs.r).
						append(mbs.friends).
						css({
							display: 'inline-block'
						}).click(function(e) {
					if ($(this).hasClass('block')) {
						e.preventDefault();
					}
				}).hide();

				mbs.places = $('<div id="map_alls"></div>').
						//append(mbs.place_karma).
						append(mbs.place_thanks).
						append(mbs.place_all).
						css({
							display: 'inline-block'
						});

				mbs.place_thanks.click(function() {
					that.clickToThanksBtn(that.checkNear('thanks'));
				}).hover(function() {
					var obj = $(this);
					$('.map_but_hover', obj).show();
				}, function() {
					var obj = $(this);
					$('.map_but_hover', obj).hide();
				});

				mbs.place_karma.click(function() {
					that.clickToKarmaBtn();
				}).hover(function() {
					var obj = $(this);
					$('.map_but_hover', obj).show();
				}, function() {
					var obj = $(this);
					$('.map_but_hover', obj).hide();
				});


				mbs.place_all.click(function() {
					that.clickToAllThanks();
					//mbs.r.addClass('active');
					// Getting all thanks. Need clusterization.
				}).hover(function() {
					var obj = $(this);
					$('.map_but_hover', obj).show();
				}, function() {
					var obj = $(this);
					$('.map_but_hover', obj).hide();
				});

				mbs.r.unbind('click').click(function() {
					if ($(this).hasClass('active')) {
						$(this).removeClass('active');
						that.checkMode();
					} else {
						$(this).addClass('active');
						that.checkMode();
					}
					//that.clickToAllThanks();
				}).hover(function() {
					var obj = $(this);
					$('.map_but_hover', obj).show();
				}, function() {
					var obj = $(this);
					$('.map_but_hover', obj).hide();
				});


				mbs.friends.click(function() {
					that.toggleActive($(this));
					that.clickToAllThanksFriends(that.checkNear('friends'));
					// Getting my friends thanks. Need clusterization.
				}).hover(function() {
					var obj = $(this);
					$('.map_but_hover', obj).show();
				}, function() {
					var obj = $(this);
					$('.map_but_hover', obj).hide();
				});

				mbs.y.click(function() {
					that.toggleActive($(this));
					that.clickToAllThanksY();
					// Getting all thanks for a year. Need clusterization.
				}).hover(function() {
					var obj = $(this);
					$('.map_but_hover', obj).show();
				}, function() {
					var obj = $(this);
					$('.map_but_hover', obj).hide();
				});

				mbs.m.click(function() {
					that.toggleActive($(this));
					that.clickToAllThanksM();
					// Getting all thanks for a month. Need clusterization.
				}).hover(function() {
					var obj = $(this);
					$('.map_but_hover', obj).show();
				}, function() {
					var obj = $(this);
					$('.map_but_hover', obj).hide();
				});

				//controlDiv.append(mbs.y_m_friends).append(mbs.places);
				var url = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyAe4yycn9kwVfMC71j9HWDI8QFHYkaqMfA';
				$.getScript(url);

				that.mapsButtons = mbs;
			});//of loadremote
		});
	},
	getMap: function() {
		this.map = this.map || new google.maps.Map(document.getElementById(this.mapsId), {
			zoom: 12,
			center: {lat: 55.751244, lng: 37.618423} // Moscow
		});

		return this.map;
	},
	setCenter: function(center) {
		if (!center) {
			// Moscow
			center = {lat: 55.751244, lng: 37.618423};
		}

		this.getMap().setCenter(center);
	},
	/**
	 * Функция показа карты.
	 * @param type - может быть "karma", "thanks", "all"
	 * @param ymfriends - может быть "y", "m", "friends" или undefined
	 */
	showMap: function(type, ymfriends) {
		var me = this;
		
		$('#' + me.mapsId).slideDown(400, function(){
			
			var map = me.getMap();

			me.clearMarkers();
			me.blockButtons();

			switch (type) {
				case "karma":

					if (Maps.meMarker) {
						Maps.meMarker.setMap(null);
					}

					me.mapsButtons.near_host.hide();
					// Manipulating with buttons
					me.mapsButtons.place_karma.hide();
					me.mapsButtons.place_thanks.hide();
					me.mapsButtons.place_all.hide();
					me.mapsButtons.y_m_friends.hide();

					/*
					 me.mapsButtons.place_thanks.css('display', 'inline-block');
					 me.mapsButtons.place_all.css('display', 'inline-block');
					 me.mapsButtons.y_m_friends.hide().children().removeClass('active');
					 */

					me.getKarmaPlaces(map, function(m, mapsId, response, markers) {
						//me.blockButtons();
						// Create a marker and set its position.
						var place, contentString, infoWindow = new google.maps.InfoWindow();
						for (var i = 0; i < response.places.length; i++) {
							place = response.places[i];
							var marker = new google.maps.Marker({
								map: m,
								position: place.coordinates,
								title: place.title + ' (' + place.karma + ')',
								icon: 'images/pin_karma.png'
							});


							google.maps.event.addListener(marker, "click", (function(marker, i) {

								return function() {
									contentString = '<table style="margin-bottom:0px;"><tr><td rowspan="2"><img src="//graph.facebook.com/' +
											response.places[i].place_uid + '/picture?width=50&height=50"></td><td>' +
											'<span class="show_details_2" style="border-bottom:1px dotted black; cursor:pointer;">' +
											response.places[i].title + "</span></td>" +
											"<tr><td><img src='images/pin_karma.png'> " + response.places[i].karma + '</td></tr></table>';
									infoWindow.setContent(contentString);
									infoWindow.open(m, marker);

									$('.show_details_2').unbind('click').click(function() {
										//$('#' + mapsId).hide();
										//Switchers.list['statistics_filter'].switch(-1, 'noaction');

										Dialog.show({
											title: '<div style="margin-bottom:-10px; margin-left:50px;" class="ac">' + T.out('KARMA_GRAPH_1717') + '</div>',
											message: 'placekarmachart.php',
											onShow: function() {

												ChartThank.init('place_karma_chart_dialog', response.places[i].place_uid, 'fb');

												/*
												 Statistics.getKarma(response.places[i].place_uid, {
												 friends_karma_name: response.places[i].title,
												 friends_karma_rank: undefined,
												 friends_karma_value: response.places[i].karma,
												 image: "//graph.facebook.com/" + response.places[i].place_uid + "/picture?width=50&height=50",
												 //image: 'images/thk_hands_blue.png',
												 name_inline: response.places[i].title,
												 net: "fb",
												 place: true,
												 type: "map"
												 },'place_karma_chart_dialog');
												 */
											}
										});


									});


								}
							})(marker, i));


							markers.push(marker);
						}
						
						var clusterStyles = [
							{
								textColor: 'white',
								url: './images/klaster_karma_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_karma_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_karma_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_karma_64_3.png',
								height: 64,
								width: 64
							}
						];
						
						me.markerCluster = new MarkerClusterer(m, markers, {
							maxZoom: 15,
							styles: clusterStyles
						});
						
						
					});
					break;
				case "thanks":

					if (Maps.meMarker) { //remove self marker
						Maps.meMarker.setMap(null);
					}

/*
					if (!ymfriends) {
						var ymfriends = 'thanks';
					}
*/

					me.mapsButtons.near_host.show();

					// Manipulating with buttons
					me.mapsButtons.place_thanks.show().removeClass('active').addClass('active');
					/*me.mapsButtons.place_karma.css('display', 'inline-block');
					 me.mapsButtons.place_all.css('display', 'inline-block');*/
					me.mapsButtons.place_all.hide();
					me.mapsButtons.place_karma.hide();
					me.mapsButtons.y_m_friends.show().children().removeClass('active');

					me.getThanksPlaces(map, function(m, response, markers) {

						if (ymfriends == 'thanks_near' && response.self && response.self.lat && response.self.lng) {
							Maps.meMarker = new google.maps.Marker({
								map: m,
								position: {lat: +response.self.lat, lng: +response.self.lng},
								info: 'test_' + i,
								icon: response.self.url
							});
							
							//console.log('we are here');
							
							map.setCenter({lat: +response.self.lat, lng: +response.self.lng});
							
						}

						//me.blockButtons();
						// Create a marker and set its position.
						var place, contentString, infoWindow = new google.maps.InfoWindow();
						for (var i = 0; i < response.places.length; i++) {
							place = response.places[i];
							var marker = new google.maps.Marker({
								map: m,
								position: place.coordinates,
								title: place.title + ' (' + place.thank_count + ')',
								icon: 'images/pin_thank.png'
							});

							google.maps.event.addListener(marker, "click", (function(marker, i) {
								return function() {
									contentString = "<table style='margin-bottom:0px;'><tr><td rowspan='2'><img src='//graph.facebook.com/" +
											response.places[i].place_uid + "/picture?width=50&height=50'></td><td><a href='//facebook.com/" + response.places[i].place_uid +"'>" +
											response.places[i].title + "</a></td>" +
											"<tr><td><img src='images/pin_thank.png'> " + response.places[i].thank_count + '</td></tr></table>';
									infoWindow.setContent(contentString);
									infoWindow.open(m, marker);
								}
							})(marker, i));

							markers.push(marker);
						}
						
						var clusterStyles = [
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							}
						];

						me.markerCluster = new MarkerClusterer(m, markers, {
							maxZoom: 15,
							styles: clusterStyles
						});
						
					}, ymfriends);
					break;
				case "all":
					// Manipulating with buttons
					me.mapsButtons.place_all.hide();
					me.mapsButtons.y_m_friends.css('display', 'inline-block');
					me.mapsButtons.place_karma.css('display', 'inline-block');
					me.mapsButtons.place_thanks.css('display', 'inline-block');

					if (ymfriends === 'friends') {
						me.mapsButtons.near_host.show();
						//me.mapsButtons.near_host.children().removeClass('active');
					} else if (ymfriends === 'friends_near') {
						me.mapsButtons.near_host.show();
						//me.mapsButtons.near_host.children().removeClass('active').addClass('active');
					} else {
						me.mapsButtons.near_host.hide();
					}

					if (!ymfriends) {
						ymfriends = '';
					}

					me.getAllThanks(map, function(m, response, markers) {
						
						if (Maps.meMarker) {
							Maps.meMarker.setMap(null);
						}

						//place myself
						if (ymfriends == 'friends_near' && response.self && response.self.lat && response.self.lng) {

							Maps.meMarker = new google.maps.Marker({
								map: m,
								position: {lat: +response.self.lat, lng: +response.self.lng},
								info: 'test_' + i,
								icon: response.self.url
							});
							
							map.setCenter({lat: +response.self.lat, lng: +response.self.lng});

						}

						var place, infoWindow = new google.maps.InfoWindow();
						for (var i = 0; i < response.places.length; i++) {
							place = response.places[i];

							var marker = new google.maps.Marker({
								map: m,
								position: {lat: +place.lat, lng: +place.lng},
								data: place,
								info: 'test_' + i,
								icon: './images/pin_thank_15.png'
							});


							google.maps.event.addListener(marker, "click", (function(marker, i) {
								return function() {

									A.w(['Thanks'], function() {
										Base.getHTML({
											file: 'map_marker_line.php',
											include: true,
											host: false,
											callback: function(html) {
												infoWindow.setContent(B.replaceTemplate(html, {
													'{{name}}': Thanks.getCaption(marker.data, true, true, false, false),
													'{{left_picture}}': '//graph.facebook.com/' + marker.data.sender_uid + '/picture',
													'{{right_picture}}': marker.data.receiver_net.indexOf('link_') !== -1
															? (
																	marker.data.logo
																	? marker.data.logo
																	: A.baseURL() + 'images/question.png'
																	)
															: ('//graph.facebook.com/' + marker.data.receiver_uid + '/picture')
												})
														);
												infoWindow.open(m, marker);
											}
										});
									});

									/*contentString = "<table style='margin-bottom:0px;'><tr><td rowspan='2'><img src='//graph.facebook.com/" +
									 response.places[i].place_uid + "/picture?width=50&height=50'></td><td>" +
									 response.places[i].title + "</td>" +
									 "<tr><td><img src='images/pin_thank.png'> " + response.places[i].thank_count + '</td></tr></table>';
									 infoWindow.setContent(contentString);
									 infoWindow.open(m, marker);*/


								}
							})(marker, i));

							markers.push(marker);
						}

						var clusterStyles = [
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							},
							{
								textColor: 'white',
								url: './images/klaster_thank_64_3.png',
								height: 64,
								width: 64
							}
						];

						me.markerCluster = new MarkerClusterer(m, markers, {
							maxZoom: 30,
							styles: clusterStyles
						});

						google.maps.event.addListener(me.markerCluster, 'clusterclick', function(cluster) {
							var markers = cluster[0].a;

							if (m.getZoom() >= 15) {

								A.w(['Thanks'], function() {
									Base.getHTML({
										file: 'map_marker_line.php',
										include: true,
										host: false,
										callback: function(html) {

											var h = [];
											for (var i in markers) {

												var marker = markers[i];

												h.push(B.replaceTemplate(html, {
													'{{name}}': Thanks.getCaption(markers[i].data, true, true, false, false),
													'{{left_picture}}': '//graph.facebook.com/' + marker.data.sender_uid + '/picture',
													'{{right_picture}}': marker.data.receiver_net.indexOf('link_') !== -1
															? (
																	marker.data.logo
																	? marker.data.logo
																	: A.baseURL() + 'images/question.png'
																	)
															: ('//graph.facebook.com/' + marker.data.receiver_uid + '/picture')
												}));
											}
											infoWindow.setContent(h.join(''));
											infoWindow.setPosition(cluster[0].d);
											infoWindow.open(m);
										}
									});
								});
							}
						});
					}, ymfriends);
					break;
				default:
					console.error('You should set type as \'karma\', \'thanks\' or \'all\'');
			}

			var centerControlDiv = $('<div class="control_div"></div>');

			centerControlDiv.css({paddingTop: '8px'}).
					append(me.mapsButtons.y_m_friends).append(me.mapsButtons.places);

			centerControlDiv = centerControlDiv.get(0);
			centerControlDiv.index = 1;
			map.controls[google.maps.ControlPosition.TOP_CENTER].push(centerControlDiv);

			var rightControlDiv = $('<div class="right_control_div"></div>');
			rightControlDiv.append(me.mapsButtons.near_host);

			rightControlDiv = rightControlDiv.get(0);
			rightControlDiv.index = 1;
			map.controls[google.maps.ControlPosition.RIGHT_CENTER].push(rightControlDiv);


		});
	},
	hoverInit: function() {
		$('.map_but_host').unbind('mouseover').mouseover(function() {
			var obj = $(this);
			$('.map_but_hover', obj).show();
		}).unbind('mouseleave').mouseleave(function() {
			var obj = $(this);
			$('.map_but_hover', obj).hide();
		});
	},
	clearMarkers: function() {
		var me = this, i;
		for (i = 0; i < me.markers.length; i++) {
			google.maps.event.clearListeners(me.markers[i], 'click');
			me.markers[i].setMap(null);
		}

		me.markers = [];
		if (me.markerCluster instanceof MarkerClusterer) {
			me.markerCluster.clearMarkers();
		}

		me.markerCluster = {};
	},
	showWaiter: function(){
		$('.maps_waiter').fadeIn();
		Preloader.show('maps_waiter');
	},
	hideWaiter: function(){
		$('.maps_waiter').fadeOut();
	},
	getKarmaPlaces: function(map, callback) {
		var me = this;
		me.setCenter();
		me.showWaiter();
		Base.get({
			script: 'index.php?r=user/get-geo-karma-places',
			ok: function(response) {
				me.unBlockButtons();
				callback(map, me.mapsId, response, me.markers);
				me.hideWaiter();
			},
			no: function(response) {
				me.unBlockButtons();
				console.error(response);
				me.hideWaiter();
			}
		});
	},
	getThanksPlaces: function(map, callback, filter) {
		var me = this;
		me.setCenter();
		me.showWaiter();
		Base.get({
			script: 'index.php?r=user/get-geo-thank-places',
			near: filter && filter === 'thanks_near'
					? 1
					: 0,
			ok: function(response) {
				me.unBlockButtons();
				callback(map, response, me.markers);
				me.hideWaiter();
			},
			no: function(response) {
				me.unBlockButtons();
				me.hideWaiter();
			}
		});
	},
	getAllThanks: function(map, callback, filter) {
		var me = this;
		me.showWaiter();
		Base.get({
			script: 'index.php?r=user/get-all-thanks',
			filter: filter || '',
			ok: function(response) {
				me.unBlockButtons();
				callback(map, response, me.markers);
				me.hideWaiter();
			},
			no: function(response) {
				me.unBlockButtons();
				me.hideWaiter();
				console.log(response);
			}
		});
	},
	clickToKarmaBtn: function() {
		if (!this.mapsButtons.places.hasClass('block')) {
			this.showMap('karma');
		}
	},
	clickToThanksBtn: function(filter) {
		if (!this.mapsButtons.places.hasClass('block')) {
			this.showMap('thanks', filter);
		}
	},
	clickToAllThanks: function() {
		if (!this.mapsButtons.places.hasClass('block')) {
			this.showMap('all');
		}
	},
	clickToAllThanksM: function() {
		if (!this.mapsButtons.y_m_friends.hasClass('block')) {
			this.showMap('all', 'm');
		}
	},
	clickToAllThanksY: function() {
		if (!this.mapsButtons.y_m_friends.hasClass('block')) {
			this.showMap('all', 'y');
		}
	},
	clickToAllThanksFriends: function(filter) {
		if (!this.mapsButtons.y_m_friends.hasClass('block')) {
			this.showMap('all', filter);
		}
	},
	toggleActive: function(item) {
		if (!item.parent().hasClass('block')) {
			item.parent().children().removeClass('active');
			$('#map_thanks').removeClass('active');
			item.addClass('active');
		}
	},
	hide: function() {
		$('#' + this.mapsId).slideUp();	
	},
	blockButtons: function() {
		var me = this;
		me.mapsButtons.y_m_friends.addClass('block');
		me.mapsButtons.places.addClass('block');
	},
	unBlockButtons: function() {
		var me = this;
		me.mapsButtons.y_m_friends.removeClass('block');
		me.mapsButtons.places.removeClass('block');
	}
};