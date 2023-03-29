<?php

declare( strict_types = 1 );

namespace Achetronic\Dumbometrics\Implementation;

use \Achetronic\Dumbometrics\Contract\Metrics;

# REF: https://github.com/promphp/prometheus_client_php
# REF: https://prometheus.io/docs/concepts/metric_types/
use \Prometheus\CollectorRegistry;
use \Prometheus\Storage\InMemory;
use \Prometheus\RenderTextFormat;

# Ref:
use Apix\Cache;

final class Prometheus implements Metrics
{
    /**
     * The namespace for the metrics into Prometheus
     */
    private $namespace;

    /**
     * Cache pool
     */
    private $pool;

    /**
     * Prometheus Client's abstract registry where aggregate the metrics
     */
    private $registry;

    /**
     *
     */
    public function __Construct()
    {
        $this->setNamespace("dumbometrics");
        if( !empty($_SERVER['METRICS_NAMESPACE']) ) {
            $this->setNamespace(strtolower($_SERVER['METRICS_NAMESPACE']));
        }

        $this->setCachePool();
        $this->setMemory();
        $this->setRegistry();
    }

    /**
     * Craft a cache pool ready to store data
     * and stores it into the object
     *
     * @return void
     */
    private function setCachePool()
    {
        $options['directory'] = sys_get_temp_dir() . '/achetronic/dumbometrics/';
        $options['locking'] = true;

        $files_cache = new Cache\Files($options);
        $this->pool = Cache\Factory::getPool($files_cache);

        echo sys_get_temp_dir() . '/achetronic/dumbometrics/';
    }

    /**
     * Craft a swap memory to allow Prometheus Collector Registry store data.
     * Stores it into the object
     *
     * @return void
     */
    public function setMemory()
    {
        if ( !$this->pool->hasItem('metrics') ) {
            $item = $this->pool->getItem('metrics')->set(new InMemory());
            $this->pool->save($item);
        }
        $this->memory = $this->pool->getItem('metrics')->get();
    }

    /**
     * Craft a Prometheus Collector Registry.
     * Stores it into the object
     *
     * @return void
     */
    public function setRegistry()
    {
        $this->registry  = new CollectorRegistry($this->memory);
    }

    /**
     * Set a namespace where to store the metrics on Prometheus
     * Tries to take the name from env(METRICS_APP_NAME)
     *
     * @return void
     */
    private function setNamespace(string $namespace = '')
    {
        $this->namespace = strtolower($namespace);
    }

    /**
     * Delete all the metrics
     *
     * @return bool
     */
    public function flush()
    {
        $item = $this->pool->getItem('metrics')->set(new InMemory());
        $this->pool->save($item);

        # Point Prometheus to the new pool's item
        $this->setMemory();
        $this->setRegistry();
    }

    /**
     * Sync cache with memory content
     *
     * @return void
     */
    protected function syncCache()
    {
       $item = $this->pool->getItem('metrics')->set($this->memory);
       $this->pool->save($item);
    }

    /**
     * Register a counter to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  labels
     *
     * @return void
     */

    public function registerCounter(string $name, string $description = '', array $labels = [])
    {
        $counter = $this->registry->registerCounter($this->namespace, $name, $description, $labels);

        $this->syncCache();
    }

    /**
     * Get or register a counter to store information inside
     *
     * @param string $name         The name of the item to register
     *
     * @return void
     */
    public function getCounter(string $name)
    {
       $counter = $this->registry->getCounter($this->namespace, $name);

       $this->syncCache();
    }

    /**
     * Get or register a counter to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  labels
     *
     * @return void
     */
    public function getOrRegisterCounter(string $name, string $description = '', array $labels = [])
    {
       $counter = $this->registry->getOrRegisterCounter($this->namespace, $name, $description, $labels);

       $this->syncCache();
    }

    /**
     * Set a counter by aggregating the number of value
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param float  $value        The value of the item
     *
     * @return void
     */
    public function setCounter(string $name, float $value = 1, array $labels = [])
    {
        $counter = $this->registry->getCounter($this->namespace, $name);
        $counter->incBy($value, $labels);

        $this->syncCache();
    }

     /**
      * Register a gauge to store metrics
      *
      * @param string $name         The name of the item to register
      * @param string $description  A message to help to understand the item
      * @param float  $value        The value of the item
      *
      * @return void
      */
     public function registerGauge(string $name, string $description = '', array $labels = [])
    {
        $counter = $this->registry->registerGauge($this->namespace, $name, $description, $labels);

        $this->syncCache();
    }

    /**
     * Get a gauge to store information inside
     *
     * @param string $name    The name of the item to register
     *
     * @return void
     */
    public function getGauge(string $name)
    {
       $counter = $this->registry->getGauge($this->namespace, $name, $description, $labels);

       $this->syncCache();
    }

    /**
     * Get or register a gauge to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  labels
     *
     * @return void
     */
    public function getOrRegisterGauge(string $name, string $description = '', array $labels = [])
    {
       $counter = $this->registry->getOrRegisterGauge($this->namespace, $name, $description, $labels);

       $this->syncCache();
    }

     /**
      * Set a gauge to the desired value
      *
      * @param string $name         The name of the item to register
      * @param string $description  A message to help to understand the item
      * @param float  $value        The value of the item
      *
      * @return void
      */
    public function setGauge(string $name, float $value, array $labels = [])
    {
        $counter = $this->registry->getGauge($this->namespace, $name);
        $counter->set($value, $labels);

        $this->syncCache();
    }

     /**
      * Get a string with syntax ready for Prometheus
      *
      * @return string
      */
     public function renderMetrics(): string
     {
        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());
        return $result;
     }
}
