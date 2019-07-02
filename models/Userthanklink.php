<?php

namespace app\models;

use app\components\vh2015\B;
use app\components\vh2015\M;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Userthanklink
 *
 * @author valera261104
 */
class Userthanklink extends M {

	/**
	 * 
	 * fields data fields
	 * 
	 * @param type $input ->
	 * 
	 * 'fields' - fields which we need
	 * 
	 *  'link_id'
	 * 
	 */
	public static function statistics($input) {
		if (empty($input['fields'])) {

			$input['fields'] = [
				'button_id'	 => 1,
				'id'		 => 1,
				'changed'	 => 1,
				'thanklink'	 => 1,
				'referal'	 => [
					'name'		 => true,
					'link'		 => true,
					'uid'		 => true,
					'net'		 => true,
					'gender'	 => true,
					'locale'	 => true,
					'city'		 => true,
					'country'	 => true,
					'email'		 => true
				]
			];
		}

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		if (empty($input['period'])) {
			$input['period'] = -90;
		}

		if (empty($input['link'])) {
			$links = Thanklink::getBy([
						'user_id'	 => $user->get('id'),
						'_notfound'	 => false,
						'_return'	 => ['id' => 'array']
			]);
			
			$links_ids = array_keys($links);
		} else {
			$links_ids = $input['link'];
		}
			
		$logs = self::getBy([
					'thanklink'	 => $links_ids,
					'_return'	 => ['id' => 'objects'],
					'_limit'	 => 1000
		]);
	
		
		$return = [];
		foreach ($logs as $log) {
			$referal = ThwUser::getBy([
						'id' => $log->get('user_id')
			]);

			$record = [];
			foreach ($log->toArray([
				'id'		 => true,
				'changed'	 => true,
				'thanklink'	 => true
			]) as $key => $val) {
				if (isset($input['fields'][$key])) {
					$record[$key] = $val;
				}
			}

			if (!empty($referal)) {
				foreach ($referal->encode(false) as $key => $val) {
					if (isset($input['fields']['referal'][$key])) {
						$record[$key] = $val;
					}
				}
				$record['link'] = B::setProtocol('https:', B::baseURL() . 'thankyou' . Thanklink::getBy([
									'id' => $log->get('thanklink')
								])->get('code'));
			}

			$return[] = $record;
		}
		return $return;
	}

	public static function f() {
		return [
			'title'		 => 'Link between user who received Thanklink',
			'required'	 => [
				'user_id'	 => 0,
				'thanklink'	 => 0
			],
			'datatype'	 => [
				'user_id'	 => [
					'ThwUser' => [
						'id' => false
					]
				],
				'thanklink'	 => [
					'Thanklink' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'user_id'	 => "bigint unsigned not null comment 'Link to receiver'",
				'thanklink'	 => "bigint unsigned not null comment 'Link to thanklink'"
			]
		];
	}

    public static function tableName() {
        return '{{%userthanklink}}';
    }

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
