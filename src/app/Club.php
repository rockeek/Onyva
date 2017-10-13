<?php

namespace App;

class Club
{
    public $clubId;
    public $name;
    public $password;
    public $isInvalid;

    public function __construct()
    {
        $this->clubId = intval($this->clubId);
    }
}
