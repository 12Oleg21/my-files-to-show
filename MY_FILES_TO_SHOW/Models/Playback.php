<?php

namespace app\models\asterisk;

use AGI_AsteriskManager;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\web\UploadedFile;

class Playback extends ActiveRecord
{
    public $upload;
    public $modules;
    public $ext;

    public static function tableName()
    {
        return 'OWN_playbacks';
    }

    public static function primaryKey()
    {
        return ['name'];
    }


    public static function getDb()
    {
        return Yii::$app->get('dbAsterisk');
    }

    public function rules()
    {
        $rules = [
            ['name', 'filter', 'filter' => 'trim'],
            ['name', 'required'],
            ['name', 'string', 'min' => 1, 'max' => 255],
            ['name', 'unique'],
            ['name', 'match', 'pattern' => '/^[a-zA-Z0-9_\s]+$/i', 'message' => 'Please, use only this symbols: A-Z, a-z, 0-9, _'],
            ['description', 'filter', 'filter' => 'trim'],
            [['upload'], 'file', 'maxSize' => 1024 * 1024 * 8, 'skipOnEmpty' => true, 'checkExtensionByMimeType' => true, 'extensions' => 'wav,mp3'],
        ];
        return $rules;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = $scenarios['update'] = $scenarios['default'] = ['name', 'description', 'upload', 'size', 'duration', 'upload_link'];
        return $scenarios;
    }

    /**
     * Function works for save file to directory
     * if all conditions are passed.
     * Convert file to wav format.
     * Besides, it save size and duration into model.
     * Invoked from actionCreate and actionUpdate
     * @return true or false
     */
    public function handleRecordSave($record, $old_name = null): bool
    {
        if ($record->upload) {
            $path = '/var/asterisk/records/';
            $ext = $record->upload->extension;
            $filePath = $path . $record->name . '_.' . $ext;
            if ($record->upload->saveAs($filePath, true)) {
                $this->convert_to_wav($path, $filePath, $ext);
                list($size, $duration) = $this->getParamsFile($record->name, $path);
                $record->size = $size;
                $record->duration = $duration;
                $record->save(false);
                return true;
            }
        } else {
            return !$record->isNewRecord;
        }
        return false;
    }


    /**
     * Define duration audio file and file size
     * Invoked from function handleRecordSave
     * @renurn array with size and duration
     */
    public static function getParamsFile($id, $path): array
    {
        $filePath = $path . $id . '.' . 'wav';
        $fileSize = filesize($filePath) / 1000;
        $duration_tmp = $fileSize / 16;
        $duration = gmdate("i:s", $duration_tmp);
        return [$fileSize, $duration];
    }

    /**
     * Delete audio file when you run actionDelete
     * Invoked from actionDelete
     */
    public function delete_file($id): void
    {
        $file = "/var/asterisk/records/{$id}.wav";
        if (file_exists($file)) unlink($file);
    }


    /**
     * Convert audio file to wav format.
     * Delete temporary file.
     * Invoked from function handleRecordSave
     */
    public function convert_to_wav($path, $filePath, $ext)
    {
        $filePath_out = $path . $this->name . '.' . 'wav';
        if ($ext == 'wav') {
            $command = "sox {$filePath} -r 8000 -c 1 -b 16 {$filePath_out}";
        } elseif ($ext == 'mp3') {
            $command = "lame --decode {$filePath} - | sox -t wav - -t wav -r 8000 -c 1 {$filePath_out}";
        } else {
            return false;
        }
        system($command, $retval);
        if (file_exists($filePath)) unlink($filePath);
    }

    /**
     * Make new link to new file.
     * Delete an old link.
     * Write the new link to database.
     * Invoked from view.php
     * @return real path to resource like this (/web/assets/45yss89jhjdk)
     */
    public function resurse_registering($model, $flag = null)
    {
        $assets_link = $model->getAssetManager()->getBundle('\app\assets\RecordAsset');
        $assets_link->register($model);
        $bundle = $model->getAssetManager()->getBundle('\app\assets\RecordAsset');
        $new_upload_link = $assets_link->basePath;
        $old_upload_link = $this->upload_link;
        if ($old_upload_link != $new_upload_link) {
            if ($flag) $this->delete_old_links($new_upload_link);
            $this->upload_link = $new_upload_link;
            $this->update();
            if (file_exists($old_upload_link)) unlink($old_upload_link);
        }
        return $bundle;
    }

