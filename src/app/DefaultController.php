<?php

namespace App;

class DefaultController extends Controller
{
    public function other($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        echo 'Welcome to Onyva.';

        return $response->withStatus(200);
    }
}
