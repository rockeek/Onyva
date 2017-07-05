<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'prettyJson' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Onyva application settings
        'app' => [
            'minimumCodeValue' => 100000,
            'maximumCodeValue' => 999999,
            ],

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__.'/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__.'/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Database
        'db' => [
            'host' => 'localhost',
            'dbname' => 'onyva',
            'user' => 'rockeek',
            'pass' => '',
        ],

        // Qa
        'qa' => [
            'dbInitSqlFile' => 'tests/Database/OnyvaDbInit.sql',
            'dbInitQaDataSqlFile' => 'tests/Database/OnyvaQaData.sql',
            'dbTriggersSqlFile' => 'tests/Database/OnyvaTriggers.sql',
            ],
    ],
];
