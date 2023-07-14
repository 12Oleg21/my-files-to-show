<?php

namespace app\controllers\asterisk\clientsprofiles;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use app\models\asterisk\Client;
use app\models\asterisk\ClientOptionalField;
use app\models\asterisk\Pool;
use yii\web\Response;
use yii\helpers\ArrayHelper;

class ClientsController extends Controller
{
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => ['asterisk_deleteClient'],
                    ],
                    [
                        'actions' => ['update', 'updateoptional'],
                        'allow' => true,
                        'roles' => ['asterisk_updateClient'],
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['asterisk_createClient'],
                    ],
                    [
                        'actions' => ['view'],
                        'allow' => true,
                        'roles' => ['asterisk_viewClient'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['asterisk_deleteClient', 'asterisk_updateClient',
                            'asterisk_createClient', 'asterisk_viewClient'],
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
    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * @return mixed
     */
    public function actionIndex(): mixed
    {
        $model = new Client();
        $model->set_session(Yii::$app->request->get());
        $all_models = $_SESSION['clients'] ? $model->find()->with('pools')->all() : $model->find()->with('pools')->where(['active' => 1])->all();
        $list = new ArrayDataProvider([
          'allModels' => $all_models,
          'key' => 'id',
          'pagination' => false
          ]);
        return $this->render('index',compact('list', 'model'));
    }

    /**
     * @return mixed
     */
    public function actionCreate(): mixed
    {
        $client = new Client(['scenario' => 'create']);
        $optional = ClientOptionalField::find()->one();
        $optional_fields = $optional ? $optional->getFields() : [] ;
        $client->loadDefaultValues();
        $yiiRequest = Yii::$app->request;
        $client_pools = $client->manipulatePools($yiiRequest);

        if( $client->load(Yii::$app->request->post())){
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return \yii\widgets\ActiveForm::validate($client);
            }
            if($client->saveClient($yiiRequest)){
                return $this->redirect(['view', 'id' => $client->id]);
            }
        }

        list($list, $list_all_pools, $unavailable_pools) = $client->mainSortPools($client_pools);

        return $this->render('_form',compact('client', 'optional_fields', 'list', 'list_all_pools', 'unavailable_pools'));

    }

    public function actionUpdate($id)
    {
        $client = Client::findOne($id);
        if(!existsModel($id, $client)) return $this->redirect('index');
        $optional = ClientOptionalField::find()->one();
        $optional_fields = $optional ? $optional->getFields() : [] ;
        $client->scenario = 'update';

        $yiiRequest = Yii::$app->request;
        $client_pools = $client->manipulatePools($yiiRequest);

        if( $client->load($yiiRequest->post())){
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return \yii\widgets\ActiveForm::validate($client);
            }
            if($client->saveClient($yiiRequest, 'updated')){
                return $this->redirect(['view', 'id' => $client->id]);
            }
        }
        list($list, $list_all_pools, $unavailable_pools) = $client->mainSortPools($client_pools);

        return $this->render('_form',compact('client', 'optional_fields', 'list', 'list_all_pools' , 'unavailable_pools'));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionView($id)
    {
        $client = Client::findOne($id);
        if(!existsModel($id, $client)) return $this->redirect('index');
        $optional = ClientOptionalField::find()->one();
        $optional_fields = $optional ? $optional->getFields() : [] ;
        $client->scenario = 'view';
        $pools = $client->pools;

        $list_pools = new ArrayDataProvider([
            'allModels' => $pools,
            'key' => 'id',
            'pagination' => false
        ]);

        return $this->render('view',compact('client', 'optional_fields', 'list_pools'));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionDelete($id): mixed
    {
        $client = Client::findOne($id);
        if(!existsModel($id, $client)) return $this->redirect('index');
        $transaction = $client->getDb()->beginTransaction();
        try {
            $client->active = 0;
            $flag = $client->update(false) === false ? false : true;
            if($flag) {
              Pool::updateAll(['OWN_client_id' => Null], ['OWN_client_id'=>$id]);
              Voice::updateALL(['OWN_client_id'=>Null], ['OWN_client_id'=>$id]);
              routeEngine::cacheClients();
              $transaction->commit();
            }else{
                $transaction->rollBack();
            }
        } catch (Exception $e) {
            $transaction->rollBack();
        }
        return $this->redirect('index');
    }

    /**
     * @return mixed
     */
    public function actionUpdateoptional(): mixed
    {
        $optional = ClientOptionalField::find()->one();
        if (!$optional) {
            $optional = new ClientOptionalField();
            $optional->loadDefaultValues();
        }
        $optional->scenario = 'update';
        if ($post = Yii::$app->request->post()) {
            $optional->load($post);
            $valid = $optional->validate();
            if ($valid) {
                $transaction = $optional->getDb()->beginTransaction();
                try {
                    $flag = $optional->updateFields() === false ? false : true;
                    if ($flag) {
                        $transaction->commit();
                        return $this->redirect('index');
                    }else{
                        $transaction->rollBack();
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
        }
        return $this->render('updateOptional', compact('optional'));
    }
}
