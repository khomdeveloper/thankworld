<?php

/**
 * Created by PhpStorm.
 * User: indikator
 * Date: 12/15/15
 * Time: 11:04 AM
 */

namespace app\controllers;

use app\models\ThwUser;
use app\models\ThwPopular;
use app\models\ThwModel;
use app\models\Userlog;
use app\models\ThwThank;
use app\models\Karmalog;
use app\models\Names;
use app\models\SavedPhones;
use app\models\Thanklink;
use app\models\Userthanklink;
use app\models\Buttons;
use app\models\Buttonlog;
use app\models\Admins;
use app\models\Maillog;
use app\models\SavedMails;
use app\models\Pressed;
use app\models\Karmalogplaces;
use app\components\vh2015\A;
use app\components\vh2015\B;
use app\components\vh2015\T;
use app\components\vh2015\M;
use app\components\vh2015\H;
use app\components\vh2015\S;
use app\components\vh2015\Mandrillmail;
use app\components\vh2015\Pdf;
use Yii;
use yii\web\Controller;
use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;
use yii\web\Response;

class UserController extends Controller {

	public function actionAdmin() {
		A::action($_REQUEST);
	}

	public function actionIndex() {
		$this->render('index');
	}

	//TODO: move it to separate class
	/*
	  just_check -> don`t load profile and friends
	  TODO: use session for this case
	 */
	public static function checkFBsession($uid, $just_check = false, $nofriends = false) {

		/*
		  $app_id = 277236325820557;
		  $app_secret = 'ad7b44ea1db4fd8ec20bd546597417af'; */

		$app_id = Yii::$app->params['app_id'];
		$app_secret = Yii::$app->params['app_secret'];

		FacebookSession::setDefaultApplication($app_id, $app_secret);

		$helper = new FacebookJavaScriptLoginHelper($app_id);
		try {
			$session = $helper->getSession();
		} catch (FacebookRequestException $ex) {
			ThwModel::e([
				'error'		 => 'login_error',
				'message'	 => $ex->getMessage()
			]);
		} catch (\Exception $ex) {
			ThwModel::e([
				'error'		 => 'login_error',
				'message'	 => $ex->getMessage()
			]);
		}

		if ($session) {

			$_SESSION['facebook_token'] = $session->getToken();

			try {

				if ($just_check) { //if only check user logged - just return true
					return true;
				}

				$user_profile = (new FacebookRequest(
						$session, 'GET', '/me'
						))->execute()->getGraphObject(GraphUser::className());

				if (empty($user_profile) || $uid != $user_profile->getId()) {
					ThwModel::e([
						'error'		 => 'fatal_login_error',
						'message'	 => [
							'empty_user_profile' => [
								'en' => 'Failed to get user profile',
								'ru' => 'Профиль пользователя не получен'
							]
						]
					]);
				}

				$user_profile = $user_profile->asArray();

				return $user_profile; //-> this is success point
			} catch (FacebookRequestException $e) {
				ThwModel::e([
					'error'		 => 'fatal_login_error',
					'message'	 => $e->getMessage() . ' code: ' . $e->getCode()
				]);
			}
		} else {
			ThwModel::e([
				'error'		 => 'login_error',
				'message'	 => [
					'session_expired_or_lost' => [
						'en' => 'Session expired or lost!',
						'ru' => 'Сессия просрочена или утрачена!'
					]
				]
			]);
		}
	}

	public function actionMyCharity() {

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		if (empty($user)) {
			M::no([
				'error' => 'login_error'
			]);
		}

		$db = Yii::$app->db->createCommand("
				SELECT `amount`,`project`,`changed`
				FROM `thw_donation`
				WHERE `user_id` = :user_id
				ORDER BY `changed` DESC
			   ", [
					':user_id' => $user->get('id')
				])->query();

		if (empty($db)) {
			M::ok([
				'data' => false
			]);
		}

		$return = [];
		while (($row = $db->read()) != false) {
			$return[] = $row;
		}


