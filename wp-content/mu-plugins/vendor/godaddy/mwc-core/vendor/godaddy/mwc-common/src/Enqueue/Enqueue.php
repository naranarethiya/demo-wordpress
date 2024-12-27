<?php

namespace GoDaddy\WordPress\MWC\Common\Enqueue;

use GoDaddy\WordPress\MWC\Common\Enqueue\Types\EnqueueScript;
use GoDaddy\WordPress\MWC\Common\Enqueue\Types\EnqueueStyle;
use GoDaddy\WordPress\MWC\Common\Traits\HasConditionCheckTrait;

/**
 * Static asset enqueue handler.
 */
class Enqueue
{
    use HasConditionCheckTrait;

    /** @var string type of asset being enqueued */
    protected $enqueueType;

    /** @var string enqueued asset handle */
    protected $handle;

    /** @var string the location of the asset to be enqueued (e.g. URL or path) */
    protected $source = '';

    /** @var string[] optional enqueued item's dependencies (array of handles) */
    protected $dependencies = [];

    /** @var string|null version tag for enqueued asset */
    protected $version;

    /** @var bool whether item loading should be deferred (default false) */
    protected $deferred = false;

    /**
     * Sets the enqueue type.
     *
     * @param string $type the enqueue type
     * @return $this
     */
    protected function setType(string $type) : Enqueue
    {
        $this->enqueueType = $type;

        return $this;
    }

    /**
     * Gets the enqueue type.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->enqueueType ?: '';
    }

    /**
     * Creates a instance for enqueuing scripts.
     *
     * @return EnqueueScript
     */
    public static function script() : EnqueueScript
    {
        return new EnqueueScript();
    }

    /**
     * Creates a instance for enqueuing stylesheets.
     *
     * @return EnqueueStyle
     */
    public static function style() : EnqueueStyle
    {
        return new EnqueueStyle();
    }

    /**
     * Sets the enqueued asset handle.
     *
     * @param string $handle the asset handle
     * @return $this
     */
    public function setHandle(string $handle) : Enqueue
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * Sets the location of the asset to enqueue.
     *
     * @param string $source the asset's source location (e.g. URL or path)
     * @return $this
     */
    public function setSource(string $source) : Enqueue
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Sets dependencies for the asset being enqueued.
     *
     * @param string[] $dependencies array of asset identifiers (default none)
     * @return $this
     */
    public function setDependencies(array $dependencies = []) : Enqueue
    {
        $this->dependencies = $dependencies;

        return $this;
    }

    /**
     * Sets the file version.
     *
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version) : Enqueue
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Sets whether enqueue should be deferred.
     *
     * @param bool $defer whether to defer script loading
     * @return $this
     */
    public function setDeferred(bool $defer) : Enqueue
    {
        $this->deferred = $defer;

        return $this;
    }

    /**
     * Determines whether the asset should be enqueued based on the defined condition, if present.
     *
     * @return bool
     */
    protected function shouldEnqueue() : bool
    {
        return $this->conditionPasses();
    }
}
