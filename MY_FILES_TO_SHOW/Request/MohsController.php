<?php

namespace app\controllers\asterisk\records;

use app\models\asterisk\Musiconhold;
use app\models\asterisk\RecordMoh;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;

class MohsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['delete', 'deletemoh'],
                        'allow' => true,
                        'roles' => ['asterisk_deleteMohclass'],
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['asterisk_createMohclass'],
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        'roles' => ['asterisk_udateMohclass'],
                    ],
                    [
                        'actions' => ['downloadmoh', 'download'],
                        'allow' => true,
                        'roles' => ['asterisk_downloadMohclass'],
                    ],
                    [
                        'actions' => ['index', 'playmoh', 'playall', 'checkmohclass', 'playmohclass'],
                        'allow' => true,
                        'roles' => ['asterisk_deleteMohclass', 'asterisk_createMohclass', 'asterisk_udateMohclass', 'asterisk_downloadMohclass'],
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
    public function actionIndex($id = null): string
    {
        $moh = new RecordMoh();
        $new_model = Musiconhold::findOne($id);
        $model = new Musiconhold(['scenario' => 'create']);
        $mohs = new ArrayDataProvider(['allModels' => $moh->listAllMohs(), 'key' => 'id', 'pagination' => false]);
        return $this->render('index', compact('mohs', 'new_model', 'model'));
    }

    /**
     * Sends record to user.
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
     * Plays audio file to user.
     */
    public function actionPlaymoh($id, $className)
    {
        $filename = $id;
        $mohclass = Musiconhold::findOne($className);
        return $this->renderAjax('playmoh', compact('filename', 'mohclass'));
    }

    /**
     * Plays all audio files to user.
     */
    public function actionPlayall($id)
    {
        $model = new RecordMoh();
        $mohclass = Musiconhold::findOne($id);
        $all_files = $model->listAllMohFiles($mohclass->name);
        return $this->renderAjax('playall', compact('mohclass', 'all_files'));
    }

    /**
     * Calls to your phone and plays moh audio files
     */
    public function actionCheckmohclass($id)
    {
        $mohclass = Musiconhold::findOne($id);
        if (!existsModel($id, $mohclass)) return $this->redirect('index');
        if (Yii::$app->request->isAjax) {
            $all_online_extensions = $mohclass->listAll_online_extensions();
            return $this->renderAjax('checkmohclass', compact('mohclass', 'all_online_extensions'));
        }
        if ($musiconhold = Yii::$app->request->post('Musiconhold')) {
            $extension = $musiconhold['digit'];
            $_SESSION['play_extension'] = $extension;
            $mohclass->originate_mohclass($extension, $mohclass->name);
        }
        return $this->redirect('index');
    }

    /**
     * Plays audio files of moh class to user.
     */
    public function actionPlaymohclass()
    {
        $mohclass_name = Yii::$app->request->post('mohclass_name');
        $mohclass = Musiconhold::findOne($mohclass_name);
        if (!existsModel($mohclass_name, $mohclass)) return $this->redirect('index');
        $all_online_extensions = $mohclass->listAll_online_extensions();
        $extension = Yii::$app->request->post('extention');
        $_SESSION['play_extension'] = $extension;
        $mohclass->originate_mohclass($extension, $mohclass->name);
        return $this->render('checkmohclass', compact('mohclass', 'all_online_extensions'));
    }

    /**
     * Deletes record from local storage.
     * @return string
     */
    public function actionDeletemoh()
    {
        $params = Yii::$app->request->get('params');
        $dir = basename($params['dir']);
        $filename = basename($params['filename']);
        $file = '/var/asterisk/moh/' . $dir . '/' . $filename;
        if (file_exists($file)) {
            unlink($file);
            $reload = ['moh' => 'moh reload'];
            if ($AsteriskReload = Yii::$app->cache->get('AsteriskReload')) {
                $AsteriskReload['pjsip'] = 'pjsip reload';
                $AsteriskReload = Yii::$app->cache->set('AsteriskReload', $AsteriskReload);
            } else {
                $AsteriskReload = Yii::$app->cache->set('AsteriskReload', $reload);
            }
        }
        return $this->redirect(['update', 'id' => $dir]);
    }

    /**
     * Sends record to user.
     * @return string
     */
    public function actionDownloadmoh()
    {
        $params = Yii::$app->request->get('params');
        $dir = basename($params['dir']);
        $filename = basename($params['filename']);
        $file = '/var/asterisk/moh/' . $dir . '/' . $filename;
        if (file_exists($file)) {
            return Yii::$app->getResponse()->sendFile($file);
        }
        return $this->redirect(['update', 'id' => $dir]);
    }

    /**
     * Create new MoH Class (folder in /var/asterisk/moh/).
     * @return string
     */
    public function actionCreate(): string
    {
        $model = new Musiconhold(['scenario' => 'create']);
        if ($model->load(Yii::$app->request->post())) {
            $model->name = $model->check_filename_format(strtolower($model->name));
            $model->mode = 'files';
            $model->directory = "/var/asterisk/moh/{$model->name}";
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
            if ($model->save()) {
                FileHelper::createDirectory('/var/asterisk/moh/' . $model->name, $mode = 0755);
                Yii::$app->session->setFlash('info', "Music on hold $model->name has been created successfully!");
                return $this->redirect(['index', 'id' => $model->name]);
            }
        }
        return $this->redirect(['index']);
    }

    /**
     * Update MoH Class (folder in /var/asterisk/moh/).
     * @param $id
     * @return string
     */
    public function actionUpdate($id): string
    {
        $model = new RecordMoh();
        $model->name = $id;
        if ($model->load(Yii::$app->request->post())) {
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->uploadmoh($model->name)) {
                $reload = ['moh' => 'moh reload'];
                if ($AsteriskReload = Yii::$app->cache->get('AsteriskReload')) {
                    $AsteriskReload['pjsip'] = 'pjsip reload';
                    $AsteriskReload = Yii::$app->cache->set('AsteriskReload', $AsteriskReload);
                } else {
                    $AsteriskReload = Yii::$app->cache->set('AsteriskReload', $reload);
                }
                Yii::$app->session->setFlash('info', "Music on hold $model->name has been updated successfully!");
            }
        }
        $all_files = $model->listAllMohFiles($model->name);
        $all_mohclass = $model->listAllMohs();
        $list = new ArrayDataProvider(['allModels' => $all_files, 'pagination' => false]);
        return $this->render('update', compact('model', 'list', 'all_mohclass'));
    }

    /**
     * Deletes MoH Class (folder in /var/asterisk/moh/).
     * @param $id
     * @return string
     */
    public function actionDelete($id): string
    {
        $mohclass = Musiconhold::findOne($id);
        if (!existsModel($id, $mohclass)) return $this->redirect('index');
        $moh = new RecordMoh();
        $moh_class_default = Musiconhold::findOne('default');
        if ($mohclass and $moh->check_in_module($mohclass->name)) {
            $mohclass->delete();
            $all_mohclass = $moh_class_default->find()->all();
            if ($all_mohclass) {
                $count = count($all_mohclass);
                if ($count == 1) {
                    $assets_link = $moh_class_default->upload_link;
                    if (file_exists($assets_link)) unlink($assets_link);
                }
            }
        }
        FileHelper::removeDirectory('/var/asterisk/moh/' . $id);
        $reload = ['moh' => 'moh reload'];
        if ($AsteriskReload = Yii::$app->cache->get('AsteriskReload')) {
            $AsteriskReload['pjsip'] = 'pjsip reload';
            $AsteriskReload = Yii::$app->cache->set('AsteriskReload', $AsteriskReload);
        } else {
            $AsteriskReload = Yii::$app->cache->set('AsteriskReload', $reload);
        }
        return $this->redirect('index');
    }
}
