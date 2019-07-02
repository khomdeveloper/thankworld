<?php

namespace app\models;

use app\components\vh2015\M;

/**
 * Sticker class
 */

class Sticker extends M{

    public static function tableName() {
        return '{{%sticker}}';
    }
    
    public static function f($which = false) {
	$fields = [
	    'required'	 => [
		'title'	 => 0,
		'description'	 => 0
	    ],
	    'create'	 => [
		'title'	 => "tinytext comment 'Title'",
		'description'	 => "text comment 'Description'"
	    ]
	];

	return empty($which)
		? $fields
		: (isset($fields[$which])
			? $fields[$which]
			: $fields);
    }

//    public static function model($className = __CLASS__) {
//	return parent::model($className);
//    }
    
}
