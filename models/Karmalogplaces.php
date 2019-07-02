<?php
namespace app\models;

use app\components\vh2015\M;
use Yii;
use \DateTime;
/**
 * Karma history of places
 */
class Karmalogplaces extends M {

    public static function tableName() {
        return '{{%karmalogplaces}}';
    }

	/**
	 * 
	 * @param type $input = [
	 * 	'period' => 'year', 'month' , 'week'
	 *  'place_id'  -  place id = receiver_uid in thwthank
	 * ]
	 */
	public static function graph($input) {

		$receiver_uid = $input['place_id'];

		$db = Yii::$app->db->createCommand("
			SELECT `date`, `karma`
			FROM `" . self::table() . "`
			WHERE `receiver_uid` = :receiver_uid
			AND `receiver_net` = :receiver_net
			AND `changed` BETWEEN NOW() - INTERVAL 1 " . (
						$input['period'] === 'month'
								? 'MONTH'
								: ($input['period'] === 'week'
										? 'WEEK'
										: 'YEAR')) . " AND NOW() + INTERVAL 1 DAY 
			ORDER BY `date`
		", [
            ':receiver_uid' => $receiver_uid,
            ':receiver_net' => 'fb'
        ])->query();

		$karma_history = $input['period'] == 'year'
				? [
			'receiver' => ['2015-01-01' => 0]
				]
				: [
			'receiver' => []
		];

		//get full period
		if ($input['period'] === 'year') {
			$end = (new DateTime())->format('Y-m-d H:i:s');
			$begin = (new DateTime('today - 1 year'))->format('Y-m-d H:i:s');
			$dates = ThwModel::getDatesFromRange($begin, $end, 7);
		} else {
			$end = (new DateTime())->format('Y-m-d H:i:s');
			$begin = (new DateTime('today - ' . ($input['period'] === 'week'
							? '1 week'
							: '1 month')))->format('Y-m-d H:i:s');
			$dates = ThwModel::getDatesFromRange($begin, $end);
		}

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				if (isset($row['karma'])) {
					$karma_history['receiver'][$row['date']] = round($row['karma'] / 1000, 1);
				}
			}
		}

		//print_r($karma_history);

		$result = [
			'receiver' => [],
			'karma'  => ThwThank::getKarmaOfPlace($input['place_id'], 'fb')
		];

		$current_karma = [
			'receiver' => 0
		];

		if ($input['period'] === 'year') {

			foreach ($dates as $date) {
				$begin = strtotime($date);
				$end = $begin + 604800;
				foreach ($karma_history as $key => $val) {
					$result[$key][$date] = $current_karma[$key];
					foreach ($val as $key_d => $karma_val) {
						if (strtotime($key_d) >= $begin && strtotime($key_d) < $end) {
							$result[$key][$date] = $karma_val;
							$current_karma[$key] = $karma_val;
						}
					}
				}
			}
		} else {
			foreach ($dates as $date) {
				if (isset($karma_history['receiver'][$date])) {
					$result['receiver'][$date] = $karma_history['receiver'][$date];
					$current_karma['receiver'] = $karma_history['receiver'][$date];
				} else {
					$result['receiver'][$date] = $current_karma['receiver'];
				}
			}
		}

		//self::autoFill();  //run it to fill karma data

		return $result;
	}

	/**
	 * return friends and self Karma on date
	 */
	public static function getKarma($receiver_uid, $receiver_net = 'fb', $interval) {

		$result = Yii::$app->db->createCommand("
			SELECT `karma`
			FROM `" . self::table() . "`
			WHERE `receiver_uid` = :receiver_uid
			AND `receiver_net` = :receiver_net
			AND `changed` BETWEEN NOW() - INTERVAL 1 YEAR AND NOW() - INTERVAL " . $interval . " DAY
			ORDER BY `changed` DESC
			LIMIT 1
		", [
            ':receiver_uid' => $receiver_uid,
            ':receiver_net' => $receiver_net
        ])->query();

		if (empty($result)) {
			return [
				'self'		 => 0,
				'friends'	 => 0
			];
		}

		while (($row = $result->read()) != false) {
			return [
				'self'		 => $row['karma'],
				'friends'	 => 0
			];
			break;
		}
	}

	public static function checkRaise($place_id, $place_net) {

		$karma = [
			'0'	 => self::getKarma($place_id, $place_net, 0),
			'1'	 => self::getKarma($place_id, $place_net, 1),
			'2'	 => self::getKarma($place_id, $place_net, 2),
			'3'	 => self::getKarma($place_id, $place_net, 3)
		];

		return $karma['2']['self'] - $karma['3']['self'] + ($karma['1']['self'] - $karma['2']['self']) + ($karma['0']['self'] - $karma['1']['self']);
	}

	//first data calcualtion not used refactor it
	public static function autoFill() {

		throw new \Exception('Need to refactor autoFill fucntion');

		//refactor this	

		$db = Yii::$app->db->createCommand("
	    SELECT distinct `receiver_uid`
	    FROM `thw_thank`
	    WHERE `status` = 'place'
	")->query();

		$places = [];

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				ThwThank::getKarmaOfPlace($row['receiver_uid']);
			}
		}
	}

	public static function f() {
		return [
			'title'	 => 'Karma change log of places',
			'blank'	 => false,
			'create' => [
				'date'			 => "DATE default null comment 'Date of record'",
				'receiver_uid'	 => "bigint comment 'Place id = thank_receiver_uid'",
				'receiver_net'	 => "tinytext comment 'Place id = thank_receiver_net'",
				'karma'			 => "bigint default 0 comment 'Karma value'",
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
