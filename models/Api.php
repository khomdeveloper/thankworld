<?php

namespace app\models;

use \Exception;
use Yii;
use app\components\vh2015\B;
use app\components\vh2015\T;
use app\components\vh2015\H;
use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use app\components\vh2015\Mandrillmail;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Api
 *
 * @author valera261104
 */
class Api {

	//put your code here

	public static function getAvailableMethods() {
		return [
			'GetFBUserQty'								 => true,
			'GetFBUsersByName'							 => true,
			'RegisterThank'								 => 0,
			'GetThanksForUserId'						 => true,
			'GetUserKarmaById'							 => true,
			'GetFriendsKarmaAverageByUserId'			 => true,
			'GetFriendsThankAverageByUserId'			 => true,
			'GetLastFriendThanksByUserId'				 => true,
			'GetFriendListWithKarmaData'				 => true,
			'GetEnterpriseListWithKarmaData'			 => true,
			'GetPopularEnterpriseListBySearchingText'	 => true,
			'registerThankToEnterprise'					 => true,
			'registerAndSendThankByEmail'				 => true,
			'registerAndSendThankBySMS'					 => true,
			'getApplicationUserURLLink'					 => true,
			'registerGeoPoint'							 => true
		];
	}

	/**
	 * get picture by id
	 */
	public static function getPicture($uid, $place = false, $net = 'fb') {
		if (strpos($net, 'link_') === false) {
			return 'https://graph.facebook.com/' . $uid . '/picture/?type=large';
		} else {
			$button = Buttons::getBy([
						'www'		 => $place,
						'_notfound'	 => false
			]);

			if (empty($button)) {
				return B::setProtocol('https:', B::baseURL() . 'images/question.png');
			} else {
				return B::setProtocol('https:', B::baseURL() . $button->get(['image' => 0]));
			}
		}
	}

	/**
	 * check if we call for api method
	 */
	public static function isRequest($r) {
		foreach (self::getAvailableMethods() as $key => $val) {
			if (isset($r[$key])) {
				return true;
			}
		}
		return false;
	}

	public static function getCall($r) {
		$methods = self::getAvailableMethods();
		$param = [];
		foreach ($r as $key => $val) {
			$param[$key] = $val;
			if (isset($methods[$key])) {
				if ($val != 1) {//json expected
					return [
						'com'	 => $key,
						'arg'	 => json_decode(urldecode($val), true)
					];
				} else {
					$com = $key;
				}
			}
		}

		if (!empty($com)) {
			return [
				'com'	 => $com,
				'arg'	 => $param
			];
		}

		throw new Exception('Unknown method');
	}

