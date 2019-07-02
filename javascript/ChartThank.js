var ChartThank = {
	whatByhost: function(hostname) {
		return hostname == 'thanks_chart'
				? 'user/graph'
				: 'user/karmagraph';
	},
	placeSwitchers: function(input) {

		var that = this;

		Switchers.place({
			host: input.host,
			id: input.id,
			cls: 'switcher',
			unselected: 'switch_unselected',
			last: 'switch_unselected_last',
			selected: 'switch_selected',
			cur: 1,
			but: input.uid
					? [
						{
							title: '<div class="red_round" title="' + T.out('1_year') + '">' + T.out('1y') + '</div>',
							action: function() {
								that.get({
									period: 'year',
									what: that.whatByhost(input.hostname),
									callback: function(response) {
										that.data[input.hostname] = response;
										that.out(response, input.hostname);
									}
								});
							}
						}
					]
					: [
						{
							title: '<div class="red_round" title="' + T.out('1_month') + '">' + T.out('1m') + '</div>',
							action: function() {
								that.get({
									period: 'month',
									what: that.whatByhost(input.hostname),
									callback: function(response) {
										that.out(response, input.hostname);
										that.data[input.hostname] = response;
									}
								});
							}
						},
						{
							title: '<div class="red_round" title="' + T.out('1_year') + '">' + T.out('1y') + '</div>',
							action: function() {
								that.get({
									period: 'year',
									what: that.whatByhost(input.hostname),
									callback: function(response) {
										that.data[input.hostname] = response;
										that.out(response, input.hostname);
									}
								});
							}
						}
					]
		});

	},
	init: function(hostname, uid, place) {
		var that = this;

		if (!window.Chart) {
			Base.loadRemote(Application.baseUrl + '/js_minified/Chart.min.js', function() {
				that.init(hostname, uid, place);
			});
			return false;
		}

		Base.wait(function() {
			if (window.Switchers) {
				return true;
			} else {
				return false;
			}
		}, function() {

			that.placeSwitchers({
				host: $('.chart_switcher_host', $('.' + hostname)),
				hostname: hostname,
				id: 'chart_switcher_' + hostname,
				uid: place
						? place
						: uid
			});

			Switchers.list['chart_switcher_' + hostname].switch(0, 'noaction');

			that.get({
				period: !uid
						? 'month'
						: 'year',
				place: place
						? true
						: false,
				place_uid : place ? uid : null,
				place_net : place ? place : null,
				what: that.whatByhost(hostname),
				user_id: !uid
						? false
						: uid,
				callback: function(response) {
					User.karmaLoaded = true;
					that.out(response, hostname);
					that.data[hostname] = response;
					
					if (place) {
						$('.place_karma.uid_' + uid + '.net_fb').html(response.karma);
					}
					
				}
			});

		});
	},
	/**
	 *	what - 
	 *	period
	 *	callback 
	 */
	get: function(input) {

		Base.get({
			script: 'index.php',
			r: input.what,
			place: input.place
					? input.place
					: false,
			place_uid: input.place ? input.place_uid : null,
			place_net: input.place ? input.place_net : null,
			user_id: !input.user_id
					? false
					: input.user_id,
			period: input
					? input.period
					: 'month',
			ok: function(response) {
				if (input.callback) {
					input.callback(response);
				}
			}
		});
	},
	data: {},
	show_my: true,
	show_me: true,
	getColor: function(hostname) {
		if (hostname == 'karma_chart' || hostname == 'karma_chart_dialog') {
			return [
				'rgba(51, 102, 153, 1)',
				'rgba(255, 143, 42, 1)'
			];
		}
		return false;
	},
	out: function(response, hostname) { //make it independent
		var that = this;

		var color = that.getColor(hostname);

		var host = $('.' + hostname);

		Chart.defaults.global.scaleFontSize = hostname === 'karma_chart' || hostname == 'karma_chart_dialog'
				? 12
				: 12;
		Chart.defaults.global.scaleFontColor = hostname === 'karma_chart' || hostname == 'karma_chart_dialog'
				? 'rgba(51,102,153,1);'
				: 'rgba(51,102,153,1);'; //'rgba(245,202,83,0)';
		Chart.defaults.global.scaleLineColor = "rgba(51,102,153,0.5)";
		Chart.defaults.global.scaleShowLabels = true;
		Chart.defaults.global.scaleIntegersOnly = hostname === 'karma_chart' || hostname == 'karma_chart_dialog'
				? false
				: true;
		Chart.defaults.global.scaleLabel = '<%=Math.round(value*100)/100%>';
		Chart.defaults.global.showXAxisLabel = false;


		//TODO: calendar label instead of real

		var labels = [];
		var receiver = [];
		for (var i in response.receiver) {
			var a = i.split('-');
			labels.push(a[2] + '.' + a[1] + '.' + a[0].split('20')[1]);
			receiver.push(response.receiver[i]);
		}

		var sender = [];
		for (var i in response.sender) {
			sender.push(response.sender[i]);
		}

		$('.chart_host', host).html('<div class="myChartWrapper_' + hostname + ' pa" style="overflow-x:auto; overflow-y:hidden; width:360px; height:320px; top:10px;"><div class="pr" style="width:' + (labels.length < 9
				? '360'
				: '800') + 'px; ' + (hostname === 'karma_chart' || hostname == 'karma_chart_dialog'
				? 'height:355px; overflow:hidden;'
				: 'height:355px;') + '"><canvas id="myChart_' + hostname + '" class="pa" style="left:' + (hostname === 'karma_chart' || hostname == 'karma_chart_dialog'
				? '5'
				: '5') + 'px; top:0px;" width="' + (labels.length < 9
				? '360'
				: '800') + '" height="360"></canvas></div>' +
				'</div>' +
				'<div class="pa" style="width:45px; height:320px; border:0px solid black; overflow:hidden; left:0px; top:10px; background:white;">' +
				'<canvas id="repeatChart_' + hostname + '" class="pa" style="left:' + (hostname === 'karma_chart' || hostname == 'karma_chart_dialog'
						? '5'
						: '5') + 'px; top:0px;" width="' + (labels.length < 9
				? '360'
				: '800') + '" height="360"></canvas>' +
				'</div>' +
				'<div class="legendDiv"></div>');

		var ctx = $("#myChart_" + hostname).get(0).getContext("2d"); //TODO:make it settable
		var repeat_ctx = $("#repeatChart_" + hostname).get(0).getContext("2d");
		$('.myChartWrapper_' + hostname).scrollLeft(440);

		if (labels) {

			var empty_labels = labels;
			/*
			 for (var i in labels) {
			 var a = labels[i].split('.');
			 empty_labels.push('');
			 }*/

			empty_labels[0] = '';
			empty_labels[1] = '';

			//var empty_labels = labels;


			var data = {
				labels: empty_labels,
				datasets: [
					sender && that.show_me
							? {
								label: T.out('i_thank_chart'),
								fillColor: "rgba(51,102,153,0)",
								strokeColor: color
										? color[0]
										: "rgba(51,102,153,1)",
								pointColor: color
										? color[0]
										: "rgba(51,102,153,1)",
								pointDotStrokeWidth: 1,
								pointStrokeColor: "#fff",
								pointHighlightFill: "#fff",
								pointHighlightStroke: color
										? color[0]
										: "rgba(51,102,153,1)",
								data: sender,
								showXAxisLabel: false
							}
					: false,
					receiver && that.show_my
							? {
								label: T.out('thank_me_chart'),
								fillColor: "rgba(51,102,153,0)",
								strokeColor: color
										? color[1]
										: "rgba(51,102,153,1)",
								pointColor: color
										? color[1]
										: "rgba(51,102,153,1)",
								pointStrokeColor: "#fff",
								pointDotStrokeWidth: 1,
								pointHighlightFill: "#fff",
								pointHighlightStroke: color
										? color[1]
										: "rgba(0,128,0,1)",
								pointHitDetectionRadius: 1,
								data: receiver,
								showXAxisLabel: false
							}
					: false
				]
			};

			var repeat_data = {
				labels: empty_labels,
				datasets: [
					sender && that.show_me
							? {
								label: T.out('i_thank_chart'),
								fillColor: "rgba(51,102,153,0)",
								strokeColor: "rgba(51,102,153,0)",
								pointColor: "rgba(51,102,153,0)",
								pointDotStrokeWidth: 1,
								pointStrokeColor: "#fff",
								pointHighlightFill: "#fff",
								pointHighlightStroke: "rgba(51,102,153,0)",
								data: sender
							}
					: false,
					receiver && that.show_my
							? {
								label: T.out('thank_me_chart'),
								fillColor: "rgba(0,128,0,0)",
								strokeColor: "rgba(51,102,153,0)",
								pointColor: "rgba(51,102,153,0)",
								pointStrokeColor: "#fff",
								pointDotStrokeWidth: 1,
								pointHighlightFill: "#fff",
								pointHighlightStroke: "rgba(51,102,153,0)",
								pointHitDetectionRadius: 1,
								data: receiver
							}
					: false
				]
			};

			var myLineChart = new Chart(ctx).Line(data, {
				scaleGridLineColor: "rgba(51,102,153,0.5)",
				pointDot: false,
				datasetStrokeWidth: 2,
				pointHitDetectionRadius: 1,
				scaleFontColor: 'rgba(51,102,153,0)',
				bezierCurve: false,
				showXAxisLabel: false
			});

			var myLineChart2 = new Chart(repeat_ctx).Line(repeat_data, {
				scaleGridLineColor: "rgba(51,102,153,0.5)",
				pointDot: false,
				datasetStrokeWidth: 2,
				pointHitDetectionRadius: 1,
				scaleFontColor: 'rgba(51,102,153,1)',
				bezierCurve: false,
				showXAxisLabel: false
			});

			$('.chart_my', host).unbind('mousedown').mousedown(function() {
				Base.press($(this));
				if (that.show_my) {
					that.show_my = false;
					$(this).css({
						opacity: 0.5
					});
				} else {
					that.show_my = true;
					$(this).css({
						opacity: 1
					});
				}

				var outputhost = 'thanks_chart';

				if (host.hasClass('karma_chart')) {
					outputhost = 'karma_chart';
				}

				if (host.hasClass('karma_chart_dialog')) {
					outputhost = 'karma_chart_dialog';
				}

				that.out(that.data[outputhost], B.getID($(this), 'role_'));
				History.switcherControl();
			});

			$('.chart_me', host).unbind('mousedown').mousedown(function() {
				Base.press($(this));
				if (that.show_me) {
					that.show_me = false;
					$(this).css({
						opacity: 0.5
					});
				} else {
					that.show_me = true;
					$(this).css({
						opacity: 1
					});
				}

				var outputhost = 'thanks_chart';

				if (host.hasClass('karma_chart')) {
					outputhost = 'karma_chart';
				}

				if (host.hasClass('karma_chart_dialog')) {
					outputhost = 'karma_chart_dialog';
				}

				that.out(that.data[outputhost], B.getID($(this), 'role_'));
				History.switcherControl();
			});

		}
	},
	outText: function(host_name, text) {

		var canvas = $("#repeatChart_" + hostname).get(0).getContext("2d");

		canvas.fillText(text, 0, 0);

	}

};