<?php

use app\assets\InboundRouteFormAsset;
use app\assets\RoutingVisualizationAsset;
use app\models\asterisk\Pool;
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = $route->isNewRecord ? 'New route' : $route->name;
$this->params['breadcrumbs'][] = ['label' => 'Inbound routes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['small'] = 'Inbound route - ' . ucfirst(Yii::$app->controller->action->id);
RoutingVisualizationAsset::register($this);
if (!$route->isNewRecord) {
    $this->registerJs($inlineScript, $this::POS_HEAD);
    InboundRouteFormAsset::register($this);
}
?>
<?php if (!$route->isNewRecord) { ?>
    <!-- START MODAL WINDOW-->
    <div class="modal fade" id="graphModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="panel panel-default">
                        <div class="panel-heading"><b>Current</b></div>
                        <div class="panel-body" id="currentGraph" style="height: 480px;"></div>
                    </div>
                    <?php if ($aftercommit) { ?>
                        <div class="panel panel-default">
                            <div class="panel-heading"><b>After Commit</b></div>
                            <div class="panel-body" id="afterCommitGraph" style="height: 480px;"></div>
                        </div>'
                    <?php } ?>
                </div><!--div class="modal-body"-->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- STOP MODAL WINDOW -->
<?php } ?>
<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">Inbound route settings</h3>
        <?php if (!$route->isNewRecord) { ?>
            <?= Html::a('Copy', ['create', 'id' => $route->id], ['class' => 'btn btn-default pull-right', 'style' => "margin-left: 5px;"]) ?>
            <?= Html::a('Route Graph', ['#'], ['class' => 'btn btn-primary pull-right', 'data-toggle' => "modal", 'backdrop' => "static", 'data-target' => "#graphModal"]) ?>
        <?php } ?>
    </div>
    <div class="box-body">
        <div class="row">
            <?php $form = ActiveForm::begin(['id' => 'dynamic-form', 'enableAjaxValidation' => true]); ?>
            <div class="col-md-6">
                <div class="form-group">
                    <?= $form->field($route, 'name')->textInput(['maxlength' => true])->label('Name', ['id' => 'inbound_routes_Name']) ?>
                    <?= $form->field($route, 'description')->textInput(['maxlength' => true])->label('Description', ['id' => 'inbound_routes_Description']) ?>
                    <?= $form->field($route, 'route_cid')->textInput(['maxlength' => true, 'placeholder' => '${CALLERIDNAME}<${CALLERID}>'])->label('Route CID', ['id' => 'inbound_routes_RouteCID']) ?>
                    <?= $form->field($route, 'musiconhold')->label('Music On Hold', ['id' => 'inbound_route_Musiconhold'])->dropDownList($mohs, ['prompt' => 'Select MOH']) ?>
                    <?= $form->field($route, 'destination')->dropDownList($destinations, ['options' => $options])->label('Destination', ['id' => 'inbound_routes_Destination']) ?>
                </div>
            </div><!-- class="col-md-6"-->
            <div class="col-md-6">
                <div class="form-group">
                    <?= $form->field($route, 'prepend')->textInput(['maxlength' => true])->label('Prepend', ['id' => 'inbound_routes_Prepend']); ?>
                    <?= $form->field($route, 'length')->textInput(['maxlength' => true])->label('Max.length', ['id' => 'inbound_routes_Length']); ?>
                    <?= $form->field($route, 'metric')->dropDownList($positions, ['prompt' => 'Select position'])->label('Position', ['id' => 'inbound_routes_Position']) ?>
                    <?= Html::label("Recording", '', ['id' => 'inbound_routes_Recording']) ?>
                    <?= $form->field($route, 'recording')->radioList(['1' => 'Allow', '0' => 'Deny'],
                        [
                            'class' => 'btn-group',
                            'data-toggle' => 'buttons',
                            'unselect' => null,
                            'item' => function ($index, $label, $name, $checked, $value) {
                                return '<label class="btn btn-default' . ($checked ? ' active' : '') . '">' .
                                    Html::radio($name, $checked, ['value' => $value, 'class' => 'project-status-btn']) . $label . '</label>';
                            },
                        ])->label(false);
                    ?>
                </div><!--div class="form-group"-->
            </div><!-- class="col-md-6"-->
            <div class="col-md-12">
                <?php
                DynamicFormWidget::begin([
                    'widgetContainer' => 'dynamicform_wrapper', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                    'widgetBody' => '.container-items', // required: css class selector
                    'widgetItem' => '.item', // required: css class
                    'limit' => 999, // the maximum times, an element can be added (default 999)
                    'min' => 0, // 0 or 1 (default 1)
                    'insertButton' => '.add-item', // css class
                    'deleteButton' => '.remove-item', // css class
                    'model' => $patternlist[0],
                    'formId' => 'dynamic-form',
                    'formFields' => [
                        'did_pattern',
                        'cid_pattern',
                    ],
                ]);
                ?>

                <div class="panel panel-default">
                    <div id='inbound_routes_patternlist' class="panel-heading">
                        <h4>
                            Pattern List
                        </h4>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <?php if (!$route->isNewRecord) { ?>
                                    <th>
                                        <?= Html::label('Id', '', ['id' => 'inbound_routes_pattern_Id']) ?>
                                    </th>
                                <?php } ?>
                                <th>
                                    <?= Html::label("DID pattern", '', ['id' => 'inbound_routes_pattern_DID']) ?>
                                </th>
                                <th>
                                    <?= Html::label("Pool", '', ['id' => 'inbound_routes_pattern_Pool']) ?>
                                </th>
                                <th>
                                    <?= Html::label("CID pattern", '', ['id' => 'inbound_routes_pattern_CID']) ?>
                                </th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody class="container-items">
                            <?php foreach ($patternlist as $i => $entry): ?>
                                <tr class="item">
                                    <?php
                                    // necessary for update action.
                                    if (!$entry->isNewRecord) {
                                        echo Html::activeHiddenInput($entry, "[{$i}]id");
                                    }
                                    ?>
                                    <?php if (!$route->isNewRecord) { ?>
                                        <td>
                                            <?= $form->field($entry, "[{$i}]id", ['inputOptions' => ['readonly' => true]])->textInput(['class' => 'id_route_dynamicform'])->label(false); ?>
                                        </td>
                                    <?php } ?>
                                    <td>
                                        <?= $form->field($entry, "[{$i}]did_pattern")->label(false) ?>
                                    </td>
                                    <td>
                                        <?= $form->field($entry, "[${i}]OWN_pool_id")->dropDownList(ArrayHelper::map(Pool::listAll(), 'id', 'name'), ['prompt' => 'Select pool of numbers'])->label(false) ?>
                                    </td>
                                    <td>
                                        <?= $form->field($entry, "[{$i}]cid_pattern")->label(false) ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="remove-item btn btn-danger btn-xs"><i
                                                    class="glyphicon glyphicon-minus"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="<?= $route->isNewRecord ? 3 : 4 ?>"></td>
                                <td class="text-center">
                                    <button type="button" class="add-item btn btn-success btn-xs"><i
                                                class="glyphicon glyphicon-plus"></i></button>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div><!-- .panel -->
                <?php DynamicFormWidget::end(); ?>
            </div><!-- class="col-md-12"-->
        </div><!--div class="row"-->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-default pull-right', 'style' => "margin-left: 5px;"]) ?>
                    <?= Html::submitButton($route->isNewRecord ? 'Create' : 'Update', ['class' => 'btn btn-primary pull-right']) ?>
                </div>
            </div><!-- class="col-md-12"-->
        </div><!--div class="row"-->
        <?php ActiveForm::end() ?>
    </div><!-- class="box-body"-->
</div><!--div class="box box-default"-->
