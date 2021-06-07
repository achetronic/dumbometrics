<?php

declare( strict_types = 1 );

namespace Achetronic\Dumbometrics\Contract;

interface Server
{
    /**
     * Set the IP to bind the webserver
     *
     * @param string $value The value of the ip
     * @return void
     */
    public function setIp (string $value): void;


    /**
     * Get the IP value of the webserver
     * 
     * @return string
     */
    public function getIp (): ?string;

    /**
     * Set the port to bind the webserver
     *
     * @param string $value The value of the port
     * @return void
     */
    public function setPort (string $value): void;

    /**
     * Get the port value of the webserver
     *
     * @return string
     */
    public function getPort (): ?string;

    /**
     * Set the initial callback executed on server start
     *
     * @param callable $callback
     * @return void
     */
    public function setInitCallback (callable $callback): void;

    /**
     * Set the initial callback executed on each request
     *
     * @param callable $callback
     * @return void
     */
    public function setRequestInitCallback (callable $callback): void;

    /**
     * Set the final callback executed on each request
     *
     * @param callable $callback
     * @return void
     */
    public function setRequestFinalCallback (callable $callback): void;

    /**
     * Start the web server
     *
     * @return void
     */
    public function loop (): void;
}
