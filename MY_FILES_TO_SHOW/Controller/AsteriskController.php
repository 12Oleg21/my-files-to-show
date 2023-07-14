<?php

namespace app\controllers;

use AGI_AsteriskManager;
use app\commands\LinuxdataController;
use app\models\asterisk\AsteriskDashboard;
use app\models\asterisk\Extension;
use app\models\asterisk\Peertrunk;
use app\models\asterisk\Registration;
use app\models\asterisk\Routing;
use app\models\Package;
use phpari\phpari;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;


/**
 * Reloads Asterisk, renders Dashboard and controll spy on channel.
 *
 * @author Martin Moucka <moucka.m@gmail.com>
 */
class AsteriskController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['spy'],
                        'allow' => true,
                        'roles' => ['asterisk_spyChannel'],
                    ],
                    [
                        'actions' => ['hangup'],
                        'allow' => true,
                        'roles' => ['asterisk_hangup'],
                    ],
                    [
                        'actions' => ['reload'],
                        'allow' => true,
                        'roles' => ['asterisk_reload'],
                    ],
                    [
                        'actions' => ['commitrouting'],
                        'allow' => true,
                        'roles' => ['asterisk_reload'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            $permissions = Yii::$app->authManager->getPermissionsByUser(Yii::$app->user->getId());
                            return preg_match('"asterisk"', implode(',', array_keys($permissions)));
                        }
                    ],
                    [
                        'actions' => [],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * @return mixed
     */
    public function actionRefresh()
    {
        $package = new Package();
        $package->setNewData();
        return $this->redirect(['index']);
    }

    /**
     * Dashboard page
     */
    public function actionIndex()
    {
        $model = new AsteriskDashboard();
        $statistics = $model->populateStatistics();
        if (Yii::$app->request->isPjax) {
            return $this->renderPartial('_index_statistics', compact('statistics'));
        }
        $model->check_emergency_mode();
        $timeZone = $model->setTimeZone();
        $trunks = \app\models\asterisk\Endpoint::find()->select('id')->where(['context' => 'external'])->asArray(true)->all();
        $all_trunks = ['All' => 'All'] + ArrayHelper::map($trunks, 'id', 'id');
        $information = $model->populateInformation();
        $package = new Package();
        $packages = $package->listAll();
        return $this->render('index', compact('timeZone', 'all_trunks', 'information', 'statistics', 'packages'));
    }

    /**
     * Forming and sending the data for 
     * 'System load average' graph
     * invoked from index.js with Ajax request
     * return json with data and options for the graph
     */
    public function actionGettingData()
    {
        $model = new AsteriskDashboard();
        $interval = Yii::$app->request->post('interval');
        $res = $model->formingLa($interval);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $res;
    }

    /**
     * Forming and sending the data for 
     * 'Calls by trunks' graph
     * invoked from index.js with Ajax request
     * return json with data and options for the graph
     */
    public function actionGettingdatatrunks()
    {
        $model = new AsteriskDashboard();
        $interval_trunks = Yii::$app->request->post('interval_trunks');
        $trunk = Yii::$app->request->post('trunk');
        $res = $model->formingCallsByTrunk($interval_trunks, $trunk);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $res;
    }

    /**
     * @return mixed
     */
    public function actionCorerestart(): mixed
    {
        $ami = new AGI_AsteriskManager();
        if ($ami->connect() && (Yii::$app->cache->exists('AsteriskRestart'))) {
            $req = "Action: Command\r\n";
            $req .= "Command: core restart now\r\n";
            $req .= "\r\n";
            fwrite($ami->socket, $req);
            Yii::$app->cache->delete('AsteriskReload');
            Yii::$app->cache->delete('AsteriskRestart');
            Yii::$app->session->setFlash('info', "Asterisk has been restarted!");
        } else {
            Yii::$app->session->setFlash('warning', 'Asterisk has not been restarted!');
        }
        return Yii::$app->request->referrer ? $this->redirect(Yii::$app->request->referrer) : $this->redirect(['index']);
    }

    /**
     * Reload Asterisk's modules pursuant to reload parameters.
     * @return string
     */
    public function modulesReload(): string
    {
        $ami = new AGI_AsteriskManager();
        if ($ami->connect() && ($AsteriskReload = Yii::$app->cache->get('AsteriskReload'))) {
            foreach ($AsteriskReload as $value) {
                $ami->Command($value);
            }
            Yii::$app->cache->delete('AsteriskReload');
            Yii::$app->session->setFlash('info', "Asterisk's changes have been applied!");
        } else {
            Yii::$app->session->setFlash('warning', "Asterisk's changes have not been applied!");
        }
    }

    /**
     * @return mixed
     */
    public function actionCommitRouting(): mixed
    {
        if (!Yii::$app->dbAsterisk->schema->getTableSchema('OWN_routing_temp', true) == null) {
            if (Routing::commit()) Yii::$app->session->setFlash('info', 'Changes have been applied!');
        }
        if (Yii::$app->cache->exists('AsteriskReload')) {
            $this->modulesReload();
        }
        return Yii::$app->request->referrer ? $this->redirect(Yii::$app->request->referrer) : $this->redirect(['index']);
    }

    /**
     * @return mixed
     */
    public function actionRollbackrouting(): mixed
    {
        if (!Yii::$app->dbAsterisk->schema->getTableSchema('OWN_routing_temp', true) == null) {
            Routing::rollback();
        }
        if (Yii::$app->cache->exists('AsteriskReload')) {
            Yii::$app->cache->delete('AsteriskReload');
        }
        Yii::$app->session->setFlash('info', 'Changes have been rolled back');
        return Yii::$app->request->referrer ? $this->redirect(Yii::$app->request->referrer) : $this->redirect('index');
    }
}
