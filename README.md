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

To put in production with nginx.

* Put in a directory like /var/www/onyvaapi_internal where /var/www is the root of nginx config.
Don't put an index.html or index.php in the directory so that accessing it will return 403 forbidden error.

* Set url prefix in settings.php
    i.e. onyvaapi
This way, you can access api with: http://server/onyvaapi/getvehicule
    
Set proper nginx config

    server {
        listen 80;
        server_name boogy.ovh;
        index index.html index.php;
        error_log /var/log/slim.error.log;
        access_log /var/log/slim.access.log;
        rewrite_log on;
    
        root /var/www;
    
        location / {
            try_files $uri $uri/ =404;
        }
    
        location ~ ^/onyvaapi/ {
            try_files $request_uri $request_uri/ /onyvaapi_internal/public/index.php?$query_string;
        }
    
        location ~ \.php {
            try_files $uri =404;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_pass unix:/var/run/php5-fpm.sock;
        }
    }
