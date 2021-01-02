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

        $result = ODS::reprocessRawData($data, 'xml');

        $this->assertNotEmpty($result);
    }

    public function testExtractDecisioningData()
    {
        $data = json_decode(file_get_contents(base_path() . '/tests/Feature/odsRawData.json'), true);

        $result = ODS::extractDecisioningData($data);

        $this->assertNotEmpty($result);
    }
}
