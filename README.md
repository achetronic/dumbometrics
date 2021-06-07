# Dumbometrics

### Description
Package that stores metrics on cache and exposes an endpoint with them 
to allow Prometheus to scrape them

### How it works
1. Add the private repository to `composer.json` file
```text
"repositories": [
  {
    "type": "vcs",
    "url": "git@github.com:achetronic/dumbometrics.git"
  }
]
```

2. Install the package
```text
composer require achetronic/dumbometrics
```

3. (Optional) Flush the metrics
```php
use \Achetronic\Dumbometrics\Implementation\Prometheus;

$metrics = new Prometheus();

$metrics->flush();
```

4. Store some metrics wherever you need it
```php
use \Achetronic\Dumbometrics\Implementation\Prometheus;

$metrics = new Prometheus();

$this->metrics->registerCounter('some_example_counter', 'description or empty', ['label1', 'label2']);
$this->metrics->setCounter('some_example_counter', 1, ['value1', 'value2']);

$this->metrics->registerGauge('some_example_gauge', 'description or empty', ['label1', 'label2']);
$this->metrics->setGauge('some_example_gauge', 10, ['value1', 'value2']);

```

5. Start the server 
```php
use \Achetronic\Dumbometrics\Implementation\Webserver;

$server = new Webserver;

$server->loop();
```

6. (Optional) Start the server using callbacks
```php
use \Achetronic\Dumbometrics\Implementation\Webserver;

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

### Environment vars
| Environment | Description |
|---|---|
| `METRICS_IP` | The IP server will be listening on. By default `0.0.0.0` |
| `METRICS_PORT` | The port server will be listening on. By default `9090` |
| `METRICS_NAMESPACE` | The namespace where the server will work. By default `dumbometrics` |