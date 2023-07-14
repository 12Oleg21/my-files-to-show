<?php

namespace app\modules\sitemap\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

use app\modules\sitemap\components\Sitemap;

/**
 * Default controller for the `sitemap` module
 */
class DefaultController extends Controller
{

    private $sitemap;


    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $this->sitemap = new Sitemap();

        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }


    /**
     * Renders the index view for the module
     *
     * @return string
     */
    public function actionIndex()
    {
        if (($path = $this->sitemap->getIndexSitemapPath()) && is_readable($path)) {
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->add('Content-Type', 'text/xml');

            return file_get_contents($path);
        }

        return 'Sitemap index does not exist or is unavailable.';
    }


    /**
     * @param string|integer $index
     *
     * @return string
     */
    public function actionItem($index)
    {
        if (($path = $this->sitemap->getItemSitemapPath($index)) && is_readable($path)) {
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->add('Content-Type', 'text/xml');

            return file_get_contents($path);
        }

        return 'Sitemap index does not exist or is unavailable.';
    }
}
