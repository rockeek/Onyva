<?php

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
