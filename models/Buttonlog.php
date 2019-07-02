<?php
namespace app\models;

use app\components\vh2015\M;
use Yii;
/**
 * Запись переходов по кнопке
 */
class Buttonlog extends M {

    public static function tableName() {
        return '{{%buttonlog}}';
    }

	public static function statistics($input) {

		if (empty($input['fields'])) {

			$input['fields'] = [
				'button_id'	 => 1,
				'id'		 => 1,
				'changed'	 => 1,
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

			/*

			  Array
			  (
			  [0] =&gt; Array
			  (
			  [uid] =&gt; 1472935179646029
			  [net] =&gt; fb
			  [button_id] =&gt; 26
			  [files] =&gt;
			  [referal] =&gt; Array
			  (
			  [net] =&gt; fb
			  [uid] =&gt; 1472935179646029
			  [first_name] =&gt; Sharon
			  [last_name] =&gt; user
			  [link] =&gt; https://www.facebook.com/app_scoped_user_id/1472935179646029/
			  [email] =&gt; sharon_ilmckvs_user@tfbnw.net
			  [name] =&gt; Sharon main user
			  [gender] =&gt; female
			  [phone] =&gt;
			  [photo] =&gt; //graph.facebook.com/1472935179646029/picture?type=large
			  [locale] =&gt; ru_RU
			  [city] =&gt;
			  [country] =&gt; RU
			  [invitable_friends] =&gt; [{"picture":{"data":{"is_silhouette":true,"url":"https:\/\/fbcdn-profile-a.akamaihd.net\/hprofile-ak-xfa1\/v\/t1.0-1\/s200x200\/1379841_10150004552801901_469209496895221757_n.jpg?oh=68f1d81e5adea117350ee9ec0c0242c0&amp;oe=556431F8&amp;__gda__=1431462769_5ed6cbc67da2fa89862ccf10b4dfd21a"}},"id":"AVmyjr7eJ_5j8UsbPHPpAYWc61-ondjutMZRjSMVZ709WxzDghoTKaTp5TP-boMCnF0bWcxGpYAQBnqKUUEq-t6upm9Oj6LrdHgWwITUxGQ8BA","name":"Ruth Amdejbgggghh Occhinosen"},{"picture":{"data":{"is_silhouette":false,"url":"https:\/\/fbcdn-profile-a.akamaihd.net\/hprofile-ak-xpf1\/v\/t1.0-1\/10360459_1520078548235920_2263391478041450212_n.jpg?oh=b8cc3f08defed0be2202cdf8dd25b4f3&amp;oe=554E765D&amp;__gda__=1431778762_1103fa3bb5f895ec9d14deb3864874fc"}},"id":"AVllIBPsLoFeEeFuyp0sqQDBSr1nY-qXOg5laOZ-uwUzFKE8KIjN08OU2Un-hF7RL5-dYn2KlasTnxUIDX8fHDevexVqvjGSvR-OExA1HTAWEg","name":"Margaret Amgjacfhggai Moidusen"}]
			  [invited_friends] =&gt; [{"name":"Charlie Amdcgfddeedd Thurnman","id":"369785659843979"},{"name":"Elizabeth Amdhjifidibb Chengwitz","id":"153152968188359"},{"name":"Tom Amechhafgjce Baosen","id":"276238699232400"},{"name":"Mary Amgedjcifagd Laverdetman","id":"1482263502034982"},{"name":"Mary Amgedejdfiag Dingleberg","id":"1480346488893505"},{"name":"Carol Amgehkhhhgc Sharpeescu","id":"1474437896152199"},{"name":"Dick Amgiaebdhfdd Sidhustein","id":"1475137076093453"},{"name":"Karen Amgibaijeeec Thurnsky","id":"1467554553518594"},{"name":"Dick Amgifjedhcah Smithman","id":"1470423166566354"},{"name":"Tom Amhdjbefccig Liangescu","id":"1387898081500264"}]
			  [activity] =&gt; 1422121486
			  [unsubscribed] =&gt; 0
			  [karma] =&gt; 0
			  [id] =&gt; 15
			  [changed] =&gt; 2015-02-09 18:06:48
			  )

			  )

			 */
		}

		$user = ThwUser::getBy([
					'id' => ThwUser::getCurrent()['id']
		]);

		self::required(['button_id'], $input);

		//TODO: get date, user_id, name, link, email

		if (empty($input['period'])) {
			$input['period'] = -90;
		}

		$logs = self::getBy([
					'button_id'	 => Buttons::getBy([
						'id'		 => $input['button_id'],
						'uid'		 => $user->get('uid'),
						'net'		 => $user->get('net'),
						'_notfound'	 => true
					])->get('id'),
					'changed'	 => [
						'_between' => [
							'DATE_ADD(NOW(), INTERVAL ' . urlencode($input['period']) . ' DAY)',
							'NOW()'
						]
					],
					'_return'	 => ['id' => 'objects'],
					'_limit'	 => 1000
		]);

		$return = [];
		foreach ($logs as $log) {
			$referal = ThwUser::getBy([
						'uid'	 => $log->get('uid'),
						'net'	 => $log->get('net')
			]);

			$record = [];
			foreach ($log->toArray([
				'id'		 => true,
				'changed'	 => true
			]) as $key => $val) {
				if (isset($input['fields'][$key])) {
					$record[$key] = $val;
				}
			}

			/* $record['referal'] = empty($referal)
			  ? false
			  : []; */

			if (!empty($referal)) {
				foreach ($referal->encode(false) as $key => $val) {
					if (isset($input['fields']['referal'][$key])) {
						$record[$key] = $val;
					}
				}
			}

			$return[] = $record;
		}
		return $return;
	}

	public static function record($input) {

		self::required([
			'uid'	 => 1,
			'net'	 => 1,
			'button' => 1
				], $input);

		return self::getBy([
					'id'		 => 'new',
					'_notfound'	 => [
						'uid'		 => $input['uid'],
						'net'		 => $input['net'],
						'button_id'	 => $input['button'] instanceof Buttons
								? $input['button']->get('id')
								: Buttons::getBy([
									'id'		 => $input['button'],
									'_notfound'	 => true
								])->get('id')
					]
		]);
	}

	/**
	 * return how many 1 user click on 1 button today
	 */
	public static function CountUserClicks($uid, $net, $button_id) {

		$db = Yii::$app->db->createCommand("
	     SELECT DATE(`changed`) as `date`, count(*) as `count` 
	    FROM `thw_buttonlog` 
	    WHERE `uid` = :uid
	    AND `net` = :net
	    AND `button_id` = :button_id
	    AND DATE(`changed`) = DATE(CURRENT_DATE)
	    GROUP BY `date`
	", [
            ':net'		 => $net,
            ':uid'		 => $uid,
            ':button_id'	 => $button_id
        ])->query();

		if (empty($db)) {
			return 0;
		}

		while (($row = $db->read()) != false) {
			return $row['count'];
		}
	}

	public static function f() {
		return [
			'title'		 => 'Button conversion statistics',
			'blank'		 => [
				'uid'		 => 0,
				'net'		 => 0,
				'button_id'	 => 0
			],
			'datatype'	 => [
				'button_id' => [
					'Buttons' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'uid'		 => "tinytext comment 'User id in network'",
				'net'		 => "tinytext comment 'User network'",
				'button_id'	 => "bigint unsigned comment 'Link to button'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
