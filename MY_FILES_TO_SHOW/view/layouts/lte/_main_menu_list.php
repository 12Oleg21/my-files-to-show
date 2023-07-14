<?php

use yii\helpers\Html;

?>
<?php foreach ($model as $item) { ?>
    <div class="col-xs-6 text-center application-menu-item">
        <a href="<?= $item->path ?>">
            <div class="info-box">
            <span class="info-box-icon">
              <i class="fa fa-<?= $item->icon ?>"></i>
              <span class="text"><?= $item->name ?></span>
            </span>
            </div>
        </a>
    </div>
<?php } ?>