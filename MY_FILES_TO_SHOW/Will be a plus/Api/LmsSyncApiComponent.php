<?php

/**
 * Class LmsSyncApiComponent
 * The Class is created to sync tutors, clients and their students on LMS system
 */
class LmsSyncApiComponent extends CApplicationComponent
{
    /**
     * Token for production
     */
    const TOKEN_PRODUCTION = 'pvN3cUHt';

    /**
     * Token for staging
     */
    const TOKEN_STAGING = 'pvNuz2VAgbM3cUHt';

    /**
     * Token for local
     */
    const TOKEN_LOCAL = 'pvNuzt8TgzmHt';

    /**
     * name of the http server on staging
     */
    const STAGING_SERVER_NAME = 'https://stage.com.au';

    /**
     * name of the http server on staging
     */
    const LOCAL_SERVER_NAME = 'http://127.0.0.1';

    /**
     * name of the http server on production
     */
    const PRODUCTION_SERVER_NAME = 'https://lms-api.ezymathtutoring.com.au';

    /**
     * url to create/update/delete users on Lms side
     */
    const LMS_API_URL_USER = '/service-api/learning-entity-update/user';

    /**
     * url to create/update/delete students on Lms side
     */
    const LMS_API_URL_STUDENT = '/service-api/learning-entity-update/student';

    const PRODUCTION_LMS_API_PORT = '443';
    const STAGE_LMS_API_PORT = '443';
    const LOCAL_LMS_API_PORT = '8082';

    /**
     * The main function to sync data on LMS
     * The function is run with this command 'php yiic lmssyncqueue'
     *
     * @return bool
     */
    public function syncQueueChangesToLms()
    {
        Yii::log('syncQueueChangesToLms STARTED', 'info', 'lms-sync');

        $ids = $this->selectAllIds();

        if ($ids) {
            foreach ($ids as $oneBlock) {
                $criteria = new CDbCriteria();
                $criteria->compare('success', false);
                $criteria->addInCondition('id', $oneBlock);

                $allItems = LmsSyncQueueModel::model()->findAll($criteria);
                foreach ($allItems as $item) {
                    /* @var $item LmsSyncQueueModel */
                    $result = $this->processing($item);
                    $this->updateLmsQueueModel($item, $result);
                }
            }
        }

        Yii::log('syncQueueChangesToLms ENDED', 'info', 'lms-sync');
        return true;
    }

    /**
     * The function select all ids from LmsSyncQueue table and then create chunk array
     * according to queue limit const.
     *
     * @return array|false the array can look like this: [[67,123,12335,...],[9000,93002,95432,...], ...]
     * @throws CException
     */
    public function selectAllIds()
    {
        $ids = Yii::app()->db->createCommand()
            ->select('id')
            ->from('LmsSyncQueue lms')
            ->where('success = :success AND attempts <= :attempts',
                [':success' => false, ':attempts' => LmsSync::ATTEMPT_LIMIT])
            ->order('id')
            ->queryAll();

        if (!empty($ids)) {
            $onlyValues = array_column(array_values($ids), 'id');
            return array_chunk($onlyValues, LmsSync::SYNC_QUEUE_LIMIT);
        }

        return false;
    }

    /**
     * It handles a one line from LmsSyncQueue table only to synchronize on LMS
     *
     * @param LmsSyncQueueModel $item
     * @return bool
     */
    public function processing($item)
    {
        Yii::log("START processing with ITEM: Id => $item->id, USER_STUDENT_ID => $item->itemId,  typeId => $item->typeId, relativeId => $item->relativeId,
             attempts => $item->attempts, delete => $item->delete, attach_detach_flag => $item->attach_detach_flag",
            'info', 'lms-sync');

        if ($item->relativeId) {
            // $relativeItem it means, a current item depends on a previous item and
            // we have to check if it has success = true or not.
            $relativeItem = $this->checkIfRelativeItemIsSuccess($item->relativeId);
            if ($relativeItem) {
                $result = $this->syncItemToLmsSystem($item, $relativeItem);
            } else {
                $result = false;
            }
        } else {
            $result = $this->syncItemToLmsSystem($item);
        }

        Yii::log("STOP processing with ITEM: Id => $item->id, resultOfSync => $result", 'info', 'lms-sync');

        return $result;
    }

    /**
     * @param LmsSyncQueueModel $item
     * @param $relativeItem
     * @return bool
     */
    public function syncItemToLmsSystem($item, $relativeItem = null)
    {
        // set attached and detached arrays
        list($attached, $detached) = $this->setAttachedDetached($relativeItem);

        // find model
        if ($item->typeId == UserTypes::TYPE_TUTOR or $item->typeId == UserTypes::TYPE_CLIENT) {
            $model = UserModel::model()->findByPk($item->itemId);
            // If the model had been deleted, then set new with a deleted model ID
            if (!$model) {
                $model = new UserModel();
                $model->id = $item->itemId;
            }
        } else {
            $model = StudentModel::model()->findByPk($item->itemId);
            if (!$model) {
                $model = new StudentModel();
                $model->id = $item->itemId;
            }
        }

        return $this->updateLmsUserOrStudent($model, $item->delete, $attached, $detached);
    }

