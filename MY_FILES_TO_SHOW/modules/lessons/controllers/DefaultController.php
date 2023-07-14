<?php

namespace app\modules\lessons\controllers;

use app\models\Student;
use yii\web\Controller;
use Yii;

/**
 * Default controller for the `lessons` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex($whiteboard): string
    {
        // set the almost empty layout because we don't need any header or footer to show a whiteboard
        $this->layout = 'main';
        //find a student model by an unique token. $whiteboard looks so :  93ce0f443c6d32bf0f322ae44
        $boardURL = Student::setBitPaperUrl($whiteboard);
        // the index page will redirect us to the bitPaper whiteboard by this $boardURL
        // the $boardURL looks : https://bitpaper.io/go/22247-seva%20bek/bh3dr7bhM?access-token=93ce0f443c6d32bf0f322ae44....
        return $boardURL ? $this->render('index', compact('boardURL')) : $this->render('error');
    }
}
