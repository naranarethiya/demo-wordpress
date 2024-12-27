<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits;

trait HasPageTokenTrait
{
    /** @var ?string the page token */
    protected ?string $pageToken = null;

    /** @var ?string the token direction */
    protected ?string $tokenDirection = null;

    /**
     * {@inheritDoc}
     */
    public function setPageToken(?string $pageToken)
    {
        $this->pageToken = $pageToken;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPageToken() : ?string
    {
        return $this->pageToken;
    }

    /**
     * {@inheritDoc}
     */
    public function setTokenDirection(?string $tokenDirection)
    {
        $this->tokenDirection = $tokenDirection;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenDirection() : ?string
    {
        return $this->tokenDirection;
    }
}
