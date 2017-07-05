<?php

namespace Tests\Functional;

class DefaultTest extends BaseTestCase
{
    /**
     * Test that the index route returns a rendered response containing the text 'SlimFramework' but not a greeting.
     */
    public function testGetDefault()
    {
        $response = $this->runApp('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Welcome to Onyva', (string) $response->getBody());
        /*$this->assertNotContains('Hello', (string) $response->getBody());*/
    }

    /**
     * Test that the index route won't accept a post request.
     */
    public function testPostDefaultNotAllowed()
    {
        $response = $this->runApp('POST', '/', ['test']);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertContains('Method not allowed', (string) $response->getBody());
    }
}
