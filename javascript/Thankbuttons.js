var Thankbuttons = {
	ready: false,
	choose: function() {
		var that = this;

		if (!Application.logged) {
			User.requestLogin(function(response) {
				User.logged(response);
				that.choose();
			});
			return;
		}

		Dialog.show({
			title: '',
			message: 'choosethankbutton.php',
			noexit: true,
			onShow: function() {

				Buttons.place({
					title: T.out('QR_BUTTON2'),
					host: $('.get_qr_button_host'),
					action: function() {
						Dialog.hide(function() {
							that.QRbutton();
						});
					},
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					},
					class: 'transparent_button'
				});

				Buttons.place({
					title: T.out('QR_BUTTON3'),
					host: $('.thank_from_company_button_host'),
					action: function() {
						Dialog.hide(function() {
							that.QRbuttonReverted();
						});
					},
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					},
					class: 'transparent_button'
				});

				Buttons.place({
					title: '<a style="width:100%; height:100%; position:absolute; left:0px; top:0px; color:white; text-decoration:none; padding-top:10px;" href="' + A.baseURL() + '?r=site/out&t=mythank" target="_blank">' + T.out('collect_thank_qr') + '</a>',
					host: $('.thank_me_qr_button_host'),
					action: function() {

					},
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					},
					class: 'transparent_button'
				});

				Buttons.place({
					title: T.out('link_qr2'),
					host: $('.get_link_qr_button_host'),
					action: function() {
						that.openSendThankLinkDialog();
					},
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					},
					class: 'transparent_button'
				});

				Buttons.place({
					title: T.out('GET_THANK_BUTTON3'),
					host: $('.get_thank_button_host'),
					action: function() {
						Dialog.hide(function() {
							that.show();
						});
					},
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					},
					class: 'transparent_button'
				});

				Buttons.place({
					title: 'LOGOUT',
					host: $('.logout_button_host'),
					action: function() {
						User.logout();
					},
					class: 'transparent_button',
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					}
				});

				//KARMA GRAPH / ГРАФИК КАРМЫ

				Buttons.place({
					title: T.out('KARMA_GRAPH_1717'),
					host: $('.history_button_host'),
					action: function() {
						that.getGraph();
					},
					class: 'transparent_button',
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					}
				});

				Buttons.place({
					title: T.out('THANKS_HISTORY_1717'),
					host: $('.history_thanks_button_host'),
					action: function() {

						Dialog.hide(function() {
							Dialog.show({
								title: '<div style="margin-bottom:-10px; margin-left:50px;" class="ac">' + T.out('THANKS_HISTORY_1717') + '</div>',
								message: 'history_in_dialog.php',
								onShow: function() {
									History.thanks();
								}
							});
						});

					},
					class: 'transparent_button',
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					}
				});

				Buttons.place({
					title: T.out('charity'),
					host: $('.total_statistics_button_host'),
					action: function() {
						that.loadCharityStatistics();
					},
					class: 'transparent_button',
					outer: {
						css: {
							width: '360px'
						}
					},
					css: {
						width: '360px'
					}
				});

				/*
				 Buttons.place({
				 title: T.out('HISTORY'),
				 host: $('.history_button_host'),
				 action: function() {
				 that.getHistory();
				 },
				 class: 'transparent_button',
				 outer: {
				 css: {
				 width: '300px'
				 }
				 },
				 css: {
				 width: '300px'
				 }
				 });
				 */
				Buttons.place(
						{
							title: 'OK',
							host: $('.dont_want_to_choose'),
							action: function() {
								Dialog.hide();
							},
							class: ['round', 'transparent_button'],
							css: {
								width: '40px',
								height: '33px',
								'padding-top': '7px'
							},
							outer: {
								css: {
									width: '45px'
								}
							}
						}
				);

			}
		});
	},
	loadCharityStatistics: function() {
		var that = this;
		B.get({
			script: 'index.php?r=user/my-charity',
			//r: 'user/myCharity',
			ok: function(response) {
				Dialog.hide(function() {

					var message = 'No data'

					if (response.data) {

						var h = [
							'<div style="max-height:300px; margin-bottom:25px; overflow:auto; font-size:0.8rem;"><table><tr><td>Date</td><td class="ac">Amount</td><td>Project</td></tr>'
						];

						for (var i in response.data) {
							h.push('<tr><td>' + response.data[i].changed.split(' ')[0].split('-').join('.') + '</td><td class="ac">' + response.data[i].amount + '</td><td>' + response.data[i].project + '</td></tr>');
						}

						h.push('</table></div>');


						message = h.join('');
					}

					Dialog.show({
						title: T.out('charity'),
						message: message
					});
				});
			},
			no: function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.loadCharityStatistics();
					});
				}
			}
		})
	},
	loadStatistics: function() {
		B.get({
			script: 'index.php?r=user/total-statistics',
			//r: 'user/totalStatistics',
			Admins: 'get',
			ok: function(response) {
				console.log(response);
			}
		});
	},
	link: function() {
		Dialog.show({
			title: '<div style="font-size:1.2rem; padding-left:20px; padding-top:10px; width:100%; text-align:left;">' + T.out('INSERT_IT_IN_ANY_TEXT5') + '</div>',
			message: 'thanklink.php',
			onShow: function() {
				//copy_to_clipboard_host
				/*Buttons.place({
				 title: T.out('COPY_TO_CLIPBOARD2'),
				 host: $('.copy_to_clipboard_host'),
				 action: function() {
				 window.prompt(T.out('ctrl_c'), $('.thank_link_code').val());
				 },
				 class: ['transparent_button'],
				 outer: {
				 css: {
				 float: 'right'
				 }
				 }
				 });*/
				/*
				 $('.thank_link_code').focus();
				 $('.thank_link_code').select();*/

				/*
				 $('#thank_link_code').unbind('focus').focus(function() {
				 console.log('set focus');
				 Thankbuttons.select();
				 });
				 */
				setTimeout(function() {
					$('#thank_link_code').focus().select();
				}, 500);

			}
		});
	},
	getHistory: function() {
		Dialog.hide(function() {
			Dialog.show({
				title: '<div style="margin-bottom:-10px; margin-left:50px;" class="ac">' + T.out('HISTORY') + '</div>',
				message: '<div class="history_in_dialog_host"></div>',
				onShow: function() {
					Pagers.list['history_in_dialog_host'] = undefined;
					Thanks.get({
						host: $('.history_in_dialog_host'),
						name: 'history_in_dialog_host',
						filter: {
							get: 'me',
							start: 0,
							page: 7
						},
						afterOutput: function(data) {

							$('.thank_history_line', $('.history_in_dialog_host')).each(function() {
								var id = B.getId($(this), 'thank_history_');

								if (data.pressed && data.pressed['thank_77_back_' + id]) {

									$('tr', $('tbody', $(this)))
											.append('<td><div style="width:100px; height:20px; top:0px; float:right;" class="pr">' +
													'<div class="ac pr thank_back_but_pressed thank_77_back_' + id + '"><img src="images/saythankyou_transparent_' + T.locale + '.png" style="width:100px;"/></div>' +
													'</div></td>');
									$(this).css({
										width: '100%'
									});

								} else {
									$('tr', $('tbody', $(this)))
											.append('<td><div style="width:100px; height:20px; top:0px; float:right;" class="pr">' +
													'<div class="ac pr cp thank_back_but thank_77_back_' + id + '"><img src="images/saythankyou_fill_' + T.locale + '.png" style="width:100px;"/></div>' +
													'</div></td>');
									$(this).css({
										width: '100%'
									});
								}

							});

							B.click($('.thank_back_but'), function(obj) {
								var id0 = B.getId(obj, 'thank_77_back_');
								for (var i in data) {
									var record = data[i];
									if (record.id && record.id == id0) {

										//	console.log('record', record);

										B.post({
											script: 'index.php?r=user/press',
											//r: 'user/press',
											Pressed: 'add',
											selector: 'thank_77_back_' + id0
										});

										obj.removeClass('thank_back_but').addClass('thank_back_but_pressed').unbind('click');
										$('img', obj).attr({
											src: 'images/saythankyou_fill_' + T.locale + '.png'
										});

										Dialog.hide();
										Menu.switchPage('send', function() {
											if (record.place) {
												Send.select_place({
													uid: record.sender_uid,
													net: record.sender_uid,
													title: record.place
												});
											} else {
												Send.select(record.sender_uid);
											}
										}, 'redrawMenu');
										break;
									}
								}
							});

						} //afterOutput
					});
				}
			});
		});
	},
	getGraph: function() {
		Dialog.hide(function() {
			Dialog.show({
				title: '<div style="margin-bottom:-10px; margin-left:50px;" class="ac">' + T.out('KARMA_GRAPH_1717') + '</div>',
				message: 'karmacharthost.php',
				onShow: function() {
					Statistics.getKarma(false, false, 'karma_chart_dialog');
				}
			});
		});
	},
	backlink: function() {
		Dialog.show({
			title: '<div style="font-size:1.2rem; padding-left:20px; padding-top:10px; width:100%; text-align:left;">' + T.out('INSERT_IT_IN_ANY_TEXT5') + '</div>',
			message: 'backlink.php',
			onShow: function() {
				setTimeout(function() {
					$('#thank_link_code').focus().select();
				}, 500);
			}
		});
	},
	select: function() {
		document.getElementById('thank_link_code').focus();
		document.getElementById('thank_link_code').select();
	},
	del: function(id, ask, reverted) {
		var that = this;
		Base.post({
			script: 'index.php?r=user/buttons',
			//r: 'user/buttons',
			Buttons: 'del',
			id: id,
			ask: ask,
			reverted: reverted,
			ok: function(response) {
				if (response.ok) {
					Dialog.hide(function() {
						if (response.action) {
							if (response.action == 'usial') {
								that.show();
							} else {
								if (reverted) {
									that.QRbuttonReverted();
								} else {
									that.QRbutton();
								}
							}
						} else {
							that.show();
						}
					});
				} else {
					if (response.ask) {
						Dialog.hide(function() {
							Dialog.show({
								title: '',
								message: '<div>' + response.message + ' </div><div class="confirm_del_internal_button_host" style="padding-bottom:20px; padding-top:10px;"></div>',
								onShow: function() {
									Buttons.place({
										id: 'confirm_but_deletion',
										title: T.out('YES'),
										host: $('.confirm_del_internal_button_host'),
										action: function() {
											that.del(id, false, reverted);
										},
										refresh: 1,
										class: 'transparent_button'
									});
								}
							});
						});
					} else {
						if (response.error === 'login_error') {
							User.notLogged(function() {
								that.del(id, ask, reverted);
							});
						}
					}
				}
			}
		});
	},
	/**
	 *  d - default values of fields
	 */
	show: function(d) {
		var that = this;

		//console.log(d);

		Dialog.show({
			message: 'thankbutton.php',
			title: '<div style="font-size:1.2rem; padding-left:20px; padding-top:10px; width:100%; text-align:center;">' + T.out('GET_THANK_BUTTON3') + '</div>',
			onShow: function() {

				//TODO: create new button

				Buttons.place({
					id: 'request_thank',
					title: T.out('REQUEST'),
					host: $('.request_thank_button_host'),
					action: function() {
						that.create();
					},
					refresh: 1,
					class: 'transparent_button'
				});

				CheckBoxes.place({
					host: $('.button_post_to_timeline_host'),
					id: 'button_post_to_timeline',
					title: T.out('post_timeline'),
					css: {
						'margin-left': '-5px',
						'margin-top': '-5px'
					},
					action: function(status) {

					}
				});

				CheckBoxes.list['button_post_to_timeline'].setStatus(1);

				$('.button_post_to_timeline_hosts').each(function() {
					var id = B.getId($(this), 'button_post_to_timeline_host_');

					CheckBoxes.place({
						host: $(this),
						id: 'button_post_to_timeline_' + id,
						title: T.out('post_timeline'),
						css: {
							'margin-left': '-5px',
							'margin-top': '-5px'
						},
						action: function(status) {

						}
					});

					CheckBoxes.list['button_post_to_timeline_' + id].setStatus(
							$(this).hasClass('posttimeline_1')
							? 1
							: 0
							);

					$('.checkbox_button_post_to_timeline_' + id).css({
						color: 'rgb(249,217,118)',
						border: '1px solid rgb(249,217,118)'
					});

					$('.checkbox_title_button_post_to_timeline_' + id).css({
						color: 'rgb(249,217,118)'
					});

				});

				$('.required', $('.thankbuttonform')).unbind('mousedown').mousedown(function() {
					that.control($(this));
				}).unbind('mouseup').mouseup(function() {
					that.control($(this));
				}).unbind('keyup').keyup(function() {
					that.control($(this));
				}).unbind('change').change(function() {
					that.control($(this));
				});

				if (d) {
					for (var field in d) {
						var val = d[field];
						$('.buttons_field_' + field).val(val);
					}
				}

				that.control();

				//logo_selector_host  
				/*
				 Buttons.place({
				 title: T.out('GET_FROM_SITE'),
				 host: $('.logo_selector_host_but'),
				 action: function() {
				 if (!$('.buttons_field_www').val() && $('.buttons_field_logo').val()) {
				 $('.buttons_field_www').val($('.buttons_field_logo').val());
				 $('.buttons_field_logo').val('');
				 }
				 that.getImagesFromURL($('.buttons_field_www').val());
				 },
				 class: 'transparent_button round',
				 css: {
				 width: '60px',
				 height: '60px',
				 'line-height': '20px'
				 },
				 outer: {
				 css: {
				 width: '60px',
				 height: '60px'
				 }
				 }
				 });
				 */
				$('.delete_internal_button').unbind('mousedown').mousedown(function() {
					Base.press($(this));
					var id = $(this).attr('alt');
					that.del(id, true);
				});

				$('.edit_internal_button').unbind('mousedown').mousedown(function() {
					Base.press($(this));
					var id = $(this).attr('alt');
					that.edit(id);
				});

				$('.button_field_save_changes').unbind('mousedown').mousedown(function() {
					B.press($(this));
					that.buttonDataSave(B.getId($(this), 'button_field_save_changes_'));
				});

				var prefix_path = Application.baseUrl
						? Application.appSourcePath.split(Application.baseUrl)[0]
						: '';

				var cropper_path = prefix_path + '/vh2015/startscript/Cropper.js';

				B.loadRemote(cropper_path);

				var path = prefix_path + '/vh2015/startscript/Uploader.js';

				B.loadRemote(path, function() {

					$('.upload_logo_host').each(function() {

						var button_id = B.getId($(this), 'upload_logo_host_');
						var file_name = 'Buttons_' + button_id;

						Uploaders.uploaders[file_name] = new Uploader({
							host: $('.upload_logo_host_' + button_id),
							name: file_name,
							script: 'index.php?r=user/buttons',
							get: {
								//r: 'user/buttons',
								Buttons: 'upload',
								button_id: button_id
							},
							maxsize: 3 * 1048576,
							multi: false,
							title: '',
							onRemoveOnError: function(id) {
								if (id) {
									$('.remove_file_host_' + id.split('file_ready_')[1]).remove();
								}
							},
							error: function(errors) {
								if (!errors) {
									return;
								}
								console.log(errors);
							},
							change: false, //we submit on change
							previewer: false //make it true
						});

					}); //each logo host

				}); //Uploader load


				//Colorpicker
				B.loadStyle(Application.appSourcePath + '/js_minified/spectrum/spectrum.css', function() {
					B.loadRemote(Application.appSourcePath + '/js_minified/spectrum/spectrum.js', function() {
						$('.color_picker').each(function() {
							$(this).spectrum({
								allowEmpty: true,
								/*showAlpha: true,
								 showInput: true*/
								showPaletteOnly: true,
								togglePaletteOnly: true,
								togglePaletteMoreText: 'more',
								togglePaletteLessText: 'less',
								palette: [
									["#f00", "#f90", "#ff0", "#0f0"],
									["#0ff", "#00f", "#90f", "#f0f"],
									["rgb(194,45,48)", "#e69138", "#f1c232", "#6aa84f"],
									["#45818e", "#3d85c6", "#674ea7", "#ffffff"]
								]
							}).unbind('change').change(function() {
								var id = B.getId($(this), 'color_picker_for_button_');

								var css = that.collectColorData(id);

								$('.button_preview_' + id).attr({
									src: Application.appSourcePath + '/?r=site/button&id=' + id +
											'&text=' + css.color.text +
											'&border=' + css.color.border +
											'&background=' + css.color.background +
											'&hover_text=' + css.hover.text +
											'&hover_border=' + css.hover.border +
											'&hover_background=' + css.hover.background
								});

								that.edit(id);

							});
						});
					});
				});

			}
		});


	},
	collectColorData: function(id) {

		var color = {
			text: $('.cp_text_' + id).spectrum('get'),
			border: $('.cp_border_' + id).spectrum('get'),
			background: $('.cp_background_' + id).spectrum('get')
		};

		var hover = {
			text: $('.cp_hover_text_' + id).spectrum('get'),
			border: $('.cp_hover_border_' + id).spectrum('get'),
			background: $('.cp_hover_background_' + id).spectrum('get')
		}

		return {
			color: {
				text: color.text
						? color.text.toRgbString()
						: 'none',
				border: color.border
						? color.border.toRgbString()
						: 'none',
				background: color.background
						? color.background.toRgbString()
						: 'none'
			},
			hover: {
				text: hover.text
						? hover.text.toRgbString()
						: 'none',
				border: hover.border
						? hover.border.toRgbString()
						: 'none',
				background: hover.background
						? hover.background.toRgbString()
						: 'none'
			}
		};

	},
	buttonDataSave: function(id) {
		var that = this;

		Base.post({
			script: 'index.php?r=user/buttons',
			//r: 'user/buttons',
			Buttons: 'save',
			id: id,
			logo: $('.buttons_field_logo_' + id).val(),
			title: $('.buttons_field_title_' + id).val(),
			css: that.collectColorData(id),
			posttimeline: CheckBoxes.list['button_post_to_timeline_' + id].status,
			ok: function(response) {
				if (response && response.ok) {
					$('.edit_button_fields').slideUp();
				}
			},
			no: function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.buttonDataSave();
					});
				}
			}
		});
	},
	edit: function(id) {
		var menu_host = $('.edit_button_fields_' + id);
		$('.edit_button_fields').hide();

		if (menu_host.is(':visible')) {

		} else {
			/*
			 */
			menu_host.slideDown(function() {
				setTimeout(function() {
					$('.thankbutton_operations_host').animate({
						scrollTop: $('.edit_button_fields_' + id).position().top
					}, 500);
				}, 100);
			});
		}

	},
	parsedImages: {},
	parsedTitles: {},
	defaultURL: false,
	getTitleFromURL: function(url, callback) {
		var that = this;
		if (B.isURL(url)) {
			if (that.parsedTitles[url]) {
				if (callback) {
					callback(that.parsedTitles[url]);
				}
			} else {
				Base.get({
					script: 'index.php?r=user/buttons',
					//r: 'user/buttons',
					Buttons: 'title',
					url: url,
					ok: function(response) {
						that.parsedTitles[url] = response && response['title']
								? response['title']
								: '';
						if (callback) {
							callback(that.parsedTitles[url]);
						}
					},
					no: function(response) {
						if (response.error === 'login_error') {
							User.notLogged(function() {
								that.getTitleFromURL();
							});
						}
					}
				});
			}
		}
	},
	getImagesFromURL: function(url) {
		var that = this;
		if (that.parsedImages[url]) {
			//console.log('from_cash');
			that.out(that.parsedImages[url]);
			that.defaultURL = url;
		} else {

			if (!Base.isURL(url)) {
				$('.buttons_field_title').val('');
				$('.logo_selector_host').html('');

				Dialog.hide(function() {
					Dialog.show({
						title: T.out('correct_url_expected'),
						message: '',
						no: function() {
							that.show();
						}
					});
				});
			}

			Base.get({
				script: 'index.php?r=user/buttons',
				//r: 'user/buttons',
				Buttons: 'images',
				url: url,
				ok: function(response) {
					//console.log(response);
					if (response && response[url]) {
						that.parsedImages[url] = response[url];
						that.out(response[url]);
						that.defaultURL = url;
					}
				},
				no: function(response) {
					if (response.error === 'login_error') {
						User.notLogged(function() {
							that.getImagesFromURL();
						});
					}
				}
			})
		}
	},
	uploadError: function(input) {
		Dialog.hide(function() {
			Dialog.show({
				title: input.message
			});
		});
	},
	cropUploaded: function(input) {

		var that = this;

		that.back_values = {
			www: $('.buttons_field_www').val(),
			title: $('.buttons_field_title').val(),
			email: $('.buttons_field_email').val(),
			name: $('.buttons_field_name').val()
		};

		//console.log(that.back_values);

		Dialog.hide(function() {
			Dialog.show({
				title: '<div style="text-align:center;">' + T.out('Crop Image') + '</div>',
				message: '<div class="cropper_host" style="margin-bottom:0px; margin-top:30px;"></div><div style="margin:10px;" class="run_crop_button_host"></div>',
				noexit: true,
				onShow: function() {

					Cropper.show({
						host: $('.cropper_host'),
						image: input,
						ratio: 1,
						innerHTML: '<div class="pa round" style="width:100%; height:100%; left:0px; top:0px; border:400px solid rgba(196,43,44,0.8); margin-left:-400px; margin-top:-400px;"></div>'
					});

					Buttons.place({
						host: $('.run_crop_button_host'),
						title: T.out('CROP'),
						id: 'crop_image',
						refresh: true,
						class: 'transparent_button',
						action: function() {

							var post = Cropper.get();
							post['button_id'] = input.button_id;
							post['ok'] = function(response) {
								if (response.ok) {
									Dialog.hide(function() {
										//console.log(that.back_values);
										that.show(that.back_values);
									});
								}
							};

							post['no'] = function(response) {
								if (response.error === 'login_error') {
									User.notLogged(function() {
										that.onShow();
									});
								}
							}

							post['script'] = 'index.php';
							post['r'] = 'user/buttons';
							post['Buttons'] = 'crop';
							post['button_id'] = input.button_id;

							Base.post(post);
						}
					});

					//$('.run_crop_button_host')
				}
			});
		});
	},
	out: function(data) {
		$('.buttons_field_title').val(data.title);
		var h = [];
		for (var i in data.images) {
			h.push('<div style="width:50px; height:50px; overflow:hidden; display:inline-block; margin:5px;" class="round select_logo_host"><img src="' + data.images[i].src + '" alt="' + data.images[i].src + '"  style="height:50px;" class="cp select_logo"/></div>')
		}

		$('.logo_selector_host').html(h);

		$('.select_logo').unbind('mousedown').mousedown(function() {
			$('.select_logo_host').hide();
			$('.buttons_field_logo').val($(this).attr('alt'));
			$(this).parent().show();
		});
	},
	control: function(obj) {

		var that = this;

		if (obj && obj.hasClass('buttons_field_www')) {
			that.getTitleFromURL($('.buttons_field_www').val(), function(title) {
				$('.buttons_field_title').val(title);
			});
		}

		var ok = true;
		$('.required', $('.thankbuttonform')).each(function() {
			if (!$(this).val()) {
				ok = false;
				//console.log(Buttons.list);
				Buttons.list['request_thank'].disable();
				return false;
			}
		});
		if (ok) {
			Buttons.list['request_thank'].enable();
			return true;
		} else {
			return false;
		}
	},
	create: function() {
		var that = this;
		var post = {};
		$('.field', $('.thankbuttonform')).each(function() {
			var field = $(this).attr('class').split('buttons_field_')[1].split(' ')[0];
			if ($(this).val()) {
				post[field] = $(this).val();
			}
		});

		if (this.control()) {
			post.script = 'index.php';
			post.r = 'user/buttons';
			post.Buttons = 'create';
			post.posttimeline = CheckBoxes.list['button_post_to_timeline'].status;

			post.ok = function(response) {
				if (response.ok) {
					Dialog.hide(function() {
						that.show();
					});
				}
			};

			post.no = function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.create();
					});
				}
			}

			Base.post(post);
		}
	},
	QRbuttonReverted: function() {
		var that = this;
		Dialog.show({
			title: '<div class="ac" style="width:100%; font-size:1.2rem; padding-left:0px; padding-top:5px;">' + T.out('QR_BUTTON3') + '</div>',
			message: 'qrcode_reverted.php',
			onShow: function() {
				//new_qrbutton_host
				if (Search && Search.placed['searchQRreverted']) {
					Search.placed['searchQRreverted'] = false;
				}

				//upload search menu
				Base.getHTML({//try to get switcher code if it is not already loaded
					file: 'search.php?host=searchQRreverted',
					selector: '.searchQRreverted',
					host: $('.searchQRreverted_host'),
					callback: function(response) {
						Search.setActions($('.searchQRreverted'), {
							select_place: function(input) {
								that.createQRrevertedButton(input.uid, input.title);
							}
						});
						$('.find_what', $('.searchQRreverted')).attr({
							placeholder: T.out('input page or place to search it in_facebook')
						});
					}
				});


				Base.get({
					script: 'index.php?r=user/buttons',
					//r: 'user/buttons',
					Buttons: 'qr_list_reverted',
					ok: function(response) {
						that.outputQRbuttonsReverted(response.buttons);
					},
					no: function(response) {
						if (response.error === 'login_error') {
							User.notLogged(function() {
								that.QRbuttonReverted();
							});
						}
					}
				});

			}
		});
	},
	QRbutton: function() {
		var that = this;
		Dialog.show({
			title: '<div class="ac" style="width:100%; font-size:1.2rem; padding-left:0px; padding-top:5px;">' + T.out('new_qr_code2') + '</div>',
			message: 'qrcode.php',
			onShow: function() {
				//new_qrbutton_host
				if (Search && Search.placed['searchQR']) {
					Search.placed['searchQR'] = false;
				}

				//upload search menu
				Base.getHTML({//try to get switcher code if it is not already loaded
					file: 'search.php?host=searchQR',
					selector: '.searchQR',
					host: $('.searchQR_host'),
					callback: function(response) {
						Search.setActions($('.searchQR'), {
							select_place: function(input) {
								that.createQRbutton(input.uid, input.title);
							}
						});
						$('.find_what', $('.searchQR')).attr({
							placeholder: T.out('input page or place to search it in_facebook')
						});
					}
				});

				Base.get({
					script: 'index.php?r=user/buttons',
					//r: 'user/buttons',
					Buttons: 'qr_list',
					ok: function(response) {
						//console.log(response);
						that.outputQRbuttons(response.buttons);
					},
					no: function(response) {
						if (response.error === 'login_error') {
							User.notLogged(function() {
								that.QRbutton();
							});
						}
					}
				});

			}
		});
	},
	createQRbutton: function(id, title) {
		var that = this;
		//create or reset new button
		Base.post({
			script: 'index.php?r=user/buttons',
			//r: 'user/buttons',
			Buttons: 'create_qr',
			id: id,
			title: title,
			ok: function(response) {
				if (response.buttons) {
					that.outputQRbuttons(response.buttons);
				}
			},
			no: function(error) {
				if (error.error == 'login_error') {
					User.notLogged(function() {
						that.createQRbutton(id, title);
					});
				}
			}
		});
		//get buttons list
	},
	createQRrevertedButton: function(id, title) {
		var that = this;
		//create or reset new button
		Base.post({
			script: 'index.php?r=user/buttons',
			//r: 'user/buttons',
			Buttons: 'create_qr_reverted',
			id: id,
			title: title,
			ok: function(response) {
				if (response.buttons) {
					that.outputQRbuttonsReverted(response.buttons);
				}
			},
			no: function(error) {
				if (error.error == 'login_error') {
					User.notLogged(function() {
						that.createQRrevertedButton(id, title);
					});
				}
			}
		});
		//get buttons list
	},
	outputQRbuttons: function(buttons) {
		var that = this;

		var host = $('.present_qr_buttons_host');

		if (!buttons) {
			host.html(T.out('thereAreNoQRbuttons'));
			return;
		}

		var buttons_by_id = {};

		B.loadStyle('https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', function() {

			var h = [];
			for (var i in buttons) {
				var button = buttons[i];
				//console.log(button);
				buttons_by_id[button.id] = button;
				h.push('<table style="margin-bottom:0px; width:100%;"><tr>' +
						'<td style="width:20px;"><a class="pr" style="width:30px; height:30px; top:0px; font-size:20px; color:white; text-decoration:none;" href="' + Application.appSourcePath + '/?r=user/qrcode&button_id=' + button.id + '&url=' + encodeURIComponent(Application.appSourcePath + '/?thank_qr=' + (button.id * 1 + 1735492)) + '" target="_blank">▼<a></td>' +
						'<td style="width:35px;"><img style="left:0px; top:0px; width:30px; height:30px;" src="' + A.baseURL() + 'images/qr_button.png" alt="" title="' + T.out('get_qrcode') + '"/></td>' +
						'<td style="width:40px;">' + button.referals + '</td>' +
						'<td style="width:30px;"><div class="userpic_in_history_list_host"><img class="userpic self_userpic_in_history goto_place_image" src="//graph.facebook.com/' + button.www + '/picture" alt="' + button.www + '" title="' + button.title + '" style="width:30px; height:30px;"></div></td>' +
						'<td class="cp goto_graph_18 uid_' + button.www + ' net_fb"><span style="border-bottom:1px dotted white;">' + button.title + '</span></td>' +
						'<td style="width:130px;">' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px;">' +
						'<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center; color:white;" class="pa cp delete_internal_button id_' + button.id + '"><i class="fa fa-trash-o"></i></div>' +
						'</div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px; top:0px;" title="' + T.out('get_statistics') + '"><a href="' + A.baseURL() + '?r=user/buttonstatistics&button_id=' + button.id + '" target="_blank">' +
						'<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center; color:white;" class="pa cp"><i class="fa fa-bars"></i></div>' +
						'</a></div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px;">' +
						'<div class="pa cp set_posttimeline set_posttimeline_' + button.id + '" style="left:0px; top:0px; line-height:20px; text-align:center;" title="' + T.out('post_timeline_pdf') + '"><i class="fa fa-facebook-square"></i></div>' +
						'</div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px; top:0px;" title="' + T.out('change_language_pdf') + '">' +
						'<img src="' + A.baseURL() + 'images/flags/' + (button.language
						? button.language
						: 'GB') + '.png" style="width:20px; height:20px; left:0px; top:0px;" class="pa cp change_language_for" alt="' + button.id + '">' +
						'</div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px;" title="Change coordinates">' +
						'<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center;" class="pa cp change_coordinates_for id_' + button.id + '"><i class="fa fa-map-marker"></i></div>' +
						'</div>' +
						'</td>' +
						'</tr></table>');
			}

			host.html(h.join(''));

			B.click($('.goto_graph_18'), function(obj) {
				var uid = B.getID(obj, 'uid_');
				var net = B.getId(obj, 'net_');
				Dialog.hide();
				Switchers.list['statistics_filter'].switch(-1, 'noaction');
				Statistics.getKarma(uid, {
					place: true,
					name_inline: $('.goto_graph_18 span').html(),
					type: 'other',
					net: net,
					friends_karma_name: $('span', obj).html(),
					friends_karma_value: '<span class="place_karma uid_' + uid + ' net_' + net + '"></span>',
					friends_karma_rank: '',
					image: '//graph.facebook.com/' + uid + '/picture?width=50&height=50'
				});

			});

			B.click($('.delete_internal_button', host), function(obj) {
				var id = B.getId(obj, 'id_');
				that.del(id, true);
			});

			B.click($('.change_language_for', host), function(obj) {
				var id = obj.attr('alt');
				that.changeLanguage(id);
			});

			B.click($('.change_coordinates_for', host), function(obj) {
				var id = B.getId(obj, 'id_');
				that.changeCoordinates(id);
			});

			$('.set_posttimeline', host).each(function() {
				var id = B.getId($(this), 'set_posttimeline_');
				//console.log(buttons_by_id, id);
				$(this).css({
					opacity: buttons_by_id[id].posttimeline * 1
							? 1
							: 0.5
				});
			});

			B.click($('.set_posttimeline', host), function(obj) {
				var id = B.getId(obj, 'set_posttimeline_');

				Dialog.hide(function() {
					Base.post({
						script: 'index.php?r=user/buttons',
						//r: 'user/buttons',
						Buttons: 'set_qr_posttimeline',
						button_id: id,
						posttimeline: buttons_by_id[id].posttimeline * 1
								? 0
								: 1,
						ok: function(response) {
							that.QRbutton();
						}
					});
				});

			});

		}); //of loadStyle

	},
	outputQRbuttonsReverted: function(buttons) {
		var that = this;

		var host = $('.present_qr_reverted_buttons_host');

		if (!buttons) {
			host.html(T.out('thereAreNoQRbuttons'));
			return;
		}

		var buttons_by_id = {};

		B.loadStyle('https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', function() {

			var h = [];
			for (var i in buttons) {
				var button = buttons[i];
				buttons_by_id[button.id] = button;

				var url = Application.appSourcePath + '/?r=site/out&t=mythank&sender=' + button.id;

				h.push('<table style="margin-bottom:0px; width:100%;"><tr>' +
						'<td style="width:20px;"><a class="pr" style="width:30px; height:30px; top:0px; font-size:20px; color:white; text-decoration:none;" href="' + url + '" target="_blank">▼<a></td>' +
						'<td style="width:35px;"><img style="left:0px; top:0px; width:30px; height:30px;" src="' + A.baseURL() + 'images/qr_button.png" alt="" title="' + T.out('get_qrcode') + '"/></td>' +
						'<td style="width:40px;">' + button.referals + '</td>' +
						'<td style="width:30px;"><div class="userpic_in_history_list_host"><img class="userpic self_userpic_in_history goto_place_image cp" src="//graph.facebook.com/' + button.www + '/picture" alt="' + button.www + '" title="' + button.title + '" style="width:30px; height:30px;"></div></td>' +
						'<td class="cp goto_graph_19 uid_' + button.www + ' net_fb"><span style="border-bottom:1px dotted white;">' + button.title + '</span></td>' +
						'<td style="width:130px;">' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px;">' +
						'<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center; color:white;" class="pa cp delete_internal_button id_' + button.id + '"><i class="fa fa-trash-o"></i></div>' +
						'</div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px; top:0px;" title="' + T.out('get_statistics') + '"><a href="' + A.baseURL() + '?r=user/buttonstatistics&button_id=' + button.id + '" target="_blank">' +
						'<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center; color:white;" class="pa cp"><i class="fa fa-bars"></i></div>' + '<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center; color:white;" class="pa cp"><i class="fa fa-bars"></i></div>' +
						//'<img src="' + A.baseURL() + 'images/call_button.png" style="width:20px; height:20px; left:0px; top:0px;" class="pa cp" alt="' + button.id + '">' +
						'</a></div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; top:0px; margin:3px;">' +
						//'<div class="pa cp set_posttimeline set_posttimeline_' + button.id + '" style="width:20px; height:20px;" title="' + T.out('post_timeline_pdf') + '">✓</div>' +
						'<div class="pa cp set_posttimeline set_posttimeline_' + button.id + '" style="left:0px; top:0px; line-height:20px; text-align:center;" title="' + T.out('post_timeline_pdf') + '"><i class="fa fa-facebook-square"></i></div>' + '<div class="pa cp set_posttimeline set_posttimeline_' + button.id + '" style="left:0px; top:0px; line-height:20px; text-align:center;" title="' + T.out('post_timeline_pdf') + '"><i class="fa fa-facebook-square"></i></div>' +
						'</div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px; top:0px;" title="' + T.out('change_language_pdf') + '">' +
						'<img src="' + A.baseURL() + 'images/flags/' + (button.language
						? button.language
						: 'GB') + '.png" style="width:20px; height:20px; left:0px; top:0px;" class="pa cp change_language_for" alt="' + button.id + '">' +
						'</div>' +
						'<div class="pr" style="width:20px; height:20px; float:right; margin:3px; top:0px;" title="Change coordinates">' +
						'<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center;" class="pa cp change_coordinates_for id_' + button.id + '"><i class="fa fa-map-marker"></i></div>' + '<div style="width:20px; height:20px; left:0px; top:0px; line-height:20px; text-align:center;" class="pa cp change_coordinates_for id_' + button.id + '"><i class="fa fa-map-marker"></i></div>' +
						'</div>' +
						'</td>' +
						'</tr></table>');
			}


			host.html(h.join(''));

			B.click($('.goto_graph_19'), function(obj) {
				var uid = B.getID(obj, 'uid_');
				var net = B.getId(obj, 'net_');
				Dialog.hide();
				Switchers.list['statistics_filter'].switch(-1, 'noaction');
				Statistics.getKarma(uid, {
					place: true,
					name_inline: $('.goto_graph_19 span').html(),
					type: 'other',
					net: net,
					friends_karma_name: $('span', obj).html(),
					friends_karma_value: '<span class="place_karma uid_' + uid + ' net_' + net + '"></span>',
					friends_karma_rank: '',
					image: '//graph.facebook.com/' + uid + '/picture?width=50&height=50'
				});

			});

			B.click($('.delete_internal_button', host), function(obj) {
				var id = B.getId(obj, 'id_');
				that.del(id, true, true);
			});

			B.click($('.change_language_for', host), function(obj) {
				var id = obj.attr('alt');
				that.changeLanguage(id, 'reverted');
			});

			B.click($('.change_coordinates_for', host), function(obj) {
				var id = B.getId(obj, 'id_');
				that.changeCoordinates(id, true);
			});

			$('.set_posttimeline', host).each(function() {
				var id = B.getId($(this), 'set_posttimeline_');
				//console.log(buttons_by_id, id);
				$(this).css({
					opacity: buttons_by_id[id].posttimeline * 1
							? 1
							: 0.5
				});
			});

			B.click($('.set_posttimeline', host), function(obj) {
				var id = B.getId(obj, 'set_posttimeline_');

				//check how it works

				Dialog.hide(function() {
					Base.post({
						script: 'index.php?r=user/buttons',
						//r: 'user/buttons',
						Buttons: 'set_qr_posttimeline',
						button_id: id,
						posttimeline: buttons_by_id[id].posttimeline * 1
								? 0
								: 1,
						ok: function(response) {
							that.QRbuttonReverted();
						}
					});
				});

			});

		});

	},
	changeLanguage: function(id, reverted) {
		var that = this;
		Dialog.hide(function() {
			Dialog.show({
				title: '<div class="ac" style="padding-left:50px; font-size:1.5rem;">' + T.out('change_language_dialog_title') + '</div>',
				message: 'change_pdf_language.php?id=' + id + '&reverted=' + (reverted
						? 1
						: 0),
				noexit: true,
				onShow: function() {
					Buttons.place(
							{
								title: 'OK',
								host: $('.dont_want_to_change_lang'),
								action: function() {
									that.saveLanguage(id, reverted);
								},
								class: ['round', 'transparent_button'],
								css: {
									width: '40px',
									height: '33px',
									'padding-top': '7px'
								},
								outer: {
									css: {
										width: '45px'
									}
								}
							}
					);

				}
			});
		});
	},
	saveLanguage: function(id, reverted) {
		var that = this;
		B.post({
			script: 'index.php?r=user/buttons',
			//r: 'user/buttons',
			Buttons: 'set_qr_language',
			language: B.getId($('.selected_language:checked'), 'selected_language_'),
			id: id,
			ok: function(response) {
				//console.log(response);
				Dialog.hide(function() {
					if (reverted) {
						that.QRbuttonReverted();
					} else {
						that.QRbutton();
					}
				});
			},
			no: function(response) {
				if (response.error === 'login_error') {
					User.notLogged(function() {
						that.saveLanguage(id, reverted);
					});
				}
			}
		});
	},
	openSendThankLinkDialog: function() {
		var that = this;
		Dialog.hide(function() {
			Dialog.show({
				title: false,
				message: 'thanklinkmenu.php',
				noexit: true,
				onShow: function() {

					B.get({
						script: 'index.php?r=user/qrlinks',
						//r: 'user/qrlinks',
						ok: function(response) {
							if (response.links) {
								var h = [];
								for (var i in response.links) {
									var link = response.links[i];
									var a = link.changed.split(' ');
									var date = a[0].split('-').join('.');
									var b = a[1].split(':');
									var time = b[0] + ':' + b[1];
									h.push('<table><tr>' +
											'<td>' +
											'<a href="' + A.baseURL() + '?r=user/qrlink&code=' + link.id + '">' +
											'<img src="' + A.baseURL() + 'images/qr_button.png" alt="' + link.id + '" style="dispaly:inline-block;"/></a></td>' +
											'<td style="left:90%; top:50%; margin-top:-5px; font-size:1rem;">' + link.amount + '</td>' +
											'<td class="ac">' + date + ' ' + time + '</td>' +
											'<td>' +
											'<a href="' + A.baseURL() + '?r=user/qrlinkstat&link=' + link.id + '" class="ac" target="_blank">' +
											'<img src="' + A.baseURL() + 'images/call_button.png" class="cp qrlink178 qrlink178_' + link.id + '" style="display:inline-block;"/></a></td>' +
											'<td><div class="pr" style="width:20px; height:20px; margin-top:8px; display:inline-block;"><img src="' + A.baseURL() + 'images/exit.png" class="pa cp qrlink178_del qrlink178_del_' + link.id + '" style="left:0px; top:0px;"/></div></td>' +
											'</tr></table>');
								}
								$('.qrlinks_host_15').html(h.join(''));

								$('.qrlink178_del').each(function() {
									B.click($(this), function(obj) {
										var id = B.getID(obj, 'qrlink178_del_');
										B.post({
											script: 'index.php?r=user/qrlinkdel',
											//r: 'user/qrlinkdel',
											id: id,
											ok: function(response) {
												Dialog.hide(function() {
													that.openSendThankLinkDialog();
												});
											}
										});
									});
								});

							}
						}
					});

					Buttons.place({
						title: T.out('get_link_qr2'),
						host: $('.get_thanklink_pdf_host'),
						action: function() {

						},
						outer: {
							css: {
								width: '300px'
							}
						},
						css: {
							width: '300px'
						},
						class: 'transparent_button'
					});
					/*
					 Buttons.place({
					 title: T.out('thanklink_statistics'),
					 host: $('.get_thanklink_statistics_host'),
					 action: function() {
					 
					 },
					 outer: {
					 css: {
					 width: '300px'
					 }
					 },
					 css: {
					 width: '300px'
					 },
					 class: 'transparent_button'
					 }); */

					Buttons.place(
							{
								title: 'OK',
								host: $('.dont_want_to_choose'),
								action: function() {
									Dialog.hide();
								},
								class: ['round', 'transparent_button'],
								css: {
									width: '40px',
									height: '33px',
									'padding-top': '7px'
								},
								outer: {
									css: {
										width: '45px'
									}
								}
							}
					);
				}
			});
		});
	},
	getMyThankQR: function() {

	},
	changeCoordinates: function(id, isReverted) {
		var me = this,
				description = isReverted
				? 'QRCODE_REVERTED'
				: 'QRCODE';
		Base.get({
			script: 'index.php?r=user/get-geo-place-coordinates',
			id: id,
			description: description,
			defaultMoscow: true,
			ok: function(response) {
				var map_id = isReverted
						? 'place_map_reverted'
						: 'place_map';
				me.showMap(map_id, response, description, null, id);
			},
			no: function(response) {
				console.log('Error while pending request "index.php?r=user/get-geo-place-coordinates"');
				console.log(response);
			}
		});

	},
	showMap: function(map_id, response, description, uid_net, id) {
		$('#' + map_id).fadeIn(400, function() {
			var coordinates = response;
			var map = new google.maps.Map(document.getElementById('map_of_' + map_id), {
				center: response,
				scrollwheel: false,
				zoom: 12
			});

			var marker = new google.maps.Marker({
				position: response,
				map: map,
				title: 'Set lat/lon values for this property',
				draggable: true
			});

			google.maps.event.addListener(marker, 'dragend', function(a) {
				coordinates.lat = a.latLng.lat().toFixed(4) * 1;
				coordinates.lng = a.latLng.lng().toFixed(4) * 1;
			});

			$('#' + map_id).off('click', '#' + map_id + '_close_button').on('click', '#' + map_id + '_close_button', function() {
				$('#' + map_id).fadeOut();
			}).off('click', '#' + map_id + '_ok_button').on('click', '#' + map_id + '_ok_button', function() {

				Base.post({
					script: './index.php?r=user/set-geo-place-coordinates',
					uid_net: uid_net,
					id: id,
					description: description, //,
					coordinates: coordinates
				});

				$('#' + map_id).fadeOut();
			});
		});
	}
};

