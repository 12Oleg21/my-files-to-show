<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\assets\RoutingVisualizationAsset;
use app\assets\InboundRouteFormAsset;

$this->title = $route->name;
$this->params['breadcrumbs'][] = ['label' => 'Inbound routes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['small'] =  'Inbound route - ' . ucfirst(Yii::$app->controller->action->id);
RoutingVisualizationAsset::register($this);
$this->registerJs($inlineScript, $this::POS_HEAD);
InboundRouteFormAsset::register($this);
?>
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

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">Inbound route settings</h3>
    </div>
    <div class="box-body">
        <div class="row">
        <?php $form = ActiveForm::begin(['id' => 'inbound_route_active_form']); ?>
            <div class="col-md-6">
                <div class="form-group">
                    <?= $form->field($route, 'name',['inputOptions' => ['readonly' => true]])->label('Name', ['id' => 'inbound_routes_Name']) ?>
                    <?= $form->field($route, 'description',['inputOptions' => ['readonly' => true]])->label('Description', ['id' => 'inbound_routes_Description']) ?>
                    <?= $form->field($route, 'route_cid',['inputOptions' => ['readonly' => true]])->label('Route Cid', ['id' => 'inbound_routes_Route_Cid']) ?>
                    <?= $form->field($route, 'musiconhold',['inputOptions' => ['readonly' => true]])->label('Musiconhold', ['id' => 'inbound_routes_Musiconhold'])?>
                    <?= $form->field($route, 'destination',['inputOptions' => ['readonly' => true]])->label('Destination', ['id' => 'inbound_routes_Destination'])?>
                </div>
            </div><!-- class="col-md-6"-->
            <div class="col-md-6">
                <div class="form-group">
                    <?= $form->field($route, 'prepend',['inputOptions' => ['readonly' => true]])->label('Prepend', ['id' => 'inbound_routes_Prepend']) ?>
                    <?= $form->field($route, 'length',['inputOptions' => ['readonly' => true]])->label('Length', ['id' => 'inbound_routes_Length']) ?>
                    <?= $form->field($route, 'metric', ['inputOptions' => ['readonly' => true, 'disabled'=>'disabled']])->dropDownList($positions,['prompt'=>'Select position'])->label('Position', ['id' => 'inbound_routes_Position'])?>
                    <?= $form->field($route, 'recording',['inputOptions' => ['readonly' => true]] )->textInput(['value' => $route->recording ? 'Always' : 'Never'])->label('Recording', ['id' => 'inbound_routes_Recording'])?>
                </div><!--div class="form-group"-->
            </div><!-- class="col-md-6"-->
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div id = 'inbound_routes_patternlist' class="panel-heading">
                        <h4>
                            Pattern List
                        </h4>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <?= Html::label('Id', '', ['id' => 'inbound_routes_pattern_Id'])?>
                                    </th>
                                    <th>
                                        <?= Html::label("DID pattern", '', ['id' => 'inbound_routes_pattern_DID']) ?>
                                    </th>
                                    <th>
                                        <?= Html::label("Pool", '', ['id' => 'inbound_routes_pattern_Pool']) ?>
                                    </th>
                                    <th>
                                        <?= Html::label("CID pattern", '', ['id' => 'inbound_routes_pattern_CID']) ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="container-items">
                            <?php if(!empty($patternlist)) {
                                foreach ($patternlist as $i => $entry): ?>
                                    <tr class="item">
                                        <td>
                                            <?= $form->field($entry, "[{$i}]id", ['inputOptions' => ['readonly' => true]])->textInput(['class' => 'id_route_dynamicform'])->label(false); ?>
                                        </td>
                                        <td>
                                            <?= $form->field($entry, "[{$i}]did_pattern", ['inputOptions' => ['readonly' => true]])->label(false) ?>
                                        </td>
                                        <td>
                                            <?= $form->field($entry, "[${i}]OWN_pool_id", ['inputOptions' => ['readonly' => true]])->textInput(['value' => $entry->pool ? $entry->pool->name : ''])->label(false) ?>
                                        </td>
                                        <td>
                                            <?= $form->field($entry, "[{$i}]cid_pattern", ['inputOptions' => ['readonly' => true]] )->label(false) ?>
                                        </td>
                                    </tr> 
                                <?php endforeach; } ?>
                            </tbody>
                        </table>
                    </div>
                </div><!-- .panel -->
            </div><!-- class="col-md-12"-->
        </div><!--div class="row"-->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <?= Html::a('Route Graph', ['#'] ,['class' => 'btn btn-default', 'data-toggle' => "modal", 'backdrop' => "static", 'data-target' => "#graphModal"])?>
                    <?= Html::a('Cancel', ['index'] ,['class' => 'btn btn-default pull-right', 'style' => "margin-left: 5px;"])?>
                    <?= Html::a('Edit', ['update', 'id' => $route->id], ['class' => 'btn btn-primary pull-right']) ?>
                </div>
            </div><!-- class="col-md-12"-->
        </div><!--div class="row"-->
        <?php ActiveForm::end() ?>
    </div><!-- class="box-body"-->
</div><!--div class="box box-default"-->
