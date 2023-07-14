<?php

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */


if (class_exists('backend\assets\AppAsset')) {
    backend\assets\AppAsset::register($this);
} else {
    app\assets\AppAsset::register($this);
}

dmstr\web\AdminLteAsset::register($this);
$directoryAsset = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<?php $this->beginBody() ?>
<div class="wrapper">

    <?= $this->render(
        'lte/header.php',
        ['directoryAsset' => $directoryAsset]
    ) ?>

    <?= $this->render(
        'lte/left.php',
        ['directoryAsset' => $directoryAsset]
    )
    ?>

    <?= $this->render(
        'lte/content.php',
        ['content' => $content, 'directoryAsset' => $directoryAsset]
    ) ?>

</div>
<?php $this->endBody() ?>
<?php
// TOOLTIP - start
if (isset(Yii::$app->params["tooltip-show"]) && Yii::$app->params["tooltip-show"] === true) {
    // read by config
    $url = Yii::$app->params["tooltip-url"] ?? null;
    $port = Yii::$app->params["tooltip-port"] ?? null;
    $language = Yii::$app->params["tooltip-language"] ?? null;

    // check values
    if ($url == null || $url == "") {
        $url = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['SERVER_NAME']; // server IP
    }
    if ($port == null || $port == "") {
        $port = 8666; // default port
    }
    if ($language == null || $language == "") {
        $language = Yii::$app->language;
    }

    // values
    $tooltipUrl = $url . ($port == 80 ? "" : ":" . $port);
    $thisUrl = Yii::$app->request->url . ($language == "en" ? "" : "&language=" . $language);
    $application = explode('/', $thisUrl)[1];
    $tooltipDataUrl = $tooltipUrl . "/$application" . $thisUrl;
    echo "\n<script type=\"text/javascript\" src=\"" . $tooltipUrl . "/tooltipster/dist/js/tooltipster.bundle.min.js\"></script>";
    echo "\n<script type=\"text/javascript\" src=\"" . $tooltipDataUrl . "\"></script>";
    echo "\n<script>";
    echo "\nvar tooltipUrl = '" . $tooltipDataUrl . "';\n";
    echo "\n</script>";
}
// TOOLTIP - end
?>
</body>
</html>
<?php $this->endPage() ?>

