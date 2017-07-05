# Onyva server

Server for Onyva Application.

## Install the Application

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writeable.

To run the application in development, you can also run this command. 

	php composer.phar start

Run this command to run the test suite

	php composer.phar test

To restore database

    php composer.phar restore
    
To run all tests

    php composer.phar testAll
