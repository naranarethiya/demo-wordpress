<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

use DateTimeInterface;

interface HasCreatedAtContract
{
    /**
     * Gets the date when the entity was created.
     *
     * @return DateTimeInterface|null
     */
    public function getCreatedAt() : ?DateTimeInterface;

    /**
     * Sets the date when the entity was created.
     *
     * @param DateTimeInterface|null $value
     * @return $this
     */
    public function setCreatedAt(?DateTimeInterface $value);
}
