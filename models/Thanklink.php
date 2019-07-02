<?php

namespace app\models;

use app\components\vh2015\B;
use app\components\vh2015\M;

/**
 * Обратная реферралка
 */
class Thanklink extends M {

	public static function tableName() {
		return '{{%thanklink}}';
	}

	public static function action($r) {

		$com = $r['Thanklink'];

		if ($com === 'create') { //create new link
			return [
				'code' => self::getBy([
					'id'		 => 'new',
					'_notfound'	 => self::blank()
				])
			];
		} else {
			return [];
		}
	}

	public static function getLink($usage = false, $for = false, $user = null) {

		//print_r($for);		
		
		if (empty($user)) {
			$user = ThwUser::getBy([
						'id' => ThwUser::getCurrent()['id']
			]);
		}
		

		if (!empty($usage)) { //always generate new code
			$code = Thanklink::getBy([
						'id'		 => 'new',
						'_notfound'	 => Thanklink::blank()
					])->set([
						'code'	 => Thanklink::getUnique('code'),
						'usage'	 => $usage,
						'for'	 => !empty($for)
								? $for
								: false
					])->get('code');
			
			return B::setProtocol('https:', B::baseURL() . 'thankyou' . $code);
		}
		
		$present = Thanklink::getBy([
					'user_id'	 => $user->get('id'),
					'_return'	 => ['id' => 'object']
		]);

		$free = false;
		foreach ($present as $id => $thanklink) {

			if (Userthanklink::getBy([
						'thanklink'	 => $thanklink->get('id'),
						'_return'	 => 'count'
					]) == 0) {
				$free = $thanklink;
				break;
			}
		}

		$code = empty($free)
				? Thanklink::getBy([
					'id'		 => 'new',
					'_notfound'	 => Thanklink::blank()
				])->set([
					'code' => Thanklink::getUnique('code')
				])->get('code')
				: $free->get('code');


		return B::setProtocol('https:', B::baseURL() . 'thankyou' . $code);
	}

	public static function f() {
		return [
			'title'		 => 'Thank link',
			'blank'		 => [
				'user_id'	 => ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
				])->get('id'),
				'code'		 => false
			],
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => false
					]
				]
			],
			'create'	 => [
				'user_id'	 => "bigint unsigned not null comment 'Link to sender'",
				'code'		 => "tinytext comment 'Activation code'",
				'usage'		 => "tinytext comment 'Presentation flag'",
				'for'		 => "tinytext comment 'For what'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}
}
