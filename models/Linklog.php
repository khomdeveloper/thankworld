<?php

namespace app\models;

/**
 * Log thanklink using
 * 
 * !!! функционал дублирован в Userthanklink
 *
 * @author valera261104
 */
class Linklog extends M{

    public static function tableName() {
        return '{{%linklog}}';
    }
	
	public static function statistics($input){
		//TODO:
	}
	
	public static function record($input) {

		self::required([
			'uid'	 => 1,
			'net'	 => 1,
			'link' => 1
				], $input);

		return self::getBy([
					'id'		 => 'new',
					'_notfound'	 => [
						'uid'		 => $input['uid'],
						'net'		 => $input['net'],
						'link_id'	 => $input['link'] instanceof Thanklink
								? $input['link']->get('id')
								: Thanklink::getBy([
									'id'		 => $input['link'],
									'_notfound'	 => true
								])->get('id')
					]
		]);
	}
	
	
	public static function f() {
		return [
			'title'		 => 'Button conversion statistics',
			'blank'		 => [
				'uid'		 => 0,
				'net'		 => 0,
			],
			'datatype'	 => [
				'link_id' => [
					'Thanklink' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'uid'		 => "tinytext comment 'User id in network'",
				'net'		 => "tinytext comment 'User network'",
				'link_id'	 => "bigint unsigned default null comment 'Link to button'"
			]
		];
	}
	
	
//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}
	
}
