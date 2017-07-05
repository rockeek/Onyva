<?php

namespace App;

class DatabaseController
{
    public static function restoreTestDatabase()
    {
        self::restoreDatabase(true);
    }

    public static function restoreDatabase($isTestEnv = false)
    {
        require __DIR__.'/../../vendor/autoload.php';

        // Instantiate the app
        $settings = require __DIR__.'/../../src/settings.php';
        $app = new \Slim\App($settings);

        // Set up dependencies
        require __DIR__.'/../../src/dependencies.php';

        $container = $app->getContainer();
        $container->logger->info('***Restoring database started.***');

        $qaSettings = $container->get('settings')['qa'];

        $initDbSql = mb_convert_encoding(file_get_contents($qaSettings['dbInitSqlFile']), 'auto');
        $qaDataSql = mb_convert_encoding(file_get_contents($qaSettings['dbInitQaDataSqlFile']), 'auto');
        $triggersSql = mb_convert_encoding(file_get_contents($qaSettings['dbTriggersSqlFile']), 'auto');

        $db = $container->get('db');
        try {
            echo "\t**DB Init ";
            echo $db->exec($initDbSql) == 0 ? 'OK' : 'failed';
            echo "\n";

            echo "\t**Triggers Init ";
            echo $db->exec($triggersSql) == 1 ? 'OK' : 'failed';
            echo "\n";

            if ($isTestEnv) {
                $db->exec($qaDataSql);
                echo "\t**Fake data added.\n";
            }

            echo "\t++Restore database finished.\n";
        } catch (Exception $e) {
            echo 'Exception -> ';
            var_dump($e->getMessage());
        }

        $container->logger->info('***Restoring database finished.***');
    }
}
