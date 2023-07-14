<?php

namespace app\components\widgets;

use app\models\enums\EnquireReasons;
use app\models\forms\EnquireForm;
use app\models\Tutor;
use Yii;
use yii\base\Widget;

class EnquireFormWidget extends Widget
{

    /**
     * @var EnquireForm
     */
    public $model;

    /**
     * @var string
     */
    public $buttonText;

    /**
     * @var Tutor
     */
    public $tutor;


    /**
     * @return string
     */
    public function run()
    {
        return $this->render('//widgets/enquireForm', [
            'model' => new EnquireForm(),
            'buttonText' => $this->buttonText,
            'tutor' => $this->tutor,
            'reasons' => array_combine(EnquireReasons::REASONS, EnquireReasons::REASONS)
        ]);
    }
}
