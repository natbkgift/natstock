<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => env('APP_KEY', $this->testingAppKey())]);
    }

    protected function testingAppKey(): string
    {
        return 'base64:TPsL1tiSnUiGKe11FbyhTSMp+04o291B9Xj78a39qQs=';
    }
}
