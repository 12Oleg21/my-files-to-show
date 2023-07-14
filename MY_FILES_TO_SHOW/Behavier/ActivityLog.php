<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class ActivityLog extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'activity_log';
    }

    /**
     * Set properties for a log object to save in DB
     * It works with ActivityLogFilter Behavior
     * @param $activity
     * @param $event_name
     * @return void
     */
    public function setProperties($activity, $event_name): void
    {
        $model = $activity->owner;
        $this->action = $event_name;
        $name = $activity->modelName;
        $this->entity_name = $model->$name;
        $key = $activity->modelId;
        $this->entity_id = $model->$key;
        $this->user_id = Yii::$app->user->getId();
        $this->user_name = Yii::$app->user->identity->username;
        $this->success = true;
        $this->setCommonPropertiesAndSaveToDB();
    }

    /**
     * Set common properties for login/logout actions and ActiveRecord actions (afterInsert/afterUpdate)
     * Save model to DB
     * @return void
     */
    public function setCommonPropertiesAndSaveToDB(): void
    {
        $this->ip = Yii::$app->request->userIP;
        $this->url = $this->pruneLongUrl(Yii::$app->request->url);
        $this->browser = $this->getBrowser();
        $this->save();
    }

    /**
     * @param $name
     * @param $param_len
     * @return false|string
     */
    public function pruneLongUrl($name, $param_len = 122): false|string
    {
        $l = strlen($name);
        if ($l > $param_len) {
            $rest = substr($name, 0, $param_len);
            $name = $rest . '...';
        }
        $charset = mb_detect_encoding($name);
        $name = iconv($charset, 'UTF-8//IGNORE', $name);
        return $name;
    }

    /**
     * Set properties for a log object to save in DB
     * It works with ActivityLoginLogout Filter
     */
    public function setLoginLogout($activity, $user_id, $user_name): void
    {
        $this->user_id = $user_id;
        $this->user_name = $user_name;
        $this->action = $activity->id;
        if ($activity->id == 'login') {
            if (Yii::$app->user->isGuest) {
                //UNSUCCESSFULLY LOGIN
                $this->success = false;
            } else {
                // SUCCESSFULLY LOGIN
                $this->success = true;
            }
        }

        if ($activity->id == 'logout') {
            if (Yii::$app->user->isGuest) {
                // SUCCESSFULLY LOGIN
                $this->success = true;
            } else {
                //UNSUCCESSFULLY LOGIN
                $this->success = false;
            }
        }

        $this->setCommonPropertiesAndSaveToDB();
    }

    /**
     * Parse the $_SERVER['HTTP_USER_AGENT'] variable to know the user's browser
     * @return string like this: 'Google Chrome/90.0.4430.93'
     */
    public function getBrowser(): string
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }

        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1];
            }
        } else {
            $version = $matches['version'][0];
        }

        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }

        $browser = array(
            'userAgent' => $u_agent,
            'name' => $bname,
            'version' => $version,
            'platform' => $platform,
            'pattern' => $pattern
        );
        return $browser['name'] . '/' . $browser['version'];
    }

    /**
     * Set properties for a log object to save in DB
     */
    static function writeToActivityLog($model, $name, $key, $action, $success = true): void
    {
        if (!Yii::$app->user->isGuest) {
            $log = new self();
            $log->action = $action;
            $log->entity_name = $model->$name;
            $log->entity_id = $model->$key;
            $log->user_id = Yii::$app->user->getId();
            $log->user_name = Yii::$app->user->identity->username;
            $log->success = $success;
            $log->setCommonPropertiesAndSaveToDB();
        }
    }

}
