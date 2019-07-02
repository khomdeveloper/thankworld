<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\models\ContactForm;
use app\components\vh2015\M;
use app\components\vh2015\H;

class SiteController extends Controller
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
        return $this->render('index');
    }

    //WIP

    /**
     * display any requested page without template
     */
    public function actionOut() {

        M::session(empty($_REQUEST['_sid'])
            ? false
            : $_REQUEST['_sid']);

        die(H::getTemplate($_REQUEST['t'], $_REQUEST));
    }

    public function actionReturn() {
        M::session(empty($_REQUEST['_sid'])
            ? false
            : $_REQUEST['_sid']);

        if (!empty($_REQUEST['app'])){
            header("Location: " . Yii::$app->params['app_path']);
        } else {
            header("Location: " . Yii::$app->params['app_source_path']);
        }

        exit;
    }

    public function actionEmail() {
        $this->render('email');
    }

    public function actionButton() {
        $this->renderPartial('internal_button');
    }

    public function actionButtoncode() {
        $this->render('code_for_internal_button');
    }

    public function actionStatistics() {

        $this->render('external_statistics');

        /**
         * TODO:
         *
         * должен отдавать отдельную страницу
         * на которой линковать styles
         * подключать библиотеки
         * и выводить урезанную статистику (без друзей)
         *
         *
         *
         */
    }

    /**
     * Displays the contact page
     */
//    public function actionContact() {
//        $model = new ContactForm;
//        if (isset($_POST['ContactForm'])) {
//            $model->attributes = $_POST['ContactForm'];
//            if ($model->validate()) {
//                $name = '=?UTF-8?B?' . base64_encode($model->name) . '?=';
//                $subject = '=?UTF-8?B?' . base64_encode($model->subject) . '?=';
//                $headers = "From: $name <{$model->email}>\r\n" .
//                    "Reply-To: {$model->email}\r\n" .
//                    "MIME-Version: 1.0\r\n" .
//                    "Content-Type: text/plain; charset=UTF-8";
//
//                mail(Yii::$app->params['adminEmail'], $subject, $model->body, $headers);
//                Yii::$app->user->setFlash('contact', 'Thank you for contacting us. We will respond to you as soon as possible.');
//                $this->refresh();
//            }
//        }
//        $this->render('contact', array(
//            'model' => $model));
//    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
//
//    /**
//     * Displays the login page
//     */
//    public function actionLogin() {
//        $model = new LoginForm;
//
//        // if it is ajax validation request
//        if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
//            echo CActiveForm::validate($model);
//            Yii::app()->end();
//        }
//
//        // collect user input data
//        if (isset($_POST['LoginForm'])) {
//            $model->attributes = $_POST['LoginForm'];
//            // validate user input and redirect to the previous page if valid
//            if ($model->validate() && $model->login())
//                $this->redirect(Yii::app()->user->returnUrl);
//        }
//        // display the login form
//        $this->render('login', array(
//            'model' => $model));
//    }
//
//    /**
//     * Logs out the current user and redirect to homepage.
//     */
//    public function actionLogout() {
//        Yii::app()->user->logout();
//        $this->redirect(Yii::app()->homeUrl);
//    }
//
//
//
//
//
//
//
//
//
//
//
//
//
//    public function actionLogin()
//    {
//        if (!\Yii::$app->user->isGuest) {
//            return $this->goHome();
//        }
//
//        $model = new LoginForm();
//        if ($model->load(Yii::$app->request->post()) && $model->login()) {
//            return $this->goBack();
//        }
//        return $this->render('login', [
//            'model' => $model,
//        ]);
//    }
//
//    public function actionLogout()
//    {
//        Yii::$app->user->logout();
//
//        return $this->goHome();
//    }
//
//    public function actionContact()
//    {
//        $model = new ContactForm();
//        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
//            Yii::$app->session->setFlash('contactFormSubmitted');
//
//            return $this->refresh();
//        }
//        return $this->render('contact', [
//            'model' => $model,
//        ]);
//    }
//
    public function actionAbout()
    {
        return $this->render('about');
    }
}
