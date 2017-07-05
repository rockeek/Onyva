<?php

namespace Tests\Functional;

class VehiculeTest extends BaseTestCase
{
    public function testGetVehicules()
    {
        $identifier = 'aaabbb';
        $idRequest = ['identifier' => $identifier];
        $response = $this->runApp('POST', '/getvehicule', $idRequest);

        $json = json_decode($response->getBody(), true);
        //$this->show($json);
        $this->assertEquals('Xsara Rémy', (string) $json[0]['name']);
        $this->assertEquals('Citroën Xsara', (string) $json[0]['trademark']);
        $this->assertEquals('Grise', (string) $json[0]['color']);
        $this->assertEquals(5, (int) $json[0]['seats']);
    }

    public function testCannotCreateOrUpdateWithWrongIdentifier()
    {
        $jsonRequest = json_decode('[
           {
              "identifier":"idontexist"
           },
           {
              "vehicules":[
                 {
                    "vehiculeId":2,
                    "name":"Another man that is not good"
                 },
                 {
                    "name":"User that should not be created"
                 }
              ]
           }
        ]', true);

        $response = $this->runApp('POST', '/setvehicule', $jsonRequest);
        $this->assertEquals(406, $response->getStatusCode());
    }

    public function testCannotUpdateAVehiculeBelongingToAnotherDevice()
    {
        $identifier = 'kkkooo';
        $idRequest = ['identifier' => $identifier];
        $response = $this->runApp('POST', '/getvehicule', $idRequest);

        $json = json_decode($response->getBody(), true);
        $this->assertEquals('Opel Olivier', (string) $json[0]['name']);

        $jsonRequest = json_decode('[
           {
              "identifier":"aaabbb"
           },
           {
              "vehicules":[
                 {
                    "vehiculeId":'.$json[0]['vehiculeId'].',
                    "name":"The modified vehicule"
                 }
              ]
           }
        ]', true);

        $response = $this->runApp('POST', '/setvehicule', $jsonRequest);
        $this->assertEquals(200, $response->getStatusCode());

        $jsonRequest = json_decode('[
           {
              "identifier":"kkkooo"
           }
        ]', true);

        $json = json_decode($response->getBody(), true);
        $this->assertContains('Xsara', $json[0]['name']);

        // Check back that the vehicule of the other device has not been modified
        $response = $this->runApp('POST', '/getvehicule', $idRequest);
        $json = json_decode($response->getBody(), true);
        $this->assertEquals('Opel Olivier', (string) $json[0]['name']);
    }

    // Seats are mandatory in database
    public function testCreateAndUpdateVehicules()
    {
        $identifier = 'aaabbb';
        $name1 = $this->randomString(12);
        $name2 = $this->randomString(12);
        $seats1 = '3';
        $seats2 = '4';
        $trademark1 = $this->randomString(15);
        $trademark2 = $this->randomString(15);
        $color1 = $this->randomString(10);
        $color2 = $this->randomString(10);

        $idRequest = ['identifier' => $identifier];
        $vehicule1 = ['name' => $name1, 'seats' => $seats1, 'trademark' => $trademark1, 'color' => $color1];
        $vehicule2 = ['name' => $name2, 'seats' => $seats2, 'trademark' => $trademark2, 'color' => $color2];
        $vehicules = ['vehicules' => [$vehicule1, $vehicule2]];
        $requestVehicules = [$idRequest, $vehicules];

        $response = $this->runApp('POST', '/setvehicule', $requestVehicules);
        $json = json_decode($response->getBody(), true);

        //$this->show($json);
        $this->assertEquals(200, $response->getStatusCode());

        $jsonString = json_encode($json);

        $this->assertContains($name1, $jsonString);
        $this->assertContains($name2, $jsonString);
        $this->assertContains($seats1, $jsonString);
        $this->assertContains($seats2, $jsonString);
        $this->assertContains($trademark1, $jsonString);
        $this->assertContains($trademark2, $jsonString);
        $this->assertContains($color1, $jsonString);
        $this->assertContains($color2, $jsonString);

        foreach ($json as $p) {
            if ($p['name'] == $name1) {
                $vehicule1['vehiculeId'] = $p['vehiculeId'];
            } elseif ($p['name'] == $name2) {
                $vehicule2['vehiculeId'] = $p['vehiculeId'];
            }
        }

        $newName1 = $this->randomString(12);
        $newName2 = $this->randomString(12);
        $newSeats1 = '1';
        $newSeats2 = '2';
        $newTrademark1 = $this->randomString(15);
        $newTrademark2 = $this->randomString(15);
        $newColor1 = $this->randomString(10);
        $newColor2 = $this->randomString(10);

        $vehicule1['name'] = $newName1;
        $vehicule1['seats'] = $newSeats1;
        $vehicule1['trademark'] = $newTrademark1;
        $vehicule1['color'] = $newColor1;

        $vehicule2['name'] = $newName2;
        $vehicule2['seats'] = $newSeats2;
        $vehicule2['trademark'] = $newTrademark2;
        $vehicule2['color'] = $newColor2;

        $vehicules = ['vehicules' => [$vehicule1, $vehicule2]];
        $requestVehicules = [$idRequest, $vehicules];
        $response = $this->runApp('POST', '/setvehicule', $requestVehicules);
        $json = json_decode($response->getBody(), true);

        //$this->show($json);
        $this->assertEquals(200, $response->getStatusCode());
        $jsonString = json_encode($json);
        $this->assertNotContains($name1, $jsonString);
        $this->assertNotContains($name2, $jsonString);
        $this->assertNotContains($trademark1, $jsonString);
        $this->assertNotContains($trademark2, $jsonString);
        $this->assertNotContains($color1, $jsonString);
        $this->assertNotContains($color2, $jsonString);

        $this->assertContains($newName1, $jsonString);
        $this->assertContains($newName2, $jsonString);
        $this->assertContains($newTrademark1, $jsonString);
        $this->assertContains($newTrademark2, $jsonString);
        $this->assertContains($newColor1, $jsonString);
        $this->assertContains($newColor2, $jsonString);
    }
}
