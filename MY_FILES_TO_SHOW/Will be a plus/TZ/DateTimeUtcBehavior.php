<?php

/*
 * Saves datetime/timestamp columns in UTC timezone,
 * reads in user native timezone
 *
 * @author Alex Makhorin
 */

class DateTimeUtcBehavior extends CActiveRecordBehavior
{

    /**
     * @var array
     * List of datetime fields
     */
    public $dateTimeFields;

    /**
     * @var string
     * Timezone field name
     */
//    public $timezoneExpression;

    const TIMEZONE_UTC = 'UTC';
    const DATE_FORMAT = 'Y-m-d H:i:s';
    const MODE_CREATE = 'createAttribute';
    const MODE_UPDATE = 'updateAttribute';
    const MODE_NORMAL = 'normal';

//    const DEFAULT_TIMEZONE = 'Australia/Sydney';

    /**
     * 1. Create DateTime object in user's Timezone using given time
     * 2. Convert it to UTC Timezone
     * 3. Save to DB.
     *
     * @param CModelEvent $event event parameter
     * @throws Exception
     */
    public function beforeValidate($event): void
    {
        foreach ($this->dateTimeFields as $field) {

            $dateFormat = $field['dateFormat'] ?? Yii::app()->params['dateFormats']['sqlDateTime'];

            if ($this->owner->isNewRecord && isset($field['mode']) && $field['mode'] == self::MODE_CREATE) {
                $date = new DateTime('now', new DateTimeZone(self::TIMEZONE_UTC));
                $this->owner->{$field['name']} = $date->format($dateFormat);
            } elseif (!$this->owner->isNewRecord && isset($field['mode']) && $field['mode'] == self::MODE_UPDATE) {
                $date = new DateTime('now', new DateTimeZone(self::TIMEZONE_UTC));
                $this->owner->{$field['name']} = $date->format($dateFormat);
            } else {
                $date = DateTime::createFromFormat('d/m/y', $this->owner->{$field['name']});
                if (!$date) {
                    $date = new DateTime($this->owner->{$field['name']}, new DateTimeZone($this->timeZone));
                }
                $date->setTimezone(new DateTimeZone(self::TIMEZONE_UTC));
                $this->owner->{$field['name']} = $date->format($dateFormat);
            }
        }
    }

    /**
     * 1. Create DateTime object in UTC Timezone using time from DB
     * 2. Convert it to user's Timezone
     * 3. Return datetime.
     *
     * @param CModelEvent $event event parameter
     * @throws Exception
     */
    public function afterFind($event): void
    {
        foreach ($this->dateTimeFields as $field) {
            $dateFormat = $field['dateFormat'] ?? Yii::app()->params['dateFormats']['sqlDateTime'];

            $date = new DateTime($this->owner->{$field['name']}, new DateTimeZone(self::TIMEZONE_UTC));
            $date->setTimezone(new DateTimeZone($this->timeZone));
            $this->owner->{$field['name']} = $date->format($dateFormat);
        }
    }

    public function getTimeZone()
    {
        return (Yii::app() instanceof CConsoleApplication or !isset(Yii::app()->user->timezone)) ? Yii::app()->params['defaulTimezone'] : Yii::app()->user->timezone;
    }

}
