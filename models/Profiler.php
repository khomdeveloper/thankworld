<?php

namespace app\models;

use app\components\vh2015\M;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Profiler
 *
 * @author valera261104
 */
class Profiler extends M {

	protected $time;

    public static function tableName() {
        return '{{%profiler}}';
    }

	public function start() {
		$this->time = microtime(true);
	}

	public function record($mark) {
		self::getBy([
			'mark'		 => $mark,
			'_notfound'	 => [
				'mark'		 => $mark,
				'elapced'	 => microtime(true) - $this->time
			]
		]);
		self::start();
	}

	public static function f() {
		return [
			'title'	 => 'Profiler',
			'create' => [
				'mark'			 => "tinytext comment 'Social newtwork'",
				'elapced_time'	 => "float default null comment 'Elapced time in microseconds'"
			]
		];
	}

}
