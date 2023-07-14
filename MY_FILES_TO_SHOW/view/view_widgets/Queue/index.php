<?php

use app\assets\DataTableLteAsset;
use app\assets\DialerCampaignsIndexAsset;
use app\models\dialer\Route;
use yii\bootstrap\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

DataTableLteAsset::register($this);
if (!empty($list->allModels)) DialerCampaignsIndexAsset::register($this);

$this->title = Yii::t('dialer', 'Campaigns');
$this->params['breadcrumbs'][] = $this->title;
$this->params['small'] = Yii::t('dialer', 'Campaigns created for Dialer application');

?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?php echo Yii::t('dialer', 'Campaign configurations') ?></h3>
            </div>
            <div class="box-body">
                <?php Pjax::begin(['id' => 'notes']); ?>
                <?= GridView::widget([
                    'dataProvider' => $list,
                    'layout' => "{items}\n{pager}",
                    'tableOptions' => ['class' => "table table-bordered table-hover", 'id' => 'queuesgrid', 'data-pjax' => true],
                    'columns' => [
                        [
                            'attribute' => 'id', 'label' => Yii::t('dialer', 'Id'), 'headerOptions' => ['id' => 'campaignID'],
                        ],
                        [
                            'attribute' => 'name', 'label' => Yii::t('dialer', 'Name'), 'headerOptions' => ['id' => 'campaignName'],
                        ],
                        [
                            'attribute' => 'description', 'label' => Yii::t('dialer', 'Description'), 'headerOptions' => ['id' => 'campaignDescription'],
                        ],
                        [ // Dialer Route
                            'attribute' => 'DIALER_route_id',
                            'headerOptions' => ['id' => 'campaignRoute'],
                            'label' => Yii::t('dialer', 'Route'),
                            'value' => function ($model) {
                                $route = $model->route;
                                return $route ? $route->name : '';
                            },
                            'format' => 'text'
                        ],
                        [ // Override Flag
                            'attribute' => 'override',
                            'label' => Yii::t('dialer', 'Override'),
                            'headerOptions' => ['id' => 'campaignOverride'],
                            'format' => 'raw',
                            'value' => function ($list) {
                                if ($list['destination_override']) {
                                    return Html::tag(
                                        'button',
                                        '<i class="glyphicon glyphicon-check text-info"></i>',
                                        ['class' => 'btn disabled btn-md',]
                                    );
                                } else {
                                    return Html::tag(
                                        'button',
                                        '<i class="glyphicon glyphicon-unchecked text-info"></i>',
                                        ['class' => 'btn disabled btn-md',]
                                    );
                                }
                            }
                        ],
                        [ // Destination
                            'attribute' => 'destination_override',
                            'label' => Yii::t('dialer', 'Destination'),
                            'headerOptions' => ['id' => 'campaignDestination'],
                            'format' => 'text',
                            'value' => function ($model) {
                                if (!isset($model->route)) return '';
                                return $model->destination_override ? $model->destination_override : $model->route->destination;
                            }
                        ],
                        [ // Concurrent calls
                            'attribute' => 'max_calls',
                            'label' => Yii::t('dialer', 'Max calls'),
                            'headerOptions' => ['id' => 'campaignMaxCalls'],
                        ],
                        [ // All calls
                            'attribute' => 'active_calls',
                            'label' => Yii::t('dialer', 'All calls'),
                            'headerOptions' => ['id' => 'campaignAllCalls'],
                            'value' => function ($model) {
                                return $model->active_calls + $model->calls_ringing;
                            }
                        ],
                        [
                            'attribute' => 'State',
                            'label' => Yii::t('dialer', 'Start / Stop'),
                            'headerOptions' => ['id' => 'campaignStartStop', 'style' => "width:90px"],
                            'format' => 'raw',
                            'filter' => [0 => 'Stop', 1 => 'Start'],
                            'value' => function ($model) {
                                if ($model->finished == 1) {
                                    return "<label class='onoffswitchfinished-label'>
                                                        <i class='glyphicon glyphicon-flag text-danger finished'><span class = 'finished-text'>FINISHED</span></i>
                                                    </label>";
                                } else {
                                    $ch = $model->enabled == 1 ? 'checked' : '';
                                    return "<div class='onoffswitch'>
                                                        <input type='checkbox' name='onoffswitch' class='onoffswitch-checkbox' id='myonoffswitch_$model->id' onclick = 'changeStatusCampaing($model->id, $model->enabled)' $ch  >
                                                        <label class='onoffswitch-label' for='myonoffswitch_$model->id'>
                                                            <span class='onoffswitch-inner'></span>
                                                            <span class='onoffswitch-switch'></span>
                                                        </label>
                                                    </div>";
                                }
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'controller' => 'dialer/queue',
                            'headerOptions' => ['style' => "width:60px"],
                            'footer' => Html::a(Yii::t('dialer', 'Add'), ['create'], ['class' => 'btn btn-primary']),
                        ],
                    ],
                    'showFooter' => true,
                    'id' => 'id',
                ]);
                ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>
