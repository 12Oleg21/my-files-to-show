<?php

use app\models\lte\LteLeftMenu;
use app\models\lte\LteUtils;
use kartik\dialog\Dialog;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\widgets\Breadcrumbs;

?>
<div class="content-wrapper">
    <?php if ($this->title !== Yii::t('stats', 'Dialer dashboard')) { ?>
        <section class="content-header">
            <?php if (isset($this->blocks['content-header'])) { ?>
                <h1><?= $this->blocks['content-header'] ?></h1>
            <?php } else { ?>
                <h1>
                    <?php
                    if ($this->title !== null) {
                        echo Html::encode($this->title); ?>
                        <small><?php echo isset($this->params['small']) ? $this->params['small'] : null; ?></small>
                        <?php
                    } else {
                        echo Inflector::camel2words(
                            Inflector::id2camel($this->context->module->id)
                        );
                        echo ($this->context->module->id !== \Yii::$app->id) ? '<small>Module</small>' : '';
                    } ?>
                </h1>
            <?php } ?>
            <?= Breadcrumbs::widget([
                'encodeLabels' => false,
                'homeLink' => ['label' => '<i class="fa fa-dashboard"></i>' . Yii::t('application', 'Home'), 'url' => (new LteLeftMenu())->getFirstItemLeftMenu()],
                'links' => $this->params['breadcrumbs'] ?? [],
            ]) ?>
        </section>
    <?php } ?>

    <section class="content">
        <!-- The Dialog widget transforms the default confirmation windows to our project style. -->
        <?= Dialog::widget(['overrideYiiConfirm' => true, 'options' => ['size' => Dialog::SIZE_SMALL, 'type' => 'type-primary', 'btnOKClass' => 'btn btn-primary']]) ?>
        <!-- The Alert widget shows notificaton windows -->
        <?php foreach (LteUtils::notifications() as $notification) echo $notification; ?>
        <!-- Show content of page -->
        <?= /** @var string $content */
        $content ?>
    </section>
</div>

<footer class="main-footer">
    <div class="pull-right hidden-xs">
        <b>Version</b> 2.0
    </div>
    <strong>Copyright &copy; <?php echo date("Y"); ?> All rights reserved.
</footer>
