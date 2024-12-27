<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

interface HasPageTokenContract
{
    /**
     * Set the page token.
     *
     * @param ?string $pageToken
     * @return $this
     */
    public function setPageToken(?string $pageToken);

    /**
     * Get the page token.
     *
     * @return ?string
     */
    public function getPageToken() : ?string;

    /**
     * Set the token direction.
     *
     * @param ?string $tokenDirection
     * @return $this
     */
    public function setTokenDirection(?string $tokenDirection);

    /**
     * Get the token direction.
     *
     * @return ?string
     */
    public function getTokenDirection() : ?string;
}
