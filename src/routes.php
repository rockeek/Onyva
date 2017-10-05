<?php

// All the routes are prefixed with /onyvaapi
// The nginx config must reflect that too.
// Put the slim project in /var/www/ and name it onyvaapi_internal
// Example of Nginx config
/*
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
*/

$app->group('/onyvaapi', function () use ($app) {
    // Routes
    $app->post('/device', 'App\DeviceController:device');
    $app->post('/club', 'App\ClubController:club');
    $app->post('/getpassenger', 'App\PassengerController:getPassengers');
    $app->post('/setpassenger', 'App\PassengerController:updatePassengers');
    $app->post('/deletepassenger', 'App\PassengerController:deletePassenger');

    $app->post('/getvehicule', 'App\VehiculeController:getVehicules');
    $app->post('/setvehicule', 'App\VehiculeController:updateVehicules');
    $app->post('/deletevehicule', 'App\VehiculeController:deleteVehicule');

    $app->post('/gettravel', App\TravelController::class.':getTravels');
    $app->post('/gettravel/[{day:[a-z]{1,9}}[/{time:[0-9]{1,2}\:[0-9]{1,2}}]]', 'App\TravelController:getTravels');
    $app->post('/settravel', App\TravelController::class.':updateTravels');

    $app->post('/getlink', App\LinkController::class.':getLinks');
    $app->post('/setlink', App\LinkController::class.':updateLinks');

    $app->get('/', 'App\DefaultController:other');
});
