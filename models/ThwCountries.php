<?php

namespace app\models;

/**
 * This is the model class for table "{{countries}}".
 *
 * The followings are the available columns in table '{{countries}}':
 * @property string $id
 * @property string $code
 * @property string $country_ru
 * @property string $country_en
 */
class ThwCountries extends ThwModel{

    public static function tableName() {
        return '{{%countries}}';
    }

	public static function getTable(){
		return 'thw_countries';
	}

	public static function filter($add = []){
		return array_merge([
			'code' => 1,	
			'country_ru' => 0,
			'country_en' => 0
		],$add);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('code, country_ru, country_en', 'required'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, code, country_ru, country_en', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'code' => 'Code',
			'country_ru' => 'Country Ru',
			'country_en' => 'Country En',
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
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('country_ru',$this->country_ru,true);
		$criteria->compare('country_en',$this->country_en,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ThwCountries the static model class
	 */
//	public static function model($className=__CLASS__)
//	{
//		return parent::model($className);
//	}
}
