<?php

namespace app\models;

use \Exception;

use Yii;

use app\components\vh2015\M;
use app\components\vh2015\B;


/**
 * list of admins whoc can see statistics
 */
class Admins extends M {

    public static function tableName() {
        return '{{%admins}}';
    }

	public static function action($r) {

		$com = $r['Admins'];

		if ($com === 'get') {

			if (empty($_REQUEST['return']) || $_REQUEST['return'] != 'redirect') {
				$user = self::getBy([
							'user_id' => ThwUser::getCurrent()['id']
				]);

				if (empty($user)) {
					throw new Exception('Current user has no rules to view statistics!');
				}
			}

			$date = Logs::getLastDate(); //last date in statistics
			//1) Число новых пользователей по дням
			$db = Yii::$app->db->createCommand("
				SELECT `registered`, count(`id`) as `count`
				FROM `thw_user`
				WHERE `registered` is not null
				AND `registered` BETWEEN :date AND now()
				GROUP BY `registered`
				ORDER BY `registered`
			", [
                ':date' => $date
            ])->query();

			$new = [];
			if (!empty($db)) {
				while (($row = $db->read()) != false) {
					$new[$row['registered']] = $row;
				}
			}

			//2) Число активных пользователей по дням //wrong
			$db = Yii::$app->db->createCommand("
				SELECT DATE(`changed`) as `date`, count(`id`) as `count`
				FROM `thw_userlog`
				WHERE `changed` is not null
				AND `changed` > 0
				AND `changed` BETWEEN :date AND now()
				GROUP BY `date` 
				ORDER BY `date`
			", [
                ':date' => $date
            ])->query();

			$active = [];
			if (!empty($db)) {
				while (($row = $db->read()) != false) {
					$active[$row['date']] = $row;
				}
			}

			//3) Количество отправленных email по всем типам оповещения			 
			$db = Yii::$app->db->createCommand("
				SELECT `type`, DATE(`changed`) as `date`, count(*) as `count`
				FROM `thw_maillog`
				WHERE `changed` is not null
				AND `changed` > 0
				AND `changed` BETWEEN :date AND now()
				GROUP BY concat(`date`,`type`)
				ORDER BY `date`
			", [
                ':date' => $date
            ])->query();

			$emails = [];
			if (!empty($db)) {
				while (($row = $db->read()) != false) {
					if (empty($emails[$row['date']])) {
						$emails[$row['date']] = [];
					}
					$emails[$row['date']][$row['type']] = $row['count'];
				}
			}

			//4) Количество отвеченных email по типу
			$db = Yii::$app->db->createCommand("
				SELECT `type`, DATE(`changed`) as `date`, count(*) as `count`
				FROM `thw_maillog`
				WHERE `changed` is not null
				AND `changed` > 0
				AND `code` = 'responded'
				AND `changed` BETWEEN :date AND now()
				GROUP BY concat(`date`,`type`)
				ORDER BY `date`
			", [
                ':date' => $date
            ])->query();

			$responded = [];
			if (!empty($db)) {
				while (($row = $db->read()) != false) {
					if (empty($responded[$row['date']])) {
						$responded[$row['date']] = [];
					}
					$responded[$row['date']][$row['type']] = $row['count'];
				}
			}

			$add = [];
			foreach ($new as $key => $val) {
				if (empty($add[$key])) {
					$add[$key] = [
						'new_users'		 => 0,
						'active_user'	 => 0,
						'email'			 => ''
					];
				}
				$add[$key]['new_users'] = $val['count'] * 1;
			}

			foreach ($active as $key => $val) {
				if (empty($add[$key])) {
					$add[$key] = [
						'new_users'		 => 0,
						'active_user'	 => 0,
						'email'			 => ''
					];
				}
				$add[$key]['active_user'] = $val['count'];
			}

			$totalEmails = [];
			foreach ($emails as $key => $val) {
				if (empty($totalEmails[$key])) {
					$totalEmails[$key] = [];
				}
				foreach ($val as $k => $v) {
					$totalEmails[$key][] = $k . ' ' . $v . ' (' . (empty($responded[$key][$k])
									? 0
									: $responded[$key][$k]) . ' clicked)';
				}

				$add[$key]['email'] = join("\n", $totalEmails[$key]);
			}

			foreach ($add as $key => $val) {
				Logs::getBy([
					'date'		 => $key,
					'_notfound'	 => [
						'date' => $key
					]
				])->set($val);
			}

			if (!empty($_REQUEST['return']) && $_REQUEST['return'] == 'redirect') {
				header('Location: ' . B::baseURL() . '?admin=1349');
			}

			M::ok([
				'new'		 => $new,
				'active'	 => $active,
				'emails'	 => $emails,
				'responded'	 => $responded
			]);
		}

		throw new Exception('Uncknown command in Admins::action');
	}

	public static function f() {
		return [
			'title'		 => 'Admins in system',
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'user_id' => "bigint unsigned comment 'Link to ThwUser'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
