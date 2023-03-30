<?php

declare( strict_types = 1 );

namespace Achetronic\Dumbometrics\Implementation;

use \React\Http\Server as HttpServer;
use \React\Socket\Server as SocketServer;
use \React\Http\Message\Response as HttpResponse;
use \Psr\Http\Message\ServerRequestInterface as HttpRequest;

use \Achetronic\Dumbometrics\Contract\Server;
use \Achetronic\Dumbometrics\Implementation\Prometheus;

final class Webserver implements Server
{
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
        $this->setIp('0.0.0.0');
        if( !empty($_SERVER['METRICS_IP']) ) {
            $this->setIp( $_SERVER['METRICS_IP'] );
        }

        $this->setPort('9090');
        if( !empty($_SERVER['METRICS_PORT']) ) {
            $this->setPort( $_SERVER['METRICS_PORT'] );
        }

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

        $server = new HttpServer(function (HttpRequest $request) use ($requestInitCallback, $requestFinalCallback) {

            // Execute init callback on each request
            if( !empty($requestInitCallback) ){
                $requestInitCallback();
            }

            $path = $request->getUri()->getPath();
            $method = $request->getMethod();
            $response = null;

            if ($path === '/metrics') {
                if ($method === 'GET') {
                    $response = new HttpResponse(
                        200,
                        ['Content-Type' => 'text/plain'],
                        (new Prometheus)->renderMetrics()
                    );
                }
            }

            if( empty($response) ){
                $response = new HttpResponse(404, ['Content-Type' => 'text/plain'],  'Not found');
            }

            // Execute final callback on each request
            if( !empty($requestFinalCallback) ){
                $requestFinalCallback();
            }

            return $response;
        });

        $socket = new SocketServer($this->getIp().':'.$this->getPort());
        $server->listen($socket);

        echo "Metrics server running at http://".$this->getIp().':'.$this->getPort() . PHP_EOL;
        echo "/metrics instrumented for Prometheus". PHP_EOL;
        echo "/example/metrics set some fake metrics". PHP_EOL;
        echo "/example/flush flush all metrics". PHP_EOL;
        echo "/example/delay set a fake delay". PHP_EOL;

        // Execute init callback when server starts
        if( !empty($initCallback) ){
            $initCallback();
        }

        $loop->run();
    }
}