    /**
     * Attach/Detach a student to a tutor/client
     *
     * @param LmsSyncQueueModel|null $relativeItem
     * @return array[], example: to attach a student --> [[233],[]] to detach student --> [[], [233]] . 233 is student ID.
     */
    public function setAttachedDetached($relativeItem)
    {
        $attached = [];
        $detached = [];
        if ($relativeItem) {
            if ($relativeItem->attach_detach_flag == LmsSync::ATTACH_STUDENT) {
                $attached[] = $relativeItem->itemId;
            }
            if ($relativeItem->attach_detach_flag == LmsSync::DETACH_STUDENT) {
                $detached[] = $relativeItem->itemId;
            }
        }

        return [$attached, $detached];
    }

    /**
     * Update LmsSyncQueueModel to set result from LMS system and increase an attempts
     *
     * @param LmsSyncQueueModel $item
     * @param bool $result
     * @return void
     */
    public function updateLmsQueueModel($item, $result)
    {
        $item->attempts = ++$item->attempts;
        $item->success = $result;
        $item->timestamp = date('Y-m-d H:i:s');
        $item->save();
    }

    /**
     * @param int $relativeId
     * @return LmsSyncQueueModel|null
     */
    public function checkIfRelativeItemIsSuccess($relativeId)
    {
        $relativeItem = LmsSyncQueueModel::model()->findByPk($relativeId);
        if ($relativeItem and $relativeItem->success) {
            return $relativeItem;
        }
        return null;
    }

    /**
     * Prepare data and send a request to LmsApi to create/update/delete user or student
     *
     * @param $model UserModel|StudentModel
     * @param bool $delete
     * @param array $attached array of ids of students that needs to be attached to user
     * @param array $detached array of ids of students that needs to be detached to user
     * @return bool
     * @author Oleh.Timoshenko
     */
    public function updateLmsUserOrStudent($model, $delete = false, $attached = [], $detached = [])
    {
        $flagStudent = $model instanceof StudentModel;
        // form data in JSON format
        $lmsData = $flagStudent ? $this->creatingStudentJsonData($model,
            $delete) : $this->creatingUserJsonData($model, $delete, $attached, $detached);
        return $this->sendToLms($lmsData, $flagStudent);
    }

    /**
     * Send a request to LmsApi to create/update/delete user or student(depend on $flagUser)
     *
     * @param $lmsData
     * @param $flagUser
     * @return bool
     */
    public function sendToLms($lmsData, $flagStudent)
    {
        list($token, $host_name, $port) = $this->setEnvTokenHostPort();

        $url = $flagStudent ? $host_name . self::LMS_API_URL_STUDENT : $host_name . self::LMS_API_URL_USER;

        //initialize CURL and set option
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $lmsData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Uncomment this if you are getting SSL errors (common in WAMP)
        // Make sure it is enabled when you go live!
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Ucomment for CURL debugging
        // curl_setopt($ch, CURLOPT_VERBOSE, true);

        // send a request to LmsApi
        curl_exec($ch);

        if (curl_errno($ch) != CURLE_OK) {
            echo "<h2>POST Error: " . curl_error($ch) . ' ' . self::LMS_API_URL_USER . "</h2>";
        } else {
            $info = curl_getinfo($ch);
            if ($info['http_code'] == 200) {
                curl_close($ch);
                return true;
            }
        }
        curl_close($ch);
        return false;
    }

    /**
     * To avoid corrupting data on production server set different token, host and port for a CURL request
     *
     * @return array with token, host, post according to the environment
     */
    public function setEnvTokenHostPort()
    {
        if (ENV_TYPE == 'prod') {
            $token = self::TOKEN_PRODUCTION;
            $host_name = self::PRODUCTION_SERVER_NAME;
            $port = self::PRODUCTION_LMS_API_PORT;
        } elseif (ENV_TYPE == 'stage') {
            $token = self::TOKEN_STAGING;
            $host_name = self::STAGING_SERVER_NAME;
            $port = self::STAGE_LMS_API_PORT;
        } else {
            $token = self::TOKEN_LOCAL;
            $host_name = self::LOCAL_SERVER_NAME;
            $port = self::LOCAL_LMS_API_PORT;
        }

        return [$token, $host_name, $port];
    }


