<?php

namespace app\models;

use Yii;
use app\components\vh2015\M;
use app\components\vh2015\B;

/**
 * Total logs
 */
class Logs extends M {

    public static function tableName() {
        return '{{%logs}}';
    }

	public static function f() {
		return [
			'title'	 => 'Common user statistics',
			'help'	 => '<a style="float:right; margin-right:25px; margin-top:-70px;" href="'. b::baseURL() .'?r=user%2Ftotal-statistics&Admins=get&return=redirect" target="_self"><div class="pr admin_button_host_45" style="margin-right:5px;"><div class="lightblue ac pa cp" style="width:100%; height:100%; padding-top:5px; color:white;">UPDATE</div></div></a>' . 'Press UPDATE button to update statistics.',
			'create' => [
				'date' => "date comment 'Date key'",
				'new_users'		 => "bigint default 0 comment 'Amount of new registered users'",
				'active_user'	 => "int default 0 comment 'Amount of active users'",
				'email'			 => "text comment 'Email activity'",
			]
		];
	}

	/**
	 * return last date when statistics calculated
	 */
	public static function getLastDate() {

		//return '1970-01-01 00:00';
		
		$db = Yii::$app->db->createCommand("
				SELECT DATE(`changed`) as `date`
				FROM `thw_logs`
				WHERE `changed` is not null
				AND `changed` > 0
				ORDER BY `date`
				LIMIT 1
			")->query();

		if (empty($db)) {
			return '1970-01-01 00:00';
		}

		//die('stop');
		
		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				return $row['date'] . ' 00:00';
			}
		}
		
		return '1970-01-01 00:00';
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
