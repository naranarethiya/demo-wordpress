<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Traits;

/**
 * Trait for models that handle GoDaddy Marketplaces data.
 */
trait HasMarketplacesDataTrait
{
    /** @var string|null Marketplaces channel unique identifier */
    protected $marketplacesChannelUuid;

    /** @var string|null Marketplaces channel name */
    protected $marketplacesChannelName;

    /** @var string|null Marketplaces channel type slug (e.g. 'amazon', 'etsy') */
    protected $marketplacesChannelType;

    /** @var string|null Marketplaces display order number */
    protected $marketplacesDisplayOrderNumber;

    /** @var string|null Marketplaces internal order number */
    protected $marketplacesInternalOrderNumber;

    /** @var string|null Marketplaces channel order reference */
    protected $marketplacesChannelOrderReference;

    /** @var string|null Marketplaces status */
    protected $marketplacesStatus;

    /**
     * Gets the Marketplaces channel UUID.
     *
     * @return string|null
     */
    public function getMarketplacesChannelUuid() : ?string
    {
        return $this->marketplacesChannelUuid;
    }

    /**
     * Gets the Marketplaces channel name.
     *
     * @return string|null
     */
    public function getMarketplacesChannelName() : ?string
    {
        return $this->marketplacesChannelName;
    }

    /**
     * Gets the Marketplaces channel type.
     *
     * @return string|null
     */
    public function getMarketplacesChannelType() : ?string
    {
        return $this->marketplacesChannelType;
    }

    /**
     * Gets the Marketplaces display order number.
     *
     * @return string|null
     */
    public function getMarketplacesDisplayOrderNumber() : ?string
    {
        return $this->marketplacesDisplayOrderNumber;
    }

    /**
     * Gets the Marketplaces internal order number.
     *
     * @return string|null
     */
    public function getMarketplacesInternalOrderNumber() : ?string
    {
        return $this->marketplacesInternalOrderNumber;
    }

    /**
     * Gets the Marketplaces channel order reference.
     *
     * @return string|null
     */
    public function getMarketplacesChannelOrderReference() : ?string
    {
        return $this->marketplacesChannelOrderReference;
    }

    /**
     * Gets the Marketplaces status.
     *
     * @return string|null
     */
    public function getMarketplacesStatus() : ?string
    {
        return $this->marketplacesStatus;
    }

    /**
     * Sets the Marketplaces channel UUID.
     *
     * @param string $value
     * @return $this
     */
    public function setMarketplacesChannelUuid(string $value) : self
    {
        $this->marketplacesChannelUuid = $value;

        return $this;
    }

    /**
     * Sets the Marketplaces channel name.
     *
     * @param string $value
     * @return $this
     */
    public function setMarketplacesChannelName(string $value) : self
    {
        $this->marketplacesChannelName = $value;

        return $this;
    }

    /**
     * Sets the Marketplaces channel type.
     *
     * @param string $value
     * @return $this
     */
    public function setMarketplacesChannelType(string $value) : self
    {
        $this->marketplacesChannelType = $value;

        return $this;
    }

    /**
     * Sets the marketplaces display order number.
     *
     * @param string $value
     * @return $this
     */
    public function setMarketplacesDisplayOrderNumber(string $value) : self
    {
        $this->marketplacesDisplayOrderNumber = $value;

        return $this;
    }

    /**
     * Sets the marketplaces internal order number.
     *
     * @param string $value
     * @return $this
     */
    public function setMarketplacesInternalOrderNumber(string $value) : self
    {
        $this->marketplacesInternalOrderNumber = $value;

        return $this;
    }

    /**
     * Sets the Marketplaces channel order reference.
     *
     * @param string $value
     * @return $this
     */
    public function setMarketplacesChannelOrderReference(string $value) : self
    {
        $this->marketplacesChannelOrderReference = $value;

        return $this;
    }

    /**
     * Sets the Marketplaces status.
     *
     * @param string $value
     * @return $this
     */
    public function setMarketplacesStatus(string $value) : self
    {
        $this->marketplacesStatus = $value;

        return $this;
    }

    /**
     * Determines if the order was placed using Marketplaces.
     *
     * @return bool
     */
    public function hasMarketplacesChannel() : bool
    {
        return ! empty($this->getMarketplacesChannelUuid());
    }
}
