<?php

use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\forms\TestimonialForm */
/* @var $form ActiveForm */
/* @var $buttonText string */
/* @var $tutor \app\models\Tutor */
/* @var $reasons array */

?>
<?php Modal::begin([
    'header' => "<h2>Enquire about {$tutor->firstName}</h2>",
    'toggleButton' => ['label' => $buttonText],
]); ?>

<?php $form = ActiveForm::begin([
    'id' => 'enquire-form',
    'action' => Url::to(['site/add-enquire-handler']),
    'method' => 'POST',
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['site/add-enquire-validation'])
]); ?>
<?= $form
    ->field($model, 'tutorId')
    ->hiddenInput([
        'value' => $tutor->id,
        'class' => 'form-control'
    ])
    ->label(false); ?>

<?= $form->field($model, 'name')->textInput(['class' => 'form-control']); ?>
<?= $form->field($model, 'phone')->textInput(['class' => 'form-control']); ?>
<?= $form->field($model, 'email')->textInput(['class' => 'form-control']); ?>

<?= $form->field($model, 'request')->dropDownList($reasons, ['class' => 'form-control']); ?>

    <div class="form-group submit-btn">
        <?= Html::submitButton('Send', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>

<?php
$js = <<<JS
$('#enquire-form').on('beforeSubmit', function () { 
    // useproof START
    $.get("https://some_dns/dashboard/useproof-create-enquire-event.php?name=" + document.getElementById("enquireform-name").value + "&email=tutor-page-" + document.getElementById("enquireform-email").value);
    // useproof END
    return true;
});
JS;

$this->registerJs($js);
?>

<?php Modal::end(); ?>