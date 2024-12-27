<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * Model representing a Marketplaces product listing.
 */
class Listing extends AbstractModel
{
    /** @var string|null */
    protected $listingId;

    /** @var string */
    protected $channelType = '';

    /** @var bool */
    protected $isPublished = false;

    /** @var string */
    protected $link = '';

    /**
     * Gets the unique ID of the listing.
     *
     * @return string|null
     */
    public function getListingId() : ?string
    {
        return $this->listingId;
    }

    /**
     * Gets the listing channel type (e.g. Amazon).
     *
     * @return string
     */
    public function getChannelType() : string
    {
        return $this->channelType;
    }

    /**
     * Returns true if the listing is published or false if it is a draft.
     *
     * @return bool
     */
    public function isPublished() : bool
    {
        return $this->isPublished;
    }

    /**
     * Gets the listing link.
     *
     * @return string
     */
    public function getLink() : string
    {
        return $this->link;
    }

    /**
     * Sets the listing ID.
     *
     * @param string $value
     * @return $this
     */
    public function setListingId(string $value) : Listing
    {
        $this->listingId = $value;

        return $this;
    }

    /**
     * Sets the listing channel type.
     *
     * @param string $channelType
     * @return $this
     */
    public function setChannelType(string $channelType) : Listing
    {
        $this->channelType = $channelType;

        return $this;
    }

    /**
     * Sets if the listing is published or a draft.
     *
     * @param bool $isPublished
     * @return $this
     */
    public function setIsPublished(bool $isPublished) : Listing
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    /**
     * Sets the listing link.
     *
     * @param string $link
     * @return $this
     */
    public function setLink(string $link) : Listing
    {
        $this->link = $link;

        return $this;
    }
}
