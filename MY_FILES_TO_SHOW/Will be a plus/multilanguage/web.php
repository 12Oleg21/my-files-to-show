<?php

$params = require(__DIR__ . '/params.php');
Yii::setAlias('@tests', dirname(__DIR__) . '/tests');
$config = [
    'id' => 'webinterface',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],

    'language' => 'en',
    //'sourceLanguage' => 'en',
    'aliases' => [
            '@bower' => '@vendor/bower-asset',
            '@npm'   => '@vendor/npm-asset',
        ],

    'components' => [
      'assetManager' => [
          'linkAssets' => true,
          'appendTimestamp' => true,
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'Tf9fg?Y?7fj3/F?rpdidvM92_gYpgF4y',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\MemCache',
            'useMemcached' => true,
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 60,
                ],
            ],
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET,HEAD api/campaigns/'      => 'api/campaign/index',
                'POST api/campaigns'           => 'api/campaign/create',
                'PUT api/campaigns/<id>/'      => 'api/campaign/update',
                'GET,HEAD api/campaigns/<id>/' => 'api/campaign/view',
                'DELETE api/campaigns/<id>/'   => 'api/campaign/delete',
                'GET,HEAD api/routes/'         => 'api/route/index',
                'GET,HEAD api/destinations/'   => 'api/destination/index',
                'GET,HEAD api/extensions/pause'      => 'api/extension/viewpauseall',
                'POST api/extensions/<id>/pause'     => 'api/extension/pause',
                'DELETE api/extensions/<id>/pause'   => 'api/extension/unpause',
                'GET,HEAD api/extensions/<id>/pause' => 'api/extension/viewpause',
                'POST api/extensions/<id>/call'      => 'api/extension/createcall',
                'DELETE api/extensions/<id>/call'    => 'api/extension/deletecall',
                'GET,HEAD api/extensions/<id>/call'  => 'api/extension/viewcall',
                'GET,HEAD api/extensions/<id>/vmlist'=> 'api/extension/vmlist',
                'PUT api/extensions/<id>/call'       => 'api/extension/transfer',
                'GET,HEAD api/blocked'         => 'api/blocked/index',
                'POST api/blocked'             => 'api/blocked/create',
                'DELETE api/blocked'           => 'api/blocked/delete',
                'GET,HEAD api/statistic/<begin>/<end>/'       => 'api/statistic/index',
                'GET,HEAD api/statisticad/<begin>/<end>/'    => 'api/statisticad/index',
            ],
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'authTimeout' => '3600',
            'enableAutoLogin' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'mail.mbae-it.com',
                'username' => 'asterisk_mbae',
                'password' => '123QWEasd',
                'port' => '25',
                'encryption' => 'tls',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'dbAsterisk' => require(__DIR__ . '/dbAsterisk.php'),
        'dbStats' => require(__DIR__ . '/dbStats.php'),
        'dbTest' => require(__DIR__ . '/dbTest.php'),
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        /* THIS IS FOR MULTI LANGUAGES*/
        'i18n' => [
            'translations' => [
                'stats*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'fileMap' => [
                        'stats' => 'stats.php'
                    ],
                ],
                'dialer*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'fileMap' => [
                        'dialer' => 'dialer.php'
                    ],
                ],
                'application*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'fileMap' => [
                        'application' => 'application.php'
                    ],
                ],
            ],
        ],
     ],
     'modules' => [
        'gridview' => [
            'class' => 'kartik\grid\Module',
             'downloadAction' => 'gridview/export/download',
             'i18n' => [
                'class' => 'yii\i18n\PhpMessageSource',
                 'basePath' => '@kvgrid/messages',
                 'forceTranslation' => true
                ]
            ]
    ],
    'params' => $params,
    ];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'fixture' => [
                'class' => 'elisdn\gii\fixture\Generator',
            ],
        ],
    ];
    $config['bootstrap'][] = 'gii';
}

return $config;
