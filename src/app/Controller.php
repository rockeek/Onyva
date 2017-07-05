<?php

namespace App;

class Controller
{
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct($container)
    {
        $this->container = $container;
        $this->db = $this->container->db;
    }

    public function getDeviceIdFromIdentifier($identifier)
    {
        $getQuery = $this->db->prepare('SELECT DeviceId as deviceId FROM Device WHERE Identifier = :identifier');
        $getQuery->bindParam(':identifier', $identifier);
        $getQuery->execute();
        $device = $getQuery->fetchObject();

        return $device->deviceId; // > 0 ? $device->deviceId : null;
        //return $getQuery->rowCount() > 0;
    }

    public function logMe($class, $method, $args)
    {
        $this->container->logger->info('Accessed **'.$class.'** method: **'.$method.'**'.'params: '.print_r($args, true));
    }

    public function info($message)
    {
        $this->container->logger->info($message);
    }
}
