<?php

namespace app\models;

use app\components\vh2015\M;
use app\components\vh2015\H;
use app\components\vh2015\T;
use app\components\vh2015\Mandrillmail;
use Yii;
use \DateTime;
/**
 * Karma history
 */
class Karmalog extends M {

    public static function tableName() {
        return '{{%karmalog}}';
    }

	/**
	 * 
	 * @param type $input = [
	 * 	'period' => 'year', 'month' , 'week'
	 * 
	 *  'user_id'  -  facebook uid
	 * 
	 * ]
	 */
	public static function graph($input) {

		$user_id = empty($input['user_id'])
				? ThwUser::getCurrent()['id']
				: ThwUser::getBy([
					'uid'		 => $input['user_id'],
					'_notfound'	 => true
				])->get('id');

		$db = Yii::$app->db->createCommand("
	    SELECT `date`,`self_karma`,`friends_karma`
				    FROM `" . self::table() . "`
					WHERE `user_id` = :user_id
					AND `changed` BETWEEN NOW() - INTERVAL 1 " . (
						$input['period'] === 'month'
								? 'MONTH'
								: ($input['period'] === 'week'
										? 'WEEK'
										: 'YEAR')) . " AND NOW() + INTERVAL 1 DAY 
					    ORDER BY `date`
	", [
            ':user_id' => $user_id
        ])->query();

		$karma_history = $input['period'] == 'year'
				? [
			'sender'	 => ['2015-01-01' => 0],
			'receiver'	 => ['2015-01-01' => 0]
				]
				: [
			'sender'	 => [],
			'receiver'	 => []
		];

		//get full period
		if ($input['period'] === 'year') {
			$end = (new DateTime())->format('Y-m-d H:i:s');
			$begin = (new DateTime('today - 1 year'))->format('Y-m-d H:i:s');
			$dates = ThwModel::getDatesFromRange($begin, $end, 7);
		} else {
			$end = (new DateTime())->format('Y-m-d H:i:s');
			$begin = (new DateTime('today - ' . ($input['period'] === 'week'
							? '1 week'
							: '1 month')))->format('Y-m-d H:i:s');
			$dates = ThwModel::getDatesFromRange($begin, $end);
		}

		if (!empty($db)) {
			while (($row = $db->read()) != false) {
				if (isset($row['self_karma'])) {
					$karma_history['sender'][$row['date']] = round($row['self_karma'] / 1000, 1);
				}
				if (isset($row['friends_karma'])) {
					$karma_history['receiver'][$row['date']] = round($row['friends_karma'] / 1000, 1);
				}
			}
		}

		$result = [
			'sender'	 => [],
			'receiver'	 => []
		];

		$current_karma = [
			'sender'	 => 0,
			'receiver'	 => 0
		];

		if ($input['period'] === 'year') {

			foreach ($dates as $date) {
				$begin = strtotime($date);
				$end = $begin + 604800;
				foreach ($karma_history as $key => $val) {
					$result[$key][$date] = $current_karma[$key];
					foreach ($val as $key_d => $karma_val) {
						if (strtotime($key_d) >= $begin && strtotime($key_d) < $end) {
							$result[$key][$date] = $karma_val;
							$current_karma[$key] = $karma_val;
						}
					}
				}
			}
		} else {
			foreach ($dates as $date) {
				if (isset($karma_history['receiver'][$date])) {
					$result['receiver'][$date] = $karma_history['receiver'][$date];
					$current_karma['receiver'] = $karma_history['receiver'][$date];
				} else {
					$result['receiver'][$date] = $current_karma['receiver'];
				}
				if (isset($karma_history['sender'][$date])) {
					$result['sender'][$date] = $karma_history['sender'][$date];
					$current_karma['sender'] = $karma_history['sender'][$date];
				} else {
					$result['sender'][$date] = $current_karma['sender'];
				}
			}
		}

		return $result;
	}

	public function emailAndSet($data) {

		$rate = $this->d('self_karma') - $this->d('friends_karma');

		if ($rate < 0) {
			$current_norm = -1;
		} elseif ($rate > 0) {
			$current_norm = 1;
		} else {
			$current_norm = 0;
		}

		$rate = $data['self_karma'] - $data['friends_karma'];

		if ($rate < 0) {
			$new_norm = -1;
		} elseif ($rate > 0) {
			$new_norm = 1;
		} else {
			$new_norm = 0;
		}

		if ($current_norm != $new_norm && $new_norm != 0) { //вариант когда карма сравнялась нас не интересует
			//отправляем email что состояние кармы изменилось
			$user = ThwUser::getBy([
						'id' => $this->get('user_id')
			]);

			if (!empty($user)) {

				if ($new_norm < 0 && $current_norm >= 0) {
					$text = T::out([
								'email_text_wnen_change_karma_less_than_friends' => [
									'en'		 => '<div>Dear {{name}}, your Karma fall below average Karma of your friends.</div>
													<div style="text-align:right;">We wish you a good and pure Karma.</div>',
									'ru'		 => '<div>Дорогой {{name}}, Ваша карма опустилась ниже средней Кармы Ваших друзей.</div>
<div style="text-align:right;">Мы желаем Вам хорошей и светлой Кармы</div>',
									'_include'	 => [
										'name' => $user->get('name')
									]
								]
					]);
				} elseif ($new_norm > 0 && $current_norm < 0) {
					$text = T::out([
								'email_text_wnen_change_karma_more_than_friends' => [
									'en'		 => '<div>Dear {{name}}, we congratulate you with your Karma overgrow average karma of your friends.</div> 
<div style="text-align:right;">We wish you a good and pure Karma</div>',
									'ru'		 => '<div>Дорогой {{name}}, мы поздравляем вас с ростом Вашей кармы выше средней кармы Ваших друзей.</div> 
<div style="text-align:right;">Мы желаем Вам хорошей и светлой Кармы.</div>',
									'_include'	 => [
										'name' => $user->get('name')
									]
								]
					]);
				} else {
					$text = false;
				}

				if (!empty($text)) {

					$friends = $user->get('normalized_friends');

					$activity = ThwThank::formatAll([
								'get'	 => 'email',
								'begin'	 => 0,
								'page'	 => 100,
								'user'	 => $user
					]);

					if ($new_norm < 0 && $current_norm >= 0) { //less
						$html = H::getTemplate('letter_2', [
									'link'	 => 'https://topkarma.com',
									'text1'	 => T::out([
										'dear' => [
											'en' => 'Dear',
											'ru' => 'Уважаемый'
										]
									]) . ' ' . $user->get('full_name') . ', ' . T::out([
										'email_text_wnen_change_karma_less_than_friends' => [
											'en'		 => 'average karma of your friends has exceeded your karma. We wish you a good and pure karma.',
											'ru'		 => 'средняя карма Ваших друзей превысила вашу карму. Желаем Вам хорошей и чистой кармы.',
											'_include'	 => [
												'name' => $user->get('name')
											]
										]
									]),
									'text2'	 => '<p style="margin:0px; margin-bottom:20px;">' . T::out([
										'smth_about_mom2' => [
											'en' => 'Meanwhile, your friends and relatives are waiting for your "Thank you"! May be a long time passed, since you have told "Thank you" to your Mom and Dad last time?',
											'ru' => 'Тем временем, Вас ждут множество Ваших друзей и просто людей, которым Вы могли бы послать благодарность и сказать «Спасибо!»
А возможно, Вы давно не говорили «Спасибо» своим родителям — Маме и Папе.'
										]
									]) . '</p><p style="margin:0px;">' . T::out([
										'just_click_and_be_happy' => [
											'en' => 'We are ready to assist <b>you</b> to say "Thank you". Just click the <b>Thank button</b> below.',
											'ru' => 'Мы всегда готовы помочь <b>Вам</b> сделать это. <b>Для этого</b> Вам просто нужно нажать на кнопку - <b>СКАЗАТЬ СПАСИБО</b>!'
										]
									]) . '</p>'
										], 'parse');
					} else { //higher
						$html = H::getTemplate('letter_2', [
									'link'	 => 'https://topkarma.com',
									'text1'	 => T::out([
										'dear' => [
											'en' => 'Dear',
											'ru' => 'Уважаемый'
										]
									]) . ' ' . $user->get('full_name') . ', ' . T::out([
										'email_text_wnen_change_karma_more_than_friends' => [
											'en'		 => 'we are pleased to congratulate you that your karma exceeded the average karma of your friends. We wish you a good and pure karma.',
											'ru'		 => 'мы рады поздравить вас, Ваша карма превысила среднюю карму Ваших друзей. Желаем Вам хорошей и чистой кармы.',
											'_include'	 => [
												'name' => $user->get('name')
											]
										]
									]),
									'text2'	 => '<p style="margin:0px; margin-bottom:20px;">' . T::out([
										'smth_about_mom2' => [
											'en' => 'Meanwhile, your friends and relatives are waiting for your "Thank you"! May be a long time passed, since you have told "Thank you" to your Mom and Dad last time?',
											'ru' => 'Тем временем, Вас ждут множество Ваших друзей и просто людей, которым Вы могли бы послать благодарность и сказать «Спасибо!»
А возможно, Вы давно не говорили «Спасибо» своим родителям — Маме и Папе.'
										]
									]) . '</p><p style="margin:0px;">' . T::out([
										'just_click_and_be_happy' => [
											'en' => 'We are ready to assist <b>you</b> to say "Thank you". Just click the <b>Thank button</b> below.',
											'ru' => 'Мы всегда готовы помочь <b>Вам</b> сделать это. <b>Для этого</b> Вам просто нужно нажать на кнопку - <b>СКАЗАТЬ СПАСИБО</b>!'
										]
									]) . '</p>'
										], 'parse');
					}

					Mandrillmail::send([
						'to'		 => Yii::$app->params['email_notification'] === true
								? $user->get('email')
								: Yii::$app->params['email_notification'],
						'html'		 => $html,
						'from_name'	 => 'Top Karma',
						'subject'	 => T::out([
							'your_karma_changed' => [
								'en' => 'Your karma has changed with respect to the karma of your friends!',
								'ru' => 'Ваша карма изменилась относительно кармы ваших друзей!'
							]
						])
					]);
				}
			}
		}

		return $this->set($data);
	}

	/**
	 * return friends and self Karma on date
	 */
	public static function getKarma($user_id, $interval) {

		$result = Yii::$app->db->createCommand("
			SELECT `friends_karma`,`self_karma`
			FROM `thw_karmalog`
			WHERE `user_id` = :user_id
			AND `changed` BETWEEN NOW() - INTERVAL 1 YEAR AND NOW() - INTERVAL " . $interval . " DAY
			ORDER BY `changed` DESC
			LIMIT 1
		", [
            ':user_id' => $user_id
        ])->query();

		if (empty($result)) {
			return [
				'self'		 => 0,
				'friends'	 => 0
			];
		}

		while (($row = $result->read()) != false) {
			return [
				'self'		 => $row['self_karma'],
				'friends'	 => $row['friends_karma']
			];
			break;
		}
	}

	/**
	 * 
	 * Проверяем растет карма для выбранного пользователя последние три дня или нет
	 * 
	 * @param type $user_id
	 */
	public static function checkRaise($user_id) {

		$karma = [
			'0'	 => self::getKarma($user_id, 0),
			'1'	 => self::getKarma($user_id, 1),
			'2'	 => self::getKarma($user_id, 2),
			'3'	 => self::getKarma($user_id, 3)
		];

		return $karma['2']['self'] - $karma['3']['self'] + ($karma['1']['self'] - $karma['2']['self']) + ($karma['0']['self'] - $karma['1']['self']);
	}

	public static function f() {
		return [
			'title'		 => 'Karma change log',
			'blank'		 => false,
			'datatype'	 => [
				'user_id' => [
					'ThwUser' => [
						'id' => 'ON DELETE CASCADE'
					]
				]
			],
			'create'	 => [
				'date'			 => "DATE default null comment 'Date of record'",
				'user_id'		 => "bigint unsigned comment 'Link to ThwUser'",
				'self_karma'	 => "bigint default 0 comment 'Karma value'",
				'friends_karma'	 => "bigint default 0 comment 'Friends karma value'"
			]
		];
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
