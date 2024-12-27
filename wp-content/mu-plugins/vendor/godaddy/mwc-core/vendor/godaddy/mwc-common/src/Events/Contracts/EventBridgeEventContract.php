<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Contracts;

use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Event bridge contract.
 *
 * @see IsEventBridgeEventTrait when implementing some of the interface methods below
 */
interface EventBridgeEventContract extends EventContract
{
    /**
     * Gets the name of the resource for the current event.
     *
     * @return string
     */
    public function getResource() : string;

    /**
     * Gets the name of the action for the current event.
     *
     * @return string
     */
    public function getAction() : string;

    /**
     * Gets the data for the current event.
     *
     * @return array<mixed>
     */
    public function getData() : array;

    /**
     * Sets the data for the current event.
     *
     * @param array<mixed> $data
     * @return $this
     */
    public function setData(array $data) : EventBridgeEventContract;

    /**
     * Sets the event context.
     *
     * The context of an event can be anything to identify why it was broadcast. This is an optional property, not
     * required to be set. Examples of context: 'sync' - meaning that the event was sent during a sync operation, 'test'
     * - meaning that the event was sent as a result of a test, etc.
     *
     * @param string $value
     * @return $this
     */
    public function setContext(string $value) : EventBridgeEventContract;

    /**
     * Gets the event context.
     *
     * See {@see setContext} documentation to more information about the context.
     *
     * @return string
     */
    public function getContext() : string;
}
