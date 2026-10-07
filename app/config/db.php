<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=db;dbname=' . ($_ENV['MYSQL_DATABASE'] ?? 'yii2_db'),
    'username' => $_ENV['MYSQL_USER'] ?? 'yii2_user',
    'password' => $_ENV['MYSQL_PASSWORD'] ?? 'yii2_password',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'prod',
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
