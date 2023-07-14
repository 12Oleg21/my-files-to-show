<?php

/**
 * LmsApiController class file
 * Used for external requests from LMS system
 *
 * @author Oleh Timoshenko
 */
class LmsApiController extends Controller
{
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
            array('ext.yiibooster.filters.BootstrapFilter'),
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     *
     * @return array access control rules
     *
     * @author Cyril Turkevich
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'roles' => array('Tutor', 'Client'),
                'actions' => array('LoginToLms'),
            ),
            array(
                'allow',
                'users' => array('*'),
                'actions' => array('CheckUser', 'AssessmentStatus'),
            ),
            array(
                'deny',
                'users' => array('*'),
            ),

        );
    }

    /**
     * The LMS system sends to this api point POST like:
     * https://www.ezymathtutoring.com.au/check-user
     *
     * In case of success return code 200 and JSON with User.id
     * else return code 401(unauthorized) and JSON with message "User not found"
     *
     * @return void
     */
    public function actionCheckUser()
    {
        $email = Yii::app()->request->getParam('email');
        $password = Yii::app()->request->getParam('password');

        if ($email and $password) {
            $user = UserModel::model()->findByAttributes(array('email' => $email, 'password' => md5($password)));
            if ($user) {
                http_response_code(200);
                echo CJSON::encode(array(
                    'id' => $user->id,
                ));
                Yii::app()->end();
            }
        }

        http_response_code(401);
        echo CJSON::encode(array(
            'message' => 'User not found'
        ));

        Yii::app()->end();
    }

    /**
     * Send request to LMS to get Access Token and then go to(redirect to) LMS site to log in without credentials
     *
     * @return void|null
     */
    public function actionLoginToLms()
    {
        $lmsToken = Yii::app()->lms->getAccessTokenFromLms(['user_id' => Yii::app()->user->id]);
        if ($lmsToken) {
            $urlToLms = Yii::app()->lms->getUrlToLms($lmsToken);
            $this->redirect($urlToLms);
        } else {
            Yii::app()->user->setFlash('error', 'Something has gone wrong! Please, ask Ezymath team to help!');
            return $this->redirect(Yii::app()->user->returnUrl);
        }
    }

    /**
     * When a student has started/finished an assessment test,
     * then LMS will use the point to tell about it.
     *
     * @return void
     */
    public function actionAssessmentStatus()
    {
        X('here in actionAssessmentStatus');
        $test_id = Yii::app()->request->getParam('test_id');
        $student_id = Yii::app()->request->getParam('student_id');
        $status = strtolower(Yii::app()->request->getParam('status'));
        $assessment_id = Yii::app()->request->getParam('assessment_id');

        if ($test_id and $student_id and $status) {
            $student = StudentModel::model()->findByPk($student_id);
            if ($student) {
                $params = [
                    'assessment_id' => $student->assessment_id,
                    'student_id' => $student->id,
                    'test_id' => $test_id,
                    'status' => StudentWorkStatus::$statuses[$status],
                ];

                $result = StudentWorkModel::addWorkRecord($params);

                if ($result) {
                    http_response_code(200);
                    echo CJSON::encode([
                        'Success' => 'Ok',
                    ]);
                    Yii::app()->end();
                }
            }
        }

        http_response_code(401);
        echo CJSON::encode(array(
            'message' => 'Parameters test_id, student_id, status have not been found'
        ));

        Yii::app()->end();
    }

}