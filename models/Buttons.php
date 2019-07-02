<?php

namespace app\models;

use app\components\vh2015\H;
use app\components\vh2015\M;
use app\components\vh2015\T;
use app\components\vh2015\Image;
use app\components\vh2015\Mandrillmail;
use \Exception;
use Yii;

/**
 * Thank Buttons on external sites
 */
class Buttons extends M {

	protected $language;

    public static function tableName() {
        return '{{%buttons}}';
    }

	public static function action($r) {

		M::session(empty($r['_sid'])
						? false
						: $r['_sid']);
		$com = $r['Buttons'];
		
		try {

			if ($com == 'create') { //create new button
				$user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

				if (empty($user)) {
					throw new Exception('Need to login at first');
				}

				$button = self::getBy([
							'code'		 => 'precreated_record',
							'uid'		 => $user->get('uid'),
							'net'		 => $user->get('net'),
							'_notfound'	 => [
								'uid'	 => $user->get('uid'),
								'net'	 => $user->get('net')
							]
						])->set([
					'name'			 => empty($r['name'])
							? ''
							: $r['name'],
					'email'			 => empty($r['email'])
							? ''
							: $r['email'],
					'tel'			 => empty($r['tel'])
							? ''
							: $r['tel'],
					'www'			 => empty($r['www'])
							? ''
							: $r['www'],
					'title'			 => empty($r['title'])
							? ''
							: $r['title'],
					'description'	 => empty($r['description'])
							? ''
							: $r['description'],
					'code'			 => md5(microtime() . 'jgkdfj'),
					'referals'		 => max(1, ThwThank::getCount($r['www'])),
					'posttimeline'	 => empty($r['posttimeline'])
							? 0
							: 1
				]);

				$receiver_net = 'link_' . md5($button->get('www'));
				
				//!!! TODO: send email

				$thank = ThwThank::getBy([
					'sender_uid' => $user->get('uid'), //this means we always get new ref
					'sender_net' => $user->get('net') . 'never',
					'place'		 => $button->get('www'),
					'_notfound'	 => [
						'sender_uid'	 => $user->get('uid'),
						'sender_net'	 => $user->get('net'),
						'place'			 => $button->get('www'),
						'receiver_net'	 => 'fb',
						'message'		 => '',
						'title'			 => '',
						'read'			 => 0,
						'receiver_uid'	 => 0,
						'receiver_net'	 => $receiver_net,
						'status'		 => 'place'
					]
				]);
		
				$thank->email();

				ThwThank::getKarmaOfPlace($thank->get('receiver_uid'), $thank->get('receiver_net'));

				Maillog::create([
					'receiver_id'	 => $user->get('id'),
					'type'			 => 'Thank you button',
					'code'			 => md5(time() . mt_rand(0, 10000))
				]);

				Mandrillmail::send([
					'to'		 => Yii::app()->params['email_notification'] === true
							? $r['email']
							: Yii::app()->params['email_notification'],
					'html'		 => '<a href="' . Yii::app()->params['app_source_path'] . '/?r=site/buttoncode&id=' . $button->get('id') . '">Get the code from here</a>',
					'from_name'	 => 'Top Karma',
					'subject'	 => T::out([
						'button_subject' => [
							'en' => 'Thank button for your site',
							'ru' => 'Кнопка Спасибо для вашего сайта'
						]
							], false, 'notag')
				]);

				M::ok([
					'ok' => true
				]);
			} elseif ($com === 'create_qr') { //create QR button	
				self::required([
					'id'	 => 1,
					'title'	 => 1
						], $r);

				$user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

				if (empty($user)) {
					throw new Exception('Need to login at first');
				}

				$button = self::getBy([
							'www'			 => $r['id'],
							'uid'			 => $user->get('uid'),
							'net'			 => $user->get('net'),
							'description'	 => 'QRCODE',
							'_notfound'		 => [
								'uid'			 => $user->get('uid'),
								'net'			 => $user->get('net'),
								'description'	 => 'QRCODE'
							]
						])->set([
					'name'		 => $user->get('name'),
					'email'		 => $user->get('email'),
					'tel'		 => '',
					'www'		 => $r['id'],
					'title'		 => $r['title'],
					'code'		 => md5(microtime() . 'jgkdfj'),
					'referals'	 => 0
				]);

				M::ok([
					'buttons' => Buttons::getBy([
						'uid'			 => $user->get('uid'),
						'net'			 => $user->get('net'),
						'description'	 => 'QRCODE',
						'_return'		 => [0 => 'array']
					])
				]);
			} elseif ($com === 'set_qr_language') {

				$button = Buttons::getBy([
							'id'		 => $r['id'],
							'_notfound'	 => true
						])->set([
					'css' => $r['language']
				]);

				M::ok([
					'success' => 1
				]);
			} elseif ($com === 'qr_list') {

				$user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

				if (empty($user)) {
					throw new Exception('Need to login at first');
				}
				M::ok([
					'buttons' => Buttons::getBy([
						'uid'			 => $user->get('uid'),
						'net'			 => $user->get('net'),
						'description'	 => 'QRCODE',
						'_load'			 => [
							'language' => 1
						],
						'_return'		 => [0 => 'array']
					])
				]);
			} elseif ($com === 'qr_list_reverted') {

				$user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

				if (empty($user)) {
					throw new Exception('Need to login at first');
				}
				M::ok([
					'buttons' => Buttons::getBy([
						'uid'			 => $user->get('uid'),
						'net'			 => $user->get('net'),
						'description'	 => 'QRCODE_REVERTED',
						'_load'			 => [
							'language' => 1
						],
						'_return'		 => [
							0 => 'array'
						]
					])
				]);
			} elseif ($com === 'create_qr_reverted') {

				self::required([
					'id'	 => 1,
					'title'	 => 1
						], $r);

				$user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

				if (empty($user)) {
					throw new Exception('Need to login at first');
				}

				$button = self::getBy([
							'www'			 => $r['id'],
							'uid'			 => $user->get('uid'),
							'net'			 => $user->get('net'),
							'description'	 => 'QRCODE_REVERTED',
							'_notfound'		 => [
								'uid'			 => $user->get('uid'),
								'net'			 => $user->get('net'),
								'description'	 => 'QRCODE_REVERTED'
							]
						])->set([
					'name'		 => $user->get('name'),
					'email'		 => $user->get('email'),
					'tel'		 => '',
					'www'		 => $r['id'],
					'title'		 => $r['title'],
					'code'		 => md5(microtime() . 'jgkdfj'),
					'referals'	 => 0
				]);

				M::ok([
					'buttons' => Buttons::getBy([
						'uid'			 => $user->get('uid'),
						'net'			 => $user->get('net'),
						'description'	 => 'QRCODE_REVERTED',
						'_return'		 => [0 => 'array']
					])
				]);
			} elseif ($com === 'upload') {

				try {

					$user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

					if (empty($user)) {
						throw new Exception('Need to login at first');
					}

					$button = self::getBy([
								'id'		 => $r['button_id'],
								'_notfound'	 => true
					]);

					$upload_response = $button->upload(['_rewrite' => true]);

					if ($upload_response['failed']) {
						throw new Exception(join('<br/>', $upload_response['failed']));
					}

					M::jsonp([
						'parent.Thankbuttons.cropUploaded' => [
							'url'		 => $button->get(['image' => 0]),
							'info'		 => getimagesize($button->get(['image' => 0])),
							'button_id'	 => $button->get('id')
						]
					]);
				} catch (Exception $e) {
					//TODO: open error dialog
					M::jsonp([
						'parent.Thankbuttons.uploadError' => [
							'message' => $e->getMessage()
						]
					]);
				}
			} elseif ($com === 'crop') {

				$user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

				if (empty($user)) {
					throw new Exception('Need to login at first');
				}

//				require_once Settings::getPath() . '/vh2015/Image.php';

				self::required([
					'button_id' => 1
						], $r);

				$button = self::getBy([
							'id'		 => $r['button_id'],
							'_notfound'	 => true
				]);

				//print_r($r);

				/*
				  Image::getBy($button->get(['image' => 0]))->cut([
				  'left'	 => $r['left'],
				  'top'	 => $r['top'],
				  'width'	 => $r['width'],
				  'height' => $r['height']
				  ], [
				  'left'	 => 0,
				  'top'	 => 0,
				  'width'	 => 80,
				  'height' => 80,
				  'image'	 => $button->get(['image' => 0])
				  ]); */

				//print_r($r);

				Image::getBy($button->get(['image' => 0]))->crop($r)->resize([
					'width'	 => 80,
					'height' => 80
				])->save($button->get(['image' => 0]));

				$button->set([
					'logo' => $button->get(['image' => 0])
				]);

				M::ok([
					'ok' => true
				]);
			} elseif ($com === 'thank') { //CLICK THE BUTTON
				$user = ThwUser::getBy([
							'id'		 => ThwUser::getCurrent()['id'],
							'_notfound'	 => true
				]);

				$button = self::getBy([
							'id'		 => $r['id'] * 1,
							//'uid'		 => '!=' . $user->get('uid'),
							'_notfound'	 => true
				]);

				$already_thanked_today = Buttonlog::CountUserClicks($user->get('uid'), $user->get('net'), $button->get('id'));

				if ($already_thanked_today >= 3) {
					M::no([
						'error'		 => 'already_thanked',
						'message'	 => T::out([
							'already_thanked' => [
								'en' => 'No more than 3 thanks fro mone user per day!',
								'ru' => 'Не более 3 фенков от одного человека в день!'
							]
						]),
						'count'		 => $already_thanked_today
					]);
				}

				//send thank by sender user to resource with id
				$thank = ThwThank::getBy([
							'sender_uid' => $user->get('uid'), //this means we always get new ref
							'sender_net' => $user->get('net') . 'never',
							'place'		 => $button->get('description') == 'QRCODE'
									? $button->get('title')
									: $button->get('www'),
							'_notfound'	 => [
								'sender_uid'	 => $user->get('uid'),
								'sender_net'	 => $user->get('net'),
								'place'			 => $button->get('description') == 'QRCODE'
										? $button->get('title')
										: $button->get('www'),
								'receiver_net'	 => 'fb',
								'message'		 => '',
								'title'			 => '',
								'read'			 => 0,
								'receiver_uid'	 => 0,
								'receiver_net'   => 'new',
								'status'		 => 'place'
							]
				]);


				if ($thank->get('receiver_net') === 'new') {

					if ($button->get('description') == 'QRCODE'){
						$receiver_uid = $button->get('www');
						$receiver_net = 'fb';
					} else {
						$receiver_uid = 0;
						$receiver_net = 'link_' . md5($button->get('www'));
					}
					

					$thank->set([
						'receiver_uid' => $receiver_uid,
						'receiver_net' => $receiver_net
					]);

					ThwThank::getKarmaOfPlace($thank->get('receiver_uid'), $thank->get('receiver_net'));

					$button->set([
						'referals' => $button->d('referals') + 1
					]);

					Buttonlog::record([
						'uid'	 => $user->get('uid'),
						'net'	 => $user->get('net'),
						'button' => $button
					]);

					M::ok([
						'ok'		 => $button->get('description') == 'QRCODE'
								? $button->get('title')
								: $button->get('www'),
						'referals'	 => $button->get('referals')
					]);
				} else { //we have already make thank
					M::no([
						'error'		 => 'already_thanked',
						'message'	 => 'You already thanked'
					]);
				}
			} elseif ($com === 'del') { //DELETE BUTTON
				if ($r['ask'] == 1) {
					M::ok([
						'ask'		 => 1,
						'message'	 => T::out([
							'delete_button' => [
								'en' => 'Are you sure to delete buton? All thanks for site will be saved.',
								'ru' => 'Вы уверены, что хотите удалить кнопку? Все фенки для сайта будут сохранены.'
							]
						])
					]);
				}

				$button = self::getBy([
							'id'		 => $r['id'],
							'_notfound'	 => true
						])->remove();

				M::ok([
					'ok'	 => true,
					'action' => $button->get('description') == 'QRCODE'
							? 'qr'
							: (
							$button->get('description') == 'QRCODE_REVERTED'
									? 'qr'
									: 'usial')
				]);
			} elseif ($com === 'set_qr_posttimeline') {

				$user = ThwUser::getBy([
							'id'		 => ThwUser::getCurrent()['id'],
							'_notfound'	 => true
				]);

				$button = self::getBy([
							'id'		 => $r['button_id'],
							'uid'		 => $user->get('uid'),
							'_notfound'	 => true
						])->set([
					'posttimeline' => $r['posttimeline']
				]);

				M::ok([
					'buttons' => Buttons::getBy([
						'uid'			 => $user->get('uid'),
						'net'			 => $user->get('net'),
						'description'	 => 'QRCODE',
						'_load'			 => [
							'language' => 1
						],
						'_return'		 => [0 => 'array']
					])
				]);
			} elseif ($com === 'save') { //save edited
				self::required([
					'title' => true
						], $r);

				$set = [
					'title'			 => $r['title'],
					'posttimeline'	 => $r['posttimeline']
				];

				if (!empty($r['link'])) {
					$set['link'] = $r['link'];
				}

				if (!empty($r['css'])) {
					$set['css'] = json_encode($r['css']);
				}

				$button = self::getBy([
							'id'		 => $r['id'],
							'uid'		 => ThwUser::getBy([
								'id'		 => ThwUser::getCurrent()['id'],
								'_notfound'	 => true
							])->get('uid'),
							'_notfound'	 => true
						])->set($set);

				M::ok([
					'ok' => T::out([
						'data_success_updated' => [
							'en' => 'Data has been successfully updated!',
							'ru' => 'Данные успешно обновлены!'
						]
					])
				]);
			} elseif ($com === 'images') {

				if (empty($r['url'])) {
					throw new Exception('need not empty url');
				}

				$images = H::fetchImages($r['url']);

				M::ok([
					$r['url'] => $images
				]);
			} elseif ($com === 'title') { //getPagetitle	
				M::ok([
					'title' => H::getPageTitle($r['url'])
				]);
			} elseif ($com === 'list') { //get buttons list
			} else {
				throw new Exception('Unexpected command:' . $com);
			}

			return [];
		} catch (Exception $e) {
			M::no([
				'error'		 => 'kernel_error',
				'message'	 => $e->getMessage()
			]);
		}
	}

