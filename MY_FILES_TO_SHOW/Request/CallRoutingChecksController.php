<?php

namespace app\controllers\asterisk\routes;

use app\models\asterisk\RouteCallRoutingCheck;
use app\models\asterisk\RouteUploadedFile;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

class CallRoutingChecksController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'upload', 'template_in', 'template_out'],
                        'allow' => true,
                        'roles' => ['asterisk_CheckingRoutes'],
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
     * Renders Index page. Run the findPath() function from routeEngine model
     * @return string
     */
    public function actionIndex(): string
    {
        $modelCheck = new RouteCallRoutingCheck();
        $model = new RouteUploadedFile();
        $path = [];
        $flag = false;
        $message = false;
        if ($post = Yii::$app->request->post()) {
            $flag = true;
            list($path, $message) = $modelCheck->findRoutePath($post);
        }
        $modelCheck->setDirection($flag);
        $result = new ArrayDataProvider(['allModels' => $path ? [$path] : []]);
        return $this->render('index', compact('result', 'path', 'modelCheck', 'model', 'message', 'flag'));
    }

    /**
     * Upload template file to do mass route check
     * send the result to web browser
     */
    public function actionUpload()
    {
        $model = new RouteUploadedFile();
        if (Yii::$app->request->post()) {
            $model->uploaded_file = UploadedFile::getInstance($model, 'uploaded_file');
            if ($model->upload()) {
                // file is uploaded successfully
                list($array_for_csv, $type) = $model->mass_route_check();
                $model->outputCSV($array_for_csv, "Result_{$type}_" . date('d.m.Y') . ".csv");
                return;
            }
        }
        $path = [];
        $flag = false;
        $message = false;
        $modelCheck = new RouteCallRoutingCheck();
        //set default direction
        $modelCheck->setDirection($flag);
        $result = new ArrayDataProvider(['allModels' => $path]);
        return $this->render('index', compact('result', 'path', 'modelCheck', 'model', 'message', 'flag'));
    }

    /**
     * Create the template file for incoming mass route check
     * The web-page return csv file
     */
    public function actionTemplate_in(): void
    {
        $model = new RouteUploadedFile();
        $array_for_csv = $model->get_template_in();
        $model->outputCSV($array_for_csv, "Template_IN_" . date('d.m.Y') . ".csv");
    }

    /**
     * Create the template file for outbound mass route check
     * The web-page return csv file
     */
    public function actionTemplate_out(): void
    {
        $model = new RouteUploadedFile();
        $array_for_csv = $model->get_template_out();
        $model->outputCSV($array_for_csv, "Template_OUT_" . date('d.m.Y') . ".csv");
    }
}
