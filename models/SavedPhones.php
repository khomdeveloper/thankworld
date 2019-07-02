<?php
namespace app\models;

use app\components\vh2015\M;
/**
 * Description of SavedPhones
 * @author valera261104
 */
class SavedPhones extends M{

    public static function tableName() {
        return "{{%savedphones}}";
    }
	
	public static function action($r) {
		$com = $r['SavedPhones'];

		if ($com === 'list') { //create new link
			return [
				'phones' => self::getBy([
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
			'title'		 => 'Saved phones',
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
				'phone'			 => "tinytext comment 'Phone number'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

	
}
