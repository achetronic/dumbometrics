<?php

declare( strict_types = 1 );

namespace Achetronic\Dumbometrics\Contract;

interface Metrics
{
    /**
     * Delete all the metrics from implemented storage
     *
     * @return bool
     */
    public function flush();

    /**
     * Register a counter to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  $labels
     *
     * @return void
     */
    public function registerCounter(string $name, string $description, array $labels);

    /**
     * Get an existant counter to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  $labels
     *
     * @return void
     */
    public function getCounter(string $name);

    /**
     * Get or register a counter to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  $labels
     *
     * @return void
     */
    public function getOrRegisterCounter(string $name, string $description, array $labels);

    /**
     * Set a counter by aggregating the number of value
     *
     * @param string $name        The name of the item to register
     * @param string $increment   Increment by this number
     * @param float  $labels
     *
     * @return void
     */
    public function setCounter(string $name, float $increment, array $labels);

     /**
      * Register a gauge to store metrics
      *
      * @param string $name          The name of the item to register
      * @param string $description   A message to help to understand the item
      * @param float  $labels
      *
      * @return void
      */
    public function registerGauge(string $name, string $description, array $labels);

    /**
     * Get an existant gauge to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  $labels
     *
     * @return void
     */
    public function getGauge(string $name);

    /**
     * Get or register a gauge to store information inside
     *
     * @param string $name         The name of the item to register
     * @param string $description  A message to help to understand the item
     * @param array  labels
     *
     * @return void
     */
    public function getOrRegisterGauge(string $name, string $description, array $labels);
    
     /**
      * Set a gauge to the desired value
      *
      * @param string $name         The name of the item to register
      * @param string $description  A message to help to understand the item
      * @param float  $value        The value of the item
      *
      * @return void
      */
    public function setGauge(string $name, float $value, array $labels = []);

     /**
      * Get metrics as string on implemented syntax
      *
      * @return string
      */
    public function renderMetrics(): string;
}
