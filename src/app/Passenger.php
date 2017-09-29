<?php

namespace App;

class Passenger
{
    private $deviceId;
    public $passengerId;
    public $passengerName;

    public function __construct()
    {
        $this->passengerId = intval($this->passengerId);
    }
}
