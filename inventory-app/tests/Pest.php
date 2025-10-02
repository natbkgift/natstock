<?php

use Tests\TestCase;

$envPath = dirname(__DIR__).'/.env';
$testingAppKey = 'base64:TPsL1tiSnUiGKe11FbyhTSMp+04o291B9Xj78a39qQs=';

if (! file_exists($envPath)) {
    file_put_contents($envPath, "APP_KEY={$testingAppKey}".PHP_EOL);

    register_shutdown_function(static function () use ($envPath) {
        if (file_exists($envPath)) {
            unlink($envPath);
        }
    });
}

$_ENV['APP_KEY'] ??= $testingAppKey;
putenv('APP_KEY='.$_ENV['APP_KEY']);

uses(TestCase::class)->in('Feature');
