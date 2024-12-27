<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

trait HasStringRemoteIdentifierTrait
{
    /** @var string|null */
    protected $remoteId;

    /**
     * Get the remote ID.
     *
     * @return string|null
     */
    public function getRemoteId() : ?string
    {
        return $this->remoteId;
    }

    /**
     * Set the remote ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRemoteId(string $value)
    {
        $this->remoteId = $value;

        return $this;
    }
}
