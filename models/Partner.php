<?php

namespace app\models;

use app\components\vh2015\M;

/**
 * Description of Partner
 *
 * Partner who can see organisation statistics
 * 
 * @author valera261104
 */
class Partner extends M {

    public static function tableName() {
        return '{{%partner}}';
    }

	public static function f(){
		return [
			'title' => 'Partners',
			'_blank' => false,
			'create' => [
				'user_id' => "bigint unsigned comment 'User facebook id'",
				'page_id' => "bigint unsigned comment 'Organisation facebook id'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}
	
}
