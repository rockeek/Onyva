<?php

namespace Tests\Functional;

class TravelTest extends BaseTestCase
{
    /**
     * Test that we don't get info without correct identifer.
     */
    public function testCheckTravelSecurity()
    {
        // No identifier at all
        $response = $this->runApp('POST', '/gettravel');
        $this->assertEquals(406, $response->getStatusCode());

        // Non existent identifier
        $identifier = 'idontexist';
        $clubId = 10001;
        $clubPassword = 'boing44';
        $idRequest = ['identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword];
        $response = $this->runApp('POST', '/gettravel', $idRequest);
        $this->assertEquals(406, $response->getStatusCode());
    }

    /**
     * Test that we can search all travels.
     */
    public function testGetAllTravels()
    {
        $identifier = 'aaabbb';
        $clubId = 10001;
        $clubPassword = 'boing44';
        $idRequest = ['identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword];
        $response = $this->runApp('POST', '/gettravel', $idRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('FRIDAY', (string) $response->getBody());
        $this->assertContains('MONDAY', (string) $response->getBody());
    }

    /**
     * Test that we can search a travel on Friday.
     */
    public function testSearchTravelFriday()
    {
        $identifier = 'aaabbb';
        $clubId = 10001;
        $clubPassword = 'boing44';
        $idRequest = ['identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword];
        $response = $this->runApp('POST', '/gettravel/friday', $idRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('FRIDAY', (string) $response->getBody());
        $this->assertNotContains('MONDAY', (string) $response->getBody());
    }

    /**
     * Test that we can search a travel on Friday at 18:15.
     */
    public function testSearchTravelFriday1815()
    {
        $identifier = 'aaabbb';
        $clubId = 10001;
        $clubPassword = 'boing44';
        $idRequest = ['identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword];
        $response = $this->runApp('POST', '/gettravel/friday/18:15', $idRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('FRIDAY', (string) $response->getBody());
        $this->assertContains('18:15:00', (string) $response->getBody());
        $this->assertNotContains('MONDAY', (string) $response->getBody());
    }

    /**
     * Test that we can't update without correct identifier.
     */
    public function testCannotUpdateWithWrongIdentifier()
    {
        // No identifier at all
        $clubId = 10001;
        $clubPassword = 'boing44';
        $requestBody = array('clubId' => $clubId, 'clubPassword' => $clubPassword, 'travels' => [array('date' => '2015-10-10', 'time' => '10:10:00')]);
        // $this->show(json_encode($requestBody));

        $response = $this->runApp('POST', '/settravel', $requestBody);
        $this->assertEquals(406, $response->getStatusCode());

        // Non existent identifier
        $identifier = 'idontexist';
        $clubId = 10001;
        $clubPassword = 'boing44';

        $requestBody = array('identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword,
            'travels' => [array('date' => '2015-10-10', 'time' => '10:10:00')], );
        $response = $this->runApp('POST', '/settravel', $requestBody);
        $this->assertEquals(406, $response->getStatusCode());
    }

    /**
     * Test that we can't update a travel of another club.
     */
    public function testCannotUpdateATravelOfAnotherClub()
    {
        // Get TravelId of a travel of another club 10002
        $idRequest = ['identifier' => 'aaabbb', 'clubId' => 10002, 'clubPassword' => 'popo123'];
        $response = $this->runApp('POST', '/gettravel', $idRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        //$this->show($body);
        $travel = $body[0];
        $this->assertEquals('TUESDAY', $travel['day']);
        $this->assertEquals('08:10:00', $travel['time']);
        $travelId = $travel['travelId'];

        // In club 10001, try to modify travel of club 10002
        $requestBody = array('identifier' => 'aaabbb', 'clubId' => 10001, 'clubPassword' => 'boing44', 'travels' => [array('day' => 'WEDNESDAY')]); // we change travel to update
        //$this->show(json_encode($requestBody));

        $response = $this->runApp('POST', '/settravel', $requestBody);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        //$this->show($body);

        // Check the travel has not changed
        $idRequest = ['identifier' => 'aaabbb', 'clubId' => 10002, 'clubPassword' => 'popo123'];
        $response = $this->runApp('POST', '/gettravel', $idRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        //$this->show($body);
        $travel = $body[0];
        $this->assertEquals('TUESDAY', $travel['day']);
        $this->assertEquals('08:10:00', $travel['time']);
        $this->assertEquals($travelId, $travel['travelId']);
    }

    /**
     * Test that we can create new travel.
     */
    public function testCreateTravel()
    {
        $identifier = 'aaabbb';
        $clubId = 10001;
        $clubPassword = 'boing44';
        $idRequest = ['identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword];

        $travels = [array('day' => 'SUNDAY', 'time' => '12:12:00'), array('date' => '2016-10-30', 'time' => '12:13:00')];
        $requestBody = array('identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword, 'travels' => $travels);
        //$this->show(json_encode($requestBody));

        $response = $this->runApp('POST', '/settravel', $requestBody);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        $bodyCount = count($body);
        $created1 = $body[$bodyCount - 2];
        $created2 = $body[$bodyCount - 1];

        $this->assertEquals($travels[0]['day'], $created1['day']);
        $this->assertEquals($travels[0]['time'], $created1['time']);
        $this->assertEquals($travels[0]['date'], $created1['date']);

        $this->assertEquals($travels[1]['day'], $created2['day']);
        $this->assertEquals($travels[1]['time'], $created2['time']);
        $this->assertEquals($travels[1]['date'], $created2['date']);

        // Try to create a travel that already exists: none is created
        $travels = [array($travels['travels'][0])];
        $requestBody = array('identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword, 'travels' => $travels);
        $response = $this->runApp('POST', '/settravel', $requestBody);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals($bodyCount, count($body)); // Number of travels is the same

        // Create another different travel with date only
        $otherTravel = array('date' => '2012-12-12', 'time' => '12:12:00');
        $travels = [$otherTravel];
        $requestBody = array('identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword, 'travels' => $travels);
        $response = $this->runApp('POST', '/settravel', $requestBody);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals($bodyCount + 1, count($body));
        $this->assertEquals($otherTravel['day'], $body[count($body) - 1]['day']);
        $this->assertEquals($otherTravel['time'], $body[count($body) - 1]['time']);
        $bodyCount = count($body); // Update bodyCount

        // Create another different travel with day only
        $otherTravel = array('day' => 'TUESDAY', 'time' => '09:09:00');
        $travels = [$otherTravel];
        $requestBody = array('identifier' => $identifier, 'clubId' => $clubId, 'clubPassword' => $clubPassword, 'travels' => $travels);
        $response = $this->runApp('POST', '/settravel', $requestBody);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals($bodyCount + 1, count($body));
        $this->assertEquals($otherTravel['day'], $body[count($body) - 1]['day']);
        $this->assertEquals($otherTravel['time'], $body[count($body) - 1]['time']);
    }

    // TODO Can we test directly isTravelExist?

    // Test security: with wrong club password.

    // Test cannot modify travel of another club.
}