    /**
     * Create data in JSON format to send to LmsApi
     * { "items": [{
     * "id": "72222", "first_name": "Liam", "last_name": "Cut", "role": 4, "email": "dummy96787@gmail.com",
     * "students": { "attached": [ 0 ], "detached": [ 0 ] }, "deleted_at": false }]}
     *
     * @param $user UserModel
     * @param $delete
     * @return string
     */
    public function creatingUserJsonData(UserModel $user, $delete, $attached, $detached)
    {
        $lmsUser = [];
        $lmsUser['items'] = [
            [
                'id' => $user->id,
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
                'role' => $user->userTypeId == 3 ? 2 : $user->userTypeId,
                'email' => $user->email,
                'students' => [
                    'attached' => $attached,
                    'detached' => $detached,
                ],
                'deleted_at' => $delete,
            ],
        ];

        return CJSON::encode($lmsUser);
    }

    /**
     * Create data in JSON format to send to LmsApi
     * { "items": [{ "id": 77709, "first_name": "student", "last_name": "Porter", "is_active": true, "deleted_at": false }]}
     *
     * @param $student StudentModel
     * @param $delete
     * @return int|string
     */
    public function creatingStudentJsonData(StudentModel $student, $delete)
    {
        $lmsStudent = [];
        $lmsStudent['items'] = [
            [
                'id' => $student->id,
                'first_name' => $student->firstName,
                'last_name' => $student->lastName,
                'is_active' => $student->isActive,
                'white_board_url' => $student->boardEzmUrl,
                'deleted_at' => $delete
            ],
        ];

        return CJSON::encode($lmsStudent);
    }

    /**
     * The main function to fill out users and students on LMS system first time.
     *
     * @return void
     */
    public function firstFillingOutLms()
    {
        Yii::log('start firstFillingOutStudents', 'info', 'lms-sync');

        //fill out students
        $this->firstFillingOutStudents();

        //fill out tutors with their students
        $this->firstFillingOutTutorsOrClients('TutorModel', 'allocations', 'studentId');

        //fill out clients with they students
        $this->firstFillingOutTutorsOrClients('ClientModel', 'students', 'id');

        Yii::log('STOP firstFillingOutStudents', 'info', 'lms-sync');
    }


    /**
     * Fill out students on LMS
     *
     * @return void
     */
    public function firstFillingOutStudents()
    {
        Yii::log('STUDENTS SYNC STARTED', 'info', 'lms-sync');

        $ids = $this->getAllStudentsIds();

        if ($ids) {
            foreach ($ids as $oneBlock) {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('id', $oneBlock);

                $students = StudentModel::model()->findAll($criteria);
                foreach ($students as $student) {
                    $result = $this->updateLmsUserOrStudent($student);
                    Yii::log("Student Id => $student->id , Result => $result", 'info', 'lms-sync');
                }
            }
        }

        Yii::log('STUDENTS SYNC ENDED', 'info', 'lms-sync');

    }

    /**
     * The function select all ids from Student table and then create chunk array
     * according to queue limit const.
     *
     * @return array|false
     * @throws CException
     */
    public function getAllStudentsIds()
    {
        $ids = Yii::app()->db->createCommand()
            ->select('id')
            ->from('Student s')
            ->order('id')
            ->queryAll();

        if (!empty($ids)) {
            $onlyValues = array_column(array_values($ids), 'id');
            return array_chunk($onlyValues, LmsSync::SYNC_QUEUE_LIMIT);
        }

        return false;
    }

    /**
     * Fill out tutors and clients on LMS
     *
     * @param string $modelClass
     * @param string $withModel
     * @param string $modelId
     * @return void
     */
    public function firstFillingOutTutorsOrClients($modelClass, $withModel, $modelId)
    {
        Yii::log("$modelClass SYNC STARTED", 'info', 'lms-sync');

        $ids = $this->getAllTutorsAndClientsIds($modelClass);

        if ($ids) {
            foreach ($ids as $oneBlock) {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('id', $oneBlock);
                $models = $modelClass::model()->findAll($criteria);
                foreach ($models as $user) {
                    $users = $user->$withModel;
                    $attached = [];
                    if ($users) {
                        /** @var StudentModel $model OR */
                        /** @var StudentTutorModel $model */
                        foreach ($users as $model) {
                            $attached[] = $model->$modelId;
                        }
                    }

                    $result = $this->updateLmsUserOrStudent($user, false, $attached);
                    Yii::log("$modelClass Id => $user->id , Result => $result", 'info', 'lms-sync');
                }
            }
        }
    }

    /**
     * The function select all tutor's or client's ids from User table and then create chunk array
     * according to queue limit const.
     *
     * @param $modelClass
     * @return array|false
     * @throws CException
     */
    public function getAllTutorsAndClientsIds($modelClass)
    {
        $ids = Yii::app()->db->createCommand()
            ->select('id')
            ->from('User u')
            ->where('userTypeId = :userTypeId',
                [':userTypeId' => $modelClass == 'TutorModel' ? UserTypes::TYPE_TUTOR : UserTypes::TYPE_CLIENT])
            ->order('id')
            ->queryAll();

        if (!empty($ids)) {
            $onlyValues = array_column(array_values($ids), 'id');
            return array_chunk($onlyValues, LmsSync::SYNC_QUEUE_LIMIT);
        }

        return false;
    }

}
