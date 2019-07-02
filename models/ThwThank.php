<?php

namespace app\models;

use app\components\vh2015\B;
use app\components\vh2015\H;
use app\components\vh2015\T; 
use app\components\vh2015\S;
use app\components\vh2015\Mandrillmail;
use Yii;

/**
 * This is the model class for table "{{thank}}".
 *
 * The followings are the available columns in table '{{thank}}':
 * @property string $id
 * @property string $sender_net
 * @property string $sender_uid
 * @property string $receiver_uid
 * @property string $receiver_net
 * @property string $ref
 * @property string $message
 * @property string $title
 * @property string $status
 * @property string $changed
 * @property string $place
 */
class ThwThank extends ThwModel {

	public $sender_name;
	public $receiver_name;

	public static function filter($add = []) {
		return array_merge([
			'sender_net'	 => 1,
			'sender_uid'	 => 1,
			'receiver_uid'	 => 1,
			'receiver_net'	 => 1,
			'ref'			 => 0,
			'title'			 => 0,
			'message'		 => 0,
			'status'		 => 0,
			'read'			 => 0,
			'place'			 => 0,
			'used'			 => 0,
			'sender_name'	 => -1,
			'receiver_name'	 => -1,
			'favourite'		 => 0,
			'lat'			 => 0,
			'lng'			 => 0,
			'when'			 => 0
				], $add);
	}

	public static function getTable() {
		return 'thw_thank';
	}

	/**
	 * spend thanks_to donation
	 * @param type $amount
	 */
	public static function spend($user, $amount = 100) {

		Donation::create([
			'user_id'	 => $user->get('id'),
			'name'		 => $user->get('name'),
			'email'		 => $user->get('email'),
			'amount'	 => $amount,
			'project'	 => 'Charity donation <a href="http://TakieDela.ru">TakieDela.ru</a>'
		]);


		$db = Yii::$app->db->createCommand("
				UPDATE `thw_thank`
				SET `used` = 1
				WHERE `receiver_uid` = :uid
				AND `used` is null
				LIMIT " . $amount . "
			   ", [':uid' => $user->get('uid')])->query();

		return $user->get('donations');
	}

	/**
	 * 
	 * send email to receiver
	 */
	public function email() {

		if ($this->get('status') != 'place' && $this->get('receiver_net') == 'fb') {
			$receiver = ThwUser::getBy([
						'uid'	 => $this->get('receiver_uid'),
						'net'	 => 'fb'
			]);

			if (!empty($receiver) && $receiver->get('email')) {

				if ($this->get('place')) {
					$place = $this->get('place');
				} else {
					$sender = ThwUser::getBy([
								'uid'	 => $this->get('sender_uid'),
								'net'	 => $this->get('receiver_net')
					]);
				}


				$karma = ThwThank::getKarma($receiver, 'dontmail');

				$html = H::getTemplate('letter_3', [
							'sender'				 => empty($place)
									? $sender->get('name')
									: $place,
							'karma'					 => $karma['karma'],
							'average_friends_karma'	 => $karma['average_friends_karma'],
							'thank_back'			 => empty($place)
									? H::getTemplate('letter_4', [
										'link'	 => '<a style="color:rgb(41,169,225);" href="' . Thanklink::getLink('sms', '') . '">https://topkarma.com</a>',
										'back'	 => B::setProtocol('https:', B::baseURL() . 'thank/' . ($sender->d('id') + 23457890))
											], 'parse')
									: H::getTemplate('letter_5', [
											], 'parse')
								], 'parse');

//die($html);

				Mandrillmail::send([
					'to'		 => $receiver->get('email'),
					'html'		 => $html,
					'from_name'	 => 'Top Karma',
					'subject'	 => T::out([
						'thank_1978' => [
							'en'		 => 'You get thank from {{sender_name}}',
							'ru'		 => 'Вы получили спасибо от {{sender_name}}',
							'_include'	 => [
								'sender_name' => empty($place)
										? $sender->get('name')
										: $place
							]
						]
					])
				]);
			}
		}


		return $this;
	}

	public function add($input) {

		if ($input['status'] === '') { //check if user is already in system
//this part is necessary to resend request to user which are in application but not in system
			$receiver = ThwUser::getBy([
						'net'		 => $input['receiver_net'],
						'uid'		 => $input['receiver_uid'],
						'_notfound'	 => false
			]);

			if (empty($receiver) && !empty($input['name'])) {
				$input['status'] = $input['name'];
			}
		}

		if ($input['status'] == 'place') {
			ThwThank::getKarmaOfPlace($input['receiver_uid'], $input['receiver_net']);
		}

//print_r($input);

		return parent::add($input);
	}

	public static function getBy($what = false) {

		$obj = parent::getBy($what);

		if (empty($obj)) {
			return false;
		};

		if (is_array($obj)) {
			$return = [];
			foreach ($obj as $key => $val) {
				$return[] = $val->addNames();
			}
			return $return;
		} else {
			return $obj->addNames();
		}
	}

	/**
	 * get history data for graph
	 * 
	 * 	$input = [
	 * 	    'period' => week, month, year,
	 * 	    'direction' => 'sender/receiver'
	 * ] 

	 */
	public function getGraph($input) {

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

//DATE_ADD(mydate, INTERVAL(1-DAYOFWEEK(mydate)) +1 DAY)

		$db = Yii::$app->db->createCommand("
				SELECT " . ($input['period'] !== 'year'
								? "DATE_FORMAT(`changed`,'%Y-%m-%d')"
								: "DATE_FORMAT(DATE_ADD(`changed`, INTERVAL(1-DAYOFWEEK(`changed`)) +1 DAY),'%Y-%m-%d')") . " as `date` , count(*) as `count`
				FROM `thw_thank`
				WHERE `" . ($input['direction'] === 'sender'
								? 'sender_uid'
								: 'receiver_uid') . "` = :arg1
				AND `status` != 'message'    
				AND `changed` BETWEEN NOW() - INTERVAL 1 " . (
						$input['period'] === 'month'
								? 'MONTH'
								: ($input['period'] === 'week'
										? 'WEEK'
										: 'YEAR')) . " AND NOW() 
				GROUP BY `date`
				", [
					':arg1' => ThwUser::getCurrent()['uid']
				])->query();

		$thanks = [];
		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				$thanks[$row['date']] = $row['count'];
			}
		}

