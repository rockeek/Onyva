<?php

// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$checkProxyHeaders = true;
$trustedProxies = ['10.0.0.1', '10.0.0.2'];
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));
