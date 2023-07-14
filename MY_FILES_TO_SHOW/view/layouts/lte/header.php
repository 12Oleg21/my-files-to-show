<?php

use app\models\lte\LteUtils;
use yii\helpers\Html;
use yii\widgets\ListView;

$application_object = new LteUtils();
$application = $application_object->actualApplication();
$list_menu_items = $application_object->formingTheMainMenu();
list($visible_reload_icons, $visible_asterisk_restart_icon, $commit_link, $rollback_link, $asterisk_restart_link) = $application_object->visible_reload_icons();
?>

<header class="main-header">

    <div class='logo'>
        <a href="#" data-toggle="dropdown" role="button"><img src="/images/app-menu.png" width="16px"
                                                              height="16px"><?= $application->name; ?></a>
        <ul class="dropdown-menu application-menu">
            <?= ListView::widget([
                'dataProvider' => $list_menu_items,
                'itemView' => '_main_menu_list',
                'layout' => "{items}",
            ]);
            ?>
        </ul>
    </div>
    <nav class="navbar navbar-static-top" role="navigation">
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <?php if (!Yii::$app->user->isGuest) { ?>
                    <!-- RELOAD-ROLLBACK BUTTONS -->
                    <li id='header_lte_template_button_Apply' class='dropdown'
                        style="display: <?php echo $visible_reload_icons ?>;">
                        <a href= <?php echo $commit_link ?> title='Apply changes'>
                        <i class='fa fa-refresh fa-lg fa-pulse'></i>
                        <div class='pulse-btn pulse-apply'></div>
                        </a>
                    </li>
                    <li id='header_lte_template_button_Rollback' class='dropdown'
                        style="display: <?php echo $visible_reload_icons ?>;">
                        <a href= <?php echo $rollback_link ?> title='Rollback changes'>
                        <i class='fa fa-times fa-lg'></i>
                        <div class='pulse-btn pulse-rollback'></div>
                        </a>
                    </li>
                    <li id='header_lte_template_button_RestartAsterisk' class='dropdown'
                        style="display: <?php echo $visible_asterisk_restart_icon ?>;">
                        <a href= <?php echo $asterisk_restart_link ?> title="Restart asterisk" data-confirm="Asterisk
                        will be restarted, are you sure?">
                        <i class='fa fa-lg fa-asterisk'></i>
                        <div class='pulse-btn pulse-asterisk'></div>
                        </a>
                    </li>
                    <!-- RELOAD-ROLLBACK BUTTONS ARE ENDED -->
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                            <img src="/images/man-161282_640.png" class="user-image" alt="User Image">
                            <span class="hidden-xs"><?= Yii::$app->user->identity->username ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <!-- User image -->
                            <li class="user-header">
                                <img src="/images/man-161282_640.png" class="img-circle" alt="User Image">

                                <p>
                                    <?= Yii::$app->user->identity->username ?>
                                    <small><?= Yii::$app->user->identity->email ?></small>
                                </p>
                            </li>
                            <li class="user-footer">
                                <div class="pull-right">
                                    <a href="/site/logout" class="btn btn-default btn-flat">Sign out</a>
                                </div>
                            </li>
                        </ul>
                    </li>

                <?php } ?>
            </ul>
        </div>

    </nav>
</header>