//get interval from DB -> not from PHP because of possible database and PHP dates difference
//$input['period'] !== 'year'

		$Period = "NOW() - INTERVAL 1 " . ($input['period'] === 'month'
						? 'MONTH'
						: ($input['period'] === 'week'
								? 'WEEK'
								: 'YEAR'));

		$endDate = $input['period'] !== 'year'
				? $Period
				: "DATE_ADD(" . $Period . ", INTERVAL(1-DAYOFWEEK(" . $Period . ")) + 1 DAY)";

		$db = Yii::$app->db->createCommand("
	    SELECT DATE_FORMAT(" . ($input['period'] !== 'year'
								? 'NOW()'
								: 'DATE_ADD(NOW(), INTERVAL(1-DAYOFWEEK(NOW())) +1 DAY)') . ",'%Y-%m-%d') as `end`,
		   DATE_FORMAT(" . $endDate . " ,'%Y-%m-%d') as `begin`
	    ")->query();

		$interval = [];
		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				$interval = $row;
			}
		}

//get full period
		if ($input['period'] === 'year') {
			$dates = ThwModel::getDatesFromRange($interval['begin'], $interval['end'], 7);
		} else {
			$dates = ThwModel::getDatesFromRange($interval['begin'], $interval['end']);
		}

		$return = [];
		foreach ($dates as $key => $val) {
			$return[$val] = isset($thanks[$val])
					? $thanks[$val]
					: 0;
		}

		return $return;
	}

	/*
	  Add sender and receiver names to object
	 */

	public function addNames() {

		if ($this->status == 'place') {
			$this->receiver_name = $this->get('place');
		} else {
			$receiver = ThwUser::getBy([
						'uid'		 => $this->get('receiver_uid'),
						'net'		 => $this->get('receiver_net'),
						'_notfound'	 => false
			]);

			if (!empty($receiver)) {
				$this->receiver_name = $receiver->first_name . ' ' . $receiver->last_name;
			} else {
				$this->receiver_name = '';
			}
		}

		$sender = ThwUser::getBy([
					'uid'		 => $this->get('sender_uid'),
					'net'		 => $this->get('sender_net'),
					'_notfound'	 => false
		]);

		if (!empty($sender)) {
			$this->sender_name = $sender->first_name . ' ' . $sender->last_name;
		} else {
			$this->sender_name = '';
		}

		//TODO: get user lat lng when
		return $this->addGeoData();
	}

	public function addGeoData() {
		
		$sender = ThwUser::getBy([
					'uid'		 => $this->get('sender_uid'),
					'net'		 => $this->get('sender_net'),
					'_notfound'	 => false
		]);

		if (empty($sender)) {
			return $this;
		};

		$actuality = S::getBy([
			'key'		 => 'geolocation_actual',
			'_notfound'	 => [
				'key' => 'geolocation_actual',
				'val' => 3600,
				'comment' => 'Time while geocoordinates are actual'
			]
		])->d('val');

		if ($sender->get('lat') && $sender->get('lng') && (time() <= $sender->get('when') * 1 + $actuality)) {

			$this->set([
				'lat'	 => $sender->get('lat'),
				'lng'	 => $sender->get('lng'),
				'when'	 => (new \DateTime())->format('Y-m-d H:i:s')
			]);
		}

		return $this;
	}

	public static function getMessages($r) {

		self::required([
			'id' => 1
				], $r);


		$user = ThwUser::getCurrent();

		$thank = ThwThank::getBy([
					'id'		 => $r['id'],
					'_notfound'	 => 'record not found'
		]);

		if ($thank->ref != 0) { //this is message replace by parent
			$thank = ThwThank::getBy([
						'id'		 => $thank->ref,
						'_notfound'	 => 'record not found'
			]);
		}

//we can read messages only where we are sender or receiver
		if (($thank->receiver_uid == $user['uid'] && $thank->receiver_net == $user['net'] ) ||
				($thank->sender_uid == $user['uid'] && $thank->sender_net == $user['net'])) {
			
		} else {
			ThwModel::e([
				'error'		 => 'we are not in correspondense',
				'message'	 => 'We do not participate in the correspondence'
			]);
		}

		$children = ThwThank::getBy([
					'ref'		 => $thank->id,
					'_return'	 => 'array',
					'_notfound'	 => false
		]);

		if (!empty($children)) {
			$res = [];
			foreach ($children as $key => $val) {
				$res[$val->id] = $val->encode(false);
			}
		}

		$thank = empty($thank)
				? []
				: $thank->encode(false);

		$thank['children'] = $res;

		return $thank;
	}

	public static function getAll($what) {

		$user = empty($what['user'])
				? ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
				])
				: ($what['user'] instanceof ThwThank
						? $what['user']
						: ThwUser::getBy([
							'id' => $what['user']
		]));


		if (!empty($what['_filter']) && ($what['_filter'] === 'new' || $what['_filter'] === 'email')) { //output all new friends posts
			$friends = [];

			if (!empty($user->invited_friends)) {
				foreach (json_decode($user->invited_friends, true) as $key => $val) {
					$friends[] = urlencode($val['id']) * 1;
				}
			}

//WHERE (`status` = '' OR `status` = 'place')

			if (empty($friends)) {
				$list = false;
				$count = 0;
			} else {

				$list = ThwThank::findBySQL("
		 SELECT `" . self::getTable() . "`.*
		 FROM `" . ThwThank::getTable() . "`
		     LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
		 WHERE `status` != 'message'" .
								($what['_filter'] === 'email'
										? "
		    AND (`" . self::getTable() . "`.`changed` BETWEEN NOW() - INTERVAL 7 DAY AND NOW())
		    "
										: "")
								. "AND (`" . ThwUser::getTable() . "`.`uid` is not null OR `" . self::getTable() . "`.`status` != '')
		 AND (
		    `sender_uid` in (" . join(',', $friends) . ")
			OR
		    `receiver_uid` in (" . join(',', $friends) . ") 
			)
		 AND `sender_uid` != " . urlencode($user->get('uid') * 1) . "
		 AND `receiver_uid` != " . urlencode($user->get('uid') * 1) . "
			 AND `receiver_net` != 'email'
			 AND `receiver_net` != 'phone'
		 ORDER BY `changed` DESC
		 LIMIT " . urlencode($what['start']) . ',' . urlencode($what['page'])
						)->all();

				$count = Yii::$app->db->createCommand("
			SELECT count(*) as `count`
			FROM `" . ThwThank::getTable() . "`
			    LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			WHERE (`status` != 'message')
			AND (`" . ThwUser::getTable() . "`.`uid` is not null OR `" . self::getTable() . "`.`status` != '')
			AND (
		    `sender_uid` in (" . join(',', $friends) . ")
			OR
		    `receiver_uid` in (" . join(',', $friends) . ") 
			)
		    AND `sender_uid` != " . urlencode($user->get('uid') * 1) . "
			AND `receiver_net` != 'email'
			AND `receiver_net` != 'phone'
		 AND `receiver_uid` != " . urlencode($user->get('uid') * 1) . "	
		")->queryScalar();

				$count = min(200, $count);
			}

			if (empty($list)) {
				return false;
			}
		} elseif (is_array($what['_filter'])) { //output partners
			$fltr = current($what['_filter']);

			$button = Buttons::getBy([
						'www'	 => $fltr['uid'],
						'uid'	 => $user->get('uid'),
						'net'	 => $user->get('net')
			]);

			if (empty($button)) { //try to get admin
				//echo $user->get('uid') . ', ' . $fltr['uid'];
				$button = Partner::getBy([
							'user_id'	 => $user->get('uid'),
							'page_id'	 => $fltr['uid']
				]);
			}

			if (!empty($button)) {

				$list = ThwThank::findBySQL("
				SELECT `" . self::getTable() . "`.*
			    FROM `" . self::getTable() . "`
			    WHERE `receiver_net` = :receiver_net
				AND `receiver_uid` = :receiver_uid
			    ORDER BY `changed` DESC 
				LIMIT " . urlencode($what['start']) . "," . urlencode($what['page'])
								, [
							'receiver_net'	 => $fltr['net'],
							'receiver_uid'	 => $fltr['uid']
						])->all();

				$count = Yii::$app->db->createCommand("
				SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
			    WHERE `receiver_net` = :receiver_net
				AND `receiver_uid` = :receiver_uid 
				", [
							'receiver_net'	 => $fltr['net'],
							'receiver_uid'	 => $fltr['uid']
						])->queryScalar();
			} else {
				$count = 0;
				$list = false;
			}
		} elseif (empty($what['_filter']) || $what['_filter'] === 'all') { //messages, thanks, invites 
			$list = ThwThank::findBySQL("
            		    SELECT `" . self::getTable() . "`.*
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR `" . self::getTable() . "`.`status` != '') 
			    AND (
				(`sender_net` = :sender_net AND `sender_uid` = :sender_uid) OR 
				(`receiver_net` = :receiver_net AND `receiver_uid` = :receiver_uid)
			    ) AND (`status` != 'message')
				AND `receiver_net` != 'email' 
				AND `receiver_net` != 'phone'
			    ORDER BY `changed` DESC LIMIT " . urlencode($what['start']) . "," . urlencode($what['page'])
							, [
						'sender_net'	 => $user->get('net'),
						'receiver_net'	 => $user->get('net'),
						'sender_uid'	 => $user->get('uid'),
						'receiver_uid'	 => $user->get('uid')
					])->all();

			$count = Yii::$app->db->createCommand("
            		    SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR 
				   `" . self::getTable() . "`.`status` != '') 
			    AND (
				(`sender_net` = :sender_net AND `sender_uid` = :sender_uid) OR 
				(`receiver_net` = :receiver_net AND `receiver_uid` = :receiver_uid)
			    ) AND (`status` != 'message')
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
	    ", [
						'sender_net'	 => $user->get('net'),
						'receiver_net'	 => $user->get('net'),
						'sender_uid'	 => $user->get('uid'),
						'receiver_uid'	 => $user->get('uid')
					])->queryScalar();
		} elseif (!empty($what['_filter']) && $what['_filter'] === 'i') {

			$list = ThwThank::findBySQL("
            		    SELECT `" . self::getTable() . "`.*
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR `" . self::getTable() . "`.`status` = 'place') 
			    AND `sender_net` = :sender_net AND `sender_uid` = :sender_uid
			    AND (`status` = 'place' OR `status` = '')
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
			    ORDER BY `changed` DESC LIMIT " . urlencode($what['start']) . "," . urlencode($what['page'])
							, [
						'sender_net' => $user->get('net'),
						'sender_uid' => $user->get('uid')
					])->all();

			$count = Yii::$app->db->createCommand("
            		    SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR `" . self::getTable() . "`.`status` = 'place') 
			    AND `sender_net` = :sender_net 
			    AND `sender_uid` = :sender_uid
			    AND (`status` = 'place' OR `status` = '')
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
	    ", [
						'sender_net' => $user->get('net'),
						'sender_uid' => $user->get('uid')
					])->queryScalar();
		} elseif (!empty($what['_filter']) && $what['_filter'] === 'me') { //History output here
			$list = ThwThank::findBySQL("
            		    SELECT `" . self::getTable() . "`.*
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
				WHERE ( `" . ThwUser::getTable() . "`.`uid` is not null OR 
				    `" . self::getTable() . "`.`status` = 'place') 		
			    AND `receiver_net` = :receiver_net 
			    AND `receiver_uid` = :receiver_uid
				" . (!empty($what['_favourite'])
									? ' AND `favourite` = 1 '
									: '') . "	
			    AND (`" . self::getTable() . "`.`status` = 'place' OR `" . self::getTable() . "`.`status` = '')
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'	
			    ORDER BY `changed` DESC LIMIT " . urlencode($what['start']) . "," . urlencode($what['page'])
							, [
						'receiver_net'	 => $user->get('net'),
						'receiver_uid'	 => $user->get('uid')
					])->all();


			$count = Yii::$app->db->createCommand("
            		    SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR 
				   `" . self::getTable() . "`.`status` = 'place') 
			    AND `receiver_net` = :receiver_net 
			    AND `receiver_uid` = :receiver_uid
				" . (!empty($what['_favourite'])
									? ' AND `favourite` = 1 '
									: '') . "	
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
			    AND (`" . self::getTable() . "`.`status` = 'place' OR `" . self::getTable() . "`.`status` = '')
	    ", [
						'receiver_net'	 => $user->get('net'),
						'receiver_uid'	 => $user->get('uid')
					])->queryScalar();
		} elseif (!empty($what['_filter']) && $what['_filter'] === 'messages') {

			$list = ThwThank::findBySQL("
            		    SELECT `" . self::getTable() . "`.*
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null) 
					AND `receiver_net` != 'email'
					AND `receiver_net` != 'phone'
			    AND (
				(`sender_net` = :sender_net AND `sender_uid` = :sender_uid) OR 
				(`receiver_net` = :receiver_net AND `receiver_uid` = :receiver_uid)
			    ) AND (`" . self::getTable() . "`.`status` = 'message')
			    ORDER BY `changed` DESC LIMIT " . urlencode($what['start']) . "," . urlencode($what['page'])
							, [
						'sender_net'	 => $user->get('net'),
						'receiver_net'	 => $user->get('net'),
						'sender_uid'	 => $user->get('uid'),
						'receiver_uid'	 => $user->get('uid')
					])->all();

			$count = Yii::$app->db->createCommand("
            		    SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null) 
			    AND (
				(`sender_net` = :sender_net AND `sender_uid` = :sender_uid) OR 
				(`receiver_net` = :receiver_net AND `receiver_uid` = :receiver_uid)
			    ) AND (`" . self::getTable() . "`.`status` = 'message')
	    ", [
						'sender_net'	 => $user->get('net'),
						'receiver_net'	 => $user->get('net'),
						'sender_uid'	 => $user->get('uid'),
						'receiver_uid'	 => $user->get('uid')
					])->queryScalar();
		}