	public static function oldFields($which = false) {
		return [
			'create' => [
				'net'			 => "tinytext comment 'Social newtwork'",
				'uid'			 => "bigint unsigned comment 'User id in social network'",
				'name'			 => "tinytext comment 'Name of contact person'",
				'email'			 => "tinytext comment 'Email address of contact person'",
				'tel'			 => "tinytext comment 'Phone of contact person'",
				'www'			 => "text comment 'Link to site'",
				'logo'			 => "text comment 'Link to logo'",
				'title'			 => "tinytext comment 'Site title'",
				'description'	 => "text comment 'Description of the site'", //if QRcode - this is QR button
				'code'			 => "tinytext comment 'Unique button code'",
				'referals'		 => "bigint default 0 comment 'Amount of referals'",
				'css'			 => "text comment 'Encoded button css'",
				'posttimeline'	 => "tinytint default 1 comment 'Post to timeline when click'"
			]
		];
	}

	public function get($what, $data = false) {

		if ($what == 'inscriptions') {
			//TODO: migrate to T or anither object
			return [
				'GB' => 'Thank us!',
				'RU' => 'Скажите нам спасибо!',
				'IN' => 'हमें धन्यवाद कह दो',
				'ES' => 'Díganos gracias!',
				'DE' => 'Sagen Sie uns danke!',
				'JP' => '私たちにありがとうと言って下さい。',
				'FR' => 'Dites-nous merci!',
				'CN' => '请告诉我们，谢谢'
			];
		}

		if ($what == 'inscriptions2') {
			return [
				'GB' => 'Get our thank!',
				'RU' => 'Получи наше спасибо!',
				'IN' => 'हमारे धन्यवाद जाओ',
				'ES' => 'llegar nuestro gracias!',
				'DE' => 'Holen Sie sich unsere danke!',
				'JP' => '私たちの取得に感謝',
				'FR' => 'obtenez notre merci!',
				'CN' => '让我们的感谢'
			];
		}


		if ($what == 'inscription') {
			$inscriptions = $this->get('inscriptions');
			$lang = $this->get('language');
			return isset($inscriptions[$lang])
					? $inscriptions[$lang]
					: $inscriptions['GB'];
		}

		if ($what == 'inscription2') {
			$inscriptions = $this->get('inscriptions2');
			$lang = $this->get('language');
			return isset($inscriptions[$lang])
					? $inscriptions[$lang]
					: $inscriptions['GB'];
		}

		if ($what == 'language') {
			$this->$what = empty($this->css) || !in_array($this->css, array_keys($this->get('inscriptions')))
					? 'GB'
					: $this->css;
			return $this->$what;
		}

		if ($what == 'css') {

			$a = $this->css;

			if (is_array($data)) {

				$hover = key($data);
				$element = current($data);

				if (empty($a)) {
					return $hover == 'color'
							? (
							$element == 'background'
									? 'rgb(255,255,255)'
									: 'rgb(194,45,48)')
							: (
							$element != 'background'
									? 'rgb(255,255,255)'
									: 'rgb(194,45,48)'
							);
				};

				$d = json_decode($a, true);


				return empty($d[$hover][$element])
						? (
						$hover == 'color'
								? (
								$element == 'background'
										? 'rgb(255,255,255)'
										: 'rgb(194,45,48)')
								: (
								$element != 'background'
										? 'rgb(255,255,255)'
										: 'rgb(194,45,48)'
								)
						)
						: $d[$hover][$element];
			} else {
				return $data == 'text'
						? 'rgb(255,255,255)'
						: 'rgb(249,217,118)';
			}
		}

		return parent::get($what, $data);
	}

    public static function setCoordinates($post) {
        $user = ThwUser::getBy(['id' => ThwUser::getCurrent()['id']]);

        if (empty($user)) {
            throw new Exception('Need to login at first');
        }

        $id_part = empty($post['id']) ? 'www = :www AND uid = :uid AND net = :net AND description = :description' : 'id = :id';

        $sql = "
            UPDATE thw_buttons
            SET lat = :lat, lng = :lng
            WHERE $id_part
        ";

        $arr = empty($post['id']) ? [
            ':lat' => $post['coordinates']['lat'] * 1,
            ':lng' => $post['coordinates']['lng'] * 1,
            ':www' => $post['uid_net'],
            ':uid' => $user->get('uid'),
            ':net' => $user->get('net'),
            ':description' => $post['description']
        ] : [
            ':lat' => $post['coordinates']['lat'] * 1,
            ':lng' => $post['coordinates']['lng'] * 1,
            ':id' => $post['id'] * 1
        ];

        $result = Yii::$app->db->createCommand($sql, $arr)->execute();

        return $result;
    }

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
