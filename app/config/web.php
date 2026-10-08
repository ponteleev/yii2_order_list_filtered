<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'language' => $_ENV['APP_LANGUAGE'] ?? 'en-US',
    // Автоматически открываем страницу заказов при входе на главную
    'defaultRoute' => 'orders/order/index',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'orders' => [
            'class' => 'app\modules\orders\Module',
        ],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $_ENV['COOKIE_VALIDATION_KEY'],
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            // Перенаправляем обработку ошибок на наш модуль
            'errorAction' => 'orders/order/index',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Полное ЧПУ: /orders/pending/manual/service-6
                'orders/<statusSlug:[a-z-]+>/<mode:\d+>/service-<service_id:\d+>' => 'orders/order/index',
                // ЧПУ без режима: /orders/pending/service-6
                'orders/<statusSlug:[a-z-]+>/service-<service_id:\d+>' => 'orders/order/index',
                // ЧПУ без сервиса: /orders/pending/manual
                'orders/<statusSlug:[a-z-]+>/<mode:\d+>' => 'orders/order/index',

                // Базовые правила
                'orders/<statusSlug:[a-z-]+>' => 'orders/order/index',
                'orders' => 'orders/order/index',
            ],
        ],
        'i18n' => [
            'translations' => [
                'modules/orders' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'sourceLanguage' => 'en-US', // Дефолтный язык в коде (английский)
                    'basePath' => '@app/modules/orders/messages', // Сюда мы позже положим переводы
                    'fileMap' => [
                        'modules/orders' => 'orders.php',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
];

if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '*'],
    ];
}

return $config;
