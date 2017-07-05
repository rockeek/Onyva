<?php

namespace App;

class Device
{
    protected $db;
    private $passengerIds = array();
    private $vehiculeIds = array();
    private $deviceId;

    public function __construct($db, $deviceId)
    {
        $this->db = $db;
        $this->deviceId = $deviceId;
        $this->loadPassengers();
        $this->loadVehicules();
    }

    public function passengerIds()
    {
        return $this->passengerIds;
    }

    public function vehiculeIds()
    {
        return $this->vehiculeIds;
    }

    public function isOwnPassengerId($passengerId)
    {
        return in_array($passengerId, $this->passengerIds);
    }

    public function isOwnVehiculeId($vehiculeId)
    {
        return in_array($vehiculeId, $this->vehiculeIds);
    }

    private function loadPassengers()
    {
        // Keep in mind the device's vehicules
        $q = 'SELECT PassengerId as passengerId
                    FROM Passenger
                    WHERE DeviceId = :deviceId';
        $query = $this->db->prepare($q);
        $query->bindParam(':deviceId', $this->deviceId);
        $query->execute();

        foreach ($query as $passenger) {
            $this->passengerIds[] = $passenger['passengerId'];
        }
    }

    private function loadVehicules()
    {
        // Keep in mind the device's passengers
        $q = 'SELECT VehiculeId as vehiculeId
                    FROM Vehicule
                    WHERE DeviceId = :deviceId';
        $query = $this->db->prepare($q);
        $query->bindParam(':deviceId', $this->deviceId);
        $query->execute();

        foreach ($query as $vehiculeId) {
            $this->vehiculeIds[] = $vehiculeId['vehiculeId'];
        }
    }
}
