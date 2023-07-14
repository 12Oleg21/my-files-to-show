<?php

use app\models\lte\LteLeftMenu;

$menu = (new LteLeftMenu())->getMenu();
?>
<aside class="main-sidebar">
    <section class="sidebar">
        <?= dmstr\widgets\Menu::widget($menu) ?>
    </section>
</aside>
