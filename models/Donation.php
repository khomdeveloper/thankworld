<?php

namespace app\models;

use app\components\vh2015\M;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Donation
 *
 * @author valera261104
 */
class Donation extends M{

    public static function tableName() {
        return '{{%donation}}';
    }
	
	public static function f() {
		return [
			'title'		 => 'Donations',
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'user_id'	 => "bigint unsigned comment 'Link to ThwUser'",
				'name' => "tinytext comment 'User Name'",
				'email' => "tinytext comment 'User Email'",
				'amount' => "int default 0 comment 'Donated THK'",
				'project' => "text comment 'Project description'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}
	
}
