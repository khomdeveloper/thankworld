<?php

namespace app\models;

use app\components\vh2015\M;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Favourite
 *
 * @author valera261104
 */
class Favourite extends M {

    public static function tableName() {
        return '{{%favourite}}';
    }

	public static function action($r) {

		$com = $r['Favourite'];

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		if (empty($user)) {
			throw new \Exception('Need to relogin');
		}

		if ($com === 'get') {

			M::ok([
				'favourites' => Favourite::getBy([
					'user_id'	 => $user->get('id'),
					'_return'	 => ['thank_id' => 'array']
				])
			]);
		} elseif ($com === 'set') {

			$favourite = Favourite::getBy([
						'user_id'	 => $user->get('id'),
						'thank_id'	 => $_REQUEST['thank_id'],
						'_notfound'	 => [
							'user_id'	 => $user->get('id'),
							'thank_id'	 => $_REQUEST['thank_id']
						]
			]);

			M::ok([
				'favourite' => $favourite->set([
					'status' => $favourite->d('status') == 0
							? 1
							: 0
				])->toArray()
			]);
		} else {
			throw new \Exception('Uncknown_command');
		}
	}

	public static function f() {
		return [
			'title'		 => 'Favourite thanks',
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'user_id'	 => "bigint unsigned comment 'Link to ThwUser'",
				'thank_id'	 => "bigint unsigned not null comment 'Link to ThwThank'",
				'status'	 => "tinyint default 0 comment 'Status 0 added 1 not added'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
