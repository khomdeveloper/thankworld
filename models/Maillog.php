<?php

namespace app\models;

use app\components\vh2015\M;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Maillog
 *
 * @author valera261104
 */
class Maillog extends M {

    public static function tableName() {
        return '{{%maillog}}';
    }

	public static function f() {
		return [
			'title'		 => 'Sent emails',
			'datatype'	 => [
				'receiver_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'receiver_id' => "bigint unsigned comment 'Link to ThwUser'",
				'type' => "tinytext comment 'Type of notification'",
				'code' => "tinytext comment 'Response code (null if responsed)'"
			]
		];
	}

	public static function checkResponse($r){
		
		if (isset($r['response_code'])){
			$log = self::getBy([
				'code' => $r['response_code']
			]);
			if (!empty($log)){
				$log->set([
					'code' => 'responded'
				]);
			}
		}
		
		if (!empty($r['thank'])){
			$log = self::getBy([
				'code' => '%%' . $r['thank']
			]);
			if (!empty($log)){
				$log->set([
					'code' => ''
				]);
			}
		}
		
		if (!empty($r['thankyou'])){
			$log = self::getBy([
				'code' => '%%' . $r['thankyou']
			]);
			if (!empty($log)){
				$log->set([
					'code' => ''
				]);
			}
		}
		
	}
	
//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