//get statistics
		$my = Yii::$app->db->createCommand("
            		    SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR 
				   `" . self::getTable() . "`.`status` = 'place') 
			    AND `receiver_net` = :receiver_net 
			    AND `receiver_uid` = :receiver_uid
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
			    AND (`status` = 'place' OR `status` = '')
	    ", [
					'receiver_net'	 => $user->get('net'),
					'receiver_uid'	 => $user->get('uid')
				])->queryScalar();

		$i = Yii::$app->db->createCommand("
            		    SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`status` != 'message')
			    AND `sender_net` = :sender_net 
			    AND `sender_uid` = :sender_uid
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
			    AND (`status` != 'message')
	    ", [
					'sender_net' => $user->get('net'),
					'sender_uid' => $user->get('uid')
				])->queryScalar();

//WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR `" . self::getTable() . "`.`status` = 'place') 

		$t = Yii::$app->db->createCommand("
            		    SELECT count(*) as `count`
			    FROM `" . self::getTable() . "`
				LEFT JOIN `" . ThwUser::getTable() . "` 
				ON (
				    `" . self::getTable() . "`.`receiver_uid` = `" . ThwUser::getTable() . "`.`uid` 
				    AND 
				    `" . self::getTable() . "`.`receiver_net` = `" . ThwUser::getTable() . "`.`net` 
				)
			    WHERE (`" . ThwUser::getTable() . "`.`uid` is not null OR 
				   `" . self::getTable() . "`.`status` != '') 
			    AND (
				(`sender_net` = :sender_net AND `sender_uid` = :sender_uid) OR 
				(`receiver_net` = :receiver_net AND `receiver_uid` = :receiver_uid)
			    )
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
	    ", [
					'sender_net'	 => $user->get('net'),
					'receiver_net'	 => $user->get('net'),
					'sender_uid'	 => $user->get('uid'),
					'receiver_uid'	 => $user->get('uid')
				])->queryScalar();