		M::ok([
			'data' => $return
		]);
	}

	public function actionQrcode() {

//        require_once Settings::getPath() . '/vh2015/Pdf.php';

		Pdf::createQR([
			'before'	 => 'Thank us!',
			'encoded'	 => B::setProtocol('https:', $_REQUEST['url']),
			'after'		 => /* Buttons::getBy([
			  'id'		 => $_REQUEST['button_id'],
			  '_notfound'	 => true
			  ])->get('inscription') */B::setProtocol('https:', $_REQUEST['url']),
			'color'		 => [196,
				43,
				44]
		]);
	}

	public function actionQrRevertedcode() {

//        require_once Settings::getPath() . '/vh2015/Pdf.php';

		Pdf::createQR([
			'before'	 => 'Thank me!',
			'encoded'	 => B::setProtocol('https:', $_REQUEST['url']),
			'after'		 => Buttons::getBy([
				'id'		 => $_REQUEST['button_id'],
				'_notfound'	 => true
			])->get('inscription'),
			'color'		 => [196,
				43,
				44]
		]);
	}

	public function actionQrlink() {
//        require_once Settings::getPath() . '/vh2015/Pdf.php';

		header('Content-Disposition: Attachment;filename=qrcode.png');

		if (empty($_REQUEST['code'])) {
			$encode = Thanklink::getLink('presentation');
		} else { //present code
			$encode = B::setProtocol('https:', B::baseURL() . 'thankyou' . Thanklink::getBy([
								'id'		 => $_REQUEST['code'],
								'_notfound'	 => true
							])->get('code'));
		}

		Pdf::QRcodeAsPng($encode);
	}

	public function actionQrlinkdel() {

		if (empty($_REQUEST['id'])) {
			M::no([
				'error' => 'need_id'
			]);
		}

		$thanklink = Thanklink::getBy([
					'id'		 => $_REQUEST['id'],
					'_notfound'	 => true
				])->remove();

		M::ok([
			'success' => 1
		]);
	}

	public function actionTest() {

		T::w(['loading_user_data' => [
				'en' => 'Loading user data...',
				'ru' => 'Загружаются данные пользователя...'
			]], 'ru');
	}

	//direct thank of specified user
	public function actionThank() {

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		unset($_SESSION['thank']);

		if (empty($_REQUEST['thank'])) { //сюда приходит декодированный thank
			die(json_encode([]));
		}

		$user_id = $_REQUEST['thank'];

		//TODO: short link
		$self_user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$user = ThwUser::getBy([
					'id'		 => $user_id,
					'_notfound'	 => false
		]);

		if (empty($user)) {
			M::no([
				'error'		 => 'user_not_found',
				'message'	 => T::out([
					'try_thank_but_user_not_found' => [
						'en'		 => 'You try tio thank user but his not found!',
						'ru'		 => 'Вы пытались поблагодарить пользователя, но он не найден!',
						'_include'	 => [
							'id' => $user_id
						]
					]
				])
			]);
		}

		if (empty($user) || $user->get('id') === $self_user->get('id')) {
			die(json_encode([]));
		}

		//!!! TODO: send email

		die(json_encode([
			'ok' => ThwThank::getBy([
				'id'		 => '_new',
				'_notfound'	 => [
					'sender_uid'	 => $self_user->get('uid'),
					'sender_net'	 => 'fb',
					'receiver_uid'	 => $user->get('uid'),
					'receiver_net'	 => 'fb',
					'message'		 => '',
					'title'			 => '',
					'read'			 => 0,
					'status'		 => ''
				]
			])->addNames()->email()->encode(false)
		]));
	}

	public function actionFavourite() {
		//Favourite::call($_REQUEST);

		$thank = ThwThank::getBy([
					'id'		 => $_REQUEST['thank_id'],
					'_notfound'	 => true
		]);

		M::ok([
			'favourite' => $thank->set([
				'favourite' => $thank->get('favourite') == 1
						? 0
						: 1
			])->get('favourite')
		]);
	}

	public function actionThankyou() {

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		unset($_SESSION['thankyou']);

		if (empty($_REQUEST['thankyou'])) {
			die(json_encode([
				'error' => 'no thankyou'
			]));
		}

		$thanklink = Thanklink::getBy([
					'code' => $_REQUEST['thankyou']
		]);

		if (empty($thanklink)) {
			die(json_encode([
				'error'		 => 'thanklink_not_found',
				'message'	 => T::out([
					'error_thanklink_notfound' => [
						'en' => 'Thank link is not present or has deleted',
						'ru' => 'Ссылка для спасибо не существует или удалена'
					]
				])
			]));
		}

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		if (Userthanklink::getBy([
					'user_id'	 => $user->get('id'),
					'thanklink'	 => $thanklink->get('id'),
					'_return'	 => 'count'
				]) > 0) {
			M::no([
				'error'		 => 'already_thanked',
				'message'	 => T::out([
					'thank_received' => [
						'en' => 'Thank has already received!',
						'ru' => 'Thank уже получен!'
					]
				])
			]);
		}

		$owner = ThwUser::getBy([
					'id'		 => $thanklink->get('user_id'),
					'_notfound'	 => true
		]);

		if ($user->get('id') == $owner->get('id')) {
			M::no([
				'error'		 => 'unable to thank myself',
				'message'	 => T::out([
					'unable to thank myself' => [
						'en' => 'Unable to thank myself!',
						'ru' => 'Нельзя поблагодарить самого себя!'
					]
				])
			]);
		}

		//record thank data
		Userthanklink::getBy([
			'id'		 => 'new',
			'_notfound'	 => [
				'user_id'	 => $user->get('id'),
				'thanklink'	 => $thanklink->get('id')
			]
		]);

		//!!! TODO: send email

		die(json_encode([
			'ok'		 => ThwThank::getBy([
				'id'		 => '_new',
				'_notfound'	 => [
					'sender_net'	 => $owner->get('net'),
					'sender_uid'	 => $owner->get('uid'),
					'receiver_uid'	 => $user->get('uid'),
					'receiver_net'	 => $user->get('net'),
					'message'		 => '',
					'title'			 => $thanklink->get('usage') == 'sms'
							? $thanklink->get('for')
							: '',
					'read'			 => 0,
					'status'		 => ''
				]
			])->addNames()->email()->encode(false),
			'message'	 => T::out([
				'somebody_thanks_you_788' => [
					'en'		 => '{{name}} thanked you{{for}}!',
					'ru'		 => '{{name}} поблагодарил(а) вас{{for}}!',
					'_include'	 => [
						'name'	 => $owner->get('name'),
						'for'	 => $thanklink->get('for')
								? T::out([
									'for_14' => [
										'en' => ' for ',
										'ru' => ' за '
									]
								]) . $thanklink->get('for')
								: ''
					]
				]
			])
		]));
	}

	public function actionThankme() {

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		unset($_SESSION['thankme']);

		if (empty($_REQUEST['thankme'])) {
			die(json_encode([
				'error' => 'no thankme'
			]));
		}

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$owner = ThwUser::getBy([
					'id' => $_REQUEST['thankme'],
		]);

		if (empty($owner)) {
			M::no([
				'error'		 => 'user to thank undefined',
				'message'	 => T::out([
					'user to thank undefined' => [
						'en' => 'User undefined!',
						'ru' => 'Пользователя не существует!'
					]
				])
			]);
		}

		if ($user->get('id') == $owner->get('id')) {
			M::no([
				'error'		 => 'unable to thank myself',
				'message'	 => T::out([
					'unable to thank myself' => [
						'en' => 'Unable to thank myself!',
						'ru' => 'Нельзя поблагодарить самого себя!'
					]
				])
			]);
		}

		die(json_encode([
			'ok'		 => ThwThank::getBy([
				'id'		 => '_new',
				'_notfound'	 => [
					'receiver_net'	 => $owner->get('net'),
					'receiver_uid'	 => $owner->get('uid'),
					'sender_uid'	 => $user->get('uid'),
					'sender_net'	 => $user->get('net'),
					'message'		 => '',
					'title'			 => '',
					'read'			 => 0,
					'status'		 => ''
				]
			])->addNames()->email()->encode(false),
			'message'	 => T::out([
				'somebody_thanks_you_799' => [
					'en'		 => 'You thanked {{name}}!',
					'ru'		 => 'Вы поблагодарили {{name}}!',
					'_include'	 => [
						'name' => $owner->get('name'),
					]
				]
			])
		]));
	}

	public function actionThankmeqr() {

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		unset($_SESSION['thank_meqr']);

		if (empty($_REQUEST['thank_meqr'])) {
			die(json_encode([
				'error' => 'no thankmeqr'
			]));
		}

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);


		$button = Buttons::getBy([
					'id' => $_REQUEST['thank_meqr']
		]);
		$button->set([
			'referals' => $button->d('referals') + 1
		]);

		Buttonlog::record([
			'uid'	 => $user->get('uid'),
			'net'	 => $user->get('net'),
			'button' => $button
		]);

		$real_address = json_decode(file_get_contents(B::setProtocol('http:', B::baseURL() . 'fb_real_link.php?fb_id=' . $button->get('www'))), true);

		die(json_encode([
			'ok'		 => ThwThank::getBy([
				'id'		 => '_new',
				'_notfound'	 => [
					'sender_net'	 => 'fb',
					'sender_uid'	 => $button->get('www'),
					'receiver_uid'	 => $user->get('uid'),
					'receiver_net'	 => $user->get('net'),
					'message'		 => '',
					'title'			 => '',
					'place'			 => $button->get('title'),
					'read'			 => 0,
					'status'		 => ''
				]
			])->addNames()->email()->encode(false),
			'message'	 => T::out([
				'somebody_thanks_you_798' => [
					'en'		 => 'You was thanked by {{name}}{{href}}! ',
					'ru'		 => 'Вас поблагодарили от лица {{name}}{{href}}!',
					'_include'	 => [
						'name'	 => $button->get('title'),
						'href'	 => empty($real_address)
								? ''
								: ' (' . $real_address['target_address'] . ')'
					]
				]
			])
		]));
	}

	public function actionNames() {
		Yii::$app->response->format = Response::FORMAT_JSON;
		return Names::call($_REQUEST);
//        die(json_encode(Names::call($_REQUEST)));
	}

	public function actionAdd() {

		$r = $_REQUEST;

		$light = empty($_REQUEST['light'])
				? false
				: true;

		ThwUser::checkRequired($r);
		ThwModel::session(empty($r['_sid'])
						? false
						: $r['_sid']);

		$user = ThwUser::getBy([
					'net'	 => $r['net'],
					'uid'	 => $r['uid']
		]);

		if (!$user->get('registered')) {
			$user = $user->set([
				'registered' => (new \DateTime())->format('Y-m-d')
			]);
		}

		Userlog::getBy([
			'user_id'	 => $user->get('id'),
			'date'		 => (new \DateTime())->format('Y-m-d'),
			'_notfound'	 => [
				'user_id'	 => $user->get('id'),
				'date'		 => (new \DateTime())->format('Y-m-d'),
				'type'		 => 'enter'
			]
		]);

		$onlycheck = !($user->get('first_name') . $user->get('last_name'))
				? false
				: true;

		$user_profile = self::checkFBsession($r['uid'], $onlycheck, $light);

		if (empty($onlycheck)) {

			if ((!$user->get('invitable_friends') || !$user->get('invited_friends')) && empty($light)) {
				//if empty invited/invitable friends data and it is not the light mode reload it
				$user_profile = array_merge($user_profile, ThwUser::getFriends());
				$r['invited_friends'] = !empty($user_profile['invited_friends'])
						? json_encode($user_profile['invited_friends'])
						: '';
				$r['invitable_friends'] = !empty($user_profile['invitable_friends'])
						? json_encode($user_profile['invitable_friends'])
						: '';
			}

			$r['photo'] = '//graph.facebook.com/' . $r['uid'] . '/picture?type=large';
			$r['country'] = explode('_', $user_profile['locale'])[1];
			$user->add($r);
		} else {
			$user = $user->enc([
				'invited_friends'	 => 'json',
				'invitable_friends'	 => 'json'
			]);
		}

		$_SESSION['user'] = [
			'id'	 => $user->id,
			'net'	 => $user->net,
			'uid'	 => $user->uid
		];

		//here try to get unread thanks
		if (empty($light)) {
			$unread = ThwThank::getBy([
						'read'			 => 0,
						'receiver_uid'	 => $user->uid,
						'receiver_net'	 => $user->net,
						'status'		 => '!=message',
						'_return'		 => 'array',
						'_order'		 => 'changed'
			]);
		}

		if (empty($unread)) {
			$result = 0;
		} else {
			$result = [];
			foreach ($unread as $key => $val) {
				$obj = $val->encode(false);
				if (!empty($val['place'])) {
					$obj['sender_name'] = $val['place'];
				} else {
					$sender = ThwUser::getBy([
								'net'	 => $val['sender_net'],
								'uid'	 => $val['sender_uid']
					]);
					$obj['sender_name'] = $sender->get('full_name');
				}
				$result[] = $obj;
			}
		}

		die(json_encode([
			'user'			 => $user->encode(false),
			'unread'		 => $result,
			'locale_changed' => T::setLocale(empty($_SESSION['force_locale']) || empty($_SESSION['force_locale']['locale'])
							? $user->locale
							: $_SESSION['force_locale']['locale']),
			'locale'		 => isset($_SESSION['locale'])
					? $_SESSION['locale']
					: 'en',
			'profiler'		 => isset($_SESSION['profiler'])
					? $_SESSION['profiler']
					: false
		]));
	}

	public function actionUpdatefriends() {

		$r = $_REQUEST;

		M::session(empty($r['_sid'])
						? false
						: $r['_sid']);

		B::trace();

		//собственный пользователь
		$user = ThwUser::getBy([
					'net'	 => $r['net'],
					'uid'	 => $r['uid']
		]);

		$app_id = Yii::$app->params['app_id'];
		$app_secret = Yii::$app->params['app_secret'];

		//B::trace('getUser');

		FacebookSession::setDefaultApplication($app_id, $app_secret);

		//B::trace('FacebookSession');

		$response = ThwUser::batchRequest([
					'invitable_friends'	 => [
						'method'		 => 'GET',
						'relative_url'	 => '/me/invitable_friends?fields=picture.type(large),id,name'
					],
					'inv0'				 => [
						'method'		 => 'GET',
						'relative_url'	 => '/me/invitable_friends?fields=picture.type(square),id,name'
					],
					'invited_friends'	 => [
						'method'		 => 'GET',
						'relative_url'	 => '/me/friends'
					],
					'user_profile'		 => [
						'method'		 => 'GET',
						'relative_url'	 => '/me'
					]
		]);

		B::trace('facebook response');

//ThwUser::getProfile($r['uid'])

		if ($r['uid'] != $response['user_profile']['id']) {
			throw new \Exception('User profile has not retrieved!');
		}

		//print_r($response['user_profile']);

		$user_profile = array_merge(
				$response['user_profile'], ThwUser::getFriends($response)
		);

		session_write_close();

		B::trace('getProfile session closed');

		$r['invited_friends'] = !empty($user_profile['invited_friends'])
				? json_encode($user_profile['invited_friends'])
				: '';
		$r['invitable_friends'] = !empty($user_profile['invitable_friends'])
				? json_encode($user_profile['invitable_friends'])
				: '';

		//echo $r['invitable_friends'];

		$r['photo'] = '//graph.facebook.com/' . $r['uid'] . '/picture?type=large';
		$r['country'] = explode('_', $user_profile['locale'])[1];

		$first_entrance = $user->get('first_entrance');


		if (empty($first_entrance)) {
			$r['first_entrance'] = 1;
		}

		//заносим собственные данные
		$user->add($r);

		B::trace('data_saved');

		//here call karma update

		if (!empty($user_profile['invited_friends'])) {
			foreach ($user_profile['invited_friends'] as $val) {

				Names::getBy([
					'uid'		 => $user->get('uid'),
					'friend'	 => $val['id'],
					'_notfound'	 => [
						'uid'		 => $user->get('uid'),
						'friend'	 => $val['id'],
						'eng_name'	 => $val['name']
					]
				]);
				/*
				  ThwThank::getKarma(ThwUser::getBy([
				  'uid' => $val['id']
				  ])); */
			}
		}

		B::trace('update_name');

		M::ok([
			'success'		 => 'user friends updated',
			'first_entrance' => $first_entrance,
			'data'			 => ThwThank::getKarma(ThwUser::getBy([
						'net'	 => $r['net'],
						'uid'	 => $r['uid']
			])),
			'time'			 => B::trace()
		]);
	}

	public function actionGetfriendsemails() {

		$r = $_REQUEST;

		M::session(empty($r['_sid'])
						? false
						: $r['_sid']);

		if (empty($r['ids'])) {
			throw new \Exception('Не заданы ids друзей!');
		}

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$a = [];
		foreach (explode(',', $r['ids']) as $v) {
			$a[] = urlencode($v);
		}

		$db = Yii::$app->db->createCommand("
				SELECT `uid`,`email`,`invited_friends`
				FROM `thw_user`
				WHERE `uid` in (" . join(',', $a) . ")
			   ")->query();

		if (empty($db)) {
			M::no([
				'error' => 'no_data'
			]);
		}

		$return = [];
		while (($row = $db->read()) != false) {

			//check if they are friends
			$are_friends = false;
			$invited_friends = json_decode($row['invited_friends'], true);
			$are_friends = false;

			if (!empty($invited_friends)) {

				foreach ($invited_friends as $val) {
					if ($val['id'] == $user->get('uid')) {
						$are_friends = true;
					}
				}
			}

			if (!empty($are_friends)) {
				$return[$row['uid']] = $row;
			}
		}

		M::ok([
			'data' => $return
		]);
	}

	//send thank to (or invite) to selected user
	public function actionSend() {
		if ($_REQUEST['status'] === 'place') { //user -> place

			/*
			  sender_uid  <- user
			  sender_net  <- user

			  {
			  net  - 'fb', 'link_ueiieuf'  - в идеале уйти от md5
			  uid  - 123415245 или 0
			  for - за что
			  name <- placeName
			  }

			  status = place / null
			  read 0
			  used null
			 */

			$user = ThwUser::getBy([
						'id' => ThwUser::getCurrent()['id']
					])->set([
				'activity' => time()
			]);

			//если мы благодарим внешний сайт, то передаем net = link
			if ($_REQUEST['net'] === 'link' && !filter_var($_REQUEST['name'], FILTER_VALIDATE_URL)) {
				die(json_encode([
					'error'		 => 'wrong_link',
					'message'	 => T::out([
						'wrong_link' => [
							'en'		 => 'Wrong link: {{link}}',
							'ru'		 => 'Некорректнаяя ссылка: {{link}}',
							'_include'	 => [
								'link' => $d['name']
							]
						]
					])
				]));
			}

			if ($_REQUEST['net'] === 'link') {
				$_REQUEST['net'] = 'link_' . md5($d['name']);
				$_REQUEST['uid'] = 0;
			}

			die(json_encode([
				'sent'	 => ThwThank::getBy()->add([
					'sender_uid'	 => $user->get('uid'),
					'sender_net'	 => $user->get('net'),
					//{
					'receiver_net'	 => $_REQUEST['net'],
					'receiver_uid'	 => strpos($_REQUEST['net'], 'link_') === false
							? $_REQUEST['uid']
							: 0,
					'title'			 => $_REQUEST['for'],
					'place'			 => $_REQUEST['name'], //name -> converted to place
					//}
					'status'		 => 'place',
					'read'			 => 0
				])->email()->encode(false),
				'all'	 => ThwThank::formatAll([
					'start'	 => 0,
					'page'	 => 3,
					'get'	 => 'new'
				])
			]));
		}

		//user -> user

		$d = $_REQUEST;

		$user = ThwUser::getCurrent();
		$d['sender_uid'] = $user['uid'];
		$d['sender_net'] = $user['net'];

		//here we set user activity
		ThwUser::getBy([
			'id' => $user['id']
		])->set([
			'activity' => time()
		]);


		//add thank and return it back
		die(json_encode([
			'sent'	 => ThwThank::getBy()->add($d)->email()->encode(false),
			'all'	 => ThwThank::formatAll([
				'start'	 => 0,
				'page'	 => 3,
				'get'	 => 'new'
			])
		]));
	}

	public function actionAddgeodata() {
		$r = $_REQUEST;

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
				])->set([
			'lat'	 => $r['lat'],
			'lng'	 => $r['lng'],
			'when'	 => time(),
			'ip'	 => $_SERVER['REMOTE_ADDR']
		]);

		M::ok([
			'ok' => true
		]);
	}

	/**
	  expected :
	  'get' => 	'all'
	  'sent'
	  'received'
	  'unread'
	 */
	public function actionHistory() {

		$r = $_REQUEST;

		$r['get'] = empty($_REQUEST['get'])
				? 'all'
				: $_REQUEST['get'];

		die(json_encode(ThwThank::formatAll($r)));
	}

	public function actionTotalStatistics() {
		Admins::call($_REQUEST);
	}

	public function actionStatistics() {

		//all
		//countries -> pyramid
		//friends -> a list

		$r = $_REQUEST;

		if (!empty($r['filter']) && $r['filter'] == 'friends') {

			$user = ThwUser::getCurrent();

			$userObj = ThwUser::getBy([
						'id'	 => $user['id'],
						'net'	 => $user['net']
			]);

			if (empty($userObj)) {
				ThwModel::e([
					'error'		 => 'login_error',
					'message'	 => [
						'need_login' => [
							'en' => 'Please login!',
							'ru' => 'Пожалуйста войдите!'
						]
					]
				]);
			}

			$friends = $userObj->invited_friends;

			if (!empty($friends)) {
				$friends = json_decode($friends, true);

				$filter = [];
				foreach ($friends as $key => $val) {
					$filter[] = urlencode($val['id']); //encode to sanitize data
				}

				die(json_encode(
								ThwThank::getStat([
									'_filter'	 => $filter,
									'start'		 => empty($r['start'])
											? 0
											: $r['start'],
									'page'		 => empty($r['page'])
											? 7
											: $r['page']
								])
				));
			}
		}//of friends filter

		if ($r['filter'] == 'countries') {

			ThwModel::session(empty($_REQUEST['_sid'])
							? false
							: $_REQUEST['_sid']);

			die(json_encode(
							ThwThank::getStat([
								'_filter'		 => $r['filter'],
								'_ignorelimit'	 => empty($r['_ignorelimit'])
										? false
										: $r['_ignorelimit']
							])
			));
		}

		if (in_array($r['filter'], ['other',
					'other_but_friends'])) {
			ThwModel::session(empty($_REQUEST['_sid'])
							? false
							: $_REQUEST['_sid']);

			die(json_encode(
							ThwThank::getStat([
								'_filter'		 => $r['filter'],
								'start'			 => empty($r['start'])
										? 0
										: $r['start'],
								'page'			 => empty($r['page'])
										? 7
										: $r['page'],
								'_ignorelimit'	 => empty($r['_ignorelimit'])
										? false
										: $r['_ignorelimit']
							])
			));
		}

		die(json_encode(
						ThwThank::getStat([
							'_filter'		 => empty($r['filter'])
									? 'all'
									: $r['filter'],
							'_ignorelimit'	 => empty($r['_ignorelimit'])
									? false
									: $r['_ignorelimit']
						])
		));
	}

	/*
	  mark message as it is read
	 */

	public function actionRead() {

		$r = $_REQUEST;

		$user = ThwUser::getCurrent();

		if (isset($r['id'])) {
			ThwThank::getBy([
				'id'			 => $r['id'],
				'receiver_uid'	 => $user['uid'],
				'receiver_net'	 => $user['net'],
				'read'			 => 0,
				'_notfound'		 => T::out([
					'not_found' => [
						'en' => 'Record not found!',
						'ru' => 'Запись не найдена!'
					]
				])
			])->set([
				'read' => 1
			]);
			/*
			  $thank->read = 1;
			  $thank->save(false);
			 */
			return[];
		} elseif (isset($r['ids'])) {
			foreach ($r['ids'] as $id) {
				ThwThank::getBy([
					'id'			 => $id,
					'receiver_uid'	 => $user['uid'],
					'receiver_net'	 => $user['net'],
					'read'			 => 0,
					'_notfound'		 => T::out([
						'not_found' => [
							'en' => 'Record not found!',
							'ru' => 'Запись не найдена!'
						]
					])
				])->set([
					'read' => 1
				]);
			}
		} else {
			throw new \Exception('No required parameters');
		}
	}

	//return one message with childs
	public function actionMessage() {
		die(json_encode(ThwThank::getMessages($_REQUEST)));
	}

	//send message as answer
	/**
	  expected
	  id - parent message
	  message - text of message
	 */
	public function actionAnswer() {

		try {

			$r = $_REQUEST;

			ThwModel::required([
				'id'		 => 1,
				'message'	 => 1
			]);

			$parent = ThwThank::getBy([
						'id'		 => $r['id'],
						'ref'		 => 0,
						'_notfound'	 => T::out([
							'not_found' => [
								'en' => 'Record not found!',
								'ru' => 'Запись не найдена!'
							]
						])
			]);

			if ($parent->status !== '') {
				ThwModel::e([
					'error'		 => 'unable to answer on invite',
					'messgae'	 => 'unable to answer on invite'
				]);
			}

			$user = ThwUser::getCurrent();

			if ($parent->sender_uid == $user['uid'] && $parent->sender_net == $user['net']) {//if we are the sender of main message
				//echo 'stop';
				$receiver_uid = $parent->receiver_uid;
				$receiver_net = $parent->receiver_net;
			} else {
				$receiver_uid = $parent->sender_uid;
				$receiver_net = $parent->sender_net;
			}

			if ($receiver_net == $user['net'] && $receiver_uid == $user['uid']) {
				ThwModel::e([
					'error'		 => 'send_message_error',
					'message'	 => [
						'unable_to_send_self' => [
							'en' => 'Unable to send to self!',
							'ru' => 'Нельзя отправить самому себе!'
						]
					]
				]);
			}

			//!!! TODO: send email

			$new_thank = ThwThank::getBy()->add([
						'sender_net'	 => $user['net'],
						'sender_uid'	 => $user['uid'],
						'receiver_uid'	 => $receiver_uid,
						'receiver_net'	 => $receiver_net,
						'ref'			 => $parent->id,
						'message'		 => !empty($r['message'])
								? $r['message']
								: '',
						'read'			 => 0,
						'status'		 => 'message'
					])->email();


			die(json_encode(ThwThank::getMessages([
								'id' => $new_thank->id
			])));


			//it shall return the list of messages instead self message
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	public function actionUsercarma() {

		$user = ThwUser::getBy([
					'id'		 => ThwUser::getCurrent()['id'],
					'_notfound'	 => true
		]);

		die(json_encode([
			'karma' => $user->get('karma')
		]));
	}

	public function actionKarma() {
		die(json_encode(ThwThank::getKarma()));
	}

	public function actionDonate() {
		/*
		  Donation::getBy([
		  '_all' => [0 => 'array']
		  ]); */

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$amount = 100;

		$need = $user->get('donations') * 1 - $amount;

		if ($need >= 0) {

			M::ok([
				'message'			 => T::out([
					'You send donation' => [
						'en' => 'Thank you for donating 1$ to the project TakieDela.ru!',
						'ru' => 'Спасибо за пожертвование 1$ проекту TakieDela.ru!'
					]
				]),
				'available_thanks'	 => ThwThank::spend($user, $amount)
			]);
		} else {
			M::no([
				'error'		 => T::out([
					'not_enough_thank' => [
						'en' => 'You don`t have enough Thank!',
						'ru' => 'У вас не достаточно Спасибо!'
					]
				]),
				'message'	 => T::out([
					'not_enough_thank' => [
						'en' => 'You don`t have enough Thank!',
						'ru' => 'У вас не достаточно Спасибо!'
					]
				])
			]);
		}
	}

	public function actionKarmaHistory() {

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		B::trace();

		$karma = ThwThank::getKarma();

		B::trace('karma:');

		$history = ThwThank::formatAll([
					'get'	 => 'me',
					'start'	 => 0,
					'page'	 => 3
		]);

		B::trace('history');

		$friends = ThwThank::friendsAverage();

		B::trace('friends');

		die(json_encode([
			'karma'			 => $karma,
			'history'		 => $history,
			'friends_thanks' => $friends,
			'notused'		 => ThwUser::getBy([
				'id' => ThwUser::getCurrent()['id']
			])->get('donations'),
			'time'			 => B::trace('complete')
		]));
	}

	//return all translates for current locale
	public function actionTranslates() {
		die(json_encode(T::all()));
	}

	public function actionTranslate() {

		T::required([
			'key' => 1
		]);

		try {

			$t = T::getBy([
						'key'		 => $_REQUEST['key'],
						'_notfound'	 => false
			]);

			if (empty($t)) {
				ThwModel::e([
					'error'		 => 'translate_error',
					'key'		 => $_REQUEST['key'],
					'request'	 => $_REQUEST
				]);
			}

			Yii::$app->response->format = Response::FORMAT_JSON;

//            die(json_encode(
//                $t->encode(false)
			return $t;
//            ));
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	/**
	 * action random friend
	 */
	public function actionRandomFriends() {

		$User = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$friends = $User->get('normalized_friends');

		try {
			die(json_encode([
				'html' => empty($friends)
						? 0
						: $this->renderPartial('/site/random', ['email' => [
								'uid'		 => ThwModel::hsc($User->uid),
								'net'		 => ThwModel::hsc($User->net),
								'name'		 => $User->get('full_name'),
								'friends'	 => $friends
							]
								], true)
			]));
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	/**
	 * return presentation qr links of this user
	 *
	 * for each link: quantity, date
	 *
	 */
	public function actionQrlinks() {

		$period = 30;

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$links0 = Thanklink::getBy([
					'user_id'	 => $user->get('id'),
					'usage'		 => 'presentation',
					'order'		 => '`changed`',
					'_return'	 => [0 => 'array']
		]);

		$period = 365;

		$links = [];

		foreach ($links0 as $link) {
			$logs = Userthanklink::statistics([
						'link'	 => $link['id'],
						'period' => "-" . $period
			]);
			$record = $link;
			$record['amount'] = count($logs);
			$links[] = $record;
		}

		M::ok([
			'links' => $links
		]);
	}

	public function actionQrlinkstat() {
		$period = 30;

		try {
			$user = ThwUser::getCurrent(true);
			$log = Userthanklink::statistics([
						'link'	 => $_REQUEST['link'],
						'period' => "-" . $period
			]);
		} catch (\Exception $e) {
			header('Location: ' . B::setProtocol('https:', Yii::$app->params['app_source_path']));
			exit;
		}

		if (empty($log)) {
			header('Content-Type: text/html; charset=utf-8');
			die(T::out([
						'no_button_data' => [
							'en'		 => 'No activity in last {{period}} days.',
							'ru'		 => 'За последние {{period}} дней нет активности.',
							'_include'	 => [
								'period' => $period
							]
						]
			]));
		}

		ini_set("auto_detect_line_endings", true);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=qrlink_statistics.csv');
		$out = fopen('php://output', 'w');
		fwrite($out, b"\xEF\xBB\xBF");
		fputcsv($out, array_keys($log[0]), ";", '"');
		foreach ($log as $record) {
			fputcsv($out, array_values($record), ';', '"');
		}
		fclose($out);
	}

	public function actionButtonstatistics() {

		$period = 30;

		try {
			$user = ThwUser::getCurrent(true);
			$log = Buttonlog::statistics([
						'button_id'	 => $_REQUEST['button_id'],
						'period'	 => "-" . $period
			]);
		} catch (\Exception $e) {
			header('Location: ' . B::setProtocol('https:', Yii::$app->params['app_source_path']));
			exit;
		}

		if (empty($log)) {
			header('Content-Type: text/html; charset=utf-8');
			die(T::out([
						'no_button_data' => [
							'en'		 => 'No activity in last {{period}} days.',
							'ru'		 => 'За последние {{period}} дней нет активности.',
							'_include'	 => [
								'period' => $period
							]
						]
			]));
		}

		ini_set("auto_detect_line_endings", true);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=thankbutton_statistics.csv');
		$out = fopen('php://output', 'w');
		fwrite($out, b"\xEF\xBB\xBF");
		fputcsv($out, array_keys($log[0]), ";", '"');
		foreach ($log as $record) {
			fputcsv($out, array_values($record), ';', '"');
		}
		fclose($out);
	}

	/**
	 * send email
	 */
	public function actionEmail() {

		if (empty(Yii::$app->params['email_notification']) || !empty($_REQUEST['just_show'])) { //test mode (just show template)
			$User = ThwUser::getBy([
						'id' => ThwUser::getCurrent()['id']
			]);

			$friends = $User->get('normalized_friends');

			if (empty($friends)) { //no friends
				die(json_encode([]));
			}

			//TODO: from the last week

			$activity = ThwThank::formatAll([
						'get'	 => 'email',
						'begin'	 => 0,
						'page'	 => 100
			]);

			$html = $this->renderPartial('/email/email', ['email' => [
					'uid'		 => ThwModel::hsc($User->uid),
					'net'		 => ThwModel::hsc($User->net),
					'name'		 => $User->get('full_name'),
					'friends'	 => $friends,
					'activity'	 => $activity
				]
					], false);

			exit;
		} else { //work mode send notification
			session_write_close();

			//get users
			$Users = ThwUser::getBy([
						'activity'		 => '<<' . (time() - (empty(Yii::$app->params['email_frequency'])
								? 432000
								: Yii::$app->params['email_frequency'])), //(time() - 604800), //activity 1 week //432000
						'unsubscribe'	 => 0, //subscription active
						'_limit'		 => 2, //users each request
						'_return'		 => 'array',
						'email'			 => '>>0'//user has email
			]);

			//unset($Users);

			if (empty($Users)) { //no users to send
				die(json_encode([
					'mailing' => 'no users to send'
				]));
			}

			$recipients = [];

			//print_r($Users);

			foreach ($Users as $User) {

				M::log(['mail' => 'sent']);

				$User->set([
					'activity' => time()
				]);

				$recipients[] = $User->encode(false);

				//get normalized array of the objects to thank
				$friends = $User->get('normalized_friends');

				if (empty($friends)) { //no friends
					continue;
				}

				$html = H::getTemplate('letter_2', [
							'link'	 => 'https://topkarma.com',
							'text1'	 => '<p style="margin:0px;">' . T::out([
								'dear' => [
									'en' => 'Dear',
									'ru' => 'Уважаемый'
								]
							]) . ' ' . $User->get('full_name') . '!</p><p style="margin:0px;">' . T::out([
								'we_noticed_7' => [
									'en' => 'We noticed, you didn`t use TopKarma for 7 days!',
									'ru' => 'Мы заметили, что Вы не пользовались нашим сервисом уже 7 дней!'
								]
							]) . '</p>',
							'text2'	 => '<p style="margin:0px; margin-bottom:20px;">' . T::out([
								'smth_about_mom2' => [
									'en' => 'Meanwhile, your friends and relatives are waiting for your "Thank you"! May be a long time passed, since you have told "Thank you" to your Mom and Dad last time?',
									'ru' => 'Тем временем, Вас ждут множество Ваших друзей и просто людей, которым Вы могли бы послать благодарность и сказать «Спасибо!»
А возможно, Вы давно не говорили «Спасибо» своим родителям — Маме и Папе.'
								]
							]) . '</p><p style="margin:0px;">' . T::out([
								'just_click_and_be_happy' => [
									'en' => 'We are ready to assist <b>you</b> to say "Thank you". Just click the <b>Thank button</b> below.',
									'ru' => 'Мы всегда готовы помочь <b>Вам</b> сделать это. <b>Для этого</b> Вам просто нужно нажать на кнопку - <b>СКАЗАТЬ СПАСИБО</b>!'
								]
							]) . '</p>'
								], 'parse');

				$response[] = Mandrillmail::send([
							'to'		 => Yii::$app->params['email_notification'] === true
									? $User->get('email')
									: Yii::$app->params['email_notification'],
							'html'		 => $html,
							'from_name'	 => 'Top Karma',
							'subject'	 => T::out([
								'notification_subject_2' => [
									'en' => 'You didn`t say any thank you to anyone, so we picked some variants for you...',
									'ru' => 'Вы не говорили спасибо целую неделю и мы подобрали несколько вариантов для вас...'
								]
									], false, 'notag')
				]);
			}

			die(json_encode([
				'sent' => empty($response)
						? 0
						: count($response)
			]));
		}
	}

	public function actionThankmail() {

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		$email0 = filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL);

		if (empty($email0)) {
			die(json_encode([
				'title' => T::out([
					'send_email_error_tt' => [
						'en'		 => 'Empty or wrong email address: {{email}}',
						'ru'		 => 'Пустой или ошибочный email адрес: {{email}}',
						'_include'	 => [
							'email' => $_REQUEST['email']
						]
					]
				])
			]));
		}

		$link = Thanklink::getLink('sms', isset($_REQUEST['forwhat'])
								? $_REQUEST['forwhat']
								: '');

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		Maillog::create([
			'receiver_id'	 => null,
			'type'			 => 'Send thank by email',
			'code'			 => explode('thankyou', $link)[1] . '_' . ($user->d('id') + 23457890)
		])->get('code');

		//отправить ее получателю
		$text = T::out([
					'thankmailtext' => [
						'en'		 => '{{name}} thanks you! Please accept his thank and see the reason by clicking link {{link}}. Click a link {{back}} to say thank you in return.',
						'ru'		 => '{{name}} благодарит Вас! Нажмите на ссылку чтобы принять благодарность и узнать ее причину {{link}}. Нажмите на ссылку {{back}} чтобы сказать спасибо в ответ.',
						'_include'	 => [
							'name'	 => $user->get('name'),
							'link'	 => $link,
							'back'	 => B::setProtocol('https:', B::baseURL() . 'thank/' . ($user->d('id') + 23457890))
						]
					]
		]);

		//user remote thank
		$response[] = Mandrillmail::send([
					'to'		 => $email0,
					'html'		 => $text,
					'from_name'	 => 'Top Karma',
					'subject'	 => T::out([
						'thankyou_em_subject' => [
							'en' => 'Thank you!',
							'ru' => 'Спасибо!'
						]
					])
		]);

		SavedMails::getBy([
			'user_id'	 => $user->get('id'),
			'email'		 => $email0,
			'_notfound'	 => [
				'user_id'	 => $user->get('id'),
				'email'		 => $email0
			]
		]);

		if (empty($response)) {
			M::no([
				'error' => 'No mesages sent'
			]);
		}

		M::ok([
			'ok'		 => true,
			'message'	 => '',
			'title'		 => T::out([
				'success_thankmail' => [
					'en' => 'Email successfully sent',
					'ru' => 'Сообщение успешно отправлено'
				]
			]),
			'response'	 => $response
		]);
	}

	public function actionPress() {
		Pressed::call($_REQUEST);
	}

	public function actionSavedMails() {
		SavedMails::call($_REQUEST);
	}

	public function actionSavedPhones() {
		SavedPhones::call($_REQUEST);
	}

	public function actionThanksms() {

		M::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);

		if (empty($_REQUEST['phone'])) {
			die(json_encode([
				'title' => T::out([
					'empty_phone' => [
						'en' => 'Enter correct phone number.',
						'ru' => 'Введите корректный номер телефона.'
					]
				])
			]));
		}

		$phone = filter_var(strtr(trim($_REQUEST['phone']), [
			'-'	 => '',
			'.'	 => '',
			'+'	 => '',
			'/'	 => ''
				]), FILTER_SANITIZE_NUMBER_INT);

		if (empty($phone)) {
			die(json_encode([
				'title' => T::out([
					'only_digitals_with_contry_code' => [
						'en' => 'Only digitals with country code (79991234567)',
						'ru' => 'Только цифры с кодом страны (19991234567)'
					]
				])
			]));
		}

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		//тут добавлять к линку причину этого линка

		$link = Thanklink::getLink('sms', isset($_REQUEST['forwhat'])
								? $_REQUEST['forwhat']
								: '');

		$locale = T::getLocale();

		if (mb_substr($phone, 0, 1) * 1 == 7) {
			T::setLocale('ru');
		} else {
			T::setLocale('en');
		}

		$text = T::out([
					'thankmailtext' => [
						'en'		 => '{{name}} thanks you! Please accept his thank and see the reason by clicking link {{link}}. Click a link {{back}} to say thank you in return.',
						'ru'		 => '{{name}} благодарит Вас! Нажмите на ссылку чтобы принять благодарность и узнать ее причину {{link}}. Нажмите на ссылку {{back}} чтобы сказать спасибо в ответ.',
						'_include'	 => [
							'name'	 => $user->get('name'),
							'link'	 => $link,
							'back'	 => B::setProtocol('https:', B::baseURL() . 'thank/' . ($user->d('id') + 23457890))//B::setProtocol('https:', B::baseURL() . 'thank/' . base_convert($user->get('id'), 10, 36))
						]
					]
		]);

		T::setLocale($locale);

		//and now call SMS API

		$path2 = 'https://rest.nexmo.com/sms/json?api_key=' .
				Yii::$app->params['sms_key']
				. '&api_secret=' .
				Yii::$app->params['sms_secret'] . '&from=' . ($phone[1] == '1'
						? '12028388354'
						: 'TopKarma') . '&to=' . ($phone * 1) . '&type=unicode&text=' . urlencode(iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8", $text));

		$response = H::getSSLPage($path2);
		$error = false;
		if (!empty($response)) {
			$data = json_decode($response, true);

			if (!empty($data['messages'])) {
				if (!empty($data['messages'][0]['error-text'])) {
					$error = $data['messages'][0]['error-text'];
				}
			} else {
				$error = T::out([
							'sms_not_sent' => [
								'en' => 'SMS is not sent',
								'ru' => 'СМС не отправлено'
							]
				]);
			}
		} else {
			$error = T::out([
						'sms_not_sent' => [
							'en' => 'SMS is not sent',
							'ru' => 'СМС не отправлено'
						]
			]);
		}

		//curl "https://rest.nexmo.com/sms/json?api_key=98fc01bb&api_secret=395c645e&from=NEXMO&to=79629871153&text=Welcome+to+Nexmo"

		if (empty($error)) {
			SavedPhones::getBy([
				'user_id'	 => $user->get('id'),
				'phone'		 => $phone,
				'_notfound'	 => [
					'user_id'	 => $user->get('id'),
					'phone'		 => $phone
				]
			]);
		}

		M::ok([
			'title'		 => !empty($error)
					? $error
					: T::out([
						'sms_sent' => [
							'en' => 'SMS sent',
							'ru' => 'СМС отправлено'
						]
					]),
			'path'		 => $path2,
			'response'	 => json_decode($response)
		]);
	}

	public function actionGraph() {
		die(json_encode([
			'sender'	 => ThwThank::getGraph([
				'period'	 => $_REQUEST['period'],
				'direction'	 => 'sender'
			]),
			'receiver'	 => ThwThank::getGraph([
				'period'	 => $_REQUEST['period'],
				'direction'	 => 'receiver'
			])
		]));
	}

	public function actionKarmagraph() {

		if (empty($_REQUEST['place'])) {
			M::ok(Karmalog::graph([
						'period'	 => $_REQUEST['period'],
						'user_id'	 => empty($_REQUEST['user_id'])
								? false
								: $_REQUEST['user_id']
			]));
		} else {

			ThwThank::getKarmaOfPlace($_REQUEST['user_id']);

			M::ok(Karmalogplaces::graph([
						'period'	 => $_REQUEST['period'],
						'place_id'	 => $_REQUEST['user_id']
			]));
		}
	}

	public function actionLogout() {
		ThwUser::getBy([
			'id' => ThwUser::getCurrent()['id']
		])->logout();

		M::ok([
			'ok' => T::out([
				'see_you_later' => [
					'en' => 'Will be glad to see you later!',
					'ru' => 'Будем рады увидеть Вас вновь!'
				]
			])
		]);
	}

	public function actionUnsubscribe() {

		ThwUser::getBy([
			'id' => ThwUser::getCurrent()['id']
		])->set([
			'unsubscribed' => 1
		]);

		die(json_encode([
			'message' => T::out([
				'unsubscribed' => [
					'en' => 'You will not receive e-mail notifications any more.',
					'ru' => 'Вы больше не будете получать уведомления по электронной почте.'
				]
			])
		]));
	}

	/*
	 * Return data for random thank
	 */

	public function actionRandomThank() {

		$popular = ThwPopular::getAll(1);

		$User = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$invited_friends = $User->get('invited_friends');

		if (empty($invited_friends)) {

			$invitable_friends = $User->get('invitable_friends');

			if (empty($invitable_friends)) {
				die(json_encode([]));
			}

			shuffle($invitable_friends);
			die(json_encode([
				'text'	 => ThwModel::hsc($popular[0]['value']),
				'name'	 => $invitable_friends[0]['name']
			]));
		} else {
			shuffle($invited_friends);
			die(json_encode([
				'text'	 => ThwModel::hsc($popular[0]['value']),
				'name'	 => $invited_friends[0]['name']
			]));
		}
	}

	/**
	 * work with buttons on site
	 */
	public function actionButtons() {
		Buttons::call($_REQUEST);
	}

	/**
	 * Search thanks by text
	 */
	public function actionSearch() {
		ThwModel::required([
			'term' => 1
		]);

		ThwModel::session(empty($r['_sid'])
						? false
						: $r['_sid']);

		die(json_encode([
			'finded'	 => ThwThank::serchBy($_REQUEST['term']),
			'popular'	 => ThwPopular::getAll()
		]));
	}

	/**
	 * get popular requests
	 */
	public function actionGetPopular() {

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		//self thanks

		$db = Yii::$app->db->createCommand("
			SELECT `title`, count(*) as `repeat`
			FROM `thw_thank`
			WHERE `title` is not null
			AND `title` != ''
			AND `sender_uid` = :sender_uid
			GROUP BY `title`
			ORDER BY `repeat` DESC
			LIMIT 10
		", [
					':sender_uid' => $user->get('uid')
				])->query();


		$self = [];

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				$self[] = [
					'for'	 => htmlspecialchars($row['title']),
					'count'	 => $row['repeat']
				];
			}
		}

		//friends thanks
		$db = Yii::$app->db->createCommand("
			SELECT `title`, count(*) as `repeat`
			FROM `thw_thank`
			WHERE `title` is not null
			AND `title` != ''
			AND `sender_uid` in (
				SELECT `uid`
				FROM `thw_names`
				WHERE `friend` = :self_uid

				UNION

				SELECT `friend`
				FROM `thw_names`
				WHERE `uid` = :self_uid
			)
			AND `sender_uid` != :self_uid
			GROUP BY `title`
			ORDER BY `repeat` DESC
			LIMIT 10
		", [
					':self_uid' => $user->get('uid')
				])->query();

		$friends = [];

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				$friends[] = [
					'for'	 => htmlspecialchars($row['title']),
					'count'	 => $row['repeat']
				];
			}
		}

		//other thanks
		//friends thanks
		$db = Yii::$app->db->createCommand("
			SELECT `title`, count(*) as `repeat`
			FROM `thw_thank`
			WHERE `title` is not null
			AND `title` != ''
			AND `sender_uid` not in (
				SELECT `uid`
				FROM `thw_names`
				WHERE `friend` = :self_uid

				UNION

				SELECT `friend`
				FROM `thw_names`
				WHERE `uid` = :self_uid
			)
			AND `sender_uid` != :self_uid
			GROUP BY `title`
			ORDER BY `repeat` DESC
			LIMIT 10
		", [
					':self_uid' => $user->get('uid')
				])->query();

		$others = [];

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				$others[] = [
					'for'	 => htmlspecialchars($row['title']),
					'count'	 => $row['repeat']
				];
			}
		}


		M::ok([
			'self'		 => $self,
			'friends'	 => $friends,
			'others'	 => $others
		]);
	}

	public function actionGetGeoKarmaPlaces() {
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$sql = "
            SELECT DISTINCT www AS place_uid, title, lat, lng FROM thw_buttons
            WHERE description IN ('QRCODE', 'QRCODE_REVERTED')
            AND lat IS NOT NULL
            AND lng IS NOT NULL
        ";
			$cmd = Yii::$app->db->createCommand($sql);
			$res = $cmd->queryAll();
			$result = [
				'places' => []
			];
			$uids = array_map(function($item) {
				return $item['place_uid'];
			}, $res);

			$karmas = ThwThank::getKarmaOfPlaces($uids);

			foreach ($res as $r) {
				$item = [
					'place_uid'		 => $r['place_uid'],
					'title'			 => $r['title'],
					'karma'			 => 0,
					'coordinates'	 => [
						'lat'	 => floatval($r['lat']),
						'lng'	 => floatval($r['lng'])
					]
				];
				foreach ($karmas as $karma) {
					if ($karma['receiver_uid'] == $item['place_uid']) {
						$item['karma'] = $karma['count'];
						break;
					}
				}

				$result['places'][] = $item;
			}

			return $result;
		}
	}

	public function actionGetGeoThankPlaces() {

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		$lat = $user->get('lat') * 1;
		$lng = $user->get('lng') * 1;

		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;

			if (!empty($_REQUEST['near'])) {

				$radius = S::getBy([
							'key'		 => 'geo_radius',
							'_notfound'	 => [
								'key'	 => 'geo_radius',
								'val'	 => '1000',
							]
						])->get('val') * 5;

				$radius = $radius / 111111;

				$sql = "
            SELECT DISTINCT www AS place_uid, title, lat, lng FROM thw_buttons
            WHERE description IN ('QRCODE', 'QRCODE_REVERTED')
            AND lat IS NOT NULL
            AND lng IS NOT NULL
			AND SQRT(POW($lat - lat, 2) + POW($lng - lng, 2)) <= $radius";
				
			} else {
				
				$sql = "
            SELECT DISTINCT www AS place_uid, title, lat, lng FROM thw_buttons
            WHERE description IN ('QRCODE', 'QRCODE_REVERTED')
            AND lat IS NOT NULL
            AND lng IS NOT NULL";
			}

			$cmd = Yii::$app->db->createCommand($sql);
			$res = $cmd->queryAll();

			$result = [
				'places' => [],
				'self'	 => [
					'lat'	 => $lat,
					'lng'	 => $lng,
					'url'	 => '//graph.facebook.com/' . $user->get('uid') . '/picture/?width=20&height=20'
				]
			];

			$uids = array_map(function($item) {
				return $item['place_uid'];
			}, $res);

			$thanks = ThwThank::getThanksOfPlaces($uids);
			$karmas = ThwThank::getKarmaOfPlaces($uids);

			foreach ($res as $r) {
				$item = [
					'place_uid'		 => $r['place_uid'],
					'title'			 => $r['title'],
					'karma'			 => 0,
					'thank_count'	 => 0,
					'coordinates'	 => [
						'lat'	 => floatval($r['lat']),
						'lng'	 => floatval($r['lng'])
					]
				];
				foreach ($karmas as $karma) {
					if ($karma['receiver_uid'] == $item['place_uid']) {
						$item['karma'] = $karma['count'];
						break;
					}
				}

				foreach ($thanks as $thank) {
					if ($thank['receiver_uid'] == $item['place_uid']) {
						$item['thank_count'] = $thank['thank_count'];
						break;
					}
				}

				$result['places'][] = $item;
			}

			return $result;
		}
	}

	public function actionGetGeoOfCurrentUser($defaultMoscow = false) {
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$user = ThwUser::getCurrent();
			$coordinates = ThwUser::getCoordinates($user['id'], $defaultMoscow);
			return $coordinates;
		}
	}

	public function actionSetGeoPlaceCoordinates() {
		Yii::$app->response->format = Response::FORMAT_JSON;
		$post = Yii::$app->request->post();
		Buttons::setCoordinates($post);
	}

	public function actionGetGeoPlaceCoordinates($id, $description, $defaultMoscow = false) {
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$sql = "
                SELECT lat, lng FROM thw_buttons
                WHERE id = :id AND description = :description AND lat IS NOT NULL AND lng IS NOT NULL
            ";

			$result = Yii::$app->db->createCommand($sql, [':id'			 => $id,
						':description'	 => $description])->queryOne();
			if (!$result) {
				return $this->actionGetGeoOfCurrentUser($defaultMoscow);
			} else {
				$result['lat'] = floatval($result['lat']);
				$result['lng'] = floatval($result['lng']);
			}

			return $result;
		}
	}

	public function actionGetAllThanks($filter = null) {
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$me = ThwUser::getBy([
						'id' => ThwUser::getCurrent()['id']
			]);

			$lat = $me->get('lat') * 1;
			$lng = $me->get('lng') * 1;

			$sql = "
                SELECT * FROM thw_thank
                WHERE `lat` IS NOT NULL AND `lng` IS NOT NULL AND
            ";

			$where = 'TRUE';

			$params = [];

			if (empty($filter) || $filter === 'friends_near') {
				$radius = S::getBy([
							'key'		 => 'geo_radius',
							'_notfound'	 => [
								'key'	 => 'geo_radius',
								'val'	 => '1000',
							]
						])->get('val') * 5;

				// Get radius in degrees. 1 degree = 111.111 km = 111111 m
				$radius = $radius / 111111;

				if (empty($filter)) {
					$where = "SQRT(POW($lat - lat, 2) + POW($lng - lng, 2)) <= $radius";
				} else {
					$uid = $me->get('uid');
					$where = "`sender_uid` IN (SELECT `uid` as `user_uid` from `thw_names`
                        WHERE `friend` = :uid
                        AND `uid` != :uid
                        UNION
                        SELECT `friend` as `user_uid` from `thw_names`
                        WHERE `uid` = :uid
                        AND `friend` != :uid) 
						AND receiver_uid = :uid
						AND " . "SQRT(POW($lat - lat, 2) + POW($lng - lng, 2)) <= $radius";
					$params[':uid'] = $uid;
				}
			} else {
				if ($filter === 'y') {
					$y = date('Y-m-d H:i:s', (time() - 365 * 24 * 60 * 60));
					$where = "changed > '$y'";
				} else if ($filter === 'm') {
					$m = date('Y-m-d H:i:s', (time() - 30 * 24 * 60 * 60));
					$where = "changed > '$m'";
				} else if ($filter === 'friends') {
					$uid = $me->get('uid');
					$where = "sender_uid IN (SELECT `uid` as `user_uid` from `thw_names`
                        WHERE `friend` = :uid
                        AND `uid` != :uid
                        UNION
                        SELECT `friend` as `user_uid` from `thw_names`
                        WHERE `uid` = :uid
                        AND `friend` != :uid) AND receiver_uid = :uid";
					$params[':uid'] = $uid;
				}
			}

			$sql .= $where;

			$result = Yii::$app->db->createCommand($sql, $params)->queryAll();

			$return = [];
			foreach ($result as $record) {
				$new = $record;
				$new['sender_name'] = ThwUser::getBy([
							'uid' => $record['sender_uid']
						])->get('full_name');

				$new['receiver_name'] = empty($record['place'])
						? ThwUser::getBy([
							'uid' => $record['receiver_uid']
						])->get('full_name')
						: $record['place'];
				$return[] = $new;
			}
			return [
				'places' => $return,
				'self'	 => [
					'lat'	 => $lat,
					'lng'	 => $lng,
					'url'	 => '//graph.facebook.com/' . $me->get('uid') . '/picture/?width=20&height=20'
				]
			];
		}
	}

}
