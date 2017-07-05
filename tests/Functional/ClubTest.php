<?php

namespace Tests\Functional;

class ClubTest extends BaseTestCase
{
    public function testRegisterClubsMandatoryFields()
    {
        // identifier is mandatory
        $response = $this->registerClub(null, null, $this->randomString(20), $this->randomString(10));
        $this->assertEquals(406, $response->getStatusCode());
    }

    /**
     * Test that we can register clubs.
     * With Curl, use:
     * curl -H 'Content-Type: application/json' -X POST -d '[{"identifier":"n1ik6cK53xLOUTayA0WqsZIMPCHR8jS2rBQegFuJhbE4vVpzwY7oDmG9ltXdNf"},{"clubs":[{"name":"St Sever","password":"Reunion"}]}]' http://localhost:8081/club.
     */
    public function testRegisterOneClub()
    {
        $clubName = 'St Sever';
        $password = self::randomString(10);
        $identifier = self::registerNewDevice();

        $response = $this->registerClub($identifier, null, $clubName, $password);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        //$this->show($json);

        $this->assertEquals($clubName, $json[0]['name']);
        $this->assertNotEmpty($json[0]['clubId']);
        $this->assertGreaterThanOrEqual(10000, $json[0]['clubId']);
        $this->assertEquals(0, $json[0]['passengers']);
    }

    // Depends on testRegisterOneClub
    public function testRegisterOneClubWithWrongAndGoodPassword()
    {
        $clubName = 'St Sever';
        $password = self::randomString(10);
        $identifier = self::registerNewDevice();

        // Create club
        $response = $this->registerClub($identifier, null, $clubName, $password);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        //$this->show($json);
        $clubId = $json[0]['clubId'];
        $this->assertNotEmpty($clubId);

        $response = $this->registerClub($identifier, $clubId, null, $password);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        //$this->show($json);
        $this->assertEquals($clubName, $json[0]['name']);
        $this->assertEquals($clubId, $clubId);

        // Request club with wrong password
        $response = $this->registerClub($identifier, $clubId, null, 'ReunionBADPASSWORD');
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        //$this->show($json);
        $this->assertEmpty($json[0]);
    }

/*    public function testTwoDeviceRegisterSameClub()
    {
        $clubName = 'Super cars';
        $password = self::randomString(10);

        $identifier1 = self::registerNewDevice();
        $identifier2 = self::registerNewDevice();

        // set user1 2 passengers and 1 vehicule
        // set user2 1 passenger and 1 vehicule

        // Device 1 create a club
        $response1 = $this->registerClub($identifier1, null, $clubName, 'Cars7');
        $json1 = json_decode($response1->getBody(), true);
        $clubId = $json1[0]['clubId'];
        $this->assertGreaterThanOrEqual(10000, $clubId);
        $this->assertEquals(2, $json1[0]['passengers']);
        $this->assertEquals(1, $json1[0]['vehicules']);

        // Device 2 joins the club
        $response2 = $this->registerClub($identifier2, $clubId, null, 'Cars7');
        $json2 = json_decode($response2->getBody(), true);
        $this->show($json2);
        $this->assertEquals(3, $json1[0]['passengers']);
        $this->assertEquals(1, $json1[0]['vehicules']);
    }*/

    public function registerClub($identifier, $clubId, $clubName, $password)
    {
        $idRequest = ['identifier' => $identifier];
        $club = ['clubId' => $clubId, 'name' => $clubName, 'password' => $password];
        $clubs = ['clubs' => [$club]];
        $requestClubs = [$idRequest, $clubs];
        //$this->show($requestClubs);

        return $this->runApp('POST', '/club', $requestClubs);
    }
}
