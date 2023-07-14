<?php
use app\models\statistics\PassedCalls;
use app\models\statistics\StatsUtils;

app\assets\StatisticsAsset::register($this);

$this->title = Yii::t('stats', 'Passed calls');
$this->params['breadcrumbs'] = [Yii::t('stats', 'Statistics'), $this->title,];
$this->params['addTableJ'] = true;
$this->params['small'] = Yii::t('stats', 'Records of missed calls, i.e. calls terminated by the caller before being connected');

?>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?php echo Yii::t('stats', 'Passed calls records') ?></h3>
                <div id="reservation" class="selectbox pull-right tt-calendar" style="padding-left: 10px; cursor: pointer">
                    <i class="fa fa-calendar"></i>
                    <span></span> <b class="caret"></b>
                </div>
            </div>
            <div class="box-body">
                <div>
                <table id="datatable" class="table table-striped table-hover table-condensed">
                    <thead>
                        <tr>
                            <th class="wh-abonent"><?php echo Yii::t('stats', 'Abonent') ?></th>
                            <th class="wh-time"><?php echo Yii::t('stats', 'Time of passed call') ?></th>
                            <th class="wh-count"><?php echo Yii::t('stats', 'Count') ?></th>
                            <th class="wh-last-contact"><?php echo Yii::t('stats', 'Time of the last contact') ?></th>
                            <th class="wh-grouping"><?php echo Yii::t('stats', 'Advanced grouping') ?></th>
                            <th class="wh-status" width="250px"><?php echo Yii::t('stats', 'Status') ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="wh-abonent-filter">
                                <input id="abonent" style="width:auto" ><br>
                                <div class='icheck-primary'>
                                    <input type='checkbox' id="abonent_strict" style="width:auto"> <label for="abonent_strict"><?php echo Yii::t('stats', 'strict') ?></label>
                                </div>
                            </th>
                            <th class="wh-time-filter"></th>
                            <th class="wh-count-filter"></th>
                            <th class="wh-last-contact-filter"></th>
                            <th class="wh-grouping-filter">
                                <input id="group" style="width:auto" ><br>
                                <div class='icheck-primary'>
                                    <input type='checkbox' id="group_strict" style="width:auto"> <label for="group_strict"><?php echo Yii::t('stats', 'strict') ?></label>
                                </div>
                            </th>
                            <th class="wh-status-filter"><select id="select_status" class="form-control" style="padding: 0px; line-height: 18px; height: 26px"></select></th>
                        </tr>
                    </tfoot>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade bs-example-modal-ku" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-ku">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title" id="myModalLabel"><?php echo Yii::t('stats', 'Details') ?> <span class='js-detail-number'></span></h4>
            </div>
            <div class="modal-body">
                <table class="table table-hover table-detail">
                    <tr>
                        <th></th>
                        <th><?php echo Yii::t('stats', 'date') ?></th>
                        <th><?php echo Yii::t('stats', 'callback') ?></th>
                        <th><?php echo Yii::t('stats', 'abonent') ?></th>
                        <th><?php echo Yii::t('stats', 'disposition') ?></th>
                        <th><?php echo Yii::t('stats', 'did') ?></th>
                        <th><?php echo Yii::t('stats', 'duration') ?></th>
                        <th><?php echo Yii::t('stats', 'duration bill') ?></th>
                        <th><?php echo Yii::t('stats', 'status') ?></th>
                    </tr>
                    <tbody class='detail-tb'>

                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo Yii::t('stats', 'Close') ?></button>
            </div>

        </div>
    </div>
</div>
<?php

$jsData = [];
$jsData["pickerDateFrom"] = $filter->date_from;
$jsData["pickerDateTo"] = $filter->date_to;
$jsData["pickerUrl"] = "index";
$jsData["filter"] = $filter;
$jsData["jsFile"] = __DIR__ . "/../../../web/" . "/stats/passed-calls/index.js";
$jsData['choose_advanced_group_text'] = Yii::t('stats', 'Choose advanced grouping');
$jsData['mode_text'] = Yii::t('stats', 'mode');
$jsData['text_refresh'] = Yii::t('stats', 'Refresh');


echo $this->render(
    '../../layouts/lte/_stats_js.php',
    ['jsData' => $jsData]
);
