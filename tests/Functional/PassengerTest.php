<?php

namespace Tests\Functional;

class PassengerTest extends BaseTestCase
{
    public function testGetPassengers()
    {
        $identifier = 'aaabbb';
        $idRequest = ['identifier' => $identifier];
        $response = $this->runApp('POST', '/getpassenger', $idRequest);

        $json = json_decode($response->getBody(), true);
        //$this->show($json);
        $this->assertEquals('Rémy', (string) $json[0]['name']);
        $this->assertEquals('Emilie', (string) $json[1]['name']);
    }

    public function testCannotCreateOrUpdateWithWrongIdentifier()
    {
        $jsonRequest = json_decode('[
           {
              "identifier":"idontexist"
           },
           {
              "passengers":[
                 {
                    "passengerId":2,
                    "name":"Another man that is not good"
                 },
                 {
                    "name":"User that should not be created"
                 }
              ]
           }
        ]', true);

        $response = $this->runApp('POST', '/setpassenger', $jsonRequest);
        $this->assertEquals(406, $response->getStatusCode());
    }

    public function testCreateAndUpdatePassengers()
    {
        $identifier = 'aaabbb';
        $name1 = $this->randomString(12);
        $name2 = $this->randomString(12);

        $idRequest = ['identifier' => $identifier];
        $passenger1 = ['name' => $name1];
        $passenger2 = ['name' => $name2];
        $passengers = ['passengers' => [$passenger1, $passenger2]];
        $requestPassengers = [$idRequest, $passengers];

        $response = $this->runApp('POST', '/setpassenger', $requestPassengers);
        $json = json_decode($response->getBody(), true);

        //$this->show($requestPassengers);
        //$this->show($json);
        $this->assertEquals(200, $response->getStatusCode());

        $jsonString = json_encode($json);
        $this->assertContains($name1, $jsonString);
        $this->assertContains($name2, $jsonString);

        foreach ($json as $p) {
            if ($p['name'] == $name1) {
                $passenger1['passengerId'] = $p['passengerId'];
            } elseif ($p['name'] == $name2) {
                $passenger2['passengerId'] = $p['passengerId'];
            }
        }
        //$this->show($passengerId1.' '.$passengerId2);

        $newName1 = $this->randomString(12);
        $newName2 = $this->randomString(12);

        $passenger1['name'] = $newName1;
        $passenger2['name'] = $newName2;

        $passengers = ['passengers' => [$passenger1, $passenger2]];
        $requestPassengers = [$idRequest, $passengers];
        $response = $this->runApp('POST', '/setpassenger', $requestPassengers);
        $json = json_decode($response->getBody(), true);

        //$this->show($requestPassengers);
        //$this->show($json);
        $this->assertEquals(200, $response->getStatusCode());
        $jsonString = json_encode($json);
        $this->assertNotContains($name1, $jsonString);
        $this->assertNotContains($name2, $jsonString);
        $this->assertContains($newName1, $jsonString);
        $this->assertContains($newName2, $jsonString);
    }
}
