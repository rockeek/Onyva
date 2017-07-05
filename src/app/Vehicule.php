<?php

namespace App;

class Vehicule
{
    private $freeSeats;
    private $deviceId;
    public $vehiculeId;
    public $name;
    public $trademark;
    public $color;
    public $seats;
    public $passengers = array();
    public $nbPassengers;

    public function __construct()
    {
        $this->vehiculeId = intval($this->vehiculeId);
        $this->seats = intval($this->seats);
    }

    public function freeSeats()
    {
        return $this->freeSeats;
    }

    public function hasPassenger($passengerId)
    {
        foreach ($this->passengers as $passenger) {
            if ($passenger->passengerId == $passengerId) {
                return true;
            }
        }

        return false;
    }
}
