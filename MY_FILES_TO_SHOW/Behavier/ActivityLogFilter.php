<?php

namespace app\models;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

class ActivityLogFilter extends Behavior
{

    public $modelId = 'id';
    public $modelName = 'name';

    /**
     * @return string[]
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterChanges',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterChanges',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterChanges',
        ];
    }

    /**
     * @param $event
     * @return void
     */
    public function afterChanges($event): void
    {
        if (!Yii::$app->user->isGuest) {
            $log = new ActivityLog();
            $log->setProperties($this, $event->name);
        }
    }
}
