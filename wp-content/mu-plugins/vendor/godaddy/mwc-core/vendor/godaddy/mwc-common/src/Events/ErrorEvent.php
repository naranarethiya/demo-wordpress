<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Generic error event.
 */
class ErrorEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var array<mixed> */
    protected $errorData = [];

    /** @var array<mixed> */
    protected $resourceData = [];

    /**
     * Error event constructor.
     *
     * @param string $resource
     * @param string $action
     * @param string $errorMessage
     */
    public function __construct(string $resource, string $action, string $errorMessage = '')
    {
        $this->resource = $resource;
        $this->action = $action;
        $this->errorData['message'] = $errorMessage;
    }

    /**
     * Sets additional error data.
     *
     * @param array<mixed> $errorData
     * @return ErrorEvent
     * @throws Exception
     */
    public function setErrorData(array $errorData) : ErrorEvent
    {
        $this->errorData = ArrayHelper::combine($this->errorData, $errorData);

        return $this;
    }

    /**
     * Sets the resource data.
     *
     * @param array<mixed> $resourceData
     * @return ErrorEvent
     */
    public function setResourceData(array $resourceData) : ErrorEvent
    {
        $this->resourceData = $resourceData;

        return $this;
    }

    /**
     * Gets the error data.
     *
     * @return array<mixed>
     */
    public function getErrorData() : array
    {
        return $this->errorData;
    }

    /**
     * Gets the resource data.
     *
     * @return array<mixed>
     */
    public function getResourceData() : array
    {
        return $this->resourceData;
    }

    /**
     * Gets the error message.
     *
     * @return string
     */
    public function getErrorMessage() : string
    {
        return $this->errorData['message'] ?? '';
    }

    /**
     * Builds the default event data.
     *
     * @return array<string, mixed>
     */
    public function buildInitialData() : array
    {
        return [
            'resource' => $this->resourceData,
            'error'    => $this->errorData,
        ];
    }
}
