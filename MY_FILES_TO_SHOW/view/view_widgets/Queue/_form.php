<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use kartik\widgets\TimePicker;
use kartik\widgets\ActiveForm;
use kartik\date\DatePicker;
use yii\grid\GridView;
use app\assets\DataTableLteAsset;
use app\assets\DialerCampaignsFormAsset;
use app\models\dialer\DialerMethod;
use app\models\dialer\TimeRanges;
use app\models\dialer\Route;

$this->title = $model->isNewRecord ? Yii::t('dialer', 'New campaign') : $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('dialer', 'Campaigns'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['small'] =  Yii::t('dialer', 'Campaigns') . ' - ' . Yii::t('dialer', ucfirst(Yii::$app->controller->action->id));

DataTableLteAsset::register($this);
DialerCampaignsFormAsset::register($this);
?>
<div class="box box-default">
    <div class="box-header">
        <h3 class="box-title"><?php echo Yii::t('dialer', 'Campaign settings') ?></h3>
    </div>
    <div class="panel-body" >
        <div class="row">
            <?php $form = ActiveForm::begin(['id' => 'from-updatequeues','enableAjaxValidation' => true]);//'enableClientValidation' => true]); ?>
                <div class="col-md-6">
                    <div class="box">
                        <div class="box-header with-border">
                          <h3 class="box-title"><?php echo Yii::t('dialer', 'Main settings') ?></h3>
                        </div>
                        <div class="box-body">
                            <?= $form->field($model, 'name')->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Name'), ['id' => 'campaignName'])?>
                            <?= $form->field($model, 'description')->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Description'), ['id' => 'campaignDescription'])?>

                            <?= $form->field($model, 'DIALER_route_id')->dropDownList(ArrayHelper::map(Route::find()->all(),'id','name'),
                                ['prompt'=>Yii::t('dialer', 'Select route')])->label(Yii::t('dialer', 'Routes'), ['id' => 'campaignRoute'])?>
                            <?= $form->field($model, 'destination_override')->dropdownlist($destinations,
                                ['prompt'=>Yii::t('dialer', 'Select Destination'), 'options' => $options])->label(Yii::t('dialer', 'Override Destination'), ['id' => 'campaignOverride'])?>
                            <?php if($model->isNewRecord){ ?>
                                <?= $form->field($case_list, 'file_name')->dropDownList($list->allModels,
                                    ['prompt'=>Yii::t('dialer', 'Select number list')])->label(Yii::t('dialer', 'Number list'), ['id' => 'campaignNumberList'] );?>
                            <?php }?>
                         </div><!-- class="box-body" -->
                    </div><!-- class="box" -->
                </div><!-- class="col-md-6"-->
                <div class="col-md-6">
                    <div class="box collapsed-box">
                        <div class="box-header with-border">
                          <h3 class="box-title"><?php echo Yii::t('dialer', 'Dialing algorithm') ?></h3>
                          <div class="box-tools pull-right">
                              <?= Html::button('<i class="fa fa-plus"></i>', ['class' => "btn btn-box-tool", 'data-widget'=>"collapse"]) ?>
                          </div>
                        </div>
                        <div class="box-body" style="display: none;">
                            <?php $dialer_type_all = DialerMethod::listAllType();?>
                            <?= $form->field($model, 'DIALER_method_id')->dropDownList($dialer_type_all,
                                    [
                                        'prompt'=>Yii::t('dialer', 'Select dialer method'),
                                        'onclick' => 'hideShow($(this).val())',
                                    ])->label(Yii::t('dialer', 'Dialer method'), ['id' => 'campaignDialerMethod'])?>
                            <?php $abandonments = DialerMethod::listAbandonment();?>
                            <div id='hiddenSuper' <?php if($model->DIALER_method_id != 2) echo 'style: hidden';?>>
                                <?= $form->field($model, 'superacceptable')
                                    ->dropDownList($abandonments, ['options' => ['0.1' =>['Selected'=>true]]])
                                    ->label(Yii::t('dialer', 'Acceptable Abandonment Rate'),  ['id' => 'campaignAcceptableAbandonmentRate'])?>
                            </div><!-- div id='hiddenSuper'-->
                            <div id='hiddenConstant' <?php if($model->DIALER_method_id != 3) echo 'style: hidden';?>>
                                <?= $form->field($model, 'constantdelay')
                                        ->textInput()->hint('Input call delay')
                                        ->label(Yii::t('dialer', 'Call delay'), ['id' => 'campaignCallDelay']);?>
                                <?= $form->field($model, 'constantwaitidle')->dropDownList([0=>'No', 1=>'Yes'])
                                        ->label(Yii::t('dialer', 'Wait for idle agent'), ['id' => 'campaignWaitForIdleAgent']);?>
                                <?= $form->field($model, 'constantmaxhold')
                                        ->textInput()
                                        ->label(Yii::t('dialer', 'Stop calling when N on hold'), ['id' => 'campaignStopCallingWhen']);?>
                            </div><!-- div id='hiddenConstant'-->
                            <?= $form->field($model, 'max_calls')->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Concurrent calls'), ['id' => 'campaignMaxCalls']);?>
                        </div><!-- class="box-body" -->
                    </div><!-- class="box" -->
                    <div  class="box collapsed-box">
                        <div class="box-header with-border">
                          <h3 class="box-title"><?php echo Yii::t('dialer', 'Redial settings') ?></h3>
                          <div class="box-tools pull-right">
                              <?= Html::button('<i class="fa fa-plus"></i>', ['class' => "btn btn-box-tool", 'data-widget'=>"collapse"]) ?>
                          </div>
                        </div>
                        <div class="box-body" style="display: none;">
                            <?= $form->field($model, 'retry_interval')->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Minutes between redial attempts'), ['id' => 'campaignMinuteBetween']);?>
                            <?= $form->field($model, 'retry_num')->textInput(['maxlength' => true])->label(Yii::t('dialer', 'Redial attempts'), ['id' => 'campaignRedialAttempts']);?>
                        </div><!-- class="box-body" -->
                    </div><!-- class="box" -->
                    <div  class="box collapsed-box">
                        <div class="box-header with-border">
                          <h3 class="box-title"><?php echo Yii::t('dialer', 'Date settings') ?></h3>
                          <div class="box-tools pull-right">
                              <?= Html::button('<i class="fa fa-plus"></i>', ['class' => "btn btn-box-tool", 'data-widget'=>"collapse"]) ?>
                          </div>
                        </div>
                        <div class="box-body" style="display: none;">
                            <div class="row" id="extensions_row">
                                <div class="col-lg-12">
                                            <?= $form->field($model, 'dates')->hiddenInput(['id' => 'dateInput'])->label(false)?>
                                                <?= GridView::widget([
                                                      'dataProvider' => $listtimes,
                                                      'layout' => "{items}\n{pager}",
                                                      'showHeader' => true,
                                                      'columns' => [
                                                        ['attribute'=>'date','label' => Yii::t('dialer', 'Date'), 'headerOptions' => ['id' => 'campaignDate']],
                                                        ['attribute'=>'start','label' => Yii::t('dialer', 'Start'), 'headerOptions' => ['id' => 'campaignStart'], 'value'=>function($model){return substr($model['start'], 0, 5);}],
                                                        ['attribute'=>'stop','label' => Yii::t('dialer', 'Stop'), 'headerOptions' => ['id' => 'campaignStop'], 'value'=>function($model){return substr($model['stop'], 0, 5);}],
                                                        [
                                                          'class' => 'yii\grid\ActionColumn',
                                                          'template' => '{delete}',
                                                          'headerOptions' => ['style' => "width:30px"],
                                                          'buttons' => [
                                                            'delete' => function ($url,$model) {
                                                                return Html::button('<i class="glyphicon glyphicon-minus"></i>', ['class' => 'btn btn-danger btn-xs', 'name' => 'add-button' ]);
                                                              }
                                                          ],
                                                        ],
                                                    ],
                                                    'showOnEmpty' => true,
                                                    'id' => 'extensionsview',
                                                    'tableOptions' => [
                                                        'class' => 'table table-bordered table-hover',
                                                        'id' => 'table_time',
                                                      ],
                                                    ]);
                                                ?>
                                                <table class="table">
                                                    <tbody class="container-items">
                                                        <tr>
                                                            <td>
                                                                <?= $form->field($time_range, 'date')->widget(DatePicker::classname(), [
                                                                    'name' => 'date',
                                                                    'readonly'=> true,
                                                                    'size'=>'sm',//lg, md, sm
                                                                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                                                    'pluginOptions' =>[
                                                                        'format' => 'yyyy-mm-dd',
                                                                        'multidate' => true,
                                                                        'multidateSeparator' => ';',
                                                                        'todayHighlight' => true,
                                                                    ]
                                                                  ])->label(Yii::t('dialer', 'Date'), ['id' => 'campaignDateAdd']);
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?= $form->field($time_range, 'start')->widget(TimePicker::classname(), [
                                                                      'readonly'=> true,
                                                                      'size'=>'sm',//lg, md, sm
                                                                      'pluginOptions' => [
                                                                          'showSeconds' => false,
                                                                          'showMeridian' => false,
                                                                          'minuteStep' => 1,
                                                                          'secondStep' => 5,
                                                                          ]
                                                                  ])->label(Yii::t('dialer', 'Start'), ['id' => 'campaignStartAdd'])
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?= $form->field($time_range, 'stop')->widget(TimePicker::classname(), [
                                                                      'readonly'=> true,
                                                                      'size'=>'sm',//lg, md, sm
                                                                      'pluginOptions' => [
                                                                          'showSeconds' => false,
                                                                          'showMeridian' => false,
                                                                          'minuteStep' => 1,
                                                                          'secondStep' => 5,
                                                                          ]
                                                                  ])->label(Yii::t('dialer', 'Stop'), ['id' => 'campaignStopAdd'])
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <?= Html::label(Yii::t('dialer', 'Add'), '', ['id' => 'campaignAdd'])?>
                                                                </div>
                                                                <button type="button" class="btn btn-primary btn-sm pull-right"
                                                                   onclick = 'addTimeline()'><i class="glyphicon glyphicon-plus"></i></button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                </div><!--div class="col-lg-12"-->
                        </div><!-- class="box-body" -->
                    </div><!-- class="box" --> 
                </div><!-- class="col-md-6"-->
                <div class="form-group">
                    <?= Html::a(Yii::t('dialer', 'Cancel'), ['index'], ['class' => 'btn btn-default pull-right', 'style' => "margin-left: 5px;"]) ?>
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('dialer', 'Create') : Yii::t('dialer', 'Update'), ['id' => 'buttonSubmit', 'class' => 'btn btn-primary pull-right', 'name' => 'update-button']) ?>
                </div>
            </div><!--div class="row" -->
            <?php ActiveForm::end() ?>
        </div> <!-- class="row" -->
    </div><!-- class="box-body"-->
</div><!-- class="box box-default"-->


