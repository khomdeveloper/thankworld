<?php

namespace app\models;

use app\components\vh2015\M;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Pressed
 *
 * @author valera261104
 */
class Pressed extends M {

    public static function tableName() {
        return '{{%pressed}}';
    }

	public static function action($r) {

		$com = $r['Pressed'];

		if ($com == 'add') {
			
			if (empty($r['selector'])){
				throw new \Exception('Empty selector');
			}
			
			$user = ThwUser::getBy([
				'id' => ThwUser::getCurrent()['id']
			]);
			
			$already_pressed = self::getBy([
				'user_id' => $user->get('id'),
				'selector' => $r['selector'],
				'_notfound' => [
					'user_id' => $user->get('id'),
					'selector' => 'new'
				]
			]);
			
			if ($already_pressed->get('selector') == 'new'){
				$already_pressed->set([
					'selector' => $r['selector']
				]);
				return [
					'pressed' => 0
				];
			} else {
				return [
					'pressed' => 1
				];
			}
			
		}
	}

	public static function f() {
		return [
			'title'		 => 'Profiler',
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'selector'		 => "tinytext comment 'Access class'",
				'user_id'	 => "bigint unsigned comment 'Link to ThwUser'",
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
