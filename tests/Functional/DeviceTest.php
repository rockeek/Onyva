<?php

namespace Tests\Functional;

class DeviceTest extends BaseTestCase
{
    /**
     * Test that we can register new device.
     * With Curl, use:
     * curl -H 'Content-Type: application/json' -X POST -d '{"os": "Android", "identifier": "abc123", "version": "1"}' http://localhost:8081/device.
     */
    public function testRegisterNewDevice()
    {
        $os = 'Android';
        $version = '1';
        $identifier = $this->randomString(64);

        // Check the device does not exist in DB
        $query = $this->db->prepare('SELECT * FROM Device WHERE Identifier=:identifier;');
        $query->bindParam(':identifier', $identifier);
        $query->execute();
        $result = $query->fetch();

        $this->assertEmpty($result);

        // Register device
        $this->registerDevice($os, $version, $identifier);

        // Check that device now exists
        $this->assertDevice($identifier, $os, $version);

        // Check we can update the device now
        $os = 'iOS';
        $version = '2';

        $this->registerDevice($os, $version, $identifier);

        // Check that device now exists
        $this->assertDevice($identifier, $os, $version);
    }

    private function assertDevice($identifier, $os, $version)
    {
        $query = $this->db->prepare('SELECT * FROM Device WHERE Identifier=:identifier;');
        $query->bindParam(':identifier', $identifier);
        $query->execute();
        $result = $query->fetch();

        //fwrite(STDERR, print_r($result, true));
        $this->assertContains($os, $result['Os']);
        $this->assertContains($version, $result['Version']);
        $this->assertContains($identifier, $result['Identifier']);
    }

    public function registerDevice($os, $version, $identifier)
    {
        $data = ['os' => $os, 'version' => $version, 'identifier' => $identifier];
        $response = $this->runApp('POST', '/device', $data);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
