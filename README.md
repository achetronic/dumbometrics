# Dumbometrics

## Description
Package that stores metrics on cache and exposes an endpoint with them 
to allow Prometheus to scrape them

## How it works

### 1. Add the private repository to `composer.json` file

```json
"repositories": [
  {
    "type": "vcs",
    "url": "git@github.com:achetronic/dumbometrics.git"
  }
]
```

### 2. Install the package

```console
composer require achetronic/dumbometrics
```

### 3. (Optional) Flush the metrics

```php
use \Achetronic\Dumbometrics\Prometheus;

$metrics = new Prometheus();

$metrics->flush();
```

### 4. Store some metrics wherever you need it

```php
use \Achetronic\Dumbometrics\Prometheus;

$metrics = new Prometheus();

$this->metrics->registerCounter('some_example_counter', 'description or empty', ['label1', 'label2']);
$this->metrics->setCounter('some_example_counter', 1, ['value1', 'value2']);

$this->metrics->registerGauge('some_example_gauge', 'description or empty', ['label1', 'label2']);
$this->metrics->setGauge('some_example_gauge', 10, ['value1', 'value2']);
```

### 5. Expose your metrics

#### **Starting a ReactPHP server:**

The most recommended way is to launch a little [ReactPHP] webserver to expose the metrics. 
We have already managed this, so you only have to create another PHP file with the following code inside, 
and launch that process in parallel with your application.

```php
<?php

# content of metrics/webserver.php

require __DIR__ . '../vendor/autoload.php';

use \Achetronic\Dumbometrics\Webserver;

$server = new Webserver;

$server->loop();
```

```console
php metrics/webserver.php
```

#### **Creating a route on your framework:**

If you want to expose the metrics using a simple route of your framework (Laravel, Symfony, [FrameworkX]), just create
a route and render the metrics as the response:

```php
# Example for Laravel
use \Achetronic\Dumbometrics\Prometheus;
 
Route::get('/metrics', function () {
    return response((new Prometheus)->renderMetrics(), 200)
        ->header('Content-Type', 'text/plain');
});
```

### 6. (Optional) Start the server using callbacks

Our little webserver admit some callbacks when it is started, or in the beginning and final of a request. 
It's done this way to cover some use cases where 3rd party APM agents are not fully automated on starting/cutting 
the traces.

```php
use \Achetronic\Dumbometrics\Webserver;

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
```

## Environment vars

Some options can be configured by setting environment variables. 
You have all of them in the following table:

| Environment                      | Description                                                         |
|----------------------------------|---------------------------------------------------------------------|
| `DUMBOMETRICS_METRICS_IP`        | The IP server will be listening on. By default `0.0.0.0`            |
| `DUMBOMETRICS_METRICS_PORT`      | The port server will be listening on. By default `9090`             |
| `DUMBOMETRICS_METRICS_NAMESPACE` | The namespace where the server will work. By default `dumbometrics` |

## How to collaborate

We are open to external collaborations for this project: improvements, bugfixes, whatever.

For doing it, open an issue to discuss the need of the changes, then:

- Open an issue, to discuss what is needed and the reasons
- Fork the repository
- Make your changes to the code
- Open a PR and wait for review

## License

Copyright 2022.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

[//]: #

[ReactPHP]: <https://reactphp.org/>
[FrameworkX]: <https://framework-x.org/>
