<?php

namespace Zinio\MetricsServer\Controller;

use Zinio\MetricsServer\Controller\PrometheusController;

final class ExampleController
{
    /**
     * @var \Zinio\MetricsServer\Controller\PrometheusController $metrics
     */
    private $metrics;

    /**
     *
     */
    public function __Construct()
    {
        $this->metrics = new PrometheusController();
    }

    /**
     *
     */
     public function exampleAction(): string
     {
        $this->metrics->flush();

        $this->metrics->registerCounter('some_example_counter', 'description or empty', ['label1', 'label2']);
        $this->metrics->setCounter('some_example_counter', 1, ['a', 'b']);

        $this->metrics->registerGauge('some_example_gauge', 'description or empty', ['label1', 'label2']);
        $this->metrics->setGauge('some_example_gauge', 10, ['a', 'b']);

        return "done";
     }

     /**
     *
     */
     public function exampleDelayAction(): string
     {
        sleep(5);
        return "done";
     }
}
