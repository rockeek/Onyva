<?php

namespace Tests\Functional;

class LinkTest extends BaseTestCase
{
    public function testCase1_AlexandraOffersHerCarWithHerself()
    {
        $identifier = 'poulet';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 6, 'vehiculeId' => 3];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('new vehicule with a passenger linked successfully', $json->result);
        $this->assertEquals(null, $json->validation);

        // Test also Alexandra offers a car of another device
        $linkRequest['link']['vehiculeId'] = 5;
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('vehicule and passenger must belong to the commanding device', $json->validation);
    }

    public function testCase2_NoemieTriesToOfferHerCarWithoutDriver()
    {
        $identifier = 'cahier';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => null, 'vehiculeId' => 5];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('a vehicule needs a driver', $json->validation);

        // Test also Noemie tries to offer someone else's car without driver
        $linkRequest['link']['vehiculeId'] = 2;
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('the vehicule must belong to the commanding device', $json->validation);

        // Test also with a vehicule that is already waiting with passengers
        $linkRequest['link']['vehiculeId'] = 3;
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('the vehicule must belong to the commanding device', $json->validation);
    }

    public function testCase3_DeborahToXsara()
    {
        $identifier = 'joujou';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 8, 'vehiculeId' => 1];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('new passenger linked to existing vehicule successfully', $json->result);
        $this->assertEquals(null, $json->validation);

        // Try again: the passenger is already in that vehicule
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('the passenger is already in that vehicule', $json->validation);
    }

    public function testCase4_LisaGoesWaitingAsALonelyPassenger()
    {
        $identifier = 'youhou';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 9, 'vehiculeId' => null];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('lonely passenger linked to existing vehicule successfully', $json->result);
        $this->assertEquals(null, $json->validation);

        // Note: If we try again the same post, the passenger will now exit the car

        // Test also: Lisa try to set Abigail to go waiting as a lonely passenger
        $linkRequest['link']['passengerId'] = 7;
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('passenger must belong to the commanding device', $json->validation);

        // Test also: Lisa try to set Deborah (who is already in a car by the way) to go waiting as a lonely passenger.
        // The validation message should be the same. It is not Deborah's device so refuse to compute further
        $linkRequest['link']['passengerId'] = 8;
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('passenger must belong to the commanding device', $json->validation);
    }

    public function testCase5_FormerLonelyLisaGoesToXsara()
    {
        $identifier = 'youhou';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 9, 'vehiculeId' => 1];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('lonely passenger went to waiting vehicule successfully', $json->result);
        $this->assertEquals(null, $json->validation);

        // Try again
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('', $json->result);
        $this->assertEquals('the passenger is already in that vehicule', $json->validation);
    }

    public function testCase5b_XsaraOwnerTakesInLonelyNoemie()
    {
        $identifier = 'aaabbb';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 10, 'vehiculeId' => 1];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('vehicule owner picked the lonely passenger up', $json->result);
        $this->assertEquals(null, $json->validation);

        // Try again
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('', $json->result);
        $this->assertEquals('the passenger is already in that vehicule', $json->validation);

        // But cannot take in another passenger who is not a lonely waiting passenger
        $linkRequest['link']['passengerId'] = 4;
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('vehicule owner cannot pick up a passenger who is not lonely waiting', $json->validation);
    }

    public function testCase5c_NoemieExistsXsaraCarToSidewalk()
    {
        $identifier = 'cahier';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 10, 'vehiculeId' => null];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('passenger went lonely waiting to the sidewalk', $json->result);
        $this->assertEquals(null, $json->validation);
    }

    public function testCase5d_XsaraDriverKicksOutLisa()
    {
        $identifier = 'aaabbb';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 9, 'vehiculeId' => null];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('vehicule driver kicked the passenger to the sidewalk', $json->result);
        $this->assertEquals(null, $json->validation);

        // Try again: now the vehicule driver has no more rights on Lisa
        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('passenger must belong to the commanding device', $json->validation);
    }

    public function testCase5d_LisaGoesToRenaultCarThenXsaraDriverCannotKickHerOut()
    {
        // First Lisa goes to Renault car
        $identifier = 'youhou';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 9, 'vehiculeId' => 3];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals('lonely passenger went to waiting vehicule successfully', $json->result);
        $this->assertEquals(null, $json->validation);

        // Then Xsara tries to kick Lisa out of Renault
        $identifier = 'aaabbb';
        $clubId = 10001;
        $clubPassword = 'boing';
        $travelId = 2;
        $link = ['passengerId' => 9, 'vehiculeId' => null];

        $linkRequest = ['identifier' => $identifier, 'clubId' => $clubId,
                        'clubPassword' => $clubPassword, 'travelId' => $travelId,
                        'link' => $link, ];

        $response = $this->runApp('POST', '/setlink', $linkRequest);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody());
        $this->assertEquals(null, $json->result);
        $this->assertEquals('passenger must belong to the commanding device', $json->validation);
    }
}
