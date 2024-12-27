<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait for objects that have a relationship to some remote entity.
 */
trait HasRemoteResourceTrait
{
    /** @var string ID from a remote system */
    protected $remoteId;

    /** @var string parent ID from a remote system */
    protected $remoteParentId;

    /** @var string the source identifier */
    protected $source;

    /**
     * Gets the remote ID.
     *
     * @return string|null
     */
    public function getRemoteId()
    {
        return $this->remoteId;
    }

    /**
     * Gets the remote parent ID.
     *
     * @return string|null
     */
    public function getRemoteParentId()
    {
        return $this->remoteParentId;
    }

    /**
     * Gets the source.
     *
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Sets the remote ID.
     *
     * @param string $value
     * @return $this
     */
    public function setRemoteId(string $value)
    {
        $this->remoteId = $value;

        return $this;
    }

    /**
     * Sets the remote parent ID.
     *
     * @param string $value
     * @return $this
     */
    public function setRemoteParentId(string $value)
    {
        $this->remoteParentId = $value;

        return $this;
    }

    /**
     * Sets the source.
     *
     * @param string $value
     * @return $this
     */
    public function setSource(string $value)
    {
        $this->source = $value;

        return $this;
    }
}
