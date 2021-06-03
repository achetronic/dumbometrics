<?php

require __DIR__ . '/../vendor/autoload.php';

use \Achetronic\Dumbometrics\Controller\WebserverController as Webserver;

$server = new Webserver;

$date = new DateTime();
$timestamp = $date->format('Y-m-d H:i:s');

$server->setInitCallback(function () use ($timestamp){
    // End the transaction because of the infinite loop
    if( extension_loaded('newrelic') ){
        echo "[{$timestamp}] Stopping NewRelic auto-started transaction" . PHP_EOL;
        newrelic_end_transaction();
    }
});

$server->setRequestInitCallback(function () use ($timestamp){
    // Start recording data for New Relic
    if( extension_loaded('newrelic') ){
        echo "[{$timestamp}] Starting NewRelic transaction" . PHP_EOL;
        newrelic_start_transaction(ini_get("newrelic.appname"));
    }
});

$server->setRequestFinalCallback(function () use ($timestamp){
    // Stop recording data, send them to New Relic
    if( extension_loaded('newrelic') ){
        echo "[{$timestamp}] Stopping NewRelic transaction" . PHP_EOL;
        newrelic_end_transaction();
    }
});

$server->loop();
