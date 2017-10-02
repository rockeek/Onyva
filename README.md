# Onyva server

Server for Onyva Application.

## Install the Application

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writeable.

## Setup Mysql. Create a database with a user:

    #mysql -u root -p
    CREATE DATABASE onyva;
    CREATE USER 'onyva'@'localhost' IDENTIFIED BY 'Kass567Loo';
    GRANT ALL PRIVILEGES ON onyva . * TO 'onyva'@'localhost';
    FLUSH PRIVILEGES;

To run the application in development, you can also run this command. 
	php composer start

Run this command to run the test suite
	php composer test

To restore database
    php composer restore
    
To run all tests
    php composer testAll
