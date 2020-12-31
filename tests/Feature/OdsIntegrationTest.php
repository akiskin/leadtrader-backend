<?php

namespace Tests\Feature;

use App\Integration\ODS;
use Tests\TestCase;

class OdsIntegrationTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testReprocessData()
    {
        $data = file_get_contents(base_path() . '/tests/Feature/odsRawData.xml');

        $result = ODS::reprocessRawData($data);

        $this->assertNotEmpty($result);
    }
}
