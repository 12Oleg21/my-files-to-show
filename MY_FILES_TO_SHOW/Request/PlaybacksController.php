<?php

namespace app\controllers\asterisk\records;

use app\models\asterisk\Musiconhold;
use app\models\asterisk\Playback;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;


class PlaybacksController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => ['asterisk_deletePlaybacks'],
                    ],
                    [
                        'actions' => ['download'],
                        'allow' => true,
                        'roles' => ['asterisk_downloadPlaybacks'],
                    ],
                    [
                        'actions' => ['play', 'checkrecord', 'playrecord'],
                        'allow' => true,
                        'roles' => ['asterisk_playPlaybacks'],
                    ],

                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['asterisk_indexPlaybacks', 'asterisk_downloadPlaybacks', 'asterisk_deletePlaybacks', 'asterisk_playPlaybacks',],
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
     * Renders Index page.
     * @return string
     */
    public function actionIndex()
    {
        $playback = new Playback(['scenario' => 'create']);

        if ($playback->load(Yii::$app->request->post())) {
            $playback->upload = UploadedFile::getInstance($playback, 'upload');
            $playback->name = $playback->check_filename_format(strtolower($playback->name));

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return \yii\widgets\ActiveForm::validate($playback);
            }

            if ($playback->validate()) {
                $transaction = $playback->getDb()->beginTransaction();
                try {
                    if ($playback->handleRecordSave($playback)) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('info', "File has been upload successfully!");
                    } else {
                        $transaction->rollBack();
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
        }
        $playbacks = $playback->add_modules_field();
        $list = new ArrayDataProvider(['allModels' => $playbacks, 'key' => 'name', 'pagination' => false]);

        return $this->render('index', compact('list', 'playback'));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionDelete($id): mixed
    {
        $playback = Playback::findOne($id);
        if (!existsModel($id, $playback)) return $this->redirect('index');
        if ($playback->check_in_module($id)) {
            $playback->delete_file($playback->name);
            $playback->delete();
        }
        return $this->redirect('index');
    }

    /**
     * Sends playback to user.
     * @param $id
     * @return string
     */
    public function actionDownload($id): string
    {
        $file = "/var/asterisk/records/{$id}.wav";
        if (file_exists($file)) {
            return Yii::$app->getResponse()->sendFile($file);
        }
        return $this->redirect('index');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionPlay($id): mixed
    {
        $playback = Playback::findOne($id);
        $short_name = Playback::prune_long_name($playback->name);
        return $this->renderAjax('play', compact('playback', 'short_name'));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionCheckrecord($id): mixed
    {
        $playback = Playback::findOne($id);
        $mohclass = new Musiconhold();
        $all_online_extensions = $mohclass->listAll_online_extensions();
        return $this->renderAjax('checkrecord', compact('playback', 'all_online_extensions'));
    }

    /**
     * @return mixed
     */
    public function actionPlayrecord(): mixed
    {
        $playback_name = Yii::$app->request->post('playback_name');
        $playback = Playback::findOne($playback_name);
        $mohclass = new Musiconhold();
        $all_online_extensions = $mohclass->listAll_online_extensions();
        $extension = Yii::$app->request->post('extention');
        $_SESSION['play_extension'] = $extension;
        $playback->originate_record($extension, $playback->name);
        return $this->render('checkrecord', compact('playback', 'all_online_extensions'));
    }

}
