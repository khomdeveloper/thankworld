<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\vh2015\T;
use app\components\vh2015\S;

class ThwModel extends ActiveRecord {

    public function oldToArray() {
        return $this->toArray();
    }

    public function getFieldFormat() {
        $result = [];
        $fields = $this->fields();
        foreach ($fields as $field) {
            $result[$field] = 'text';
        }
        return $result;
    }

	public static function is_session_started() {
		if (php_sapi_name() !== 'cli') {
			if (version_compare(phpversion(), '5.4.0', '>=')) {
				return session_status() === PHP_SESSION_ACTIVE
						? true
						: false;
			} else {
				return session_id() === ''
						? false
						: true;
			}
		}
		return false;
	}

	public static function getDatesFromRange($start, $end, $step = 1) {
		$dates = [$start];
		while (end($dates) < $end) {
			$dates[] = date('Y-m-d', strtotime(end($dates) . ' +' . $step . ' day'));
		}
		$dates[count($dates)-1] = (new \DateTime())->format('Y-m-d');
		return $dates;
	}

	public static function ue($input) {
		if (empty($input)) {
			return false;
		}
		if (is_array($input)) {
			$return = [];
			foreach ($input as $key => $val) {
				$return[$key] = self::ue($val);
			}
			return $return;
		} elseif (is_object($input)) {
			return self::ue(json_decode(json_encode($input), true));
		} else {
			return urlencode($input);
		}
	}

	public static function isDev() {
		if (strpos(Yii::$app->params['app_source_path'], 'khom.biz') === false) {
			return false;
		} else {
			return true;
		}
	}

	public static function session($sid = false) {
		if (self::is_session_started() === false) {
			ini_set('session.use_cookies', 1);
			ini_set('session.use_trans_sid', 1);
			if (!empty($sid))
				session_id($sid);
			session_start();
		}
	}

	public static function getSessionId() {
		return session_id();
	}

	/**
	 * output json error
	 * 
	 * [
	 *      'error'
	 *      'message' => '' or [
	 *                          'key' => [
	 *                              'en' => 
	 *                              'ru' =>
	 *                          ]
	 *                      ]
	 * ]
	 * 
	 */
	public static function e($error) {
		if (isset($error['message']) && is_array($error['message'])) {
			$error['message'] = T::out($error['message']);
		}

		if (isset($_REQUEST['jsonp'])) { //jsonp response
			die($_REQUEST['jsonp'] . '(' . json_encode($error) . ')');
		}

		die(json_encode($error));
	}

	public static function notFound($what, $default) {
		if (isset($what['_notfound'])) {
			if (is_array($what['_notfound'])) {
				die(json_encode($what['_notfound']));
			} else {
				if (empty($what['_notfound'])) {
					return false;
				} else {
					self::e([
						'error'		 => 'record_not_found',
						'message'	 => $what['_notfound']
					]);
				}
			}
		} else {
			return $default; //by default return false
		}
	}

	public function get($what) {
		if (isset($this->$what)) {
			return $this->$what;
		} else {
			return false;
		}
	}

	public static function hsc($input, $key = false) {

		if (get_called_class() == 'ThwUser') { //TODO: describe it in require
			$no_hsc = [
				'photo'				 => 'url',
				'invitable_friends'	 => 'json',
				'invited_friends'	 => 'json'
			];
		}

		if (get_called_class() == 'T') {
			$no_hsc = [
				'en' => 1,
				'ru' => 1
			];
		}

		if (is_array($input)) {
			$return = [];
			foreach ($input as $key1 => $val) {
				$return[$key1] = self::hsc($val, $key1);
			}
			return $return;
		} else {
			return isset($no_hsc[$key])
					? $input
					: /* get_called_class() . ':' . */htmlspecialchars($input);
		}
	}

