<?php

namespace app\models;

use app\components\vh2015\B;
use app\components\vh2015\M;
use app\components\vh2015\T;
use Yii;
use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;

class ThwUser extends ThwModel {

	/**
	 * @return string the associated database table name
	 */
	public static function tableName() {
		return '{{%user}}';
	}

	public static function getCurrent($throwerror = false) {
		ThwModel::session(empty($_REQUEST['_sid'])
						? false
						: $_REQUEST['_sid']);
		if (empty($_SESSION['user'])) {
			if (isset($_SESSION['signal']) && $_SESSION['signal'] == 'throw') {
				throw new \Exception(T::out([
					'session_expired_or_lost' => [
						'en' => 'Session expired or lost!',
						'ru' => 'Сессия просрочена или утрачена!'
					]
				]));
			} else {
				if (!empty($throwerror)) {
					throw new \Exception(T::out([
						'session_expired_or_lost' => [
							'en' => 'Session expired or lost!',
							'ru' => 'Сессия просрочена или утрачена!'
						]
					]));
				} else {
					M::e([
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
		}
		return $_SESSION['user'];
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array(
				'changed',
				'length',
				'max' => 20),
			array(
				'net, uid, token, first_name, last_name, link, email, name, gender, phone, photo, city, country',
				'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array(
				'id, net, uid, token, first_name, last_name, link, email, name, gender, phone, photo, city, country, changed',
				'safe',
				'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
				);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id'			 => 'ID',
			'net'			 => 'Net',
			'uid'			 => 'Uid',
			'token'			 => 'Token',
			'first_name'	 => 'First Name',
			'last_name'		 => 'Last Name',
			'link'			 => 'Link',
			'email'			 => 'Email',
			'name'			 => 'Name',
			'gender'		 => 'Gender',
			'phone'			 => 'Phone',
			'photo'			 => 'Photo',
			'city'			 => 'City',
			'country'		 => 'Country',
			'changed'		 => 'Changed',
			'activity'		 => 'Activity',
			'unsubscribed'	 => 'If user unsubscribed'
		);
	}

	public static function filter($add = []) {
		return array_merge([
			'net'				 => 1,
			'uid'				 => 1,
			'first_name'		 => 0,
			'last_name'			 => 0,
			'link'				 => 0,
			'email'				 => 0,
			'name'				 => 0,
			'gender'			 => 0,
			'phone'				 => 0,
			'photo'				 => 0,
			'locale'			 => 0,
			'city'				 => 0,
			'country'			 => 0,
			'invitable_friends'	 => 0,
			'invited_friends'	 => 0,
			'activity'			 => 0,
			'unsubscribed'		 => 0,
			'karma'				 => 0,
			'first_entrance'	 => 0,
			'registered'		 => 0,
			'lat'				 => 0,
			'lng'				 => 0,
			'when'				 => 0,
			'ip'				 => 0
				], $add);
	}

	public static function table() {
		return self::getTable();
	}

	public static function getTable() {
		return 'thw_user';
	}

	public function get($what) {

		if ($what === 'invitable_friends' || $what === 'invited_friends') {
			return json_decode($this->$what, true); //filter input friends by possible xss attacks
		}

		if ($what === 'normalized_friends') {
			return $this->normilizedFriends();
		}

		if ($what === 'full_name') {
			return self::hsc($this->first_name . ($this->last_name
									? ' ' . $this->last_name
									: ''));
		}

		if ($what === 'donations') {

			return \Yii::$app->db->createCommand("
						SELECT count(*) as `count`
						FROM `thw_thank`
						WHERE `receiver_uid` = :uid
						AND `used` is null
					 ", [':uid' => $this->get('uid')])->queryScalar();
		}

		return parent::get($what);
	}

	public function d($what) {
		return $this->get($what) * 1;
	}

	public function add($input) {
		return parent::add($input)->enc([
					'invited_friends'	 => 'json',
					'invitable_friends'	 => 'json'
		]);
	}

	/**
	 * packet request to facebook
	 * 
	 * 
	 * 
	 * @param type $params
	 */
	public static function batchRequest($params) {

		$session = new FacebookSession($_SESSION['facebook_token']);
//
//        try {
//            $result = [];
//
//            foreach ($params as $key => $value) {
//                $temp_request = new FacebookRequest($session, $value['method'], $value['relative_url']);
//                $temp_response = $temp_request->execute();
//                $temp_objects = $temp_response->getGraphObject()->asArray();
//                $temp_result = $temp_objects;
//                while ($temp_response = $temp_response->getRequestForNextPage()) {
//                    $temp_response = $temp_response->execute();
//                    $temp_objects = $temp_response->getGraphObject()->asArray();
//                    $temp_result['data'] = array_merge($temp_result['data'], $temp_objects['data']);
//                }
//
//                $result[$key] = $temp_result;
//            }
//
//            return $result;
//        } catch (FacebookRequestException $ex) {
//            echo $ex->getMessage();
//        } catch (\Exception $ex) {
//            echo $ex->getMessage();
//        }


        $objects = (new FacebookRequest($session, 'POST', '?batch=' . json_encode(array_values($params))))->execute()->getGraphObject()->asArray();

		$response = [];
		$keys = array_keys($params);
		$count = 0;
		foreach ($objects as $object) {
			$response[$keys[$count]] = json_decode($object->body, true);
			$count += 1;
		}

		return $response;
	}

	public static function getProfile($uid, $response = false) {

		$session = new FacebookSession($_SESSION['facebook_token']);

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

		return $user_profile->asArray();
	}

	public static function getFriends($response = false) {


		$result = [
			'invitable_friends'	 => [],
			'invited_friends'	 => []
		];

		//batch request
		/*
		  $params = [
		  [
		  'method'		 => 'GET',
		  'relative_url'	 => '/me/invitable_friends?fields=picture.type(large),id,name'
		  ],
		  [
		  'method'		 => 'GET',
		  'relative_url'	 => '/me/invitable_friends?fields=picture.type(square),id,name'
		  ],
		  [
		  'method'		 => 'GET',
		  'relative_url'	 => '/me/friends'
		  ]
		  ];

		  $objects = (new FacebookRequest($session, 'POST', '?batch=' . json_encode($params)))->execute()->getGraphObject()->asArray();

		  $response = [];

		  foreach ($objects as $object) {
		  $response[] = json_decode($object->body, true);
		  }

		 */
		if (empty($response)) {

			$response = self::batchRequest([
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
						]
			]);
		}

		$invitable_friends = $response['invitable_friends'];
		$inv0 = $response['inv0'];
		$invited_friends = $response['invited_friends'];


		//of batch request		

		/*
		  $invitable_friends = (new FacebookRequest(
		  $session, 'GET', '/me/invitable_friends?fields=picture.type(large),id,name'
		  ))->execute()->getGraphObject();
		 */

		//print_r($invitable_friends);

		if (!empty($invitable_friends)) {

			/*
			  $inv0 = (new FacebookRequest(
			  $session, 'GET', '/me/invitable_friends?fields=picture.type(square),id,name'
			  ))->execute()->getGraphObject()->asArray();
			 */

			$inv_friends_square = json_decode(json_encode(empty($inv0['data'])
									? []
									: $inv0['data']), true);


			//$inv = $invitable_friends->asArray();
			$invitable_large_picture = json_decode(json_encode(empty($invitable_friends['data'])
									? []
									: $invitable_friends['data']), true);

			//print_r($inv_friends_square);

			foreach ($invitable_large_picture as $key => $val) {
				$result['invitable_friends'][$key] = $val;
				$result['invitable_friends'][$key]['picture']['data']['url_small'] = $inv_friends_square[$key]['picture']['data']['url'];
			}
		}


		//and now get invited_friends

		/*
		  $invited_friends = (new FacebookRequest(
		  $session, 'GET', '/me/friends'
		  ))->execute()->getGraphObject();
		 */

		if (!empty($invited_friends)) {
			$inv = $invited_friends;
			$result['invited_friends'] = json_decode(json_encode(empty($inv['data'])
									? []
									: $inv['data']), true);
		}

		return $result;
	}

	public static function getShortName($longname) {
		$a = explode(' ', $longname);
		return $a[0] . (empty($a[2])
						? (empty($a[1])
								? ''
								: (' ' . $a[1]))
						: (' ' . $a[2]));
	}

	public static function logout() {
		foreach ($_SESSION as $key => $val) {
			unset($_SESSION[$key]);
		}
	}

	/**
	 * Retrieves a normilized array for insert to email template
	 */
	public function normilizedFriends($limit = 3) {

		$normalized = [];

		//here we try to return a boy/a girl or a company
		//invitable friends
		$invitable = $this->get('invitable_friends');

		//print_r($invitable);

		if (!empty($invitable)) {
			foreach ($invitable as $friend) {
				$normalized[] = [
					'name'			 => self::hsc($friend['name']),
					'picture'		 => $friend['picture']['data']['url'],
					'small_picture'	 => isset($friend['picture']['data']['small_picture'])
							? $friend['picture']['data']['small_picture']
							: $friend['picture']['data']['url'],
					'type'			 => 'invitable'
				];
			}
		}

		//invited friends
		$invited = $this->get('invited_friends');
		if (!empty($invited)) {
			foreach ($invited as $friend) {
				$normalized[] = [
					'name'		 => self::hsc($friend['name']),
					'picture'	 => 'https://graph.facebook.com/' . $friend['id'] . '/picture?width=80&height=80',
					'type'		 => 'invited'
				];
			}
		}

		if (count($normalized) < 3 && isset($normalized[0])) {
			$add = 3 - count($normalized);
			for ($i = 0; $i < $add; $i++) {
				$normalized[] = $normalized[0];
			}
		}

		if (empty($normalized)) {
			return false;
		}

		shuffle($normalized);

		$normalized = array_slice($normalized, 0, 3);

		//add text
		$popular = ThwPopular::getAll();

		$count = 0;
		foreach ($popular as $record) {
			if (isset($normalized[$count])) {
				$normalized[$count]['text'] = $record['value'];
			}
			$count += 1;
		}

		$friends = [];
		foreach ($normalized as $record) {
			$friend = $record;
			$friend['link'] = Yii::$app->params['app_source_path'] . '/?run_action=thank&name=' . $record['name'] /* . '&text=' . $record['text'] */;
			$friends[] = $friend;
		}

		//get random place

		$db = Yii::$app->db->createCommand("
				SELECT	`place` as `name`, 
						`receiver_uid`,
						`receiver_net`
				FROM `thw_thank`
				WHERE `status` = 'place'
				ORDER BY RAND()
				LIMIT 1
			    ")->query();

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				$record = self::hsc($row);
			}

			$what_to_replace = mt_rand(0, 2);

			if (strpos($record['receiver_net'], 'link_') !== false) {

				$button = Buttons::getBy([
							'www' => $record['name']
				]);

				if (!empty($button)) {
					if ($button->title) {
						$record['title'] = $button->title;
					}
					if ($button->logo) {
						$record['logo'] = $button->logo;
					}
				}
			}

			$image = !empty($record['logo'])
					? B::baseURL() . $record['logo']
					: (
					strpos($record['receiver_net'], 'link_') !== false
							? B::baseURL() . 'images/question.png'
							: '//graph.facebook.com/' . $record['receiver_uid'] . '/picture/?width=80&height=80'
					);

			$friends[$what_to_replace] = [
				'name'		 => $record['name'],
				'title'		 => empty($record['title'])
						? false
						: $record['title'],
				'picture'	 => $image,
				'type'		 => 'place',
				'place'		 => $record['receiver_uid'],
				'uid'		 => $record['receiver_uid'],
				'net'		 => $record['receiver_net'],
				'link'		 => B::baseURL() . '?run_action=thank&name=' . urlencode(json_encode([
					'place'			 => $record['receiver_uid'],
					'receiver_uid'	 => $record['receiver_uid'],
					'receiver_net'	 => $record['receiver_net'],
					'image'			 => $image,
					'title'			 => $record['name'],
					'post_title'	 => (empty($record['title'])
							? $record['name']
							: $record['title']) . ' ' .
					(strpos($record['receiver_net'], 'link_') !== false
							? $record['name']
							: 'https://facebook.com/' . $record['receiver_uid']) . ''
				]))
			];
		}

		return $friends;
	}

    public static function getCoordinates($user_id, $defaultMoscow = true) {
        $sql = "
            SELECT lat, lng FROM thw_user
            WHERE id = :user_id AND lat IS NOT NULL AND lng IS NOT NULL
        ";
        $result = Yii::$app->db->createCommand($sql, [':user_id' => $user_id])->queryOne();
        if (!$result && $defaultMoscow) {
            return ['lat' => 55.751244, 'lng' => 37.618423];
        }

        if ($result) {
            $result['lat'] = floatval($result['lat']);
            $result['lng'] = floatval($result['lng']);
        }

        return $result;
    }

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search() {
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id, true);
		$criteria->compare('net', $this->net, true);
		$criteria->compare('uid', $this->uid, true);
		$criteria->compare('token', $this->token, true);
		$criteria->compare('first_name', $this->first_name, true);
		$criteria->compare('last_name', $this->last_name, true);
		$criteria->compare('link', $this->link, true);
		$criteria->compare('email', $this->email, true);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('gender', $this->gender, true);
		$criteria->compare('phone', $this->phone, true);
		$criteria->compare('photo', $this->photo, true);
		$criteria->compare('city', $this->city, true);
		$criteria->compare('country', $this->country, true);
		$criteria->compare('changed', $this->changed, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ThwUser the static model class
	 */
//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}
}