    /**
     * Get old links from database.
     * Delete it from web/assets directory.
     * Invoked from function resource_registering this class
     */
    public function delete_old_links($new_upload_link): void
    {
        $all_records = $this->find()->all();
        if ($all_records) {
            $array_old_links = [];
            foreach ($all_records as $record) {
                if ($record->upload_link != $new_upload_link and $record->upload_link != '') {
                    $array_old_links[] = $record->upload_link;
                }
            }

            if (!empty($array_old_links)) {
                foreach ($array_old_links as $path) {
                    if (file_exists($path)) unlink($path);
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function add_modules_field(): mixed
    {
        $records = $this->find()->all();
        foreach ($records as $rec) {
            $rec->modules = $this->find_where_used($rec->name);
        }
        return $records;

    }

    /**
     * @param $file
     * @return string
     */
    public function find_where_used($file): string
    {
        $modules = '';
        $arr_queues = $this->find_in_queues($file);
        $modules_qu = $this->forming_string_modules($arr_queues, 'Queues');
        $arr_custom_dest = $this->find_in_custom_dest($file);
        $modules_cust = $this->forming_string_modules($arr_custom_dest, 'CustomDestinations');
        $arr_app = $this->find_in_ivrs($file);
        $modules_app = $this->forming_string_modules($arr_app, 'Ivrs');
        if ($modules_qu) $modules = $modules_qu;
        if ($modules_cust) $modules = $modules . $modules_cust;
        if ($modules_app) $modules = $modules . $modules_app;
        return $modules;
    }

    /**
     * Forming a string for modules column
     * if name of module very long then prune it
     * invoked from function find_where_used
     * @param $arr
     * @param $name_module
     * @return string
     */
    public function forming_string_modules($arr, $name_module): string
    {
        $modules = '';
        if (!empty($arr)) {
            $new_array_name = [];
            foreach ($arr as $name) {
                if (isset($name[40])) {
                    $new_name = substr($name, 0, 40) . '..';
                    array_push($new_array_name, $new_name);
                } else {
                    array_push($new_array_name, $name);
                }
            }
            $str = implode(', ', $new_array_name);
            $modules = $modules . "<div> <b>{$name_module}:</b> {$str} </div>";
        }
        return $modules;
    }//function

    /**
     * @param $file
     * @return array
     */
    public function find_in_ivrs($file): array
    {
        $full_name = "/var/asterisk/records/{$file}";

        $result_app = IvrLine::find()->where(['action' => 'intro', 'data' => $full_name])
            ->orWhere(['data' => "play,{$file}"])->all();
        $list_of_app = [];
        if (!empty($result_app)) {
            foreach ($result_app as $app) {
                if (!in_array($app->appname, $list_of_app)) $list_of_app[] = $app->appname;
            }
        }
        return $list_of_app;
    }//function

    /**
     * @param $file
     * @return array
     */
    public function find_in_queues($file): array
    {
        $full_name = "/var/asterisk/records/{$file}";
        $result_qu = \app\models\asterisk\Queue::find()->where(['announce_before_join' => $full_name])
            ->orWhere(['periodic_announce' => $full_name])
            ->orWhere(['queue_callerannounce' => $full_name])
            ->orWhere(['queue_holdtime' => $full_name])
            ->orWhere(['queue_minutes' => $full_name])
            ->orWhere(['queue_quantity1' => $full_name])
            ->orWhere(['queue_quantity2' => $full_name])
            ->orWhere(['queue_reporthold' => $full_name])
            ->orWhere(['queue_thankyou' => $full_name])
            ->orWhere(['queue_thereare' => $full_name])
            ->orWhere(['queue_youarenext' => $full_name])
            ->all();
        $list_of_qu = [];
        if (!empty($result_qu)) {
            foreach ($result_qu as $qu) {
                if (!in_array($qu->name, $list_of_qu)) $list_of_qu[] = $qu->name;
            }
        }
        return $list_of_qu;
    }

    /**
     * @param $file
     * @return array
     */
    public function find_in_custom_dest($file): array
    {
        $s_full_name = "Playback(/var/asterisk/records/{$file})";
        $s_file = "Playback($file)";

        $result_cust = CustomDestinations::find()->andFilterWhere(['like', 'context', $s_full_name])
            ->orFilterWhere(['like', 'context', $s_file])->all();
        $list_of_cust = [];
        if (!empty($result_cust)) {
            foreach ($result_cust as $cust) {
                if (!in_array($cust->name, $list_of_cust)) $list_of_cust[] = $cust->name;
            }
        }
        return $list_of_cust;
    }

    /**
     * @param $file
     * @return bool
     */
    public function check_in_module($file): bool
    {
        $session = Yii::$app->session;
        $message = $this->find_where_used($file);
        if ($message) {
            $message = "The playback $file has not been deleted, because it is in : <br>" . $message;
            $session->setFlash('warning', $message);
            return false;
        }
        return true;
    }

    /**
     * @param $filename
     * @return string
     */
    public function check_filename_format($filename): string
    {
        $tmp_arr = explode(' ', $filename);
        $new_name = implode("_", $tmp_arr);
        return $new_name;
    }

    /**
     * @param $name
     * @param $param_len
     * @return false|string
     */
    static function prune_long_name($name, $param_len = 21): false|string
    {
        $l = strlen($name);
        if ($l > $param_len) {
            $rest = substr($name, 0, $param_len);
            $name = $rest . '...';
        }
        $charset = mb_detect_encoding($name);
        return iconv($charset, 'UTF-8//IGNORE', $name);
    }

    /**
     * @param $extension
     * @param $namerecord
     * @return void
     */
    public function originate_record($extension, $namerecord): void
    {
        $ami = new AGI_AsteriskManager();
        if ($ami->connect()) {
            $ami->Originate("PJSIP/{$extension}", null, null, null, 'Playback', "/var/asterisk/records/{$namerecord}", null, "RECORD<{$namerecord}>");
            $ami->disconnect();
        }
    }
}
