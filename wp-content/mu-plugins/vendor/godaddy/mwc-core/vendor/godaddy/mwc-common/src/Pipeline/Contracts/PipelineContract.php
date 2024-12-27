<?php

namespace GoDaddy\WordPress\MWC\Common\Pipeline\Contracts;

use Closure;
use Throwable;

interface PipelineContract
{
    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable) : PipelineContract;

    /**
     * Set the array of pipes.
     *
     * @param  array<object|class-string|Closure|callable|string> $pipes
     * @return $this
     */
    public function through(array $pipes) : PipelineContract;

    /**
     * Set the method to call on the stops.
     *
     * @param  string  $method
     * @return $this
     */
    public function via(string $method) : PipelineContract;

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  Closure  $destination
     * @return mixed
     * @throws Throwable
     */
    public function then(Closure $destination);
}
