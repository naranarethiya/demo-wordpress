<?php

namespace GoDaddy\WordPress\MWC\Common\Interceptors\Contracts;

/**
 * The contract for interceptor classes that enqueue scripts wrapped in a safe load technique.
 */
interface EnqueueScriptsInterceptorContract
{
    /**
     * Enqueues the JS file that defines the JS handler.
     */
    public function enqueueJs();

    /**
     * Returns true if the JS should be enqueued and false otherwise.
     *
     * Classes implementing this interface can implement this method to check the current screen, for instance.
     *
     * @return bool
     */
    public function shouldEnqueueJs() : bool;

    /**
     * Gets the handler instantiation JS wrapped in a safe load technique.
     *
     * @return string
     */
    public function getSafeHandlerJs() : string;

    /**
     * Gets the handler instantiation JS.
     *
     * @return string
     */
    public function getHandlerJs() : string;

    /**
     * Gets the name of the JS class for the handler.
     *
     * @return string
     */
    public function getJsHandlerClassName() : string;

    /**
     * Gets the name of the JS event triggered when the handler is loaded.
     *
     * @return string
     */
    public function getJsLoadedEventName() : string;

    /**
     * Gets the name of the JS variable that should hold an instance of the handler.
     *
     * @return string
     */
    public function getJsHandlerObjectName() : string;

    /**
     * Gets the args for the handler constructor.
     *
     * @return array
     */
    public function getJsHandlerArgs() : array;
}
