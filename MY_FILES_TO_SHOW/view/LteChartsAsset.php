<?php

namespace app\assets;

use yii\web\AssetBundle;

class LteChartsAsset extends AssetBundle
{
  public $sourcePath = '@vendor/almasaeed2010/adminlte/bower_components';
  public $js = [
      'Flot/jquery.flot.js',
      'Flot/jquery.flot.resize.js',
      'Flot/jquery.flot.pie.js',
      'Flot/jquery.flot.categories.js',
      'Flot/jquery.flot.time.js',
      'moment/min/moment.min.js',
    ];
  public $css = [
      //'Flot/examples/examples.css'
    ];
    public $depends = [
      'yii\web\JqueryAsset',
      'dmstr\web\AdminLteAsset',
    ];

}
