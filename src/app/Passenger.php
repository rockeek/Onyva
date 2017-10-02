<?php

namespace App;

class Passenger
{
    public $passengerId;
    public $name;

    public function __construct()
    {
        $this->passengerId = intval($this->passengerId);
    }
}
