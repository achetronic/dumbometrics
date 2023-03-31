<?php

declare( strict_types = 1 );

namespace Achetronic\Dumbometrics;

use \React\Http\Server as HttpServer;
use \React\Socket\Server as SocketServer;
use \React\Http\Message\Response as HttpResponse;
use \Psr\Http\Message\ServerRequestInterface as HttpRequest;

use \Achetronic\Dumbometrics\Contract\Server;
use \Achetronic\Dumbometrics\Prometheus;

//

final class Webserver implements Server
{
    const DEFAULT_METRICS_IP = '0.0.0.0';
    const DEFAULT_METRICS_PORT = '9090';

    protected $ip;

    protected $port;

    public $initCallback;

    public $requestInitCallback;

    public $requestFinalCallback;

    /**
     *
     *
     */
    public function __construct()
    {
        $this->setIp( getenv('DUMBOMETRICS_METRICS_IP', true) ?: self::DEFAULT_METRICS_IP );
        $this->setPort( getenv('DUMBOMETRICS_METRICS_PORT', true) ?: self::DEFAULT_METRICS_PORT );

        $this->initCallback = null;
        $this->requestInitCallback = null;
        $this->requestFinalCallback = null;
    }

    /**
     * Set the IP to bind the webserver
     *
     * @param string $value The value of the ip
     *
     * @return void
     */
    public function setIp (string $value): void
    {
        $this->ip = $value;
    }

    /**
     * Get the IP value of the webserver
     *
     * @return string
     */
    public function getIp (): ?string
    {
        return $this->ip;
    }

    /**
     * Set the port to bind the webserver
     *
     * @param string $value The value of the port
     *
     * @return void
     */
    public function setPort (string $value): void
    {
        $this->port = $value;
    }

    /**
     * Get the port value of the webserver
     *
     * @return string
     */
    public function getPort (): ?string
    {
        return $this->port;
    }

    /**
     * Set the initial callback executed on server start
     *
     * @param callable $callback
     * @return void
     */
    public function setInitCallback (callable $callback): void
    {
        $this->initCallback = $callback;
    }

    /**
     * Set the initial callback executed on each request
     *
     * @param callable $callback
     * @return void
     */
    public function setRequestInitCallback (callable $callback): void
    {
        $this->requestInitCallback = $callback;
    }

    /**
     * Set the final callback executed on each request
     *
     * @param callable $callback
     * @return void
     */
    public function setRequestFinalCallback (callable $callback): void
    {
        $this->requestFinalCallback = $callback;
    }

    /**
     * Start the web server
     *
     * @return void
     */
    public function loop (): void
    {
        $initCallback = $this->initCallback;
        $requestInitCallback = $this->requestInitCallback;
        $requestFinalCallback = $this->requestFinalCallback;

        //$metrics = new Prometheus;
        //var_dump($metrics);

        $server = new HttpServer(function (HttpRequest $request) use ($requestInitCallback, $requestFinalCallback) {

            // Execute init callback on each request
            if( !empty($requestInitCallback) ){
                $requestInitCallback();
            }

            $path = $request->getUri()->getPath();
            $method = $request->getMethod();

            if ($path === '/metrics') {
                if ($method === 'GET') {
                    $metrics = new Prometheus;
                    return HttpResponse::plaintext($metrics->renderMetrics());
                }
            }

            if ($path === '/metrics/flush') {
                if ($method === 'GET') {
                    $metrics = new Prometheus;
                    return HttpResponse::plaintext($metrics->flush());
                }
            }

            // Execute final callback on each request
            if( !empty($requestFinalCallback) ){
                $requestFinalCallback();
            }

            return HttpResponse::plaintext('Not found');
        });

        $socket = new SocketServer($this->getIp().':'.$this->getPort());
        $server->listen($socket);

        echo "Metrics server running at http://".$this->getIp().':'.$this->getPort() . PHP_EOL;
        echo "/metrics instrumented for Prometheus". PHP_EOL;

        // Execute init callback when server starts
        if( !empty($initCallback) ){
            $initCallback();
        }
    }
}
