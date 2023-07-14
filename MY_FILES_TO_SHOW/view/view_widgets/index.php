<?php
/**
 * @package Webinterface
 * @author Martin Moucka <moucka.m@gmail.com>
 * @license GNU/GPL, see license.txt
 * Webinterface is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * Webinterface is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Webinterface; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 * or see http://www.gnu.org/licenses/.
 */

use app\assets\DataTableLteAsset;
use app\assets\InboundRouteIndexAsset;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\JuiAsset;

DataTableLteAsset::register($this);
JuiAsset::register($this);
InboundRouteIndexAsset::register($this);

$this->title = "Inbound routes";
$this->params['breadcrumbs'][] = $this->title;
$this->params['small'] = 'Destinations for incoming calls.'; //ucfirst(Yii::$app->controller->action->id);

?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Routes configurations</h3>
            </div>
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $list,
                    'id' => 'in_module_id_grid_table',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return $model->disable_bit ? ['class' => 'danger'] : [];
                    },
                    'tableOptions' => ['id' => 'inbound_routes', 'class' => "table table-bordered table-hover"],
                    'layout' => "{items}\n{pager}",
                    'columns' => [
                        [
                            'class' => 'yii\grid\Column',
                            'contentOptions' => ['class' => "sortable-handle text-center vcenter ui-sortable-handle", 'style' => "cursor: move"],
                            'content' => function ($data) {
                                return '<i class="glyphicon glyphicon-move"></i>';
                            }
                        ],
                        [
                            'attribute' => 'metric', 'label' => '#', 'headerOptions' => ['id' => 'inbound_routes_Positions'],
                        ],
                        [
                            'attribute' => 'id', 'headerOptions' => ['id' => 'inbound_routes_Id'],
                        ],
                        [
                            'attribute' => 'name', 'headerOptions' => ['id' => 'inbound_routes_Name'],
                        ],
                        [
                            'attribute' => 'description', 'headerOptions' => ['id' => 'inbound_routes_Description'],
                        ],
                        [
                            'attribute' => 'destination', 'headerOptions' => ['id' => 'inbound_routes_Destination'],
                        ],
                        [
                            'attribute' => 'recording',
                            'format' => 'raw',
                            'headerOptions' => ['id' => 'inbound_routes_Recording'],
                            'value' => function ($model) {
                                return $model->recording ? 'Allow' : 'Deny';
                            }
                        ],
                        [
                            'format' => 'raw',
                            'label' => 'Position',
                            'headerOptions' => ['id' => 'inbound_routes_ChangePosition', 'style' => "width: 70px"],
                            'value' => function ($model) use ($positions) {
                                unset($positions[$model->id]);
                                return Html::dropDownList('position_dropdown', null, $positions, ['style' => "width: 65px", 'prompt' => 'Select', 'onchange' => "changePosition(this)"]);
                            },
                        ],
                        [
                            'class' => 'yii\grid\CheckboxColumn',
                            'header' => 'Disable',
                            'name' => 'disable',
                            'headerOptions' => ['id' => 'inbound_routes_Disable', 'style' => "width: 70px"],
                            'checkboxOptions' =>
                                function ($model, $key) {
                                    $disabled = $model->patterns ? null : 'disabled';
                                    return ['value' => $key, 'checked' => $model->disable_bit ? true : false, 'disabled' => $disabled];
                                },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'headerOptions' => ['style' => "width: 70px"],
                        ],
                    ],
                    'showFooter' => false,
                ]);
                ?>
                <div class='row'>
                    <div class="col-lg-12">
                        <div class="form-group text-center">
                            <?= Html::a('Commit', null, ['class' => 'btn btn-default pull-right', 'style' => "margin-left: 5px;", 'name' => 'inbound-button', 'onclick' => 'SendData()']) ?>
                            <?= Html::a('Create route', ['create'], ['class' => 'btn btn-primary pull-right']) ?>
                        </div>
                    </div><!--div class="col-lg-1"-->
                </div><!--div class="row"-->
            </div><!--div class="box-body"-->
        </div><!--div class="box"-->
    </div><!--div class="col-lg-12"-->
</div><!--div class="row"-->
