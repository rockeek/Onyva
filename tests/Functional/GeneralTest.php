<?php

namespace Tests\Functional;

class GeneralTest extends BaseTestCase
{
    public function testOsRandom()
    {
        $availableOs = array('Android', 'iOS', 'Windows');
        for ($i = 0; $i < 7; ++$i) {
            $this->assertContains(self::randomOs(), $availableOs);
        }
    }
}
