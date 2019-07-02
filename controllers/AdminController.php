<?php

namespace app\controllers;

use app\models\ThwUser;
use Yii;
use yii\web\Controller;
use Facebook\FacebookRequestException;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use app\components\vh2015\S;

class AdminController extends Controller
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction'
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('admin');
    }
}
