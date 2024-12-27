<?php

namespace GoDaddy\WordPress\MWC\Core\Channels\Traits;

/**
 * Traits for models that have an originating channel.
 */
trait HasOriginatingChannelTrait
{
    /** @var string|null Channel unique identifier */
    protected $originatingChannelId;

    /**
     * Gets the originating channel unique identifier.
     *
     * @return string|null
     */
    public function getOriginatingChannelId() : ?string
    {
        return $this->originatingChannelId;
    }

    /**
     * Sets the originating channel ID.
     *
     * @param string $value
     * @return $this
     */
    public function setOriginatingChannelId(string $value) : self
    {
        $this->originatingChannelId = $value;

        return $this;
    }
}
