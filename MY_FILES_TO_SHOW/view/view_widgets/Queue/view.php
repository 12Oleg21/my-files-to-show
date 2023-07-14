<?php

use yii\bootstrap\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('dialer', 'Campaigns'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['small'] = Yii::t('dialer', 'Campaigns') . ' - ' . Yii::t('dialer', ucfirst(Yii::$app->controller->action->id));

?>

<div class="box box-default">
    <div class="box-header">
        <h3 class="box-title"><?php echo Yii::t('dialer', 'Campaign settings') ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php $form = ActiveForm::begin(['id' => 'form-campaign']); ?>
            <div class="col-md-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo Yii::t('dialer', 'Main settings') ?></h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <?= $form->field($model, 'name', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Name'), ['id' => 'campaignName']) ?>
                            <?= $form->field($model, 'description', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Description'), ['id' => 'campaignDescription']) ?>
                            <?= $form->field($route, 'name', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Dialer route'), ['id' => 'campaignRoute']) ?>
                            <?= $form->field($model, 'destination_override', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Override Destination'), ['id' => 'campaignOverride']) ?>
                        </div>
                    </div><!-- class="box-body" -->
                </div><!-- class="box" -->
            </div><!-- class="col-md-6"-->
            <div class="col-md-6">
                <div class="box collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo Yii::t('dialer', 'Dialing algorithm') ?></h3>
                        <div class="box-tools pull-right">
                            <?= Html::button('<i class="fa fa-plus"></i>', ['class' => "btn btn-box-tool", 'data-widget' => "collapse"]) ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <?= $form->field($dialer_method, 'type', ['inputOptions' => ['readonly' => true]])->label(Yii::t('dialer', 'Dialer method'), ['id' => 'campaignDialerMethod']) ?>
                        <?php if ($model->DIALER_method_id == 2)
                            echo $form->field($model, 'superacceptable', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Acceptable Abandonment Rate'), ['id' => 'campaignAcceptableAbandonmentRate']); ?>
                        <?php if ($model->DIALER_method_id == 3) {
                            echo $form->field($model, 'constantdelay', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Call delay'), ['id' => 'campaignCallDelay']);
                            echo $form->field($model, 'constantwaitidle', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Wait for idle agent'), ['id' => 'campaignWaitForIdleAgent']);
                            echo $form->field($model, 'constantmaxhold', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Stop calling when N on hold'), ['id' => 'campaignStopCallingWhen']);
                        } ?>
                        <?= $form->field($model, 'max_calls', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Concurrent calls'), ['id' => 'campaignMaxCalls']) ?>
                    </div><!-- class="box-body" -->
                </div><!-- class="box" -->

                <div class="box collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo Yii::t('dialer', 'Redial settings') ?></h3>
                        <div class="box-tools pull-right">
                            <?= Html::button('<i class="fa fa-plus"></i>', ['class' => "btn btn-box-tool", 'data-widget' => "collapse"]) ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <?= $form->field($model, 'retry_interval', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Minutes between redial attempts'), ['id' => 'campaignMinuteBetween']) ?>
                        <?= $form->field($model, 'retry_num', ['inputOptions' => ['readonly' => true]])->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Redial attempts'), ['id' => 'campaignRedialAttempts']) ?>
                    </div><!-- class="box-body" -->
                </div><!-- class="box" -->
                <div class="box collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo Yii::t('dialer', 'Date settings') ?></h3>
                        <div class="box-tools pull-right">
                            <?= Html::button('<i class="fa fa-plus"></i>', ['class' => "btn btn-box-tool", 'data-widget' => "collapse"]) ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <?= GridView::widget([
                                'dataProvider' => $time_ranges,
                                'tableOptions' => ['id' => 'gridview', 'class' => "table table-bordered table-hover"],
                                'layout' => '{items}{pager}',
                                'showHeader' => true,
                                'columns' => [
                                    ['attribute' => 'date', 'label' => Yii::t('dialer', 'Date'), 'headerOptions' => ['id' => 'campaignDate']],
                                    ['attribute' => 'start', 'label' => Yii::t('dialer', 'Start'), 'headerOptions' => ['id' => 'campaignStart'], 'value' => function ($model) {
                                        return substr($model['start'], 0, 5);
                                    }],
                                    ['attribute' => 'stop', 'label' => Yii::t('dialer', 'Stop'), 'headerOptions' => ['id' => 'campaignStop'], 'value' => function ($model) {
                                        return substr($model['stop'], 0, 5);
                                    }],
                                ],
                                'showFooter' => false,
                            ]);
                            ?>
                        </div>
                    </div><!-- class="box-body" -->
                </div><!-- class="box" -->
                <div class="form-group">
                    <?= Html::a(Yii::t('dialer', 'Cancel'), ['index'], ['class' => 'btn btn-default pull-right', 'style' => "margin-left: 5px;"]) ?>
                    <?= Html::a(Yii::t('dialer', 'Edit'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary pull-right', 'name' => 'edit-button']) ?>
                </div>
            </div><!-- class="col-md-6"-->
            <?php ActiveForm::end() ?>
        </div> <!-- class="row" -->
    </div><!-- class="box-body"-->
</div><!-- class="box box-default"-->