	public static function call($r) {

		$call = self::getCall($r);

		//return $call;

		if (empty($call['arg'])) {
			throw new Exception('no arguments');
		}

		if (empty($call['arg']['token'])) {
			echo $_SESSION['facebook_token'] . '<p>';
			throw new Exception('Empty facebook API token');
		}

		//check FB authorization
		FacebookSession::setDefaultApplication(Yii::$app->params['app_id'], Yii::$app->params['app_secret']);
		$session = new FacebookSession($call['arg']['token']);
		$session->validate();

		if ($call['com'] === 'GetFBUserQty') {
			$amount = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_user`
				", [])->queryScalar();

			return [
				'count' => $amount * 1
			];
		} elseif ($call['com'] === 'registerGeoPoint') {

			$user_uid = (new FacebookRequest(
					$session, 'GET', '/me'
					))->execute()->getGraphObject(GraphUser::className())->getId();

			$user = ThwUser::getBy([
						'uid'	 => $user_uid,
						'net'	 => 'fb'
			]);
			
			
			if (!empty($call['arg']['longitude']) && !empty($call['arg']['latitude']) && !empty($user)) {
				$user->set([
					'lat'	 => $call['arg']['latitude'],
					'lng'	 => $call['arg']['longitude'],
					'when'	 => time(),
					'ip'	 => $_SERVER['REMOTE_ADDR']
				]);

				return [
					'success' => 1
				];
			}

			return [
				'success' => 0
			];
		} elseif ($call['com'] === 'registerThankToEnterprise') {

			$user_profile = (new FacebookRequest(
					$session, 'GET', '/me'
					))->execute()->getGraphObject(GraphUser::className());


			if (empty($call['arg']['receiver_uid'])) {
				throw new Exception('Need receiver_uid');
			}

			return [
				'thank_id' => ThwThank::getBy()->add([
					'sender_uid'	 => $user_profile->getId(),
					'sender_net'	 => 'fb',
					'receiver_uid'	 => $call['arg']['receiver_uid'],
					'receiver_net'	 => 'fb',
					'title'			 => $call['arg']['forWhat'],
					'status'		 => 'place',
					'place'			 => $call['arg']['name']
				])->get('id') * 1
			];
		} elseif ($call['com'] === 'GetPopularEnterpriseListBySearchingText') {

			//get_user
			if (empty($call['arg']['user_uid'])) {//try to get self data
				$user_uid = (new FacebookRequest(
						$session, 'GET', '/me'
						))->execute()->getGraphObject(GraphUser::className())->getId();
			} else {
				$user_uid = $call['arg']['user_uid'];
			}

			$User = ThwUser::getBy([
						'uid'	 => $user_uid,
						'net'	 => 'fb'
			]);

			if (empty($User)) {
				throw new Exception('Login error');
			}

			$thanks = ThwThank::getStat([
						'_filter'	 => 'other',
						'start'		 => empty($call['arg']['currentPage'])
								? 0
								: $r['start'],
						'page'		 => empty($call['arg']['qtyOnPage'])
								? 7
								: $call['arg']['qtyOnPage']
			]);

			if (empty($thanks['data'])) {
				throw new Exception('No data');
			}

			$return = [
				'data'	 => [],
				'total'	 => $thanks['count']
			];

			foreach ($thanks['data'] as $thank) {

				if (in_array($thank['net'], ['email',
							'phone'])) {
					$isemail = true;
				}

				$return['data'][] = [
					'id'		 => $thank['receiver_uid'],
					'name'		 => $thank['name'],
					'thanksQty'	 => $thank['count'],
					'image'		 => strpos($thank['net'], 'link_') === false
							? ((empty($thank['receiver_uid']) || !empty($isemail))
									? ''
									: 'https://graph.facebook.com/' . $thank['receiver_uid'] . '/picture/?type=large')
							: (isset($thank['logo'])
									? $thank['logo']
									: '')
				];
			}

			die(json_encode($return));
		} elseif ($call['com'] === 'GetEnterpriseListWithKarmaData') {

			//get_user
			if (empty($call['arg']['user_uid'])) {//try to get self data
				$user_uid = (new FacebookRequest(
						$session, 'GET', '/me'
						))->execute()->getGraphObject(GraphUser::className())->getId();
			} else {
				$user_uid = $call['arg']['user_uid'];
			}

			$User = ThwUser::getBy([
						'uid'	 => $user_uid,
						'net'	 => 'fb'
			]);

			if (empty($User)) {
				throw new Exception('Login error');
			}

			$thanks = ThwThank::getStat([
						'_filter'	 => 'other',
						'start'		 => empty($call['arg']['currentPage'])
								? 0
								: $r['start'],
						'page'		 => empty($call['arg']['qtyOnPage'])
								? 7
								: $call['arg']['qtyOnPage']
			]);

			if (empty($thanks['data'])) {
				throw new Exception('No data');
			}

			$return = [
				'data'	 => [],
				'total'	 => $thanks['count']
			];

			foreach ($thanks['data'] as $thank) {
				$return['data'][] = [
					'uid'	 => $thank['receiver_uid'],
					'name'	 => $thank['name'],
					'logo'	 => strpos($thank['receiver_net'], 'link_') !== false
							? (isset($thank['logo'])
									? B::setProtocol('https:', B::baseURL() . explode('//', $thank['logo'])[0])
									: '')
							: 'https://graph.facebook.com/' . $thank['receiver_uid'] . '/picture/?type=large',
					'karma'	 => $thank['count'],
					'raise'	 => $thank['raise'],
					'status' => ThwThank::getKarmaImage($thank['count']),
				];
			}

			die(json_encode($return));
		} elseif ($call['com'] === 'GetFriendListWithKarmaData') {

			//get_user
			if (empty($call['arg']['user_uid'])) {//try to get self data
				$user_uid = (new FacebookRequest(
						$session, 'GET', '/me'
						))->execute()->getGraphObject(GraphUser::className())->getId();
			} else {
				$user_uid = $call['arg']['user_uid'];
			}

			$User = ThwUser::getBy([
						'uid'	 => $user_uid,
						'net'	 => 'fb'
			]);

			if (empty($User)) {
				throw new Exception('Login error');
			}

			$friends = $User->invited_friends;


			if (!empty($friends)) {

				$friends = json_decode($friends, true);

				$filter = [];
				$f_friends = [];
				foreach ($friends as $key => $val) {
					$filter[] = urlencode($val['id']); //encode to sanitize data
					$f_friends[$val['id']] = [
						'uid'	 => $val['id'],
						'name'	 => $val['name'],
						'photo'	 => 'https://graph.facebook.com/' . $val['id'] . '/picture/?type=large'
					];
				}

				$thanks = ThwThank::getStat([
							'_filter'	 => $filter,
							'start'		 => empty($call['arg']['currentPage'])
									? 0
									: $r['start'],
							'page'		 => empty($call['arg']['qtyOnPage'])
									? 7
									: $call['arg']['qtyOnPage']
				]);

				if (empty($thanks['data'])) {
					throw new Exception('No data');
				}

				$return = [
					'data'	 => [],
					'total'	 => $thanks['count']
				];
				foreach ($thanks['data'] as $thank) {
					if (isset($f_friends[$thank['receiver_uid']])) {

						$return['data'][] = [
							'uid'	 => $thank['receiver_uid'],
							'name'	 => $f_friends[$thank['receiver_uid']]['name'],
							'photo'	 => $f_friends[$thank['receiver_uid']]['photo'],
							'karma'	 => $thank['count'],
							'raise'	 => $thank['raise'],
							'status' => ThwThank::getKarmaImage($thank['count'])
						];
					}
				}

				die(json_encode($return));
			}
		} elseif ($call['com'] === 'GetFBUsersByName') {

			/*
			 * currentPage
			 * qtyOnPage
			 * name			 
			 */

			$total = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_user`
				WHERE `name` like :name
				", [
						':name' => '%' . $call['arg']['name'] . '%'
					])->queryScalar();

