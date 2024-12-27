<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

use DateTimeInterface;

interface HasUpdatedAtContract
{
    /**
     * Gets the date when the entity was updated.
     *
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt() : ?DateTimeInterface;

    /**
     * Sets the date when the entity was updated.
     *
     * @param DateTimeInterface|null $value
     * @return $this
     */
    public function setUpdatedAt(?DateTimeInterface $value);
}
