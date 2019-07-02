<?php

//require_once Settings::getPath() . '/facebook/autoload.php';
//require_once Settings::getPath() . '/vh2015/autoload.php';

namespace app\models;

use Yii;
use app\components\vh2015\M;
use app\components\vh2015\T;
use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;

/**
 * Description of Names
 *
 * This is the class which provides National names from facebook
 * 
 * @author valera261104
 */
class Names extends M {

	public static function tableName() {
		return '{{%names}}';
	}

	//put your code here

	public static function call($r) {

		M::session(empty($r['_sid'])
						? false
						: $r['_sid']);
		$com = $r['Names'];

		$user = ThwUser::getBy([
					'uid' => ThwUser::getCurrent()['uid']
		]);

		session_write_close();

		try {

			//get names by ids
			//write names
			if ($com == 'write') {

				self::checkRequired();

				if (empty($user)) {
					M::no([
						'error'		 => 'user_not_found',
						'message'	 => T::out([
							'user_not_found' => [
								'en' => 'User not logged',
								'ru' => 'Пользователь не авторизован'
							]
						])
					]);
				}

				//check if name in friend list

				$friends = [];

				$invited = $user->get('invited_friends');

				if (!empty($invited)) {
					foreach ($invited as $record) {
						$friends[$record['name']] = true;
					}
				}

				$invitable = $user->get('invitable_friends');

				if (!empty($invitable)) {
					foreach ($invitable as $record) {
						$friends[$record['name']] = true;
					}
				}

				if (isset($friends[$r['name']])) {


					$name = self::getBy([
								'uid'		 => $r['uid'],
								'friend'	 => $user->get('uid'),
								'_notfound'	 => [
									'net'		 => 'facebook',
									'uid'		 => $r['uid'],
									'name'		 => $r['name'],
									'eng_name'	 => $r['eng_name'],
									'friend'	 => $user->get('uid')
								]
					]);

					if (!empty($r['name']) && !$name->get('name')) {
						$name->set([
							'name' => $r['name']
						]);
					}

					M::commit();
					return [
						'names'	 => 'updated',
						'name'	 => $name->get('name')
					];
				} else {//not included
					$obj = self::getBy([
								'uid'	 => $r['uid'],
								'friend' => $user->get('uid')
					]);

					if (!empty($obj)) {
						$obj->remove();

						M::ok([
							'removed' => [
								'uid' => $r['uid']
							]
						]);
					} else {
						M::ok([
							'nothing' => 'removed'
						]);
					}
				}
			} elseif ($com == 'gender') {
				
			} elseif ($com == 'read') {

				if (empty($user)) {
					M::no([
						'error'		 => 'user_not_found',
						'message'	 => T::out([
							'user_not_found' => [
								'en' => 'User not logged',
								'ru' => 'Пользователь не авторизован'
							]
						])
					]);
				}

				$friends = [];

				$invited = $user->get('invited_friends');

				if (!empty($invited)) {
					foreach ($invited as $record) {
						$friends[$record['name']] = true;
					}
				}

				$invitable = $user->get('invitable_friends');

				if (!empty($invitable)) {
					foreach ($invitable as $record) {
						$friends[$record['name']] = true;
					}
				}

				//var_dump($friends);

				$names = self::getBy([
							'friend'	 => $user->get('uid'),
							'_return'	 => ['uid' => 'array']
				]);

				if (empty($names)) {
					M::ok([]);
				}

				$return = [];
				foreach ($names as $name) {
					if (isset($friends[$name['name']])) {
						$return[$name['name']] = $name;
					}
				}

				M::commit();
				return empty($return)
						? []
						: $return;
			}

			return [];
		} catch (\Exception $e) {
			M::no([
				'error'		 => 'kernel_error',
				'message'	 => $e->getMessage()
			]);
		}
	}

	public function callGender() {

		//not completed because application do not give information about gender

		$app_id = Yii::$app->params['app_id'];
		$app_secret = Yii::$app->params['app_secret'];

		FacebookSession::setDefaultApplication($app_id, $app_secret);

		$session = new FacebookSession($_SESSION['facebook_token']);

		if ($session) {
			$user_profile = (new FacebookRequest(
					$session, 'GET', '/' . $this->get('uid')
					))->execute()->getGraphObject(GraphUser::className());

			if (!empty($user_profile)) {
				$user_profile = $user_profile->asArray();
				//print_r($user_profile);
			}
		}
	}

	public function get($what, $data = false) {

		if ($what == 'gender') {
			//die($this->gender);
			if (empty($this->gender)) {
				$this->gender = $this->callGender();
			}
			return $this->gender;
		}

		return parent::get($what, $data);
	}

	public static function f($which = false) {
		return [
			'title'		 => 'Names cash',
			'required'	 => [
				'net'		 => 0,
				'eng_name'	 => 0,
				'uid'		 => 1,
				'name'		 => 0,
				'friend'	 => 0,
				'gender'	 => 0
			],
			'create'	 => [
				'net'		 => "tinytext comment 'Social newtwork'",
				'eng_name'	 => "tinytext comment 'Eng user name'",
				'uid'		 => "bigint unsigned comment 'User id in social network'",
				'name'		 => "tinytext comment 'Russian name'",
				'friend'	 => "bigint comment 'uid of user in game who is a common friend'",
				'gender'	 => "tinytext comment 'gender'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}
}
