<?php

namespace app\models\asterisk;

use app\models\dialer\Route;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Model used for listing and uploading record files.
 *
 * @author Martin Moucka <moucka.m@gmail.com>
 */
class RecordMoh extends Model
{
    /**
     *
     * @var file File to be uploaded.
     */
    public $id;
    public $file;

    public $name;
    public $size;
    public $duration;

    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'trim'],
            ['name', 'string', 'min' => 3, 'max' => 255],
            [['file'], 'file',
                'maxSize' => 4048 * 4048 * 4048,
                'skipOnEmpty' => false,
                'uploadRequired' => 'Please, select a file to upload',
                'checkExtensionByMimeType' => true,
                'extensions' => 'wav,mp3'],
        ];
        return $rules;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['upload'] = ['file'];
        return $scenarios;
    }

    /**
     * Return list of all files stored in "/var/asterisk/records/"
     * @return array
     */
    public function listAllRecords(): array
    {
        $list = [];
        $files = FileHelper::findFiles('/var/asterisk/records/', ['recursive' => true]);
        foreach ($files as $value) {
            $file = basename($value);
            $size = filesize($value) / 1000;
            $list[] = ['id' => $file, 'size' => $size];
        }
        return $list;
    }

    /**
     * @param $id
     * @return array
     */
    public function listAllMohFiles($id): array
    {
        $filelist = [];
        $files = FileHelper::findFiles('/var/asterisk/moh/' . $id, ['recursive' => true]);
        foreach ($files as $value) {
            $model = new RecordMoh;
            $file = basename($value);
            $size = filesize($value) / 1000;
            $duration_tmp = $size / 16;
            $duration = gmdate("i:s", $duration_tmp);
            $model->id = $file;
            $model->name = $file;
            $model->size = $size;
            $model->duration = $duration;
            array_push($filelist, $model);
        }
        return $filelist;
    }

    /**
     * @return string[]
     */
    public function getListAllMohsDefault(): array
    {
        $mohs = $this->listAllMohs();
        $moharray = ArrayHelper::index($mohs, 'id');
        $moharray = ArrayHelper::getColumn($moharray, 'id');
        $mohs = array('default' => 'default') + $moharray;
        return $mohs;
    }

    /**
     * @return array
     */
    public function listAllMohs(): array
    {
        $path = '/var/asterisk/moh/';
        $entries = array_diff(scandir($path), array('..', '.'));
        $list = [];
        $moh = new Musiconhold();
        $all_mohclass = $moh->find()->select(['name', 'description'])->where('name != :default', [':default' => 'default'])->asArray()->all();
        $new_entries = [];
        if (!empty($entries) and $all_mohclass) {
            foreach ($all_mohclass as $mohclass) {
                if (in_array($mohclass['name'], $entries)) array_push($new_entries, $mohclass);
            }
        }
        foreach ($new_entries as $value) {
            if (is_dir($path . $value['name'])) {
                $file = $value['name'];
                $files = count(array_diff(scandir($path . $value['name']), array('..', '.')));
                $modules = $this->find_where_used($file);
                $list[] = [
                    'id' => $file,
                    'description' => $value['description'],
                    'files' => $files,
                    'modules' => $modules,
                ];
            }
        }
        return $list;
    }

    /**
     * @param $arr
     * @param $name_module
     * @return string
     */
    public function forming_string_modules($arr, $name_module): string
    {
        $modules = '';
        if (!empty($arr)) {
            $str = implode(', ', $arr);
            $modules = $modules . "{$name_module}: " . $str;
        }
        return $modules;
    }

    /**
     * @param $file
     * @param $model
     * @param $cd
     * @return array
     */
    public function find_in_module($file, $model, $cd = false): array
    {
        $arr = [];
        $search_value = "{$file})";
        $models = $cd ? $model->find()->andFilterWhere(['like', 'context', $search_value])->all() : $model->find()->where(['musiconhold' => $file])->all();
        if ($models) {
            foreach ($models as $object) {
                $arr[] = $object->name;
            }
        }
        return $arr;
    }

    /**
     * @param $file
     * @return string
     */
    public function find_where_used($file): string
    {
        $modules = '';
        $arr_queues = $this->find_in_module($file, new Queue());
        $modules_qu = $this->forming_string_modules($arr_queues, 'Queues');
        $arr_custom_dest = $this->find_in_module($file, new CustomDestinations(), true);
        $modules_cust = $this->forming_string_modules($arr_custom_dest, 'CustomDestinations');
        $arr_routes = $this->find_in_module($file, new Route());
        $modules_route = $this->forming_string_modules($arr_routes, 'Routes');
        $arr_dialer_routes = $this->find_in_module($file, new Route());
        $modules_dialer_route = $this->forming_string_modules($arr_dialer_routes, 'Dialer routes');
        if (!empty($arr_queues)) $modules = $modules_qu . '<br>';
        if (!empty($arr_custom_dest)) $modules = $modules . $modules_cust . '<br>';
        if (!empty($arr_routes)) $modules = $modules . $modules_route . '<br>';
        if (!empty($arr_dialer_routes)) $modules = $modules . $modules_dialer_route;
        return $modules;
    }

    /**
     * The function changes space to underscore
     * and do lower case for name of file
     * invoked from function uploadmoh this model
     * return new name file
     */
    public function check_filename_format($filename): string
    {
        $tmp_arr = explode(' ', $filename);
        $new_name = implode("_", $tmp_arr);
        return mb_strtolower($new_name, 'UTF-8');
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
            $message = "The musiconhold $file has not been deleted, because it is in : <br>{$message}";
            $session->setFlash('warning', $message);
            return false;
        }
        return true;
    }

    /**
     * Convert audio file to wav format.
     * Delete temporary file.
     * Invoked from function uploadmoh
     */
    public function convert_to_wav($path, $filePath, $ext)
    {
        $filePath_out = $path . $this->file->name;
        if ($ext == 'wav') {
            $command = "sox {$filePath} -r 8000 -c 1 -b 16 {$filePath_out}";
        } elseif ($ext == 'mp3') {
            $filePath_out = preg_replace("/mp3$/", 'wav', $filePath_out);
            $command = "lame --decode {$filePath} - | sox -t wav - -t wav -r 8000 -c 1 {$filePath_out}";
        } else {
            return false;
        }
        system($command, $retval);
        if (file_exists($filePath)) unlink($filePath);
    }

    /**
     * Uploads file to "/var/asterisk/records/"
     * @return boolean
     */
    public function upload(): bool
    {
        if ($this->validate()) {
            $this->file->saveAs('/var/asterisk/records/' . $this->file->name, true);
            return true;
        }
        return false;
    }

    /**
     * @param $id
     * @return bool
     */
    public function uploadmoh($id)
    {
        if ($this->validate() and isset($this->file->name)) {
            $path = '/var/asterisk/moh/' . $id . '/';
            $this->file->name = $this->check_filename_format($this->file->name);
            $ext = $this->file->extension;
            $filePath = $path . $this->file->name . '_.' . $ext;
            $charset = mb_detect_encoding($filePath);
            $filePath = iconv($charset, 'UTF-8//IGNORE', $filePath);
            $this->file->saveAs($filePath, true);
            $this->convert_to_wav($path, $filePath, $ext);
            return true;
        }
        return false;
    }

}
