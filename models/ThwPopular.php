<?php

namespace app\models;

use Yii;
use app\components\vh2015\T;

/**
 * This is the model class for table "{{popular}}".
 *
 * The followings are the available columns in table '{{popular}}':
 * @property string $id
 * @property string $text
 * @property string $changed
 */
class ThwPopular extends ThwModel {

    public static function tableName() {
        return '{{%popular}}';
    }

	public static function getTable() {
		return 'thw_popular';
	}

	public static function filter($add = []) {
		return array_merge([
			'text' => 1
				], $add);
	}

	public static function getAll($amount = 4) {

		try {

			$db = Yii::$app->db->createCommand("
		SELECT `text` 
		FROM `thw_popular`
		WHERE `expired` > :time 
		AND `text` is not null
		AND `text` != ''
		ORDER BY RAND()
		LIMIT " . urlencode($amount) * 1 . "
	    ",[
                ':time' => time()
            ])->query();

			$return = [];

			if (!empty($db)) {
				while (($row = $db->read()) != false) {
					$return[] = [
						'label'	 => self::hsc($row['text']) . ' ' . T::out([
							'popular_for' => [
								'en' => '(popular)',
								'ru' => '(популярный)'
							]
								], false, 'notag'),
						'value'	 => $row['text']
					];
				}
			}

			if (!empty($return)) {
				return $return;
			} else { //get popular data from thw_thank
				$db = Yii::$app->db->createCommand("
		DELETE
		FROM `thw_popular`
		WHERE 1 = 1
	    ")->query();

				$db = Yii::$app->db->createCommand("
		SELECT `title`,count(*) as `repeat`
		FROM `thw_thank`
		WHERE `title` is not null
		AND `title` != ''
		GROUP BY `title`
		LIMIT 10
	    ")->query();

				while (($row = $db->read()) != false) {
					$return[$row['title']] = $row['repeat'];
				}

				if (empty($return)) {
					return false;
				}

				//prepare query
				$query = [];
				foreach ($return as $title => $repeat) {
					$query[] = [
						'text'		 => $title,
						'repeat'	 => $repeat,
						'expired'	 => time() + 86400
					];
				}

				//add finded data to popular requests
				$builder = Yii::$app->db->createCommand();
				$command = $builder->batchInsert('thw_popular', ['text', 'repeat', 'expired'], $query);
				$command->execute();

				return self::getAll();
			}
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array(
				'text, changed',
				'required'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array(
				'id, text, changed',
				'safe',
				'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
				);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id'		 => 'ID',
			'text'		 => 'Text',
			'changed'	 => 'Changed',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search() {
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id, true);
		$criteria->compare('text', $this->text, true);
		$criteria->compare('changed', $this->changed, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ThwPopular the static model class
	 */
//	public static function model($className = __CLASS__) {
//		return parent::model($className);
//	}

}