//get affairs
//TODO: return all ids which we are sender_id or receiver_id and status != 'place' 

		$connection = Yii::$app->db;
		$db = $connection->createCommand("
				SELECT distinct `receiver_uid` 
				FROM `thw_thank`
				WHERE `thw_thank`.`status` != 'place'
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
				AND `sender_uid` = :sender_uid
				", [
					':sender_uid' => $user->get('uid')
				])->query();
		$return = [];
		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				$return[] = $row['receiver_uid'];
			}
		}

		$db = $connection->createCommand("
				SELECT distinct `sender_uid` 
				FROM `thw_thank`
				WHERE `thw_thank`.`status` != 'place'
				AND `receiver_net` != 'email'
				AND `receiver_net` != 'phone'
				AND `receiver_uid` = :receiver_uid
				", [
					':receiver_uid' => urlencode($user->get('uid'))
				])->query();

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				if (!in_array($row['sender_uid'], $return)) {
					$return[] = $row['sender_uid'];
				}
			}
		}

		$list['affairs'] = $return;
		$list['my'] = $my;
		$list['i'] = $i;
		$list['t'] = $t;
		$list['total'] = $count;
		$list['filter'] = empty($what['_filter'])
				? 0
				: $what['_filter'];

		if (empty($list)) {
			return false;
		} else {
			return $list;
		}
	}

	/**
	 * get Average friends karma value
	 */
	public static function friendsAverage($r = false) {

		$user = empty($r['user'])
				? ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
				])
				: $r['user'];