			$pagination = B::pagination($total, empty($call['arg']['currentPage'])
									? 0
									: $call['arg']['currentPage'], empty($call['arg']['qtyOnPage'])
									? 1000
									: $call['arg']['qtyOnPage']);

			$result = Yii::$app->db->createCommand("
				SELECT `id`,`net`,`uid`,`first_name`,`last_name`,`name`,`link`,`photo`,`karma`
				FROM `thw_user`
				WHERE `name` like :name
				LIMIT " . $pagination['start'] . "," . $pagination['limit'] . "
				", [
						':name' => '%' . $call['arg']['name'] . '%'
					])->query();

			$users = [];
			while (($row = $result->read()) != false) {
				$users[] = $row;
			}

			return [
				'totalRecords'	 => $total,
				'currentPage'	 => $pagination['current'],
				'qtyOnPage'		 => $pagination['page'],
				'users'			 => $users
			];
		} elseif ($call['com'] === 'GetLastFriendThanksByUserId') {

			//get_user
			if (empty($call['arg']['user_uid'])) {//try to get self data
				$user_uid = (new FacebookRequest(
						$session, 'GET', '/me'
						))->execute()->getGraphObject(GraphUser::className())->getId();
			} else {
				$user_uid = $call['arg']['user_uid'];
			}

			//get user friends
			$db = Yii::$app->db->createCommand("
				SELECT `uid` as `user_uid` from `thw_names`
				WHERE `friend` = :uid
				AND `uid` != :uid
				UNION
				SELECT `friend` as `user_uid` from `thw_names`
				WHERE `uid` = :uid
				AND `friend` != :uid
			", [
						':uid' => $user_uid
					])->query();

			$friends = [];

			while (($row = $db->read()) != false) {
				$friends[] = urlencode($row['user_uid']);
			}

			$total = Yii::$app->db->createCommand("
				SELECT	count(*) as `count`
				FROM `thw_thank`
				WHERE (`receiver_uid` in ('" . join("','", $friends) . "')
				OR `sender_uid` in ('" . join("','", $friends) . "'))
				AND `sender_uid` != :uid
				AND `receiver_uid` != :uid
				", [
						':uid' => $user_uid
					])->queryScalar();

			$pagination = B::pagination($total, empty($call['arg']['currentPage'])
									? 0
									: $call['arg']['currentPage'], empty($call['arg']['qtyOnPage'])
									? 1000
									: $call['arg']['qtyOnPage']);

			$db = Yii::$app->db->createCommand("
				SELECT	`id`,
						`sender_uid`,
						`sender_net`,
						`receiver_uid`,
						`receiver_net`,
						`title`,
						`status`,
						`place`,
						`read`,
						`used`,
						`changed` as `date`
				FROM `thw_thank`
				WHERE (`receiver_uid` in ('" . join("','", $friends) . "')
				OR `sender_uid` in ('" . join("','", $friends) . "'))
					AND `receiver_uid` != :uid
					AND `sender_uid` != :uid
				ORDER BY `date` DESC	
				LIMIT " . $pagination['start'] . "," . $pagination['limit'] . "					
				", [
						':uid' => $user_uid
					])->query();

			$thanks = [];

			while (($row = $db->read()) != false) {
				$a = $row;
				$a['image'] = [
					'sender'	 => self::getPicture($row['sender_uid'], $row['place'], 'fb'),
					'receiver'	 => self::getPicture($row['receiver_uid'], $row['place'], $row['receiver_net'])
				];
				$thanks[] = $a;
			}

			//print_r($thanks);

			return [
				'totalRecords'	 => $total,
				'pagination'	 => [
					'currentPage'	 => $pagination['current'],
					'qtyOnPage'		 => $pagination['page']
				],
				'thanks'		 => $thanks
			];
		} elseif ($call['com'] === 'GetThanksForUserId') {

			/*
			 *   sender_uId : 123456789
			  currentPage : 0
			  qtyOnPage : 5
			 */

			if (empty($call['arg']['user_uid'])) {//try to get self data
				$user_uid = (new FacebookRequest(
						$session, 'GET', '/me'
						))->execute()->getGraphObject(GraphUser::className())->getId();
			} else {
				$user_uid = $call['arg']['user_uid'];
			}

			$total = [];

			//
			$total['received'] = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank`
				WHERE `receiver_uid` = :user_uid
				", [
						':user_uid' => $user_uid
					])->queryScalar();

			$total['sent'] = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank`
				WHERE `sender_uid` = :sender_uid
				", [
						':sender_uid' => $user_uid
					])->queryScalar();

			$notused = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank`
				WHERE `receiver_uid` = :receiver_uid
				AND `used` is null
				", [
						':receiver_uid' => $user_uid
					])->queryScalar();

			$pagination = [];

			$pagination['sent'] = B::pagination($total['sent'], empty($call['arg']['currentPage'])
									? 0
									: $call['arg']['currentPage'], empty($call['arg']['qtyOnPage'])
									? 1000
									: $call['arg']['qtyOnPage']);

			$pagination['received'] = B::pagination($total['received'], empty($call['arg']['currentPage'])
									? 0
									: $call['arg']['currentPage'], empty($call['arg']['qtyOnPage'])
									? 1000
									: $call['arg']['qtyOnPage']);

			$db = Yii::$app->db->createCommand("
				SELECT	`id`,
						`sender_uid`,
						`sender_net`,
						`receiver_uid`,
						`receiver_net`,
						`title`,
						`status`,
						`place`,
						`read`,
						`used`,
						`changed` as `date`
				FROM `thw_thank`
				WHERE `receiver_uid` = :receiver_uid
				LIMIT " . $pagination['received']['start'] . "," . $pagination['received']['limit'] . "
				", [
						':receiver_uid' => $user_uid
					])->query();

			$thanks = [
				'received'	 => [],
				'sent'		 => []
			];

			while (($row = $db->read()) != false) {
				$a = $row;
				$a['image'] = [
					'sender' => self::getPicture($row['sender_uid'], $row['place'], 'fb'),
				];
				$thanks['received'][] = $a;
			}

			$db = Yii::$app->db->createCommand("
				SELECT	`id`,
						`sender_uid`,
						`sender_net`,
						`receiver_uid`,
						`receiver_net`,
						`title`,
						`status`,
						`place`,
						`read`,
						`used`,
						`changed` as `date`
				FROM `thw_thank`
				WHERE `sender_uid` = :sender_uid
				LIMIT " . $pagination['sent']['start'] . "," . $pagination['sent']['limit'] . "
				", [
						':sender_uid' => $user_uid
					])->query();

			while (($row = $db->read()) != false) {
				$a = $row;
				$a['image'] = [
					'receiver' => self::getPicture($row['receiver_uid'], $row['place'], $row['receiver_net'])
				];
				$thanks['sent'][] = $a;
			}

			return [
				'notUsed'		 => $notused,
				'totalRecords'	 => $total,
				'pagination'	 => [
					'sent'		 => [
						'currentPage'	 => $pagination['sent']['current'],
						'qtyOnPage'		 => $pagination['sent']['page']
					],
					'received'	 => [
						'currentPage'	 => $pagination['received']['current'],
						'qtyOnPage'		 => $pagination['received']['page']
					]
				],
				'thanks'		 => $thanks
			];
		} elseif (in_array($call['com'], ['GetUserKarmaById',
					'GetFriendsKarmaAverageByUserId',
					'GetFriendsThankAverageByUserId'])) {

			if (!empty($call['arg']['sender_uid'])) {
				$call['arg']['user_uid'] = $call['arg']['sender_uid'];
			}

			$user = empty($call['arg']['user_uid'])
					? ThwUser::getBy([
						'uid'	 => (new FacebookRequest(
						$session, 'GET', '/me'
						))->execute()->getGraphObject(GraphUser::className())->getId(),
						'net'	 => 'fb'
					])
					: ThwUser::getBy([
						'uid'	 => $call['arg']['user_uid'],
						'net'	 => 'fb'
			]);

			if (in_array($call['com'], [
						'GetUserKarmaById',
						'GetFriendsKarmaAverageByUserId'
					])) {
				return [
					'karma' => ThwThank::getKarma($user, 'noemail')[
					$call['com'] === 'GetUserKarmaById'
							? 'karma'
							: 'average_friends_karma'
					]
				];
			} else {

				return [
					'thanks' => ThwThank::friendsAverage([
						'user' => $user
					])
				];
			}
		} elseif ($call['com'] === 'registerAndSendThankByEmail') {


			try {

				$email0 = filter_var($call['arg']['email'], FILTER_VALIDATE_EMAIL);

				if (empty($email0)) {
					throw new Exception('Wrong email');
				}

				$user = ThwUser::getBy([
							'uid'	 => (new FacebookRequest(
							$session, 'GET', '/me'
							))->execute()->getGraphObject(GraphUser::className())->getId(),
							'net'	 => 'fb'
				]);

				$_SESSION['user'] = [
					'id'	 => $user->id,
					'net'	 => $user->net,
					'uid'	 => $user->uid
				];

				$link = Thanklink::getLink('sms', isset($call['arg']['forWhat'])
										? $call['arg']['forWhat']
										: '', $user);

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
					throw new \Exception('No messages sent');
				}

				return 1;
			} catch (\Exception $ex) {
				if (empty($call['arg']['debug'])) {
					return 0;
				} else {
					throw new \Exception($ex->getMessage());
				}
			}
		} elseif ($call['com'] === 'getApplicationUserURLLink') {

			try {

				$user = ThwUser::getBy([
							'uid'	 => (new FacebookRequest(
							$session, 'GET', '/me'
							))->execute()->getGraphObject(GraphUser::className())->getId(),
							'net'	 => 'fb'
				]);

				$_SESSION['user'] = [
					'id'	 => $user->id,
					'net'	 => $user->net,
					'uid'	 => $user->uid
				];

				$link = Thanklink::getLink('sms', isset($call['arg']['forWhat'])
										? $call['arg']['forWhat']
										: '', $user);

				return $link;
			} catch (\Exception $ex) {
				if (empty($call['arg']['debug'])) {
					return 0;
				} else {
					throw new \Exception($ex->getMessage());
				}
			}
		} elseif ($call['com'] === 'registerAndSendThankBySMS') {

			try {

				if (empty($call['arg']['phone'])) {

					if (!empty($call['arg']['smsNo'])) {
						$call['arg']['phone'] = $call['arg']['smsNo'];
					} else {

						throw new \Exception(T::out([
							'empty_phone' => [
								'en' => 'Enter correct phone number.',
								'ru' => 'Введите корректный номер телефона.'
							]
						]));
					}
				}

				$phone = filter_var(strtr(trim($call['arg']['phone']), [
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
							'uid'	 => (new FacebookRequest(
							$session, 'GET', '/me'
							))->execute()->getGraphObject(GraphUser::className())->getId(),
							'net'	 => 'fb'
				]);

				$_SESSION['user'] = [
					'id'	 => $user->id,
					'net'	 => $user->net,
					'uid'	 => $user->uid
				];

				$link = Thanklink::getLink('sms', isset($call['arg']['forWhat'])
										? $call['arg']['forWhat']
										: '', $user);

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

				if ($error) {
					throw new \Exception($error);
				}

				return 1;
			} catch (\Exception $ex) {
				if (empty($call['arg']['debug'])) {
					return 0;
				} else {
					throw new \Exception($ex->getMessage());
				}
			}

			//============== Register Thank =================
		} elseif ($call['com'] === 'RegisterThank') {

			$user_profile = (new FacebookRequest(
					$session, 'GET', '/me'
					))->execute()->getGraphObject(GraphUser::className());


			//print_r($user_profile);
			//TODO: check if user is itself

			/**
			  receiver_uId : 321654987,		// FB ID  принимателя
			  forWhat : ‘usyufwuguqywiduhisudh’
			 * //1468755150066997
			 * 
			 */
			if (empty($call['arg']['receiver_uid'])) {
				throw new \Exception('Need receiver_uid');
			}

			if ($call['arg']['receiver_uid'] == $user_profile->getId()) {
				throw new \Exception('Unable to thank myself');
			}

			return [
				'thank_id' => ThwThank::getBy()->add([
					'sender_uid'	 => $user_profile->getId(),
					'sender_net'	 => 'fb',
					'receiver_uid'	 => $call['arg']['receiver_uid'],
					'receiver_net'	 => 'fb',
					'title'			 => $call['arg']['forWhat'],
					'status'		 => ''
				])->get('id') * 1
			];
		} else {
			throw new \Exception('Uncknown method');
		}
	}

}
