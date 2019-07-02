<?php

namespace app\models;

use app\components\vh2015\M;

/**
  Log user activity
 */
class Userlog extends M {

	public static function f() {
		return [
			'title'		 => 'User activity',
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'date'		 => "DATE default null comment 'Date of record'",
				'user_id'	 => "bigint unsigned comment 'Link to ThwUser'",
				'type'		 => "tinytext comment 'Type of activity'"
			]
		];
	}

    public static function table() {
        return self::getTable();
    }

    public static function getTable() {
        return 'thw_userlog';
    }

    public static function tableName() {
        return '{{%userlog}}';
    }

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