//MY FRIENDS RECEIVED
		$result = Yii::$app->db->createCommand("
			SELECT AVG(`count`) as `avg` FROM (
			SELECT `receiver_uid`, count(`id`) as `count`
				FROM `thw_thank` 
				WHERE `receiver_uid` in (
					SELECT `thw_names`.`uid` 
					FROM `thw_names`
                    JOIN `thw_user` ON `thw_user`.`uid` = `thw_names`.`uid`
					WHERE `friend` = :uid
				)
				AND `receiver_uid` != :uid
				GROUP BY `receiver_uid`
			) as `a`", [
					':uid' => $user->get('uid')
				])->query();

		if (empty($result)) {
			return 0;
		}

		while (($row = $result->read()) != false) {
			return $row['avg'];
		}
	}

	/**
	 * @param type $r
	 * @return type
	 */
	public static function formatAll($r) {
		$user = empty($r['user'])
				? ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
				])
				: $r['user'];

		B::trace();

		$sent = ThwThank::getAll([
					'user'		 => $user->get('id'),
					'start'		 => isset($r['start'])
							? $r['start']
							: 0,
					'page'		 => isset($r['page'])
							? $r['page']
							: 1000,
					'_filter'	 => empty($r['get']) || (!in_array($r['get'], ['me',
						'all',
						'i',
						'new',
						'messages',
						'email']) && !is_array($r['get']))
							? false
							: $r['get'],
					'_favourite' => empty($r['only_favourite'])
							? 0
							: 1
		]);

		B::trace('getAll');

		if (empty($sent)) {
			return [];
		} else {

			$return = [];
			foreach ($sent as $key => $val) {
				if ($key !== 'total' && $key !== 'my' && $key !== 'i' && $key !== 't' && $key !== 'affairs' && $key !== 'filter') {
					$record = $val->addNames()->encode(false);

					if (strpos($record['receiver_uid'], 'link') !== false) {
						$button = Buttons::getBy([
									'www' => $record['place']
						]);
						if (!empty($button)) {
							if ($button->get('title')) {
								$record['replace_title'] = $button->get('title');
							}
							if ($button->get('logo')) {
								$record['logo'] = $button->get('logo');
							}
						}
					}
					$return[$key] = $record;
				} else {
					if (in_array($key, ['affairs',
								'filter'])) {
						$return[$key] = $sent[$key];
					} else {
						$return[$key] = $sent[$key] * 1;
					}
				}
			}

			B::trace('affairs');

//here we will get pressed buttons

			$return['pressed'] = Pressed::getBy([
						'user_id'	 => $user->get('id'),
						'_return'	 => ['selector' => 'array']
			]);


			B::trace('pressed');

//here we will get favourite

			if ($r['get'] == 'me') {
				$return['favourite'] = Favourite::getBy([
							'user_id'	 => $user->get('id'),
							'_return'	 => ['thank_id' => 'array']
				]);
			}

			B::trace('favourite');

			$return['time'] = B::trace();

			return $return;
		}
	}

	public static function getStat($what) {
		try {

			if (empty($what['_filter']) || $what['_filter'] === 'all') {

				$connection = Yii::$app->db;
				$result = $connection->createCommand("
				SELECT `id`, `name`, `receiver_uid`, `receiver_net` as `net`, `receiver_net`, count(*) as `count` 
				FROM `thw_thank`
				JOIN `thw_user` ON `thw_thank`.`receiver_uid` = `thw_user`.`uid`
				WHERE `thw_thank`.`status` != 'message'
				AND `thw_thank`.`status` != 'place'
			    	GROUP BY `receiver_uid` 
			    	ORDER BY `count` DESC 
			    	LIMIT " . (!empty($what['_ignorelimit'])
										? urlencode($what['_ignorelimit']) * 1
										: 10) . "
			 ")->query();
				$return = [];
				while (($row = $result->read()) != false) {
					$return[] = self::hsc($row);
				}

				return $return;
			}

			if (is_array($what['_filter'])) { //expected friends list [123123, 234234, 345435]

				/* if (key($what['_filter']) == 'partner') {
				  print_r($what['_filter']);
				  throw new Exception('stop');
				  } */

				$connection = Yii::$app->db;

				$start = empty($what['start'])
						? 0
						: urlencode($what['start'] * 1);
				$page = empty($what['page'])
						? 3
						: urlencode($what['page'] * 1);


				$result = $connection->createCommand("
				SELECT	`id`, 
						`name`, 
						`uid` as `receiver_uid`, 
						`karma` as `count`,
						`net` as `receiver_net`,
						`uid`,
						`net`
				FROM `" . ThwUser::getTable() . "`
				WHERE `thw_user`.`uid` in (" . join(',', self::ue($what['_filter'])) . ") 
				ORDER BY `karma` DESC
			    	LIMIT " . $start . "," . $page
						)->query();

				$count = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `" . ThwUser::getTable() . "`
				WHERE `thw_user`.`uid` in (" . join(',', self::ue($what['_filter'])) . ") 
		            ", [])->queryScalar();

				$return = [
					'data'	 => [],
					'count'	 => $count
				];

				while (($row = $result->read()) != false) {
					$row2 = $row;
					$row2['count'] = round($row['count'] / 1000, 1);

					$user = ThwUser::getBy([
								'uid'		 => $row2['receiver_uid'],
								'_notfound'	 => false
					]);

					$row2['raise'] = empty($user)
							? 0
							: Karmalog::checkRaise($user->get('id'));
					$return['data'][] = self::hsc($row2);
				}

				return $return;
			}

			if ($what['_filter'] == 'countries') {

				$connection = Yii::$app->db;

				try {
					$result = $connection->createCommand("
				SELECT `country_" . urlencode(T::getLocale()) . "` as `country`, `code`, count(*) as `count` 
				FROM `thw_thank`
				JOIN `thw_user` ON `thw_thank`.`sender_uid` = `thw_user`.`uid`
				JOIN `thw_countries` ON `thw_user`.`country` = `thw_countries`.`code`
				AND `thw_thank`.`status` = ''
			    	GROUP BY `thw_countries`.`code` 
			    	ORDER BY `count` DESC 
			    	LIMIT " . (!empty($what['_ignorelimit'])
											? urlencode($what['_ignorelimit']) * 1
											: 5) . "
			 ")->query();
				} catch (\Exception $e) {
					ThwModel::e([
						'error'		 => 'database error',
						'message'	 => $e->getMessage()
					]);
				}

				$return = [];
				$except = [];
				while (($row = $result->read()) != false) {
					$return[] = self::hsc($row);
					$except[] = urlencode($row['code']);
				}

//print_r($except);
//get other countries

				if (!empty($except) && is_array($except)) {
					try {
						$other = Yii::$app->db->createCommand("
						SELECT count(*) as `count`
						FROM `thw_thank`
						JOIN `thw_user` ON `thw_thank`.`sender_uid` = `thw_user`.`uid`
						JOIN `thw_countries` ON `thw_user`.`country` = `thw_countries`.`code`
						WHERE `country` not in ('" . join("','", self::ue($except)) . "')
						AND `thw_thank`.`status` = ''
					 ")->queryScalar();

						if (!empty($other)) {
							$return[] = [
								'code'		 => false,
								'country'	 => T::out([
									'other_title' => [
										'en' => 'Other',
										'ru' => 'Остальные'
							]]),
								'count'		 => $other
							];
						}
					} catch (\Exception $e) {
						ThwModel::e([
							'error'		 => 'database error',
							'message'	 => $e->getMessage()
						]);
					}
				}

				return $return;
			}

			if ($what['_filter'] === 'other') {

				$start = empty($what['start'])
						? 0
						: urlencode($what['start'] * 1);
				$page = empty($what['page'])
						? 3
						: urlencode($what['page'] * 1);

				$connection = Yii::$app->db;

				$count = Yii::$app->db->createCommand("
			SELECT 
			    count(*) as `count`
			FROM
			(
			    SELECT `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count90` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 90 DAY AND NOW() 
			    GROUP BY `receiver_uid` 
			) as `a`
			LEFT JOIN
			(
			    SELECT `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count10` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 10 DAY AND NOW() 
			    GROUP BY `receiver_uid`
			) as `b`
USING(`receiver_uid`)
			LEFT JOIN
			(
			    SELECT `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count24` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 24 HOUR AND NOW() 
			    GROUP BY `receiver_uid`
			) as `c`
			USING(`receiver_uid`)
			LEFT JOIN
			(
			    SELECT `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count_forwhat` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `title` != ''
			    GROUP BY `receiver_uid`
			) as `d`
			USING(`receiver_uid`)
		    ", [])->queryScalar();


//ROUND(IFNULL(`count10`,0)*20/ `count30`*1,1) as `count`
				$result = $connection->createCommand("
			SELECT 
				`a`.`id` as `id`,
			    `a`.`place` as `name`,
			    `a`.`receiver_uid`, 
				`a`.`net`,
				`a`.`net` as `receiver_net`,
			    ROUND((IFNULL(`count90`,0)*0.5 + IFNULL(`count10`,0)*1.5 + IFNULL(`count24`,0)*3)*(1+IFNULL(`count_forwhat`,0)*0.1/(1+IFNULL(`count90`,0))),1) as `count`
			FROM
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count90` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 90 DAY AND NOW() 
			    GROUP BY `receiver_uid` 
			) as `a`
			LEFT JOIN
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count10` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 10 DAY AND NOW() 
			    GROUP BY `receiver_uid`
			) as `b`
USING(`receiver_uid`)
			LEFT JOIN
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count24` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 24 HOUR AND NOW() 
			    GROUP BY `receiver_uid`
			) as `c`
			USING(`receiver_uid`)
			LEFT JOIN
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count_forwhat` 
			    FROM `thw_thank` 
			    WHERE `status` = 'place'
			    AND `title` != '' 
			    GROUP BY `receiver_uid`
			) as `d`
			USING(`receiver_uid`)
			ORDER BY `count` DESC LIMIT
		     " . $start . "," . $page)->query([]);

				$return = [
					'data'	 => [],
					'count'	 => min(30, $count) //limit on 30 records for output in statistics
				];

//print_r($record);
//die('stop');

				while (($row = $result->read()) != false) {

					$record = self::hsc($row);

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

					$record['raise'] = Karmalogplaces::checkRaise($record['receiver_uid'], $record['receiver_net']);

					$return['data'][] = self::hsc($record);
				}

				return $return;
			}

			return [];
		} catch (\Exception $e) {
			ThwModel::e([
				'error'		 => 'kernel error',
				'message'	 => $e->getMessage()
			]);
		}
	}

	public static function getKarmaOfPlace($receiver_uid = false, $receiver_net = false) {

//throw new Exception('stop');
//пересчитывать карму лучше всего на отправку фенка

		if (empty($receiver_uid) && empty($receiver_net)) {
//TODO: what to return
		} else {

			$result = Yii::$app->db->createCommand("
			SELECT  
			    ROUND((IFNULL(`count90`,0)*0.5 + IFNULL(`count10`,0)*1.5 + IFNULL(`count24`,0)*3)*(1+IFNULL(`count_forwhat`,0)*0.1/(1+IFNULL(`count90`,0))),1) as `count`
			FROM
			(
			    SELECT `receiver_uid`, `receiver_net`, `place`, count(*) as `count90` 
			    FROM `thw_thank` 
			    WHERE `receiver_uid` = :place
				AND   `receiver_net` = :net
			    AND `changed` BETWEEN NOW() - INTERVAL 90 DAY AND NOW() 
			) as `a`
			LEFT JOIN
			(
			    SELECT `receiver_uid`, `receiver_net`, `place`, count(*) as `count10` 
			    FROM `thw_thank` 
			    WHERE `receiver_uid` = :place
				AND   `receiver_net` = :net
			    AND `changed` BETWEEN NOW() - INTERVAL 10 DAY AND NOW() 
			) as `b`
			USING(`receiver_uid`,`receiver_net`)
			LEFT JOIN
			(
			    SELECT `receiver_uid`, `receiver_net`, `place`, count(*) as `count24` 
			    FROM `thw_thank` 
			    WHERE `receiver_uid` = :place
				AND   `receiver_net` = :net
			    AND `changed` BETWEEN NOW() - INTERVAL 24 HOUR AND NOW() 
			) as `c`
			USING(`receiver_uid`, `receiver_net`)
			LEFT JOIN
			(
			    SELECT `receiver_uid`, `receiver_net`, `place`, count(*) as `count_forwhat` 
			    FROM `thw_thank` 
			    WHERE `receiver_uid` = :place
				AND   `receiver_net` = :net
			    AND `title` != '' 
			) as `d`
			USING(`receiver_uid`, `receiver_net`)
			", [
						':place' => $receiver_uid,
						':net'	 => $receiver_net
					])->query();

			while (($row = $result->read()) != false) {
				$karma = $row['count'];
				break;
			};

			$today = (new \DateTime())->format('Y-m-d');


			return round(Karmalogplaces::getBy([
						'date'			 => $today,
						'receiver_uid'	 => $receiver_uid,
						'receiver_net'	 => $receiver_net,
						'_notfound'		 => [
							'receiver_uid'	 => $receiver_uid,
							'receiver_net'	 => $receiver_net,
							'date'			 => $today
						]
					])->set([
						'karma' => round($karma * 1000, 0)
					])->get('karma') / 1000, 2);
		}
	}

    public static function getKarmaOfPlaces($receiver_uids = []) {
        if (!empty($receiver_uids)) {
            $connection = Yii::$app->db;
            $receiver_uids = implode(',', $receiver_uids);
            $result = $connection->createCommand("
			SELECT
				`a`.`id` as `id`,
			    `a`.`place` as `name`,
			    `a`.`receiver_uid`,
				`a`.`net`,
				`a`.`net` as `receiver_net`,
			    ROUND((IFNULL(`count90`,0)*0.5 + IFNULL(`count10`,0)*1.5 + IFNULL(`count24`,0)*3)*(1+IFNULL(`count_forwhat`,0)*0.1/(1+IFNULL(`count90`,0))),1) as `count`
			FROM
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count90`
			    FROM `thw_thank`
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 90 DAY AND NOW()
			    GROUP BY `receiver_uid`
			) as `a`
			LEFT JOIN
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count10`
			    FROM `thw_thank`
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 10 DAY AND NOW()
			    GROUP BY `receiver_uid`
			) as `b` USING(`receiver_uid`)
			LEFT JOIN
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count24`
			    FROM `thw_thank`
			    WHERE `status` = 'place'
			    AND `changed` BETWEEN NOW() - INTERVAL 24 HOUR AND NOW()
			    GROUP BY `receiver_uid`
			) as `c` USING(`receiver_uid`)
			LEFT JOIN
			(
			    SELECT `id`, `receiver_uid`, `receiver_net` as `net`, `place`, count(*) as `count_forwhat`
			    FROM `thw_thank`
			    WHERE `status` = 'place'
			    AND `title` != ''
			    GROUP BY `receiver_uid`
			) as `d` USING(`receiver_uid`)
			WHERE `receiver_uid` IN ($receiver_uids)
			AND `a`.`net` = 'fb'
            ORDER BY `count` DESC
		     ")->queryAll();

           return $result;
        }
    }

    public static function getThanksOfPlaces($receiver_uids = []) {
        if (!empty($receiver_uids)) {
            $connection = Yii::$app->db;
            $receiver_uids = implode(',', $receiver_uids);
            $result = $connection->createCommand("
			SELECT receiver_uid, place, count(*) as thank_count
			FROM thw_thank
			WHERE receiver_uid IN ($receiver_uids)
			AND receiver_net = 'fb'
			GROUP BY receiver_uid
            ORDER BY thank_count DESC
		     ")->queryAll();

            return $result;
        }
    }

	public static function getKarma($user = false, $notemail = false) {

		if (empty($user)) {
			$user = ThwUser::getBy([
						'id' => ThwUser::getCurrent()['id']
			]);
		}

		$t = microtime(true);

//ME THANKED LAST 10 DAYS Yii::$app->db->createCommand
		$sent10 = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `sender_uid` = :uid
				AND `changed` BETWEEN NOW() - INTERVAL 10 DAY AND NOW()
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();
		;

		$sent90 = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `sender_uid` = :uid
				AND `changed` BETWEEN NOW() - INTERVAL 90 DAY AND NOW()
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();

		$sent24 = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `sender_uid` = :uid
				AND `changed` BETWEEN NOW() - INTERVAL 24 HOUR AND NOW()
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();

//I RECEIVE LAST 30 DAYS
		$received10 = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `receiver_uid` = :uid
				AND `changed` BETWEEN NOW() - INTERVAL 10 DAY AND NOW()
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();


		$received90 = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `receiver_uid` = :uid
				AND `changed` BETWEEN NOW() - INTERVAL 90 DAY AND NOW()
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();


		$received24 = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `receiver_uid` = :uid
				AND `changed` BETWEEN NOW() - INTERVAL 24 HOUR AND NOW()
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();


//MY FRIENDS SEND
		$friends_sent = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `sender_uid` in (
				SELECT `uid` 
				FROM `thw_names`
				WHERE `friend` = :uid
				)
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();


//MY FRIENDS RECEIVED
		$friends_receive = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `receiver_uid` in (
				SELECT `uid` 
				FROM `thw_names`
				WHERE `friend` = :uid
				)
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();

		$for_what = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `receiver_uid` = :uid
				AND `title` != ''
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();

		$invested90 = Yii::$app->db->createCommand("
				SELECT count(*) as `count` 
				FROM `thw_thank` 
				WHERE `receiver_uid` = :uid
				AND `changed` BETWEEN NOW() - INTERVAL 90 DAY AND NOW()
				AND `used` is not null
		            ", [
					'uid' => $user->get('uid')
				])->queryScalar();

		$carma = $friends_sent == 0
				? 0
				: ($received90 * 1 + $received10 * 1.5 + $received24 * 2 + $sent90 * 2 + $sent10 * 2 + $sent24 * 3 +
				$invested90 * 1
				) * (1 + $for_what * 2 / (1 + $received90)) * $friends_receive / $friends_sent;

//echo $user->get('name') . ':' . $carma;

		if (empty($_SESSION['cashKarma']) || $_SESSION['cashKarma'] != $carma) {

			$_SESSION['cashKarma'] = $carma;

			$user->set([
				'karma' => $carma * 1000
			]);
		}

//GET AVERAGE FRIENDS KARMA
//TODO calculate friends karma automatic if it is null in dtb -> remove user -> id based -> in dtb by default set null

		$result = Yii::$app->db->createCommand("
				SELECT `thw_names`.`uid` as `uid`, `karma`
				FROM `thw_names`
				JOIN `thw_user` ON `thw_user`.`uid` = `thw_names`.`uid`
				WHERE `friend` = :uid
			", [':uid' => $user->get('uid')])->query();

		$count = 0;
		$sum = 0;
		while (($row = $result->read()) != false) {
			$count += 1;
			$sum += $row['karma'] / 1000;
		}

//TODO: автоматический расчет кармы в ретроспективе если данных нет

		$self_karma = round($carma * 1000, 0);
		$friends_karma = $count == 0
				? 0
				: round($sum / $count * 1000, 0);

		if (empty($_SESSION['karmalog'])) {
			B::trace('empty');
		}

		if (empty($_SESSION['karmalog']) ||
				$_SESSION['karmalog']['self_karma'] != $self_karma ||
				$_SESSION['karmalog']['friends_karma'] != $friends_karma) {

			$_SESSION['karmalog'] = [
				'self_karma'	 => $self_karma,
				'friends_karma'	 => $friends_karma
			];


			$today = (new \DateTime())->format('Y-m-d');

			$karmalog = Karmalog::getBy([
						'date'		 => $today,
						'user_id'	 => $user->get('id'),
						'_notfound'	 => [
							'user_id'	 => $user->get('id'),
							'date'		 => $today
						]
			]);

			if (empty($notemail) || 1 == 0) { //!!! replaced
				$karmalog = $karmalog->emailAndSet([
					'self_karma'	 => $self_karma,
					'friends_karma'	 => $friends_karma
				]);
			} else {
				$karmalog = $karmalog->set([
					'self_karma'	 => $self_karma,
					'friends_karma'	 => $friends_karma
				]);
			}
		}

		return [
			'worktime'				 => microtime(true) - $t,
			'karma'					 => round($carma, $carma > 100
							? 0
							: 1),
			'sent'					 => 0,
			'received'				 => 0,
			'friends_receive'		 => $friends_receive,
			'friends_sent'			 => $friends_sent,
			'average_friends_karma'	 => $count == 0
					? 0
					: round($sum / $count, 1),
			'count'					 => $count
		];
	}

	public static function serchBy($what) {

		try {

			$db = Yii::$app->db->createCommand("
		SELECT distinct `title` 
		FROM `thw_thank`
		WHERE `title` LIKE :title
		AND `title` is not null
		AND `title` != ''
		LIMIT 4
	    ", [
						':title' => '%' . addcslashes($what, '%_') . '%'
					])->query();

			$return = [];
			if (!empty($db)) {
				while (($row = $db->read()) != false) {
					$return[] = [
						'label'	 => self::hsc($row['title']),
						'value'	 => self::hsc($row['title'])
					];
				}
			}

			return $return;
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

//calculate 
	public static function getCount($place) {
		return Yii::$app->db->createCommand("
						SELECT count(*) as `count`
						FROM `thw_thank`
						WHERE `place` = ?
					 ", [
					$place
				])->queryScalar();
	}

	/**
	 * @return string the associated database table name
	 */
	public static function tableName() {
		return '{{%thank}}';
	}

	public static function getKarmaImage($k) {
		if ($k < 1)
			return '1.png';
		if ($k < 5)
			return '5.png';
		if ($k < 10)
			return '10.png';
		if ($k < 20)
			return '20.png';
		if ($k < 40)
			return '40.png';
		if ($k < 70)
			return '70.png';
		if ($k < 110)
			return '100.png';

		return '110.png';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
// NOTE: you should only define rules for those attributes that
// will receive user inputs.
		return array(
			array(
				'sender_net, sender_uid, receiver_uid, receiver_net, ref, message, title, status, changed',
				'required'),
			array(
				'ref',
				'length',
				'max' => 20),
			array(
				'message',
				'length',
				'max' => 1024),
			// The following rule is used by search().
// @todo Please remove those attributes that should not be searched.
			array(
				'id, sender_net, sender_uid, receiver_uid, receiver_net, ref, message, title, status, changed',
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
		return array();
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id'			 => 'ID',
			'sender_net'	 => 'Sender Net',
			'sender_uid'	 => 'Sender Uid',
			'receiver_uid'	 => 'Receiver uid',
			'receiver_net'	 => 'Receiver net',
			'ref'			 => 'Answer on',
			'message'		 => 'Message',
			'title'			 => 'Thanks title',
			'status'		 => 'invited, read',
			'changed'		 => 'Changed',
			'receiver_name'	 => '',
			'sender_name'	 => ''
		);
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
		$criteria->compare('sender_net', $this->sender_net, true);
		$criteria->compare('sender_uid', $this->sender_uid, true);
		$criteria->compare('receiver_uid', $this->receiver_uid, true);
		$criteria->compare('receiver_net', $this->receiver_net, true);
		$criteria->compare('ref', $this->ref, true);
		$criteria->compare('message', $this->message, true);
		$criteria->compare('title', $this->title, true);
		$criteria->compare('status', $this->status, true);
		$criteria->compare('changed', $this->changed, true);

		$criteria->compare('receiver_name', $this->receiver_name, true);
		$criteria->compare('sender_name', $this->sender_name, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ThwThank the static model class
	 */
//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}
}
