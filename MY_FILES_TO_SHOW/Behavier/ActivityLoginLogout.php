<?php

namespace app\models;

use Yii;
use yii\base\ActionFilter;

class ActivityLoginLogout extends ActionFilter
{
    private $user_id;
    private $user_name;

    /**
     * @param $action
     * @return mixed
     */
    public function beforeAction($action): mixed
    {
        if ($action->id == 'logout') {
            $this->user_id = Yii::$app->user->isGuest ? 'Unknown' : Yii::$app->user->getId();
            $this->user_name = Yii::$app->user->isGuest ? 'Unknown' : Yii::$app->user->identity->username;
        }
        return parent::beforeAction($action);
    }

    /**
     * @param $action
     * @param $result
     * @return mixed
     */
    public function afterAction($action, $result): mixed
    {
        $log = new ActivityLog();
        if ($action->id == 'login') {
            if (Yii::$app->user->isGuest) {
                $this->user_id = 'Unknown';
                if (isset(Yii::$app->request->post()['LoginForm'])) {
                    $this->user_name = Yii::$app->request->post()['LoginForm']['username'];
                } else {
                    $this->user_name = 'Unknown';
                }
            } else {
                $this->user_id = Yii::$app->user->getId();
                $this->user_name = Yii::$app->user->identity->username;
            }
        }
        $log->setLoginLogout($action, $this->user_id, $this->user_name);

        return parent::afterAction($action, $result);
    }
}
