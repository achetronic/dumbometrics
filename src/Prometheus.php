<?php
declare( strict_types = 1 );

namespace Achetronic\Dumbometrics;

use \Achetronic\Dumbometrics\Contract\Metrics;

# Prometheus related dependencies
# REF: https://github.com/promphp/prometheus_client_php
# REF: https://prometheus.io/docs/concepts/metric_types/
use \Prometheus\CollectorRegistry;
use \Prometheus\Storage\InMemory;
use \Prometheus\RenderTextFormat;

# Cache related dependencies
# Ref: https://github.com/matthiasmullie/scrapbook/tree/1.5.1#filesystem
# Ref: https://github.com/matthiasmullie/scrapbook/tree/1.5.1#apcu
use \League\Flysystem\Local\LocalFilesystemAdapter;
use \League\Flysystem\Filesystem;

use \MatthiasMullie\Scrapbook\Adapters\Flysystem;
use \MatthiasMullie\Scrapbook\Adapters\Apc;
use \MatthiasMullie\Scrapbook\Psr6\Pool;

//

final class Prometheus implements Metrics
{
    const DEFAULT_METRICS_NAMESPACE = 'dumbometrics';
    const DEFAULT_CACHE_BACKEND = 'fs';

    /**
     * Pointer to the cache pool
     */
    private $cachePool;

    /**
     * The namespace for the metrics into Prometheus
     */
    private $namespace;

    /**
     * InMemory object stored into (and recovered from) cache
     * Prometheus' Collector needs an adapter used as realtime data exchange point.
     * This is exactly that, for being stored into memory
     */
    private $inMemoryData;

    /**
     * Prometheus Client's abstract registry where aggregate the metrics
     */
    private $collectorRegistry;

    /**
     *
     */
    public function __Construct()
    {
        $namespace = getenv('DUMBOMETRICS_METRICS_NAMESPACE', true) ?: self::DEFAULT_METRICS_NAMESPACE;
        $this->setNamespace(strtolower($namespace));

        $this->setCachePool();
        $this->recoverInMemoryFromCache();
        $this->setCollectorRegistry();
    }

    /**
     * Craft a cache pool to store data into filesystem
     * Stores it into this object
     *
     * @return void
     */
    private function setCachePool()
    {
        $cacheBackend = getenv('DUMBOMETRICS_CACHE_BACKEND', true) ?: self::DEFAULT_CACHE_BACKEND;

        // TODO implement more persistent backends
        if ($cacheBackend == "placeholder") {
            goto createCachePool;
        }

        // Fall into default option
        syslog(LOG_INFO, 'Filesystem backend selected');

        // Create a filesystem from Fly-system
        $adapter = new LocalFilesystemAdapter(
            sys_get_temp_dir() . '/achetronic/dumbometrics/',
            null,
            LOCK_EX);

        $filesystem = new Filesystem($adapter);

        // Create Scrapbook KeyValueStore object
        $cache = new Flysystem($filesystem);

        createCachePool:
        // Create Pool object (PSR-6) from Scrapbook KeyValueStore object
        $this->cachePool = new Pool($cache);
    }

    /**
     * Recover Prometheus' InMemory object from cache.
     * Create a new InMemory object when it does not exist.
     * Stores it into the object
     *
     * @return void
     */
    private function recoverInMemoryFromCache() // TODO Improve the naming for this process
    {
        // No metrics object on cache, generate a new one
        if ( !$this->cachePool->hasItem('metrics') ) {
            $item = $this->cachePool->getItem('metrics')->set(new InMemory());
            $this->cachePool->save($item);
        }

        // Get metrics object from cache
        $this->inMemoryObject = $this->cachePool->getItem('metrics')->get();
    }

    /**
     * Craft a Prometheus Collector Registry.
     * Stores it into the object
     *
     * @return void
     */
    private function setCollectorRegistry()
    {
        $this->collectorRegistry  = new CollectorRegistry($this->inMemoryObject);
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
     * Sync cache with memory content
     *
     * @return void
     */
    private function syncCache()
    {
       $item = $this->cachePool->getItem('metrics')->set($this->inMemoryObject);
       $this->cachePool->save($item);
    }

    /**
     * Delete all the metrics
     *
     * @return bool
     */
    public function flush()
    {
        $item = $this->cachePool->getItem('metrics')->set(new InMemory());
        $this->cachePool->save($item);

        # Point Prometheus to the new pool's item
        $this->recoverInMemoryFromCache();
        $this->setCollectorRegistry();
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
        $counter = $this->collectorRegistry->registerCounter($this->namespace, $name, $description, $labels);

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
       $counter = $this->collectorRegistry->getCounter($this->namespace, $name);

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
       $counter = $this->collectorRegistry->getOrRegisterCounter($this->namespace, $name, $description, $labels);

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
        $counter = $this->collectorRegistry->getCounter($this->namespace, $name);
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
        $counter = $this->collectorRegistry->registerGauge($this->namespace, $name, $description, $labels);

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
       $counter = $this->collectorRegistry->getGauge($this->namespace, $name, $description, $labels);

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
       $counter = $this->collectorRegistry->getOrRegisterGauge($this->namespace, $name, $description, $labels);

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
        $counter = $this->collectorRegistry->getGauge($this->namespace, $name);
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
        $result = $renderer->render($this->collectorRegistry->getMetricFamilySamples());

        return $result;
     }
}
