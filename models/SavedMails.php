<?php

namespace app\models;

use app\components\vh2015\M;

/**
 * Description of SavedPhones
 * @author valera261104
 */
class SavedMails extends M {

    public static function tableName() {
        return '{{%savedmails}}';
    }
	
	public static function action($r) {

		$com = $r['SavedMails'];

		if ($com === 'list') { //create new link
			return [
				'emails' => self::getBy([
					'user_id' => ThwUser::getBy([
						'id' => ThwUser::getCurrent()['id']
					])->get('id'),
					'_return' => ['id' => 'array']
				])
			];
		} else {
			return [];
		}
	}
	
	
	public static function f() {
		return [
			'title'		 => 'Saved emails',
			'blank'		 => false,
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'user_id'		 => "bigint unsigned comment 'Link to ThwUser'",
				'email'			 => "tinytext comment 'Saved email'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

	
}