	public static function getBy($what = null) {

		try {

			if (empty($what)) {
				return new static;
			}

			if (isset($what['_all'])) {//retrun all values as array
				$list = static::findBySQL('
		            		    SELECT * 
				            FROM `' . static::getTable() . '`
				            ORDER BY `changed`')->all();
				if (empty($list)) {
					return self::notFound($what, false);
				} else {
					return $list;
				}
			}

			$filter = static::filter([
						'id' => 1
			]);

			$parameters = [];
			$statement = [];
			foreach ($what as $key => $val) {
				if (isset($filter[$key]) && $filter[$key] >= 0) {

					//check << >> != >= <=

					if (is_string($val) && in_array($val[0], ['<',
								'!',
								'>']) && in_array($val[1], ['<',
								'>',
								'='])) {
						if ($val[0] == '!' && $val[1] == '=') {
							$parameters[$key] = explode('!=', $val)[1];
							$statement[] = "`" . $key . "` != :" . $key;
						} elseif ($val[0] == '<' && $val[1] == '<') {
							$parameters[$key] = explode('<<', $val)[1];
							$statement[] = "`" . $key . "` < :" . $key;
						} elseif ($val[0] == '>' && $val[1] == '>') {
							$parameters[$key] = explode('>>', $val)[1];
							$statement[] = "`" . $key . "` > :" . $key;
						} elseif ($val[0] == '>' && $val[1] == '=') {
							$parameters[$key] = explode('>=', $val)[1];
							$statement[] = "`" . $key . "` >= :" . $key;
						} elseif ($val[0] == '<' && $val[1] == '=') {
							$parameters[$key] = explode('<=', $val)[1];
							$statement[] = "`" . $key . "` <= :" . $key;
						} else {
							$parameters[$key] = $val;
							$statement[] = "`" . $key . "` = :" . $key;
						}
					} else {
						if (is_array($val)) {
							//TODO: if parameter is array
						} else {
							$parameters[$key] = $val;
							$statement[] = "`" . $key . "` = :" . $key;
						}
					}
				}
			}

			if (empty($statement) || empty($parameters)) {
				$obj = false;
			} else {

				if (!empty($what['_return']) && $what['_return'] = 'array') {

					//TODO: add order

					$where = join(' AND ', $statement);
					$list = static::findBySQL('
		            		    SELECT * 
				            FROM `' . static::getTable() . '`
				            WHERE ' . $where . '
					    ORDER BY `changed` ' .
							(!empty($what['_limit'])
									? 'LIMIT ' . $what['_limit']
									: ''), $parameters)->all();

					if (empty($list)) {
						return self::notFound($what, false);
					} else {
						return $list;
					}
				} else {

					$obj = static::findBySQL('
		            		    SELECT * 
				            FROM `' . static::getTable() . '`
				            WHERE ' . join(' AND ', $statement) . '
				            ORDER BY ' . (!empty($what['_order'])
									? '`' . join('`,`', urlencode($what['_order'])) . '`'
									: '`id`' ), $parameters)->one();

					if (empty($obj) && !empty($what['_notfound']) && $what['_notfound'] === false) {
						return false;
					}

					//if _notfound is array on single request we expect to create such object with parameters
					if (empty($obj) && !empty($what['_notfound']) && is_array($what['_notfound'])) {
						$obj = (new static)->add($what['_notfound']);
					}
				}
			}

			return empty($obj)
					? self::notFound($what, new static)
					: $obj;
		} catch (\Exception $e) {
			if (isset($_SESSION['signal']) && $_SESSION['signal'] == 'throw') {
				throw $e;
				unset($_SESSION['signal']);
			} else {
				self::e([
					'error'		 => 'kernel_error',
					'message'	 => $e->getMessage()
				]);
			}
		}
	}

	public static function no_hsc() {
		if (get_called_class() == 'ThwUser') {
			return [
				'photo'				 => 1,
				'invitable_friends'	 => 1,
				'invited_friends'	 => 1
			];
		}

		return [];
	}

	/**
	 * 
	 * if json_encode -> encode result otherwise return array
	 * 
	 * @param type $json_encode
	 * @return type
	 */
	public function encode($json_encode = true) {
		$filter = static::filter([
					'id'		 => 1,
					'changed'	 => 1
		]);
		$return = [];


		foreach ($filter as $key => $val) {
			$return[$key] = self::hsc($this->$key, $key);
		}
		return empty($json_encode)
				? $return
				: json_encode($return);
	}

	/*
	  check required paramters in $_REQUEST

	  $required = [
	  'field' => 1 required
	  'field' = >!= 1 no required
	  ]
	 */

	public static function required($required, $request = false) {

		$request = empty($request)
				? $_REQUEST
				: $request;

		$error = [];
		foreach ($required as $key => $val) {
			if ($val === 1 && !isset($request[$key])) {
				$error[] = $key;
			}
		}

		if (!empty($error)) {
			self::e([
				'error'		 => 'empty_required_parameters',
				'message'	 => '"' . join('","', $error) . '" parameters required!'
			]);
		}

		return true;
	}

	/*
	  (is necessary for meta data)
	  converts selected properties from string to JSON
	  $what = [
	  'property' => 'json'
	  ]
	 */

	public function enc($what) {
		$filter = static::filter();
		foreach ($what as $key => $val) {
			if (isset($filter[$key])) {
				if ($val == 'json') {
					$this->$key = json_decode($this->$key, true);
				}
			}
		}
		return $this;
	}

	public function add($input) {

		static::checkRequired($input);

		$filter = static::filter();
		foreach ($input as $key => $val) {
			if (isset($filter[$key]) && $filter[$key] >= 0) {
				$this->$key = $val;
			}
		}

		$old_hash = $this->hash;
		$this->hash = $this->getHash($input); //TODO: invitable friends are always changing and resetting hash
		//echo $old_hash . ', ' . $this->hash;

		if ($this->hash != $old_hash || empty($old_hash) || empty($this->hash)) {
			$this->changed = new \yii\db\Expression('NOW()');
			$this->save(false);
		}
		return $this;
	}

	public function set($input) {
		$filter = static::filter();
		$changed = false;
		foreach ($input as $key => $val) { //filtering variable
			if (isset($filter[$key]) && $filter[$key] >= 0) {
				$this->$key = $val;
				$changed = true;
			}
		}
		if (!empty($changed)) { //saving variable
			$this->save(false);
		}
		return $this;
	}

//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

	/*
	  return properites hash
	 */

	public function getHash($input) {
		$filter = static::filter();
		$s = [];
		foreach ($filter as $key => $val) {
			$s[] = $this->$key;
		}
		return md5(join('', $s));
	}

	/**
	 *   check $r if it has all required in filter() and add parameters
	 * 
	 *      add shall be ['key' => 1]
	 *
	 *      is empty($r) -> $_REQUEST
	 */
	public static function checkRequired($r = false, $add = []) {

		$r = empty($r)
				? $_REQUEST
				: $r;

		$filter = array_merge(static::filter(), $add);

		return self::required($filter, $r);
		/*
		  $required = [];
		  foreach ($filter as $key => $val) {
		  if ($val == 1 && empty($r[$key])) {
		  $required[] = $key;
		  }
		  }

		  if (!empty($required)) {
		  die(json_encode([
		  'error'   => 'empty required parameters',
		  'message' => join(',', $required) . ' parameters requred'
		  ]));
		  }
		 */
	}

}
