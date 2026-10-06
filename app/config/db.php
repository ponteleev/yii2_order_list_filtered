<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=db;dbname=yii2_db',
    'username' => 'yii2_user',
    'password' => 'yii2_password',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
