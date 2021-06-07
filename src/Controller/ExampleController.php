<?php

namespace Achetronic\Dumbometrics\Controller;

use \Achetronic\Dumbometrics\Implementation\Prometheus;

final class ExampleController
{
    /**
     * @var \Achetronic\Dumbometrics\Implementation\Prometheus $metrics
     */
    private $metrics;

    /**
     *
     */
    public function __Construct()
    {
        $this->metrics = new Prometheus();
    }

    /**
     *
     */
    public function exampleMetricsAction(): string
    {
        $this->metrics->registerCounter('some_example_counter', 'description or empty', ['label1', 'label2']);
        $this->metrics->setCounter('some_example_counter', 1, ['a', 'b']);

        $this->metrics->registerGauge('some_example_gauge', 'description or empty', ['label1', 'label2']);
        $this->metrics->setGauge('some_example_gauge', 10, ['a', 'b']);

        return "done";
    }

    /**
         *
         */
    public function exampleFlushAction(): string
    {
        $this->metrics->flush();
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
